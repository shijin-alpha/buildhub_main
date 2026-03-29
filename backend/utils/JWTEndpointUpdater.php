<?php
/**
 * JWT Endpoint Updater
 * Utility to add JWT authentication to existing API endpoints
 */

class JWTEndpointUpdater {
    
    private $endpointMappings = [
        // Admin endpoints
        'admin' => [
            'middleware' => 'requireFullAdmin',
            'endpoints' => [
                'add_material.php',
                'update_user_status.php',
                'assign_site_inspector.php',
                'get_site_inspectors.php',
                'get_pending_users.php',
                'get_all_users.php',
                'admin_reply.php',
                'admin_verify.php',
                'delete_material.php',
                'get_materials.php'
            ]
        ],
        
        // Homeowner endpoints
        'homeowner' => [
            'middleware' => 'requireHomeowner',
            'endpoints' => [
                'submit_request.php',
                'submit_enhanced_request.php',
                'get_my_requests.php',
                'get_my_projects.php',
                'get_my_designs.php',
                'get_received_designs.php',
                'update_design_selection.php',
                'send_house_plan_to_contractor.php',
                'review_house_plan.php',
                'create_house_plan.php',
                'submit_house_plan.php',
                'get_house_plans.php',
                'delete_house_plan.php',
                'upload_payment_receipt.php',
                'verify_stage_payment.php',
                'verify_technical_details_payment.php',
                'verify_custom_payment.php',
                'verify_split_payment.php',
                'respond_payment_request.php',
                'respond_to_custom_payment.php',
                'get_payment_requests.php',
                'get_payment_history.php',
                'submit_validation_feedback.php',
                'view_progress_report.php',
                'get_progress_reports.php'
            ]
        ],
        
        // Contractor endpoints
        'contractor' => [
            'middleware' => 'requireContractor',
            'endpoints' => [
                'get_contractor_projects.php',
                'get_contractor_requests.php',
                'get_assigned_requests.php',
                'submit_estimate.php',
                'get_estimates.php',
                'delete_estimate.php',
                'save_estimate_draft.php',
                'submit_proposal.php',
                'get_my_proposals.php',
                'respond_to_estimate.php',
                'submit_stage_completion.php',
                'get_project_stage_workflow.php',
                'get_stage_workflow_projects.php',
                'submit_daily_progress.php',
                'submit_progress_update.php',
                'submit_enhanced_progress_update.php',
                'get_progress_updates.php',
                'submit_stage_payment_request.php',
                'submit_enhanced_stage_payment_request.php',
                'get_payment_requests.php',
                'initiate_stage_payment.php',
                'initiate_technical_details_payment.php',
                'initiate_custom_payment.php',
                'initiate_split_payment.php',
                'initiate_alternative_payment.php',
                'upload_payment_receipt.php',
                'verify_payment_receipt.php',
                'send_estimate_message.php',
                'send_report_to_homeowner.php',
                'submit_stage_withdrawal_request.php',
                'get_contractor_house_plans.php',
                'send_to_contractor.php',
                'get_sent_to_contractors.php',
                'get_sent_reports.php',
                'submit_monthly_report.php',
                'submit_weekly_summary.php',
                'submit_integrated_progress_report.php',
                'generate_progress_report.php',
                'get_construction_timeline.php',
                'get_construction_details.php',
                'get_construction_estimates.php',
                'get_project_budget_summary.php',
                'get_stage_payment_breakdown.php',
                'get_stage_payment_info.php',
                'get_available_stages.php',
                'get_available_workers.php',
                'get_phase_workers.php',
                'start_construction.php',
                'update_stage_completion.php',
                'get_project_current_progress.php',
                'calculate_real_progress.php',
                'get_project_progress.php',
                'get_project_payment_requests.php',
                'get_all_payment_requests.php',
                'get_enhanced_payment_requests.php',
                'get_recent_paid_payments.php',
                'get_pending_payment_verifications.php',
                'submit_custom_payment_request.php',
                'initiate_estimate_payment.php',
                'verify_estimate_payment.php',
                'initiate_layout_payment.php',
                'verify_layout_payment.php',
                'initiate_international_payment.php',
                'initiate_smart_payment.php',
                'process_split_payment.php',
                'get_payment_methods.php',
                'get_project_overview.php',
                'get_project_details.php',
                'get_project_progress_details.php',
                'get_all_real_projects.php',
                'get_projects_with_real_progress.php',
                'get_projects_simple.php',
                'get_projects.php',
                'get_my_projects.php',
                'get_assigned_projects.php',
                'create_project_from_estimate.php',
                'get_progress_analytics.php'
            ]
        ],
        
        // Architect endpoints
        'architect' => [
            'middleware' => 'requireArchitect',
            'endpoints' => [
                'get_architects.php',
                'get_assigned_projects.php',
                'get_my_layouts.php',
                'get_layout_library.php',
                'create_layout_library_item.php',
                'update_layout_library_item.php',
                'get_layout_requests.php',
                'submit_layout.php',
                'get_contractor_house_plans.php',
                'upload_design.php',
                'delete_design.php',
                'get_my_designs.php',
                'generate_conceptual_image.php',
                'get_concept_previews.php',
                'download_concept_preview.php',
                'delete_concept_preview.php',
                'regenerate_concept_preview.php',
                'cancel_concept_generation.php',
                'get_active_concept_generations.php',
                'check_image_generation_status.php',
                'check_image_status.php',
                'process_concept_background.php',
                'get_room_templates.php',
                'get_room_improvement_analysis.php',
                'get_room_improvement_history.php',
                'analyze_room_improvement.php',
                'get_my_proposals.php',
                'get_my_requests.php',
                'get_my_projects.php',
                'get_assigned_requests.php',
                'get_pending_requests.php',
                'respond_assignment.php',
                'assign_architect.php',
                'approve_request.php'
            ]
        ],
        
        // Site Inspector endpoints
        'inspector' => [
            'middleware' => 'requireSiteInspector',
            'endpoints' => [
                'get_inspection_history.php',
                'create_inspection_report.php',
                'get_inspection_reports.php',
                'upload_inspection_photos.php',
                'get_project_details.php',
                'get_project_progress_details.php',
                'get_site_notes.php',
                'create_site_notes.php'
            ]
        ]
    ];
    
    public function generateJWTWrapper($role, $endpoint) {
        $middleware = $this->endpointMappings[$role]['middleware'];
        
        return "<?php
/**
 * JWT Protected Endpoint: {$endpoint}
 * Role: {$role}
 * Auto-generated JWT wrapper
 */

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (\$_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// JWT Authentication
require_once __DIR__ . '/../../middleware/JWTAuthMiddleware.php';

try {
    \$auth = new JWTAuthMiddleware();
    \$user = \$auth->{$middleware}();
    
    if (!\$user) {
        exit; // Middleware handles the response
    }
    
    // Set global user for backward compatibility
    \$_SESSION['user_id'] = \$user['id'];
    \$_SESSION['user_role'] = \$user['role'];
    \$_SESSION['user_email'] = \$user['email'];
    
    // Include original endpoint logic
    require_once __DIR__ . '/original_{$endpoint}';
    
} catch (Exception \$e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Authentication error: ' . \$e->getMessage(),
        'code' => 'AUTH_ERROR'
    ]);
}
?>";
    }
    
    public function updateEndpoint($role, $endpoint) {
        $basePath = __DIR__ . "/../api/{$role}/";
        $originalFile = $basePath . $endpoint;
        $backupFile = $basePath . "original_{$endpoint}";
        
        if (!file_exists($originalFile)) {
            echo "Warning: {$originalFile} not found\n";
            return false;
        }
        
        // Create backup of original file
        if (!file_exists($backupFile)) {
            copy($originalFile, $backupFile);
            echo "Created backup: {$backupFile}\n";
        }
        
        // Generate JWT wrapper
        $wrapperContent = $this->generateJWTWrapper($role, $endpoint);
        file_put_contents($originalFile, $wrapperContent);
        
        echo "Updated: {$originalFile}\n";
        return true;
    }
    
    public function updateAllEndpoints() {
        $updated = 0;
        $total = 0;
        
        foreach ($this->endpointMappings as $role => $config) {
            echo "\nUpdating {$role} endpoints...\n";
            
            foreach ($config['endpoints'] as $endpoint) {
                $total++;
                if ($this->updateEndpoint($role, $endpoint)) {
                    $updated++;
                }
            }
        }
        
        echo "\nSummary: Updated {$updated}/{$total} endpoints\n";
        return $updated;
    }
    
    public function createTempDirectory() {
        $tempDir = __DIR__ . '/../../temp';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
            echo "Created temp directory: {$tempDir}\n";
        }
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    $updater = new JWTEndpointUpdater();
    $updater->createTempDirectory();
    
    if (isset($argv[1]) && $argv[1] === 'update') {
        echo "Starting JWT endpoint updates...\n";
        $updater->updateAllEndpoints();
        echo "JWT endpoint updates completed!\n";
    } else {
        echo "Usage: php JWTEndpointUpdater.php update\n";
        echo "This will add JWT authentication to all API endpoints.\n";
    }
}
?>