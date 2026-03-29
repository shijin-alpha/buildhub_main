<?php
/**
 * Test the received designs API logic directly
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== Testing Received Designs with House Plans ===\n\n";

    $user_id = 28; // SHIJIN THOMAS MCA2024-2026
    echo "Testing for Homeowner ID: $user_id\n\n";

    // Test the first query - regular designs
    echo "1. Testing Regular Designs Query:\n";
    $sql = "SELECT d.*, 
                   a.first_name AS architect_first_name, 
                   a.last_name AS architect_last_name, 
                   a.email AS architect_email,
                   lr.selected_layout_id AS selected_layout_id,
                   'design' as source_type
            FROM designs d
            JOIN users a ON d.architect_id = a.id
            LEFT JOIN layout_requests lr ON lr.id = d.layout_request_id
            WHERE d.homeowner_id = :uid1
               OR d.layout_request_id IN (SELECT lr2.id FROM layout_requests lr2 WHERE lr2.homeowner_id = :uid2)
            ORDER BY d.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':uid1', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':uid2', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $designRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($designRows) . " regular designs\n\n";

    // Test the second query - house plans
    echo "2. Testing House Plans Query:\n";
    $housePlanSql = "SELECT 
                        hp.*,
                        a.first_name AS architect_first_name, 
                        a.last_name AS architect_last_name, 
                        a.email AS architect_email,
                        lr.selected_layout_id AS selected_layout_id,
                        'house_plan' as source_type
                     FROM house_plans hp
                     INNER JOIN layout_requests lr ON hp.layout_request_id = lr.id
                     INNER JOIN users a ON hp.architect_id = a.id
                     WHERE lr.user_id = :homeowner_id 
                       AND hp.status IN ('submitted', 'approved', 'rejected')
                       AND hp.technical_details IS NOT NULL 
                       AND hp.technical_details != ''
                     ORDER BY hp.updated_at DESC";

    $housePlanStmt = $db->prepare($housePlanSql);
    $housePlanStmt->bindParam(':homeowner_id', $user_id, PDO::PARAM_INT);
    $housePlanStmt->execute();
    $housePlanRows = $housePlanStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($housePlanRows) . " house plans with technical details\n\n";

    // Combine and process
    $allRows = array_merge($designRows, $housePlanRows);
    echo "3. Combined Results:\n";
    echo "Total items: " . count($allRows) . "\n\n";

    // Sort by creation date
    usort($allRows, function($a, $b) {
        $dateA = strtotime($a['source_type'] === 'house_plan' ? $a['updated_at'] : $a['created_at']);
        $dateB = strtotime($b['source_type'] === 'house_plan' ? $b['updated_at'] : $b['created_at']);
        return $dateB - $dateA;
    });

    $designs = [];
    foreach ($allRows as $row) {
        if ($row['source_type'] === 'house_plan') {
            // Handle house plan data
            $plan_data = json_decode($row['plan_data'], true) ?? [];
            $technical_details = json_decode($row['technical_details'], true) ?? [];
            
            // Create files array from technical details
            $files = [];
            if (!empty($technical_details['layout_image'])) {
                $layoutImage = $technical_details['layout_image'];
                if (is_array($layoutImage) && !empty($layoutImage['name'])) {
                    $files[] = [
                        'original' => $layoutImage['name'],
                        'stored' => $layoutImage['name'],
                        'ext' => strtolower(pathinfo($layoutImage['name'], PATHINFO_EXTENSION)),
                        'path' => '/buildhub/backend/uploads/house_plans/' . $layoutImage['name'],
                        'type' => 'layout_image'
                    ];
                }
            }

            $designs[] = [
                'id' => 'hp_' . $row['id'],
                'house_plan_id' => (int)$row['id'],
                'layout_request_id' => $row['layout_request_id'] ? (int)$row['layout_request_id'] : null,
                'design_title' => $row['plan_name'] . ' (House Plan)',
                'description' => $row['notes'] ?: 'House plan with technical specifications',
                'files' => $files,
                'technical_details' => $technical_details,
                'plan_data' => $plan_data,
                'status' => 'proposed',
                'source_type' => 'house_plan',
                'house_plan_status' => $row['status'],
                'plot_dimensions' => $row['plot_width'] . ' × ' . $row['plot_height'],
                'total_area' => (float)$row['total_area'],
                'architect' => [
                    'name' => trim(($row['architect_first_name'] ?? '') . ' ' . ($row['architect_last_name'] ?? '')),
                    'email' => $row['architect_email'] ?? null
                ],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        } else {
            // Handle regular design (simplified for test)
            $designs[] = [
                'id' => (int)$row['id'],
                'design_title' => $row['design_title'],
                'source_type' => 'design',
                'architect' => [
                    'name' => trim(($row['architect_first_name'] ?? '') . ' ' . ($row['architect_last_name'] ?? '')),
                    'email' => $row['architect_email'] ?? null
                ],
                'created_at' => $row['created_at']
            ];
        }
    }

    echo "4. Processed Results:\n";
    foreach ($designs as $design) {
        echo "- ID: " . $design['id'] . "\n";
        echo "  Title: " . $design['design_title'] . "\n";
        echo "  Type: " . $design['source_type'] . "\n";
        echo "  Architect: " . ($design['architect']['name'] ?? 'Unknown') . "\n";
        
        if ($design['source_type'] === 'house_plan') {
            echo "  Plot: " . ($design['plot_dimensions'] ?? 'N/A') . "\n";
            echo "  Area: " . ($design['total_area'] ?? 0) . " sq ft\n";
            echo "  Status: " . ($design['house_plan_status'] ?? 'N/A') . "\n";
            echo "  Files: " . count($design['files'] ?? []) . "\n";
            echo "  Technical Details: " . (empty($design['technical_details']) ? 'No' : 'Yes') . "\n";
        }
        echo "\n";
    }

    // Simulate final API response
    $apiResponse = [
        'success' => true,
        'designs' => $designs
    ];

    echo "5. Final API Response Summary:\n";
    echo "Success: " . ($apiResponse['success'] ? 'true' : 'false') . "\n";
    echo "Total designs: " . count($apiResponse['designs']) . "\n";
    
    $regularCount = count(array_filter($apiResponse['designs'], function($d) { return $d['source_type'] === 'design'; }));
    $housePlanCount = count(array_filter($apiResponse['designs'], function($d) { return $d['source_type'] === 'house_plan'; }));
    
    echo "Regular designs: $regularCount\n";
    echo "House plans: $housePlanCount\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>