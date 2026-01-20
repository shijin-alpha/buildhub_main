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
    // Database connection
    $host = 'localhost';
    $dbname = 'buildhub';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if contractor is logged in
    $contractor_id = $_SESSION['user_id'] ?? null;
    if (!$contractor_id) {
        throw new Exception('Contractor not logged in');
    }
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Required fields
    $required_fields = [
        'project_id', 'stage_name', 'withdrawal_amount', 
        'work_completed_percentage', 'work_description'
    ];
    
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    $project_id = $input['project_id'];
    $stage_name = $input['stage_name'];
    $withdrawal_amount = floatval($input['withdrawal_amount']);
    $work_completed_percentage = floatval($input['work_completed_percentage']);
    $work_description = trim($input['work_description']);
    
    // Optional fields
    $materials_used = $input['materials_used'] ?? '';
    $labor_details = $input['labor_details'] ?? '';
    $quality_assurance = $input['quality_assurance'] ?? false;
    $safety_compliance = $input['safety_compliance'] ?? false;
    $photos_uploaded = $input['photos_uploaded'] ?? false;
    $contractor_notes = $input['contractor_notes'] ?? '';
    
    // Validation
    if ($withdrawal_amount <= 0) {
        throw new Exception('Withdrawal amount must be greater than 0');
    }
    
    if ($work_completed_percentage < 0 || $work_completed_percentage > 100) {
        throw new Exception('Work completion percentage must be between 0 and 100');
    }
    
    if (strlen($work_description) < 50) {
        throw new Exception('Work description must be at least 50 characters');
    }
    
    if (!$quality_assurance) {
        throw new Exception('Quality assurance confirmation is required');
    }
    
    if (!$safety_compliance) {
        throw new Exception('Safety compliance confirmation is required');
    }
    
    // Verify contractor access to this project
    $stmt = $pdo->prepare("
        SELECT p.*, e.total_cost, e.contractor_id 
        FROM projects p 
        LEFT JOIN estimates e ON p.estimate_id = e.id 
        WHERE p.id = ? AND e.contractor_id = ?
    ");
    $stmt->execute([$project_id, $contractor_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        throw new Exception('Project not found or access denied');
    }
    
    $total_project_cost = $project['total_cost'] ?? 0;
    
    // Check if there's already a pending request for this stage
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as pending_count 
        FROM stage_payment_requests 
        WHERE project_id = ? AND stage_name = ? AND status = 'pending'
    ");
    $stmt->execute([$project_id, $stage_name]);
    $pending_count = $stmt->fetchColumn();
    
    if ($pending_count > 0) {
        throw new Exception('There is already a pending payment request for this stage');
    }
    
    // Calculate stage typical amount (for validation)
    $stage_percentages = [
        'Foundation' => 20,
        'Structure' => 25,
        'Brickwork' => 15,
        'Roofing' => 15,
        'Electrical' => 8,
        'Plumbing' => 7,
        'Finishing' => 10
    ];
    
    $typical_percentage = $stage_percentages[$stage_name] ?? 10;
    $typical_amount = ($total_project_cost * $typical_percentage) / 100;
    
    // Check if withdrawal amount exceeds typical amount for this stage
    if ($withdrawal_amount > $typical_amount * 1.2) { // Allow 20% buffer
        throw new Exception("Withdrawal amount exceeds typical amount for $stage_name stage (₹" . number_format($typical_amount) . ")");
    }
    
    // Check total payments don't exceed project cost
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN status = 'paid' THEN COALESCE(approved_amount, requested_amount) ELSE 0 END), 0) as total_paid,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN requested_amount ELSE 0 END), 0) as total_pending
        FROM stage_payment_requests 
        WHERE project_id = ?
    ");
    $stmt->execute([$project_id]);
    $payment_summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_paid = $payment_summary['total_paid'] ?? 0;
    $total_pending = $payment_summary['total_pending'] ?? 0;
    
    if (($total_paid + $total_pending + $withdrawal_amount) > $total_project_cost) {
        $available_amount = $total_project_cost - $total_paid - $total_pending;
        throw new Exception("Withdrawal amount exceeds available project budget. Available: ₹" . number_format($available_amount));
    }
    
    // Create the stage payment request
    $stmt = $pdo->prepare("
        INSERT INTO stage_payment_requests (
            project_id, contractor_id, stage_name, requested_amount, 
            completion_percentage, work_description, materials_used, 
            labor_details, quality_assurance, safety_compliance, 
            photos_uploaded, contractor_notes, status, request_date,
            percentage_of_total, typical_stage_percentage
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(),
            ?, ?
        )
    ");
    
    $percentage_of_total = ($withdrawal_amount / $total_project_cost) * 100;
    
    $stmt->execute([
        $project_id, $contractor_id, $stage_name, $withdrawal_amount,
        $work_completed_percentage, $work_description, $materials_used,
        $labor_details, $quality_assurance ? 1 : 0, $safety_compliance ? 1 : 0,
        $photos_uploaded ? 1 : 0, $contractor_notes,
        $percentage_of_total, $typical_percentage
    ]);
    
    $request_id = $pdo->lastInsertId();
    
    // Update construction progress if applicable
    $stmt = $pdo->prepare("
        INSERT INTO construction_progress (
            project_id, contractor_id, stage_name, completion_percentage,
            work_description, materials_used, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            completion_percentage = GREATEST(completion_percentage, VALUES(completion_percentage)),
            work_description = VALUES(work_description),
            materials_used = VALUES(materials_used),
            updated_at = NOW()
    ");
    $stmt->execute([
        $project_id, $contractor_id, $stage_name, $work_completed_percentage,
        $work_description, $materials_used
    ]);
    
    // Create notification for homeowner
    $stmt = $pdo->prepare("
        SELECT homeowner_id FROM projects p 
        JOIN estimates e ON p.estimate_id = e.id 
        JOIN layout_requests lr ON e.layout_request_id = lr.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$project_id]);
    $homeowner_id = $stmt->fetchColumn();
    
    if ($homeowner_id) {
        $notification_message = "New payment request for $stage_name stage: ₹" . number_format($withdrawal_amount);
        
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
    }
    
    // Get the created request details
    $stmt = $pdo->prepare("
        SELECT 
            spr.*,
            p.project_name,
            u.name as contractor_name
        FROM stage_payment_requests spr
        JOIN projects p ON spr.project_id = p.id
        JOIN users u ON spr.contractor_id = u.id
        WHERE spr.id = ?
    ");
    $stmt->execute([$request_id]);
    $request_details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => "Stage payment withdrawal request submitted successfully for $stage_name stage",
        'data' => [
            'request_id' => $request_id,
            'stage_name' => $stage_name,
            'requested_amount' => $withdrawal_amount,
            'completion_percentage' => $work_completed_percentage,
            'status' => 'pending',
            'request_details' => $request_details,
            'percentage_of_total' => round($percentage_of_total, 2),
            'typical_amount' => $typical_amount
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>