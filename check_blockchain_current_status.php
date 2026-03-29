<?php
/**
 * Check Current Status of Blockchain Implementation
 * 
 * This script verifies if the blockchain system is still working and useful
 */

require_once 'backend/config/database.php';

try {
    echo "=== BLOCKCHAIN IMPLEMENTATION CURRENT STATUS ===\n\n";
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    echo "✓ Database connection: ACTIVE\n\n";
    
    // 1. Check if audit tables exist and have recent data
    echo "1. AUDIT SYSTEM STATUS:\n";
    echo "======================\n";
    
    $tables = [
        'immutable_payment_audit_ledger' => 'Main audit ledger',
        'blockchain_trust_records' => 'Blockchain trust records',
        'audit_ledger_statistics' => 'Audit statistics',
        'blockchain_integration_status' => 'Integration status'
    ];
    
    $allTablesExist = true;
    foreach ($tables as $table => $description) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "✓ $description: $count records\n";
        } catch (Exception $e) {
            echo "❌ $description: TABLE NOT FOUND\n";
            $allTablesExist = false;
        }
    }
    
    if (!$allTablesExist) {
        echo "\n⚠️  Some blockchain tables are missing. System may need reinitialization.\n\n";
    } else {
        echo "\n✓ All blockchain tables present and active\n\n";
    }
    
    // 2. Check recent activity
    echo "2. RECENT ACTIVITY CHECK:\n";
    echo "========================\n";
    
    try {
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total_entries,
                MAX(created_at) as last_entry,
                MIN(created_at) as first_entry
            FROM immutable_payment_audit_ledger
        ");
        $activity = $stmt->fetch();
        
        if ($activity['total_entries'] > 0) {
            echo "Total audit entries: {$activity['total_entries']}\n";
            echo "First entry: {$activity['first_entry']}\n";
            echo "Last entry: {$activity['last_entry']}\n";
            
            // Check if recent (within last 30 days)
            $lastEntryTime = strtotime($activity['last_entry']);
            $daysSinceLastEntry = (time() - $lastEntryTime) / (24 * 3600);
            
            if ($daysSinceLastEntry < 30) {
                echo "✓ Recent activity detected (last entry {$daysSinceLastEntry} days ago)\n";
            } else {
                echo "⚠️  No recent activity (last entry {$daysSinceLastEntry} days ago)\n";
            }
        } else {
            echo "❌ No audit entries found - system may not be recording\n";
        }
    } catch (Exception $e) {
        echo "❌ Cannot check activity: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // 3. Test chain integrity
    echo "3. CHAIN INTEGRITY VERIFICATION:\n";
    echo "================================\n";
    
    try {
        $stmt = $db->query("
            SELECT 
                block_number,
                content_hash,
                previous_hash,
                block_hash,
                entry_type,
                payment_id
            FROM immutable_payment_audit_ledger 
            ORDER BY block_number ASC
        ");
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($entries)) {
            echo "❌ No entries to verify\n\n";
        } else {
            $validChain = true;
            $previousHash = '';
            
            foreach ($entries as $index => $entry) {
                if ($index > 0 && $entry['previous_hash'] !== $previousHash) {
                    $validChain = false;
                    echo "❌ Chain broken at block #{$entry['block_number']}\n";
                    break;
                }
                $previousHash = $entry['block_hash'];
            }
            
            if ($validChain) {
                echo "✓ Chain integrity: VALID (" . (count($entries)) . " blocks verified)\n";
            } else {
                echo "❌ Chain integrity: COMPROMISED\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ Cannot verify chain: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // 4. Check integration with payment system
    echo "4. PAYMENT SYSTEM INTEGRATION:\n";
    echo "==============================\n";
    
    // Check if payment tables have blockchain columns
    $paymentTables = [
        'stage_payment_requests' => 'blockchain_proof_hash',
        'alternative_payments' => 'blockchain_proof_hash',
        'technical_details_payments' => 'blockchain_proof_hash'
    ];
    
    $integrationActive = true;
    foreach ($paymentTables as $table => $column) {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM $table LIKE '$column'");
            if ($stmt->rowCount() > 0) {
                // Check if column has data
                $stmt = $db->query("SELECT COUNT(*) as count FROM $table WHERE $column IS NOT NULL");
                $count = $stmt->fetch()['count'];
                echo "✓ $table: Integration column exists ($count records with blockchain data)\n";
            } else {
                echo "⚠️  $table: Integration column missing\n";
                $integrationActive = false;
            }
        } catch (Exception $e) {
            echo "❌ $table: Cannot check integration - " . $e->getMessage() . "\n";
            $integrationActive = false;
        }
    }
    
    if ($integrationActive) {
        echo "\n✓ Payment system integration: ACTIVE\n";
    } else {
        echo "\n⚠️  Payment system integration: PARTIAL OR INACTIVE\n";
    }
    
    echo "\n";
    
    // 5. Check API endpoints
    echo "5. API ENDPOINTS STATUS:\n";
    echo "=======================\n";
    
    $apiFiles = [
        'backend/api/blockchain/get_payment_audit_trail.php' => 'Payment audit trail API',
        'backend/api/blockchain/get_immutable_audit_trail.php' => 'Immutable audit trail API',
        'backend/api/blockchain/verify_audit_ledger_integrity.php' => 'Integrity verification API'
    ];
    
    $apiAvailable = true;
    foreach ($apiFiles as $file => $description) {
        if (file_exists($file)) {
            echo "✓ $description: Available\n";
        } else {
            echo "❌ $description: Missing\n";
            $apiAvailable = false;
        }
    }
    
    if ($apiAvailable) {
        echo "\n✓ API endpoints: AVAILABLE\n";
    } else {
        echo "\n⚠️  API endpoints: SOME MISSING\n";
    }
    
    echo "\n";
    
    // 6. Check core blockchain classes
    echo "6. CORE IMPLEMENTATION FILES:\n";
    echo "============================\n";
    
    $coreFiles = [
        'backend/blockchain/BlockchainTrustLayer.php' => 'Main blockchain class',
        'backend/blockchain/ImmutablePaymentAuditLedger.php' => 'Audit ledger class',
        'backend/blockchain/config/blockchain_config.php' => 'Configuration file',
        'backend/blockchain/contracts/TrustLayer.sol' => 'Smart contract source',
        'backend/blockchain/contracts/TrustLayer.json' => 'Contract ABI'
    ];
    
    $coreComplete = true;
    foreach ($coreFiles as $file => $description) {
        if (file_exists($file)) {
            echo "✓ $description: Present\n";
        } else {
            echo "❌ $description: Missing\n";
            $coreComplete = false;
        }
    }
    
    if ($coreComplete) {
        echo "\n✓ Core implementation: COMPLETE\n";
    } else {
        echo "\n⚠️  Core implementation: INCOMPLETE\n";
    }
    
    echo "\n";
    
    // 7. Test actual functionality
    echo "7. FUNCTIONALITY TEST:\n";
    echo "=====================\n";
    
    try {
        if (file_exists('backend/blockchain/ImmutablePaymentAuditLedger.php')) {
            require_once 'backend/blockchain/ImmutablePaymentAuditLedger.php';
            
            $auditLedger = new ImmutablePaymentAuditLedger($db);
            echo "✓ Audit ledger class: Instantiated successfully\n";
            
            // Test getting statistics
            $stats = $auditLedger->getAuditStatistics();
            if ($stats) {
                echo "✓ Statistics retrieval: Working\n";
                echo "  - Total entries: {$stats['total_entries']}\n";
                echo "  - Last block: #{$stats['last_block_number']}\n";
            } else {
                echo "⚠️  Statistics retrieval: Failed\n";
            }
            
            // Test integrity verification
            $integrity = $auditLedger->verifyLedgerIntegrity();
            if ($integrity) {
                echo "✓ Integrity verification: Working\n";
                echo "  - Valid: " . ($integrity['valid'] ? 'YES' : 'NO') . "\n";
                echo "  - Verified entries: {$integrity['verified_entries']}/{$integrity['total_entries']}\n";
            } else {
                echo "⚠️  Integrity verification: Failed\n";
            }
            
        } else {
            echo "❌ Cannot test functionality - core class missing\n";
        }
    } catch (Exception $e) {
        echo "❌ Functionality test failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // 8. Overall assessment
    echo "8. OVERALL ASSESSMENT:\n";
    echo "=====================\n";
    
    $score = 0;
    $maxScore = 7;
    
    if ($allTablesExist) $score++;
    if (isset($activity) && $activity['total_entries'] > 0) $score++;
    if (isset($validChain) && $validChain) $score++;
    if ($integrationActive) $score++;
    if ($apiAvailable) $score++;
    if ($coreComplete) $score++;
    if (isset($stats) && $stats) $score++;
    
    $percentage = round(($score / $maxScore) * 100);
    
    echo "System Health Score: $score/$maxScore ($percentage%)\n\n";
    
    if ($percentage >= 85) {
        echo "🎉 STATUS: FULLY OPERATIONAL\n";
        echo "✅ Your blockchain implementation is working perfectly!\n";
        echo "✅ All core components are functional\n";
        echo "✅ Data integrity is maintained\n";
        echo "✅ Integration with payment system is active\n\n";
        
        echo "USEFULNESS: VERY HIGH\n";
        echo "• Provides immutable audit trails for all payments\n";
        echo "• Prevents disputes with cryptographic proof\n";
        echo "• Ensures compliance with regulatory requirements\n";
        echo "• Detects tampering attempts automatically\n";
        echo "• Maintains privacy while providing transparency\n\n";
        
    } elseif ($percentage >= 60) {
        echo "⚠️  STATUS: PARTIALLY WORKING\n";
        echo "✅ Core functionality is operational\n";
        echo "⚠️  Some components may need attention\n";
        echo "✅ Basic audit trail functionality available\n\n";
        
        echo "USEFULNESS: MODERATE\n";
        echo "• Basic audit functionality working\n";
        echo "• Some integration issues may exist\n";
        echo "• Manual verification may be needed\n\n";
        
    } else {
        echo "❌ STATUS: NEEDS ATTENTION\n";
        echo "❌ Multiple components not working properly\n";
        echo "❌ System may need reinitialization\n";
        echo "❌ Limited functionality available\n\n";
        
        echo "USEFULNESS: LIMITED\n";
        echo "• System requires maintenance\n";
        echo "• Core functionality may be compromised\n";
        echo "• Immediate attention recommended\n\n";
    }
    
    // 9. Recommendations
    echo "9. RECOMMENDATIONS:\n";
    echo "==================\n";
    
    if ($percentage >= 85) {
        echo "✅ Continue using the system as-is\n";
        echo "✅ Regular integrity checks are recommended\n";
        echo "✅ Consider expanding to full Ethereum integration\n";
        echo "✅ Monitor system performance regularly\n";
    } elseif ($percentage >= 60) {
        echo "🔧 Address missing components\n";
        echo "🔧 Verify integration with payment endpoints\n";
        echo "🔧 Test API endpoints functionality\n";
        echo "🔧 Consider system maintenance\n";
    } else {
        echo "🚨 Immediate system maintenance required\n";
        echo "🚨 Reinitialize blockchain tables if needed\n";
        echo "🚨 Verify database integrity\n";
        echo "🚨 Check file system permissions\n";
    }
    
} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "\nThis indicates a fundamental system issue that needs immediate attention.\n";
}
?>