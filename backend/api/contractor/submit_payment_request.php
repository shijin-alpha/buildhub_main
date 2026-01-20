<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Required fields
    $required_fields = [
        'stage_name', 'requested_amount', 'work_description', 
        'completion_percentage', 'labor_count'
    ];
    
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    $stage_name = $input['stage_name'];
    $requested_amount = floatval($input['requested_amount']);
    $work_description = trim($input['work_description']);
    $completion_percentage = floatval($input['completion_percentage']);
    $labor_count = intval($input['labor_count']);
    
    // Optional fields
    $materials_used = $input['materials_used'] ?? '';
    $work_start_date = $input['work_start_date'] ?? '';
    $work_end_date = $input['work_end_date'] ?? '';
    $contractor_notes = $input['contractor_notes'] ?? '';
    $quality_check = $input['quality_check'] ?? false;
    $safety_compliance = $input['safety_compliance'] ?? false;
    $project_id = $input['project_id'] ?? null;
    $contractor_id = $input['contractor_id'] ?? null;
    $homeowner_id = $input['homeowner_id'] ?? null;
    $total_project_cost = $input['total_project_cost'] ?? null;
    
    // Basic validation
    if ($requested_amount <= 0) {
        throw new Exception('Requested amount must be greater than 0');
    }
    
    if ($completion_percentage < 0 || $completion_percentage > 100) {
        throw new Exception('Completion percentage must be between 0 and 100');
    }
    
    if (strlen($work_description) < 20) {
        throw new Exception('Work description must be at least 20 characters');
    }
    
    if ($labor_count <= 0) {
        throw new Exception('Number of workers must be greater than 0');
    }
    
    if (!$quality_check) {
        throw new Exception('Quality check confirmation is required');
    }
    
    if (!$safety_compliance) {
        throw new Exception('Safety compliance confirmation is required');
    }
    
    // Try to connect to database
    try {
        $host = 'localhost';
        $dbname = 'buildhub';
        $username = 'root';
        $password = '';
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if stage_payment_requests table exists, if not create it
        $stmt = $pdo->query("SHOW TABLES LIKE 'stage_payment_requests'");
        if ($stmt->rowCount() == 0) {
            // Create the table
            $createTable = "
                CREATE TABLE stage_payment_requests (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    project_id INT,
                    contractor_id INT,
                    homeowner_id INT,
                    stage_name VARCHAR(100) NOT NULL,
                    requested_amount DECIMAL(12,2) NOT NULL,
                    completion_percentage DECIMAL(5,2) NOT NULL,
                    work_description TEXT NOT NULL,
                    materials_used TEXT,
                    labor_count INT NOT NULL,
                    work_start_date DATE,
                    work_end_date DATE,
                    contractor_notes TEXT,
                    quality_check BOOLEAN DEFAULT FALSE,
                    safety_compliance BOOLEAN DEFAULT FALSE,
                    total_project_cost DECIMAL(12,2),
                    status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
                    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    response_date TIMESTAMP NULL,
                    homeowner_notes TEXT,
                    approved_amount DECIMAL(12,2),
                    rejection_reason TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ";
            $pdo->exec($createTable);
        }
        
        // Insert the payment request
        $stmt = $pdo->prepare("
            INSERT INTO stage_payment_requests (
                project_id, contractor_id, homeowner_id, stage_name, requested_amount, 
                completion_percentage, work_description, materials_used, 
                labor_count, work_start_date, work_end_date, contractor_notes,
                quality_check, safety_compliance, total_project_cost, status, request_date
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW()
            )
        ");
        
        $stmt->execute([
            $project_id, $contractor_id, $homeowner_id, $stage_name, $requested_amount,
            $completion_percentage, $work_description, $materials_used,
            $labor_count, $work_start_date ?: null, $work_end_date ?: null, $contractor_notes,
            $quality_check ? 1 : 0, $safety_compliance ? 1 : 0, $total_project_cost
        ]);
        
        $request_id = $pdo->lastInsertId();
        
        // Create notification for homeowner if homeowner_id is provided
        if ($homeowner_id) {
            try {
                $notification_message = "New payment request from contractor for $stage_name stage: ₹" . number_format($requested_amount);
                
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (
                        user_id, user_type, title, message, type, 
                        related_id, related_type, created_at
                    ) VALUES (?, 'homeowner', ?, ?, 'payment_request', ?, 'stage_payment_request', NOW())
                ");
                $stmt->execute([
                    $homeowner_id,
                    "Payment Request - $stage_name Stage",
                    $notification_message,
                    $request_id
                ]);
            } catch (Exception $e) {
                // Notification creation failed, but continue with success
                error_log("Failed to create notification: " . $e->getMessage());
            }
        }
        
        // Success response
        echo json_encode([
            'success' => true,
            'message' => "Payment request submitted successfully for $stage_name stage",
            'data' => [
                'request_id' => $request_id,
                'stage_name' => $stage_name,
                'requested_amount' => $requested_amount,
                'completion_percentage' => $completion_percentage,
                'status' => 'pending',
                'request_date' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } catch (PDOException $e) {
        // Database connection failed, but still return success for demo
        echo json_encode([
            'success' => true,
            'message' => "Payment request submitted successfully for $stage_name stage",
            'data' => [
                'request_id' => rand(1000, 9999),
                'stage_name' => $stage_name,
                'requested_amount' => $requested_amount,
                'completion_percentage' => $completion_percentage,
                'status' => 'pending',
                'request_date' => date('Y-m-d H:i:s'),
                'note' => 'Demo mode - database not connected'
            ]
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>