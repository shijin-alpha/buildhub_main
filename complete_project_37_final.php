<?php
/**
 * Complete Construction Progress for Project ID 37
 * Using correct schema: construction_projects.id = 37
 */

$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== COMPLETING PROJECT 37 CONSTRUCTION ===\n\n";
    
    // Step 1: Check if project 37 exists
    $stmt = $db->query("SELECT * FROM construction_projects WHERE id = 37");
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo "Creating Project 37...\n";
        $db->exec("INSERT INTO construction_projects (
            id, homeowner_id, contractor_id, project_name, project_description,
            total_cost, estimated_cost, timeline, status, start_date, expected_completion_date,
            current_stage, completion_percentage, created_at
        ) VALUES (
            37, 32, 29, 'Complete Construction Demo Project',
            'Full construction project demonstrating all stages and functionalities',
            5000000.00, 5000000.00, '12 months', 'in_progress', '2026-01-01', '2026-12-31',
            'Foundation', 0.00, NOW()
        )");
        echo "✓ Project 37 created\n\n";
        
        $stmt = $db->query("SELECT * FROM construction_projects WHERE id = 37");
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        echo "✓ Project 37 exists: {$project['project_name']}\n\n";
    }
    
    // Step 2: Create Construction Progress Updates for all stages
    echo "=== CREATING CONSTRUCTION PROGRESS UPDATES ===\n\n";
    
    $stages = [
        ['name' => 'Foundation', 'order' => 1, 'progress' => 100, 'cost' => 1000000],
        ['name' => 'Structure', 'order' => 2, 'progress' => 100, 'cost' => 1500000],
        ['name' => 'Roofing', 'order' => 3, 'progress' => 100, 'cost' => 500000],
        ['name' => 'Electrical', 'order' => 4, 'progress' => 100, 'cost' => 400000],
        ['name' => 'Plumbing', 'order' => 5, 'progress' => 100, 'cost' => 400000],
        ['name' => 'Finishing', 'order' => 6, 'progress' => 100, 'cost' => 800000],
        ['name' => 'Painting', 'order' => 7, 'progress' => 100, 'cost' => 200000],
        ['name' => 'Final Inspection', 'order' => 8, 'progress' => 100, 'cost' => 200000]
    ];
    
    foreach ($stages as $stage) {
        // Check if progress update exists
        $stmt = $db->prepare("SELECT id FROM construction_progress_updates 
                             WHERE project_id = 37 AND stage_name = ?");
        $stmt->execute([$stage['name']]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            $updateDate = date('Y-m-d', strtotime('2026-01-01 + ' . (($stage['order'] - 1) * 30) . ' days'));
            
            $db->exec("INSERT INTO construction_progress_updates (
                project_id, contractor_id, stage_name, stage_order,
                progress_percentage, update_description, update_date,
                workers_count, materials_used, cost_incurred, created_at
            ) VALUES (
                37, 29, '{$stage['name']}', {$stage['order']},
                {$stage['progress']}, 'Stage {$stage['name']} completed successfully',
                '$updateDate', 10, 'Standard construction materials', {$stage['cost']}, NOW()
            )");
            echo "✓ Created progress update: {$stage['name']} - {$stage['progress']}%\n";
        } else {
            $db->exec("UPDATE construction_progress_updates SET 
                progress_percentage = {$stage['progress']},
                update_description = 'Stage {$stage['name']} completed successfully',
                updated_at = NOW()
                WHERE project_id = 37 AND stage_name = '{$stage['name']}'");
            echo "✓ Updated progress: {$stage['name']} - {$stage['progress']}%\n";
        }
    }
    
    echo "\n=== CREATING DAILY PROGRESS UPDATES ===\n\n";
    
    // Create daily progress reports for each stage
    foreach ($stages as $stage) {
        for ($day = 1; $day <= 5; $day++) {
            $progress = $day * 20; // 20%, 40%, 60%, 80%, 100%
            $reportDate = date('Y-m-d', strtotime('2026-01-01 + ' . (($stage['order'] - 1) * 30 + $day * 5) . ' days'));
            
            $stmt = $db->prepare("SELECT id FROM daily_progress_updates 
                                 WHERE project_id = 37 AND stage_name = ? AND report_date = ?");
            $stmt->execute([$stage['name'], $reportDate]);
            $existing = $stmt->fetch();
            
            if (!$existing) {
                $db->exec("INSERT INTO daily_progress_updates (
                    project_id, contractor_id, stage_name, report_date,
                    progress_percentage, work_description, workers_present,
                    weather_conditions, issues_faced, photos, created_at
                ) VALUES (
                    37, 29, '{$stage['name']}', '$reportDate',
                    $progress, 'Day $day: {$stage['name']} stage progress', 10,
                    'Clear and sunny', 'No issues', '[]', NOW()
                )");
            }
        }
        echo "✓ Created 5 daily reports for {$stage['name']}\n";
    }
    
    echo "\n=== CREATING STAGE PAYMENT REQUESTS ===\n\n";
    
    foreach ($stages as $stage) {
        $stmt = $db->prepare("SELECT id FROM project_stage_payment_requests 
                             WHERE project_id = 37 AND stage_name = ?");
        $stmt->execute([$stage['name']]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            $db->exec("INSERT INTO project_stage_payment_requests (
                project_id, contractor_id, homeowner_id, stage_name, stage_order,
                requested_amount, payment_status, request_date, approval_date,
                payment_date, description, created_at
            ) VALUES (
                37, 29, 32, '{$stage['name']}', {$stage['order']},
                {$stage['cost']}, 'paid', NOW(), NOW(), NOW(),
                'Payment for {$stage['name']} stage completion', NOW()
            )");
            echo "✓ Created payment request: {$stage['name']} - ₹" . number_format($stage['cost']) . "\n";
        } else {
            $db->exec("UPDATE project_stage_payment_requests SET 
                payment_status = 'paid',
                approval_date = NOW(),
                payment_date = NOW()
                WHERE project_id = 37 AND stage_name = '{$stage['name']}'");
            echo "✓ Updated payment: {$stage['name']}\n";
        }
    }
    
    echo "\n=== CREATING INSPECTION REPORTS ===\n\n";
    
    foreach ($stages as $stage) {
        $stmt = $db->prepare("SELECT id FROM inspection_reports 
                             WHERE project_id = 37 AND stage_name = ?");
        $stmt->execute([$stage['name']]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            $db->exec("INSERT INTO inspection_reports (
                project_id, inspector_id, stage_name, inspection_date,
                overall_status, quality_rating, safety_rating, compliance_rating,
                inspector_notes, recommendations, created_at
            ) VALUES (
                37, 1001, '{$stage['name']}', NOW(),
                'approved', 5, 5, 5,
                'Stage {$stage['name']} inspected and approved. All quality standards met.',
                'Continue to next stage', NOW()
            )");
            echo "✓ Created inspection: {$stage['name']} - Approved\n";
        }
    }
    
    echo "\n=== CREATING CONTRACTOR DOCUMENTS ===\n\n";
    
    foreach ($stages as $stage) {
        $documents = [
            ['type' => 'progress_photo', 'name' => 'Progress Photo - ' . $stage['name']],
            ['type' => 'material_bill', 'name' => 'Material Bill - ' . $stage['name']],
            ['type' => 'completion_certificate', 'name' => 'Completion Certificate - ' . $stage['name']]
        ];
        
        foreach ($documents as $doc) {
            $stmt = $db->prepare("SELECT id FROM contractor_stage_documents 
                                 WHERE project_id = 37 AND stage_name = ? AND document_type = ?");
            $stmt->execute([$stage['name'], $doc['type']]);
            $existing = $stmt->fetch();
            
            if (!$existing) {
                $db->exec("INSERT INTO contractor_stage_documents (
                    project_id, contractor_id, stage_name, document_type,
                    document_name, file_path, upload_date, status, created_at
                ) VALUES (
                    37, 29, '{$stage['name']}', '{$doc['type']}',
                    '{$doc['name']}', 'uploads/project_37/{$stage['name']}/{$doc['type']}.pdf',
                    NOW(), 'approved', NOW()
                )");
            }
        }
        echo "✓ Created 3 documents for {$stage['name']}\n";
    }
    
    echo "\n=== UPDATING PROJECT STATUS ===\n\n";
    
    // Update project to completed
    $db->exec("UPDATE construction_projects SET 
        status = 'completed',
        current_stage = 'Final Inspection',
        completion_percentage = 100.00,
        actual_completion_date = NOW(),
        actual_end_date = NOW(),
        updated_at = NOW()
        WHERE id = 37");
    
    echo "✓ Project 37 marked as 100% completed\n\n";
    
    // Final Summary
    echo "=== FINAL SUMMARY FOR PROJECT 37 ===\n\n";
    
    $stmt = $db->query("SELECT * FROM construction_projects WHERE id = 37");
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Project Name: {$project['project_name']}\n";
    echo "Status: {$project['status']}\n";
    echo "Completion: {$project['completion_percentage']}%\n";
    echo "Current Stage: {$project['current_stage']}\n\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM construction_progress_updates WHERE project_id = 37");
    $progressCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM daily_progress_updates WHERE project_id = 37");
    $dailyCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM project_stage_payment_requests WHERE project_id = 37");
    $paymentCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM inspection_reports WHERE project_id = 37");
    $inspectionCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM contractor_stage_documents WHERE project_id = 37");
    $documentCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT SUM(requested_amount) as total FROM project_stage_payment_requests WHERE project_id = 37");
    $totalPaid = $stmt->fetch()['total'];
    
    echo "📊 Statistics:\n";
    echo "  - Construction Stages: 8 (All 100% complete)\n";
    echo "  - Progress Updates: $progressCount\n";
    echo "  - Daily Reports: $dailyCount\n";
    echo "  - Stage Payments: $paymentCount (All paid)\n";
    echo "  - Inspection Reports: $inspectionCount (All approved)\n";
    echo "  - Contractor Documents: $documentCount\n";
    echo "  - Total Amount Paid: ₹" . number_format($totalPaid) . "\n\n";
    
    echo "✅ PROJECT 37 CONSTRUCTION FULLY COMPLETED WITH ALL FUNCTIONALITIES!\n\n";
    
    echo "You can now test:\n";
    echo "  1. View project details in homeowner dashboard\n";
    echo "  2. Check all 8 construction stages at 100%\n";
    echo "  3. Review daily progress reports (40 reports total)\n";
    echo "  4. Verify all stage payments are completed\n";
    echo "  5. View inspection reports for each stage\n";
    echo "  6. Access contractor documents (24 documents total)\n";
    echo "  7. See project completion status\n\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
