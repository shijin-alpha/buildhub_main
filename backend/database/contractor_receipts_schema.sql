-- Simple Contractor Receipts Table
-- This table stores receipts uploaded by contractors that are visible to homeowners

CREATE TABLE IF NOT EXISTS `contractor_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_contractor_id` (`contractor_id`),
  KEY `idx_homeowner_id` (`homeowner_id`),
  KEY `idx_upload_date` (`upload_date`),
  FOREIGN KEY (`project_id`) REFERENCES `construction_projects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`contractor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`homeowner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create uploads directory structure
-- This will be created automatically by the PHP upload script
-- Structure: backend/uploads/contractor_receipts/[PROJECT_ID]/[FILENAME]