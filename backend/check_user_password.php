<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare('SELECT id, first_name, last_name, email, password FROM users WHERE id = 28');
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo 'User found: ' . $user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['email'] . ')' . PHP_EOL;
    echo 'Password hash: ' . $user['password'] . PHP_EOL;
    echo 'Testing password123: ' . (password_verify('password123', $user['password']) ? 'MATCH' : 'NO MATCH') . PHP_EOL;
    echo 'Testing 123456: ' . (password_verify('123456', $user['password']) ? 'MATCH' : 'NO MATCH') . PHP_EOL;
    echo 'Testing shijin123: ' . (password_verify('shijin123', $user['password']) ? 'MATCH' : 'NO MATCH') . PHP_EOL;
    echo 'Testing admin123: ' . (password_verify('admin123', $user['password']) ? 'MATCH' : 'NO MATCH') . PHP_EOL;
} else {
    echo 'User not found';
}
?>