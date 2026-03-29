<?php
/**
 * Debug Inspector API
 * Check what's causing the 500 errors
 */

echo "🔍 Debugging Inspector API Issues\n";
echo "=================================\n\n";

// Test the get_project_details.php directly
echo "Testing get_project_details.php directly...\n";

// Set up environment to simulate web request
$_GET['project_id'] = '1';

// Capture output
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Include the API file
    include 'backend/api/inspector/get_project_details.php';
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "Error caught: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();

echo "Output:\n";
echo $output . "\n";

// Test database connection
echo "\nTesting database connection...\n";
try {
    require_once 'backend/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Database connection successful\n";
    
    // Test a simple query
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM construction_projects");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "✅ Found " . $result['count'] . " construction projects\n";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test authorization middleware
echo "\nTesting authorization middleware...\n";
try {
    require_once 'backend/middleware/AuthorizationMiddleware.php';
    $auth = new AuthorizationMiddleware($db);
    echo "✅ Authorization middleware loaded\n";
    
    // Test authentication (should fail without session)
    if (!$auth->isAuthenticated()) {
        echo "⚠️  Not authenticated (expected)\n";
    } else {
        echo "✅ Authenticated\n";
    }
    
} catch (Exception $e) {
    echo "❌ Authorization error: " . $e->getMessage() . "\n";
}
?>