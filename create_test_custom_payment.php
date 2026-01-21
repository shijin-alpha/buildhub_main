<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create a new test custom payment request
    $query = "INSERT INTO custom_payment_requests (
        project_id, contractor_id, homeowner_id, request_title, request_reason, 
        requested_amount, urgency_level, category, contractor_notes, status, request_date
    ) VALUES (
        37, 29, 28, 'Additional Electrical Work', 
        'Need to install additional power outlets and lighting fixtures as requested by homeowner',
        5000.00, 'medium', 'electrical', 
        'This includes 4 additional power outlets and 2 ceiling fans installation',
        'pending', NOW()
    )";
    
    $stmt = $db->prepare($query);
    $result = $stmt->execute();
    
    if ($result) {
        $new_id = $db->lastInsertId();
        echo "✅ Created new test custom payment request with ID: $new_id\n";
        echo "Title: Additional Electrical Work\n";
        echo "Amount: ₹5,000\n";
        echo "Status: pending\n";
        echo "Urgency: medium\n";
        echo "Category: electrical\n";
    } else {
        echo "❌ Failed to create test custom payment request\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>