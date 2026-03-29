<?php
try {
    $pdo = new PDO('sqlite:buildhub.db');
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    
    echo "Tables in database:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>