-- Add international payment support to existing tables

-- Add columns to technical_details_payments table
ALTER TABLE technical_details_payments 
ADD COLUMN currency VARCHAR(3) DEFAULT 'INR' AFTER amount,
ADD COLUMN country_code VARCHAR(2) DEFAULT 'IN' AFTER currency,
ADD COLUMN original_amount DECIMAL(15,2) NULL AFTER country_code,
ADD COLUMN original_currency VARCHAR(3) NULL AFTER original_amount,
ADD COLUMN exchange_rate DECIMAL(10,6) NULL AFTER original_currency,
ADD COLUMN payment_method VARCHAR(50) NULL AFTER exchange_rate,
ADD COLUMN international_payment BOOLEAN DEFAULT FALSE AFTER payment_method;

-- Add columns to stage_payment_transactions table
ALTER TABLE stage_payment_transactions 
ADD COLUMN currency VARCHAR(3) DEFAULT 'INR' AFTER amount,
ADD COLUMN country_code VARCHAR(2) DEFAULT 'IN' AFTER currency,
ADD COLUMN original_amount DECIMAL(15,2) NULL AFTER country_code,
ADD COLUMN original_currency VARCHAR(3) NULL AFTER original_amount,
ADD COLUMN exchange_rate DECIMAL(10,6) NULL AFTER original_currency,
ADD COLUMN payment_method VARCHAR(50) NULL AFTER exchange_rate,
ADD COLUMN international_payment BOOLEAN DEFAULT FALSE AFTER payment_method;

-- Create international payment settings table
CREATE TABLE IF NOT EXISTS international_payment_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_code VARCHAR(2) NOT NULL,
    country_name VARCHAR(100) NOT NULL,
    currency_code VARCHAR(3) NOT NULL,
    is_supported BOOLEAN DEFAULT TRUE,
    supported_methods JSON,
    min_amount DECIMAL(15,2) DEFAULT 0.00,
    max_amount DECIMAL(15,2) DEFAULT 1000000.00,
    processing_fee_percentage DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_country (country_code),
    INDEX idx_currency (currency_code),
    INDEX idx_supported (is_supported)
);

-- Insert default supported countries
INSERT INTO international_payment_settings 
(country_code, country_name, currency_code, supported_methods, max_amount) VALUES
('IN', 'India', 'INR', '["card", "netbanking", "wallet", "upi"]', 1000000.00),
('US', 'United States', 'USD', '["card"]', 12000.00),
('GB', 'United Kingdom', 'GBP', '["card"]', 9500.00),
('CA', 'Canada', 'CAD', '["card"]', 16000.00),
('AU', 'Australia', 'AUD', '["card"]', 18000.00),
('SG', 'Singapore', 'SGD', '["card"]', 16000.00),
('AE', 'United Arab Emirates', 'AED', '["card"]', 44000.00),
('MY', 'Malaysia', 'MYR', '["card"]', 50000.00)
ON DUPLICATE KEY UPDATE
country_name = VALUES(country_name),
currency_code = VALUES(currency_code),
supported_methods = VALUES(supported_methods),
max_amount = VALUES(max_amount);

-- Create currency exchange rates table (for reference)
CREATE TABLE IF NOT EXISTS currency_exchange_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_currency VARCHAR(3) NOT NULL,
    to_currency VARCHAR(3) NOT NULL,
    exchange_rate DECIMAL(10,6) NOT NULL,
    rate_date DATE NOT NULL,
    source VARCHAR(50) DEFAULT 'manual',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_currency_pair_date (from_currency, to_currency, rate_date),
    INDEX idx_from_currency (from_currency),
    INDEX idx_to_currency (to_currency),
    INDEX idx_rate_date (rate_date)
);

-- Insert basic exchange rates (update these regularly in production)
INSERT INTO currency_exchange_rates (from_currency, to_currency, exchange_rate, rate_date) VALUES
('USD', 'INR', 83.50, CURDATE()),
('EUR', 'INR', 91.20, CURDATE()),
('GBP', 'INR', 105.80, CURDATE()),
('AUD', 'INR', 55.40, CURDATE()),
('CAD', 'INR', 61.20, CURDATE()),
('SGD', 'INR', 62.10, CURDATE()),
('AED', 'INR', 22.75, CURDATE()),
('MYR', 'INR', 18.90, CURDATE()),
('INR', 'USD', 0.012, CURDATE()),
('INR', 'EUR', 0.011, CURDATE()),
('INR', 'GBP', 0.0095, CURDATE()),
('INR', 'AUD', 0.018, CURDATE()),
('INR', 'CAD', 0.016, CURDATE())
ON DUPLICATE KEY UPDATE
exchange_rate = VALUES(exchange_rate),
rate_date = VALUES(rate_date);

-- Create payment failure logs table for better debugging
CREATE TABLE IF NOT EXISTS payment_failure_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_type ENUM('technical_details', 'stage_payment') NOT NULL,
    payment_id INT NULL,
    user_id INT NOT NULL,
    razorpay_order_id VARCHAR(255) NULL,
    error_code VARCHAR(50) NULL,
    error_description TEXT NULL,
    country_code VARCHAR(2) NULL,
    currency VARCHAR(3) NULL,
    amount DECIMAL(15,2) NULL,
    user_agent TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_payment_type (payment_type),
    INDEX idx_user_id (user_id),
    INDEX idx_error_code (error_code),
    INDEX idx_country_code (country_code),
    INDEX idx_created_at (created_at)
);

-- Add indexes for better performance
ALTER TABLE technical_details_payments 
ADD INDEX idx_currency (currency),
ADD INDEX idx_country_code (country_code),
ADD INDEX idx_international_payment (international_payment);

ALTER TABLE stage_payment_transactions 
ADD INDEX idx_currency (currency),
ADD INDEX idx_country_code (country_code),
ADD INDEX idx_international_payment (international_payment);