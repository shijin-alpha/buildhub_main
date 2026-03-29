<?php
require_once 'backend/config/database.php';

try {
    $db = new PDO("sqlite:buildhub.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Creating concept_previews table...\n";
    
    // SQLite version of the concept_previews table
    $sql = "
    CREATE TABLE IF NOT EXISTS concept_previews (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        architect_id INTEGER NOT NULL,
        layout_request_id INTEGER NOT NULL,
        job_id TEXT UNIQUE,
        original_description TEXT NOT NULL,
        refined_prompt TEXT,
        status TEXT DEFAULT 'processing' CHECK (status IN ('processing', 'generating', 'completed', 'failed')),
        image_url TEXT,
        image_path TEXT,
        is_placeholder INTEGER DEFAULT 0,
        error_message TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    $db->exec($sql);
    echo "Table created successfully!\n";
    
    // Create indexes
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_architect_id ON concept_previews(architect_id)",
        "CREATE INDEX IF NOT EXISTS idx_layout_request_id ON concept_previews(layout_request_id)",
        "CREATE INDEX IF NOT EXISTS idx_status ON concept_previews(status)",
        "CREATE INDEX IF NOT EXISTS idx_job_id ON concept_previews(job_id)",
        "CREATE INDEX IF NOT EXISTS idx_created_at ON concept_previews(created_at)"
    ];
    
    foreach ($indexes as $index) {
        $db->exec($index);
    }
    echo "Indexes created successfully!\n";
    
    // Check if table was created
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='concept_previews'");
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✓ concept_previews table is ready!\n";
        
        // Show table structure
        $stmt = $db->query("PRAGMA table_info(concept_previews)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nTable structure:\n";
        foreach ($columns as $column) {
            echo "- {$column['name']} ({$column['type']})\n";
        }
    } else {
        echo "✗ Failed to create table\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>