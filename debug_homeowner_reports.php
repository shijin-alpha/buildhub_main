<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $homeowner_id = 28; // Test homeowner ID

    echo "=== HOMEOWNER PROGRESS REPORTS DEBUG ===\n\n";

    // Check if daily_progress_updates table exists and has data
    echo "1. Checking daily_progress_updates table:\n";
    $stmt = $db->prepare("SHOW TABLES LIKE 'daily_progress_updates'");
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "✅ Table exists\n";
        
        // Check total records
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM daily_progress_updates");
        $stmt->execute();
        $total = $stmt->fetch()['total'];
        echo "📊 Total records: $total\n";
        
        // Check records for homeowner
        $stmt = $db->prepare("SELECT COUNT(*) as homeowner_total FROM daily_progress_updates WHERE homeowner_id = ?");
        $stmt->execute([$homeowner_id]);
        $homeownerTotal = $stmt->fetch()['homeowner_total'];
        echo "🏠 Records for homeowner $homeowner_id: $homeownerTotal\n";
        
        if ($homeownerTotal > 0) {
            // Get sample record
            $stmt = $db->prepare("
                SELECT 
                    dpu.*,
                    u_contractor.first_name as contractor_first_name,
                    u_contractor.last_name as contractor_last_name
                FROM daily_progress_updates dpu
                LEFT JOIN users u_contractor ON dpu.contractor_id = u_contractor.id
                WHERE dpu.homeowner_id = ?
                ORDER BY dpu.created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$homeowner_id]);
            $sample = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "\n📋 Sample record:\n";
            echo "ID: " . $sample['id'] . "\n";
            echo "Project ID: " . $sample['project_id'] . "\n";
            echo "Contractor: " . $sample['contractor_first_name'] . " " . $sample['contractor_last_name'] . "\n";
            echo "Stage: " . $sample['construction_stage'] . "\n";
            echo "Date: " . $sample['update_date'] . "\n";
            echo "Work Done: " . substr($sample['work_done_today'], 0, 50) . "...\n";
            echo "Progress: " . $sample['cumulative_completion_percentage'] . "%\n";
            echo "Photos: " . ($sample['progress_photos'] ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "❌ Table does not exist\n";
    }

    echo "\n2. Checking users table for contractors:\n";
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE user_type = 'contractor'");
    $stmt->execute();
    $contractorCount = $stmt->fetch()['total'];
    echo "👷 Total contractors: $contractorCount\n";

    echo "\n3. Testing API endpoint:\n";
    $apiUrl = "http://localhost/buildhub/backend/api/homeowner/get_progress_updates.php?homeowner_id=$homeowner_id&limit=5";
    echo "🔗 API URL: $apiUrl\n";
    
    // Simulate API call
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    $apiResponse = @file_get_contents($apiUrl, false, $context);
    
    if ($apiResponse) {
        $apiData = json_decode($apiResponse, true);
        if ($apiData && $apiData['success']) {
            echo "✅ API call successful\n";
            echo "📊 Progress updates returned: " . count($apiData['data']['progress_updates']) . "\n";
            echo "🏗️ Projects returned: " . count($apiData['data']['projects']) . "\n";
            
            if (count($apiData['data']['progress_updates']) > 0) {
                $firstUpdate = $apiData['data']['progress_updates'][0];
                echo "\n📋 First update sample:\n";
                echo "ID: " . $firstUpdate['id'] . "\n";
                echo "Contractor: " . $firstUpdate['contractor_name'] . "\n";
                echo "Stage: " . $firstUpdate['construction_stage'] . "\n";
                echo "Progress: " . $firstUpdate['cumulative_completion_percentage'] . "%\n";
                echo "Photos: " . count($firstUpdate['photos']) . "\n";
            }
        } else {
            echo "❌ API call failed: " . ($apiData['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ Could not reach API endpoint\n";
    }

    echo "\n4. Checking for potential issues:\n";
    
    // Check if homeowner exists
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'homeowner'");
    $stmt->execute([$homeowner_id]);
    $homeowner = $stmt->fetch();
    
    if ($homeowner) {
        echo "✅ Homeowner exists: " . $homeowner['first_name'] . " " . $homeowner['last_name'] . "\n";
    } else {
        echo "❌ Homeowner with ID $homeowner_id not found\n";
    }
    
    // Check for recent updates
    $stmt = $db->prepare("
        SELECT COUNT(*) as recent_count 
        FROM daily_progress_updates 
        WHERE homeowner_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$homeowner_id]);
    $recentCount = $stmt->fetch()['recent_count'];
    echo "📅 Recent updates (last 7 days): $recentCount\n";

    echo "\n=== DEBUG COMPLETE ===\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
</content>