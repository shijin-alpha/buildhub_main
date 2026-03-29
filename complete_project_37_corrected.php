<?php
/**
 * Complete ALL Construction Stages for Project 37 - CORRECTED VERSION
 * Using correct database schema
 */

$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║  COMPLETING PROJECT 37 - FULL CONSTRUCTION STAGES            ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    $projectId = 37;
    $contractorId = 29;
    $homeownerId = 28; // Correct homeowner from the project data
    $inspectorId = 1001;
    
    // Verify project exists
    $stmt = $db->query("SELECT * FROM construction_projects WHERE id = $projectId");
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        die("❌ Project 37 not found!\n");
    }
    
    echo "📋 Project: {$project['project_name']}\n";
    echo "💰 Budget: ₹" . number_format($project['total_cost']) . "\n";
    echo "👷 Contractor ID: {$project['contractor_id']}\n";
    echo "🏠 Homeowner ID: {$project['homeowner_id']}\n\n";
    
    // Use actual homeowner from project
    $homeownerId = $project['homeowner_id'];
    $contractorId = $project['contractor_id'];
    
    // Define all 8 construction stages
    $stages = [
        ['name' => 'Foundation', 'order' => 1, 'days' => 30, 'cost' => 133718],
        ['name' => 'Structure', 'order' => 2, 'days' => 45, 'cost' => 200657],
        ['name' => 'Roofing', 'order' => 3, 'days' => 20, 'cost' => 106974],
        ['name' => 'Electrical', 'order' => 4, 'days' => 25, 'cost' => 85580],
        ['name' => 'Plumbing', 'order' => 5, 'days' => 25, 'cost' => 85580],
        ['name' => 'Finishing', 'order' => 6, 'days' => 40, 'cost' => 213949],
        ['name' => 'Painting', 'order' => 7, 'days' => 15, 'cost' => 106974],
        ['name' => 'Final Inspection', 'order' => 8, 'days' => 5, 'cost' => 136313]
    ];
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "STEP 1: CREATING STAGE PROGRESS UPDATES\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $dayOffset = 0;
    foreach ($stages as $stage) {
        $startDate = date('Y-m-d', strtotime('2026-01-15 + ' . $dayOffset . ' days'));
        
        // Check if stage progress exists
        $stmt = $db->prepare("SELECT id FROM construction_progress_updates 
                             WHERE project_id = ? AND stage_name = ?");
        $stmt->execute([$projectId, $stage['name']]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            $stmt = $db->prepare("INSERT INTO construction_progress_updates (
                project_id, contractor_id, homeowner_id, stage_name,
                stage_status, completion_percentage, remarks, created_at, updated_at
            ) VALUES (?, ?, ?, ?, 'Completed', 100.00, ?, NOW(), NOW())");
            
            $remarks = "Stage {$stage['name']} completed successfully. Duration: {$stage['days']} days. Cost: ₹" . number_format($stage['cost']);
            $stmt->execute([$projectId, $contractorId, $homeownerId, $stage['name'], $remarks]);
            echo "✅ {$stage['name']}: 100% COMPLETE\n";
        } else {
            $stmt = $db->prepare("UPDATE construction_progress_updates SET 
                stage_status = 'Completed',
                completion_percentage = 100.00,
                remarks = ?,
                updated_at = NOW()
                WHERE project_id = ? AND stage_name = ?");
            
            $remarks = "Stage {$stage['name']} completed successfully. Duration: {$stage['days']} days. Cost: ₹" . number_format($stage['cost']);
            $stmt->execute([$remarks, $projectId, $stage['name']]);
            echo "✅ {$stage['name']}: UPDATED TO 100%\n";
        }
        
        $dayOffset += $stage['days'];
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "STEP 2: CREATING DAILY PROGRESS REPORTS (5 per stage)\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $dayOffset = 0;
    $totalReports = 0;
    foreach ($stages as $stage) {
        $reportsCreated = 0;
        
        for ($day = 1; $day <= 5; $day++) {
            $incrementalProgress = 20; // Each day adds 20%
            $cumulativeProgress = $day * 20; // 20%, 40%, 60%, 80%, 100%
            $reportDate = date('Y-m-d', strtotime('2026-01-15 + ' . ($dayOffset + ($day * ($stage['days'] / 5))) . ' days'));
            
            // Check if report exists
            $stmt = $db->prepare("SELECT id FROM daily_progress_updates 
                                 WHERE project_id = ? AND construction_stage = ? AND update_date = ?");
            $stmt->execute([$projectId, $stage['name'], $reportDate]);
            $existing = $stmt->fetch();
            
            if (!$existing) {
                $workDone = "Day $day of {$stage['name']} stage. ";
                if ($day == 1) {
                    $workDone .= "Started work, site preparation and material delivery completed. Foundation marking done.";
                } elseif ($day == 2) {
                    $workDone .= "Main construction work in progress. All materials on site. Workers performing primary tasks.";
                } elseif ($day == 3) {
                    $workDone .= "Halfway through stage. Quality checks performed and passed. Work progressing as scheduled.";
                } elseif ($day == 4) {
                    $workDone .= "Nearing completion. Final touches and cleanup in progress. Quality assurance checks ongoing.";
                } elseif ($day == 5) {
                    $workDone .= "Stage completed successfully. Final inspection done. Ready for next stage.";
                }
                
                $stmt = $db->prepare("INSERT INTO daily_progress_updates (
                    project_id, contractor_id, homeowner_id, update_date, construction_stage,
                    work_done_today, incremental_completion_percentage, cumulative_completion_percentage,
                    working_hours, weather_condition, site_issues, progress_photos,
                    location_verified, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 8.0, 'Clear and sunny', 'No major issues', '[]', 1, NOW(), NOW())");
                
                $stmt->execute([
                    $projectId, $contractorId, $homeownerId, $reportDate, $stage['name'],
                    $workDone, $incrementalProgress, $cumulativeProgress
                ]);
                $reportsCreated++;
                $totalReports++;
            }
        }
        
        if ($reportsCreated > 0) {
            echo "✅ {$stage['name']}: $reportsCreated daily reports created\n";
        }
        
        $dayOffset += $stage['days'];
    }
    
    echo "\n   📊 Total Daily Reports: $totalReports\n";
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "STEP 3: CREATING STAGE PAYMENT REQUESTS\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $totalPaid = 0;
    foreach ($stages as $stage) {
        // Check if payment exists
        $stmt = $db->prepare("SELECT id FROM project_stage_payment_requests 
                             WHERE project_id = ? AND stage_name = ?");
        $stmt->execute([$projectId, $stage['name']]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            $workDesc = "Payment for {$stage['name']} stage completion - ₹" . number_format($stage['cost']);
            $percentage = ($stage['cost'] / $project['total_cost']) * 100;
            
            $stmt = $db->prepare("INSERT INTO project_stage_payment_requests (
                project_id, contractor_id, homeowner_id, stage_name,
                requested_amount, percentage_of_total, work_description,
                completion_percentage, request_date, status, payment_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 100.00, NOW(), 'paid', NOW())");
            
            $stmt->execute([
                $projectId, $contractorId, $homeownerId, 
                $stage['name'], $stage['cost'], $percentage, $workDesc
            ]);
            
            echo "✅ {$stage['name']}: ₹" . number_format($stage['cost']) . " - PAID\n";
        } else {
            $stmt = $db->prepare("UPDATE project_stage_payment_requests SET 
                status = 'paid',
                payment_date = NOW()
                WHERE project_id = ? AND stage_name = ?");
            $stmt->execute([$projectId, $stage['name']]);
            
            echo "✅ {$stage['name']}: Payment updated to PAID\n";
        }
        
        $totalPaid += $stage['cost'];
    }
    
    echo "\n   💰 Total Paid: ₹" . number_format($totalPaid) . "\n";
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "STEP 4: CREATING INSPECTION REPORTS\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    foreach ($stages as $stage) {
        // Check if inspection exists
        $stmt = $db->prepare("SELECT id FROM inspection_reports 
                             WHERE project_id = ? AND inspection_stage = ?");
        $stmt->execute([$projectId, $stage['name']]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            $notes = "Comprehensive inspection of {$stage['name']} stage completed on " . date('M d, Y') . ". ";
            $notes .= "All construction work meets required quality standards and building codes. ";
            $notes .= "Safety protocols were properly followed throughout the stage. ";
            $notes .= "Materials used are as per approved specifications. ";
            $notes .= "Stage approved for progression to next phase.";
            
            $recommendations = "Continue to next stage. Maintain current quality standards and safety protocols.";
            
            $stmt = $db->prepare("INSERT INTO inspection_reports (
                project_id, inspector_id, inspection_stage, inspection_date,
                inspection_type, overall_status, quality_score, safety_compliance,
                notes, recommendations, structural_integrity, workmanship_quality,
                code_compliance, site_cleanliness, follow_up_required, created_at
            ) VALUES (?, ?, ?, NOW(), 'milestone', 'approved', 5.0, 'compliant', ?, ?, 
                'excellent', 'excellent', 'compliant', 'excellent', 'no', NOW())");
            
            $stmt->execute([$projectId, $inspectorId, $stage['name'], $notes, $recommendations]);
            
            echo "✅ {$stage['name']}: APPROVED (⭐⭐⭐⭐⭐ 5/5)\n";
        }
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "STEP 5: CREATING CONTRACTOR DOCUMENTS\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $totalDocs = 0;
    foreach ($stages as $stage) {
        $documents = [
            [
                'type' => 'invoice',
                'name' => "Progress Photos - {$stage['name']}",
                'desc' => "Photographic documentation of {$stage['name']} stage progress and completion"
            ],
            [
                'type' => 'bill',
                'name' => "Material Bills - {$stage['name']}",
                'desc' => "Invoices and receipts for materials purchased for {$stage['name']} stage"
            ],
            [
                'type' => 'quality_report',
                'name' => "Quality Report - {$stage['name']}",
                'desc' => "Quality assurance report for {$stage['name']} stage"
            ]
        ];
        
        $docsCreated = 0;
        foreach ($documents as $doc) {
            // Check if document exists
            $stmt = $db->prepare("SELECT id FROM contractor_stage_documents 
                                 WHERE project_id = ? AND stage_name = ? AND document_type = ?");
            $stmt->execute([$projectId, $stage['name'], $doc['type']]);
            $existing = $stmt->fetch();
            
            if (!$existing) {
                $filePath = "uploads/project_37/{$stage['name']}/" . str_replace(' ', '_', $doc['type']) . ".pdf";
                
                $stmt = $db->prepare("INSERT INTO contractor_stage_documents (
                    project_id, contractor_id, stage_name, document_type,
                    file_path, original_filename, description,
                    upload_date, verification_status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'approved', NOW())");
                
                $filename = $doc['name'] . ".pdf";
                $stmt->execute([
                    $projectId, $contractorId, $stage['name'],
                    $doc['type'], $filePath, $filename, $doc['desc']
                ]);
                
                $docsCreated++;
                $totalDocs++;
            }
        }
        
        if ($docsCreated > 0) {
            echo "✅ {$stage['name']}: $docsCreated documents uploaded\n";
        }
    }
    
    echo "\n   📄 Total Documents: $totalDocs\n";
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "STEP 6: FINALIZING PROJECT STATUS\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    // Update project to completed
    $stmt = $db->prepare("UPDATE construction_projects SET 
        status = 'completed',
        current_stage = 'Final Inspection',
        completion_percentage = 100.00,
        actual_completion_date = NOW(),
        actual_end_date = NOW(),
        updated_at = NOW()
        WHERE id = ?");
    $stmt->execute([$projectId]);
    
    echo "✅ Project Status: COMPLETED\n";
    echo "✅ Completion Percentage: 100%\n";
    echo "✅ Current Stage: Final Inspection\n";
    echo "✅ Completion Date: " . date('M d, Y') . "\n";
    
    // Generate final statistics
    echo "\n╔══════════════════════════════════════════════════════════════╗\n";
    echo "║              FINAL COMPLETION SUMMARY                        ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    $stmt = $db->query("SELECT * FROM construction_projects WHERE id = $projectId");
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "📋 PROJECT DETAILS:\n";
    echo "   Name: {$project['project_name']}\n";
    echo "   Status: {$project['status']}\n";
    echo "   Completion: {$project['completion_percentage']}%\n";
    echo "   Current Stage: {$project['current_stage']}\n";
    echo "   Budget: ₹" . number_format($project['total_cost']) . "\n\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM construction_progress_updates WHERE project_id = $projectId");
    $stageCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM daily_progress_updates WHERE project_id = $projectId");
    $dailyCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count, SUM(requested_amount) as total 
                        FROM project_stage_payment_requests WHERE project_id = $projectId");
    $paymentData = $stmt->fetch();
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM inspection_reports WHERE project_id = $projectId");
    $inspectionCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM contractor_stage_documents WHERE project_id = $projectId");
    $documentCount = $stmt->fetch()['count'];
    
    echo "📊 COMPLETION STATISTICS:\n";
    echo "   ✅ Construction Stages: $stageCount (All 100% complete)\n";
    echo "   ✅ Daily Progress Reports: $dailyCount\n";
    echo "   ✅ Stage Payments: {$paymentData['count']} (₹" . number_format($paymentData['total']) . " paid)\n";
    echo "   ✅ Inspection Reports: $inspectionCount (All approved)\n";
    echo "   ✅ Contractor Documents: $documentCount\n\n";
    
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ PROJECT 37 FULLY COMPLETED - ALL FUNCTIONALITIES READY   ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    echo "🎯 YOU CAN NOW TEST:\n";
    echo "   1. ✅ Homeowner Dashboard - View complete project\n";
    echo "   2. ✅ Construction Progress - All 8 stages at 100%\n";
    echo "   3. ✅ Daily Reports - $dailyCount detailed progress reports\n";
    echo "   4. ✅ Payment History - All {$paymentData['count']} payments completed\n";
    echo "   5. ✅ Inspection Reports - All $inspectionCount stages approved\n";
    echo "   6. ✅ Document Management - $documentCount contractor documents\n";
    echo "   7. ✅ Project Timeline - Complete construction timeline\n";
    echo "   8. ✅ Financial Summary - Full payment breakdown\n\n";
    
    echo "🚀 Refresh your frontend (Project ID: 37) to see all changes!\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
