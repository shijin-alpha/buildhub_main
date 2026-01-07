<?php
header('Content-Type: application/json');
// Support credentialed requests so session cookies are sent
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    session_start();
    $user_id = $_SESSION['user_id'] ?? null; // homeowner id

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    // Ensure designs table exists to avoid errors on fresh DBs
    $db->exec("CREATE TABLE IF NOT EXISTS designs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        layout_request_id INT NULL,
        homeowner_id INT NULL,
        architect_id INT NOT NULL,
        design_title VARCHAR(255) NOT NULL,
        description TEXT,
        design_files TEXT,
        technical_details TEXT,
        status ENUM('proposed','shortlisted','finalized') DEFAULT 'proposed',
        batch_id VARCHAR(64) NULL,
        view_price DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Add technical_details column if it doesn't exist (for existing tables)
    $db->exec("ALTER TABLE designs ADD COLUMN IF NOT EXISTS technical_details TEXT");
    // Add view_price column if it doesn't exist (for existing tables)
    $db->exec("ALTER TABLE designs ADD COLUMN IF NOT EXISTS view_price DECIMAL(10,2) DEFAULT 0.00");

    // First, get traditional designs from the designs table
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

    // Second, get house plans with technical details
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
                     WHERE lr.user_id = :homeowner_id 
                       AND hp.status IN ('submitted', 'approved', 'rejected')
                       AND hp.technical_details IS NOT NULL 
                       AND hp.technical_details != ''
                     ORDER BY hp.updated_at DESC";

    $housePlanStmt = $db->prepare($housePlanSql);
    $housePlanStmt->bindParam(':homeowner_id', $user_id, PDO::PARAM_INT);
    $housePlanStmt->bindParam(':homeowner_id_payment', $user_id, PDO::PARAM_INT);
    $housePlanStmt->execute();

    $housePlanRows = $housePlanStmt->fetchAll(PDO::FETCH_ASSOC);

    // Combine both types
    $allRows = array_merge($designRows, $housePlanRows);
    
    // Sort by creation date (most recent first)
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
            
            // Create files array from technical details if layout image exists
            $files = [];
            if (!empty($technical_details['layout_image'])) {
                $layoutImage = $technical_details['layout_image'];
                // Only include files that were actually uploaded
                if (is_array($layoutImage) && !empty($layoutImage['name']) && 
                    (!isset($layoutImage['uploaded']) || $layoutImage['uploaded'] === true)) {
                    
                    // Use stored filename if available, otherwise fall back to original name
                    $storedName = $layoutImage['stored'] ?? $layoutImage['name'];
                    
                    $files[] = [
                        'original' => $layoutImage['name'],
                        'stored' => $storedName,
                        'ext' => strtolower(pathinfo($storedName, PATHINFO_EXTENSION)),
                        'path' => '/buildhub/backend/uploads/house_plans/' . $storedName,
                        'type' => 'layout_image'
                    ];
                }
            }
            
            // Add other technical detail files
            $fileTypes = ['elevation_images', 'section_drawings', 'renders_3d'];
            foreach ($fileTypes as $fileType) {
                if (!empty($technical_details[$fileType]) && is_array($technical_details[$fileType])) {
                    foreach ($technical_details[$fileType] as $file) {
                        // Only include files that were actually uploaded
                        if (is_array($file) && !empty($file['name']) && 
                            (!isset($file['uploaded']) || $file['uploaded'] === true)) {
                            
                            // Use stored filename if available, otherwise fall back to original name
                            $storedName = $file['stored'] ?? $file['name'];
                            
                            $files[] = [
                                'original' => $file['name'],
                                'stored' => $storedName,
                                'ext' => strtolower(pathinfo($storedName, PATHINFO_EXTENSION)),
                                'path' => '/buildhub/backend/uploads/house_plans/' . $storedName,
                                'type' => $fileType
                            ];
                        }
                    }
                }
            }

            $designs[] = [
                'id' => 'hp_' . $row['id'], // Prefix to distinguish from regular designs
                'house_plan_id' => (int)$row['id'],
                'layout_request_id' => $row['layout_request_id'] ? (int)$row['layout_request_id'] : null,
                'selected_layout_id' => isset($row['selected_layout_id']) && $row['selected_layout_id'] !== null ? (int)$row['selected_layout_id'] : null,
                'homeowner_id' => null, // House plans don't have direct homeowner_id
                'architect_id' => (int)$row['architect_id'],
                'design_title' => $row['plan_name'] . ' (House Plan)',
                'description' => $row['notes'] ?: 'House plan with technical specifications',
                'files' => $files,
                'layout_json' => null,
                'technical_details' => $technical_details,
                'plan_data' => $plan_data,
                'status' => 'proposed', // Map house plan status to design status
                'batch_id' => null,
                'view_price' => (float)($row['unlock_price'] ?? 8000.00), // Use unlock_price for house plans
                'source_type' => 'house_plan',
                'house_plan_status' => $row['status'],
                'plot_dimensions' => $row['plot_width'] . ' × ' . $row['plot_height'],
                'total_area' => (float)$row['total_area'],
                'unlock_price' => (float)($row['unlock_price'] ?? 8000.00),
                'is_technical_details_unlocked' => ($row['payment_status'] === 'completed'),
                'payment_status' => $row['payment_status'],
                'paid_amount' => $row['paid_amount'] ? (float)$row['paid_amount'] : null,
                'architect' => [
                    'name' => trim(($row['architect_first_name'] ?? '') . ' ' . ($row['architect_last_name'] ?? '')),
                    'email' => $row['architect_email'] ?? null
                ],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        } else {
            // Handle regular design data (existing logic)
            $files = json_decode($row['design_files'], true);
            if (!is_array($files)) {
                // Backward-compat: comma-separated list
                $files = array_filter(array_map('trim', explode(',', (string)$row['design_files'])));
                $files = array_map(function($name){
                    return [
                        'original' => $name,
                        'stored' => $name,
                        'ext' => strtolower(pathinfo($name, PATHINFO_EXTENSION)),
                        'path' => '/buildhub/backend/uploads/designs/' . $name,
                        'type' => 'design_file'
                    ];
                }, $files);
            }

            // Parse technical details if present
            $technical_details = null;
            if (!empty($row['technical_details'])) {
                try {
                    $technical_details = json_decode($row['technical_details'], true);
                } catch (Exception $e) {
                    // If JSON parsing fails, keep as null
                    $technical_details = null;
                }
            }

            $designs[] = [
                'id' => (int)$row['id'],
                'layout_request_id' => $row['layout_request_id'] ? (int)$row['layout_request_id'] : null,
                'selected_layout_id' => isset($row['selected_layout_id']) && $row['selected_layout_id'] !== null ? (int)$row['selected_layout_id'] : null,
                'homeowner_id' => $row['homeowner_id'] ? (int)$row['homeowner_id'] : null,
                'architect_id' => (int)$row['architect_id'],
                'design_title' => $row['design_title'],
                'description' => $row['description'],
                'files' => $files,
                'layout_json' => $row['layout_json'] ?? null,
                'technical_details' => $technical_details,
                'status' => $row['status'],
                'batch_id' => $row['batch_id'],
                'view_price' => isset($row['view_price']) ? (float)$row['view_price'] : 0.00,
                'source_type' => 'design',
                'architect' => [
                    'name' => trim(($row['architect_first_name'] ?? '') . ' ' . ($row['architect_last_name'] ?? '')),
                    'email' => $row['architect_email'] ?? null
                ],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }
    }

    echo json_encode(['success' => true, 'designs' => $designs]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching designs: ' . $e->getMessage()]);
}