<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

echo json_encode([
    'success' => true,
    'message' => 'Proxy is working!',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'not set',
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'not set',
        'HTTP_ORIGIN' => $_SERVER['HTTP_ORIGIN'] ?? 'not set'
    ]
]);
?>