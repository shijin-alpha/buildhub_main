<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input && isset($input['user_id'])) {
        $_SESSION['user_id'] = $input['user_id'];
        $_SESSION['user_type'] = $input['user_type'] ?? 'contractor';
        
        echo json_encode([
            'success' => true,
            'message' => 'Test session created successfully',
            'session_data' => [
                'user_id' => $_SESSION['user_id'],
                'user_type' => $_SESSION['user_type']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid input data'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Only POST method allowed'
    ]);
}
?>