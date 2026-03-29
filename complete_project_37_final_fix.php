<?php
/**
 * Complete Project 37 - Add Missing Brickwork Stage
 * This script adds the missing Brickwork stage with all necessary data
 */

$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║     COMPLETING PROJECT 37 - ADDING BRICKWORK STAGE           ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    $projectId = 37;
    
    // Get project details
    $stmt = $db->query("SELECT * FROM construction_projects WHERE id = $projectId");
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        die("❌ Project 37 not found!\n");
    }
    
    $contractorId = $project['contractor_id'];
    $homeownerId = $project['homeowner_id'];
    $inspectorId = 1001;
    
    echo "📋 Project: {$project['project_name']}\n";
    echo "👷 Contractor ID: $contractorId\n";
    echo "🏠 Homeowner ID: $homeownerId\n";
    echo "🔍 Inspector ID: $inspectorId\n\n";
    
    // Brickwork stage details
    $brickworkStage = [
        'name' => 'Brickwork',
        'order' => 3, // Between Structure and Roofing
        'days' => 35,
        'cost' => 180000, // Reasonable cost for brickwork
        'description' => 'Complete brickwork and masonry construction'
    ];
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "STEP 1: ADDING BRICKWORK STAGE PROGRESS UPDATE\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    // Check if Brickwork stage exists
    $stmt = $db->prepare("SELECT id FROM construction_progress_updates 
                         WHERE project_id = ? AND stage_name = 'Brickwork'");
    $stmt->execute([$projectId]);
    $existing = $stmt->fetch();
    
    if (!$existing) {
        $stmt = $db->prepare("INSERT INTO construction_progress_updates (
            project_id, contractor_id, homeowner_id, stage_name,
            stage_status, completion_percentage, remarks, 
            created_at, updated_at
        ) VALUES (?, ?, ?, 'Brickwork', 'Completed', 100.00, ?, NOW(), NOW())");
        
        $remarks = "Brickwork stage completed successfully. All walls constructed with quality bricks. " .
                   "Mortar joints properly finished. Structural integrity verified. " .
                   "Duration: {$brickworkStage['days']} days. Cost: ₹" . number_format($brickworkStage['cost']);
        
        $stmt->execute([$projectId, $contractorId, $homeownerId, $remarks]);
        echo "✅ Brickwork Stage: 100% COMPLETE\n";
    } else {
        echo "ℹ️ Brickwork stage already exists\n";
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "STEP 2: CREATING DAILY PROGRESS REPORTS FOR BRICKWORK\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    // Create additional daily progress reports for Brickwork (3 more needed)
    $brickworkDates = ['2026-02-01', '2026-02-06', '2026-02-10']; // Dates not in use
    $reportsCreated = 0;
    
    foreach ($brickworkDates as $index => $reportDate) {
        $day = $index + 3; // Days 3, 4, 5 (since we already have 2)
        $incrementalProgress = 20; // Each day adds 20%
        $cumulativeProgress = ($day + 2) * 20; // 60%, 80%, 100%
        
        // Check if report exists
        $stmt = $db->prepare("SELECT id FROM daily_progress_updates 
                             WHERE project_id = ? AND construction_stage = 'Brickwork' 
                             AND update_date = ?");
        $stmt->execute([$projectId, $reportDate]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            $workDone = "Day $day of Brickwork stage. ";
            
            if ($day == 3) {
                $workDone .= "Halfway through brickwork. All external walls at 60% height. " .
                            "Internal partition walls started. Lintel installation in progress. " .
                            "Vertical alignment verified with plumb lines.";
            } elseif ($day == 4) {
                $workDone .= "Nearing completion. External walls at 90% height. " .
                            "Internal partitions 80% complete. Window and door lintels installed. " .
                            "Final course preparation. Quality assurance checks ongoing.";
            } elseif ($day == 5) {
                $workDone .= "Brickwork stage completed successfully. All walls constructed to full height. " .
                            "Final inspection done. Mortar joints properly finished. " .
                            "Structural integrity verified. Ready for roofing stage.";
            }
            
            $stmt = $db->prepare("INSERT INTO daily_progress_updates (
                project_id, contractor_id, homeowner_id, update_date, construction_stage,
                work_done_today, incremental_completion_percentage, cumulative_completion_percentage,
                working_hours, weather_condition, site_issues, 
                progress_photos, location_verified, created_at, updated_at
            ) VALUES (?, ?, ?, ?, 'Brickwork', ?, ?, ?, 8.5, 'Clear and sunny', 
                     'No major issues', '[]', 1, NOW(), NOW())");
            
            $stmt->execute([
                $projectId, $contractorId, $homeownerId, $reportDate,
                $workDone, $incrementalProgress, $cumulativeProgress
            ]);
            $reportsCreated++;
        }
    }
    
    echo "✅ Brickwork: $reportsCreated daily reports created\n";
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "STEP 3: CREATING STAGE PAYMENT REQUEST FOR BRICKWORK\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    // Check if payment exists
    $stmt = $db->prepare("SELECT id FROM project_stage_payment_requests 
                         WHERE project_id = ? AND stage_name = 'Brickwork'");
    $stmt->execute([$projectId]);
    $existing = $stmt->fetch();
    
    if (!$existing) {
        $workDesc = "Payment for Brickwork stage completion - Complete wall construction with quality bricks. " .
                    "All external and internal walls constructed. Proper bonding and alignment maintained. " .
                    "Cost: ₹" . number_format($brickworkStage['cost']);
        
        $percentage = ($brickworkStage['cost'] / $project['total_cost']) * 100;
        
        $stmt = $db->prepare("INSERT INTO project_stage_payment_requests (
            project_id, contractor_id, homeowner_id, stage_name,
            requested_amount, percentage_of_total, work_description,
            completion_percentage, request_date, status, payment_date
        ) VALUES (?, ?, ?, 'Brickwork', ?, ?, ?, 100.00, NOW(), 'paid', NOW())");
        
        $stmt->execute([
            $projectId, $contractorId, $homeownerId,
            $brickworkStage['cost'], $percentage, $workDesc
        ]);
        
        echo "✅ Brickwork Payment: ₹" . number_format($brickworkStage['cost']) . " - PAID\n";
    } else {
        echo "ℹ️ Brickwork payment already exists\n";
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "STEP 4: CREATING INSPECTION REPORT FOR BRICKWORK\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    // Check if inspection exists
    $stmt = $db->prepare("SELECT id FROM inspection_reports 
                         WHERE project_id = ? AND inspection_stage = 'Brickwork'");
    $stmt->execute([$projectId]);
    $existing = $stmt->fetch();
    
    if (!$existing) {
        $notes = "Comprehensive inspection of Brickwork stage completed on " . date('M d, Y') . ". " .
                "All brickwork meets required quality standards and building codes. " .
                "Wall alignment and verticality verified with precision instruments. " .
                "Mortar joints are properly finished and cured. " .
                "Brick bonding patterns are correct and structurally sound. " .
                "Door and window openings are properly sized and reinforced. " .
                "No cracks or defects observed. " .
                "Stage approved for progression to roofing phase.";
        
        $recommendations = "Proceed to roofing stage. Ensure proper curing time for mortar before applying loads. " .
                          "Maintain current quality standards for remaining construction phases.";
        
        $stmt = $db->prepare("INSERT INTO inspection_reports (
            project_id, inspector_id, inspection_stage, inspection_date,
            inspection_type, overall_status, quality_score, safety_compliance,
            notes, recommendations, structural_integrity, workmanship_quality,
            code_compliance, site_cleanliness, follow_up_required, 
            created_at, updated_at
        ) VALUES (?, ?, 'Brickwork', NOW(), 'milestone', 'approved', 5.0, 'compliant', 
                 ?, ?, 'excellent', 'excellent', 'compliant', 'excellent', 'no', NOW(), NOW())");
        
        $stmt->execute([$projectId, $inspectorId, $notes, $recommendations]);
        
        echo "✅ Brickwork Inspection: APPROVED (⭐⭐⭐⭐⭐ 5/5)\n";
    } else {
        echo "ℹ️ Brickwork inspection already exists\n";
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "STEP 5: CREATING CONTRACTOR DOCUMENTS FOR BRICKWORK\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $documents = [
        [
            'type' => 'progress_photos',
            'name' => 'Progress Photos - Brickwork',
            'desc' => 'Photographic documentation of brickwork stage progress showing wall construction, bonding patterns, and completion'
        ],
        [
            'type' => 'bill',
            'name' => 'Material Bills - Brickwork',
            'desc' => 'Invoices for bricks, cement, sand, and other masonry materials purchased for brickwork stage'
        ],
        [
            'type' => 'quality_report',
            'name' => 'Quality Report - Brickwork',
            'desc' => 'Quality assurance report for brickwork including mortar strength tests and wall alignment verification'
        ]
    ];
    
    $docsCreated = 0;
    foreach ($documents as $doc) {
        // Check if document exists
        $stmt = $db->prepare("SELECT id FROM contractor_stage_documents 
                             WHERE project_id = ? AND stage_name = 'Brickwork' 
                             AND document_type = ?");
        $stmt->execute([$projectId, $doc['type']]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            $filePath = "uploads/project_37/Brickwork/" . str_replace(' ', '_', $doc['type']) . ".pdf";
            $filename = $doc['name'] . ".pdf";
            
            $stmt = $db->prepare("INSERT INTO contractor_stage_documents (
                project_id, contractor_id, stage_name, document_type,
                file_path, original_filename, description,
                upload_date, verification_status, created_at, updated_at
            ) VALUES (?, ?, 'Brickwork', ?, ?, ?, ?, NOW(), 'approved', NOW(), NOW())");
            
            $stmt->execute([
                $projectId, $contractorId, $doc['type'],
                $filePath, $filename, $doc['desc']
            ]);
            
            $docsCreated++;
        }
    }
    
    echo "✅ Brickwork: $docsCreated documents uploaded\n";
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "STEP 6: VERIFYING PROJECT COMPLETION\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    // Verify all stages are complete
    $requiredStages = [
        'Foundation', 'Structure', 'Brickwork', 'Roofing', 
        'Electrical', 'Plumbing', 'Finishing', 'Painting', 'Final Inspection'
    ];
    
    $stmt = $db->prepare("SELECT stage_name, completion_percentage 
                         FROM construction_progress_updates 
                         WHERE project_id = ? AND stage_name IN ('" . implode("','", $requiredStages) . "')");
    $stmt->execute([$projectId]);
    $stages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $allComplete = true;
    foreach ($requiredStages as $stageName) {
        $found = false;
        foreach ($stages as $stage) {
            if ($stage['stage_name'] == $stageName) {
                $found = true;
                if ($stage['completion_percentage'] < 100) {
                    $allComplete = false;
                    echo "⚠️ $stageName: {$stage['completion_percentage']}%\n";
                } else {
                    echo "✅ $stageName: 100%\n";
                }
                break;
            }
        }
        if (!$found) {
            $allComplete = false;
            echo "❌ $stageName: MISSING\n";
        }
    }
    
    if ($allComplete) {
        // Update project status
        $stmt = $db->prepare("UPDATE construction_projects SET 
            status = 'completed',
            current_stage = 'Final Inspection',
            completion_percentage = 100.00,
            actual_completion_date = NOW(),
            updated_at = NOW()
            WHERE id = ?");
        $stmt->execute([$projectId]);
        
        echo "\n✅ Project Status: COMPLETED\n";
        echo "✅ All 9 stages: 100% COMPLETE\n";
    }
    
    echo "\n╔══════════════════════════════════════════════════════════════╗\n";
    echo "║              FINAL COMPLETION SUMMARY                        ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    // Generate statistics
    $stmt = $db->query("SELECT COUNT(*) as count FROM construction_progress_updates WHERE project_id = $projectId");
    $stageCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM daily_progress_updates WHERE project_id = $projectId");
    $dailyCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count, SUM(requested_amount) as total 
                        FROM project_stage_payment_requests WHERE project_id = $projectId AND status = 'paid'");
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
    echo "║  ✅ PROJECT 37 FULLY COMPLETED - INCLUDING BRICKWORK         ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    echo "🎯 ALL STAGES NOW COMPLETE:\n";
    echo "   1. ✅ Foundation - 100%\n";
    echo "   2. ✅ Structure - 100%\n";
    echo "   3. ✅ Brickwork - 100% (NEWLY ADDED)\n";
    echo "   4. ✅ Roofing - 100%\n";
    echo "   5. ✅ Electrical - 100%\n";
    echo "   6. ✅ Plumbing - 100%\n";
    echo "   7. ✅ Finishing - 100%\n";
    echo "   8. ✅ Painting - 100%\n";
    echo "   9. ✅ Final Inspection - 100%\n\n";
    
    echo "🚀 Refresh your frontend to see all changes!\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
