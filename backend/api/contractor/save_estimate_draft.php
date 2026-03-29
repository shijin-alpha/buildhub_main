<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'buildhub';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Save draft
        $input = json_decode(file_get_contents('php://input'), true);
        
        $contractor_id = $input['contractor_id'] ?? null;
        $send_id = $input['send_id'] ?? null;
        $draft_data = json_encode($input['draft_data'] ?? []);
        
        if (!$contractor_id || !$send_id) {
            throw new Exception('Missing contractor_id or send_id');
        }
        
        // Create drafts table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS estimate_drafts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contractor_id INT NOT NULL,
                send_id INT NOT NULL,
                draft_data JSON NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_draft (contractor_id, send_id)
            )
        ");
        
        // Insert or update draft
        $stmt = $pdo->prepare("
            INSERT INTO estimate_drafts (contractor_id, send_id, draft_data) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            draft_data = VALUES(draft_data),
            updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([$contractor_id, $send_id, $draft_data]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Draft saved successfully'
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Load draft
        $contractor_id = $_GET['contractor_id'] ?? null;
        $send_id = $_GET['send_id'] ?? null;
        
        if (!$contractor_id || !$send_id) {
            throw new Exception('Missing contractor_id or send_id');
        }
        
        $stmt = $pdo->prepare("
            SELECT draft_data, updated_at 
            FROM estimate_drafts 
            WHERE contractor_id = ? AND send_id = ?
        ");
        
        $stmt->execute([$contractor_id, $send_id]);
        $draft = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($draft) {
            echo json_encode([
                'success' => true,
                'draft_data' => json_decode($draft['draft_data'], true),
                'last_saved' => $draft['updated_at']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'draft_data' => null,
                'last_saved' => null
            ]);
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>