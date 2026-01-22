<?php
session_start();

// Set up contractor session
$_SESSION['user_id'] = 29; // Contractor ID from the database
$_SESSION['user_type'] = 'contractor';

echo "Session established for contractor ID: " . $_SESSION['user_id'] . "\n";

// Test data for payment request
$testData = [
    'project_id' => 1, // Using construction project ID 1
    'homeowner_id' => 28, // Homeowner ID from the database
    'stage_name' => 'Structure', // Different stage from the paid Foundation
    'requested_amount' => 75000,
    'work_description' => 'Structure work completed including column construction, beam work, and slab casting. All structural framework is in place according to approved plans and engineering specifications.',
    'completion_percentage' => 25,
    'labor_count' => 8,
    'total_project_cost' => 500000,
    'quality_check' => true,
    'safety_compliance' => true,
    'materials_used' => 'Steel rebar, concrete, cement, formwork materials',
    'contractor_notes' => 'Structure stage completed as per schedule with quality checks verified'
];

echo "Test data prepared:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Make the API call
$url = 'http://localhost/buildhub/backend/api/contractor/submit_payment_request.php';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, '');
curl_setopt($ch, CURLOPT_COOKIEFILE, '');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Response Code: $httpCode\n";
echo "Response:\n";
echo $response . "\n";

// Parse and display the response
$responseData = json_decode($response, true);
if ($responseData) {
    echo "\nParsed Response:\n";
    echo json_encode($responseData, JSON_PRETTY_PRINT);
} else {
    echo "\nFailed to parse JSON response\n";
}
?>