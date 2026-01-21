<?php
header('Content-Type: application/json');
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Test query to fetch layout_requests data
    $query = "SELECT 
                id, 
                user_id, 
                homeowner_id, 
                plot_size, 
                building_size,
                budget_range, 
                requirements, 
                location, 
                timeline,
                preferred_style,
                num_floors,
                floor_rooms,
                status,
                created_at
              FROM layout_requests 
              WHERE status != 'deleted' 
              ORDER BY created_at DESC 
              LIMIT 5";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $requests = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Parse requirements
        $requirements_parsed = json_decode($row['requirements'], true);
        
        // Parse floor_rooms
        $floor_rooms_parsed = null;
        if ($row['floor_rooms']) {
            $floor_rooms_parsed = json_decode($row['floor_rooms'], true);
        }
        
        // Parse plot_size to extract numeric value
        $plot_size_numeric = null;
        if ($row['plot_size']) {
            $plotSize = strtolower($row['plot_size']);
            
            if (strpos($plotSize, 'x') !== false) {
                // Format like "30x40" or "30x40 feet"
                $dimensions = explode('x', $plotSize);
                if (count($dimensions) === 2) {
                    $width = floatval(preg_replace('/[^0-9.]/', '', trim($dimensions[0])));
                    $height = floatval(preg_replace('/[^0-9.]/', '', trim($dimensions[1])));
                    if ($width > 0 && $height > 0) {
                        $plot_size_numeric = $width * $height;
                    }
                }
            } else {
                // Format like "2000", "2000 sq ft", etc.
                $plot_size_numeric = floatval(preg_replace('/[^0-9.]/', '', $plotSize));
            }
        }
        
        $requests[] = [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'homeowner_id' => (int)$row['homeowner_id'],
            'plot_size_original' => $row['plot_size'],
            'plot_size_numeric' => $plot_size_numeric,
            'building_size' => $row['building_size'],
            'budget_range' => $row['budget_range'],
            'requirements_raw' => $row['requirements'],
            'requirements_parsed' => $requirements_parsed,
            'location' => $row['location'],
            'timeline' => $row['timeline'],
            'preferred_style' => $row['preferred_style'],
            'num_floors' => $row['num_floors'],
            'floor_rooms_raw' => $row['floor_rooms'],
            'floor_rooms_parsed' => $floor_rooms_parsed,
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Layout requests data fetched successfully',
        'count' => count($requests),
        'requests' => $requests
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching layout requests: ' . $e->getMessage()
    ]);
}
?>