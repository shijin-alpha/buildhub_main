<?php
session_start();

// Simulate contractor login
$_SESSION['user_id'] = 29;
$_SESSION['role'] = 'contractor';
$_SESSION['email'] = 'shijinthomas248@gmail.com';

echo "=== Testing Daily Progress Updates API ===\n";
echo "Session User ID: " . $_SESSION['user_id'] . "\n";
echo "Session Role: " . $_SESSION['role'] . "\n\n";

// Capture output without headers
ob_start();

// Include the updated API logic
require_once 'backend/config/database.php';

try {
    $contractor_id = $_SESSION['user_id'];
    
    // Test the updated query for daily progress updates
    $query = "
        SELECT 
            dpu.*,
            COALESCE(
                CONCAT(lr.plot_size, ' - ', lr.preferred_style, ' Style'),
                CONCAT('Project ', dpu.project_id)
            ) as project_name,
            CONCAT(u.first_name, ' ', u.last_name) as homeowner_name,
            u.email as homeowner_email,
            CONCAT(c.first_name, ' ', c.last_name) as contractor_name,
            c.email as contractor_email
        FROM daily_progress_updates dpu
        LEFT JOIN layout_requests lr ON dpu.project_id = lr.id AND lr.status != 'deleted'
        LEFT JOIN users u ON dpu.homeowner_id = u.id
        LEFT JOIN users c ON dpu.contractor_id = c.id
        WHERE dpu.contractor_id = :contractor_id
        ORDER BY dpu.update_date DESC, dpu.created_at DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':contractor_id', $contractor_id);
    $stmt->execute();
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($updates) . " daily progress updates for contractor {$contractor_id}\n\n";
    
    if (!empty($updates)) {
        foreach ($updates as $update) {
            echo "✅ Daily Progress Update Found:\n";
            echo "- ID: {$update['id']}\n";
            echo "- Project: {$update['project_name']}\n";
            echo "- Homeowner: {$update['homeowner_name']}\n";
            echo "- Contractor: {$update['contractor_name']}\n";
            echo "- Date: {$update['update_date']}\n";
            echo "- Stage: {$update['construction_stage']}\n";
            echo "- Work Done: " . substr($update['work_done_today'], 0, 100) . "...\n";
            echo "- Progress: {$update['incremental_completion_percentage']}% (Cumulative: {$update['cumulative_completion_percentage']}%)\n";
            echo "- Hours: {$update['working_hours']}\n";
            echo "- Weather: {$update['weather_condition']}\n";
            echo "- Location: " . ($update['location_verified'] ? 'Verified' : 'Not verified') . "\n";
            
            // Check photos
            if (!empty($update['progress_photos'])) {
                $photos = json_decode($update['progress_photos'], true);
                if (is_array($photos)) {
                    echo "- Photos: " . count($photos) . " photos attached\n";
                } else {
                    echo "- Photos: Invalid photo data\n";
                }
            } else {
                echo "- Photos: No photos\n";
            }
            echo "\n";
        }
        
        // Test the processing logic
        $processedReports = [];
        foreach ($updates as $update) {
            $photos = [];
            if (!empty($update['progress_photos'])) {
                $photoData = json_decode($update['progress_photos'], true);
                if (is_array($photoData)) {
                    $photos = $photoData;
                }
            }
            
            $processedReports[] = [
                'id' => $update['id'],
                'project_id' => $update['project_id'],
                'project_name' => $update['project_name'],
                'homeowner_name' => $update['homeowner_name'],
                'contractor_name' => $update['contractor_name'],
                'report_type' => 'daily',
                'period_start' => $update['update_date'],
                'period_end' => $update['update_date'],
                'status' => 'sent',
                'summary' => [
                    'total_days' => 1,
                    'total_hours' => floatval($update['working_hours'] ?: 0),
                    'progress_percentage' => floatval($update['incremental_completion_percentage'] ?: 0),
                    'photos_count' => count($photos)
                ],
                'work_details' => [
                    'construction_stage' => $update['construction_stage'],
                    'work_done_today' => $update['work_done_today'],
                    'weather_condition' => $update['weather_condition']
                ]
            ];
        }
        
        echo "=== Processed Reports ===\n";
        foreach ($processedReports as $report) {
            echo "Report ID: {$report['id']}\n";
            echo "- Project: {$report['project_name']}\n";
            echo "- Type: {$report['report_type']}\n";
            echo "- Period: {$report['period_start']}\n";
            echo "- Progress: {$report['summary']['progress_percentage']}%\n";
            echo "- Hours: {$report['summary']['total_hours']}\n";
            echo "- Photos: {$report['summary']['photos_count']}\n";
            echo "- Stage: {$report['work_details']['construction_stage']}\n";
            echo "\n";
        }
        
    } else {
        echo "❌ No daily progress updates found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>