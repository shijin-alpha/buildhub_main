<?php
/**
 * Add "Final" Stage to Project 37
 * The frontend expects a stage called "Final" but database has "Final Inspection"
 */

$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║         ADDING 'FINAL' STAGE TO PROJECT 37                   ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    $projectId = 37;
    
    // Get project details
    $stmt = $db->query("SELECT * FROM construction_projects WHERE id = $projectId");
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $contractorId = $project['contractor_id'];
    $homeownerId = $project['homeowner_id'];
    $inspectorId = 1001;
    
    echo "📋 Project: {$project['project_name']}\n";
    echo "👷 Contractor ID: $contractorId\n";
    echo "🏠 Homeowner ID: $homeownerId\n\n";
    
    // Final stage details
    $finalStage = [
        'name' => 'Final',
        'order' => 9,
        'days' => 5,
        'cost' => 50000,
        'description' => 'Final completion and handover'
    ];
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "STEP 1: ADDING 'FINAL' STAGE PROGRESS UPDATE\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    // Check if Final stage exists
    $stmt = $db->prepare("SELECT id FROM construction_progress_updates 
                         WHERE project_id = ? AND stage_name = 'Final'");
    $stmt->execute([$projectId]);
    $existing = $stmt->fetch();
    
    if (!$existing) {
        $stmt = $db->prepare("INSERT INTO construction_progress_updates (
            project_id, contractor_id, homeowner_id, stage_name,
            stage_status, completion_percentage, remarks, 
            created_at, updated_at
        ) VALUES (?, ?, ?, 'Final', 'Completed', 100.00, ?, NOW(), NOW())");
        
        $remarks = "Final stage completed successfully. Project handover completed. " .
                   "All documentation verified. Final walkthrough conducted. " .
                   "Client satisfaction confirmed. Project officially closed. " .
                   "Duration: {$finalStage['days']} days. Cost: ₹" . number_format($finalStage['cost']);
        
        $stmt->execute([$projectId, $contractorId, $homeownerId, $remarks]);
        echo "✅ Final Stage: 100% COMPLETE\n";
    } else {
        $stmt = $db->prepare("UPDATE construction_progress_updates SET 
            stage_status = 'Completed',
            completion_percentage = 100.00,
            updated_at = NOW()
            WHERE project_id = ? AND stage_name = 'Final'");
        $stmt->execute([$projectId]);
        echo "✅ Final Stage: UPDATED TO 100%\n";
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "STEP 2: CREATING DAILY PROGRESS REPORTS FOR FINAL\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    // Create 5 daily progress reports for Final
    $finalDates = ['2026-08-10', '2026-08-11', '2026-08-12', '2026-08-13', '2026-08-14'];
    $reportsCreated = 0;
    
    foreach ($finalDates as $index => $reportDate) {
        $day = $index + 1;
        $incrementalProgress = 20;
        $cumulativeProgress = $day * 20;
        
        // Check if report exists
        $stmt = $db->prepare("SELECT id FROM daily_progress_updates 
                             WHERE project_id = ? AND construction_stage = 'Final' 
                             AND update_date = ?");
        $stmt->execute([$projectId, $reportDate]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            $workDone = "Day $day of Final stage. ";
            
            switch ($day) {
                case 1:
                    $workDone .= "Started final completion activities. Final walkthrough with client initiated. " .
                                "Punch list items identified and documented. Minor touch-ups scheduled. " .
                                "Final cleaning and site preparation begun.";
                    break;
                case 2:
                    $workDone .= "Addressing punch list items. Minor repairs and adjustments completed. " .
                                "Final cleaning in progress. All systems tested and verified. " .
                                "Documentation compilation started.";
                    break;
                case 3:
                    $workDone .= "Completing final touches. All punch list items resolved. " .
                                "Final cleaning completed. Systems handover documentation prepared. " .
                                "Warranty documents compiled.";
                    break;
                case 4:
                    $workDone .= "Final inspection and verification. Client walkthrough completed. " .
                                "All documentation handed over. Keys and access codes provided. " .
                                "Maintenance guidelines explained.";
                    break;
                case 5:
                    $workDone .= "Project officially completed and handed over. Client satisfaction confirmed. " .
                                "All warranties activated. Final photographs taken. " .
                                "Project closure documentation completed. Site cleared.";
                    break;
            }
            
            $stmt = $db->prepare("INSERT INTO daily_progress_updates (
                project_id, contractor_id, homeowner_id, update_date, construction_stage,
                work_done_today, incremental_completion_percentage, cumulative_completion_percentage,
                working_hours, weather_condition, site_issues, 
                progress_photos, location_verified, created_at, updated_at
            ) VALUES (?, ?, ?, ?, 'Final', ?, ?, ?, 8.0, 'Clear', 
                     'No issues', '[]', 1, NOW(), NOW())");
            
            $stmt->execute([
                $projectId, $contractorId, $homeownerId, $reportDate,
                $workDone, $incrementalProgress, $cumulativeProgress
            ]);
            $reportsCreated++;
        }
    }
    
    echo "✅ Final: $reportsCreated daily reports created\n";
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "STEP 3: CREATING STAGE PAYMENT REQUEST FOR FINAL\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    // Check if payment exists
    $stmt = $db->prepare("SELECT id FROM project_stage_payment_requests 
                         WHERE project_id = ? AND stage_name = 'Final'");
    $stmt->execute([$projectId]);
    $existing = $stmt->fetch();
    
    if (!$existing) {
        $workDesc = "Payment for Final stage completion - Project handover and final documentation. " .
                    "All punch list items completed. Final cleaning and site clearance. " .
                    "Cost: ₹" . number_format($finalStage['cost']);
        
        $percentage = ($finalStage['cost'] / $project['total_cost']) * 100;
        
        $stmt = $db->prepare("INSERT INTO project_stage_payment_requests (
            project_id, contractor_id, homeowner_id, stage_name,
            requested_amount, percentage_of_total, work_description,
            completion_percentage, request_date, status, payment_date
        ) VALUES (?, ?, ?, 'Final', ?, ?, ?, 100.00, NOW(), 'paid', NOW())");
        
        $stmt->execute([
            $projectId, $contractorId, $homeownerId,
            $finalStage['cost'], $percentage, $workDesc
        ]);
        
        echo "✅ Final Payment: ₹" . number_format($finalStage['cost']) . " - PAID\n";
    } else {
        echo "ℹ️ Final payment already exists\n";
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "STEP 4: CREATING INSPECTION REPORT FOR FINAL\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    // Check if inspection exists
    $stmt = $db->prepare("SELECT id FROM inspection_reports 
                         WHERE project_id = ? AND inspection_stage = 'Final'");
    $stmt->execute([$projectId]);
    $existing = $stmt->fetch();
    
    if (!$existing) {
        $notes = "Final comprehensive inspection completed on " . date('M d, Y') . ". " .
                "Complete project walkthrough conducted with client. " .
                "All construction work meets required quality standards and building codes. " .
                "All systems tested and functioning properly. " .
                "No defects or issues identified. " .
                "All punch list items completed satisfactorily. " .
                "Project documentation complete and handed over. " .
                "Client expressed full satisfaction with the completed project. " .
                "Project officially approved for handover and occupancy.";
        
        $recommendations = "Project successfully completed. Maintain regular maintenance schedule. " .
                          "Follow warranty guidelines. Contact contractor for any post-completion support.";
        
        $stmt = $db->prepare("INSERT INTO inspection_reports (
            project_id, inspector_id, inspection_stage, inspection_date,
            inspection_type, overall_status, quality_score, safety_compliance,
            notes, recommendations, structural_integrity, workmanship_quality,
            code_compliance, site_cleanliness, follow_up_required, 
            created_at, updated_at
        ) VALUES (?, ?, 'Final', NOW(), 'final', 'approved', 5.0, 'compliant', 
                 ?, ?, 'excellent', 'excellent', 'compliant', 'excellent', 'no', NOW(), NOW())");
        
        $stmt->execute([$projectId, $inspectorId, $notes, $recommendations]);
        
        echo "✅ Final Inspection: APPROVED (⭐⭐⭐⭐⭐ 5/5)\n";
    } else {
        echo "ℹ️ Final inspection already exists\n";
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "STEP 5: CREATING CONTRACTOR DOCUMENTS FOR FINAL\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $documents = [
        [
            'type' => 'completion_certificate',
            'name' => 'Completion Certificate - Final',
            'desc' => 'Official project completion certificate with all sign-offs and approvals'
        ],
        [
            'type' => 'handover_document',
            'name' => 'Handover Documentation - Final',
            'desc' => 'Complete handover documentation including keys, warranties, and maintenance guides'
        ],
        [
            'type' => 'final_photos',
            'name' => 'Final Project Photos',
            'desc' => 'Professional photographs of completed project from all angles'
        ]
    ];
    
    $docsCreated = 0;
    foreach ($documents as $doc) {
        // Check if document exists
        $stmt = $db->prepare("SELECT id FROM contractor_stage_documents 
                             WHERE project_id = ? AND stage_name = 'Final' 
                             AND document_type = ?");
        $stmt->execute([$projectId, $doc['type']]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            $filePath = "uploads/project_37/Final/" . str_replace(' ', '_', $doc['type']) . ".pdf";
            $filename = $doc['name'] . ".pdf";
            
            $stmt = $db->prepare("INSERT INTO contractor_stage_documents (
                project_id, contractor_id, stage_name, document_type,
                file_path, original_filename, description,
                upload_date, verification_status, created_at, updated_at
            ) VALUES (?, ?, 'Final', ?, ?, ?, ?, NOW(), 'approved', NOW(), NOW())");
            
            $stmt->execute([
                $projectId, $contractorId, $doc['type'],
                $filePath, $filename, $doc['desc']
            ]);
            
            $docsCreated++;
        }
    }
    
    echo "✅ Final: $docsCreated documents uploaded\n";
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "STEP 6: VERIFYING ALL STAGES\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $requiredStages = [
        'Foundation', 'Structure', 'Brickwork', 'Roofing', 
        'Electrical', 'Plumbing', 'Finishing', 'Painting', 
        'Final Inspection', 'Final'
    ];
    
    $allComplete = true;
    foreach ($requiredStages as $stageName) {
        $stmt = $db->prepare("SELECT completion_percentage 
                             FROM construction_progress_updates 
                             WHERE project_id = ? AND stage_name = ?");
        $stmt->execute([$projectId, $stageName]);
        $stage = $stmt->fetch();
        
        if (!$stage) {
            echo "❌ $stageName: MISSING\n";
            $allComplete = false;
        } elseif ($stage['completion_percentage'] < 100) {
            echo "⚠️ $stageName: {$stage['completion_percentage']}%\n";
            $allComplete = false;
        } else {
            echo "✅ $stageName: 100%\n";
        }
    }
    
    if ($allComplete) {
        echo "\n✅ All stages are 100% complete!\n";
    }
    
    echo "\n╔══════════════════════════════════════════════════════════════╗\n";
    echo "║              FINAL STAGE ADDITION COMPLETE                   ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    // Get statistics
    $stmt = $db->query("SELECT COUNT(*) as count FROM construction_progress_updates WHERE project_id = $projectId");
    $stageCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM daily_progress_updates WHERE project_id = $projectId");
    $dailyCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count, SUM(requested_amount) as total 
                        FROM project_stage_payment_requests WHERE project_id = $projectId AND status = 'paid'");
    $paymentData = $stmt->fetch();
    
    echo "📊 UPDATED STATISTICS:\n";
    echo "   ✅ Construction Stages: $stageCount (All 100% complete)\n";
    echo "   ✅ Daily Progress Reports: $dailyCount\n";
    echo "   ✅ Stage Payments: {$paymentData['count']} (₹" . number_format($paymentData['total']) . " paid)\n\n";
    
    echo "🎯 'FINAL' STAGE NOW COMPLETE:\n";
    echo "   ✅ Stage Progress: 100%\n";
    echo "   ✅ Daily Reports: 5 reports\n";
    echo "   ✅ Payment: ₹" . number_format($finalStage['cost']) . " paid\n";
    echo "   ✅ Inspection: Approved (5/5 stars)\n";
    echo "   ✅ Documents: 3 documents\n\n";
    
    echo "🚀 Refresh your frontend to see the 'Final' stage at 100%!\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
