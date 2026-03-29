<?php
/**
 * Complete Construction Progress for Project 37
 * This script will:
 * 1. Create/verify project 37 exists
 * 2. Create all construction stages
 * 3. Add daily progress reports
 * 4. Create stage payments
 * 5. Add inspection reports
 * 6. Upload contractor documents
 * 7. Complete all stages to 100%
 */

$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== STARTING PROJECT 37 COMPLETE CONSTRUCTION SETUP ===\n\n";
    
    // Step 1: Check if project 37 exists in construction_projects
    $stmt = $db->query("SELECT * FROM construction_projects WHERE project_id = 37");
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo "Creating Project 37...\n";
        $db->exec("INSERT INTO construction_projects (
            project_id, project_name, homeowner_id, contractor_id, 
            status, start_date, end_date, budget, location, description, created_at
        ) VALUES (
            37, 'Complete Construction Demo Project', 32, 29,
            'in_progress', '2026-01-01', '2026-12-31', 5000000.00,
            'Kochi, Kerala', 'Full construction project with all stages completed', NOW()
        )");
        echo "✓ Project 37 created\n\n";
    } else {
        echo "✓ Project 37 already exists\n\n";
    }
    
    // Step 2: Create Construction Stages
    echo "=== CREATING CONSTRUCTION STAGES ===\n\n";
    
    $stages = [
        ['name' => 'Foundation', 'order' => 1, 'duration' => 30, 'budget' => 1000000],
        ['name' => 'Structure', 'order' => 2, 'duration' => 45, 'budget' => 1500000],
        ['name' => 'Roofing', 'order' => 3, 'duration' => 20, 'budget' => 500000],
        ['name' => 'Electrical', 'order' => 4, 'duration' => 25, 'budget' => 400000],
        ['name' => 'Plumbing', 'order' => 5, 'duration' => 25, 'budget' => 400000],
        ['name' => 'Finishing', 'order' => 6, 'duration' => 40, 'budget' => 800000],
        ['name' => 'Painting', 'order' => 7, 'duration' => 15, 'budget' => 200000],
        ['name' => 'Final Inspection', 'order' => 8, 'duration' => 5, 'budget' => 200000]
    ];
    
    foreach ($stages as $stage) {
        // Check if stage exists
        $stmt = $db->prepare("SELECT stage_id FROM construction_phases WHERE project_id = 37 AND stage_name = ?");
        $stmt->execute([$stage['name']]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            $startDate = date('Y-m-d', strtotime('2026-01-01 + ' . (($stage['order'] - 1) * 30) . ' days'));
            $endDate = date('Y-m-d', strtotime($startDate . ' + ' . $stage['duration'] . ' days'));
            
            $db->exec("INSERT INTO construction_phases (
                project_id, stage_name, stage_order, status, progress_percentage,
                start_date, end_date, budget_allocated, created_at
            ) VALUES (
                37, '{$stage['name']}', {$stage['order']}, 'completed', 100.00,
                '$startDate', '$endDate', {$stage['budget']}, NOW()
            )");
            echo "✓ Created stage: {$stage['name']}\n";
        } else {
            // Update to completed
            $db->exec("UPDATE construction_phases SET 
                status = 'completed', 
                progress_percentage = 100.00,
                updated_at = NOW()
                WHERE project_id = 37 AND stage_name = '{$stage['name']}'");
            echo "✓ Updated stage: {$stage['name']} to 100%\n";
        }
    }
    
    echo "\n=== CREATING DAILY PROGRESS REPORTS ===\n\n";
    
    // Create daily progress for each stage
    foreach ($stages as $stage) {
        // Get stage_id
        $stmt = $db->prepare("SELECT stage_id FROM construction_phases WHERE project_id = 37 AND stage_name = ?");
        $stmt->execute([$stage['name']]);
        $stageData = $stmt->fetch();
        
        if ($stageData) {
            $stageId = $stageData['stage_id'];
            
            // Create 5 daily reports per stage
            for ($i = 1; $i <= 5; $i++) {
                $progress = $i * 20; // 20%, 40%, 60%, 80%, 100%
                $reportDate = date('Y-m-d', strtotime('2026-01-01 + ' . (($stage['order'] - 1) * 30 + $i * 5) . ' days'));
                
                // Check if report exists
                $stmt = $db->prepare("SELECT id FROM daily_progress_updates WHERE project_id = 37 AND stage_id = ? AND report_date = ?");
                $stmt->execute([$stageId, $reportDate]);
                $existingReport = $stmt->fetch();
                
                if (!$existingReport) {
                    $db->exec("INSERT INTO daily_progress_updates (
                        project_id, stage_id, contractor_id, report_date,
                        progress_percentage, work_description, workers_present,
                        weather_conditions, issues_faced, created_at
                    ) VALUES (
                        37, $stageId, 29, '$reportDate',
                        $progress, 'Day $i progress for {$stage['name']} stage', 10,
                        'Clear', 'None', NOW()
                    )");
                }
            }
            echo "✓ Created 5 daily reports for {$stage['name']}\n";
        }
    }
    
    echo "\n=== CREATING STAGE PAYMENTS ===\n\n";
    
    foreach ($stages as $stage) {
        // Check if payment exists
        $stmt = $db->prepare("SELECT id FROM construction_stage_payments WHERE project_id = 37 AND stage_name = ?");
        $stmt->execute([$stage['name']]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            $db->exec("INSERT INTO construction_stage_payments (
                project_id, stage_name, stage_order, amount,
                payment_status, is_paid, paid_date, created_at
            ) VALUES (
                37, '{$stage['name']}', {$stage['order']}, {$stage['budget']},
                'completed', 1, NOW(), NOW()
            )");
            echo "✓ Created payment for {$stage['name']}: ₹" . number_format($stage['budget']) . "\n";
        } else {
            $db->exec("UPDATE construction_stage_payments SET 
                payment_status = 'completed',
                is_paid = 1,
                paid_date = NOW()
                WHERE project_id = 37 AND stage_name = '{$stage['name']}'");
            echo "✓ Updated payment for {$stage['name']}\n";
        }
    }
    
    echo "\n=== CREATING INSPECTION REPORTS ===\n\n";
    
    foreach ($stages as $stage) {
        // Check if inspection exists
        $stmt = $db->prepare("SELECT id FROM inspection_reports WHERE project_id = 37 AND stage_name = ?");
        $stmt->execute([$stage['name']]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            $db->exec("INSERT INTO inspection_reports (
                project_id, inspector_id, stage_name, inspection_date,
                overall_status, quality_rating, safety_rating,
                compliance_rating, inspector_notes, created_at
            ) VALUES (
                37, 1001, '{$stage['name']}', NOW(),
                'approved', 5, 5, 5,
                'Stage {$stage['name']} completed successfully. All quality standards met.', NOW()
            )");
            echo "✓ Created inspection for {$stage['name']}\n";
        }
    }
    
    echo "\n=== CREATING CONTRACTOR DOCUMENTS ===\n\n";
    
    foreach ($stages as $stage) {
        // Get stage_id
        $stmt = $db->prepare("SELECT stage_id FROM construction_phases WHERE project_id = 37 AND stage_name = ?");
        $stmt->execute([$stage['name']]);
        $stageData = $stmt->fetch();
        
        if ($stageData) {
            $stageId = $stageData['stage_id'];
            
            // Check if documents exist
            $stmt = $db->prepare("SELECT id FROM contractor_stage_documents WHERE project_id = 37 AND stage_id = ?");
            $stmt->execute([$stageId]);
            $existing = $stmt->fetch();
            
            if (!$existing) {
                $documents = [
                    ['type' => 'progress_photo', 'name' => 'Progress Photo 1'],
                    ['type' => 'material_bill', 'name' => 'Material Bill'],
                    ['type' => 'completion_certificate', 'name' => 'Completion Certificate']
                ];
                
                foreach ($documents as $doc) {
                    $db->exec("INSERT INTO contractor_stage_documents (
                        project_id, stage_id, contractor_id, document_type,
                        document_name, file_path, upload_date, status, created_at
                    ) VALUES (
                        37, $stageId, 29, '{$doc['type']}',
                        '{$doc['name']}', 'uploads/project_37/stage_{$stageId}/{$doc['type']}.pdf',
                        NOW(), 'approved', NOW()
                    )");
                }
                echo "✓ Created 3 documents for {$stage['name']}\n";
            }
        }
    }
    
    echo "\n=== UPDATING PROJECT STATUS ===\n\n";
    
    // Update project to completed
    $db->exec("UPDATE construction_projects SET 
        status = 'completed',
        completion_date = NOW(),
        updated_at = NOW()
        WHERE project_id = 37");
    
    echo "✓ Project 37 marked as completed\n\n";
    
    // Final Summary
    echo "=== FINAL SUMMARY ===\n\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM construction_phases WHERE project_id = 37");
    $stageCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM daily_progress_updates WHERE project_id = 37");
    $progressCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM construction_stage_payments WHERE project_id = 37");
    $paymentCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM inspection_reports WHERE project_id = 37");
    $inspectionCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM contractor_stage_documents WHERE project_id = 37");
    $documentCount = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT SUM(budget_allocated) as total FROM construction_phases WHERE project_id = 37");
    $totalBudget = $stmt->fetch()['total'];
    
    echo "Construction Stages: $stageCount (All 100% complete)\n";
    echo "Daily Progress Reports: $progressCount\n";
    echo "Stage Payments: $paymentCount (All paid)\n";
    echo "Inspection Reports: $inspectionCount (All approved)\n";
    echo "Contractor Documents: $documentCount\n";
    echo "Total Budget: ₹" . number_format($totalBudget) . "\n\n";
    
    echo "✅ PROJECT 37 CONSTRUCTION FULLY COMPLETED!\n\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
