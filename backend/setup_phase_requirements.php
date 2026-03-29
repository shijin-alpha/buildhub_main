<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Setting up phase worker requirements...\n";
    
    // Get phase and worker type IDs
    $phases = $db->query("SELECT id, phase_name FROM construction_phases")->fetchAll(PDO::FETCH_ASSOC);
    $worker_types = $db->query("SELECT id, type_name FROM worker_types")->fetchAll(PDO::FETCH_ASSOC);
    
    $phase_map = [];
    foreach ($phases as $phase) {
        $phase_map[$phase['phase_name']] = $phase['id'];
    }
    
    $worker_type_map = [];
    foreach ($worker_types as $type) {
        $worker_type_map[$type['type_name']] = $type['id'];
    }
    
    // Define requirements for each phase
    $requirements = [
        'Site Preparation' => [
            ['Machine Operator', true, 1, 2, 'essential'],
            ['Laborer', true, 4, 8, 'essential'],
            ['Helper', true, 2, 4, 'important'],
            ['Watchman', false, 1, 1, 'optional']
        ],
        'Foundation' => [
            ['Mason', true, 2, 4, 'essential'],
            ['Assistant Mason', true, 2, 4, 'important'],
            ['Steel Fixer', true, 1, 3, 'essential'],
            ['Laborer', true, 4, 8, 'important'],
            ['Helper', true, 2, 4, 'important'],
            ['Machine Operator', false, 1, 2, 'optional']
        ],
        'Structure' => [
            ['Mason', true, 3, 6, 'essential'],
            ['Assistant Mason', true, 3, 6, 'essential'],
            ['Steel Fixer', true, 2, 4, 'essential'],
            ['Carpenter', true, 2, 4, 'essential'],
            ['Assistant Carpenter', true, 2, 4, 'essential'],
            ['Welder', true, 1, 2, 'essential'],
            ['Laborer', true, 6, 10, 'essential'],
            ['Helper', true, 4, 6, 'essential']
        ],
        'Brickwork' => [
            ['Mason', true, 4, 8, 'essential'],
            ['Assistant Mason', true, 4, 8, 'essential'],
            ['Laborer', true, 4, 6, 'important'],
            ['Helper', true, 2, 4, 'important']
        ],
        'Roofing' => [
            ['Carpenter', true, 2, 4, 'essential'],
            ['Assistant Carpenter', true, 2, 4, 'important'],
            ['Steel Fixer', true, 1, 2, 'important'],
            ['Helper', true, 2, 4, 'important'],
            ['Laborer', true, 2, 4, 'important']
        ],
        'Electrical' => [
            ['Electrician', true, 2, 4, 'essential'],
            ['Assistant Electrician', true, 2, 4, 'important'],
            ['Helper', true, 1, 2, 'important']
        ],
        'Plumbing' => [
            ['Plumber', true, 2, 4, 'essential'],
            ['Assistant Plumber', true, 2, 4, 'important'],
            ['Helper', true, 1, 2, 'important']
        ],
        'Finishing' => [
            ['Mason', true, 2, 4, 'essential'],
            ['Painter', true, 2, 4, 'essential'],
            ['Tiler', true, 1, 3, 'important'],
            ['Carpenter', true, 1, 3, 'important'],
            ['Helper', true, 2, 4, 'important']
        ],
        'Flooring' => [
            ['Tiler', true, 2, 4, 'essential'],
            ['Helper', true, 2, 3, 'important'],
            ['Laborer', true, 1, 2, 'optional']
        ],
        'Final Inspection' => [
            ['Cleaner', true, 2, 4, 'essential'],
            ['Helper', true, 1, 2, 'important']
        ]
    ];
    
    $stmt = $db->prepare("
        INSERT IGNORE INTO phase_worker_requirements 
        (phase_id, worker_type_id, is_required, min_workers, max_workers, priority_level) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $requirements_added = 0;
    
    foreach ($requirements as $phase_name => $phase_requirements) {
        $phase_id = $phase_map[$phase_name] ?? null;
        if (!$phase_id) continue;
        
        echo "Adding requirements for $phase_name...\n";
        
        foreach ($phase_requirements as $req) {
            $worker_type_id = $worker_type_map[$req[0]] ?? null;
            if ($worker_type_id) {
                $stmt->execute([
                    $phase_id,
                    $worker_type_id,
                    $req[1], // is_required
                    $req[2], // min_workers
                    $req[3], // max_workers
                    $req[4]  // priority_level
                ]);
                $requirements_added++;
            }
        }
    }
    
    echo "✓ Added $requirements_added phase worker requirements\n";
    
    // Show summary
    $stmt = $db->query("
        SELECT 
            cp.phase_name,
            COUNT(*) as total_requirements,
            COUNT(CASE WHEN pwr.priority_level = 'essential' THEN 1 END) as essential,
            COUNT(CASE WHEN pwr.priority_level = 'important' THEN 1 END) as important,
            COUNT(CASE WHEN pwr.priority_level = 'optional' THEN 1 END) as optional
        FROM construction_phases cp
        LEFT JOIN phase_worker_requirements pwr ON cp.id = pwr.phase_id
        GROUP BY cp.id, cp.phase_name
        ORDER BY cp.phase_order
    ");
    
    echo "\nPhase Requirements Summary:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['phase_name']}: {$row['total_requirements']} total ({$row['essential']} essential, {$row['important']} important, {$row['optional']} optional)\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>