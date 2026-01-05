<?php
/**
 * BuildHub Integrated Workflow Setup
 * 
 * This script sets up the complete integrated workflow system that connects:
 * - House Plan Designer
 * - Geo-Tagged Photos
 * - Progress Reports
 * - Project Management
 * - Notification System
 */

header('Content-Type: application/json');
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🚀 Setting up BuildHub Integrated Workflow System...\n\n";
    
    // 1. Execute the integrated workflow database schema
    echo "📊 Creating integrated workflow database schema...\n";
    $schema_sql = file_get_contents(__DIR__ . '/database/create_integrated_workflow_tables.sql');
    
    // Split and execute SQL statements
    $statements = array_filter(array_map('trim', explode(';', $schema_sql)));
    $executed = 0;
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(--|\/\*|\s*$)/', $statement)) {
            try {
                $db->exec($statement);
                $executed++;
            } catch (Exception $e) {
                // Log but continue for non-critical errors
                error_log("SQL execution warning: " . $e->getMessage());
            }
        }
    }
    
    echo "✅ Executed {$executed} database statements\n\n";
    
    // 2. Verify core tables exist
    echo "🔍 Verifying core tables...\n";
    $required_tables = [
        'projects',
        'project_milestones', 
        'project_progress_reports',
        'workflow_notifications',
        'feature_integrations'
    ];
    
    foreach ($required_tables as $table) {
        $check = $db->query("SHOW TABLES LIKE '{$table}'");
        if ($check->rowCount() > 0) {
            echo "✅ Table '{$table}' exists\n";
        } else {
            echo "❌ Table '{$table}' missing\n";
        }
    }
    
    // 3. Update existing layout_requests for integration
    echo "\n🔄 Updating existing layout requests for integration...\n";
    
    // Add integration columns if they don't exist
    $integration_columns = [
        'requires_house_plan' => 'BOOLEAN DEFAULT TRUE',
        'enable_progress_tracking' => 'BOOLEAN DEFAULT TRUE', 
        'enable_geo_photos' => 'BOOLEAN DEFAULT TRUE',
        'house_plan_requirements' => 'JSON NULL',
        'workflow_status' => "ENUM('pending', 'design_phase', 'approval_phase', 'construction_phase', 'completed') DEFAULT 'pending'",
        'integration_features' => 'JSON NULL'
    ];
    
    foreach ($integration_columns as $column => $definition) {
        try {
            $db->exec("ALTER TABLE layout_requests ADD COLUMN IF NOT EXISTS {$column} {$definition}");
            echo "✅ Added column '{$column}' to layout_requests\n";
        } catch (Exception $e) {
            echo "ℹ️ Column '{$column}' already exists or error: " . $e->getMessage() . "\n";
        }
    }
    
    // 4. Create projects for existing layout requests
    echo "\n📋 Creating projects for existing layout requests...\n";
    
    $existing_requests = $db->query("
        SELECT lr.*, u.name as homeowner_name 
        FROM layout_requests lr 
        LEFT JOIN users u ON lr.homeowner_id = u.id 
        WHERE lr.id NOT IN (SELECT COALESCE(layout_request_id, 0) FROM projects WHERE layout_request_id IS NOT NULL)
        LIMIT 50
    ");
    
    $projects_created = 0;
    while ($request = $existing_requests->fetch(PDO::FETCH_ASSOC)) {
        try {
            // Create project for existing request
            $project_name = "Construction Project - " . $request['plot_size'] . 
                           ($request['location'] ? " (" . $request['location'] . ")" : "");
            
            $project_query = "INSERT INTO projects (
                                homeowner_id, layout_request_id, project_name, status,
                                enable_house_plans, enable_geo_photos, enable_progress_reports,
                                created_at
                              ) VALUES (?, ?, ?, 'planning', 1, 1, 1, ?)";
            
            $stmt = $db->prepare($project_query);
            $stmt->execute([
                $request['homeowner_id'],
                $request['id'],
                $project_name,
                $request['created_at']
            ]);
            
            $project_id = $db->lastInsertId();
            
            // Create default milestones
            $milestones = [
                ['Requirements Review', 'planning', 1],
                ['House Plan Design', 'design', 2],
                ['Technical Approval', 'approval', 3],
                ['Construction Start', 'construction', 4],
                ['Progress Tracking', 'construction', 5],
                ['Final Completion', 'completion', 6]
            ];
            
            foreach ($milestones as $milestone) {
                $milestone_query = "INSERT INTO project_milestones (
                                      project_id, milestone_name, phase, order_sequence, 
                                      status, created_at
                                    ) VALUES (?, ?, ?, ?, 'pending', ?)";
                
                $milestone_stmt = $db->prepare($milestone_query);
                $milestone_stmt->execute([
                    $project_id,
                    $milestone[0],
                    $milestone[1], 
                    $milestone[2],
                    $request['created_at']
                ]);
            }
            
            // Enable feature integrations
            $features = ['house_plan_designer', 'geo_tagged_photos', 'progress_reports'];
            foreach ($features as $feature) {
                $feature_query = "INSERT INTO feature_integrations (
                                    project_id, feature_name, is_enabled, configuration
                                  ) VALUES (?, ?, 1, ?)";
                
                $config = json_encode([
                    'auto_enabled' => true,
                    'setup_date' => date('Y-m-d H:i:s'),
                    'version' => '1.0'
                ]);
                
                $feature_stmt = $db->prepare($feature_query);
                $feature_stmt->execute([$project_id, $feature, $config]);
            }
            
            $projects_created++;
            
        } catch (Exception $e) {
            echo "⚠️ Error creating project for request {$request['id']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "✅ Created {$projects_created} projects from existing requests\n\n";
    
    // 5. Link existing house plans to projects
    echo "🏠 Linking existing house plans to projects...\n";
    
    $house_plans_linked = $db->exec("
        UPDATE house_plans hp
        JOIN projects p ON hp.layout_request_id = p.layout_request_id
        SET hp.project_id = p.id
        WHERE hp.project_id IS NULL AND p.layout_request_id IS NOT NULL
    ");
    
    echo "✅ Linked {$house_plans_linked} house plans to projects\n";
    
    // 6. Link existing geo photos to projects
    echo "📍 Linking existing geo photos to projects...\n";
    
    $geo_photos_linked = $db->exec("
        UPDATE geo_photos gp
        JOIN projects p ON gp.layout_request_id = p.layout_request_id
        SET gp.project_id = p.id
        WHERE gp.project_id IS NULL AND p.layout_request_id IS NOT NULL
    ");
    
    echo "✅ Linked {$geo_photos_linked} geo photos to projects\n";
    
    // 7. Update project statistics
    echo "\n📊 Updating project statistics...\n";
    
    // Update house plan counts
    $db->exec("
        UPDATE projects p
        SET active_house_plan_id = (
            SELECT hp.id FROM house_plans hp 
            WHERE hp.project_id = p.id 
            ORDER BY hp.created_at DESC LIMIT 1
        )
        WHERE p.enable_house_plans = 1
    ");
    
    // Update geo photo counts
    $db->exec("
        UPDATE projects p
        SET total_geo_photos = (
            SELECT COUNT(*) FROM geo_photos gp 
            WHERE gp.project_id = p.id
        )
        WHERE p.enable_geo_photos = 1
    ");
    
    echo "✅ Updated project statistics\n";
    
    // 8. Create sample workflow notifications
    echo "\n🔔 Creating sample workflow notifications...\n";
    
    $sample_notifications = $db->exec("
        INSERT INTO workflow_notifications (
            project_id, user_id, notification_type, title, message, 
            action_required, created_at
        )
        SELECT 
            p.id,
            p.homeowner_id,
            'milestone_completed',
            'Welcome to Integrated BuildHub Workflow',
            CONCAT('Your project \"', p.project_name, '\" has been set up with integrated features including House Plan Designer, Geo-Tagged Photos, and Progress Reports. You can now track your construction journey with enhanced visibility and control.'),
            false,
            NOW()
        FROM projects p
        WHERE p.id NOT IN (SELECT DISTINCT project_id FROM workflow_notifications)
        LIMIT 10
    ");
    
    echo "✅ Created {$sample_notifications} welcome notifications\n";
    
    // 9. Verify integration setup
    echo "\n🔍 Verifying integration setup...\n";
    
    $stats = $db->query("
        SELECT 
            COUNT(DISTINCT p.id) as total_projects,
            COUNT(DISTINCT hp.id) as total_house_plans,
            COUNT(DISTINCT gp.id) as total_geo_photos,
            COUNT(DISTINCT pm.id) as total_milestones,
            COUNT(DISTINCT fi.id) as total_feature_integrations,
            COUNT(DISTINCT wn.id) as total_notifications
        FROM projects p
        LEFT JOIN house_plans hp ON p.id = hp.project_id
        LEFT JOIN geo_photos gp ON p.id = gp.project_id  
        LEFT JOIN project_milestones pm ON p.id = pm.project_id
        LEFT JOIN feature_integrations fi ON p.id = fi.project_id
        LEFT JOIN workflow_notifications wn ON p.id = wn.project_id
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "📊 Integration Statistics:\n";
    echo "   • Projects: {$stats['total_projects']}\n";
    echo "   • House Plans: {$stats['total_house_plans']}\n";
    echo "   • Geo Photos: {$stats['total_geo_photos']}\n";
    echo "   • Milestones: {$stats['total_milestones']}\n";
    echo "   • Feature Integrations: {$stats['total_feature_integrations']}\n";
    echo "   • Notifications: {$stats['total_notifications']}\n\n";
    
    // 10. Create API endpoints documentation
    echo "📚 Creating API endpoints documentation...\n";
    
    $api_docs = [
        'Enhanced Request Submission' => '/buildhub/backend/api/homeowner/submit_enhanced_request.php',
        'Project Overview' => '/buildhub/backend/api/project/get_project_overview.php',
        'Integrated House Plans' => '/buildhub/backend/api/architect/create_integrated_house_plan.php',
        'Workflow Progress Reports' => '/buildhub/backend/api/contractor/submit_integrated_progress_report.php',
        'Project Milestones' => '/buildhub/backend/api/project/update_milestone.php',
        'Feature Integration Status' => '/buildhub/backend/api/project/get_feature_status.php'
    ];
    
    echo "🔗 Available API Endpoints:\n";
    foreach ($api_docs as $name => $endpoint) {
        echo "   • {$name}: {$endpoint}\n";
    }
    
    echo "\n✅ BuildHub Integrated Workflow Setup Complete!\n\n";
    
    echo "🎯 Next Steps:\n";
    echo "1. Test the enhanced request submission flow\n";
    echo "2. Verify house plan designer integration\n";
    echo "3. Test geo-tagged photo workflow\n";
    echo "4. Validate progress report integration\n";
    echo "5. Check notification system functionality\n\n";
    
    echo "🚀 The integrated workflow is now ready to use!\n";
    echo "   Homeowners can create requests that automatically:\n";
    echo "   • Trigger house plan design workflows\n";
    echo "   • Enable geo-tagged photo documentation\n";
    echo "   • Set up progress report tracking\n";
    echo "   • Create project milestones and notifications\n\n";
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Integrated workflow setup completed successfully',
        'statistics' => $stats,
        'features_enabled' => [
            'house_plan_designer_integration' => true,
            'geo_tagged_photos_integration' => true,
            'progress_reports_integration' => true,
            'project_milestone_tracking' => true,
            'workflow_notifications' => true
        ]
    ]);
    
} catch (Exception $e) {
    echo "❌ Setup Error: " . $e->getMessage() . "\n";
    echo json_encode([
        'success' => false,
        'message' => 'Setup failed: ' . $e->getMessage()
    ]);
}
?>