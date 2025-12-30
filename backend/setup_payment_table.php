<?php
// Setup payment table

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔧 Setting up payment table...\n\n";
    
    // Create layout_payments table
    $sql = "CREATE TABLE IF NOT EXISTS layout_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        homeowner_id INT NOT NULL,
        architect_id INT NOT NULL,
        design_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'INR',
        payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
        razorpay_order_id VARCHAR(255),
        razorpay_payment_id VARCHAR(255),
        razorpay_signature VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (architect_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (design_id) REFERENCES designs(id) ON DELETE CASCADE
    )";
    
    $db->exec($sql);
    echo "✅ Created layout_payments table\n";
    
    // Create indexes
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_layout_payments_homeowner ON layout_payments(homeowner_id)",
        "CREATE INDEX IF NOT EXISTS idx_layout_payments_design ON layout_payments(design_id)",
        "CREATE INDEX IF NOT EXISTS idx_layout_payments_status ON layout_payments(payment_status)"
    ];
    
    foreach ($indexes as $index) {
        $db->exec($index);
    }
    echo "✅ Created payment table indexes\n";
    
    echo "\n🎉 Payment table setup completed successfully!\n\n";
    
    echo "📋 Summary:\n";
    echo "   ✅ layout_payments - Track layout view payments\n";
    echo "   ✅ Foreign key relationships established\n";
    echo "   ✅ Database indexes created for performance\n\n";
    
    echo "💳 Ready to process payments!\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up payment table: " . $e->getMessage() . "\n";
}
?>