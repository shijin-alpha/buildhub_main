<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Creating sample payment history data...\n";
    
    // First, ensure the stage_payment_requests table exists
    $create_table = "
        CREATE TABLE IF NOT EXISTS stage_payment_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT,
            contractor_id INT,
            homeowner_id INT,
            stage_name VARCHAR(100),
            requested_amount DECIMAL(12,2),
            approved_amount DECIMAL(12,2) NULL,
            completion_percentage DECIMAL(5,2),
            work_description TEXT,
            materials_used TEXT,
            labor_count INT,
            work_start_date DATE,
            work_end_date DATE,
            contractor_notes TEXT,
            homeowner_notes TEXT,
            quality_check BOOLEAN DEFAULT FALSE,
            safety_compliance BOOLEAN DEFAULT FALSE,
            status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
            request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            response_date TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    $db->exec($create_table);
    echo "✅ stage_payment_requests table created/verified\n";
    
    // Get some existing projects for sample data
    $projects_query = "SELECT id, user_id, homeowner_id FROM layout_requests WHERE status = 'approved' LIMIT 3";
    $projects_stmt = $db->query($projects_query);
    $projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($projects)) {
        echo "⚠️ No approved projects found. Creating sample project...\n";
        
        // Create a sample project
        $sample_project = "
            INSERT INTO layout_requests (
                user_id, homeowner_id, plot_size, budget_range, requirements,
                preferred_style, status, location, timeline
            ) VALUES (
                1, 1, '30x40', '15-25 lakhs', 'Modern 3BHK house with garden',
                'Modern', 'approved', 'Bangalore', '6-8 months'
            )
        ";
        $db->exec($sample_project);
        $project_id = $db->lastInsertId();
        
        $projects = [[
            'id' => $project_id,
            'user_id' => 1,
            'homeowner_id' => 1
        ]];
        
        echo "✅ Sample project created with ID: $project_id\n";
    }
    
    // Sample payment requests data
    $sample_requests = [
        [
            'stage_name' => 'Foundation',
            'requested_amount' => 300000,
            'approved_amount' => 300000,
            'completion_percentage' => 100,
            'work_description' => 'Foundation work completed including excavation, concrete pouring, and plinth beam construction. All work done as per approved drawings.',
            'materials_used' => 'Cement (50 bags), Steel bars (2 tons), Sand (10 cubic meters), Aggregate (15 cubic meters)',
            'labor_count' => 8,
            'work_start_date' => '2024-01-01',
            'work_end_date' => '2024-01-15',
            'contractor_notes' => 'Foundation work completed successfully. Quality checks passed. Ready for next stage.',
            'homeowner_notes' => 'Excellent work quality. Foundation is strong and well-constructed. Approved full amount.',
            'quality_check' => true,
            'safety_compliance' => true,
            'status' => 'paid',
            'request_date' => '2024-01-16 10:00:00',
            'response_date' => '2024-01-17 14:30:00'
        ],
        [
            'stage_name' => 'Structure',
            'requested_amount' => 450000,
            'approved_amount' => 400000,
            'completion_percentage' => 90,
            'work_description' => 'Structural work including column construction, beam work, and slab casting. Minor finishing work remaining.',
            'materials_used' => 'Cement (80 bags), Steel bars (3.5 tons), Shuttering material, Concrete mix',
            'labor_count' => 12,
            'work_start_date' => '2024-01-16',
            'work_end_date' => '2024-02-05',
            'contractor_notes' => 'Structural work 90% complete. Minor finishing work will be completed in next 2 days.',
            'homeowner_notes' => 'Good progress but some minor issues with beam alignment. Approved 90% of requested amount.',
            'quality_check' => true,
            'safety_compliance' => true,
            'status' => 'approved',
            'request_date' => '2024-02-06 09:15:00',
            'response_date' => '2024-02-07 16:45:00'
        ],
        [
            'stage_name' => 'Brickwork',
            'requested_amount' => 250000,
            'approved_amount' => null,
            'completion_percentage' => 75,
            'work_description' => 'Brickwork for all walls including internal and external walls. Door and window frames installation in progress.',
            'materials_used' => 'Red bricks (5000 pieces), Cement (30 bags), Sand (8 cubic meters), Door frames (5 units)',
            'labor_count' => 10,
            'work_start_date' => '2024-02-06',
            'work_end_date' => '2024-02-25',
            'contractor_notes' => 'Brickwork progressing well. 75% completed. Window frames pending delivery.',
            'homeowner_notes' => null,
            'quality_check' => true,
            'safety_compliance' => true,
            'status' => 'pending',
            'request_date' => '2024-02-26 11:30:00',
            'response_date' => null
        ],
        [
            'stage_name' => 'Electrical',
            'requested_amount' => 180000,
            'approved_amount' => null,
            'completion_percentage' => 40,
            'work_description' => 'Electrical wiring work started. Conduit laying and main panel installation completed. Switch board work in progress.',
            'materials_used' => 'Electrical wires (500 meters), Conduits (200 meters), Main panel (1 unit), Switch boards (8 units)',
            'labor_count' => 4,
            'work_start_date' => '2024-02-20',
            'work_end_date' => '2024-03-10',
            'contractor_notes' => 'Electrical work 40% complete. Need homeowner approval for additional switch points.',
            'homeowner_notes' => 'Work quality is not up to standard. Some wiring is not properly concealed. Please redo the work.',
            'quality_check' => false,
            'safety_compliance' => true,
            'status' => 'rejected',
            'request_date' => '2024-03-01 14:20:00',
            'response_date' => '2024-03-02 10:15:00'
        ]
    ];
    
    // Insert sample payment requests for each project
    foreach ($projects as $project) {
        // Use user_id as contractor_id for this demo
        $contractor_id = $project['user_id'];
        
        foreach ($sample_requests as $request) {
            // Check if request already exists
            $check_stmt = $db->prepare("
                SELECT id FROM stage_payment_requests 
                WHERE project_id = ? AND contractor_id = ? AND stage_name = ?
            ");
            $check_stmt->execute([$project['id'], $contractor_id, $request['stage_name']]);
            
            if ($check_stmt->rowCount() == 0) {
                $insert_stmt = $db->prepare("
                    INSERT INTO stage_payment_requests (
                        project_id, contractor_id, homeowner_id, stage_name, requested_amount, 
                        approved_amount, completion_percentage, work_description, materials_used,
                        labor_count, work_start_date, work_end_date, contractor_notes, 
                        homeowner_notes, quality_check, safety_compliance, status, 
                        request_date, response_date
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $insert_stmt->execute([
                    $project['id'],
                    $contractor_id,
                    $project['homeowner_id'],
                    $request['stage_name'],
                    $request['requested_amount'],
                    $request['approved_amount'],
                    $request['completion_percentage'],
                    $request['work_description'],
                    $request['materials_used'],
                    $request['labor_count'],
                    $request['work_start_date'],
                    $request['work_end_date'],
                    $request['contractor_notes'],
                    $request['homeowner_notes'],
                    $request['quality_check'],
                    $request['safety_compliance'],
                    $request['status'],
                    $request['request_date'],
                    $request['response_date']
                ]);
                
                echo "✅ Sample payment request created: {$request['stage_name']} for project {$project['id']} - Status: {$request['status']}\n";
            } else {
                echo "⚠️ Payment request already exists: {$request['stage_name']} for project {$project['id']}\n";
            }
        }
    }
    
    echo "\n🎉 Sample payment history data creation completed!\n";
    echo "\nSample data includes:\n";
    echo "- Foundation stage (PAID) - ₹3,00,000 - Homeowner approved full amount\n";
    echo "- Structure stage (APPROVED) - ₹4,00,000 - Homeowner approved 90% of requested amount\n";
    echo "- Brickwork stage (PENDING) - ₹2,50,000 - Awaiting homeowner response\n";
    echo "- Electrical stage (REJECTED) - ₹1,80,000 - Homeowner rejected due to quality issues\n";
    echo "\nYou can now test the payment history system in the contractor dashboard.\n";
    
} catch (Exception $e) {
    echo "❌ Error creating sample payment history: " . $e->getMessage() . "\n";
}
?>