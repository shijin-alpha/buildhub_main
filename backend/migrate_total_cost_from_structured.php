<?php
/**
 * Migration Script: Extract total_cost from structured JSON field
 * 
 * This script reads the 'structured' JSON field from contractor_send_estimates
 * and populates the 'total_cost' column with the grand total value.
 */

header('Content-Type: text/plain; charset=utf-8');

try {
    $host = 'localhost';
    $dbname = 'buildhub';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Migrating Total Cost from Structured JSON ===\n\n";
    
    // Get all records from contractor_send_estimates
    $stmt = $pdo->query("
        SELECT id, contractor_id, structured, total_cost 
        FROM contractor_send_estimates 
        WHERE structured IS NOT NULL
    ");
    
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_records = count($records);
    
    echo "Found {$total_records} records with structured data\n\n";
    
    if ($total_records === 0) {
        echo "No records to process.\n";
        exit;
    }
    
    $updated_count = 0;
    $skipped_count = 0;
    $error_count = 0;
    
    foreach ($records as $record) {
        $id = $record['id'];
        $current_total = $record['total_cost'];
        $structured_json = $record['structured'];
        
        // Parse the structured JSON
        $structured = json_decode($structured_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "❌ Record ID {$id}: Invalid JSON - " . json_last_error_msg() . "\n";
            $error_count++;
            continue;
        }
        
        // Try to find the grand total in various possible locations
        $grand_total = null;
        
        // Method 1: Check totals.grand
        if (isset($structured['totals']['grand'])) {
            $grand_total = $structured['totals']['grand'];
        }
        // Method 2: Check totals.grandTotal
        elseif (isset($structured['totals']['grandTotal'])) {
            $grand_total = $structured['totals']['grandTotal'];
        }
        // Method 3: Check totals.total
        elseif (isset($structured['totals']['total'])) {
            $grand_total = $structured['totals']['total'];
        }
        // Method 4: Calculate from individual totals
        elseif (isset($structured['totals'])) {
            $totals = $structured['totals'];
            $grand_total = 0;
            
            // Sum up all category totals
            if (isset($totals['materials'])) $grand_total += floatval($totals['materials']);
            if (isset($totals['labor'])) $grand_total += floatval($totals['labor']);
            if (isset($totals['utilities'])) $grand_total += floatval($totals['utilities']);
            if (isset($totals['misc'])) $grand_total += floatval($totals['misc']);
            if (isset($totals['miscellaneous'])) $grand_total += floatval($totals['miscellaneous']);
            
            if ($grand_total > 0) {
                echo "ℹ️  Record ID {$id}: Calculated grand total from category totals\n";
            }
        }
        // Method 5: Check direct grand field
        elseif (isset($structured['grand'])) {
            $grand_total = $structured['grand'];
        }
        // Method 6: Check grandTotal field
        elseif (isset($structured['grandTotal'])) {
            $grand_total = $structured['grandTotal'];
        }
        
        if ($grand_total === null || $grand_total <= 0) {
            echo "⚠️  Record ID {$id}: No valid grand total found in structured data\n";
            $skipped_count++;
            continue;
        }
        
        // Convert to float
        $grand_total = floatval($grand_total);
        
        // Update the total_cost column
        $update_stmt = $pdo->prepare("
            UPDATE contractor_send_estimates 
            SET total_cost = ? 
            WHERE id = ?
        ");
        
        $update_stmt->execute([$grand_total, $id]);
        
        if ($current_total === null || $current_total == 0) {
            echo "✅ Record ID {$id}: Updated total_cost to ₹" . number_format($grand_total, 2) . "\n";
        } else {
            echo "🔄 Record ID {$id}: Updated total_cost from ₹" . number_format($current_total, 2) . " to ₹" . number_format($grand_total, 2) . "\n";
        }
        
        $updated_count++;
    }
    
    echo "\n=== Migration Complete ===\n";
    echo "Total Records: {$total_records}\n";
    echo "✅ Updated: {$updated_count}\n";
    echo "⚠️  Skipped: {$skipped_count}\n";
    echo "❌ Errors: {$error_count}\n";
    
    // Show sample of updated records
    echo "\n=== Sample of Updated Records ===\n";
    $sample_stmt = $pdo->query("
        SELECT 
            cse.id,
            cse.contractor_id,
            cse.total_cost,
            cse.status,
            CONCAT(u.first_name, ' ', u.last_name) as contractor_name
        FROM contractor_send_estimates cse
        LEFT JOIN users u ON u.id = cse.contractor_id
        WHERE cse.total_cost IS NOT NULL AND cse.total_cost > 0
        ORDER BY cse.created_at DESC
        LIMIT 10
    ");
    
    $samples = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($samples as $sample) {
        echo "ID: {$sample['id']}, Contractor: {$sample['contractor_name']}, Total: ₹" . number_format($sample['total_cost'], 2) . ", Status: {$sample['status']}\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
