<?php
// Suppress warnings to prevent JSON corruption
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);


header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) { header('Access-Control-Allow-Origin: ' . $origin); header('Vary: Origin'); } else { header('Access-Control-Allow-Origin: http://localhost'); }
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); header('Access-Control-Max-Age: 86400'); exit; }

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get contractor_id from query parameter
    $contractorId = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : 0;
    if ($contractorId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing contractor_id']);
        exit;
    }

    // Ensure all required tables exist
    $db->exec("CREATE TABLE IF NOT EXISTS contractor_send_estimates (
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(send_id),
        INDEX(contractor_id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS contractor_layout_sends (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT NOT NULL,
        homeowner_id INT NULL,
        layout_id INT NULL,
        design_id INT NULL,
        message TEXT NULL,
        payload LONGTEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        acknowledged_at DATETIME NULL,
        due_date DATE NULL
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS layout_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        homeowner_id INT NOT NULL,
        plot_size VARCHAR(100) NOT NULL,
        budget_range VARCHAR(100) NOT NULL,
        requirements TEXT NOT NULL,
        preferred_style VARCHAR(100),
        location VARCHAR(255),
        timeline VARCHAR(100),
        num_floors INT NULL,
        plot_shape VARCHAR(100),
        topography VARCHAR(100),
        development_laws TEXT,
        family_needs TEXT,
        rooms TEXT,
        aesthetic TEXT,
        orientation VARCHAR(100),
        site_considerations TEXT,
        material_preferences TEXT,
        budget_allocation TEXT,
        floor_rooms TEXT,
        site_images TEXT,
        reference_images TEXT,
        room_images TEXT,
        status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // First, let's check if there are any estimates at all for this contractor
    $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM contractor_send_estimates WHERE contractor_id = :contractor_id");
    $checkStmt->bindParam(':contractor_id', $contractorId, PDO::PARAM_INT);
    $checkStmt->execute();
    $totalEstimates = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Fetch comprehensive construction details - be more flexible with status
    $stmt = $db->prepare("
        SELECT 
            e.id as estimate_id,
            e.send_id,
            e.contractor_id,
            e.materials,
            e.cost_breakdown,
            e.total_cost,
            e.timeline,
            e.notes,
            e.structured,
            e.status as estimate_status,
            e.created_at as estimate_created_at,
            
            -- Layout send details
            s.homeowner_id,
            s.layout_id,
            s.design_id,
            s.message as send_message,
            s.payload as send_payload,
            s.created_at as send_created_at,
            
            -- Homeowner details
            h.first_name as homeowner_first_name,
            h.last_name as homeowner_last_name,
            h.email as homeowner_email,
            h.phone as homeowner_phone,
            h.address as homeowner_address,
            h.city as homeowner_city,
            h.state as homeowner_state,
            h.zip_code as homeowner_zip,
            
            -- Layout request details (only existing columns)
            lr.plot_size,
            lr.budget_range,
            lr.requirements,
            lr.preferred_style,
            lr.location,
            lr.timeline as request_timeline,
            lr.created_at as request_created_at,
            
            -- Architect layout details
            al.layout_file,
            al.description as layout_description,
            al.notes as layout_notes,
            al.created_at as layout_created_at
            
        FROM contractor_send_estimates e
        INNER JOIN contractor_layout_sends s ON s.id = e.send_id
        INNER JOIN users h ON h.id = s.homeowner_id
        LEFT JOIN layout_requests lr ON lr.id = s.layout_id
        LEFT JOIN architect_layouts al ON al.id = s.design_id
        WHERE e.contractor_id = :contractor_id 
        AND (e.status = 'construction_started' OR e.status = 'accepted' OR e.status = 'submitted')
        ORDER BY e.created_at DESC
        LIMIT 10
    ");
    
    $stmt->bindParam(':contractor_id', $contractorId, PDO::PARAM_INT);
    $stmt->execute();
    
    $constructionProjects = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $constructionProjects[] = [
            'estimate_id' => (int)$row['estimate_id'],
            'send_id' => (int)$row['send_id'],
            'contractor_id' => (int)$row['contractor_id'],
            'materials' => $row['materials'],
            'cost_breakdown' => $row['cost_breakdown'],
            'total_cost' => $row['total_cost'] ? (float)$row['total_cost'] : null,
            'timeline' => $row['timeline'],
            'notes' => $row['notes'],
            'structured' => $row['structured'] ? json_decode($row['structured'], true) : null,
            'estimate_status' => $row['estimate_status'],
            'estimate_created_at' => $row['estimate_created_at'],
            
            'homeowner' => [
                'id' => (int)$row['homeowner_id'],
                'name' => trim($row['homeowner_first_name'] . ' ' . $row['homeowner_last_name']),
                'email' => $row['homeowner_email'],
                'phone' => $row['homeowner_phone'],
                'address' => $row['homeowner_address'],
                'city' => $row['homeowner_city'],
                'state' => $row['homeowner_state'],
                'zip_code' => $row['homeowner_zip']
            ],
            
            'layout_request' => [
                'id' => (int)$row['layout_id'],
                'plot_size' => $row['plot_size'],
                'budget_range' => $row['budget_range'],
                'requirements' => $row['requirements'],
                'preferred_style' => $row['preferred_style'],
                'location' => $row['location'],
                'timeline' => $row['request_timeline'],
                'created_at' => $row['request_created_at']
            ],
            
            'architect_layout' => [
                'id' => (int)$row['design_id'],
                'layout_file' => $row['layout_file'],
                'description' => $row['layout_description'],
                'notes' => $row['layout_notes'],
                'created_at' => $row['layout_created_at']
            ],
            
            'send_details' => [
                'message' => $row['send_message'],
                'payload' => $row['send_payload'] ? json_decode($row['send_payload'], true) : null,
                'created_at' => $row['send_created_at']
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'construction_projects' => $constructionProjects,
        'debug_info' => [
            'contractor_id' => $contractorId,
            'total_estimates' => $totalEstimates,
            'projects_found' => count($constructionProjects)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
