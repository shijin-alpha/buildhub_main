<?php
// Test script to verify email functionality

require_once __DIR__ . '/../../utils/send_mail.php';

echo "Testing email functionality...\n\n";

// Test sending an email
$result = sendMail(
    'test@example.com',
    'Test Email Subject',
    '<h1>Test Email</h1><p>This is a test email to verify the email system is working.</p>',
    'admin@buildhub.com'
);

if ($result) {
    echo "✅ Email function returned success!\n";
    echo "📧 Check your PHP error log for the simulated email content.\n\n";
    
    // Show where to find the log
    echo "📍 Common log locations:\n";
    echo "   - XAMPP: C:\\xampp\\php\\logs\\php_error_log\n";
    echo "   - Or check: " . ini_get('error_log') . "\n\n";
    
    echo "🔍 Look for lines starting with '=== EMAIL SIMULATION ==='\n";
} else {
    echo "❌ Email function failed!\n";
}

echo "\nTest completed.\n";
?>