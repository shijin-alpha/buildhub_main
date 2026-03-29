<?php
// Real-time debug for receipt upload issue
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== REAL-TIME RECEIPT UPLOAD DEBUG ===\n\n";
    
    // Check current session
    session_start();
    echo "Current session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
    echo "Session ID: " . session_id() . "\n\n";
    
    // Check what payment requests exist for different homeowners
    echo "=== ALL PAYMENT REQUESTS ===\n";
    $stmt = $pdo->query("SELECT id, homeowner_id, contractor_id, requested_amount, status FROM stage_payment_requests ORDER BY id DESC LIMIT 10");
    $all_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($all_payments as $payment) {
        echo "ID: {$payment['id']}, Homeowner: {$payment['homeowner_id']}, Amount: {$payment['requested_amount']}, Status: {$payment['status']}\n";
    }
    
    echo "\n=== CHECKING SPECIFIC PAYMENT ID 13 ===\n";
    $stmt = $pdo->prepare("SELECT * FROM stage_payment_requests WHERE id = 13");
    $stmt->execute();
    $payment_13 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment_13) {
        echo "Payment ID 13 EXISTS:\n";
        echo "Homeowner ID: {$payment_13['homeowner_id']}\n";
        echo "Contractor ID: {$payment_13['contractor_id']}\n";
        echo "Amount: {$payment_13['requested_amount']}\n";
        echo "Status: {$payment_13['status']}\n";
    } else {
        echo "Payment ID 13 does NOT exist\n";
    }
    
    echo "\n=== CHECKING WHO IS LOGGED IN ===\n";
    
    // Check if there's a user logged in by checking common session variables
    $possible_user_ids = [28, 29, 32];
    foreach ($possible_user_ids as $user_id) {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "User ID {$user_id}: {$user['first_name']} {$user['last_name']} ({$user['role']}) - {$user['email']}\n";
            
            // Check their payments
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM stage_payment_requests WHERE homeowner_id = ?");
            $stmt->execute([$user_id]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "  Has {$count['count']} payment requests\n";
        }
    }
    
    echo "\n=== SOLUTION ===\n";
    echo "The issue is likely:\n";
    echo "1. Frontend is showing payment ID 13 which doesn't exist\n";
    echo "2. Or user session is not properly set\n";
    echo "3. Or payment belongs to different homeowner\n\n";
    
    echo "Quick fix: Set session for testing\n";
    $_SESSION['user_id'] = 32; // Set to homeowner who has payment ID 24
    echo "Session set to user_id = 32\n";
    echo "Now try uploading receipt for payment ID 24 (not 13)\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>