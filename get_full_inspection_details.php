<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== FULL INSPECTION REPORT DETAILS ===\n\n";
    
    // Get the complete inspection report for user 32
    $stmt = $db->prepare('
        SELECT ir.*, cp.project_name, cp.homeowner_name, cp.project_location,
               CONCAT(inspector.first_name, " ", inspector.last_name) as inspector_name,
               inspector.email as inspector_email, inspector.phone as inspector_phone
        FROM inspection_reports ir 
        JOIN construction_projects cp ON ir.project_id = cp.id 
        LEFT JOIN users inspector ON ir.inspector_id = inspector.id
        WHERE cp.homeowner_id = 32
        ORDER BY ir.created_at DESC
    ');
    $stmt->execute();
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        echo "❌ No inspection report found for user 32\n";
        exit;
    }
    
    echo "✅ COMPLETE INSPECTION REPORT DATA:\n\n";
    
    // Basic Information
    echo "📋 BASIC INFORMATION:\n";
    echo "  Report ID: {$report['id']}\n";
    echo "  Project: {$report['project_name']}\n";
    echo "  Homeowner: {$report['homeowner_name']}\n";
    echo "  Location: " . ($report['project_location'] ?: 'Not specified') . "\n";
    echo "  Inspector: " . ($report['inspector_name'] ?: 'Unknown') . "\n";
    echo "  Inspector Email: " . ($report['inspector_email'] ?: 'Not available') . "\n";
    echo "  Inspector Phone: " . ($report['inspector_phone'] ?: 'Not available') . "\n\n";
    
    // Inspection Details
    echo "🔍 INSPECTION DETAILS:\n";
    echo "  Date: {$report['inspection_date']}\n";
    echo "  Time: " . ($report['inspection_time'] ?: 'Not specified') . "\n";
    echo "  Stage: {$report['inspection_stage']}\n";
    echo "  Type: {$report['inspection_type']}\n";
    echo "  Overall Status: {$report['overall_status']}\n";
    echo "  Quality Score: " . ($report['quality_score'] ?: 'Not scored') . "/10\n\n";
    
    // Safety & Compliance
    echo "🛡️ SAFETY & COMPLIANCE:\n";
    echo "  Safety Compliance: {$report['safety_compliance']}\n";
    echo "  Safety Equipment Available: " . ($report['safety_equipment_available'] ?: 'Not specified') . "\n";
    echo "  Safety Violations Found: " . ($report['safety_violations_found'] ?: 'Not specified') . "\n";
    echo "  Structural Integrity: " . ($report['structural_integrity'] ?: 'Not assessed') . "\n";
    echo "  Workmanship Quality: " . ($report['workmanship_quality'] ?: 'Not assessed') . "\n";
    echo "  Code Compliance: " . ($report['code_compliance'] ?: 'Not assessed') . "\n\n";
    
    // Site Conditions
    echo "🌤️ SITE CONDITIONS:\n";
    echo "  Weather: " . ($report['weather_conditions'] ?: 'Not recorded') . "\n";
    echo "  Temperature: " . ($report['temperature'] ? $report['temperature'] . '°C' : 'Not recorded') . "\n";
    echo "  Site Accessibility: " . ($report['site_accessibility'] ?: 'Not assessed') . "\n";
    echo "  Site Cleanliness: " . ($report['site_cleanliness'] ?: 'Not assessed') . "\n";
    echo "  Environmental Impact: " . ($report['environmental_impact'] ?: 'Not assessed') . "\n";
    echo "  Waste Management: " . ($report['waste_management'] ?: 'Not assessed') . "\n\n";
    
    // Work Progress & Materials
    echo "🏗️ WORK PROGRESS & MATERIALS:\n";
    echo "  Work Progress Since Last: " . ($report['work_progress_since_last'] ?: 'Not specified') . "\n";
    echo "  Materials on Site: " . ($report['materials_on_site'] ?: 'Not specified') . "\n";
    echo "  Equipment on Site: " . ($report['equipment_on_site'] ?: 'Not specified') . "\n";
    echo "  Workforce Present: " . ($report['workforce_present'] ?: 'Not specified') . "\n\n";
    
    // Infrastructure
    echo "🚧 INFRASTRUCTURE:\n";
    echo "  Access Roads Condition: " . ($report['access_roads_condition'] ?: 'Not assessed') . "\n";
    echo "  Utilities Status: " . ($report['utilities_status'] ?: 'Not assessed') . "\n";
    echo "  Security Measures: " . ($report['security_measures'] ?: 'Not assessed') . "\n\n";
    
    // Inspector Notes & Recommendations
    echo "📝 INSPECTOR NOTES & RECOMMENDATIONS:\n";
    echo "  Notes: " . ($report['notes'] ?: 'No notes provided') . "\n";
    echo "  Recommendations: " . ($report['recommendations'] ?: 'No recommendations provided') . "\n";
    echo "  Issues Identified: " . ($report['issues_identified'] ?: 'No issues identified') . "\n";
    echo "  Corrective Actions Required: " . ($report['corrective_actions_required'] ?: 'No corrective actions required') . "\n\n";
    
    // Follow-up Information
    echo "🔄 FOLLOW-UP INFORMATION:\n";
    echo "  Next Inspection Date: " . ($report['next_inspection_date'] ?: 'Not scheduled') . "\n";
    echo "  Follow-up Required: " . ($report['follow_up_required'] ?: 'Not specified') . "\n";
    echo "  Contractor Present: " . ($report['contractor_present'] ?: 'Not specified') . "\n";
    echo "  Contractor Representative: " . ($report['contractor_representative'] ?: 'Not specified') . "\n";
    echo "  Homeowner Notified: " . ($report['homeowner_notified'] ?: 'Not specified') . "\n\n";
    
    // Timestamps
    echo "⏰ TIMESTAMPS:\n";
    echo "  Created: {$report['created_at']}\n";
    echo "  Updated: {$report['updated_at']}\n\n";
    
    // Get checklist items if they exist
    $stmt = $db->prepare('SELECT * FROM inspection_checklist_items WHERE inspection_report_id = ?');
    $stmt->execute([$report['id']]);
    $checklistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($checklistItems)) {
        echo "✅ CHECKLIST ITEMS:\n";
        foreach ($checklistItems as $item) {
            echo "  - {$item['item_name']}: {$item['status']} ({$item['priority']})\n";
            if ($item['notes']) {
                echo "    Notes: {$item['notes']}\n";
            }
        }
    } else {
        echo "📋 CHECKLIST ITEMS: None found\n";
    }
    
    echo "\n=== REPORT COMPLETE ===\n";
    
} catch (Exception $e) {
    echo '❌ Error: ' . $e->getMessage() . "\n";
}
?>