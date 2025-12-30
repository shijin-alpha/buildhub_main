<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get homeowner ID from session
    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    if (!$homeowner_id) {
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated'
        ]);
        exit;
    }
    
    // Get JSON or form input robustly
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) { $input = $_POST; }
    
    // Debug: Log received data
    error_log('Received raw length: ' . strlen($raw));
    error_log('Received data: ' . json_encode($input));
    
    // Validate required fields
    // Normalize and validate required fields
    $input['plot_size'] = $input['plot_size'] ?? ($input['plot_size'] ?? null);
    $input['budget_range'] = $input['budget_range'] ?? ($input['budget_range'] ?? null);
    if (empty($input['plot_size']) || empty($input['budget_range'])) {
        error_log('Missing required fields: ' . json_encode([
            'plot_size' => $input['plot_size'] ?? null,
            'budget_range' => $input['budget_range'] ?? null,
        ]));
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    $plot_size = $input['plot_size'];
    $building_size = $input['building_size'] ?? null;
    $budget_range = $input['budget_range'];
    $requirements = $input['requirements'] ?? '';
    $location = $input['location'] ?? '';
    $timeline = $input['timeline'] ?? '';
    $selected_layout_id = $input['selected_layout_id'] ?? null;
    $layout_type = $input['layout_type'] ?? 'custom';

    // New structured fields
    $plot_shape = $input['plot_shape'] ?? null;
    $topography = $input['topography'] ?? null;
    $development_laws = $input['development_laws'] ?? null;
    $family_needs = $input['family_needs'] ?? null;
    $rooms = $input['rooms'] ?? null;
    $aesthetic = $input['aesthetic'] ?? null;
    
    // Additional detailed fields
    $orientation = $input['orientation'] ?? null;
    $site_considerations = $input['site_considerations'] ?? null;
    $material_preferences = $input['material_preferences'] ?? null;
    $budget_allocation = $input['budget_allocation'] ?? null;
    $num_floors = $input['num_floors'] ?? null;
    $preferred_style = $input['preferred_style'] ?? null;
    $floor_rooms = $input['floor_rooms'] ?? null;
    
    // Image fields
    $site_images = $input['site_images'] ?? [];
    $reference_images = $input['reference_images'] ?? [];
    $room_images = $input['room_images'] ?? [];
    
    // Create layout_requests table if it doesn't exist (align with existing DB schema only)
    $create_table_query = "CREATE TABLE IF NOT EXISTS layout_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        homeowner_id INT NOT NULL,
        plot_size VARCHAR(100) NOT NULL,
        building_size VARCHAR(100) NULL,
        budget_range VARCHAR(100) NOT NULL,
        requirements TEXT NULL,
        location VARCHAR(255),
        timeline VARCHAR(100),
        selected_layout_id INT NULL,
        layout_type ENUM('custom', 'library') DEFAULT 'custom',
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        layout_file VARCHAR(255),
        orientation VARCHAR(100) NULL,
        site_considerations TEXT NULL,
        material_preferences VARCHAR(255) NULL,
        budget_allocation TEXT NULL,
        num_floors INT NULL,
        preferred_style VARCHAR(100) NULL,
        floor_rooms TEXT NULL,
        site_images TEXT NULL,
        reference_images TEXT NULL,
        room_images TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (selected_layout_id) REFERENCES layout_library(id)
    )";
    $db->exec($create_table_query);
    
    // Add missing columns if they don't exist (for existing tables)
    $db->exec("ALTER TABLE layout_requests ADD COLUMN IF NOT EXISTS orientation VARCHAR(100) NULL");
    $db->exec("ALTER TABLE layout_requests ADD COLUMN IF NOT EXISTS site_considerations TEXT NULL");
    $db->exec("ALTER TABLE layout_requests ADD COLUMN IF NOT EXISTS material_preferences VARCHAR(255) NULL");
    $db->exec("ALTER TABLE layout_requests ADD COLUMN IF NOT EXISTS budget_allocation TEXT NULL");
    $db->exec("ALTER TABLE layout_requests ADD COLUMN IF NOT EXISTS num_floors INT NULL");
    $db->exec("ALTER TABLE layout_requests ADD COLUMN IF NOT EXISTS preferred_style VARCHAR(100) NULL");
    $db->exec("ALTER TABLE layout_requests ADD COLUMN IF NOT EXISTS floor_rooms TEXT NULL");
    $db->exec("ALTER TABLE layout_requests ADD COLUMN IF NOT EXISTS site_images TEXT NULL");
    $db->exec("ALTER TABLE layout_requests ADD COLUMN IF NOT EXISTS reference_images TEXT NULL");
    $db->exec("ALTER TABLE layout_requests ADD COLUMN IF NOT EXISTS room_images TEXT NULL");
    
    // Pack structured fields into JSON for the existing 'requirements' column
    $requirements_payload = [
        'plot_shape' => $plot_shape,
        'topography' => $topography,
        'development_laws' => $development_laws,
        'family_needs' => $family_needs,
        'rooms' => $rooms,
        'aesthetic' => $aesthetic,
        'notes' => $requirements,
        'orientation' => $orientation,
        'site_considerations' => $site_considerations,
        'material_preferences' => $material_preferences,
        'budget_allocation' => $budget_allocation,
        'num_floors' => $num_floors,
        'preferred_style' => $preferred_style,
        'floor_rooms' => $floor_rooms,
        'site_images' => $site_images,
        'reference_images' => $reference_images,
        'room_images' => $room_images,
    ];
    $requirements_json = json_encode($requirements_payload);
    
    // Prevent duplicates: reuse existing library request for same layout if present
    $requestId = null;
    if ($layout_type === 'library' && $selected_layout_id) {
        $chk = $db->prepare("SELECT id FROM layout_requests WHERE homeowner_id = :hid AND selected_layout_id = :lid AND layout_type = 'library' AND (status IS NULL OR status <> 'deleted') ORDER BY id DESC LIMIT 1");
        $chk->execute([':hid' => $homeowner_id, ':lid' => $selected_layout_id]);
        $existing = $chk->fetch(PDO::FETCH_ASSOC);
        if ($existing && isset($existing['id'])) {
            $requestId = (int)$existing['id'];
        }
    }

    if (!$requestId) {
        // Insert layout request with all detailed fields
        $query = "INSERT INTO layout_requests (
                    user_id, homeowner_id, plot_size, building_size, budget_range, requirements, location, timeline, selected_layout_id, layout_type,
                    orientation, site_considerations, material_preferences, budget_allocation, num_floors, preferred_style, floor_rooms,
                    site_images, reference_images, room_images
                  ) VALUES (
                    :user_id, :homeowner_id, :plot_size, :building_size, :budget_range, :requirements, :location, :timeline, :selected_layout_id, :layout_type,
                    :orientation, :site_considerations, :material_preferences, :budget_allocation, :num_floors, :preferred_style, :floor_rooms,
                    :site_images, :reference_images, :room_images
                  )";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $homeowner_id, PDO::PARAM_INT);
        $stmt->bindParam(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
        $stmt->bindParam(':plot_size', $plot_size);
        $stmt->bindParam(':building_size', $building_size);
        $stmt->bindParam(':budget_range', $budget_range);
        $stmt->bindParam(':requirements', $requirements_json);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':timeline', $timeline);
        $stmt->bindParam(':selected_layout_id', $selected_layout_id);
        $stmt->bindParam(':layout_type', $layout_type);
        $stmt->bindParam(':orientation', $orientation);
        $stmt->bindParam(':site_considerations', $site_considerations);
        $stmt->bindParam(':material_preferences', $material_preferences);
        $stmt->bindParam(':budget_allocation', $budget_allocation);
        $stmt->bindParam(':num_floors', $num_floors);
        $stmt->bindParam(':preferred_style', $preferred_style);
        
        // Pre-encode arrays/objects to JSON to avoid passing expressions by reference
        $floor_rooms_json = $floor_rooms ? (is_string($floor_rooms) ? $floor_rooms : json_encode($floor_rooms)) : null;
        $site_images_json = json_encode($site_images);
        $reference_images_json = json_encode($reference_images);
        $room_images_json = json_encode($room_images);
        
        $stmt->bindParam(':floor_rooms', $floor_rooms_json);
        $stmt->bindParam(':site_images', $site_images_json);
        $stmt->bindParam(':reference_images', $reference_images_json);
        $stmt->bindParam(':room_images', $room_images_json);
        
        if ($stmt->execute()) {
            $requestId = (int)$db->lastInsertId();
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to submit layout request'
            ]);
            exit;
        }
    }
    
    if ($requestId) {
        // Optional fast path: directly send library layout to contractors
        $activate_for_contractors = isset($input['activate_for_contractors']) ? (bool)$input['activate_for_contractors'] : false;
        if ($activate_for_contractors && $layout_type === 'library' && $selected_layout_id) {
            try {
                // Pull design file URL from library and set request active + layout_file
                $ls = $db->prepare("SELECT design_file_url FROM layout_library WHERE id = :lid");
                $ls->execute([':lid' => $selected_layout_id]);
                $lib = $ls->fetch(PDO::FETCH_ASSOC);
                $fileUrl = $lib['design_file_url'] ?? null;

                // Ensure column exists in some DBs
                try { $db->exec("ALTER TABLE layout_requests ADD COLUMN IF NOT EXISTS layout_file VARCHAR(255) NULL"); } catch (Exception $__) {}
                try { $db->exec("ALTER TABLE layout_requests MODIFY COLUMN status ENUM('pending','approved','rejected','active') DEFAULT 'pending'"); } catch (Exception $__) {}

                $upd = $db->prepare("UPDATE layout_requests SET status = 'active', layout_file = :lf WHERE id = :id");
                $upd->execute([':lf' => $fileUrl, ':id' => $requestId]);

                // Skip architect auto-assign for this path
                echo json_encode([
                    'success' => true,
                    'message' => 'Layout request submitted and activated for contractors',
                    'request_id' => $requestId
                ]);
                exit;
            } catch (Exception $ie) {
                // fall through to normal flow on failure
            }
        }

        // If this is a library selection and the layout has an architect, auto-create assignment
        if ($layout_type === 'library' && $selected_layout_id) {
            try {
                // Find architect_id for selected layout
                $as = $db->prepare("SELECT architect_id FROM layout_library WHERE id = :lid");
                $as->execute([':lid' => $selected_layout_id]);
                $row = $as->fetch(PDO::FETCH_ASSOC);
                if ($row && (int)$row['architect_id'] > 0) {
                    // Ensure table exists
                    $db->exec("CREATE TABLE IF NOT EXISTS layout_request_assignments (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        layout_request_id INT NOT NULL,
                        homeowner_id INT NOT NULL,
                        architect_id INT NOT NULL,
                        message TEXT NULL,
                        status ENUM('sent','accepted','declined') DEFAULT 'sent',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY uniq_lr_arch (layout_request_id, architect_id)
                    )");
                    $ins = $db->prepare("INSERT INTO layout_request_assignments (layout_request_id, homeowner_id, architect_id, message)
                                          VALUES (:lrid, :hid, :aid, :msg)
                                          ON DUPLICATE KEY UPDATE status = 'sent', updated_at = CURRENT_TIMESTAMP");
                    $ins->execute([
                        ':lrid' => $requestId,
                        ':hid' => $homeowner_id,
                        ':aid' => (int)$row['architect_id'],
                        ':msg' => 'Library customization request'
                    ]);
                }
            } catch (Exception $ie) { /* ignore auto-assign errors */ }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Layout request submitted successfully',
            'request_id' => $requestId
        ]);
        exit;
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit layout request'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error submitting request: ' . $e->getMessage()
    ]);
}
?>