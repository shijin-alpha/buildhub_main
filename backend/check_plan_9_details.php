<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare('SELECT technical_details FROM house_plans WHERE id = 9');
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        $details = json_decode($result['technical_details'], true);
        echo "Technical Details for Plan ID 9:\n";
        echo "================================\n";
        
        if (isset($details['layout_image'])) {
            echo "Layout Image Data:\n";
            print_r($details['layout_image']);
        } else {
            echo "No layout_image found in technical_details\n";
        }
        
        echo "\nAll technical details keys:\n";
        print_r(array_keys($details));
        
    } else {
        echo "No plan found with ID 9\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>