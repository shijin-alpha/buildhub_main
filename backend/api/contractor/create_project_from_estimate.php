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
    header('Access-Control-Max-Age: 86400'); 
    exit; 
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    $estimate_id = isset($input['estimate_id']) ? (int)$input['estimate_id'] : 0;

    // Validation
    if ($estimate_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing estimate_id']);
        exit;
    }

    // Get estimate details with all related information
    $stmt = $db->prepare("
        SELECT 
            cse.id as estimate_id,
            cse.send_id,
            cse.contractor_id,
            cse.total_cost,
            cse.timeline,
            cse.materials,
            cse.cost_breakdown,
            cse.notes,
            cse.structured,
            cse.status,
            cse.created_at as estimate_date,
            cls.homeowner_id,
            cls.layout_id,
            cls.design_id,
            cls.message as project_message,
            cls.acknowledged_at,
            u_homeowner.first_name as homeowner_first_name,
            u_homeowner.last_name as homeowner_last_name,
            u_homeowner.email as homeowner_email,
            u_homeowner.phone as homeowner_phone,
            u_contractor.first_name as contractor_first_name,
            u_contractor.last_name as contractor_last_name,
            u_contractor.email as contractor_email,
            lr.id as layout_request_id,
            lr.plot_size,
            lr.budget_range,
            lr.requirements,
            lr.preferred_style,
            lr.location,
            lr.timeline as requested_timeline,
            lr.created_at as request_date
        FROM contractor_send_estimates cse
        INNER JOIN contractor_layout_sends cls ON cse.send_id = cls.id
        LEFT JOIN users u_homeowner ON cls.homeowner_id = u_homeowner.id
        LEFT JOIN users u_contractor ON cse.contractor_id = u_contractor.id
        LEFT JOIN layout_requests lr ON cls.layout_id = lr.id
        WHERE cse.id = :estimate_id 
        AND cse.status = 'accepted'
    ");

    $stmt->bindValue(':estimate_id', $estimate_id, PDO::PARAM_INT);
    $stmt->execute();
    $estimate = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$estimate) {
        echo json_encode(['success' => false, 'message' => 'Estimate not found or not accepted']);
        exit;
    }

    // Create projects table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS construction_projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            estimate_id INT NOT NULL UNIQUE,
            contractor_id INT NOT NULL,
            homeowner_id INT NOT NULL,
            project_name VARCHAR(255) NOT NULL,
            project_description TEXT,
            total_cost DECIMAL(15,2),
            timeline VARCHAR(255),
            status ENUM('created', 'in_progress', 'completed', 'on_hold', 'cancelled') DEFAULT 'created',
            start_date DATE NULL,
            expected_completion_date DATE NULL,
            actual_completion_date DATE NULL,
            
            -- Project details from estimate
            materials TEXT,
            cost_breakdown TEXT,
            structured_data LONGTEXT,
            contractor_notes TEXT,
            
            -- Homeowner and location details
            homeowner_name VARCHAR(255),
            homeowner_email VARCHAR(255),
            homeowner_phone VARCHAR(50),
            project_location TEXT,
            plot_size VARCHAR(100),
            budget_range VARCHAR(100),
            preferred_style VARCHAR(100),
            requirements TEXT,
            
            -- Layout and design information
            layout_id INT,
            design_id INT,
            layout_images JSON,
            technical_details JSON,
            
            -- Progress tracking
            current_stage VARCHAR(100) DEFAULT 'Planning',
            completion_percentage DECIMAL(5,2) DEFAULT 0.00,
            last_update_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_contractor_id (contractor_id),
            INDEX idx_homeowner_id (homeowner_id),
            INDEX idx_status (status),
            INDEX idx_estimate_id (estimate_id),
            
            FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (estimate_id) REFERENCES contractor_send_estimates(id) ON DELETE CASCADE
        )
    ");

    // Check if project already exists for this estimate
    $checkStmt = $db->prepare("SELECT id FROM construction_projects WHERE estimate_id = :estimate_id");
    $checkStmt->bindValue(':estimate_id', $estimate_id, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Project already exists for this estimate']);
        exit;
    }

    // Parse structured data
    $structured = null;
    if (!empty($estimate['structured'])) {
        $structured = json_decode($estimate['structured'], true);
    }

    // Generate project name
    $project_name = '';
    if ($structured && isset($structured['project_name'])) {
        $project_name = $structured['project_name'];
    } else {
        $homeowner_name = trim($estimate['homeowner_first_name'] . ' ' . $estimate['homeowner_last_name']);
        $project_name = $homeowner_name . ' - ' . ($estimate['plot_size'] ?: 'Construction Project');
    }

    // Generate project description
    $project_description = "Construction project for " . trim($estimate['homeowner_first_name'] . ' ' . $estimate['homeowner_last_name']);
    if ($estimate['location']) {
        $project_description .= " at " . $estimate['location'];
    }
    if ($estimate['plot_size']) {
        $project_description .= " (" . $estimate['plot_size'] . ")";
    }

    // Calculate expected completion date
    $expected_completion = null;
    if ($estimate['timeline']) {
        // Try to extract days from timeline (e.g., "90 days", "3 months")
        $timeline = strtolower($estimate['timeline']);
        $days = 90; // default
        
        if (preg_match('/(\d+)\s*days?/', $timeline, $matches)) {
            $days = (int)$matches[1];
        } elseif (preg_match('/(\d+)\s*months?/', $timeline, $matches)) {
            $days = (int)$matches[1] * 30;
        } elseif (preg_match('/(\d+)\s*weeks?/', $timeline, $matches)) {
            $days = (int)$matches[1] * 7;
        }
        
        $expected_completion = date('Y-m-d', strtotime("+{$days} days"));
    }

    // Get layout images if available
    $layout_images = null;
    if ($estimate['layout_id']) {
        $layoutStmt = $db->prepare("
            SELECT layout_image, layout_file, technical_details 
            FROM contractor_layout_sends 
            WHERE layout_id = :layout_id AND homeowner_id = :homeowner_id
        ");
        $layoutStmt->bindValue(':layout_id', $estimate['layout_id'], PDO::PARAM_INT);
        $layoutStmt->bindValue(':homeowner_id', $estimate['homeowner_id'], PDO::PARAM_INT);
        $layoutStmt->execute();
        $layout_data = $layoutStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($layout_data) {
            $layout_images = json_encode([
                'layout_image' => $layout_data['layout_image'],
                'layout_file' => $layout_data['layout_file'],
                'technical_details' => $layout_data['technical_details']
            ]);
        }
    }

    // Insert project
    $insertStmt = $db->prepare("
        INSERT INTO construction_projects (
            estimate_id, contractor_id, homeowner_id, project_name, project_description,
            total_cost, timeline, materials, cost_breakdown, structured_data, contractor_notes,
            homeowner_name, homeowner_email, homeowner_phone, project_location,
            plot_size, budget_range, preferred_style, requirements,
            layout_id, design_id, layout_images, expected_completion_date
        ) VALUES (
            :estimate_id, :contractor_id, :homeowner_id, :project_name, :project_description,
            :total_cost, :timeline, :materials, :cost_breakdown, :structured_data, :contractor_notes,
            :homeowner_name, :homeowner_email, :homeowner_phone, :project_location,
            :plot_size, :budget_range, :preferred_style, :requirements,
            :layout_id, :design_id, :layout_images, :expected_completion_date
        )
    ");

    $insertStmt->bindValue(':estimate_id', $estimate_id, PDO::PARAM_INT);
    $insertStmt->bindValue(':contractor_id', $estimate['contractor_id'], PDO::PARAM_INT);
    $insertStmt->bindValue(':homeowner_id', $estimate['homeowner_id'], PDO::PARAM_INT);
    $insertStmt->bindValue(':project_name', $project_name, PDO::PARAM_STR);
    $insertStmt->bindValue(':project_description', $project_description, PDO::PARAM_STR);
    $insertStmt->bindValue(':total_cost', $estimate['total_cost'], PDO::PARAM_STR);
    $insertStmt->bindValue(':timeline', $estimate['timeline'], PDO::PARAM_STR);
    $insertStmt->bindValue(':materials', $estimate['materials'], PDO::PARAM_STR);
    $insertStmt->bindValue(':cost_breakdown', $estimate['cost_breakdown'], PDO::PARAM_STR);
    $insertStmt->bindValue(':structured_data', $estimate['structured'], PDO::PARAM_STR);
    $insertStmt->bindValue(':contractor_notes', $estimate['notes'], PDO::PARAM_STR);
    $insertStmt->bindValue(':homeowner_name', trim($estimate['homeowner_first_name'] . ' ' . $estimate['homeowner_last_name']), PDO::PARAM_STR);
    $insertStmt->bindValue(':homeowner_email', $estimate['homeowner_email'], PDO::PARAM_STR);
    $insertStmt->bindValue(':homeowner_phone', $estimate['homeowner_phone'], PDO::PARAM_STR);
    $insertStmt->bindValue(':project_location', $estimate['location'], PDO::PARAM_STR);
    $insertStmt->bindValue(':plot_size', $estimate['plot_size'], PDO::PARAM_STR);
    $insertStmt->bindValue(':budget_range', $estimate['budget_range'], PDO::PARAM_STR);
    $insertStmt->bindValue(':preferred_style', $estimate['preferred_style'], PDO::PARAM_STR);
    $insertStmt->bindValue(':requirements', $estimate['requirements'], PDO::PARAM_STR);
    $insertStmt->bindValue(':layout_id', $estimate['layout_id'], PDO::PARAM_INT);
    $insertStmt->bindValue(':design_id', $estimate['design_id'], PDO::PARAM_INT);
    $insertStmt->bindValue(':layout_images', $layout_images, PDO::PARAM_STR);
    $insertStmt->bindValue(':expected_completion_date', $expected_completion, PDO::PARAM_STR);

    if ($insertStmt->execute()) {
        $project_id = $db->lastInsertId();

        // Update estimate status to indicate project created
        $updateEstimateStmt = $db->prepare("
            UPDATE contractor_send_estimates 
            SET status = 'project_created' 
            WHERE id = :estimate_id
        ");
        $updateEstimateStmt->bindValue(':estimate_id', $estimate_id, PDO::PARAM_INT);
        $updateEstimateStmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Project created successfully',
            'data' => [
                'project_id' => $project_id,
                'project_name' => $project_name,
                'estimate_id' => $estimate_id,
                'contractor_id' => $estimate['contractor_id'],
                'homeowner_id' => $estimate['homeowner_id'],
                'total_cost' => $estimate['total_cost'],
                'timeline' => $estimate['timeline'],
                'expected_completion_date' => $expected_completion
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create project']);
    }

} catch (Exception $e) {
    error_log("Create project from estimate error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>