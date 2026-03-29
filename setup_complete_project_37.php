<?php
/**
 * Complete Setup for Project 37 with all dependencies
 */

$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== SETTING UP COMPLETE PROJECT 37 ===\n\n";
    
    // Step 1: Check if project 37 already exists
    $stmt = $db->query("SELECT * FROM construction_projects WHERE id = 37");
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project) {
        echo "✓ Project 37 already exists: {$project['project_name']}\n";
        echo "  Status: {$project['status']}\n";
        echo "  Completion: {$project['completion_percentage']}%\n\n";
        $projectId = 37;
    } else {
        echo "Creating dependencies for Project 37...\n\n";
        
        // Check if estimate exists
        $stmt = $db->query("SELECT id FROM contractor_send_estimates WHERE id = 37");
        $estimate = $stmt->fetch();
        
        if (!$estimate) {
            $db->exec("INSERT INTO contractor_send_estimates (
                id, contractor_id, total_cost, timeline, status, created_at
            ) VALUES (
                37, 29, 5000000.00, '12 months', 'accepted', NOW()
            )");
            echo "✓ Created estimate #37\n";
        } else {
            echo "✓ Estimate #37 already exists\n";
        }
        
        // Check if project exists
        $stmt = $db->query("SELECT id FROM construction_projects WHERE id = 37");
        $existingProject = $stmt->fetch();
        
        if (!$existingProject) {
            // Create project
            $db->exec("INSERT INTO construction_projects (
                id, estimate_id, homeowner_id, contractor_id, project_name, project_description,
                total_cost, estimated_cost, timeline, status, start_date, expected_completion_date,
                current_stage, completion_percentage, created_at
            ) VALUES (
                37, 37, 32, 29, 'Complete Construction Demo Project',
                'Full construction project demonstrating all stages and functionalities',
                5000000.00, 5000000.00, '12 months', 'in_progress', '2026-01-01', '2026-12-31',
                'Foundation', 0.00, NOW()
            )");
            echo "✓ Created project #37\n\n";
        } else {
            echo "✓ Project #37 already exists\n\n";
        }
        $projectId = 37;
    }
    
    // Step 2: Create all construction stages with progress
    echo "=== CREATING CONSTRUCTION STAGES ===\n\n";
    
    $stages = [
        ['name' => 'Foundation', 'order' => 1, 'progress' => 100, 'cost' => 1000000, 'days' => 30],
        ['name' => 'Structure', 'order' => 2, 'progress' => 100, 'cost' => 1500000, 'days' => 45],
        ['name' => 'Roofing', 'order' => 3, 'progress' => 100, 'cost' => 500000, 'days' => 20],
        ['name' => 'Electrical', 'order' => 4, 'progress' => 100, 'cost' => 400000, 'days' => 25],
        ['name' => 'Plumbing', 'order' => 5, 'progress' => 100, 'cost' => 400000, 'days' => 25],
        ['name' => 'Finishing', 'order' => 6, 'progress' => 100, 'cost' => 800000, 'days' => 40],
        ['name' => 'Painting', 'order' => 7, 'progress' => 100, 'cost' => 200000, 'days' => 15],
        ['name' => 'Final Inspection', 'order' => 8, 'progress' => 100, 'cost' => 200000, 'days' => 5]
    ];
    
    $totalDays = 0;
    foreach ($stages as $stage) {
        $startDate = date('Y-m-d', strtotime('2026-01-01 + ' . $totalDays . ' days'));
        $endDate = date('Y-m-d', strtotime($startDate . ' + ' . $stage['days'] . ' days'));
        $totalDays += $stage['days'];
        
        // Check if progress update exists
        $stmt = $db->prepare("SELECT id FROM construction_progress_updates 
                             WHERE project_id = ? AND stage_name = ?");
        $stmt->execute([$projectId, $stage['name']]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            $db->exec("INSERT INTO construction_progress_updates (
                project_id, contractor_id, stage_name, stage_order,
                progress_percentage, update_description, update_date,
                workers_count, materials_used, cost_incurred, created_at
            ) VALUES (
                $projectId, 29, '{$stage['name']}', {$stage['order']},
                {$stage['progress']}, 'Stage {$stage['name']} completed successfully. All work finished on schedule.',
                '$endDate', 10, 'Standard construction materials as per specifications', {$stage['cost']}, NOW()
            )");
            echo "✓ {$stage['name']}: {$stage['progress']}% complete\n";
        } else {
            $db->exec("UPDATE construction_progress_updates SET 
                progress_percentage = {$stage['progress']},
                update_description = 'Stage {$stage['name']} completed successfully. All work finished on schedule.',
                updated_at = NOW()
                WHERE project_id = $projectId AND stage_name = '{$stage['name']}'");
            echo "✓ {$stage['name']}: Updated to {$stage['progress']}%\n";
        }
    }
    
    echo "\n=== CREATING DAILY PROGRESS REPORTS ===\n\n";
    
    // Create 5 daily reports per stage
    $dayOffset = 0;
    foreach ($stages as $stage) {
        $reportsCreated = 0;
        for ($day = 1; $day <= 5; $day++) {
            $progress = $day * 20; // 20%, 40%, 60%, 80%, 100%
            $reportDate = date('Y-m-d', strtotime('2026-01-01 + ' . ($dayOffset + $day * ($stage['days'] / 5)) . ' days'));
            
            $stmt = $db->prepare("SELECT id FROM daily_progress_updates 
                                 WHERE project_id = ? AND stage_name = ? AND report_date = ?");
            $stmt->execute([$projectId, $stage['name'], $reportDate]);
            $existing = $stmt->fetch();
            
            if (!$existing) {
                $workDesc = "Day $day of {$stage['name']} stage. Progress: $progress%. ";
                if ($day == 1) {
                    $workDesc .= "Started work, site preparation completed.";
                } elseif ($day == 2) {
                    $workDesc .= "Main work in progress, materials delivered.";
                } elseif ($day == 3) {
                    $workDesc .= "Halfway through, quality checks performed.";
                } elseif ($day == 4) {
                    $workDesc .= "Nearing completion, final touches being added.";
                } elseif ($day == 5) {
                    $workDesc .= "Stage completed, ready for inspection.";
                } else {
                    $workDesc .= "Work progressing as planned.";
                }
                
                $db->exec("INSERT INTO daily_progress_updates (
                    project_id, contractor_id, stage_name, report_date,
                    progress_percentage, work_description, workers_present,
                    weather_conditions, issues_faced, photos, created_at
                ) VALUES (
                    $projectId, 29, '{$stage['name']}', '$reportDate',
                    $progress, '$workDesc', 10,
                    'Clear and sunny', 'No major issues', '[]', NOW()
                )");
                $reportsCreated++;
            }
        }
        echo "✓ {$stage['name']}: $reportsCreated daily reports\n";
        $dayOffset += $stage['days'];
    }
    
    echo "\n=== CREATING STAGE PAYMENTS ===\n\n";
    
    foreach ($stages as $stage) {
        $stmt = $db->prepare("SELECT id FROM project_stage_payment_requests 
                             WHERE project_id = ? AND stage_name = ?");
        $stmt->execute([$projectId, $stage['name']]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            $description = "Payment for {$stage['name']} stage completion - ₹" . number_format($stage['cost']);
            $stmt = $db->prepare("INSERT INTO project_stage_payment_requests (
                project_id, contractor_id, homeowner_id, stage_name, stage_order,
                requested_amount, payment_status, request_date, approval_date,
                payment_date, description, created_at
            ) VALUES (
                ?, 29, 32, ?, ?,
                ?, 'paid', NOW(), NOW(), NOW(),
                ?, NOW()
            )");
            $stmt->execute([$projectId, $stage['name'], $stage['order'], $stage['cost'], $description]);
            echo "✓ {$stage['name']}: ₹" . number_format($stage['cost']) . " - PAID\n";
        } else {
            $db->exec("UPDATE project_stage_payment_requests SET 
                payment_status = 'paid',
                approval_date = NOW(),
                payment_date = NOW()
                WHERE project_id = $projectId AND stage_name = '{$stage['name']}'");
            echo "✓ {$stage['name']}: Payment updated to PAID\n";
        }
    }
    
    echo "\n=== CREATING INSPECTION REPORTS ===\n\n";
    
    foreach ($stages as $stage) {
        $stmt = $db->prepare("SELECT id FROM inspection_reports 
                             WHERE project_id = ? AND stage_name = ?");
        $stmt->execute([$projectId, $stage['name']]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            $notes = "Inspection of {$stage['name']} stage completed. ";
            $notes .= "All construction work meets quality standards. ";
            $notes .= "Safety protocols followed. Materials used are as per specifications. ";
            $notes .= "Approved for next stage.";
            
            $db->exec("INSERT INTO inspection_reports (
                project_id, inspector_id, stage_name, inspection_date,
                overall_status, quality_rating, safety_rating, compliance_rating,
                inspector_notes, recommendations, created_at
            ) VALUES (
                $projectId, 1001, '{$stage['name']}', NOW(),
                'approved', 5, 5, 5,
                '$notes',
                'Continue to next stage. Maintain current quality standards.', NOW()
            )");
            echo "✓ {$stage['name']}: APPROVED (5/5 rating)\n";
        }
    }
    
    echo "\n=== CREATING CONTRACTOR DOCUMENTS ===\n\n";
    
    $totalDocs = 0;
    foreach ($stages as $stage) {
        $documents = [
            ['type' => 'progress_photo', 'name' => 'Progress Photos - ' . $stage['name'], 'desc' => 'Site progress photographs'],
            ['type' => 'material_bill', 'name' => 'Material Bills - ' . $stage['name'], 'desc' => 'Material purchase invoices'],
            ['type' => 'completion_certificate', 'name' => 'Completion Certificate - ' . $stage['name'], 'desc' => 'Stage completion certificate']
        ];
        
        $docsCreated = 0;
        foreach ($documents as $doc) {
            $stmt = $db->prepare("SELECT id FROM contractor_stage_documents 
                                 WHERE project_id = ? AND stage_name = ? AND document_type = ?");
            $stmt->execute([$projectId, $stage['name'], $doc['type']]);
            $existing = $stmt->fetch();
            
            if (!$existing) {
                $db->exec("INSERT INTO contractor_stage_documents (
                    project_id, contractor_id, stage_name, document_type,
                    document_name, document_description, file_path, upload_date, status, created_at
                ) VALUES (
                    $projectId, 29, '{$stage['name']}', '{$doc['type']}',
                    '{$doc['name']}', '{$doc['desc']}',
                    'uploads/project_37/{$stage['name']}/{$doc['type']}.pdf',
                    NOW(), 'approved', NOW()
                )");
                $docsCreated++;
            }
        }
        echo "✓ {$stage['name']}: $docsCreated documents uploaded\n";
        $totalDocs += $docsCreated;
    }
    
    echo "\n=== FINALIZING PROJECT ===\n\n";
    
    // Update project to completed
    $db->exec("UPDATE construction_projects SET 
        status = 'completed',
        current_stage = 'Final Inspection',
        completion_percentage = 100.00,
        actual_completion_date = NOW(),
        actual_end_date = NOW(),
        updated_at = NOW()
        WHERE id = $projectId");
    
    echo "✓ Project status: COMPLETED\n";
    echo "✓ Completion percentage: 100%\n\n";
    
    // Generate final summary
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║         PROJECT 37 - CONSTRUCTION COMPLETE SUMMARY         ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    $stmt = $db->query("SELECT * FROM construction_projects WHERE id = $projectId");
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "📋 Project Details:\n";
    echo "   Name: {$project['project_name']}\n";
    echo "   Status: {$project['status']}\n";
    echo "   Completion: {$project['completion_percentage']}%\n";
    echo "   Budget: ₹" . number_format($project['total_cost']) . "\n\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM construction_progress_updates WHERE project_id = $projectId");
    $progressCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM daily_progress_updates WHERE project_id = $projectId");
    $dailyCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count, SUM(requested_amount) as total 
                        FROM project_stage_payment_requests WHERE project_id = $projectId");
    $paymentData = $stmt->fetch();
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM inspection_reports WHERE project_id = $projectId");
    $inspectionCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM contractor_stage_documents WHERE project_id = $projectId");
    $documentCount = $stmt->fetch()['count'];
    
    echo "📊 Completion Statistics:\n";
    echo "   ✓ Construction Stages: 8 (All 100% complete)\n";
    echo "   ✓ Stage Progress Updates: $progressCount\n";
    echo "   ✓ Daily Progress Reports: $dailyCount\n";
    echo "   ✓ Stage Payments: {$paymentData['count']} (₹" . number_format($paymentData['total']) . " paid)\n";
    echo "   ✓ Inspection Reports: $inspectionCount (All approved)\n";
    echo "   ✓ Contractor Documents: $documentCount\n\n";
    
    echo "✅ ALL FUNCTIONALITIES COMPLETED SUCCESSFULLY!\n\n";
    
    echo "🎯 You can now test:\n";
    echo "   1. Homeowner Dashboard - View complete project\n";
    echo "   2. Construction Progress - All 8 stages at 100%\n";
    echo "   3. Daily Reports - 40 detailed progress reports\n";
    echo "   4. Payment History - All 8 stage payments completed\n";
    echo "   5. Inspection Reports - All stages approved\n";
    echo "   6. Document Management - 24 contractor documents\n";
    echo "   7. Project Timeline - Complete construction timeline\n";
    echo "   8. Financial Summary - Full payment breakdown\n\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
