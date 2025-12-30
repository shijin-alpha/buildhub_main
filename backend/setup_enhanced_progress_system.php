<?php
/**
 * Setup script for Enhanced Construction Progress Monitoring System
 * This script creates the comprehensive database structure and sample data
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Setting up Enhanced Construction Progress Monitoring System</h2>\n";
    
    // Read and execute the enhanced SQL file
    $sqlFile = __DIR__ . '/database/create_enhanced_progress_tables.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt) && !preg_match('/^\s*DELIMITER/', $stmt);
        }
    );
    
    echo "<h3>Creating Enhanced Database Tables...</h3>\n";
    
    foreach ($statements as $statement) {
        if (trim($statement)) {
            try {
                $db->exec($statement);
                $shortStmt = substr(trim($statement), 0, 60);
                echo "✓ Executed: " . htmlspecialchars($shortStmt) . "...\n<br>";
            } catch (PDOException $e) {
                // Some statements might fail if tables already exist, that's okay
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate') === false) {
                    echo "⚠ Warning: " . htmlspecialchars($e->getMessage()) . "\n<br>";
                }
            }
        }
    }
    
    echo "<h3>Creating Enhanced Upload Directories...</h3>\n";
    
    // Create upload directories for enhanced system
    $uploadDirs = [
        __DIR__ . '/uploads/daily_progress',
        __DIR__ . '/uploads/weekly_summaries',
        __DIR__ . '/uploads/monthly_reports',
        __DIR__ . '/uploads/labour_photos',
        __DIR__ . '/uploads/material_photos'
    ];
    
    foreach ($uploadDirs as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0777, true)) {
                echo "✓ Created directory: $dir\n<br>";
            } else {
                echo "✗ Failed to create directory: $dir\n<br>";
            }
        } else {
            echo "✓ Directory already exists: $dir\n<br>";
        }
    }
    
    echo "<h3>Setting up Sample Project Data...</h3>\n";
    
    // Add sample project locations for testing
    $sampleLocations = [
        [1, 12.9716, 77.5946, 'Bangalore, Karnataka - Sample Project 1', 100],
        [2, 19.0760, 72.8777, 'Mumbai, Maharashtra - Sample Project 2', 150],
        [3, 28.7041, 77.1025, 'New Delhi, Delhi - Sample Project 3', 200]
    ];
    
    $locationStmt = $db->prepare("
        INSERT IGNORE INTO project_locations (project_id, latitude, longitude, address, radius_meters) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleLocations as $location) {
        try {
            $locationStmt->execute($location);
            echo "✓ Added sample location for project {$location[0]}: {$location[3]}\n<br>";
        } catch (PDOException $e) {
            echo "⚠ Location already exists for project {$location[0]}\n<br>";
        }
    }
    
    echo "<h3>Adding Sample Progress Milestones...</h3>\n";
    
    // Add sample milestones for projects
    $sampleMilestones = [
        [1, 'Foundation Excavation Complete', 'Foundation', date('Y-m-d', strtotime('+7 days')), 10.00],
        [1, 'Foundation Concrete Pour', 'Foundation', date('Y-m-d', strtotime('+14 days')), 20.00],
        [1, 'Ground Floor Structure', 'Structure', date('Y-m-d', strtotime('+30 days')), 40.00],
        [1, 'First Floor Structure', 'Structure', date('Y-m-d', strtotime('+45 days')), 60.00],
        [1, 'Roofing Complete', 'Roofing', date('Y-m-d', strtotime('+60 days')), 75.00],
        [1, 'Electrical Rough-in', 'Electrical', date('Y-m-d', strtotime('+75 days')), 85.00],
        [1, 'Plumbing Rough-in', 'Plumbing', date('Y-m-d', strtotime('+80 days')), 90.00],
        [1, 'Final Finishing', 'Finishing', date('Y-m-d', strtotime('+90 days')), 100.00]
    ];
    
    $milestoneStmt = $db->prepare("
        INSERT IGNORE INTO progress_milestones (project_id, milestone_name, milestone_stage, planned_completion_date, planned_progress_percentage) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleMilestones as $milestone) {
        try {
            $milestoneStmt->execute($milestone);
            echo "✓ Added milestone: {$milestone[1]} for project {$milestone[0]}\n<br>";
        } catch (PDOException $e) {
            echo "⚠ Milestone already exists: {$milestone[1]}\n<br>";
        }
    }
    
    echo "<h3>Verifying Enhanced Table Structure...</h3>\n";
    
    // Verify enhanced tables were created
    $enhancedTables = [
        'daily_progress_updates',
        'daily_labour_tracking',
        'weekly_progress_summary',
        'monthly_progress_report',
        'progress_milestones',
        'enhanced_progress_notifications'
    ];
    
    foreach ($enhancedTables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        
        if ($stmt->rowCount() > 0) {
            echo "✓ Enhanced table '$table' exists\n<br>";
            
            // Show column count
            $colStmt = $db->prepare("SHOW COLUMNS FROM $table");
            $colStmt->execute();
            $columnCount = $colStmt->rowCount();
            echo "&nbsp;&nbsp;→ $columnCount columns\n<br>";
        } else {
            echo "✗ Enhanced table '$table' not found\n<br>";
        }
    }
    
    echo "<h3>Testing Enhanced API Endpoints...</h3>\n";
    
    // Test if enhanced API files exist
    $enhancedApiFiles = [
        'api/contractor/submit_daily_progress.php',
        'api/contractor/submit_weekly_summary.php',
        'api/contractor/submit_monthly_report.php',
        'api/contractor/get_progress_analytics.php'
    ];
    
    foreach ($enhancedApiFiles as $file) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            echo "✓ Enhanced API endpoint exists: $file\n<br>";
        } else {
            echo "✗ Enhanced API endpoint missing: $file\n<br>";
        }
    }
    
    echo "<h3>Checking Frontend Components...</h3>\n";
    
    // Check if enhanced frontend files exist
    $frontendFiles = [
        '../frontend/src/components/EnhancedProgressUpdate.jsx',
        '../frontend/src/styles/EnhancedProgress.css'
    ];
    
    foreach ($frontendFiles as $file) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            echo "✓ Enhanced frontend file exists: " . basename($file) . "\n<br>";
        } else {
            echo "⚠ Enhanced frontend file not found: " . basename($file) . "\n<br>";
        }
    }
    
    echo "<h3>Enhanced Setup Complete!</h3>\n";
    echo "<p><strong>Enhanced Construction Progress Monitoring System is ready to use.</strong></p>\n";
    
    echo "<h4>🎯 New Features Available:</h4>\n";
    echo "<ul>\n";
    echo "<li>✅ <strong>Daily Progress Updates</strong> - Comprehensive daily tracking with labour and materials</li>\n";
    echo "<li>✅ <strong>Weekly Progress Summaries</strong> - Aggregated weekly reports with stage analysis</li>\n";
    echo "<li>✅ <strong>Monthly Progress Reports</strong> - Detailed monthly analysis with planned vs actual</li>\n";
    echo "<li>✅ <strong>Labour Tracking System</strong> - Track worker types, hours, overtime, and absences</li>\n";
    echo "<li>✅ <strong>Progress Analytics & Graphs</strong> - Visual progress tracking and analytics</li>\n";
    echo "<li>✅ <strong>Milestone Management</strong> - Automated milestone tracking and status updates</li>\n";
    echo "<li>✅ <strong>Weather Impact Analysis</strong> - Track weather effects on progress</li>\n";
    echo "<li>✅ <strong>Enhanced Notifications</strong> - Detailed notifications for all update types</li>\n";
    echo "<li>✅ <strong>Geo-location Verification</strong> - Automatic location verification for updates</li>\n";
    echo "<li>✅ <strong>Immutable Progress History</strong> - Complete audit trail of all updates</li>\n";
    echo "</ul>\n";
    
    echo "<h4>📊 Analytics & Visualization:</h4>\n";
    echo "<ul>\n";
    echo "<li>📈 Overall construction completion graph (planned vs actual)</li>\n";
    echo "<li>📊 Stage-wise progress charts</li>\n";
    echo "<li>👷 Labour utilization graphs</li>\n";
    echo "<li>🌤️ Weather impact analysis</li>\n";
    echo "<li>📅 Weekly and monthly trend analysis</li>\n";
    echo "<li>🎯 Milestone achievement tracking</li>\n";
    echo "</ul>\n";
    
    echo "<h4>🔐 Security & Validation:</h4>\n";
    echo "<ul>\n";
    echo "<li>🔒 Role-based access control (contractor, homeowner, admin)</li>\n";
    echo "<li>📍 GPS location verification</li>\n";
    echo "<li>📸 Mandatory photos for significant progress claims</li>\n";
    echo "<li>🚫 Progress percentage cannot decrease</li>\n";
    echo "<li>📝 Immutable update records</li>\n";
    echo "<li>⏰ Automatic timestamping</li>\n";
    echo "</ul>\n";
    
    echo "<h4>🚀 Next Steps:</h4>\n";
    echo "<ol>\n";
    echo "<li><strong>Import CSS:</strong> Add EnhancedProgress.css to your main CSS imports</li>\n";
    echo "<li><strong>Update Dashboard:</strong> The ContractorDashboard has been updated to use EnhancedProgressUpdate</li>\n";
    echo "<li><strong>Test System:</strong> Create sample daily updates to test the system</li>\n";
    echo "<li><strong>Configure Projects:</strong> Set up project locations for geo-verification</li>\n";
    echo "<li><strong>Train Users:</strong> Provide training on the new multi-section system</li>\n";
    echo "</ol>\n";
    
    echo "<h4>📱 Usage Guide:</h4>\n";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;'>\n";
    echo "<strong>For Contractors:</strong><br>\n";
    echo "1. Navigate to 'Progress Updates' tab in dashboard<br>\n";
    echo "2. Choose between Daily Update, Weekly Summary, or Monthly Report<br>\n";
    echo "3. Fill comprehensive forms with labour tracking and photos<br>\n";
    echo "4. System automatically calculates cumulative progress<br>\n";
    echo "5. View analytics and graphs in timeline section<br><br>\n";
    
    echo "<strong>For Homeowners:</strong><br>\n";
    echo "1. Receive detailed notifications for all update types<br>\n";
    echo "2. View comprehensive progress analytics and graphs<br>\n";
    echo "3. Track labour utilization and material usage<br>\n";
    echo "4. Monitor milestone achievements<br>\n";
    echo "5. Access complete project history and photos<br>\n";
    echo "</div>\n";
    
    echo "<p style='color: #28a745; font-weight: bold;'>🎉 Enhanced Construction Progress Monitoring System is now fully operational!</p>\n";
    
} catch (Exception $e) {
    echo "<h3>Setup Error</h3>\n";
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Please check your database connection and try again.</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>