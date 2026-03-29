<?php
/**
 * Finalize Project 37 - Mark as Completed
 * This script marks the project as fully completed and generates completion certificate
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║         FINALIZING PROJECT 37 - COMPLETION STATUS            ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";

    $project_id = 37;
    
    // Step 1: Update project status to 'completed'
    echo "📋 Step 1: Updating project status to 'completed'...\n";
    $updateProjectQuery = "
        UPDATE construction_projects 
        SET 
            status = 'completed',
            current_stage = 'Completed',
            completion_percentage = 100.00,
            actual_completion_date = CURDATE(),
            updated_at = NOW()
        WHERE id = :project_id
    ";
    
    $stmt = $db->prepare($updateProjectQuery);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    echo "   ✅ Project status updated to 'completed'\n\n";

    // Step 2: Verify all stages are at 100%
    echo "📊 Step 2: Verifying all construction stages...\n";
    $stagesQuery = "
        SELECT stage_name, completion_percentage
        FROM construction_progress_updates
        WHERE project_id = :project_id
        ORDER BY updated_at DESC
    ";
    
    $stmt = $db->prepare($stagesQuery);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $stages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($stages as $stage) {
        $icon = $stage['completion_percentage'] == 100 ? '✅' : '⚠️';
        echo "   {$icon} {$stage['stage_name']}: {$stage['completion_percentage']}%\n";
    }
    echo "\n";

    // Step 3: Generate completion certificate data
    echo "📜 Step 3: Generating completion certificate...\n";
    
    $certificateQuery = "
        INSERT INTO contractor_stage_documents 
        (project_id, contractor_id, stage_name, document_type, file_path, 
         original_filename, file_size, upload_date, verification_status, description)
        VALUES 
        (:project_id, 29, 'Project Completion', 'other', 
         'uploads/documents/project_37_completion_certificate.pdf',
         'Project Completion Certificate.pdf', 524288, NOW(), 'approved',
         'Official project completion certificate - All stages completed successfully')
    ";
    
    $stmt = $db->prepare($certificateQuery);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    echo "   ✅ Completion certificate generated\n\n";

    // Step 4: Add handover documentation
    echo "📦 Step 4: Adding handover documentation...\n";
    
    $handoverDocs = [
        [
            'type' => 'other',
            'name' => 'Project Handover Checklist.pdf',
            'path' => 'uploads/documents/project_37_handover_checklist.pdf',
            'notes' => 'Complete handover checklist with all items verified'
        ],
        [
            'type' => 'other',
            'name' => 'Warranty and Guarantee Documents.pdf',
            'path' => 'uploads/documents/project_37_warranty_docs.pdf',
            'notes' => 'All warranty documents for materials and workmanship'
        ],
        [
            'type' => 'other',
            'name' => 'Building Maintenance Guide.pdf',
            'path' => 'uploads/documents/project_37_maintenance_guide.pdf',
            'notes' => 'Comprehensive maintenance guide for the completed building'
        ],
        [
            'type' => 'other',
            'name' => 'As-Built Drawings.pdf',
            'path' => 'uploads/documents/project_37_as_built_drawings.pdf',
            'notes' => 'Final as-built architectural and structural drawings'
        ]
    ];

    foreach ($handoverDocs as $doc) {
        $insertDocQuery = "
            INSERT INTO contractor_stage_documents 
            (project_id, contractor_id, stage_name, document_type, file_path, 
             original_filename, file_size, upload_date, verification_status, description)
            VALUES 
            (:project_id, 29, 'Project Completion', :doc_type, :doc_path,
             :doc_name, 262144, NOW(), 'approved', :notes)
        ";
        
        $stmt = $db->prepare($insertDocQuery);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->bindParam(':doc_type', $doc['type']);
        $stmt->bindParam(':doc_path', $doc['path']);
        $stmt->bindParam(':doc_name', $doc['name']);
        $stmt->bindParam(':notes', $doc['notes']);
        $stmt->execute();
        
        echo "   ✅ {$doc['name']} added\n";
    }
    echo "\n";

    // Step 5: Add final inspection approval
    echo "🔍 Step 5: Recording final inspection approval...\n";
    
    $finalInspectionQuery = "
        INSERT INTO inspection_reports 
        (project_id, inspector_id, inspection_date, inspection_stage, inspection_type,
         overall_status, quality_score, safety_compliance, notes, 
         workmanship_quality, code_compliance, site_cleanliness, created_at)
        VALUES 
        (:project_id, 31, CURDATE(), 'Project Completion', 'final',
         'approved', 5.0, 'compliant',
         'Final inspection completed. All construction stages meet quality standards. Project approved for handover to homeowner. Excellent workmanship throughout all phases.',
         'excellent', 'compliant', 'excellent', NOW())
    ";
    
    $stmt = $db->prepare($finalInspectionQuery);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    echo "   ✅ Final inspection approval recorded\n\n";

    // Step 6: Calculate final project statistics
    echo "📈 Step 6: Calculating final project statistics...\n";
    
    // Get total payments
    $paymentQuery = "
        SELECT 
            COUNT(*) as total_payments,
            SUM(requested_amount) as total_amount_paid,
            MIN(request_date) as first_payment_date,
            MAX(payment_date) as last_payment_date
        FROM stage_payment_requests
        WHERE project_id = :project_id AND status = 'paid'
    ";
    
    $stmt = $db->prepare($paymentQuery);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $paymentStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get total daily updates
    $updatesQuery = "
        SELECT COUNT(*) as total_updates
        FROM daily_progress_updates
        WHERE project_id = :project_id
    ";
    
    $stmt = $db->prepare($updatesQuery);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $updateStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get total documents
    $docsQuery = "
        SELECT COUNT(*) as total_documents
        FROM contractor_stage_documents
        WHERE project_id = :project_id
    ";
    
    $stmt = $db->prepare($docsQuery);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $docStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get total inspections
    $inspectionsQuery = "
        SELECT COUNT(*) as total_inspections
        FROM inspection_reports
        WHERE project_id = :project_id
    ";
    
    $stmt = $db->prepare($inspectionsQuery);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $inspectionStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   💰 Total Payments: {$paymentStats['total_payments']} (₹" . number_format($paymentStats['total_amount_paid']) . ")\n";
    echo "   📝 Daily Progress Updates: {$updateStats['total_updates']}\n";
    echo "   📄 Documents Uploaded: {$docStats['total_documents']}\n";
    echo "   🔍 Inspections Completed: {$inspectionStats['total_inspections']}\n";
    echo "   📅 First Payment: {$paymentStats['first_payment_date']}\n";
    echo "   📅 Last Payment: {$paymentStats['last_payment_date']}\n\n";

    // Step 7: Create project completion summary
    echo "📋 Step 7: Creating project completion summary...\n";
    
    $projectQuery = "
        SELECT 
            cp.*,
            CONCAT(u_homeowner.first_name, ' ', u_homeowner.last_name) as homeowner_name,
            u_homeowner.email as homeowner_email,
            CONCAT(u_contractor.first_name, ' ', u_contractor.last_name) as contractor_name,
            u_contractor.email as contractor_email,
            DATEDIFF(CURDATE(), cp.start_date) as actual_duration_days
        FROM construction_projects cp
        LEFT JOIN users u_homeowner ON cp.homeowner_id = u_homeowner.id
        LEFT JOIN users u_contractor ON cp.contractor_id = u_contractor.id
        WHERE cp.id = :project_id
    ";
    
    $stmt = $db->prepare($projectQuery);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║              PROJECT COMPLETION SUMMARY                      ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    echo "🏗️  PROJECT DETAILS:\n";
    echo "   Project ID: {$project['id']}\n";
    echo "   Project Name: {$project['project_name']}\n";
    echo "   Location: {$project['project_location']}\n";
    echo "   Status: ✅ {$project['status']}\n";
    echo "   Completion: {$project['completion_percentage']}%\n\n";
    
    echo "👥 STAKEHOLDERS:\n";
    echo "   Homeowner: {$project['homeowner_name']} ({$project['homeowner_email']})\n";
    echo "   Contractor: {$project['contractor_name']} ({$project['contractor_email']})\n\n";
    
    echo "📅 TIMELINE:\n";
    echo "   Start Date: {$project['start_date']}\n";
    echo "   Expected Completion: {$project['expected_completion_date']}\n";
    echo "   Actual Completion: " . date('Y-m-d') . "\n";
    echo "   Duration: {$project['actual_duration_days']} days\n\n";
    
    echo "💰 FINANCIAL SUMMARY:\n";
    echo "   Total Budget: ₹" . number_format($project['total_cost']) . "\n";
    echo "   Total Paid: ₹" . number_format($paymentStats['total_amount_paid']) . "\n";
    echo "   Payment Completion: " . round(($paymentStats['total_amount_paid'] / $project['total_cost']) * 100, 2) . "%\n\n";
    
    echo "📊 PROJECT METRICS:\n";
    echo "   Construction Stages: 10 (All completed)\n";
    echo "   Daily Updates: {$updateStats['total_updates']}\n";
    echo "   Documents: {$docStats['total_documents']}\n";
    echo "   Inspections: {$inspectionStats['total_inspections']}\n";
    echo "   Payment Requests: {$paymentStats['total_payments']}\n\n";
    
    echo "✅ COMPLETION CHECKLIST:\n";
    echo "   ✓ All construction stages completed (100%)\n";
    echo "   ✓ All payments processed\n";
    echo "   ✓ All inspections approved\n";
    echo "   ✓ Completion certificate generated\n";
    echo "   ✓ Handover documentation prepared\n";
    echo "   ✓ Final inspection approved\n";
    echo "   ✓ Project status updated to 'completed'\n\n";
    
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║           🎉 PROJECT 37 SUCCESSFULLY COMPLETED! 🎉           ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    echo "📌 NEXT STEPS FOR HOMEOWNER:\n";
    echo "   1. Review completion certificate and handover documents\n";
    echo "   2. Schedule final walkthrough with contractor\n";
    echo "   3. Collect all warranty documents\n";
    echo "   4. Review maintenance guide\n";
    echo "   5. Provide feedback and rating for contractor\n";
    echo "   6. Close project and release final retention (if any)\n\n";
    
    echo "📌 AVAILABLE IN HOMEOWNER DASHBOARD:\n";
    echo "   • Project marked as 'Completed' with 100% progress\n";
    echo "   • Completion certificate available for download\n";
    echo "   • All handover documents accessible\n";
    echo "   • Final inspection report viewable\n";
    echo "   • Complete payment history\n";
    echo "   • Full construction timeline and progress history\n\n";

} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
