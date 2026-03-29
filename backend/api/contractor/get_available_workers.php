<?php
// Suppress warnings to prevent JSON corruption
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);


header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    $contractor_id = $_SESSION['user_id'] ?? $_GET['contractor_id'] ?? null;
    
    if (!$contractor_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Contractor not authenticated'
        ]);
        exit;
    }
    
    // Create workers table if it doesn't exist
    $create_workers_table = "
        CREATE TABLE IF NOT EXISTS contractor_workers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contractor_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(100) NOT NULL,
            category VARCHAR(50) NOT NULL,
            level ENUM('supervisor', 'specialist', 'skilled', 'semi_skilled', 'apprentice', 'laborer') NOT NULL,
            
            -- Wage and experience
          