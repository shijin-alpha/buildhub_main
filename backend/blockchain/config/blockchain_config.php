<?php
/**
 * Blockchain Trust Layer Configuration
 * 
 * Configuration for Ethereum blockchain integration with BuildHub payment system
 * Address: 0xf8e81D47203A594245E36C48e151709F0C19fBe8
 */

// Network Configuration
define('ETHEREUM_NETWORK', 'sepolia'); // testnet for development
define('ETHEREUM_RPC_URL', 'https://sepolia.infura.io/v3/' . getenv('INFURA_PROJECT_ID'));
define('ETHEREUM_CHAIN_ID', 11155111); // Sepolia testnet chain ID

// Contract Configuration
define('TRUST_CONTRACT_ADDRESS', '0xf8e81D47203A594245E36C48e151709F0C19fBe8');
define('TRUST_CONTRACT_ABI_PATH', __DIR__ . '/../contracts/TrustLayer.json');

// Account Configuration
define('ETHEREUM_PRIVATE_KEY', getenv('ETHEREUM_PRIVATE_KEY'));
define('ETHEREUM_PUBLIC_ADDRESS', getenv('ETHEREUM_PUBLIC_ADDRESS'));

// Gas Configuration
define('DEFAULT_GAS_LIMIT', 300000);
define('DEFAULT_GAS_PRICE', '20000000000'); // 20 Gwei
define('MAX_GAS_PRICE', '100000000000'); // 100 Gwei

// Integration Settings
define('BLOCKCHAIN_ENABLED', true);
define('BLOCKCHAIN_ASYNC_MODE', true);
define('BLOCKCHAIN_RETRY_ATTEMPTS', 3);
define('BLOCKCHAIN_TIMEOUT', 30); // seconds

// Logging Configuration
define('BLOCKCHAIN_LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARN, ERROR
define('BLOCKCHAIN_LOG_FILE', __DIR__ . '/../../logs/blockchain.log');

// Database Configuration
define('BLOCKCHAIN_DB_PREFIX', 'blockchain_');
define('BLOCKCHAIN_PROOF_RETENTION_DAYS', 365);

// Security Configuration
define('BLOCKCHAIN_RATE_LIMIT', 100); // requests per minute
define('BLOCKCHAIN_WHITELIST_ENABLED', true);
define('BLOCKCHAIN_AUTHORIZED_ADDRESSES', [
    '0xf8e81D47203A594245E36C48e151709F0C19fBe8', // Primary integration address
    getenv('ETHEREUM_PUBLIC_ADDRESS') // Runtime address
]);

// Feature Flags
define('ENABLE_PAYMENT_INITIATION_RECORDING', true);
define('ENABLE_PAYMENT_COMPLETION_RECORDING', true);
define('ENABLE_CONTRACTOR_VERIFICATION_RECORDING', true);
define('ENABLE_ADMIN_VERIFICATION_RECORDING', true);
define('ENABLE_AUDIT_TRAIL_API', true);

// Error Handling
define('BLOCKCHAIN_FAIL_SILENTLY', true); // Don't break payment flow on blockchain errors
define('BLOCKCHAIN_ALERT_ON_FAILURE', true);
define('BLOCKCHAIN_ALERT_EMAIL', getenv('ADMIN_EMAIL'));

// Performance Settings
define('BLOCKCHAIN_BATCH_SIZE', 10);
define('BLOCKCHAIN_QUEUE_ENABLED', true);
define('BLOCKCHAIN_CACHE_TTL', 300); // 5 minutes

/**
 * Get blockchain configuration array
 */
function getBlockchainConfig() {
    return [
        'network' => ETHEREUM_NETWORK,
        'rpc_url' => ETHEREUM_RPC_URL,
        'chain_id' => ETHEREUM_CHAIN_ID,
        'contract_address' => TRUST_CONTRACT_ADDRESS,
        'contract_abi_path' => TRUST_CONTRACT_ABI_PATH,
        'private_key' => ETHEREUM_PRIVATE_KEY,
        'public_address' => ETHEREUM_PUBLIC_ADDRESS,
        'gas_limit' => DEFAULT_GAS_LIMIT,
        'gas_price' => DEFAULT_GAS_PRICE,
        'max_gas_price' => MAX_GAS_PRICE,
        'enabled' => BLOCKCHAIN_ENABLED,
        'async_mode' => BLOCKCHAIN_ASYNC_MODE,
        'retry_attempts' => BLOCKCHAIN_RETRY_ATTEMPTS,
        'timeout' => BLOCKCHAIN_TIMEOUT,
        'log_level' => BLOCKCHAIN_LOG_LEVEL,
        'log_file' => BLOCKCHAIN_LOG_FILE,
        'authorized_addresses' => BLOCKCHAIN_AUTHORIZED_ADDRESSES,
        'fail_silently' => BLOCKCHAIN_FAIL_SILENTLY
    ];
}

/**
 * Validate blockchain configuration
 */
function validateBlockchainConfig() {
    $errors = [];
    
    if (!TRUST_CONTRACT_ADDRESS || !preg_match('/^0x[a-fA-F0-9]{40}$/', TRUST_CONTRACT_ADDRESS)) {
        $errors[] = 'Invalid contract address: ' . TRUST_CONTRACT_ADDRESS;
    }
    
    if (!ETHEREUM_PRIVATE_KEY) {
        $errors[] = 'ETHEREUM_PRIVATE_KEY environment variable not set';
    }
    
    if (!ETHEREUM_PUBLIC_ADDRESS || !preg_match('/^0x[a-fA-F0-9]{40}$/', ETHEREUM_PUBLIC_ADDRESS)) {
        $errors[] = 'Invalid public address: ' . ETHEREUM_PUBLIC_ADDRESS;
    }
    
    if (!file_exists(TRUST_CONTRACT_ABI_PATH)) {
        $errors[] = 'Contract ABI file not found: ' . TRUST_CONTRACT_ABI_PATH;
    }
    
    if (!getenv('INFURA_PROJECT_ID')) {
        $errors[] = 'INFURA_PROJECT_ID environment variable not set';
    }
    
    return $errors;
}

/**
 * Get contract ABI
 */
function getTrustContractABI() {
    if (!file_exists(TRUST_CONTRACT_ABI_PATH)) {
        throw new Exception('Contract ABI file not found: ' . TRUST_CONTRACT_ABI_PATH);
    }
    
    $abi = file_get_contents(TRUST_CONTRACT_ABI_PATH);
    return json_decode($abi, true);
}

/**
 * Check if address is authorized
 */
function isAuthorizedAddress($address) {
    return in_array(strtolower($address), array_map('strtolower', BLOCKCHAIN_AUTHORIZED_ADDRESSES));
}

/**
 * Get network explorer URL for transaction
 */
function getTransactionExplorerUrl($txHash) {
    switch (ETHEREUM_NETWORK) {
        case 'mainnet':
            return "https://etherscan.io/tx/{$txHash}";
        case 'sepolia':
            return "https://sepolia.etherscan.io/tx/{$txHash}";
        case 'goerli':
            return "https://goerli.etherscan.io/tx/{$txHash}";
        default:
            return null;
    }
}

/**
 * Get network explorer URL for address
 */
function getAddressExplorerUrl($address) {
    switch (ETHEREUM_NETWORK) {
        case 'mainnet':
            return "https://etherscan.io/address/{$address}";
        case 'sepolia':
            return "https://sepolia.etherscan.io/address/{$address}";
        case 'goerli':
            return "https://goerli.etherscan.io/address/{$address}";
        default:
            return null;
    }
}

// Initialize logging directory
$logDir = dirname(BLOCKCHAIN_LOG_FILE);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Log configuration validation on load
$configErrors = validateBlockchainConfig();
if (!empty($configErrors) && BLOCKCHAIN_LOG_LEVEL === 'DEBUG') {
    error_log('Blockchain configuration errors: ' . implode(', ', $configErrors));
}