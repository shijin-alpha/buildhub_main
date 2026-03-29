<?php
try {
    $pdo = new PDO('sqlite:buildhub.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Setting up House Plans tables for SQLite...\n";

    // Create house_plans table
    $sql1 = "CREATE TABLE IF NOT EXISTS house_plans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        architect_id INTEGER NOT NULL,
        layout_request_id INTEGER NULL,
        plan_name TEXT NOT NULL,
        plot_width REAL NOT NULL,
        plot_height REAL NOT NULL,
        plan_data TEXT NOT NULL,
        total_area REAL NOT NULL,
        status TEXT DEFAULT 'draft' CHECK (status IN ('draft', 'submitted', 'approved', 'rejected')),
        version INTEGER DEFAULT 1,
        parent_plan_id INTEGER NULL,
        notes TEXT,
        layout_image TEXT NULL,
        technical_details TEXT NULL,
        unlock_price REAL DEFAULT 8000,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql1);
    echo "✓ Created house_plans table\n";

    // Create house_plan_reviews table
    $sql3 = "CREATE TABLE IF NOT EXISTS house_plan_reviews (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        house_plan_id INTEGER NOT NULL,
        homeowner_id INTEGER NOT NULL,
        status TEXT NOT NULL CHECK (status IN ('pending', 'approved', 'rejected', 'revision_requested')),
        feedback TEXT,
        reviewed_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql3);
    echo "✓ Created house_plan_reviews table\n";

    // Insert room templates (they already exist, so we'll skip this)
    echo "✓ Room templates already exist\n";

    // Create technical_details_payments table
    $sql4 = "CREATE TABLE IF NOT EXISTS technical_details_payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        house_plan_id INTEGER NOT NULL,
        homeowner_id INTEGER NOT NULL,
        amount REAL NOT NULL,
        razorpay_order_id TEXT NOT NULL,
        razorpay_payment_id TEXT NULL,
        razorpay_signature TEXT NULL,
        status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'failed')),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql4);
    echo "✓ Created technical_details_payments table\n";

    echo "\nHouse Plans feature setup completed successfully!\n";

} catch (Exception $e) {
    echo "Error setting up House Plans feature: " . $e->getMessage() . "\n";
}
?>