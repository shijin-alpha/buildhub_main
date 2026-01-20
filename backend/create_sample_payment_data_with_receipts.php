<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Creating sample payment data with receipts...\n";
    
    // First, let's check if we have any projects
    $projectQuery = "SELECT id, homeowner_id FROM layout_requests LIMIT 1";
    $projectStmt = $db->query($projectQuery);
    $project = $projectStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo "❌ No projects found. Creating a sample project first...\n";
        
        // Create a sample user (homeowner)
        $userInsert = "INSERT INTO users (first_name, last_name, email, password, role) 
                      VALUES ('John', 'Doe', 'john.doe@example.com', 'password123', 'homeowner')";
        $db->exec($userInsert);
        $homeowner_id = $db->lastInsertId();
        
        // Create a sample contractor
        $contractorInsert = "INSERT INTO users (first_name, last_name, email, password, role) 
                            VALUES ('Mike', 'Builder', 'mike.builder@example.com', 'password123', 'contractor')";
        $db->exec($contractorInsert);
        $contractor_id = $db->lastInsertId();
        
        // Create a sample project
        $projectInsert = "INSERT INTO layout_requests (user_id, project_name, location, budget, status) 
                         VALUES (:homeowner_id, 'Modern Villa Construction', 'Kochi, Kerala', 2500000, 'approved')";
        $projectStmt = $db->prepare($projectInsert);
        $projectStmt->execute([':homeowner_id' => $homeowner_id]);
        $project_id = $db->lastInsertId();
        
        echo "✅ Created sample project with ID: $project_id\n";
    } else {
        $project_id = $project['id'];
        $homeowner_id = $project['homeowner_id'];
        
        // Get a contractor
        $contractorQuery = "SELECT id FROM users WHERE role = 'contractor' LIMIT 1";
        $contractorStmt = $db->query($contractorQuery);
        $contractor = $contractorStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$contractor) {
            $contractorInsert = "INSERT INTO users (first_name, last_name, email, password, role) 
                                VALUES ('Mike', 'Builder', 'mike.builder@example.com', 'password123', 'contractor')";
            $db->exec($contractorInsert);
            $contractor_id = $db->lastInsertId();
        } else {
            $contractor_id = $contractor['id'];
        }
        
        echo "✅ Using existing project with ID: $project_id\n";
    }
    
    // Clear existing payment requests for this project
    $deleteQuery = "DELETE FROM stage_payment_requests WHERE project_id = :project_id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->execute([':project_id' => $project_id]);
    
    // Sample receipt file data (JSON format)
    $sampleReceiptFiles = [
        [
            [
                "original_name" => "bank_transfer_receipt.jpg",
                "stored_name" => "receipt_1704067200_0.jpg",
                "file_path" => "uploads/payment_receipts/1/receipt_1704067200_0.jpg",
                "file_size" => 2400000,
                "file_type" => "image/jpeg"
            ],
            [
                "original_name" => "payment_confirmation.pdf",
                "stored_name" => "receipt_1704067200_1.pdf",
                "file_path" => "uploads/payment_receipts/1/receipt_1704067200_1.pdf",
                "file_size" => 850000,
                "file_type" => "application/pdf"
            ]
        ],
        [
            [
                "original_name" => "upi_payment_screenshot.png",
                "stored_name" => "receipt_1704153600_0.png",
                "file_path" => "uploads/payment_receipts/2/receipt_1704153600_0.png",
                "file_size" => 1200000,
                "file_type" => "image/png"
            ]
        ],
        [
            [
                "original_name" => "cheque_copy.jpg",
                "stored_name" => "receipt_1704240000_0.jpg",
                "file_path" => "uploads/payment_receipts/3/receipt_1704240000_0.jpg",
                "file_size" => 1800000,
                "file_type" => "image/jpeg"
            ]
        ]
    ];
    
    // Create sample payment requests with different statuses and receipt data
    $paymentRequests = [
        [
            'stage_name' => 'Foundation',
            'requested_amount' => 50000,
            'approved_amount' => 50000,
            'completion_percentage' => 100,
            'work_description' => 'Foundation work including excavation, concrete pouring, and structural setup. Quality checks completed and safety compliance verified.',
            'materials_used' => 'Concrete, Steel bars, Sand, Cement',
            'labor_count' => 8,
            'work_start_date' => '2024-01-01',
            'work_end_date' => '2024-01-15',
            'contractor_notes' => 'Foundation work completed as per specifications. All quality checks passed.',
            'homeowner_notes' => 'Excellent work quality. Payment approved.',
            'quality_check' => 1,
            'safety_compliance' => 1,
            'status' => 'paid',
            'request_date' => '2024-01-16 10:30:00',
            'response_date' => '2024-01-17 14:20:00',
            'transaction_reference' => 'NEFT240117001234',
            'payment_date' => '2024-01-17',
            'payment_method' => 'bank_transfer',
            'receipt_file_path' => json_encode($sampleReceiptFiles[0]),
            'verification_status' => 'verified',
            'verified_by' => $contractor_id,
            'verified_at' => '2024-01-18 09:15:00',
            'verification_notes' => 'Payment receipt verified. Bank transfer confirmed.'
        ],
        [
            'stage_name' => 'Structure',
            'requested_amount' => 75000,
            'approved_amount' => 70000,
            'completion_percentage' => 85,
            'work_description' => 'Structural work including column construction, beam installation, and slab casting. Work is 85% complete.',
            'materials_used' => 'Steel, Concrete, Formwork materials',
            'labor_count' => 12,
            'work_start_date' => '2024-01-16',
            'work_end_date' => '2024-02-05',
            'contractor_notes' => 'Structural work progressing well. 85% completion achieved.',
            'homeowner_notes' => 'Good progress. Approved with slight amount adjustment due to material cost savings.',
            'quality_check' => 1,
            'safety_compliance' => 1,
            'status' => 'approved',
            'request_date' => '2024-02-06 11:45:00',
            'response_date' => '2024-02-07 16:30:00',
            'transaction_reference' => 'UPI240208567890',
            'payment_date' => '2024-02-08',
            'payment_method' => 'upi',
            'receipt_file_path' => json_encode($sampleReceiptFiles[1]),
            'verification_status' => 'pending',
            'verified_by' => null,
            'verified_at' => null,
            'verification_notes' => null
        ],
        [
            'stage_name' => 'Roofing',
            'requested_amount' => 60000,
            'approved_amount' => null,
            'completion_percentage' => 60,
            'work_description' => 'Roofing work including tile installation and waterproofing. Work is 60% complete.',
            'materials_used' => 'Roof tiles, Waterproofing materials, Support structures',
            'labor_count' => 6,
            'work_start_date' => '2024-02-06',
            'work_end_date' => '2024-02-20',
            'contractor_notes' => 'Roofing work in progress. Weather conditions have been favorable.',
            'homeowner_notes' => null,
            'quality_check' => 1,
            'safety_compliance' => 1,
            'status' => 'pending',
            'request_date' => '2024-02-21 09:15:00',
            'response_date' => null,
            'transaction_reference' => null,
            'payment_date' => null,
            'payment_method' => null,
            'receipt_file_path' => null,
            'verification_status' => null,
            'verified_by' => null,
            'verified_at' => null,
            'verification_notes' => null
        ],
        [
            'stage_name' => 'Electrical',
            'requested_amount' => 40000,
            'approved_amount' => null,
            'completion_percentage' => 30,
            'work_description' => 'Electrical wiring and fixture installation. Initial wiring completed.',
            'materials_used' => 'Electrical wires, Switches, Sockets, Circuit breakers',
            'labor_count' => 4,
            'work_start_date' => '2024-02-15',
            'work_end_date' => '2024-03-01',
            'contractor_notes' => 'Electrical work started. Basic wiring layout completed.',
            'homeowner_notes' => 'Work quality is not up to standard. Please redo the wiring in the kitchen area.',
            'quality_check' => 0,
            'safety_compliance' => 1,
            'status' => 'rejected',
            'request_date' => '2024-03-02 14:20:00',
            'response_date' => '2024-03-03 10:45:00',
            'transaction_reference' => null,
            'payment_date' => null,
            'payment_method' => null,
            'receipt_file_path' => null,
            'verification_status' => null,
            'verified_by' => null,
            'verified_at' => null,
            'verification_notes' => null
        ],
        [
            'stage_name' => 'Plumbing',
            'requested_amount' => 35000,
            'approved_amount' => 35000,
            'completion_percentage' => 100,
            'work_description' => 'Complete plumbing installation including water supply and drainage systems.',
            'materials_used' => 'PVC pipes, Fittings, Taps, Bathroom fixtures',
            'labor_count' => 5,
            'work_start_date' => '2024-02-20',
            'work_end_date' => '2024-03-05',
            'contractor_notes' => 'Plumbing work completed successfully. All connections tested and working.',
            'homeowner_notes' => 'Excellent plumbing work. All fixtures working perfectly.',
            'quality_check' => 1,
            'safety_compliance' => 1,
            'status' => 'approved',
            'request_date' => '2024-03-06 13:30:00',
            'response_date' => '2024-03-07 11:15:00',
            'transaction_reference' => 'CHQ240310123456',
            'payment_date' => '2024-03-10',
            'payment_method' => 'cheque',
            'receipt_file_path' => json_encode($sampleReceiptFiles[2]),
            'verification_status' => 'verified',
            'verified_by' => $contractor_id,
            'verified_at' => '2024-03-11 08:30:00',
            'verification_notes' => 'Cheque cleared successfully. Payment verified.'
        ]
    ];
    
    // Insert payment requests
    $insertQuery = "
        INSERT INTO stage_payment_requests (
            project_id, contractor_id, homeowner_id, stage_name, requested_amount, approved_amount,
            completion_percentage, work_description, materials_used, labor_count, work_start_date,
            work_end_date, contractor_notes, homeowner_notes, quality_check, safety_compliance,
            status, request_date, response_date, transaction_reference, payment_date, payment_method,
            receipt_file_path, verification_status, verified_by, verified_at, verification_notes
        ) VALUES (
            :project_id, :contractor_id, :homeowner_id, :stage_name, :requested_amount, :approved_amount,
            :completion_percentage, :work_description, :materials_used, :labor_count, :work_start_date,
            :work_end_date, :contractor_notes, :homeowner_notes, :quality_check, :safety_compliance,
            :status, :request_date, :response_date, :transaction_reference, :payment_date, :payment_method,
            :receipt_file_path, :verification_status, :verified_by, :verified_at, :verification_notes
        )
    ";
    
    $insertStmt = $db->prepare($insertQuery);
    
    foreach ($paymentRequests as $index => $request) {
        $request['project_id'] = $project_id;
        $request['contractor_id'] = $contractor_id;
        $request['homeowner_id'] = $homeowner_id;
        
        $insertStmt->execute($request);
        echo "✅ Created payment request " . ($index + 1) . ": {$request['stage_name']} - {$request['status']}\n";
    }
    
    echo "\n🎉 Successfully created sample payment data with receipts!\n";
    echo "\nSample data includes:\n";
    echo "- Foundation: PAID with bank transfer receipt (verified)\n";
    echo "- Structure: APPROVED with UPI receipt (pending verification)\n";
    echo "- Roofing: PENDING (no receipt yet)\n";
    echo "- Electrical: REJECTED (no receipt)\n";
    echo "- Plumbing: APPROVED with cheque receipt (verified)\n";
    
    echo "\nProject ID: $project_id\n";
    echo "Contractor ID: $contractor_id\n";
    echo "Homeowner ID: $homeowner_id\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>