<?php

/**
 * Blockchain Integration Deployment Script
 * 
 * This script sets up the blockchain trust layer integration
 * without modifying existing payment functionality.
 */

require_once __DIR__ . '/../../config/database.php';

class BlockchainIntegrationDeployer {
    
    private $db;
    private $errors = [];
    private $warnings = [];
    private $success = [];
    
    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
        } catch (Exception $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Deploy blockchain integration
     */
    public function deploy() {
        echo "=== Blockchain Trust Layer Integration Deployment ===\n\n";
        
        // Step 1: Validate configuration
        $this->validateConfiguration();
        
        // Step 2: Create database schema
        $this->createDatabaseSchema();
        
        // Step 3: Install dependencies
        $this->installDependencies();
        
        // Step 4: Validate smart contract
        $this->validateSmartContract();
        
        // Step 5: Test integration
        $this->testIntegration();
        
        // Step 6: Generate integration report
        $this->generateReport();
        
        echo "\n=== Deployment Complete ===\n";
    }
    
    /**
     * Validate blockchain configuration
     */
    private function validateConfiguration() {
        echo "1. Validating blockchain configuration...\n";
        
        require_once __DIR__ . '/../config/blockchain_config.php';
        
        $validation = validateBlockchainConfig();
        
        if ($validation['valid']) {
            $this->success[] = "Blockchain configuration is valid";
        } else {
            foreach ($validation['errors'] as $error) {
                $this->errors[] = "Configuration error: " . $error;
            }
        }
        
        // Check if Web3 PHP library is available
        if (!class_exists('Web3\Web3')) {
            $this->warnings[] = "Web3 PHP library not found. Run 'composer install' to install dependencies.";
        } else {
            $this->success[] = "Web3 PHP library is available";
        }
        
        echo "   Configuration validation complete.\n\n";
    }
    
    /**
     * Create database schema
     */
    private function createDatabaseSchema() {
        echo "2. Creating blockchain database schema...\n";
        
        try {
            $schemaFile = __DIR__ . '/../database/blockchain_trust_schema.sql';
            
            if (!file_exists($schemaFile)) {
                $this->errors[] = "Schema file not found: " . $schemaFile;
                return;
            }
            
            $sql = file_get_contents($schemaFile);
            $statements = explode(';', $sql);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    try {
                        $this->db->exec($statement);
                    } catch (PDOException $e) {
                        // Ignore "table already exists" errors and other non-critical errors
                        if (strpos($e->getMessage(), 'already exists') === false && 
                            strpos($e->getMessage(), 'Duplicate key name') === false) {
                            $this->warnings[] = "SQL execution warning: " . $e->getMessage();
                        }
                    }
                }
            }
            $this->success[] = "Database schema created successfully";
            
        } catch (Exception $e) {
            $this->errors[] = "Database schema creation failed: " . $e->getMessage();
        }
        
        echo "   Database schema setup complete.\n\n";
    }
    
    /**
     * Install dependencies
     */
    private function installDependencies() {
        echo "3. Checking dependencies...\n";
        
        // Check if composer.json exists
        $composerFile = __DIR__ . '/../../../composer.json';
        
        if (!file_exists($composerFile)) {
            $this->createComposerFile();
        }
        
        // Check required PHP extensions
        $requiredExtensions = ['curl', 'json', 'openssl', 'mbstring'];
        
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $this->errors[] = "Required PHP extension not loaded: " . $extension;
            } else {
                $this->success[] = "PHP extension available: " . $extension;
            }
        }
        
        echo "   Dependency check complete.\n\n";
    }
    
    /**
     * Create composer.json for blockchain dependencies
     */
    private function createComposerFile() {
        $composerConfig = [
            "name" => "construction-platform/blockchain-integration",
            "description" => "Blockchain trust layer for construction payment system",
            "require" => [
                "php" => ">=7.4",
                "sc0vu/web3.php" => "^0.1.0",
                "kornrunner/keccak" => "^1.1"
            ],
            "autoload" => [
                "psr-4" => [
                    "BlockchainTrust\\" => "backend/blockchain/"
                ]
            ]
        ];
        
        $composerFile = __DIR__ . '/../../../composer.json';
        file_put_contents($composerFile, json_encode($composerConfig, JSON_PRETTY_PRINT));
        
        $this->warnings[] = "Created composer.json. Run 'composer install' to install blockchain dependencies.";
    }
    
    /**
     * Validate smart contract configuration
     */
    private function validateSmartContract() {
        echo "4. Validating smart contract configuration...\n";
        
        $contractFile = __DIR__ . '/../contracts/TrustLayer.json';
        
        if (!file_exists($contractFile)) {
            $this->errors[] = "Smart contract ABI file not found: " . $contractFile;
            return;
        }
        
        $contractAbi = json_decode(file_get_contents($contractFile), true);
        
        if (!$contractAbi) {
            $this->errors[] = "Invalid smart contract ABI format";
            return;
        }
        
        // Check required contract functions
        $requiredFunctions = [
            'recordPaymentInitiation',
            'recordPaymentCompletion',
            'recordVerification',
            'getPaymentRecord',
            'getPaymentStatus'
        ];
        
        $availableFunctions = array_column($contractAbi, 'name');
        
        foreach ($requiredFunctions as $function) {
            if (in_array($function, $availableFunctions)) {
                $this->success[] = "Smart contract function available: " . $function;
            } else {
                $this->errors[] = "Smart contract function missing: " . $function;
            }
        }
        
        echo "   Smart contract validation complete.\n\n";
    }
    
    /**
     * Test blockchain integration
     */
    private function testIntegration() {
        echo "5. Testing blockchain integration...\n";
        
        try {
            require_once __DIR__ . '/../BlockchainTrustLayer.php';
            
            $trustLayer = new BlockchainTrustLayer($this->db);
            $status = $trustLayer->getStatus();
            
            if ($status['enabled']) {
                $this->success[] = "Blockchain trust layer is enabled and configured";
                
                // Test proof generation
                $testPaymentData = [
                    'payment_request_id' => 999999,
                    'project_id' => 999999,
                    'homeowner_id' => 999999,
                    'contractor_id' => 999999,
                    'amount' => 50000.00,
                    'stage_name' => 'Test Stage',
                    'payment_method' => 'test'
                ];
                
                $proof = $trustLayer->generatePaymentProof($testPaymentData);
                
                if ($proof) {
                    $this->success[] = "Payment proof generation test successful";
                } else {
                    $this->warnings[] = "Payment proof generation test failed (non-critical)";
                }
                
            } else {
                $this->warnings[] = "Blockchain trust layer is disabled or not configured";
            }
            
        } catch (Exception $e) {
            $this->warnings[] = "Blockchain integration test failed: " . $e->getMessage();
        }
        
        echo "   Integration testing complete.\n\n";
    }
    
    /**
     * Generate deployment report
     */
    private function generateReport() {
        echo "6. Generating deployment report...\n";
        
        $report = [
            'deployment_timestamp' => date('Y-m-d H:i:s'),
            'success_count' => count($this->success),
            'warning_count' => count($this->warnings),
            'error_count' => count($this->errors),
            'success_items' => $this->success,
            'warnings' => $this->warnings,
            'errors' => $this->errors,
            'next_steps' => $this->getNextSteps()
        ];
        
        $reportFile = __DIR__ . '/deployment_report_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
        
        echo "   Deployment report saved to: " . $reportFile . "\n\n";
        
        $this->displayReport($report);
    }
    
    /**
     * Display deployment report
     */
    private function displayReport($report) {
        echo "=== DEPLOYMENT REPORT ===\n";
        echo "Timestamp: " . $report['deployment_timestamp'] . "\n";
        echo "Success: " . $report['success_count'] . " items\n";
        echo "Warnings: " . $report['warning_count'] . " items\n";
        echo "Errors: " . $report['error_count'] . " items\n\n";
        
        if (!empty($this->success)) {
            echo "✅ SUCCESS:\n";
            foreach ($this->success as $item) {
                echo "   - " . $item . "\n";
            }
            echo "\n";
        }
        
        if (!empty($this->warnings)) {
            echo "⚠️  WARNINGS:\n";
            foreach ($this->warnings as $item) {
                echo "   - " . $item . "\n";
            }
            echo "\n";
        }
        
        if (!empty($this->errors)) {
            echo "❌ ERRORS:\n";
            foreach ($this->errors as $item) {
                echo "   - " . $item . "\n";
            }
            echo "\n";
        }
        
        echo "📋 NEXT STEPS:\n";
        foreach ($report['next_steps'] as $step) {
            echo "   " . $step . "\n";
        }
    }
    
    /**
     * Get next steps based on deployment results
     */
    private function getNextSteps() {
        $steps = [];
        
        if (count($this->errors) > 0) {
            $steps[] = "1. Fix configuration errors listed above";
            $steps[] = "2. Re-run deployment script after fixing errors";
        } else {
            $steps[] = "1. Run 'composer install' to install blockchain dependencies";
            $steps[] = "2. Configure Ethereum RPC endpoint in blockchain_config.php";
            $steps[] = "3. Deploy smart contract to Sepolia testnet";
            $steps[] = "4. Update TRUST_CONTRACT_ADDRESS in blockchain_config.php";
            $steps[] = "5. Add integration hooks to existing payment endpoints";
            $steps[] = "6. Test blockchain integration with sample payments";
        }
        
        return $steps;
    }
}

// Run deployment if script is executed directly
if (php_sapi_name() === 'cli') {
    $deployer = new BlockchainIntegrationDeployer();
    $deployer->deploy();
}