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
    // Database connection
    $host = 'localhost';
    $dbname = 'buildhub';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get project ID from query parameters
    $project_id = $_GET['project_id'] ?? null;
    
    if (!$project_id) {
        throw new Exception('Project ID is required');
    }
    
    // Verify contractor access to this project
    $contractor_id = $_SESSION['user_id'] ?? null;
    if (!$contractor_id) {
        throw new Exception('Contractor not logged in');
    }
    
    // Check if contractor is assigned to this project
    $stmt = $pdo->prepare("
        SELECT p.*, e.total_cost, e.contractor_id 
        FROM projects p 
        LEFT JOIN estimates e ON p.estimate_id = e.id 
        WHERE p.id = ? AND e.contractor_id = ?
    ");
    $stmt->execute([$project_id, $contractor_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        throw new Exception('Project not found or access denied');
    }
    
    $total_project_cost = $project['total_cost'] ?? 0;
    
    // Define standard construction stages with typical percentages
    $standard_stages = [
        [
            'stage_name' => 'Foundation',
            'typical_percentage' => 20,
            'description' => 'Site preparation, excavation, and foundation work',
            'key_deliverables' => ['Site clearance', 'Excavation', 'Foundation laying', 'Plinth beam'],
            'typical_duration' => '15-20 days'
        ],
        [
            'stage_name' => 'Structure',
            'typical_percentage' => 25,
            'description' => 'Column, beam, and slab construction',
            'key_deliverables' => ['Column construction', 'Beam work', 'Slab casting', 'Structural framework'],
            'typical_duration' => '25-30 days'
        ],
        [
            'stage_name' => 'Brickwork',
            'typical_percentage' => 15,
            'description' => 'Wall construction and masonry work',
            'key_deliverables' => ['Wall construction', 'Door/window frames', 'Plastering base'],
            'typical_duration' => '20-25 days'
        ],
        [
            'stage_name' => 'Roofing',
            'typical_percentage' => 15,
            'description' => 'Roof construction and waterproofing',
            'key_deliverables' => ['Roof structure', 'Waterproofing', 'Insulation', 'Drainage'],
            'typical_duration' => '10-15 days'
        ],
        [
            'stage_name' => 'Electrical',
            'typical_percentage' => 8,
            'description' => 'Electrical wiring and connections',
            'key_deliverables' => ['Wiring installation', 'Switch boards', 'Light fittings', 'Power connections'],
            'typical_duration' => '10-12 days'
        ],
        [
            'stage_name' => 'Plumbing',
            'typical_percentage' => 7,
            'description' => 'Plumbing installation and testing',
            'key_deliverables' => ['Pipe installation', 'Fixtures', 'Water connections', 'Drainage system'],
            'typical_duration' => '8-10 days'
        ],
        [
            'stage_name' => 'Finishing',
            'typical_percentage' => 10,
            'description' => 'Final finishing and handover',
            'key_deliverables' => ['Painting', 'Flooring', 'Final fixtures', 'Cleanup'],
            'typical_duration' => '15-20 days'
        ]
    ];
    
    // Get existing stage payment requests
    $stmt = $pdo->prepare("
        SELECT 
            stage_name,
            SUM(CASE WHEN status = 'paid' THEN COALESCE(approved_amount, requested_amount) ELSE 0 END) as amount_paid,
            SUM(CASE WHEN status = 'pending' THEN requested_amount ELSE 0 END) as pending_amount,
            MAX(CASE WHEN status = 'paid' THEN response_date END) as last_payment_date,
            COUNT(*) as request_count,
            MAX(completion_percentage) as completion_percentage,
            MAX(CASE WHEN status IN ('pending', 'approved') THEN 1 ELSE 0 END) as has_pending_request
        FROM stage_payment_requests 
        WHERE project_id = ? 
        GROUP BY stage_name
    ");
    $stmt->execute([$project_id]);
    $existing_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create a lookup array for existing payments
    $payment_lookup = [];
    foreach ($existing_payments as $payment) {
        $payment_lookup[$payment['stage_name']] = $payment;
    }
    
    // Get construction progress data if available
    $stmt = $pdo->prepare("
        SELECT 
            stage_name,
            MAX(completion_percentage) as progress_completion,
            MAX(created_at) as last_update
        FROM construction_progress 
        WHERE project_id = ? 
        GROUP BY stage_name
    ");
    $stmt->execute([$project_id]);
    $progress_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create progress lookup
    $progress_lookup = [];
    foreach ($progress_data as $progress) {
        $progress_lookup[$progress['stage_name']] = $progress;
    }
    
    // Build stage payment breakdown
    $stages = [];
    $total_paid = 0;
    $total_pending = 0;
    $stages_completed = 0;
    $stages_in_progress = 0;
    
    foreach ($standard_stages as $stage) {
        $stage_name = $stage['stage_name'];
        $typical_amount = round(($total_project_cost * $stage['typical_percentage']) / 100);
        
        // Get existing payment data
        $payment_data = $payment_lookup[$stage_name] ?? null;
        $progress_data_stage = $progress_lookup[$stage_name] ?? null;
        
        $amount_paid = $payment_data['amount_paid'] ?? 0;
        $pending_amount = $payment_data['pending_amount'] ?? 0;
        $completion_percentage = max(
            $payment_data['completion_percentage'] ?? 0,
            $progress_data_stage['progress_completion'] ?? 0
        );
        
        // Determine stage status
        $status = 'not_started';
        if ($amount_paid >= $typical_amount) {
            $status = 'paid';
            $stages_completed++;
        } elseif ($pending_amount > 0) {
            $status = 'payment_requested';
            $stages_in_progress++;
        } elseif ($completion_percentage >= 90) {
            $status = 'completed';
            $stages_in_progress++;
        } elseif ($completion_percentage > 0) {
            $status = 'in_progress';
            $stages_in_progress++;
        }
        
        $can_request_payment = ($status === 'completed' && $amount_paid < $typical_amount && $pending_amount == 0);
        
        $stages[] = [
            'stage_name' => $stage_name,
            'typical_percentage' => $stage['typical_percentage'],
            'typical_amount' => $typical_amount,
            'description' => $stage['description'],
            'key_deliverables' => $stage['key_deliverables'],
            'typical_duration' => $stage['typical_duration'],
            'status' => $status,
            'completion_percentage' => $completion_percentage,
            'amount_requested' => ($payment_data['request_count'] ?? 0) > 0 ? $pending_amount + $amount_paid : 0,
            'amount_paid' => $amount_paid,
            'pending_amount' => $pending_amount,
            'can_request_payment' => $can_request_payment,
            'last_payment_date' => $payment_data['last_payment_date'] ?? null,
            'request_count' => $payment_data['request_count'] ?? 0,
            'has_pending_request' => ($payment_data['has_pending_request'] ?? 0) > 0
        ];
        
        $total_paid += $amount_paid;
        $total_pending += $pending_amount;
    }
    
    $total_available = $total_project_cost - $total_paid - $total_pending;
    
    // Prepare summary
    $summary = [
        'total_project_cost' => $total_project_cost,
        'total_paid' => $total_paid,
        'total_pending' => $total_pending,
        'total_available' => max(0, $total_available),
        'stages_completed' => $stages_completed,
        'stages_in_progress' => $stages_in_progress,
        'total_stages' => count($standard_stages),
        'project_completion_percentage' => $total_project_cost > 0 ? round(($total_paid / $total_project_cost) * 100, 1) : 0
    ];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'stages' => $stages,
            'summary' => $summary,
            'project_info' => [
                'project_id' => $project_id,
                'project_name' => $project['project_name'] ?? 'Construction Project',
                'contractor_id' => $contractor_id,
                'total_cost' => $total_project_cost
            ]
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>