<?php
// Script to enable production email mode

echo "🔧 Switching to Production Email Mode\n";
echo "=====================================\n\n";

$configFile = __DIR__ . '/config/email_config.php';

// Read current config
$config = file_get_contents($configFile);

// Check current mode
if (strpos($config, "define('EMAIL_MODE', 'production')") !== false) {
    echo "✅ Already in production mode!\n";
} else {
    // Switch to production mode
    $config = str_replace(
        "define('EMAIL_MODE', 'development');",
        "define('EMAIL_MODE', 'production');",
        $config
    );
    
    // Write back to file
    file_put_contents($configFile, $config);
    
    echo "✅ Switched to production mode!\n";
    echo "📧 Emails will now be sent via SMTP instead of logged\n\n";
}

// Load and display current settings
require_once $configFile;

echo "📋 Current Settings:\n";
echo "   Mode: " . EMAIL_MODE . "\n";
echo "   From: " . SMTP_FROM_EMAIL . "\n";
echo "   SMTP Host: " . SMTP_HOST . "\n";
echo "   Password Set: " . (SMTP_PASSWORD !== 'PASTE_YOUR_APP_PASSWORD_HERE' ? 'Yes ✅' : 'No ❌') . "\n\n";

if (SMTP_PASSWORD === 'PASTE_YOUR_APP_PASSWORD_HERE') {
    echo "⚠️  WARNING: Gmail App Password not configured!\n";
    echo "   You need to set your Gmail App Password in email_config.php\n\n";
} else {
    echo "🎉 Ready to send real emails!\n\n";
}

echo "🧪 To test real email sending:\n";
echo "   Run: php backend/test_real_email.php\n\n";

echo "Configuration updated! 🚀\n";
?>