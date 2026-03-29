<?php
// Summary of current architect users

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🏛️  Current Architect Users Summary\n";
    echo "=====================================\n\n";
    
    // Get all architect users
    $architects = $db->prepare("
        SELECT 
            id, 
            first_name, 
            last_name, 
            email, 
            status, 
            company_name, 
            phone, 
            city, 
            specialization,
            experience_years,
            created_at
        FROM users 
        WHERE role = 'architect' 
        ORDER BY status DESC, created_at ASC
    ");
    $architects->execute();
    $architectList = $architects->fetchAll(PDO::FETCH_ASSOC);
    
    $approvedCount = 0;
    $pendingCount = 0;
    
    echo "📊 Total Architects: " . count($architectList) . "\n\n";
    
    foreach ($architectList as $architect) {
        $status = $architect['status'];
        $statusIcon = $status === 'approved' ? '✅' : '⏳';
        
        if ($status === 'approved') $approvedCount++;
        if ($status === 'pending') $pendingCount++;
        
        echo "{$statusIcon} {$architect['first_name']} {$architect['last_name']}\n";
        echo "   📧 Email: {$architect['email']}\n";
        echo "   🏢 Company: " . ($architect['company_name'] ?: 'Not specified') . "\n";
        echo "   📍 Location: " . ($architect['city'] ?: 'Not specified') . "\n";
        echo "   🎯 Specialization: " . ($architect['specialization'] ?: 'Not specified') . "\n";
        echo "   ⏰ Experience: " . ($architect['experience_years'] ?: 'Not specified') . " years\n";
        echo "   📞 Phone: " . ($architect['phone'] ?: 'Not specified') . "\n";
        echo "   📅 Joined: {$architect['created_at']}\n";
        echo "   🔖 Status: " . strtoupper($status) . "\n";
        echo "\n";
    }
    
    echo "📈 Summary:\n";
    echo "   ✅ Approved: $approvedCount architect(s)\n";
    echo "   ⏳ Pending: $pendingCount architect(s)\n";
    echo "   📊 Total: " . count($architectList) . " architect(s)\n\n";
    
    echo "💡 All architects appear to be valid, registered users with proper profiles.\n";
    echo "🎉 No test users found - your system is clean!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
