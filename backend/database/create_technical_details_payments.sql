-- Create table for tracking technical details payments
CREATE TABLE IF NOT EXISTS technical_details_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_plan_id INT NOT NULL,
    homeowner_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    razorpay_order_id VARCHAR(255),
    razorpay_payment_id VARCHAR(255),
    razorpay_signature VARCHAR(255),
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (house_plan_id) REFERENCES house_plans(id) ON DELETE CASCADE,
    FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_payment (house_plan_id, homeowner_id),
    INDEX idx_homeowner_payments (homeowner_id),
    INDEX idx_house_plan_payments (house_plan_id),
    INDEX idx_payment_status (payment_status)
);

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_technical_payments_created ON technical_details_payments(created_at);
CREATE INDEX IF NOT EXISTS idx_technical_payments_amount ON technical_details_payments(amount);