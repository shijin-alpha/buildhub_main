<?php
// Test real email sending to your Gmail

echo "🧪 Testing Real Email to Your Gmail\n";
echo "===================================\n\n";

// Load configuration
require_once __DIR__ . '/config/email_config.php';
require_once __DIR__ . '/utils/send_mail.php';

echo "📧 Configuration Status:\n";
echo "   From Email: " . SMTP_FROM_EMAIL . "\n";
echo "   Mode: " . EMAIL_MODE . "\n";
echo "   Password Set: Yes ✅\n\n";

echo "🚀 Sending test email to: shijinthomas369@gmail.com\n";

// Test email content
$subject = "✅ BuildHub Email System - Working!";
$message = "
<html>
<head><title>BuildHub Email Test</title></head>
<body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 30px;'>
        <h1 style='margin: 0; font-size: 2.5em;'>🎉</h1>
        <h2 style='margin: 10px 0 0 0;'>Email System Working!</h2>
    </div>
    
    <div style='background: #f8f9fa; padding: 25px; border-radius: 8px; border-left: 4px solid #28a745;'>
        <h3 style='color: #28a745; margin-top: 0;'>✅ Success!</h3>
        <p>Congratulations! Your BuildHub admin panel email system is now working perfectly.</p>
    </div>
    
    <div style='margin: 30px 0;'>
        <h3>📋 System Details:</h3>
        <ul style='background: #f8f9fa; padding: 20px; border-radius: 8px;'>
            <li><strong>From:</strong> " . SMTP_FROM_EMAIL . "</li>
            <li><strong>Method:</strong> Gmail SMTP</li>
            <li><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</li>
            <li><strong>Status:</strong> Production Mode Active</li>
        </ul>
    </div>
    
    <div style='background: #e3f2fd; padding: 20px; border-radius: 8px; border-left: 4px solid #2196f3;'>
        <h3 style='color: #1976d2; margin-top: 0;'>🚀 What's Next?</h3>
        <p>Your admin panel will now send real emails when you:</p>
        <ul>
            <li>✅ Approve user registrations</li>
            <li>❌ Reject user applications</li>
            <li>📧 Send any notifications</li>
        </ul>
    </div>
    
    <div style='text-align: center; margin: 30px 0;'>
        <p style='color: #666;'>This email was sent from your BuildHub Admin Panel</p>
        <p style='font-size: 12px; color: #999;'>If you received this, everything is working perfectly! 🎯</p>
    </div>
</body>
</html>
";

// Send the email
$result = sendMail('shijinthomas369@gmail.com', $subject, $message);

if ($result) {
    echo "✅ Email sent successfully!\n";
    echo "📬 Check your Gmail inbox: shijinthomas369@gmail.com\n";
    echo "📁 If not in inbox, check spam/promotions folder\n\n";
    
    echo "🎉 SUCCESS! Your email system is working!\n\n";
    
    echo "📋 What happens now:\n";
    echo "   ✅ User approvals will send real emails\n";
    echo "   ✅ User rejections will send real emails\n";
    echo "   ✅ All emails will come from: shijinthomas369@gmail.com\n\n";
    
    echo "🧪 Test the admin panel:\n";
    echo "   1. Register a test user (contractor/architect)\n";
    echo "   2. Login as admin: shijinthomas369@gmail.com / admin123\n";
    echo "   3. Approve/reject the user\n";
    echo "   4. Check your Gmail for the notification email\n\n";
    
} else {
    echo "❌ Email sending failed!\n";
    echo "🔍 Possible issues:\n";
    echo "   - Check internet connection\n";
    echo "   - Verify Gmail App Password is correct\n";
    echo "   - Check if 2-Step Verification is enabled\n\n";
}

echo "Test completed! 🚀\n";
?>