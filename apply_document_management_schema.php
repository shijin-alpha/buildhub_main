<?php
/**
 * Apply Document Management Schema
 * This script creates the necessary database tables for the contractor document management system
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Applying Contractor Document Management Schema...\n\n";
    
    // Read and execute the schema file
    $schema = file_get_contents('backend/database/contractor_document_management_schema.sql');
    
    if (!$schema) {
        throw new Exception("Could not read schema file");
    }
    
    // Split the schema into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        try {
            $db->exec($statement);
            $success_count++;
            
            // Extract table name for logging
            if (preg_match('/CREATE TABLE.*?`([^`]+)`/i', $statement, $matches)) {
                echo "✓ Created table: {$matches[1]}\n";
            } elseif (preg_match('/INSERT INTO.*?`([^`]+)`/i', $statement, $matches)) {
                echo "✓ Inserted data into: {$matches[1]}\n";
            } elseif (preg_match('/ALTER TABLE.*?`([^`]+)`/i', $statement, $matches)) {
                echo "✓ Altered table: {$matches[1]}\n";
            } elseif (preg_match('/CREATE INDEX.*?`([^`]+)`/i', $statement, $matches)) {
                echo "✓ Created index: {$matches[1]}\n";
            } else {
                echo "✓ Executed statement\n";
            }
            
        } catch (PDOException $e) {
            $error_count++;
            echo "✗ Error executing statement: " . $e->getMessage() . "\n";
            echo "Statement: " . substr($statement, 0, 100) . "...\n\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Schema Application Complete!\n";
    echo "Successful statements: $success_count\n";
    echo "Failed statements: $error_count\n";
    
    if ($error_count === 0) {
        echo "\n🎉 All database changes applied successfully!\n";
        echo "\nThe following features are now available:\n";
        echo "• Stage-specific document uploads\n";
        echo "• Document type categorization (receipts, bills, certificates, etc.)\n";
        echo "• Document verification workflow\n";
        echo "• Project-specific document organization\n";
        echo "• Document audit trail\n";
        echo "• Integration with payment requests\n";
        
        echo "\nNext steps:\n";
        echo "1. Ensure the uploads/stage_documents/ directory exists and is writable\n";
        echo "2. Test the document upload functionality\n";
        echo "3. Configure document requirements per project if needed\n";
    } else {
        echo "\n⚠️  Some errors occurred. Please review the output above.\n";
    }
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
?>