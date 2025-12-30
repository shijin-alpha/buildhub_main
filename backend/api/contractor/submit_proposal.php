<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/send_mail.php';
session_start();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get contractor ID from session
    $user = json_decode($_SESSION['user'] ?? '{}', true);
    $contractor_id = $user['id'] ?? null;
    
    if (!$contractor_id) {
        echo json_encode([
            'success' => false,
            'message' => 'User not logged in'
        ]);
        exit;
    }
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    $layout_request_id = $data['layout_request_id'] ?? '';
    $materials = $data['materials'] ?? '';
    $cost_breakdown = $data['cost_breakdown'] ?? '';
    $total_cost = $data['total_cost'] ?? '';
    $timeline = $data['timeline'] ?? '';
    $notes = $data['notes'] ?? '';
    
    // Validate required fields
    if (empty($layout_request_id) || empty($materials) || empty($cost_breakdown) || empty($total_cost) || empty($timeline)) {
        echo json_encode([
            'success' => false,
            'message' => 'All required fields must be filled'
        ]);
        exit;
    }
    
    // Check if contractor already submitted a proposal for this request
    $checkQuery = "SELECT id FROM contractor_proposals WHERE contractor_id = :contractor_id AND layout_request_id = :layout_request_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':contractor_id', $contractor_id);
    $checkStmt->bindParam(':layout_request_id', $layout_request_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'You have already submitted a proposal for this project'
        ]);
        exit;
    }
    
    // Insert proposal
    $query = "INSERT INTO contractor_proposals 
              (contractor_id, layout_request_id, materials, cost_breakdown, total_cost, timeline, notes, status, created_at) 
              VALUES 
              (:contractor_id, :layout_request_id, :materials, :cost_breakdown, :total_cost, :timeline, :notes, 'pending', NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':contractor_id', $contractor_id);
    $stmt->bindParam(':layout_request_id', $layout_request_id);
    $stmt->bindParam(':materials', $materials);
    $stmt->bindParam(':cost_breakdown', $cost_breakdown);
    $stmt->bindParam(':total_cost', $total_cost);
    $stmt->bindParam(':timeline', $timeline);
    $stmt->bindParam(':notes', $notes);
    
    if ($stmt->execute()) {
        // Get homeowner details for email notification
        $homeownerQuery = "SELECT u.email, u.first_name, u.last_name, lr.requirements 
                          FROM layout_requests lr 
                          JOIN users u ON lr.homeowner_id = u.id 
                          WHERE lr.id = :layout_request_id";
        $homeownerStmt = $db->prepare($homeownerQuery);
        $homeownerStmt->bindParam(':layout_request_id', $layout_request_id);
        $homeownerStmt->execute();
        $homeowner = $homeownerStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get contractor details
        $contractorQuery = "SELECT first_name, last_name FROM users WHERE id = :contractor_id";
        $contractorStmt = $db->prepare($contractorQuery);
        $contractorStmt->bindParam(':contractor_id', $contractor_id);
        $contractorStmt->execute();
        $contractor = $contractorStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($homeowner && $contractor) {
            // Send email notification to homeowner
            $subject = "New Material Proposal - BuildHub";
            $message = "<html><head><title>New Material Proposal</title></head><body>";
            $message .= "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>";
            $message .= "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 30px;'>";
            $message .= "<h1 style='margin: 0; font-size: 2.5em;'>💼</h1>";
            $message .= "<h2 style='margin: 10px 0 0 0;'>New Material Proposal!</h2>";
            $message .= "</div>";
            
            $message .= "<div style='background: #f8f9fa; padding: 25px; border-radius: 8px; border-left: 4px solid #28a745; margin-bottom: 20px;'>";
            $message .= "<h3 style='color: #28a745; margin-top: 0;'>Great News!</h3>";
            $message .= "<p>Dear " . htmlspecialchars($homeowner['first_name']) . " " . htmlspecialchars($homeowner['last_name']) . ",</p>";
            $message .= "<p>You have received a new material proposal from contractor <strong>" . htmlspecialchars($contractor['first_name']) . " " . htmlspecialchars($contractor['last_name']) . "</strong> for your project.</p>";
            $message .= "</div>";
            
            $message .= "<div style='background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6; margin-bottom: 20px;'>";
            $message .= "<h3>Proposal Details:</h3>";
            $message .= "<p><strong>Total Cost:</strong> ₹" . htmlspecialchars($total_cost) . "</p>";
            $message .= "<p><strong>Timeline:</strong> " . htmlspecialchars($timeline) . "</p>";
            $message .= "<p><strong>Materials:</strong></p>";
            $message .= "<p style='background: #f8f9fa; padding: 10px; border-radius: 4px;'>" . nl2br(htmlspecialchars($materials)) . "</p>";
            $message .= "</div>";
            
            $message .= "<div style='text-align: center; margin: 30px 0;'>";
            $message .= "<a href='http://localhost:3000/login' style='background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>View Full Proposal</a>";
            $message .= "</div>";
            
            $message .= "<p style='color: #666; font-size: 12px; text-align: center;'>This email was sent from BuildHub. Please log in to your account to view complete proposal details and respond.</p>";
            $message .= "</div></body></html>";
            
            sendMail($homeowner['email'], $subject, $message);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Proposal submitted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit proposal'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error submitting proposal: ' . $e->getMessage()
    ]);
}
?>