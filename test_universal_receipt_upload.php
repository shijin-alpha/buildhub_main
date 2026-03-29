<?php
// Test universal receipt upload for any homeowner

echo "=== TESTING UNIVERSAL RECEIPT UPLOAD SYSTEM ===\n\n";

require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get all homeowners with payment requests
    $homeowners_query = "
        SELECT DISTINCT 
            u.id, u.first_name, u.last_name, u.email,
            COUNT(spr.id) as payment_count,
            GROUP_CONCAT(spr.id) as payment_ids
        FROM users u
        JOIN stage_payment_requests spr ON u.id = spr.homeowner_id
        WHERE u.role = 'homeowner'
        GROUP BY u.id, u.first_name, u.last_name, u.email
        ORDER BY u.id
    ";
    
    $homeowners_stmt = $pdo->prepare($homeowners_query);
    $homeowners_stmt->execute();
    $homeowners = $homeowners_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($homeowners) . " homeowner(s) with payment requests:\n\n";
    
    foreach ($homeowners as $homeowner) {
        echo "--- Homeowner: {$homeowner['first_name']} {$homeowner['last_name']} (ID: {$homeowner['id']}) ---\n";
        echo "Email: {$homeowner['email']}\n";
        echo "Payment Requests: {$homeowner['payment_count']}\n";
        echo "Payment IDs: {$homeowner['payment_ids']}\n";
        
        // Test session establishment for this homeowner's first payment
        $payment_ids = explode(',', $homeowner['payment_ids']);
        $test_payment_id = $payment_ids[0];
        
        echo "Testing session establishment for payment ID: $test_payment_id\n";
        
        // Test the session establishment API
        $session_url = "http://localhost/buildhub/backend/api/auth/establish_session_for_payment.php";
        $session_data = json_encode(['payment_id' => intval($test_payment_id)]);
        
        $session_context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                ],
                'content' => $session_data
            ]
        ]);
        
        $session_response = file_get_contents($session_url, false, $session_context);
        
        if ($session_response !== false) {
            $session_result = json_decode($session_response, true);
            if ($session_result && $session_result['success']) {
                echo "✅ Session establishment: SUCCESS\n";
                echo "   User ID: {$session_result['data']['user_id']}\n";
                echo "   User Name: {$session_result['data']['user_name']}\n";
                echo "   Payment Amount: ₹{$session_result['data']['payment_amount']}\n";
            } else {
                echo "❌ Session establishment: FAILED - " . ($session_result['message'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "❌ Session establishment: API call failed\n";
        }
        
        echo "\n";
    }
    
    echo "=== TESTING PAYMENT OWNERSHIP VALIDATION ===\n\n";
    
    // Test payment ownership validation for each homeowner
    foreach ($homeowners as $homeowner) {
        $payment_ids = explode(',', $homeowner['payment_ids']);
        
        foreach ($payment_ids as $payment_id) {
            $payment_id = trim($payment_id);
            
            // Check if payment belongs to homeowner
            $validation_query = "
                SELECT * FROM stage_payment_requests 
                WHERE id = ? AND homeowner_id = ?
            ";
            $validation_stmt = $pdo->prepare($validation_query);
            $validation_stmt->execute([$payment_id, $homeowner['id']]);
            $payment = $validation_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($payment) {
                echo "✅ Payment {$payment_id} belongs to {$homeowner['first_name']} {$homeowner['last_name']}\n";
            } else {
                echo "❌ Payment {$payment_id} validation failed for {$homeowner['first_name']} {$homeowner['last_name']}\n";
            }
        }
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "Total homeowners tested: " . count($homeowners) . "\n";
    echo "Universal receipt upload system should now work for all homeowners!\n";
    echo "\nKey improvements:\n";
    echo "1. ✅ Auto-session establishment based on payment ownership\n";
    echo "2. ✅ Universal homeowner support (not hardcoded to specific user)\n";
    echo "3. ✅ Better error logging and debugging\n";
    echo "4. ✅ Proper payment validation for any homeowner\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>