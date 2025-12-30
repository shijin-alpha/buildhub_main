<?php
// Load email configuration
require_once __DIR__ . '/../config/email_config.php';

function sendMail($to, $subject, $message, $from = null) {
    // Use configured email if no from address provided
    if (!$from) {
        $from = SMTP_FROM_EMAIL;
    }
    
    // Try SMTP first, fallback to basic mail
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/smtp_mail.php';
        return sendSMTPMail($to, $subject, $message, $from);
    } else {
        return send_mail($to, $subject, $message, $from);
    }
}

function send_mail($to, $subject, $message, $from = null) {
    try {
        // Use configured email if no from address provided
        if (!$from) {
            $from = SMTP_FROM_EMAIL;
        }
        
        // Check if we're in development or production mode
        if (EMAIL_MODE === 'development') {
            // DEVELOPMENT MODE: Log emails instead of sending
            $logMessage = "=== EMAIL SIMULATION ===\n";
            $logMessage .= "To: $to\n";
            $logMessage .= "From: $from\n";
            $logMessage .= "Subject: $subject\n";
            $logMessage .= "Message: " . strip_tags($message) . "\n";
            $logMessage .= "========================\n\n";
            
            error_log($logMessage);
            
            // Always return true in development mode
            return true;
            
        } else {
            // PRODUCTION MODE: Send actual emails
            $headers = "From: $from\r\n";
            $headers .= "Reply-To: $from\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "X-Mailer: BuildHub Platform\r\n";
            
            $result = @mail($to, $subject, $message, $headers);
            
            if (!$result) {
                error_log("Mail sending failed to: $to, subject: $subject");
            } else {
                error_log("Mail sent successfully to: $to, subject: $subject");
            }
            
            return $result;
        }
        
    } catch (Exception $e) {
        error_log("Mail function error: " . $e->getMessage());
        return false;
    }
}

// Function to send email using SMTP (for better delivery in production)
function send_smtp_mail($to, $subject, $message, $from = null) {
    // This would require PHPMailer or similar library
    // For now, falls back to regular mail() function
    return send_mail($to, $subject, $message, $from);
}
?>