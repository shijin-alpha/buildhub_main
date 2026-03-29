<?php
require_once 'backend/config/database.php';

try {
    echo "=== Checking Progress Reports ===\n\n";
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Check if progress_reports table exists
    $stmt = $db->query("SHOW TABLES LIKE 'progress_reports'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "❌ progress_reports table does not exist\n";
        echo "Creating the table...\n";
        
        // Create the table based on the SQL structure
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS progress_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            contractor_id INT NOT NULL,
            homeowner_id INT NOT NULL,
            report_type ENUM('daily','weekly','monthly') NOT NULL,
            report_period_start DATE DEFAULT NULL,
            report_period_end DATE DEFAULT NULL,
            report_data TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status ENUM('draft','sent','viewed','acknowledged') DEFAULT 'draft',
            homeowner_viewed_at TIMESTAMP NULL DEFAULT NULL,
            homeowner_acknowledged_at TIMESTAMP NULL DEFAULT NULL,
            acknowledgment_notes TEXT DEFAULT NULL,
            
            INDEX idx_project_contractor (project_id, contractor_id),
            INDEX idx_contractor_id (contractor_id),
            INDEX idx_homeowner_id (homeowner_id),
            INDEX idx_report_type (report_type),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        )";
        
        $db->exec($createTableSQL);
        echo "✓ progress_reports table created\n\n";
    } else {
        echo "✓ progress_reports table exists\n\n";
    }
    
    // Check existing progress reports
    $stmt = $db->query("SELECT * FROM progress_reports ORDER BY created_at DESC");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total progress reports in MySQL: " . count($reports) . "\n\n";
    
    if (count($reports) > 0) {
        foreach ($reports as $report) {
            echo "--- Report ID: {$report['id']} ---\n";
            echo "Project ID: {$report['project_id']}\n";
            echo "Contractor ID: {$report['contractor_id']}\n";
            echo "Homeowner ID: {$report['homeowner_id']}\n";
            echo "Type: {$report['report_type']}\n";
            echo "Status: {$report['status']}\n";
            echo "Period: {$report['report_period_start']} to {$report['report_period_end']}\n";
            echo "Created: {$report['created_at']}\n";
            echo "Updated: {$report['updated_at']}\n";
            
            // Parse report data
            $reportData = json_decode($report['report_data'], true);
            if ($reportData) {
                echo "Report Data Summary:\n";
                if (isset($reportData['summary'])) {
                    echo "  - Total Days: " . ($reportData['summary']['total_days'] ?? 'N/A') . "\n";
                    echo "  - Progress: " . ($reportData['summary']['progress_percentage'] ?? 'N/A') . "%\n";
                    echo "  - Photos: " . ($reportData['summary']['photos_count'] ?? 'N/A') . "\n";
                }
                if (isset($reportData['project'])) {
                    echo "  - Project Name: " . ($reportData['project']['name'] ?? 'N/A') . "\n";
                }
            }
            echo "\n";
        }
    } else {
        echo "No progress reports found in MySQL database\n";
        echo "Let's create a sample daily progress report for testing...\n\n";
        
        // Create a sample daily progress report
        $sampleReportData = [
            'project' => [
                'id' => 1,
                'name' => 'Modern Villa Construction - Phase 2'
            ],
            'contractor' => [
                'id' => 27,
                'name' => 'Shijin Thomas'
            ],
            'homeowner' => [
                'id' => 28,
                'name' => 'SHIJIN THOMAS MCA2024-2026'
            ],
            'summary' => [
                'total_days' => 1,
                'total_workers' => 8,
                'total_hours' => 64,
                'progress_percentage' => 5,
                'total_wages' => 25000,
                'photos_count' => 15,
                'geo_photos_count' => 12
            ],
            'daily_updates' => [
                [
                    'update_date' => '2026-01-18',
                    'construction_stage' => 'Foundation Work',
                    'work_done_today' => 'Completed foundation excavation and started concrete pouring for the main foundation',
                    'incremental_completion_percentage' => 5,
                    'working_hours' => 8,
                    'weather_condition' => 'Clear',
                    'site_issues' => null
                ]
            ],
            'labour_analysis' => [
                'Mason' => [
                    'total_workers' => 3,
                    'total_hours' => 24,
                    'overtime_hours' => 0,
                    'total_wages' => 9000,
                    'avg_productivity' => 4
                ],
                'Helper' => [
                    'total_workers' => 4,
                    'total_hours' => 32,
                    'overtime_hours' => 0,
                    'total_wages' => 8000,
                    'avg_productivity' => 4
                ],
                'Supervisor' => [
                    'total_workers' => 1,
                    'total_hours' => 8,
                    'overtime_hours' => 0,
                    'total_wages' => 8000,
                    'avg_productivity' => 5
                ]
            ],
            'costs' => [
                'labour_cost' => 25000,
                'material_cost' => 15000,
                'equipment_cost' => 5000,
                'total_cost' => 45000
            ],
            'materials' => [
                ['name' => 'Cement', 'quantity' => 50, 'unit' => 'bags'],
                ['name' => 'Steel Rods', 'quantity' => 500, 'unit' => 'kg'],
                ['name' => 'Sand', 'quantity' => 10, 'unit' => 'cubic meters']
            ],
            'photos' => [
                ['url' => '/buildhub/uploads/progress/foundation_1.jpg', 'date' => '2026-01-18', 'location' => 'Foundation Area'],
                ['url' => '/buildhub/uploads/progress/foundation_2.jpg', 'date' => '2026-01-18', 'location' => 'Excavation Site'],
                ['url' => '/buildhub/uploads/progress/foundation_3.jpg', 'date' => '2026-01-18', 'location' => 'Concrete Pouring']
            ],
            'quality' => [
                'safety_score' => 4,
                'quality_score' => 4,
                'schedule_adherence' => 95
            ],
            'recommendations' => [
                [
                    'priority' => 'High',
                    'title' => 'Weather Monitoring',
                    'description' => 'Monitor weather conditions for concrete curing'
                ],
                [
                    'priority' => 'Medium',
                    'title' => 'Material Delivery',
                    'description' => 'Schedule next batch of materials for upcoming work'
                ]
            ]
        ];
        
        // Insert sample report
        $stmt = $db->prepare("
            INSERT INTO progress_reports (
                project_id, contractor_id, homeowner_id, report_type,
                report_period_start, report_period_end, report_data, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            1, // project_id
            27, // contractor_id (Shijin Thomas - architect)
            28, // homeowner_id (SHIJIN THOMAS MCA2024-2026 - homeowner)
            'daily',
            '2026-01-18',
            '2026-01-18',
            json_encode($sampleReportData),
            'sent'
        ]);
        
        if ($result) {
            echo "✓ Sample daily progress report created successfully!\n";
            echo "Report ID: " . $db->lastInsertId() . "\n";
        } else {
            echo "✗ Failed to create sample report\n";
        }
    }
    
    // Check layout_requests table for project context
    echo "\n=== Checking Layout Requests (Projects) ===\n";
    $stmt = $db->query("SELECT id, homeowner_id, requirements, status FROM layout_requests LIMIT 5");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $project) {
        echo "Project ID: {$project['id']}, Homeowner: {$project['homeowner_id']}, Status: {$project['status']}\n";
        echo "  Requirements: " . substr($project['requirements'], 0, 50) . "...\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>