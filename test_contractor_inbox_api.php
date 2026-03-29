<?php
/**
 * Test Contractor Inbox API
 * Diagnose why inbox is empty
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'backend/config/database.php';

echo "=" . str_repeat("=", 79) . "\n";
echo "CONTRACTOR INBOX API - DIAGNOSTIC TEST\n";
echo "=" . str_repeat("=", 79) . "\n\n";

$database = new Database();
$db = $database->getConnection();

// Test 1: Check if contractor_layout_sends has data
echo "Test 1: Check contractor_layout_sends table\n";
echo str_repeat("-", 79) . "\n";

try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM contractor_layout_sends");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total records in contractor_layout_sends: {$result['count']}\n";
    
    if ($result['count'] > 0) {
        $stmt = $db->query("SELECT id, contractor_id, homeowner_id, layout_id, created_at FROM contractor_layout_sends ORDER BY id DESC LIMIT 5");
        echo "\nRecent records:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  ID: {$row['id']}, Contractor: {$row['contractor_id']}, Homeowner: {$row['homeowner_id']}, Layout: {$row['layout_id']}, Created: {$row['created_at']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Check if contractor_inbox has data
echo "Test 2: Check contractor_inbox table\n";
echo str_repeat("-", 79) . "\n";

try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM contractor_inbox");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total records in contractor_inbox: {$result['count']}\n";
    
    if ($result['count'] > 0) {
        $stmt = $db->query("SELECT id, contractor_id, homeowner_id, type, title, created_at FROM contractor_inbox ORDER BY id DESC LIMIT 5");
        echo "\nRecent records:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  ID: {$row['id']}, Contractor: {$row['contractor_id']}, Type: {$row['type']}, Title: {$row['title']}, Created: {$row['created_at']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Test the actual UNION query
echo "Test 3: Test UNION query (contractor_id = 1)\n";
echo str_repeat("-", 79) . "\n";

try {
    $contractorId = 1;
    
    $stmt = $db->prepare("
        SELECT s.id, s.contractor_id, s.homeowner_id, s.layout_id, s.design_id, NULL as estimate_id,
               s.message, s.payload, s.created_at, s.acknowledged_at, s.due_date,
               CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS homeowner_name,
               u.email AS homeowner_email,
               'layout_request' as type,
               'New layout sent' as title,
               'unread' as status,
               lr.predicted_cost_risk_level,
               lr.predicted_cost_probability,
               lr.predicted_time_risk_level,
               lr.predicted_time_probability,
               lr.prediction_explanation,
               lr.model_version as prediction_model_version
        FROM contractor_layout_sends s
        LEFT JOIN users u ON u.id = s.homeowner_id
        LEFT JOIN layout_requests lr ON s.layout_id = lr.id
        WHERE s.contractor_id = :cid1
        
        UNION ALL
        
        SELECT ci.id, ci.contractor_id, ci.homeowner_id, NULL as layout_id, NULL as design_id, ci.estimate_id,
               ci.message, NULL as payload, ci.created_at, ci.acknowledged_at, ci.due_date,
               CONCAT(COALESCE(u2.first_name,''), ' ', COALESCE(u2.last_name,'')) AS homeowner_name,
               u2.email AS homeowner_email,
               ci.type,
               ci.title,
               ci.status,
               NULL as predicted_cost_risk_level,
               NULL as predicted_cost_probability,
               NULL as predicted_time_risk_level,
               NULL as predicted_time_probability,
               NULL as prediction_explanation,
               NULL as prediction_model_version
        FROM contractor_inbox ci
        LEFT JOIN users u2 ON u2.id = ci.homeowner_id
        WHERE ci.contractor_id = :cid2
        
        ORDER BY created_at DESC
    ");
    
    $stmt->bindValue(':cid1', $contractorId, PDO::PARAM_INT);
    $stmt->bindValue(':cid2', $contractorId, PDO::PARAM_INT);
    $stmt->execute();
    
    $count = 0;
    echo "Results:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        echo "  {$count}. ID: {$row['id']}, Type: {$row['type']}, Title: {$row['title']}\n";
        echo "      Homeowner: {$row['homeowner_name']}, Created: {$row['created_at']}\n";
        if ($row['predicted_cost_risk_level']) {
            echo "      AI: Cost={$row['predicted_cost_risk_level']}, Time={$row['predicted_time_risk_level']}\n";
        }
    }
    
    if ($count == 0) {
        echo "  No results found for contractor_id = $contractorId\n";
    } else {
        echo "\nTotal: $count records\n";
    }
    
} catch (Exception $e) {
    echo "Error executing query: " . $e->getMessage() . "\n";
    echo "SQL Error Code: " . $e->getCode() . "\n";
}

echo "\n";

// Test 4: Check if layout_requests has prediction columns
echo "Test 4: Check layout_requests prediction columns\n";
echo str_repeat("-", 79) . "\n";

try {
    $stmt = $db->query("SHOW COLUMNS FROM layout_requests LIKE 'predicted%'");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($columns) > 0) {
        echo "Prediction columns found: " . implode(", ", $columns) . "\n";
    } else {
        echo "⚠️  No prediction columns found in layout_requests\n";
        echo "Run: php APPLY_ML_FIXES_NOW.php\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Check contractor_layout_sends columns
echo "Test 5: Check contractor_layout_sends columns\n";
echo str_repeat("-", 79) . "\n";

try {
    $stmt = $db->query("SHOW COLUMNS FROM contractor_layout_sends");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(", ", $columns) . "\n";
    
    // Check for acknowledged_at and due_date
    if (!in_array('acknowledged_at', $columns)) {
        echo "⚠️  Missing 'acknowledged_at' column\n";
    }
    if (!in_array('due_date', $columns)) {
        echo "⚠️  Missing 'due_date' column\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: Try different contractor IDs
echo "Test 6: Check all contractor IDs in contractor_layout_sends\n";
echo str_repeat("-", 79) . "\n";

try {
    $stmt = $db->query("SELECT DISTINCT contractor_id FROM contractor_layout_sends ORDER BY contractor_id");
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($ids) > 0) {
        echo "Contractor IDs with inbox items: " . implode(", ", $ids) . "\n";
    } else {
        echo "No contractor IDs found in contractor_layout_sends\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

echo str_repeat("=", 79) . "\n";
echo "DIAGNOSTIC COMPLETE\n";
echo str_repeat("=", 79) . "\n";

$db = null;
?>
