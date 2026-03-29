<?php
header('Content-Type: application/json');

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $result = [
        'success' => true,
        'tables' => []
    ];
    
    // Check stage_payment_requests table
    try {
        $stmt = $db->query("DESCRIBE stage_payment_requests");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result['tables']['stage_payment_requests'] = [
            'exists' => true,
            'columns' => $columns
        ];
        
        // Get sample data
        $stmt = $db->query("SELECT COUNT(*) as count FROM stage_payment_requests");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        $result['tables']['stage_payment_requests']['row_count'] = $count['count'];
        
    } catch (Exception $e) {
        $result['tables']['stage_payment_requests'] = [
            'exists' => false,
            'error' => $e->getMessage()
        ];
    }
    
    // Check projects table
    try {
        $stmt = $db->query("DESCRIBE projects");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result['tables']['projects'] = [
            'exists' => true,
            'columns' => array_column($columns, 'Field')
        ];
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM projects");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        $result['tables']['projects']['row_count'] = $count['count'];
        
    } catch (Exception $e) {
        $result['tables']['projects'] = [
            'exists' => false,
            'error' => $e->getMessage()
        ];
    }
    
    // Check users table
    try {
        $stmt = $db->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result['tables']['users'] = [
            'exists' => true,
            'columns' => array_column($columns, 'Field')
        ];
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        $result['tables']['users']['row_count'] = $count['count'];
        
    } catch (Exception $e) {
        $result['tables']['users'] = [
            'exists' => false,
            'error' => $e->getMessage()
        ];
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error: ' . $e->getMessage()
    ]);
}
?>