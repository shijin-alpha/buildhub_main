<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Database connection
require_once '../../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if user is logged in and is a contractor
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contractor') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
    
    $contractor_id = $_SESSION['user_id'];
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['project_id']) || !isset($input['report_type']) || 
        !isset($input['start_date']) || !isset($input['end_date'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $project_id = intval($input['project_id']);
    $report_type = $input['report_type'];
    $start_date = $input['start_date'];
    $end_date = $input['end_date'];
    
    // Verify project belongs to contractor
    $projectStmt = $pdo->prepare("
        SELECT lr.*, h.first_name as homeowner_first_name, h.last_name as homeowner_last_name, h.email as homeowner_email,
               cls.contractor_id, cls.acknowledged_at
        FROM layout_requests lr
        LEFT JOIN users h ON lr.homeowner_id = h.id
        LEFT JOIN contractor_layout_sends cls ON lr.id = cls.layout_id AND cls.homeowner_id = lr.homeowner_id
        WHERE lr.id = ? AND cls.contractor_id = ? AND cls.acknowledged_at IS NOT NULL
    ");
    $projectStmt->execute([$project_id, $contractor_id]);
    $project = $projectStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found or not assigned to contractor']);
        exit;
    }
    
    // Get contractor information
    $contractorStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $contractorStmt->execute([$contractor_id]);
    $contractor = $contractorStmt->fetch(PDO::FETCH_ASSOC);
    
    // Generate comprehensive report data
    $reportData = generateReportData($pdo, $project_id, $contractor_id, $start_date, $end_date, $report_type);
    
    // Add project and contractor info
    $reportData['project'] = [
        'id' => $project['id'],
        'name' => $project['requirements'] ? substr($project['requirements'], 0, 50) . '...' : 'Construction Project',
        'homeowner_name' => trim($project['homeowner_first_name'] . ' ' . $project['homeowner_last_name']),
        'homeowner_email' => $project['homeowner_email'],
        'budget_range' => $project['budget_range'],
        'plot_size' => $project['plot_size'],
        'building_size' => $project['building_size']
    ];
    
    $reportData['contractor'] = [
        'id' => $contractor['id'],
        'name' => trim($contractor['first_name'] . ' ' . $contractor['last_name']),
        'email' => $contractor['email'],
        'phone' => $contractor['phone'] ?? ''
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $reportData
    ]);
    
} catch (Exception $e) {
    error_log("Generate progress report error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

function generateReportData($pdo, $project_id, $contractor_id, $start_date, $end_date, $report_type) {
    // Get daily progress updates
    $dailyUpdatesStmt = $pdo->prepare("
        SELECT * FROM daily_progress_updates 
        WHERE project_id = ? AND contractor_id = ? 
        AND update_date BETWEEN ? AND ?
        ORDER BY update_date ASC
    ");
    $dailyUpdatesStmt->execute([$project_id, $contractor_id, $start_date, $end_date]);
    $dailyUpdates = $dailyUpdatesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get labour data
    $labourStmt = $pdo->prepare("
        SELECT * FROM labour_tracking 
        WHERE project_id = ? AND contractor_id = ? 
        AND work_date BETWEEN ? AND ?
        ORDER BY work_date ASC
    ");
    $labourStmt->execute([$project_id, $contractor_id, $start_date, $end_date]);
    $labourData = $labourStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get geo photos
    $photosStmt = $pdo->prepare("
        SELECT * FROM geo_photos 
        WHERE project_id = ? AND contractor_id = ? 
        AND DATE(upload_timestamp) BETWEEN ? AND ?
        ORDER BY upload_timestamp DESC
        LIMIT 20
    ");
    $photosStmt->execute([$project_id, $contractor_id, $start_date, $end_date]);
    $photos = $photosStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate summary statistics
    $summary = calculateSummaryStats($dailyUpdates, $labourData, $photos);
    
    // Analyze labour data
    $labourAnalysis = analyzeLabourData($labourData);
    
    // Calculate costs
    $costs = calculateCosts($labourData, $dailyUpdates);
    
    // Extract materials used
    $materials = extractMaterials($dailyUpdates);
    
    // Generate quality metrics
    $quality = generateQualityMetrics($dailyUpdates, $labourData);
    
    // Generate recommendations
    $recommendations = generateRecommendations($summary, $quality, $report_type);
    
    // Process photos
    $processedPhotos = processPhotos($photos);
    
    return [
        'report_type' => $report_type,
        'date_range' => ['start' => $start_date, 'end' => $end_date],
        'summary' => $summary,
        'daily_updates' => $dailyUpdates,
        'labour_analysis' => $labourAnalysis,
        'costs' => $costs,
        'materials' => $materials,
        'photos' => $processedPhotos,
        'quality' => $quality,
        'recommendations' => $recommendations
    ];
}

function calculateSummaryStats($dailyUpdates, $labourData, $photos) {
    $totalDays = count($dailyUpdates);
    $totalWorkers = 0;
    $totalHours = 0;
    $totalWages = 0;
    $progressPercentage = 0;
    
    // Calculate from labour data
    foreach ($labourData as $labour) {
        $totalWorkers += intval($labour['worker_count']);
        $totalHours += floatval($labour['hours_worked']) + floatval($labour['overtime_hours']);
        $totalWages += floatval($labour['total_wages']);
    }
    
    // Calculate progress percentage
    foreach ($dailyUpdates as $update) {
        $progressPercentage += floatval($update['incremental_completion_percentage']);
    }
    
    // Count geo photos
    $geoPhotosCount = 0;
    foreach ($photos as $photo) {
        if ($photo['latitude'] && $photo['longitude']) {
            $geoPhotosCount++;
        }
    }
    
    return [
        'total_days' => $totalDays,
        'total_workers' => $totalWorkers,
        'total_hours' => round($totalHours, 1),
        'total_wages' => $totalWages,
        'progress_percentage' => round($progressPercentage, 1),
        'photos_count' => count($photos),
        'geo_photos_count' => $geoPhotosCount
    ];
}

function analyzeLabourData($labourData) {
    $analysis = [];
    
    foreach ($labourData as $labour) {
        $workerType = $labour['worker_type'];
        
        if (!isset($analysis[$workerType])) {
            $analysis[$workerType] = [
                'total_workers' => 0,
                'total_hours' => 0,
                'overtime_hours' => 0,
                'total_wages' => 0,
                'productivity_sum' => 0,
                'count' => 0
            ];
        }
        
        $analysis[$workerType]['total_workers'] += intval($labour['worker_count']);
        $analysis[$workerType]['total_hours'] += floatval($labour['hours_worked']);
        $analysis[$workerType]['overtime_hours'] += floatval($labour['overtime_hours']);
        $analysis[$workerType]['total_wages'] += floatval($labour['total_wages']);
        $analysis[$workerType]['productivity_sum'] += floatval($labour['productivity_rating']);
        $analysis[$workerType]['count']++;
    }
    
    // Calculate averages
    foreach ($analysis as $workerType => $data) {
        $analysis[$workerType]['avg_productivity'] = round($data['productivity_sum'] / $data['count'], 1);
        unset($analysis[$workerType]['productivity_sum']);
        unset($analysis[$workerType]['count']);
    }
    
    return $analysis;
}

function calculateCosts($labourData, $dailyUpdates) {
    $labourCost = 0;
    $materialCost = 0;
    $equipmentCost = 0;
    
    // Calculate labour costs
    foreach ($labourData as $labour) {
        $labourCost += floatval($labour['total_wages']);
    }
    
    // Estimate material costs (simplified calculation)
    $materialCost = $labourCost * 0.6; // Assume materials are 60% of labour cost
    
    // Estimate equipment costs
    $equipmentCost = $labourCost * 0.15; // Assume equipment is 15% of labour cost
    
    return [
        'labour_cost' => $labourCost,
        'material_cost' => $materialCost,
        'equipment_cost' => $equipmentCost,
        'total_cost' => $labourCost + $materialCost + $equipmentCost
    ];
}

function extractMaterials($dailyUpdates) {
    $materials = [];
    $materialCounts = [];
    
    foreach ($dailyUpdates as $update) {
        if ($update['materials_used']) {
            $materialsList = explode(',', $update['materials_used']);
            foreach ($materialsList as $material) {
                $material = trim($material);
                if ($material) {
                    if (!isset($materialCounts[$material])) {
                        $materialCounts[$material] = 0;
                    }
                    $materialCounts[$material]++;
                }
            }
        }
    }
    
    foreach ($materialCounts as $material => $count) {
        $materials[] = [
            'name' => $material,
            'quantity' => $count,
            'unit' => 'days used'
        ];
    }
    
    return $materials;
}

function generateQualityMetrics($dailyUpdates, $labourData) {
    $safetySum = 0;
    $safetyCount = 0;
    $qualitySum = 0;
    $qualityCount = 0;
    $scheduleAdherence = 85; // Default value
    
    // Calculate safety and quality from labour data
    foreach ($labourData as $labour) {
        if (isset($labour['safety_compliance'])) {
            $safetyValue = getSafetyScore($labour['safety_compliance']);
            $safetySum += $safetyValue;
            $safetyCount++;
        }
        
        if (isset($labour['productivity_rating'])) {
            $qualitySum += floatval($labour['productivity_rating']);
            $qualityCount++;
        }
    }
    
    $safetyScore = $safetyCount > 0 ? round($safetySum / $safetyCount, 1) : 4.0;
    $qualityScore = $qualityCount > 0 ? round($qualitySum / $qualityCount, 1) : 4.0;
    
    // Calculate schedule adherence based on progress
    $totalProgress = 0;
    foreach ($dailyUpdates as $update) {
        $totalProgress += floatval($update['incremental_completion_percentage']);
    }
    
    if ($totalProgress > 0) {
        $expectedProgress = count($dailyUpdates) * 1.5; // Assume 1.5% per day
        $scheduleAdherence = min(100, round(($totalProgress / $expectedProgress) * 100));
    }
    
    return [
        'safety_score' => $safetyScore,
        'quality_score' => $qualityScore,
        'schedule_adherence' => $scheduleAdherence
    ];
}

function getSafetyScore($safetyCompliance) {
    switch (strtolower($safetyCompliance)) {
        case 'excellent': return 5;
        case 'good': return 4;
        case 'average': return 3;
        case 'poor': return 2;
        case 'needs_improvement': return 1;
        default: return 4;
    }
}

function generateRecommendations($summary, $quality, $reportType) {
    $recommendations = [];
    
    // Safety recommendations
    if ($quality['safety_score'] < 4) {
        $recommendations[] = [
            'priority' => 'HIGH',
            'title' => 'Improve Safety Compliance',
            'description' => 'Safety score is below acceptable levels. Implement additional safety training and monitoring.'
        ];
    }
    
    // Quality recommendations
    if ($quality['quality_score'] < 3.5) {
        $recommendations[] = [
            'priority' => 'MEDIUM',
            'title' => 'Enhance Work Quality',
            'description' => 'Work quality metrics indicate room for improvement. Consider additional supervision and quality checks.'
        ];
    }
    
    // Schedule recommendations
    if ($quality['schedule_adherence'] < 80) {
        $recommendations[] = [
            'priority' => 'HIGH',
            'title' => 'Address Schedule Delays',
            'description' => 'Project is behind schedule. Review resource allocation and identify bottlenecks.'
        ];
    }
    
    // Productivity recommendations
    if ($summary['total_hours'] > 0 && $summary['progress_percentage'] / $summary['total_hours'] < 0.1) {
        $recommendations[] = [
            'priority' => 'MEDIUM',
            'title' => 'Optimize Productivity',
            'description' => 'Consider optimizing work processes and resource allocation to improve productivity.'
        ];
    }
    
    // Default recommendations if none generated
    if (empty($recommendations)) {
        $recommendations[] = [
            'priority' => 'LOW',
            'title' => 'Maintain Current Standards',
            'description' => 'Continue current work practices and maintain quality and safety standards.'
        ];
    }
    
    return $recommendations;
}

function processPhotos($photos) {
    $processedPhotos = [];
    
    foreach ($photos as $photo) {
        $photoUrl = '/buildhub/backend/uploads/geo_photos/' . $photo['filename'];
        $location = $photo['place_name'] ?: 'Location not available';
        
        $processedPhotos[] = [
            'url' => $photoUrl,
            'date' => $photo['upload_timestamp'],
            'location' => $location,
            'filename' => $photo['original_filename']
        ];
    }
    
    return $processedPhotos;
}
?>