<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');

require_once '../../config/database.php';

try {
    $homeowner_id = isset($_GET['homeowner_id']) ? (int)$_GET['homeowner_id'] : 0;
    if ($homeowner_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing homeowner_id']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Ensure tables exist
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

    // Ensure payment table exists
    $db->exec("CREATE TABLE IF NOT EXISTS contractor_estimate_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        homeowner_id INT NOT NULL,
        estimate_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'INR',
        payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
        razorpay_order_id VARCHAR(255),
        razorpay_payment_id VARCHAR(255),
        razorpay_signature VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX(homeowner_id), INDEX(estimate_id), INDEX(payment_status)
    )");

    $sql = "SELECT e.id, e.send_id, e.contractor_id, e.total_cost, e.timeline, e.notes, e.structured, e.status, e.created_at,
                   s.homeowner_id, s.layout_id, s.design_id, s.acknowledged_at, s.due_date,
                   CONCAT(c.first_name, ' ', c.last_name) AS contractor_name, c.email AS contractor_email,
                   (SELECT COUNT(*) FROM contractor_estimate_payments p WHERE p.homeowner_id = s.homeowner_id AND p.estimate_id = e.id AND p.payment_status = 'completed') AS is_paid
            FROM contractor_send_estimates e
            INNER JOIN contractor_layout_sends s ON s.id = e.send_id
            LEFT JOIN users c ON c.id = e.contractor_id
            WHERE s.homeowner_id = :hid
            ORDER BY e.created_at DESC";
    $q = $db->prepare($sql);
    $q->bindValue(':hid', $homeowner_id, PDO::PARAM_INT);
    $q->execute();
    $rows = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
    echo json_encode(['success' => true, 'estimates' => $rows]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}



