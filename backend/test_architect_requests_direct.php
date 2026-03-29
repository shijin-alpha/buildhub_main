<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

$architect_id = 31; // Test with architect 31

echo "=== TESTING ARCHITECT $architect_id REQUESTS ===\n";

// This is the same query from get_assigned_requests.php
$query = "SELECT 
            a.id as assignment_id,
            a.status as assignment_status,
            a.created_at as assigned_at,
            a.message,
            lr.id as layout_request_id,
            lr.user_id as homeowner_id,
            lr.plot_size, lr.budget_range, lr.requirements, lr.location, lr.timeline,
            lr.preferred_style, lr.layout_type, lr.selected_layout_id, lr.layout_file,
            lr.site_images, lr.reference_images, lr.room_images,
            lr.orientation, lr.site_considerations, lr.material_preferences,
            lr.budget_allocation, lr.floor_rooms, lr.num_floors,
            lr.status as request_status, lr.created_at as request_created_at, lr.updated_at as request_updated_at,
            u.id as user_id, CONCAT(u.first_name, ' ', u.last_name) as homeowner_name, u.email as homeowner_email,
            ll.title as library_title, ll.image_url as library_image, ll.layout_type as library_layout_type, ll.design_file_url as library_file
          FROM layout_request_assignments a
          JOIN layout_requests lr ON lr.id = a.layout_request_id
          JOIN users u ON u.id = a.homeowner_id
          LEFT JOIN layout_library ll ON lr.selected_layout_id = ll.id
          WHERE a.architect_id = :aid
          ORDER BY a.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute([':aid' => $architect_id]);

$assignments = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $assignments[] = $row;
}

echo "Found " . count($assignments) . " assignments for architect $architect_id:\n\n";

foreach ($assignments as $assignment) {
    echo "Assignment ID: {$assignment['assignment_id']}\n";
    echo "Request ID: {$assignment['layout_request_id']}\n";
    echo "Assignment Status: {$assignment['assignment_status']}\n";
    echo "Request Status: {$assignment['request_status']}\n";
    echo "Homeowner: {$assignment['homeowner_name']} ({$assignment['homeowner_email']})\n";
    echo "Plot Size: {$assignment['plot_size']}\n";
    echo "Budget: {$assignment['budget_range']}\n";
    echo "Requirements: {$assignment['requirements']}\n";
    echo "Created: {$assignment['assigned_at']}\n";
    echo "---\n";
}

// Test what the frontend would see (only accepted assignments)
$acceptedAssignments = array_filter($assignments, function($assignment) {
    return $assignment['assignment_status'] === 'accepted';
});

echo "\nAccepted assignments (what shows in dashboard): " . count($acceptedAssignments) . "\n";
foreach ($acceptedAssignments as $assignment) {
    echo "- Request ID: {$assignment['layout_request_id']}, Status: {$assignment['assignment_status']}\n";
}

// Test what shows as pending (sent status)
$pendingAssignments = array_filter($assignments, function($assignment) {
    return $assignment['assignment_status'] === 'sent';
});

echo "\nPending assignments (need to accept/decline): " . count($pendingAssignments) . "\n";
foreach ($pendingAssignments as $assignment) {
    echo "- Request ID: {$assignment['layout_request_id']}, Status: {$assignment['assignment_status']}\n";
}
?>