import React, { useState, useEffect } from 'react';
import jwtAuth from '../utils/jwtAuth';

/**
 * JWT User Profile Component
 * Shows current user info and provides logout functionality
 */
const JWTUserProfile = ({ showFullProfile = false }) => {
    const [user, setUser] = useState(null);
    const [activeSessions, setActiveSessions] = useState([]);
    const [loading, setLoading] = useState(false);
    const [showSessionsModal, setShowSessionsModal] = useState(false);

    useEffect(() => {
        const currentUser = jwtAuth.getUser();
        setUser(currentUser);
    }, []);

    const handleLogout = async (logoutAll = false) => {
        setLoading(true);
        try {
            await jwtAuth.logout(logoutAll);
            // Redirect will be handled by jwtAuth.handleAuthFailure()
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            setLoading(false);
        }
    };

    const loadActiveSessions = async () => {
        try {
            const response = await jwtAuth.apiRequest('/backend/api/auth/jwt_sessions.php');
            const data = await response.json();
            
            if (data.success) {
                setActiveSessions(data.sessions);
            }
        } catch (error) {
            console.error('Failed to load active sessions:', error);
        }
    };

    const handleShowSessions = () => {
        setShowSessionsModal(true);
        loadActiveSessions();
    };

    if (!user) {
        return null;
    }

    const getRoleIcon = (role) => {
        const icons = {
            homeowner: 'fas fa-home',
            contractor: 'fas fa-hard-hat',
            architect: 'fas fa-drafting-compass',
            site_inspector: 'fas fa-clipboard-check',
            admin: 'fas fa-user-shield'
        };
        return icons[role] || 'fas fa-user';
    };

    const getRoleBadgeClass = (role) => {
        const classes = {
            homeowner: 'role-badge-homeowner',
            contractor: 'role-badge-contractor',
            architect: 'role-badge-architect',
            site_inspector: 'role-badge-inspector',
            admin: 'role-badge-admin'
        };
        return classes[role] || 'role-badge-default';
    };

    if (!showFullProfile) {
        // Compact profile for header/navbar
        return (
            <div className="jwt-user-profile-compact">
                <div className="user-info">
                    <div className="user-avatar">
                        <i className={getRoleIcon(user.role)}></i>
                    </div>
                    <div className="user-details">
                        <span className="user-name">
                            {user.first_name} {user.last_name}
                        </span>
                        <span className={`role-badge ${getRoleBadgeClass(user.role)}`}>
                            {user.role.replace('_', ' ')}
                            {user.admin_scope && ` (${user.admin_scope})`}
                        </span>
                    </div>
                </div>
                
                <div className="user-actions">
                    <button 
                        className="logout-btn"
                        onClick={() => handleLogout(false)}
                        disabled={loading}
                        title="Logout"
                    >
                        {loading ? (
                            <i className="fas fa-spinner fa-spin"></i>
                        ) : (
                            <i className="fas fa-sign-out-alt"></i>
                        )}
                    </button>
                </div>
            </div>
        );
    }

    // Full profile view
    return (
        <div className="jwt-user-profile-full">
            <div className="profile-header">
                <div className="profile-avatar">
                    <i className={getRoleIcon(user.role)}></i>
                </div>
                <div className="profile-info">
                    <h3>{user.first_name} {user.last_name}</h3>
                    <p className="user-email">{user.email}</p>
                    <span className={`role-badge ${getRoleBadgeClass(user.role)}`}>
                        {user.role.replace('_', ' ')}
                        {user.admin_scope && ` (${user.admin_scope})`}
                    </span>
                </div>
            </div>

            <div className="profile-details">
                <div className="detail-item">
                    <label>User ID:</label>
                    <span>{user.id}</span>
                </div>
                <div className="detail-item">
                    <label>Account Status:</label>
                    <span className="status-verified">
                        {user.is_verified ? (
                            <>
                                <i className="fas fa-check-circle"></i>
                                Verified
                            </>
                        ) : (
                            <>
                                <i className="fas fa-exclamation-circle"></i>
                                Unverified
                            </>
                        )}
                    </span>
                </div>
            </div>

            <div className="profile-actions">
                <button 
                    className="btn-secondary"
                    onClick={handleShowSessions}
                >
                    <i className="fas fa-list"></i>
                    Active Sessions
                </button>
                
                <button 
                    className="btn-warning"
                    onClick={() => handleLogout(false)}
                    disabled={loading}
                >
                    {loading ? (
                        <i className="fas fa-spinner fa-spin"></i>
                    ) : (
                        <i className="fas fa-sign-out-alt"></i>
                    )}
                    Logout
                </button>
                
                <button 
                    className="btn-danger"
                    onClick={() => handleLogout(true)}
                    disabled={loading}
                >
                    {loading ? (
                        <i className="fas fa-spinner fa-spin"></i>
                    ) : (
                        <i className="fas fa-power-off"></i>
                    )}
                    Logout All Devices
                </button>
            </div>

            {/* Active Sessions Modal */}
            {showSessionsModal && (
                <div className="modal-overlay" onClick={() => setShowSessionsModal(false)}>
                    <div className="modal-content" onClick={e => e.stopPropagation()}>
                        <div className="modal-header">
                            <h4>Active Sessions</h4>
                            <button 
                                className="modal-close"
                                onClick={() => setShowSessionsModal(false)}
                            >
                                <i className="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div className="modal-body">
                            {activeSessions.length === 0 ? (
                                <p>No active sessions found.</p>
                            ) : (
                                <div className="sessions-list">
                                    {activeSessions.map((session, index) => (
                                        <div key={session.jti} className="session-item">
                                            <div className="session-info">
                                                <span className="session-type">
                                                    {session.token_type === 'access' ? (
                                                        <i className="fas fa-key"></i>
                                                    ) : (
                                                        <i className="fas fa-refresh"></i>
                                                    )}
                                                    {session.token_type} token
                                                </span>
                                                <span className="session-created">
                                                    Created: {new Date(session.created_at).toLocaleString()}
                                                </span>
                                                <span className="session-expires">
                                                    Expires: {new Date(session.expires_at).toLocaleString()}
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default JWTUserProfile;