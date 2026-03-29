<?php
// Quick login for homeowner 32 to establish session
session_start();

// Set session for homeowner 32 (Amal Samuel)
$_SESSION['user_id'] = 32;
$_SESSION['user_role'] = 'homeowner';
$_SESSION['user_name'] = 'Amal Samuel';
$_SESSION['user_email'] = 'thomasshijin90@gmail.com';
$_SESSION['logged_in'] = true;

// Set session cookie to last longer
ini_set('session.cookie_lifetime', 86400); // 24 hours

echo "<!DOCTYPE html>
<html>
<head>
    <title>Quick Login - Homeowner</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; font-size: 18px; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .button { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
        .button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>✅ Login Successful!</h1>
        <div class='success'>You are now logged in as Amal Samuel (Homeowner)</div>
        
        <div class='info'>
            <strong>Session Details:</strong><br>
            User ID: " . $_SESSION['user_id'] . "<br>
            Role: " . $_SESSION['user_role'] . "<br>
            Name: " . $_SESSION['user_name'] . "<br>
            Email: " . $_SESSION['user_email'] . "<br>
            Session ID: " . session_id() . "
        </div>
        
        <h3>Next Steps:</h3>
        <ol>
            <li>Go back to your homeowner dashboard</li>
            <li>Navigate to payment requests</li>
            <li>Try uploading the receipt again</li>
            <li>The upload should now work!</li>
        </ol>
        
        <div>
            <a href='/buildhub/frontend/dist/index.html' class='button'>Go to Dashboard</a>
            <a href='javascript:window.close()' class='button'>Close Window</a>
        </div>
        
        <div class='info'>
            <strong>Available Payment for Receipt Upload:</strong><br>
            Payment ID: #13<br>
            Amount: ₹3,76,161<br>
            Status: Approved<br>
            Method: Bank Transfer
        </div>
    </div>
</body>
</html>";
?>