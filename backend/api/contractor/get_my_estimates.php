<?php
// Suppress warnings to prevent JSON corruption
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);


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

    // Ensure both tables exist
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

    $db->exec("CREATE TABLE IF NOT EXISTS contractor_estimates (
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
        INDEX idx_send (send_id)
    )");

    // Get estimates from both tables and combine them
    $estimates = [];

    // Get from new contractor_estimates table (from EstimationForm submissions)
    $newEstimatesQuery = $db->prepare("
        SELECT 
            e.id,
            e.contractor_id,
            e.homeowner_id,
            e.send_id,
            e.project_name,
            e.location,
            e.client_name,
            e.timeline,
            e.notes,
            e.status,
            e.created_at,
            e.totals_data,
            e.materials_data,
            e.labor_data,
            e.utilities_data,
            e.misc_data,
            CONCAT(h.first_name, ' ', h.last_name) AS homeowner_name,
            h.email AS homeowner_email,
            'new' as source_table
        FROM contractor_estimates e
        LEFT JOIN users h ON h.id = e.homeowner_id
        WHERE e.contractor_id = :cid AND (e.status IS NULL OR e.status != 'deleted')
    ");
    $newEstimatesQuery->bindValue(':cid', $contractor_id, PDO::PARAM_INT);
    $newEstimatesQuery->execute();
    $newEstimates = $newEstimatesQuery->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Process new estimates to match expected format
    foreach ($newEstimates as $est) {
        $totalsData = json_decode($est['totals_data'], true) ?? [];
        $materialsData = json_decode($est['materials_data'], true) ?? [];
        $laborData = json_decode($est['labor_data'], true) ?? [];
        $utilitiesData = json_decode($est['utilities_data'], true) ?? [];
        $miscData = json_decode($est['misc_data'], true) ?? [];

        // Calculate total cost from totals_data if available
        $totalCost = $totalsData['grand'] ?? 0;
        if (!$totalCost) {
            $totalCost = ($totalsData['materials'] ?? 0) + 
                        ($totalsData['labor'] ?? 0) + 
                        ($totalsData['utilities'] ?? 0) + 
                        ($totalsData['misc'] ?? 0);
        }

        // Create structured data for compatibility
        $structured = [
            'project_name' => $est['project_name'],
            'project_address' => $est['location'],
            'client_name' => $est['client_name'],
            'materials' => $materialsData,
            'labor' => $laborData,
            'utilities' => $utilitiesData,
            'misc' => $miscData,
            'totals' => $totalsData
        ];

        $estimates[] = [
            'id' => $est['id'],
            'send_id' => $est['send_id'],
            'contractor_id' => $est['contractor_id'],
            'homeowner_id' => $est['homeowner_id'],
            'materials' => json_encode($materialsData),
            'cost_breakdown' => $est['notes'],
            'total_cost' => $totalCost,
            'timeline' => $est['timeline'],
            'notes' => $est['notes'],
            'structured' => json_encode($structured),
            'structured_data' => json_encode($structured),
            'status' => $est['status'],
            'created_at' => $est['created_at'],
            'homeowner_name' => $est['homeowner_name'],
            'homeowner_email' => $est['homeowner_email'],
            'source_table' => 'new'
        ];
    }

    // Get from legacy contractor_send_estimates table
    $legacyEstimatesQuery = $db->prepare("
        SELECT 
            e.id, e.send_id, e.contractor_id, e.materials, e.cost_breakdown, e.total_cost, 
            e.timeline, e.notes, e.structured, e.status, e.created_at,
            e.homeowner_feedback, e.homeowner_action_at,
            s.homeowner_id,
            CONCAT(h.first_name, ' ', h.last_name) AS homeowner_name,
            h.email AS homeowner_email,
            ci.message AS homeowner_message,
            ci.acknowledged_at,
            ci.due_date,
            'legacy' as source_table
        FROM contractor_send_estimates e
        LEFT JOIN contractor_layout_sends s ON s.id = e.send_id
        LEFT JOIN users h ON h.id = s.homeowner_id
        LEFT JOIN contractor_inbox ci ON ci.estimate_id = e.id AND ci.type = 'estimate_message'
        WHERE e.contractor_id = :cid AND (e.status IS NULL OR e.status != 'deleted')
    ");
    $legacyEstimatesQuery->bindValue(':cid', $contractor_id, PDO::PARAM_INT);
    $legacyEstimatesQuery->execute();
    $legacyEstimates = $legacyEstimatesQuery->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Add legacy estimates to the combined array
    foreach ($legacyEstimates as $est) {
        $estimates[] = $est;
    }

    // Sort by created_at descending
    usort($estimates, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    echo json_encode([
        'success' => true, 
        'estimates' => $estimates,
        'count' => count($estimates),
        'new_estimates_count' => count($newEstimates),
        'legacy_estimates_count' => count($legacyEstimates)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}



