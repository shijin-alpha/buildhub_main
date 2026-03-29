<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $user_id = 28; // SHIJIN THOMAS MCA2024-2026 homeowner ID
    
    echo "Testing received designs API fix...\n\n";
    
    // Get house plans with technical details
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
    
    foreach ($housePlanRows as $row) {
        echo "House Plan: {$row['plan_name']}\n";
        
        $technical_details = json_decode($row['technical_details'], true) ?? [];
        
        // Test the filtering logic
        $files = [];
        if (!empty($technical_details['layout_image'])) {
            $layoutImage = $technical_details['layout_image'];
            echo "Layout Image Data: " . json_encode($layoutImage) . "\n";
            
            // Apply the new filtering logic
            if (is_array($layoutImage) && !empty($layoutImage['name']) && 
                (!isset($layoutImage['uploaded']) || $layoutImage['uploaded'] === true)) {
                $files[] = [
                    'original' => $layoutImage['name'],
                    'type' => 'layout_image'
                ];
                echo "✓ Would include layout image: {$layoutImage['name']}\n";
            } else {
                echo "✗ Filtered out layout image (uploaded: " . ($layoutImage['uploaded'] ?? 'not set') . ")\n";
            }
        }
        
        echo "Final files count: " . count($files) . "\n";
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>