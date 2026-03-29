<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Prevent any output before JSON headers
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Clear any previous output
ob_clean();

try {
    require_once __DIR__ . '/../../config/database.php';

    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    // For testing purposes, create a mock user session if none exists
    if (!$homeowner_id) {
        $_SESSION['user_id'] = 999;
        $_SESSION['role'] = 'homeowner';
        $homeowner_id = 999;
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid JSON input'
        ]);
        exit;
    }
    
    $validation_result = $input['validation_result'] ?? [];
    $user_feedback = $input['user_feedback'] ?? '';
    $room_type = $input['room_type'] ?? '';
    $image_url = $input['image_url'] ?? '';
    
    if (empty($user_feedback)) {
        echo json_encode([
            'success' => false, 
            'message' => 'User feedback is required'
        ]);
        exit;
    }
    
    // Create validation feedback table if it doesn't exist
    $create_table_sql = "
        CREATE TABLE IF NOT EXISTS validation_feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            homeowner_id INT NOT NULL,
            room_type VARCHAR(50) NOT NULL,
            image_url TEXT,
            validation_result JSON,
            user_feedback TEXT NOT NULL,
            feedback_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed BOOLEAN DEFAULT FALSE,
            INDEX idx_homeowner_id (homeowner_id),
            INDEX idx_room_type (room_type),
            INDEX idx_timestamp (feedback_timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $db->exec($create_table_sql);
    
    // Insert feedback record
    $stmt = $db->prepare("
        INSERT INTO validation_feedback 
        (homeowner_id, room_type, image_url, validation_result, user_feedback, feedback_timestamp) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $validation_json = json_encode($validation_result);
    $stmt->execute([
        $homeowner_id, 
        $room_type, 
        $image_url, 
        $validation_json, 
        $user_feedback
    ]);
    
    $feedback_id = $db->lastInsertId();
    
    // Log feedback for analysis
    error_log("Validation feedback received - ID: $feedback_id, Room Type: $room_type, Homeowner: $homeowner_id");
    
    // Analyze feedback for immediate insights
    $feedback_analysis = analyzeFeedback($user_feedback, $validation_result);
    
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your feedback! This helps improve our image validation system.',
        'feedback_id' => (int)$feedback_id,
        'analysis' => $feedback_analysis
    ]);
    
} catch (Exception $e) {
    // Clear any output that might have been generated
    ob_clean();
    
    error_log("Validation feedback submission error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while submitting feedback',
        'debug' => [
            'error_type' => get_class($e),
            'error_message' => $e->getMessage()
        ]
    ]);
} catch (Error $e) {
    // Handle PHP fatal errors
    ob_clean();
    
    error_log("PHP Error in validation feedback submission: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'A system error occurred while submitting feedback',
        'debug' => [
            'error_type' => 'PHP Error',
            'error_message' => $e->getMessage()
        ]
    ]);
}

/**
 * Analyze user feedback for immediate insights
 */
function analyzeFeedback($user_feedback, $validation_result) {
    $feedback_lower = strtolower($user_feedback);
    $confidence_score = $validation_result['confidence_score'] ?? 0;
    
    $analysis = [
        'sentiment' => 'neutral',
        'agreement_with_validation' => 'unknown',
        'key_concerns' => [],
        'improvement_suggestions' => []
    ];
    
    // Sentiment analysis (basic)
    $positive_words = ['good', 'great', 'perfect', 'love', 'like', 'appropriate', 'correct', 'accurate'];
    $negative_words = ['bad', 'wrong', 'inappropriate', 'hate', 'dislike', 'incorrect', 'poor', 'terrible'];
    
    $positive_count = 0;
    $negative_count = 0;
    
    foreach ($positive_words as $word) {
        if (strpos($feedback_lower, $word) !== false) {
            $positive_count++;
        }
    }
    
    foreach ($negative_words as $word) {
        if (strpos($feedback_lower, $word) !== false) {
            $negative_count++;
        }
    }
    
    if ($positive_count > $negative_count) {
        $analysis['sentiment'] = 'positive';
    } elseif ($negative_count > $positive_count) {
        $analysis['sentiment'] = 'negative';
    }
    
    // Agreement with validation
    if ($confidence_score < 70 && $analysis['sentiment'] === 'negative') {
        $analysis['agreement_with_validation'] = 'agrees';
    } elseif ($confidence_score >= 70 && $analysis['sentiment'] === 'positive') {
        $analysis['agreement_with_validation'] = 'agrees';
    } elseif ($confidence_score < 70 && $analysis['sentiment'] === 'positive') {
        $analysis['agreement_with_validation'] = 'disagrees';
    } elseif ($confidence_score >= 70 && $analysis['sentiment'] === 'negative') {
        $analysis['agreement_with_validation'] = 'disagrees';
    }
    
    // Extract key concerns
    $concern_keywords = [
        'objects' => ['object', 'furniture', 'item', 'thing'],
        'style' => ['style', 'design', 'look', 'appearance'],
        'color' => ['color', 'colour', 'paint', 'shade'],
        'room_type' => ['room', 'bedroom', 'kitchen', 'bathroom', 'living'],
        'relevance' => ['relevant', 'appropriate', 'suitable', 'fit']
    ];
    
    foreach ($concern_keywords as $concern => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($feedback_lower, $keyword) !== false) {
                $analysis['key_concerns'][] = $concern;
                break;
            }
        }
    }
    
    $analysis['key_concerns'] = array_unique($analysis['key_concerns']);
    
    // Generate improvement suggestions based on feedback
    if ($analysis['agreement_with_validation'] === 'disagrees') {
        if ($confidence_score < 70) {
            $analysis['improvement_suggestions'][] = 'Validation system may be too strict - consider adjusting thresholds';
        } else {
            $analysis['improvement_suggestions'][] = 'Validation system may be missing important context - review validation rules';
        }
    }
    
    if (in_array('objects', $analysis['key_concerns'])) {
        $analysis['improvement_suggestions'][] = 'Review object detection accuracy and room-specific object rules';
    }
    
    if (in_array('style', $analysis['key_concerns'])) {
        $analysis['improvement_suggestions'][] = 'Improve style appropriateness validation for this room type';
    }
    
    return $analysis;
}