<?php
/**
 * Verify Contractor AI Integration
 * 
 * This script checks if all components are properly configured
 * for displaying AI predictions in the contractor dashboard
 */

require_once 'backend/config/database.php';

echo "=" . str_repeat("=", 79) . "\n";
echo "CONTRACTOR AI INTEGRATION - VERIFICATION SCRIPT\n";
echo "=" . str_repeat("=", 79) . "\n\n";

$database = new Database();
$conn = $database->getConnection();

$all_checks_passed = true;

// ============================================================================
// Check 1: layout_requests prediction columns
// ============================================================================
echo "Check 1: layout_requests prediction columns\n";
echo str_repeat("-", 79) . "\n";

try {
    $stmt = $conn->query("SHOW COLUMNS FROM layout_requests LIKE 'predicted%'");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_columns = [
        'predicted_cost_risk_level',
        'predicted_cost_probability',
        'predicted_time_risk_level',
        'predicted_time_probability',
        'prediction_explanation',
    ];
    
    $missing = [];
    foreach ($required_columns as $col) {
        if (!in_array($col, $columns)) {
            $missing[] = $col;
        }
    }
    
    if (empty($missing)) {
        echo "✅ All prediction columns exist in layout_requests\n";
        echo "   Found: " . implode(", ", $columns) . "\n";
    } else {
        echo "❌ Missing columns in layout_requests:\n";
        foreach ($missing as $col) {
            echo "   - $col\n";
        }
        $all_checks_passed = false;
    }
} catch (Exception $e) {
    echo "❌ Error checking layout_requests: " . $e->getMessage() . "\n";
    $all_checks_passed = false;
}

echo "\n";

// ============================================================================
// Check 2: contractor_send_estimates prediction columns
// ============================================================================
echo "Check 2: contractor_send_estimates prediction columns\n";
echo str_repeat("-", 79) . "\n";

try {
    $stmt = $conn->query("SHOW COLUMNS FROM contractor_send_estimates LIKE 'predicted%'");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($columns) >= 5) {
        echo "✅ Prediction columns exist in contractor_send_estimates\n";
        echo "   Found: " . implode(", ", $columns) . "\n";
    } else {
        echo "❌ Missing prediction columns in contractor_send_estimates\n";
        $all_checks_passed = false;
    }
} catch (Exception $e) {
    echo "❌ Error checking contractor_send_estimates: " . $e->getMessage() . "\n";
    $all_checks_passed = false;
}

echo "\n";

// ============================================================================
// Check 3: construction_projects evaluation columns
// ============================================================================
echo "Check 3: construction_projects evaluation columns\n";
echo str_repeat("-", 79) . "\n";

try {
    $stmt = $conn->query("SHOW COLUMNS FROM construction_projects WHERE Field LIKE '%threshold%' OR Field LIKE '%ground_truth%'");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($columns) >= 6) {
        echo "✅ Evaluation columns exist in construction_projects\n";
        echo "   Found: " . implode(", ", $columns) . "\n";
    } else {
        echo "❌ Missing evaluation columns in construction_projects\n";
        echo "   Found only: " . implode(", ", $columns) . "\n";
        $all_checks_passed = false;
    }
} catch (Exception $e) {
    echo "❌ Error checking construction_projects: " . $e->getMessage() . "\n";
    $all_checks_passed = false;
}

echo "\n";

// ============================================================================
// Check 4: Evaluation procedures
// ============================================================================
echo "Check 4: Evaluation procedures\n";
echo str_repeat("-", 79) . "\n";

try {
    $stmt = $conn->query("SHOW PROCEDURE STATUS WHERE Db = DATABASE() AND Name LIKE '%3class%'");
    $procedures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($procedures) >= 2) {
        echo "✅ Evaluation procedures installed\n";
        foreach ($procedures as $proc) {
            echo "   - {$proc['Name']}\n";
        }
    } else {
        echo "⚠️  Evaluation procedures may need manual installation\n";
        echo "   Run: mysql -u root -p buildhub < backend/database/procedures/evaluate_project_3class.sql\n";
    }
} catch (Exception $e) {
    echo "⚠️  Could not check procedures: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================================
// Check 5: Sample data with predictions
// ============================================================================
echo "Check 5: Sample data with AI predictions\n";
echo str_repeat("-", 79) . "\n";

try {
    $stmt = $conn->query("
        SELECT COUNT(*) as count
        FROM layout_requests
        WHERE predicted_cost_risk_level IS NOT NULL
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = $result['count'];
    
    if ($count > 0) {
        echo "✅ Found $count layout requests with AI predictions\n";
        
        // Show sample
        $stmt = $conn->query("
            SELECT 
                id,
                predicted_cost_risk_level,
                predicted_cost_probability,
                predicted_time_risk_level,
                predicted_time_probability,
                model_version
            FROM layout_requests
            WHERE predicted_cost_risk_level IS NOT NULL
            ORDER BY id DESC
            LIMIT 3
        ");
        
        echo "\n   Sample predictions:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   ┌─────────────────────────────────────────────────────────────\n";
            echo "   │ Request ID: {$row['id']}\n";
            echo "   │ Cost Risk: {$row['predicted_cost_risk_level']} ";
            if ($row['predicted_cost_probability']) {
                echo "(" . ($row['predicted_cost_probability'] * 100) . "%)\n";
            } else {
                echo "\n";
            }
            echo "   │ Time Risk: {$row['predicted_time_risk_level']} ";
            if ($row['predicted_time_probability']) {
                echo "(" . ($row['predicted_time_probability'] * 100) . "%)\n";
            } else {
                echo "\n";
            }
            echo "   │ Model: {$row['model_version']}\n";
            echo "   └─────────────────────────────────────────────────────────────\n";
        }
    } else {
        echo "ℹ️  No layout requests with AI predictions yet\n";
        echo "   Submit a new homeowner request to generate predictions\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking sample data: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================================
// Check 6: API file exists and has AI prediction code
// ============================================================================
echo "Check 6: Backend API configuration\n";
echo str_repeat("-", 79) . "\n";

$api_file = 'backend/api/contractor/get_inbox.php';
if (file_exists($api_file)) {
    $content = file_get_contents($api_file);
    
    if (strpos($content, 'predicted_cost_risk_level') !== false) {
        echo "✅ API includes AI prediction fields in query\n";
    } else {
        echo "❌ API does not include AI prediction fields\n";
        $all_checks_passed = false;
    }
    
    if (strpos($content, 'ai_predictions') !== false) {
        echo "✅ API returns ai_predictions in response\n";
    } else {
        echo "❌ API does not return ai_predictions\n";
        $all_checks_passed = false;
    }
    
    if (strpos($content, 'LEFT JOIN layout_requests lr') !== false) {
        echo "✅ API joins layout_requests table\n";
    } else {
        echo "❌ API does not join layout_requests table\n";
        $all_checks_passed = false;
    }
} else {
    echo "❌ API file not found: $api_file\n";
    $all_checks_passed = false;
}

echo "\n";

// ============================================================================
// Check 7: Frontend file exists and has AI display code
// ============================================================================
echo "Check 7: Frontend configuration\n";
echo str_repeat("-", 79) . "\n";

$frontend_file = 'frontend/src/components/ContractorDashboard.jsx';
if (file_exists($frontend_file)) {
    $content = file_get_contents($frontend_file);
    
    if (strpos($content, 'AI Risk Assessment') !== false) {
        echo "✅ Frontend includes AI Risk Assessment display\n";
    } else {
        echo "❌ Frontend does not include AI Risk Assessment display\n";
        $all_checks_passed = false;
    }
    
    if (strpos($content, 'ai_predictions') !== false) {
        echo "✅ Frontend reads ai_predictions from API\n";
    } else {
        echo "❌ Frontend does not read ai_predictions\n";
        $all_checks_passed = false;
    }
    
    if (strpos($content, 'getRiskColor') !== false) {
        echo "✅ Frontend has risk visualization functions\n";
    } else {
        echo "❌ Frontend missing risk visualization functions\n";
        $all_checks_passed = false;
    }
} else {
    echo "❌ Frontend file not found: $frontend_file\n";
    $all_checks_passed = false;
}

echo "\n";

// ============================================================================
// Summary
// ============================================================================
echo str_repeat("=", 79) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 79) . "\n\n";

if ($all_checks_passed) {
    echo "✅ ALL CHECKS PASSED!\n\n";
    echo "Your contractor AI integration is ready to use.\n\n";
    echo "Next Steps:\n";
    echo "1. Rebuild frontend: cd frontend && npm run build\n";
    echo "2. Submit a new homeowner request with AI predictions\n";
    echo "3. Send request to contractor\n";
    echo "4. Login as contractor and check inbox\n";
    echo "5. You should see AI Risk Assessment with predictions\n";
} else {
    echo "⚠️  SOME CHECKS FAILED\n\n";
    echo "Please review the errors above and:\n";
    echo "1. Run: php APPLY_ML_FIXES_NOW.php\n";
    echo "2. Verify all files are in place\n";
    echo "3. Run this verification script again\n";
}

echo "\n" . str_repeat("=", 79) . "\n";

$conn = null;
?>
