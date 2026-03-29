-- JWT Token Management Tables
-- Add these tables to support JWT token tracking, blacklisting, and rate limiting

-- Table for tracking issued JWT tokens
CREATE TABLE IF NOT EXISTS jwt_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    jti VARCHAR(255) NOT NULL UNIQUE,
    token_type ENUM('access', 'refresh') NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_jti (jti),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table for blacklisted tokens (logout/revoked tokens)
CREATE TABLE IF NOT EXISTS jwt_blacklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jti VARCHAR(255) NOT NULL UNIQUE,
    blacklisted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_jti (jti),
    INDEX idx_blacklisted_at (blacklisted_at)
);

-- Table for API rate limiting
CREATE TABLE IF NOT EXISTS api_rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table for audit logging
CREATE TABLE IF NOT EXISTS auth_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    endpoint VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    success BOOLEAN NOT NULL DEFAULT TRUE,
    error_message TEXT NULL,
    additional_data JSON NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    INDEX idx_success (success),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Add JWT-related columns to users table if they don't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL,
ADD COLUMN IF NOT EXISTS login_attempts INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS locked_until DATETIME NULL,
ADD COLUMN IF NOT EXISTS two_factor_enabled BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(255) NULL;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
CREATE INDEX IF NOT EXISTS idx_users_deleted_at ON users(deleted_at);

-- Note: Stored procedures and events should be created separately via MySQL client
-- For now, we'll skip the automated cleanup and handle it manually