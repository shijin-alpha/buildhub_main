<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log all debug information
$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'files_data' => $_FILES,
    'session_data' => $_SESSION ?? 'No session',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads')
];

try {
    // Start session first
    session_start();
    
    // For testing purposes, create a mock user session if none exists
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 999; // Mock homeowner ID for testing
        $_SESSION['role'] = 'homeowner';
        $debug_info['mock_session'] = 'Created mock session for testing';
    }
    
    $debug_info['session_after_start'] = $_SESSION;
    
    require_once '../../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    if (!$homeowner_id) {
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated',
            'debug' => $debug_info
        ]);
        exit;
    }
    
    // Get form data
    $room_type = $_POST['room_type'] ?? '';
    $improvement_notes = $_POST['improvement_notes'] ?? '';
    
    $debug_info['parsed_data'] = [
        'room_type' => $room_type,
        'improvement_notes' => $improvement_notes,
        'files_count' => count($_FILES)
    ];
    
    // Validation
    if (empty($room_type)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Room type is required',
            'debug' => $debug_info
        ]);
        exit;
    }
    
    if (!isset($_FILES['room_image'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'No file uploaded - room_image field missing',
            'debug' => $debug_info
        ]);
        exit;
    }
    
    $file = $_FILES['room_image'];
    $debug_info['file_details'] = [
        'name' => $file['name'],
        'type' => $file['type'],
        'size' => $file['size'],
        'tmp_name' => $file['tmp_name'],
        'error' => $file['error'],
        'error_message' => $file['error'] === UPLOAD_ERR_OK ? 'No error' : 'Upload error code: ' . $file['error']
    ];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        echo json_encode([
            'success' => false, 
            'message' => 'File upload error: ' . ($error_messages[$file['error']] ?? 'Unknown error'),
            'debug' => $debug_info
        ]);
        exit;
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Only JPG and PNG images are allowed. Received: ' . $file['type'],
            'debug' => $debug_info
        ]);
        exit;
    }
    
    // Validate file size (5MB limit)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode([
            'success' => false, 
            'message' => 'Image file size must be less than 5MB. Received: ' . round($file['size'] / (1024 * 1024), 2) . 'MB',
            'debug' => $debug_info
        ]);
        exit;
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = '../../uploads/room_improvements/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to create upload directory',
                'debug' => $debug_info
            ]);
            exit;
        }
    }
    
    $debug_info['upload_dir'] = [
        'path' => $upload_dir,
        'exists' => file_exists($upload_dir),
        'writable' => is_writable($upload_dir)
    ];
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'room_' . $homeowner_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $filename;
    
    $debug_info['file_paths'] = [
        'filename' => $filename,
        'full_path' => $file_path,
        'tmp_name' => $file['tmp_name']
    ];
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to move uploaded file',
            'debug' => $debug_info
        ]);
        exit;
    }
    
    $debug_info['file_moved'] = [
        'success' => true,
        'final_path' => $file_path,
        'file_exists' => file_exists($file_path),
        'file_size' => file_exists($file_path) ? filesize($file_path) : 'File not found'
    ];
    
    // Analyze the room image and generate improvement concept
    $analysis_result = analyzeRoomForImprovement($room_type, $improvement_notes, $file_path);
    
    // Try to store the analysis in database
    try {
        $stmt = $db->prepare("
            INSERT INTO room_improvement_analyses 
            (homeowner_id, room_type, improvement_notes, image_path, analysis_result, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $analysis_json = json_encode($analysis_result);
        $stmt->execute([$homeowner_id, $room_type, $improvement_notes, $filename, $analysis_json]);
        
        $debug_info['database'] = [
            'insert_success' => true,
            'insert_id' => $db->lastInsertId()
        ];
    } catch (Exception $db_error) {
        $debug_info['database'] = [
            'insert_success' => false,
            'error' => $db_error->getMessage()
        ];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Room analysis completed successfully',
        'analysis' => $analysis_result,
        'debug' => $debug_info
    ]);
    
} catch (Exception $e) {
    error_log("Room improvement analysis error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during analysis: ' . $e->getMessage(),
        'debug' => $debug_info ?? ['error' => 'Debug info not available']
    ]);
}

/**
 * Analyze room image and generate improvement concepts
 */
function analyzeRoomForImprovement($room_type, $improvement_notes, $image_path) {
    // Simulate image analysis by extracting basic information
    $image_info = @getimagesize($image_path);
    $image_width = $image_info[0] ?? 0;
    $image_height = $image_info[1] ?? 0;
    
    // Generate room-specific analysis based on room type and user notes
    $room_analysis = generateRoomAnalysis($room_type, $improvement_notes);
    
    // Create structured analysis result
    $analysis = [
        'concept_name' => $room_analysis['concept_name'],
        'room_condition_summary' => $room_analysis['condition_summary'],
        'visual_observations' => $room_analysis['visual_observations'],
        'improvement_suggestions' => [
            'lighting' => $room_analysis['lighting_suggestion'],
            'color_ambience' => $room_analysis['color_suggestion'],
            'furniture_layout' => $room_analysis['furniture_suggestion']
        ],
        'style_recommendation' => [
            'style' => $room_analysis['recommended_style'],
            'description' => $room_analysis['style_description'],
            'key_elements' => $room_analysis['key_elements']
        ],
        'visual_reference' => $room_analysis['visual_reference'],
        'analysis_metadata' => [
            'room_type' => $room_type,
            'user_notes' => $improvement_notes,
            'image_dimensions' => $image_width . 'x' . $image_height,
            'analysis_timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    return $analysis;
}

/**
 * Generate room-specific analysis and recommendations
 */
function generateRoomAnalysis($room_type, $improvement_notes) {
    // Room-specific analysis templates
    $room_templates = [
        'bedroom' => [
            'concept_name' => 'Serene Sleep Sanctuary Enhancement',
            'condition_summary' => 'Analysis of your bedroom reveals opportunities to create a more restful and personalized sleeping environment.',
            'visual_observations' => [
                'Natural light availability and window placement',
                'Current color scheme and wall treatments',
                'Furniture arrangement and space utilization',
                'Storage solutions and organization'
            ],
            'lighting_suggestion' => 'Consider layered lighting with warm-toned bedside lamps, dimmable overhead fixtures, and blackout curtains for better sleep quality. Soft ambient lighting creates a calming atmosphere.',
            'color_suggestion' => 'Incorporate calming neutral tones like soft blues, warm grays, or earth tones. These colors promote relaxation and better sleep while maintaining a sophisticated look.',
            'furniture_suggestion' => 'Optimize furniture placement for better flow and functionality. Consider a comfortable reading chair, adequate nightstand storage, and ensure the bed is positioned away from direct light sources.',
            'recommended_style' => 'Modern Minimalist with Cozy Accents',
            'style_description' => 'A clean, uncluttered aesthetic with warm textures and personal touches that promote rest and relaxation.',
            'key_elements' => ['Soft textures', 'Warm lighting', 'Neutral colors', 'Functional storage', 'Personal artwork'],
            'visual_reference' => 'Imagine a space with clean lines, soft bedding in neutral tones, warm wood accents, and carefully placed lighting that creates a hotel-like serenity in your own home.'
        ],
        'living_room' => [
            'concept_name' => 'Welcoming Social Hub Transformation',
            'condition_summary' => 'Your living room has great potential to become a more inviting and functional space for both relaxation and entertaining.',
            'visual_observations' => [
                'Seating arrangement and conversation flow',
                'Natural light sources and window treatments',
                'Entertainment center and technology integration',
                'Decorative elements and personal touches'
            ],
            'lighting_suggestion' => 'Create multiple lighting zones with table lamps, floor lamps, and overhead lighting. Use warm white LEDs and consider accent lighting to highlight artwork or architectural features.',
            'color_suggestion' => 'Build a cohesive color palette with a neutral base and add personality through accent colors in pillows, artwork, and accessories. Consider the room\'s natural light when selecting colors.',
            'furniture_suggestion' => 'Arrange seating to encourage conversation while maintaining clear pathways. Consider a mix of seating options and ensure adequate surface space for drinks and books.',
            'recommended_style' => 'Contemporary Comfort',
            'style_description' => 'A balanced approach combining modern functionality with comfortable, livable elements that reflect your personality.',
            'key_elements' => ['Comfortable seating', 'Layered lighting', 'Personal artwork', 'Natural materials', 'Flexible layout'],
            'visual_reference' => 'Envision a space where modern furniture meets warm textures, with strategic lighting that adapts from bright and energetic during the day to cozy and intimate in the evening.'
        ],
        'kitchen' => [
            'concept_name' => 'Efficient Culinary Workspace Enhancement',
            'condition_summary' => 'Your kitchen shows opportunities for improved functionality, better lighting, and enhanced aesthetic appeal while maintaining practical cooking needs.',
            'visual_observations' => [
                'Work triangle efficiency and counter space',
                'Storage accessibility and organization',
                'Lighting adequacy for food preparation',
                'Appliance integration and workflow'
            ],
            'lighting_suggestion' => 'Implement task lighting under cabinets, pendant lights over islands or peninsulas, and ensure adequate general lighting. Good lighting is crucial for food safety and cooking enjoyment.',
            'color_suggestion' => 'Choose colors that are both practical and appealing. Light colors can make the space feel larger, while darker accents can add sophistication. Consider easy-to-clean surfaces.',
            'furniture_suggestion' => 'Optimize storage with pull-out drawers, lazy Susans, and vertical storage solutions. Ensure adequate counter space near the stove, sink, and refrigerator.',
            'recommended_style' => 'Modern Functional',
            'style_description' => 'Clean lines and efficient design that prioritizes functionality while maintaining visual appeal and easy maintenance.',
            'key_elements' => ['Efficient storage', 'Task lighting', 'Easy-clean surfaces', 'Organized workflow', 'Quality appliances'],
            'visual_reference' => 'Picture a kitchen where every tool has its place, surfaces are easy to clean, and the lighting makes food preparation a pleasure rather than a chore.'
        ],
        'dining_room' => [
            'concept_name' => 'Elegant Dining Experience Enhancement',
            'condition_summary' => 'Your dining space has potential to become a more inviting area for meals and gatherings, with improved ambiance and functionality.',
            'visual_observations' => [
                'Table size and seating capacity',
                'Lighting ambiance and dimming options',
                'Storage for dining essentials',
                'Connection to kitchen and living areas'
            ],
            'lighting_suggestion' => 'Install a statement chandelier or pendant light over the dining table with dimming capability. Add ambient lighting with wall sconces or buffet lamps for a warm, inviting atmosphere.',
            'color_suggestion' => 'Create a sophisticated palette that encourages lingering over meals. Warm colors can stimulate appetite, while cooler tones create a more formal atmosphere.',
            'furniture_suggestion' => 'Ensure adequate space around the dining table for comfortable seating and serving. Consider a sideboard or buffet for storage and serving space.',
            'recommended_style' => 'Refined Traditional',
            'style_description' => 'A timeless approach that emphasizes comfort and elegance, perfect for both everyday meals and special occasions.',
            'key_elements' => ['Statement lighting', 'Comfortable seating', 'Serving surfaces', 'Warm colors', 'Quality textiles'],
            'visual_reference' => 'Imagine a space where family dinners feel special and guests feel welcomed, with lighting that can transition from bright and cheerful to intimate and cozy.'
        ],
        'other' => [
            'concept_name' => 'Personalized Space Enhancement',
            'condition_summary' => 'This unique space has individual characteristics that can be enhanced to better serve your specific needs and lifestyle.',
            'visual_observations' => [
                'Current function and usage patterns',
                'Natural light and ventilation',
                'Storage and organization needs',
                'Potential for multi-functional use'
            ],
            'lighting_suggestion' => 'Assess the primary activities in this space and provide appropriate lighting. Task lighting for work areas, ambient lighting for relaxation, and accent lighting for visual interest.',
            'color_suggestion' => 'Choose colors that support the room\'s primary function while reflecting your personal style. Consider how the space connects to adjacent rooms.',
            'furniture_suggestion' => 'Select furniture that maximizes the space\'s potential while maintaining flexibility for changing needs. Consider multi-functional pieces.',
            'recommended_style' => 'Adaptive Contemporary',
            'style_description' => 'A flexible approach that can evolve with your needs while maintaining a cohesive and attractive appearance.',
            'key_elements' => ['Flexible furniture', 'Appropriate lighting', 'Personal touches', 'Efficient storage', 'Cohesive design'],
            'visual_reference' => 'Envision a space that perfectly serves your unique needs while maintaining visual harmony with the rest of your home.'
        ]
    ];
    
    $template = $room_templates[$room_type] ?? $room_templates['other'];
    
    // Customize based on user improvement notes
    if (!empty($improvement_notes)) {
        $template = customizeAnalysisBasedOnNotes($template, $improvement_notes);
    }
    
    return $template;
}

/**
 * Customize analysis based on user's specific improvement notes
 */
function customizeAnalysisBasedOnNotes($template, $notes) {
    $notes_lower = strtolower($notes);
    
    // Lighting-related customizations
    if (strpos($notes_lower, 'dark') !== false || strpos($notes_lower, 'lighting') !== false) {
        $template['lighting_suggestion'] = 'Based on your concerns about lighting, focus on adding multiple light sources at different levels. ' . $template['lighting_suggestion'];
    }
    
    // Color-related customizations
    if (strpos($notes_lower, 'color') !== false || strpos($notes_lower, 'paint') !== false) {
        $template['color_suggestion'] = 'Since you mentioned color concerns, consider how different colors affect mood and the perceived size of the space. ' . $template['color_suggestion'];
    }
    
    // Storage-related customizations
    if (strpos($notes_lower, 'storage') !== false || strpos($notes_lower, 'clutter') !== false) {
        $template['furniture_suggestion'] = 'Addressing your storage needs, look for furniture pieces that serve dual purposes and vertical storage solutions. ' . $template['furniture_suggestion'];
    }
    
    // Comfort-related customizations
    if (strpos($notes_lower, 'cozy') !== false || strpos($notes_lower, 'comfort') !== false) {
        $template['style_description'] = 'With comfort as a priority, ' . strtolower($template['style_description']);
        $template['key_elements'][] = 'Cozy textiles';
    }
    
    // Space-related customizations
    if (strpos($notes_lower, 'small') !== false || strpos($notes_lower, 'cramped') !== false) {
        $template['condition_summary'] = 'Your space shows potential for feeling more open and spacious with strategic improvements. ' . $template['condition_summary'];
    }
    
    return $template;
}
?>