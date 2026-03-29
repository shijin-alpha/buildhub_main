# JWT Token Authorization Implementation Guide

## Overview

This guide covers the complete implementation of JWT (JSON Web Token) authorization for the BuildHub project. The implementation provides secure, stateless authentication with role-based access control, token refresh mechanisms, and comprehensive audit logging.

## 🚀 Features Implemented

### Backend Features
- **JWT Token Management**: Access and refresh tokens with automatic expiration
- **Role-Based Access Control**: Homeowner, Contractor, Architect, Site Inspector, Admin roles
- **Admin Scope Control**: FULL and INSPECTOR admin scopes
- **Token Blacklisting**: Secure logout and token revocation
- **Rate Limiting**: Prevent brute force attacks and API abuse
- **Audit Logging**: Complete authentication and API access logging
- **Automatic Cleanup**: Expired tokens and old logs cleanup
- **Project-Level Authorization**: Resource-specific access control

### Frontend Features
- **JWT Authentication Utility**: Complete token management
- **Protected Routes**: Role-based route protection
- **Automatic Token Refresh**: Seamless token renewal
- **User Profile Management**: Session management and logout
- **Error Handling**: Graceful authentication failure handling

## 📁 File Structure

```
backend/
├── utils/
│   ├── JWTManager.php              # Core JWT token management
│   └── JWTEndpointUpdater.php      # Utility to update existing endpoints
├── middleware/
│   └── JWTAuthMiddleware.php       # Authentication middleware
├── database/
│   ├── jwt_tables.sql              # JWT-related database tables
│   ├── apply_jwt_schema.php        # Schema application script
│   └── check_jwt_tables.php        # Table verification script
└── api/
    ├── auth/
    │   ├── jwt_login.php           # JWT login endpoint
    │   ├── jwt_refresh.php         # Token refresh endpoint
    │   ├── jwt_logout.php          # Logout endpoint
    │   └── jwt_verify.php          # Token verification endpoint
    ├── homeowner/
    │   └── jwt_get_my_projects.php # Example protected endpoint
    ├── contractor/
    │   └── jwt_get_my_projects.php # Example protected endpoint
    └── admin/
        └── jwt_get_all_users.php   # Example admin endpoint

frontend/
├── src/
│   ├── utils/
│   │   └── jwtAuth.js              # JWT authentication utility
│   └── components/
│       ├── JWTLogin.jsx            # JWT login component
│       ├── JWTProtectedRoute.jsx   # Route protection component
│       └── JWTUserProfile.jsx      # User profile component
```

## 🛠 Installation & Setup

### 1. Database Setup

```bash
# Apply JWT schema to database
php backend/database/apply_jwt_schema.php

# Verify tables were created
php backend/database/check_jwt_tables.php
```

### 2. Install Dependencies

```bash
# Install Firebase JWT library
composer require firebase/php-jwt
```

### 3. Environment Configuration

Create or update your environment configuration:

```php
// In your config file or .env
$_ENV['JWT_SECRET'] = 'your-super-secure-jwt-secret-key-here';
$_ENV['JWT_REFRESH_SECRET'] = 'your-super-secure-refresh-secret-key-here';
```

## 🔧 Database Schema

The implementation adds the following tables:

### jwt_tokens
Tracks all issued JWT tokens for monitoring and blacklisting.

```sql
CREATE TABLE jwt_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    jti VARCHAR(255) NOT NULL UNIQUE,
    token_type ENUM('access', 'refresh') NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### jwt_blacklist
Stores revoked tokens for secure logout functionality.

```sql
CREATE TABLE jwt_blacklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jti VARCHAR(255) NOT NULL UNIQUE,
    blacklisted_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### api_rate_limits
Tracks API requests for rate limiting.

```sql
CREATE TABLE api_rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### auth_audit_log
Comprehensive authentication and API access logging.

```sql
CREATE TABLE auth_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    endpoint VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    success BOOLEAN NOT NULL DEFAULT TRUE,
    error_message TEXT NULL,
    additional_data JSON NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

## 🔐 Authentication Flow

### 1. Login Process
```
User Credentials → JWT Login Endpoint → Token Generation → Client Storage
```

### 2. API Request Process
```
Client Request → JWT Middleware → Token Validation → Endpoint Access
```

### 3. Token Refresh Process
```
Expired Token → Refresh Endpoint → New Token Generation → Updated Client Storage
```

## 🎯 Usage Examples

### Backend: Protecting an Endpoint

```php
<?php
require_once __DIR__ . '/../../middleware/JWTAuthMiddleware.php';

try {
    $auth = new JWTAuthMiddleware();
    
    // Require homeowner role
    $user = $auth->requireHomeowner();
    
    // Your endpoint logic here
    echo json_encode([
        'success' => true,
        'user_id' => $user['id'],
        'data' => 'Protected data'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
```

### Frontend: Making Authenticated Requests

```javascript
import jwtAuth from '../utils/jwtAuth';

// Login
const loginResult = await jwtAuth.login('user@example.com', 'password');

// Make authenticated API request
const response = await jwtAuth.apiRequest('/backend/api/homeowner/get_my_projects.php');
const data = await response.json();

// Check user role
if (jwtAuth.hasRole('homeowner')) {
    // Show homeowner-specific content
}

// Logout
await jwtAuth.logout();
```

### Frontend: Protected Routes

```jsx
import { HomeownerRoute, AdminRoute } from './components/JWTProtectedRoute';

function App() {
    return (
        <Router>
            <Routes>
                <Route path="/login" element={<JWTLogin />} />
                
                <Route path="/homeowner-dashboard" element={
                    <HomeownerRoute>
                        <HomeownerDashboard />
                    </HomeownerRoute>
                } />
                
                <Route path="/admin-panel" element={
                    <AdminRoute adminScope="FULL">
                        <AdminPanel />
                    </AdminRoute>
                } />
            </Routes>
        </Router>
    );
}
```

## 🔒 Security Features

### 1. Token Security
- **Secure Secrets**: Environment-based JWT secrets
- **Token Expiration**: 1-hour access tokens, 7-day refresh tokens
- **Token Blacklisting**: Immediate token revocation on logout
- **Unique Token IDs**: Each token has a unique identifier for tracking

### 2. Rate Limiting
- **Login Protection**: 5 attempts per 15 minutes per email
- **API Protection**: 100 requests per 15 minutes per user
- **Automatic Cleanup**: Old rate limit entries are cleaned up

### 3. Audit Logging
- **Authentication Events**: Login, logout, token refresh
- **API Access**: All protected endpoint access
- **Failure Tracking**: Failed authentication attempts
- **IP and User Agent**: Complete request context

### 4. Role-Based Access Control
- **Granular Permissions**: Role and scope-based access
- **Resource Ownership**: Project-level access control
- **Admin Scopes**: FULL and INSPECTOR admin levels

## 🧪 Testing

### Test JWT Login
```bash
curl -X POST http://localhost/backend/api/auth/jwt_login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'
```

### Test Protected Endpoint
```bash
curl -X GET http://localhost/backend/api/homeowner/jwt_get_my_projects.php \
  -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE"
```

### Test Token Refresh
```bash
curl -X POST http://localhost/backend/api/auth/jwt_refresh.php \
  -H "Content-Type: application/json" \
  -d '{"refresh_token":"YOUR_REFRESH_TOKEN_HERE"}'
```

## 🔄 Migration from Session-Based Auth

### 1. Gradual Migration
- Keep existing session-based endpoints working
- Add JWT versions with `jwt_` prefix
- Update frontend to use JWT endpoints
- Deprecate session endpoints after migration

### 2. Backward Compatibility
The JWT middleware sets session variables for backward compatibility:
```php
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['user_email'] = $user['email'];
```

### 3. Update Existing Endpoints
Use the provided `JWTEndpointUpdater.php` utility:
```bash
php backend/utils/JWTEndpointUpdater.php update
```

## 📊 Monitoring & Maintenance

### 1. Token Cleanup
Expired tokens are automatically cleaned up, but you can manually run:
```sql
DELETE FROM jwt_tokens WHERE expires_at < NOW();
DELETE FROM jwt_blacklist WHERE blacklisted_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### 2. Audit Log Analysis
Monitor authentication patterns:
```sql
SELECT action, COUNT(*) as count, DATE(created_at) as date
FROM auth_audit_log 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY action, DATE(created_at)
ORDER BY date DESC, count DESC;
```

### 3. Rate Limit Monitoring
Check for potential attacks:
```sql
SELECT user_id, COUNT(*) as requests, DATE(created_at) as date
FROM api_rate_limits 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY user_id, DATE(created_at)
HAVING requests > 1000
ORDER BY requests DESC;
```

## 🚨 Troubleshooting

### Common Issues

1. **"Token has expired"**
   - Check system time synchronization
   - Verify token expiration settings
   - Ensure automatic refresh is working

2. **"Invalid token signature"**
   - Verify JWT secret keys match
   - Check for token corruption in storage
   - Ensure proper base64 encoding

3. **"User not found or inactive"**
   - Verify user exists in database
   - Check user status is 'approved'
   - Ensure user is not soft-deleted

4. **Rate limit exceeded**
   - Check rate limit settings
   - Clear old rate limit entries
   - Verify user behavior patterns

### Debug Mode
Enable debug logging by adding to your JWT endpoints:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 🔮 Future Enhancements

### Planned Features
1. **Two-Factor Authentication (2FA)**
2. **OAuth2 Integration**
3. **API Key Management**
4. **Advanced Rate Limiting**
5. **Token Rotation Policies**
6. **Biometric Authentication**
7. **Single Sign-On (SSO)**

### Performance Optimizations
1. **Redis Token Storage**
2. **JWT Caching**
3. **Database Indexing**
4. **Connection Pooling**

## 📞 Support

For issues or questions regarding the JWT implementation:

1. Check the troubleshooting section above
2. Review the audit logs for authentication failures
3. Verify database schema and table creation
4. Test with the provided example endpoints

## 🏁 Conclusion

This JWT implementation provides enterprise-grade security for the BuildHub platform with:

- ✅ Secure token-based authentication
- ✅ Role-based access control
- ✅ Comprehensive audit logging
- ✅ Rate limiting and abuse prevention
- ✅ Automatic token management
- ✅ Scalable architecture

The system is production-ready and provides a solid foundation for secure API access across all user roles and administrative scopes.