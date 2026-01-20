<?php
header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) { 
    header('Access-Control-Allow-Origin: ' . $origin); 
    header('Vary: Origin'); 
} else { 
    header('Access-Control-Allow-Origin: http://localhost:3000'); 
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';
require_once '../../utils/send_mail.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $project_id = isset($input['project_id']) ? (int)$input['project_id'] : 0;
    $contractor_id = isset($input['contractor_id']) ? (int)$input['contractor_id'] : 0;
    $stage_name = trim($input['stage_name'] ?? '');
    $requested_amount = isset($input['requested_amount']) ? (float)$input['requested_amount'] : 0;
    $work_description = trim($input['work_description'] ?? '');
    $completion_percentage = isset($input['completion_percentage']) ? (float)$input['completion_percentage'] : 0;
    $contractor_notes = trim($input['contractor_notes'] ?? '');
    
    // Enhanced stage-specific fields
    $materials_used = trim($input['materials_used'] ?? '');
    $labor_cost = isset($input['labor_cost']) ? (float)$input['labor_cost'] : 0;
    $material_cost = isset($input['material_cost']) ? (float)$input['material_cost'] : 0;
    $equipment_cost = isset($input['equipment_cost']) ? (float)$input['equipment_cost'] : 0;
    $other_expenses = isset($input['other_expenses']) ? (float)$input['other_expenses'] : 0;
    $work_start_date = trim($input['work_start_date'] ?? '');
    $work_end_date = trim($input['work_end_date'] ?? '');
    $quality_check_status = trim($input['quality_check_status'] ?? 'pending');
    $safety_compliance = isset($input['safety_compliance']) ? (bool)$input['safety_compliance'] : false;
    $weather_delays = isset($input['weather_delays']) ? (int)$input['weather_delays'] : 0;
    $workers_count = isset($input['workers_count']) ? (int)$input['workers_count'] : 0;
    $supervisor_name = trim($input['supervisor_name'] ?? '');
    $next_stage_readiness = trim($input['next_stage_readiness'] ?? 'not_ready');
    
    // Validation
    if ($project_id <= 0 || $contractor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid project or contractor ID']);
        exit;
    }
    
    if (empty($stage_name)) {
        echo json_encode(['success' => false, 'message' => 'Stage name is required']);
        exit;
    }
    
    if ($requested_amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Requested amount must be greater than 0']);
        exit;
    }
    
    if (empty($work_description) || strlen($work_description) < 50) {
        echo json_encode(['success' => false, 'message' => 'Work description must be at least 50 characters']);
        exit;
    }
    
    if ($completion_percentage < 0 || $completion_percentage > 100) {
        echo json_encode(['success' => false, 'message' => 'Completion percentage must be between 0 and 100']);
        exit;
    }
    
    // Validate cost breakdown
    $total_breakdown = $labor_cost + $material_cost + $equipment_cost + $other_expenses;
    if ($total_breakdown > 0 && abs($total_breakdown - $requested_amount) > 1) {
        echo json_encode(['success' => false, 'message' => 'Cost breakdown does not match requested amount']);
        exit;
    }
    
    // Verify contractor is assigned to this project and get project details
    $projectCheck = $db->prepare("
        SELECT 
            cse.id, cse.homeowner_id, cse.total_cost, cse.timeline,
            cls.homeowner_id as layout_homeowner_id,
            u_homeowner.first_name as homeowner_first_name, 
            u_homeowner.last_name as homeowner_last_name,
            u_homeowner.email as homeowner_email,
            u_contractor.first_name as contractor_first_name,
            u_contractor.last_name as contractor_last_name
        FROM contractor_send_estimates cse 
        INNER JOIN contractor_layout_sends cls ON cse.send_id = cls.id
        LEFT JOIN users u_homeowner ON cls.homeowner_id = u_homeowner.id
        LEFT JOIN users u_contractor ON cse.contractor_id = u_contractor.id
        WHERE cse.id = :project_id AND cse.contractor_id = :contractor_id
        LIMIT 1
    ");
    $projectCheck->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $projectCheck->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $projectCheck->execute();
    $project = $projectCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found or contractor not assigned']);
        exit;
    }
    
    $homeowner_id = $project['homeowner_id'] ?: $project['layout_homeowner_id'];
    $total_cost = $project['total_cost'];
    
    // Calculate percentage of total project cost
    $percentage_of_total = ($requested_amount / $total_cost) * 100;
    
    // Check if there's already a pending request for this stage
    $existingCheck = $db->prepare("
        SELECT id FROM enhanced_stage_payment_requests 
        WHERE project_id = :project_id AND stage_name = :stage_name AND status = 'pending'
        LIMIT 1
    ");
    $existingCheck->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $existingCheck->bindValue(':stage_name', $stage_name, PDO::PARAM_STR);
    $existingCheck->execute();
    
    if ($existingCheck->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'There is already a pending payment request for this stage']);
        exit;
    }
    
    // Create enhanced_stage_payment_requests table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS enhanced_stage_payment_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            contractor_id INT NOT NULL,
            homeowner_id INT NOT NULL,
            stage_name VARCHAR(100) NOT NULL,
            requested_amount DECIMAL(15,2) NOT NULL,
            percentage_of_total DECIMAL(5,2) NOT NULL,
            work_description TEXT NOT NULL,
            completion_percentage DECIMAL(5,2) NOT NULL,
            contractor_notes TEXT,
            
            -- Enhanced stage-specific fields
            materials_used TEXT,
            labor_cost DECIMAL(15,2) DEFAULT 0,
            material_cost DECIMAL(15,2) DEFAULT 0,
            equipment_cost DECIMAL(15,2) DEFAULT 0,
            other_expenses DECIMAL(15,2) DEFAULT 0,
            work_start_date DATE,
            work_end_date DATE,
            quality_check_status ENUM('pending', 'passed', 'failed', 'not_applicable') DEFAULT 'pending',
            safety_compliance BOOLEAN DEFAULT FALSE,
            weather_delays INT DEFAULT 0,
            workers_count INT DEFAULT 0,
            supervisor_name VARCHAR(255),
            next_stage_readiness ENUM('ready', 'not_ready', 'partial') DEFAULT 'not_ready',
            
            -- Response fields
            status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
            homeowner_response_date DATETIME NULL,
            homeowner_notes TEXT NULL,
            approved_amount DECIMAL(15,2) NULL,
            rejection_reason TEXT NULL,
            payment_date DATETIME NULL,
            payment_method VARCHAR(100) NULL,
            payment_reference VARCHAR(255) NULL,
            
            request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX(project_id),
            INDEX(contractor_id),
            INDEX(homeowner_id),
            INDEX(stage_name),
            INDEX(status)
        )
    ");
    
    // Get stage-specific requirements and validation
    $stageRequirements = getStageRequirements($stage_name);
    
    // Validate stage-specific requirements
    $validationErrors = validateStageRequirements($stage_name, $input, $stageRequirements);
    if (!empty($validationErrors)) {
        echo json_encode(['success' => false, 'message' => 'Validation errors: ' . implode(', ', $validationErrors)]);
        exit;
    }
    
    // Insert enhanced payment request
    $stmt = $db->prepare("
        INSERT INTO enhanced_stage_payment_requests (
            project_id, contractor_id, homeowner_id, stage_name, 
            requested_amount, percentage_of_total, work_description, 
            completion_percentage, contractor_notes,
            materials_used, labor_cost, material_cost, equipment_cost, other_expenses,
            work_start_date, work_end_date, quality_check_status, safety_compliance,
            weather_delays, workers_count, supervisor_name, next_stage_readiness
        ) VALUES (
            :project_id, :contractor_id, :homeowner_id, :stage_name,
            :requested_amount, :percentage_of_total, :work_description,
            :completion_percentage, :contractor_notes,
            :materials_used, :labor_cost, :material_cost, :equipment_cost, :other_expenses,
            :work_start_date, :work_end_date, :quality_check_status, :safety_compliance,
            :weather_delays, :workers_count, :supervisor_name, :next_stage_readiness
        )
    ");
    
    $stmt->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $stmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $stmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
    $stmt->bindValue(':stage_name', $stage_name, PDO::PARAM_STR);
    $stmt->bindValue(':requested_amount', $requested_amount, PDO::PARAM_STR);
    $stmt->bindValue(':percentage_of_total', $percentage_of_total, PDO::PARAM_STR);
    $stmt->bindValue(':work_description', $work_description, PDO::PARAM_STR);
    $stmt->bindValue(':completion_percentage', $completion_percentage, PDO::PARAM_STR);
    $stmt->bindValue(':contractor_notes', $contractor_notes, PDO::PARAM_STR);
    $stmt->bindValue(':materials_used', $materials_used, PDO::PARAM_STR);
    $stmt->bindValue(':labor_cost', $labor_cost, PDO::PARAM_STR);
    $stmt->bindValue(':material_cost', $material_cost, PDO::PARAM_STR);
    $stmt->bindValue(':equipment_cost', $equipment_cost, PDO::PARAM_STR);
    $stmt->bindValue(':other_expenses', $other_expenses, PDO::PARAM_STR);
    $stmt->bindValue(':work_start_date', $work_start_date ?: null, $work_start_date ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(':work_end_date', $work_end_date ?: null, $work_end_date ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(':quality_check_status', $quality_check_status, PDO::PARAM_STR);
    $stmt->bindValue(':safety_compliance', $safety_compliance, PDO::PARAM_BOOL);
    $stmt->bindValue(':weather_delays', $weather_delays, PDO::PARAM_INT);
    $stmt->bindValue(':workers_count', $workers_count, PDO::PARAM_INT);
    $stmt->bindValue(':supervisor_name', $supervisor_name, PDO::PARAM_STR);
    $stmt->bindValue(':next_stage_readiness', $next_stage_readiness, PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        $payment_request_id = $db->lastInsertId();
        
        // Send email notification to homeowner
        $homeowner_name = $project['homeowner_first_name'] . ' ' . $project['homeowner_last_name'];
        $contractor_name = $project['contractor_first_name'] . ' ' . $project['contractor_last_name'];
        
        $subject = "💰 Payment Request: {$stage_name} Stage - ₹" . number_format($requested_amount, 2);
        
        $emailBody = "<!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 700px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 30px; }
                .content-box { background: #f8f9fa; padding: 25px; border-radius: 8px; border-left: 4px solid #28a745; margin-bottom: 20px; }
                .details { background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6; margin-bottom: 20px; }
                .cost-breakdown { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0; }
                .cost-item { display: flex; justify-content: space-between; padding: 8px; background: #f8f9fa; border-radius: 4px; }
                .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>💰</h1>
                    <h2>Payment Request Received</h2>
                    <p>{$stage_name} Stage - {$completion_percentage}% Complete</p>
                </div>
                
                <div class='content-box'>
                    <h3>Payment Request Details</h3>
                    <p>Dear {$homeowner_name},</p>
                    <p><strong>{$contractor_name}</strong> has submitted a payment request for the <strong>{$stage_name}</strong> stage of your construction project.</p>
                </div>
                
                <div class='details'>
                    <h3>💼 Request Summary</h3>
                    <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>
                        <div>
                            <p><strong>Stage:</strong> {$stage_name}</p>
                            <p><strong>Completion:</strong> {$completion_percentage}%</p>
                            <p><strong>Requested Amount:</strong> ₹" . number_format($requested_amount, 2) . "</p>
                            <p><strong>% of Total Cost:</strong> " . number_format($percentage_of_total, 2) . "%</p>
                        </div>
                        <div>
                            <p><strong>Workers:</strong> {$workers_count} workers</p>
                            <p><strong>Supervisor:</strong> {$supervisor_name}</p>
                            <p><strong>Work Period:</strong> {$work_start_date} to {$work_end_date}</p>
                            <p><strong>Quality Check:</strong> " . ucfirst($quality_check_status) . "</p>
                        </div>
                    </div>
                </div>";
        
        if ($total_breakdown > 0) {
            $emailBody .= "
                <div class='details'>
                    <h3>💰 Cost Breakdown</h3>
                    <div class='cost-breakdown'>
                        <div class='cost-item'><span>Labor Cost:</span><span>₹" . number_format($labor_cost, 2) . "</span></div>
                        <div class='cost-item'><span>Material Cost:</span><span>₹" . number_format($material_cost, 2) . "</span></div>
                        <div class='cost-item'><span>Equipment Cost:</span><span>₹" . number_format($equipment_cost, 2) . "</span></div>
                        <div class='cost-item'><span>Other Expenses:</span><span>₹" . number_format($other_expenses, 2) . "</span></div>
                    </div>
                    <div style='border-top: 2px solid #28a745; padding-top: 10px; margin-top: 10px;'>
                        <div class='cost-item' style='background: #28a745; color: white; font-weight: bold;'>
                            <span>Total Amount:</span><span>₹" . number_format($requested_amount, 2) . "</span>
                        </div>
                    </div>
                </div>";
        }
        
        $emailBody .= "
                <div class='details'>
                    <h3>🏗️ Work Description</h3>
                    <p>" . nl2br(htmlspecialchars($work_description)) . "</p>
                    
                    " . (!empty($materials_used) ? "<h4>Materials Used:</h4><p>" . nl2br(htmlspecialchars($materials_used)) . "</p>" : "") . "
                    " . (!empty($contractor_notes) ? "<h4>Contractor Notes:</h4><p>" . nl2br(htmlspecialchars($contractor_notes)) . "</p>" : "") . "
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='http://localhost:3000/homeowner-dashboard' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; margin-right: 10px;'>
                        ✅ Review & Approve
                    </a>
                    <a href='http://localhost:3000/homeowner-dashboard' style='background: #6c757d; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>
                        📋 View Details
                    </a>
                </div>
                
                <div class='footer'>
                    <p>Please review this payment request in your homeowner dashboard.</p>
                    <p>You can approve, request modifications, or ask questions about this payment.</p>
                </div>
            </div>
        </body>
        </html>";
        
        // Send email to homeowner
        @sendMail($project['homeowner_email'], $subject, $emailBody);
        
        echo json_encode([
            'success' => true,
            'message' => 'Enhanced payment request submitted successfully',
            'data' => [
                'payment_request_id' => $payment_request_id,
                'requested_amount' => $requested_amount,
                'percentage_of_total' => round($percentage_of_total, 2),
                'stage_name' => $stage_name,
                'homeowner_name' => $homeowner_name,
                'cost_breakdown' => [
                    'labor_cost' => $labor_cost,
                    'material_cost' => $material_cost,
                    'equipment_cost' => $equipment_cost,
                    'other_expenses' => $other_expenses,
                    'total' => $requested_amount
                ]
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit payment request']);
    }
    
} catch (Exception $e) {
    error_log("Enhanced stage payment request error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}

/**
 * Get stage-specific requirements and validation rules
 */
function getStageRequirements($stage_name) {
    $requirements = [
        'Foundation' => [
            'required_fields' => ['materials_used', 'quality_check_status'],
            'typical_materials' => ['Cement', 'Steel bars', 'Sand', 'Aggregate', 'Water'],
            'quality_checks' => ['Soil test', 'Foundation depth', 'Reinforcement placement'],
            'safety_requirements' => true
        ],
        'Structure' => [
            'required_fields' => ['materials_used', 'quality_check_status', 'safety_compliance'],
            'typical_materials' => ['Cement', 'Steel bars', 'Bricks', 'Sand', 'Aggregate'],
            'quality_checks' => ['Column alignment', 'Beam strength', 'Slab thickness'],
            'safety_requirements' => true
        ],
        'Brickwork' => [
            'required_fields' => ['materials_used'],
            'typical_materials' => ['Bricks', 'Cement', 'Sand', 'Mortar'],
            'quality_checks' => ['Wall alignment', 'Mortar strength', 'Brick quality'],
            'safety_requirements' => false
        ],
        'Roofing' => [
            'required_fields' => ['materials_used', 'weather_delays'],
            'typical_materials' => ['Roofing sheets', 'Tiles', 'Waterproofing', 'Insulation'],
            'quality_checks' => ['Water proofing', 'Slope verification', 'Material quality'],
            'safety_requirements' => true
        ],
        'Electrical' => [
            'required_fields' => ['materials_used', 'safety_compliance'],
            'typical_materials' => ['Wires', 'Switches', 'Sockets', 'MCB', 'Conduits'],
            'quality_checks' => ['Circuit testing', 'Earthing check', 'Load calculation'],
            'safety_requirements' => true
        ],
        'Plumbing' => [
            'required_fields' => ['materials_used', 'quality_check_status'],
            'typical_materials' => ['Pipes', 'Fittings', 'Valves', 'Fixtures', 'Sealants'],
            'quality_checks' => ['Pressure testing', 'Leak check', 'Drainage test'],
            'safety_requirements' => false
        ],
        'Finishing' => [
            'required_fields' => ['materials_used', 'quality_check_status'],
            'typical_materials' => ['Paint', 'Tiles', 'Fixtures', 'Hardware', 'Sealants'],
            'quality_checks' => ['Surface finish', 'Color matching', 'Hardware functionality'],
            'safety_requirements' => false
        ]
    ];
    
    return $requirements[$stage_name] ?? [];
}

/**
 * Validate stage-specific requirements
 */
function validateStageRequirements($stage_name, $input, $requirements) {
    $errors = [];
    
    if (empty($requirements)) {
        return $errors;
    }
    
    // Check required fields
    foreach ($requirements['required_fields'] ?? [] as $field) {
        if (empty($input[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required for {$stage_name} stage";
        }
    }
    
    // Check safety compliance for stages that require it
    if (($requirements['safety_requirements'] ?? false) && !($input['safety_compliance'] ?? false)) {
        $errors[] = "Safety compliance confirmation is required for {$stage_name} stage";
    }
    
    // Validate quality check status
    if (in_array('quality_check_status', $requirements['required_fields'] ?? [])) {
        $valid_statuses = ['pending', 'passed', 'failed', 'not_applicable'];
        if (!in_array($input['quality_check_status'] ?? '', $valid_statuses)) {
            $errors[] = "Valid quality check status is required for {$stage_name} stage";
        }
    }
    
    return $errors;
}
?>