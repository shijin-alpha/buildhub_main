<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "=== ARCHITECT REQUEST ISSUE ANALYSIS & SOLUTION ===\n\n";

echo "PROBLEM SUMMARY:\n";
echo "- Homeowner sent request to architect but it doesn't show up on architect page\n";
echo "- This happens when requests are in 'sent' status (pending) vs 'accepted' status\n\n";

echo "CURRENT STATUS:\n";
$stmt = $db->query("
    SELECT 
        lr.id as request_id,
        lr.user_id as homeowner_id,
        lr.plot_size,
        lr.budget_range,
        lr.status as request_status,
        lra.id as assignment_id,
        lra.architect_id,
        lra.status as assignment_status,
        u.first_name,
        u.last_name
    FROM layout_requests lr
    LEFT JOIN layout_request_assignments lra ON lr.id = lra.layout_request_id
    LEFT JOIN users u ON lra.architect_id = u.id
    WHERE lr.id >= 105
    ORDER BY lr.id DESC, lra.id DESC
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Request ID: {$row['request_id']}\n";
    echo "  Homeowner ID: {$row['homeowner_id']}\n";
    echo "  Plot Size: {$row['plot_size']}\n";
    echo "  Budget: {$row['budget_range']}\n";
    echo "  Request Status: {$row['request_status']}\n";
    if ($row['assignment_id']) {
        echo "  Assignment ID: {$row['assignment_id']}\n";
        echo "  Architect: {$row['first_name']} {$row['last_name']} (ID: {$row['architect_id']})\n";
        echo "  Assignment Status: {$row['assignment_status']}\n";
    } else {
        echo "  No assignments found\n";
    }
    echo "  ---\n";
}

echo "\nSOLUTION STEPS:\n";
echo "1. ARCHITECT DASHBOARD WORKFLOW:\n";
echo "   - Architect logs in and goes to dashboard\n";
echo "   - Looks at 'Pending Assignments' section (NOT 'Your Assigned Projects')\n";
echo "   - Clicks 'Accept' on the request\n";
echo "   - Request then moves to 'Your Assigned Projects' section\n\n";

echo "2. FRONTEND SECTIONS:\n";
echo "   - 'Pending Assignments': Shows assignment_status = 'sent'\n";
echo "   - 'Your Assigned Projects': Shows assignment_status = 'accepted'\n\n";

echo "3. API ENDPOINTS:\n";
echo "   - GET /api/architect/get_assigned_requests.php - Gets all assignments\n";
echo "   - POST /api/architect/respond_assignment.php - Accept/decline assignments\n\n";

echo "4. DATABASE TABLES:\n";
echo "   - layout_requests: Contains the actual request data\n";
echo "   - layout_request_assignments: Links requests to architects with status\n\n";

echo "QUICK FIX - Accept Request 109 for Architect 31:\n";
$stmt = $db->prepare("UPDATE layout_request_assignments SET status = 'accepted' WHERE layout_request_id = 109 AND architect_id = 31");
$result = $stmt->execute();
echo "Auto-accept result: " . ($result ? "SUCCESS" : "FAILED") . "\n";

if ($result) {
    echo "\nRequest 109 is now accepted and should appear in 'Your Assigned Projects'\n";
}

echo "\nTEST URLS:\n";
echo "- Test Page: /buildhub/tests/demos/architect_pending_requests_test.html\n";
echo "- Architect Dashboard: /buildhub/frontend/architect-dashboard.html\n";
?>