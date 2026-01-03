<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $category = $_GET['category'] ?? null;

    // Build query
    $whereClause = "is_active = 1";
    $params = [];

    if ($category && in_array($category, ['bedroom', 'bathroom', 'kitchen', 'living', 'dining', 'utility', 'outdoor', 'other'])) {
        $whereClause .= " AND category = :category";
        $params[':category'] = $category;
    }

    $query = "
        SELECT id, name, category, default_width, default_height, 
               min_width, min_height, max_width, max_height, 
               color, icon
        FROM room_templates 
        WHERE {$whereClause}
        ORDER BY category, name
    ";

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    $templates = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $templates[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'category' => $row['category'],
            'default_width' => (float)$row['default_width'],
            'default_height' => (float)$row['default_height'],
            'min_width' => (float)$row['min_width'],
            'min_height' => (float)$row['min_height'],
            'max_width' => (float)$row['max_width'],
            'max_height' => (float)$row['max_height'],
            'color' => $row['color'],
            'icon' => $row['icon']
        ];
    }

    // Group by category for easier frontend handling
    $grouped = [];
    foreach ($templates as $template) {
        $cat = $template['category'];
        if (!isset($grouped[$cat])) {
            $grouped[$cat] = [];
        }
        $grouped[$cat][] = $template;
    }

    echo json_encode([
        'success' => true,
        'templates' => $templates,
        'grouped' => $grouped
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>