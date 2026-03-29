<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== PROJECT ID 37 DATA CHECK ===\n\n";
    
    // Check contractor_send_estimates
    echo "1. CONTRACTOR_SEND_ESTIMATES:\n";
    $stmt = $db->prepare("SELECT * FROM contractor_send_estimates WHERE id = 37");
    $stmt->execute();
    $estimate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($estimate) {
        echo "Found estimate!\n";
        echo "- total_cost: " . ($estimate['total_cost'] ?? 'NULL') . "\n";
        echo "- timeline: " . ($estimate['timeline'] ?? 'NULL') . "\n";
        echo "- status: " . ($estimate['status'] ?? 'NULL') . "\n";
        echo "- notes: " . (strlen($estimate['notes'] ?? '') > 0 ? 'YES' : 'NO') . "\n";
        
        if (!empty($estimate['structured'])) {
            echo "- structured JSON: YES\n";
            $structured = json_decode($estimate['structured'], true);
            if ($structured) {
                echo "  - Keys: " . implode(', ', array_keys($structured)) . "\n";
            }
        } else {
            echo "- structured JSON: NO\n";
        }
    } else {
        echo "Not found\n";
    }
    
    // Check contractor_layout_sends
    echo "\n2. CONTRACTOR_LAYOUT_SENDS:\n";
    if ($estimate && $estimate['send_id']) {
        $stmt = $db->prepare("SELECT * FROM contractor_layout_sends WHERE id = ?");
        $stmt->execute([$estimate['send_id']]);
        $send = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($send) {
            echo "Found send record!\n";
            echo "- homeowner_id: " . ($send['homeowner_id'] ?? 'NULL') . "\n";
            echo "- layout_id: " . ($send['layout_id'] ?? 'NULL') . "\n";
            echo "- design_id: " . ($send['design_id'] ?? 'NULL') . "\n";
            echo "- message: " . (strlen($send['message'] ?? '') > 0 ? 'YES' : 'NO') . "\n";
        } else {
            echo "Not found\n";
        }
    }
    
    // Check layout_requests
    echo "\n3. LAYOUT_REQUESTS:\n";
    if (isset($send) && $send && $send['layout_id']) {
        $stmt = $db->prepare("SELECT * FROM layout_requests WHERE id = ?");
        $stmt->execute([$send['layout_id']]);
        $layout = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($layout) {
            echo "Found layout request!\n";
            echo "- plot_size: " . ($layout['plot_size'] ?? 'NULL') . "\n";
            echo "- budget_range: " . ($layout['budget_range'] ?? 'NULL') . "\n";
            echo "- location: " . ($layout['location'] ?? 'NULL') . "\n";
            echo "- preferred_style: " . ($layout['preferred_style'] ?? 'NULL') . "\n";
            echo "- requirements: " . (strlen($layout['requirements'] ?? '') > 0 ? 'YES' : 'NO') . "\n";
            
            if (!empty($layout['requirements'])) {
                $req = json_decode($layout['requirements'], true);
                if ($req) {
                    echo "  - Requirements is JSON with keys: " . implode(', ', array_keys($req)) . "\n";
                }
            }
        } else {
            echo "Not found\n";
        }
    }
    
    // Check architect_layouts
    echo "\n4. ARCHITECT_LAYOUTS:\n";
    if (isset($send) && $send && $send['layout_id']) {
        $stmt = $db->prepare("SELECT * FROM architect_layouts WHERE layout_request_id = ? LIMIT 1");
        $stmt->execute([$send['layout_id']]);
        $arch_layout = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($arch_layout) {
            echo "Found architect layout!\n";
            echo "- layout_file: " . ($arch_layout['layout_file'] ?? 'NULL') . "\n";
            echo "- technical_details: " . (strlen($arch_layout['technical_details'] ?? '') > 0 ? 'YES' : 'NO') . "\n";
            
            if (!empty($arch_layout['technical_details'])) {
                $tech = json_decode($arch_layout['technical_details'], true);
                if ($tech) {
                    echo "  - Technical details is JSON with keys: " . implode(', ', array_keys($tech)) . "\n";
                }
            }
        } else {
            echo "Not found\n";
        }
    }
    
    // Check users table for homeowner
    echo "\n5. HOMEOWNER INFO:\n";
    if (isset($send) && $send && $send['homeowner_id']) {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$send['homeowner_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "Found homeowner!\n";
            echo "- Name: " . ($user['first_name'] ?? '') . " " . ($user['last_name'] ?? '') . "\n";
            echo "- Email: " . ($user['email'] ?? 'NULL') . "\n";
            echo "- Phone: " . ($user['phone'] ?? 'NULL') . "\n";
        } else {
            echo "Not found\n";
        }
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "All data sources checked. Review above for available data.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
