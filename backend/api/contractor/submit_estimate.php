<?php
header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) { 
    header('Access-Control-Allow-Origin: ' . $origin); 
    header('Vary: Origin'); 
} else { 
    header('Access-Control-Allow-Origin: http://localhost'); 
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    header('Access-Control-Max-Age: 86400'); 
    exit; 
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    $contractorId = $input['contractor_id'] ?? 0;
    $homeownerId = $input['homeowner_id'] ?? 0;
    $inboxItemId = $input['inbox_item_id'] ?? 0;
    
    if (!$contractorId || !$homeownerId) {
        echo json_encode(['success' => false, 'message' => 'Missing contractor_id or homeowner_id']);
        exit;
    }

    // Create estimates table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS contractor_estimates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT NOT NULL,
        homeowner_id INT NOT NULL,
        send_id INT NULL,
        project_name VARCHAR(255) NULL,
        location VARCHAR(255) NULL,
        client_name VARCHAR(255) NULL,
        client_contact VARCHAR(255) NULL,
        project_type VARCHAR(100) NULL,
        timeline VARCHAR(100) NULL,
        materials_data LONGTEXT NULL,
        labor_data LONGTEXT NULL,
        utilities_data LONGTEXT NULL,
        misc_data LONGTEXT NULL,
        totals_data LONGTEXT NULL,
        structured_data LONGTEXT NULL,
        materials TEXT NULL,
        cost_breakdown TEXT NULL,
        total_cost DECIMAL(15,2) NULL,
        notes TEXT NULL,
        terms TEXT NULL,
        status ENUM('draft', 'submitted', 'accepted', 'rejected') DEFAULT 'submitted',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_contractor (contractor_id),
        INDEX idx_homeowner (homeowner_id),
        INDEX idx_send (send_id)
    )");

    // Prepare estimate data
    $projectName = $input['project_name'] ?? '';
    $location = $input['location'] ?? '';
    $clientName = $input['client_name'] ?? '';
    $projectType = $input['project_type'] ?? 'Residential';
    $timeline = $input['timeline'] ?? '90 days';
    $notes = $input['notes'] ?? '';
    $terms = $input['terms'] ?? '';
    
    $materialsData = json_encode($input['materials'] ?? []);
    $laborData = json_encode($input['labor'] ?? []);
    $utilitiesData = json_encode($input['utilities'] ?? []);
    $miscData = json_encode($input['misc'] ?? []);
    $totalsData = json_encode($input['totals'] ?? []);

    // Insert estimate
    $stmt = $db->prepare("
        INSERT INTO contractor_estimates (
            contractor_id, homeowner_id, send_id, project_name, location, 
            client_name, project_type, timeline, materials_data, labor_data, 
            utilities_data, misc_data, totals_data, notes, terms, status
        ) VALUES (
            :contractor_id, :homeowner_id, :send_id, :project_name, :location,
            :client_name, :project_type, :timeline, :materials_data, :labor_data,
            :utilities_data, :misc_data, :totals_data, :notes, :terms, 'submitted'
        )
    ");

    $stmt->bindParam(':contractor_id', $contractorId, PDO::PARAM_INT);
    $stmt->bindParam(':homeowner_id', $homeownerId, PDO::PARAM_INT);
    $stmt->bindParam(':send_id', $inboxItemId, PDO::PARAM_INT);
    $stmt->bindParam(':project_name', $projectName);
    $stmt->bindParam(':location', $location);
    $stmt->bindParam(':client_name', $clientName);
    $stmt->bindParam(':project_type', $projectType);
    $stmt->bindParam(':timeline', $timeline);
    $stmt->bindParam(':materials_data', $materialsData);
    $stmt->bindParam(':labor_data', $laborData);
    $stmt->bindParam(':utilities_data', $utilitiesData);
    $stmt->bindParam(':misc_data', $miscData);
    $stmt->bindParam(':totals_data', $totalsData);
    $stmt->bindParam(':notes', $notes);
    $stmt->bindParam(':terms', $terms);

    if ($stmt->execute()) {
        $estimateId = $db->lastInsertId();
        
        // Get contractor details
        $contractorStmt = $db->prepare("SELECT first_name, last_name, email FROM users WHERE id = :contractor_id");
        $contractorStmt->bindParam(':contractor_id', $contractorId, PDO::PARAM_INT);
        $contractorStmt->execute();
        $contractor = $contractorStmt->fetch(PDO::FETCH_ASSOC);
        
        $contractorName = ($contractor['first_name'] ?? '') . ' ' . ($contractor['last_name'] ?? '');
        $contractorEmail = $contractor['email'] ?? '';

        // Create notification for homeowner
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                type VARCHAR(50) DEFAULT 'info',
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id)
            )");

            $notificationTitle = "New Estimate Received";
            $notificationMessage = "You have received a new estimate from contractor {$contractorName} for your project '{$projectName}'. Total cost: ₹" . number_format($input['totals']['grand'] ?? 0, 0, '.', ',');
            
            $notifStmt = $db->prepare("
                INSERT INTO notifications (user_id, title, message, type) 
                VALUES (:user_id, :title, :message, 'estimate')
            ");
            $notifStmt->bindParam(':user_id', $homeownerId, PDO::PARAM_INT);
            $notifStmt->bindParam(':title', $notificationTitle);
            $notifStmt->bindParam(':message', $notificationMessage);
            $notifStmt->execute();
        } catch (Exception $e) {
            // Notification creation failed, but estimate was created successfully
            error_log("Failed to create notification: " . $e->getMessage());
        }

        // Create inbox message for homeowner
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS homeowner_inbox (
                id INT AUTO_INCREMENT PRIMARY KEY,
                homeowner_id INT NOT NULL,
                contractor_id INT NOT NULL,
                estimate_id INT NULL,
                type VARCHAR(50) DEFAULT 'estimate',
                title VARCHAR(255) NOT NULL,
                message TEXT NULL,
                payload JSON NULL,
                status VARCHAR(50) DEFAULT 'unread',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_homeowner (homeowner_id),
                INDEX idx_contractor (contractor_id),
                INDEX idx_estimate (estimate_id)
            )");

            $inboxTitle = "New Estimate from {$contractorName}";
            $inboxMessage = "You have received a detailed cost estimate for your project '{$projectName}'. Please review the estimate and let us know if you would like to proceed.";
            
            $inboxPayload = json_encode([
                'estimate_id' => $estimateId,
                'contractor_name' => $contractorName,
                'contractor_email' => $contractorEmail,
                'project_name' => $projectName,
                'total_cost' => $input['totals']['grand'] ?? 0,
                'timeline' => $timeline,
                'materials_total' => $input['totals']['materials'] ?? 0,
                'labor_total' => $input['totals']['labor'] ?? 0,
                'utilities_total' => $input['totals']['utilities'] ?? 0,
                'misc_total' => $input['totals']['misc'] ?? 0
            ]);

            $inboxStmt = $db->prepare("
                INSERT INTO homeowner_inbox (
                    homeowner_id, contractor_id, estimate_id, type, title, message, payload
                ) VALUES (
                    :homeowner_id, :contractor_id, :estimate_id, 'estimate', :title, :message, :payload
                )
            ");
            $inboxStmt->bindParam(':homeowner_id', $homeownerId, PDO::PARAM_INT);
            $inboxStmt->bindParam(':contractor_id', $contractorId, PDO::PARAM_INT);
            $inboxStmt->bindParam(':estimate_id', $estimateId, PDO::PARAM_INT);
            $inboxStmt->bindParam(':title', $inboxTitle);
            $inboxStmt->bindParam(':message', $inboxMessage);
            $inboxStmt->bindParam(':payload', $inboxPayload);
            $inboxStmt->execute();
        } catch (Exception $e) {
            // Inbox message creation failed, but estimate was created successfully
            error_log("Failed to create inbox message: " . $e->getMessage());
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Estimate submitted successfully!',
            'estimate_id' => $estimateId,
            'data' => [
                'project_name' => $projectName,
                'total_cost' => $input['totals']['grand'] ?? 0,
                'timeline' => $timeline,
                'contractor_name' => $contractorName
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit estimate']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>