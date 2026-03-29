<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Database connection
require_once '../../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if user is logged in and is a homeowner
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
    
    // Check if geo_photos table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'geo_photos'");
    if ($tableCheck->rowCount() == 0) {
        echo json_encode([
            'success' => true,
            'data' => [
                'photos' => [],
                'pagination' => [
                    'total' => 0,
                    'limit' => 50,
                    'offset' => 0,
                    'has_more' => false
                ],
                'project_summary' => null
            ],
            'message' => 'Geo photos system not yet initialized. No photos available.'
        ]);
        exit;
    }
    
    $homeowner_id = $_SESSION['user_id'];
    $project_id = $_GET['project_id'] ?? null;
    $limit = intval($_GET['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);
    
    // Build query
    $whereClause = "WHERE gp.homeowner_id = ?";
    $params = [$homeowner_id];
    
    if ($project_id) {
        $whereClause .= " AND gp.project_id = ?";
        $params[] = intval($project_id);
    }
    
    // Get photos with contractor and project details
    $photosStmt = $pdo->prepare("
        SELECT 
            gp.*,
            c.first_name as contractor_first_name,
            c.last_name as contractor_last_name,
            c.email as contractor_email,
            c.phone as contractor_phone,
            lr.requirements as project_requirements,
            lr.budget_range as project_budget,
            lr.plot_size,
            lr.building_size
        FROM geo_photos gp
        LEFT JOIN users c ON gp.contractor_id = c.id
        LEFT JOIN layout_requests lr ON gp.project_id = lr.id
        {$whereClause}
        ORDER BY gp.upload_timestamp DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $photosStmt->execute($params);
    $photos = $photosStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM geo_photos gp
        {$whereClause}
    ");
    $countStmt->execute(array_slice($params, 0, -2)); // Remove limit and offset
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Process photos
    $processedPhotos = [];
    foreach ($photos as $photo) {
        // Parse location data
        $locationData = json_decode($photo['location_data'], true) ?? [];
        
        // Generate photo URL
        $photoUrl = '/buildhub/backend/uploads/geo_photos/' . $photo['filename'];
        
        // Check if file exists
        $filePath = '../../uploads/geo_photos/' . $photo['filename'];
        $fileExists = file_exists($filePath);
        
        $processedPhoto = [
            'id' => $photo['id'],
            'project_id' => $photo['project_id'],
            'filename' => $photo['filename'],
            'original_filename' => $photo['original_filename'],
            'photo_url' => $fileExists ? $photoUrl : null,
            'file_size' => $photo['file_size'],
            'file_size_formatted' => formatFileSize($photo['file_size']),
            'mime_type' => $photo['mime_type'],
            'location' => [
                'latitude' => $photo['latitude'],
                'longitude' => $photo['longitude'],
                'place_name' => $photo['place_name'],
                'accuracy' => $photo['location_accuracy'],
                'full_data' => $locationData
            ],
            'timestamps' => [
                'photo_taken' => $photo['photo_timestamp'],
                'uploaded' => $photo['upload_timestamp'],
                'photo_taken_formatted' => $photo['photo_timestamp'] ? date('M j, Y g:i A', strtotime($photo['photo_timestamp'])) : null,
                'uploaded_formatted' => date('M j, Y g:i A', strtotime($photo['upload_timestamp'])),
                'time_ago' => timeAgo($photo['upload_timestamp'])
            ],
            'contractor' => [
                'id' => $photo['contractor_id'],
                'name' => trim($photo['contractor_first_name'] . ' ' . $photo['contractor_last_name']),
                'email' => $photo['contractor_email'],
                'phone' => $photo['contractor_phone']
            ],
            'project' => [
                'requirements' => $photo['project_requirements'],
                'budget_range' => $photo['project_budget'],
                'plot_size' => $photo['plot_size'],
                'building_size' => $photo['building_size']
            ],
            'viewing' => [
                'viewed' => (bool)$photo['homeowner_viewed'],
                'viewed_at' => $photo['homeowner_viewed_at'],
                'viewed_at_formatted' => $photo['homeowner_viewed_at'] ? date('M j, Y g:i A', strtotime($photo['homeowner_viewed_at'])) : null
            ],
            'file_exists' => $fileExists
        ];
        
        $processedPhotos[] = $processedPhoto;
    }
    
    // Get project summary if project_id is specified
    $projectSummary = null;
    if ($project_id) {
        $projectStmt = $pdo->prepare("
            SELECT 
                lr.*,
                c.first_name as contractor_first_name,
                c.last_name as contractor_last_name,
                COUNT(gp.id) as total_photos,
                COUNT(CASE WHEN gp.homeowner_viewed = 0 THEN 1 END) as unviewed_photos,
                MAX(gp.upload_timestamp) as latest_photo_date
            FROM layout_requests lr
            LEFT JOIN users c ON lr.contractor_id = c.id
            LEFT JOIN geo_photos gp ON lr.id = gp.project_id
            WHERE lr.id = ? AND lr.homeowner_id = ?
            GROUP BY lr.id
        ");
        $projectStmt->execute([$project_id, $homeowner_id]);
        $projectData = $projectStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($projectData) {
            $projectSummary = [
                'id' => $projectData['id'],
                'requirements' => $projectData['requirements'],
                'budget_range' => $projectData['budget_range'],
                'plot_size' => $projectData['plot_size'],
                'building_size' => $projectData['building_size'],
                'status' => $projectData['status'],
                'contractor_name' => trim($projectData['contractor_first_name'] . ' ' . $projectData['contractor_last_name']),
                'photo_stats' => [
                    'total_photos' => $projectData['total_photos'],
                    'unviewed_photos' => $projectData['unviewed_photos'],
                    'latest_photo_date' => $projectData['latest_photo_date'],
                    'latest_photo_formatted' => $projectData['latest_photo_date'] ? date('M j, Y g:i A', strtotime($projectData['latest_photo_date'])) : null
                ]
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'photos' => $processedPhotos,
            'pagination' => [
                'total' => $totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ],
            'project_summary' => $projectSummary
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get geo photos error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

// Helper functions
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}
?>