<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>🔍 Checking Existing Receipts in Database</h2>";
    
    // Check stage payment requests with receipts
    $stmt = $db->prepare("
        SELECT 
            id, homeowner_id, stage_name, requested_amount, 
            receipt_file_path, verification_status, created_at
        FROM stage_payment_requests 
        WHERE receipt_file_path IS NOT NULL 
        AND receipt_file_path != '' 
        AND receipt_file_path != 'null'
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $stage_receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📋 Stage Payment Requests with Receipts (" . count($stage_receipts) . ")</h3>";
    if (count($stage_receipts) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Homeowner ID</th><th>Stage</th><th>Amount</th><th>Receipt Files</th><th>Status</th><th>Created</th></tr>";
        foreach ($stage_receipts as $receipt) {
            $files = json_decode($receipt['receipt_file_path'], true);
            $file_count = is_array($files) ? count($files) : 0;
            echo "<tr>";
            echo "<td>{$receipt['id']}</td>";
            echo "<td>{$receipt['homeowner_id']}</td>";
            echo "<td>{$receipt['stage_name']}</td>";
            echo "<td>₹{$receipt['requested_amount']}</td>";
            echo "<td>{$file_count} files</td>";
            echo "<td>{$receipt['verification_status']}</td>";
            echo "<td>{$receipt['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No stage payment receipts found.</p>";
    }
    
    // Check custom payment requests with receipts
    $stmt = $db->prepare("
        SELECT 
            id, homeowner_id, request_title, requested_amount, 
            receipt_file_path, verification_status, created_at
        FROM custom_payment_requests 
        WHERE receipt_file_path IS NOT NULL 
        AND receipt_file_path != '' 
        AND receipt_file_path != 'null'
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $custom_receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>💼 Custom Payment Requests with Receipts (" . count($custom_receipts) . ")</h3>";
    if (count($custom_receipts) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Homeowner ID</th><th>Title</th><th>Amount</th><th>Receipt Files</th><th>Status</th><th>Created</th></tr>";
        foreach ($custom_receipts as $receipt) {
            $files = json_decode($receipt['receipt_file_path'], true);
            $file_count = is_array($files) ? count($files) : 0;
            echo "<tr>";
            echo "<td>{$receipt['id']}</td>";
            echo "<td>{$receipt['homeowner_id']}</td>";
            echo "<td>{$receipt['request_title']}</td>";
            echo "<td>₹{$receipt['requested_amount']}</td>";
            echo "<td>{$file_count} files</td>";
            echo "<td>{$receipt['verification_status']}</td>";
            echo "<td>{$receipt['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No custom payment receipts found.</p>";
    }
    
    // Check for homeowner 32 specifically
    echo "<h3>👤 Receipts for Homeowner 32 (Test User)</h3>";
    $stmt = $db->prepare("
        SELECT 'stage' as type, id, stage_name as title, requested_amount, receipt_file_path, verification_status
        FROM stage_payment_requests 
        WHERE homeowner_id = 32 AND receipt_file_path IS NOT NULL AND receipt_file_path != '' AND receipt_file_path != 'null'
        UNION ALL
        SELECT 'custom' as type, id, request_title as title, requested_amount, receipt_file_path, verification_status
        FROM custom_payment_requests 
        WHERE homeowner_id = 32 AND receipt_file_path IS NOT NULL AND receipt_file_path != '' AND receipt_file_path != 'null'
    ");
    $stmt->execute();
    $homeowner_32_receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($homeowner_32_receipts) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Type</th><th>ID</th><th>Title</th><th>Amount</th><th>Receipt Files</th><th>Status</th></tr>";
        foreach ($homeowner_32_receipts as $receipt) {
            $files = json_decode($receipt['receipt_file_path'], true);
            $file_count = is_array($files) ? count($files) : 0;
            echo "<tr>";
            echo "<td>{$receipt['type']}</td>";
            echo "<td>{$receipt['id']}</td>";
            echo "<td>{$receipt['title']}</td>";
            echo "<td>₹{$receipt['requested_amount']}</td>";
            echo "<td>{$file_count} files</td>";
            echo "<td>{$receipt['verification_status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No receipts found for homeowner 32. You need to upload some receipts first!</p>";
        echo "<p>💡 <strong>To test the fix:</strong></p>";
        echo "<ol>";
        echo "<li>Go to the homeowner dashboard</li>";
        echo "<li>Upload a receipt for any payment request</li>";
        echo "<li>Then run this test again</li>";
        echo "</ol>";
    }
    
    echo "<h3>📁 File System Check</h3>";
    $upload_dir = 'uploads/payment_receipts';
    if (is_dir($upload_dir)) {
        $files = glob($upload_dir . '/*');
        echo "<p>Upload directory exists with " . count($files) . " items.</p>";
        if (count($files) > 0) {
            echo "<ul>";
            foreach (array_slice($files, 0, 10) as $file) {
                echo "<li>" . basename($file) . "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p>❌ Upload directory does not exist: {$upload_dir}</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>