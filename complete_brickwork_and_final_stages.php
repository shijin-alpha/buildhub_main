<?php
/**
 * Complete Brickwork and Final Stages for Project 37
 * Adds missing construction stages to complete the full house construction
 */

$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║  COMPLETING BRICKWORK & FINAL STAGES - PROJECT 37            ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    $projectId = 37;
    
    // Verify project exists
    $stmt = $db->query("SELECT * FROM construction_projects WHERE id = $projectId");
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        die("❌ Project 37 not found!\n");
    }
    
    echo "📋 Project: {$project['project_name']}\n";
    echo "💰 Budget: ₹" . number_format($project['total_cost']) . "\n";
    echo "👷 Contractor ID: {$project['contractor_id']}\n";
    echo "🏠 Homeowner ID: {$project['homeowner_id']}\n\n";
    
    $contractorId = $project['contractor_id'];
    $homeownerId = $project['homeowner_id'];
    $inspectorId = 1001;
    
    // Define the missing stages - Brickwork and Final stages
    $newStages = [
        [
            'name' => 'Brickwork',
            'order' => 9,
            'days' => 35,
            'cost' => 180000,
            'description' => 'Complete brickwork including walls, partitions, and masonry work'
        ],
        [
            'name' => 'Final Touches',
            'order' => 10,
            'days' => 20,
            'cost' => 120000,
            'description' => 'Final finishing touches, cleanup, and handover preparation'
        ],
        [
            'name' => 'Handover',
            'order' => 11,
            'days' => 5,
            'cost' => 50000,
            'description' => 'Final handover, documentation, and warranty setup'
        ]
    ];
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "STEP 1: CREATING STAGE PROGRESS UPDATES\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $dayOffset = 200; // Start after existing stages
    foreach ($newStages as $stage) {
        $startD