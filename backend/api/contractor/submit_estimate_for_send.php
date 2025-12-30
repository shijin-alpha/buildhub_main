<?php
header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) { header('Access-Control-Allow-Origin: ' . $origin); header('Vary: Origin'); }
else { header('Access-Control-Allow-Origin: http://localhost:3000'); }
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); header('Access-Control-Max-Age: 86400'); exit; }

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $isMultipart = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;
    if ($isMultipart) {
        $send_id = isset($_POST['send_id']) ? (int)$_POST['send_id'] : 0;
        if ($send_id === 0 && isset($_REQUEST['send_id'])) $send_id = (int)$_REQUEST['send_id'];
        $contractor_id = isset($_POST['contractor_id']) ? (int)$_POST['contractor_id'] : 0;
        if ($contractor_id === 0 && isset($_REQUEST['contractor_id'])) $contractor_id = (int)$_REQUEST['contractor_id'];
        $materials = trim((string)($_POST['materials'] ?? ''));
        $cost_breakdown = trim((string)($_POST['cost_breakdown'] ?? ''));
        $total_cost = isset($_POST['total_cost']) ? (string)$_POST['total_cost'] : '';
        $timeline = trim((string)($_POST['timeline'] ?? ''));
        $notes = trim((string)($_POST['notes'] ?? ''));
    } else {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $send_id = isset($input['send_id']) ? (int)$input['send_id'] : (isset($_REQUEST['send_id']) ? (int)$_REQUEST['send_id'] : 0);
        $contractor_id = isset($input['contractor_id']) ? (int)$input['contractor_id'] : (isset($_REQUEST['contractor_id']) ? (int)$_REQUEST['contractor_id'] : 0);
        $materials = trim((string)($input['materials'] ?? ''));
        $cost_breakdown = trim((string)($input['cost_breakdown'] ?? ''));
        $total_cost = isset($input['total_cost']) ? (string)$input['total_cost'] : '';
        $timeline = trim((string)($input['timeline'] ?? ''));
        $notes = trim((string)($input['notes'] ?? ''));
        $_POST['structured'] = isset($input['structured']) ? json_encode($input['structured']) : null;
    }

    if ($send_id <= 0 || $contractor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing send_id or contractor_id', 'debug' => ['send_id' => $send_id, 'contractor_id' => $contractor_id]]);
        exit;
    }

    // Ensure tables
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
        INDEX(send_id)
    )");

    // Ensure legacy databases have the 'structured' column
    try {
        $colChk = $db->query("SHOW COLUMNS FROM contractor_send_estimates LIKE 'structured'");
        if ($colChk && $colChk->rowCount() === 0) {
            $db->exec("ALTER TABLE contractor_send_estimates ADD COLUMN structured LONGTEXT NULL");
        }
    } catch (Exception $e) {
        // Fallback: attempt ALTER without check (ignore if fails)
        try { $db->exec("ALTER TABLE contractor_send_estimates ADD COLUMN structured LONGTEXT NULL"); } catch (Exception $ignored) {}
    }

    $db->exec("CREATE TABLE IF NOT EXISTS contractor_send_estimate_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        estimate_id INT NOT NULL,
        path VARCHAR(512) NOT NULL,
        original_name VARCHAR(255) NULL,
        ext VARCHAR(16) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(estimate_id)
    )");

    // Verify send belongs to contractor
    $chk = $db->prepare("SELECT id FROM contractor_layout_sends WHERE id = :sid AND contractor_id = :cid");
    $chk->bindValue(':sid', $send_id, PDO::PARAM_INT);
    $chk->bindValue(':cid', $contractor_id, PDO::PARAM_INT);
    $chk->execute();
    if (!$chk->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Invalid send or permission denied']);
        exit;
    }

    $ins = $db->prepare("INSERT INTO contractor_send_estimates (send_id, contractor_id, materials, cost_breakdown, total_cost, timeline, notes, structured) VALUES (:sid, :cid, :materials, :cb, :total, :timeline, :notes, :structured)");
    $ins->bindValue(':sid', $send_id, PDO::PARAM_INT);
    $ins->bindValue(':cid', $contractor_id, PDO::PARAM_INT);
    $ins->bindValue(':materials', $materials ?: null, $materials !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $ins->bindValue(':cb', $cost_breakdown ?: null, $cost_breakdown !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $ins->bindValue(':total', $total_cost !== '' ? (float)$total_cost : null, $total_cost !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $ins->bindValue(':timeline', $timeline ?: null, $timeline !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $ins->bindValue(':notes', $notes ?: null, $notes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $ins->bindValue(':structured', isset($_POST['structured']) && $_POST['structured'] ? $_POST['structured'] : null, (isset($_POST['structured']) && $_POST['structured']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $ins->execute();
    $estimateId = (int)$db->lastInsertId();

    // Get homeowner and contractor details for notification
    $detailsStmt = $db->prepare("
        SELECT 
            s.homeowner_id, 
            s.layout_id,
            u.first_name as contractor_first_name,
            u.last_name as contractor_last_name,
            l.title as layout_title
        FROM contractor_layout_sends s
        LEFT JOIN users u ON s.contractor_id = u.id
        LEFT JOIN layout_library l ON s.layout_id = l.id
        WHERE s.id = ?
    ");
    $detailsStmt->execute([$send_id]);
    $details = $detailsStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($details && $details['homeowner_id']) {
        // Create notification for homeowner
        require_once '../../utils/notification_helper.php';
        $contractor_name = trim($details['contractor_first_name'] . ' ' . $details['contractor_last_name']);
        $project_title = $details['layout_title'] ?: 'Your Project';
        
        createEstimateReceivedNotification(
            $db,
            $details['homeowner_id'],
            $estimateId,
            $contractor_name,
            $project_title
        );
    }

    // Handle attachments if multipart
    if ($isMultipart && isset($_FILES['attachments'])) {
        $files = $_FILES['attachments'];
        $uploadDir = realpath(__DIR__ . '/../../uploads');
        if (!$uploadDir) { $uploadDir = __DIR__ . '/../../uploads'; }
        $destDir = rtrim($uploadDir, '/\\') . '/estimates/' . $send_id;
        if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
        $count = is_array($files['name']) ? count($files['name']) : 0;
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $orig = $files['name'][$i];
            $tmp = $files['tmp_name'][$i];
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $safe = uniqid('est_', true) . '.' . $ext;
            $target = $destDir . '/' . $safe;
            if (@move_uploaded_file($tmp, $target)) {
                $relPath = 'buildhub/backend/uploads/estimates/' . $send_id . '/' . $safe;
                $fins = $db->prepare("INSERT INTO contractor_send_estimate_files (estimate_id, path, original_name, ext) VALUES (:eid, :p, :o, :e)");
                $fins->bindValue(':eid', $estimateId, PDO::PARAM_INT);
                $fins->bindValue(':p', $relPath, PDO::PARAM_STR);
                $fins->bindValue(':o', $orig, PDO::PARAM_STR);
                $fins->bindValue(':e', $ext, PDO::PARAM_STR);
                $fins->execute();
            }
        }
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error submitting estimate: ' . $e->getMessage()]);
}


