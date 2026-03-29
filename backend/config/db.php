<?php
$host = '127.0.0.1'; // Use explicit IP to avoid socket/IPv6 issues
$port = 3306;        // Change if XAMPP MySQL uses a different port (e.g., 3307/3308)
$db   = 'buildhub';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 5, // seconds
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Return clean JSON error for API usage
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Check MySQL service/credentials/port.', 'error' => $e->getMessage()]);
    exit;
}
?>
