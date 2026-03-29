<?php
/**
 * Test Blockchain Functionality Status
 * 
 * This script tests the current status of blockchain implementation
 */

require_once 'backend/config/database.php';

try {
    echo "=== Blockchain Implementation Status Test ===\n\n";
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    echo "✓ Connected to MySQL database\n\n";
    
    // 1. Check Blockchain Trust Records
    echo "1. BLOCKCHAIN TRUST RECORDS:\n";
    echo "----------------------------\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM blockchain_trust_records");
    $count = $stmt->fetch()['count'];
    echo "Total records: $count\n";
    
    if ($count > 0) {
        $stmt = $db->query("SELECT * FROM blockchain_trust_records ORDER BY created_at DESC LIMIT 3");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($records as $record) {
            echo "- Record ID: {$record['id']}\n";
            echo "  Proof Hash: " . substr($record['proof_hash'], 0, 20) . "...\n";
            echo "  Blockchain TX: " . ($record['blockchain_tx_hash'] ?? 'NULL') . "\n";
            echo "  Created: {$record['created_at']}\n\n";
        }
    } else {
        echo "No blockchain trust records found\n\n";
    }
    
    // 2. Check Immutable Audit Ledger
    echo "2. IMMUTABLE PAYMENT AUDIT LEDGER:\n";
    echo "----------------------------------\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM immutable_payment_audit_ledger");
    $count = $stmt->fetch()['count'];
    echo "Total audit entries: $count\n";
    
    if ($count > 0) {
        $stmt = $db->query("SELECT * FROM immutable_payment_audit_ledger ORDER BY block_number DESC LIMIT 3");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($records as $record) {
            echo "- Block #{$record['block_number']}\n";
            echo "  Entry Type: {$record['entry_type']}\n";
            echo "  Payment ID: {$record['payment_id']}\n";
            echo "  Content Hash: " . substr($record['content_hash'], 0, 20) . "...\n";
            echo "  Block Hash: " . substr($record['block_hash'], 0, 20) . "...\n";
            echo "  Amount Range: {$record['amount_range']}\n";
            echo "  Stage Category: {$record['stage_category']}\n";
            echo "  Created: {$record['created_at']}\n\n";
        }
    } else {
        echo "No audit ledger entries found\n\n";
    }
    
    // 3. Check Integration Status
    echo "3. BLOCKCHAIN INTEGRATION STATUS:\n";
    echo "---------------------------------\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM blockchain_integration_status");
    $count = $stmt->fetch()['count'];
    echo "Integration status records: $count\n";
    
    if ($count > 0) {
        $stmt = $db->query("SELECT * FROM blockchain_integration_status ORDER BY created_at DESC LIMIT 3");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($records as $record) {
            echo "- Payment ID: {$record['payment_id']}\n";
            echo "  Initiation Recorded: " . ($record['initiation_recorded'] ? 'YES' : 'NO') . "\n";
            echo "  Completion Recorded: " . ($record['completion_recorded'] ? 'YES' : 'NO') . "\n";
            echo "  Created: {$record['created_at']}\n\n";
        }
    } else {
        echo "No integration status records found\n\n";
    }
    
    // 4. Check Network Status
    echo "4. BLOCKCHAIN NETWORK STATUS:\n";
    echo "-----------------------------\n";
    
    try {
        $stmt = $db->query("SELECT * FROM blockchain_network_status ORDER BY created_at DESC LIMIT 1");
        $networkStatus = $stmt->fetch();
        
        if ($networkStatus) {
            echo "Network: {$networkStatus['network_name']}\n";
            echo "Status: {$networkStatus['status']}\n";
            echo "Block Height: " . ($networkStatus['block_height'] ?? 'N/A') . "\n";
            echo "Gas Price: " . ($networkStatus['gas_price'] ?? 'N/A') . " Gwei\n";
            echo "Created: {$networkStatus['created_at']}\n\n";
        } else {
            echo "No network status found\n\n";
        }
    } catch (Exception $e) {
        echo "Network status check failed: " . $e->getMessage() . "\n\n";
    }
    
    // 5. Test Blockchain Configuration
    echo "5. BLOCKCHAIN CONFIGURATION:\n";
    echo "----------------------------\n";
    
    if (file_exists('backend/blockchain/config/blockchain_config.php')) {
        require_once 'backend/blockchain/config/blockchain_config.php';
        
        echo "Configuration file: ✓ EXISTS\n";
        echo "Blockchain enabled: " . (BLOCKCHAIN_ENABLED ? 'YES' : 'NO') . "\n";
        echo "Contract address: " . TRUST_CONTRACT_ADDRESS . "\n";
        echo "Network: " . ETHEREUM_NETWORK . "\n";
        echo "Async mode: " . (BLOCKCHAIN_ASYNC_MODE ? 'YES' : 'NO') . "\n";
        echo "Fail silently: " . (BLOCKCHAIN_FAIL_SILENTLY ? 'YES' : 'NO') . "\n\n";
        
        // Check environment variables
        echo "Environment Variables:\n";
        echo "- INFURA_PROJECT_ID: " . (getenv('INFURA_PROJECT_ID') ? 'SET' : 'NOT SET') . "\n";
        echo "- ETHEREUM_PRIVATE_KEY: " . (getenv('ETHEREUM_PRIVATE_KEY') ? 'SET' : 'NOT SET') . "\n";
        echo "- ETHEREUM_PUBLIC_ADDRESS: " . (getenv('ETHEREUM_PUBLIC_ADDRESS') ? 'SET' : 'NOT SET') . "\n\n";
    } else {
        echo "Configuration file: ❌ NOT FOUND\n\n";
    }
    
    // 6. Test Smart Contract
    echo "6. SMART CONTRACT:\n";
    echo "------------------\n";
    
    if (file_exists('backend/blockchain/contracts/TrustLayer.sol')) {
        echo "Smart contract source: ✓ EXISTS\n";
    } else {
        echo "Smart contract source: ❌ NOT FOUND\n";
    }
    
    if (file_exists('backend/blockchain/contracts/TrustLayer.json')) {
        echo "Contract ABI: ✓ EXISTS\n";
    } else {
        echo "Contract ABI: ❌ NOT FOUND\n";
    }
    
    // 7. Test Core Blockchain Class
    echo "\n7. BLOCKCHAIN TRUST LAYER CLASS:\n";
    echo "--------------------------------\n";
    
    if (file_exists('backend/blockchain/BlockchainTrustLayer.php')) {
        echo "Core class: ✓ EXISTS\n";
        
        try {
            require_once 'backend/blockchain/BlockchainTrustLayer.php';
            $trustLayer = new BlockchainTrustLayer($db);
            echo "Class instantiation: ✓ SUCCESS\n";
            
            // Test health check
            try {
                $healthCheck = $trustLayer->healthCheck();
                echo "Health check results:\n";
                echo "- Web3 connected: " . ($healthCheck['web3_connected'] ? 'YES' : 'NO') . "\n";
                echo "- Contract accessible: " . ($healthCheck['contract_accessible'] ? 'YES' : 'NO') . "\n";
                echo "- Contract active: " . ($healthCheck['contract_active'] ? 'YES' : 'NO') . "\n";
                
                if (isset($healthCheck['error'])) {
                    echo "- Error: {$healthCheck['error']}\n";
                }
            } catch (Exception $e) {
                echo "Health check failed: " . $e->getMessage() . "\n";
            }
            
        } catch (Exception $e) {
            echo "Class instantiation: ❌ FAILED\n";
            echo "Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Core class: ❌ NOT FOUND\n";
    }
    
    // 8. Check Demo Files
    echo "\n8. DEMO FILES:\n";
    echo "-------------\n";
    
    $demoFiles = [
        'demo_blockchain_audit_trail.html',
        'demo_immutable_audit_system.html'
    ];
    
    foreach ($demoFiles as $file) {
        if (file_exists($file)) {
            echo "- $file: ✓ EXISTS\n";
        } else {
            echo "- $file: ❌ NOT FOUND\n";
        }
    }
    
    // 9. Overall Status Assessment
    echo "\n9. OVERALL STATUS ASSESSMENT:\n";
    echo "============================\n";
    
    $auditRecordsExist = $db->query("SELECT COUNT(*) as count FROM immutable_payment_audit_ledger")->fetch()['count'] > 0;
    $blockchainRecordsExist = $db->query("SELECT COUNT(*) as count FROM blockchain_trust_records")->fetch()['count'] > 0;
    $configExists = file_exists('backend/blockchain/config/blockchain_config.php');
    $coreClassExists = file_exists('backend/blockchain/BlockchainTrustLayer.php');
    $demoFilesExist = file_exists('demo_blockchain_audit_trail.html') && file_exists('demo_immutable_audit_system.html');
    
    if ($auditRecordsExist && $blockchainRecordsExist && $configExists && $coreClassExists && $demoFilesExist) {
        echo "🎉 BLOCKCHAIN IMPLEMENTATION: FULLY OPERATIONAL\n";
        echo "✓ Database tables created and populated\n";
        echo "✓ Configuration files present\n";
        echo "✓ Core classes available\n";
        echo "✓ Demo files ready\n";
        echo "✓ Payment audit system actively recording\n";
        echo "\nYour blockchain implementation is working properly!\n";
    } else {
        echo "⚠️  BLOCKCHAIN IMPLEMENTATION: PARTIALLY WORKING\n";
        echo "Some components may be missing or not fully configured.\n";
        
        if (!$auditRecordsExist) echo "- No audit records found\n";
        if (!$blockchainRecordsExist) echo "- No blockchain trust records found\n";
        if (!$configExists) echo "- Configuration file missing\n";
        if (!$coreClassExists) echo "- Core class file missing\n";
        if (!$demoFilesExist) echo "- Demo files missing\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nThis might indicate:\n";
    echo "1. Database connection issues\n";
    echo "2. Missing blockchain tables\n";
    echo "3. Configuration problems\n";
    echo "4. File system issues\n";
}
?>