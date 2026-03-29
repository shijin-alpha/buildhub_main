-- Site Inspector Dashboard Database Schema
-- Add site_inspector role to existing users table and create inspector-specific tables

-- Modify users table to include site_inspector role
ALTER TABLE `users` MODIFY COLUMN `role` ENUM('homeowner','contractor','architect','site_inspector') DEFAULT NULL;

-- Create site inspector assignments table
CREATE TABLE `site_inspector_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inspector_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inspector_id` (`inspector_id`),
  KEY `project_id` (`project_id`),
  KEY `assigned_by` (`assigned_by`),
  FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`project_id`) REFERENCES `construction_projects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create inspection reports table
CREATE TABLE `inspection_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `inspector_id` int(11) NOT NULL,
  `inspection_date` date NOT NULL,
  `inspection_stage` varchar(100) NOT NULL,
  `inspection_type` enum('routine','milestone','quality','safety','final') DEFAULT 'routine',
  `overall_status` enum('approved','rejected','needs_attention','pending') DEFAULT 'pending',
  `quality_score` decimal(3,1) DEFAULT NULL COMMENT 'Score out of 10',
  `safety_compliance` enum('compliant','non_compliant','partial') DEFAULT 'compliant',
  `notes` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `next_inspection_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `inspector_id` (`inspector_id`),
  KEY `inspection_date` (`inspection_date`),
  FOREIGN KEY (`project_id`) REFERENCES `construction_projects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create inspection photos table
CREATE TABLE `inspection_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inspection_report_id` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `caption` varchar(500) DEFAULT NULL,
  `photo_type` enum('progress','issue','quality','safety','completion') DEFAULT 'progress',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_accuracy` decimal(8,2) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inspection_report_id` (`inspection_report_id`),
  FOREIGN KEY (`inspection_report_id`) REFERENCES `inspection_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create inspection checklist items table
CREATE TABLE `inspection_checklist_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inspection_report_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `item_description` varchar(500) NOT NULL,
  `status` enum('pass','fail','na','pending') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inspection_report_id` (`inspection_report_id`),
  FOREIGN KEY (`inspection_report_id`) REFERENCES `inspection_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create inspection notifications table
CREATE TABLE `inspection_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inspection_report_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `recipient_type` enum('homeowner','contractor','admin') NOT NULL,
  `notification_type` enum('inspection_scheduled','inspection_completed','issue_found','approval_required') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inspection_report_id` (`inspection_report_id`),
  KEY `recipient_id` (`recipient_id`),
  FOREIGN KEY (`inspection_report_id`) REFERENCES `inspection_reports` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample site inspector user
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `role`, `status`, `is_verified`, `phone`, `city`, `state`) VALUES
('Site', 'Inspector', 'inspector@buildhub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'site_inspector', 'approved', 1, '9876543210', 'Mumbai', 'Maharashtra');

-- Insert default inspection checklist categories and items
INSERT INTO `inspection_checklist_items` (`inspection_report_id`, `category`, `item_description`, `status`) VALUES
(0, 'Foundation', 'Foundation depth as per approved plans', 'pending'),
(0, 'Foundation', 'Concrete quality and curing', 'pending'),
(0, 'Foundation', 'Reinforcement placement and cover', 'pending'),
(0, 'Structure', 'Column alignment and dimensions', 'pending'),
(0, 'Structure', 'Beam reinforcement and concrete quality', 'pending'),
(0, 'Structure', 'Slab thickness and reinforcement', 'pending'),
(0, 'Electrical', 'Conduit installation as per code', 'pending'),
(0, 'Electrical', 'Earthing system compliance', 'pending'),
(0, 'Plumbing', 'Pipe installation and testing', 'pending'),
(0, 'Plumbing', 'Drainage system functionality', 'pending'),
(0, 'Safety', 'Safety equipment availability', 'pending'),
(0, 'Safety', 'Site safety protocols followed', 'pending'),
(0, 'Quality', 'Material quality as per specifications', 'pending'),
(0, 'Quality', 'Workmanship standards', 'pending');