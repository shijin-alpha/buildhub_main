<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Direct database connection
    $host = 'localhost';
    $dbname = 'buildhub';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $project_id = 37;
    
    // Test the exact query from the API
    $query = "
        SELECT 
            cumulative_completion_percentage,
            incremental_completion_percentage,
            update_date,
            construction_stage,
            work_done_today,
            working_hours,
            weather_condition
        FROM daily_progress_updates dpu
        WHERE dpu.project_id = ?
        ORDER BY dpu.update_date DESC, dpu.created_at DESC 
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$project_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Found progress data',
            'data' => [
                'project_id' => $project_id,
                'current_progress' => floatval($result['cumulative_completion_percentage']),
                'latest_stage' => $result['construction_stage'],
                'latest_update_date' => $result['update_date'],
                'has_updates' => true,
                'raw_data' => $result
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No progress updates found',
            'data' => [
                'project_id' => $project_id,
                'current_progress' => 0,
                'has_updates' => false
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>