import React, { useState, useEffect } from 'react';
import jwtAuth from '../utils/jwtAuth';
import './Login.css';

const JWTLogin = () => {
    const [formData, setFormData] = useState({
        email: '',
        password: ''
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    useEffect(() => {
        // Check if user is already authenticated
        if (jwtAuth.isAuthenticated()) {
            const user = jwtAuth.getUser();
            redirectToDashboard(user.role);
        }
    }, []);

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
        setError('');
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const result = await jwtAuth.login(formData.email, formData.password);
            
            if (result.success) {
                setSuccess('Login successful! Redirecting...');
                
                // Redirect based on user role
                setTimeout(() => {
                    redirectToDashboard(result.user.role);
                }, 1000);
            }
        } catch (error) {
            setError(error.message || 'Login failed. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    const redirectToDashboard = (role) => {
        switch (role) {
            case 'homeowner':
                window.location.href = '/homeowner-dashboard';
                break;
            case 'contractor':
                window.location.href = '/contractor-dashboard';
                break;
            case 'architect':
                window.location.href = '/architect-dashboard';
                break;
            case 'site_inspector':
                window.location.href = '/site-inspector-dashboard';
                break;
            case 'admin':
                window.location.href = '/admin-dashboard';
                break;
            default:
                window.location.href = '/dashboard';
        }
    };

    const handleGoogleLogin = () => {
        // Google OAuth integration would go here
        setError('Google login not yet implemented with JWT');
    };

    return (
        <div className="login-container">
            <div className="login-card">
                <div className="login-header">
                    <h2>BuildHub Login</h2>
                    <p>Secure JWT Authentication</p>
                </div>

                <form onSubmit={handleSubmit} className="login-form">
                    <div className="form-group">
                        <label htmlFor="email">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value={formData.email}
                            onChange={handleInputChange}
                            required
                            disabled={loading}
                            placeholder="Enter your email"
                        />
                    </div>

                    <div className="form-group">
                        <label htmlFor="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            value={formData.password}
                            onChange={handleInputChange}
                            required
                            disabled={loading}
                            placeholder="Enter your password"
                        />
                    </div>

                    {error && (
                        <div className="error-message">
                            <i className="fas fa-exclamation-circle"></i>
                            {error}
                        </div>
                    )}

                    {success && (
                        <div className="success-message">
                            <i className="fas fa-check-circle"></i>
                            {success}
                        </div>
                    )}

                    <button 
                        type="submit" 
                        className="login-button"
                        disabled={loading}
                    >
                        {loading ? (
                            <>
                                <i className="fas fa-spinner fa-spin"></i>
                                Signing In...
                            </>
                        ) : (
                            <>
                                <i className="fas fa-sign-in-alt"></i>
                                Sign In
                            </>
                        )}
                    </button>
                </form>

                <div className="login-divider">
                    <span>or</span>
                </div>

                <button 
                    type="button" 
                    className="google-login-button"
                    onClick={handleGoogleLogin}
                    disabled={loading}
                >
                    <i className="fab fa-google"></i>
                    Continue with Google
                </button>

                <div className="login-footer">
                    <p>
                        Don't have an account? 
                        <a href="/register"> Sign up here</a>
                    </p>
                    <p>
                        <a href="/forgot-password">Forgot your password?</a>
                    </p>
                </div>

                <div className="security-info">
                    <div className="security-badge">
                        <i className="fas fa-shield-alt"></i>
                        <span>Secured with JWT</span>
                    </div>
                    <small>Your session is protected with industry-standard JWT tokens</small>
                </div>
            </div>
        </div>
    );
};

export default JWTLogin;