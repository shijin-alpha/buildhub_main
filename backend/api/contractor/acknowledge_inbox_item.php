<?php
header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) { header('Access-Control-Allow-Origin: ' . $origin); header('Vary: Origin'); } else { header('Access-Control-Allow-Origin: http://localhost'); }
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); header('Access-Control-Max-Age: 86400'); exit; }

require_once '../../config/database.php';
require_once '../../utils/send_mail.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $contractorId = isset($input['contractor_id']) ? (int)$input['contractor_id'] : 0;
    $dueDate = isset($input['due_date']) ? trim((string)$input['due_date']) : null; // YYYY-MM-DD
    if ($id <= 0 || $contractorId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing id or contractor_id']);
        exit;
    }

    // Ensure columns exist
    try {
        $cols = $db->query("SHOW COLUMNS FROM contractor_layout_sends")->fetchAll(PDO::FETCH_COLUMN, 0);
        if ($cols && !in_array('acknowledged_at', $cols)) {
            $db->exec("ALTER TABLE contractor_layout_sends ADD COLUMN acknowledged_at DATETIME NULL AFTER created_at");
        }
        if ($cols && !in_array('due_date', $cols)) {
            $db->exec("ALTER TABLE contractor_layout_sends ADD COLUMN due_date DATE NULL AFTER acknowledged_at");
        }
    } catch (Throwable $e) {}

    // First, get the homeowner_id and layout details for notification
    $getItemStmt = $db->prepare("SELECT homeowner_id, layout_id, design_id, payload FROM contractor_layout_sends WHERE id = :id AND contractor_id = :cid");
    $getItemStmt->bindValue(':id', $id, PDO::PARAM_INT);
    $getItemStmt->bindValue(':cid', $contractorId, PDO::PARAM_INT);
    $getItemStmt->execute();
    $itemData = $getItemStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$itemData) {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }
    
    $homeownerId = $itemData['homeowner_id'];

    $sql = "UPDATE contractor_layout_sends SET acknowledged_at = NOW(), due_date = :due WHERE id = :id AND contractor_id = :cid";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':due', $dueDate ?: null, $dueDate ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':cid', $contractorId, PDO::PARAM_INT);
    $stmt->execute();

    // Create notification and send email to homeowner if they exist
    if ($homeownerId) {
        try {
            // Get contractor details
            $contractorStmt = $db->prepare("SELECT first_name, last_name, email FROM users WHERE id = :id");
            $contractorStmt->bindValue(':id', $contractorId, PDO::PARAM_INT);
            $contractorStmt->execute();
            $contractorData = $contractorStmt->fetch(PDO::FETCH_ASSOC);
            $contractorName = trim(($contractorData['first_name'] ?? '') . ' ' . ($contractorData['last_name'] ?? '')) ?: 'Contractor';
            $contractorEmail = $contractorData['email'] ?? '';
            
            // Get homeowner details
            $homeownerStmt = $db->prepare("SELECT first_name, last_name, email FROM users WHERE id = :id");
            $homeownerStmt->bindValue(':id', $homeownerId, PDO::PARAM_INT);
            $homeownerStmt->execute();
            $homeownerData = $homeownerStmt->fetch(PDO::FETCH_ASSOC);
            $homeownerName = trim(($homeownerData['first_name'] ?? '') . ' ' . ($homeownerData['last_name'] ?? '')) ?: 'Homeowner';
            $homeownerEmail = $homeownerData['email'] ?? '';
            
            // Parse payload to get technical details
            $payload = [];
            if (!empty($itemData['payload'])) {
                $decoded = json_decode($itemData['payload'], true);
                if (is_array($decoded)) $payload = $decoded;
            }
            
            // Get technical details from payload
            $technicalDetails = null;
            if (isset($payload['technical_details']) && is_array($payload['technical_details'])) {
                $technicalDetails = $payload['technical_details'];
            } else if (isset($payload['forwarded_design']['technical_details']) && is_array($payload['forwarded_design']['technical_details'])) {
                $technicalDetails = $payload['forwarded_design']['technical_details'];
            }
            
            // Get layout/design details
            $layoutTitle = 'Layout';
            $layoutDetails = [];
            if ($itemData['layout_id']) {
                $layoutStmt = $db->prepare("SELECT title, layout_type, bedrooms, bathrooms, area FROM layout_library WHERE id = :id");
                $layoutStmt->bindValue(':id', $itemData['layout_id'], PDO::PARAM_INT);
                $layoutStmt->execute();
                $layoutData = $layoutStmt->fetch(PDO::FETCH_ASSOC);
                if ($layoutData) {
                    $layoutTitle = $layoutData['title'] ?? 'Layout';
                    $layoutDetails = [
                        'type' => $layoutData['layout_type'] ?? '',
                        'bedrooms' => $layoutData['bedrooms'] ?? '',
                        'bathrooms' => $layoutData['bathrooms'] ?? '',
                        'area' => $layoutData['area'] ?? ''
                    ];
                }
            }
            
            // Ensure homeowner_notifications table exists
            $db->exec("CREATE TABLE IF NOT EXISTS homeowner_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                homeowner_id INT NOT NULL,
                contractor_id INT NULL,
                type VARCHAR(50) DEFAULT 'acknowledgment',
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                status ENUM('unread', 'read') DEFAULT 'unread',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX(homeowner_id), INDEX(status), INDEX(type)
            )");
            
            // Insert notification
            $ackTime = date('Y-m-d H:i:s');
            $ackDate = $dueDate ? date('F j, Y', strtotime($dueDate)) : 'not specified';
            $title = "Contractor Acknowledged Your Layout";
            $notificationMessage = "{$contractorName} acknowledged your layout at {$ackTime}.\nDue date: {$ackDate}";
            
            $notifStmt = $db->prepare("INSERT INTO homeowner_notifications (homeowner_id, contractor_id, type, title, message, status) VALUES (:hid, :cid, 'acknowledgment', :title, :msg, 'unread')");
            $notifStmt->bindValue(':hid', $homeownerId, PDO::PARAM_INT);
            $notifStmt->bindValue(':cid', $contractorId, PDO::PARAM_INT);
            $notifStmt->bindValue(':title', $title);
            $notifStmt->bindValue(':msg', $notificationMessage);
            $notifStmt->execute();
            
            // Also send a message to the messages table for the Messages tab
            try {
                // Create messages table if it doesn't exist
                $db->exec("CREATE TABLE IF NOT EXISTS messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    from_user_id INT NOT NULL,
                    to_user_id INT NOT NULL,
                    subject VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    message_type VARCHAR(50) DEFAULT 'acknowledgment',
                    related_id INT NULL,
                    is_read BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE
                )");
                
                // Create the acknowledgment message
                $subject = "Layout Request Acknowledged - {$layoutTitle}";
                $due_text = $dueDate ? "Expected completion: " . date('F j, Y', strtotime($dueDate)) : "Due date to be confirmed";
                $message_text = "Hello! I have acknowledged your layout request for '{$layoutTitle}' and will begin working on your estimate. {$due_text}. I'll keep you updated on the progress.";
                
                // Insert the message
                $messageStmt = $db->prepare("
                    INSERT INTO messages (from_user_id, to_user_id, subject, message, message_type, created_at) 
                    VALUES (?, ?, ?, ?, 'acknowledgment', NOW())
                ");
                $messageStmt->execute([$contractorId, $homeownerId, $subject, $message_text]);
                
                error_log("Acknowledgment message sent to homeowner messages");
            } catch (Exception $msgError) {
                error_log("Failed to send acknowledgment message: " . $msgError->getMessage());
            }
            
            // Send email to homeowner
            if ($homeownerEmail) {
                try {
                    $formattedDate = $dueDate ? date('F j, Y', strtotime($dueDate)) : 'Not specified';
                    $layoutInfo = '';
                    if (!empty($layoutDetails)) {
                        $parts = array_filter([
                            $layoutDetails['type'] ? 'Type: ' . $layoutDetails['type'] : '',
                            $layoutDetails['bedrooms'] ? $layoutDetails['bedrooms'] . ' BHK' : '',
                            $layoutDetails['area'] ? $layoutDetails['area'] . ' sq ft' : ''
                        ]);
                        if (!empty($parts)) {
                            $layoutInfo = ' (' . implode(', ', $parts) . ')';
                        }
                    }
                    
                    $emailSubject = "Contractor Acknowledged Your Estimate Request - {$layoutTitle}";
                    
                    $emailBody = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset='UTF-8'>
                        <style>
                            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9fafb; }
                            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; border-radius: 10px 10px 0 0; }
                            .header h1 { margin: 0; font-size: 24px; }
                            .content { background: white; padding: 30px 20px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                            .info-box { background: #f0f9ff; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 5px; }
                            .info-row { margin: 10px 0; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
                            .info-row:last-child { border-bottom: none; }
                            .info-label { font-weight: 600; color: #374151; display: inline-block; width: 150px; }
                            .info-value { color: #6b7280; }
                            .cta-button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 20px; font-weight: 600; }
                            .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 14px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>✅ Layout Acknowledgment</h1>
                                <p style='margin: 5px 0; opacity: 0.9;'>Your estimate request has been acknowledged</p>
                            </div>
                            <div class='content'>
                                <p>Dear {$homeownerName},</p>
                                
                                <p>We're pleased to inform you that <strong>{$contractorName}</strong> has acknowledged your layout request and is now reviewing the details.</p>
                                
                                <div class='info-box'>
                                    <h3 style='margin: 0 0 15px 0; color: #1f2937;'>Request Details</h3>
                                    <div class='info-row'>
                                        <span class='info-label'>Layout:</span>
                                        <span class='info-value'><strong>{$layoutTitle}{$layoutInfo}</strong></span>
                                    </div>
                                    <div class='info-row'>
                                        <span class='info-label'>Contractor:</span>
                                        <span class='info-value'>{$contractorName}</span>
                                    </div>
                                    <div class='info-row'>
                                        <span class='info-label'>Email:</span>
                                        <span class='info-value'>{$contractorEmail}</span>
                                    </div>
                                    <div class='info-row'>
                                        <span class='info-label'>Acknowledged:</span>
                                        <span class='info-value'>{$ackTime}</span>
                                    </div>
                                    <div class='info-row'>
                                        <span class='info-label'>Expected Due Date:</span>
                                        <span class='info-value'><strong style='color: #059669;'>{$formattedDate}</strong></span>
                                    </div>"
                    . (($technicalDetails && !empty($technicalDetails)) ? 
                       "<div class='info-row'>
                           <span class='info-label'>Technical Details:</span>
                           <span class='info-value'><strong style='color: #667eea;'>Available</strong></span>
                        </div>" : "") . "
                                </div>
                                
                                " . (($technicalDetails && !empty($technicalDetails)) ? 
                                "<p style='background: #f0f9ff; border-left: 4px solid #3b82f6; padding: 12px; border-radius: 6px; margin: 20px 0;'>
                                    <strong>📋 Technical Details:</strong><br>
                                    The contractor has access to all technical specifications including room dimensions, structural elements, 
                                    material specifications, and construction details. They will reference these when preparing your estimate.
                                </p>" : "") . "
                                
                                <p>The contractor is now working on preparing your detailed estimate. You'll be notified once they submit their proposal.</p>
                                
                                <a href='http://localhost/buildhub/#/homeowner/dashboard' class='cta-button'>View in Dashboard</a>
                                
                                <p style='margin-top: 30px; color: #6b7280; font-size: 14px;'>
                                    Best regards,<br>
                                    <strong>The BuildHub Team</strong>
                                </p>
                                
                                <div class='footer'>
                                    <p>This is an automated notification from BuildHub.</p>
                                    <p>If you have any questions, please contact us.</p>
                                </div>
                            </div>
                        </div>
                    </body>
                    </html>";
                    
                    sendMail($homeownerEmail, $emailSubject, $emailBody);
                    error_log("Acknowledgment email sent to homeowner: {$homeownerEmail}");
                } catch (Exception $emailError) {
                    error_log("Failed to send acknowledgment email: " . $emailError->getMessage());
                }
            }
        } catch (Throwable $e) {
            // Notification creation failed, but acknowledgment succeeded
            error_log("Failed to create homeowner notification: " . $e->getMessage());
        }
    }

    echo json_encode(['success' => true, 'acknowledged_at' => date('Y-m-d H:i:s')]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error acknowledging item: ' . $e->getMessage()]);
}















