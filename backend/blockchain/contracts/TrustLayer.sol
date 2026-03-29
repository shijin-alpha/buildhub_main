// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

/**
 * @title TrustLayer
 * @dev Lightweight smart contract for construction payment trust layer
 * 
 * This contract stores only cryptographic proofs and minimal metadata,
 * never storing personal data, payment amounts, or sensitive information.
 * It serves as an immutable audit trail for payment verification.
 */
contract TrustLayer {
    
    // Events for blockchain audit trail
    event PaymentInitiated(
        bytes32 indexed proofHash,
        bytes32 metadataHash,
        uint256 timestamp,
        address indexed recorder
    );
    
    event PaymentCompleted(
        bytes32 indexed proofHash,
        bytes32 completionHash,
        uint256 timestamp,
        address indexed recorder
    );
    
    event VerificationRecorded(
        bytes32 indexed proofHash,
        bytes32 verificationHash,
        uint256 timestamp,
        uint8 verifierType, // 1=contractor, 2=admin
        address indexed recorder
    );
    
    // Structs for data organization
    struct PaymentRecord {
        bytes32 proofHash;
        bytes32 metadataHash;
        uint256 initiationTimestamp;
        bytes32 completionHash;
        uint256 completionTimestamp;
        bool isCompleted;
        uint8 verificationCount;
        address recorder;
    }
    
    struct VerificationRecord {
        bytes32 verificationHash;
        uint256 timestamp;
        uint8 verifierType;
        address recorder;
    }
    
    // Storage mappings
    mapping(bytes32 => PaymentRecord) public paymentRecords;
    mapping(bytes32 => VerificationRecord[]) public verificationRecords;
    mapping(address => bool) public authorizedRecorders;
    
    // Contract owner and access control
    address public owner;
    bool public contractActive;
    
    // Statistics
    uint256 public totalPaymentsRecorded;
    uint256 public totalVerificationsRecorded;
    
    modifier onlyOwner() {
        require(msg.sender == owner, "Only owner can call this function");
        _;
    }
    
    modifier onlyAuthorized() {
        require(authorizedRecorders[msg.sender] || msg.sender == owner, "Not authorized to record");
        _;
    }
    
    modifier contractIsActive() {
        require(contractActive, "Contract is not active");
        _;
    }
    
    constructor() {
        owner = msg.sender;
        contractActive = true;
        authorizedRecorders[msg.sender] = true;
    }
    
    /**
     * @dev Record payment initiation with cryptographic proof
     * @param proofHash Hash of payment context (no sensitive data)
     * @param metadataHash Hash of payment metadata (amount range, stage category, etc.)
     * @param timestamp Payment initiation timestamp
     */
    function recordPaymentInitiation(
        bytes32 proofHash,
        bytes32 metadataHash,
        uint256 timestamp
    ) external onlyAuthorized contractIsActive {
        require(proofHash != bytes32(0), "Invalid proof hash");
        require(metadataHash != bytes32(0), "Invalid metadata hash");
        require(timestamp > 0, "Invalid timestamp");
        require(paymentRecords[proofHash].proofHash == bytes32(0), "Payment already recorded");
        
        paymentRecords[proofHash] = PaymentRecord({
            proofHash: proofHash,
            metadataHash: metadataHash,
            initiationTimestamp: timestamp,
            completionHash: bytes32(0),
            completionTimestamp: 0,
            isCompleted: false,
            verificationCount: 0,
            recorder: msg.sender
        });
        
        totalPaymentsRecorded++;
        
        emit PaymentInitiated(proofHash, metadataHash, timestamp, msg.sender);
    }
    
    /**
     * @dev Record payment completion
     * @param proofHash Original payment proof hash
     * @param completionHash Hash of completion data
     * @param timestamp Payment completion timestamp
     */
    function recordPaymentCompletion(
        bytes32 proofHash,
        bytes32 completionHash,
        uint256 timestamp
    ) external onlyAuthorized contractIsActive {
        require(proofHash != bytes32(0), "Invalid proof hash");
        require(completionHash != bytes32(0), "Invalid completion hash");
        require(timestamp > 0, "Invalid timestamp");
        require(paymentRecords[proofHash].proofHash != bytes32(0), "Payment not found");
        require(!paymentRecords[proofHash].isCompleted, "Payment already completed");
        
        paymentRecords[proofHash].completionHash = completionHash;
        paymentRecords[proofHash].completionTimestamp = timestamp;
        paymentRecords[proofHash].isCompleted = true;
        
        emit PaymentCompleted(proofHash, completionHash, timestamp, msg.sender);
    }
    
    /**
     * @dev Record verification by contractor or admin
     * @param proofHash Original payment proof hash
     * @param verificationHash Hash of verification data
     * @param timestamp Verification timestamp
     * @param verifierType 1=contractor, 2=admin
     */
    function recordVerification(
        bytes32 proofHash,
        bytes32 verificationHash,
        uint256 timestamp,
        uint8 verifierType
    ) external onlyAuthorized contractIsActive {
        require(proofHash != bytes32(0), "Invalid proof hash");
        require(verificationHash != bytes32(0), "Invalid verification hash");
        require(timestamp > 0, "Invalid timestamp");
        require(verifierType == 1 || verifierType == 2, "Invalid verifier type");
        require(paymentRecords[proofHash].proofHash != bytes32(0), "Payment not found");
        
        verificationRecords[proofHash].push(VerificationRecord({
            verificationHash: verificationHash,
            timestamp: timestamp,
            verifierType: verifierType,
            recorder: msg.sender
        }));
        
        paymentRecords[proofHash].verificationCount++;
        totalVerificationsRecorded++;
        
        emit VerificationRecorded(proofHash, verificationHash, timestamp, verifierType, msg.sender);
    }
    
    /**
     * @dev Get payment record details
     * @param proofHash Payment proof hash
     * @return PaymentRecord struct
     */
    function getPaymentRecord(bytes32 proofHash) external view returns (PaymentRecord memory) {
        require(paymentRecords[proofHash].proofHash != bytes32(0), "Payment not found");
        return paymentRecords[proofHash];
    }
    
    /**
     * @dev Get verification records for a payment
     * @param proofHash Payment proof hash
     * @return Array of VerificationRecord structs
     */
    function getVerificationRecords(bytes32 proofHash) external view returns (VerificationRecord[] memory) {
        require(paymentRecords[proofHash].proofHash != bytes32(0), "Payment not found");
        return verificationRecords[proofHash];
    }
    
    /**
     * @dev Check if payment exists and is completed
     * @param proofHash Payment proof hash
     * @return exists Whether payment record exists
     * @return completed Whether payment is completed
     * @return verificationCount Number of verifications
     */
    function getPaymentStatus(bytes32 proofHash) external view returns (
        bool exists,
        bool completed,
        uint8 verificationCount
    ) {
        PaymentRecord memory record = paymentRecords[proofHash];
        exists = record.proofHash != bytes32(0);
        completed = record.isCompleted;
        verificationCount = record.verificationCount;
    }
    
    /**
     * @dev Add authorized recorder
     * @param recorder Address to authorize
     */
    function addAuthorizedRecorder(address recorder) external onlyOwner {
        require(recorder != address(0), "Invalid recorder address");
        authorizedRecorders[recorder] = true;
    }
    
    /**
     * @dev Remove authorized recorder
     * @param recorder Address to remove authorization
     */
    function removeAuthorizedRecorder(address recorder) external onlyOwner {
        authorizedRecorders[recorder] = false;
    }
    
    /**
     * @dev Toggle contract active status
     * @param active New active status
     */
    function setContractActive(bool active) external onlyOwner {
        contractActive = active;
    }
    
    /**
     * @dev Get contract statistics
     * @return totalPayments Total payments recorded
     * @return totalVerifications Total verifications recorded
     * @return isActive Contract active status
     */
    function getContractStats() external view returns (
        uint256 totalPayments,
        uint256 totalVerifications,
        bool isActive
    ) {
        totalPayments = totalPaymentsRecorded;
        totalVerifications = totalVerificationsRecorded;
        isActive = contractActive;
    }
    
    /**
     * @dev Emergency pause function
     */
    function emergencyPause() external onlyOwner {
        contractActive = false;
    }
    
    /**
     * @dev Transfer ownership
     * @param newOwner New owner address
     */
    function transferOwnership(address newOwner) external onlyOwner {
        require(newOwner != address(0), "Invalid new owner address");
        owner = newOwner;
    }
}