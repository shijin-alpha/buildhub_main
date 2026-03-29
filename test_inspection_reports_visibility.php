<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== TESTING INSPECTION REPORTS VISIBILITY ===\n\n";
    
    // 1. Check if inspection reports exist
    echo "1. Checking inspection reports in database:\n";
    $result = $db->query('SELECT COUNT(*) as count FROM inspection_reports');
    $count = $result->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Total inspection reports: $count\n";
    
    if ($count > 0) {
        $result = $db->query('SELECT ir.id, ir.project_id, ir.inspection_date, ir.overall_status, cp.project_name, cp.homeowner_id FROM inspection_reports ir JOIN construction_projects cp ON ir.project_id = cp.id ORDER BY ir.created_at DESC LIMIT 5');
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "   - Report ID: {$row['id']}, Project: {$row['project_name']}, Status: {$row['overall_status']}, Homeowner ID: {$row['homeowner_id']}\n";
        }
    }
    
    // 2. Test admin API
    echo "\n2. Testing Admin API:\n";
    session_start();
    $_SESSION['admin_logged_in'] = true;
    
    ob_start();
    include 'backend/api/admin/get_inspection_reports.php';
    $admin_response = ob_get_clean();
    
    $admin_data = json_decode($admin_response, true);
    if ($admin_data && $admin_data['success']) {
        echo "   ✅ Admin API working - Found {$admin_data['statistics']['total_reports']} reports\n";
        echo "   - Approved: {$admin_data['statistics']['approved_count']}\n";
        echo "   - Rejected: {$admin_data['statistics']['rejected_count']}\n";
        echo "   - Need Attention: {$admin_data['statistics']['needs_attention_count']}\n";
    } else {
        echo "   ❌ Admin API failed: " . ($admin_data['message'] ?? 'Unknown error') . "\n";
    }
    
    // 3. Test homeowner API (simulate homeowner session)
    echo "\n3. Testing Homeowner API:\n";
    session_destroy();
    session_start();
    $_SESSION['user_id'] = 1; // Assuming homeowner ID 1
    $_SESSION['role'] = 'homeowner';
    
    ob_start();
    include 'backend/api/homeowner/get_inspection_reports.php';
    $homeowner_response = ob_get_clean();
    
    $homeowner_data = json_decode($homeowner_response, true);
    if ($homeowner_data && $homeowner_data['success']) {
        echo "   ✅ Homeowner API working - Found {$homeowner_data['statistics']['total_reports']} reports\n";
        echo "   - Projects with reports: {$homeowner_data['statistics']['projects_with_reports']}\n";
        if (!empty($homeowner_data['projects'])) {
            echo "   - Projects:\n";
            foreach ($homeowner_data['projects'] as $project) {
                echo "     * {$project['name']}: {$project['inspection_summary']['total_inspections']} inspections\n";
            }
        }
    } else {
        echo "   ❌ Homeowner API failed: " . ($homeowner_data['message'] ?? 'Unknown error') . "\n";
    }
    
    // 4. Test inspector dashboard API fix
    echo "\n4. Testing Inspector Dashboard API fix:\n";
    session_destroy();
    session_start();
    $_SESSION['admin_logged_in'] = true; // Admin can access inspector APIs
    
    ob_start();
    include 'backend/api/inspector/get_project_details.php?project_id=2';
    $inspector_response = ob_get_clean();
    
    $inspector_data = json_decode($inspector_response, true);
    if ($inspector_data && $inspector_data['success']) {
        echo "   ✅ Inspector API working - Found " . count($inspector_data['inspection_reports']) . " reports for project\n";
        if (!empty($inspector_data['inspection_reports'])) {
            foreach ($inspector_data['inspection_reports'] as $report) {
                echo "     * Report {$report['id']}: {$report['inspection_type']} on {$report['inspection_date']}\n";
            }
        }
    } else {
        echo "   ❌ Inspector API failed: " . ($inspector_data['message'] ?? 'Unknown error') . "\n";
    }
    
    echo "\n=== TEST COMPLETE ===\n";
    echo "Summary:\n";
    echo "- Database has $count inspection reports\n";
    echo "- Admin API: " . ($admin_data['success'] ? "✅ Working" : "❌ Failed") . "\n";
    echo "- Homeowner API: " . ($homeowner_data['success'] ? "✅ Working" : "❌ Failed") . "\n";
    echo "- Inspector API: " . ($inspector_data['success'] ? "✅ Working" : "❌ Failed") . "\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>