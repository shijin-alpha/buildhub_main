<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>🧪 Creating Test Receipt for Homeowner 32</h2>";
    
    // First, check if homeowner 32 exists
    $stmt = $db->prepare("SELECT id, first_name, last_name FROM users WHERE id = 32");
    $stmt->execute();
    $homeowner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$homeowner) {
        echo "<p>❌ Homeowner 32 not found. Creating test homeowner...</p>";
        
        $stmt = $db->prepare("
            INSERT INTO users (id, first_name, last_name, email, phone, role, password_hash, created_at) 
            VALUES (32, 'Amal', 'Samuel', 'thomasshijin90@gmail.com', '9876543210', 'homeowner', 'test_hash', NOW())
            ON DUPLICATE KEY UPDATE 
            first_name = 'Amal', last_name = 'Samuel', role = 'homeowner'
        ");
        $stmt->execute();
        echo "<p>✅ Test homeowner created/updated.</p>";
    } else {
        echo "<p>✅ Homeowner 32 exists: {$homeowner['first_name']} {$homeowner['last_name']}</p>";
    }
    
    // Create a test stage payment request with receipt
    $test_receipt_data = [
        [
            "original_name" => "test_receipt_1.pdf",
            "stored_name" => "1234567890_test_receipt_1.pdf",
            "file_path" => "uploads/payment_receipts/999/1234567890_test_receipt_1.pdf",
            "file_size" => 102400,
            "file_type" => "application/pdf"
        ],
        [
            "original_name" => "bank_statement.jpg",
            "stored_name" => "1234567891_bank_statement.jpg",
            "file_path" => "uploads/payment_receipts/999/1234567891_bank_statement.jpg",
            "file_size" => 256000,
            "file_type" => "image/jpeg"
        ]
    ];
    
    $receipt_json = json_encode($test_receipt_data);
    
    // Insert test stage payment request
    $stmt = $db->prepare("
        INSERT INTO stage_payment_requests (
            id, project_id, contractor_id, homeowner_id, stage_name, 
            requested_amount, work_description, status, request_date,
            transaction_reference, payment_date, receipt_file_path,
            payment_method, verification_status, created_at, updated_at
        ) VALUES (
            999, 1, 2, 32, 'Foundation Work Payment',
            50000, 'Payment for foundation work completion', 'approved', NOW(),
            'TXN123456789', '2024-01-15', ?,
            'bank_transfer', 'pending', NOW(), NOW()
        ) ON DUPLICATE KEY UPDATE
        receipt_file_path = ?, verification_status = 'pending', updated_at = NOW()
    ");
    $stmt->execute([$receipt_json, $receipt_json]);
    
    echo "<p>✅ Test stage payment request created with ID 999</p>";
    
    // Create a test custom payment request with receipt
    $stmt = $db->prepare("
        INSERT INTO custom_payment_requests (
            id, project_id, contractor_id, homeowner_id, request_title,
            requested_amount, work_description, status, request_date,
            transaction_reference, payment_date, receipt_file_path,
            payment_method, verification_status, created_at, updated_at
        ) VALUES (
            888, 1, 2, 32, 'Material Purchase Receipt',
            25000, 'Receipt for construction materials', 'approved', NOW(),
            'TXN987654321', '2024-01-20', ?,
            'upi', 'verified', NOW(), NOW()
        ) ON DUPLICATE KEY UPDATE
        receipt_file_path = ?, verification_status = 'verified', updated_at = NOW()
    ");
    $stmt->execute([$receipt_json, $receipt_json]);
    
    echo "<p>✅ Test custom payment request created with ID 888</p>";
    
    // Create upload directory structure
    $upload_dir = 'uploads/payment_receipts/999';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
        echo "<p>✅ Created upload directory: {$upload_dir}</p>";
    }
    
    $upload_dir_2 = 'uploads/payment_receipts/888';
    if (!is_dir($upload_dir_2)) {
        mkdir($upload_dir_2, 0755, true);
        echo "<p>✅ Created upload directory: {$upload_dir_2}</p>";
    }
    
    // Create dummy receipt files
    $dummy_pdf_content = "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n>>\nendobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \ntrailer\n<<\n/Size 4\n/Root 1 0 R\n>>\nstartxref\n174\n%%EOF";
    
    file_put_contents($upload_dir . '/1234567890_test_receipt_1.pdf', $dummy_pdf_content);
    file_put_contents($upload_dir_2 . '/1234567890_test_receipt_1.pdf', $dummy_pdf_content);
    
    // Create dummy image file (1x1 pixel JPEG)
    $dummy_jpg_content = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/8A');
    
    file_put_contents($upload_dir . '/1234567891_bank_statement.jpg', $dummy_jpg_content);
    file_put_contents($upload_dir_2 . '/1234567891_bank_statement.jpg', $dummy_jpg_content);
    
    echo "<p>✅ Created dummy receipt files</p>";
    
    // Verify the data was created
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
    $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📋 Verification: Receipts for Homeowner 32</h3>";
    if (count($receipts) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Type</th><th>ID</th><th>Title</th><th>Amount</th><th>Files</th><th>Status</th></tr>";
        foreach ($receipts as $receipt) {
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
        
        echo "<h3>🎉 Success!</h3>";
        echo "<p>✅ Test data created successfully. Now you can test the homeowner dashboard fix:</p>";
        echo "<ol>";
        echo "<li><a href='test_homeowner_receipt_display_fix.html' target='_blank'>Run the Receipt Display Fix Test</a></li>";
        echo "<li>The test should now show {count($receipts)} receipt(s) in the homeowner dashboard</li>";
        echo "<li>The fix ensures receipts are fetched from the correct API endpoint</li>";
        echo "</ol>";
        
    } else {
        echo "<p>❌ No receipts found after creation. Something went wrong.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>