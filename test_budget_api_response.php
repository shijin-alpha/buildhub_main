<?php
header('Content-Type: application/json');
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== Testing Budget API Response ===\n\n";

    // Test 1: Check if budget_range is included in get_assigned_requests.php response
    echo "Test 1: API Response Structure\n";
    echo "------------------------------\n";
    
    // Create a test session (simulate architect login)
    session_start();
    $_SESSION['user_id'] = 27; // Use architect ID from database
    $_SESSION['role'] = 'architect';
    
    // Test the API query structure
    $apiQuery = "SELECT 
                a.id as assignment_id,
                a.status as assignment_status,
                lr.id as layout_request_id,
                lr.plot_size, lr.building_size, lr.budget_range, lr.requirements,
                lr.location, lr.timeline, lr.preferred_style,
                u.id as user_id, CONCAT(u.first_name, ' ', u.last_name) as homeowner_name
              FROM layout_request_assignments a
              JOIN layout_requests lr ON lr.id = a.layout_request_id
              JOIN users u ON u.id = a.homeowner_id
              WHERE a.architect_id = :aid AND lr.status != 'deleted'
              ORDER BY a.created_at DESC
              LIMIT 3";

    $stmt = $db->prepare($apiQuery);
    $stmt->execute([':aid' => 27]);

    $assignments = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $assignments[] = [
            'assignment_id' => (int)$row['assignment_id'],
            'layout_request' => [
                'id' => (int)$row['layout_request_id'],
                'plot_size' => $row['plot_size'],
                'building_size' => $row['building_size'],
                'budget_range' => $row['budget_range'], // This should be included
                'location' => $row['location'],
                'timeline' => $row['timeline'],
                'preferred_style' => $row['preferred_style']
            ],
            'homeowner' => [
                'name' => $row['homeowner_name']
            ]
        ];
    }

    if (count($assignments) > 0) {
        echo "✅ Found " . count($assignments) . " assignments\n\n";
        
        foreach ($assignments as $assignment) {
            $budgetRange = $assignment['layout_request']['budget_range'] ?? 'NULL';
            echo "Assignment {$assignment['assignment_id']}:\n";
            echo "  Request ID: {$assignment['layout_request']['id']}\n";
            echo "  Budget Range: '{$budgetRange}'\n";
            echo "  Plot Size: '{$assignment['layout_request']['plot_size']}'\n";
            echo "  Building Size: '{$assignment['layout_request']['building_size']}'\n";
            echo "  Location: '{$assignment['layout_request']['location']}'\n\n";
        }
        
        // Test budget parsing logic
        echo "Test 2: Budget Parsing Logic\n";
        echo "-----------------------------\n";
        
        foreach ($assignments as $assignment) {
            $budgetRange = $assignment['layout_request']['budget_range'];
            if ($budgetRange) {
                echo "Testing budget: '{$budgetRange}'\n";
                
                $result = parseBudgetRange($budgetRange);
                if ($result) {
                    echo "  ✅ Parsed successfully: {$result['range']}\n";
                    echo "  💰 Auto-populated: {$result['autoPopulated']}\n";
                } else {
                    echo "  ❌ Failed to parse\n";
                }
                echo "\n";
            }
        }
        
    } else {
        echo "⚠️ No assignments found for architect ID 27\n";
        
        // Check if there are any layout requests with budget_range
        echo "\nChecking all layout requests with budget_range:\n";
        $checkQuery = "SELECT id, budget_range, status FROM layout_requests WHERE budget_range IS NOT NULL AND budget_range != '' ORDER BY created_at DESC LIMIT 5";
        $stmt = $db->prepare($checkQuery);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "ID {$row['id']}: '{$row['budget_range']}' (status: {$row['status']})\n";
        }
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

function parseBudgetRange($budgetRange) {
    if (!$budgetRange) return null;
    
    $budget = trim($budgetRange);
    
    if (strpos($budget, '-') !== false) {
        // Range format
        $parts = explode('-', strtolower($budget));
        if (count($parts) === 2) {
            $lowBudget = floatval(preg_replace('/[^0-9.]/', '', $parts[0]));
            $highBudget = floatval(preg_replace('/[^0-9.]/', '', $parts[1]));
            
            if ($lowBudget > 0 && $highBudget > 0) {
                $multiplier = strpos(strtolower($budget), 'lakh') !== false ? 100000 : 1;
                $lowAmount = round($lowBudget * $multiplier);
                $highAmount = round($highBudget * $multiplier);
                $midAmount = round(($lowAmount + $highAmount) / 2);
                
                return [
                    'range' => "₹" . number_format($lowAmount) . " - ₹" . number_format($highAmount),
                    'autoPopulated' => "₹" . number_format($midAmount)
                ];
            }
        }
    } else {
        // Single value
        $budgetValue = floatval(preg_replace('/[^0-9.]/', '', $budget));
        if ($budgetValue > 0) {
            $multiplier = strpos(strtolower($budget), 'lakh') !== false ? 100000 : 1;
            $amount = round($budgetValue * multiplier);
            $lowAmount = round($amount * 0.9);
            $highAmount = round($amount * 1.1);
            
            return [
                'range' => "₹" . number_format($lowAmount) . " - ₹" . number_format($highAmount),
                'autoPopulated' => "₹" . number_format($amount)
            ];
        }
    }
    
    return null;
}
?>