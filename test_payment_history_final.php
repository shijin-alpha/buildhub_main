<?php
// Final test of payment history API
session_start();
$_SESSION['user_id'] = 29; // Set contractor ID

header('Content-Type: application/json');

// Set the project ID
$_GET['project_id'] = 37;

// Include the fixed API
include 'backend/api/contractor/get_payment_history.php';
?>