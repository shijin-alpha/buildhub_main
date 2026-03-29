<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 Checking receipt file path in database...\n\n";
    
    // Check stage_payment_requests table
    $stmt2 = $pdo->prepare("SELECT id, receipt_file_path FROM stage_payment_requests WHERE receipt_file_path LIKE '%receipt_1769100372_0.png%' LIMIT 1");
    $stmt2->execute();
    $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    if ($result2) {
        echo "Found in stage_payment_requests table:\n";
        echo "Payment Request ID: {$result2['id']}\n";
        echo "Receipt File Path: {$result2['receipt_file_path']}\n\n";
        
        // Parse the JSON to see the structure
        $receiptData = json_decode($result2['receipt_file_path'], true);
        if ($receiptData) {
            echo "Receipt data structure:\n";
            print_r($receiptData);
        }
    } else {
        echo "Not found in stage_payment_requests table\n\n";
    }
    
    // Check custom_payment_requests table too
    $stmt3 = $pdo->prepare("SELECT id, receipt_file_path FROM custom_payment_requests WHERE receipt_file_path LIKE '%receipt_1769100372_0.png%' LIMIT 1");
    $stmt3->execute();
    $result3 = $stmt3->fetch(PDO::FETCH_ASSOC);
    
    if ($result3) {
        echo "Found in custom_payment_requests table:\n";
        echo "Payment Request ID: {$result3['id']}\n";
        echo "Receipt File Path: {$result3['receipt_file_path']}\n\n";
        
        // Parse the JSON to see the structure
        $receiptData = json_decode($result3['receipt_file_path'], true);
        if ($receiptData) {
            echo "Receipt data structure:\n";
            print_r($receiptData);
        }
    } else {
        echo "Not found in custom_payment_requests table\n\n";
    }
    
    // Check if file exists on filesystem
    $filePath = "backend/uploads/payment_receipts/22/receipt_1769100372_0.png";
    if (file_exists($filePath)) {
        echo "✅ File exists on filesystem at: $filePath\n";
        echo "File size: " . filesize($filePath) . " bytes\n";
    } else {
        echo "❌ File does not exist on filesystem at: $filePath\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>