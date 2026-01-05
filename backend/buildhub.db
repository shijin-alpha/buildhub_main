-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 05, 2026 at 06:22 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `buildhub`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `action`, `user_id`, `details`, `created_at`) VALUES
(1, 'status_change', 19, '{\"old_status\":\"pending\",\"new_status\":\"approved\",\"user_name\":\"Shijin Thomas\",\"user_email\":\"thomasshijin12@gmail.com\",\"user_role\":\"homeowner\"}', '2025-08-15 11:29:50'),
(2, 'status_change', 26, '{\"old_status\":\"pending\",\"new_status\":\"approved\",\"user_name\":\"APARNA K SANTHOSH MCA2024-2026\",\"user_email\":\"aparnaksanthosh2026@mca.ajce.in\",\"user_role\":\"architect\"}', '2025-09-03 15:17:59'),
(3, 'status_change', 27, '{\"old_status\":\"pending\",\"new_status\":\"approved\",\"user_name\":\"Shijin Thomas\",\"user_email\":\"shijinthomas1501@gmail.com\",\"user_role\":\"architect\"}', '2025-09-03 15:18:01'),
(4, 'status_change', 28, '{\"old_status\":\"pending\",\"new_status\":\"approved\",\"user_name\":\"SHIJIN THOMAS MCA2024-2026\",\"user_email\":\"shijinthomas2026@mca.ajce.in\",\"user_role\":\"homeowner\"}', '2025-09-03 15:18:02'),
(5, 'status_change', 29, '{\"old_status\":\"pending\",\"new_status\":\"approved\",\"user_name\":\"Shijin Thomas\",\"user_email\":\"shijinthomas248@gmail.com\",\"user_role\":\"contractor\"}', '2025-09-03 15:18:04'),
(6, 'status_change', 30, '{\"old_status\":\"pending\",\"new_status\":\"approved\",\"user_name\":\"Fathima Shibu\",\"user_email\":\"fathima470077@gmail.com\",\"user_role\":\"homeowner\"}', '2025-09-03 15:18:05'),
(7, 'status_change', 32, '{\"old_status\":\"approved\",\"new_status\":\"suspended\",\"user_name\":\"Amal Samuel\",\"user_email\":\"thomasshijin90@gmail.com\",\"user_role\":\"homeowner\",\"schema_has_status\":true}', '2025-09-19 08:10:36'),
(8, 'status_change', 32, '{\"old_status\":\"suspended\",\"new_status\":\"approved\",\"user_name\":\"Amal Samuel\",\"user_email\":\"thomasshijin90@gmail.com\",\"user_role\":\"homeowner\",\"schema_has_status\":true}', '2025-09-19 08:11:33'),
(9, 'status_change', 34, '{\"old_status\":\"approved\",\"new_status\":\"suspended\",\"user_name\":\"Savio Joseph\",\"user_email\":\"saviojoseph2026@mca.ajce.in\",\"user_role\":\"architect\",\"schema_has_status\":true}', '2025-09-25 04:36:43'),
(10, 'status_change', 34, '{\"old_status\":\"suspended\",\"new_status\":\"approved\",\"user_name\":\"Savio Joseph\",\"user_email\":\"saviojoseph2026@mca.ajce.in\",\"user_role\":\"architect\",\"schema_has_status\":true}', '2025-09-25 04:36:45');

-- --------------------------------------------------------

--
-- Table structure for table `architect_layouts`
--

CREATE TABLE `architect_layouts` (
  `id` int(11) NOT NULL,
  `architect_id` int(11) NOT NULL,
  `layout_request_id` int(11) NOT NULL,
  `design_type` enum('custom','template') NOT NULL,
  `description` text NOT NULL,
  `layout_file` varchar(255) DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `architect_request_details`
-- (See below for the actual view)
--
CREATE TABLE `architect_request_details` (
`id` int(11)
,`user_id` int(11)
,`homeowner_id` int(11)
,`plot_size` varchar(100)
,`budget_range` varchar(100)
,`location` varchar(255)
,`timeline` varchar(100)
,`num_floors` varchar(10)
,`preferred_style` varchar(100)
,`orientation` varchar(255)
,`site_considerations` text
,`material_preferences` text
,`budget_allocation` varchar(255)
,`site_images` text
,`reference_images` text
,`room_images` text
,`floor_rooms` text
,`requirements` text
,`status` enum('pending','approved','rejected','active','accepted','declined','deleted')
,`layout_type` enum('custom','library')
,`selected_layout_id` int(11)
,`layout_file` varchar(255)
,`created_at` timestamp
,`updated_at` timestamp
,`first_name` varchar(100)
,`last_name` varchar(100)
,`email` varchar(255)
,`phone` varchar(20)
,`address` text
,`city` varchar(100)
,`state` varchar(50)
);

-- --------------------------------------------------------

--
-- Table structure for table `architect_reviews`
--

CREATE TABLE `architect_reviews` (
  `id` int(11) NOT NULL,
  `architect_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `design_id` int(11) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `architect_reviews`
--

INSERT INTO `architect_reviews` (`id`, `architect_id`, `homeowner_id`, `design_id`, `rating`, `comment`, `created_at`) VALUES
(1, 27, 30, 6, 5, 'very good', '2025-09-01 16:18:06'),
(2, 27, 30, 6, 5, 'very good', '2025-09-01 16:21:01'),
(3, 27, 30, 6, 5, 'very good', '2025-09-01 16:23:25'),
(4, 27, 30, 7, 5, 'it was very good and thank you for your service', '2025-09-07 07:19:46'),
(5, 27, 30, 8, 5, 'Thank you for the service', '2025-09-14 11:24:44'),
(6, 27, 30, 8, 5, 'thankyou', '2025-09-14 11:24:53'),
(7, 31, 30, 9, 5, 'thankyou', '2025-09-14 11:46:54'),
(8, 27, 30, 8, 5, 'thankyou', '2025-09-14 14:41:48'),
(9, 27, 19, 14, 5, 'Thankyou for the service', '2025-09-22 01:05:00'),
(10, 27, 28, 19, 5, 'Thankyou for your service', '2025-09-25 15:26:15'),
(11, 1, 1, 1, 5, 'Excellent work! The architect was very professional and delivered exactly what we wanted. Highly recommended!', '2025-10-21 07:34:25'),
(12, 1, 1, 2, 4, 'Good design and timely delivery. Would work with them again.', '2025-10-21 07:34:25'),
(13, 2, 1, 3, 5, 'Outstanding commercial design. The architect understood our business needs perfectly.', '2025-10-21 07:34:25'),
(14, 2, 1, 4, 4, 'Professional service and creative solutions for our office space.', '2025-10-21 07:34:25'),
(15, 3, 1, 5, 5, 'Beautiful traditional design that respects our cultural heritage.', '2025-10-21 07:34:25'),
(16, 3, 1, 6, 3, 'Good work but communication could be better.', '2025-10-21 07:34:25'),
(17, 4, 1, 7, 5, 'Amazing eco-friendly design! The architect was very knowledgeable about sustainable architecture.', '2025-10-21 07:34:25'),
(18, 4, 1, 8, 4, 'Great modern design with green features. Very satisfied with the result.', '2025-10-21 07:34:25'),
(19, 5, 1, 9, 5, 'Exceptional industrial design. The architect handled our complex requirements perfectly.', '2025-10-21 07:34:25'),
(20, 5, 1, 10, 4, 'Good commercial design. Professional and reliable service.', '2025-10-21 07:34:25'),
(21, 1, 1, 1, 5, 'Excellent work! The architect was very professional and delivered exactly what we wanted. Highly recommended!', '2025-10-21 07:40:52'),
(22, 1, 1, 2, 4, 'Good design and timely delivery. Would work with them again.', '2025-10-21 07:40:52'),
(23, 2, 1, 3, 5, 'Outstanding commercial design. The architect understood our business needs perfectly.', '2025-10-21 07:40:52'),
(24, 2, 1, 4, 4, 'Professional service and creative solutions for our office space.', '2025-10-21 07:40:52'),
(25, 3, 1, 5, 5, 'Beautiful traditional design that respects our cultural heritage.', '2025-10-21 07:40:52'),
(26, 3, 1, 6, 3, 'Good work but communication could be better.', '2025-10-21 07:40:52'),
(27, 4, 1, 7, 5, 'Amazing eco-friendly design! The architect was very knowledgeable about sustainable architecture.', '2025-10-21 07:40:52'),
(28, 4, 1, 8, 4, 'Great modern design with green features. Very satisfied with the result.', '2025-10-21 07:40:52'),
(29, 5, 1, 9, 5, 'Exceptional industrial design. The architect handled our complex requirements perfectly.', '2025-10-21 07:40:52'),
(30, 5, 1, 10, 4, 'Good commercial design. Professional and reliable service.', '2025-10-21 07:40:52'),
(31, 27, 28, 22, 4, 'good', '2025-10-21 10:33:14');

-- --------------------------------------------------------

--
-- Table structure for table `construction_phases`
--

CREATE TABLE `construction_phases` (
  `id` int(11) NOT NULL,
  `phase_name` varchar(100) NOT NULL,
  `phase_order` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `typical_duration_days` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `construction_phases`
--

INSERT INTO `construction_phases` (`id`, `phase_name`, `phase_order`, `description`, `typical_duration_days`, `created_at`) VALUES
(1, 'Site Preparation', 1, 'Land clearing, excavation, and site setup', 7, '2026-01-05 15:00:17'),
(2, 'Foundation', 2, 'Foundation excavation, concrete work, and curing', 14, '2026-01-05 15:00:17'),
(3, 'Structure', 3, 'Column, beam, and slab construction', 21, '2026-01-05 15:00:17'),
(4, 'Brickwork', 4, 'Wall construction and masonry work', 18, '2026-01-05 15:00:17'),
(5, 'Roofing', 5, 'Roof structure and covering installation', 10, '2026-01-05 15:00:17'),
(6, 'Electrical', 6, 'Electrical wiring and installations', 12, '2026-01-05 15:00:17'),
(7, 'Plumbing', 7, 'Plumbing installations and pipe work', 10, '2026-01-05 15:00:17'),
(8, 'Finishing', 8, 'Plastering, painting, and final touches', 15, '2026-01-05 15:00:17'),
(9, 'Flooring', 9, 'Floor installation and finishing', 8, '2026-01-05 15:00:17'),
(10, 'Final Inspection', 10, 'Quality checks and handover preparation', 3, '2026-01-05 15:00:17');

-- --------------------------------------------------------

--
-- Table structure for table `construction_progress_updates`
--

CREATE TABLE `construction_progress_updates` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `stage_name` varchar(100) NOT NULL,
  `stage_status` enum('Not Started','In Progress','Completed') NOT NULL,
  `completion_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `delay_reason` varchar(100) DEFAULT NULL,
  `delay_description` text DEFAULT NULL,
  `photo_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photo_paths`)),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `construction_stage_payments`
--

CREATE TABLE `construction_stage_payments` (
  `id` int(11) NOT NULL,
  `stage_name` varchar(100) NOT NULL,
  `stage_order` int(11) NOT NULL,
  `typical_percentage` decimal(5,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `construction_stage_payments`
--

INSERT INTO `construction_stage_payments` (`id`, `stage_name`, `stage_order`, `typical_percentage`, `description`, `created_at`) VALUES
(1, 'Site Preparation', 1, 5.00, 'Initial site setup, clearing, and preparation work', '2026-01-05 15:46:43'),
(2, 'Foundation', 2, 20.00, 'Foundation excavation, concrete work, and structural base', '2026-01-05 15:46:43'),
(3, 'Structure', 3, 25.00, 'Main structural work including columns, beams, and slabs', '2026-01-05 15:46:43'),
(4, 'Brickwork', 4, 15.00, 'Wall construction and masonry work', '2026-01-05 15:46:43'),
(5, 'Roofing', 5, 10.00, 'Roof structure and covering installation', '2026-01-05 15:46:43'),
(6, 'Electrical', 6, 8.00, 'Electrical wiring and installations', '2026-01-05 15:46:43'),
(7, 'Plumbing', 7, 7.00, 'Plumbing installations and pipe work', '2026-01-05 15:46:43'),
(8, 'Finishing', 8, 8.00, 'Plastering, painting, and interior finishing', '2026-01-05 15:46:43'),
(9, 'Final Inspection', 9, 2.00, 'Quality checks, cleanup, and project handover', '2026-01-05 15:46:43');

-- --------------------------------------------------------

--
-- Table structure for table `contractor_assignments`
--

CREATE TABLE `contractor_assignments` (
  `id` int(11) NOT NULL,
  `layout_request_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `status` enum('assigned','accepted','rejected','completed') DEFAULT 'assigned',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contractor_assignment_hides`
--

CREATE TABLE `contractor_assignment_hides` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `hidden_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contractor_assignment_hides`
--

INSERT INTO `contractor_assignment_hides` (`id`, `assignment_id`, `contractor_id`, `hidden_at`) VALUES
(1, 4, 29, '2025-09-21 07:59:30'),
(2, 6, 29, '2025-09-21 07:59:34'),
(3, 5, 29, '2025-09-21 07:59:38'),
(4, 8, 29, '2025-09-21 07:59:41'),
(5, 7, 29, '2025-09-21 07:59:44');

-- --------------------------------------------------------

--
-- Table structure for table `contractor_estimate_payments`
--

CREATE TABLE `contractor_estimate_payments` (
  `id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `estimate_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'INR',
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `razorpay_order_id` varchar(255) DEFAULT NULL,
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contractor_estimate_payments`
--

INSERT INTO `contractor_estimate_payments` (`id`, `homeowner_id`, `estimate_id`, `amount`, `currency`, `payment_status`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `created_at`, `updated_at`) VALUES
(1, 28, 24, 100.00, 'INR', 'completed', 'order_RU4ZVnWZqBzOKD', 'pay_RU4Zghdf0713db', 'f7b3e7b5c457ebfb152ea45394996f80686a3a484e5d14bb0363d89ae683015d', '2025-10-16 08:07:21', '2025-10-16 08:07:44'),
(2, 28, 23, 100.00, 'INR', 'pending', 'order_RUPLuNInB8T5t7', NULL, NULL, '2025-10-17 04:27:03', '2025-10-17 04:27:03'),
(3, 28, 23, 100.00, 'INR', 'pending', 'order_RUPLz2tVm72vVw', NULL, NULL, '2025-10-17 04:27:08', '2025-10-17 04:27:08'),
(4, 28, 23, 100.00, 'INR', 'pending', 'order_RUPM026os9UPez', NULL, NULL, '2025-10-17 04:27:09', '2025-10-17 04:27:09'),
(5, 28, 23, 100.00, 'INR', 'pending', 'order_RUPM938N3JrLkg', NULL, NULL, '2025-10-17 04:27:17', '2025-10-17 04:27:17'),
(6, 28, 23, 100.00, 'INR', 'completed', 'order_RUPMAqBPPW7d3g', 'pay_RUPMLP1qU84Keo', 'aba39dcaab8f1e203fea5f9a6d1cbfaa2a63bb739c639bd27c4b3f756c73c469', '2025-10-17 04:27:18', '2025-10-17 04:27:41'),
(7, 28, 29, 100.00, 'INR', 'completed', 'order_RYCR5ty0D6TYJs', 'pay_RYCRjYXFJlUXRe', '5bc87cca642a3a65d6d996a4ca52e6f43cb0d215244105ece379df912a7d4b28', '2025-10-26 18:24:58', '2025-10-26 18:26:05');

-- --------------------------------------------------------

--
-- Table structure for table `contractor_inbox`
--

CREATE TABLE `contractor_inbox` (
  `id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `estimate_id` int(11) DEFAULT NULL,
  `type` enum('layout_request','construction_start','estimate_response','general') DEFAULT 'layout_request',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('unread','read','acknowledged') DEFAULT 'unread',
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `homeowner_name` varchar(255) DEFAULT NULL,
  `homeowner_email` varchar(255) DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contractor_layout_sends`
--

CREATE TABLE `contractor_layout_sends` (
  `id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `homeowner_id` int(11) DEFAULT NULL,
  `layout_id` int(11) DEFAULT NULL,
  `design_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `acknowledged_at` datetime DEFAULT NULL,
  `due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contractor_layout_sends`
--

INSERT INTO `contractor_layout_sends` (`id`, `contractor_id`, `homeowner_id`, `layout_id`, `design_id`, `message`, `payload`, `created_at`, `acknowledged_at`, `due_date`) VALUES
(3, 37, 28, NULL, NULL, NULL, '{\"layout_id\":null,\"design_id\":null,\"message\":null,\"forwarded_design\":{\"id\":21,\"title\":\"nbn\",\"description\":\"\",\"files\":[{\"original\":\"4.png\",\"stored\":\"68e51548c6d1c5.76863274_1759843656.png\",\"ext\":\"png\",\"path\":\"/buildhub/backend/uploads/designs/68e51548c6d1c5.76863274_1759843656.png\"}],\"technical_details\":{\"floor_plans\":{\"living_room_dimensions\":\"24 × 18 ft\",\"master_bedroom_dimensions\":\"18 × 14 ft\"},\"structural\":{\"foundation_outline\":\"Isolated footings; basement optional\",\"roof_outline\":\"Flat + partial sloped accents; terrace deck\"},\"construction\":{\"wall_thickness\":\"External 250–300 mm with high insulation; internal 115–150 mm\",\"ceiling_heights\":\"Ground 3.4 m; Upper 3.2 m\",\"building_codes\":\"High energy performance; local villa standards\",\"critical_instructions\":\"Provision for home automation and solar PV\"},\"meta\":{\"building_type\":\"residential\"},\"elevations\":{\"front_elevation\":\"Monolithic volumes; concealed gutters; frameless corners\",\"height_details\":\"Clear height 3.0 m; floor-to-floor 3.2 m\"}},\"created_at\":\"2025-10-07 18:57:36\"},\"layout_image_url\":null}', '2025-10-07 16:03:31', '2025-10-07 21:33:55', '2025-10-16'),
(7, 37, 28, NULL, NULL, NULL, '{\"layout_id\":null,\"design_id\":null,\"message\":null,\"forwarded_design\":{\"id\":22,\"title\":\"hgvv\",\"description\":\"\",\"files\":[{\"original\":\"2.png\",\"stored\":\"68ef98b6b90964.28887100_1760532662.png\",\"ext\":\"png\",\"path\":\"/buildhub/backend/uploads/designs/68ef98b6b90964.28887100_1760532662.png\"}],\"technical_details\":{\"floor_plans\":{\"living_room_dimensions\":\"20 × 15 ft\",\"master_bedroom_dimensions\":\"16 × 12 ft\",\"layout_description\":\" jhg\",\"kitchen_dimensions\":\"12 × 10 ft\"},\"structural\":{\"load_bearing_walls\":\"Reinforced concrete walls at cores; 200 mm slabs\",\"column_positions\":\"8 m grid; edge columns 300×600 mm\",\"foundation_outline\":\"Isolated footings; M30 concrete\",\"roof_outline\":\"Flat RCC slab with insulation\"},\"construction\":{\"wall_thickness\":\"External 230 mm RCC + insulation + plaster; Internal 115 mm block\",\"ceiling_heights\":\"Living 3.1 m; Bedrooms 3.0 m; Kitchen 2.9 m\",\"building_codes\":\"IBC 2021 / IS 456 as applicable\",\"critical_instructions\":\"Use Fe500 rebars; cover as per exposure class XC2\"},\"meta\":{\"building_type\":\"residential\"},\"elevations\":{\"front_elevation\":\"Monolithic volumes; concealed gutters; frameless corners\",\"height_details\":\"Clear height 3.0 m; floor-to-floor 3.2 m\"}},\"created_at\":\"2025-10-15 18:21:02\"},\"layout_image_url\":null,\"floor_details\":null}', '2025-10-21 10:32:49', NULL, NULL),
(10, 51, 48, 99, NULL, 'Project for 3BHK modern house in Bangalore. Please provide detailed estimate.', NULL, '2025-12-21 08:25:23', '2025-12-16 09:25:23', NULL),
(11, 52, 49, 100, NULL, 'Traditional 4BHK house project in Mumbai. Vastu compliant design required.', NULL, '2025-12-21 08:25:23', '2025-12-18 09:25:23', NULL),
(12, 53, 50, 101, NULL, 'Compact 2BHK house project in Delhi. Space optimization is key.', NULL, '2025-12-21 08:25:23', '2025-12-14 09:25:23', NULL),
(13, 51, 48, 99, NULL, 'Project for 3BHK modern house in Bangalore. Please provide detailed estimate.', NULL, '2025-12-30 06:04:26', '2025-12-25 07:04:26', NULL),
(14, 52, 49, 100, NULL, 'Traditional 4BHK house project in Mumbai. Vastu compliant design required.', NULL, '2025-12-30 06:04:26', '2025-12-27 07:04:26', NULL),
(15, 53, 50, 101, NULL, 'Compact 2BHK house project in Delhi. Space optimization is key.', NULL, '2025-12-30 06:04:26', '2025-12-23 07:04:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contractor_proposals`
--

CREATE TABLE `contractor_proposals` (
  `id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `layout_request_id` int(11) NOT NULL,
  `materials` text NOT NULL,
  `cost_breakdown` text NOT NULL,
  `total_cost` decimal(12,2) NOT NULL,
  `timeline` varchar(100) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contractor_requests_queue`
--

CREATE TABLE `contractor_requests_queue` (
  `id` int(11) NOT NULL,
  `layout_request_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `timeline` varchar(100) DEFAULT NULL,
  `share_contact` tinyint(1) DEFAULT 1,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contractor_reviews`
--

CREATE TABLE `contractor_reviews` (
  `id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `layout_request_id` int(11) DEFAULT NULL,
  `rating` tinyint(1) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contractor_send_estimates`
--

CREATE TABLE `contractor_send_estimates` (
  `id` int(11) NOT NULL,
  `send_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `materials` text DEFAULT NULL,
  `cost_breakdown` text DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `timeline` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(32) DEFAULT 'submitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `structured` longtext DEFAULT NULL,
  `homeowner_feedback` text DEFAULT NULL,
  `homeowner_action_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contractor_send_estimates`
--

INSERT INTO `contractor_send_estimates` (`id`, `send_id`, `contractor_id`, `materials`, `cost_breakdown`, `total_cost`, `timeline`, `notes`, `status`, `created_at`, `structured`, `homeowner_feedback`, `homeowner_action_at`) VALUES
(23, 2, 29, NULL, NULL, NULL, '6 months', NULL, 'deleted', '2025-10-20 08:38:43', '{\"project_name\":\"Commercial Complex\",\"project_address\":\"\",\"plot_size\":\"\",\"built_up_area\":\"\",\"floors\":\"\",\"estimation_date\":\"\",\"client_name\":\"\",\"client_contact\":\"\",\"materials\":{\"cement\":{\"name\":\"OPC 43 grade - 50 bags\",\"qty\":\"50\",\"rate\":\"380\",\"amount\":\"19000\"},\"sand\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"bricks\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"steel\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"aggregate\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"tiles\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"paint\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"doors\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"windows\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"labor\":{\"mason\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plaster\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"painting\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plumbing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"flooring\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"roofing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"utilities\":{\"sanitary\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"kitchen\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical_fixtures\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"water_tank\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"hvac\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"gas_water\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"misc\":{\"transport\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"contingency\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"fees\":{\"name\":\"\",\"amount\":\"\"},\"cleaning\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"safety\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"totals\":{\"materials\":\"19000\",\"labor\":\"\",\"utilities\":\"\",\"misc\":\"\",\"grand\":\"19000\"},\"brands\":\"\"}', 'I am satisfied with this estimate and ready to start the construction project. Please let me know the next steps and when we can begin work.', '2025-10-21 10:42:37'),
(24, 2, 29, NULL, NULL, NULL, '6 months', NULL, 'deleted', '2025-10-15 17:23:16', '{\"project_name\":\"Residential Villa\",\"project_address\":\"\",\"plot_size\":\"\",\"built_up_area\":\"\",\"floors\":\"\",\"estimation_date\":\"\",\"client_name\":\"\",\"client_contact\":\"\",\"materials\":{\"cement\":{\"name\":\"OPC 43 grade - 50 bags\",\"qty\":\"50\",\"rate\":\"380\",\"amount\":\"19000\"},\"sand\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"bricks\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"steel\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"aggregate\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"tiles\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"paint\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"doors\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"windows\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"labor\":{\"mason\":{\"name\":\"Masonry - \\u20b9\\/m\\u00b3\",\"qty\":\"5\",\"rate\":\"90\",\"amount\":\"450\"},\"plaster\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"painting\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plumbing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"flooring\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"roofing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"utilities\":{\"sanitary\":{\"name\":\"WC, basin, shower set\",\"qty\":\"6\",\"rate\":\"9000\",\"amount\":\"54000\"},\"kitchen\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical_fixtures\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"water_tank\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"hvac\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"gas_water\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"misc\":{\"transport\":{\"name\":\"Material transport local\",\"qty\":\"50\",\"rate\":\"50\",\"amount\":\"2500\"},\"contingency\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"fees\":{\"name\":\"\",\"amount\":\"\"},\"cleaning\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"safety\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"totals\":{\"materials\":\"19000\",\"labor\":\"450\",\"utilities\":\"54000\",\"misc\":\"2500\",\"grand\":\"75950\"},\"brands\":\"\"}', 'I am satisfied with this estimate and ready to start the construction project. Please let me know the next steps and when we can begin work.', '2025-10-21 12:20:38'),
(25, 6, 29, 'Cement, Steel, Bricks, Tiles, Paint, Electrical fixtures', 'Materials: ₹35L, Labor: ₹20L, Utilities: ₹3L, Misc: ₹2L', 6000000.00, '8-10 months', 'High-quality construction with modern amenities', 'deleted', '2025-10-20 10:00:07', '{\"project_name\":\"Modern Family Home\",\"totals\":{\"materials\":3500000,\"labor\":2000000,\"utilities\":300000,\"misc\":200000,\"grand\":6000000}}', NULL, NULL),
(26, 9, 29, NULL, NULL, NULL, '6 months', NULL, 'deleted', '2025-10-26 18:22:46', '{\"project_name\":\"gf\",\"project_address\":\"\",\"plot_size\":\"\",\"built_up_area\":\"\",\"floors\":\"\",\"estimation_date\":\"\",\"client_name\":\"\",\"client_contact\":\"\",\"materials\":{\"cement\":{\"name\":\"OPC 43 grade - 50 bags\",\"qty\":\"50\",\"rate\":\"380\",\"amount\":\"19000\"},\"sand\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"bricks\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"steel\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"aggregate\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"tiles\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"paint\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"doors\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"windows\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"labor\":{\"mason\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plaster\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"painting\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plumbing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"flooring\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"roofing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"utilities\":{\"sanitary\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"kitchen\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical_fixtures\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"water_tank\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"hvac\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"gas_water\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"misc\":{\"transport\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"contingency\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"fees\":{\"name\":\"\",\"amount\":\"\"},\"cleaning\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"safety\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"totals\":{\"materials\":\"19000\",\"labor\":\"\",\"utilities\":\"\",\"misc\":\"\",\"grand\":\"19000\"},\"brands\":\"\"}', NULL, NULL),
(27, 9, 29, NULL, NULL, NULL, '6 months', NULL, 'deleted', '2025-10-26 18:22:56', '{\"project_name\":\"gf\",\"project_address\":\"\",\"plot_size\":\"\",\"built_up_area\":\"\",\"floors\":\"\",\"estimation_date\":\"\",\"client_name\":\"\",\"client_contact\":\"\",\"materials\":{\"cement\":{\"name\":\"OPC 43 grade - 50 bags\",\"qty\":\"50\",\"rate\":\"380\",\"amount\":\"19000\"},\"sand\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"bricks\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"steel\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"aggregate\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"tiles\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"paint\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"doors\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"windows\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"labor\":{\"mason\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plaster\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"painting\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plumbing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"flooring\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"roofing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"utilities\":{\"sanitary\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"kitchen\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical_fixtures\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"water_tank\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"hvac\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"gas_water\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"misc\":{\"transport\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"contingency\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"fees\":{\"name\":\"\",\"amount\":\"\"},\"cleaning\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"safety\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"totals\":{\"materials\":\"19000\",\"labor\":\"\",\"utilities\":\"\",\"misc\":\"\",\"grand\":\"19000\"},\"brands\":\"\"}', NULL, NULL),
(28, 9, 29, NULL, NULL, NULL, '6 months', NULL, 'deleted', '2025-10-26 18:23:02', '{\"project_name\":\"gf\",\"project_address\":\"\",\"plot_size\":\"\",\"built_up_area\":\"\",\"floors\":\"\",\"estimation_date\":\"\",\"client_name\":\"\",\"client_contact\":\"\",\"materials\":{\"cement\":{\"name\":\"OPC 43 grade - 50 bags\",\"qty\":\"50\",\"rate\":\"380\",\"amount\":\"19000\"},\"sand\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"bricks\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"steel\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"aggregate\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"tiles\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"paint\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"doors\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"windows\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"labor\":{\"mason\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plaster\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"painting\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plumbing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"flooring\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"roofing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"utilities\":{\"sanitary\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"kitchen\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical_fixtures\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"water_tank\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"hvac\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"gas_water\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"misc\":{\"transport\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"contingency\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"fees\":{\"name\":\"\",\"amount\":\"\"},\"cleaning\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"safety\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"totals\":{\"materials\":\"19000\",\"labor\":\"\",\"utilities\":\"\",\"misc\":\"\",\"grand\":\"19000\"},\"brands\":\"\"}', NULL, NULL),
(29, 9, 29, NULL, NULL, NULL, '6 months', NULL, 'deleted', '2025-10-26 18:24:17', '{\"project_name\":\"Residential Villa\",\"project_address\":\"\",\"plot_size\":\"2800\",\"built_up_area\":\"\",\"floors\":\"\",\"estimation_date\":\"\",\"client_name\":\"\",\"client_contact\":\"\",\"materials\":{\"cement\":{\"name\":\"OPC 43 grade - 50 bags\",\"qty\":\"50\",\"rate\":\"380\",\"amount\":\"19000\"},\"sand\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"bricks\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"steel\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"aggregate\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"tiles\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"paint\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"doors\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"windows\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"labor\":{\"mason\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plaster\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"painting\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plumbing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"flooring\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"roofing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"utilities\":{\"sanitary\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"kitchen\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical_fixtures\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"water_tank\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"hvac\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"gas_water\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"misc\":{\"transport\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"contingency\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"fees\":{\"name\":\"\",\"amount\":\"\"},\"cleaning\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"safety\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"totals\":{\"materials\":\"19000\",\"labor\":\"\",\"utilities\":\"\",\"misc\":\"\",\"grand\":\"19000\"},\"brands\":\"\"}', 'I am satisfied with this estimate and ready to start the construction project. Please let me know the next steps and when we can begin work.', '2025-10-26 23:56:12'),
(30, 10, 51, 'Cement: 200 bags, Steel: 2 tons, Bricks: 15000 pieces, Sand: 100 cubic feet, Aggregate: 150 cubic feet', 'Foundation: ₹3,00,000, Structure: ₹5,00,000, Brickwork: ₹2,50,000, Roofing: ₹2,00,000, Electrical: ₹1,50,000, Plumbing: ₹1,00,000, Finishing: ₹2,00,000', 1700000.00, '7 months', 'Complete construction with all modern amenities. Includes electrical, plumbing, and basic finishing work.', 'accepted', '2025-12-21 08:25:23', NULL, NULL, NULL),
(31, 11, 52, 'Cement: 300 bags, Steel: 3 tons, Bricks: 25000 pieces, Sand: 150 cubic feet, Aggregate: 200 cubic feet, Marble: 2000 sq ft', 'Foundation: ₹4,50,000, Structure: ₹7,50,000, Brickwork: ₹4,00,000, Roofing: ₹3,00,000, Electrical: ₹2,50,000, Plumbing: ₹2,00,000, Finishing: ₹4,50,000', 2800000.00, '9 months', 'Traditional design with vastu compliance. Premium materials and finishes included.', 'accepted', '2025-12-21 08:25:23', NULL, NULL, NULL),
(32, 12, 53, 'Cement: 120 bags, Steel: 1.5 tons, Bricks: 10000 pieces, Sand: 75 cubic feet, Aggregate: 100 cubic feet', 'Foundation: ₹2,00,000, Structure: ₹3,50,000, Brickwork: ₹1,50,000, Roofing: ₹1,50,000, Electrical: ₹1,00,000, Plumbing: ₹75,000, Finishing: ₹1,75,000', 1200000.00, '5 months', 'Compact and efficient design with space optimization. All basic amenities included.', 'accepted', '2025-12-21 08:25:23', NULL, NULL, NULL),
(33, 10, 51, 'Cement: 200 bags, Steel: 2 tons, Bricks: 15000 pieces, Sand: 100 cubic feet, Aggregate: 150 cubic feet', 'Foundation: ₹3,00,000, Structure: ₹5,00,000, Brickwork: ₹2,50,000, Roofing: ₹2,00,000, Electrical: ₹1,50,000, Plumbing: ₹1,00,000, Finishing: ₹2,00,000', 1700000.00, '7 months', 'Complete construction with all modern amenities. Includes electrical, plumbing, and basic finishing work.', 'accepted', '2025-12-30 06:04:26', NULL, NULL, NULL),
(34, 11, 52, 'Cement: 300 bags, Steel: 3 tons, Bricks: 25000 pieces, Sand: 150 cubic feet, Aggregate: 200 cubic feet, Marble: 2000 sq ft', 'Foundation: ₹4,50,000, Structure: ₹7,50,000, Brickwork: ₹4,00,000, Roofing: ₹3,00,000, Electrical: ₹2,50,000, Plumbing: ₹2,00,000, Finishing: ₹4,50,000', 2800000.00, '9 months', 'Traditional design with vastu compliance. Premium materials and finishes included.', 'accepted', '2025-12-30 06:04:26', NULL, NULL, NULL),
(35, 12, 53, 'Cement: 120 bags, Steel: 1.5 tons, Bricks: 10000 pieces, Sand: 75 cubic feet, Aggregate: 100 cubic feet', 'Foundation: ₹2,00,000, Structure: ₹3,50,000, Brickwork: ₹1,50,000, Roofing: ₹1,50,000, Electrical: ₹1,00,000, Plumbing: ₹75,000, Finishing: ₹1,75,000', 1200000.00, '5 months', 'Compact and efficient design with space optimization. All basic amenities included.', 'accepted', '2025-12-30 06:04:26', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contractor_send_estimate_files`
--

CREATE TABLE `contractor_send_estimate_files` (
  `id` int(11) NOT NULL,
  `estimate_id` int(11) NOT NULL,
  `path` varchar(512) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `ext` varchar(16) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contractor_workers`
--

CREATE TABLE `contractor_workers` (
  `id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `worker_name` varchar(255) NOT NULL,
  `worker_type_id` int(11) NOT NULL,
  `experience_years` int(11) DEFAULT 0,
  `skill_level` enum('apprentice','junior','senior','master') DEFAULT 'junior',
  `daily_wage` decimal(8,2) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `is_main_worker` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contractor_workers`
--

INSERT INTO `contractor_workers` (`id`, `contractor_id`, `worker_name`, `worker_type_id`, `experience_years`, `skill_level`, `daily_wage`, `phone_number`, `is_available`, `is_main_worker`, `created_at`, `updated_at`) VALUES
(1, 29, 'Rajesh Kumar', 1, 5, 'senior', 850.00, '9876543210', 1, 1, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(2, 29, 'Suresh Patel', 2, 8, 'master', 900.00, '9876543211', 1, 1, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(3, 29, 'Amit Singh', 3, 6, 'senior', 950.00, '9876543212', 1, 1, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(4, 29, 'Ravi Sharma', 4, 4, 'junior', 800.00, '9876543213', 1, 1, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(5, 29, 'Deepak Yadav', 8, 7, 'senior', 820.00, '9876543214', 1, 1, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(6, 29, 'Mohan Das', 9, 3, 'junior', 520.00, '9876543215', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(7, 29, 'Prakash Jha', 10, 2, 'apprentice', 480.00, '9876543216', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(8, 29, 'Vikram Gupta', 11, 4, 'junior', 580.00, '9876543217', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(9, 29, 'Santosh Kumar', 13, 5, 'senior', 650.00, '9876543218', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(10, 29, 'Ramesh Pal', 14, 2, 'apprentice', 380.00, '9876543219', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(11, 29, 'Dinesh Roy', 15, 3, 'junior', 320.00, '9876543220', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(12, 29, 'Mukesh Sah', 14, 1, 'apprentice', 360.00, '9876543221', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(13, 29, 'Ganesh Lal', 15, 4, 'junior', 340.00, '9876543222', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(14, 29, 'Mahesh Bind', 18, 3, 'junior', 370.00, '9876543223', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(15, 29, 'Naresh Tiwari', 17, 6, 'senior', 320.00, '9876543224', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(16, 37, 'Rajesh Kumar', 1, 5, 'senior', 850.00, '9876543210', 1, 1, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(17, 37, 'Suresh Patel', 2, 8, 'master', 900.00, '9876543211', 1, 1, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(18, 37, 'Amit Singh', 3, 6, 'senior', 950.00, '9876543212', 1, 1, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(19, 37, 'Ravi Sharma', 4, 4, 'junior', 800.00, '9876543213', 1, 1, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(20, 37, 'Deepak Yadav', 8, 7, 'senior', 820.00, '9876543214', 1, 1, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(21, 37, 'Mohan Das', 9, 3, 'junior', 520.00, '9876543215', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(22, 37, 'Prakash Jha', 10, 2, 'apprentice', 480.00, '9876543216', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(23, 37, 'Vikram Gupta', 11, 4, 'junior', 580.00, '9876543217', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(24, 37, 'Santosh Kumar', 13, 5, 'senior', 650.00, '9876543218', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(25, 37, 'Ramesh Pal', 14, 2, 'apprentice', 380.00, '9876543219', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(26, 37, 'Dinesh Roy', 15, 3, 'junior', 320.00, '9876543220', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(27, 37, 'Mukesh Sah', 14, 1, 'apprentice', 360.00, '9876543221', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(28, 37, 'Ganesh Lal', 15, 4, 'junior', 340.00, '9876543222', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(29, 37, 'Mahesh Bind', 18, 3, 'junior', 370.00, '9876543223', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(30, 37, 'Naresh Tiwari', 17, 6, 'senior', 320.00, '9876543224', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(31, 51, 'Rajesh Kumar', 1, 5, 'senior', 850.00, '9876543210', 1, 1, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(32, 51, 'Suresh Patel', 2, 8, 'master', 900.00, '9876543211', 1, 1, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(33, 51, 'Amit Singh', 3, 6, 'senior', 950.00, '9876543212', 1, 1, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(34, 51, 'Ravi Sharma', 4, 4, 'junior', 800.00, '9876543213', 1, 1, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(35, 51, 'Deepak Yadav', 8, 7, 'senior', 820.00, '9876543214', 1, 1, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(36, 51, 'Mohan Das', 9, 3, 'junior', 520.00, '9876543215', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(37, 51, 'Prakash Jha', 10, 2, 'apprentice', 480.00, '9876543216', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(38, 51, 'Vikram Gupta', 11, 4, 'junior', 580.00, '9876543217', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(39, 51, 'Santosh Kumar', 13, 5, 'senior', 650.00, '9876543218', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(40, 51, 'Ramesh Pal', 14, 2, 'apprentice', 380.00, '9876543219', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(41, 51, 'Dinesh Roy', 15, 3, 'junior', 320.00, '9876543220', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(42, 51, 'Mukesh Sah', 14, 1, 'apprentice', 360.00, '9876543221', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(43, 51, 'Ganesh Lal', 15, 4, 'junior', 340.00, '9876543222', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(44, 51, 'Mahesh Bind', 18, 3, 'junior', 370.00, '9876543223', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37'),
(45, 51, 'Naresh Tiwari', 17, 6, 'senior', 320.00, '9876543224', 1, 0, '2026-01-05 15:00:37', '2026-01-05 15:00:37');

-- --------------------------------------------------------

--
-- Table structure for table `designs`
--

CREATE TABLE `designs` (
  `id` int(11) NOT NULL,
  `layout_request_id` int(11) NOT NULL,
  `architect_id` int(11) NOT NULL,
  `design_title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `design_files` text DEFAULT NULL,
  `status` enum('proposed','shortlisted','finalized') DEFAULT 'proposed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `homeowner_id` int(11) DEFAULT NULL,
  `batch_id` varchar(64) DEFAULT NULL,
  `layout_json` text DEFAULT NULL,
  `technical_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Comprehensive technical details including floor plans, site orientation, structural elements, elevations, and construction notes',
  `view_price` decimal(10,2) DEFAULT 0.00 COMMENT 'Price for homeowners to view this layout'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `design_comments`
--

CREATE TABLE `design_comments` (
  `id` int(11) NOT NULL,
  `design_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `design_comments`
--

INSERT INTO `design_comments` (`id`, `design_id`, `user_id`, `message`, `created_at`) VALUES
(1, 6, 30, 'very good', '2025-09-01 16:23:25'),
(2, 7, 30, 'it was very good and thank you for your service', '2025-09-07 07:19:46'),
(3, 8, 30, 'Thank you for the service', '2025-09-14 11:24:44'),
(4, 8, 30, 'thankyou', '2025-09-14 11:24:53'),
(5, 9, 30, 'thankyou', '2025-09-14 11:46:54'),
(6, 8, 30, 'thankyou', '2025-09-14 14:41:48'),
(7, 14, 19, 'Thankyou for the service', '2025-09-22 01:04:59'),
(8, 19, 28, 'Thankyou for your service', '2025-09-25 15:26:15'),
(9, 22, 28, 'good', '2025-10-21 10:33:14');

-- --------------------------------------------------------

--
-- Table structure for table `geo_photos`
--

CREATE TABLE `geo_photos` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `place_name` text DEFAULT NULL,
  `location_accuracy` decimal(8,2) DEFAULT NULL,
  `location_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`location_data`)),
  `photo_timestamp` timestamp NULL DEFAULT NULL,
  `upload_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_sent_to_homeowner` tinyint(1) DEFAULT 1,
  `homeowner_viewed` tinyint(1) DEFAULT 0,
  `homeowner_viewed_at` timestamp NULL DEFAULT NULL,
  `progress_update_id` int(11) DEFAULT NULL,
  `is_included_in_progress` tinyint(1) DEFAULT 0,
  `progress_association_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `homeowner_notifications`
--

CREATE TABLE `homeowner_notifications` (
  `id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'acknowledgment',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `homeowner_notifications`
--

INSERT INTO `homeowner_notifications` (`id`, `homeowner_id`, `contractor_id`, `type`, `title`, `message`, `status`, `created_at`) VALUES
(1, 28, 29, 'acknowledgment', 'Contractor Acknowledged Your Layout', 'Shijin Thomas acknowledged your layout at 2025-10-26 19:19:55.\nDue date: December 25, 2025', 'unread', '2025-10-26 18:19:55');

-- --------------------------------------------------------

--
-- Table structure for table `house_plans`
--

CREATE TABLE `house_plans` (
  `id` int(11) NOT NULL,
  `architect_id` int(11) NOT NULL,
  `layout_request_id` int(11) DEFAULT NULL,
  `plan_name` varchar(255) NOT NULL,
  `plot_width` decimal(8,2) NOT NULL,
  `plot_height` decimal(8,2) NOT NULL,
  `plan_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`plan_data`)),
  `total_area` decimal(10,2) NOT NULL,
  `status` enum('draft','submitted','approved','rejected') DEFAULT 'draft',
  `version` int(11) DEFAULT 1,
  `parent_plan_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `house_plans`
--

INSERT INTO `house_plans` (`id`, `architect_id`, `layout_request_id`, `plan_name`, `plot_width`, `plot_height`, `plan_data`, `total_area`, `status`, `version`, `parent_plan_id`, `notes`, `created_at`, `updated_at`) VALUES
(3, 27, 105, 'SHIJIN THOMAS MCA2024-2026 House Plan', 52.00, 62.00, '{\"rooms\":[{\"id\":1,\"name\":\"master bedroom\",\"type\":\"master_bedroom\",\"x\":50,\"y\":50,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"color\":\"#c8e6c9\",\"floor\":1},{\"id\":2,\"name\":\"bedrooms\",\"type\":\"bedrooms\",\"x\":190,\"y\":50,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"color\":\"#dcedc8\",\"floor\":1},{\"id\":3,\"name\":\"bathrooms\",\"type\":\"bathrooms\",\"x\":330,\"y\":50,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"color\":\"#e1f5fe\",\"floor\":1},{\"id\":4,\"name\":\"attached bathroom\",\"type\":\"attached_bathroom\",\"x\":50,\"y\":170,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"color\":\"#e3f2fd\",\"floor\":1},{\"id\":5,\"name\":\"kitchen\",\"type\":\"kitchen\",\"x\":190,\"y\":170,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"color\":\"#ffcdd2\",\"floor\":1},{\"id\":6,\"name\":\"living room\",\"type\":\"living_room\",\"x\":330,\"y\":170,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"color\":\"#ffe0b2\",\"floor\":1},{\"id\":7,\"name\":\"dining room\",\"type\":\"dining_room\",\"x\":50,\"y\":290,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"color\":\"#e1bee7\",\"floor\":1},{\"id\":8,\"name\":\"store room\",\"type\":\"store_room\",\"x\":190,\"y\":290,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"color\":\"#f5f5f5\",\"floor\":1},{\"id\":9,\"name\":\"garage\",\"type\":\"garage\",\"x\":330,\"y\":290,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"color\":\"#e3f2fd\",\"floor\":1},{\"id\":10,\"name\":\"study room\",\"type\":\"study_room\",\"x\":50,\"y\":610,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"color\":\"#e8eaf6\",\"floor\":2},{\"id\":11,\"name\":\"prayer room\",\"type\":\"prayer_room\",\"x\":190,\"y\":610,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"color\":\"#b39ddb\",\"floor\":2},{\"id\":12,\"name\":\"guest room\",\"type\":\"guest_room\",\"x\":460,\"y\":840,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"color\":\"#e3f2fd\",\"floor\":2},{\"id\":13,\"name\":\"balcony\",\"type\":\"balcony\",\"x\":760,\"y\":800,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"color\":\"#c8e6c9\",\"floor\":2},{\"id\":14,\"name\":\"terrace\",\"type\":\"terrace\",\"x\":0,\"y\":820,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"color\":\"#dcedc8\",\"floor\":2},{\"id\":15,\"name\":\"bedrooms\",\"type\":\"bedrooms\",\"x\":320,\"y\":1040,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"color\":\"#dcedc8\",\"floor\":2},{\"id\":16,\"name\":\"bathrooms\",\"type\":\"bathrooms\",\"x\":520,\"y\":1020,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"color\":\"#e1f5fe\",\"floor\":2},{\"id\":17,\"name\":\"attached bathroom\",\"type\":\"attached_bathroom\",\"x\":80,\"y\":1040,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"color\":\"#e3f2fd\",\"floor\":2}],\"scale_ratio\":1.2,\"total_layout_area\":1700,\"total_construction_area\":2448}', 2448.00, 'draft', 1, NULL, '', '2026-01-05 17:06:30', '2026-01-05 17:21:00');

-- --------------------------------------------------------

--
-- Table structure for table `house_plan_reviews`
--

CREATE TABLE `house_plan_reviews` (
  `id` int(11) NOT NULL,
  `house_plan_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected','revision_requested') NOT NULL,
  `feedback` text DEFAULT NULL,
  `reviewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `layout_library`
--

CREATE TABLE `layout_library` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `layout_type` varchar(100) NOT NULL,
  `bedrooms` int(11) NOT NULL,
  `bathrooms` int(11) NOT NULL,
  `area` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `design_file_url` varchar(500) DEFAULT NULL,
  `price_range` varchar(100) DEFAULT NULL,
  `view_price` decimal(10,2) DEFAULT 0.00,
  `technical_details` text DEFAULT NULL,
  `architect_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `floor_plans` text DEFAULT NULL,
  `room_dimensions` text DEFAULT NULL,
  `door_window_positions` text DEFAULT NULL,
  `circulation_paths` text DEFAULT NULL,
  `plot_boundaries` text DEFAULT NULL,
  `orientation_north` text DEFAULT NULL,
  `access_points` text DEFAULT NULL,
  `load_bearing_walls` text DEFAULT NULL,
  `column_positions` text DEFAULT NULL,
  `foundation_outline` text DEFAULT NULL,
  `roof_outline` text DEFAULT NULL,
  `front_elevation` text DEFAULT NULL,
  `cross_sections` text DEFAULT NULL,
  `height_details` text DEFAULT NULL,
  `wall_thickness` text DEFAULT NULL,
  `ceiling_heights` text DEFAULT NULL,
  `building_codes` text DEFAULT NULL,
  `critical_instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `layout_library`
--

INSERT INTO `layout_library` (`id`, `title`, `layout_type`, `bedrooms`, `bathrooms`, `area`, `description`, `image_url`, `design_file_url`, `price_range`, `view_price`, `technical_details`, `architect_id`, `status`, `created_at`, `updated_at`, `floor_plans`, `room_dimensions`, `door_window_positions`, `circulation_paths`, `plot_boundaries`, `orientation_north`, `access_points`, `load_bearing_walls`, `column_positions`, `foundation_outline`, `roof_outline`, `front_elevation`, `cross_sections`, `height_details`, `wall_thickness`, `ceiling_heights`, `building_codes`, `critical_instructions`) VALUES
(10, '3BHK', 'Modern', 3, 3, 2500, '', '/buildhub/backend/uploads/designs/lib_1759842787_f51c001b.jpeg', '/buildhub/backend/uploads/designs/libfile_1759842787_e8794d84.png', '80-90 laks', 0.00, NULL, 27, 'active', '2025-09-07 06:44:14', '2025-10-07 13:13:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'Modern 3BHK Hosue', 'Modern', 3, 3, 3000, '', '/buildhub/backend/uploads/designs/lib_1759842744_d4b24a9c.webp', '/buildhub/backend/uploads/designs/libfile_1759842744_a818e016.png', '85-90', 0.00, NULL, 27, 'active', '2025-09-21 16:11:24', '2025-10-26 18:15:42', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `layout_library_technical_details`
--

CREATE TABLE `layout_library_technical_details` (
  `id` int(11) NOT NULL,
  `layout_library_id` int(11) NOT NULL,
  `architect_id` int(11) NOT NULL,
  `room_layout_dimensions` text DEFAULT NULL,
  `door_window_positions` text DEFAULT NULL,
  `circulation_paths` text DEFAULT NULL,
  `plot_boundaries` text DEFAULT NULL,
  `orientation_north_direction` text DEFAULT NULL,
  `access_points` text DEFAULT NULL,
  `load_bearing_walls` text DEFAULT NULL,
  `column_positions` text DEFAULT NULL,
  `foundation_outline` text DEFAULT NULL,
  `roof_outline` text DEFAULT NULL,
  `front_elevation` text DEFAULT NULL,
  `cross_sections` text DEFAULT NULL,
  `height_details` text DEFAULT NULL,
  `wall_thickness` text DEFAULT NULL,
  `ceiling_heights` text DEFAULT NULL,
  `building_codes_compliance` text DEFAULT NULL,
  `critical_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `layout_payments`
--

CREATE TABLE `layout_payments` (
  `id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `architect_id` int(11) NOT NULL,
  `design_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'INR',
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `razorpay_order_id` varchar(255) DEFAULT NULL,
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `layout_requests`
--

CREATE TABLE `layout_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `plot_size` varchar(100) NOT NULL,
  `budget_range` varchar(100) NOT NULL,
  `requirements` text DEFAULT NULL,
  `preferred_style` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected','active','accepted','declined','deleted') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `location` varchar(255) DEFAULT NULL,
  `timeline` varchar(100) DEFAULT NULL,
  `selected_layout_id` int(11) DEFAULT NULL,
  `layout_type` enum('custom','library') NOT NULL DEFAULT 'custom',
  `layout_file` varchar(255) DEFAULT NULL,
  `site_images` text DEFAULT NULL COMMENT 'JSON array of site images with file paths and metadata',
  `reference_images` text DEFAULT NULL COMMENT 'JSON array of reference images with file paths and metadata',
  `room_images` text DEFAULT NULL COMMENT 'JSON object of room-specific images with file paths and metadata',
  `orientation` varchar(255) DEFAULT NULL COMMENT 'Site orientation preferences',
  `site_considerations` text DEFAULT NULL COMMENT 'Additional site considerations and notes',
  `material_preferences` text DEFAULT NULL COMMENT 'Material preferences as comma-separated values',
  `budget_allocation` varchar(255) DEFAULT NULL COMMENT 'Budget allocation preferences',
  `floor_rooms` text DEFAULT NULL COMMENT 'JSON object of floor-wise room planning',
  `num_floors` varchar(10) DEFAULT NULL COMMENT 'Number of floors requested',
  `building_size` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `layout_requests`
--

INSERT INTO `layout_requests` (`id`, `user_id`, `homeowner_id`, `plot_size`, `budget_range`, `requirements`, `preferred_style`, `status`, `created_at`, `updated_at`, `location`, `timeline`, `selected_layout_id`, `layout_type`, `layout_file`, `site_images`, `reference_images`, `room_images`, `orientation`, `site_considerations`, `material_preferences`, `budget_allocation`, `floor_rooms`, `num_floors`, `building_size`) VALUES
(62, 28, 28, '3000', '9000000', '{\"plot_shape\":\"hvh\",\"topography\":\"jbjbj\",\"development_laws\":\"bb\",\"family_needs\":\"jbbj\",\"rooms\":\"3\",\"aesthetic\":\"vv\",\"notes\":\"\"}', NULL, 'deleted', '2025-09-22 08:25:49', '2025-10-02 16:43:05', 'Kottakkal', '12-18 months', NULL, 'custom', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(66, 28, 28, '2100', '5500000', '{\"plot_shape\":\"Rectangular\",\"topography\":\"Flat\",\"development_laws\":\"nil\",\"family_needs\":\"nothing special\",\"rooms\":\"3\",\"aesthetic\":\"modern\",\"notes\":\"\"}', NULL, 'deleted', '2025-09-25 15:14:06', '2025-10-02 16:43:09', 'Kollam', '12-18 months', NULL, 'custom', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(86, 28, 28, '3000', '75 Lakhs - 1 Crore', '{\"plot_shape\":\"Rectangular\",\"topography\":\"Slightly Sloped\",\"development_laws\":\"\",\"family_needs\":\"\",\"rooms\":\"\",\"aesthetic\":\"Traditional\",\"notes\":\"\",\"orientation\":\"South-facing\",\"site_considerations\":\"\",\"material_preferences\":\"Marble, Vitrified Tiles\",\"budget_allocation\":\"Balanced approach\",\"num_floors\":\"2\",\"preferred_style\":\"Traditional\",\"floor_rooms\":\"{\\\"floor1\\\":{\\\"master_bedroom\\\":1,\\\"bedrooms\\\":1,\\\"attached_bathrooms\\\":1,\\\"common_bathrooms\\\":1,\\\"living_room\\\":1},\\\"floor2\\\":{\\\"master_bedroom\\\":1,\\\"bedrooms\\\":1,\\\"attached_bathrooms\\\":1,\\\"common_bathrooms\\\":1}}\",\"site_images\":[{\"id\":\"68deacff07dac\",\"file\":null,\"name\":\"11.webp\",\"size\":42242,\"url\":\"\\/buildhub\\/backend\\/uploads\\/site_images\\/28_68deacff07b1f.webp\"}],\"reference_images\":[],\"room_images\":{\"floor1\":{\"master_bedroom\":[{\"id\":\"68deacbd684ca\",\"name\":\"master bed.jpg\",\"size\":42453,\"url\":\"\\/buildhub\\/backend\\/uploads\\/room_images\\/28_68deacbd67dce.jpg\",\"floor\":1}]}}}', 'Traditional', 'deleted', '2025-10-02 16:49:17', '2025-12-30 07:44:39', 'Kottayam', '12-18 months', NULL, 'custom', NULL, '[{\"id\":\"68deacff07dac\",\"file\":null,\"name\":\"11.webp\",\"size\":42242,\"url\":\"\\/buildhub\\/backend\\/uploads\\/site_images\\/28_68deacff07b1f.webp\"}]', '[]', '{\"floor1\":{\"master_bedroom\":[{\"id\":\"68deacbd684ca\",\"name\":\"master bed.jpg\",\"size\":42453,\"url\":\"\\/buildhub\\/backend\\/uploads\\/room_images\\/28_68deacbd67dce.jpg\",\"floor\":1}]}}', 'South-facing', '', 'Marble, Vitrified Tiles', 'Balanced approach', '{\"floor1\":{\"master_bedroom\":1,\"bedrooms\":1,\"attached_bathrooms\":1,\"common_bathrooms\":1,\"living_room\":1},\"floor2\":{\"master_bedroom\":1,\"bedrooms\":1,\"attached_bathrooms\":1,\"common_bathrooms\":1}}', '2', NULL),
(87, 28, 28, '2500', '30-50 Lakhs', '{\"plot_shape\":\"Irregular\",\"topography\":\"Flat\",\"development_laws\":\"\",\"family_needs\":\"Garden\\/Outdoor space, Kids play area, Security features, Storage space, Elder-friendly\",\"rooms\":\"\",\"aesthetic\":\"Contemporary\",\"notes\":\"\",\"orientation\":\"North-facing\",\"site_considerations\":\"\",\"material_preferences\":\"Granite, Wood, Natural Stone, Glass, Vitrified Tiles, Concrete, Eco-friendly, Smart Materials\",\"budget_allocation\":\"Eco-friendly focus\",\"num_floors\":\"2\",\"preferred_style\":\"Contemporary\",\"floor_rooms\":\"{\\\"floor1\\\":{\\\"master_bedroom\\\":1,\\\"attached_bathrooms\\\":1,\\\"bedrooms\\\":2,\\\"common_bathrooms\\\":1,\\\"living_room\\\":1,\\\"kitchen\\\":1,\\\"study_room\\\":0,\\\"store_room\\\":1,\\\"garage\\\":1,\\\"utility_area\\\":1}}\",\"site_images\":[{\"id\":\"68f1c1cb5b641\",\"file\":null,\"name\":\"fgdf.jpg\",\"size\":80861,\"url\":\"\\/buildhub\\/backend\\/uploads\\/site_images\\/28_68f1c1cb5a625.jpg\"}],\"reference_images\":[],\"room_images\":[]}', 'Contemporary', 'deleted', '2025-10-17 04:25:09', '2025-10-24 09:26:41', 'Kanpur', '12-18 months', NULL, 'custom', NULL, '[{\"id\":\"68f1c1cb5b641\",\"file\":null,\"name\":\"fgdf.jpg\",\"size\":80861,\"url\":\"\\/buildhub\\/backend\\/uploads\\/site_images\\/28_68f1c1cb5a625.jpg\"}]', '[]', '[]', 'North-facing', '', 'Granite, Wood, Natural Stone, Glass, Vitrified Tiles, Concrete, Eco-friendly, Smart Materials', 'Eco-friendly focus', '{\"floor1\":{\"master_bedroom\":1,\"attached_bathrooms\":1,\"bedrooms\":2,\"common_bathrooms\":1,\"living_room\":1,\"kitchen\":1,\"study_room\":0,\"store_room\":1,\"garage\":1,\"utility_area\":1}}', '2', NULL),
(88, 28, 28, '10', '50-75 Lakhs', '{\"plot_shape\":\"Rectangular\",\"topography\":\"Flat\",\"development_laws\":\"\",\"family_needs\":\"\",\"rooms\":\"bedrooms,master_bedroom,bathrooms,kitchen,living_room,dining_room\",\"aesthetic\":\"Minimalist\",\"notes\":\"\",\"orientation\":null,\"site_considerations\":null,\"material_preferences\":null,\"budget_allocation\":null,\"num_floors\":\"2\",\"preferred_style\":\"Minimalist\",\"floor_rooms\":null,\"site_images\":[],\"reference_images\":[],\"room_images\":[]}', 'Minimalist', 'deleted', '2025-10-24 08:37:11', '2025-12-30 07:44:37', '', '6-12 months', NULL, 'custom', NULL, '[]', '[]', '[]', NULL, NULL, NULL, NULL, NULL, '2', '2500'),
(89, 28, 28, '10', '50-75 Lakhs', '{\"plot_shape\":\"Square\",\"topography\":\"Flat\",\"development_laws\":\"\",\"family_needs\":\"\",\"rooms\":\"master_bedroom,bedrooms\",\"aesthetic\":\"Minimalist\",\"notes\":\"\",\"orientation\":null,\"site_considerations\":null,\"material_preferences\":null,\"budget_allocation\":null,\"num_floors\":\"2\",\"preferred_style\":\"Minimalist\",\"floor_rooms\":null,\"site_images\":[],\"reference_images\":[],\"room_images\":[]}', 'Minimalist', 'deleted', '2025-10-24 08:38:36', '2025-10-24 09:26:37', '', '6-12 months', NULL, 'custom', NULL, '[]', '[]', '[]', NULL, NULL, NULL, NULL, NULL, '2', '2498'),
(90, 28, 28, '10', '50-75 Lakhs', '{\"plot_shape\":\"Square\",\"topography\":\"Flat\",\"development_laws\":\"\",\"family_needs\":\"\",\"rooms\":\"master_bedroom,bedrooms,bathrooms,kitchen\",\"aesthetic\":\"Minimalist\",\"notes\":\"\",\"orientation\":null,\"site_considerations\":null,\"material_preferences\":null,\"budget_allocation\":null,\"num_floors\":\"2\",\"preferred_style\":\"Minimalist\",\"floor_rooms\":null,\"site_images\":[],\"reference_images\":[],\"room_images\":[]}', 'Minimalist', 'deleted', '2025-10-24 08:46:11', '2025-10-24 09:32:09', '', '6-12 months', NULL, 'custom', NULL, '[]', '[]', '[]', NULL, NULL, NULL, NULL, NULL, '2', '2495'),
(91, 28, 28, '10', '50-75 Lakhs', '{\"plot_shape\":\"Rectangular\",\"topography\":\"Flat\",\"development_laws\":\"\",\"family_needs\":\"\",\"rooms\":\"master_bedroom,bedrooms,bathrooms,kitchen,living_room,dining_room,study_room\",\"aesthetic\":\"Minimalist\",\"notes\":\"\",\"orientation\":null,\"site_considerations\":null,\"material_preferences\":null,\"budget_allocation\":null,\"num_floors\":\"2\",\"preferred_style\":\"Minimalist\",\"floor_rooms\":null,\"site_images\":[],\"reference_images\":[],\"room_images\":[]}', 'Minimalist', 'deleted', '2025-10-24 09:00:21', '2025-10-24 09:26:35', 'Mumbai', '6-12 months', NULL, 'custom', NULL, '[]', '[]', '[]', NULL, NULL, NULL, NULL, NULL, '2', '2498'),
(92, 28, 28, '10', '50-75 Lakhs', '{\"plot_shape\":\"Rectangular\",\"topography\":\"Flat\",\"development_laws\":\"\",\"family_needs\":\"\",\"rooms\":\"master_bedroom,bedrooms,bathrooms,kitchen,living_room,dining_room\",\"aesthetic\":\"Minimalist\",\"notes\":\"\",\"orientation\":null,\"site_considerations\":null,\"material_preferences\":null,\"budget_allocation\":null,\"num_floors\":\"2\",\"preferred_style\":\"Minimalist\",\"floor_rooms\":null,\"site_images\":[],\"reference_images\":[],\"room_images\":[]}', 'Minimalist', 'deleted', '2025-10-24 09:06:17', '2025-10-24 09:26:33', '', '6-12 months', NULL, 'custom', NULL, '[]', '[]', '[]', NULL, NULL, NULL, NULL, NULL, '2', '2489'),
(93, 28, 28, '10', '50-75 Lakhs', '{\"plot_shape\":\"Rectangular\",\"topography\":\"Flat\",\"development_laws\":\"\",\"family_needs\":\"\",\"rooms\":\"master_bedroom,bedrooms,bathrooms\",\"aesthetic\":\"Minimalist\",\"notes\":\"\",\"orientation\":null,\"site_considerations\":null,\"material_preferences\":null,\"budget_allocation\":null,\"num_floors\":\"2\",\"preferred_style\":\"Minimalist\",\"floor_rooms\":null,\"site_images\":[],\"reference_images\":[],\"room_images\":[]}', 'Minimalist', 'deleted', '2025-10-24 09:09:12', '2025-10-24 09:32:18', '', '6-12 months', NULL, 'custom', NULL, '[]', '[]', '[]', NULL, NULL, NULL, NULL, NULL, '2', '2497'),
(94, 28, 28, '7', '50-75 Lakhs', '{\"plot_shape\":\"Square\",\"topography\":\"Flat\",\"development_laws\":\"\",\"family_needs\":\"\",\"rooms\":\"master_bedroom,bedrooms,bathrooms\",\"aesthetic\":\"Minimalist\",\"notes\":\"\",\"orientation\":null,\"site_considerations\":null,\"material_preferences\":null,\"budget_allocation\":null,\"num_floors\":\"2\",\"preferred_style\":\"Minimalist\",\"floor_rooms\":null,\"site_images\":[],\"reference_images\":[],\"room_images\":[]}', 'Minimalist', 'deleted', '2025-10-24 09:26:08', '2025-12-30 07:44:37', '', '6-12 months', NULL, 'custom', NULL, '[]', '[]', '[]', NULL, NULL, NULL, NULL, NULL, '2', '2491'),
(95, 28, 28, '10', '50-75 Lakhs', '{\"plot_shape\":\"Rectangular\",\"topography\":\"Flat\",\"development_laws\":\"Standard\",\"family_needs\":\"\",\"rooms\":\"master_bedroom,bedrooms,bathrooms,prayer_room,study_room\",\"aesthetic\":\"Scandinavian\",\"notes\":\"\",\"orientation\":null,\"site_considerations\":null,\"material_preferences\":null,\"budget_allocation\":null,\"num_floors\":\"1\",\"preferred_style\":\"Scandinavian\",\"floor_rooms\":null,\"site_images\":[],\"reference_images\":[],\"room_images\":[]}', 'Scandinavian', 'deleted', '2025-10-26 18:18:58', '2025-12-30 07:44:36', 'Delhi', '6-12 months', NULL, 'custom', NULL, '[]', '[]', '[]', NULL, NULL, NULL, NULL, NULL, '1', '2500'),
(99, 48, 48, '30x40 feet', '₹15-20 lakhs', 'Need a 3BHK house with modern kitchen and spacious living room. Should have good ventilation and natural light.', 'Modern', 'pending', '2025-12-21 08:25:23', '2025-12-21 08:25:23', 'Bangalore, Karnataka', '6-8 months', NULL, 'custom', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(100, 49, 49, '40x60 feet', '₹25-30 lakhs', 'Want a traditional style house with 4 bedrooms and garden space. Prefer vastu compliant design.', 'Traditional', 'pending', '2025-12-21 08:25:23', '2025-12-21 08:25:23', 'Mumbai, Maharashtra', '8-10 months', NULL, 'custom', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(101, 50, 50, '25x30 feet', '₹10-15 lakhs', 'Compact 2BHK house with efficient space utilization. Need parking for 1 car.', 'Compact', 'pending', '2025-12-21 08:25:23', '2025-12-21 08:25:23', 'Delhi, India', '4-6 months', NULL, 'custom', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(102, 48, 48, '30x40 feet', '₹15-20 lakhs', 'Need a 3BHK house with modern kitchen and spacious living room. Should have good ventilation and natural light.', 'Modern', 'pending', '2025-12-30 06:04:26', '2025-12-30 06:04:26', 'Bangalore, Karnataka', '6-8 months', NULL, 'custom', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(103, 49, 49, '40x60 feet', '₹25-30 lakhs', 'Want a traditional style house with 4 bedrooms and garden space. Prefer vastu compliant design.', 'Traditional', 'pending', '2025-12-30 06:04:26', '2025-12-30 06:04:26', 'Mumbai, Maharashtra', '8-10 months', NULL, 'custom', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(104, 50, 50, '25x30 feet', '₹10-15 lakhs', 'Compact 2BHK house with efficient space utilization. Need parking for 1 car.', 'Compact', 'pending', '2025-12-30 06:04:26', '2025-12-30 06:04:26', 'Delhi, India', '4-6 months', NULL, 'custom', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(105, 28, 28, '20', '50-75 Lakhs', '{\"plot_shape\":\"Rectangular\",\"topography\":\"Flat\",\"development_laws\":\"Standard\",\"family_needs\":\"\",\"rooms\":\"master_bedroom,bedrooms,bathrooms,attached_bathroom,kitchen,living_room,dining_room,study_room,prayer_room,guest_room,store_room,balcony,terrace,garage\",\"aesthetic\":\"Modern\",\"notes\":\"\",\"orientation\":null,\"site_considerations\":null,\"material_preferences\":null,\"budget_allocation\":null,\"num_floors\":\"2\",\"preferred_style\":\"Modern\",\"floor_rooms\":{\"floor1\":{\"master_bedroom\":1,\"bedrooms\":1,\"bathrooms\":1,\"attached_bathroom\":1,\"kitchen\":1,\"living_room\":1,\"dining_room\":1,\"store_room\":1,\"garage\":1},\"floor2\":{\"study_room\":1,\"prayer_room\":1,\"guest_room\":1,\"balcony\":1,\"terrace\":1,\"bedrooms\":1,\"bathrooms\":1,\"attached_bathroom\":1}},\"site_images\":[],\"reference_images\":[],\"room_images\":[]}', 'Modern', 'approved', '2026-01-05 16:33:31', '2026-01-05 16:35:28', 'Mumbai', '3-6 months', NULL, 'custom', NULL, '[]', '[]', '[]', NULL, NULL, NULL, NULL, '{\"floor1\":{\"master_bedroom\":1,\"bedrooms\":1,\"bathrooms\":1,\"attached_bathroom\":1,\"kitchen\":1,\"living_room\":1,\"dining_room\":1,\"store_room\":1,\"garage\":1},\"floor2\":{\"study_room\":1,\"prayer_room\":1,\"guest_room\":1,\"balcony\":1,\"terrace\":1,\"bedrooms\":1,\"bathrooms\":1,\"attached_bathroom\":1}}', '2', '2500');

-- --------------------------------------------------------

--
-- Table structure for table `layout_request_assignments`
--

CREATE TABLE `layout_request_assignments` (
  `id` int(11) NOT NULL,
  `layout_request_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `architect_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('sent','accepted','declined') DEFAULT 'sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `layout_request_assignments`
--

INSERT INTO `layout_request_assignments` (`id`, `layout_request_id`, `homeowner_id`, `architect_id`, `message`, `status`, `created_at`, `updated_at`) VALUES
(56, 105, 28, 27, NULL, 'accepted', '2026-01-05 16:33:31', '2026-01-05 16:35:28');

-- --------------------------------------------------------

--
-- Table structure for table `layout_technical_details`
--

CREATE TABLE `layout_technical_details` (
  `id` int(11) NOT NULL,
  `design_id` int(11) NOT NULL,
  `architect_id` int(11) NOT NULL,
  `room_layout_dimensions` text DEFAULT NULL,
  `door_window_positions` text DEFAULT NULL,
  `circulation_paths` text DEFAULT NULL,
  `plot_boundaries` text DEFAULT NULL,
  `orientation_north_direction` text DEFAULT NULL,
  `access_points` text DEFAULT NULL,
  `load_bearing_walls` text DEFAULT NULL,
  `column_positions` text DEFAULT NULL,
  `foundation_outline` text DEFAULT NULL,
  `roof_outline` text DEFAULT NULL,
  `front_elevation` text DEFAULT NULL,
  `cross_sections` text DEFAULT NULL,
  `height_details` text DEFAULT NULL,
  `wall_thickness` text DEFAULT NULL,
  `ceiling_heights` text DEFAULT NULL,
  `building_codes_compliance` text DEFAULT NULL,
  `critical_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `layout_templates`
--

CREATE TABLE `layout_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `style` varchar(100) DEFAULT NULL,
  `rooms` int(11) DEFAULT NULL,
  `preview_image` varchar(255) DEFAULT NULL,
  `template_file` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `layout_templates`
--

INSERT INTO `layout_templates` (`id`, `name`, `description`, `style`, `rooms`, `preview_image`, `template_file`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Modern Villa Template', 'Contemporary villa design with open spaces and large windows', 'Modern', 4, NULL, NULL, 'active', '2025-08-15 07:18:01', '2025-08-15 07:18:01'),
(2, 'Traditional House Template', 'Classic house design with traditional architectural elements', 'Traditional', 3, NULL, NULL, 'active', '2025-08-15 07:18:01', '2025-08-15 07:18:01'),
(3, 'Compact Home Template', 'Space-efficient design perfect for small plots', 'Compact', 2, NULL, NULL, 'active', '2025-08-15 07:18:01', '2025-08-15 07:18:01'),
(4, 'Luxury Mansion Template', 'Grand mansion design with premium features', 'Luxury', 6, NULL, NULL, 'active', '2025-08-15 07:18:01', '2025-08-15 07:18:01'),
(5, 'Eco-Friendly Home Template', 'Sustainable design with green building features', 'Eco-Friendly', 3, NULL, NULL, 'active', '2025-08-15 07:18:01', '2025-08-15 07:18:01');

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`id`, `name`, `category`, `unit`, `price`, `description`, `created_at`, `updated_at`) VALUES
(14, 'kajaria steels', 'steel', '2000 tons', 2000.00, '', '2025-09-15 08:53:31', '2025-09-15 08:53:31');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL,
  `to_user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `message_type` varchar(50) DEFAULT 'general',
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `related_id`, `is_read`, `created_at`) VALUES
(1, 19, 'test_notification', 'Test Notification', 'This is a test notification created by the notification system test.', NULL, 0, '2025-12-30 09:45:32'),
(2, 28, 'test_notification', 'Test Notification', 'This is a test notification created at 2025-12-30 11:00:56', NULL, 1, '2025-12-30 10:00:56');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `email`, `token_hash`, `expires_at`, `used`, `created_at`) VALUES
(1, 30, 'fathima470077@gmail.com', 'ad3484cae65594d475d3645407e4a456be1d15a04489eaf526da38f89f0ef785', '2025-08-31 08:45:10', 1, '2025-08-31 05:45:10'),
(2, 30, 'fathima470077@gmail.com', '048ca2716ede4bf0cd6d31f31283f0767c6938ba3b3d20cdadf35dbd1aa3dbf2', '2025-08-31 08:46:12', 1, '2025-08-31 05:46:12'),
(3, 30, 'fathima470077@gmail.com', '731a165630d5a22e912102647e0167b7badbe04fa24ffd67ed2c3315b2fbc112', '2025-08-31 08:51:13', 1, '2025-08-31 05:51:13'),
(4, 30, 'fathima470077@gmail.com', 'ea570e6c6f9c68473d8b8af7f29be0184dc08a1d50a4ae1febcca6d307d4a9a4', '2025-08-31 08:54:16', 1, '2025-08-31 05:54:16'),
(5, 30, 'fathima470077@gmail.com', '995292a1d0bc99ad09b2fc1102de8023de4350a4092714f4b1a94a70198a21b6', '2025-08-31 08:57:30', 1, '2025-08-31 05:57:30'),
(6, 32, 'thomasshijin90@gmail.com', '2b08026e2b7bc8f04f747f4204aa26a86d0a4e9924c7b00cf753fe8dff62cee2', '2025-09-17 16:20:48', 1, '2025-09-17 13:20:48'),
(7, 32, 'thomasshijin90@gmail.com', '2cbb28c93b5e622d7b543a1b2a30d997cba1e778002281365f3a58c9d92eaf26', '2025-09-17 16:27:09', 1, '2025-09-17 13:27:09'),
(8, 32, 'thomasshijin90@gmail.com', '2fd2221e079fb2fd11fe5244817dc36596c62e77da18e75935d41e1ec97b2876', '2025-09-17 16:31:53', 1, '2025-09-17 13:31:53');

-- --------------------------------------------------------

--
-- Table structure for table `payment_notifications`
--

CREATE TABLE `payment_notifications` (
  `id` int(11) NOT NULL,
  `payment_request_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `recipient_type` enum('homeowner','contractor') NOT NULL,
  `notification_type` enum('request_submitted','request_approved','request_rejected','payment_completed','payment_overdue') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `phase_worker_requirements`
--

CREATE TABLE `phase_worker_requirements` (
  `id` int(11) NOT NULL,
  `phase_id` int(11) NOT NULL,
  `worker_type_id` int(11) NOT NULL,
  `is_required` tinyint(1) DEFAULT 1,
  `min_workers` int(11) DEFAULT 1,
  `max_workers` int(11) DEFAULT 10,
  `priority_level` enum('essential','important','optional') DEFAULT 'important',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `phase_worker_requirements`
--

INSERT INTO `phase_worker_requirements` (`id`, `phase_id`, `worker_type_id`, `is_required`, `min_workers`, `max_workers`, `priority_level`, `created_at`) VALUES
(1, 1, 13, 1, 1, 2, 'essential', '2026-01-05 15:01:15'),
(2, 1, 15, 1, 4, 8, 'essential', '2026-01-05 15:01:15'),
(3, 1, 14, 1, 2, 4, 'important', '2026-01-05 15:01:15'),
(4, 1, 17, 0, 1, 1, 'optional', '2026-01-05 15:01:15'),
(5, 2, 1, 1, 2, 4, 'essential', '2026-01-05 15:01:15'),
(6, 2, 9, 1, 2, 4, 'important', '2026-01-05 15:01:15'),
(7, 2, 8, 1, 1, 3, 'essential', '2026-01-05 15:01:15'),
(8, 2, 15, 1, 4, 8, 'important', '2026-01-05 15:01:15'),
(9, 2, 14, 1, 2, 4, 'important', '2026-01-05 15:01:15'),
(10, 2, 13, 0, 1, 2, 'optional', '2026-01-05 15:01:15'),
(11, 3, 1, 1, 3, 6, 'essential', '2026-01-05 15:01:15'),
(12, 3, 9, 1, 3, 6, 'essential', '2026-01-05 15:01:15'),
(13, 3, 8, 1, 2, 4, 'essential', '2026-01-05 15:01:15'),
(14, 3, 2, 1, 2, 4, 'essential', '2026-01-05 15:01:15'),
(15, 3, 10, 1, 2, 4, 'essential', '2026-01-05 15:01:15'),
(16, 3, 5, 1, 1, 2, 'essential', '2026-01-05 15:01:15'),
(17, 3, 15, 1, 6, 10, 'essential', '2026-01-05 15:01:15'),
(18, 3, 14, 1, 4, 6, 'essential', '2026-01-05 15:01:15'),
(19, 4, 1, 1, 4, 8, 'essential', '2026-01-05 15:01:15'),
(20, 4, 9, 1, 4, 8, 'essential', '2026-01-05 15:01:15'),
(21, 4, 15, 1, 4, 6, 'important', '2026-01-05 15:01:15'),
(22, 4, 14, 1, 2, 4, 'important', '2026-01-05 15:01:15'),
(23, 5, 2, 1, 2, 4, 'essential', '2026-01-05 15:01:15'),
(24, 5, 10, 1, 2, 4, 'important', '2026-01-05 15:01:15'),
(25, 5, 8, 1, 1, 2, 'important', '2026-01-05 15:01:15'),
(26, 5, 14, 1, 2, 4, 'important', '2026-01-05 15:01:15'),
(27, 5, 15, 1, 2, 4, 'important', '2026-01-05 15:01:15'),
(28, 6, 3, 1, 2, 4, 'essential', '2026-01-05 15:01:15'),
(29, 6, 11, 1, 2, 4, 'important', '2026-01-05 15:01:15'),
(30, 6, 14, 1, 1, 2, 'important', '2026-01-05 15:01:15'),
(31, 7, 4, 1, 2, 4, 'essential', '2026-01-05 15:01:15'),
(32, 7, 12, 1, 2, 4, 'important', '2026-01-05 15:01:15'),
(33, 7, 14, 1, 1, 2, 'important', '2026-01-05 15:01:15'),
(34, 8, 1, 1, 2, 4, 'essential', '2026-01-05 15:01:15'),
(35, 8, 6, 1, 2, 4, 'essential', '2026-01-05 15:01:15'),
(36, 8, 7, 1, 1, 3, 'important', '2026-01-05 15:01:15'),
(37, 8, 2, 1, 1, 3, 'important', '2026-01-05 15:01:15'),
(38, 8, 14, 1, 2, 4, 'important', '2026-01-05 15:01:15'),
(39, 9, 7, 1, 2, 4, 'essential', '2026-01-05 15:01:15'),
(40, 9, 14, 1, 2, 3, 'important', '2026-01-05 15:01:15'),
(41, 9, 15, 1, 1, 2, 'optional', '2026-01-05 15:01:15'),
(42, 10, 16, 1, 2, 4, 'essential', '2026-01-05 15:01:15'),
(43, 10, 14, 1, 1, 2, 'important', '2026-01-05 15:01:15');

-- --------------------------------------------------------

--
-- Table structure for table `progress_reports`
--

CREATE TABLE `progress_reports` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `report_type` enum('daily','weekly','monthly') NOT NULL,
  `report_period_start` date DEFAULT NULL,
  `report_period_end` date DEFAULT NULL,
  `report_data` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('draft','sent','viewed','acknowledged') DEFAULT 'draft',
  `homeowner_viewed_at` timestamp NULL DEFAULT NULL,
  `homeowner_acknowledged_at` timestamp NULL DEFAULT NULL,
  `acknowledgment_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `progress_worker_assignments`
--

CREATE TABLE `progress_worker_assignments` (
  `id` int(11) NOT NULL,
  `progress_update_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `work_date` date NOT NULL,
  `hours_worked` decimal(4,2) DEFAULT 8.00,
  `overtime_hours` decimal(4,2) DEFAULT 0.00,
  `daily_wage` decimal(8,2) NOT NULL,
  `overtime_rate` decimal(8,2) DEFAULT 0.00,
  `total_payment` decimal(10,2) GENERATED ALWAYS AS (`hours_worked` * `daily_wage` / 8 + `overtime_hours` * `overtime_rate`) STORED,
  `work_description` text DEFAULT NULL,
  `performance_rating` enum('excellent','good','average','poor') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_locations`
--

CREATE TABLE `project_locations` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `address` varchar(500) NOT NULL,
  `radius_meters` int(11) DEFAULT 100,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_locations`
--

INSERT INTO `project_locations` (`id`, `project_id`, `latitude`, `longitude`, `address`, `radius_meters`, `created_at`) VALUES
(4, 30, 12.97160000, 77.59460000, 'Bangalore, Karnataka - John Smith Project', 100, '2025-12-21 08:29:39'),
(5, 31, 19.07600000, 72.87770000, 'Mumbai, Maharashtra - Sarah Johnson Project', 150, '2025-12-21 08:29:39'),
(6, 32, 28.70410000, 77.10250000, 'Delhi, India - Michael Brown Project', 200, '2025-12-21 08:29:39'),
(10, 1, 12.97160000, 77.59460000, 'Bangalore, Karnataka', 100, '2026-01-05 14:54:56'),
(11, 2, 19.07600000, 72.87770000, 'Mumbai, Maharashtra', 150, '2026-01-05 14:54:56'),
(12, 3, 28.70410000, 77.10250000, 'New Delhi, Delhi', 200, '2026-01-05 14:54:56');

-- --------------------------------------------------------

--
-- Table structure for table `project_payment_schedule`
--

CREATE TABLE `project_payment_schedule` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `stage_name` varchar(100) NOT NULL,
  `scheduled_percentage` decimal(5,2) NOT NULL,
  `scheduled_amount` decimal(12,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_stage_payment_requests`
--

CREATE TABLE `project_stage_payment_requests` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `stage_name` varchar(100) NOT NULL,
  `requested_amount` decimal(12,2) NOT NULL,
  `percentage_of_total` decimal(5,2) NOT NULL,
  `work_description` text NOT NULL,
  `completion_percentage` decimal(5,2) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected','paid') DEFAULT 'pending',
  `homeowner_response_date` timestamp NULL DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `contractor_notes` text DEFAULT NULL,
  `homeowner_notes` text DEFAULT NULL,
  `progress_update_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proposals`
--

CREATE TABLE `proposals` (
  `id` int(11) NOT NULL,
  `layout_request_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_templates`
--

CREATE TABLE `room_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('bedroom','bathroom','kitchen','living','dining','utility','outdoor','other') NOT NULL,
  `default_width` decimal(6,2) NOT NULL,
  `default_height` decimal(6,2) NOT NULL,
  `min_width` decimal(6,2) NOT NULL,
  `min_height` decimal(6,2) NOT NULL,
  `max_width` decimal(6,2) NOT NULL,
  `max_height` decimal(6,2) NOT NULL,
  `color` varchar(7) DEFAULT '#e3f2fd',
  `icon` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_templates`
--

INSERT INTO `room_templates` (`id`, `name`, `category`, `default_width`, `default_height`, `min_width`, `min_height`, `max_width`, `max_height`, `color`, `icon`, `is_active`, `created_at`) VALUES
(1, 'Master Bedroom', 'bedroom', 14.00, 12.00, 10.00, 10.00, 20.00, 16.00, '#e8f5e8', 'bed', 1, '2026-01-03 09:38:57'),
(2, 'Bedroom', 'bedroom', 12.00, 10.00, 8.00, 8.00, 16.00, 14.00, '#e8f5e8', 'bed', 1, '2026-01-03 09:38:57'),
(3, 'Living Room', 'living', 16.00, 14.00, 12.00, 10.00, 24.00, 20.00, '#fff3e0', 'sofa', 1, '2026-01-03 09:38:57'),
(4, 'Kitchen', 'kitchen', 12.00, 8.00, 8.00, 6.00, 16.00, 12.00, '#fce4ec', 'kitchen', 1, '2026-01-03 09:38:57'),
(5, 'Dining Room', 'dining', 12.00, 10.00, 8.00, 8.00, 16.00, 14.00, '#f3e5f5', 'dining', 1, '2026-01-03 09:38:57'),
(6, 'Bathroom', 'bathroom', 8.00, 6.00, 5.00, 4.00, 12.00, 10.00, '#e1f5fe', 'bath', 1, '2026-01-03 09:38:57'),
(7, 'Master Bathroom', 'bathroom', 10.00, 8.00, 6.00, 5.00, 14.00, 12.00, '#e1f5fe', 'bath', 1, '2026-01-03 09:38:57'),
(8, 'Utility Room', 'utility', 8.00, 6.00, 4.00, 4.00, 12.00, 10.00, '#f1f8e9', 'utility', 1, '2026-01-03 09:38:57'),
(9, 'Balcony', 'outdoor', 8.00, 4.00, 4.00, 3.00, 16.00, 8.00, '#e8f5e8', 'balcony', 1, '2026-01-03 09:38:57'),
(10, 'Terrace', 'outdoor', 12.00, 8.00, 6.00, 4.00, 20.00, 16.00, '#e8f5e8', 'terrace', 1, '2026-01-03 09:38:57'),
(11, 'Study Room', 'other', 10.00, 8.00, 6.00, 6.00, 14.00, 12.00, '#fff8e1', 'desk', 1, '2026-01-03 09:38:57'),
(12, 'Store Room', 'utility', 6.00, 6.00, 4.00, 4.00, 10.00, 10.00, '#f5f5f5', 'storage', 1, '2026-01-03 09:38:57'),
(13, 'Pooja Room', 'other', 6.00, 6.00, 4.00, 4.00, 8.00, 8.00, '#fff3e0', 'temple', 1, '2026-01-03 09:38:57'),
(14, 'Entrance Hall', 'living', 8.00, 6.00, 4.00, 4.00, 12.00, 10.00, '#fff3e0', 'door', 1, '2026-01-03 09:38:57'),
(15, 'Master Bedroom', 'bedroom', 14.00, 12.00, 10.00, 10.00, 20.00, 16.00, '#e8f5e8', 'bed', 1, '2026-01-03 09:40:18'),
(16, 'Bedroom', 'bedroom', 12.00, 10.00, 8.00, 8.00, 16.00, 14.00, '#e8f5e8', 'bed', 1, '2026-01-03 09:40:18'),
(17, 'Living Room', 'living', 16.00, 14.00, 12.00, 10.00, 24.00, 20.00, '#fff3e0', 'sofa', 1, '2026-01-03 09:40:18'),
(18, 'Kitchen', 'kitchen', 12.00, 8.00, 8.00, 6.00, 16.00, 12.00, '#fce4ec', 'kitchen', 1, '2026-01-03 09:40:18'),
(19, 'Dining Room', 'dining', 12.00, 10.00, 8.00, 8.00, 16.00, 14.00, '#f3e5f5', 'dining', 1, '2026-01-03 09:40:18'),
(20, 'Bathroom', 'bathroom', 8.00, 6.00, 5.00, 4.00, 12.00, 10.00, '#e1f5fe', 'bath', 1, '2026-01-03 09:40:18'),
(21, 'Master Bathroom', 'bathroom', 10.00, 8.00, 6.00, 5.00, 14.00, 12.00, '#e1f5fe', 'bath', 1, '2026-01-03 09:40:18'),
(22, 'Utility Room', 'utility', 8.00, 6.00, 4.00, 4.00, 12.00, 10.00, '#f1f8e9', 'utility', 1, '2026-01-03 09:40:18'),
(23, 'Balcony', 'outdoor', 8.00, 4.00, 4.00, 3.00, 16.00, 8.00, '#e8f5e8', 'balcony', 1, '2026-01-03 09:40:18'),
(24, 'Terrace', 'outdoor', 12.00, 8.00, 6.00, 4.00, 20.00, 16.00, '#e8f5e8', 'terrace', 1, '2026-01-03 09:40:18'),
(25, 'Study Room', 'other', 10.00, 8.00, 6.00, 6.00, 14.00, 12.00, '#fff8e1', 'desk', 1, '2026-01-03 09:40:18'),
(26, 'Store Room', 'utility', 6.00, 6.00, 4.00, 4.00, 10.00, 10.00, '#f5f5f5', 'storage', 1, '2026-01-03 09:40:18'),
(27, 'Pooja Room', 'other', 6.00, 6.00, 4.00, 4.00, 8.00, 8.00, '#fff3e0', 'temple', 1, '2026-01-03 09:40:18'),
(28, 'Entrance Hall', 'living', 8.00, 6.00, 4.00, 4.00, 12.00, 10.00, '#fff3e0', 'door', 1, '2026-01-03 09:40:18'),
(29, 'Master Bedroom', 'bedroom', 14.00, 12.00, 10.00, 10.00, 20.00, 16.00, '#e8f5e8', '🛏️', 1, '2026-01-03 09:54:34'),
(30, 'Bedroom', 'bedroom', 12.00, 10.00, 8.00, 8.00, 16.00, 14.00, '#e8f5e8', '🛏️', 1, '2026-01-03 09:54:34'),
(31, 'Living Room', 'living', 16.00, 14.00, 12.00, 10.00, 24.00, 20.00, '#fff3e0', '🛋️', 1, '2026-01-03 09:54:34'),
(32, 'Kitchen', 'kitchen', 12.00, 8.00, 8.00, 6.00, 16.00, 12.00, '#fce4ec', '🍳', 1, '2026-01-03 09:54:34'),
(33, 'Dining Room', 'dining', 12.00, 10.00, 8.00, 8.00, 16.00, 14.00, '#f3e5f5', '🍽️', 1, '2026-01-03 09:54:34'),
(34, 'Bathroom', 'bathroom', 8.00, 6.00, 5.00, 4.00, 12.00, 10.00, '#e1f5fe', '🚿', 1, '2026-01-03 09:54:34'),
(35, 'Master Bathroom', 'bathroom', 10.00, 8.00, 6.00, 5.00, 14.00, 12.00, '#e1f5fe', '🛁', 1, '2026-01-03 09:54:34'),
(36, 'Utility Room', 'utility', 8.00, 6.00, 4.00, 4.00, 12.00, 10.00, '#f1f8e9', '🧹', 1, '2026-01-03 09:54:34'),
(37, 'Balcony', 'outdoor', 8.00, 4.00, 4.00, 3.00, 16.00, 8.00, '#e8f5e8', '🌿', 1, '2026-01-03 09:54:34'),
(38, 'Terrace', 'outdoor', 12.00, 8.00, 6.00, 4.00, 20.00, 16.00, '#e8f5e8', '🏡', 1, '2026-01-03 09:54:34'),
(39, 'Study Room', 'other', 10.00, 8.00, 6.00, 6.00, 14.00, 12.00, '#fff8e1', '📚', 1, '2026-01-03 09:54:34'),
(40, 'Store Room', 'utility', 6.00, 6.00, 4.00, 4.00, 10.00, 10.00, '#f5f5f5', '📦', 1, '2026-01-03 09:54:34'),
(41, 'Pooja Room', 'other', 6.00, 6.00, 4.00, 4.00, 8.00, 8.00, '#fff3e0', '🕉️', 1, '2026-01-03 09:54:34'),
(42, 'Entrance Hall', 'living', 8.00, 6.00, 4.00, 4.00, 12.00, 10.00, '#fff3e0', '🚪', 1, '2026-01-03 09:54:34');

-- --------------------------------------------------------

--
-- Table structure for table `support_issues`
--

CREATE TABLE `support_issues` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(32) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `category` varchar(64) DEFAULT 'general',
  `message` text NOT NULL,
  `status` varchar(32) DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_issues`
--

INSERT INTO `support_issues` (`id`, `user_id`, `role`, `subject`, `category`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 30, 'homeowner', 'Bug', 'bug', 'The site have a bug', 'open', '2025-09-15 08:44:13', '2025-12-30 08:19:23'),
(2, 30, 'homeowner', 'bug', 'bug', 'this site have bugs', 'open', '2025-09-15 08:49:55', '2025-12-30 08:19:23'),
(3, 30, 'homeowner', 'bug', 'billing', 'bug', 'open', '2025-09-15 08:54:17', '2025-12-30 08:19:23'),
(4, 30, 'homeowner', 'bug', 'bug', 'this have bug', 'open', '2025-09-15 09:05:55', '2025-12-30 08:19:23'),
(5, 30, 'homeowner', 'bug', 'bug', 'fs', 'open', '2025-09-15 09:17:26', '2025-12-30 08:19:23'),
(6, 30, 'homeowner', 'bug', 'bug', 'hbvhvh', 'open', '2025-09-15 09:22:01', '2025-12-30 08:19:23'),
(7, 28, '', 'bug', 'bug', 'ddsd', 'replied', '2025-12-30 08:14:42', '2025-12-30 09:34:31');

-- --------------------------------------------------------

--
-- Table structure for table `support_replies`
--

CREATE TABLE `support_replies` (
  `id` int(11) NOT NULL,
  `issue_id` int(11) NOT NULL,
  `sender` varchar(16) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_replies`
--

INSERT INTO `support_replies` (`id`, `issue_id`, `sender`, `user_id`, `message`, `created_at`) VALUES
(1, 7, 'admin', NULL, 'we fix this very fatsely', '2025-12-30 09:34:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('homeowner','contractor','architect') DEFAULT NULL,
  `status` enum('pending','approved','rejected','suspended') DEFAULT 'pending',
  `is_verified` tinyint(1) DEFAULT 0,
  `license` varchar(255) DEFAULT NULL,
  `portfolio` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `profile_image`, `bio`, `email`, `password`, `role`, `status`, `is_verified`, `license`, `portfolio`, `created_at`, `updated_at`, `deleted_at`, `specialization`, `experience_years`, `license_number`, `company_name`, `website`, `phone`, `address`, `location`, `city`, `state`, `zip_code`) VALUES
(19, 'Shijin', 'Thomas', NULL, NULL, 'thomasshijin12@gmail.com', '$2y$10$3gq5TYKFrxe79x7Bd6zfYeop4C3lPHlT0RBbDCRK8Wd/olTpWnsNK', 'homeowner', 'approved', 1, NULL, NULL, '2025-08-15 08:37:34', '2025-09-25 04:15:11', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, 'അമൽ ജ്യോതി കോളേജ് ഓഫ് എഞ്ചിനീയറിങ്, കൂവപ്പള്ളി - വിഴിക്കത്തോട് റോഡ്, കൂവപ്പള്ളി, Kanjirappally, കോട്ടയം ജില്ല, Kerala, 686518, India', NULL, NULL, NULL),
(26, 'APARNA K SANTHOSH', 'MCA2024-2026', NULL, NULL, 'aparnaksanthosh2026@mca.ajce.in', '$2y$10$3h5YpKY7duoyJ5YHWNRtpOP0a5hyLXfCiy1mzGG.Dgsaz10KZMehu', 'architect', 'approved', 1, NULL, 'uploads/portfolios/68a421323b2f3_license_20.jpeg', '2025-08-19 07:01:06', '2025-09-14 09:35:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(27, 'Shijin', 'Thomas', NULL, NULL, 'shijinthomas1501@gmail.com', '$2y$10$i/i/4o20DEqfRIsuEsLw..7OEL.5HhWbOQFSLcKBfz.XN0a47uGbu', 'architect', 'approved', 1, NULL, '/uploads/portfolios/68a89de45ea03_license_20.jpeg', '2025-08-22 16:42:12', '2025-09-07 05:29:40', NULL, 'Residential', 3, NULL, NULL, NULL, '7558895667', NULL, NULL, 'Kottayam', NULL, NULL),
(28, 'SHIJIN THOMAS', 'MCA2024-2026', NULL, NULL, 'shijinthomas2026@mca.ajce.in', '$2y$10$J243fQ/Wi88Bk9UbtlSKvOJStinlPcePeWgV8C0gApCZnxbG5qRfe', 'homeowner', 'approved', 1, NULL, NULL, '2025-08-22 17:48:50', '2025-10-19 10:35:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(29, 'Shijin', 'Thomas', NULL, NULL, 'shijinthomas248@gmail.com', '$2y$10$m6o/je.6qIdMD6/k17enr.0QD0PAYSYSIyhTHF5b9Hs57hpqMsvR6', 'contractor', 'approved', 1, 'uploads/licenses/68b1a6aa444ec_license_20.jpeg', NULL, '2025-08-29 13:10:02', '2025-09-03 15:18:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(30, 'Fathima', 'Shibu', NULL, NULL, 'fathima470077@gmail.com', '$2y$10$ZFxAkA99J0LlBoYyr0TPne2PTEc5qFDDwFOCYoaZnH3m6a/ztMRSG', 'homeowner', 'approved', 1, NULL, NULL, '2025-08-31 05:37:04', '2025-09-11 09:29:51', NULL, NULL, NULL, NULL, NULL, NULL, '7558895667', NULL, 'Amal Jyothi College of Engineering, Koovappalli - Vizhikkathodu Road, Koovapally, Kanjirappally, Kottayam, Kerala, 686518, India', NULL, NULL, NULL),
(31, 'shijin', 'thomas', NULL, NULL, 'thomasshijin3@gmail.com', '$2y$10$5V6TmtS.aQnGhVt078Ugnuzq5PZu3afLzcEejgNFYv8bKtuQwe5Xi', 'architect', 'approved', 1, NULL, 'uploads/portfolios/68c686c50945d_license.jpeg', '2025-09-14 09:11:33', '2025-09-24 15:43:55', NULL, 'Commercial', 3, NULL, NULL, NULL, '7558958947', NULL, NULL, 'Ernakulam', NULL, NULL),
(32, 'Amal', 'Samuel', NULL, NULL, 'thomasshijin90@gmail.com', '$2y$10$QeLhw1WzOr9RRyFr5UJd1eYge9qLg1A6s2z98YKKVGsIb8Dk7iVjG', 'homeowner', 'approved', 1, NULL, NULL, '2025-09-17 13:20:34', '2025-09-19 08:11:33', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(33, 'Linsha', 'Nadir', NULL, NULL, 'linshan2026@mca.ajce.in', '$2y$10$xnBF0c6kzGtZ15OLQELJg.pxAxkruyq9O.pITuNr0XaVr8blQSBrG', 'architect', 'approved', 1, NULL, '/uploads/portfolios/68d10766bb3e1_1.png', '2025-09-22 08:23:02', '2025-09-24 15:44:52', NULL, 'Interior Design', 0, NULL, NULL, NULL, '7558958478', NULL, NULL, 'Malappuram', NULL, NULL),
(34, 'Savio', 'Joseph', NULL, NULL, 'saviojoseph2026@mca.ajce.in', '$2y$10$5cBxbUh2PhhTK0013cmvneDeSJJmNPsyYCl6kDbCxn2b6RqNT0bzC', 'architect', 'approved', 1, NULL, '/uploads/portfolios/68d4c250e8f94_2222.png', '2025-09-25 04:17:21', '2025-09-25 04:41:39', NULL, 'Urban Planner', 0, NULL, NULL, NULL, '9656819474', NULL, NULL, 'Kottayam', NULL, NULL),
(35, 'SHIJIN', 'THOMAS', NULL, NULL, 'thomasshijin6@gmail.com', '$2y$10$S2jih5XV.2Bb3gfpdji76.xS89SXuglKVpkIpPG9UsrYvU809/ddq', 'homeowner', 'pending', 1, NULL, NULL, '2025-09-28 09:11:23', '2025-09-28 09:11:23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(36, 'Thomas', 'Joseph', NULL, NULL, 'thomasshijin281@gmail.com', '$2y$10$89COFm04m7rM9oTBLXdDee8nzohwua1AmFvutXrZXYqi68Dls2adu', 'homeowner', 'pending', 1, NULL, NULL, '2025-10-06 16:00:36', '2025-10-06 16:00:36', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(37, 'Shijin', 'Thomas', NULL, NULL, 'shijinthomas81@gmail.com', '$2y$10$.tyScF6DTz3gYhm.CcHC3uKG2GHNEz5F1kuegebG/mNJ/KhU9avKy', 'contractor', 'approved', 1, '/uploads/licenses/68e3e7ffec8f6_68b5c450e97c92.78819543_1756742736.pdf', NULL, '2025-10-06 16:02:08', '2025-10-06 16:02:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(48, 'John', 'Smith', NULL, NULL, 'john.smith@email.com', '$2y$10$bpvaT.tU9/64Tr.WB8TSou.Z0s5lcJWA.cn3BsxDW96m5vUlIFWNy', 'homeowner', 'pending', 0, NULL, NULL, '2025-12-21 08:07:06', '2025-12-21 08:07:06', NULL, NULL, NULL, NULL, NULL, NULL, '9876543210', NULL, NULL, NULL, NULL, NULL),
(49, 'Sarah', 'Johnson', NULL, NULL, 'sarah.johnson@email.com', '$2y$10$8QrAZhObaPeV3E.BFRa4NeqXuNUmeGJWppy5lTtPG1OvfT9PNT7SO', 'homeowner', 'pending', 0, NULL, NULL, '2025-12-21 08:07:06', '2025-12-21 08:07:06', NULL, NULL, NULL, NULL, NULL, NULL, '9876543211', NULL, NULL, NULL, NULL, NULL),
(50, 'Michael', 'Brown', NULL, NULL, 'michael.brown@email.com', '$2y$10$10B0fn154Auz6myiCeqJv.xo/g6sC5hQDuJrdm4RLFrtwm5TOeNBm', 'homeowner', 'pending', 0, NULL, NULL, '2025-12-21 08:07:06', '2025-12-21 08:07:06', NULL, NULL, NULL, NULL, NULL, NULL, '9876543212', NULL, NULL, NULL, NULL, NULL),
(51, 'Rajesh', 'Kumar', NULL, NULL, 'rajesh.contractor@email.com', '$2y$10$L/6KUg5b8IaULZQ/I1Fe0e4Sf180tMbkISAOcxFKIhOjP3DRfTT3O', 'contractor', 'pending', 0, NULL, NULL, '2025-12-21 08:07:06', '2025-12-21 08:07:06', NULL, NULL, NULL, NULL, NULL, NULL, '9876543220', NULL, NULL, NULL, NULL, NULL),
(52, 'Amit', 'Sharma', NULL, NULL, 'amit.contractor@email.com', '$2y$10$YfplXoJG37HgKw/QsNSjdeamhlqF2rD9gwBHm69lkifY7MoSjU4oi', 'contractor', 'pending', 0, NULL, NULL, '2025-12-21 08:07:06', '2025-12-21 08:07:06', NULL, NULL, NULL, NULL, NULL, NULL, '9876543221', NULL, NULL, NULL, NULL, NULL),
(53, 'Priya', 'Patel', NULL, NULL, 'priya.contractor@email.com', '$2y$10$7a4WZwfX9gPbXsC3nc2mWOWE/HX6tEPDbEbEcl9oQ.9Nby7Zqb4W.', 'contractor', 'pending', 0, NULL, NULL, '2025-12-21 08:07:06', '2025-12-21 08:07:06', NULL, NULL, NULL, NULL, NULL, NULL, '9876543222', NULL, NULL, NULL, NULL, NULL);

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `users_status_verify_consistency` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN
        -- If status is being set to 'approved', ensure is_verified is 1
        IF NEW.status = 'approved' AND OLD.status != 'approved' THEN
            SET NEW.is_verified = 1;
        END IF;
        
        -- If status is being set to 'pending', 'rejected', or 'suspended', ensure is_verified is 0
        IF NEW.status IN ('pending', 'rejected', 'suspended') AND OLD.status = 'approved' THEN
            SET NEW.is_verified = 0;
        END IF;
    END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `users_verify_status_consistency` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN
        -- If is_verified is being set to 1, ensure status is 'approved'
        IF NEW.is_verified = 1 AND (OLD.is_verified != 1 OR OLD.status != 'approved') THEN
            SET NEW.status = 'approved';
        END IF;
        
        -- If is_verified is being set to 0, ensure status is 'pending' (unless explicitly set to rejected/suspended)
        IF NEW.is_verified = 0 AND OLD.is_verified != 0 AND NEW.status NOT IN ('rejected', 'suspended') THEN
            SET NEW.status = 'pending';
        END IF;
    END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `worker_types`
--

CREATE TABLE `worker_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `category` enum('skilled','semi_skilled','unskilled') NOT NULL,
  `description` text DEFAULT NULL,
  `base_wage_per_day` decimal(8,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `worker_types`
--

INSERT INTO `worker_types` (`id`, `type_name`, `category`, `description`, `base_wage_per_day`, `created_at`) VALUES
(1, 'Mason', 'skilled', 'Skilled in brickwork, plastering, and masonry', 800.00, '2026-01-05 15:00:17'),
(2, 'Carpenter', 'skilled', 'Skilled in woodwork, formwork, and finishing', 750.00, '2026-01-05 15:00:17'),
(3, 'Electrician', 'skilled', 'Electrical installations and wiring', 900.00, '2026-01-05 15:00:17'),
(4, 'Plumber', 'skilled', 'Plumbing installations and pipe work', 850.00, '2026-01-05 15:00:17'),
(5, 'Welder', 'skilled', 'Metal welding and fabrication', 800.00, '2026-01-05 15:00:17'),
(6, 'Painter', 'skilled', 'Wall painting and finishing work', 600.00, '2026-01-05 15:00:17'),
(7, 'Tiler', 'skilled', 'Floor and wall tile installation', 700.00, '2026-01-05 15:00:17'),
(8, 'Steel Fixer', 'skilled', 'Reinforcement steel work', 750.00, '2026-01-05 15:00:17'),
(9, 'Assistant Mason', 'semi_skilled', 'Assists masons with mixing and preparation', 500.00, '2026-01-05 15:00:17'),
(10, 'Assistant Carpenter', 'semi_skilled', 'Assists carpenters with cutting and preparation', 450.00, '2026-01-05 15:00:17'),
(11, 'Assistant Electrician', 'semi_skilled', 'Assists with electrical installations', 550.00, '2026-01-05 15:00:17'),
(12, 'Assistant Plumber', 'semi_skilled', 'Assists with plumbing work', 500.00, '2026-01-05 15:00:17'),
(13, 'Machine Operator', 'semi_skilled', 'Operates construction machinery', 600.00, '2026-01-05 15:00:17'),
(14, 'Helper', 'unskilled', 'General construction helper', 350.00, '2026-01-05 15:00:17'),
(15, 'Laborer', 'unskilled', 'Manual labor and material handling', 300.00, '2026-01-05 15:00:17'),
(16, 'Cleaner', 'unskilled', 'Site cleaning and maintenance', 250.00, '2026-01-05 15:00:17'),
(17, 'Watchman', 'unskilled', 'Site security and monitoring', 300.00, '2026-01-05 15:00:17'),
(18, 'Material Handler', 'unskilled', 'Loading and unloading materials', 350.00, '2026-01-05 15:00:17');

-- --------------------------------------------------------

--
-- Structure for view `architect_request_details`
--
DROP TABLE IF EXISTS `architect_request_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `architect_request_details`  AS SELECT `lr`.`id` AS `id`, `lr`.`user_id` AS `user_id`, `lr`.`homeowner_id` AS `homeowner_id`, `lr`.`plot_size` AS `plot_size`, `lr`.`budget_range` AS `budget_range`, `lr`.`location` AS `location`, `lr`.`timeline` AS `timeline`, `lr`.`num_floors` AS `num_floors`, `lr`.`preferred_style` AS `preferred_style`, `lr`.`orientation` AS `orientation`, `lr`.`site_considerations` AS `site_considerations`, `lr`.`material_preferences` AS `material_preferences`, `lr`.`budget_allocation` AS `budget_allocation`, `lr`.`site_images` AS `site_images`, `lr`.`reference_images` AS `reference_images`, `lr`.`room_images` AS `room_images`, `lr`.`floor_rooms` AS `floor_rooms`, `lr`.`requirements` AS `requirements`, `lr`.`status` AS `status`, `lr`.`layout_type` AS `layout_type`, `lr`.`selected_layout_id` AS `selected_layout_id`, `lr`.`layout_file` AS `layout_file`, `lr`.`created_at` AS `created_at`, `lr`.`updated_at` AS `updated_at`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, `u`.`email` AS `email`, `u`.`phone` AS `phone`, `u`.`address` AS `address`, `u`.`city` AS `city`, `u`.`state` AS `state` FROM (`layout_requests` `lr` join `users` `u` on(`lr`.`homeowner_id` = `u`.`id`)) WHERE `lr`.`status` in ('pending','approved','active') ORDER BY `lr`.`created_at` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `architect_layouts`
--
ALTER TABLE `architect_layouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_architect_layouts_architect` (`architect_id`),
  ADD KEY `idx_architect_layouts_request` (`layout_request_id`),
  ADD KEY `idx_architect_layouts_status` (`status`);

--
-- Indexes for table `architect_reviews`
--
ALTER TABLE `architect_reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `construction_phases`
--
ALTER TABLE `construction_phases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_phase_name` (`phase_name`),
  ADD KEY `idx_phase_order` (`phase_order`);

--
-- Indexes for table `construction_progress_updates`
--
ALTER TABLE `construction_progress_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_contractor` (`project_id`,`contractor_id`),
  ADD KEY `idx_homeowner` (`homeowner_id`),
  ADD KEY `idx_stage` (`stage_name`,`stage_status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `construction_stage_payments`
--
ALTER TABLE `construction_stage_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_stage_name` (`stage_name`),
  ADD KEY `idx_stage_order` (`stage_order`);

--
-- Indexes for table `contractor_assignments`
--
ALTER TABLE `contractor_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contractor_assignments_request` (`layout_request_id`),
  ADD KEY `idx_contractor_assignments_contractor` (`contractor_id`),
  ADD KEY `idx_contractor_assignments_status` (`status`);

--
-- Indexes for table `contractor_assignment_hides`
--
ALTER TABLE `contractor_assignment_hides`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_assignment_contractor` (`assignment_id`,`contractor_id`),
  ADD KEY `idx_contractor` (`contractor_id`);

--
-- Indexes for table `contractor_estimate_payments`
--
ALTER TABLE `contractor_estimate_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `homeowner_id` (`homeowner_id`),
  ADD KEY `estimate_id` (`estimate_id`),
  ADD KEY `payment_status` (`payment_status`);

--
-- Indexes for table `contractor_inbox`
--
ALTER TABLE `contractor_inbox`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contractor_id` (`contractor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `contractor_layout_sends`
--
ALTER TABLE `contractor_layout_sends`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contractor_proposals`
--
ALTER TABLE `contractor_proposals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contractor_proposals_contractor` (`contractor_id`),
  ADD KEY `idx_contractor_proposals_request` (`layout_request_id`),
  ADD KEY `idx_contractor_proposals_status` (`status`);

--
-- Indexes for table `contractor_requests_queue`
--
ALTER TABLE `contractor_requests_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `layout_request_id` (`layout_request_id`),
  ADD KEY `homeowner_id` (`homeowner_id`),
  ADD KEY `contractor_id` (`contractor_id`);

--
-- Indexes for table `contractor_reviews`
--
ALTER TABLE `contractor_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contractor_reviews_contractor` (`contractor_id`),
  ADD KEY `idx_contractor_reviews_homeowner` (`homeowner_id`),
  ADD KEY `idx_contractor_reviews_request` (`layout_request_id`),
  ADD KEY `idx_contractor_reviews_rating` (`rating`);

--
-- Indexes for table `contractor_send_estimates`
--
ALTER TABLE `contractor_send_estimates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `send_id` (`send_id`);

--
-- Indexes for table `contractor_send_estimate_files`
--
ALTER TABLE `contractor_send_estimate_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estimate_id` (`estimate_id`);

--
-- Indexes for table `contractor_workers`
--
ALTER TABLE `contractor_workers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contractor_available` (`contractor_id`,`is_available`),
  ADD KEY `idx_worker_type_skill` (`worker_type_id`,`skill_level`);

--
-- Indexes for table `designs`
--
ALTER TABLE `designs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `layout_request_id` (`layout_request_id`),
  ADD KEY `architect_id` (`architect_id`);

--
-- Indexes for table `design_comments`
--
ALTER TABLE `design_comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `geo_photos`
--
ALTER TABLE `geo_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_contractor` (`project_id`,`contractor_id`),
  ADD KEY `idx_homeowner` (`homeowner_id`),
  ADD KEY `idx_upload_date` (`upload_timestamp`),
  ADD KEY `idx_location` (`latitude`,`longitude`),
  ADD KEY `idx_progress_update` (`progress_update_id`),
  ADD KEY `idx_viewed` (`homeowner_viewed`),
  ADD KEY `contractor_id` (`contractor_id`);

--
-- Indexes for table `homeowner_notifications`
--
ALTER TABLE `homeowner_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `homeowner_id` (`homeowner_id`),
  ADD KEY `status` (`status`),
  ADD KEY `type` (`type`);

--
-- Indexes for table `house_plans`
--
ALTER TABLE `house_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `layout_request_id` (`layout_request_id`),
  ADD KEY `parent_plan_id` (`parent_plan_id`),
  ADD KEY `idx_architect_request` (`architect_id`,`layout_request_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `house_plan_reviews`
--
ALTER TABLE `house_plan_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `homeowner_id` (`homeowner_id`),
  ADD KEY `idx_plan_homeowner` (`house_plan_id`,`homeowner_id`);

--
-- Indexes for table `layout_library`
--
ALTER TABLE `layout_library`
  ADD PRIMARY KEY (`id`),
  ADD KEY `architect_id` (`architect_id`);

--
-- Indexes for table `layout_library_technical_details`
--
ALTER TABLE `layout_library_technical_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `layout_library_id` (`layout_library_id`),
  ADD KEY `architect_id` (`architect_id`);

--
-- Indexes for table `layout_payments`
--
ALTER TABLE `layout_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `architect_id` (`architect_id`),
  ADD KEY `idx_layout_payments_homeowner` (`homeowner_id`),
  ADD KEY `idx_layout_payments_design` (`design_id`),
  ADD KEY `idx_layout_payments_status` (`payment_status`);

--
-- Indexes for table `layout_requests`
--
ALTER TABLE `layout_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_layout_requests_homeowner` (`homeowner_id`),
  ADD KEY `idx_layout_requests_status` (`status`),
  ADD KEY `idx_lr_user_id` (`user_id`),
  ADD KEY `idx_lr_homeowner_id` (`homeowner_id`),
  ADD KEY `idx_lr_selected_layout_id` (`selected_layout_id`),
  ADD KEY `idx_lr_status` (`status`),
  ADD KEY `idx_lr_created_at` (`created_at`),
  ADD KEY `idx_layout_requests_architect_view` (`status`,`created_at`),
  ADD KEY `idx_layout_requests_homeowner_status` (`homeowner_id`,`status`);

--
-- Indexes for table `layout_request_assignments`
--
ALTER TABLE `layout_request_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_lr_arch` (`layout_request_id`,`architect_id`),
  ADD KEY `homeowner_id` (`homeowner_id`),
  ADD KEY `architect_id` (`architect_id`),
  ADD KEY `idx_lra_request` (`layout_request_id`),
  ADD KEY `idx_lra_architect` (`architect_id`),
  ADD KEY `idx_lra_homeowner` (`homeowner_id`),
  ADD KEY `idx_lra_status` (`status`);

--
-- Indexes for table `layout_technical_details`
--
ALTER TABLE `layout_technical_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `design_id` (`design_id`),
  ADD KEY `architect_id` (`architect_id`);

--
-- Indexes for table `layout_templates`
--
ALTER TABLE `layout_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_user_id` (`from_user_id`),
  ADD KEY `to_user_id` (`to_user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `payment_notifications`
--
ALTER TABLE `payment_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipient_unread` (`recipient_id`,`is_read`),
  ADD KEY `idx_notification_type` (`notification_type`,`created_at`);

--
-- Indexes for table `phase_worker_requirements`
--
ALTER TABLE `phase_worker_requirements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_phase_worker` (`phase_id`,`worker_type_id`),
  ADD KEY `worker_type_id` (`worker_type_id`),
  ADD KEY `idx_phase_priority` (`phase_id`,`priority_level`);

--
-- Indexes for table `progress_reports`
--
ALTER TABLE `progress_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_contractor` (`project_id`,`contractor_id`),
  ADD KEY `idx_homeowner` (`homeowner_id`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_period_range` (`report_period_start`,`report_period_end`),
  ADD KEY `idx_project_type_period` (`project_id`,`report_type`,`report_period_start`);

--
-- Indexes for table `progress_worker_assignments`
--
ALTER TABLE `progress_worker_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_progress_worker` (`progress_update_id`,`worker_id`),
  ADD KEY `idx_work_date` (`work_date`),
  ADD KEY `idx_worker_date` (`worker_id`,`work_date`);

--
-- Indexes for table `project_locations`
--
ALTER TABLE `project_locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_location` (`project_id`);

--
-- Indexes for table `project_payment_schedule`
--
ALTER TABLE `project_payment_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_schedule` (`project_id`,`stage_name`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_completion` (`is_completed`,`due_date`);

--
-- Indexes for table `project_stage_payment_requests`
--
ALTER TABLE `project_stage_payment_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_stage` (`project_id`,`stage_name`),
  ADD KEY `idx_contractor_status` (`contractor_id`,`status`),
  ADD KEY `idx_homeowner_status` (`homeowner_id`,`status`),
  ADD KEY `idx_request_date` (`request_date`),
  ADD KEY `idx_status_date` (`status`,`request_date`);

--
-- Indexes for table `proposals`
--
ALTER TABLE `proposals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_templates`
--
ALTER TABLE `room_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `support_issues`
--
ALTER TABLE `support_issues`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `support_replies`
--
ALTER TABLE `support_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issue_id` (`issue_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `worker_types`
--
ALTER TABLE `worker_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_worker_type` (`type_name`),
  ADD KEY `idx_category` (`category`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `architect_layouts`
--
ALTER TABLE `architect_layouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `architect_reviews`
--
ALTER TABLE `architect_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `construction_phases`
--
ALTER TABLE `construction_phases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `construction_progress_updates`
--
ALTER TABLE `construction_progress_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `construction_stage_payments`
--
ALTER TABLE `construction_stage_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `contractor_assignments`
--
ALTER TABLE `contractor_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `contractor_assignment_hides`
--
ALTER TABLE `contractor_assignment_hides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contractor_estimate_payments`
--
ALTER TABLE `contractor_estimate_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `contractor_inbox`
--
ALTER TABLE `contractor_inbox`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contractor_layout_sends`
--
ALTER TABLE `contractor_layout_sends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `contractor_proposals`
--
ALTER TABLE `contractor_proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contractor_requests_queue`
--
ALTER TABLE `contractor_requests_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contractor_reviews`
--
ALTER TABLE `contractor_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contractor_send_estimates`
--
ALTER TABLE `contractor_send_estimates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `contractor_send_estimate_files`
--
ALTER TABLE `contractor_send_estimate_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contractor_workers`
--
ALTER TABLE `contractor_workers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `designs`
--
ALTER TABLE `designs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `design_comments`
--
ALTER TABLE `design_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `geo_photos`
--
ALTER TABLE `geo_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `homeowner_notifications`
--
ALTER TABLE `homeowner_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `house_plans`
--
ALTER TABLE `house_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `house_plan_reviews`
--
ALTER TABLE `house_plan_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `layout_library`
--
ALTER TABLE `layout_library`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `layout_library_technical_details`
--
ALTER TABLE `layout_library_technical_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `layout_payments`
--
ALTER TABLE `layout_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `layout_requests`
--
ALTER TABLE `layout_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `layout_request_assignments`
--
ALTER TABLE `layout_request_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `layout_technical_details`
--
ALTER TABLE `layout_technical_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `layout_templates`
--
ALTER TABLE `layout_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payment_notifications`
--
ALTER TABLE `payment_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `phase_worker_requirements`
--
ALTER TABLE `phase_worker_requirements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `progress_reports`
--
ALTER TABLE `progress_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `progress_worker_assignments`
--
ALTER TABLE `progress_worker_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_locations`
--
ALTER TABLE `project_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `project_payment_schedule`
--
ALTER TABLE `project_payment_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_stage_payment_requests`
--
ALTER TABLE `project_stage_payment_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proposals`
--
ALTER TABLE `proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `room_templates`
--
ALTER TABLE `room_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `support_issues`
--
ALTER TABLE `support_issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `support_replies`
--
ALTER TABLE `support_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `worker_types`
--
ALTER TABLE `worker_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `architect_layouts`
--
ALTER TABLE `architect_layouts`
  ADD CONSTRAINT `architect_layouts_ibfk_1` FOREIGN KEY (`architect_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `architect_layouts_ibfk_2` FOREIGN KEY (`layout_request_id`) REFERENCES `layout_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contractor_assignments`
--
ALTER TABLE `contractor_assignments`
  ADD CONSTRAINT `contractor_assignments_ibfk_1` FOREIGN KEY (`layout_request_id`) REFERENCES `layout_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contractor_assignments_ibfk_2` FOREIGN KEY (`contractor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contractor_proposals`
--
ALTER TABLE `contractor_proposals`
  ADD CONSTRAINT `contractor_proposals_ibfk_1` FOREIGN KEY (`contractor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contractor_proposals_ibfk_2` FOREIGN KEY (`layout_request_id`) REFERENCES `layout_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contractor_requests_queue`
--
ALTER TABLE `contractor_requests_queue`
  ADD CONSTRAINT `contractor_requests_queue_ibfk_1` FOREIGN KEY (`layout_request_id`) REFERENCES `layout_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contractor_requests_queue_ibfk_2` FOREIGN KEY (`homeowner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contractor_requests_queue_ibfk_3` FOREIGN KEY (`contractor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contractor_reviews`
--
ALTER TABLE `contractor_reviews`
  ADD CONSTRAINT `contractor_reviews_ibfk_1` FOREIGN KEY (`contractor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contractor_reviews_ibfk_2` FOREIGN KEY (`homeowner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contractor_reviews_ibfk_3` FOREIGN KEY (`layout_request_id`) REFERENCES `layout_requests` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contractor_workers`
--
ALTER TABLE `contractor_workers`
  ADD CONSTRAINT `contractor_workers_ibfk_1` FOREIGN KEY (`contractor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contractor_workers_ibfk_2` FOREIGN KEY (`worker_type_id`) REFERENCES `worker_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `designs`
--
ALTER TABLE `designs`
  ADD CONSTRAINT `designs_ibfk_1` FOREIGN KEY (`layout_request_id`) REFERENCES `layout_requests` (`id`),
  ADD CONSTRAINT `designs_ibfk_2` FOREIGN KEY (`architect_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `geo_photos`
--
ALTER TABLE `geo_photos`
  ADD CONSTRAINT `geo_photos_ibfk_1` FOREIGN KEY (`contractor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `geo_photos_ibfk_2` FOREIGN KEY (`homeowner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `house_plans`
--
ALTER TABLE `house_plans`
  ADD CONSTRAINT `house_plans_ibfk_1` FOREIGN KEY (`architect_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `house_plans_ibfk_2` FOREIGN KEY (`layout_request_id`) REFERENCES `layout_requests` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `house_plans_ibfk_3` FOREIGN KEY (`parent_plan_id`) REFERENCES `house_plans` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `house_plan_reviews`
--
ALTER TABLE `house_plan_reviews`
  ADD CONSTRAINT `house_plan_reviews_ibfk_1` FOREIGN KEY (`house_plan_id`) REFERENCES `house_plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `house_plan_reviews_ibfk_2` FOREIGN KEY (`homeowner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `layout_library`
--
ALTER TABLE `layout_library`
  ADD CONSTRAINT `layout_library_ibfk_1` FOREIGN KEY (`architect_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `layout_library_technical_details`
--
ALTER TABLE `layout_library_technical_details`
  ADD CONSTRAINT `layout_library_technical_details_ibfk_1` FOREIGN KEY (`layout_library_id`) REFERENCES `layout_library` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `layout_library_technical_details_ibfk_2` FOREIGN KEY (`architect_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `layout_payments`
--
ALTER TABLE `layout_payments`
  ADD CONSTRAINT `layout_payments_ibfk_1` FOREIGN KEY (`homeowner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `layout_payments_ibfk_2` FOREIGN KEY (`architect_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `layout_payments_ibfk_3` FOREIGN KEY (`design_id`) REFERENCES `designs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `layout_requests`
--
ALTER TABLE `layout_requests`
  ADD CONSTRAINT `fk_lr_homeowner` FOREIGN KEY (`homeowner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lr_selected_layout` FOREIGN KEY (`selected_layout_id`) REFERENCES `layout_library` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_lr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `layout_requests_ibfk_1` FOREIGN KEY (`homeowner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `layout_request_assignments`
--
ALTER TABLE `layout_request_assignments`
  ADD CONSTRAINT `layout_request_assignments_ibfk_1` FOREIGN KEY (`layout_request_id`) REFERENCES `layout_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `layout_request_assignments_ibfk_2` FOREIGN KEY (`homeowner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `layout_request_assignments_ibfk_3` FOREIGN KEY (`architect_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `layout_technical_details`
--
ALTER TABLE `layout_technical_details`
  ADD CONSTRAINT `layout_technical_details_ibfk_1` FOREIGN KEY (`design_id`) REFERENCES `designs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `layout_technical_details_ibfk_2` FOREIGN KEY (`architect_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `phase_worker_requirements`
--
ALTER TABLE `phase_worker_requirements`
  ADD CONSTRAINT `phase_worker_requirements_ibfk_1` FOREIGN KEY (`phase_id`) REFERENCES `construction_phases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `phase_worker_requirements_ibfk_2` FOREIGN KEY (`worker_type_id`) REFERENCES `worker_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `progress_worker_assignments`
--
ALTER TABLE `progress_worker_assignments`
  ADD CONSTRAINT `progress_worker_assignments_ibfk_1` FOREIGN KEY (`progress_update_id`) REFERENCES `construction_progress_updates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `progress_worker_assignments_ibfk_2` FOREIGN KEY (`worker_id`) REFERENCES `contractor_workers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
