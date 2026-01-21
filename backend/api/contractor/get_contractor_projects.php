<?php
// Suppress warnings to prevent JSON corruption
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

try {
    // Get contractor ID from query parameters
    $contractor_id = $_GET['contractor_id'] ?? null;
    
    if (!$contractor_id) {
        throw new Exception('Contractor ID is required');
    }
    
    // Try to connect to database
    try {
        $host = 'localhost';
        $dbname = 'buildhub';
        $username = 'root';
        $password = '';
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create contractor_estimates table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS contractor_estimates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contractor_id INT NOT NULL,
                homeowner_id INT NOT NULL,
                send_id INT NULL,
                project_name VARCHAR(255) NULL,
                location VARCHAR(255) NULL,
                client_name VARCHAR(255) NULL,
                client_contact VARCHAR(255) NULL,
                project_type VARCHAR(100) NULL,
                timeline VARCHAR(100) NULL,
                materials_data LONGTEXT NULL,
                labor_data LONGTEXT NULL,
                utilities_data LONGTEXT NULL,
                misc_data LONGTEXT NULL,
                totals_data LONGTEXT NULL,
                structured_data LONGTEXT NULL,
                materials TEXT NULL,
                cost_breakdown TEXT NULL,
                total_cost DECIMAL(15,2) NULL,
                notes TEXT NULL,
                terms TEXT NULL,
                status ENUM('draft', 'submitted', 'accepted', 'rejected') DEFAULT 'submitted',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_contractor (contractor_id),
                INDEX idx_homeowner (homeowner_id),
                INDEX idx_send (send_id),
                INDEX idx_status (status)
            )
        ");
        
        // Create contractor_send_estimates table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS contractor_send_estimates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                send_id INT NOT NULL,
                contractor_id INT NOT NULL,
                materials TEXT NULL,
                cost_breakdown TEXT NULL,
                total_cost DECIMAL(15,2) NULL,
                timeline VARCHAR(255) NULL,
                notes TEXT NULL,
                structured LONGTEXT NULL,
                status VARCHAR(32) DEFAULT 'submitted',
                homeowner_feedback TEXT NULL,
                homeowner_action_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX(send_id), 
                INDEX(contractor_id),
                INDEX(status)
            )
        ");
        
        // Create contractor_layout_sends table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS contractor_layout_sends (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contractor_id INT NOT NULL,
                homeowner_id INT NOT NULL,
                layout_id INT NULL,
                message TEXT NULL,
                status VARCHAR(50) DEFAULT 'sent',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX(contractor_id),
                INDEX(homeowner_id)
            )
        ");
        
        // Create construction_projects table if it doesn't exist (same structure as in create_project_from_estimate.php)
        $pdo->exec("
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
                INDEX idx_estimate_id (estimate_id)
            )
        ");
        
        // Get contractor's actual construction projects (ready for construction)
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                cp.id,
                cp.project_name,
                cp.project_description,
                cp.total_cost as estimate_cost,
                cp.timeline,
                cp.status,
                cp.homeowner_id,
                cp.homeowner_name,
                cp.homeowner_email,
                cp.homeowner_phone,
                cp.project_location as location,
                cp.plot_size,
                cp.budget_range,
                cp.preferred_style,
                cp.requirements,
                cp.estimate_id,
                cp.layout_id,
                cp.current_stage,
                cp.completion_percentage,
                cp.created_at,
                cp.updated_at,
                cp.expected_completion_date,
                cp.start_date,
                cp.actual_completion_date,
                
                -- Contractor information
                contractor.first_name as contractor_first_name,
                contractor.last_name as contractor_last_name,
                contractor.email as contractor_email,
                
                -- Additional project dates and info
                cp.created_at as request_date_formatted,
                cp.created_at as estimate_date_formatted,
                cp.updated_at as acknowledged_date_formatted,
                
                -- Try to get additional info from layout_requests if missing (use LIMIT 1 to avoid duplicates)
                (SELECT lr.budget_range FROM layout_requests lr WHERE lr.homeowner_id = cp.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_budget_range,
                (SELECT lr.plot_size FROM layout_requests lr WHERE lr.homeowner_id = cp.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_plot_size,
                (SELECT lr.location FROM layout_requests lr WHERE lr.homeowner_id = cp.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_location,
                (SELECT lr.preferred_style FROM layout_requests lr WHERE lr.homeowner_id = cp.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_preferred_style,
                
                'construction_project' as source
            FROM construction_projects cp
            LEFT JOIN users contractor ON contractor.id = cp.contractor_id
            WHERE cp.contractor_id = ? 
            AND cp.status IN ('created', 'in_progress')
            ORDER BY cp.created_at DESC
        ");
        
        $stmt->execute([$contractor_id]);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Also get accepted estimates from contractor_estimates table
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                ce.id,
                ce.project_name,
                ce.notes as project_description,
                ce.total_cost as estimate_cost,
                ce.timeline,
                'ready_for_construction' as status,
                ce.homeowner_id,
                CONCAT(u.first_name, ' ', u.last_name) as homeowner_name,
                u.email as homeowner_email,
                u.phone as homeowner_phone,
                ce.location,
                NULL as plot_size,
                NULL as budget_range,
                NULL as preferred_style,
                NULL as requirements,
                ce.id as estimate_id,
                NULL as layout_id,
                'Planning' as current_stage,
                0 as completion_percentage,
                ce.created_at,
                ce.updated_at,
                NULL as expected_completion_date,
                NULL as start_date,
                NULL as actual_completion_date,
                
                -- Contractor information
                contractor.first_name as contractor_first_name,
                contractor.last_name as contractor_last_name,
                contractor.email as contractor_email,
                
                -- Additional project dates
                ce.created_at as request_date_formatted,
                ce.created_at as estimate_date_formatted,
                ce.updated_at as acknowledged_date_formatted,
                
                -- Try to get additional info from layout_requests if missing (use subqueries to avoid duplicates)
                (SELECT lr.location FROM layout_requests lr WHERE lr.homeowner_id = ce.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_location,
                (SELECT lr.budget_range FROM layout_requests lr WHERE lr.homeowner_id = ce.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_budget_range,
                (SELECT lr.plot_size FROM layout_requests lr WHERE lr.homeowner_id = ce.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_plot_size,
                (SELECT lr.preferred_style FROM layout_requests lr WHERE lr.homeowner_id = ce.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_preferred_style,
                
                'contractor_estimate' as source
            FROM contractor_estimates ce
            LEFT JOIN users u ON u.id = ce.homeowner_id
            LEFT JOIN users contractor ON contractor.id = ce.contractor_id
            WHERE ce.contractor_id = ? 
            AND ce.status = 'accepted'
            ORDER BY ce.created_at DESC
        ");
        
        $stmt->execute([$contractor_id]);
        $accepted_estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Merge accepted estimates with projects
        $projects = array_merge($projects, $accepted_estimates);
        
        // Also get accepted estimates from contractor_send_estimates table (legacy)
        $stmt = $pdo->prepare("
            SELECT 
                cse.id,
                CONCAT('Project for ', COALESCE(u.first_name, 'Homeowner')) as project_name,
                cse.notes as project_description,
                cse.total_cost as estimate_cost,
                cse.structured,
                cse.timeline,
                CASE 
                    WHEN cse.status = 'project_created' THEN 'ready_for_construction'
                    ELSE cse.status 
                END as status,
                cls.homeowner_id,
                CONCAT(u.first_name, ' ', u.last_name) as homeowner_name,
                u.email as homeowner_email,
                u.phone as homeowner_phone,
                NULL as location,
                NULL as plot_size,
                NULL as budget_range,
                NULL as preferred_style,
                NULL as requirements,
                cse.id as estimate_id,
                NULL as layout_id,
                'Planning' as current_stage,
                0 as completion_percentage,
                cse.created_at,
                cse.created_at as updated_at,
                NULL as expected_completion_date,
                NULL as start_date,
                NULL as actual_completion_date,
                
                -- Contractor information
                contractor.first_name as contractor_first_name,
                contractor.last_name as contractor_last_name,
                contractor.email as contractor_email,
                
                -- Additional project dates
                cse.created_at as request_date_formatted,
                cse.created_at as estimate_date_formatted,
                cse.homeowner_action_at as acknowledged_date_formatted,
                
                'contractor_send_estimate' as source
            FROM contractor_send_estimates cse
            LEFT JOIN contractor_layout_sends cls ON cls.id = cse.send_id
            LEFT JOIN users u ON u.id = cls.homeowner_id
            LEFT JOIN users contractor ON contractor.id = cse.contractor_id
            WHERE cse.contractor_id = ? 
            AND cse.status IN ('accepted', 'project_created')
            ORDER BY cse.created_at DESC
        ");
        
        $stmt->execute([$contractor_id]);
        $legacy_accepted_estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Extract total_cost from structured JSON if NULL
        foreach ($legacy_accepted_estimates as &$estimate) {
            // Extract data from structured JSON
            if ($estimate['structured']) {
                $structured = json_decode($estimate['structured'], true);
                if ($structured && json_last_error() === JSON_ERROR_NONE) {
                    // Extract total_cost if NULL
                    if (($estimate['estimate_cost'] === null || $estimate['estimate_cost'] == 0)) {
                        if (isset($structured['totals']['grand'])) {
                            $estimate['estimate_cost'] = floatval($structured['totals']['grand']);
                        } elseif (isset($structured['totals']['grandTotal'])) {
                            $estimate['estimate_cost'] = floatval($structured['totals']['grandTotal']);
                        } elseif (isset($structured['totals']['total'])) {
                            $estimate['estimate_cost'] = floatval($structured['totals']['total']);
                        } elseif (isset($structured['grand'])) {
                            $estimate['estimate_cost'] = floatval($structured['grand']);
                        } elseif (isset($structured['grandTotal'])) {
                            $estimate['estimate_cost'] = floatval($structured['grandTotal']);
                        } elseif (isset($structured['totals'])) {
                            // Calculate from category totals
                            $totals = $structured['totals'];
                            $calculated = 0;
                            if (isset($totals['materials'])) $calculated += floatval($totals['materials']);
                            if (isset($totals['labor'])) $calculated += floatval($totals['labor']);
                            if (isset($totals['utilities'])) $calculated += floatval($totals['utilities']);
                            if (isset($totals['misc'])) $calculated += floatval($totals['misc']);
                            if (isset($totals['miscellaneous'])) $calculated += floatval($totals['miscellaneous']);
                            if ($calculated > 0) {
                                $estimate['estimate_cost'] = $calculated;
                            }
                        }
                    }
                    
                    // Extract other fields from structured data
                    if (isset($structured['project_name']) && !empty($structured['project_name'])) {
                        $estimate['project_name'] = $structured['project_name'];
                    }
                    if (isset($structured['project_address']) && !empty($structured['project_address'])) {
                        $estimate['location'] = $structured['project_address'];
                    }
                    if (isset($structured['plot_size']) && !empty($structured['plot_size'])) {
                        $estimate['plot_size'] = $structured['plot_size'];
                    }
                    if (isset($structured['built_up_area']) && !empty($structured['built_up_area'])) {
                        $estimate['built_up_area'] = $structured['built_up_area'];
                    }
                    if (isset($structured['floors']) && !empty($structured['floors'])) {
                        $estimate['floors'] = $structured['floors'];
                    }
                    if (isset($structured['client_name']) && !empty($structured['client_name'])) {
                        $estimate['client_name'] = $structured['client_name'];
                        // Update homeowner_name if client_name is more complete
                        if (empty($estimate['homeowner_name']) || $estimate['homeowner_name'] === ' ') {
                            $estimate['homeowner_name'] = $structured['client_name'];
                        }
                    }
                    if (isset($structured['client_contact']) && !empty($structured['client_contact'])) {
                        $estimate['client_contact'] = $structured['client_contact'];
                        // Update homeowner_email if client_contact looks like email
                        if (empty($estimate['homeowner_email']) && filter_var($structured['client_contact'], FILTER_VALIDATE_EMAIL)) {
                            $estimate['homeowner_email'] = $structured['client_contact'];
                        }
                    }
                    if (isset($structured['estimation_date']) && !empty($structured['estimation_date'])) {
                        $estimate['estimation_date'] = $structured['estimation_date'];
                    }
                    
                    // Store structured data for frontend use
                    $estimate['structured_data'] = $structured;
                }
            }
        }
        unset($estimate); // Break reference
        
        // Merge legacy accepted estimates with projects
        $projects = array_merge($projects, $legacy_accepted_estimates);
        
        // If no real projects found, get layout requests that can be used as projects
        if (empty($projects)) {
            $stmt = $pdo->prepare("
                SELECT 
                    lr.id,
                    lr.user_id as homeowner_id,
                    lr.plot_size,
                    lr.budget_range,
                    lr.location,
                    lr.preferred_style,
                    lr.requirements,
                    lr.timeline,
                    lr.status,
                    lr.created_at,
                    lr.updated_at,
                    u.first_name,
                    u.last_name,
                    u.email as homeowner_email,
                    u.phone as homeowner_phone,
                    
                    -- Contractor information (current contractor)
                    contractor.first_name as contractor_first_name,
                    contractor.last_name as contractor_last_name,
                    contractor.email as contractor_email,
                    
                    -- Additional dates
                    lr.created_at as request_date_formatted,
                    lr.updated_at as estimate_date_formatted,
                    lr.updated_at as acknowledged_date_formatted
                FROM layout_requests lr
                LEFT JOIN users u ON lr.user_id = u.id
                LEFT JOIN users contractor ON contractor.id = ?
                WHERE lr.status = 'approved'
                ORDER BY lr.created_at DESC
                LIMIT 10
            ");
            
            $stmt->execute([$contractor_id]);
            $layout_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($layout_requests as $request) {
                $homeowner_name = 'Unknown Homeowner';
                if ($request['first_name'] && $request['last_name']) {
                    $homeowner_name = $request['first_name'] . ' ' . $request['last_name'];
                } elseif ($request['first_name']) {
                    $homeowner_name = $request['first_name'];
                }
                
                $contractor_name = 'Unknown Contractor';
                if ($request['contractor_first_name'] && $request['contractor_last_name']) {
                    $contractor_name = $request['contractor_first_name'] . ' ' . $request['contractor_last_name'];
                }
                
                $projects[] = [
                    'id' => $request['id'],
                    'project_name' => $homeowner_name . ' - ' . ($request['plot_size'] ?: 'Construction Project'),
                    'project_description' => $request['requirements'],
                    'total_cost' => null,
                    'timeline' => $request['timeline'],
                    'status' => 'ready_for_construction',
                    'homeowner_id' => $request['homeowner_id'],
                    'homeowner_name' => $homeowner_name,
                    'homeowner_email' => $request['homeowner_email'],
                    'homeowner_phone' => $request['homeowner_phone'],
                    'project_location' => $request['location'],
                    'plot_size' => $request['plot_size'],
                    'budget_range' => $request['budget_range'],
                    'preferred_style' => $request['preferred_style'],
                    'requirements' => $request['requirements'],
                    'estimate_id' => null,
                    'layout_id' => $request['id'],
                    'current_stage' => 'Planning',
                    'completion_percentage' => 0,
                    'created_at' => $request['created_at'],
                    'updated_at' => $request['updated_at'],
                    'expected_completion_date' => null,
                    'start_date' => null,
                    'actual_completion_date' => null,
                    
                    // Contractor information
                    'contractor_first_name' => $request['contractor_first_name'],
                    'contractor_last_name' => $request['contractor_last_name'],
                    'contractor_email' => $request['contractor_email'],
                    'contractor_name' => $contractor_name,
                    
                    // Formatted dates
                    'request_date_formatted' => $request['request_date_formatted'],
                    'estimate_date_formatted' => $request['estimate_date_formatted'],
                    'acknowledged_date_formatted' => $request['acknowledged_date_formatted'],
                    
                    'source' => 'layout_request'
                ];
            }
        }
        
        // Format the projects data
        $formatted_projects = [];
        foreach ($projects as $project) {
            $projectId = $project['id'];
            
            // Calculate update counts for this project
            // We need to check both the project ID and estimate ID since daily_progress_updates 
            // might use either depending on how the project was created
            $update_counts = [
                'daily_updates_count' => 0,
                'weekly_summaries_count' => 0,
                'monthly_reports_count' => 0,
                'latest_update_date' => null
            ];
            
            try {
                // Check daily updates - try both project ID and estimate ID
                $daily_query = "SELECT COUNT(*) as count, MAX(created_at) as latest FROM daily_progress_updates WHERE project_id = ? OR project_id = ?";
                $daily_stmt = $pdo->prepare($daily_query);
                $daily_stmt->execute([$projectId, $project['estimate_id'] ?? $projectId]);
                $daily_result = $daily_stmt->fetch(PDO::FETCH_ASSOC);
                $update_counts['daily_updates_count'] = (int)$daily_result['count'];
                $update_counts['latest_update_date'] = $daily_result['latest'];
                
                // Check weekly summaries
                $weekly_query = "SELECT COUNT(*) as count FROM weekly_progress_summaries WHERE project_id = ? OR project_id = ?";
                $weekly_stmt = $pdo->prepare($weekly_query);
                $weekly_stmt->execute([$projectId, $project['estimate_id'] ?? $projectId]);
                $weekly_result = $weekly_stmt->fetch(PDO::FETCH_ASSOC);
                $update_counts['weekly_summaries_count'] = (int)$weekly_result['count'];
                
                // Check monthly reports
                $monthly_query = "SELECT COUNT(*) as count FROM monthly_progress_reports WHERE project_id = ? OR project_id = ?";
                $monthly_stmt = $pdo->prepare($monthly_query);
                $monthly_stmt->execute([$projectId, $project['estimate_id'] ?? $projectId]);
                $monthly_result = $monthly_stmt->fetch(PDO::FETCH_ASSOC);
                $update_counts['monthly_reports_count'] = (int)$monthly_result['count'];
                
            } catch (Exception $e) {
                // If there's an error, keep default values (0)
                error_log("Error calculating update counts for project {$projectId}: " . $e->getMessage());
            }
            
            $formatted_project = [
                'id' => $project['id'],
                'project_name' => $project['project_name'],
                'homeowner_id' => $project['homeowner_id'],
                'homeowner_name' => $project['homeowner_name'],
                'homeowner_email' => $project['homeowner_email'],
                'homeowner_phone' => $project['homeowner_phone'] ?? $project['client_contact'] ?? null,
                'estimate_cost' => $project['estimate_cost'] ? floatval($project['estimate_cost']) : null,
                'estimate_id' => $project['estimate_id'],
                'layout_id' => $project['layout_id'],
                'status' => $project['status'],
                'location' => $project['lr_location'] ?? $project['location'] ?? null,
                'plot_size' => $project['lr_plot_size'] ?? $project['plot_size'],
                'built_up_area' => $project['built_up_area'] ?? null,
                'floors' => $project['floors'] ?? null,
                'budget_range' => $project['lr_budget_range'] ?? $project['budget_range'],
                'preferred_style' => $project['lr_preferred_style'] ?? $project['preferred_style'],
                'requirements' => $project['requirements'],
                'timeline' => $project['timeline'],
                'current_stage' => $project['current_stage'],
                'completion_percentage' => floatval($project['completion_percentage']),
                'created_at' => $project['created_at'],
                'updated_at' => $project['updated_at'] ?? $project['created_at'],
                'expected_completion_date' => $project['expected_completion_date'],
                'start_date' => $project['start_date'] ?? null,
                'actual_completion_date' => $project['actual_completion_date'] ?? null,
                'source' => $project['source'] ?? 'unknown',
                'needs_project_creation' => ($project['source'] !== 'construction_project'),
                
                // Contractor information
                'contractor_first_name' => $project['contractor_first_name'] ?? null,
                'contractor_last_name' => $project['contractor_last_name'] ?? null,
                'contractor_email' => $project['contractor_email'] ?? null,
                'contractor_name' => ($project['contractor_first_name'] && $project['contractor_last_name']) 
                    ? $project['contractor_first_name'] . ' ' . $project['contractor_last_name'] 
                    : null,
                
                // Formatted dates
                'request_date_formatted' => $project['request_date_formatted'] ? date('M j, Y', strtotime($project['request_date_formatted'])) : null,
                'estimate_date_formatted' => $project['estimate_date_formatted'] ? date('M j, Y', strtotime($project['estimate_date_formatted'])) : null,
                'acknowledged_date_formatted' => $project['acknowledged_date_formatted'] ? date('M j, Y', strtotime($project['acknowledged_date_formatted'])) : null,
                
                // Additional fields from structured data
                'client_name' => $project['client_name'] ?? null,
                'client_contact' => $project['client_contact'] ?? null,
                'estimation_date' => $project['estimation_date'] ?? null,
                
                // Update counts - THIS IS THE KEY FIX
                'daily_updates_count' => $update_counts['daily_updates_count'],
                'weekly_summaries_count' => $update_counts['weekly_summaries_count'],
                'monthly_reports_count' => $update_counts['monthly_reports_count'],
                'latest_update_timestamp' => $update_counts['latest_update_date'],
            ];
            
            // Add structured data if available
            if (isset($project['structured_data'])) {
                $formatted_project['structured_data'] = $project['structured_data'];
            }
            
            $formatted_projects[] = $formatted_project;
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'projects' => $formatted_projects,
                'total_projects' => count($formatted_projects)
            ]
        ]);
        
    } catch (PDOException $e) {
        // Log the specific error for debugging
        error_log("Database error in get_contractor_projects.php: " . $e->getMessage());
        
        // Return error instead of empty array
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database connection error: ' . $e->getMessage(),
            'error_code' => 'DB_CONNECTION_FAILED'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>