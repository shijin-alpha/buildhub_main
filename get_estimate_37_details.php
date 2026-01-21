<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== ESTIMATE ID 37 DETAILS ===\n\n";
    
    // Get estimate 37 details
    $stmt = $pdo->prepare("SELECT * FROM contractor_send_estimates WHERE id = 37");
    $stmt->execute();
    $estimate37 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($estimate37) {
        echo "CONTRACTOR SEND ESTIMATES (ID 37):\n";
        foreach($estimate37 as $key => $value) {
            echo "  $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
        }
        
        // Parse structured data
        if ($estimate37['structured']) {
            echo "\nPARSED STRUCTURED DATA:\n";
            $structured = json_decode($estimate37['structured'], true);
            if ($structured) {
                foreach($structured as $key => $value) {
                    if (is_array($value)) {
                        if ($key === 'totals') {
                            echo "  $key:\n";
                            foreach($value as $subkey => $subvalue) {
                                echo "    $subkey: $subvalue\n";
                            }
                        } else {
                            echo "  $key: [complex data]\n";
                        }
                    } else {
                        echo "  $key: " . (empty($value) ? 'EMPTY' : $value) . "\n";
                    }
                }
            }
        }
    }
    
    // Check if there's a corresponding contractor_estimates record
    echo "\n=== RELATED CONTRACTOR ESTIMATES ===\n";
    $stmt = $pdo->prepare("SELECT * FROM contractor_estimates WHERE send_id = ?");
    $stmt->execute([$estimate37['send_id']]);
    $contractorEst = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contractorEst) {
        echo "CONTRACTOR ESTIMATES (send_id = {$estimate37['send_id']}):\n";
        foreach($contractorEst as $key => $value) {
            if ($key === 'structured_data' && $value) {
                echo "  $key: [JSON data - " . strlen($value) . " chars]\n";
            } else {
                echo "  $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
            }
        }
    } else {
        echo "No contractor_estimates record found for send_id = {$estimate37['send_id']}\n";
    }
    
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>