<?php
// Notification Helper Functions

function createNotification($pdo, $user_id, $type, $title, $message, $related_id = null) {
    try {
        // Create notifications table if it doesn't exist
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                related_id INT NULL,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
        $pdo->exec($createTableSQL);
        
        // Insert notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, related_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $type, $title, $message, $related_id]);
        
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Notification creation error: " . $e->getMessage());
        return false;
    }
}

function createEstimateReceivedNotification($pdo, $homeowner_id, $estimate_id, $contractor_name, $project_title) {
    return createNotification(
        $pdo,
        $homeowner_id,
        'estimate_received',
        'New Estimate Received',
        "You have received a new estimate from {$contractor_name} for project: {$project_title}",
        $estimate_id
    );
}

function createLayoutApprovedNotification($pdo, $homeowner_id, $layout_id, $layout_title) {
    return createNotification(
        $pdo,
        $homeowner_id,
        'layout_approved',
        'Layout Approved',
        "Your layout request '{$layout_title}' has been approved and is ready for contractor estimates",
        $layout_id
    );
}

function createConstructionStartedNotification($pdo, $homeowner_id, $project_id, $project_title) {
    return createNotification(
        $pdo,
        $homeowner_id,
        'construction_started',
        'Construction Started',
        "Construction has started for your project: {$project_title}",
        $project_id
    );
}

function createMessageReceivedNotification($pdo, $user_id, $sender_name, $subject) {
    return createNotification(
        $pdo,
        $user_id,
        'message_received',
        'New Message Received',
        "You have received a new message from {$sender_name}: {$subject}",
        null
    );
}

function createPaymentRequiredNotification($pdo, $homeowner_id, $amount, $description) {
    return createNotification(
        $pdo,
        $homeowner_id,
        'payment_required',
        'Payment Required',
        "Payment of ₹{$amount} is required for: {$description}",
        null
    );
}

function createProjectCompletedNotification($pdo, $homeowner_id, $project_id, $project_title) {
    return createNotification(
        $pdo,
        $homeowner_id,
        'project_completed',
        'Project Completed',
        "Congratulations! Your project '{$project_title}' has been completed successfully",
        $project_id
    );
}
?>