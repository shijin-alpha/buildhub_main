<?php
// Helper function for database connection
// This provides a simple function-based approach for compatibility

require_once __DIR__ . '/database.php';

function getConnection() {
    $database = new Database();
    return $database->getConnection();
}

function getPDO() {
    return getConnection();
}
?>