<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>🧪 Creating Test Contractor Stage Documents</h2>";
    
    // First, ensure we have the contractor_stage_documents table
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS `contractor_stage_documents` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `project_id` int(11) NOT NULL,
      `contractor_id` int(11) NOT NULL,
      `stage_name` varchar(100) NOT NULL,
      `document_type` enum('receipt', 'bill', 'invoice', 'material_certificate', 'quality_report', 'safety_certificate', 'permit', 'inspection_report', 'other') NOT NULL,
      `document_category` enum('stage_specific', 'project_wide') DEFAULT 'stage_specific',
      `file_path` varchar(500) NOT NULL,
      `original_filename` varchar(255) NOT NULL,
      `file_size` int(11) NOT NULL,
      `mime_type` varchar(100) NOT NULL,
      `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
      `uploaded_by` int(11) NOT NULL,
      `description` text DEFAULT NULL,
      `verification_status` enum('pending', 'approved', 'rejected') DEFAULT 'pending',
      `verified_by` int(11) DEFAULT NULL,
      `verified_at` timestamp NULL DEFAULT NULL,
      `verification_notes` text DEFAULT NULL,
      `is_mandatory` tinyint(1) DEFAULT 0,
      `related_payment_id` int(11) DEFAULT NULL,
      `metadata` json DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `idx_project_contractor` (`project_id`, `contractor_id`),
      KEY `idx_stage_name` (`stage_name`),
      KEY `idx_document_type` (`document_type`),
      KEY `idx_verification_status` (`verification_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $db->exec($create_table_sql);
    echo "<p>✅ Contractor stage documents table created/verified</p>";
    
    // Check if homeowner 32 exists and get their project
    $stmt = $db->prepare("
        SELECT cp.id as project_id, cp.project_name, cp.contractor_id, cp.homeowner_id
        FROM construction_projects cp 
        WHERE cp.homeowner_id = 32 
        LIMIT 1
    ");
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        // Create a test project for homeowner 32
        $stmt = $db->prepare("
            INSERT INTO construction_projects (
                project_name, homeowner_id, contractor_id, project_location, 
                total_cost, start_date, estimated_completion_date, status, created_at
            ) VALUES (
                'Test Construction Project', 32, 2, 'Test Location', 
                500000, '2024-01-01', '2024-12-31', 'in_progress', NOW()
            )
        ");
        $stmt->execute();
        $project_id = $db->lastInsertId();
        
        $project = [
            'project_id' => $project_id,
            'project_name' => 'Test Construction Project',
            'contractor_id' => 2,
            'homeowner_id' => 32
        ];
        
        echo "<p>✅ Test project created with ID: {$project_id}</p>";
    } else {
        echo "<p>✅ Using existing project: {$project['project_name']} (ID: {$project['project_id']})</p>";
    }
    
    // Create upload directories
    $upload_base = 'uploads/stage_documents/' . $project['project_id'];
    $stages = ['Foundation', 'Structure', 'Brickwork', 'Electrical'];
    $doc_types = ['receipt', 'bill', 'material_certificate'];
    
    foreach ($stages as $stage) {
        foreach ($doc_types as $doc_type) {
            $dir = $upload_base . '/' . strtolower($stage) . '/' . $doc_type;
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                echo "<p>✅ Created directory: {$dir}</p>";
            }
        }
    }
    
    // Create test documents
    $test_documents = [
        [
            'stage_name' => 'Foundation',
            'document_type' => 'receipt',
            'filename' => 'cement_purchase_receipt.pdf',
            'description' => 'Receipt for cement and steel purchase for foundation work',
            'verification_status' => 'approved'
        ],
        [
            'stage_name' => 'Foundation',
            'document_type' => 'bill',
            'filename' => 'foundation_labor_bill.jpg',
            'description' => 'Labor bill for foundation excavation and concrete work',
            'verification_status' => 'pending'
        ],
        [
            'stage_name' => 'Foundation',
            'document_type' => 'material_certificate',
            'filename' => 'cement_quality_certificate.pdf',
            'description' => 'Quality certificate for cement used in foundation',
            'verification_status' => 'approved'
        ],
        [
            'stage_name' => 'Structure',
            'document_type' => 'receipt',
            'filename' => 'steel_bars_receipt.pdf',
            'description' => 'Receipt for steel bars and structural materials',
            'verification_status' => 'pending'
        ],
        [
            'stage_name' => 'Structure',
            'document_type' => 'bill',
            'filename' => 'structural_work_bill.jpg',
            'description' => 'Bill for structural construction work',
            'verification_status' => 'approved'
        ],
        [
            'stage_name' => 'Brickwork',
            'document_type' => 'receipt',
            'filename' => 'brick_purchase_receipt.jpg',
            'description' => 'Receipt for brick and mortar purchase',
            'verification_status' => 'pending'
        ],
        [
            'stage_name' => 'Electrical',
            'document_type' => 'receipt',
            'filename' => 'electrical_materials_receipt.pdf',
            'description' => 'Receipt for electrical wires, switches, and fittings',
            'verification_status' => 'rejected'
        ]
    ];
    
    // Insert test documents
    foreach ($test_documents as $index => $doc) {
        $file_path = $upload_base . '/' . strtolower($doc['stage_name']) . '/' . $doc['document_type'] . '/' . time() . '_' . $doc['filename'];
        
        // Create dummy file content
        $dummy_content = "Dummy content for " . $doc['filename'] . " - " . $doc['description'];
        file_put_contents($file_path, $dummy_content);
        
        $stmt = $db->prepare("
            INSERT INTO contractor_stage_documents (
                project_id, contractor_id, stage_name, document_type, 
                file_path, original_filename, file_size, mime_type,
                uploaded_by, description, verification_status,
                verified_by, verified_at, verification_notes,
                created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
            )
        ");
        
        $mime_type = strpos($doc['filename'], '.pdf') !== false ? 'application/pdf' : 'image/jpeg';
        $file_size = strlen($dummy_content);
        $verified_by = $doc['verification_status'] !== 'pending' ? 1 : null; // Admin user ID 1
        $verified_at = $doc['verification_status'] !== 'pending' ? date('Y-m-d H:i:s') : null;
        $verification_notes = $doc['verification_status'] === 'rejected' ? 'Document quality is poor, please resubmit' : null;
        
        $stmt->execute([
            $project['project_id'],
            $project['contractor_id'],
            $doc['stage_name'],
            $doc['document_type'],
            $file_path,
            $doc['filename'],
            $file_size,
            $mime_type,
            $project['contractor_id'], // uploaded_by
            $doc['description'],
            $doc['verification_status'],
            $verified_by,
            $verified_at,
            $verification_notes
        ]);
        
        echo "<p>✅ Created document: {$doc['stage_name']} - {$doc['document_type']} - {$doc['filename']} ({$doc['verification_status']})</p>";
    }
    
    // Verify the created documents
    $stmt = $db->prepare("
        SELECT 
            csd.*,
            cp.project_name,
            CONCAT(u.first_name, ' ', u.last_name) as contractor_name
        FROM contractor_stage_documents csd
        JOIN construction_projects cp ON csd.project_id = cp.id
        JOIN users u ON csd.contractor_id = u.id
        WHERE cp.homeowner_id = 32
        ORDER BY csd.stage_name, csd.document_type
    ");
    $stmt->execute();
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📋 Verification: Contractor Documents for Homeowner 32</h3>";
    if (count($documents) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Stage</th><th>Type</th><th>Filename</th><th>Status</th><th>Contractor</th><th>Description</th></tr>";
        foreach ($documents as $doc) {
            echo "<tr>";
            echo "<td>{$doc['stage_name']}</td>";
            echo "<td>{$doc['document_type']}</td>";
            echo "<td>{$doc['original_filename']}</td>";
            echo "<td>{$doc['verification_status']}</td>";
            echo "<td>{$doc['contractor_name']}</td>";
            echo "<td>" . substr($doc['description'], 0, 50) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>🎉 Success!</h3>";
        echo "<p>✅ Test contractor stage documents created successfully. Now you can test the homeowner dashboard:</p>";
        echo "<ol>";
        echo "<li><a href='test_homeowner_contractor_documents_fix.html' target='_blank'>Run the Contractor Documents Display Test</a></li>";
        echo "<li>Go to the homeowner dashboard and click on 'Contractor Documents' tab</li>";
        echo "<li>You should see " . count($documents) . " contractor-uploaded documents</li>";
        echo "</ol>";
        
    } else {
        echo "<p>❌ No documents found after creation. Something went wrong.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>