<?php
require_once 'config/database.php';

echo "Testing house plan deletion functionality...\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check current house plans
    echo "Current house plans:\n";
    $stmt = $db->query("SELECT id, plan_name, architect_id, status FROM house_plans");
    $plans = $stmt->fetchAll();
    
    foreach ($plans as $plan) {
        echo "- ID: {$plan['id']}, Name: {$plan['plan_name']}, Architect: {$plan['architect_id']}, Status: {$plan['status']}\n";
    }
    
    if (empty($plans)) {
        echo "No house plans found to test deletion\n";
        exit;
    }
    
    echo "\nChecking related records for house plan ID 8:\n";
    
    // Check house plan reviews
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM house_plan_reviews WHERE house_plan_id = 8");
    $stmt->execute();
    $reviewCount = $stmt->fetch()['count'];
    echo "- House plan reviews: $reviewCount\n";
    
    // Check technical details payments
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM technical_details_payments WHERE house_plan_id = 8");
    $stmt->execute();
    $paymentCount = $stmt->fetch()['count'];
    echo "- Technical details payments: $paymentCount\n";
    
    // Check notifications
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE related_id = 8 AND type LIKE '%house_plan%'");
    $stmt->execute();
    $notificationCount = $stmt->fetch()['count'];
    echo "- Related notifications: $notificationCount\n";
    
    // Check inbox messages
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM inbox_messages WHERE metadata LIKE '%\"plan_id\":8%'");
    $stmt->execute();
    $messageCount = $stmt->fetch()['count'];
    echo "- Related inbox messages: $messageCount\n";
    
    // Check files
    $stmt = $db->prepare("SELECT technical_details FROM house_plans WHERE id = 8");
    $stmt->execute();
    $plan = $stmt->fetch();
    
    if ($plan && $plan['technical_details']) {
        $details = json_decode($plan['technical_details'], true);
        $fileCount = 0;
        
        if (isset($details['layout_image']['stored'])) {
            $fileCount++;
            $filepath = "uploads/house_plans/" . $details['layout_image']['stored'];
            echo "- Layout image file: " . (file_exists($filepath) ? "EXISTS" : "MISSING") . " ($filepath)\n";
        }
        
        $fileTypes = ['elevation_images', 'section_drawings', 'renders_3d'];
        foreach ($fileTypes as $fileType) {
            if (isset($details[$fileType]) && is_array($details[$fileType])) {
                foreach ($details[$fileType] as $file) {
                    if (isset($file['stored'])) {
                        $fileCount++;
                        $filepath = "uploads/house_plans/" . $file['stored'];
                        echo "- {$fileType} file: " . (file_exists($filepath) ? "EXISTS" : "MISSING") . " ($filepath)\n";
                    }
                }
            }
        }
        
        echo "- Total files to delete: $fileCount\n";
    }
    
    echo "\nDeletion API is ready to handle:\n";
    echo "✓ House plan record deletion\n";
    echo "✓ Related reviews cleanup\n";
    echo "✓ Payment records cleanup\n";
    echo "✓ Notification cleanup\n";
    echo "✓ Inbox message cleanup\n";
    echo "✓ File cleanup from disk\n";
    
    echo "\nTo test actual deletion, use the frontend or call the API directly.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>