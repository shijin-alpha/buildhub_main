<?php
header('Content-Type: text/html; charset=utf-8');

require_once 'config/database.php';

echo "<h1>Debug: Contractor House Plans API</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>1. Database Connection</h2>";
    echo "✅ Database connected successfully<br><br>";
    
    // Check if required tables exist
    echo "<h2>2. Table Structure Check</h2>";
    
    $tables = [
        'house_plans',
        'layout_requests', 
        'users',
        'contractor_engagements',
        'contractor_layout_sends',
        'contractor_send_estimates'
    ];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
            
            // Show table structure
            $columns = $db->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_ASSOC);
            echo "<details><summary>View columns</summary>";
            echo "<ul>";
            foreach ($columns as $col) {
                echo "<li>{$col['Field']} ({$col['Type']})</li>";
            }
            echo "</ul></details>";
        } else {
            echo "❌ Table '$table' does not exist<br>";
        }
    }
    
    echo "<br><h2>3. Sample Data Check</h2>";
    
    // Check house_plans
    $stmt = $db->query("SELECT COUNT(*) as count FROM house_plans");
    $count = $stmt->fetch()['count'];
    echo "House Plans: $count records<br>";
    
    if ($count > 0) {
        $stmt = $db->query("SELECT id, plan_name, architect_id, status FROM house_plans LIMIT 3");
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<details><summary>Sample house plans</summary>";
        echo "<pre>" . print_r($plans, true) . "</pre>";
        echo "</details>";
    }
    
    // Check contractor_engagements
    $stmt = $db->query("SELECT COUNT(*) as count FROM contractor_engagements");
    $count = $stmt->fetch()['count'];
    echo "Contractor Engagements: $count records<br>";
    
    if ($count > 0) {
        $stmt = $db->query("SELECT * FROM contractor_engagements LIMIT 3");
        $engagements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<details><summary>Sample contractor engagements</summary>";
        echo "<pre>" . print_r($engagements, true) . "</pre>";
        echo "</details>";
    }
    
    // Check contractor_layout_sends
    $stmt = $db->query("SELECT COUNT(*) as count FROM contractor_layout_sends");
    $count = $stmt->fetch()['count'];
    echo "Contractor Layout Sends: $count records<br>";
    
    if ($count > 0) {
        $stmt = $db->query("SELECT * FROM contractor_layout_sends LIMIT 3");
        $sends = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<details><summary>Sample contractor layout sends</summary>";
        echo "<pre>" . print_r($sends, true) . "</pre>";
        echo "</details>";
    }
    
    echo "<br><h2>4. Test Query Execution</h2>";
    
    // Test a simplified version of the main query
    $testQuery = "
        SELECT 
            hp.id as house_plan_id,
            hp.plan_name,
            hp.architect_id,
            hp.status as plan_status
        FROM house_plans hp
        WHERE hp.architect_id = 1
        LIMIT 5
    ";
    
    echo "<h3>Simple Query Test:</h3>";
    echo "<code>$testQuery</code><br><br>";
    
    try {
        $stmt = $db->prepare($testQuery);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($results) > 0) {
            echo "✅ Simple query executed successfully<br>";
            echo "<details><summary>Results</summary>";
            echo "<pre>" . print_r($results, true) . "</pre>";
            echo "</details>";
        } else {
            echo "⚠️ Simple query executed but returned no results<br>";
        }
    } catch (Exception $e) {
        echo "❌ Simple query failed: " . $e->getMessage() . "<br>";
    }
    
    echo "<br><h2>5. Test Complex Query</h2>";
    
    // Test the complex query with joins
    $complexQuery = "
        SELECT DISTINCT
            hp.id as house_plan_id,
            hp.plan_name,
            hp.status as plan_status,
            
            -- Homeowner details
            homeowner.id as homeowner_id,
            CONCAT(COALESCE(homeowner.first_name, ''), ' ', COALESCE(homeowner.last_name, '')) as homeowner_name,
            
            -- Contractor engagement details
            ce.id as engagement_id,
            ce.engagement_type,
            
            -- Contractor layout sends
            cls.id as send_id
            
        FROM house_plans hp
        
        -- Join with homeowners (through layout requests only)
        LEFT JOIN layout_requests lr ON hp.layout_request_id = lr.id
        LEFT JOIN users homeowner ON lr.user_id = homeowner.id
        
        -- Join with contractor engagements
        LEFT JOIN contractor_engagements ce ON (
            ce.house_plan_id = hp.id OR 
            ce.layout_request_id = lr.id
        )
        
        -- Join with contractor layout sends
        LEFT JOIN contractor_layout_sends cls ON cls.house_plan_id = hp.id
        
        WHERE hp.architect_id = 1
        AND hp.status != 'deleted'
        AND (
            ce.id IS NOT NULL OR 
            cls.id IS NOT NULL
        )
        
        LIMIT 5
    ";
    
    echo "<h3>Complex Query Test:</h3>";
    echo "<details><summary>View query</summary>";
    echo "<code><pre>$complexQuery</pre></code>";
    echo "</details>";
    
    try {
        $stmt = $db->prepare($complexQuery);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($results) > 0) {
            echo "✅ Complex query executed successfully<br>";
            echo "<details><summary>Results</summary>";
            echo "<pre>" . print_r($results, true) . "</pre>";
            echo "</details>";
        } else {
            echo "⚠️ Complex query executed but returned no results<br>";
            echo "This might be normal if there are no contractor engagements or layout sends.<br>";
        }
    } catch (Exception $e) {
        echo "❌ Complex query failed: " . $e->getMessage() . "<br>";
        echo "<strong>Error details:</strong> " . $e->getTraceAsString() . "<br>";
    }
    
    echo "<br><h2>6. Session Check</h2>";
    session_start();
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        echo "✅ Session active<br>";
        echo "User ID: " . $_SESSION['user_id'] . "<br>";
        echo "Role: " . $_SESSION['role'] . "<br>";
    } else {
        echo "❌ No active session<br>";
        echo "This might cause authentication issues in the API<br>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Database Error</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<br><strong>Stack trace:</strong><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #333; }
h2 { color: #666; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
h3 { color: #888; }
details { margin: 10px 0; }
summary { cursor: pointer; font-weight: bold; }
code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>