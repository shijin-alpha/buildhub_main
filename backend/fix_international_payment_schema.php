<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Applying international payment schema updates step by step...\n";
    
    // Step 1: Check if technical_details_payments table exists and add columns
    echo "\n1. Updating technical_details_payments table...\n";
    
    try {
        // Check if table exists
        $result = $db->query("SHOW TABLES LIKE 'technical_details_payments'");
        if ($result->rowCount() > 0) {
            // Add columns one by one
            $columns = [
                "ADD COLUMN currency VARCHAR(3) DEFAULT 'INR' AFTER amount",
                "ADD COLUMN country_code VARCHAR(2) DEFAULT 'IN' AFTER currency", 
                "ADD COLUMN original_amount DECIMAL(15,2) NULL AFTER country_code",
                "ADD COLUMN original_currency VARCHAR(3) NULL AFTER original_amount",
                "ADD COLUMN exchange_rate DECIMAL(10,6) NULL AFTER original_currency",
                "ADD COLUMN payment_method VARCHAR(50) NULL AFTER exchange_rate",
                "ADD COLUMN international_payment BOOLEAN DEFAULT FALSE AFTER payment_method"
            ];
            
            foreach ($columns as $column) {
                try {
                    $db->exec("ALTER TABLE technical_details_payments $column");
                    echo "✓ Added column to technical_details_payments\n";
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "- Column already exists in technical_details_payments\n";
                    } else {
                        echo "✗ Error adding column: " . $e->getMessage() . "\n";
                    }
                }
            }
        } else {
            echo "- technical_details_payments table does not exist, skipping\n";
        }
    } catch (Exception $e) {
        echo "✗ Error with technical_details_payments: " . $e->getMessage() . "\n";
    }
    
    // Step 2: Check if stage_payment_transactions table exists and add columns
    echo "\n2. Updating stage_payment_transactions table...\n";
    
    try {
        $result = $db->query("SHOW TABLES LIKE 'stage_payment_transactions'");
        if ($result->rowCount() > 0) {
            $columns = [
                "ADD COLUMN currency VARCHAR(3) DEFAULT 'INR' AFTER amount",
                "ADD COLUMN country_code VARCHAR(2) DEFAULT 'IN' AFTER currency",
                "ADD COLUMN original_amount DECIMAL(15,2) NULL AFTER country_code", 
                "ADD COLUMN original_currency VARCHAR(3) NULL AFTER original_amount",
                "ADD COLUMN exchange_rate DECIMAL(10,6) NULL AFTER original_currency",
                "ADD COLUMN payment_method VARCHAR(50) NULL AFTER exchange_rate",
                "ADD COLUMN international_payment BOOLEAN DEFAULT FALSE AFTER payment_method"
            ];
            
            foreach ($columns as $column) {
                try {
                    $db->exec("ALTER TABLE stage_payment_transactions $column");
                    echo "✓ Added column to stage_payment_transactions\n";
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "- Column already exists in stage_payment_transactions\n";
                    } else {
                        echo "✗ Error adding column: " . $e->getMessage() . "\n";
                    }
                }
            }
        } else {
            echo "- stage_payment_transactions table does not exist, skipping\n";
        }
    } catch (Exception $e) {
        echo "✗ Error with stage_payment_transactions: " . $e->getMessage() . "\n";
    }
    
    // Step 3: Create international_payment_settings table
    echo "\n3. Creating international_payment_settings table...\n";
    
    try {
        $db->exec("
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
            )
        ");
        echo "✓ Created international_payment_settings table\n";
    } catch (PDOException $e) {
        echo "✗ Error creating international_payment_settings: " . $e->getMessage() . "\n";
    }
    
    // Step 4: Insert default countries
    echo "\n4. Inserting default supported countries...\n";
    
    try {
        $countries = [
            ['IN', 'India', 'INR', '["card", "netbanking", "wallet", "upi"]', 1000000.00],
            ['US', 'United States', 'USD', '["card"]', 12000.00],
            ['GB', 'United Kingdom', 'GBP', '["card"]', 9500.00],
            ['CA', 'Canada', 'CAD', '["card"]', 16000.00],
            ['AU', 'Australia', 'AUD', '["card"]', 18000.00],
            ['SG', 'Singapore', 'SGD', '["card"]', 16000.00],
            ['AE', 'United Arab Emirates', 'AED', '["card"]', 44000.00],
            ['MY', 'Malaysia', 'MYR', '["card"]', 50000.00]
        ];
        
        $stmt = $db->prepare("
            INSERT INTO international_payment_settings 
            (country_code, country_name, currency_code, supported_methods, max_amount) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            country_name = VALUES(country_name),
            currency_code = VALUES(currency_code),
            supported_methods = VALUES(supported_methods),
            max_amount = VALUES(max_amount)
        ");
        
        foreach ($countries as $country) {
            $stmt->execute($country);
        }
        
        echo "✓ Inserted " . count($countries) . " supported countries\n";
    } catch (PDOException $e) {
        echo "✗ Error inserting countries: " . $e->getMessage() . "\n";
    }
    
    // Step 5: Create currency exchange rates table
    echo "\n5. Creating currency_exchange_rates table...\n";
    
    try {
        $db->exec("
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
            )
        ");
        echo "✓ Created currency_exchange_rates table\n";
    } catch (PDOException $e) {
        echo "✗ Error creating currency_exchange_rates: " . $e->getMessage() . "\n";
    }
    
    // Step 6: Insert exchange rates
    echo "\n6. Inserting exchange rates...\n";
    
    try {
        $rates = [
            ['USD', 'INR', 83.50],
            ['EUR', 'INR', 91.20],
            ['GBP', 'INR', 105.80],
            ['AUD', 'INR', 55.40],
            ['CAD', 'INR', 61.20],
            ['SGD', 'INR', 62.10],
            ['AED', 'INR', 22.75],
            ['MYR', 'INR', 18.90],
            ['INR', 'USD', 0.012],
            ['INR', 'EUR', 0.011],
            ['INR', 'GBP', 0.0095],
            ['INR', 'AUD', 0.018],
            ['INR', 'CAD', 0.016]
        ];
        
        $stmt = $db->prepare("
            INSERT INTO currency_exchange_rates (from_currency, to_currency, exchange_rate, rate_date) 
            VALUES (?, ?, ?, CURDATE())
            ON DUPLICATE KEY UPDATE
            exchange_rate = VALUES(exchange_rate),
            rate_date = VALUES(rate_date)
        ");
        
        foreach ($rates as $rate) {
            $stmt->execute($rate);
        }
        
        echo "✓ Inserted " . count($rates) . " exchange rates\n";
    } catch (PDOException $e) {
        echo "✗ Error inserting exchange rates: " . $e->getMessage() . "\n";
    }
    
    // Step 7: Create payment failure logs table
    echo "\n7. Creating payment_failure_logs table...\n";
    
    try {
        $db->exec("
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
            )
        ");
        echo "✓ Created payment_failure_logs table\n";
    } catch (PDOException $e) {
        echo "✗ Error creating payment_failure_logs: " . $e->getMessage() . "\n";
    }
    
    // Step 8: Add indexes
    echo "\n8. Adding performance indexes...\n";
    
    // Add indexes to technical_details_payments if columns exist
    try {
        $result = $db->query("SHOW COLUMNS FROM technical_details_payments LIKE 'currency'");
        if ($result->rowCount() > 0) {
            try {
                $db->exec("ALTER TABLE technical_details_payments ADD INDEX idx_currency (currency)");
                echo "✓ Added currency index to technical_details_payments\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "- Currency index already exists on technical_details_payments\n";
                }
            }
            
            try {
                $db->exec("ALTER TABLE technical_details_payments ADD INDEX idx_country_code (country_code)");
                echo "✓ Added country_code index to technical_details_payments\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "- Country code index already exists on technical_details_payments\n";
                }
            }
            
            try {
                $db->exec("ALTER TABLE technical_details_payments ADD INDEX idx_international_payment (international_payment)");
                echo "✓ Added international_payment index to technical_details_payments\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "- International payment index already exists on technical_details_payments\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "- Could not add indexes to technical_details_payments: " . $e->getMessage() . "\n";
    }
    
    // Add indexes to stage_payment_transactions if columns exist
    try {
        $result = $db->query("SHOW COLUMNS FROM stage_payment_transactions LIKE 'currency'");
        if ($result->rowCount() > 0) {
            try {
                $db->exec("ALTER TABLE stage_payment_transactions ADD INDEX idx_currency (currency)");
                echo "✓ Added currency index to stage_payment_transactions\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "- Currency index already exists on stage_payment_transactions\n";
                }
            }
            
            try {
                $db->exec("ALTER TABLE stage_payment_transactions ADD INDEX idx_country_code (country_code)");
                echo "✓ Added country_code index to stage_payment_transactions\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "- Country code index already exists on stage_payment_transactions\n";
                }
            }
            
            try {
                $db->exec("ALTER TABLE stage_payment_transactions ADD INDEX idx_international_payment (international_payment)");
                echo "✓ Added international_payment index to stage_payment_transactions\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "- International payment index already exists on stage_payment_transactions\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "- Could not add indexes to stage_payment_transactions: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ International payment schema setup completed!\n";
    echo "\nNext steps:\n";
    echo "1. Update your Razorpay dashboard to enable international payments\n";
    echo "2. Test with international cards\n";
    echo "3. Monitor payment_failure_logs table for any issues\n";
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
}
?>