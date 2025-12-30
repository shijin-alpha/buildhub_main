<?php
// Test admin email functionality with your Gmail

echo "🧪 Testing Admin Email Setup\n";
echo "============================\n\n";

// Test 1: Load email configuration
echo "1️⃣ Loading email configuration...\n";
require_once __DIR__ . '/config/email_config.php';

echo "   ✅ Admin Email: " . ADMIN_EMAIL . "\n";
echo "   ✅ SMTP From: " . SMTP_FROM_EMAIL . "\n";
echo "   ✅ Mode: " . EMAIL_MODE . "\n\n";

// Test 2: Test email sending
echo "2️⃣ Testing email sending...\n";
require_once __DIR__ . '/utils/send_mail.php';

$testEmail = sendMail(
    'test-user@gmail.com',
    'BuildHub Admin Test - User Approved',
    '<h2>🎉 Account Approved!</h2><p>Dear Test User,</p><p>Your BuildHub account has been approved!</p><p>From: ' . SMTP_FROM_EMAIL . '</p>',
    SMTP_FROM_EMAIL
);

if ($testEmail) {
    echo "   ✅ Email function working!\n";
    echo "   📧 Check PHP error log for email content\n\n";
} else {
    echo "   ❌ Email function failed!\n\n";
}

// Test 3: Admin login credentials
echo "3️⃣ Admin login credentials:\n";
echo "   📧 Email: shijinthomas369@gmail.com\n";
echo "   🔑 Password: admin123\n";
echo "   🌐 Login URL: http://localhost:3000/login\n\n";

echo "✅ All tests completed!\n";
echo "\n📋 Next Steps:\n";
echo "   1. Start your React app (npm start)\n";
echo "   2. Go to http://localhost:3000/login\n";
echo "   3. Login with: shijinthomas369@gmail.com / admin123\n";
echo "   4. Test user approval/rejection\n";
echo "   5. Check PHP error log for email content\n\n";

echo "🔧 To enable real email sending:\n";
echo "   1. Get Gmail App Password from Google Account\n";
echo "   2. Update SMTP_PASSWORD in email_config.php\n";
echo "   3. Change EMAIL_MODE to 'production'\n\n";

echo "Setup complete! 🚀\n";
?>