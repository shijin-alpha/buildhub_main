<?php
/**
 * Debug Receipt Upload Issue
 * 
 * This script diagnoses the "No files were uploaded successfully" error
 */

require_once 'backend/config/database.php';

try {
    echo "=== RECEIPT UPLOAD ISSUE DIAGNOSIS ===\n\n";
    
    // 1. Check PHP configuration
    echo "1. PHP CONFIGURATION:\n";
    echo "====================\n";
    echo "file_uploads: " . (ini_get('file_uploads') ? 'ON' : 'OFF') . "\n";
    echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
    echo "post_max_size: " . ini_get('post_max_size') . "\n";
    echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
    echo "memory_limit: " . ini_get('memory_limit') . "\n";
    echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
    echo "max_input_time: " . ini_get('max_input_time') . "\n\n";
    
    // 2. Check upload directory
    echo "2. UPLOAD DIRECTORY CHECK:\n";
    echo "=========================\n";
    
    $baseUploadDir = 'backend/uploads/payment_receipts/';
    echo "Base upload directory: $baseUploadDir\n";
    
    if (!file_exists($baseUploadDir)) {
        echo "❌ Base directory does not exist. Creating...\n";
        if (mkdir($baseUploadDir, 0755, true)) {
            echo "✅ Base directory created successfully\n";
        } else {
            echo "❌ Failed to create base directory\n";
        }
    } else {
        echo "✅ Base directory exists\n";
    }
    
    // Check permissions
    if (is_writable($baseUploadDir)) {
        echo "✅ Base directory is writable\n";
    } else {
        echo "❌ Base directory is not writable\n";
        echo "   Attempting to fix permissions...\n";
        if (chmod($baseUploadDir, 0755)) {
            echo "✅ Permissions fixed\n";
        } else {
            echo "❌ Failed to fix permissions\n";
        }
    }
    
    // Test creating a subdirectory
    $testDir = $baseUploadDir . 'test_' . time() . '/';
    if (mkdir($testDir, 0755, true)) {
        echo "✅ Can create subdirectories\n";
        rmdir($testDir); // Clean up
    } else {
        echo "❌ Cannot create subdirectories\n";
    }
    
    echo "\n";
    
    // 3. Check database connection and session
    echo "3. DATABASE AND SESSION CHECK:\n";
    echo "==============================\n";
    
    if ($db) {
        echo "✅ Database connection: OK\n";
    } else {
        echo "❌ Database connection: FAILED\n";
    }
    
    session_start();
    echo "Session ID: " . session_id() . "\n";
    echo "Session data: " . json_encode($_SESSION) . "\n\n";
    
    // 4. Test file upload simulation
    echo "4. FILE UPLOAD SIMULATION:\n";
    echo "=========================\n";
    
    // Create a test file
    $testContent = "Test receipt file content - " . date('Y-m-d H:i:s');
    $testFileName = 'test_receipt_' . time() . '.txt';
    $testFilePath = sys_get_temp_dir() . '/' . $testFileName;
    
    if (file_put_contents($testFilePath, $testContent)) {
        echo "✅ Test file created: $testFilePath\n";
        
        // Simulate $_FILES array
        $_FILES = [
            'receipt_files' => [
                'name' => [$testFileName],
                'type' => ['text/plain'],
                'tmp_name' => [$testFilePath],
                'error' => [UPLOAD_ERR_OK],
                'size' => [strlen($testContent)]
            ]
        ];
        
        echo "✅ Simulated \$_FILES array created\n";
        echo "Files array: " . json_encode($_FILES) . "\n";
        
        // Test the upload logic
        $uploadDir = $baseUploadDir . '999/'; // Test payment ID
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadedFiles = [];
        $uploadErrors = [];
        
        if (isset($_FILES['receipt_files']) && is_array($_FILES['receipt_files']['name'])) {
            $fileCount = count($_FILES['receipt_files']['name']);
            echo "Processing $fileCount files...\n";
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['receipt_files']['error'][$i] === UPLOAD_ERR_OK) {
                    $fileName = $_FILES['receipt_files']['name'][$i];
                    $fileTmpName = $_FILES['receipt_files']['tmp_name'][$i];
                    $fileSize = $_FILES['receipt_files']['size'][$i];
                    $fileType = $_FILES['receipt_files']['type'][$i];
                    
                    echo "  File $i: $fileName ($fileSize bytes, $fileType)\n";
                    
                    // For simulation, just copy the file
                    $uniqueFileName = 'receipt_' . time() . '_' . $i . '.txt';
                    $filePath = $uploadDir . $uniqueFileName;
                    
                    if (copy($fileTmpName, $filePath)) {
                        $uploadedFiles[] = [
                            'original_name' => $fileName,
                            'stored_name' => $uniqueFileName,
                            'file_path' => 'uploads/payment_receipts/999/' . $uniqueFileName,
                            'file_size' => $fileSize,
                            'file_type' => $fileType
                        ];
                        echo "  ✅ File uploaded successfully\n";
                    } else {
                        $uploadErrors[] = "Failed to upload file '$fileName'";
                        echo "  ❌ Failed to upload file\n";
                    }
                } else {
                    $uploadErrors[] = "Upload error for file: " . $_FILES['receipt_files']['name'][$i];
                    echo "  ❌ Upload error: " . $_FILES['receipt_files']['error'][$i] . "\n";
                }
            }
        }
        
        echo "\nUpload Results:\n";
        echo "Uploaded files: " . count($uploadedFiles) . "\n";
        echo "Upload errors: " . count($uploadErrors) . "\n";
        
        if (!empty($uploadedFiles)) {
            echo "✅ File upload simulation: SUCCESS\n";
            foreach ($uploadedFiles as $file) {
                echo "  - {$file['original_name']} -> {$file['stored_name']}\n";
            }
        } else {
            echo "❌ File upload simulation: FAILED\n";
            foreach ($uploadErrors as $error) {
                echo "  - $error\n";
            }
        }
        
        // Clean up test files
        unlink($testFilePath);
        if (!empty($uploadedFiles)) {
            foreach ($uploadedFiles as $file) {
                $fullPath = $baseUploadDir . '999/' . $file['stored_name'];
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
            rmdir($uploadDir);
        }
        
    } else {
        echo "❌ Failed to create test file\n";
    }
    
    echo "\n";
    
    // 5. Check common upload errors
    echo "5. COMMON UPLOAD ERROR ANALYSIS:\n";
    echo "===============================\n";
    
    $uploadErrors = [
        UPLOAD_ERR_OK => 'No error',
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
    ];
    
    foreach ($uploadErrors as $code => $description) {
        echo "Error $code: $description\n";
    }
    
    echo "\n";
    
    // 6. Check API endpoint
    echo "6. API ENDPOINT CHECK:\n";
    echo "=====================\n";
    
    $apiFile = 'backend/api/homeowner/upload_payment_receipt.php';
    if (file_exists($apiFile)) {
        echo "✅ API file exists: $apiFile\n";
        echo "File size: " . filesize($apiFile) . " bytes\n";
        echo "Last modified: " . date('Y-m-d H:i:s', filemtime($apiFile)) . "\n";
        
        // Check if file is readable
        if (is_readable($apiFile)) {
            echo "✅ API file is readable\n";
        } else {
            echo "❌ API file is not readable\n";
        }
    } else {
        echo "❌ API file does not exist: $apiFile\n";
    }
    
    echo "\n";
    
    // 7. Test with actual payment data
    echo "7. PAYMENT DATA TEST:\n";
    echo "====================\n";
    
    if ($db) {
        // Get a test payment for homeowner 32
        $stmt = $db->prepare("
            SELECT id, homeowner_id, contractor_id, requested_amount, status, stage_name
            FROM stage_payment_requests 
            WHERE homeowner_id = 32 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $testPayment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testPayment) {
            echo "✅ Test payment found:\n";
            echo "  Payment ID: {$testPayment['id']}\n";
            echo "  Homeowner ID: {$testPayment['homeowner_id']}\n";
            echo "  Amount: ₹{$testPayment['requested_amount']}\n";
            echo "  Status: {$testPayment['status']}\n";
            echo "  Stage: {$testPayment['stage_name']}\n";
            
            // Check if this payment can accept receipts
            $canUpload = in_array($testPayment['status'], ['approved', 'paid']);
            echo "  Can upload receipt: " . ($canUpload ? 'YES' : 'NO') . "\n";
            
            if (!$canUpload) {
                echo "  ⚠️  Payment status must be 'approved' or 'paid' to upload receipts\n";
            }
        } else {
            echo "❌ No test payment found for homeowner 32\n";
            
            // Check if homeowner 32 exists
            $userStmt = $db->prepare("SELECT id, first_name, last_name FROM users WHERE id = 32");
            $userStmt->execute();
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo "✅ Homeowner 32 exists: {$user['first_name']} {$user['last_name']}\n";
            } else {
                echo "❌ Homeowner 32 does not exist\n";
            }
        }
    }
    
    echo "\n";
    
    // 8. Recommendations
    echo "8. TROUBLESHOOTING RECOMMENDATIONS:\n";
    echo "==================================\n";
    
    echo "Based on the analysis above, here are the most likely causes:\n\n";
    
    echo "A. FILE UPLOAD ISSUES:\n";
    echo "   - Check if files are being selected properly in the frontend\n";
    echo "   - Verify file types are allowed (images, PDF)\n";
    echo "   - Ensure files are under 10MB limit\n";
    echo "   - Check PHP upload limits (upload_max_filesize, post_max_size)\n\n";
    
    echo "B. PERMISSION ISSUES:\n";
    echo "   - Ensure upload directory has write permissions (755 or 777)\n";
    echo "   - Check if web server can create subdirectories\n";
    echo "   - Verify file ownership and group permissions\n\n";
    
    echo "C. SESSION/AUTHENTICATION ISSUES:\n";
    echo "   - Verify user is logged in as homeowner\n";
    echo "   - Check if payment belongs to the logged-in homeowner\n";
    echo "   - Ensure payment status allows receipt upload\n\n";
    
    echo "D. FRONTEND ISSUES:\n";
    echo "   - Check browser console for JavaScript errors\n";
    echo "   - Verify FormData is being created correctly\n";
    echo "   - Ensure fetch request includes credentials\n\n";
    
    echo "E. SERVER CONFIGURATION:\n";
    echo "   - Check Apache/Nginx configuration for file uploads\n";
    echo "   - Verify .htaccess rules don't block uploads\n";
    echo "   - Check server disk space\n\n";
    
    echo "IMMEDIATE FIXES TO TRY:\n";
    echo "1. Refresh the page and try again\n";
    echo "2. Try uploading a smaller file (under 1MB)\n";
    echo "3. Try a different file format (JPG instead of PDF)\n";
    echo "4. Check browser console for errors\n";
    echo "5. Clear browser cache and cookies\n";
    
} catch (Exception $e) {
    echo "❌ DIAGNOSIS FAILED: " . $e->getMessage() . "\n";
}
?>