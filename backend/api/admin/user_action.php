<?php
// Disable HTML error output for clean JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Set up error handler to catch any PHP errors and return JSON
set_error_handler(function($severity, $message, $file, $line) {
    $response = ['success' => false, 'message' => 'Server error occurred. Please try again.'];
    echo json_encode($response);
    exit();
});

try {
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../utils/send_mail.php';
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Database connection failed.'];
    echo json_encode($response);
    exit();
}

$response = ['success' => false, 'message' => ''];

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['user_id']) || !isset($input['action'])) {
        $response['message'] = 'Missing required parameters.';
        echo json_encode($response);
        exit;
    }
    
    $userId = (int)$input['user_id'];
    $action = $input['action'];
    
    if (!in_array($action, ['approve', 'reject'])) {
        $response['message'] = 'Invalid action.';
        echo json_encode($response);
        exit;
    }
    
    // Get user details first
    $stmt = $pdo->prepare("SELECT first_name, last_name, email, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $response['message'] = 'User not found.';
        echo json_encode($response);
        exit;
    }
    
    if ($action === 'approve') {
        // Check if status column exists
        $colStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
        $hasStatus = $colStmt && $colStmt->rowCount() > 0;
        
        // Use transaction to ensure both fields are updated atomically
        $pdo->beginTransaction();
        
        try {
            // Update user to verified and set status to approved if column exists
            if ($hasStatus) {
                $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, status = 'approved', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            }
            $result = $stmt->execute([$userId]);
            
            if (!$result) {
                throw new Exception("Failed to update user status");
            }
            
            // Verify the update was successful
            $verifyStmt = $pdo->prepare("SELECT is_verified, status FROM users WHERE id = ?");
            $verifyStmt->execute([$userId]);
            $verifyUser = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$verifyUser || $verifyUser['is_verified'] != 1) {
                throw new Exception("User verification update failed");
            }
            
            if ($hasStatus && $verifyUser['status'] !== 'approved') {
                throw new Exception("User status update failed");
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
        if ($result) {
            // Send approval email
            $subject = "BuildHub Account Approved - Welcome!";
            $message = "<html><head><title>Account Approved</title></head><body>";
            $message .= "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>";
            $message .= "<h2 style='color: #28a745;'>🎉 Congratulations! Your Account Has Been Approved</h2>";
            $message .= "<p>Dear " . htmlspecialchars($user['first_name']) . " " . htmlspecialchars($user['last_name']) . ",</p>";
            $message .= "<p>Great news! Your BuildHub account has been approved by our admin team.</p>";
            $message .= "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
            $message .= "<h3 style='color: #333; margin-top: 0;'>Account Details:</h3>";
            $message .= "<p><strong>Name:</strong> " . htmlspecialchars($user['first_name']) . " " . htmlspecialchars($user['last_name']) . "</p>";
            $message .= "<p><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</p>";
            $message .= "<p><strong>Role:</strong> " . ucfirst(htmlspecialchars($user['role'])) . "</p>";
            $message .= "<p><strong>Status:</strong> <span style='color: #28a745; font-weight: bold;'>✅ APPROVED</span></p>";
            $message .= "</div>";
            $message .= "<p><strong>You can now log in to your account and start using BuildHub!</strong></p>";
            $message .= "<div style='text-align: center; margin: 30px 0;'>";
            $message .= "<a href='http://localhost:3000/login' style='background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Login to BuildHub</a>";
            $message .= "</div>";
            $message .= "<p>As a verified " . htmlspecialchars($user['role']) . ", you now have access to:</p>";
            $message .= "<ul><li>Project opportunities from homeowners</li><li>Professional networking features</li><li>Advanced project management tools</li><li>Direct communication with clients</li></ul>";
            $message .= "<p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>";
            $message .= "<p>Welcome to the BuildHub community!</p>";
            $message .= "<hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>";
            $message .= "<p style='color: #666; font-size: 12px;'>This is an automated message from BuildHub. Please do not reply to this email.</p>";
            $message .= "</div></body></html>";
            
            // Send email
            $emailSent = false;
            try {
                $emailSent = sendMail($user['email'], $subject, $message);
            } catch (Exception $e) {
                error_log("Email sending failed: " . $e->getMessage());
                // Don't fail the approval if email fails
            }
            
            $response['success'] = true;
            $emailStatus = $emailSent ? "and notification email sent" : "(email simulated - check logs)";
            $response['message'] = "User approved successfully " . $emailStatus . ".";
        } else {
            $response['message'] = 'Failed to approve user.';
        }
        
    } else if ($action === 'reject') {
        // Check if status column exists
        $colStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
        $hasStatus = $colStmt && $colStmt->rowCount() > 0;
        
        // Use transaction to ensure both fields are updated atomically
        $pdo->beginTransaction();
        
        try {
            // Mark user as rejected instead of deleting
            if ($hasStatus) {
                $stmt = $pdo->prepare("UPDATE users SET is_verified = 0, status = 'rejected', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE users SET is_verified = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            }
            $result = $stmt->execute([$userId]);
            
            if (!$result) {
                throw new Exception("Failed to update user status");
            }
            
            // Verify the update was successful
            $verifyStmt = $pdo->prepare("SELECT is_verified, status FROM users WHERE id = ?");
            $verifyStmt->execute([$userId]);
            $verifyUser = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$verifyUser || $verifyUser['is_verified'] != 0) {
                throw new Exception("User verification update failed");
            }
            
            if ($hasStatus && $verifyUser['status'] !== 'rejected') {
                throw new Exception("User status update failed");
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
        if ($result) {
            // Send rejection email
            $subject = "BuildHub Account Application Update";
            $message = "<html><head><title>Account Application Update</title></head><body>";
            $message .= "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>";
            $message .= "<h2 style='color: #dc3545;'>Account Application Update</h2>";
            $message .= "<p>Dear " . htmlspecialchars($user['first_name']) . " " . htmlspecialchars($user['last_name']) . ",</p>";
            $message .= "<p>Thank you for your interest in joining BuildHub as a " . htmlspecialchars($user['role']) . ".</p>";
            $message .= "<p>After careful review of your application and submitted documents, we regret to inform you that we cannot approve your account at this time.</p>";
            $message .= "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
            $message .= "<h3 style='color: #333; margin-top: 0;'>Common reasons for rejection:</h3>";
            $message .= "<ul><li>Incomplete or unclear documentation</li><li>Documents that don't meet our verification standards</li><li>Missing required professional certifications</li></ul>";
            $message .= "</div>";
            $message .= "<p><strong>You're welcome to reapply</strong> with updated documentation that meets our requirements.</p>";
            $message .= "<div style='text-align: center; margin: 30px 0;'>";
            $message .= "<a href='http://localhost:3000/register' style='background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Apply Again</a>";
            $message .= "</div>";
            $message .= "<p>If you have questions about this decision or need clarification on our requirements, please contact our support team.</p>";
            $message .= "<p>Thank you for your understanding.</p>";
            $message .= "<hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>";
            $message .= "<p style='color: #666; font-size: 12px;'>This is an automated message from BuildHub. Please do not reply to this email.</p>";
            $message .= "</div></body></html>";
            
            // Send email
            $emailSent = false;
            try {
                $emailSent = sendMail($user['email'], $subject, $message);
            } catch (Exception $e) {
                error_log("Email sending failed: " . $e->getMessage());
                // Don't fail the rejection if email fails
            }
            
            $response['success'] = true;
            $emailStatus = $emailSent ? "Notification email sent" : "(email simulated - check logs)";
            $response['message'] = "User rejected successfully. " . $emailStatus . ".";
        } else {
            $response['message'] = 'Failed to reject user.';
        }
    }
    
} catch (PDOException $e) {
    error_log("User action error: " . $e->getMessage());
    $response['message'] = 'Database error occurred.';
} catch (Exception $e) {
    error_log("User action error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred.';
}

echo json_encode($response);
?>