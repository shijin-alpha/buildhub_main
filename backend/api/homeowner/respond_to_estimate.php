<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/send_mail.php';

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $homeownerId = isset($input['homeowner_id']) ? (int)$input['homeowner_id'] : 0;
    $estimateId = isset($input['estimate_id']) ? (int)$input['estimate_id'] : 0;
    $action = isset($input['action']) ? trim(strtolower($input['action'])) : '';
    $message = isset($input['message']) ? trim($input['message']) : '';

    if ($homeownerId <= 0 || $estimateId <= 0 || !in_array($action, ['accept','changes','reject'], true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Ensure required columns exist
    $db->exec("CREATE TABLE IF NOT EXISTS contractor_send_estimates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        send_id INT NOT NULL,
        contractor_id INT NOT NULL,
        materials TEXT NULL,
        cost_breakdown TEXT NULL,
        total_cost DECIMAL(15,2) NULL,
        timeline VARCHAR(255) NULL,
        notes TEXT NULL,
        structured LONGTEXT NULL,
        status VARCHAR(32) DEFAULT 'submitted',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(send_id), INDEX(contractor_id)
    )");
    try { $db->exec("ALTER TABLE contractor_send_estimates ADD COLUMN homeowner_feedback TEXT NULL"); } catch (Throwable $e) {}
    try { $db->exec("ALTER TABLE contractor_send_estimates ADD COLUMN homeowner_action_at DATETIME NULL"); } catch (Throwable $e) {}

    // Get estimate details with contractor and homeowner information
    $q = $db->prepare("
        SELECT e.id, e.contractor_id, e.total_cost, e.timeline, e.structured,
               c.first_name as contractor_first_name, c.last_name as contractor_last_name, c.email as contractor_email,
               h.first_name as homeowner_first_name, h.last_name as homeowner_last_name, h.email as homeowner_email
        FROM contractor_send_estimates e 
        INNER JOIN contractor_layout_sends s ON s.id = e.send_id 
        INNER JOIN users c ON c.id = e.contractor_id
        INNER JOIN users h ON h.id = s.homeowner_id
        WHERE e.id = :eid AND s.homeowner_id = :hid
    ");
    $q->bindValue(':eid', $estimateId, PDO::PARAM_INT);
    $q->bindValue(':hid', $homeownerId, PDO::PARAM_INT);
    $q->execute();
    $row = $q->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Estimate not found for this homeowner']);
        exit;
    }

    $newStatus = $action === 'accept' ? 'accepted' : ($action === 'changes' ? 'changes_requested' : 'rejected');
    $upd = $db->prepare("UPDATE contractor_send_estimates SET status = :st, homeowner_feedback = :fb, homeowner_action_at = NOW() WHERE id = :eid");
    $upd->bindValue(':st', $newStatus, PDO::PARAM_STR);
    $upd->bindValue(':fb', $message !== '' ? $message : null, $message !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $upd->bindValue(':eid', $estimateId, PDO::PARAM_INT);
    $upd->execute();

    // If homeowner accepted, send email notification to contractor
    if ($action === 'accept' && !empty($row['contractor_email'])) {
        $contractorName = trim($row['contractor_first_name'] . ' ' . $row['contractor_last_name']);
        $homeownerName = trim($row['homeowner_first_name'] . ' ' . $row['homeowner_last_name']);
        $totalCost = $row['total_cost'] ? '₹' . number_format($row['total_cost'], 2) : 'Not specified';
        $timeline = $row['timeline'] ?: 'Not specified';
        
        // Parse structured data if available
        $structured = null;
        if (!empty($row['structured'])) {
            $structured = json_decode($row['structured'], true);
        }
        
        // Build email subject and message
        $subject = "Estimate Accepted - Ready to Start Work - BuildHub";
        
        $emailBody = "<!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 30px; }
                .header h1 { margin: 0; font-size: 2.5em; }
                .header h2 { margin: 10px 0 0 0; }
                .content-box { background: #f8f9fa; padding: 25px; border-radius: 8px; border-left: 4px solid #28a745; margin-bottom: 20px; }
                .content-box h3 { color: #28a745; margin-top: 0; }
                .details { background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6; margin-bottom: 20px; }
                .details p { margin: 10px 0; }
                .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✓</h1>
                    <h2>Estimate Accepted!</h2>
                </div>
                
                <div class='content-box'>
                    <h3>Great News!</h3>
                    <p>Dear $contractorName,</p>
                    <p><strong>$homeownerName</strong> has accepted your estimate and is ready to proceed with the construction project.</p>
                    <p>You can now start the work as discussed in your estimate.</p>
                </div>
                
                <div class='details'>
                    <h3>Project Details:</h3>
                    <p><strong>Estimated Cost:</strong> $totalCost</p>
                    <p><strong>Timeline:</strong> $timeline</p>";

        // Add homeowner message if provided
        if (!empty($message)) {
            $emailBody .= "<p><strong>Homeowner Message:</strong></p>";
            $emailBody .= "<p style='background: #f8f9fa; padding: 10px; border-radius: 4px;'>" . nl2br(htmlspecialchars($message)) . "</p>";
        }

        $emailBody .= "
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <p style='background: #007bff; color: white; padding: 12px 30px; border-radius: 5px; display: inline-block;'>
                        Ready to Start Construction
                    </p>
                </div>
                
                <div class='footer'>
                    <p>This email was sent from BuildHub.</p>
                    <p>Please log in to your contractor dashboard to view full project details and coordinate with the homeowner.</p>
                </div>
            </div>
        </body>
        </html>";
        
        // Send email to contractor
        @sendMail($row['contractor_email'], $subject, $emailBody);
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}




