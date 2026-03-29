<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Creating sample workers for testing...\n";
    
    // Get contractor IDs (assuming role = 'contractor')
    $stmt = $db->query("SELECT id FROM users WHERE role = 'contractor' LIMIT 3");
    $contractors = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($contractors)) {
        echo "No contractors found. Please create contractor users first.\n";
        exit;
    }
    
    // Get worker type IDs
    $worker_types = $db->query("SELECT id, type_name FROM worker_types")->fetchAll(PDO::FETCH_ASSOC);
    $worker_type_map = [];
    foreach ($worker_types as $type) {
        $worker_type_map[$type['type_name']] = $type['id'];
    }
    
    // Sample workers for each contractor
    $sample_workers = [
        // Skilled workers
        ['Rajesh Kumar', 'Mason', 5, 'senior', 850.00, true, '9876543210'],
        ['Suresh Patel', 'Carpenter', 8, 'master', 900.00, true, '9876543211'],
        ['Amit Singh', 'Electrician', 6, 'senior', 950.00, true, '9876543212'],
        ['Ravi Sharma', 'Plumber', 4, 'junior', 800.00, true, '9876543213'],
        ['Deepak Yadav', 'Steel Fixer', 7, 'senior', 820.00, true, '9876543214'],
        
        // Semi-skilled workers
        ['Mohan Das', 'Assistant Mason', 3, 'junior', 520.00, false, '9876543215'],
        ['Prakash Jha', 'Assistant Carpenter', 2, 'apprentice', 480.00, false, '9876543216'],
        ['Vikram Gupta', 'Assistant Electrician', 4, 'junior', 580.00, false, '9876543217'],
        ['Santosh Kumar', 'Machine Operator', 5, 'senior', 650.00, false, '9876543218'],
        
        // Unskilled workers
        ['Ramesh Pal', 'Helper', 2, 'apprentice', 380.00, false, '9876543219'],
        ['Dinesh Roy', 'Laborer', 3, 'junior', 320.00, false, '9876543220'],
        ['Mukesh Sah', 'Helper', 1, 'apprentice', 360.00, false, '9876543221'],
        ['Ganesh Lal', 'Laborer', 4, 'junior', 340.00, false, '9876543222'],
        ['Mahesh Bind', 'Material Handler', 3, 'junior', 370.00, false, '9876543223'],
        ['Naresh Tiwari', 'Watchman', 6, 'senior', 320.00, false, '9876543224']
    ];
    
    $stmt = $db->prepare("
        INSERT INTO contractor_workers 
        (contractor_id, worker_name, worker_type_id, experience_years, skill_level, daily_wage, is_main_worker, phone_number) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $workers_created = 0;
    
    foreach ($contractors as $contractor_id) {
        echo "Adding workers for contractor ID: $contractor_id\n";
        
        foreach ($sample_workers as $worker) {
            $worker_type_id = $worker_type_map[$worker[1]] ?? null;
            if ($worker_type_id) {
                $stmt->execute([
                    $contractor_id,
                    $worker[0], // worker_name
                    $worker_type_id, // worker_type_id
                    $worker[2], // experience_years
                    $worker[3], // skill_level
                    $worker[4], // daily_wage
                    $worker[5], // is_main_worker
                    $worker[6]  // phone_number
                ]);
                $workers_created++;
            }
        }
    }
    
    echo "✓ Created $workers_created sample workers\n";
    
    // Show summary
    $stmt = $db->query("
        SELECT 
            c.contractor_id,
            COUNT(*) as total_workers,
            COUNT(CASE WHEN c.is_main_worker = 1 THEN 1 END) as main_workers,
            AVG(c.daily_wage) as avg_wage
        FROM contractor_workers c
        GROUP BY c.contractor_id
    ");
    
    echo "\nWorker Summary by Contractor:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Contractor {$row['contractor_id']}: {$row['total_workers']} workers, {$row['main_workers']} main workers, Avg wage: ₹{$row['avg_wage']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>