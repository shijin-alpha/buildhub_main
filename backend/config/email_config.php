<?php
// Email Configuration for BuildHub

// ===========================================
// EMAIL SETTINGS - CUSTOMIZE THESE
// ===========================================

// Your email settings (updated with your Gmail)
define('ADMIN_EMAIL', 'shijinthomas369@gmail.com');
define('ADMIN_NAME', 'BuildHub Admin');
define('SMTP_FROM_EMAIL', 'shijinthomas369@gmail.com');
define('SMTP_FROM_NAME', 'BuildHub Platform');

// Development vs Production
define('EMAIL_MODE', 'production'); // 'development' or 'production'

// ===========================================
// GMAIL SMTP SETTINGS (for production)
// ===========================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'shijinthomas369@gmail.com');
define('SMTP_PASSWORD', 'phlf myaw sekr pili');     // Your Gmail App Password
define('SMTP_ENCRYPTION', 'tls');

// ===========================================
// EMAIL TEMPLATES SETTINGS
// ===========================================
define('WEBSITE_URL', 'http://localhost:3000');
define('SUPPORT_EMAIL', 'shijinthomas369@gmail.com');

// ===========================================
// INSTRUCTIONS FOR SETUP
// ===========================================
/*
TO USE REAL EMAIL SENDING:

1. ✅ Gmail address updated to: shijinthomas369@gmail.com
2. Get a Gmail App Password:
   - Go to Google Account settings
   - Security → 2-Step Verification → App passwords
   - Generate password for "Mail"
   - Use that password in SMTP_PASSWORD

3. Change EMAIL_MODE to 'production' when ready

4. For testing, keep EMAIL_MODE as 'development' to log emails instead
*/

?>