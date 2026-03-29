-- Contractor Document Management System Schema
-- This schema supports stage-specific document uploads for construction projects

-- Table for storing contractor stage documents
CREATE TABLE IF NOT EXISTS `contractor_stage_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `stage_name` varchar(100) NOT NULL,
  `document_type` enum('receipt', 'bill', 'invoice', 'material_certificate', 'quality_report', 'safety_certificate', 'permit', 'inspection_report', 'other') NOT NULL,
  `document_category` enum('stage_specific', 'project_wide') DEFAULT 'stage_specific',
  `file_path` varchar(500) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `verification_status` enum('pending', 'approved', 'rejected') DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verification_notes` text DEFAULT NULL,
  `is_mandatory` tinyint(1) DEFAULT 0,
  `related_payment_id` int(11) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project_contractor` (`project_id`, `contractor_id`),
  KEY `idx_stage_name` (`stage_name`),
  KEY `idx_document_type` (`document_type`),
  KEY `idx_verification_status` (`verification_status`),
  KEY `idx_related_payment` (`related_payment_id`),
  FOREIGN KEY (`project_id`) REFERENCES `construction_projects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`contractor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`related_payment_id`) REFERENCES `stage_payment_requests` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for defining document requirements per stage
CREATE TABLE IF NOT EXISTS `stage_document_requirements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT NULL,
  `stage_name` varchar(100) NOT NULL,
  `document_type` enum('receipt', 'bill', 'invoice', 'material_certificate', 'quality_report', 'safety_certificate', 'permit', 'inspection_report', 'other') NOT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL,
  `accepted_formats` varchar(255) DEFAULT 'pdf,jpg,jpeg,png,doc,docx',
  `max_file_size` int(11) DEFAULT 10485760,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project_stage` (`project_id`, `stage_name`),
  KEY `idx_stage_document` (`stage_name`, `document_type`),
  FOREIGN KEY (`project_id`) REFERENCES `construction_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default document requirements for common construction stages
INSERT INTO `stage_document_requirements` (`stage_name`, `document_type`, `is_required`, `description`, `accepted_formats`, `max_file_size`) VALUES
('Foundation', 'receipt', 1, 'Material purchase receipts for foundation work', 'pdf,jpg,jpeg,png', 5242880),
('Foundation', 'bill', 1, 'Labor and equipment bills for foundation', 'pdf,jpg,jpeg,png', 5242880),
('Foundation', 'material_certificate', 1, 'Cement and steel quality certificates', 'pdf,jpg,jpeg,png', 5242880),
('Foundation', 'safety_certificate', 0, 'Safety compliance certificates', 'pdf', 5242880),

('Structure', 'receipt', 1, 'Material receipts for structural work', 'pdf,jpg,jpeg,png', 5242880),
('Structure', 'bill', 1, 'Labor bills for structural work', 'pdf,jpg,jpeg,png', 5242880),
('Structure', 'material_certificate', 1, 'Steel and concrete quality certificates', 'pdf,jpg,jpeg,png', 5242880),
('Structure', 'inspection_report', 0, 'Structural inspection reports', 'pdf', 5242880),

('Brickwork', 'receipt', 1, 'Brick and mortar purchase receipts', 'pdf,jpg,jpeg,png', 5242880),
('Brickwork', 'bill', 1, 'Mason labor bills', 'pdf,jpg,jpeg,png', 5242880),
('Brickwork', 'material_certificate', 0, 'Brick quality certificates', 'pdf,jpg,jpeg,png', 5242880),

('Roofing', 'receipt', 1, 'Roofing material receipts', 'pdf,jpg,jpeg,png', 5242880),
('Roofing', 'bill', 1, 'Roofing labor bills', 'pdf,jpg,jpeg,png', 5242880),
('Roofing', 'material_certificate', 0, 'Roofing material quality certificates', 'pdf,jpg,jpeg,png', 5242880),

('Electrical', 'receipt', 1, 'Electrical material receipts', 'pdf,jpg,jpeg,png', 5242880),
('Electrical', 'bill', 1, 'Electrician labor bills', 'pdf,jpg,jpeg,png', 5242880),
('Electrical', 'safety_certificate', 1, 'Electrical safety certificates', 'pdf', 5242880),
('Electrical', 'inspection_report', 0, 'Electrical inspection reports', 'pdf', 5242880),

('Plumbing', 'receipt', 1, 'Plumbing material receipts', 'pdf,jpg,jpeg,png', 5242880),
('Plumbing', 'bill', 1, 'Plumber labor bills', 'pdf,jpg,jpeg,png', 5242880),
('Plumbing', 'inspection_report', 0, 'Plumbing inspection reports', 'pdf', 5242880),

('Finishing', 'receipt', 1, 'Finishing material receipts', 'pdf,jpg,jpeg,png', 5242880),
('Finishing', 'bill', 1, 'Finishing work labor bills', 'pdf,jpg,jpeg,png', 5242880),
('Finishing', 'quality_report', 0, 'Finishing quality reports', 'pdf', 5242880);

-- Add document submission tracking to stage payment requests
ALTER TABLE `stage_payment_requests` 
ADD COLUMN `documents_submitted` json DEFAULT NULL AFTER `verification_notes`,
ADD COLUMN `document_verification_status` enum('pending', 'partial', 'complete', 'rejected') DEFAULT 'pending' AFTER `documents_submitted`;

-- Create index for better performance
CREATE INDEX `idx_document_verification` ON `stage_payment_requests` (`document_verification_status`);

-- Create audit table for document actions
CREATE TABLE IF NOT EXISTS `contractor_document_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `action` enum('uploaded', 'verified', 'rejected', 'deleted', 'downloaded') NOT NULL,
  `performed_by` int(11) NOT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_document_action` (`document_id`, `action`),
  KEY `idx_performed_by` (`performed_by`),
  FOREIGN KEY (`document_id`) REFERENCES `contractor_stage_documents` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;