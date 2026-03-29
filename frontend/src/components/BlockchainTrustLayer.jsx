import React, { useState, useEffect } from 'react';
import './BlockchainTrustLayer.css';

/**
 * BlockchainTrustLayer Component
 * 
 * Provides interface for blockchain trust layer operations
 * including payment recording, verification, and status checking
 */
const BlockchainTrustLayer = ({ projectId, onStatusUpdate }) => {
    const [contractStats, setContractStats] = useState(null);
    const [paymentRecords, setPaymentRecords] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [activeTab, setActiveTab] = useState('overview');

    // Contract address from integration
    const CONTRACT_ADDRESS = '0xf8e81D47203A594245E36C48e151709F0C19fBe8';
    const EXPLORER_URL = 'https://etherscan.io';

    useEffect(() => {
        loadContractStats();
        if (projectId) {
            loadProjectPaymentRecords();
        }
    }, [projectId]);

    const loadContractStats = async () => {
        try {
            setLoading(true);
            const response = await fetch('/backend/api/blockchain/contract_stats.php');
            const data = await response.json();
            
            if (data.success) {
                setContractStats(data.data);
            } else {
                setError(data.error || 'Failed to load contract stats');
            }
        } catch (err) {
            setError('Network error loading contract stats');
        } finally {
            setLoading(false);
        }
    };

    const loadProjectPaymentRecords = async () => {
        try {
            const response = await fetch(`/backend/api/blockchain/project_records.php?project_id=${projectId}`);
            const data = await response.json();
            
            if (data.success) {
                setPaymentRecords(data.data);
            }
        } catch (err) {
            console.error('Error loading payment records:', err);
        }
    };

    const recordPaymentInitiation = async (paymentData) => {
        try {
            setLoading(true);
            const response = await fetch('/backend/api/blockchain/payment_recording.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'initiate',
                    ...paymentData
                })
            });

            const result = await response.json();
            
            if (result.success) {
                onStatusUpdate && onStatusUpdate('Payment recorded on blockchain', 'success');
                loadProjectPaymentRecords();
                return result.data;
            } else {
                throw new Error(result.error);
            }
        } catch (err) {
            setError(err.message);
            onStatusUpdate && onStatusUpdate('Failed to record payment', 'error');
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const recordPaymentCompletion = async (proofHash) => {
        try {
            setLoading(true);
            const response = await fetch('/backend/api/blockchain/payment_recording.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'complete',
                    proof_hash: proofHash
                })
            });

            const result = await response.json();
            
            if (result.success) {
                onStatusUpdate && onStatusUpdate('Payment completion recorded', 'success');
                loadProjectPaymentRecords();
                return result.data;
            } else {
                throw new Error(result.error);
            }
        } catch (err) {
            setError(err.message);
            throw err;
        } finally {
            setLoading(false);
        }
    };
    const recordVerification = async (proofHash, verifierType) => {
        try {
            setLoading(true);
            const response = await fetch('/backend/api/blockchain/payment_recording.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'verify',
                    proof_hash: proofHash,
                    verifier_type: verifierType
                })
            });

            const result = await response.json();
            
            if (result.success) {
                onStatusUpdate && onStatusUpdate('Verification recorded', 'success');
                loadProjectPaymentRecords();
                return result.data;
            } else {
                throw new Error(result.error);
            }
        } catch (err) {
            setError(err.message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const getExplorerUrl = (txHash) => {
        return `${EXPLORER_URL}/tx/${txHash}`;
    };

    const getAddressUrl = (address) => {
        return `${EXPLORER_URL}/address/${address}`;
    };

    const formatAddress = (address) => {
        if (!address) return '';
        return `${address.slice(0, 6)}...${address.slice(-4)}`;
    };

    const formatTimestamp = (timestamp) => {
        return new Date(timestamp * 1000).toLocaleString();
    };

    const getStatusBadge = (status) => {
        const statusMap = {
            'initiated': { class: 'status-initiated', text: 'Initiated' },
            'blockchain_pending': { class: 'status-pending', text: 'Pending' },
            'blockchain_confirmed': { class: 'status-confirmed', text: 'Confirmed' },
            'completed': { class: 'status-completed', text: 'Completed' },
            'failed': { class: 'status-failed', text: 'Failed' }
        };
        
        const statusInfo = statusMap[status] || { class: 'status-unknown', text: status };
        return <span className={`status-badge ${statusInfo.class}`}>{statusInfo.text}</span>;
    };

    const renderOverview = () => (
        <div className="blockchain-overview">
            <div className="contract-info">
                <h3>Trust Layer Contract</h3>
                <div className="contract-details">
                    <div className="detail-item">
                        <label>Contract Address:</label>
                        <a 
                            href={getAddressUrl(CONTRACT_ADDRESS)} 
                            target="_blank" 
                            rel="noopener noreferrer"
                            className="address-link"
                        >
                            {formatAddress(CONTRACT_ADDRESS)}
                        </a>
                    </div>
                    <div className="detail-item">
                        <label>Network:</label>
                        <span>Ethereum Mainnet</span>
                    </div>
                </div>
            </div>

            {contractStats && (
                <div className="contract-stats">
                    <h3>Contract Statistics</h3>
                    <div className="stats-grid">
                        <div className="stat-item">
                            <div className="stat-value">{contractStats.total_payments}</div>
                            <div className="stat-label">Total Payments</div>
                        </div>
                        <div className="stat-item">
                            <div className="stat-value">{contractStats.completed_payments}</div>
                            <div className="stat-label">Completed</div>
                        </div>
                        <div className="stat-item">
                            <div className="stat-value">{contractStats.total_verifications}</div>
                            <div className="stat-label">Verifications</div>
                        </div>
                        <div className="stat-item">
                            <div className="stat-value">
                                {contractStats.contract_active ? 'Active' : 'Inactive'}
                            </div>
                            <div className="stat-label">Status</div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
    const renderPaymentRecords = () => (
        <div className="payment-records">
            <h3>Payment Records</h3>
            {paymentRecords.length === 0 ? (
                <div className="no-records">
                    <p>No blockchain payment records found for this project.</p>
                </div>
            ) : (
                <div className="records-list">
                    {paymentRecords.map((record, index) => (
                        <div key={index} className="record-item">
                            <div className="record-header">
                                <div className="record-title">
                                    <span className="stage-badge">{record.stage}</span>
                                    {getStatusBadge(record.status)}
                                </div>
                                <div className="record-amount">
                                    ${parseFloat(record.amount).toLocaleString()}
                                </div>
                            </div>
                            
                            <div className="record-details">
                                <div className="detail-row">
                                    <label>Proof Hash:</label>
                                    <code className="hash-display">{formatAddress(record.proof_hash)}</code>
                                </div>
                                
                                {record.tx_hash && (
                                    <div className="detail-row">
                                        <label>Transaction:</label>
                                        <a 
                                            href={getExplorerUrl(record.tx_hash)} 
                                            target="_blank" 
                                            rel="noopener noreferrer"
                                            className="tx-link"
                                        >
                                            {formatAddress(record.tx_hash)}
                                        </a>
                                    </div>
                                )}
                                
                                <div className="detail-row">
                                    <label>Created:</label>
                                    <span>{new Date(record.created_at).toLocaleString()}</span>
                                </div>
                                
                                {record.verification_count > 0 && (
                                    <div className="detail-row">
                                        <label>Verifications:</label>
                                        <span className="verification-count">
                                            {record.verification_count}
                                        </span>
                                    </div>
                                )}
                            </div>
                            
                            <div className="record-actions">
                                {record.status === 'blockchain_confirmed' && (
                                    <button 
                                        className="btn-complete"
                                        onClick={() => recordPaymentCompletion(record.proof_hash)}
                                        disabled={loading}
                                    >
                                        Mark Complete
                                    </button>
                                )}
                                
                                <button 
                                    className="btn-verify"
                                    onClick={() => recordVerification(record.proof_hash, 2)}
                                    disabled={loading}
                                >
                                    Add Verification
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );

    const renderActions = () => (
        <div className="blockchain-actions">
            <h3>Blockchain Actions</h3>
            <div className="action-buttons">
                <button 
                    className="btn-primary"
                    onClick={() => {
                        // This would typically be called from a payment form
                        const samplePayment = {
                            project_id: projectId,
                            stage: 'foundation',
                            amount: 5000,
                            type: 'stage_payment'
                        };
                        recordPaymentInitiation(samplePayment);
                    }}
                    disabled={loading || !projectId}
                >
                    Record Test Payment
                </button>
                
                <button 
                    className="btn-secondary"
                    onClick={loadContractStats}
                    disabled={loading}
                >
                    Refresh Stats
                </button>
                
                <a 
                    href={getAddressUrl(CONTRACT_ADDRESS)}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="btn-link"
                >
                    View on Etherscan
                </a>
            </div>
        </div>
    );

    return (
        <div className="blockchain-trust-layer">
            <div className="blockchain-header">
                <h2>Blockchain Trust Layer</h2>
                <div className="integration-status">
                    <span className="status-indicator active"></span>
                    <span>Integrated with {formatAddress(CONTRACT_ADDRESS)}</span>
                </div>
            </div>

            {error && (
                <div className="error-message">
                    <span className="error-icon">⚠️</span>
                    {error}
                    <button 
                        className="error-close"
                        onClick={() => setError(null)}
                    >
                        ×
                    </button>
                </div>
            )}

            <div className="blockchain-tabs">
                <button 
                    className={`tab-button ${activeTab === 'overview' ? 'active' : ''}`}
                    onClick={() => setActiveTab('overview')}
                >
                    Overview
                </button>
                <button 
                    className={`tab-button ${activeTab === 'records' ? 'active' : ''}`}
                    onClick={() => setActiveTab('records')}
                >
                    Payment Records
                </button>
                <button 
                    className={`tab-button ${activeTab === 'actions' ? 'active' : ''}`}
                    onClick={() => setActiveTab('actions')}
                >
                    Actions
                </button>
            </div>

            <div className="blockchain-content">
                {loading && (
                    <div className="loading-overlay">
                        <div className="loading-spinner"></div>
                        <span>Processing blockchain operation...</span>
                    </div>
                )}

                {activeTab === 'overview' && renderOverview()}
                {activeTab === 'records' && renderPaymentRecords()}
                {activeTab === 'actions' && renderActions()}
            </div>
        </div>
    );
};

export default BlockchainTrustLayer;