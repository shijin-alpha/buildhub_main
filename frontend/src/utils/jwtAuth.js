/**
 * JWT Authentication Utility
 * Handles JWT token management and API authentication
 */

class JWTAuth {
    constructor() {
        this.baseURL = '/backend/api';
        this.tokenKey = 'buildhub_access_token';
        this.refreshTokenKey = 'buildhub_refresh_token';
        this.userKey = 'buildhub_user';
        
        // Auto-refresh token when it's about to expire
        this.setupTokenRefresh();
    }
    
    /**
     * Login with email and password
     */
    async login(email, password) {
        try {
            const response = await fetch(`${this.baseURL}/auth/jwt_login.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email, password })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'Login failed');
            }
            
            if (data.success) {
                // Store tokens and user data
                this.setTokens(data.tokens);
                this.setUser(data.user);
                
                // Setup auto-refresh
                this.setupTokenRefresh();
                
                return {
                    success: true,
                    user: data.user,
                    tokens: data.tokens
                };
            } else {
                throw new Error(data.error || 'Login failed');
            }
        } catch (error) {
            console.error('Login error:', error);
            throw error;
        }
    }
    
    /**
     * Logout (blacklist current token)
     */
    async logout(logoutAll = false) {
        try {
            const token = this.getAccessToken();
            
            if (token) {
                await fetch(`${this.baseURL}/auth/jwt_logout.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({ logout_all: logoutAll })
                });
            }
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            // Clear local storage regardless of API call success
            this.clearTokens();
            this.clearUser();
        }
    }
    
    /**
     * Refresh access token
     */
    async refreshToken() {
        try {
            const refreshToken = this.getRefreshToken();
            
            if (!refreshToken) {
                throw new Error('No refresh token available');
            }
            
            const response = await fetch(`${this.baseURL}/auth/jwt_refresh.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ refresh_token: refreshToken })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'Token refresh failed');
            }
            
            if (data.success) {
                this.setTokens(data.tokens);
                return data.tokens;
            } else {
                throw new Error(data.error || 'Token refresh failed');
            }
        } catch (error) {
            console.error('Token refresh error:', error);
            // If refresh fails, logout user
            this.clearTokens();
            this.clearUser();
            throw error;
        }
    }
    
    /**
     * Verify current token
     */
    async verifyToken() {
        try {
            const token = this.getAccessToken();
            
            if (!token) {
                return false;
            }
            
            const response = await fetch(`${this.baseURL}/auth/jwt_verify.php`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                // Update user data if it has changed
                this.setUser(data.user);
                return true;
            } else {
                return false;
            }
        } catch (error) {
            console.error('Token verification error:', error);
            return false;
        }
    }
    
    /**
     * Make authenticated API request
     */
    async apiRequest(url, options = {}) {
        const token = this.getAccessToken();
        
        if (!token) {
            throw new Error('No access token available');
        }
        
        const headers = {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
            ...options.headers
        };
        
        try {
            const response = await fetch(url, {
                ...options,
                headers
            });
            
            // If token expired, try to refresh and retry
            if (response.status === 401) {
                try {
                    await this.refreshToken();
                    
                    // Retry with new token
                    const newToken = this.getAccessToken();
                    headers.Authorization = `Bearer ${newToken}`;
                    
                    return await fetch(url, {
                        ...options,
                        headers
                    });
                } catch (refreshError) {
                    // Refresh failed, redirect to login
                    this.handleAuthFailure();
                    throw refreshError;
                }
            }
            
            return response;
        } catch (error) {
            console.error('API request error:', error);
            throw error;
        }
    }
    
    /**
     * Get access token
     */
    getAccessToken() {
        return localStorage.getItem(this.tokenKey);
    }
    
    /**
     * Get refresh token
     */
    getRefreshToken() {
        return localStorage.getItem(this.refreshTokenKey);
    }
    
    /**
     * Get current user
     */
    getUser() {
        const userData = localStorage.getItem(this.userKey);
        return userData ? JSON.parse(userData) : null;
    }
    
    /**
     * Check if user is authenticated
     */
    isAuthenticated() {
        return !!this.getAccessToken() && !!this.getUser();
    }
    
    /**
     * Check if user has specific role
     */
    hasRole(role) {
        const user = this.getUser();
        return user && user.role === role;
    }
    
    /**
     * Check if user has admin access
     */
    isAdmin(scope = null) {
        const user = this.getUser();
        if (!user || user.role !== 'admin') {
            return false;
        }
        
        if (scope) {
            return user.admin_scope === scope;
        }
        
        return true;
    }
    
    /**
     * Set tokens in localStorage
     */
    setTokens(tokens) {
        localStorage.setItem(this.tokenKey, tokens.access_token);
        localStorage.setItem(this.refreshTokenKey, tokens.refresh_token);
    }
    
    /**
     * Set user data in localStorage
     */
    setUser(user) {
        localStorage.setItem(this.userKey, JSON.stringify(user));
    }
    
    /**
     * Clear tokens from localStorage
     */
    clearTokens() {
        localStorage.removeItem(this.tokenKey);
        localStorage.removeItem(this.refreshTokenKey);
    }
    
    /**
     * Clear user data from localStorage
     */
    clearUser() {
        localStorage.removeItem(this.userKey);
    }
    
    /**
     * Setup automatic token refresh
     */
    setupTokenRefresh() {
        const token = this.getAccessToken();
        
        if (!token) {
            return;
        }
        
        try {
            // Decode JWT to get expiration time
            const payload = JSON.parse(atob(token.split('.')[1]));
            const expirationTime = payload.exp * 1000; // Convert to milliseconds
            const currentTime = Date.now();
            const timeUntilExpiry = expirationTime - currentTime;
            
            // Refresh token 5 minutes before expiry
            const refreshTime = Math.max(0, timeUntilExpiry - (5 * 60 * 1000));
            
            if (refreshTime > 0) {
                setTimeout(() => {
                    this.refreshToken().catch(error => {
                        console.error('Auto token refresh failed:', error);
                        this.handleAuthFailure();
                    });
                }, refreshTime);
            } else {
                // Token already expired or about to expire, refresh immediately
                this.refreshToken().catch(error => {
                    console.error('Immediate token refresh failed:', error);
                    this.handleAuthFailure();
                });
            }
        } catch (error) {
            console.error('Error setting up token refresh:', error);
        }
    }
    
    /**
     * Handle authentication failure
     */
    handleAuthFailure() {
        this.clearTokens();
        this.clearUser();
        
        // Redirect to login page
        if (window.location.pathname !== '/login') {
            window.location.href = '/login';
        }
    }
    
    /**
     * Get authorization header
     */
    getAuthHeader() {
        const token = this.getAccessToken();
        return token ? { Authorization: `Bearer ${token}` } : {};
    }
}

// Create singleton instance
const jwtAuth = new JWTAuth();

export default jwtAuth;