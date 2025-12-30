<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    session_start();
    $architect_id = $_SESSION['user_id'] ?? null;

    if (!$architect_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    // Get architect profile from users table
    $query = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.city,
                     u.specialization, u.experience_years, u.status, u.created_at,
                     COALESCE(AVG(r.rating), 0) as avg_rating,
                     COUNT(r.id) as review_count
              FROM users u
              LEFT JOIN reviews r ON r.architect_id = u.id
              WHERE u.id = :architect_id AND u.role = 'architect'
              GROUP BY u.id";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':architect_id', $architect_id);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Architect profile not found']);
        exit;
    }

    // Format the profile data
    $profile = [
        'id' => (int)$user['id'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'full_name' => trim($user['first_name'] . ' ' . $user['last_name']),
        'email' => $user['email'],
        'phone' => $user['phone'] ?? '',
        'city' => $user['city'] ?? '',
        'specialization' => $user['specialization'] ?? '',
        'experience_years' => $user['experience_years'] ? (int)$user['experience_years'] : 0,
        'status' => $user['status'] ?? 'pending',
        'avg_rating' => $user['avg_rating'] ? round((float)$user['avg_rating'], 1) : 0,
        'review_count' => (int)$user['review_count'],
        'created_at' => $user['created_at']
    ];

    echo json_encode([
        'success' => true,
        'profile' => $profile
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching profile: ' . $e->getMessage()
    ]);
}
?>





















