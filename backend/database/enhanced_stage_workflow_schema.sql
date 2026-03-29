-- Enhanced Construction Stage Workflow Database Schema
-- Implements contractor stage completion, site inspection approval, and homeowner visibility

-- Create construction stage workflow table
CREATE TABLE `construction_stage_workflow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `stage_name` varchar(100) NOT NULL,
  `stage_order` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `contractor_submission_id` int(11) DEFAULT NULL,
  `contractor_status` enum('not_started','in_progress','submitted_for_inspection','completed') DEFAULT 'not_started',
  `contractor_submitted_at` timestamp NULL DEFAULT NULL,
  `inspection_id` int(11) DEFAULT NULL,
  `inspection_status` enum('pending','approved','rejected','needs_revision') DEFAULT 'pending',
  `inspection_approved_at` timestamp NULL DEFAULT NULL,
  `inspector_id` int(11) DEFAULT NULL,
  `homeowner_visible` tinyint(1) DEFAULT 0 COMMENT 'Only true after inspection approval',
  `stage_completion_percentage` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_project_stage` (`project_id`, `stage_name`),
  KEY `project_id` (`project_id`),
  KEY `contractor_id` (`contractor_id`),
  KEY `inspector_id` (`inspector_id`),
  KEY `stage_order` (`stage_order`),
  FOREIGN KEY (`project_id`) REFERENCES `construction_projects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`contractor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create contractor stage submissions table
CREATE TABLE `contractor_stage_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `stage_name` varchar(100) NOT NULL,
  `submission_type` enum('daily_report','stage_completion') DEFAULT 'daily_report',
  `work_description` text NOT NULL,
  `completion_percentage` decimal(5,2) NOT NULL,
  `materials_used` text DEFAULT NULL,
  `labor_details` text DEFAULT NULL,
  `challenges_faced` text DEFAULT NULL,
  `next_day_plan` text DEFAULT NULL,
  `quality_notes` text DEFAULT NULL,
  `safety_notes` text DEFAULT NULL,
  `photo_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photo_paths`)),
  `document_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`document_paths`)),
  `geo_location` point DEFAULT NULL,
  `location_verified` tinyint(1) DEFAULT 0,
  `weather_conditions` varchar(100) DEFAULT NULL,
  `worker_count` int(11) DEFAULT 0,
  `hours_worked` decimal(4,2) DEFAULT 0.00,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('submitted','under_review','approved','rejected','revision_requested') DEFAULT 'submitted',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `contractor_id` (`contractor_id`),
  KEY `stage_name` (`stage_name`),
  KEY `submission_type` (`submission_type`),
  KEY `submitted_at` (`submitted_at`),
  FOREIGN KEY (`project_id`) REFERENCES `construction_projects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`contractor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create stage inspection approvals table
CREATE TABLE `stage_inspection_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `stage_workflow_id` int(11) NOT NULL,
  `contractor_submission_id` int(11) NOT NULL,
  `inspector_id` int(11) NOT NULL,
  `inspection_date` date NOT NULL,
  `inspection_type` enum('stage_completion','quality_check','safety_inspection','final_approval') DEFAULT 'stage_completion',
  `approval_status` enum('approved','rejected','conditional_approval','needs_revision') NOT NULL,
  `quality_score` decimal(3,1) DEFAULT NULL COMMENT 'Score out of 10',
  `safety_compliance` enum('compliant','non_compliant','partial','not_applicable') DEFAULT 'compliant',
  `inspection_notes` text DEFAULT NULL,
  `defects_found` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `required_corrections` text DEFAULT NULL,
  `reinspection_required` tinyint(1) DEFAULT 0,
  `reinspection_date` date DEFAULT NULL,
  `photo_evidence` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photo_evidence`)),
  `inspection_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`inspection_checklist`)),
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `stage_workflow_id` (`stage_workflow_id`),
  KEY `contractor_submission_id` (`contractor_submission_id`),
  KEY `inspector_id` (`inspector_id`),
  KEY `inspection_date` (`inspection_date`),
  KEY `approval_status` (`approval_status`),
  FOREIGN KEY (`project_id`) REFERENCES `construction_projects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`stage_workflow_id`) REFERENCES `construction_stage_workflow` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`contractor_submission_id`) REFERENCES `contractor_stage_submissions` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create stage workflow notifications table
CREATE TABLE `stage_workflow_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `stage_workflow_id` int(11) DEFAULT NULL,
  `recipient_id` int(11) NOT NULL,
  `recipient_type` enum('homeowner','contractor','inspector','admin') NOT NULL,
  `notification_type` enum('stage_submitted','inspection_required','stage_approved','stage_rejected','revision_requested','stage_completed') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_submission_id` int(11) DEFAULT NULL,
  `related_inspection_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `stage_workflow_id` (`stage_workflow_id`),
  KEY `recipient_id` (`recipient_id`),
  KEY `recipient_type` (`recipient_type`),
  KEY `notification_type` (`notification_type`),
  KEY `is_read` (`is_read`),
  FOREIGN KEY (`project_id`) REFERENCES `construction_projects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`stage_workflow_id`) REFERENCES `construction_stage_workflow` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create stage workflow audit log table
CREATE TABLE `stage_workflow_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `stage_workflow_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `user_role` enum('homeowner','contractor','inspector','admin') NOT NULL,
  `action` varchar(100) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `stage_workflow_id` (`stage_workflow_id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`project_id`) REFERENCES `construction_projects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`stage_workflow_id`) REFERENCES `construction_stage_workflow` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initialize stage workflow for existing projects
INSERT INTO `construction_stage_workflow` (`project_id`, `stage_name`, `stage_order`, `contractor_id`, `contractor_status`)
SELECT 
  cp.id as project_id,
  csp.stage_name,
  csp.stage_order,
  cp.contractor_id,
  'not_started' as contractor_status
FROM `construction_projects` cp
CROSS JOIN `construction_stage_payments` csp
WHERE cp.id NOT IN (
  SELECT DISTINCT project_id FROM `construction_stage_workflow`
)
ORDER BY cp.id, csp.stage_order;

-- Create indexes for performance
CREATE INDEX idx_stage_workflow_project_status ON construction_stage_workflow (project_id, contractor_status);
CREATE INDEX idx_stage_workflow_inspection_status ON construction_stage_workflow (inspection_status, inspector_id);
CREATE INDEX idx_contractor_submissions_project_stage ON contractor_stage_submissions (project_id, stage_name, submission_type);
CREATE INDEX idx_inspection_approvals_status ON stage_inspection_approvals (approval_status, inspection_date);
CREATE INDEX idx_workflow_notifications_recipient ON stage_workflow_notifications (recipient_id, recipient_type, is_read);

-- Create triggers for audit logging
DELIMITER $$

CREATE TRIGGER stage_workflow_audit_insert
AFTER INSERT ON construction_stage_workflow
FOR EACH ROW
BEGIN
  INSERT INTO stage_workflow_audit_log (
    project_id, stage_workflow_id, user_id, user_role, action, new_status, details
  ) VALUES (
    NEW.project_id, NEW.id, NEW.contractor_id, 'contractor', 'stage_workflow_created', 
    NEW.contractor_status, JSON_OBJECT('stage_name', NEW.stage_name, 'stage_order', NEW.stage_order)
  );
END$$

CREATE TRIGGER stage_workflow_audit_update
AFTER UPDATE ON construction_stage_workflow
FOR EACH ROW
BEGIN
  IF OLD.contractor_status != NEW.contractor_status OR OLD.inspection_status != NEW.inspection_status THEN
    INSERT INTO stage_workflow_audit_log (
      project_id, stage_workflow_id, user_id, user_role, action, old_status, new_status, details
    ) VALUES (
      NEW.project_id, NEW.id, 
      COALESCE(NEW.inspector_id, NEW.contractor_id), 
      CASE WHEN NEW.inspector_id IS NOT NULL THEN 'inspector' ELSE 'contractor' END,
      'stage_status_changed',
      CONCAT(OLD.contractor_status, '/', OLD.inspection_status),
      CONCAT(NEW.contractor_status, '/', NEW.inspection_status),
      JSON_OBJECT(
        'stage_name', NEW.stage_name,
        'contractor_status_changed', OLD.contractor_status != NEW.contractor_status,
        'inspection_status_changed', OLD.inspection_status != NEW.inspection_status,
        'homeowner_visible', NEW.homeowner_visible
      )
    );
  END IF;
END$$

DELIMITER ;

-- Insert default stage workflow data for new projects
-- This will be handled by the application when new projects are created