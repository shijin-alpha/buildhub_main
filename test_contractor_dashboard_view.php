<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== TESTING CONTRACTOR DASHBOARD VIEW ===\n";
    
    $contractor_id = 1;
    $project_id = 2;
    
    echo "Contractor ID: $contractor_id\n";
    echo "Project ID: $project_id\n\n";
    
    // Test the exact query used in the payment history API
    $payment_query = "
        SELECT 
            spr.*,
            u.first_name, u.last_name
        FROM stage_payment_requests spr
        LEFT JOIN users u ON spr.homeowner_id = u.id
        WHERE spr.project_id = ? 
        AND spr.contractor_id = ?
        ORDER BY spr.request_date DESC
    ";
    
    $payment_stmt = $db->prepare($payment_query);
    $payment_stmt->execute([$project_id, $contractor_id]);
    $payment_requests = $payment_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($payment_requests) . " payment requests:\n\n";
    
    foreach ($payment_requests as $request) {
        echo "Payment ID: {$request['id']}\n";
        echo "  Stage: {$request['stage_name']}\n";
        echo "  Amount: ₹{$request['requested_amount']}\n";
        echo "  Status: {$request['status']}\n";
        echo "  Homeowner: {$request['first_name']} {$request['last_name']}\n";
        echo "  Request Date: {$request['request_date']}\n";
        
        // Receipt information
        echo "  Transaction Reference: " . ($request['transaction_reference'] ?: 'NULL') . "\n";
        echo "  Payment Date: " . ($request['payment_date'] ?: 'NULL') . "\n";
        echo "  Payment Method: " . ($request['payment_method'] ?: 'NULL') . "\n";
        echo "  Verification Status: " . ($request['verification_status'] ?: 'NULL') . "\n";
        
        if ($request['receipt_file_path']) {
            echo "  Receipt Files: YES\n";
            $receiptFiles = json_decode($request['receipt_file_path'], true);
            if ($receiptFiles && is_array($receiptFiles)) {
                foreach ($receiptFiles as $index => $file) {
                    echo "    File " . ($index + 1) . ": {$file['original_name']} ({$file['file_type']})\n";
                }
            }
        } else {
            echo "  Receipt Files: NO\n";
        }
        
        echo "  ---\n";
    }
    
    // Check if payment ID 16 is in the results
    $payment16Found = false;
    foreach ($payment_requests as $request) {
        if ($request['id'] == 16) {
            $payment16Found = true;
            echo "\n✅ PAYMENT ID 16 FOUND IN CONTRACTOR VIEW!\n";
            echo "This payment should be visible in the contractor dashboard.\n";
            
            if ($request['receipt_file_path']) {
                echo "✅ Receipt data is available for verification.\n";
                echo "Verification status: " . ($request['verification_status'] ?: 'pending') . "\n";
            } else {
                echo "❌ No receipt data found.\n";
            }
            break;
        }
    }
    
    if (!$payment16Found) {
        echo "\n❌ PAYMENT ID 16 NOT FOUND IN CONTRACTOR VIEW\n";
        echo "This means the contractor cannot see this payment request.\n";
    }
    
    // Check contractor projects to see if they have access to project 2
    echo "\n=== CHECKING CONTRACTOR PROJECT ACCESS ===\n";
    
    // Check construction_projects table
    $project_check = $db->prepare("
        SELECT id, homeowner_id, contractor_id, project_name, status
        FROM construction_projects 
        WHERE id = ? OR estimate_id = ?
    ");
    $project_check->execute([$project_id, $project_id]);
    $project = $project_check->fetch(PDO::FETCH_ASSOC);
    
    if ($project) {
        echo "Construction project found:\n";
        echo "  Project ID: {$project['id']}\n";
        echo "  Homeowner ID: {$project['homeowner_id']}\n";
        echo "  Contractor ID: " . ($project['contractor_id'] ?: 'NULL') . "\n";
        echo "  Project Name: {$project['project_name']}\n";
        echo "  Status: {$project['status']}\n";
        
        if ($project['contractor_id'] == $contractor_id) {
            echo "✅ Contractor has access to this project\n";
        } else {
            echo "❌ Contractor does NOT have access to this project\n";
            echo "Expected contractor ID: $contractor_id, Actual: " . ($project['contractor_id'] ?: 'NULL') . "\n";
        }
    } else {
        // Check layout_requests table
        $layout_check = $db->prepare("
            SELECT id, user_id, contractor_id, project_name, status
            FROM layout_requests 
            WHERE id = ?
        ");
        $layout_check->execute([$project_id]);
        $layout = $layout_check->fetch(PDO::FETCH_ASSOC);
        
        if ($layout) {
            echo "Layout request found:\n";
            echo "  Layout ID: {$layout['id']}\n";
            echo "  User ID: {$layout['user_id']}\n";
            echo "  Contractor ID: " . ($layout['contractor_id'] ?: 'NULL') . "\n";
            echo "  Project Name: {$layout['project_name']}\n";
            echo "  Status: {$layout['status']}\n";
            
            if ($layout['contractor_id'] == $contractor_id) {
                echo "✅ Contractor has access to this layout request\n";
            } else {
                echo "❌ Contractor does NOT have access to this layout request\n";
            }
        } else {
            echo "❌ No project found with ID $project_id\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>