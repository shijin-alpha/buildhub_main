import React, { useState, useEffect } from 'react';
import jwtAuth from '../utils/jwtAuth';

/**
 * JWT Protected Route Component
 * Handles authentication and role-based access control
 */
const JWTProtectedRoute = ({ 
    children, 
    requiredRole = null, 
    requiredRoles = [], 
    adminScope = null,
    fallbackComponent = null 
}) => {
    const [isAuthenticated, setIsAuthenticated] = useState(false);
    const [isAuthorized, setIsAuthorized] = useState(false);
    const [loading, setLoading] = useState(true);
    const [user, setUser] = useState(null);

    useEffect(() => {
        checkAuthentication();
    }, [requiredRole, requiredRoles, adminScope]);

    const checkAuthentication = async () => {
        try {
            // Check if user has valid token
            const authenticated = jwtAuth.isAuthenticated();
            
            if (!authenticated) {
                setIsAuthenticated(false);
                setIsAuthorized(false);
                setLoading(false);
                return;
            }

            // Verify token with server
            const tokenValid = await jwtAuth.verifyToken();
            
            if (!tokenValid) {
                setIsAuthenticated(false);
                setIsAuthorized(false);
                setLoading(false);
                return;
            }

            const currentUser = jwtAuth.getUser();
            setUser(currentUser);
            setIsAuthenticated(true);

            // Check authorization
            const authorized = checkAuthorization(currentUser);
            setIsAuthorized(authorized);

        } catch (error) {
            console.error('Authentication check failed:', error);
            setIsAuthenticated(false);
            setIsAuthorized(false);
        } finally {
            setLoading(false);
        }
    };

    const checkAuthorization = (user) => {
        if (!user) return false;

        // Check specific role
        if (requiredRole && user.role !== requiredRole) {
            return false;
        }

        // Check multiple roles
        if (requiredRoles.length > 0 && !requiredRoles.includes(user.role)) {
            return false;
        }

        // Check admin scope
        if (adminScope && user.role === 'admin' && user.admin_scope !== adminScope) {
            return false;
        }

        return true;
    };

    const handleLoginRedirect = () => {
        // Store current location for redirect after login
        sessionStorage.setItem('jwt_redirect_after_login', window.location.pathname);
        window.location.href = '/jwt-login';
    };

    if (loading) {
        return (
            <div className="jwt-loading-container">
                <div className="jwt-loading-spinner">
                    <i className="fas fa-spinner fa-spin"></i>
                    <p>Verifying authentication...</p>
                </div>
            </div>
        );
    }

    if (!isAuthenticated) {
        return (
            <div className="jwt-auth-required">
                <div className="auth-message">
                    <i className="fas fa-lock"></i>
                    <h3>Authentication Required</h3>
                    <p>You need to be logged in to access this page.</p>
                    <button 
                        className="login-redirect-btn"
                        onClick={handleLoginRedirect}
                    >
                        <i className="fas fa-sign-in-alt"></i>
                        Go to Login
                    </button>
                </div>
            </div>
        );
    }

    if (!isAuthorized) {
        if (fallbackComponent) {
            return fallbackComponent;
        }

        return (
            <div className="jwt-access-denied">
                <div className="access-denied-message">
                    <i className="fas fa-ban"></i>
                    <h3>Access Denied</h3>
                    <p>You don't have permission to access this page.</p>
                    <div className="user-info">
                        <p>Current role: <strong>{user?.role}</strong></p>
                        {requiredRole && (
                            <p>Required role: <strong>{requiredRole}</strong></p>
                        )}
                        {requiredRoles.length > 0 && (
                            <p>Required roles: <strong>{requiredRoles.join(', ')}</strong></p>
                        )}
                        {adminScope && (
                            <p>Required admin scope: <strong>{adminScope}</strong></p>
                        )}
                    </div>
                    <button 
                        className="dashboard-redirect-btn"
                        onClick={() => window.location.href = '/dashboard'}
                    >
                        <i className="fas fa-home"></i>
                        Go to Dashboard
                    </button>
                </div>
            </div>
        );
    }

    // User is authenticated and authorized
    return children;
};

/**
 * Specific role-based route components
 */
export const HomeownerRoute = ({ children, ...props }) => (
    <JWTProtectedRoute requiredRole="homeowner" {...props}>
        {children}
    </JWTProtectedRoute>
);

export const ContractorRoute = ({ children, ...props }) => (
    <JWTProtectedRoute requiredRole="contractor" {...props}>
        {children}
    </JWTProtectedRoute>
);

export const ArchitectRoute = ({ children, ...props }) => (
    <JWTProtectedRoute requiredRole="architect" {...props}>
        {children}
    </JWTProtectedRoute>
);

export const SiteInspectorRoute = ({ children, ...props }) => (
    <JWTProtectedRoute requiredRole="site_inspector" {...props}>
        {children}
    </JWTProtectedRoute>
);

export const AdminRoute = ({ children, adminScope = null, ...props }) => (
    <JWTProtectedRoute requiredRole="admin" adminScope={adminScope} {...props}>
        {children}
    </JWTProtectedRoute>
);

export const FullAdminRoute = ({ children, ...props }) => (
    <AdminRoute adminScope="FULL" {...props}>
        {children}
    </AdminRoute>
);

export const InspectorAdminRoute = ({ children, ...props }) => (
    <AdminRoute adminScope="INSPECTOR" {...props}>
        {children}
    </AdminRoute>
);

export const ContractorOrArchitectRoute = ({ children, ...props }) => (
    <JWTProtectedRoute requiredRoles={['contractor', 'architect']} {...props}>
        {children}
    </JWTProtectedRoute>
);

export default JWTProtectedRoute;