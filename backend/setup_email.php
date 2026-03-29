<?php
// Email Setup Helper Script for BuildHub

echo "===========================================\n";
echo "📧 BuildHub Email Configuration Setup\n";
echo "===========================================\n\n";

// Check if config file exists
$configFile = __DIR__ . '/config/email_config.php';
if (!file_exists($configFile)) {
    echo "❌ Email config file not found!\n";
    echo "Please make sure email_config.php exists in the config folder.\n";
    exit;
}

// Load current configuration
require_once $configFile;

echo "📋 Current Email Configuration:\n";
echo "   Mode: " . EMAIL_MODE . "\n";
echo "   Admin Email: " . ADMIN_EMAIL . "\n";
echo "   SMTP From: " . SMTP_FROM_EMAIL . "\n";
echo "   Website URL: " . WEBSITE_URL . "\n\n";

// Test email functionality
echo "🧪 Testing Email Functionality...\n";

require_once __DIR__ . '/utils/send_mail.php';

$testResult = sendMail(
    'test-recipient@example.com',
    'BuildHub Email Test',
    '<h2>✅ Email System Test</h2><p>This is a test email from BuildHub admin panel.</p><p>If you see this in the logs, the email system is working correctly!</p>',
    SMTP_FROM_EMAIL
);

if ($testResult) {
    echo "✅ Email test successful!\n\n";
    
    if (EMAIL_MODE === 'development') {
        echo "📝 Email was logged (not sent) because you're in development mode.\n";
        echo "📍 Check your PHP error log for the email content:\n";
        echo "   Location: " . ini_get('error_log') . "\n\n";
        
        echo "🔍 To switch to production mode:\n";
        echo "   1. Edit: backend/config/email_config.php\n";
        echo "   2. Change EMAIL_MODE to 'production'\n";
        echo "   3. Update your email settings\n\n";
    } else {
        echo "📧 Email was sent using production settings.\n\n";
    }
    
} else {
    echo "❌ Email test failed!\n";
    echo "Check your configuration and try again.\n\n";
}

echo "===========================================\n";
echo "📖 Setup Instructions:\n";
echo "===========================================\n\n";

echo "1️⃣ CUSTOMIZE EMAIL SETTINGS:\n";
echo "   Edit: backend/config/email_config.php\n";
echo "   Replace 'your-email@gmail.com' with your actual email\n\n";

echo "2️⃣ FOR GMAIL USERS (Recommended):\n";
echo "   • Go to Google Account Settings\n";
echo "   • Security → 2-Step Verification\n";
echo "   • App passwords → Generate password for 'Mail'\n";
echo "   • Use that password in SMTP_PASSWORD\n\n";

echo "3️⃣ TESTING:\n";
echo "   • Keep EMAIL_MODE = 'development' for testing\n";
echo "   • Emails will be logged instead of sent\n";
echo "   • Check PHP error log to see email content\n\n";

echo "4️⃣ PRODUCTION:\n";
echo "   • Change EMAIL_MODE to 'production'\n";
echo "   • Emails will be sent to actual recipients\n";
echo "   • Make sure SMTP settings are correct\n\n";

echo "🎯 Quick Test:\n";
echo "   Run this script again after updating your settings!\n\n";

echo "Setup completed! 🚀\n";
?>