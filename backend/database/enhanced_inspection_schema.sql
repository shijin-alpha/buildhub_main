-- Enhanced Site Inspection System Database Schema
-- Extends existing inspection system with stage-aware, real-world inspection capabilities

-- Add new columns to existing inspection_reports table
ALTER TABLE `inspection_reports` 
ADD COLUMN `stage_approval_decision` ENUM('approved', 'rejected', 'requires_reinspection', 'pending') DEFAULT 'pending' AFTER `overall_status`,
ADD COLUMN `stage_approval_notes` TEXT DEFAULT NULL AFTER `stage_approval_decision`,
ADD COLUMN `critical_failures_count` INT DEFAULT 0 AFTER `stage_approval_notes`,
ADD COLUMN `corrective_actions_deadline` DATE DEFAULT NULL AFTER `corrective_actions_required`,
ADD COLUMN `reinspection_required` BOOLEAN DEFAULT FALSE AFTER `corrective_actions_deadline`,
ADD COLUMN `reinspection_date` DATE DEFAULT NULL AFTER `reinspection_required`,
ADD COLUMN `inspection_time` TIME DEFAULT NULL AFTER `inspection_date`,
ADD COLUMN `weather_conditions` ENUM('clear', 'cloudy', 'rainy', 'windy', 'hot', 'cold') DEFAULT NULL,
ADD COLUMN `temperature` DECIMAL(5,2) DEFAULT NULL COMMENT 'Temperature in Celsius',
ADD COLUMN `site_accessibility` ENUM('good', 'fair', 'poor', 'restricted') DEFAULT 'good',
ADD COLUMN `access_roads_condition` ENUM('good', 'fair', 'poor', 'blocked') DEFAULT 'good',
ADD COLUMN `site_cleanliness` ENUM('excellent', 'good', 'fair', 'poor') DEFAULT 'good',
ADD COLUMN `utilities_status` ENUM('operational', 'partial', 'not_available', 'under_installation') DEFAULT 'operational',
ADD COLUMN `work_progress_since_last` TEXT DEFAULT NULL,
ADD COLUMN `workforce_present` INT DEFAULT NULL,
ADD COLUMN `contractor_present` ENUM('no', 'yes') DEFAULT 'no',
ADD COLUMN `contractor_representative` VARCHAR(255) DEFAULT NULL,
ADD COLUMN `materials_on_site` TEXT DEFAULT NULL,
ADD COLUMN `equipment_on_site` TEXT DEFAULT NULL,
ADD COLUMN `safety_equipment_available` ENUM('yes', 'no', 'partial') DEFAULT 'yes',
ADD COLUMN `safety_violations_found` ENUM('no', 'yes', 'minor', 'major') DEFAULT 'no',
ADD COLUMN `security_measures` ENUM('adequate', 'inadequate', 'excellent', 'needs_improvement') DEFAULT 'adequate',
ADD COLUMN `structural_integrity` ENUM('satisfactory', 'excellent', 'needs_attention', 'unsatisfactory') DEFAULT 'satisfactory',
ADD COLUMN `workmanship_quality` ENUM('excellent', 'good', 'fair', 'poor') DEFAULT 'good',
ADD COLUMN `code_compliance` ENUM('compliant', 'non_compliant', 'partial', 'pending_verification') DEFAULT 'compliant',
ADD COLUMN `waste_management` ENUM('proper', 'improper', 'needs_improvement', 'excellent') DEFAULT 'proper',
ADD COLUMN `environmental_impact` ENUM('minimal', 'moderate', 'significant', 'concerning') DEFAULT 'minimal',
ADD COLUMN `issues_identified` TEXT DEFAULT NULL,
ADD COLUMN `corrective_actions_required` TEXT DEFAULT NULL,
ADD COLUMN `follow_up_required` ENUM('no', 'yes', 'urgent') DEFAULT 'no',
ADD COLUMN `inspector_signature` VARCHAR(255) DEFAULT NULL,
ADD COLUMN `homeowner_notified` ENUM('no', 'yes', 'pending') DEFAULT 'no',
ADD COLUMN `inspection_completeness_score` DECIMAL(5,2) DEFAULT NULL COMMENT 'Completeness score out of 100',
ADD COLUMN `auto_generated_summary` TEXT DEFAULT NULL COMMENT 'Homeowner-friendly summary',
ADD COLUMN `requires_photos` BOOLEAN DEFAULT FALSE COMMENT 'Whether photos are required for this inspection',
ADD COLUMN `photos_uploaded` BOOLEAN DEFAULT FALSE COMMENT 'Whether required photos have been uploaded';

-- Create stage-specific checklist templates table
CREATE TABLE `inspection_stage_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stage_name` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `item_description` varchar(500) NOT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `is_mandatory` boolean DEFAULT FALSE,
  `applies_to_stage` varchar(100) NOT NULL,
  `order_sequence` int DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `stage_name` (`stage_name`),
  KEY `applies_to_stage` (`applies_to_stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create inspection evidence photos table (enhanced version)
CREATE TABLE `inspection_evidence_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inspection_report_id` int(11) NOT NULL,
  `checklist_item_id` int(11) DEFAULT NULL COMMENT 'Link to specific checklist item',
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `caption` varchar(500) DEFAULT NULL,
  `photo_type` enum('issue_evidence','safety_violation','quality_concern','progress_verification','compliance_check') DEFAULT 'issue_evidence',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_accuracy` decimal(8,2) DEFAULT NULL,
  `timestamp_taken` timestamp DEFAULT NULL,
  `is_required` boolean DEFAULT FALSE,
  `linked_issue` text DEFAULT NULL COMMENT 'Description of issue this photo documents',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inspection_report_id` (`inspection_report_id`),
  KEY `checklist_item_id` (`checklist_item_id`),
  FOREIGN KEY (`inspection_report_id`) REFERENCES `inspection_reports` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`checklist_item_id`) REFERENCES `inspection_checklist_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create inspection audit trail table
CREATE TABLE `inspection_audit_trail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inspection_report_id` int(11) NOT NULL,
  `action_type` enum('created','updated','approved','rejected','reinspection_required','photos_uploaded','corrective_action_completed') NOT NULL,
  `performed_by` int(11) NOT NULL,
  `performed_by_role` enum('site_inspector','admin','contractor','homeowner') NOT NULL,
  `action_details` json DEFAULT NULL,
  `previous_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inspection_report_id` (`inspection_report_id`),
  KEY `performed_by` (`performed_by`),
  KEY `timestamp` (`timestamp`),
  FOREIGN KEY (`inspection_report_id`) REFERENCES `inspection_reports` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create homeowner inspection summaries table
CREATE TABLE `homeowner_inspection_summaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inspection_report_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `summary_title` varchar(255) NOT NULL,
  `progress_status` enum('on_track','minor_issues','major_concerns','excellent_progress') DEFAULT 'on_track',
  `key_findings` text DEFAULT NULL,
  `next_steps` text DEFAULT NULL,
  `estimated_completion_impact` varchar(255) DEFAULT NULL,
  `quality_rating` enum('excellent','good','satisfactory','needs_improvement') DEFAULT 'satisfactory',
  `safety_status` enum('compliant','minor_concerns','major_violations') DEFAULT 'compliant',
  `homeowner_action_required` boolean DEFAULT FALSE,
  `action_items` text DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_visible_to_homeowner` boolean DEFAULT TRUE,
  PRIMARY KEY (`id`),
  KEY `inspection_report_id` (`inspection_report_id`),
  KEY `project_id` (`project_id`),
  KEY `homeowner_id` (`homeowner_id`),
  FOREIGN KEY (`inspection_report_id`) REFERENCES `inspection_reports` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`project_id`) REFERENCES `construction_projects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`homeowner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert stage-specific checklist templates
INSERT INTO `inspection_stage_templates` (`stage_name`, `category`, `item_description`, `priority`, `is_mandatory`, `applies_to_stage`, `order_sequence`) VALUES

-- Site Preparation Stage
('Site Preparation', 'Site Conditions', 'Site boundaries clearly marked and verified', 'high', TRUE, 'Site Preparation', 1),
('Site Preparation', 'Site Conditions', 'Existing structures and utilities identified', 'critical', TRUE, 'Site Preparation', 2),
('Site Preparation', 'Site Conditions', 'Soil conditions assessed and documented', 'high', TRUE, 'Site Preparation', 3),
('Site Preparation', 'Safety', 'Site safety barriers and signage installed', 'critical', TRUE, 'Site Preparation', 4),
('Site Preparation', 'Environmental', 'Environmental protection measures in place', 'high', FALSE, 'Site Preparation', 5),
('Site Preparation', 'Access', 'Site access roads and parking areas prepared', 'medium', FALSE, 'Site Preparation', 6),

-- Foundation Stage
('Foundation', 'Excavation', 'Excavation depth matches approved plans', 'critical', TRUE, 'Foundation', 1),
('Foundation', 'Excavation', 'Excavation walls stable and properly sloped', 'critical', TRUE, 'Foundation', 2),
('Foundation', 'Foundation', 'Foundation layout and dimensions verified', 'critical', TRUE, 'Foundation', 3),
('Foundation', 'Foundation', 'Reinforcement placement and spacing correct', 'critical', TRUE, 'Foundation', 4),
('Foundation', 'Foundation', 'Concrete cover to reinforcement adequate', 'high', TRUE, 'Foundation', 5),
('Foundation', 'Foundation', 'Concrete quality and mix design verified', 'high', TRUE, 'Foundation', 6),
('Foundation', 'Foundation', 'Curing procedures properly implemented', 'high', TRUE, 'Foundation', 7),
('Foundation', 'Waterproofing', 'Waterproofing membrane properly installed', 'high', FALSE, 'Foundation', 8),
('Foundation', 'Drainage', 'Foundation drainage system installed', 'medium', FALSE, 'Foundation', 9),

-- Structure Stage
('Structure', 'Columns', 'Column alignment and verticality verified', 'critical', TRUE, 'Structure', 1),
('Structure', 'Columns', 'Column dimensions match structural drawings', 'critical', TRUE, 'Structure', 2),
('Structure', 'Columns', 'Column reinforcement placement correct', 'critical', TRUE, 'Structure', 3),
('Structure', 'Beams', 'Beam dimensions and alignment verified', 'critical', TRUE, 'Structure', 4),
('Structure', 'Beams', 'Beam reinforcement placement and cover', 'critical', TRUE, 'Structure', 5),
('Structure', 'Slabs', 'Slab thickness and reinforcement verified', 'high', TRUE, 'Structure', 6),
('Structure', 'Slabs', 'Slab level and surface finish acceptable', 'high', TRUE, 'Structure', 7),
('Structure', 'Joints', 'Construction joints properly executed', 'medium', FALSE, 'Structure', 8),
('Structure', 'Quality', 'Concrete strength test results available', 'high', TRUE, 'Structure', 9),

-- Brickwork Stage
('Brickwork', 'Materials', 'Brick quality and specifications verified', 'high', TRUE, 'Brickwork', 1),
('Brickwork', 'Materials', 'Mortar mix proportions correct', 'high', TRUE, 'Brickwork', 2),
('Brickwork', 'Construction', 'Wall alignment and plumbness verified', 'high', TRUE, 'Brickwork', 3),
('Brickwork', 'Construction', 'Mortar joints uniform and properly filled', 'medium', TRUE, 'Brickwork', 4),
('Brickwork', 'Construction', 'Door and window openings correct', 'high', TRUE, 'Brickwork', 5),
('Brickwork', 'Construction', 'Lintels properly installed and supported', 'high', TRUE, 'Brickwork', 6),
('Brickwork', 'Damp Proofing', 'Damp proof course properly installed', 'high', FALSE, 'Brickwork', 7),

-- Roofing Stage
('Roofing', 'Structure', 'Roof structure alignment and support verified', 'critical', TRUE, 'Roofing', 1),
('Roofing', 'Structure', 'Roof slope and drainage adequate', 'high', TRUE, 'Roofing', 2),
('Roofing', 'Materials', 'Roofing materials quality verified', 'high', TRUE, 'Roofing', 3),
('Roofing', 'Installation', 'Roofing installation per specifications', 'high', TRUE, 'Roofing', 4),
('Roofing', 'Waterproofing', 'Roof waterproofing system complete', 'critical', TRUE, 'Roofing', 5),
('Roofing', 'Drainage', 'Roof drainage system functional', 'high', TRUE, 'Roofing', 6),
('Roofing', 'Safety', 'Roof safety features installed', 'high', FALSE, 'Roofing', 7),

-- Electrical Stage
('Electrical', 'Planning', 'Electrical layout matches approved drawings', 'high', TRUE, 'Electrical', 1),
('Electrical', 'Conduits', 'Conduit installation and routing correct', 'high', TRUE, 'Electrical', 2),
('Electrical', 'Wiring', 'Cable specifications and installation correct', 'high', TRUE, 'Electrical', 3),
('Electrical', 'Earthing', 'Earthing system properly installed', 'critical', TRUE, 'Electrical', 4),
('Electrical', 'Distribution', 'Distribution board installation correct', 'high', TRUE, 'Electrical', 5),
('Electrical', 'Safety', 'ELCB/MCB protection devices installed', 'critical', TRUE, 'Electrical', 6),
('Electrical', 'Testing', 'Electrical testing and certification complete', 'high', TRUE, 'Electrical', 7),
('Electrical', 'Code Compliance', 'Installation meets electrical code requirements', 'critical', TRUE, 'Electrical', 8),

-- Plumbing Stage
('Plumbing', 'Planning', 'Plumbing layout matches approved drawings', 'high', TRUE, 'Plumbing', 1),
('Plumbing', 'Water Supply', 'Water supply system properly installed', 'high', TRUE, 'Plumbing', 2),
('Plumbing', 'Drainage', 'Drainage system installation correct', 'high', TRUE, 'Plumbing', 3),
('Plumbing', 'Fixtures', 'Plumbing fixtures properly installed', 'medium', TRUE, 'Plumbing', 4),
('Plumbing', 'Testing', 'Water pressure testing completed', 'high', TRUE, 'Plumbing', 5),
('Plumbing', 'Testing', 'Drainage system testing completed', 'high', TRUE, 'Plumbing', 6),
('Plumbing', 'Ventilation', 'Plumbing ventilation system adequate', 'medium', FALSE, 'Plumbing', 7),
('Plumbing', 'Code Compliance', 'Installation meets plumbing code requirements', 'critical', TRUE, 'Plumbing', 8),

-- Finishing Stage
('Finishing', 'Plastering', 'Wall plastering quality and finish', 'medium', TRUE, 'Finishing', 1),
('Finishing', 'Flooring', 'Flooring installation and finish quality', 'medium', TRUE, 'Finishing', 2),
('Finishing', 'Painting', 'Paint application and finish quality', 'low', TRUE, 'Finishing', 3),
('Finishing', 'Doors Windows', 'Door and window installation and operation', 'high', TRUE, 'Finishing', 4),
('Finishing', 'Fixtures', 'Electrical and plumbing fixtures functional', 'high', TRUE, 'Finishing', 5),
('Finishing', 'Hardware', 'Door and window hardware properly installed', 'medium', FALSE, 'Finishing', 6),
('Finishing', 'Cleanup', 'Construction cleanup completed', 'low', FALSE, 'Finishing', 7),

-- Final Inspection Stage
('Final Inspection', 'Overall Quality', 'Overall construction quality acceptable', 'critical', TRUE, 'Final Inspection', 1),
('Final Inspection', 'Code Compliance', 'All building codes and regulations met', 'critical', TRUE, 'Final Inspection', 2),
('Final Inspection', 'Safety Systems', 'All safety systems functional', 'critical', TRUE, 'Final Inspection', 3),
('Final Inspection', 'Utilities', 'All utilities connected and functional', 'critical', TRUE, 'Final Inspection', 4),
('Final Inspection', 'Documentation', 'All required certificates and warranties provided', 'high', TRUE, 'Final Inspection', 5),
('Final Inspection', 'Defects', 'Punch list items completed', 'high', TRUE, 'Final Inspection', 6),
('Final Inspection', 'Handover', 'Property ready for handover', 'critical', TRUE, 'Final Inspection', 7);

-- Create indexes for performance
CREATE INDEX idx_inspection_reports_stage_approval ON inspection_reports(stage_approval_decision);
CREATE INDEX idx_inspection_reports_critical_failures ON inspection_reports(critical_failures_count);
CREATE INDEX idx_inspection_reports_requires_photos ON inspection_reports(requires_photos);
CREATE INDEX idx_inspection_evidence_photos_required ON inspection_evidence_photos(is_required);
CREATE INDEX idx_inspection_audit_trail_action ON inspection_audit_trail(action_type);
CREATE INDEX idx_homeowner_summaries_visible ON homeowner_inspection_summaries(is_visible_to_homeowner);

-- Create view for stage-specific inspection statistics
CREATE VIEW `inspection_stage_statistics` AS
SELECT 
    ir.inspection_stage,
    COUNT(*) as total_inspections,
    SUM(CASE WHEN ir.stage_approval_decision = 'approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN ir.stage_approval_decision = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
    SUM(CASE WHEN ir.stage_approval_decision = 'requires_reinspection' THEN 1 ELSE 0 END) as reinspection_count,
    AVG(ir.quality_score) as avg_quality_score,
    AVG(ir.inspection_completeness_score) as avg_completeness_score,
    SUM(ir.critical_failures_count) as total_critical_failures,
    COUNT(CASE WHEN ir.requires_photos = TRUE THEN 1 END) as inspections_requiring_photos,
    COUNT(CASE WHEN ir.photos_uploaded = TRUE THEN 1 END) as inspections_with_photos
FROM inspection_reports ir
GROUP BY ir.inspection_stage;

-- Create view for inspector performance metrics
CREATE VIEW `inspector_performance_metrics` AS
SELECT 
    u.id as inspector_id,
    CONCAT(u.first_name, ' ', u.last_name) as inspector_name,
    COUNT(ir.id) as total_inspections,
    AVG(ir.quality_score) as avg_quality_score,
    AVG(ir.inspection_completeness_score) as avg_completeness_score,
    SUM(CASE WHEN ir.stage_approval_decision = 'approved' THEN 1 ELSE 0 END) as approvals_given,
    SUM(CASE WHEN ir.stage_approval_decision = 'rejected' THEN 1 ELSE 0 END) as rejections_given,
    AVG(ir.critical_failures_count) as avg_critical_failures,
    COUNT(CASE WHEN ir.follow_up_required != 'no' THEN 1 END) as follow_ups_required
FROM users u
JOIN inspection_reports ir ON u.id = ir.inspector_id
WHERE u.role = 'site_inspector'
GROUP BY u.id, u.first_name, u.last_name;