# Blockchain Trust Layer Integration Instructions

This document provides step-by-step instructions for integrating the blockchain trust layer into the existing construction payment system without modifying existing functionality.

## Overview

The blockchain integration operates as a **background trust layer** that:
- Records cryptographic proofs of payment events on Ethereum testnet
- Provides immutable audit trails for dispute resolution
- Operates non-intrusively without affecting existing payment workflows
- Fails gracefully if blockchain operations encounter issues

## Prerequisites

1. **PHP 7.4+** with extensions: `curl`, `json`, `openssl`, `mbstring`
2. **Composer** for dependency management
3. **Ethereum testnet access** (Sepolia recommended)
4. **Infura or Alchemy account** for RPC access

## Installation Steps

### Step 1: Run Deployment Script

```bash
cd backend/blockchain/setup
php deploy_blockchain_integration.php
```

This script will:
- Validate configuration
- Create database schema
- Check dependencies
- Generate deployment report

### Step 2: Install Dependencies

```bash
cd /path/to/your/project
composer install
```

Required packages:
- `sc0vu/web3.php`: Ethereum interaction
- `kornrunner/keccak`: Cryptographic hashing

### Step 3: Configure Blockchain Settings

Edit `backend/blockchain/config/blockchain_config.php`:

```php
// Set your Infura/Alchemy endpoint
define('ETHEREUM_RPC_URL', 'https://sepolia.infura.io/v3/YOUR_PROJECT_ID');

// Set wallet credentials (use environment variables in production)
define('ETHEREUM_PRIVATE_KEY', getenv('ETHEREUM_PRIVATE_KEY'));
define('ETHEREUM_PUBLIC_ADDRESS', getenv('ETHEREUM_PUBLIC_ADDRESS'));
```

### Step 4: Deploy Smart Contract

1. **Install Hardhat or Truffle** (for contract deployment)
2. **Deploy TrustLayer.sol** to Sepolia testnet
3. **Update contract address** in `blockchain_config.php`:

```php
define('TRUST_CONTRACT_ADDRESS', '0xYourDeployedContractAddress');
```

### Step 5: Add Integration Hooks

Add blockchain integration to existing payment endpoints by including the appropriate patch files:

#### Payment Initiation Endpoints

Add to `backend/api/homeowner/initiate_stage_payment.php` (after line ~150):

```php
// Blockchain Trust Layer Integration
try {
    require_once __DIR__ . '/../../blockchain/integration_patches/payment_initiation_patch.php';
    
    $paymentData = [
        'payment_request_id' => $payment_request_id,
        'project_id' => $request['project_id'] ?? null,
        'homeowner_id' => $homeowner_id,
        'contractor_id' => $request['contractor_id'] ?? null,
        'amount' => $amount,
        'stage_name' => $request['stage_name'] ?? null,
        'payment_method' => 'razorpay',
        'razorpay_order_id' => $razorpayOrder['id'] ?? null
    ];
    
    integrateBlockchainPaymentInitiation($db, $paymentData);
    
} catch (Exception $e) {
    error_log("Blockchain integration failed: " . $e->getMessage());
}
```

#### Payment Completion Endpoints

Add to `backend/api/homeowner/verify_stage_payment.php` (after line ~100):

```php
// Blockchain Trust Layer Integration
try {
    require_once __DIR__ . '/../../blockchain/integration_patches/payment_completion_patch.php';
    
    $razorpayData = [
        'payment_id' => $razorpay_payment_id,
        'order_id' => $razorpay_order_id,
        'signature' => $razorpay_signature
    ];
    
    integrateBlockchainRazorpayCompletion($db, $payment_request_id, $razorpayData);
    
} catch (Exception $e) {
    error_log("Blockchain integration failed: " . $e->getMessage());
}
```

#### Contractor Verification Endpoints

Add to contractor verification endpoints:

```php
// Blockchain Trust Layer Integration
try {
    require_once __DIR__ . '/../../blockchain/integration_patches/contractor_verification_patch.php';
    
    integrateBlockchainAlternativeContractorVerification($db, $alternativePaymentRecord, $contractor_id, $contractor_notes);
    
} catch (Exception $e) {
    error_log("Blockchain integration failed: " . $e->getMessage());
}
```

#### Admin Verification Endpoints

Add to `backend/api/admin/verify_payment_receipt.php`:

```php
// Blockchain Trust Layer Integration
try {
    require_once __DIR__ . '/../../blockchain/integration_patches/admin_verification_patch.php';
    
    integrateBlockchainAdminPaymentVerification($db, $paymentRecord, $verification_action, $admin_notes, $admin_username, $auto_progress_update);
    
} catch (Exception $e) {
    error_log("Blockchain integration failed: " . $e->getMessage());
}
```

## Integration Points Summary

| Endpoint | Integration Point | Function |
|----------|------------------|----------|
| `initiate_stage_payment.php` | After Razorpay order creation | `integrateBlockchainPaymentInitiation()` |
| `initiate_alternative_payment.php` | After payment setup | `integrateBlockchainPaymentInitiation()` |
| `verify_stage_payment.php` | After signature verification | `integrateBlockchainRazorpayCompletion()` |
| `verify_payment_receipt.php` (contractor) | After receipt verification | `integrateBlockchainAlternativeContractorVerification()` |
| `verify_payment_receipt.php` (admin) | After admin approval | `integrateBlockchainAdminPaymentVerification()` |

## Database Schema

The integration adds these tables without modifying existing ones:

- `blockchain_trust_records`: Blockchain transaction references
- `payment_proof_data`: Local storage of payment proofs
- `blockchain_integration_status`: Integration status tracking
- `blockchain_network_status`: Network health monitoring
- `blockchain_operation_logs`: Operation logging

Optional columns added to existing tables:
- `blockchain_proof_hash` (added to payment tables as reference)

## Testing

### Test Blockchain Integration

```php
// Test payment proof generation
require_once 'backend/blockchain/BlockchainTrustLayer.php';

$trustLayer = new BlockchainTrustLayer($db);
$testData = [
    'payment_request_id' => 123,
    'amount' => 50000.00,
    'stage_name' => 'Foundation',
    'payment_method' => 'razorpay'
];

$proof = $trustLayer->generatePaymentProof($testData);
var_dump($proof);
```

### Test Integration Hooks

```php
// Test payment initiation hook
require_once 'backend/blockchain/PaymentBlockchainIntegrator.php';

$integrator = PaymentBlockchainIntegrator::createIntegrationHooks($db);
$integrator->onPaymentInitiated($testPaymentData);

// Check blockchain status
$status = $integrator->getBlockchainStatus();
var_dump($status);
```

## Monitoring and Maintenance

### Check Integration Status

```php
// Get blockchain integration status for a payment
$auditTrail = $integrator->getPaymentAuditTrail($payment_request_id);
```

### Monitor Blockchain Operations

```sql
-- Check recent blockchain operations
SELECT * FROM blockchain_operation_logs 
ORDER BY created_at DESC 
LIMIT 10;

-- Check integration status
SELECT * FROM blockchain_integration_status 
WHERE last_blockchain_sync > DATE_SUB(NOW(), INTERVAL 1 DAY);
```

### Error Handling

All blockchain operations are wrapped in try-catch blocks and will not affect payment processing if they fail. Errors are logged to PHP error log.

## Security Considerations

1. **Private Keys**: Store in environment variables, never in code
2. **RPC Endpoints**: Use secure HTTPS endpoints (Infura/Alchemy)
3. **Gas Limits**: Set reasonable limits to prevent excessive costs
4. **Data Privacy**: Only cryptographic proofs stored on blockchain, no personal data

## Troubleshooting

### Common Issues

1. **Web3 Connection Failed**
   - Check RPC endpoint configuration
   - Verify network connectivity
   - Ensure Infura/Alchemy project is active

2. **Smart Contract Not Found**
   - Verify contract address is correct
   - Ensure contract is deployed to correct network
   - Check contract ABI matches deployed version

3. **Database Schema Issues**
   - Run deployment script again
   - Check MySQL user permissions
   - Verify table creation in deployment report

### Enable Debug Logging

```php
// In blockchain_config.php
define('BLOCKCHAIN_LOG_LEVEL', 'DEBUG');
```

## Rollback Instructions

If you need to disable blockchain integration:

1. Set `BLOCKCHAIN_ENABLED = false` in `blockchain_config.php`
2. Remove integration hooks from payment endpoints
3. Optionally drop blockchain tables (data will be lost)

The system will continue to function normally without blockchain integration.

## Support

For issues or questions:
1. Check deployment report for configuration errors
2. Review PHP error logs for blockchain operation failures
3. Verify smart contract deployment and configuration
4. Test with small amounts on testnet before production use