<?php
require_once 'config/database.php';

echo "=== Simple Upload Check ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check the house plan we just created
    $stmt = $db->prepare('SELECT * FROM house_plans WHERE id = 10');
    $stmt->execute();
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($plan) {
        echo "House Plan Found:\n";
        echo "- ID: " . $plan['id'] . "\n";
        echo "- Name: " . $plan['plan_name'] . "\n";
        echo "- Status: " . $plan['status'] . "\n";
        echo "- Unlock Price: " . $plan['unlock_price'] . "\n";
        
        if ($plan['technical_details']) {
            $details = json_decode($plan['technical_details'], true);
            echo "\nTechnical Details:\n";
            
            if (isset($details['layout_image'])) {
                echo "Layout Image:\n";
                echo "- Name: " . ($details['layout_image']['name'] ?? 'N/A') . "\n";
                echo "- Stored: " . ($details['layout_image']['stored'] ?? 'N/A') . "\n";
                echo "- Uploaded: " . ($details['layout_image']['uploaded'] ? 'Yes' : 'No') . "\n";
                echo "- Pending: " . ($details['layout_image']['pending_upload'] ? 'Yes' : 'No') . "\n";
                
                // Check if file exists
                $storedFile = $details['layout_image']['stored'] ?? '';
                if ($storedFile) {
                    $filePath = 'uploads/house_plans/' . $storedFile;
                    if (file_exists($filePath)) {
                        echo "- File exists: Yes (" . filesize($filePath) . " bytes)\n";
                    } else {
                        echo "- File exists: No (expected at: $filePath)\n";
                    }
                }
            } else {
                echo "No layout image found in technical details\n";
            }
        } else {
            echo "No technical details found\n";
        }
        
        // Test the API query logic
        echo "\n=== Testing API Query Logic ===\n";
        
        $housePlanSql = "SELECT 
                            hp.*,
                            a.first_name AS architect_first_name, 
                            a.last_name AS architect_last_name, 
                            a.email AS architect_email,
                            lr.selected_layout_id AS selected_layout_id,
                            tdp.payment_status,
                            tdp.amount as paid_amount,
                            'house_plan' as source_type
                         FROM house_plans hp
                         INNER JOIN layout_requests lr ON hp.layout_request_id = lr.id
                         INNER JOIN users a ON hp.architect_id = a.id
                         LEFT JOIN technical_details_payments tdp ON hp.id = tdp.house_plan_id AND tdp.homeowner_id = :homeowner_id_payment
                         WHERE lr.homeowner_id = :homeowner_id 
                           AND hp.status IN ('submitted', 'approved', 'rejected')
                           AND hp.technical_details IS NOT NULL 
                           AND hp.technical_details != ''
                         ORDER BY hp.updated_at DESC";

        $housePlanStmt = $db->prepare($housePlanSql);
        $homeowner_id = 19;
        $homeowner_id_payment = 19;
        $housePlanStmt->bindParam(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
        $housePlanStmt->bindParam(':homeowner_id_payment', $homeowner_id_payment, PDO::PARAM_INT);
        $housePlanStmt->execute();

        $housePlanRows = $housePlanStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Found " . count($housePlanRows) . " house plans for homeowner 19\n";
        
        foreach ($housePlanRows as $row) {
            echo "\nHouse Plan API Result:\n";
            echo "- ID: " . $row['id'] . "\n";
            echo "- Name: " . $row['plan_name'] . "\n";
            echo "- Status: " . $row['status'] . "\n";
            
            $technical_details = json_decode($row['technical_details'], true) ?? [];
            
            if (!empty($technical_details['layout_image'])) {
                $layoutImage = $technical_details['layout_image'];
                echo "- Layout Image Found: Yes\n";
                echo "  - Name: " . ($layoutImage['name'] ?? 'N/A') . "\n";
                echo "  - Stored: " . ($layoutImage['stored'] ?? 'N/A') . "\n";
                echo "  - Uploaded: " . ($layoutImage['uploaded'] ? 'Yes' : 'No') . "\n";
                
                // Simulate file path creation
                $storedName = $layoutImage['stored'] ?? $layoutImage['name'];
                $filePath = '/buildhub/backend/uploads/house_plans/' . $storedName;
                echo "  - Expected Path: $filePath\n";
                
                // Check actual file
                $actualPath = 'uploads/house_plans/' . $storedName;
                echo "  - File Exists: " . (file_exists($actualPath) ? 'Yes' : 'No') . "\n";
            } else {
                echo "- Layout Image Found: No\n";
            }
        }
        
    } else {
        echo "No house plan found with ID 10\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>