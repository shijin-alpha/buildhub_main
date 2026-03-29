<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Setting up stage payment requests system...\n";
    
    // Create project_stage_payment_requests table
    $db->exec("
        CREATE TABLE IF NOT EXISTS project_stage_payment_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            contractor_id INT NOT NULL,
            homeowner_id INT NOT NULL,
            stage_name VARCHAR(100) NOT NULL,
            requested_amount DECIMAL(15,2) NOT NULL,
            completion_percentage DECIMAL(5,2) NOT NULL,
            work_description TEXT NOT NULL,
            materials_used TEXT,
            labor_count INT,
            work_start_date DATE,
            work_end_date DATE,
            contractor_notes TEXT,
            quality_check BOOLEAN DEFAULT FALSE,
            safety_compliance BOOLEAN DEFAULT FALSE,
            
            -- Request status and responses
            status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
            request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            homeowner_response_date TIMESTAMP NULL,
            homeowner_notes TEXT,
            rejection_reason TEXT,
            payment_date TIMESTAMP NULL,
            
            -- Razorpay integration
            razorpay_order_id VARCHAR(255) NULL,
            razorpay_payment_id VARCHAR(255) NULL,
            
            -- Metadata
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Indexes
            INDEX idx_project_id (project_id),
            INDEX idx_contractor_id (contractor_id),
            INDEX idx_homeowner_id (homeowner_id),
            INDEX idx_status (status),
            INDEX idx_stage_name (stage_name),
            INDEX idx_request_date (request_date)
        )
    ");
    echo "✅ project_stage_payment_requests table created/verified\n";
    
    // Create payment notifications table
    $db->exec("
        CREATE TABLE IF NOT EXISTS payment_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            payment_request_id INT NOT NULL,
            recipient_id INT NOT NULL,
            recipient_type ENUM('contractor', 'homeowner') NOT NULL,
            notification_type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            INDEX idx_payment_request_id (payment_request_id),
            INDEX idx_recipient (recipient_id, recipient_type),
            INDEX idx_is_read (is_read),
            INDEX idx_created_at (created_at)
        )
    ");
    echo "✅ payment_notifications table created/verified\n";
    
    // Create stage payment transactions table
    $db->exec("
        CREATE TABLE IF NOT EXISTS stage_payment_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            payment_request_id INT NOT NULL,
            homeowner_id INT NOT NULL,
            contractor_id INT NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'INR',
            razorpay_order_id VARCHAR(255) NULL,
            razorpay_payment_id VARCHAR(255) NULL,
            razorpay_signature VARCHAR(255) NULL,
            payment_status ENUM('created', 'pending', 'completed', 'failed', 'cancelled') DEFAULT 'created',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_payment_request_id (payment_request_id),
            INDEX idx_homeowner_id (homeowner_id),
            INDEX idx_contractor_id (contractor_id),
            INDEX idx_razorpay_order_id (razorpay_order_id),
            INDEX idx_payment_status (payment_status)
        )
    ");
    echo "✅ stage_payment_transactions table created/verified\n";
    
    // Insert sample payment requests for testing
    $sampleRequests = [
        [
            'project_id' => 1,
            'contractor_id' => 29,
            'homeowner_id' => 28,
            'stage_name' => 'Foundation',
            'requested_amount' => 150000,
            'completion_percentage' => 100,
            'work_description' => 'Foundation work completed including excavation, concrete pouring, and curing. All structural requirements met as per approved plans.',
            'materials_used' => 'Concrete (M25 grade), Steel reinforcement bars, Aggregate, Sand, Cement',
            'labor_count' => 8,
            'work_start_date' => date('Y-m-d', strtotime('-15 days')),
            'work_end_date' => date('Y-m-d', strtotime('-5 days')),
            'contractor_notes' => 'Foundation work completed successfully. Ready for next stage construction.',
            'quality_check' => 1,
            'safety_compliance' => 1,
            'status' => 'pending'
        ],
        [
            'project_id' => 1,
            'contractor_id' => 29,
            'homeowner_id' => 28,
            'stage_name' => 'Structure',
            'requested_amount' => 200000,
            'completion_percentage' => 75,
            'work_description' => 'Structural work 75% completed. Columns and beams constructed, slab work in progress.',
            'materials_used' => 'Steel, Concrete, Formwork materials',
            'labor_count' => 12,
            'work_start_date' => date('Y-m-d', strtotime('-10 days')),
            'work_end_date' => null,
            'contractor_notes' => 'Structural work progressing well. Expecting completion in 5 days.',
            'quality_check' => 1,
            'safety_compliance' => 1,
            'status' => 'approved'
        ],
        [
            'project_id' => 2,
            'contractor_id' => 51,
            'homeowner_id' => 48,
            'stage_name' => 'Foundation',
            'requested_amount' => 120000,
            'completion_percentage' => 100,
            'work_description' => 'Foundation work for 3BHK modern house completed. All quality checks passed.',
            'materials_used' => 'Concrete, Steel bars, Aggregate',
            'labor_count' => 6,
            'work_start_date' => date('Y-m-d', strtotime('-12 days')),
            'work_end_date' => date('Y-m-d', strtotime('-3 days')),
            'contractor_notes' => 'Foundation ready for next phase.',
            'quality_check' => 1,
            'safety_compliance' => 1,
            'status' => 'paid',
            'payment_date' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ]
    ];
    
    foreach ($sampleRequests as $request) {
        // Check if request already exists
        $checkStmt = $db->prepare("
            SELECT id FROM project_stage_payment_requests 
            WHERE project_id = ? AND contractor_id = ? AND stage_name = ?
        ");
        $checkStmt->execute([$request['project_id'], $request['contractor_id'], $request['stage_name']]);
        
        if (!$checkStmt->fetch()) {
            $columns = implode(', ', array_keys($request));
            $placeholders = ':' . implode(', :', array_keys($request));
            
            $insertStmt = $db->prepare("
                INSERT INTO project_stage_payment_requests ($columns) 
                VALUES ($placeholders)
            ");
            
            foreach ($request as $key => $value) {
                $insertStmt->bindValue(':' . $key, $value);
            }
            
            $insertStmt->execute();
            echo "✅ Sample payment request created: {$request['stage_name']} for project {$request['project_id']}\n";
        }
    }
    
    echo "\n🎉 Stage payment requests system setup completed!\n";
    echo "\nSample payment requests created for testing:\n";
    echo "- Foundation stage (pending) - ₹1,50,000\n";
    echo "- Structure stage (approved) - ₹2,00,000\n";
    echo "- Foundation stage for project 2 (paid) - ₹1,20,000\n";
    echo "\nYou can now test the payment request system in the homeowner dashboard.\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up stage payment requests: " . $e->getMessage() . "\n";
}
?>