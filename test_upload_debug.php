<?php
/**
 * Debug tool for homeowner receipt upload
 * This file tests the upload process step by step
 */

// Start session first
session_start();

// Set user session for testing
$_SESSION['user_id'] = 28; // Homeowner ID
$_SESSION['user_type'] = 'homeowner';
$_SESSION['role'] = 'homeowner';

header('Content-Type: text/html; charset=UTF-8');

echo "<h1>🔍 Receipt Upload Debug Test</h1>";
echo "<hr>";

// Test 1: Check if PHP can write to uploads directory
echo "<h2>Test 1: Upload Directory Permissions</h2>";
$uploadDir = __DIR__ . '/uploads/payment_receipts/28/';
if (!file_exists($uploadDir)) {
    if (@mkdir($uploadDir, 0755, true)) {
        echo "✅ Upload directory created successfully: $uploadDir<br>";
    } else {
        echo "❌ Failed to create upload directory: $uploadDir<br>";
        echo "Error: " . error_get_last()['message'] . "<br>";
    }
} else {
    echo "✅ Upload directory exists: $uploadDir<br>";
}

// Check if directory is writable
if (is_writable($uploadDir)) {
    echo "✅ Upload directory is writable<br>";
} else {
    echo "❌ Upload directory is NOT writable<br>";
}

// Test 2: Check database connection
echo "<h2>Test 2: Database Connection</h2>";
try {
    require_once __DIR__ . '/backend/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 3: Verify payment exists
echo "<h2>Test 3: Verify Payment Record</h2>";
$paymentId = 16; // Test with payment ID 16
$homeownerId = 28;

$stmt = $db->prepare("
    SELECT id, homeowner_id, contractor_id, requested_amount, status 
    FROM stage_payment_requests 
    WHERE id = :payment_id AND homeowner_id = :homeowner_id
");

$stmt->execute([
    ':payment_id' => $paymentId,
    ':homeowner_id' => $homeownerId
]);

$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if ($payment) {
    echo "✅ Payment found: " . json_encode($payment, JSON_PRETTY_PRINT) . "<br>";
} else {
    echo "❌ Payment not found or ownership mismatch<br>";
    echo "Looking for payment_id=$paymentId, homeowner_id=$homeownerId<br>";
    
    // Debug: List all payments for this homeowner
    echo "<h3>Payments for homeowner $homeownerId:</h3>";
    $debugStmt = $db->prepare("SELECT id, contractor_id, requested_amount, status FROM stage_payment_requests WHERE homeowner_id = :hid");
    $debugStmt->execute([':hid' => $homeownerId]);
    $payments = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . json_encode($payments, JSON_PRETTY_PRINT) . "</pre>";
}

// Test 4: Simulate file upload
echo "<h2>Test 4: Simulated File Upload Process</h2>";

// Create a test file
$testFile = $uploadDir . 'test_receipt_' . time() . '.txt';
$content = "Test receipt file created at " . date('Y-m-d H:i:s');

if (@file_put_contents($testFile, $content)) {
    echo "✅ Test file created successfully: $testFile<br>";
    
    // Try to read it back
    if (file_exists($testFile)) {
        echo "✅ Test file exists and is readable<br>";
        
        // Clean up
        @unlink($testFile);
        echo "✅ Test file cleaned up<br>";
    } else {
        echo "❌ Test file cannot be read back<br>";
    }
} else {
    echo "❌ Failed to create test file<br>";
    echo "Error: " . error_get_last()['message'] . "<br>";
}

// Test 5: Check verification tables
echo "<h2>Test 5: Database Tables Check</h2>";

$tables = [
    'stage_payment_requests',
    'stage_payment_verification_logs',
    'stage_payment_notifications'
];

foreach ($tables as $table) {
    $result = $db->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->rowCount() > 0) {
        echo "✅ Table exists: $table<br>";
        
        // Check columns for receipt_file_path
        if ($table === 'stage_payment_requests') {
            $colResult = $db->query("SHOW COLUMNS FROM $table LIKE 'receipt_file_path'");
            if ($colResult && $colResult->rowCount() > 0) {
                echo "&nbsp;&nbsp;&nbsp;✅ receipt_file_path column exists<br>";
            } else {
                echo "&nbsp;&nbsp;&nbsp;❌ receipt_file_path column missing<br>";
            }
        }
    } else {
        echo "❌ Table missing: $table<br>";
    }
}

// Test 6: Test API endpoint with simulated form data
echo "<h2>Test 6: API Endpoint Test</h2>";

echo "<form method='POST' enctype='multipart/form-data' style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
echo "<p><strong>Upload a test receipt file:</strong></p>";
echo "<input type='hidden' name='payment_id' value='16'>";
echo "<input type='hidden' name='transaction_reference' value='TEST_REF_' . time()>";
echo "<input type='hidden' name='payment_date' value='" . date('Y-m-d') . "'>";
echo "<input type='hidden' name='payment_method' value='bank_transfer'>";
echo "<input type='hidden' name='notes' value='Test upload'>";
echo "<input type='file' name='receipt_files[]' accept='image/*,.pdf' required>";
echo "<button type='submit' name='test_upload'>Upload Test File</button>";
echo "</form>";

// Process test upload if submitted
if (isset($_POST['test_upload']) && isset($_FILES['receipt_files'])) {
    echo "<h3>Processing Upload...</h3>";
    
    $paymentId = 16;
    $uploadDir = __DIR__ . '/uploads/payment_receipts/' . $paymentId . '/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadedFiles = [];
    $uploadErrors = [];
    
    if (isset($_FILES['receipt_files']) && is_array($_FILES['receipt_files']['name'])) {
        $fileCount = count($_FILES['receipt_files']['name']);
        echo "<p>Processing $fileCount file(s)...</p>";
        
        for ($i = 0; $i < $fileCount; $i++) {
            echo "<strong>File $i:</strong><br>";
            echo "Name: " . $_FILES['receipt_files']['name'][$i] . "<br>";
            echo "Size: " . $_FILES['receipt_files']['size'][$i] . " bytes<br>";
            echo "Type: " . $_FILES['receipt_files']['type'][$i] . "<br>";
            echo "Error: " . $_FILES['receipt_files']['error'][$i] . "<br>";
            
            if ($_FILES['receipt_files']['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['receipt_files']['name'][$i];
                $fileTmpName = $_FILES['receipt_files']['tmp_name'][$i];
                $fileSize = $_FILES['receipt_files']['size'][$i];
                $fileType = $_FILES['receipt_files']['type'][$i];
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
                if (!in_array($fileType, $allowedTypes)) {
                    $uploadErrors[] = "File '$fileName' has invalid type";
                    echo "❌ Invalid file type<br>";
                    continue;
                }
                
                // Validate file size
                if ($fileSize > 10 * 1024 * 1024) {
                    $uploadErrors[] = "File '$fileName' is too large";
                    echo "❌ File too large<br>";
                    continue;
                }
                
                // Generate unique filename
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $uniqueFileName = 'receipt_' . time() . '_' . $i . '.' . $fileExtension;
                $filePath = $uploadDir . $uniqueFileName;
                
                // Move uploaded file
                if (move_uploaded_file($fileTmpName, $filePath)) {
                    echo "✅ File uploaded successfully to: $filePath<br>";
                    $uploadedFiles[] = [
                        'original_name' => $fileName,
                        'stored_name' => $uniqueFileName,
                        'file_path' => 'uploads/payment_receipts/' . $paymentId . '/' . $uniqueFileName,
                        'file_size' => $fileSize,
                        'file_type' => $fileType
                    ];
                } else {
                    echo "❌ Failed to move uploaded file<br>";
                    echo "Error: " . error_get_last()['message'] . "<br>";
                    $uploadErrors[] = "Failed to upload file '$fileName'";
                }
            } else {
                echo "❌ Upload error: " . $_FILES['receipt_files']['error'][$i] . "<br>";
                $uploadErrors[] = "Upload error for file: " . $_FILES['receipt_files']['name'][$i];
            }
        }
    }
    
    echo "<h3>Upload Results:</h3>";
    echo "Files uploaded: " . count($uploadedFiles) . "<br>";
    echo "Errors: " . count($uploadErrors) . "<br>";
    
    if (count($uploadedFiles) > 0) {
        echo "<pre>" . json_encode($uploadedFiles, JSON_PRETTY_PRINT) . "</pre>";
    }
    
    if (count($uploadErrors) > 0) {
        echo "<strong>Errors:</strong><br>";
        foreach ($uploadErrors as $error) {
            echo "- $error<br>";
        }
    }
}

echo "<hr>";
echo "<p><strong>Debug Summary:</strong></p>";
echo "<ul>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "<li>Session User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "</li>";
echo "<li>Session User Type: " . ($_SESSION['user_type'] ?? 'NOT SET') . "</li>";
echo "<li>Upload Dir: $uploadDir</li>";
echo "<li>Writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "</li>";
echo "<li>POST max size: " . ini_get('post_max_size') . "</li>";
echo "<li>Upload max filesize: " . ini_get('upload_max_filesize') . "</li>";
echo "</ul>";

?>
