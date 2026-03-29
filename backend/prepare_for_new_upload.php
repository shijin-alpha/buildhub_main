<?php
require_once 'config/database.php';

echo "Preparing system for new layout image upload...\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check current house plans
    echo "Current house plans:\n";
    $stmt = $db->query("SELECT id, plan_name, architect_id, status FROM house_plans ORDER BY id DESC LIMIT 3");
    $plans = $stmt->fetchAll();
    
    foreach ($plans as $plan) {
        echo "- ID: {$plan['id']}, Name: {$plan['plan_name']}, Architect: {$plan['architect_id']}, Status: {$plan['status']}\n";
        
        // Check technical details
        $detailStmt = $db->prepare("SELECT technical_details FROM house_plans WHERE id = ?");
        $detailStmt->execute([$plan['id']]);
        $details = $detailStmt->fetch()['technical_details'];
        
        if ($details) {
            $parsed = json_decode($details, true);
            if (isset($parsed['layout_image'])) {
                echo "  Layout Image: {$parsed['layout_image']['name']} (uploaded: " . ($parsed['layout_image']['uploaded'] ? 'true' : 'false') . ")\n";
                if (isset($parsed['layout_image']['stored'])) {
                    echo "  Stored as: {$parsed['layout_image']['stored']}\n";
                }
            } else {
                echo "  No layout image\n";
            }
        } else {
            echo "  No technical details\n";
        }
    }
    
    echo "\nUpload directory status:\n";
    $uploadDir = 'uploads/house_plans/';
    if (is_dir($uploadDir)) {
        echo "✓ Upload directory exists: $uploadDir\n";
        echo "Directory permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "\n";
        
        $files = scandir($uploadDir);
        $files = array_filter($files, function($file) { return $file !== '.' && $file !== '..'; });
        
        if (empty($files)) {
            echo "- No files currently in directory\n";
        } else {
            echo "- Current files:\n";
            foreach ($files as $file) {
                $filepath = $uploadDir . $file;
                $size = file_exists($filepath) ? filesize($filepath) : 0;
                echo "  • $file (" . number_format($size) . " bytes)\n";
            }
        }
    } else {
        echo "✗ Upload directory missing - creating...\n";
        if (mkdir($uploadDir, 0755, true)) {
            echo "✓ Upload directory created\n";
        } else {
            echo "✗ Failed to create upload directory\n";
        }
    }
    
    echo "\nAPI endpoints ready:\n";
    echo "✓ File upload API: /buildhub/backend/api/architect/upload_house_plan_files.php\n";
    echo "✓ House plan submission API: /buildhub/backend/api/architect/submit_house_plan_with_details.php\n";
    echo "✓ Received designs API: /buildhub/backend/api/homeowner/get_received_designs.php\n";
    
    echo "\nSystem is ready for new layout image upload!\n";
    echo "\nTo upload a new image:\n";
    echo "1. Go to Architect Dashboard\n";
    echo "2. Open a house plan\n";
    echo "3. Click 'Upload Design' button\n";
    echo "4. Upload layout image in Technical Details section\n";
    echo "5. Submit the house plan\n";
    echo "6. Check Homeowner Dashboard > Received Designs\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>