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
    
    // Get architect ID from session
    $user = json_decode($_SESSION['user'] ?? '{}', true);
    $architect_id = $user['id'] ?? null;
    
    if (!$architect_id) {
        echo json_encode([
            'success' => false,
            'message' => 'User not logged in'
        ]);
        exit;
    }
    
    // Get POST data
    $layout_request_id = $_POST['layout_request_id'] ?? '';
    $design_type = $_POST['design_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $template_id = $_POST['template_id'] ?? null;
    $notes = $_POST['notes'] ?? '';
    
    // Validate required fields
    if (empty($layout_request_id) || empty($design_type) || empty($description)) {
        echo json_encode([
            'success' => false,
            'message' => 'All required fields must be filled'
        ]);
        exit;
    }
    
    // Check if architect already submitted a layout for this request
    $checkQuery = "SELECT id FROM architect_layouts WHERE architect_id = :architect_id AND layout_request_id = :layout_request_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':architect_id', $architect_id);
    $checkStmt->bindParam(':layout_request_id', $layout_request_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'You have already submitted a layout for this project'
        ]);
        exit;
    }
    
    $layout_file = null;
    
    // Handle file upload for custom designs
    if ($design_type === 'custom' && isset($_FILES['layout_file'])) {
        $uploadDir = __DIR__ . '/../../uploads/layouts/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $file = $_FILES['layout_file'];
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
        $filePath = $uploadDir . $fileName;
        
        // Validate file type
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid file type. Only PDF and image files are allowed.'
            ]);
            exit;
        }
        
        // Validate file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            echo json_encode([
                'success' => false,
                'message' => 'File size too large. Maximum 10MB allowed.'
            ]);
            exit;
        }
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $layout_file = $fileName;
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to upload file'
            ]);
            exit;
        }
    }
    
    // Insert layout
    $query = "INSERT INTO architect_layouts 
              (architect_id, layout_request_id, design_type, description, layout_file, template_id, notes, status, created_at) 
              VALUES 
              (:architect_id, :layout_request_id, :design_type, :description, :layout_file, :template_id, :notes, 'pending', NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':architect_id', $architect_id);
    $stmt->bindParam(':layout_request_id', $layout_request_id);
    $stmt->bindParam(':design_type', $design_type);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':layout_file', $layout_file);
    $stmt->bindParam(':template_id', $template_id);
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
        
        // Get architect details
        $architectQuery = "SELECT first_name, last_name FROM users WHERE id = :architect_id";
        $architectStmt = $db->prepare($architectQuery);
        $architectStmt->bindParam(':architect_id', $architect_id);
        $architectStmt->execute();
        $architect = $architectStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($homeowner && $architect) {
            // Send email notification to homeowner
            $subject = "New Layout Design - BuildHub";
            $message = "<html><head><title>New Layout Design</title></head><body>";
            $message .= "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>";
            $message .= "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 30px;'>";
            $message .= "<h1 style='margin: 0; font-size: 2.5em;'>🏛️</h1>";
            $message .= "<h2 style='margin: 10px 0 0 0;'>New Layout Design!</h2>";
            $message .= "</div>";
            
            $message .= "<div style='background: #f8f9fa; padding: 25px; border-radius: 8px; border-left: 4px solid #28a745; margin-bottom: 20px;'>";
            $message .= "<h3 style='color: #28a745; margin-top: 0;'>Exciting News!</h3>";
            $message .= "<p>Dear " . htmlspecialchars($homeowner['first_name']) . " " . htmlspecialchars($homeowner['last_name']) . ",</p>";
            $message .= "<p>You have received a new layout design from architect <strong>" . htmlspecialchars($architect['first_name']) . " " . htmlspecialchars($architect['last_name']) . "</strong> for your project.</p>";
            $message .= "</div>";
            
            $message .= "<div style='background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6; margin-bottom: 20px;'>";
            $message .= "<h3>Design Details:</h3>";
            $message .= "<p><strong>Design Type:</strong> " . htmlspecialchars($design_type === 'custom' ? 'Custom Design' : 'Template Based') . "</p>";
            $message .= "<p><strong>Description:</strong></p>";
            $message .= "<p style='background: #f8f9fa; padding: 10px; border-radius: 4px;'>" . nl2br(htmlspecialchars($description)) . "</p>";
            if ($notes) {
                $message .= "<p><strong>Architect's Notes:</strong></p>";
                $message .= "<p style='background: #f8f9fa; padding: 10px; border-radius: 4px;'>" . nl2br(htmlspecialchars($notes)) . "</p>";
            }
            $message .= "</div>";
            
            $message .= "<div style='text-align: center; margin: 30px 0;'>";
            $message .= "<a href='http://localhost:3000/login' style='background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>View Layout Design</a>";
            $message .= "</div>";
            
            $message .= "<p style='color: #666; font-size: 12px; text-align: center;'>This email was sent from BuildHub. Please log in to your account to view the complete design and provide feedback.</p>";
            $message .= "</div></body></html>";
            
            sendMail($homeowner['email'], $subject, $message);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Layout submitted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit layout'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error submitting layout: ' . $e->getMessage()
    ]);
}
?>