-- Simple Receipt Upload System
-- Just for contractors to upload any receipt and both parties to view them

CREATE TABLE IF NOT EXISTS `simple_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `receipt_title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_contractor` (`contractor_id`),
  KEY `idx_homeowner` (`homeowner_id`),
  KEY `idx_upload_date` (`upload_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;