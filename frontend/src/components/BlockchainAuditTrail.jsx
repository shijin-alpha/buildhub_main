import React, { useState, useEffect } from 'react';
import './BlockchainAuditTrail.css';

/**
 * Blockchain Audit Trail Component
 * 
 * Displays blockchain trust layer information for payments
 * without exposing sensitive data. Shows immutable audit trail
 * and verification status.
 */
const BlockchainAuditTrail = ({ paymentRequestId, onClose }) => {
    const [auditData, setAuditData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (paymentRequestId) {
            fetchAuditTrail();
        }
    }, [paymentRequestId]);

    const fetchAuditTrail = async () => {
        try {
            setLoading(true);
            const response = await fetch(`/backend/api/blockchain/get_payment_audit_trail.php?payment_request_id=${paymentRequestId}`);
            const data = await response.json();

            if (data.success) {
                setAuditData(data);
            } else {
                setError(data.message || 'Failed to load audit trail');
            }
        } catch (err) {
            setError('Network error loading audit trail');
        } finally {
            setLoading(false);
        }
    };

    const formatTimestamp = (timestamp) => {
        if (!timestamp) return 'N/A';
        return new Date(timestamp).toLocaleString();
    };

    const getTransactionTypeIcon = (type) => {
        switch (type) {
            case 'payment_initiation':
                return '🚀';
            case 'payment_completion':
                return '✅';
            case 'contractor_verification':
                return '🔨';
            case 'admin_verification':
                return '👨‍💼';
            default:
                return '📝';
        }
    };

    const getTransactionTypeLabel = (type) => {
        switch (type) {
            case 'payment_initiation':
                return 'Payment Initiated';
            case 'payment_completion':
                return 'Payment Completed';
            case 'contractor_verification':
                return 'Contractor Verified';
            case 'admin_verification':
                return 'Admin Verified';
            default:
                return 'Unknown Event';
        }
    };

    const getTrustScoreColor = (score) => {
        if (score >= 75) return '#4CAF50'; // Green
        if (score >= 50) return '#FF9800'; // Orange
        if (score >= 25) return '#FFC107'; // Yellow
        return '#F44336'; // Red
    };

    if (loading) {
        return (
            <div className="blockchain-audit-modal">
                <div className="blockchain-audit-content">
                    <div className="loading-spinner">
                        <div className="spinner"></div>
                        <p>Loading blockchain audit trail...</p>
                    </div>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="blockchain-audit-modal">
                <div className="blockchain-audit-content">
                    <div className="audit-header">
                        <h3>Blockchain Audit Trail</h3>
                        <button className="close-btn" onClick={onClose}>×</button>
                    </div>
                    <div className="error-message">
                        <p>⚠️ {error}</p>
                        <button onClick={fetchAuditTrail} className="retry-btn">Retry</button>
                    </div>
                </div>
            </div>
        );
    }

    if (!auditData?.blockchain_enabled) {
        return (
            <div className="blockchain-audit-modal">
                <div className="blockchain-audit-content">
                    <div className="audit-header">
                        <h3>Blockchain Audit Trail</h3>
                        <button className="close-btn" onClick={onClose}>×</button>
                    </div>
                    <div className="blockchain-disabled">
                        <p>🔗 Blockchain trust layer is not enabled for this system.</p>
                        <p>Payment verification relies on traditional database records.</p>
                    </div>
                </div>
            </div>
        );
    }

    const { payment_info, audit_summary, blockchain_audit_trail, integration_status } = auditData;

    return (
        <div className="blockchain-audit-modal">
            <div className="blockchain-audit-content">
                <div className="audit-header">
                    <h3>🔗 Blockchain Audit Trail</h3>
                    <button className="close-btn" onClick={onClose}>×</button>
                </div>

                {/* Payment Summary */}
                <div className="payment-summary">
                    <h4>Payment Information</h4>
                    <div className="payment-details">
                        <div className="detail-item">
                            <span className="label">Stage:</span>
                            <span className="value">{payment_info?.stage_name || 'N/A'}</span>
                        </div>
                        <div className="detail-item">
                            <span className="label">Amount Range:</span>
                            <span className="value">{payment_info?.amount_range || 'N/A'}</span>
                        </div>
                        <div className="detail-item">
                            <span className="label">Status:</span>
                            <span className="value">{payment_info?.status || 'N/A'}</span>
                        </div>
                        <div className="detail-item">
                            <span className="label">Blockchain Proof:</span>
                            <span className="value">
                                {payment_info?.has_blockchain_proof ? '✅ Yes' : '❌ No'}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Trust Score */}
                <div className="trust-score-section">
                    <h4>Trust Score</h4>
                    <div className="trust-score">
                        <div 
                            className="trust-score-bar"
                            style={{ 
                                width: `${audit_summary?.trust_score || 0}%`,
                                backgroundColor: getTrustScoreColor(audit_summary?.trust_score || 0)
                            }}
                        ></div>
                        <span className="trust-score-text">
                            {audit_summary?.trust_score || 0}% Verified
                        </span>
                    </div>
                    <div className="trust-indicators">
                        <div className={`indicator ${integration_status?.initiation_recorded ? 'completed' : 'pending'}`}>
                            🚀 Initiation {integration_status?.initiation_recorded ? 'Recorded' : 'Pending'}
                        </div>
                        <div className={`indicator ${integration_status?.completion_recorded ? 'completed' : 'pending'}`}>
                            ✅ Completion {integration_status?.completion_recorded ? 'Recorded' : 'Pending'}
                        </div>
                        <div className={`indicator ${integration_status?.contractor_verification_recorded ? 'completed' : 'pending'}`}>
                            🔨 Contractor {integration_status?.contractor_verification_recorded ? 'Verified' : 'Pending'}
                        </div>
                        <div className={`indicator ${integration_status?.admin_verification_recorded ? 'completed' : 'pending'}`}>
                            👨‍💼 Admin {integration_status?.admin_verification_recorded ? 'Verified' : 'Pending'}
                        </div>
                    </div>
                </div>

                {/* Blockchain Records */}
                <div className="blockchain-records">
                    <h4>Blockchain Records</h4>
                    {blockchain_audit_trail?.audit_records?.length > 0 ? (
                        <div className="records-list">
                            {blockchain_audit_trail.audit_records.map((record, index) => (
                                <div key={index} className="record-item">
                                    <div className="record-icon">
                                        {getTransactionTypeIcon(record.transaction_type)}
                                    </div>
                                    <div className="record-details">
                                        <div className="record-title">
                                            {getTransactionTypeLabel(record.transaction_type)}
                                        </div>
                                        <div className="record-meta">
                                            <span className="timestamp">
                                                {formatTimestamp(record.blockchain_record_created)}
                                            </span>
                                            {record.blockchain_tx_hash && (
                                                <span className="tx-hash">
                                                    Tx: {record.blockchain_tx_hash.substring(0, 10)}...
                                                </span>
                                            )}
                                            <span className={`status ${record.blockchain_status}`}>
                                                {record.blockchain_status}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="no-records">
                            <p>No blockchain records found for this payment.</p>
                            <p>Records may still be processing or blockchain integration may be disabled.</p>
                        </div>
                    )}
                </div>

                {/* Sync Status */}
                {integration_status && (
                    <div className="sync-status">
                        <h4>Synchronization Status</h4>
                        <div className="sync-details">
                            <div className="sync-item">
                                <span className="label">Last Sync:</span>
                                <span className="value">
                                    {formatTimestamp(integration_status.last_blockchain_sync)}
                                </span>
                            </div>
                            <div className="sync-item">
                                <span className="label">Sync Attempts:</span>
                                <span className="value">{integration_status.sync_attempts || 0}</span>
                            </div>
                            {integration_status.last_error && (
                                <div className="sync-error">
                                    <span className="label">Last Error:</span>
                                    <span className="error-text">{integration_status.last_error}</span>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* Footer */}
                <div className="audit-footer">
                    <p className="disclaimer">
                        🔒 This audit trail provides cryptographic proof of payment events 
                        recorded on the Ethereum blockchain. All sensitive data remains 
                        secure and is never stored on-chain.
                    </p>
                    <button className="refresh-btn" onClick={fetchAuditTrail}>
                        🔄 Refresh
                    </button>
                </div>
            </div>
        </div>
    );
};

export default BlockchainAuditTrail;