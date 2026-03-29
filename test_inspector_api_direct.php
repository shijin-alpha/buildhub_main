<?php
/**
 * Test Inspector API Direct
 * Test the API with proper session setup
 */

echo "🧪 Testing Inspector API Direct...\n\n";

// Start session and set up inspector credentials
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_user_id'] = 1001; // Inspector user ID from our debug
$_SESSION['admin_email'] = 'inspector@buildhub.com';
$_SESSION['admin_scope'] = 'INSPECTOR';
$_SESSION['admin_role'] = 'contractor';

// Set up environment variables to simulate HTTP request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['status'] = 'all';

// Capture the API output
ob_start();

try {
    // Include required files
    require_once 'backend/config/database.php';
    require_once 'backend/middleware/AuthorizationMiddleware.php';
    
    $database = new Database();
    $db = $database->getConnection();
    $auth = new AuthorizationMiddleware($db);
    
    echo "Session check:\n";
    echo "- admin_logged_in: " . ($_SESSION['admin_logged_in'] ? 'true' : 'false') . "\n";
    echo "- admin_user_id: " . ($_SESSION['admin_user_id'] ?? 'not set') . "\n";
    echo "- admin_scope: " . ($_SESSION['admin_scope'] ?? 'not set') . "\n";
    
    echo "\nAuth check:\n";
    echo "- isAuthenticated: " . ($auth->isAuthenticated() ? 'true' : 'false') . "\n";
    echo "- isInspector: " . ($auth->isInspector() ? 'true' : 'false') . "\n";
    echo "- getCurrentUser: " . json_encode($auth->getCurrentUser()) . "\n";
    
    if ($auth->isAuthenticated()) {
        echo "\nTesting capability check...\n";
        try {
            $auth->requireCapability('view_assigned_projects');
            echo "- view_assigned_projects: ALLOWED\n";
        } catch (Exception $e) {
            echo "- view_assigned_projects: DENIED - " . $e->getMessage() . "\n";
        }
        
        echo "\nTesting database query...\n";
        $inspectorId = $auth->getCurrentUser()['id'];
        
        $query = "
            SELECT 
                cp.id,
                cp.project_name,
                cp.status,
                cp.current_stage,
                ipa.assigned_at,
                ipa.status as assignment_status
            FROM inspector_project_assignments ipa
            JOIN construction_projects cp ON ipa.project_id = cp.id
            WHERE ipa.inspector_id = :inspector_id
            AND ipa.status = 'active'
            LIMIT 5
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([':inspector_id' => $inspectorId]);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "- Query executed successfully\n";
        echo "- Found " . count($projects) . " projects\n";
        
        foreach ($projects as $project) {
            echo "  * Project: {$project['project_name']}, Status: {$project['status']}, Assignment: {$project['assignment_status']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

$output = ob_get_clean();
echo $output;
?>