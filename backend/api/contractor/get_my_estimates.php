<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');

require_once '../../config/database.php';

try {
    $contractor_id = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : 0;
    if ($contractor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing contractor_id']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Ensure table exists
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
        INDEX(send_id), INDEX(contractor_id)
    )");

    $q = $db->prepare("SELECT 
                           e.id, e.send_id, e.contractor_id, e.materials, e.cost_breakdown, e.total_cost, e.timeline, e.notes, e.structured, e.status, e.created_at,
                           e.homeowner_feedback, e.homeowner_action_at,
                           s.homeowner_id,
                           CONCAT(h.first_name, ' ', h.last_name) AS homeowner_name,
                           h.email AS homeowner_email,
                           ci.message AS homeowner_message,
                           ci.acknowledged_at,
                           ci.due_date
                        FROM contractor_send_estimates e
                        LEFT JOIN contractor_layout_sends s ON s.id = e.send_id
                        LEFT JOIN users h ON h.id = s.homeowner_id
                        LEFT JOIN contractor_inbox ci ON ci.estimate_id = e.id AND ci.type = 'estimate_message'
                        WHERE e.contractor_id = :cid AND (e.status IS NULL OR e.status != 'deleted')
                        ORDER BY e.created_at DESC");
    $q->bindValue(':cid', $contractor_id, PDO::PARAM_INT);
    $q->execute();
    $rows = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
    echo json_encode(['success' => true, 'estimates' => $rows]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}



