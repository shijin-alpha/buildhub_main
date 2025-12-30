<?php
// This script adds sample materials to the database - run once to populate with initial data

try {
    require_once __DIR__ . '/../../config/db.php';
    
    // Sample materials data
    $sampleMaterials = [
        ['name' => 'Portland Cement', 'category' => 'cement', 'unit' => 'bag (50kg)', 'price' => 350.00, 'description' => 'High quality Portland cement for construction'],
        ['name' => 'TMT Steel Bars', 'category' => 'steel', 'unit' => 'kg', 'price' => 65.00, 'description' => 'Fe500 grade TMT steel bars'],
        ['name' => 'Red Clay Bricks', 'category' => 'bricks', 'unit' => 'piece', 'price' => 8.50, 'description' => 'Standard size red clay bricks'],
        ['name' => 'River Sand', 'category' => 'sand', 'unit' => 'cubic meter', 'price' => 1200.00, 'description' => 'Fine river sand for construction'],
        ['name' => 'Crushed Stone', 'category' => 'gravel', 'unit' => 'cubic meter', 'price' => 1500.00, 'description' => '20mm crushed stone aggregate'],
        ['name' => 'Teak Wood', 'category' => 'wood', 'unit' => 'cubic feet', 'price' => 2500.00, 'description' => 'Premium teak wood for furniture'],
        ['name' => 'Ceramic Floor Tiles', 'category' => 'tiles', 'unit' => 'sq ft', 'price' => 45.00, 'description' => '2x2 feet ceramic floor tiles'],
        ['name' => 'Exterior Paint', 'category' => 'paint', 'unit' => 'liter', 'price' => 180.00, 'description' => 'Weather resistant exterior paint'],
        ['name' => 'Copper Wire', 'category' => 'electrical', 'unit' => 'meter', 'price' => 12.00, 'description' => '2.5mm copper electrical wire'],
        ['name' => 'PVC Pipes', 'category' => 'plumbing', 'unit' => 'meter', 'price' => 85.00, 'description' => '4 inch PVC pipes for plumbing']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO materials (name, category, unit, price, description) VALUES (?, ?, ?, ?, ?)");
    
    $added = 0;
    foreach ($sampleMaterials as $material) {
        // Check if material already exists
        $checkStmt = $pdo->prepare("SELECT id FROM materials WHERE name = ? AND category = ?");
        $checkStmt->execute([$material['name'], $material['category']]);
        
        if (!$checkStmt->fetch()) {
            $result = $stmt->execute([
                $material['name'],
                $material['category'],
                $material['unit'],
                $material['price'],
                $material['description']
            ]);
            
            if ($result) {
                $added++;
                echo "Added: {$material['name']}\n";
            }
        } else {
            echo "Skipped (already exists): {$material['name']}\n";
        }
    }
    
    echo "\nSample materials setup complete! Added $added new materials.\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>