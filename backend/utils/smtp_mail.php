<?php
// SMTP Email sending using PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendSMTPMail($to, $subject, $message, $from = null) {
    try {
        // Use configured email if no from address provided
        if (!$from) {
            $from = SMTP_FROM_EMAIL;
        }
        
        // Check if we're in development or production mode
        if (EMAIL_MODE === 'development') {
            // DEVELOPMENT MODE: Log emails instead of sending
            $logMessage = "=== EMAIL SIMULATION (SMTP) ===\n";
            $logMessage .= "To: $to\n";
            $logMessage .= "From: $from\n";
            $logMessage .= "Subject: $subject\n";
            $logMessage .= "Message: " . strip_tags($message) . "\n";
            $logMessage .= "===============================\n\n";
            
            error_log($logMessage);
            return true;
        }
        
        // PRODUCTION MODE: Send actual emails via SMTP
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom($from, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo($from, SMTP_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);
        
        // Send email
        $result = $mail->send();
        
        if ($result) {
            error_log("SMTP Mail sent successfully to: $to, subject: $subject");
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("SMTP Mail error: " . $e->getMessage());
        return false;
    }
}

// Note: sendMail function is defined in send_mail.php
?>