<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🚀 Creating Test Layout Send with Site Details...\n\n";
    
    // Get the latest layout request for homeowner 28
    $layoutRequestStmt = $db->prepare("
        SELECT 
            plot_size, building_size, budget_range, location, timeline, 
            num_floors, orientation, site_considerations, material_preferences,
            budget_allocation, preferred_style, requirements
        FROM layout_requests 
        WHERE homeowner_id = 28 
        AND status NOT IN ('deleted', 'rejected')
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $layoutRequestStmt->execute();
    $layoutRequestRow = $layoutRequestStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$layoutRequestRow) {
        echo "❌ No layout requests found for homeowner 28\n";
        exit;
    }
    
    echo "✅ Found Layout Request Data:\n";
    echo "Plot Size: " . ($layoutRequestRow['plot_size'] ?: 'NOT SET') . "\n";
    echo "Building Size: " . ($layoutRequestRow['building_size'] ?: 'NOT SET') . "\n";
    echo "Budget Range: " . ($layoutRequestRow['budget_range'] ?: 'NOT SET') . "\n";
    echo "Location: " . ($layoutRequestRow['location'] ?: 'NOT SET') . "\n";
    echo "Timeline: " . ($layoutRequestRow['timeline'] ?: 'NOT SET') . "\n";
    echo "Num Floors: " . ($layoutRequestRow['num_floors'] ?: 'NOT SET') . "\n";
    echo "\n";
    
    // Parse requirements if it's JSON
    $parsed_requirements = null;
    if (!empty($layoutRequestRow['requirements'])) {
        try {
            $parsed_requirements = json_decode($layoutRequestRow['requirements'], true);
            echo "✅ Parsed Requirements:\n";
            foreach ($parsed_requirements as $key => $value) {
                echo "  $key: " . ($value ?: 'NOT SET') . "\n";
            }
        } catch (Throwable $e) {
            echo "❌ Failed to parse requirements JSON\n";
        }
    }
    echo "\n";
    
    // Get technical details from the latest house plan
    $housePlanStmt = $db->prepare("
        SELECT technical_details, plot_width, plot_height, total_area
        FROM house_plans 
        WHERE architect_id = 29 
        AND technical_details IS NOT NULL 
        AND technical_details != ''
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $housePlanStmt->execute();
    $housePlanRow = $housePlanStmt->fetch(PDO::FETCH_ASSOC);
    
    $technical_details = null;
    if ($housePlanRow && !empty($housePlanRow['technical_details'])) {
        $technical_details = json_decode($housePlanRow['technical_details'], true);
        echo "✅ Found Technical Details:\n";
        echo "Construction Cost: " . ($technical_details['construction_cost'] ?? 'NOT SET') . "\n";
        echo "Foundation Type: " . ($technical_details['foundation_type'] ?? 'NOT SET') . "\n";
        echo "Structure Type: " . ($technical_details['structure_type'] ?? 'NOT SET') . "\n";
        echo "Site Area: " . ($technical_details['site_area'] ?? 'NOT SET') . "\n";
        echo "Built-up Area: " . ($technical_details['built_up_area'] ?? 'NOT SET') . "\n";
    } else {
        echo "❌ No technical details found\n";
    }
    echo "\n";
    
    // Create comprehensive payload with enhanced site details
    $layout_request_details = [
        'plot_size' => $layoutRequestRow['plot_size'] ?: '2000 sq ft',
        'building_size' => $layoutRequestRow['building_size'] ?: '1800 sq ft',
        'budget_range' => $layoutRequestRow['budget_range'] ?: '10-15 lakhs',
        'location' => $layoutRequestRow['location'] ?: 'Mumbai, Maharashtra',
        'timeline' => $layoutRequestRow['timeline'] ?: '6-12 months',
        'num_floors' => $layoutRequestRow['num_floors'] ?: '2',
        'orientation' => $layoutRequestRow['orientation'] ?: 'South-facing',
        'site_considerations' => $layoutRequestRow['site_considerations'] ?: 'Standard residential site with good access',
        'material_preferences' => $layoutRequestRow['material_preferences'] ?: 'Granite, Vitrified Tiles, RCC',
        'budget_allocation' => $layoutRequestRow['budget_allocation'] ?: 'Balanced approach',
        'preferred_style' => $layoutRequestRow['preferred_style'] ?: 'Modern',
        'requirements' => $layoutRequestRow['requirements'],
        'parsed_requirements' => $parsed_requirements
    ];
    
    $payload = [
        'layout_id' => null,
        'design_id' => null,
        'message' => 'Test layout send with comprehensive site details for estimation form testing',
        'forwarded_design' => null,
        'layout_image_url' => null,
        'floor_details' => null,
        'technical_details' => $technical_details,
        'plot_size' => $layout_request_details['plot_size'],
        'building_size' => $layout_request_details['building_size'],
        'layout_request_details' => $layout_request_details
    ];
    
    echo "📦 Creating Payload with Layout Request Details:\n";
    echo "Direct plot_size: " . $payload['plot_size'] . "\n";
    echo "Direct building_size: " . $payload['building_size'] . "\n";
    echo "Layout Request Details Keys: " . implode(', ', array_keys($layout_request_details)) . "\n";
    echo "\n";
    
    // Insert into contractor_layout_sends
    $insertStmt = $db->prepare("
        INSERT INTO contractor_layout_sends 
        (contractor_id, homeowner_id, layout_id, design_id, message, payload) 
        VALUES (37, 28, NULL, NULL, ?, ?)
    ");
    
    $message = 'Test layout send with comprehensive site details for estimation form testing';
    $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    $insertStmt->bindValue(1, $message, PDO::PARAM_STR);
    $insertStmt->bindValue(2, $payloadJson, PDO::PARAM_STR);
    $insertStmt->execute();
    
    $insertId = $db->lastInsertId();
    
    echo "✅ Test Layout Send Created Successfully!\n";
    echo "Insert ID: " . $insertId . "\n";
    echo "Contractor ID: 37\n";
    echo "Homeowner ID: 28\n";
    echo "Message: " . $message . "\n";
    echo "\n";
    
    echo "🎯 Next Steps:\n";
    echo "1. Go to contractor dashboard (contractor ID: 37)\n";
    echo "2. Check the inbox for the new layout send\n";
    echo "3. Open the estimation form\n";
    echo "4. Verify that site details appear in Basic Information and Site Details sections\n";
    echo "\n";
    
    echo "🔍 To verify the data was created correctly, run:\n";
    echo "php check_technical_details_site_data.php\n";
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}