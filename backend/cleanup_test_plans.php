<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Delete test house plans
    $stmt = $db->prepare('DELETE FROM house_plans WHERE id IN (3,4,5,6,9)');
    $stmt->execute();
    
    echo "Cleaned up test house plans\n";
    
    // Clean up uploaded files
    $uploadDir = 'uploads/house_plans/';
    if (is_dir($uploadDir)) {
        $files = glob($uploadDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                echo "Deleted file: " . basename($file) . "\n";
            }
        }
    }
    
    echo "System ready for new test\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>