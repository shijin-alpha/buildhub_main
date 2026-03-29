<?php
/**
 * Test Session
 * Debug session information
 */

header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

echo "=== SESSION DEBUG ===\n\n";

echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Save Path: " . session_save_path() . "\n\n";

echo "=== SESSION DATA ===\n";
if (empty($_SESSION)) {
    echo "No session data found\n";
} else {
    foreach ($_SESSION as $key => $value) {
        echo "$key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
    }
}

echo "\n=== COOKIES ===\n";
if (empty($_COOKIE)) {
    echo "No cookies found\n";
} else {
    foreach ($_COOKIE as $key => $value) {
        echo "$key: $value\n";
    }
}

echo "\n=== SERVER INFO ===\n";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'not set') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "HTTP_REFERER: " . ($_SERVER['HTTP_REFERER'] ?? 'not set') . "\n";
echo "HTTP_USER_AGENT: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'not set') . "\n";

echo "\n=== AUTHENTICATION CHECK ===\n";
$isInspector = isset($_SESSION['admin_logged_in']) && 
               $_SESSION['admin_logged_in'] === true && 
               isset($_SESSION['admin_scope']) && 
               $_SESSION['admin_scope'] === 'INSPECTOR';

$isFullAdmin = isset($_SESSION['admin_logged_in']) && 
               $_SESSION['admin_logged_in'] === true && 
               isset($_SESSION['admin_scope']) && 
               $_SESSION['admin_scope'] === 'FULL';

echo "Is Inspector: " . ($isInspector ? 'YES' : 'NO') . "\n";
echo "Is Full Admin: " . ($isFullAdmin ? 'YES' : 'NO') . "\n";
echo "Has admin_logged_in: " . (isset($_SESSION['admin_logged_in']) ? 'YES' : 'NO') . "\n";
echo "admin_logged_in value: " . ($_SESSION['admin_logged_in'] ?? 'not set') . "\n";
echo "Has admin_scope: " . (isset($_SESSION['admin_scope']) ? 'YES' : 'NO') . "\n";
echo "admin_scope value: " . ($_SESSION['admin_scope'] ?? 'not set') . "\n";
?>