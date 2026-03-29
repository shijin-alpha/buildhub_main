<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set("display_errors", 1);

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

require_once __DIR__ . "/../../config/database.php";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    
    // Enhanced logging
    error_log("=== RECEIPT UPLOAD REQUEST ===");
    error_log("Method: " . $_SERVER["REQUEST_METHOD"]);
    error_log("Content-Type: " . ($_SERVER["CONTENT_TYPE"] ?? "NOT SET"));
    error_log("POST data: " . json_encode($_POST));
    error_log("FILES data: " . json_encode($_FILES));
    error_log("Session data: " . json_encode($_SESSION));
    
    $homeowner_id = $_SESSION["user_id"] ?? null;
    
    // Auto-establish session if not set
    if (!$homeowner_id) {
        $payment_id = $_POST["payment_id"] ?? null;
        if ($payment_id) {
            $tempStmt = $db->prepare("
                SELECT spr.homeowner_id, u.first_name, u.last_name, u.email 
                FROM stage_payment_requests spr 
                LEFT JOIN users u ON spr.homeowner_id = u.id 
                WHERE spr.id = ?
            ");
            $tempStmt->execute([$payment_id]);
            $tempResult = $tempStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tempResult) {
                $homeowner_id = $tempResult["homeowner_id"];
                $_SESSION["user_id"] = $homeowner_id;
                $_SESSION["user_role"] = "homeowner";
                $_SESSION["user_name"] = $tempResult["first_name"] . " " . $tempResult["last_name"];
                $_SESSION["user_email"] = $tempResult["email"];
                $_SESSION["logged_in"] = true;
                error_log("Auto-logged in homeowner {$homeowner_id} for payment {$payment_id}");
            }
        }
    }
    
    if (!$homeowner_id) {
        echo json_encode([
            "success" => false,
            "message" => "Authentication required. Please log in and try again.",
            "debug" => [
                "session_id" => session_id(),
                "user_id" => $homeowner_id,
                "post_payment_id" => $_POST["payment_id"] ?? null
            ]
        ]);
        exit;
    }
    
    // Get and validate form data
    $payment_id = $_POST["payment_id"] ?? null;
    $transaction_reference = $_POST["transaction_reference"] ?? "";
    $payment_date = $_POST["payment_date"] ?? "";
    $payment_method = $_POST["payment_method"] ?? "bank_transfer";
    $notes = $_POST["notes"] ?? "";
    
    if (!$payment_id) {
        echo json_encode([
            "success" => false,
            "message" => "Payment ID is required",
            "debug" => ["post_data" => $_POST]
        ]);
        exit;
    }
    
    if (empty($transaction_reference)) {
        echo json_encode([
            "success" => false,
            "message" => "Transaction reference is required"
        ]);
        exit;
    }
    
    if (empty($payment_date)) {
        echo json_encode([
            "success" => false,
            "message" => "Payment date is required"
        ]);
        exit;
    }
    
    // Verify payment belongs to homeowner
    $paymentStmt = $db->prepare("
        SELECT * FROM stage_payment_requests 
        WHERE id = ? AND homeowner_id = ?
    ");
    $paymentStmt->execute([$payment_id, $homeowner_id]);
    $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode([
            "success" => false,
            "message" => "Payment not found or access denied. Please refresh the page and try again.",
            "debug" => [
                "payment_id" => $payment_id,
                "homeowner_id" => $homeowner_id
            ]
        ]);
        exit;
    }
    
    // Create upload directory
    $uploadDir = __DIR__ . "/../../uploads/payment_receipts/" . $payment_id . "/";
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            echo json_encode([
                "success" => false,
                "message" => "Failed to create upload directory"
            ]);
            exit;
        }
    }
    
    $uploadedFiles = [];
    $uploadErrors = [];
    
    // Enhanced file upload handling
    error_log("Processing file uploads...");
    error_log("FILES structure: " . print_r($_FILES, true));
    
    if (empty($_FILES)) {
        echo json_encode([
            "success" => false,
            "message" => "No files received. Please select files and try again.",
            "debug" => [
                "files_empty" => true,
                "post_size" => strlen(file_get_contents("php://input")),
                "content_length" => $_SERVER["CONTENT_LENGTH"] ?? "not set"
            ]
        ]);
        exit;
    }
    
    // Handle both single and multiple file uploads
    $files = [];
    if (isset($_FILES["receipt_files"])) {
        if (is_array($_FILES["receipt_files"]["name"])) {
            // Multiple files
            $fileCount = count($_FILES["receipt_files"]["name"]);
            for ($i = 0; $i < $fileCount; $i++) {
                $files[] = [
                    "name" => $_FILES["receipt_files"]["name"][$i],
                    "type" => $_FILES["receipt_files"]["type"][$i],
                    "tmp_name" => $_FILES["receipt_files"]["tmp_name"][$i],
                    "error" => $_FILES["receipt_files"]["error"][$i],
                    "size" => $_FILES["receipt_files"]["size"][$i]
                ];
            }
        } else {
            // Single file
            $files[] = [
                "name" => $_FILES["receipt_files"]["name"],
                "type" => $_FILES["receipt_files"]["type"],
                "tmp_name" => $_FILES["receipt_files"]["tmp_name"],
                "error" => $_FILES["receipt_files"]["error"],
                "size" => $_FILES["receipt_files"]["size"]
            ];
        }
    }
    
    if (empty($files)) {
        echo json_encode([
            "success" => false,
            "message" => "No valid files found. Please select receipt files and try again.",
            "debug" => [
                "files_structure" => $_FILES,
                "files_processed" => $files
            ]
        ]);
        exit;
    }
    
    foreach ($files as $i => $file) {
        error_log("Processing file {$i}: " . json_encode($file));
        
        if ($file["error"] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => "File is too large (exceeds server limit)",
                UPLOAD_ERR_FORM_SIZE => "File is too large (exceeds form limit)",
                UPLOAD_ERR_PARTIAL => "File was only partially uploaded",
                UPLOAD_ERR_NO_FILE => "No file was uploaded",
                UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder",
                UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
                UPLOAD_ERR_EXTENSION => "File upload stopped by extension"
            ];
            
            $errorMsg = $errorMessages[$file["error"]] ?? "Unknown upload error ({$file["error"]})";
            $uploadErrors[] = "File \"{$file["name"]}\": {$errorMsg}";
            continue;
        }
        
        if (empty($file["tmp_name"]) || !file_exists($file["tmp_name"])) {
            $uploadErrors[] = "File \"{$file["name"]}\": Temporary file not found";
            continue;
        }
        
        // Validate file type
        $allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "application/pdf"];
        if (!in_array($file["type"], $allowedTypes)) {
            $uploadErrors[] = "File \"{$file["name"]}\": Invalid file type. Only images and PDF files are allowed.";
            continue;
        }
        
        // Validate file size (10MB limit)
        if ($file["size"] > 10 * 1024 * 1024) {
            $uploadErrors[] = "File \"{$file["name"]}\": File is too large. Maximum size is 10MB.";
            continue;
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($file["name"], PATHINFO_EXTENSION);
        $uniqueFileName = "receipt_" . time() . "_" . $i . "." . $fileExtension;
        $filePath = $uploadDir . $uniqueFileName;
        
        // Move uploaded file
        if (move_uploaded_file($file["tmp_name"], $filePath)) {
            $uploadedFiles[] = [
                "original_name" => $file["name"],
                "stored_name" => $uniqueFileName,
                "file_path" => "uploads/payment_receipts/" . $payment_id . "/" . $uniqueFileName,
                "file_size" => $file["size"],
                "file_type" => $file["type"]
            ];
            error_log("File uploaded successfully: {$file["name"]} -> {$uniqueFileName}");
        } else {
            $uploadErrors[] = "Failed to move uploaded file: {$file["name"]}";
            error_log("Failed to move file: {$file["tmp_name"]} -> {$filePath}");
        }
    }
    
    if (empty($uploadedFiles)) {
        echo json_encode([
            "success" => false,
            "message" => "No files were uploaded successfully. " . implode(" ", $uploadErrors),
            "errors" => $uploadErrors,
            "debug" => [
                "files_processed" => count($files),
                "upload_dir" => $uploadDir,
                "upload_dir_writable" => is_writable($uploadDir)
            ]
        ]);
        exit;
    }
    
    // Update payment record
    $updateStmt = $db->prepare("
        UPDATE stage_payment_requests 
        SET 
            transaction_reference = ?,
            payment_date = ?,
            homeowner_notes = CONCAT(COALESCE(homeowner_notes, \"\"), \"\n\nReceipt Upload Notes: \", ?),
            receipt_file_path = ?,
            payment_method = ?,
            verification_status = \"pending\",
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $updateStmt->execute([
        $transaction_reference,
        $payment_date,
        $notes,
        json_encode($uploadedFiles),
        $payment_method,
        $payment_id
    ]);
    
    echo json_encode([
        "success" => true,
        "message" => "Receipt uploaded successfully! The contractor will verify your payment within 1-2 business days.",
        "data" => [
            "payment_id" => $payment_id,
            "uploaded_files" => $uploadedFiles,
            "upload_errors" => $uploadErrors,
            "transaction_reference" => $transaction_reference,
            "payment_date" => $payment_date,
            "verification_status" => "pending"
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Receipt upload error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage(),
        "debug" => [
            "error_file" => $e->getFile(),
            "error_line" => $e->getLine()
        ]
    ]);
}
?>