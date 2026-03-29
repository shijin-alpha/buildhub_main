<?php
// Test real email sending

echo "🧪 Testing Real Email Sending\n";
echo "==============================\n\n";

// Load configuration
require_once __DIR__ . '/config/email_config.php';
require_once __DIR__ . '/utils/send_mail.php';

echo "📧 Current Configuration:\n";
echo "   From Email: " . SMTP_FROM_EMAIL . "\n";
echo "   SMTP Host: " . SMTP_HOST . "\n";
echo "   SMTP Port: " . SMTP_PORT . "\n";
echo "   Mode: " . EMAIL_MODE . "\n";
echo "   Password Set: " . (SMTP_PASSWORD !== 'PASTE_YOUR_APP_PASSWORD_HERE' ? 'Yes' : 'No') . "\n\n";

// Check if app password is set
if (SMTP_PASSWORD === 'PASTE_YOUR_APP_PASSWORD_HERE') {
    echo "❌ Gmail App Password not set!\n";
    echo "\n🔧 To fix this:\n";
    echo "   1. Get Gmail App Password from Google Account\n";
    echo "   2. Edit: backend/config/email_config.php\n";
    echo "   3. Replace 'PASTE_YOUR_APP_PASSWORD_HERE' with your app password\n";
    echo "   4. Run this test again\n\n";
    exit;
}

// Ask user for test email
echo "📮 Enter email address to send test email to: ";
$testEmail = trim(fgets(STDIN));

if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Invalid email address!\n";
    exit;
}

echo "\n🚀 Sending test email to: $testEmail\n";

// Test email content
$subject = "BuildHub Test Email - Real SMTP";
$message = "
<html>
<head><title>BuildHub Test Email</title></head>
<body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
    <h2 style='color: #28a745;'>✅ Email System Working!</h2>
    <p>Hello!</p>
    <p>This is a test email from your BuildHub admin panel.</p>
    <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
        <h3>System Information:</h3>
        <p><strong>From:</strong> " . SMTP_FROM_EMAIL . "</p>
        <p><strong>Sent via:</strong> Gmail SMTP</p>
        <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
    </div>
    <p>If you received this email, your BuildHub email system is working correctly!</p>
    <p>Best regards,<br>BuildHub Admin Panel</p>
</body>
</html>
";

// Send the email
$result = sendMail($testEmail, $subject, $message);

if ($result) {
    echo "✅ Email sent successfully!\n";
    echo "📬 Check the inbox of: $testEmail\n";
    echo "📁 Also check spam/junk folder if not in inbox\n\n";
    
    echo "🎉 Your email system is now working!\n";
    echo "📋 Next steps:\n";
    echo "   1. Test user approval/rejection in admin panel\n";
    echo "   2. Users will receive actual emails\n";
    echo "   3. Change EMAIL_MODE to 'production' in config if not already\n\n";
} else {
    echo "❌ Email sending failed!\n";
    echo "🔍 Check the error log for details\n";
    echo "📍 Common issues:\n";
    echo "   - Wrong Gmail App Password\n";
    echo "   - 2-Step Verification not enabled\n";
    echo "   - Firewall blocking SMTP\n\n";
}

echo "Test completed.\n";
?>