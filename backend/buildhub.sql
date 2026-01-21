-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 20, 2026 at 06:29 PM
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
-- Stand-in structure for view `active_split_payments`
-- (See below for the actual view)
--
CREATE TABLE `active_split_payments` (
`id` int(11)
,`payment_type` enum('technical_details','stage_payment')
,`reference_id` int(11)
,`homeowner_id` int(11)
,`contractor_id` int(11)
,`total_amount` decimal(15,2)
,`currency` varchar(3)
,`country_code` varchar(2)
,`total_splits` int(11)
,`completed_splits` int(11)
,`completed_amount` decimal(15,2)
,`status` enum('pending','partial','completed','failed','cancelled')
,`description` text
,`metadata` longtext
,`created_at` timestamp
,`updated_at` timestamp
,`homeowner_first_name` varchar(100)
,`homeowner_last_name` varchar(100)
,`homeowner_email` varchar(255)
,`contractor_first_name` varchar(100)
,`contractor_last_name` varchar(100)
,`contractor_email` varchar(255)
);

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
-- Table structure for table `alternative_payments`
--

CREATE TABLE `alternative_payments` (
  `id` int(11) NOT NULL,
  `payment_type` enum('technical_details','stage_payment') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'INR',
  `payment_method` enum('bank_transfer','upi','cash','cheque','other') NOT NULL,
  `payment_status` enum('initiated','pending_verification','verified','completed','failed','cancelled') DEFAULT 'initiated',
  `transaction_reference` varchar(255) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `verification_required` tinyint(1) DEFAULT 1,
  `verification_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `payment_instructions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_instructions`)),
  `homeowner_notes` text DEFAULT NULL,
  `contractor_notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `receipt_file_path` varchar(500) DEFAULT NULL,
  `proof_file_path` varchar(500) DEFAULT NULL,
  `additional_files` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_files`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alternative_payments`
--

INSERT INTO `alternative_payments` (`id`, `payment_type`, `reference_id`, `homeowner_id`, `contractor_id`, `amount`, `currency`, `payment_method`, `payment_status`, `transaction_reference`, `payment_date`, `verification_required`, `verification_status`, `verified_by`, `verified_at`, `payment_instructions`, `homeowner_notes`, `contractor_notes`, `admin_notes`, `receipt_file_path`, `proof_file_path`, `additional_files`, `created_at`, `updated_at`) VALUES
(1, 'stage_payment', 13, 28, 29, 50000.00, 'INR', 'bank_transfer', 'initiated', NULL, NULL, 1, 'pending', NULL, NULL, '{\"title\":\"Bank Transfer Instructions\",\"steps\":[\"Login to your net banking or visit bank branch\",\"Select NEFT\\/RTGS transfer option\",\"Enter beneficiary details:\",\"  \\u2022 Account Name: Contractor Name\",\"  \\u2022 Account Number: 1234567890\",\"  \\u2022 IFSC Code: SBIN0001234\",\"  \\u2022 Bank: State Bank of India\",\"Enter amount: \\u20b950,000.00\",\"Add reference: Payment for Project #13\",\"Complete the transfer and save transaction receipt\",\"Upload receipt in the system for verification\"],\"processing_time\":\"1-2 business days\",\"verification_required\":true}', 'Payment for Foundation stage', NULL, NULL, NULL, NULL, NULL, '2026-01-11 16:22:32', '2026-01-11 16:22:32'),
(2, 'stage_payment', 13, 28, 29, 50000.00, 'INR', 'bank_transfer', 'initiated', NULL, NULL, 1, 'pending', NULL, NULL, '{\"title\":\"Bank Transfer Instructions\",\"steps\":[\"Login to your net banking or visit bank branch\",\"Select NEFT\\/RTGS transfer option\",\"Enter beneficiary details:\",\"  \\u2022 Account Name: Contractor Name\",\"  \\u2022 Account Number: 1234567890\",\"  \\u2022 IFSC Code: SBIN0001234\",\"  \\u2022 Bank: State Bank of India\",\"Enter amount: \\u20b950,000.00\",\"Add reference: Payment for Project #13\",\"Complete the transfer and save transaction receipt\",\"Upload receipt in the system for verification\"],\"processing_time\":\"1-2 business days\",\"verification_required\":true}', 'Payment for Foundation stage', NULL, NULL, NULL, NULL, NULL, '2026-01-11 16:34:42', '2026-01-11 16:34:42'),
(3, 'stage_payment', 13, 28, 29, 50000.00, 'INR', 'bank_transfer', 'initiated', NULL, NULL, 1, 'pending', NULL, NULL, '{\"title\":\"Bank Transfer Instructions\",\"steps\":[\"Login to your net banking or visit bank branch\",\"Select NEFT\\/RTGS transfer option\",\"Enter beneficiary details:\",\"  \\u2022 Account Name: Contractor Name\",\"  \\u2022 Account Number: 1234567890\",\"  \\u2022 IFSC Code: SBIN0001234\",\"  \\u2022 Bank: State Bank of India\",\"Enter amount: \\u20b950,000.00\",\"Add reference: Payment for Project #13\",\"Complete the transfer and save transaction receipt\",\"Upload receipt in the system for verification\"],\"processing_time\":\"1-2 business days\",\"verification_required\":true}', 'Payment for Foundation stage', NULL, NULL, NULL, NULL, NULL, '2026-01-13 08:48:12', '2026-01-13 08:48:12'),
(4, 'stage_payment', 14, 28, 29, 250.00, 'INR', 'upi', 'cancelled', NULL, NULL, 1, 'pending', NULL, NULL, '{\"title\":\"UPI Payment Instructions\",\"steps\":[\"Open your UPI app (PhonePe, GPay, Paytm, etc.)\",\"Select \\\"Send Money\\\" or \\\"Pay\\\" option\",\"Enter UPI ID: contractor@paytm\",\"Or scan QR code provided below\",\"Enter amount: \\u20b9250.00\",\"Add note: Payment for Project #14\",\"Complete payment using UPI PIN\",\"Take screenshot of success message\",\"Upload screenshot for verification\"],\"processing_time\":\"Instant\",\"verification_required\":true}', 'Payment for Structure stage', NULL, NULL, NULL, NULL, NULL, '2026-01-14 17:20:37', '2026-01-14 17:31:28'),
(5, 'stage_payment', 14, 28, 29, 250.00, 'INR', 'cheque', 'cancelled', NULL, NULL, 1, 'pending', NULL, NULL, '{\"title\":\"Cheque Payment Instructions\",\"steps\":[\"Write cheque for amount: \\u20b9250.00\",\"Make payable to: Contractor Name\",\"Write current date\",\"Add reference: Payment for Project #14\",\"Sign the cheque\",\"Hand over cheque to contractor or post it\",\"Take photo of cheque before giving\",\"Upload cheque photo for records\",\"Wait for cheque clearance (3-5 business days)\"],\"processing_time\":\"3-5 business days\",\"verification_required\":true}', 'Payment for Structure stage', NULL, NULL, NULL, NULL, NULL, '2026-01-14 17:26:11', '2026-01-14 17:31:28'),
(6, 'stage_payment', 15, 28, 29, 213949.00, 'INR', 'bank_transfer', 'initiated', NULL, NULL, 1, 'pending', NULL, NULL, '{\"title\":\"Bank Transfer Instructions\",\"steps\":[\"Login to your net banking or visit bank branch\",\"Select NEFT\\/RTGS transfer option\",\"Enter beneficiary details:\",\"  \\u2022 Account Name: Contractor Name\",\"  \\u2022 Account Number: 1234567890\",\"  \\u2022 IFSC Code: SBIN0001234\",\"  \\u2022 Bank: State Bank of India\",\"Enter amount: \\u20b9213,949.00\",\"Add reference: Payment for Project #15\",\"Complete the transfer and save transaction receipt\",\"Upload receipt in the system for verification\"],\"processing_time\":\"1-2 business days\",\"verification_required\":true}', 'Payment for Foundation stage', NULL, NULL, NULL, NULL, NULL, '2026-01-20 07:42:38', '2026-01-20 07:42:38'),
(7, 'stage_payment', 15, 28, 29, 213949.00, 'INR', 'bank_transfer', 'initiated', NULL, NULL, 1, 'pending', NULL, NULL, '{\"title\":\"Bank Transfer Instructions\",\"steps\":[\"Login to your net banking or visit bank branch\",\"Select NEFT\\/RTGS transfer option\",\"Enter beneficiary details:\",\"  \\u2022 Account Name: Contractor Name\",\"  \\u2022 Account Number: 1234567890\",\"  \\u2022 IFSC Code: SBIN0001234\",\"  \\u2022 Bank: State Bank of India\",\"Enter amount: \\u20b9213,949.00\",\"Add reference: Payment for Project #15\",\"Complete the transfer and save transaction receipt\",\"Upload receipt in the system for verification\"],\"processing_time\":\"1-2 business days\",\"verification_required\":true}', 'Payment for Foundation stage', NULL, NULL, NULL, NULL, NULL, '2026-01-20 08:12:03', '2026-01-20 08:12:03'),
(8, 'stage_payment', 16, 28, 29, 250000.00, 'INR', 'bank_transfer', 'initiated', NULL, NULL, 1, 'pending', NULL, NULL, '{\"title\":\"Bank Transfer Instructions\",\"steps\":[\"Login to your net banking or visit bank branch\",\"Select NEFT\\/RTGS transfer option\",\"Enter beneficiary details:\",\"  \\u2022 Account Name: Contractor Name\",\"  \\u2022 Account Number: 1234567890\",\"  \\u2022 IFSC Code: SBIN0001234\",\"  \\u2022 Bank: State Bank of India\",\"Enter amount: \\u20b9250,000.00\",\"Add reference: Payment for Project #16\",\"Complete the transfer and save transaction receipt\",\"Upload receipt in the system for verification\"],\"processing_time\":\"1-2 business days\",\"verification_required\":true}', 'Payment for Structure stage', NULL, NULL, NULL, NULL, NULL, '2026-01-20 08:53:19', '2026-01-20 08:53:19'),
(9, 'stage_payment', 1, 28, 29, 1000000.00, 'INR', 'bank_transfer', 'initiated', NULL, NULL, 1, 'pending', NULL, NULL, '{\"title\":\"Bank Transfer Instructions\",\"steps\":[\"Login to your net banking or visit bank branch\",\"Select NEFT\\/RTGS transfer option\",\"Enter beneficiary details:\",\"  \\u2022 Account Name: Contractor Name\",\"  \\u2022 Account Number: 1234567890\",\"  \\u2022 IFSC Code: SBIN0001234\",\"  \\u2022 Bank: State Bank of India\",\"Enter amount: \\u20b91,000,000.00\",\"Add reference: Payment for Project #1\",\"Complete the transfer and save transaction receipt\",\"Upload receipt in the system for verification\"],\"processing_time\":\"1-2 business days\",\"verification_required\":true}', 'Payment for Foundation stage', NULL, NULL, NULL, NULL, NULL, '2026-01-20 09:11:31', '2026-01-20 09:11:31'),
(10, 'stage_payment', 16, 28, 29, 250000.00, 'INR', 'bank_transfer', 'initiated', NULL, NULL, 1, 'pending', NULL, NULL, '{\"title\":\"Bank Transfer Instructions\",\"steps\":[\"Login to your net banking or visit bank branch\",\"Select NEFT\\/RTGS transfer option\",\"Enter beneficiary details:\",\"  \\u2022 Account Name: Contractor Name\",\"  \\u2022 Account Number: 1234567890\",\"  \\u2022 IFSC Code: SBIN0001234\",\"  \\u2022 Bank: State Bank of India\",\"Enter amount: \\u20b9250,000.00\",\"Add reference: Payment for Project #16\",\"Complete the transfer and save transaction receipt\",\"Upload receipt in the system for verification\"],\"processing_time\":\"1-2 business days\",\"verification_required\":true}', 'Payment for Structure stage', NULL, NULL, NULL, NULL, NULL, '2026-01-20 09:12:30', '2026-01-20 09:12:30'),
(11, 'stage_payment', 15, 28, 29, 213949.00, 'INR', 'bank_transfer', 'initiated', NULL, NULL, 1, 'pending', NULL, NULL, '{\"title\":\"Bank Transfer Instructions\",\"steps\":[\"Login to your net banking or visit bank branch\",\"Select NEFT\\/RTGS transfer option\",\"Enter beneficiary details:\",\"  \\u2022 Account Name: Contractor Name\",\"  \\u2022 Account Number: 1234567890\",\"  \\u2022 IFSC Code: SBIN0001234\",\"  \\u2022 Bank: State Bank of India\",\"Enter amount: \\u20b9213,949.00\",\"Add reference: Payment for Project #15\",\"Complete the transfer and save transaction receipt\",\"Upload receipt in the system for verification\"],\"processing_time\":\"1-2 business days\",\"verification_required\":true}', 'Payment for Foundation stage', NULL, NULL, NULL, NULL, NULL, '2026-01-20 10:08:13', '2026-01-20 10:08:13');

-- --------------------------------------------------------

--
-- Table structure for table `alternative_payment_notifications`
--

CREATE TABLE `alternative_payment_notifications` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `recipient_type` enum('homeowner','contractor','admin') NOT NULL,
  `notification_type` enum('payment_initiated','verification_required','payment_verified','payment_completed','payment_failed') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alternative_payment_notifications`
--

INSERT INTO `alternative_payment_notifications` (`id`, `payment_id`, `recipient_id`, `recipient_type`, `notification_type`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 29, 'contractor', 'payment_initiated', 'New Bank Transfer (NEFT/RTGS) Payment', 'Homeowner has initiated a Bank Transfer (NEFT/RTGS) payment of ₹50,000.00 for Stage Payment: Foundation. Please check payment details and provide verification once received.', 0, '2026-01-11 16:22:32'),
(2, 1, 28, 'homeowner', 'payment_initiated', 'Payment Instructions Ready', 'Your Bank Transfer (NEFT/RTGS) payment of ₹50,000.00 has been set up. Please follow the instructions to complete the payment.', 0, '2026-01-11 16:22:32'),
(3, 2, 29, 'contractor', 'payment_initiated', 'New Bank Transfer (NEFT/RTGS) Payment', 'Homeowner has initiated a Bank Transfer (NEFT/RTGS) payment of ₹50,000.00 for Stage Payment: Foundation. Please check payment details and provide verification once received.', 0, '2026-01-11 16:34:42'),
(4, 2, 28, 'homeowner', 'payment_initiated', 'Payment Instructions Ready', 'Your Bank Transfer (NEFT/RTGS) payment of ₹50,000.00 has been set up. Please follow the instructions to complete the payment.', 0, '2026-01-11 16:34:42'),
(5, 3, 29, 'contractor', 'payment_initiated', 'New Bank Transfer (NEFT/RTGS) Payment', 'Homeowner has initiated a Bank Transfer (NEFT/RTGS) payment of ₹50,000.00 for Stage Payment: Foundation. Please check payment details and provide verification once received.', 0, '2026-01-13 08:48:13'),
(6, 3, 28, 'homeowner', 'payment_initiated', 'Payment Instructions Ready', 'Your Bank Transfer (NEFT/RTGS) payment of ₹50,000.00 has been set up. Please follow the instructions to complete the payment.', 0, '2026-01-13 08:48:13'),
(7, 4, 29, 'contractor', 'payment_initiated', 'New UPI Payment Payment', 'Homeowner has initiated a UPI Payment payment of ₹250.00 for Stage Payment: Structure. Please check payment details and provide verification once received.', 0, '2026-01-14 17:20:37'),
(8, 4, 28, 'homeowner', 'payment_initiated', 'Payment Instructions Ready', 'Your UPI Payment payment of ₹250.00 has been set up. Please follow the instructions to complete the payment.', 0, '2026-01-14 17:20:37'),
(9, 5, 29, 'contractor', 'payment_initiated', 'New Cheque Payment Payment', 'Homeowner has initiated a Cheque Payment payment of ₹250.00 for Stage Payment: Structure. Please check payment details and provide verification once received.', 0, '2026-01-14 17:26:11'),
(10, 5, 28, 'homeowner', 'payment_initiated', 'Payment Instructions Ready', 'Your Cheque Payment payment of ₹250.00 has been set up. Please follow the instructions to complete the payment.', 0, '2026-01-14 17:26:11'),
(11, 6, 29, 'contractor', 'payment_initiated', 'New Bank Transfer (NEFT/RTGS) Payment', 'Homeowner has initiated a Bank Transfer (NEFT/RTGS) payment of ₹213,949.00 for Stage Payment: Foundation. Please check payment details and provide verification once received.', 0, '2026-01-20 07:42:38'),
(12, 6, 28, 'homeowner', 'payment_initiated', 'Payment Instructions Ready', 'Your Bank Transfer (NEFT/RTGS) payment of ₹213,949.00 has been set up. Please follow the instructions to complete the payment.', 0, '2026-01-20 07:42:38'),
(13, 7, 29, 'contractor', 'payment_initiated', 'New Bank Transfer (NEFT/RTGS) Payment', 'Homeowner has initiated a Bank Transfer (NEFT/RTGS) payment of ₹213,949.00 for Stage Payment: Foundation. Please check payment details and provide verification once received.', 0, '2026-01-20 08:12:03'),
(14, 7, 28, 'homeowner', 'payment_initiated', 'Payment Instructions Ready', 'Your Bank Transfer (NEFT/RTGS) payment of ₹213,949.00 has been set up. Please follow the instructions to complete the payment.', 0, '2026-01-20 08:12:03'),
(15, 8, 29, 'contractor', 'payment_initiated', 'New Bank Transfer (NEFT/RTGS) Payment', 'Homeowner has initiated a Bank Transfer (NEFT/RTGS) payment of ₹250,000.00 for Stage Payment: Structure. Please check payment details and provide verification once received.', 0, '2026-01-20 08:53:19'),
(16, 8, 28, 'homeowner', 'payment_initiated', 'Payment Instructions Ready', 'Your Bank Transfer (NEFT/RTGS) payment of ₹250,000.00 has been set up. Please follow the instructions to complete the payment.', 0, '2026-01-20 08:53:19'),
(17, 9, 29, 'contractor', 'payment_initiated', 'New Bank Transfer (NEFT/RTGS) Payment', 'Homeowner has initiated a Bank Transfer (NEFT/RTGS) payment of ₹1,000,000.00 for Stage Payment: Foundation. Please check payment details and provide verification once received.', 0, '2026-01-20 09:11:31'),
(18, 9, 28, 'homeowner', 'payment_initiated', 'Payment Instructions Ready', 'Your Bank Transfer (NEFT/RTGS) payment of ₹1,000,000.00 has been set up. Please follow the instructions to complete the payment.', 0, '2026-01-20 09:11:31'),
(19, 10, 29, 'contractor', 'payment_initiated', 'New Bank Transfer (NEFT/RTGS) Payment', 'Homeowner has initiated a Bank Transfer (NEFT/RTGS) payment of ₹250,000.00 for Stage Payment: Structure. Please check payment details and provide verification once received.', 0, '2026-01-20 09:12:30'),
(20, 10, 28, 'homeowner', 'payment_initiated', 'Payment Instructions Ready', 'Your Bank Transfer (NEFT/RTGS) payment of ₹250,000.00 has been set up. Please follow the instructions to complete the payment.', 0, '2026-01-20 09:12:30'),
(21, 11, 29, 'contractor', 'payment_initiated', 'New Bank Transfer (NEFT/RTGS) Payment', 'Homeowner has initiated a Bank Transfer (NEFT/RTGS) payment of ₹213,949.00 for Stage Payment: Foundation. Please check payment details and provide verification once received.', 0, '2026-01-20 10:08:13'),
(22, 11, 28, 'homeowner', 'payment_initiated', 'Payment Instructions Ready', 'Your Bank Transfer (NEFT/RTGS) payment of ₹213,949.00 has been set up. Please follow the instructions to complete the payment.', 0, '2026-01-20 10:08:13');

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
-- Table structure for table `concept_previews`
--

CREATE TABLE `concept_previews` (
  `id` int(11) NOT NULL,
  `layout_request_id` int(11) NOT NULL,
  `house_plan_id` int(11) DEFAULT NULL,
  `architect_id` int(11) NOT NULL,
  `job_id` varchar(255) NOT NULL,
  `original_description` text DEFAULT NULL,
  `refined_prompt` text DEFAULT NULL,
  `prompt_text` text NOT NULL,
  `requirements_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`requirements_snapshot`)),
  `image_url` varchar(500) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `status` enum('processing','generating','completed','failed') DEFAULT 'processing',
  `is_approved` tinyint(1) DEFAULT 0,
  `is_placeholder` tinyint(1) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `concept_previews`
--

INSERT INTO `concept_previews` (`id`, `layout_request_id`, `house_plan_id`, `architect_id`, `job_id`, `original_description`, `refined_prompt`, `prompt_text`, `requirements_snapshot`, `image_url`, `image_path`, `status`, `is_approved`, `is_placeholder`, `error_message`, `created_at`, `updated_at`) VALUES
(5, 105, NULL, 27, 'concept_696cd0bb0f816_1768739003', 'A HOUSE WITH GREEN LAWN CAR PARKING IN A ROAD SIDE', 'Architectural exterior concept: A HOUSE WITH GREEN LAWN CAR PARKING IN A ROAD SIDE', 'Architectural exterior concept: A HOUSE WITH GREEN LAWN CAR PARKING IN A ROAD SIDE', '{\"plot_size\":\"20\",\"budget_range\":\"50-75 Lakhs\",\"requirements\":{\"plot_shape\":\"Rectangular\",\"topography\":\"Flat\",\"development_laws\":\"Standard\",\"family_needs\":\"\",\"rooms\":\"master_bedroom,bedrooms,bathrooms,attached_bathroom,kitchen,living_room,dining_room,study_room,prayer_room,guest_room,store_room,balcony,terrace,garage\",\"aesthetic\":\"Modern\",\"notes\":\"\",\"orientation\":null,\"site_considerations\":null,\"material_preferences\":null,\"budget_allocation\":null,\"num_floors\":\"2\",\"preferred_style\":\"Modern\",\"floor_rooms\":{\"floor1\":{\"master_bedroom\":1,\"bedrooms\":1,\"bathrooms\":1,\"attached_bathroom\":1,\"kitchen\":1,\"living_room\":1,\"dining_room\":1,\"store_room\":1,\"garage\":1},\"floor2\":{\"study_room\":1,\"prayer_room\":1,\"guest_room\":1,\"balcony\":1,\"terrace\":1,\"bedrooms\":1,\"bathrooms\":1,\"attached_bathroom\":1}},\"site_images\":[],\"reference_images\":[],\"room_images\":[]},\"snapshot_created_at\":\"2026-01-18 13:23:23\"}', '/buildhub/uploads/conceptual_images/real_ai_exterior_concept_20260118_182931.png', 'uploads/conceptual_images/real_ai_exterior_concept_20260118_182931.png', 'completed', 0, 0, NULL, '2026-01-18 12:23:23', '2026-01-18 14:54:08'),
(6, 105, NULL, 27, 'concept_696cd9910bdc8_1768741265', 'A HOUSE WITH GREEN LAWN CAR PARKING IN A ROAD SIDE', 'Architectural exterior concept: A HOUSE WITH GREEN LAWN CAR PARKING IN A ROAD SIDE', 'Architectural exterior concept: A HOUSE WITH GREEN LAWN CAR PARKING IN A ROAD SIDE', '{\"plot_size\":\"20\",\"budget_range\":\"50-75 Lakhs\",\"requirements\":{\"plot_shape\":\"Rectangular\",\"topography\":\"Flat\",\"development_laws\":\"Standard\",\"family_needs\":\"\",\"rooms\":\"master_bedroom,bedrooms,bathrooms,attached_bathroom,kitchen,living_room,dining_room,study_room,prayer_room,guest_room,store_room,balcony,terrace,garage\",\"aesthetic\":\"Modern\",\"notes\":\"\",\"orientation\":null,\"site_considerations\":null,\"material_preferences\":null,\"budget_allocation\":null,\"num_floors\":\"2\",\"preferred_style\":\"Modern\",\"floor_rooms\":{\"floor1\":{\"master_bedroom\":1,\"bedrooms\":1,\"bathrooms\":1,\"attached_bathroom\":1,\"kitchen\":1,\"living_room\":1,\"dining_room\":1,\"store_room\":1,\"garage\":1},\"floor2\":{\"study_room\":1,\"prayer_room\":1,\"guest_room\":1,\"balcony\":1,\"terrace\":1,\"bedrooms\":1,\"bathrooms\":1,\"attached_bathroom\":1}},\"site_images\":[],\"reference_images\":[],\"room_images\":[]},\"snapshot_created_at\":\"2026-01-18 14:01:05\"}', '/buildhub/uploads/conceptual_images/real_ai_exterior_concept_20260118_180828.png', 'uploads/conceptual_images/real_ai_exterior_concept_20260118_180828.png', 'completed', 0, 0, NULL, '2026-01-18 13:01:05', '2026-01-18 14:54:08');

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

--
-- Dumping data for table `construction_progress_updates`
--

INSERT INTO `construction_progress_updates` (`id`, `project_id`, `contractor_id`, `homeowner_id`, `stage_name`, `stage_status`, `completion_percentage`, `remarks`, `delay_reason`, `delay_description`, `photo_paths`, `latitude`, `longitude`, `location_verified`, `created_at`, `updated_at`) VALUES
(3, 30, 51, 48, 'Foundation', 'In Progress', 25.00, 'Day 1: Foundation progress update - 25%', NULL, NULL, NULL, NULL, NULL, 0, '2026-01-16 17:30:54', '2026-01-17 17:30:54'),
(4, 30, 51, 48, 'Foundation', 'In Progress', 60.00, 'Day 3: Foundation progress update - 60%', NULL, NULL, NULL, NULL, NULL, 0, '2026-01-14 17:30:54', '2026-01-17 17:30:54'),
(5, 30, 51, 48, 'Foundation', 'Completed', 100.00, 'Day 5: Foundation progress update - 100%', NULL, NULL, NULL, NULL, NULL, 0, '2026-01-12 17:30:54', '2026-01-17 17:30:54'),
(6, 30, 51, 48, 'Structure', 'In Progress', 30.00, 'Day 7: Structure progress update - 30%', NULL, NULL, NULL, NULL, NULL, 0, '2026-01-10 17:30:54', '2026-01-17 17:30:54'),
(7, 30, 51, 48, 'Structure', 'In Progress', 75.00, 'Day 10: Structure progress update - 75%', NULL, NULL, NULL, NULL, NULL, 0, '2026-01-07 17:30:54', '2026-01-17 17:30:54'),
(8, 30, 51, 48, 'Walls', 'In Progress', 20.00, 'Day 12: Walls progress update - 20%', NULL, NULL, NULL, NULL, NULL, 0, '2026-01-05 17:30:54', '2026-01-17 17:30:54'),
(9, 30, 51, 48, 'Walls', 'In Progress', 45.00, 'Day 14: Walls progress update - 45%', NULL, NULL, NULL, NULL, NULL, 0, '2026-01-03 17:30:54', '2026-01-17 17:30:54');

-- --------------------------------------------------------

--
-- Table structure for table `construction_projects`
--

CREATE TABLE `construction_projects` (
  `id` int(11) NOT NULL,
  `estimate_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `project_description` text DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `timeline` varchar(255) DEFAULT NULL,
  `status` enum('created','in_progress','completed','on_hold','cancelled') DEFAULT 'created',
  `start_date` date DEFAULT NULL,
  `expected_completion_date` date DEFAULT NULL,
  `actual_completion_date` date DEFAULT NULL,
  `materials` text DEFAULT NULL,
  `cost_breakdown` text DEFAULT NULL,
  `structured_data` longtext DEFAULT NULL,
  `contractor_notes` text DEFAULT NULL,
  `homeowner_name` varchar(255) DEFAULT NULL,
  `homeowner_email` varchar(255) DEFAULT NULL,
  `homeowner_phone` varchar(50) DEFAULT NULL,
  `project_location` text DEFAULT NULL,
  `plot_size` varchar(100) DEFAULT NULL,
  `budget_range` varchar(100) DEFAULT NULL,
  `preferred_style` varchar(100) DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `layout_id` int(11) DEFAULT NULL,
  `design_id` int(11) DEFAULT NULL,
  `layout_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`layout_images`)),
  `technical_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`technical_details`)),
  `current_stage` varchar(100) DEFAULT 'Planning',
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `last_update_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `construction_projects`
--

INSERT INTO `construction_projects` (`id`, `estimate_id`, `contractor_id`, `homeowner_id`, `project_name`, `project_description`, `total_cost`, `timeline`, `status`, `start_date`, `expected_completion_date`, `actual_completion_date`, `materials`, `cost_breakdown`, `structured_data`, `contractor_notes`, `homeowner_name`, `homeowner_email`, `homeowner_phone`, `project_location`, `plot_size`, `budget_range`, `preferred_style`, `requirements`, `layout_id`, `design_id`, `layout_images`, `technical_details`, `current_stage`, `completion_percentage`, `last_update_date`, `created_at`, `updated_at`) VALUES
(1, 36, 29, 28, 'SHIJIN THOMAS MCA2024-2026 Construction', 'Construction project for SHIJIN THOMAS MCA2024-2026', NULL, '6 months', 'created', NULL, '2026-07-10', NULL, NULL, NULL, '{\"project_name\":\"SHIJIN THOMAS MCA2024-2026 Construction\",\"project_address\":\"\",\"plot_size\":\"\",\"built_up_area\":\"\",\"floors\":\"\",\"estimation_date\":\"\",\"client_name\":\"SHIJIN THOMAS MCA2024-2026\",\"client_contact\":\"shijinthomas2026@mca.ajce.in\",\"materials\":{\"cement\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"sand\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"bricks\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"steel\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"aggregate\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"tiles\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"paint\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"doors\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"windows\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"labor\":{\"mason\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plaster\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"painting\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plumbing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"flooring\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"roofing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"utilities\":{\"sanitary\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"kitchen\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical_fixtures\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"water_tank\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"hvac\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"gas_water\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"misc\":{\"transport\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"contingency\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"fees\":{\"name\":\"\",\"amount\":\"\"},\"cleaning\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"safety\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"totals\":{\"materials\":\"\",\"labor\":\"\",\"utilities\":\"\",\"misc\":\"\",\"grand\":\"\"},\"brands\":\"\"}', NULL, 'SHIJIN THOMAS MCA2024-2026', 'shijinthomas2026@mca.ajce.in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Planning', 0.00, '2026-01-11 09:01:07', '2026-01-11 09:01:07', '2026-01-11 09:01:07'),
(2, 37, 29, 28, 'SHIJIN THOMAS MCA2024-2026 Construction', 'Construction project for SHIJIN THOMAS MCA2024-2026', NULL, '6 months', 'created', NULL, '2026-07-13', NULL, NULL, NULL, '{\"project_name\":\"SHIJIN THOMAS MCA2024-2026 Construction\",\"project_address\":\"jnbn\",\"plot_size\":\"2800\",\"built_up_area\":\"20\",\"floors\":\"2\",\"estimation_date\":\"2026-01-15\",\"client_name\":\"SHIJIN THOMAS MCA2024-2026\",\"client_contact\":\"shijinthomas2026@mca.ajce.in\",\"materials\":{\"cement\":{\"name\":\"OPC 43 grade - 50 bags\",\"qty\":\"50\",\"rate\":\"380\",\"amount\":\"19000\"},\"sand\":{\"name\":\"River sand - 5 m\\u00b3\",\"qty\":\"6\",\"rate\":\"2500\",\"amount\":\"15000\"},\"bricks\":{\"name\":\"Clay bricks - 5000 nos\",\"qty\":\"5000\",\"rate\":\"10\",\"amount\":\"50000\"},\"steel\":{\"name\":\"TMT 8\\/10\\/12mm - 1500 kg\",\"qty\":\"1200\",\"rate\":\"68\",\"amount\":\"81600\"},\"aggregate\":{\"name\":\"20mm aggregate - 8 m\\u00b3\",\"qty\":\"34\",\"rate\":\"1230\",\"amount\":\"41820\"},\"tiles\":{\"name\":\"Vitrified tiles - 120 m\\u00b2\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"paint\":{\"name\":\"Interior emulsion - 80 L\",\"qty\":\"80\",\"rate\":\"250\",\"amount\":\"20000\"},\"doors\":{\"name\":\"Teakwood doors - 10 nos\",\"qty\":\"10\",\"rate\":\"7000\",\"amount\":\"70000\"},\"windows\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"labor\":{\"mason\":{\"name\":\"Masonry - \\u20b9\\/m\\u00b3\",\"qty\":\"5\",\"rate\":\"90\",\"amount\":\"450\"},\"plaster\":{\"name\":\"Internal plaster - \\u20b9\\/m\\u00b2\",\"qty\":\"2\",\"rate\":\"90\",\"amount\":\"180\"},\"painting\":{\"name\":\"2-coat interior - \\u20b9\\/m\\u00b2\",\"qty\":\"3\",\"rate\":\"90\",\"amount\":\"270\"},\"electrical\":{\"name\":\"Per point - \\u20b9\\/pt\",\"qty\":\"5\",\"rate\":\"5\",\"amount\":\"25\"},\"plumbing\":{\"name\":\"Per fitting - \\u20b9\\/fit\",\"qty\":\"5\",\"rate\":\"5\",\"amount\":\"25\"},\"flooring\":{\"name\":\"Flooring install - \\u20b9\\/m\\u00b2\",\"qty\":\"5\",\"rate\":\"5\",\"amount\":\"25\"},\"roofing\":{\"name\":\"Roof sheet install - \\u20b9\\/m\\u00b2\",\"qty\":\"12\",\"rate\":\"500\",\"amount\":\"6000\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"utilities\":{\"sanitary\":{\"name\":\"WC, basin, shower set\",\"qty\":\"6\",\"rate\":\"9000\",\"amount\":\"54000\"},\"kitchen\":{\"name\":\"Modular kitchen - 12ft\",\"qty\":\"6\",\"rate\":\"100000\",\"amount\":\"600000\"},\"electrical_fixtures\":{\"name\":\"LED panels, fans, switches\",\"qty\":\"10\",\"rate\":\"10\",\"amount\":\"100\"},\"water_tank\":{\"name\":\"Overhead tank 1000L + pump\",\"qty\":\"10\",\"rate\":\"10000\",\"amount\":\"100000\"},\"hvac\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"gas_water\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"misc\":{\"transport\":{\"name\":\"Material transport local\",\"qty\":\"20\",\"rate\":\"50\",\"amount\":\"1000\"},\"contingency\":{\"name\":\"5% buffer\",\"qty\":\"5\",\"rate\":\"50\",\"amount\":\"250\"},\"fees\":{\"name\":\"Permit & registration\",\"amount\":\"10000\"},\"cleaning\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"safety\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"totals\":{\"materials\":\"297420\",\"labor\":\"6975\",\"utilities\":\"754100\",\"misc\":\"11250\",\"grand\":\"1069745\"},\"brands\":\"\"}', NULL, 'SHIJIN THOMAS MCA2024-2026', 'shijinthomas2026@mca.ajce.in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Planning', 0.00, '2026-01-14 15:57:24', '2026-01-14 15:57:24', '2026-01-14 15:57:24');

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
-- Table structure for table `contractor_bank_details`
--

CREATE TABLE `contractor_bank_details` (
  `id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `ifsc_code` varchar(20) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `account_type` enum('savings','current') DEFAULT 'savings',
  `upi_id` varchar(100) DEFAULT NULL,
  `upi_verified` tinyint(1) DEFAULT 0,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `pan_number` varchar(20) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contractor_bank_details`
--

INSERT INTO `contractor_bank_details` (`id`, `contractor_id`, `account_name`, `account_number`, `ifsc_code`, `bank_name`, `branch_name`, `account_type`, `upi_id`, `upi_verified`, `is_verified`, `verified_by`, `verified_at`, `pan_number`, `gstin`, `created_at`, `updated_at`) VALUES
(1, 1, 'ABC Construction Pvt Ltd', '1234567890123456', 'SBIN0001234', 'State Bank of India', 'Main Branch', 'savings', 'abcconstruction@paytm', 0, 1, NULL, NULL, NULL, NULL, '2026-01-11 16:16:29', '2026-01-11 16:16:29'),
(2, 2, 'XYZ Builders', '9876543210987654', 'HDFC0002345', 'HDFC Bank', 'Commercial Branch', 'savings', 'xyzbuilders@phonepe', 0, 1, NULL, NULL, NULL, NULL, '2026-01-11 16:16:29', '2026-01-11 16:16:29');

-- --------------------------------------------------------

--
-- Table structure for table `contractor_engagements`
--

CREATE TABLE `contractor_engagements` (
  `id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `layout_request_id` int(11) DEFAULT NULL,
  `house_plan_id` int(11) DEFAULT NULL,
  `engagement_type` varchar(50) DEFAULT 'estimate_request',
  `status` varchar(50) DEFAULT 'pending',
  `message` text DEFAULT NULL,
  `project_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`project_details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contractor_engagements`
--

INSERT INTO `contractor_engagements` (`id`, `homeowner_id`, `contractor_id`, `layout_request_id`, `house_plan_id`, `engagement_type`, `status`, `message`, `project_details`, `created_at`, `updated_at`) VALUES
(1, 19, 29, NULL, 12, 'estimate_request', 'pending', 'Please provide construction estimate for this house plan', NULL, '2026-01-18 06:45:56', '2026-01-18 06:45:56');

-- --------------------------------------------------------

--
-- Table structure for table `contractor_estimates`
--

CREATE TABLE `contractor_estimates` (
  `id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `send_id` int(11) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `client_contact` varchar(255) DEFAULT NULL,
  `project_type` varchar(100) DEFAULT NULL,
  `timeline` varchar(100) DEFAULT NULL,
  `materials_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`materials_data`)),
  `labor_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`labor_data`)),
  `utilities_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`utilities_data`)),
  `misc_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`misc_data`)),
  `totals_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`totals_data`)),
  `structured_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`structured_data`)),
  `materials` text DEFAULT NULL,
  `cost_breakdown` text DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `status` enum('draft','submitted','accepted','rejected') DEFAULT 'submitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contractor_estimates`
--

INSERT INTO `contractor_estimates` (`id`, `contractor_id`, `homeowner_id`, `send_id`, `project_name`, `location`, `client_name`, `client_contact`, `project_type`, `timeline`, `materials_data`, `labor_data`, `utilities_data`, `misc_data`, `totals_data`, `structured_data`, `materials`, `cost_breakdown`, `total_cost`, `notes`, `terms`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'Test Construction Project', '123 Test Street, Test City', 'Test Homeowner', 'test@example.com', NULL, '90 days', '{\"cement\":{\"name\":\"Cement (OPC 43 Grade)\",\"qty\":\"50\",\"rate\":\"400\",\"amount\":\"20000\"},\"sand\":{\"name\":\"Sand (River Sand)\",\"qty\":\"5\",\"rate\":\"2000\",\"amount\":\"10000\"},\"bricks\":{\"name\":\"Bricks (Red Clay)\",\"qty\":\"2000\",\"rate\":\"8\",\"amount\":\"16000\"}}', '{\"masonry\":{\"name\":\"Masonry Work\",\"qty\":\"1\",\"rate\":\"15000\",\"amount\":\"15000\"},\"plumbing\":{\"name\":\"Plumbing Work\",\"qty\":\"1\",\"rate\":\"12000\",\"amount\":\"12000\"},\"electrical\":{\"name\":\"Electrical Work\",\"qty\":\"1\",\"rate\":\"10000\",\"amount\":\"10000\"}}', '{\"sanitary\":{\"name\":\"Sanitary Fixtures\",\"qty\":\"1\",\"rate\":\"8000\",\"amount\":\"8000\"}}', '{\"transport\":{\"name\":\"Transportation\",\"qty\":\"1\",\"rate\":\"5000\",\"amount\":\"5000\"},\"contingency\":{\"name\":\"Contingency (5%)\",\"qty\":\"1\",\"rate\":\"4600\",\"amount\":\"4600\"}}', '{\"materials\":46000,\"labor\":37000,\"utilities\":8000,\"misc\":9600,\"grand\":100600}', '{\"project_name\":\"Test Construction Project\",\"project_address\":\"123 Test Street, Test City\",\"client_name\":\"Test Homeowner\",\"client_contact\":\"test@example.com\",\"plot_size\":\"2000 sq.ft\",\"built_up_area\":\"1500 sq.ft\",\"floors\":\"2\",\"materials\":{\"cement\":{\"name\":\"Cement (OPC 43 Grade)\",\"qty\":\"50\",\"rate\":\"400\",\"amount\":\"20000\"},\"sand\":{\"name\":\"Sand (River Sand)\",\"qty\":\"5\",\"rate\":\"2000\",\"amount\":\"10000\"},\"bricks\":{\"name\":\"Bricks (Red Clay)\",\"qty\":\"2000\",\"rate\":\"8\",\"amount\":\"16000\"}},\"labor\":{\"masonry\":{\"name\":\"Masonry Work\",\"qty\":\"1\",\"rate\":\"15000\",\"amount\":\"15000\"},\"plumbing\":{\"name\":\"Plumbing Work\",\"qty\":\"1\",\"rate\":\"12000\",\"amount\":\"12000\"},\"electrical\":{\"name\":\"Electrical Work\",\"qty\":\"1\",\"rate\":\"10000\",\"amount\":\"10000\"}},\"utilities\":{\"sanitary\":{\"name\":\"Sanitary Fixtures\",\"qty\":\"1\",\"rate\":\"8000\",\"amount\":\"8000\"}},\"misc\":{\"transport\":{\"name\":\"Transportation\",\"qty\":\"1\",\"rate\":\"5000\",\"amount\":\"5000\"},\"contingency\":{\"name\":\"Contingency (5%)\",\"qty\":\"1\",\"rate\":\"4600\",\"amount\":\"4600\"}},\"totals\":{\"materials\":46000,\"labor\":37000,\"utilities\":8000,\"misc\":9600,\"grand\":100600}}', '', '', 100600.00, 'Test estimate for verification purposes', NULL, 'submitted', '2026-01-11 08:16:49', '2026-01-11 08:16:49');

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
(7, 28, 29, 100.00, 'INR', 'completed', 'order_RYCR5ty0D6TYJs', 'pay_RYCRjYXFJlUXRe', '5bc87cca642a3a65d6d996a4ca52e6f43cb0d215244105ece379df912a7d4b28', '2025-10-26 18:24:58', '2025-10-26 18:26:05'),
(8, 28, 36, 100.00, 'INR', 'completed', 'order_S2VioyxYfbn8eX', 'pay_S2ViyrtlfJEmde', '52b75a522f7b72367d0a3d416e49aeb9a3f63edf1dd8b503a85af1f148070352', '2026-01-11 08:46:59', '2026-01-11 08:47:24'),
(9, 28, 37, 100.00, 'INR', 'pending', 'order_S3oeN9CQSVlSF1', NULL, NULL, '2026-01-14 15:56:58', '2026-01-14 15:56:58'),
(10, 28, 37, 100.00, 'INR', 'completed', 'order_S3oeQ9IrPBhA5u', 'pay_S3oeXaaOUHO0Wx', '016d9e381aef8cc392b417bdbf34128e8fc65ba1a887ac842cde6be62773a256', '2026-01-14 15:57:00', '2026-01-14 15:57:21');

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
  `house_plan_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `acknowledged_at` datetime DEFAULT NULL,
  `due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contractor_layout_sends`
--

INSERT INTO `contractor_layout_sends` (`id`, `contractor_id`, `homeowner_id`, `layout_id`, `design_id`, `house_plan_id`, `message`, `payload`, `created_at`, `acknowledged_at`, `due_date`) VALUES
(3, 37, 28, NULL, NULL, NULL, NULL, '{\"layout_id\":null,\"design_id\":null,\"message\":null,\"forwarded_design\":{\"id\":21,\"title\":\"nbn\",\"description\":\"\",\"files\":[{\"original\":\"4.png\",\"stored\":\"68e51548c6d1c5.76863274_1759843656.png\",\"ext\":\"png\",\"path\":\"/buildhub/backend/uploads/designs/68e51548c6d1c5.76863274_1759843656.png\"}],\"technical_details\":{\"floor_plans\":{\"living_room_dimensions\":\"24 × 18 ft\",\"master_bedroom_dimensions\":\"18 × 14 ft\"},\"structural\":{\"foundation_outline\":\"Isolated footings; basement optional\",\"roof_outline\":\"Flat + partial sloped accents; terrace deck\"},\"construction\":{\"wall_thickness\":\"External 250–300 mm with high insulation; internal 115–150 mm\",\"ceiling_heights\":\"Ground 3.4 m; Upper 3.2 m\",\"building_codes\":\"High energy performance; local villa standards\",\"critical_instructions\":\"Provision for home automation and solar PV\"},\"meta\":{\"building_type\":\"residential\"},\"elevations\":{\"front_elevation\":\"Monolithic volumes; concealed gutters; frameless corners\",\"height_details\":\"Clear height 3.0 m; floor-to-floor 3.2 m\"}},\"created_at\":\"2025-10-07 18:57:36\"},\"layout_image_url\":null}', '2025-10-07 16:03:31', '2025-10-07 21:33:55', '2025-10-16'),
(7, 37, 28, NULL, NULL, NULL, NULL, '{\"layout_id\":null,\"design_id\":null,\"message\":null,\"forwarded_design\":{\"id\":22,\"title\":\"hgvv\",\"description\":\"\",\"files\":[{\"original\":\"2.png\",\"stored\":\"68ef98b6b90964.28887100_1760532662.png\",\"ext\":\"png\",\"path\":\"/buildhub/backend/uploads/designs/68ef98b6b90964.28887100_1760532662.png\"}],\"technical_details\":{\"floor_plans\":{\"living_room_dimensions\":\"20 × 15 ft\",\"master_bedroom_dimensions\":\"16 × 12 ft\",\"layout_description\":\" jhg\",\"kitchen_dimensions\":\"12 × 10 ft\"},\"structural\":{\"load_bearing_walls\":\"Reinforced concrete walls at cores; 200 mm slabs\",\"column_positions\":\"8 m grid; edge columns 300×600 mm\",\"foundation_outline\":\"Isolated footings; M30 concrete\",\"roof_outline\":\"Flat RCC slab with insulation\"},\"construction\":{\"wall_thickness\":\"External 230 mm RCC + insulation + plaster; Internal 115 mm block\",\"ceiling_heights\":\"Living 3.1 m; Bedrooms 3.0 m; Kitchen 2.9 m\",\"building_codes\":\"IBC 2021 / IS 456 as applicable\",\"critical_instructions\":\"Use Fe500 rebars; cover as per exposure class XC2\"},\"meta\":{\"building_type\":\"residential\"},\"elevations\":{\"front_elevation\":\"Monolithic volumes; concealed gutters; frameless corners\",\"height_details\":\"Clear height 3.0 m; floor-to-floor 3.2 m\"}},\"created_at\":\"2025-10-15 18:21:02\"},\"layout_image_url\":null,\"floor_details\":null}', '2025-10-21 10:32:49', NULL, NULL),
(10, 51, 48, 99, NULL, NULL, 'Project for 3BHK modern house in Bangalore. Please provide detailed estimate.', NULL, '2025-12-21 08:25:23', '2025-12-16 09:25:23', NULL),
(11, 52, 49, 100, NULL, NULL, 'Traditional 4BHK house project in Mumbai. Vastu compliant design required.', NULL, '2025-12-21 08:25:23', '2025-12-18 09:25:23', NULL),
(12, 53, 50, 101, NULL, NULL, 'Compact 2BHK house project in Delhi. Space optimization is key.', NULL, '2025-12-21 08:25:23', '2025-12-14 09:25:23', NULL),
(13, 51, 48, 99, NULL, NULL, 'Project for 3BHK modern house in Bangalore. Please provide detailed estimate.', NULL, '2025-12-30 06:04:26', '2025-12-25 07:04:26', NULL),
(14, 52, 49, 100, NULL, NULL, 'Traditional 4BHK house project in Mumbai. Vastu compliant design required.', NULL, '2025-12-30 06:04:26', '2025-12-27 07:04:26', NULL),
(15, 53, 50, 101, NULL, NULL, 'Compact 2BHK house project in Delhi. Space optimization is key.', NULL, '2025-12-30 06:04:26', '2025-12-23 07:04:26', NULL),
(18, 29, 28, NULL, NULL, 11, 'Hi! I\'d like to get a construction estimate for this house plan: \"SHIJIN THOMAS MCA2024-2026 House Plan\". Please review the attached technical details and layout images.', '{\"type\":\"house_plan\",\"house_plan_id\":11,\"plan_name\":\"SHIJIN THOMAS MCA2024-2026 House Plan\",\"plot_dimensions\":\"20\' \\u00d7 20\'\",\"total_area\":0,\"technical_details\":{\"foundation_type\":\"RCC\",\"structure_type\":\"RCC Frame\",\"wall_material\":\"Brick\",\"roofing_type\":\"RCC Slab\",\"flooring_type\":\"Ceramic Tiles\",\"wall_thickness\":\"9\",\"ceiling_height\":\"10\",\"door_height\":\"7\",\"window_height\":\"4\",\"electrical_load\":\"5\",\"water_connection\":\"Municipal\",\"sewage_connection\":\"Municipal\",\"construction_cost\":\"5000000\",\"construction_duration\":\"8-12\",\"unlock_price\":\"8000\",\"special_features\":\"\",\"construction_notes\":\"\",\"compliance_certificates\":\"Building Permit, NOC\",\"exterior_finish\":\"Paint\",\"interior_finish\":\"Paint\",\"kitchen_type\":\"Modular\",\"bathroom_fittings\":\"Standard\",\"earthquake_resistance\":\"Zone III Compliant\",\"fire_safety\":\"Standard\",\"ventilation\":\"Natural + Exhaust Fans\",\"site_area\":\"2500\",\"built_up_area\":\"18\",\"carpet_area\":\"\",\"setback_front\":\"\",\"setback_rear\":\"\",\"setback_left\":\"\",\"setback_right\":\"\",\"beam_size\":\"9x12\",\"column_size\":\"9x12\",\"slab_thickness\":\"5\",\"footing_depth\":\"4 feet\",\"electrical_points\":\"\",\"plumbing_fixtures\":\"\",\"hvac_system\":\"Split AC\",\"solar_provision\":\"No\",\"main_door_material\":\"Teak Wood\",\"window_material\":\"UPVC\",\"staircase_material\":\"RCC with Granite\",\"compound_wall\":\"Yes\",\"building_plan_approval\":\"Required\",\"environmental_clearance\":\"Not Required\",\"fire_noc\":\"Required\",\"layout_image\":{\"file\":null,\"name\":\"SHIJIN_THOMAS_MCA2024_2026_House_Plan_layout (1).png\",\"size\":1220939,\"type\":\"image\\/png\",\"preview\":\"blob:http:\\/\\/localhost:3000\\/0b385194-7d8a-4027-98f4-d22d44cfd6d2\",\"uploaded\":true,\"pending_upload\":false,\"stored\":\"11_layout_image_695d380adfae7.png\",\"upload_time\":\"2026-01-06 17:27:54\"},\"elevation_images\":[],\"section_drawings\":[],\"renders_3d\":[]},\"plan_data\":{\"plan_name\":\"SHIJIN THOMAS MCA2024-2026 House Plan\",\"plot_width\":20,\"plot_height\":20,\"rooms\":[],\"scale_ratio\":1.2,\"total_layout_area\":0,\"total_construction_area\":0,\"floors\":{\"total_floors\":1,\"current_floor\":1,\"floor_names\":{\"1\":\"Ground Floor\"}}},\"architect_info\":{\"name\":\"Shijin Thomas\",\"email\":\"shijinthomas1501@gmail.com\",\"specialization\":\"Residential\"},\"layout_images\":[{\"type\":\"layout_image\",\"filename\":\"11_layout_image_695d380adfae7.png\",\"original_name\":\"SHIJIN_THOMAS_MCA2024_2026_House_Plan_layout (1).png\",\"url\":\"\\/buildhub\\/backend\\/uploads\\/house_plans\\/11_layout_image_695d380adfae7.png\"}],\"notes\":\"Upload Design with Technical Details\",\"message\":\"Hi! I\'d like to get a construction estimate for this house plan: \\\"SHIJIN THOMAS MCA2024-2026 House Plan\\\". Please review the attached technical details and layout images.\",\"sent_at\":\"2026-01-07 16:00:37\",\"homeowner_id\":28}', '2026-01-07 15:00:37', '2026-01-07 20:31:44', '2026-01-08'),
(22, 37, 35, NULL, NULL, NULL, NULL, '{\"layout_id\":null,\"design_id\":null,\"message\":null,\"forwarded_design\":{\"id\":\"hp_82\",\"title\":\"SHIJIN THOMAS House Plan (House Plan)\",\"description\":\"Upload Design with Technical Details\",\"files\":[{\"original\":\"SHIJIN_THOMAS_House_Plan_layout (1).png\",\"stored\":\"82_layout_image_696fb749881ef.png\",\"ext\":\"png\",\"path\":\"/buildhub/backend/uploads/house_plans/82_layout_image_696fb749881ef.png\",\"type\":\"layout_image\"}],\"technical_details\":{\"foundation_type\":\"RCC\",\"foundation_type_custom\":\"\",\"structure_type\":\"RCC Frame\",\"structure_type_custom\":\"\",\"wall_material\":\"Brick\",\"wall_material_custom\":\"\",\"roofing_type\":\"RCC Slab\",\"roofing_type_custom\":\"\",\"flooring_type\":\"Ceramic Tiles\",\"flooring_type_custom\":\"\",\"wall_thickness\":\"9\",\"wall_thickness_custom\":\"\",\"ceiling_height\":\"10\",\"ceiling_height_custom\":\"\",\"door_height\":\"7\",\"door_height_custom\":\"\",\"window_height\":\"4\",\"window_height_custom\":\"\",\"electrical_load\":\"5\",\"electrical_load_custom\":\"\",\"water_connection\":\"Municipal\",\"water_connection_custom\":\"\",\"sewage_connection\":\"Municipal\",\"sewage_connection_custom\":\"\",\"construction_cost\":\"3000000\",\"construction_duration\":\"8-12\",\"construction_duration_custom\":\"\",\"unlock_price\":\"8000\",\"special_features\":\"\",\"construction_notes\":\"\",\"compliance_certificates\":\"Building Permit, NOC\",\"exterior_finish\":\"Paint\",\"exterior_finish_custom\":\"\",\"interior_finish\":\"Paint\",\"interior_finish_custom\":\"\",\"kitchen_type\":\"Modular\",\"kitchen_type_custom\":\"\",\"bathroom_fittings\":\"Standard\",\"bathroom_fittings_custom\":\"\",\"earthquake_resistance\":\"Zone III Compliant\",\"earthquake_resistance_custom\":\"\",\"fire_safety\":\"Standard\",\"fire_safety_custom\":\"\",\"ventilation\":\"Natural + Exhaust Fans\",\"ventilation_custom\":\"\",\"site_area\":\"\",\"site_area_custom\":\"\",\"land_area\":\"\",\"land_area_custom\":\"\",\"built_up_area\":\"\",\"built_up_area_custom\":\"\",\"carpet_area\":\"\",\"carpet_area_custom\":\"\",\"setback_front\":\"\",\"setback_front_custom\":\"\",\"setback_rear\":\"\",\"setback_rear_custom\":\"\",\"setback_left\":\"\",\"setback_left_custom\":\"\",\"setback_right\":\"\",\"setback_right_custom\":\"\",\"beam_size\":\"9x12\",\"beam_size_custom\":\"\",\"column_size\":\"9x12\",\"column_size_custom\":\"\",\"slab_thickness\":\"5\",\"slab_thickness_custom\":\"\",\"footing_depth\":\"4 feet\",\"footing_depth_custom\":\"\",\"electrical_points\":\"\",\"plumbing_fixtures\":\"\",\"hvac_system\":\"Split AC\",\"hvac_system_custom\":\"\",\"solar_provision\":\"No\",\"solar_provision_custom\":\"\",\"main_door_material\":\"Teak Wood\",\"main_door_material_custom\":\"\",\"window_material\":\"UPVC\",\"window_material_custom\":\"\",\"staircase_material\":\"RCC with Granite\",\"staircase_material_custom\":\"\",\"compound_wall\":\"Yes\",\"compound_wall_custom\":\"\",\"building_plan_approval\":\"Required\",\"building_plan_approval_custom\":\"\",\"environmental_clearance\":\"Not Required\",\"environmental_clearance_custom\":\"\",\"fire_noc\":\"Required\",\"fire_noc_custom\":\"\",\"layout_image\":{\"file\":null,\"name\":\"SHIJIN_THOMAS_House_Plan_layout (1).png\",\"size\":1500728,\"type\":\"image/png\",\"preview\":\"blob:http://localhost:3000/f49e9091-9cea-4abb-99bf-4d831aae839e\",\"uploaded\":true,\"pending_upload\":false,\"stored\":\"82_layout_image_696fb749881ef.png\",\"upload_time\":\"2026-01-20 18:11:37\"},\"elevation_images\":[],\"section_drawings\":[],\"renders_3d\":[]},\"created_at\":\"2026-01-20 22:41:37\"},\"layout_image_url\":null,\"floor_details\":null,\"technical_details\":{\"foundation_type\":\"RCC\",\"foundation_type_custom\":\"\",\"structure_type\":\"RCC Frame\",\"structure_type_custom\":\"\",\"wall_material\":\"Brick\",\"wall_material_custom\":\"\",\"roofing_type\":\"RCC Slab\",\"roofing_type_custom\":\"\",\"flooring_type\":\"Ceramic Tiles\",\"flooring_type_custom\":\"\",\"wall_thickness\":\"9\",\"wall_thickness_custom\":\"\",\"ceiling_height\":\"10\",\"ceiling_height_custom\":\"\",\"door_height\":\"7\",\"door_height_custom\":\"\",\"window_height\":\"4\",\"window_height_custom\":\"\",\"electrical_load\":\"5\",\"electrical_load_custom\":\"\",\"water_connection\":\"Municipal\",\"water_connection_custom\":\"\",\"sewage_connection\":\"Municipal\",\"sewage_connection_custom\":\"\",\"construction_cost\":\"3000000\",\"construction_duration\":\"8-12\",\"construction_duration_custom\":\"\",\"unlock_price\":\"8000\",\"special_features\":\"\",\"construction_notes\":\"\",\"compliance_certificates\":\"Building Permit, NOC\",\"exterior_finish\":\"Paint\",\"exterior_finish_custom\":\"\",\"interior_finish\":\"Paint\",\"interior_finish_custom\":\"\",\"kitchen_type\":\"Modular\",\"kitchen_type_custom\":\"\",\"bathroom_fittings\":\"Standard\",\"bathroom_fittings_custom\":\"\",\"earthquake_resistance\":\"Zone III Compliant\",\"earthquake_resistance_custom\":\"\",\"fire_safety\":\"Standard\",\"fire_safety_custom\":\"\",\"ventilation\":\"Natural + Exhaust Fans\",\"ventilation_custom\":\"\",\"site_area\":\"\",\"site_area_custom\":\"\",\"land_area\":\"\",\"land_area_custom\":\"\",\"built_up_area\":\"\",\"built_up_area_custom\":\"\",\"carpet_area\":\"\",\"carpet_area_custom\":\"\",\"setback_front\":\"\",\"setback_front_custom\":\"\",\"setback_rear\":\"\",\"setback_rear_custom\":\"\",\"setback_left\":\"\",\"setback_left_custom\":\"\",\"setback_right\":\"\",\"setback_right_custom\":\"\",\"beam_size\":\"9x12\",\"beam_size_custom\":\"\",\"column_size\":\"9x12\",\"column_size_custom\":\"\",\"slab_thickness\":\"5\",\"slab_thickness_custom\":\"\",\"footing_depth\":\"4 feet\",\"footing_depth_custom\":\"\",\"electrical_points\":\"\",\"plumbing_fixtures\":\"\",\"hvac_system\":\"Split AC\",\"hvac_system_custom\":\"\",\"solar_provision\":\"No\",\"solar_provision_custom\":\"\",\"main_door_material\":\"Teak Wood\",\"main_door_material_custom\":\"\",\"window_material\":\"UPVC\",\"window_material_custom\":\"\",\"staircase_material\":\"RCC with Granite\",\"staircase_material_custom\":\"\",\"compound_wall\":\"Yes\",\"compound_wall_custom\":\"\",\"building_plan_approval\":\"Required\",\"building_plan_approval_custom\":\"\",\"environmental_clearance\":\"Not Required\",\"environmental_clearance_custom\":\"\",\"fire_noc\":\"Required\",\"fire_noc_custom\":\"\",\"layout_image\":{\"file\":null,\"name\":\"SHIJIN_THOMAS_House_Plan_layout (1).png\",\"size\":1500728,\"type\":\"image/png\",\"preview\":\"blob:http://localhost:3000/f49e9091-9cea-4abb-99bf-4d831aae839e\",\"uploaded\":true,\"pending_upload\":false,\"stored\":\"82_layout_image_696fb749881ef.png\",\"upload_time\":\"2026-01-20 18:11:37\"},\"elevation_images\":[],\"section_drawings\":[],\"renders_3d\":[]},\"plot_size\":null,\"building_size\":null}', '2026-01-20 17:26:14', '2026-01-20 22:56:44', '2026-01-21');

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
(23, 2, 29, NULL, NULL, 19000.00, '6 months', NULL, 'deleted', '2025-10-20 08:38:43', '{\"project_name\":\"Commercial Complex\",\"project_address\":\"\",\"plot_size\":\"\",\"built_up_area\":\"\",\"floors\":\"\",\"estimation_date\":\"\",\"client_name\":\"\",\"client_contact\":\"\",\"materials\":{\"cement\":{\"name\":\"OPC 43 grade - 50 bags\",\"qty\":\"50\",\"rate\":\"380\",\"amount\":\"19000\"},\"sand\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"bricks\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"steel\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"aggregate\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"tiles\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"paint\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"doors\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"windows\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"labor\":{\"mason\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plaster\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"painting\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plumbing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"flooring\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"roofing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"utilities\":{\"sanitary\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"kitchen\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical_fixtures\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"water_tank\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"hvac\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"gas_water\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"misc\":{\"transport\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"contingency\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"fees\":{\"name\":\"\",\"amount\":\"\"},\"cleaning\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"safety\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"totals\":{\"materials\":\"19000\",\"labor\":\"\",\"utilities\":\"\",\"misc\":\"\",\"grand\":\"19000\"},\"brands\":\"\"}', 'I am satisfied with this estimate and ready to start the construction project. Please let me know the next steps and when we can begin work.', '2025-10-21 10:42:37'),
(24, 2, 29, NULL, NULL, 75950.00, '6 months', NULL, 'deleted', '2025-10-15 17:23:16', '{\"project_name\":\"Residential Villa\",\"project_address\":\"\",\"plot_size\":\"\",\"built_up_area\":\"\",\"floors\":\"\",\"estimation_date\":\"\",\"client_name\":\"\",\"client_contact\":\"\",\"materials\":{\"cement\":{\"name\":\"OPC 43 grade - 50 bags\",\"qty\":\"50\",\"rate\":\"380\",\"amount\":\"19000\"},\"sand\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"bricks\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"steel\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"aggregate\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"tiles\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"paint\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"doors\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"windows\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"labor\":{\"mason\":{\"name\":\"Masonry - \\u20b9\\/m\\u00b3\",\"qty\":\"5\",\"rate\":\"90\",\"amount\":\"450\"},\"plaster\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"painting\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plumbing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"flooring\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"roofing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"utilities\":{\"sanitary\":{\"name\":\"WC, basin, shower set\",\"qty\":\"6\",\"rate\":\"9000\",\"amount\":\"54000\"},\"kitchen\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical_fixtures\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"water_tank\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"hvac\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"gas_water\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"misc\":{\"transport\":{\"name\":\"Material transport local\",\"qty\":\"50\",\"rate\":\"50\",\"amount\":\"2500\"},\"contingency\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"fees\":{\"name\":\"\",\"amount\":\"\"},\"cleaning\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"safety\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"totals\":{\"materials\":\"19000\",\"labor\":\"450\",\"utilities\":\"54000\",\"misc\":\"2500\",\"grand\":\"75950\"},\"brands\":\"\"}', 'I am satisfied with this estimate and ready to start the construction project. Please let me know the next steps and when we can begin work.', '2025-10-21 12:20:38'),
(25, 6, 29, 'Cement, Steel, Bricks, Tiles, Paint, Electrical fixtures', 'Materials: ₹35L, Labor: ₹20L, Utilities: ₹3L, Misc: ₹2L', 6000000.00, '8-10 months', 'High-quality construction with modern amenities', 'deleted', '2025-10-20 10:00:07', '{\"project_name\":\"Modern Family Home\",\"totals\":{\"materials\":3500000,\"labor\":2000000,\"utilities\":300000,\"misc\":200000,\"grand\":6000000}}', NULL, NULL),
(26, 9, 29, NULL, NULL, 19000.00, '6 months', NULL, 'deleted', '2025-10-26 18:22:46', '{\"project_name\":\"gf\",\"project_address\":\"\",\"plot_size\":\"\",\"built_up_area\":\"\",\"floors\":\"\",\"estimation_date\":\"\",\"client_name\":\"\",\"client_contact\":\"\",\"materials\":{\"cement\":{\"name\":\"OPC 43 grade - 50 bags\",\"qty\":\"50\",\"rate\":\"380\",\"amount\":\"19000\"},\"sand\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"bricks\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"steel\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"aggregate\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"tiles\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"paint\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"doors\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"windows\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"labor\":{\"mason\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plaster\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"painting\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plumbing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"flooring\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"roofing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"utilities\":{\"sanitary\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"kitchen\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical_fixtures\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"water_tank\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"hvac\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"gas_water\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"misc\":{\"transport\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"contingency\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"fees\":{\"name\":\"\",\"amount\":\"\"},\"cleaning\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"safety\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"totals\":{\"materials\":\"19000\",\"labor\":\"\",\"utilities\":\"\",\"misc\":\"\",\"grand\":\"19000\"},\"brands\":\"\"}', NULL, NULL),
(27, 9, 29, NULL, NULL, 19000.00, '6 months', NULL, 'deleted', '2025-10-26 18:22:56', '{\"project_name\":\"gf\",\"project_address\":\"\",\"plot_size\":\"\",\"built_up_area\":\"\",\"floors\":\"\",\"estimation_date\":\"\",\"client_name\":\"\",\"client_contact\":\"\",\"materials\":{\"cement\":{\"name\":\"OPC 43 grade - 50 bags\",\"qty\":\"50\",\"rate\":\"380\",\"amount\":\"19000\"},\"sand\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"bricks\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"steel\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"aggregate\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"tiles\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"paint\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"doors\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"windows\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"labor\":{\"mason\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plaster\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"painting\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plumbing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"flooring\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"roofing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"utilities\":{\"sanitary\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"kitchen\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical_fixtures\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"water_tank\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"hvac\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"gas_water\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"misc\":{\"transport\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"contingency\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"fees\":{\"name\":\"\",\"amount\":\"\"},\"cleaning\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"safety\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"totals\":{\"materials\":\"19000\",\"labor\":\"\",\"utilities\":\"\",\"misc\":\"\",\"grand\":\"19000\"},\"brands\":\"\"}', NULL, NULL),
(28, 9, 29, NULL, NULL, 19000.00, '6 months', NULL, 'deleted', '2025-10-26 18:23:02', '{\"project_name\":\"gf\",\"project_address\":\"\",\"plot_size\":\"\",\"built_up_area\":\"\",\"floors\":\"\",\"estimation_date\":\"\",\"client_name\":\"\",\"client_contact\":\"\",\"materials\":{\"cement\":{\"name\":\"OPC 43 grade - 50 bags\",\"qty\":\"50\",\"rate\":\"380\",\"amount\":\"19000\"},\"sand\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"bricks\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"steel\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"aggregate\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"tiles\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"paint\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"doors\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"windows\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"labor\":{\"mason\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plaster\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"painting\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plumbing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"flooring\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"roofing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"utilities\":{\"sanitary\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"kitchen\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical_fixtures\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"water_tank\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"hvac\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"gas_water\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"misc\":{\"transport\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"contingency\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"fees\":{\"name\":\"\",\"amount\":\"\"},\"cleaning\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"safety\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"totals\":{\"materials\":\"19000\",\"labor\":\"\",\"utilities\":\"\",\"misc\":\"\",\"grand\":\"19000\"},\"brands\":\"\"}', NULL, NULL),
(29, 9, 29, NULL, NULL, 19000.00, '6 months', NULL, 'deleted', '2025-10-26 18:24:17', '{\"project_name\":\"Residential Villa\",\"project_address\":\"\",\"plot_size\":\"2800\",\"built_up_area\":\"\",\"floors\":\"\",\"estimation_date\":\"\",\"client_name\":\"\",\"client_contact\":\"\",\"materials\":{\"cement\":{\"name\":\"OPC 43 grade - 50 bags\",\"qty\":\"50\",\"rate\":\"380\",\"amount\":\"19000\"},\"sand\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"bricks\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"steel\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"aggregate\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"tiles\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"paint\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"doors\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"windows\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"labor\":{\"mason\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plaster\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"painting\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plumbing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"flooring\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"roofing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"utilities\":{\"sanitary\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"kitchen\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical_fixtures\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"water_tank\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"hvac\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"gas_water\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"misc\":{\"transport\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"contingency\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"fees\":{\"name\":\"\",\"amount\":\"\"},\"cleaning\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"safety\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"totals\":{\"materials\":\"19000\",\"labor\":\"\",\"utilities\":\"\",\"misc\":\"\",\"grand\":\"19000\"},\"brands\":\"\"}', 'I am satisfied with this estimate and ready to start the construction project. Please let me know the next steps and when we can begin work.', '2025-10-26 23:56:12'),
(30, 10, 51, 'Cement: 200 bags, Steel: 2 tons, Bricks: 15000 pieces, Sand: 100 cubic feet, Aggregate: 150 cubic feet', 'Foundation: ₹3,00,000, Structure: ₹5,00,000, Brickwork: ₹2,50,000, Roofing: ₹2,00,000, Electrical: ₹1,50,000, Plumbing: ₹1,00,000, Finishing: ₹2,00,000', 1700000.00, '7 months', 'Complete construction with all modern amenities. Includes electrical, plumbing, and basic finishing work.', 'accepted', '2025-12-21 08:25:23', NULL, NULL, NULL),
(31, 11, 52, 'Cement: 300 bags, Steel: 3 tons, Bricks: 25000 pieces, Sand: 150 cubic feet, Aggregate: 200 cubic feet, Marble: 2000 sq ft', 'Foundation: ₹4,50,000, Structure: ₹7,50,000, Brickwork: ₹4,00,000, Roofing: ₹3,00,000, Electrical: ₹2,50,000, Plumbing: ₹2,00,000, Finishing: ₹4,50,000', 2800000.00, '9 months', 'Traditional design with vastu compliance. Premium materials and finishes included.', 'accepted', '2025-12-21 08:25:23', NULL, NULL, NULL),
(32, 12, 53, 'Cement: 120 bags, Steel: 1.5 tons, Bricks: 10000 pieces, Sand: 75 cubic feet, Aggregate: 100 cubic feet', 'Foundation: ₹2,00,000, Structure: ₹3,50,000, Brickwork: ₹1,50,000, Roofing: ₹1,50,000, Electrical: ₹1,00,000, Plumbing: ₹75,000, Finishing: ₹1,75,000', 1200000.00, '5 months', 'Compact and efficient design with space optimization. All basic amenities included.', 'accepted', '2025-12-21 08:25:23', NULL, NULL, NULL),
(33, 10, 51, 'Cement: 200 bags, Steel: 2 tons, Bricks: 15000 pieces, Sand: 100 cubic feet, Aggregate: 150 cubic feet', 'Foundation: ₹3,00,000, Structure: ₹5,00,000, Brickwork: ₹2,50,000, Roofing: ₹2,00,000, Electrical: ₹1,50,000, Plumbing: ₹1,00,000, Finishing: ₹2,00,000', 1700000.00, '7 months', 'Complete construction with all modern amenities. Includes electrical, plumbing, and basic finishing work.', 'accepted', '2025-12-30 06:04:26', NULL, NULL, NULL),
(34, 11, 52, 'Cement: 300 bags, Steel: 3 tons, Bricks: 25000 pieces, Sand: 150 cubic feet, Aggregate: 200 cubic feet, Marble: 2000 sq ft', 'Foundation: ₹4,50,000, Structure: ₹7,50,000, Brickwork: ₹4,00,000, Roofing: ₹3,00,000, Electrical: ₹2,50,000, Plumbing: ₹2,00,000, Finishing: ₹4,50,000', 2800000.00, '9 months', 'Traditional design with vastu compliance. Premium materials and finishes included.', 'accepted', '2025-12-30 06:04:26', NULL, NULL, NULL),
(35, 12, 53, 'Cement: 120 bags, Steel: 1.5 tons, Bricks: 10000 pieces, Sand: 75 cubic feet, Aggregate: 100 cubic feet', 'Foundation: ₹2,00,000, Structure: ₹3,50,000, Brickwork: ₹1,50,000, Roofing: ₹1,50,000, Electrical: ₹1,00,000, Plumbing: ₹75,000, Finishing: ₹1,75,000', 1200000.00, '5 months', 'Compact and efficient design with space optimization. All basic amenities included.', 'accepted', '2025-12-30 06:04:26', NULL, NULL, NULL),
(36, 18, 29, NULL, NULL, NULL, '6 months', NULL, 'deleted', '2026-01-11 08:09:25', '{\"project_name\":\"SHIJIN THOMAS MCA2024-2026 Construction\",\"project_address\":\"\",\"plot_size\":\"\",\"built_up_area\":\"\",\"floors\":\"\",\"estimation_date\":\"\",\"client_name\":\"SHIJIN THOMAS MCA2024-2026\",\"client_contact\":\"shijinthomas2026@mca.ajce.in\",\"materials\":{\"cement\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"sand\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"bricks\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"steel\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"aggregate\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"tiles\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"paint\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"doors\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"windows\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"labor\":{\"mason\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plaster\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"painting\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"plumbing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"flooring\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"roofing\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"utilities\":{\"sanitary\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"kitchen\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"electrical_fixtures\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"water_tank\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"hvac\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"gas_water\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"misc\":{\"transport\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"contingency\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"fees\":{\"name\":\"\",\"amount\":\"\"},\"cleaning\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"safety\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"totals\":{\"materials\":\"\",\"labor\":\"\",\"utilities\":\"\",\"misc\":\"\",\"grand\":\"\"},\"brands\":\"\"}', NULL, '2026-01-11 14:31:07'),
(37, 18, 29, NULL, NULL, 1069745.00, '6 months', NULL, 'project_created', '2026-01-14 15:56:17', '{\"project_name\":\"SHIJIN THOMAS MCA2024-2026 Construction\",\"project_address\":\"jnbn\",\"plot_size\":\"2800\",\"built_up_area\":\"20\",\"floors\":\"2\",\"estimation_date\":\"2026-01-15\",\"client_name\":\"SHIJIN THOMAS MCA2024-2026\",\"client_contact\":\"shijinthomas2026@mca.ajce.in\",\"materials\":{\"cement\":{\"name\":\"OPC 43 grade - 50 bags\",\"qty\":\"50\",\"rate\":\"380\",\"amount\":\"19000\"},\"sand\":{\"name\":\"River sand - 5 m\\u00b3\",\"qty\":\"6\",\"rate\":\"2500\",\"amount\":\"15000\"},\"bricks\":{\"name\":\"Clay bricks - 5000 nos\",\"qty\":\"5000\",\"rate\":\"10\",\"amount\":\"50000\"},\"steel\":{\"name\":\"TMT 8\\/10\\/12mm - 1500 kg\",\"qty\":\"1200\",\"rate\":\"68\",\"amount\":\"81600\"},\"aggregate\":{\"name\":\"20mm aggregate - 8 m\\u00b3\",\"qty\":\"34\",\"rate\":\"1230\",\"amount\":\"41820\"},\"tiles\":{\"name\":\"Vitrified tiles - 120 m\\u00b2\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"paint\":{\"name\":\"Interior emulsion - 80 L\",\"qty\":\"80\",\"rate\":\"250\",\"amount\":\"20000\"},\"doors\":{\"name\":\"Teakwood doors - 10 nos\",\"qty\":\"10\",\"rate\":\"7000\",\"amount\":\"70000\"},\"windows\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"labor\":{\"mason\":{\"name\":\"Masonry - \\u20b9\\/m\\u00b3\",\"qty\":\"5\",\"rate\":\"90\",\"amount\":\"450\"},\"plaster\":{\"name\":\"Internal plaster - \\u20b9\\/m\\u00b2\",\"qty\":\"2\",\"rate\":\"90\",\"amount\":\"180\"},\"painting\":{\"name\":\"2-coat interior - \\u20b9\\/m\\u00b2\",\"qty\":\"3\",\"rate\":\"90\",\"amount\":\"270\"},\"electrical\":{\"name\":\"Per point - \\u20b9\\/pt\",\"qty\":\"5\",\"rate\":\"5\",\"amount\":\"25\"},\"plumbing\":{\"name\":\"Per fitting - \\u20b9\\/fit\",\"qty\":\"5\",\"rate\":\"5\",\"amount\":\"25\"},\"flooring\":{\"name\":\"Flooring install - \\u20b9\\/m\\u00b2\",\"qty\":\"5\",\"rate\":\"5\",\"amount\":\"25\"},\"roofing\":{\"name\":\"Roof sheet install - \\u20b9\\/m\\u00b2\",\"qty\":\"12\",\"rate\":\"500\",\"amount\":\"6000\"},\"others\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"}},\"utilities\":{\"sanitary\":{\"name\":\"WC, basin, shower set\",\"qty\":\"6\",\"rate\":\"9000\",\"amount\":\"54000\"},\"kitchen\":{\"name\":\"Modular kitchen - 12ft\",\"qty\":\"6\",\"rate\":\"100000\",\"amount\":\"600000\"},\"electrical_fixtures\":{\"name\":\"LED panels, fans, switches\",\"qty\":\"10\",\"rate\":\"10\",\"amount\":\"100\"},\"water_tank\":{\"name\":\"Overhead tank 1000L + pump\",\"qty\":\"10\",\"rate\":\"10000\",\"amount\":\"100000\"},\"hvac\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"gas_water\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"misc\":{\"transport\":{\"name\":\"Material transport local\",\"qty\":\"20\",\"rate\":\"50\",\"amount\":\"1000\"},\"contingency\":{\"name\":\"5% buffer\",\"qty\":\"5\",\"rate\":\"50\",\"amount\":\"250\"},\"fees\":{\"name\":\"Permit & registration\",\"amount\":\"10000\"},\"cleaning\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"safety\":{\"name\":\"\",\"qty\":\"\",\"rate\":\"\",\"amount\":\"\"},\"others1\":{\"name\":\"\",\"amount\":\"\"},\"others2\":{\"name\":\"\",\"amount\":\"\"},\"others3\":{\"name\":\"\",\"amount\":\"\"}},\"totals\":{\"materials\":\"297420\",\"labor\":\"6975\",\"utilities\":\"754100\",\"misc\":\"11250\",\"grand\":\"1069745\"},\"brands\":\"\"}', NULL, '2026-01-14 21:27:24');

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
-- Table structure for table `currency_exchange_rates`
--

CREATE TABLE `currency_exchange_rates` (
  `id` int(11) NOT NULL,
  `from_currency` varchar(3) NOT NULL,
  `to_currency` varchar(3) NOT NULL,
  `exchange_rate` decimal(10,6) NOT NULL,
  `rate_date` date NOT NULL,
  `source` varchar(50) DEFAULT 'manual',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `currency_exchange_rates`
--

INSERT INTO `currency_exchange_rates` (`id`, `from_currency`, `to_currency`, `exchange_rate`, `rate_date`, `source`, `created_at`, `updated_at`) VALUES
(1, 'USD', 'INR', 83.500000, '2026-01-11', 'manual', '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(2, 'EUR', 'INR', 91.200000, '2026-01-11', 'manual', '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(3, 'GBP', 'INR', 105.800000, '2026-01-11', 'manual', '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(4, 'AUD', 'INR', 55.400000, '2026-01-11', 'manual', '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(5, 'CAD', 'INR', 61.200000, '2026-01-11', 'manual', '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(6, 'SGD', 'INR', 62.100000, '2026-01-11', 'manual', '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(7, 'AED', 'INR', 22.750000, '2026-01-11', 'manual', '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(8, 'MYR', 'INR', 18.900000, '2026-01-11', 'manual', '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(9, 'INR', 'USD', 0.012000, '2026-01-11', 'manual', '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(10, 'INR', 'EUR', 0.011000, '2026-01-11', 'manual', '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(11, 'INR', 'GBP', 0.009500, '2026-01-11', 'manual', '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(12, 'INR', 'AUD', 0.018000, '2026-01-11', 'manual', '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(13, 'INR', 'CAD', 0.016000, '2026-01-11', 'manual', '2026-01-11 15:27:58', '2026-01-11 15:27:58');

-- --------------------------------------------------------

--
-- Table structure for table `daily_labour_tracking`
--

CREATE TABLE `daily_labour_tracking` (
  `id` int(11) NOT NULL,
  `daily_progress_id` int(11) NOT NULL,
  `worker_type` varchar(100) NOT NULL,
  `worker_count` int(11) NOT NULL DEFAULT 0,
  `hours_worked` decimal(4,2) NOT NULL DEFAULT 8.00,
  `overtime_hours` decimal(4,2) NOT NULL DEFAULT 0.00,
  `absent_count` int(11) NOT NULL DEFAULT 0,
  `hourly_rate` decimal(8,2) DEFAULT NULL,
  `total_wages` decimal(10,2) DEFAULT NULL,
  `productivity_rating` int(11) DEFAULT 5 CHECK (`productivity_rating` >= 1 and `productivity_rating` <= 5),
  `safety_compliance` enum('excellent','good','average','poor','needs_improvement') DEFAULT 'good',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_labour_tracking`
--

INSERT INTO `daily_labour_tracking` (`id`, `daily_progress_id`, `worker_type`, `worker_count`, `hours_worked`, `overtime_hours`, `absent_count`, `hourly_rate`, `total_wages`, `productivity_rating`, `safety_compliance`, `remarks`, `created_at`) VALUES
(5, 9, 'Mason', 1, 8.00, 0.00, 0, 0.00, 0.00, 5, 'good', '', '2026-01-17 07:30:32'),
(6, 9, 'Helper', 7, 8.00, 0.00, 0, 0.00, 0.00, 5, 'good', '', '2026-01-17 07:30:32'),
(7, 10, 'Mason', 1, 8.00, 0.00, 0, 0.00, 0.00, 5, 'good', '', '2026-01-20 08:20:42');

-- --------------------------------------------------------

--
-- Table structure for table `daily_progress_updates`
--

CREATE TABLE `daily_progress_updates` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `update_date` date NOT NULL,
  `construction_stage` varchar(100) NOT NULL,
  `work_done_today` text NOT NULL,
  `incremental_completion_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `cumulative_completion_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `working_hours` decimal(4,2) NOT NULL DEFAULT 8.00,
  `weather_condition` varchar(50) NOT NULL,
  `site_issues` text DEFAULT NULL,
  `progress_photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`progress_photos`)),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_progress_updates`
--

INSERT INTO `daily_progress_updates` (`id`, `project_id`, `contractor_id`, `homeowner_id`, `update_date`, `construction_stage`, `work_done_today`, `incremental_completion_percentage`, `cumulative_completion_percentage`, `working_hours`, `weather_condition`, `site_issues`, `progress_photos`, `latitude`, `longitude`, `location_verified`, `created_at`, `updated_at`) VALUES
(9, 37, 29, 28, '2026-01-17', 'Foundation', 'fsssssssssssssssssssssssssssssssssssssssssssss', 2.00, 2.00, 8.00, 'Sunny', '', '[]', 9.79190000, 76.40000000, 0, '2026-01-17 07:30:32', '2026-01-17 07:30:32'),
(10, 37, 29, 28, '2026-01-20', 'Foundation', 'basdadsgyhhhfvhvhfahdfsssssssssdffff', 5.00, 7.00, 8.00, 'Sunny', '', '[]', 9.52809652, 76.82214180, 0, '2026-01-20 08:20:42', '2026-01-20 08:20:42');

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
-- Table structure for table `enhanced_progress_notifications`
--

CREATE TABLE `enhanced_progress_notifications` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `notification_type` enum('daily_update','weekly_summary','monthly_report','milestone_completed','delay_reported') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `has_photos` tinyint(1) DEFAULT 0,
  `geo_photos_count` int(11) DEFAULT 0,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enhanced_progress_notifications`
--

INSERT INTO `enhanced_progress_notifications` (`id`, `project_id`, `contractor_id`, `homeowner_id`, `notification_type`, `reference_id`, `title`, `message`, `has_photos`, `geo_photos_count`, `status`, `created_at`, `read_at`) VALUES
(3, 37, 29, 28, 'daily_update', 9, 'Daily Progress Update - Foundation', 'Contractor has submitted daily progress update for 2026-01-17. Stage: Foundation, Progress: +2% (Total: 2%). Photos attached: 1 total (Array geo-verified)', 1, 1, 'unread', '2026-01-17 07:30:32', NULL),
(4, 37, 29, 28, 'daily_update', 10, 'Daily Progress Update - Foundation', 'Contractor has submitted daily progress update for 2026-01-20. Stage: Foundation, Progress: +5% (Total: 7%). Photos attached: 1 total (Array geo-verified)', 1, 1, 'unread', '2026-01-20 08:20:42', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `estimate_drafts`
--

CREATE TABLE `estimate_drafts` (
  `id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `send_id` int(11) NOT NULL,
  `draft_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`draft_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `estimate_drafts`
--

INSERT INTO `estimate_drafts` (`id`, `contractor_id`, `send_id`, `draft_data`, `created_at`, `updated_at`) VALUES
(1, 29, 18, '{\"send_id\":\"18\",\"contractor_id\":\"29\",\"structured[project_name]\":\"SHIJIN THOMAS MCA2024-2026 Construction\",\"structured[project_address]\":\"jnbn\",\"structured[plot_size]\":\"2800\",\"structured[built_up_area]\":\"20\",\"structured[floors]\":\"2\",\"structured[estimation_date]\":\"2026-01-15\",\"structured[client_name]\":\"SHIJIN THOMAS MCA2024-2026\",\"structured[client_contact]\":\"shijinthomas2026@mca.ajce.in\",\"structured[materials][cement][name]\":\"OPC 43 grade - 50 bags\",\"structured[materials][cement][qty]\":\"50\",\"structured[materials][cement][rate]\":\"380\",\"structured[materials][cement][amount]\":\"19000\",\"structured[materials][sand][name]\":\"River sand - 5 m\\u00b3\",\"structured[materials][sand][qty]\":\"6\",\"structured[materials][sand][rate]\":\"2500\",\"structured[materials][sand][amount]\":\"15000\",\"structured[materials][bricks][name]\":\"Clay bricks - 5000 nos\",\"structured[materials][bricks][qty]\":\"5000\",\"structured[materials][bricks][rate]\":\"10\",\"structured[materials][bricks][amount]\":\"50000\",\"structured[materials][steel][name]\":\"TMT 8\\/10\\/12mm - 1500 kg\",\"structured[materials][steel][qty]\":\"1200\",\"structured[materials][steel][rate]\":\"68\",\"structured[materials][steel][amount]\":\"81600\",\"structured[materials][aggregate][name]\":\"20mm aggregate - 8 m\\u00b3\",\"structured[materials][aggregate][qty]\":\"34\",\"structured[materials][aggregate][rate]\":\"1230\",\"structured[materials][aggregate][amount]\":\"41820\",\"structured[materials][tiles][name]\":\"Vitrified tiles - 120 m\\u00b2\",\"structured[materials][paint][name]\":\"Interior emulsion - 80 L\",\"structured[materials][paint][qty]\":\"80\",\"structured[materials][paint][rate]\":\"250\",\"structured[materials][paint][amount]\":\"20000\",\"structured[materials][doors][name]\":\"Teakwood doors - 10 nos\",\"structured[materials][doors][qty]\":\"10\",\"structured[materials][doors][rate]\":\"7000\",\"structured[materials][doors][amount]\":\"70000\",\"structured[labor][mason][name]\":\"Masonry - \\u20b9\\/m\\u00b3\",\"structured[labor][mason][qty]\":\"5\",\"structured[labor][mason][rate]\":\"90\",\"structured[labor][mason][amount]\":\"450\",\"structured[labor][plaster][name]\":\"Internal plaster - \\u20b9\\/m\\u00b2\",\"structured[labor][plaster][qty]\":\"2\",\"structured[labor][plaster][rate]\":\"90\",\"structured[labor][plaster][amount]\":\"180\",\"structured[labor][painting][name]\":\"2-coat interior - \\u20b9\\/m\\u00b2\",\"structured[labor][painting][qty]\":\"3\",\"structured[labor][painting][rate]\":\"90\",\"structured[labor][painting][amount]\":\"270\",\"structured[labor][electrical][name]\":\"Per point - \\u20b9\\/pt\",\"structured[labor][electrical][qty]\":\"5\",\"structured[labor][electrical][rate]\":\"5\",\"structured[labor][electrical][amount]\":\"25\",\"structured[labor][plumbing][name]\":\"Per fitting - \\u20b9\\/fit\",\"structured[labor][plumbing][qty]\":\"5\",\"structured[labor][plumbing][rate]\":\"5\",\"structured[labor][plumbing][amount]\":\"25\",\"structured[labor][flooring][name]\":\"Flooring install - \\u20b9\\/m\\u00b2\",\"structured[labor][flooring][qty]\":\"5\",\"structured[labor][flooring][rate]\":\"5\",\"structured[labor][flooring][amount]\":\"25\",\"structured[labor][roofing][name]\":\"Roof sheet install - \\u20b9\\/m\\u00b2\",\"structured[labor][roofing][qty]\":\"12\",\"structured[labor][roofing][rate]\":\"500\",\"structured[labor][roofing][amount]\":\"6000\",\"structured[utilities][sanitary][name]\":\"WC, basin, shower set\",\"structured[utilities][sanitary][qty]\":\"6\",\"structured[utilities][sanitary][rate]\":\"9000\",\"structured[utilities][sanitary][amount]\":\"54000\",\"structured[utilities][kitchen][name]\":\"Modular kitchen - 12ft\",\"structured[utilities][kitchen][qty]\":\"6\",\"structured[utilities][kitchen][rate]\":\"100000\",\"structured[utilities][kitchen][amount]\":\"600000\",\"structured[utilities][electrical_fixtures][name]\":\"LED panels, fans, switches\",\"structured[utilities][electrical_fixtures][qty]\":\"10\",\"structured[utilities][electrical_fixtures][rate]\":\"10\",\"structured[utilities][electrical_fixtures][amount]\":\"100\",\"structured[utilities][water_tank][name]\":\"Overhead tank 1000L + pump\",\"structured[utilities][water_tank][qty]\":\"10\",\"structured[utilities][water_tank][rate]\":\"10000\",\"structured[utilities][water_tank][amount]\":\"100000\",\"structured[misc][transport][name]\":\"Material transport local\",\"structured[misc][transport][qty]\":\"20\",\"structured[misc][transport][rate]\":\"50\",\"structured[misc][transport][amount]\":\"1000\",\"structured[misc][contingency][name]\":\"5% buffer\",\"structured[misc][contingency][qty]\":\"5\",\"structured[misc][contingency][rate]\":\"50\",\"structured[misc][contingency][amount]\":\"250\",\"structured[misc][fees][name]\":\"Permit & registration\",\"structured[misc][fees][amount]\":\"10000\",\"structured[totals][materials]\":\"297420\",\"structured[totals][labor]\":\"6975\",\"structured[totals][utilities]\":\"754100\",\"structured[totals][misc]\":\"1250\",\"structured[totals][grand]\":\"1059745\",\"timeline\":\"6 months\"}', '2026-01-11 08:03:34', '2026-01-14 15:56:03');

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

--
-- Dumping data for table `geo_photos`
--

INSERT INTO `geo_photos` (`id`, `project_id`, `contractor_id`, `homeowner_id`, `filename`, `original_filename`, `file_path`, `file_size`, `mime_type`, `latitude`, `longitude`, `place_name`, `location_accuracy`, `location_data`, `photo_timestamp`, `upload_timestamp`, `is_sent_to_homeowner`, `homeowner_viewed`, `homeowner_viewed_at`, `progress_update_id`, `is_included_in_progress`, `progress_association_date`, `created_at`, `updated_at`) VALUES
(2, 37, 29, 28, 'geo_photo_37_29_1768635032_696b3a985c4af.jpg', 'verified_photo_1768632470997_9.791900_76.400000.jpg', 'C:\\xampp\\htdocs\\buildhub\\backend\\api\\contractor/../../uploads/geo_photos/geo_photo_37_29_1768635032_696b3a985c4af.jpg', 66896, 'image/jpeg', 9.79190000, 76.40000000, 'കൂട്ടുമ്മേൽ,  Vaikom,  കോട്ടയം ജില്ല', 50000.00, '{\"latitude\":9.7919,\"longitude\":76.4,\"accuracy\":50000,\"placeName\":\"കൂട്ടുമ്മേൽ,  Vaikom,  കോട്ടയം ജില്ല\",\"timestamp\":\"2026-01-17T06:39:42.775Z\",\"capturedAt\":\"2026-01-17T06:47:50.997Z\"}', '2026-01-17 01:09:42', '2026-01-17 07:30:32', 1, 1, '2026-01-17 14:44:07', NULL, 0, NULL, '2026-01-17 07:30:32', '2026-01-17 14:44:07'),
(3, 37, 29, 28, 'geo_photo_37_29_1768897242_696f3ada0aca9.jpg', 'verified_photo_1768897235020_9.528097_76.822142.jpg', 'C:\\xampp\\htdocs\\buildhub\\backend\\api\\contractor/../../uploads/geo_photos/geo_photo_37_29_1768897242_696f3ada0aca9.jpg', 25388, 'image/jpeg', 9.52809652, 76.82214180, 'പട്ടിമറ്റം,  Kanjirappally,  കോട്ടയം ജില്ല', 71.00, '{\"latitude\":9.528096516501448,\"longitude\":76.82214180333295,\"accuracy\":71,\"placeName\":\"പട്ടിമറ്റം,  Kanjirappally,  കോട്ടയം ജില്ല\",\"timestamp\":\"2026-01-20T08:20:26.728Z\",\"capturedAt\":\"2026-01-20T08:20:35.020Z\"}', '2026-01-20 02:50:26', '2026-01-20 08:20:42', 1, 1, '2026-01-20 08:53:30', NULL, 0, NULL, '2026-01-20 08:20:42', '2026-01-20 08:53:30');

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
(1, 28, 29, 'acknowledgment', 'Contractor Acknowledged Your Layout', 'Shijin Thomas acknowledged your layout at 2025-10-26 19:19:55.\nDue date: December 25, 2025', 'unread', '2025-10-26 18:19:55'),
(2, 28, 29, 'acknowledgment', 'Contractor Acknowledged Your Layout', 'Shijin Thomas acknowledged your layout at 2026-01-06 18:10:49.\nDue date: not specified', 'unread', '2026-01-06 17:10:49'),
(3, 28, 29, 'acknowledgment', 'Contractor Acknowledged Your Layout', 'Shijin Thomas acknowledged your layout at 2026-01-06 18:10:53.\nDue date: January 8, 2026', 'unread', '2026-01-06 17:10:53'),
(4, 28, 29, 'acknowledgment', 'Contractor Acknowledged Your Layout', 'Shijin Thomas acknowledged your layout at 2026-01-07 16:01:38.\nDue date: January 8, 2026', 'unread', '2026-01-07 15:01:38'),
(5, 28, 29, 'acknowledgment', 'Contractor Acknowledged Your Layout', 'Shijin Thomas acknowledged your layout at 2026-01-07 16:01:41.\nDue date: January 8, 2026', 'unread', '2026-01-07 15:01:41'),
(6, 28, 29, 'acknowledgment', 'Contractor Acknowledged Your Layout', 'Shijin Thomas acknowledged your layout at 2026-01-07 16:01:44.\nDue date: January 8, 2026', 'unread', '2026-01-07 15:01:44'),
(7, 28, 37, 'acknowledgment', 'Contractor Acknowledged Your Layout', 'Shijin Thomas acknowledged your layout at 2026-01-07 16:40:39.\nDue date: January 15, 2026', 'unread', '2026-01-07 15:40:39'),
(8, 35, 37, 'acknowledgment', 'Contractor Acknowledged Your Layout', 'Shijin Thomas acknowledged your layout at 2026-01-20 18:26:44.\nDue date: January 21, 2026', 'unread', '2026-01-20 17:26:44');

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
  `technical_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Stores comprehensive technical specifications including construction details, materials, MEP systems, etc.' CHECK (json_valid(`technical_details`)),
  `layout_image` text DEFAULT NULL,
  `total_area` decimal(10,2) NOT NULL,
  `status` enum('draft','submitted','approved','rejected') DEFAULT 'draft',
  `version` int(11) DEFAULT 1,
  `parent_plan_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `unlock_price` decimal(10,2) DEFAULT 8000.00,
  `concept_preview_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `house_plans`
--

INSERT INTO `house_plans` (`id`, `architect_id`, `layout_request_id`, `plan_name`, `plot_width`, `plot_height`, `plan_data`, `technical_details`, `layout_image`, `total_area`, `status`, `version`, `parent_plan_id`, `notes`, `created_at`, `updated_at`, `unlock_price`, `concept_preview_id`) VALUES
(10, 27, 109, 'Test Upload Plan', 30.00, 40.00, '{\"plan_name\":\"Test Upload Plan\",\"plot_width\":30,\"plot_height\":40,\"rooms\":[],\"scale_ratio\":1.2,\"total_layout_area\":0,\"total_construction_area\":0,\"floors\":{\"total_floors\":1,\"current_floor\":1,\"floor_names\":{\"1\":\"Ground Floor\"}}}', '{\"foundation_type\":\"RCC\",\"structure_type\":\"RCC Frame\",\"construction_cost\":\"25,00,000\",\"unlock_price\":\"8000\",\"layout_image\":{\"name\":\"test_layout.png\",\"stored\":\"10_layout_image_test.png\",\"size\":70,\"type\":\"image\\/png\",\"uploaded\":true,\"pending_upload\":false,\"upload_time\":\"2026-01-06 17:23:50\"}}', NULL, 0.00, 'submitted', 1, NULL, 'Test plan for upload flow', '2026-01-06 16:23:50', '2026-01-06 16:23:50', 8000.00, NULL),
(12, 27, 105, 'SHIJIN THOMAS MCA2024-2026 House Plan', 51.00, 54.00, '{\"rooms\":[{\"id\":1,\"name\":\"master bedroom\",\"type\":\"master_bedroom\",\"x\":50,\"y\":50,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#c8e6c9\",\"floor\":1},{\"id\":2,\"name\":\"bedrooms\",\"type\":\"bedrooms\",\"x\":190,\"y\":50,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#dcedc8\",\"floor\":1},{\"id\":3,\"name\":\"bathrooms\",\"type\":\"bathrooms\",\"x\":330,\"y\":50,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e1f5fe\",\"floor\":1},{\"id\":4,\"name\":\"attached bathroom\",\"type\":\"attached_bathroom\",\"x\":50,\"y\":170,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e3f2fd\",\"floor\":1},{\"id\":5,\"name\":\"kitchen\",\"type\":\"kitchen\",\"x\":190,\"y\":170,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#ffcdd2\",\"floor\":1},{\"id\":6,\"name\":\"living room\",\"type\":\"living_room\",\"x\":330,\"y\":170,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#ffe0b2\",\"floor\":1},{\"id\":7,\"name\":\"dining room\",\"type\":\"dining_room\",\"x\":50,\"y\":290,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e1bee7\",\"floor\":1},{\"id\":8,\"name\":\"store room\",\"type\":\"store_room\",\"x\":190,\"y\":290,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#f5f5f5\",\"floor\":1},{\"id\":9,\"name\":\"garage\",\"type\":\"garage\",\"x\":330,\"y\":290,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e3f2fd\",\"floor\":1},{\"id\":10,\"name\":\"study room\",\"type\":\"study_room\",\"x\":50,\"y\":610,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e8eaf6\",\"floor\":2},{\"id\":11,\"name\":\"prayer room\",\"type\":\"prayer_room\",\"x\":190,\"y\":610,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#b39ddb\",\"floor\":2},{\"id\":12,\"name\":\"guest room\",\"type\":\"guest_room\",\"x\":330,\"y\":610,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e3f2fd\",\"floor\":2},{\"id\":13,\"name\":\"balcony\",\"type\":\"balcony\",\"x\":50,\"y\":730,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#c8e6c9\",\"floor\":2},{\"id\":14,\"name\":\"terrace\",\"type\":\"terrace\",\"x\":190,\"y\":730,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#dcedc8\",\"floor\":2},{\"id\":15,\"name\":\"bedrooms\",\"type\":\"bedrooms\",\"x\":330,\"y\":730,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#dcedc8\",\"floor\":2},{\"id\":16,\"name\":\"bathrooms\",\"type\":\"bathrooms\",\"x\":50,\"y\":850,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e1f5fe\",\"floor\":2},{\"id\":17,\"name\":\"attached bathroom\",\"type\":\"attached_bathroom\",\"x\":190,\"y\":850,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e3f2fd\",\"floor\":2}],\"scale_ratio\":1.2,\"total_layout_area\":1700,\"total_construction_area\":2448,\"floors\":{\"total_floors\":1,\"current_floor\":1,\"floor_names\":{\"1\":\"Ground Floor\"},\"floor_offsets\":{\"1\":{\"x\":0,\"y\":0}}}}', NULL, NULL, 2448.00, 'draft', 1, NULL, '', '2026-01-07 06:08:46', '2026-01-18 06:15:39', 8000.00, NULL),
(16, 27, NULL, 'Draft Plan 1/18/2026, 11:46:14 AM', 100.00, 100.00, '{\"rooms\":[{\"id\":1768716968557,\"name\":\"Bedroom\",\"category\":\"bedroom\",\"x\":50,\"y\":50,\"layout_width\":12,\"layout_height\":10,\"actual_width\":14.399999999999999,\"actual_height\":12,\"rotation\":0,\"color\":\"#e8f5e8\",\"icon\":\"\\ud83d\\udecf\\ufe0f\",\"floor\":1,\"wall_thickness\":0.5,\"ceiling_height\":9,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768716968983,\"name\":\"Bedroom\",\"category\":\"bedroom\",\"x\":50,\"y\":50,\"layout_width\":12,\"layout_height\":10,\"actual_width\":14.399999999999999,\"actual_height\":12,\"rotation\":0,\"color\":\"#e8f5e8\",\"icon\":\"bed\",\"floor\":1,\"wall_thickness\":0.5,\"ceiling_height\":9,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"}],\"scale_ratio\":1.2,\"total_layout_area\":240,\"total_construction_area\":345.59999999999997,\"floors\":{\"total_floors\":1,\"current_floor\":1,\"floor_names\":{\"1\":\"Ground Floor\"},\"floor_offsets\":{\"1\":{\"x\":0,\"y\":0}}}}', NULL, NULL, 345.60, 'draft', 1, NULL, '', '2026-01-18 06:16:14', '2026-01-18 06:16:14', 8000.00, NULL),
(17, 27, NULL, 'Draft Plan 1/18/2026, 11:46:20 AM', 100.00, 100.00, '{\"rooms\":[{\"id\":1768716968557,\"name\":\"Bedroom\",\"category\":\"bedroom\",\"x\":50,\"y\":50,\"layout_width\":12,\"layout_height\":10,\"actual_width\":14.399999999999999,\"actual_height\":12,\"rotation\":0,\"color\":\"#e8f5e8\",\"icon\":\"\\ud83d\\udecf\\ufe0f\",\"floor\":1,\"wall_thickness\":0.5,\"ceiling_height\":9,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768716968983,\"name\":\"Bedroom\",\"category\":\"bedroom\",\"x\":60,\"y\":240,\"layout_width\":12,\"layout_height\":10,\"actual_width\":14.399999999999999,\"actual_height\":12,\"rotation\":0,\"color\":\"#e8f5e8\",\"icon\":\"bed\",\"floor\":1,\"wall_thickness\":0.5,\"ceiling_height\":9,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768716969609,\"name\":\"Master Bedroom\",\"category\":\"bedroom\",\"x\":440,\"y\":100,\"layout_width\":14,\"layout_height\":12,\"actual_width\":16.8,\"actual_height\":14.399999999999999,\"rotation\":0,\"color\":\"#e8f5e8\",\"icon\":\"bed\",\"floor\":1,\"wall_thickness\":0.5,\"ceiling_height\":9,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768727748902,\"name\":\"Corridor\",\"category\":\"circulation\",\"type\":\"corridor\",\"x\":50,\"y\":50,\"layout_width\":20,\"layout_height\":4,\"actual_width\":24,\"actual_height\":4.8,\"rotation\":0,\"color\":\"#fff9c4\",\"icon\":\"\\ud83d\\udeb6\",\"floor\":1,\"wall_thickness\":0.5,\"ceiling_height\":9,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768727751636,\"name\":\"Main Door\",\"category\":\"doors\",\"type\":\"main_door\",\"x\":50,\"y\":50,\"layout_width\":4,\"layout_height\":1,\"actual_width\":4.8,\"actual_height\":1.2,\"rotation\":0,\"color\":\"#b3d9ff\",\"icon\":\"\\ud83d\\udeaa\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"}],\"scale_ratio\":1.2,\"total_layout_area\":492,\"total_construction_area\":708.48,\"floors\":{\"total_floors\":1,\"current_floor\":1,\"floor_names\":{\"1\":\"Ground Floor\"}}}', NULL, NULL, 708.48, 'draft', 1, NULL, '', '2026-01-18 06:16:20', '2026-01-18 09:16:14', 8000.00, NULL),
(19, 27, 105, 'SHIJIN THOMAS MCA2024-2026 House Plan', 20.00, 20.00, '{\"rooms\":[{\"id\":1,\"name\":\"master bedroom\",\"type\":\"master_bedroom\",\"x\":50,\"y\":50,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#c8e6c9\",\"floor\":1},{\"id\":2,\"name\":\"bedrooms\",\"type\":\"bedrooms\",\"x\":190,\"y\":50,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#dcedc8\",\"floor\":1},{\"id\":3,\"name\":\"bathrooms\",\"type\":\"bathrooms\",\"x\":330,\"y\":50,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e1f5fe\",\"floor\":1},{\"id\":4,\"name\":\"attached bathroom\",\"type\":\"attached_bathroom\",\"x\":50,\"y\":170,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e3f2fd\",\"floor\":1},{\"id\":5,\"name\":\"kitchen\",\"type\":\"kitchen\",\"x\":190,\"y\":170,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#ffcdd2\",\"floor\":1},{\"id\":6,\"name\":\"living room\",\"type\":\"living_room\",\"x\":330,\"y\":170,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#ffe0b2\",\"floor\":1},{\"id\":7,\"name\":\"dining room\",\"type\":\"dining_room\",\"x\":50,\"y\":290,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e1bee7\",\"floor\":1},{\"id\":8,\"name\":\"store room\",\"type\":\"store_room\",\"x\":190,\"y\":290,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#f5f5f5\",\"floor\":1},{\"id\":9,\"name\":\"garage\",\"type\":\"garage\",\"x\":330,\"y\":290,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e3f2fd\",\"floor\":1},{\"id\":10,\"name\":\"study room\",\"type\":\"study_room\",\"x\":50,\"y\":710,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e8eaf6\",\"floor\":2},{\"id\":11,\"name\":\"prayer room\",\"type\":\"prayer_room\",\"x\":190,\"y\":710,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#b39ddb\",\"floor\":2},{\"id\":12,\"name\":\"guest room\",\"type\":\"guest_room\",\"x\":330,\"y\":710,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e3f2fd\",\"floor\":2},{\"id\":13,\"name\":\"balcony\",\"type\":\"balcony\",\"x\":50,\"y\":830,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#c8e6c9\",\"floor\":2},{\"id\":14,\"name\":\"terrace\",\"type\":\"terrace\",\"x\":190,\"y\":830,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#dcedc8\",\"floor\":2},{\"id\":15,\"name\":\"bedrooms\",\"type\":\"bedrooms\",\"x\":330,\"y\":830,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#dcedc8\",\"floor\":2},{\"id\":16,\"name\":\"bathrooms\",\"type\":\"bathrooms\",\"x\":50,\"y\":950,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e1f5fe\",\"floor\":2},{\"id\":17,\"name\":\"attached bathroom\",\"type\":\"attached_bathroom\",\"x\":190,\"y\":950,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e3f2fd\",\"floor\":2}],\"scale_ratio\":1.2,\"total_layout_area\":1700,\"total_construction_area\":2448,\"floors\":{\"total_floors\":1,\"current_floor\":1,\"floor_names\":{\"1\":\"Ground Floor\"},\"floor_offsets\":{\"1\":{\"x\":0,\"y\":0}}}}', NULL, NULL, 2448.00, 'draft', 1, NULL, '', '2026-01-18 09:36:24', '2026-01-18 09:36:24', 8000.00, NULL),
(20, 27, 105, 'SHIJIN THOMAS MCA2024-2026 House Plan', 49.00, 63.00, '{\"rooms\":[{\"id\":1,\"name\":\"master bedroom\",\"type\":\"master_bedroom\",\"x\":50,\"y\":50,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#c8e6c9\",\"floor\":1},{\"id\":2,\"name\":\"bedrooms\",\"type\":\"bedrooms\",\"x\":190,\"y\":50,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#dcedc8\",\"floor\":1},{\"id\":3,\"name\":\"bathrooms\",\"type\":\"bathrooms\",\"x\":330,\"y\":50,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e1f5fe\",\"floor\":1},{\"id\":4,\"name\":\"attached bathroom\",\"type\":\"attached_bathroom\",\"x\":50,\"y\":170,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e3f2fd\",\"floor\":1},{\"id\":5,\"name\":\"kitchen\",\"type\":\"kitchen\",\"x\":190,\"y\":170,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#ffcdd2\",\"floor\":1},{\"id\":6,\"name\":\"living room\",\"type\":\"living_room\",\"x\":330,\"y\":170,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#ffe0b2\",\"floor\":1},{\"id\":7,\"name\":\"dining room\",\"type\":\"dining_room\",\"x\":50,\"y\":290,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e1bee7\",\"floor\":1},{\"id\":8,\"name\":\"store room\",\"type\":\"store_room\",\"x\":190,\"y\":290,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#f5f5f5\",\"floor\":1},{\"id\":9,\"name\":\"garage\",\"type\":\"garage\",\"x\":330,\"y\":290,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e3f2fd\",\"floor\":1},{\"id\":10,\"name\":\"study room\",\"type\":\"study_room\",\"x\":50,\"y\":710,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e8eaf6\",\"floor\":2},{\"id\":11,\"name\":\"prayer room\",\"type\":\"prayer_room\",\"x\":190,\"y\":710,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#b39ddb\",\"floor\":2},{\"id\":12,\"name\":\"guest room\",\"type\":\"guest_room\",\"x\":330,\"y\":710,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e3f2fd\",\"floor\":2},{\"id\":13,\"name\":\"balcony\",\"type\":\"balcony\",\"x\":50,\"y\":830,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#c8e6c9\",\"floor\":2},{\"id\":14,\"name\":\"terrace\",\"type\":\"terrace\",\"x\":190,\"y\":830,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#dcedc8\",\"floor\":2},{\"id\":15,\"name\":\"bedrooms\",\"type\":\"bedrooms\",\"x\":330,\"y\":830,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#dcedc8\",\"floor\":2},{\"id\":16,\"name\":\"bathrooms\",\"type\":\"bathrooms\",\"x\":50,\"y\":950,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e1f5fe\",\"floor\":2},{\"id\":17,\"name\":\"attached bathroom\",\"type\":\"attached_bathroom\",\"x\":190,\"y\":950,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e3f2fd\",\"floor\":2}],\"scale_ratio\":1.2,\"total_layout_area\":1700,\"total_construction_area\":2448,\"floors\":{\"total_floors\":1,\"current_floor\":1,\"floor_names\":{\"1\":\"Ground Floor\"},\"floor_offsets\":{\"1\":{\"x\":0,\"y\":0}}}}', NULL, NULL, 2448.00, 'draft', 1, NULL, '', '2026-01-18 09:36:46', '2026-01-18 09:36:46', 8000.00, NULL),
(80, 31, 112, 'SHIJIN THOMAS House Plan', 55.00, 62.00, '{\"rooms\":[{\"id\":1,\"name\":\"master bedroom\",\"type\":\"master_bedroom\",\"x\":220,\"y\":600,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#c8e6c9\",\"floor\":1},{\"id\":2,\"name\":\"bedrooms 1\",\"type\":\"bedrooms\",\"x\":620,\"y\":380,\"layout_width\":10,\"layout_height\":7.5,\"actual_width\":12,\"actual_height\":9,\"rotation\":0,\"color\":\"#dcedc8\",\"floor\":1},{\"id\":3,\"name\":\"bedrooms 2\",\"type\":\"bedrooms\",\"x\":620,\"y\":600,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#dcedc8\",\"floor\":1},{\"id\":4,\"name\":\"bedrooms 3\",\"type\":\"bedrooms\",\"x\":220,\"y\":300,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#dcedc8\",\"floor\":1},{\"id\":5,\"name\":\"attached bathroom 1\",\"type\":\"attached_bathroom\",\"x\":220,\"y\":500,\"layout_width\":10,\"layout_height\":5,\"actual_width\":12,\"actual_height\":6,\"rotation\":0,\"color\":\"#e3f2fd\",\"floor\":1},{\"id\":6,\"name\":\"attached bathroom 2\",\"type\":\"attached_bathroom\",\"x\":620,\"y\":520,\"layout_width\":10,\"layout_height\":4,\"actual_width\":12,\"actual_height\":4.8,\"rotation\":0,\"color\":\"#e3f2fd\",\"floor\":1},{\"id\":7,\"name\":\"bathrooms\",\"type\":\"bathrooms\",\"x\":620,\"y\":300,\"layout_width\":10,\"layout_height\":4,\"actual_width\":12,\"actual_height\":4.8,\"rotation\":0,\"color\":\"#e1f5fe\",\"floor\":1},{\"id\":8,\"name\":\"living room\",\"type\":\"living_room\",\"x\":420,\"y\":600,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#ffe0b2\",\"floor\":1},{\"id\":9,\"name\":\"dining room\",\"type\":\"dining_room\",\"x\":420,\"y\":400,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#e1bee7\",\"floor\":1},{\"id\":10,\"name\":\"store room\",\"type\":\"store_room\",\"x\":620,\"y\":180,\"layout_width\":10,\"layout_height\":6,\"actual_width\":12,\"actual_height\":7.199999999999999,\"rotation\":0,\"color\":\"#f5f5f5\",\"floor\":1},{\"id\":11,\"name\":\"garage\",\"type\":\"garage\",\"x\":220,\"y\":800,\"layout_width\":10,\"layout_height\":8.5,\"actual_width\":12,\"actual_height\":10.2,\"rotation\":0,\"color\":\"#e3f2fd\",\"floor\":1},{\"id\":12,\"name\":\"kitchen\",\"type\":\"kitchen\",\"x\":420,\"y\":200,\"layout_width\":10,\"layout_height\":10,\"actual_width\":12,\"actual_height\":12,\"rotation\":0,\"color\":\"#ffcdd2\",\"floor\":1},{\"id\":1768920355554,\"name\":\"Entrance Hall\",\"category\":\"circulation\",\"type\":\"entrance_hall\",\"x\":420,\"y\":800,\"layout_width\":10,\"layout_height\":4,\"actual_width\":12,\"actual_height\":4.8,\"rotation\":0,\"color\":\"#ffe082\",\"icon\":\"\\ud83d\\udeaa\",\"floor\":1,\"wall_thickness\":0.5,\"ceiling_height\":9,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768920653311,\"name\":\"Main Door\",\"category\":\"doors\",\"type\":\"main_door\",\"x\":480,\"y\":800,\"layout_width\":4,\"layout_height\":1,\"actual_width\":4.8,\"actual_height\":1.2,\"rotation\":0,\"color\":\"#b3d9ff\",\"icon\":\"\\ud83d\\udeaa\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768920695989,\"name\":\"Main Door\",\"category\":\"doors\",\"type\":\"main_door\",\"x\":380,\"y\":700,\"layout_width\":4,\"layout_height\":1,\"actual_width\":4.8,\"actual_height\":1.2,\"rotation\":90,\"color\":\"#b3d9ff\",\"icon\":\"\\ud83d\\udeaa\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768920751511,\"name\":\"Interior Door\",\"category\":\"doors\",\"type\":\"interior_door\",\"x\":480,\"y\":580,\"layout_width\":3,\"layout_height\":1,\"actual_width\":3.5999999999999996,\"actual_height\":1.2,\"rotation\":0,\"color\":\"#cce7ff\",\"icon\":\"\\ud83d\\udeaa\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768920817264,\"name\":\"Interior Door\",\"category\":\"doors\",\"type\":\"interior_door\",\"x\":580,\"y\":700,\"layout_width\":3,\"layout_height\":1,\"actual_width\":3.5999999999999996,\"actual_height\":1.2,\"rotation\":90,\"color\":\"#cce7ff\",\"icon\":\"\\ud83d\\udeaa\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768921102482,\"name\":\"Interior Door\",\"category\":\"doors\",\"type\":\"interior_door\",\"x\":480,\"y\":380,\"layout_width\":3,\"layout_height\":1,\"actual_width\":3.5999999999999996,\"actual_height\":1.2,\"rotation\":0,\"color\":\"#cce7ff\",\"icon\":\"\\ud83d\\udeaa\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768921114395,\"name\":\"Window\",\"category\":\"doors\",\"type\":\"window\",\"x\":180,\"y\":400,\"layout_width\":4,\"layout_height\":1,\"actual_width\":4.8,\"actual_height\":1.2,\"rotation\":90,\"color\":\"#d9ecff\",\"icon\":\"\\ud83e\\ude9f\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768921169421,\"name\":\"Window\",\"category\":\"doors\",\"type\":\"window\",\"x\":180,\"y\":680,\"layout_width\":4,\"layout_height\":1,\"actual_width\":4.8,\"actual_height\":1.2,\"rotation\":90,\"color\":\"#d9ecff\",\"icon\":\"\\ud83e\\ude9f\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768921218852,\"name\":\"Interior Door\",\"category\":\"doors\",\"type\":\"interior_door\",\"x\":380,\"y\":440,\"layout_width\":3,\"layout_height\":1,\"actual_width\":3.5999999999999996,\"actual_height\":1.2,\"rotation\":90,\"color\":\"#cce7ff\",\"icon\":\"\\ud83d\\udeaa\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768921236099,\"name\":\"Window\",\"category\":\"doors\",\"type\":\"window\",\"x\":780,\"y\":700,\"layout_width\":4,\"layout_height\":1,\"actual_width\":4.8,\"actual_height\":1.2,\"rotation\":270,\"color\":\"#d9ecff\",\"icon\":\"\\ud83e\\ude9f\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768921247184,\"name\":\"Window\",\"category\":\"doors\",\"type\":\"window\",\"x\":780,\"y\":440,\"layout_width\":4,\"layout_height\":1,\"actual_width\":4.8,\"actual_height\":1.2,\"rotation\":270,\"color\":\"#d9ecff\",\"icon\":\"\\ud83e\\ude9f\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768921258489,\"name\":\"Interior Door\",\"category\":\"doors\",\"type\":\"interior_door\",\"x\":580,\"y\":460,\"layout_width\":3,\"layout_height\":1,\"actual_width\":3.5999999999999996,\"actual_height\":1.2,\"rotation\":270,\"color\":\"#cce7ff\",\"icon\":\"\\ud83d\\udeaa\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768921336037,\"name\":\"Window\",\"category\":\"doors\",\"type\":\"window\",\"x\":280,\"y\":800,\"layout_width\":4,\"layout_height\":1,\"actual_width\":4.8,\"actual_height\":1.2,\"rotation\":0,\"color\":\"#d9ecff\",\"icon\":\"\\ud83e\\ude9f\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768921355744,\"name\":\"Interior Door\",\"category\":\"doors\",\"type\":\"interior_door\",\"x\":580,\"y\":240,\"layout_width\":3,\"layout_height\":1,\"actual_width\":3.5999999999999996,\"actual_height\":1.2,\"rotation\":90,\"color\":\"#cce7ff\",\"icon\":\"\\ud83d\\udeaa\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768921382093,\"name\":\"Interior Door\",\"category\":\"doors\",\"type\":\"interior_door\",\"x\":280,\"y\":600,\"layout_width\":3,\"layout_height\":1,\"actual_width\":3.5999999999999996,\"actual_height\":1.2,\"rotation\":0,\"color\":\"#cce7ff\",\"icon\":\"\\ud83d\\udeaa\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768921393368,\"name\":\"Interior Door\",\"category\":\"doors\",\"type\":\"interior_door\",\"x\":680,\"y\":580,\"layout_width\":3,\"layout_height\":1,\"actual_width\":3.5999999999999996,\"actual_height\":1.2,\"rotation\":0,\"color\":\"#cce7ff\",\"icon\":\"\\ud83d\\udeaa\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768921407000,\"name\":\"Interior Door\",\"category\":\"doors\",\"type\":\"interior_door\",\"x\":680,\"y\":380,\"layout_width\":3,\"layout_height\":1,\"actual_width\":3.5999999999999996,\"actual_height\":1.2,\"rotation\":0,\"color\":\"#cce7ff\",\"icon\":\"\\ud83d\\udeaa\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"},{\"id\":1768921419538,\"name\":\"Sliding Door\",\"category\":\"doors\",\"type\":\"sliding_door\",\"x\":480,\"y\":180,\"layout_width\":4,\"layout_height\":1,\"actual_width\":4.8,\"actual_height\":1.2,\"rotation\":0,\"color\":\"#e0f2ff\",\"icon\":\"\\ud83d\\udeaa\",\"floor\":1,\"wall_thickness\":0.25,\"ceiling_height\":8,\"floor_type\":\"ceramic\",\"wall_material\":\"brick\",\"notes\":\"\"}],\"scale_ratio\":1.2,\"total_layout_area\":1049,\"total_construction_area\":1510.5599999999995,\"floors\":{\"total_floors\":1,\"current_floor\":1,\"floor_names\":{\"1\":\"Ground Floor\"},\"floor_offsets\":{\"1\":{\"x\":0,\"y\":0}}}}', NULL, NULL, 1510.56, 'draft', 1, NULL, '', '2026-01-20 15:04:13', '2026-01-20 15:05:08', 8000.00, NULL),
(81, 27, 105, 'SHIJIN THOMAS MCA2024-2026 House Plan', 20.00, 20.00, '{\"plan_name\":\"SHIJIN THOMAS MCA2024-2026 House Plan\",\"plot_width\":20,\"plot_height\":20,\"rooms\":[],\"scale_ratio\":1.2,\"total_layout_area\":0,\"total_construction_area\":0,\"floors\":{\"total_floors\":1,\"current_floor\":1,\"floor_names\":{\"1\":\"Ground Floor\"}}}', '{\"foundation_type\":\"RCC\",\"foundation_type_custom\":\"\",\"structure_type\":\"RCC Frame\",\"structure_type_custom\":\"\",\"wall_material\":\"Brick\",\"wall_material_custom\":\"\",\"roofing_type\":\"RCC Slab\",\"roofing_type_custom\":\"\",\"flooring_type\":\"Ceramic Tiles\",\"flooring_type_custom\":\"\",\"wall_thickness\":\"9\",\"wall_thickness_custom\":\"\",\"ceiling_height\":\"10\",\"ceiling_height_custom\":\"\",\"door_height\":\"7\",\"door_height_custom\":\"\",\"window_height\":\"4\",\"window_height_custom\":\"\",\"electrical_load\":\"5\",\"electrical_load_custom\":\"\",\"water_connection\":\"Municipal\",\"water_connection_custom\":\"\",\"sewage_connection\":\"Municipal\",\"sewage_connection_custom\":\"\",\"construction_cost\":\"1500000\",\"construction_duration\":\"8-12\",\"construction_duration_custom\":\"\",\"unlock_price\":\"8000\",\"special_features\":\"\",\"construction_notes\":\"\",\"compliance_certificates\":\"Building Permit, NOC\",\"exterior_finish\":\"Paint\",\"exterior_finish_custom\":\"\",\"interior_finish\":\"Paint\",\"interior_finish_custom\":\"\",\"kitchen_type\":\"Modular\",\"kitchen_type_custom\":\"\",\"bathroom_fittings\":\"Standard\",\"bathroom_fittings_custom\":\"\",\"earthquake_resistance\":\"Zone III Compliant\",\"earthquake_resistance_custom\":\"\",\"fire_safety\":\"Standard\",\"fire_safety_custom\":\"\",\"ventilation\":\"Natural + Exhaust Fans\",\"ventilation_custom\":\"\",\"site_area\":\"\",\"site_area_custom\":\"\",\"land_area\":\"\",\"land_area_custom\":\"\",\"built_up_area\":\"\",\"built_up_area_custom\":\"\",\"carpet_area\":\"\",\"carpet_area_custom\":\"\",\"setback_front\":\"\",\"setback_front_custom\":\"\",\"setback_rear\":\"\",\"setback_rear_custom\":\"\",\"setback_left\":\"\",\"setback_left_custom\":\"\",\"setback_right\":\"\",\"setback_right_custom\":\"\",\"beam_size\":\"9x12\",\"beam_size_custom\":\"\",\"column_size\":\"9x12\",\"column_size_custom\":\"\",\"slab_thickness\":\"5\",\"slab_thickness_custom\":\"\",\"footing_depth\":\"4 feet\",\"footing_depth_custom\":\"\",\"electrical_points\":\"\",\"plumbing_fixtures\":\"\",\"hvac_system\":\"Split AC\",\"hvac_system_custom\":\"\",\"solar_provision\":\"No\",\"solar_provision_custom\":\"\",\"main_door_material\":\"Teak Wood\",\"main_door_material_custom\":\"\",\"window_material\":\"UPVC\",\"window_material_custom\":\"\",\"staircase_material\":\"RCC with Granite\",\"staircase_material_custom\":\"\",\"compound_wall\":\"Yes\",\"compound_wall_custom\":\"\",\"building_plan_approval\":\"Required\",\"building_plan_approval_custom\":\"\",\"environmental_clearance\":\"Not Required\",\"environmental_clearance_custom\":\"\",\"fire_noc\":\"Required\",\"fire_noc_custom\":\"\",\"layout_image\":{\"file\":null,\"name\":\"SHIJIN_THOMAS_House_Plan_layout (1).png\",\"size\":1500728,\"type\":\"image\\/png\",\"preview\":\"blob:http:\\/\\/localhost:3000\\/14d3a771-21fc-4dba-aef7-980f2cdcb11e\",\"uploaded\":true,\"pending_upload\":false,\"stored\":\"81_layout_image_696fb6be7b5d3.png\",\"upload_time\":\"2026-01-20 18:09:18\"},\"elevation_images\":[],\"section_drawings\":[],\"renders_3d\":[]}', NULL, 0.00, 'submitted', 1, NULL, 'Upload Design with Technical Details', '2026-01-20 17:09:18', '2026-01-20 17:09:18', 8000.00, NULL),
(82, 31, 112, 'SHIJIN THOMAS House Plan', 10.00, 10.00, '{\"plan_name\":\"SHIJIN THOMAS House Plan\",\"plot_width\":10,\"plot_height\":10,\"rooms\":[],\"scale_ratio\":1.2,\"total_layout_area\":0,\"total_construction_area\":0,\"floors\":{\"total_floors\":1,\"current_floor\":1,\"floor_names\":{\"1\":\"Ground Floor\"}}}', '{\"foundation_type\":\"RCC\",\"foundation_type_custom\":\"\",\"structure_type\":\"RCC Frame\",\"structure_type_custom\":\"\",\"wall_material\":\"Brick\",\"wall_material_custom\":\"\",\"roofing_type\":\"RCC Slab\",\"roofing_type_custom\":\"\",\"flooring_type\":\"Ceramic Tiles\",\"flooring_type_custom\":\"\",\"wall_thickness\":\"9\",\"wall_thickness_custom\":\"\",\"ceiling_height\":\"10\",\"ceiling_height_custom\":\"\",\"door_height\":\"7\",\"door_height_custom\":\"\",\"window_height\":\"4\",\"window_height_custom\":\"\",\"electrical_load\":\"5\",\"electrical_load_custom\":\"\",\"water_connection\":\"Municipal\",\"water_connection_custom\":\"\",\"sewage_connection\":\"Municipal\",\"sewage_connection_custom\":\"\",\"construction_cost\":\"3000000\",\"construction_duration\":\"8-12\",\"construction_duration_custom\":\"\",\"unlock_price\":\"8000\",\"special_features\":\"\",\"construction_notes\":\"\",\"compliance_certificates\":\"Building Permit, NOC\",\"exterior_finish\":\"Paint\",\"exterior_finish_custom\":\"\",\"interior_finish\":\"Paint\",\"interior_finish_custom\":\"\",\"kitchen_type\":\"Modular\",\"kitchen_type_custom\":\"\",\"bathroom_fittings\":\"Standard\",\"bathroom_fittings_custom\":\"\",\"earthquake_resistance\":\"Zone III Compliant\",\"earthquake_resistance_custom\":\"\",\"fire_safety\":\"Standard\",\"fire_safety_custom\":\"\",\"ventilation\":\"Natural + Exhaust Fans\",\"ventilation_custom\":\"\",\"site_area\":\"\",\"site_area_custom\":\"\",\"land_area\":\"\",\"land_area_custom\":\"\",\"built_up_area\":\"\",\"built_up_area_custom\":\"\",\"carpet_area\":\"\",\"carpet_area_custom\":\"\",\"setback_front\":\"\",\"setback_front_custom\":\"\",\"setback_rear\":\"\",\"setback_rear_custom\":\"\",\"setback_left\":\"\",\"setback_left_custom\":\"\",\"setback_right\":\"\",\"setback_right_custom\":\"\",\"beam_size\":\"9x12\",\"beam_size_custom\":\"\",\"column_size\":\"9x12\",\"column_size_custom\":\"\",\"slab_thickness\":\"5\",\"slab_thickness_custom\":\"\",\"footing_depth\":\"4 feet\",\"footing_depth_custom\":\"\",\"electrical_points\":\"\",\"plumbing_fixtures\":\"\",\"hvac_system\":\"Split AC\",\"hvac_system_custom\":\"\",\"solar_provision\":\"No\",\"solar_provision_custom\":\"\",\"main_door_material\":\"Teak Wood\",\"main_door_material_custom\":\"\",\"window_material\":\"UPVC\",\"window_material_custom\":\"\",\"staircase_material\":\"RCC with Granite\",\"staircase_material_custom\":\"\",\"compound_wall\":\"Yes\",\"compound_wall_custom\":\"\",\"building_plan_approval\":\"Required\",\"building_plan_approval_custom\":\"\",\"environmental_clearance\":\"Not Required\",\"environmental_clearance_custom\":\"\",\"fire_noc\":\"Required\",\"fire_noc_custom\":\"\",\"layout_image\":{\"file\":null,\"name\":\"SHIJIN_THOMAS_House_Plan_layout (1).png\",\"size\":1500728,\"type\":\"image\\/png\",\"preview\":\"blob:http:\\/\\/localhost:3000\\/f49e9091-9cea-4abb-99bf-4d831aae839e\",\"uploaded\":true,\"pending_upload\":false,\"stored\":\"82_layout_image_696fb749881ef.png\",\"upload_time\":\"2026-01-20 18:11:37\"},\"elevation_images\":[],\"section_drawings\":[],\"renders_3d\":[]}', NULL, 0.00, 'submitted', 1, NULL, 'Upload Design with Technical Details', '2026-01-20 17:11:37', '2026-01-20 17:11:37', 8000.00, NULL);

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

--
-- Dumping data for table `house_plan_reviews`
--

INSERT INTO `house_plan_reviews` (`id`, `house_plan_id`, `homeowner_id`, `status`, `feedback`, `reviewed_at`) VALUES
(8, 81, 28, 'pending', NULL, '2026-01-20 17:09:18'),
(9, 82, 35, 'pending', NULL, '2026-01-20 17:11:37');

-- --------------------------------------------------------

--
-- Table structure for table `inbox_messages`
--

CREATE TABLE `inbox_messages` (
  `id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message_type` varchar(50) NOT NULL DEFAULT 'general',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inbox_messages`
--

INSERT INTO `inbox_messages` (`id`, `recipient_id`, `sender_id`, `message_type`, `title`, `message`, `metadata`, `priority`, `is_read`, `read_at`, `created_at`, `updated_at`) VALUES
(4, 28, 27, 'plan_submitted', 'House Plan Ready for Review - SHIJIN THOMAS MCA2024-2026 House Plan', 'Your architect has submitted a complete house plan \"SHIJIN THOMAS MCA2024-2026 House Plan\" with technical specifications. The plan includes 0 rooms covering 0 sq ft with estimated cost of 5000000. Please review and provide feedback.', '{\"plan_id\":7,\"plan_name\":\"SHIJIN THOMAS MCA2024-2026 House Plan\",\"total_rooms\":0,\"total_area\":0,\"estimated_cost\":\"5000000\",\"construction_duration\":\"8-12\",\"architect_id\":27}', 'high', 0, NULL, '2026-01-06 09:27:06', '2026-01-06 09:27:06'),
(5, 27, 28, 'house_plan_deleted', 'House Plan Deleted by Homeowner', 'The homeowner SHIJIN THOMAS MCA2024-2026 has deleted your house plan \"SHIJIN THOMAS MCA2024-2026 House Plan\". They may want a different design approach or have decided not to proceed with this plan.', '{\"house_plan_id\":7,\"plan_name\":\"SHIJIN THOMAS MCA2024-2026 House Plan\",\"homeowner_id\":28,\"homeowner_name\":\"SHIJIN THOMAS MCA2024-2026\",\"action\":\"house_plan_deleted_by_homeowner\"}', 'normal', 0, NULL, '2026-01-06 14:47:10', '2026-01-06 14:47:10'),
(7, 28, 27, 'plan_submitted', 'House Plan Ready for Review - SHIJIN THOMAS MCA2024-2026 House Plan', 'Your architect has submitted a complete house plan \"SHIJIN THOMAS MCA2024-2026 House Plan\" with technical specifications. The plan includes 0 rooms covering 0 sq ft with estimated cost of 5000000. Please review and provide feedback.', '{\"plan_id\":9,\"plan_name\":\"SHIJIN THOMAS MCA2024-2026 House Plan\",\"total_rooms\":0,\"total_area\":0,\"estimated_cost\":\"5000000\",\"construction_duration\":\"8-12\",\"architect_id\":27}', 'high', 0, NULL, '2026-01-06 16:03:50', '2026-01-06 16:03:50'),
(8, 28, 27, 'plan_submitted', 'House Plan Ready for Review - SHIJIN THOMAS MCA2024-2026 House Plan', 'Your architect has submitted a complete house plan \"SHIJIN THOMAS MCA2024-2026 House Plan\" with technical specifications. The plan includes 0 rooms covering 0 sq ft with estimated cost of 5000000. Please review and provide feedback.', '{\"plan_id\":11,\"plan_name\":\"SHIJIN THOMAS MCA2024-2026 House Plan\",\"total_rooms\":0,\"total_area\":0,\"estimated_cost\":\"5000000\",\"construction_duration\":\"8-12\",\"architect_id\":27}', 'high', 0, NULL, '2026-01-06 16:27:55', '2026-01-06 16:27:55'),
(9, 28, 27, 'plan_deleted', 'House Plan Deleted', 'Your architect Shijin Thomas has deleted the house plan \"SHIJIN THOMAS MCA2024-2026 House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":11,\"plan_name\":\"SHIJIN THOMAS MCA2024-2026 House Plan\",\"architect_id\":27,\"architect_name\":\"Shijin Thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-18 06:16:46', '2026-01-18 06:16:46'),
(10, 28, 27, 'plan_deleted', 'House Plan Deleted', 'Your architect Shijin Thomas has deleted the house plan \"SHIJIN THOMAS MCA2024-2026 House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":21,\"plan_name\":\"SHIJIN THOMAS MCA2024-2026 House Plan\",\"architect_id\":27,\"architect_name\":\"Shijin Thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-18 10:49:58', '2026-01-18 10:49:58'),
(11, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":22,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:04:27', '2026-01-20 15:04:27'),
(12, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":23,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:04:34', '2026-01-20 15:04:34'),
(13, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":24,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:21:54', '2026-01-20 15:21:54'),
(14, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":28,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:21:58', '2026-01-20 15:21:58'),
(15, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":52,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:02', '2026-01-20 15:22:02'),
(16, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":75,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:05', '2026-01-20 15:22:05'),
(17, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":79,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:12', '2026-01-20 15:22:12'),
(18, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":78,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:14', '2026-01-20 15:22:14'),
(19, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":77,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:18', '2026-01-20 15:22:18'),
(20, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":76,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:20', '2026-01-20 15:22:20'),
(21, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":74,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:22', '2026-01-20 15:22:22'),
(22, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":73,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:24', '2026-01-20 15:22:24'),
(23, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":72,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:27', '2026-01-20 15:22:27'),
(24, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":71,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:28', '2026-01-20 15:22:28'),
(25, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":70,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:29', '2026-01-20 15:22:29'),
(26, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":69,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:31', '2026-01-20 15:22:31'),
(27, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":68,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:32', '2026-01-20 15:22:32'),
(28, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":67,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:34', '2026-01-20 15:22:34'),
(29, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":66,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:35', '2026-01-20 15:22:35'),
(30, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":65,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:36', '2026-01-20 15:22:36'),
(31, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":64,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:38', '2026-01-20 15:22:38'),
(32, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":63,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:39', '2026-01-20 15:22:39'),
(33, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":62,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:40', '2026-01-20 15:22:40'),
(34, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":61,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:41', '2026-01-20 15:22:41'),
(35, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":60,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:42', '2026-01-20 15:22:42'),
(36, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":59,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:44', '2026-01-20 15:22:44'),
(37, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":58,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:45', '2026-01-20 15:22:45'),
(38, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":57,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:46', '2026-01-20 15:22:46'),
(39, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":56,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:47', '2026-01-20 15:22:47'),
(40, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":55,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:50', '2026-01-20 15:22:50'),
(41, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":54,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:51', '2026-01-20 15:22:51'),
(42, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":53,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:53', '2026-01-20 15:22:53'),
(43, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":51,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:54', '2026-01-20 15:22:54'),
(44, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":50,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:55', '2026-01-20 15:22:55'),
(45, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":49,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:56', '2026-01-20 15:22:56'),
(46, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":48,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:57', '2026-01-20 15:22:57'),
(47, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":47,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:58', '2026-01-20 15:22:58'),
(48, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":46,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:22:59', '2026-01-20 15:22:59'),
(49, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":45,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:00', '2026-01-20 15:23:00'),
(50, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":44,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:15', '2026-01-20 15:23:15'),
(51, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":43,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:16', '2026-01-20 15:23:16'),
(52, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":42,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:18', '2026-01-20 15:23:18'),
(53, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":41,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:19', '2026-01-20 15:23:19'),
(54, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":40,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:20', '2026-01-20 15:23:20'),
(55, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":39,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:21', '2026-01-20 15:23:21'),
(56, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":38,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:22', '2026-01-20 15:23:22'),
(57, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":37,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:23', '2026-01-20 15:23:23'),
(58, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":36,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:24', '2026-01-20 15:23:24'),
(59, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":35,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:25', '2026-01-20 15:23:25'),
(60, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":34,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:26', '2026-01-20 15:23:26'),
(61, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":33,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:27', '2026-01-20 15:23:27'),
(62, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":32,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:28', '2026-01-20 15:23:28'),
(63, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":31,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:30', '2026-01-20 15:23:30'),
(64, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":30,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:31', '2026-01-20 15:23:31'),
(65, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":29,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:32', '2026-01-20 15:23:32'),
(66, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":27,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:33', '2026-01-20 15:23:33'),
(67, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":26,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:34', '2026-01-20 15:23:34'),
(68, 35, 31, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', '{\"plan_id\":25,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}', 'normal', 0, NULL, '2026-01-20 15:23:35', '2026-01-20 15:23:35'),
(69, 28, 27, 'plan_submitted', 'House Plan Ready for Review - SHIJIN THOMAS MCA2024-2026 House Plan', 'Your architect has submitted a complete house plan \"SHIJIN THOMAS MCA2024-2026 House Plan\" with technical specifications. The plan includes 0 rooms covering 0 sq ft with estimated cost of 1500000. Please review and provide feedback.', '{\"plan_id\":81,\"plan_name\":\"SHIJIN THOMAS MCA2024-2026 House Plan\",\"total_rooms\":0,\"total_area\":0,\"estimated_cost\":\"1500000\",\"construction_duration\":\"8-12\",\"architect_id\":27}', 'high', 0, NULL, '2026-01-20 17:09:18', '2026-01-20 17:09:18'),
(70, 35, 31, 'plan_submitted', 'House Plan Ready for Review - SHIJIN THOMAS House Plan', 'Your architect has submitted a complete house plan \"SHIJIN THOMAS House Plan\" with technical specifications. The plan includes 0 rooms covering 0 sq ft with estimated cost of 3000000. Please review and provide feedback.', '{\"plan_id\":82,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"total_rooms\":0,\"total_area\":0,\"estimated_cost\":\"3000000\",\"construction_duration\":\"8-12\",\"architect_id\":31}', 'high', 0, NULL, '2026-01-20 17:11:37', '2026-01-20 17:11:37');

-- --------------------------------------------------------

--
-- Table structure for table `international_payment_settings`
--

CREATE TABLE `international_payment_settings` (
  `id` int(11) NOT NULL,
  `country_code` varchar(2) NOT NULL,
  `country_name` varchar(100) NOT NULL,
  `currency_code` varchar(3) NOT NULL,
  `is_supported` tinyint(1) DEFAULT 1,
  `supported_methods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supported_methods`)),
  `min_amount` decimal(15,2) DEFAULT 0.00,
  `max_amount` decimal(15,2) DEFAULT 1000000.00,
  `processing_fee_percentage` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `international_payment_settings`
--

INSERT INTO `international_payment_settings` (`id`, `country_code`, `country_name`, `currency_code`, `is_supported`, `supported_methods`, `min_amount`, `max_amount`, `processing_fee_percentage`, `created_at`, `updated_at`) VALUES
(1, 'IN', 'India', 'INR', 1, '[\"card\", \"netbanking\", \"wallet\", \"upi\"]', 0.00, 1000000.00, 0.00, '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(2, 'US', 'United States', 'USD', 1, '[\"card\"]', 0.00, 12000.00, 0.00, '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(3, 'GB', 'United Kingdom', 'GBP', 1, '[\"card\"]', 0.00, 9500.00, 0.00, '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(4, 'CA', 'Canada', 'CAD', 1, '[\"card\"]', 0.00, 16000.00, 0.00, '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(5, 'AU', 'Australia', 'AUD', 1, '[\"card\"]', 0.00, 18000.00, 0.00, '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(6, 'SG', 'Singapore', 'SGD', 1, '[\"card\"]', 0.00, 16000.00, 0.00, '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(7, 'AE', 'United Arab Emirates', 'AED', 1, '[\"card\"]', 0.00, 44000.00, 0.00, '2026-01-11 15:27:58', '2026-01-11 15:27:58'),
(8, 'MY', 'Malaysia', 'MYR', 1, '[\"card\"]', 0.00, 50000.00, 0.00, '2026-01-11 15:27:58', '2026-01-11 15:27:58');

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
(105, 28, 28, '20', '50-75 Lakhs', '{\"plot_shape\":\"Rectangular\",\"topography\":\"Flat\",\"development_laws\":\"Standard\",\"family_needs\":\"\",\"rooms\":\"master_bedroom,bedrooms,bathrooms,attached_bathroom,kitchen,living_room,dining_room,study_room,prayer_room,guest_room,store_room,balcony,terrace,garage\",\"aesthetic\":\"Modern\",\"notes\":\"\",\"orientation\":null,\"site_considerations\":null,\"material_preferences\":null,\"budget_allocation\":null,\"num_floors\":\"2\",\"preferred_style\":\"Modern\",\"floor_rooms\":{\"floor1\":{\"master_bedroom\":1,\"bedrooms\":1,\"bathrooms\":1,\"attached_bathroom\":1,\"kitchen\":1,\"living_room\":1,\"dining_room\":1,\"store_room\":1,\"garage\":1},\"floor2\":{\"study_room\":1,\"prayer_room\":1,\"guest_room\":1,\"balcony\":1,\"terrace\":1,\"bedrooms\":1,\"bathrooms\":1,\"attached_bathroom\":1}},\"site_images\":[],\"reference_images\":[],\"room_images\":[]}', 'Modern', 'approved', '2026-01-05 16:33:31', '2026-01-07 10:35:27', 'Mumbai', '3-6 months', NULL, 'custom', NULL, '[]', '[]', '[]', NULL, NULL, NULL, NULL, '{\"floor1\":{\"master_bedroom\":1,\"bedrooms\":1,\"bathrooms\":1,\"attached_bathroom\":1,\"kitchen\":1,\"living_room\":1,\"dining_room\":1,\"store_room\":1,\"garage\":1},\"floor2\":{\"study_room\":1,\"prayer_room\":1,\"guest_room\":1,\"balcony\":1,\"terrace\":1,\"bedrooms\":1,\"bathrooms\":1,\"attached_bathroom\":1}}', '2', '2500'),
(109, 19, 19, '30x40', '20-30 lakhs', 'Test request for upload flow', NULL, 'deleted', '2026-01-06 16:23:50', '2026-01-07 10:26:00', NULL, NULL, NULL, 'custom', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(111, 28, 28, '2000 sq ft', '10-15 lakhs', '{\"plot_shape\":\"Rectangular\",\"topography\":\"Flat\",\"development_laws\":\"Standard\",\"family_needs\":\"Modern family home\",\"rooms\":\"master_bedroom,bedrooms,bathrooms,kitchen,living_room,dining_room\",\"aesthetic\":\"Modern\",\"notes\":\"Construction progress tracking project\"}', 'Modern', 'approved', '2026-01-19 07:50:59', '2026-01-19 07:50:59', 'Mumbai, Maharashtra', '6-12 months', NULL, 'custom', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL),
(112, 35, 35, '10', '20-30 Lakhs', '{\"plot_shape\":\"Rectangular\",\"topography\":\"Flat\",\"development_laws\":\"Standard\",\"family_needs\":\"\",\"rooms\":\"master_bedroom,bedrooms,attached_bathroom,bathrooms,living_room,dining_room,store_room,garage,kitchen\",\"aesthetic\":\"Eco-Friendly Modern\",\"notes\":\"\",\"orientation\":null,\"site_considerations\":null,\"material_preferences\":null,\"budget_allocation\":null,\"num_floors\":\"1\",\"preferred_style\":\"Eco-Friendly Modern\",\"floor_rooms\":{\"floor1\":{\"master_bedroom\":1,\"bedrooms\":3,\"attached_bathroom\":2,\"bathrooms\":1,\"living_room\":1,\"dining_room\":1,\"store_room\":1,\"garage\":1,\"kitchen\":1}},\"site_images\":[],\"reference_images\":[],\"room_images\":[]}', 'Eco-Friendly Modern', 'approved', '2026-01-20 13:16:19', '2026-01-20 14:33:16', 'Kanjirappally', '6-12 months', NULL, 'custom', NULL, '[]', '[]', '[]', NULL, NULL, NULL, NULL, '{\"floor1\":{\"master_bedroom\":1,\"bedrooms\":3,\"attached_bathroom\":2,\"bathrooms\":1,\"living_room\":1,\"dining_room\":1,\"store_room\":1,\"garage\":1,\"kitchen\":1}}', '1', '1500');

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
(60, 105, 28, 27, '', 'accepted', '2026-01-18 09:35:19', '2026-01-18 09:35:43'),
(61, 112, 35, 31, NULL, 'accepted', '2026-01-20 13:16:19', '2026-01-20 14:33:16');

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

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `from_user_id`, `to_user_id`, `subject`, `message`, `message_type`, `related_id`, `is_read`, `created_at`) VALUES
(1, 37, 28, 'Layout Request Acknowledged - Test Layout', 'Hello! I have acknowledged your layout request for \'Test Layout\' and will begin working on your estimate. Expected completion: January 15, 2026. I\'ll keep you updated on the progress.', 'acknowledgment', NULL, 0, '2026-01-07 15:40:39'),
(2, 37, 35, 'Layout Request Acknowledged - Layout', 'Hello! I have acknowledged your layout request for \'Layout\' and will begin working on your estimate. Expected completion: January 21, 2026. I\'ll keep you updated on the progress.', 'acknowledgment', NULL, 0, '2026-01-20 17:26:44');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_progress_reports`
--

CREATE TABLE `monthly_progress_reports` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `report_month` int(11) NOT NULL CHECK (`report_month` >= 1 and `report_month` <= 12),
  `report_year` int(11) NOT NULL,
  `planned_progress_percentage` decimal(5,2) NOT NULL,
  `milestones_achieved` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`milestones_achieved`)),
  `delay_explanation` text DEFAULT NULL,
  `contractor_remarks` text NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `metadata` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `related_id`, `is_read`, `created_at`, `metadata`) VALUES
(1, 19, 'test_notification', 'Test Notification', 'This is a test notification created by the notification system test.', NULL, 0, '2025-12-30 09:45:32', NULL),
(2, 28, 'test_notification', 'Test Notification', 'This is a test notification created at 2025-12-30 11:00:56', NULL, 1, '2025-12-30 10:00:56', NULL),
(3, 28, 'house_plan_submitted', 'New House Plan Submitted', 'Architect has submitted a custom house plan: SHIJIN THOMAS MCA2024-2026 House Plan', 3, 0, '2026-01-06 07:36:28', NULL),
(6, 28, 'house_plan_submitted', 'New House Plan with Technical Details', 'Your architect has submitted a complete house plan \"SHIJIN THOMAS MCA2024-2026 House Plan\" with technical specifications. The plan includes 0 rooms covering 0 sq ft with estimated cost of 5000000. Please review and provide feedback.', 7, 0, '2026-01-06 09:27:06', NULL),
(8, 27, 'technical_details_purchased', 'Technical Details Purchased', 'Homeowner has purchased technical details for \"SHIJIN THOMAS MCA2024-2026 House Plan\" (₹8000.00). They now have full access to your technical specifications.', 7, 0, '2026-01-06 10:45:18', NULL),
(10, 27, 'technical_details_purchased', 'Technical Details Purchased', 'Homeowner has purchased technical details for \"SHIJIN THOMAS MCA2024-2026 House Plan\" (₹8000.00). They now have full access to your technical specifications.', 7, 0, '2026-01-06 13:25:41', NULL),
(13, 27, 'technical_details_purchased', 'Technical Details Purchased', 'Homeowner has purchased technical details for \"SHIJIN THOMAS MCA2024-2026 House Plan\" (₹8000.00). They now have full access to your technical specifications.', 8, 0, '2026-01-06 15:13:00', NULL),
(14, 28, 'house_plan_submitted', 'New House Plan with Technical Details', 'Your architect has submitted a complete house plan \"SHIJIN THOMAS MCA2024-2026 House Plan\" with technical specifications. The plan includes 0 rooms covering 0 sq ft with estimated cost of 5000000. Please review and provide feedback.', 9, 0, '2026-01-06 16:03:50', NULL),
(16, 27, 'technical_details_purchased', 'Technical Details Purchased', 'Homeowner has purchased technical details for \"SHIJIN THOMAS MCA2024-2026 House Plan\" (₹8000.00). They now have full access to your technical specifications.', 9, 0, '2026-01-06 16:04:32', NULL),
(17, 28, 'house_plan_submitted', 'New House Plan with Technical Details', 'Your architect has submitted a complete house plan \"SHIJIN THOMAS MCA2024-2026 House Plan\" with technical specifications. The plan includes 0 rooms covering 0 sq ft with estimated cost of 5000000. Please review and provide feedback.', 11, 0, '2026-01-06 16:27:55', NULL),
(19, 27, 'technical_details_purchased', 'Technical Details Purchased', 'Homeowner has purchased technical details for \"SHIJIN THOMAS MCA2024-2026 House Plan\" (₹8000.00). They now have full access to your technical specifications.', 11, 0, '2026-01-06 16:28:39', NULL),
(20, 29, 'house_plan_received', 'New House Plan for Estimate', 'You have received a house plan \"SHIJIN THOMAS MCA2024-2026 House Plan (House Plan)\" from a homeowner. Plot: 20.00 × 20.00, Area: 0 sq ft. Please review and provide your construction estimate.', 16, 0, '2026-01-06 17:10:28', '{\"send_id\":\"16\",\"house_plan_id\":11,\"homeowner_id\":28,\"plan_name\":\"SHIJIN THOMAS MCA2024-2026 House Plan (House Plan)\",\"total_area\":0}'),
(21, 29, 'house_plan_received', 'New House Plan for Estimate', 'You have received a house plan \"SHIJIN THOMAS MCA2024-2026 House Plan (House Plan)\" from a homeowner. Plot: 20.00 × 20.00, Area: 0 sq ft. Please review and provide your construction estimate.', 17, 0, '2026-01-07 05:37:58', '{\"send_id\":\"17\",\"house_plan_id\":11,\"homeowner_id\":28,\"plan_name\":\"SHIJIN THOMAS MCA2024-2026 House Plan (House Plan)\",\"total_area\":0}'),
(22, 29, 'house_plan_received', 'New House Plan for Estimate', 'You have received a house plan \"SHIJIN THOMAS MCA2024-2026 House Plan\" from a homeowner. Plot: 20\' × 20\', Area: 0 sq ft. Please review and provide your construction estimate.', 18, 0, '2026-01-07 15:00:37', '{\"send_id\":\"18\",\"house_plan_id\":11,\"homeowner_id\":28,\"plan_name\":\"SHIJIN THOMAS MCA2024-2026 House Plan\",\"total_area\":0}'),
(23, 28, 'estimate_received', 'New Estimate Received', 'You have received a new estimate from Shijin Thomas for project: Your Project', 36, 0, '2026-01-11 08:09:25', NULL),
(24, 28, 'estimate_received', 'New Estimate Received', 'You have received a new estimate from Shijin Thomas for project: Your Project', 37, 0, '2026-01-14 15:56:17', NULL),
(25, 28, 'plan_deleted', 'House Plan Deleted', 'Your architect Shijin Thomas has deleted the house plan \"SHIJIN THOMAS MCA2024-2026 House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-18 06:16:46', '{\"plan_id\":11,\"plan_name\":\"SHIJIN THOMAS MCA2024-2026 House Plan\",\"architect_id\":27,\"architect_name\":\"Shijin Thomas\",\"action\":\"plan_deleted\"}'),
(26, 28, 'plan_deleted', 'House Plan Deleted', 'Your architect Shijin Thomas has deleted the house plan \"SHIJIN THOMAS MCA2024-2026 House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-18 10:49:58', '{\"plan_id\":21,\"plan_name\":\"SHIJIN THOMAS MCA2024-2026 House Plan\",\"architect_id\":27,\"architect_name\":\"Shijin Thomas\",\"action\":\"plan_deleted\"}'),
(27, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:04:27', '{\"plan_id\":22,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(28, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:04:34', '{\"plan_id\":23,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(29, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:21:54', '{\"plan_id\":24,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(30, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:21:58', '{\"plan_id\":28,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(31, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:02', '{\"plan_id\":52,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(32, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:05', '{\"plan_id\":75,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(33, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:12', '{\"plan_id\":79,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(34, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:14', '{\"plan_id\":78,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(35, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:18', '{\"plan_id\":77,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(36, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:20', '{\"plan_id\":76,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(37, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:22', '{\"plan_id\":74,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(38, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:24', '{\"plan_id\":73,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(39, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:27', '{\"plan_id\":72,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(40, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:28', '{\"plan_id\":71,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(41, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:29', '{\"plan_id\":70,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(42, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:31', '{\"plan_id\":69,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(43, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:32', '{\"plan_id\":68,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(44, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:34', '{\"plan_id\":67,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(45, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:35', '{\"plan_id\":66,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(46, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:36', '{\"plan_id\":65,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(47, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:38', '{\"plan_id\":64,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(48, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:39', '{\"plan_id\":63,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(49, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:40', '{\"plan_id\":62,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(50, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:41', '{\"plan_id\":61,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(51, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:42', '{\"plan_id\":60,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(52, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:44', '{\"plan_id\":59,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(53, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:45', '{\"plan_id\":58,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(54, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:46', '{\"plan_id\":57,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(55, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:47', '{\"plan_id\":56,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(56, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:50', '{\"plan_id\":55,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(57, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:51', '{\"plan_id\":54,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(58, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:53', '{\"plan_id\":53,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(59, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:54', '{\"plan_id\":51,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(60, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:55', '{\"plan_id\":50,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(61, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:56', '{\"plan_id\":49,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(62, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:57', '{\"plan_id\":48,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(63, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:58', '{\"plan_id\":47,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(64, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:22:59', '{\"plan_id\":46,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(65, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:00', '{\"plan_id\":45,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(66, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:15', '{\"plan_id\":44,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(67, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:16', '{\"plan_id\":43,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(68, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:18', '{\"plan_id\":42,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(69, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:19', '{\"plan_id\":41,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(70, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:20', '{\"plan_id\":40,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(71, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:21', '{\"plan_id\":39,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(72, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:22', '{\"plan_id\":38,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(73, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:23', '{\"plan_id\":37,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(74, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:24', '{\"plan_id\":36,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(75, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:25', '{\"plan_id\":35,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(76, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:26', '{\"plan_id\":34,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(77, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:28', '{\"plan_id\":33,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(78, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:28', '{\"plan_id\":32,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(79, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:30', '{\"plan_id\":31,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(80, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:31', '{\"plan_id\":30,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(81, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:32', '{\"plan_id\":29,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(82, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:33', '{\"plan_id\":27,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(83, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:34', '{\"plan_id\":26,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(84, 35, 'plan_deleted', 'House Plan Deleted', 'Your architect shijin thomas has deleted the house plan \"SHIJIN THOMAS House Plan\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.', NULL, 0, '2026-01-20 15:23:35', '{\"plan_id\":25,\"plan_name\":\"SHIJIN THOMAS House Plan\",\"architect_id\":31,\"architect_name\":\"shijin thomas\",\"action\":\"plan_deleted\"}'),
(85, 28, 'house_plan_submitted', 'New House Plan with Technical Details', 'Your architect has submitted a complete house plan \"SHIJIN THOMAS MCA2024-2026 House Plan\" with technical specifications. The plan includes 0 rooms covering 0 sq ft with estimated cost of 1500000. Please review and provide feedback.', 81, 0, '2026-01-20 17:09:18', NULL),
(86, 35, 'house_plan_submitted', 'New House Plan with Technical Details', 'Your architect has submitted a complete house plan \"SHIJIN THOMAS House Plan\" with technical specifications. The plan includes 0 rooms covering 0 sq ft with estimated cost of 3000000. Please review and provide feedback.', 82, 0, '2026-01-20 17:11:37', NULL),
(87, 35, 'payment_success', 'Technical Details Unlocked', 'Payment of ₹8000.00 successful! Technical details for \"SHIJIN THOMAS House Plan\" are now unlocked and available for viewing.', 82, 0, '2026-01-20 17:22:14', NULL),
(88, 31, 'technical_details_purchased', 'Technical Details Purchased', 'Homeowner has purchased technical details for \"SHIJIN THOMAS House Plan\" (₹8000.00). They now have full access to your technical specifications.', 82, 0, '2026-01-20 17:22:14', NULL);

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
-- Table structure for table `payment_failure_logs`
--

CREATE TABLE `payment_failure_logs` (
  `id` int(11) NOT NULL,
  `payment_type` enum('technical_details','stage_payment') NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `razorpay_order_id` varchar(255) DEFAULT NULL,
  `error_code` varchar(50) DEFAULT NULL,
  `error_description` text DEFAULT NULL,
  `country_code` varchar(2) DEFAULT NULL,
  `currency` varchar(3) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Dumping data for table `payment_notifications`
--

INSERT INTO `payment_notifications` (`id`, `payment_request_id`, `recipient_id`, `recipient_type`, `notification_type`, `title`, `message`, `is_read`, `created_at`, `read_at`) VALUES
(1, 12, 29, 'contractor', 'request_approved', 'Payment Approved: Column Construction Stage', 'Homeowner has approved your payment request of ₹280,000.00 for Column Construction stage. Payment will be processed soon.', 0, '2026-01-11 13:20:27', NULL),
(2, 1, 29, 'contractor', 'request_approved', 'Payment Approved: Foundation Stage', 'Homeowner has approved your payment request of ₹100,000.00 for Foundation stage. Payment will be processed soon.', 0, '2026-01-11 13:27:09', NULL),
(3, 13, 29, 'contractor', 'request_approved', 'Payment Approved: Foundation Stage', 'Homeowner has approved your payment request of ₹50,000.00 for Foundation stage. Payment will be processed soon.', 0, '2026-01-11 15:54:16', NULL),
(4, 14, 29, 'contractor', 'request_approved', 'Payment Approved: Structure Stage', 'Homeowner has approved your payment request of ₹250.00 for Structure stage. Payment will be processed soon.', 0, '2026-01-13 08:50:54', NULL),
(5, 18, 29, 'contractor', 'request_approved', 'Payment Approved: Roofing Stage', 'Homeowner has approved your payment request of ₹150,000.00 for Roofing stage. Payment will be processed soon.', 0, '2026-01-20 07:42:22', NULL),
(6, 15, 29, 'contractor', 'request_approved', 'Payment Approved: Foundation Stage', 'Homeowner has approved your payment request of ₹213,949.00 for Foundation stage. Payment will be processed soon.', 0, '2026-01-20 07:42:28', NULL);

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

--
-- Dumping data for table `progress_reports`
--

INSERT INTO `progress_reports` (`id`, `project_id`, `contractor_id`, `homeowner_id`, `report_type`, `report_period_start`, `report_period_end`, `report_data`, `created_at`, `updated_at`, `status`, `homeowner_viewed_at`, `homeowner_acknowledged_at`, `acknowledgment_notes`) VALUES
(1, 105, 29, 28, 'daily', '2026-01-18', '2026-01-18', '{\"project\":{\"id\":1,\"name\":\"Modern Villa Construction - Phase 2\"},\"contractor\":{\"id\":27,\"name\":\"Shijin Thomas\"},\"homeowner\":{\"id\":28,\"name\":\"SHIJIN THOMAS MCA2024-2026\"},\"summary\":{\"total_days\":1,\"total_workers\":8,\"total_hours\":64,\"progress_percentage\":5,\"total_wages\":25000,\"photos_count\":15,\"geo_photos_count\":12},\"daily_updates\":[{\"update_date\":\"2026-01-18\",\"construction_stage\":\"Foundation Work\",\"work_done_today\":\"Completed foundation excavation and started concrete pouring for the main foundation\",\"incremental_completion_percentage\":5,\"working_hours\":8,\"weather_condition\":\"Clear\",\"site_issues\":null}],\"labour_analysis\":{\"Mason\":{\"total_workers\":3,\"total_hours\":24,\"overtime_hours\":0,\"total_wages\":9000,\"avg_productivity\":4},\"Helper\":{\"total_workers\":4,\"total_hours\":32,\"overtime_hours\":0,\"total_wages\":8000,\"avg_productivity\":4},\"Supervisor\":{\"total_workers\":1,\"total_hours\":8,\"overtime_hours\":0,\"total_wages\":8000,\"avg_productivity\":5}},\"costs\":{\"labour_cost\":25000,\"material_cost\":15000,\"equipment_cost\":5000,\"total_cost\":45000},\"materials\":[{\"name\":\"Cement\",\"quantity\":50,\"unit\":\"bags\"},{\"name\":\"Steel Rods\",\"quantity\":500,\"unit\":\"kg\"},{\"name\":\"Sand\",\"quantity\":10,\"unit\":\"cubic meters\"}],\"photos\":[{\"url\":\"\\/buildhub\\/uploads\\/progress\\/foundation_1.jpg\",\"date\":\"2026-01-18\",\"location\":\"Foundation Area\"},{\"url\":\"\\/buildhub\\/uploads\\/progress\\/foundation_2.jpg\",\"date\":\"2026-01-18\",\"location\":\"Excavation Site\"},{\"url\":\"\\/buildhub\\/uploads\\/progress\\/foundation_3.jpg\",\"date\":\"2026-01-18\",\"location\":\"Concrete Pouring\"}],\"quality\":{\"safety_score\":4,\"quality_score\":4,\"schedule_adherence\":95},\"recommendations\":[{\"priority\":\"High\",\"title\":\"Weather Monitoring\",\"description\":\"Monitor weather conditions for concrete curing\"},{\"priority\":\"Medium\",\"title\":\"Material Delivery\",\"description\":\"Schedule next batch of materials for upcoming work\"}]}', '2026-01-19 07:42:48', '2026-01-19 16:28:50', 'sent', NULL, NULL, NULL);

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
-- Table structure for table `room_improvement_analyses`
--

CREATE TABLE `room_improvement_analyses` (
  `id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `room_type` enum('bedroom','living_room','kitchen','dining_room','other') NOT NULL,
  `improvement_notes` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `analysis_result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`analysis_result`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores room improvement analysis results with AI-generated suggestions';

--
-- Dumping data for table `room_improvement_analyses`
--

INSERT INTO `room_improvement_analyses` (`id`, `homeowner_id`, `room_type`, `improvement_notes`, `image_path`, `analysis_result`, `created_at`, `updated_at`) VALUES
(1, 28, 'bedroom', 'colcors', 'room_28_1768233990_69651c0601f47.jpg', '{\"concept_name\":\"Serene Sleep Sanctuary Enhancement\",\"room_condition_summary\":\"Analysis of your bedroom reveals opportunities to create a more restful and personalized sleeping environment.\",\"visual_observations\":[\"Natural light availability and window placement\",\"Current color scheme and wall treatments\",\"Furniture arrangement and space utilization\",\"Storage solutions and organization\"],\"improvement_suggestions\":{\"lighting\":\"Consider layered lighting with warm-toned bedside lamps, dimmable overhead fixtures, and blackout curtains for better sleep quality. Soft ambient lighting creates a calming atmosphere.\",\"color_ambience\":\"Incorporate calming neutral tones like soft blues, warm grays, or earth tones. These colors promote relaxation and better sleep while maintaining a sophisticated look.\",\"furniture_layout\":\"Optimize furniture placement for better flow and functionality. Consider a comfortable reading chair, adequate nightstand storage, and ensure the bed is positioned away from direct light sources.\"},\"style_recommendation\":{\"style\":\"Modern Minimalist with Cozy Accents\",\"description\":\"A clean, uncluttered aesthetic with warm textures and personal touches that promote rest and relaxation.\",\"key_elements\":[\"Soft textures\",\"Warm lighting\",\"Neutral colors\",\"Functional storage\",\"Personal artwork\"]},\"visual_reference\":\"Imagine a space with clean lines, soft bedding in neutral tones, warm wood accents, and carefully placed lighting that creates a hotel-like serenity in your own home.\",\"analysis_metadata\":{\"room_type\":\"bedroom\",\"user_notes\":\"colcors\",\"image_dimensions\":\"2000x2400\",\"analysis_timestamp\":\"2026-01-12 17:06:30\"}}', '2026-01-12 16:06:30', '2026-01-12 16:06:30'),
(46, 28, 'bedroom', '', 'room_28_1768384448_696767c0ca194.jpg', '{\"concept_name\":\"Restful Sleep Sanctuary\",\"room_condition_summary\":\"Your bedroom shows potential for creating a more restful and organized sleeping environment.\",\"visual_observations\":[\"Lighting condition: moderate_lighting (confidence: 30%)\",\"Dominant colors: gray, white, brown, blue\",\"Color temperature: cool bias (%)\",\"Contrast level: 18%\",\"Saturation level: 35%\"],\"improvement_suggestions\":{\"lighting\":\"Consider adding layered lighting with bedside lamps for reading and dimmable overhead lighting for ambiance.\",\"color_ambience\":\"Your room features a cool color scheme (% cool bias). Consider adding warm accents for comfort. Soft, calming colors like muted blues, gentle greens, or warm neutrals can promote better sleep.\",\"furniture_layout\":\"Ensure your bed is the focal point, with adequate space for movement and storage solutions for organization.\"},\"style_recommendation\":{\"style\":\"Contemporary Comfort\",\"description\":\"A blend of modern functionality with cozy, personal touches that promote relaxation.\",\"key_elements\":[\"Comfortable bedding\",\"Adequate storage\",\"Soft lighting\",\"Calming colors\"],\"confidence\":75},\"visual_reference\":\"Imagine a serene retreat with soft textures, organized storage, and gentle lighting that creates a peaceful atmosphere for rest and rejuvenation.\",\"analysis_metadata\":{\"room_type\":\"bedroom\",\"user_notes\":\"\",\"image_dimensions\":\"2000x2400\",\"analysis_timestamp\":\"2026-01-14 10:54:39\",\"system_type\":\"hybrid_ai_basic_rules\",\"ai_enhancement_enabled\":false},\"visual_intelligence\":{\"extracted_features\":{\"brightness\":74,\"contrast\":18,\"dominant_colors\":{\"gray\":35,\"white\":30,\"brown\":25,\"blue\":10},\"color_temperature\":{\"category\":\"cool\",\"score\":74},\"saturation_level\":35,\"image_dimensions\":{\"width\":2000,\"height\":2400},\"aspect_ratio\":0.83},\"design_attributes\":{\"lighting_condition\":{\"primary_assessment\":\"moderate_lighting\",\"secondary_notes\":[\"soft_even_lighting\"],\"confidence\":30,\"reasoning\":\"Moderate brightness level (74\\/100) indicates balanced lighting\"},\"ambience_character\":{\"primary_character\":\"calm_modern\",\"mood_indicators\":[\"sophisticated\",\"clean\",\"earthy\"],\"energy_level\":\"low_to_moderate\",\"reasoning\":\"Cool colors (74% cool bias) with moderate saturation create calm atmosphere\"},\"color_harmony\":{\"harmony_type\":\"complex_varied\",\"color_distribution\":\"diverse_palette\",\"recommendations\":[\"consider_simplification\"],\"reasoning\":\"Multiple colors (4) may benefit from simplification\"},\"visual_balance\":{\"contrast_balance\":\"low_contrast\",\"tonal_balance\":\"dark_dominant\",\"spatial_balance\":\"proportioned_space\",\"overall_assessment\":\"partially_balanced\",\"reasoning\":\"Contrast: 18%, Brightness: 74, Aspect: 0.83 - 1\\/3 elements balanced\"},\"space_perception\":{\"spaciousness\":\"appears_confined\",\"depth_perception\":\"\",\"openness_factor\":\"low\",\"recommendations\":[\"increase_lighting\",\"add_light_colors\"],\"reasoning\":\"Brightness: 74, Light colors: 65%\"},\"style_indicators\":{\"primary_style_lean\":\"modern_minimalist\",\"style_confidence\":60,\"style_scores\":{\"modern_minimalist\":60,\"traditional_classic\":45,\"vintage_eclectic\":35,\"rustic_natural\":30,\"contemporary_bold\":20},\"reasoning\":\"Based on saturation (35%), contrast (18%), and color analysis\"},\"feature_mapping_log\":{\"timestamp\":\"2026-01-14 10:54:08\",\"input_features\":{\"brightness\":74,\"contrast\":18,\"color_temperature\":\"cool\",\"saturation\":35,\"dominant_colors_count\":4},\"mapping_decisions\":{\"lighting_assessment\":\"moderate_lighting\",\"ambience_character\":\"calm_modern\",\"visual_balance\":\"partially_balanced\",\"style_indication\":\"modern_minimalist\"},\"confidence_scores\":{\"lighting\":30,\"style\":60}}},\"feature_influence\":[],\"analysis_method\":\"basic_heuristic_analysis\"},\"ai_enhancements\":{\"detected_objects\":{\"total_objects\":0,\"major_items\":[],\"furniture_categories\":[],\"detection_summary\":\"AI service unavailable\"},\"spatial_analysis\":{\"zones_analyzed\":0,\"spatial_insights\":[],\"layout_assessment\":\"AI service unavailable\"},\"enhanced_visual_features\":[],\"spatial_guidance\":{\"placement_recommendations\":[],\"layout_improvements\":[],\"safety_considerations\":[],\"guidance_summary\":\"AI service unavailable\"},\"conceptual_visualization\":{\"success\":true,\"image_url\":\"\\/buildhub\\/uploads\\/conceptual_images\\/real_ai_bedroom_20260114_154142.png\",\"image_path\":\"uploads\\/conceptual_images\\/real_ai_bedroom_20260114_154142.png\",\"disclaimer\":\"AI-Generated Conceptual Visualization \\/ Inspirational Preview\",\"generation_metadata\":{\"model_id\":\"stable-diffusion-v1-5\",\"generation_type\":\"real_ai_generated\",\"image_size\":\"512x512\",\"file_size\":425327}},\"design_description\":\"\",\"ai_metadata\":{\"pipeline_type\":\"fallback\",\"gemini_api_available\":false,\"diffusion_device\":\"unavailable\",\"analysis_timestamp\":\"2026-01-14T10:54:39+01:00\",\"room_type\":\"bedroom\",\"stages_completed\":0},\"integration_status\":{\"status\":\"fallback\",\"message\":\"AI service temporarily unavailable, using rule-based analysis only\",\"error\":\"cURL error: Operation timed out after 30015 milliseconds with 0 bytes received\"},\"async_image_generation\":null}}', '2026-01-14 09:54:39', '2026-01-19 09:17:32'),
(47, 28, 'bedroom', '', 'room_28_1768385266_69676af20519e.jpg', '{\"concept_name\":\"Restful Sleep Sanctuary\",\"room_condition_summary\":\"Your bedroom shows potential for creating a more restful and organized sleeping environment.\",\"visual_observations\":[\"Lighting condition: moderate_lighting (confidence: 21.9%)\",\"Dominant colors: gray, white, brown, blue\",\"Color temperature: neutral bias (%)\",\"Contrast level: 16%\",\"Saturation level: 45%\"],\"improvement_suggestions\":{\"lighting\":\"Consider adding layered lighting with bedside lamps for reading and dimmable overhead lighting for ambiance. AI-detected objects suggest: Provide adequate lighting for seating areas to create inviting spaces. Add bedside reading lights and consider dimmable overhead lighting for relaxation.\",\"color_ambience\":\"Soft, calming colors like muted blues, gentle greens, or warm neutrals can promote better sleep.\",\"furniture_layout\":\"Ensure your bed is the focal point, with adequate space for movement and storage solutions for organization.\"},\"style_recommendation\":{\"style\":\"Contemporary Comfort\",\"description\":\"A blend of modern functionality with cozy, personal touches that promote relaxation.\",\"key_elements\":[\"Comfortable bedding\",\"Adequate storage\",\"Soft lighting\",\"Calming colors\"],\"confidence\":75},\"visual_reference\":\"Imagine a serene retreat with soft textures, organized storage, and gentle lighting that creates a peaceful atmosphere for rest and rejuvenation.\",\"analysis_metadata\":{\"room_type\":\"bedroom\",\"user_notes\":\"\",\"image_dimensions\":\"2000x2400\",\"analysis_timestamp\":\"2026-01-14 11:07:48\",\"system_type\":\"hybrid_ai_basic_rules\",\"ai_enhancement_enabled\":true},\"visual_intelligence\":{\"extracted_features\":{\"brightness\":92,\"contrast\":16,\"dominant_colors\":{\"gray\":35,\"white\":30,\"brown\":25,\"blue\":10},\"color_temperature\":{\"category\":\"neutral\",\"score\":0},\"saturation_level\":45,\"image_dimensions\":{\"width\":2000,\"height\":2400},\"aspect_ratio\":0.83},\"design_attributes\":{\"lighting_condition\":{\"primary_assessment\":\"moderate_lighting\",\"secondary_notes\":[\"soft_even_lighting\"],\"confidence\":21.9,\"reasoning\":\"Moderate brightness level (92\\/100) indicates balanced lighting\"},\"ambience_character\":{\"primary_character\":\"balanced_versatile\",\"mood_indicators\":[\"sophisticated\",\"clean\",\"earthy\"],\"energy_level\":\"moderate\",\"reasoning\":\"Balanced color temperature with moderate saturation creates versatile atmosphere\"},\"color_harmony\":{\"harmony_type\":\"complex_varied\",\"color_distribution\":\"diverse_palette\",\"recommendations\":[\"consider_simplification\"],\"reasoning\":\"Multiple colors (4) may benefit from simplification\"},\"visual_balance\":{\"contrast_balance\":\"low_contrast\",\"tonal_balance\":\"balanced_tones\",\"spatial_balance\":\"proportioned_space\",\"overall_assessment\":\"well_balanced\",\"reasoning\":\"Contrast: 16%, Brightness: 92, Aspect: 0.83 - 2\\/3 elements balanced\"},\"space_perception\":{\"spaciousness\":\"appears_confined\",\"depth_perception\":\"\",\"openness_factor\":\"low\",\"recommendations\":[\"increase_lighting\",\"add_light_colors\"],\"reasoning\":\"Brightness: 92, Light colors: 65%\"},\"style_indicators\":{\"primary_style_lean\":\"modern_minimalist\",\"style_confidence\":60,\"style_scores\":{\"modern_minimalist\":60,\"traditional_classic\":45,\"vintage_eclectic\":35,\"rustic_natural\":30,\"contemporary_bold\":20},\"reasoning\":\"Based on saturation (45%), contrast (16%), and color analysis\"},\"feature_mapping_log\":{\"timestamp\":\"2026-01-14 11:07:46\",\"input_features\":{\"brightness\":92,\"contrast\":16,\"color_temperature\":\"neutral\",\"saturation\":45,\"dominant_colors_count\":4},\"mapping_decisions\":{\"lighting_assessment\":\"moderate_lighting\",\"ambience_character\":\"balanced_versatile\",\"visual_balance\":\"well_balanced\",\"style_indication\":\"modern_minimalist\"},\"confidence_scores\":{\"lighting\":21.9,\"style\":60}}},\"feature_influence\":{\"ai_influence\":{\"impact\":\"high\",\"confidence\":85,\"reasoning\":\"Object detection and spatial analysis enhanced recommendations\"}},\"analysis_method\":\"basic_heuristic_analysis\"},\"ai_enhancements\":{\"detected_objects\":{\"total_objects\":3,\"major_items\":[\"chair\",\"potted_plant\",\"bed\"],\"furniture_categories\":[\"seating\",\"decoration\",\"sleeping\"],\"detection_summary\":\"Objects detected successfully\",\"detection_confidence\":0},\"spatial_analysis\":{\"zones_analyzed\":10,\"spatial_insights\":[{\"insight_type\":\"wall_alignment_opportunity\",\"priority\":\"medium\",\"description\":\"Large furniture pieces could benefit from wall alignment\",\"affected_objects\":[\"bed\"],\"reasoning\":\"Wall-aligned furniture maximizes open floor space and improves traffic flow\",\"suggestion\":\"Consider positioning large furniture against walls when possible\"}],\"spatial_issues\":[{\"issue_type\":\"center_blocking\",\"severity\":\"medium\",\"description\":\"Large furniture appears to be blocking central walking area\",\"affected_objects\":[\"bed\"],\"suggestion\":\"Consider relocating large furniture to wall-aligned positions\"}],\"layout_assessment\":\"Layout improvements recommended\",\"zone_distribution\":{\"left_zone\":1,\"center_zone\":1,\"right_zone\":1,\"top_zone\":0,\"middle_zone\":2,\"bottom_zone\":1,\"wall_aligned\":1,\"center_blocking\":1,\"near_window\":0}},\"enhanced_visual_features\":{\"brightness\":92,\"contrast\":16,\"dominant_colors\":{\"gray\":35,\"white\":30,\"brown\":25,\"blue\":10},\"color_temperature\":{\"category\":\"neutral\",\"score\":0},\"saturation_level\":45,\"image_dimensions\":{\"width\":2000,\"height\":2400},\"aspect_ratio\":0.83,\"brightness_enhanced\":{\"php_brightness\":92,\"cv_brightness\":145.46,\"perceptual_brightness\":388.03,\"brightness_distribution\":{\"dark_ratio\":0.17,\"mid_ratio\":0.464,\"bright_ratio\":0.366},\"lighting_quality\":\"mixed\",\"consensus_category\":\"dim\"},\"contrast_enhanced\":{\"php_contrast\":16,\"cv_std_contrast\":52.38,\"cv_rms_contrast\":52.38,\"local_contrast\":82.64,\"contrast_quality\":\"adequate\",\"consensus_category\":\"high\"},\"texture_analysis\":{\"texture_strength\":29.67,\"texture_std\":67.24,\"texture_variance\":40.72,\"texture_category\":\"textured\",\"surface_character\":\"moderate_texture\"},\"edge_analysis\":{\"edge_density\":0.0417,\"edge_strength\":29.67,\"detail_level\":\"minimal\",\"structural_complexity\":\"moderate\"},\"color_analysis_enhanced\":{\"color_temperature_analysis\":{\"rgb_means\":{\"red\":148.32,\"green\":144.84,\"blue\":141.6},\"temperature_category\":\"neutral\",\"temperature_strength\":9.95,\"warm_indicator\":4.98,\"cool_indicator\":-4.98},\"saturation_analysis\":{\"mean_saturation\":22.31,\"saturation_std\":43.22,\"saturation_category\":\"low\"},\"hue_analysis\":{\"dominant_hues\":[{\"hue_value\":6,\"hue_name\":\"red\",\"percentage\":21.25},{\"hue_value\":0,\"hue_name\":\"red\",\"percentage\":13.88},{\"hue_value\":173,\"hue_name\":\"red\",\"percentage\":6.78},{\"hue_value\":19,\"hue_name\":\"orange\",\"percentage\":6.59},{\"hue_value\":126,\"hue_name\":\"purple\",\"percentage\":6.42}],\"hue_diversity\":5},\"color_harmony\":{\"harmony_type\":\"analogous\",\"harmony_strength\":\"good\"}},\"enhancement_metadata\":{\"enhancement_method\":\"opencv_cv2\",\"original_system\":\"php_gd_analysis\",\"enhancement_timestamp\":\"2026-01-14 15:37:48\",\"features_enhanced\":[\"brightness_analysis\",\"contrast_analysis\",\"color_analysis\",\"texture_analysis\",\"edge_analysis\"]}},\"spatial_guidance\":{\"placement_recommendations\":[{\"object\":\"bed\",\"object_id\":\"obj_2\",\"guidance_type\":\"wall_alignment\",\"priority\":\"medium\",\"recommendation\":\"Consider relocating the bed to align with a wall\",\"reasoning\":\"Wall-aligned large furniture maximizes open floor space and improves traffic flow\",\"confidence\":\"high\",\"safety_note\":\"Ensure adequate clearance for safe movement around furniture\"},{\"object\":\"bed\",\"object_id\":\"obj_2\",\"guidance_type\":\"pathway_clearance\",\"priority\":\"high\",\"recommendation\":\"The bed appears to be blocking central walking areas\",\"reasoning\":\"Clear pathways through the center improve room functionality and safety\",\"confidence\":\"high\",\"safety_note\":\"Maintain clear walking paths to prevent accidents\"},{\"object\":\"bed\",\"object_id\":\"obj_2\",\"guidance_type\":\"bedroom_layout\",\"priority\":\"low\",\"recommendation\":\"Consider bed placement for optimal room flow and access\",\"reasoning\":\"Bed positioning affects room functionality and morning routines\",\"confidence\":\"moderate\",\"safety_note\":\"Ensure easy access to both sides of the bed when possible\"}],\"layout_improvements\":[{\"recommendation_type\":\"sleep_optimization\",\"priority\":\"medium\",\"description\":\"Optimize bedroom layout for rest and relaxation\",\"recommendation\":\"Position bed to minimize disruptions and maximize comfort\",\"reasoning\":\"Bedroom layout significantly affects sleep quality and daily routines\",\"implementation_tips\":[\"Avoid placing bed directly opposite doors\",\"Ensure bedside access\"]}],\"safety_considerations\":[{\"safety_type\":\"pathway_clearance\",\"priority\":\"high\",\"description\":\"Ensure clear pathways through the room\",\"recommendation\":\"Maintain at least 36 inches of clear walking space in main pathways\",\"reasoning\":\"Clear pathways prevent accidents and improve accessibility\",\"affected_areas\":[\"central_walking_area\"]},{\"safety_type\":\"furniture_stability\",\"priority\":\"medium\",\"description\":\"Ensure large furniture is properly secured\",\"recommendation\":\"Consider anchoring tall or heavy furniture to walls for safety\",\"reasoning\":\"Unsecured furniture can pose tipping hazards, especially in homes with children\",\"affected_areas\":[\"furniture_placement\"]},{\"safety_type\":\"lighting_safety\",\"priority\":\"medium\",\"description\":\"Ensure adequate lighting in all areas\",\"recommendation\":\"Verify that furniture placement doesn\'t create dark areas or shadows\",\"reasoning\":\"Well-lit spaces prevent accidents and improve overall safety\",\"affected_areas\":[\"lighting_coverage\"]}],\"improvement_suggestions\":[{\"suggestion_type\":\"center_blocking\",\"priority\":\"medium\",\"description\":\"Large furniture appears to be blocking central walking area\",\"recommendation\":\"Consider relocating large furniture to wall-aligned positions\",\"affected_objects\":[\"bed\"],\"reasoning\":\"Spatial analysis indicates potential improvement opportunity\",\"confidence\":\"moderate\",\"implementation_note\":\"Consider these suggestions as starting points for improvement\"},{\"suggestion_type\":\"wall_alignment_opportunity\",\"priority\":\"medium\",\"description\":\"Large furniture pieces could benefit from wall alignment\",\"recommendation\":\"Consider positioning large furniture against walls when possible\",\"affected_objects\":[\"bed\"],\"reasoning\":\"Wall-aligned furniture maximizes open floor space and improves traffic flow\",\"confidence\":\"moderate\",\"implementation_note\":\"These suggestions may improve room functionality and aesthetics\"}],\"guidance_summary\":\"3 placement recommendations provided\",\"reasoning_metadata\":{\"rule_engine_version\":\"1.0\",\"rules_applied\":8,\"room_type\":\"bedroom\",\"objects_analyzed\":3,\"spatial_zones_analyzed\":9,\"reasoning_approach\":\"deterministic_rule_based\"}},\"conceptual_visualization\":{\"success\":true,\"image_url\":\"\\/buildhub\\/uploads\\/conceptual_images\\/real_ai_bedroom_20260114_154142.png\",\"image_path\":\"uploads\\/conceptual_images\\/real_ai_bedroom_20260114_154142.png\",\"disclaimer\":\"AI-Generated Conceptual Visualization \\/ Inspirational Preview\",\"generation_metadata\":{\"model_id\":\"stable-diffusion-v1-5\",\"generation_type\":\"real_ai_generated\",\"image_size\":\"512x512\",\"file_size\":425327}},\"design_description\":\"\",\"ai_metadata\":{\"pipeline_type\":\"collaborative_ai_hybrid\",\"gemini_api_available\":true,\"diffusion_device\":\"cpu\",\"analysis_timestamp\":\"2026-01-14T15:37:48.679655\",\"room_type\":\"bedroom\",\"stages_completed\":2},\"integration_status\":{\"vision_analysis_enhanced\":true,\"spatial_reasoning_applied\":true,\"gemini_description_generated\":false,\"conceptual_image_generated\":false,\"collaborative_pipeline_status\":\"operational\"},\"async_image_generation\":{\"job_id\":\"1c8dc16e-b499-462f-bd4f-2aea76cb2e33\",\"status\":\"pending\",\"message\":\"Conceptual image generation started\",\"estimated_completion_time\":\"30-60 seconds\"}}}', '2026-01-14 10:07:48', '2026-01-19 09:17:32'),
(48, 28, 'bedroom', '', 'room_28_1768810578_696de85272a79.jpg', '{\"concept_name\":\"Restful Sleep Sanctuary\",\"room_condition_summary\":\"Your bedroom shows potential for creating a more restful and organized sleeping environment.\",\"visual_observations\":[\"Lighting condition: moderate_lighting (confidence: 28.2%)\",\"Dominant colors: gray, white, brown, blue\",\"Color temperature: warm bias (%)\",\"Contrast level: 23%\",\"Saturation level: 27%\"],\"improvement_suggestions\":{\"lighting\":\"Consider adding layered lighting with bedside lamps for reading and dimmable overhead lighting for ambiance. AI-detected objects suggest: Add bedside reading lights and consider dimmable overhead lighting for relaxation.\",\"color_ambience\":\"Your room currently has a warm color palette (% warm bias). This creates a naturally cozy atmosphere. Soft, calming colors like muted blues, gentle greens, or warm neutrals can promote better sleep.\",\"furniture_layout\":\"Ensure your bed is the focal point, with adequate space for movement and storage solutions for organization.\"},\"style_recommendation\":{\"style\":\"Contemporary Comfort\",\"description\":\"A blend of modern functionality with cozy, personal touches that promote relaxation.\",\"key_elements\":[\"Comfortable bedding\",\"Adequate storage\",\"Soft lighting\",\"Calming colors\"],\"confidence\":75},\"visual_reference\":\"Imagine a serene retreat with soft textures, organized storage, and gentle lighting that creates a peaceful atmosphere for rest and rejuvenation.\",\"analysis_metadata\":{\"room_type\":\"bedroom\",\"user_notes\":\"\",\"image_dimensions\":\"735x441\",\"analysis_timestamp\":\"2026-01-19 09:16:20\",\"system_type\":\"hybrid_ai_basic_rules\",\"ai_enhancement_enabled\":true},\"visual_intelligence\":{\"extracted_features\":{\"brightness\":85,\"contrast\":23,\"dominant_colors\":{\"gray\":35,\"white\":30,\"brown\":25,\"blue\":10},\"color_temperature\":{\"category\":\"warm\",\"score\":69},\"saturation_level\":27,\"image_dimensions\":{\"width\":735,\"height\":441},\"aspect_ratio\":1.67},\"design_attributes\":{\"lighting_condition\":{\"primary_assessment\":\"moderate_lighting\",\"secondary_notes\":[\"soft_even_lighting\"],\"confidence\":28.2,\"reasoning\":\"Moderate brightness level (85\\/100) indicates balanced lighting\"},\"ambience_character\":{\"primary_character\":\"balanced_versatile\",\"mood_indicators\":[\"sophisticated\",\"clean\",\"earthy\"],\"energy_level\":\"moderate\",\"reasoning\":\"Balanced color temperature with moderate saturation creates versatile atmosphere\"},\"color_harmony\":{\"harmony_type\":\"complex_varied\",\"color_distribution\":\"diverse_palette\",\"recommendations\":[\"consider_simplification\"],\"reasoning\":\"Multiple colors (4) may benefit from simplification\"},\"visual_balance\":{\"contrast_balance\":\"balanced_contrast\",\"tonal_balance\":\"balanced_tones\",\"spatial_balance\":\"elongated_space\",\"overall_assessment\":\"well_balanced\",\"reasoning\":\"Contrast: 23%, Brightness: 85, Aspect: 1.67 - 2\\/3 elements balanced\"},\"space_perception\":{\"spaciousness\":\"appears_confined\",\"depth_perception\":\"\",\"openness_factor\":\"low\",\"recommendations\":[\"increase_lighting\",\"add_light_colors\"],\"reasoning\":\"Brightness: 85, Light colors: 65%\"},\"style_indicators\":{\"primary_style_lean\":\"modern_minimalist\",\"style_confidence\":80,\"style_scores\":{\"modern_minimalist\":80,\"traditional_classic\":65,\"rustic_natural\":45,\"contemporary_bold\":20,\"vintage_eclectic\":20},\"reasoning\":\"Based on saturation (27%), contrast (23%), and color analysis\"},\"feature_mapping_log\":{\"timestamp\":\"2026-01-19 09:16:18\",\"input_features\":{\"brightness\":85,\"contrast\":23,\"color_temperature\":\"warm\",\"saturation\":27,\"dominant_colors_count\":4},\"mapping_decisions\":{\"lighting_assessment\":\"moderate_lighting\",\"ambience_character\":\"balanced_versatile\",\"visual_balance\":\"well_balanced\",\"style_indication\":\"modern_minimalist\"},\"confidence_scores\":{\"lighting\":28.2,\"style\":80}}},\"feature_influence\":{\"ai_influence\":{\"impact\":\"high\",\"confidence\":85,\"reasoning\":\"Object detection and spatial analysis enhanced recommendations\"}},\"analysis_method\":\"basic_heuristic_analysis\"},\"ai_enhancements\":{\"detected_objects\":{\"total_objects\":1,\"major_items\":[\"bed\"],\"furniture_categories\":[\"sleeping\"],\"detection_summary\":\"Objects detected successfully\",\"detection_confidence\":0},\"spatial_analysis\":{\"zones_analyzed\":10,\"spatial_insights\":[{\"insight_type\":\"wall_alignment_opportunity\",\"priority\":\"medium\",\"description\":\"Large furniture pieces could benefit from wall alignment\",\"affected_objects\":[\"bed\"],\"reasoning\":\"Wall-aligned furniture maximizes open floor space and improves traffic flow\",\"suggestion\":\"Consider positioning large furniture against walls when possible\"}],\"spatial_issues\":[],\"layout_assessment\":\"Layout appears well-organized\",\"zone_distribution\":{\"left_zone\":0,\"center_zone\":1,\"right_zone\":0,\"top_zone\":0,\"middle_zone\":0,\"bottom_zone\":1,\"wall_aligned\":0,\"center_blocking\":0,\"near_window\":0}},\"enhanced_visual_features\":{\"brightness\":85,\"contrast\":23,\"dominant_colors\":{\"gray\":35,\"white\":30,\"brown\":25,\"blue\":10},\"color_temperature\":{\"category\":\"warm\",\"score\":69},\"saturation_level\":27,\"image_dimensions\":{\"width\":735,\"height\":441},\"aspect_ratio\":1.67,\"brightness_enhanced\":{\"php_brightness\":85,\"cv_brightness\":178.5,\"perceptual_brightness\":469.64,\"brightness_distribution\":{\"dark_ratio\":0.039,\"mid_ratio\":0.317,\"bright_ratio\":0.643},\"lighting_quality\":\"overexposed\",\"consensus_category\":\"moderate\"},\"contrast_enhanced\":{\"php_contrast\":23,\"cv_std_contrast\":42.54,\"cv_rms_contrast\":42.54,\"local_contrast\":1995.52,\"contrast_quality\":\"excellent\",\"consensus_category\":\"high\"},\"texture_analysis\":{\"texture_strength\":63.12,\"texture_std\":105.33,\"texture_variance\":260.61,\"texture_category\":\"highly_textured\",\"surface_character\":\"rough_varied\"},\"edge_analysis\":{\"edge_density\":0.112,\"edge_strength\":63.12,\"detail_level\":\"moderate\",\"structural_complexity\":\"moderate\"},\"color_analysis_enhanced\":{\"color_temperature_analysis\":{\"rgb_means\":{\"red\":192.9,\"green\":174.63,\"blue\":160.69},\"temperature_category\":\"warm\",\"temperature_strength\":46.15,\"warm_indicator\":23.08,\"cool_indicator\":-23.08},\"saturation_analysis\":{\"mean_saturation\":47.41,\"saturation_std\":34.21,\"saturation_category\":\"low\"},\"hue_analysis\":{\"dominant_hues\":[{\"hue_value\":13,\"hue_name\":\"orange\",\"percentage\":20.89},{\"hue_value\":11,\"hue_name\":\"orange\",\"percentage\":14.84},{\"hue_value\":12,\"hue_name\":\"orange\",\"percentage\":12.8},{\"hue_value\":8,\"hue_name\":\"red\",\"percentage\":9.46},{\"hue_value\":15,\"hue_name\":\"orange\",\"percentage\":8.09}],\"hue_diversity\":5},\"color_harmony\":{\"harmony_type\":\"analogous\",\"harmony_strength\":\"good\"}},\"enhancement_metadata\":{\"enhancement_method\":\"opencv_cv2\",\"original_system\":\"php_gd_analysis\",\"enhancement_timestamp\":\"2026-01-19 13:46:20\",\"features_enhanced\":[\"brightness_analysis\",\"contrast_analysis\",\"color_analysis\",\"texture_analysis\",\"edge_analysis\"]}},\"spatial_guidance\":{\"placement_recommendations\":[{\"object\":\"bed\",\"object_id\":\"obj_0\",\"guidance_type\":\"wall_alignment\",\"priority\":\"medium\",\"recommendation\":\"Consider relocating the bed to align with a wall\",\"reasoning\":\"Wall-aligned large furniture maximizes open floor space and improves traffic flow\",\"confidence\":\"high\",\"safety_note\":\"Ensure adequate clearance for safe movement around furniture\"}],\"layout_improvements\":[{\"recommendation_type\":\"sleep_optimization\",\"priority\":\"medium\",\"description\":\"Optimize bedroom layout for rest and relaxation\",\"recommendation\":\"Position bed to minimize disruptions and maximize comfort\",\"reasoning\":\"Bedroom layout significantly affects sleep quality and daily routines\",\"implementation_tips\":[\"Avoid placing bed directly opposite doors\",\"Ensure bedside access\"]}],\"safety_considerations\":[{\"safety_type\":\"furniture_stability\",\"priority\":\"medium\",\"description\":\"Ensure large furniture is properly secured\",\"recommendation\":\"Consider anchoring tall or heavy furniture to walls for safety\",\"reasoning\":\"Unsecured furniture can pose tipping hazards, especially in homes with children\",\"affected_areas\":[\"furniture_placement\"]},{\"safety_type\":\"lighting_safety\",\"priority\":\"medium\",\"description\":\"Ensure adequate lighting in all areas\",\"recommendation\":\"Verify that furniture placement doesn\'t create dark areas or shadows\",\"reasoning\":\"Well-lit spaces prevent accidents and improve overall safety\",\"affected_areas\":[\"lighting_coverage\"]}],\"improvement_suggestions\":[{\"suggestion_type\":\"wall_alignment_opportunity\",\"priority\":\"medium\",\"description\":\"Large furniture pieces could benefit from wall alignment\",\"recommendation\":\"Consider positioning large furniture against walls when possible\",\"affected_objects\":[\"bed\"],\"reasoning\":\"Wall-aligned furniture maximizes open floor space and improves traffic flow\",\"confidence\":\"moderate\",\"implementation_note\":\"These suggestions may improve room functionality and aesthetics\"}],\"guidance_summary\":\"1 placement recommendations provided\",\"reasoning_metadata\":{\"rule_engine_version\":\"1.0\",\"rules_applied\":8,\"room_type\":\"bedroom\",\"objects_analyzed\":1,\"spatial_zones_analyzed\":9,\"reasoning_approach\":\"deterministic_rule_based\"}},\"conceptual_visualization\":{\"success\":true,\"image_url\":\"\\/buildhub\\/uploads\\/conceptual_images\\/real_ai_bedroom_20260119_135516.png\",\"image_path\":\"uploads\\/conceptual_images\\/real_ai_bedroom_20260119_135516.png\",\"disclaimer\":\"AI-Generated Conceptual Visualization \\/ Inspirational Preview\",\"generation_metadata\":{\"model_id\":\"stable-diffusion-v1-5\",\"generation_type\":\"real_ai_generated\",\"image_size\":\"512x512\",\"file_size\":400650}},\"design_description\":\"\",\"ai_metadata\":{\"pipeline_type\":\"collaborative_ai_hybrid\",\"gemini_api_available\":true,\"diffusion_device\":\"cpu\",\"analysis_timestamp\":\"2026-01-19T13:46:20.779605\",\"room_type\":\"bedroom\",\"stages_completed\":2},\"integration_status\":{\"vision_analysis_enhanced\":true,\"spatial_reasoning_applied\":true,\"gemini_description_generated\":false,\"conceptual_image_generated\":false,\"collaborative_pipeline_status\":\"operational\"},\"async_image_generation\":{\"job_id\":\"6abbe228-5d72-4978-95c3-2a394ad93f4c\",\"status\":\"pending\",\"message\":\"Conceptual image generation started\",\"estimated_completion_time\":\"30-60 seconds\"}}}', '2026-01-19 08:16:20', '2026-01-19 09:17:32');

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
-- Table structure for table `split_payment_audit_log`
--

CREATE TABLE `split_payment_audit_log` (
  `id` int(11) NOT NULL,
  `split_group_id` int(11) NOT NULL,
  `split_transaction_id` int(11) DEFAULT NULL,
  `action` enum('created','payment_initiated','payment_completed','payment_failed','cancelled','refunded') NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('homeowner','contractor','admin','system') DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `split_payment_groups`
--

CREATE TABLE `split_payment_groups` (
  `id` int(11) NOT NULL,
  `payment_type` enum('technical_details','stage_payment') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'INR',
  `country_code` varchar(2) DEFAULT 'IN',
  `total_splits` int(11) NOT NULL,
  `completed_splits` int(11) DEFAULT 0,
  `completed_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','partial','completed','failed','cancelled') DEFAULT 'pending',
  `description` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `split_payment_groups`
--

INSERT INTO `split_payment_groups` (`id`, `payment_type`, `reference_id`, `homeowner_id`, `contractor_id`, `total_amount`, `currency`, `country_code`, `total_splits`, `completed_splits`, `completed_amount`, `status`, `description`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 'stage_payment', 1, 1, 2, 1000000.00, 'INR', 'IN', 2, 0, 0.00, 'pending', 'Split payment for ₹10 lakh stage payment', NULL, '2026-01-11 15:37:54', '2026-01-11 15:37:54'),
(2, 'technical_details', 1, 1, NULL, 800000.00, 'INR', 'IN', 2, 0, 0.00, 'pending', 'Split payment for ₹8 lakh technical details unlock', NULL, '2026-01-11 15:37:54', '2026-01-11 15:37:54');

-- --------------------------------------------------------

--
-- Table structure for table `split_payment_notifications`
--

CREATE TABLE `split_payment_notifications` (
  `id` int(11) NOT NULL,
  `split_group_id` int(11) NOT NULL,
  `split_transaction_id` int(11) DEFAULT NULL,
  `recipient_id` int(11) NOT NULL,
  `recipient_type` enum('homeowner','contractor','admin') NOT NULL,
  `notification_type` enum('split_created','payment_completed','payment_failed','all_completed','payment_cancelled') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `split_payment_summary`
-- (See below for the actual view)
--
CREATE TABLE `split_payment_summary` (
`group_id` int(11)
,`payment_type` enum('technical_details','stage_payment')
,`reference_id` int(11)
,`homeowner_id` int(11)
,`contractor_id` int(11)
,`total_amount` decimal(15,2)
,`currency` varchar(3)
,`total_splits` int(11)
,`completed_splits` int(11)
,`completed_amount` decimal(15,2)
,`group_status` enum('pending','partial','completed','failed','cancelled')
,`description` text
,`group_created_at` timestamp
,`splits_progress` decimal(15,1)
,`amount_progress` decimal(20,1)
,`remaining_amount` decimal(16,2)
,`remaining_splits` bigint(12)
,`total_transactions` bigint(21)
,`completed_transactions` decimal(22,0)
,`failed_transactions` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `split_payment_transactions`
--

CREATE TABLE `split_payment_transactions` (
  `id` int(11) NOT NULL,
  `split_group_id` int(11) NOT NULL,
  `sequence_number` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'INR',
  `razorpay_order_id` varchar(255) DEFAULT NULL,
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `payment_status` enum('created','pending','completed','failed','cancelled') DEFAULT 'created',
  `failure_reason` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stage_payment_notifications`
--

CREATE TABLE `stage_payment_notifications` (
  `id` int(11) NOT NULL,
  `payment_request_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `recipient_type` enum('homeowner','contractor','admin') NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stage_payment_notifications`
--

INSERT INTO `stage_payment_notifications` (`id`, `payment_request_id`, `recipient_id`, `recipient_type`, `notification_type`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 13, 29, 'contractor', 'verification_required', 'Payment Receipt Uploaded - Verification Required', 'Homeowner has uploaded payment receipt for ₹50,000.00 payment. Please verify the payment details and mark as completed.', 0, '2026-01-11 17:17:06'),
(2, 13, 28, 'homeowner', 'payment_initiated', 'Receipt Uploaded Successfully', 'Your payment receipt has been uploaded successfully. The contractor will verify your payment within 1-2 business days.', 0, '2026-01-11 17:17:06'),
(3, 15, 29, 'contractor', 'verification_required', 'Payment Receipt Uploaded - Verification Required', 'Homeowner has uploaded payment receipt for ₹213,949.00 payment. Please verify the payment details and mark as completed.', 0, '2026-01-20 10:00:30'),
(4, 15, 28, 'homeowner', 'payment_initiated', 'Receipt Uploaded Successfully', 'Your payment receipt has been uploaded successfully. The contractor will verify your payment within 1-2 business days.', 0, '2026-01-20 10:00:30'),
(5, 15, 29, 'contractor', 'verification_required', 'Payment Receipt Uploaded - Verification Required', 'Homeowner has uploaded payment receipt for ₹213,949.00 payment. Please verify the payment details and mark as completed.', 0, '2026-01-20 10:07:50'),
(6, 15, 28, 'homeowner', 'payment_initiated', 'Receipt Uploaded Successfully', 'Your payment receipt has been uploaded successfully. The contractor will verify your payment within 1-2 business days.', 0, '2026-01-20 10:07:50');

-- --------------------------------------------------------

--
-- Table structure for table `stage_payment_requests`
--

CREATE TABLE `stage_payment_requests` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `homeowner_id` int(11) DEFAULT NULL,
  `stage_name` varchar(100) NOT NULL,
  `requested_amount` decimal(12,2) NOT NULL,
  `completion_percentage` decimal(5,2) NOT NULL,
  `work_description` text NOT NULL,
  `materials_used` text DEFAULT NULL,
  `labor_count` int(11) NOT NULL,
  `work_start_date` date DEFAULT NULL,
  `work_end_date` date DEFAULT NULL,
  `contractor_notes` text DEFAULT NULL,
  `quality_check` tinyint(1) DEFAULT 0,
  `safety_compliance` tinyint(1) DEFAULT 0,
  `total_project_cost` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','approved','rejected','paid') DEFAULT 'pending',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `response_date` timestamp NULL DEFAULT NULL,
  `homeowner_notes` text DEFAULT NULL,
  `approved_amount` decimal(12,2) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `transaction_reference` varchar(255) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `receipt_file_path` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected') DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verification_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stage_payment_requests`
--

INSERT INTO `stage_payment_requests` (`id`, `project_id`, `contractor_id`, `homeowner_id`, `stage_name`, `requested_amount`, `completion_percentage`, `work_description`, `materials_used`, `labor_count`, `work_start_date`, `work_end_date`, `contractor_notes`, `quality_check`, `safety_compliance`, `total_project_cost`, `status`, `request_date`, `response_date`, `homeowner_notes`, `approved_amount`, `rejection_reason`, `created_at`, `updated_at`, `transaction_reference`, `payment_date`, `receipt_file_path`, `payment_method`, `verification_status`, `verified_by`, `verified_at`, `verification_notes`) VALUES
(15, 37, 29, 28, 'Foundation', 213949.00, 20.00, 'amount for foundation plaese give it fastely', '', 8, '2026-01-21', '2026-01-27', '', 1, 1, 1069745.00, 'approved', '2026-01-20 07:31:15', '2026-01-20 07:42:28', '\n\nReceipt Upload Notes: \n\nReceipt Upload Notes: ', NULL, NULL, '2026-01-20 07:31:15', '2026-01-20 10:07:50', '1222222222222222222222222222222222', '2026-01-20', '[{\"original_name\":\"682b722c0cd605f66e0aa48b85a4a7a0.jpg\",\"stored_name\":\"receipt_1768903670_0.jpg\",\"file_path\":\"uploads\\/payment_receipts\\/15\\/receipt_1768903670_0.jpg\",\"file_size\":58252,\"file_type\":\"image\\/jpeg\"}]', 'bank_transfer', 'pending', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `custom_payment_requests`
--

CREATE TABLE `custom_payment_requests` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `request_title` varchar(255) NOT NULL,
  `request_reason` text NOT NULL,
  `requested_amount` decimal(12,2) NOT NULL,
  `work_description` text NOT NULL,
  `urgency_level` enum('low','medium','high','urgent') DEFAULT 'medium',
  `category` varchar(100) DEFAULT NULL,
  `supporting_documents` text DEFAULT NULL,
  `contractor_notes` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','paid') DEFAULT 'pending',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `response_date` timestamp NULL DEFAULT NULL,
  `homeowner_notes` text DEFAULT NULL,
  `approved_amount` decimal(12,2) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `receipt_file_path` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected') DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verification_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stage_payment_transactions`
--

CREATE TABLE `stage_payment_transactions` (
  `id` int(11) NOT NULL,
  `payment_request_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'INR',
  `country_code` varchar(2) DEFAULT 'IN',
  `original_amount` decimal(15,2) DEFAULT NULL,
  `original_currency` varchar(3) DEFAULT NULL,
  `exchange_rate` decimal(10,6) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `international_payment` tinyint(1) DEFAULT 0,
  `razorpay_order_id` varchar(255) DEFAULT NULL,
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `payment_status` enum('created','pending','completed','failed','cancelled') DEFAULT 'created',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stage_payment_transactions`
--

INSERT INTO `stage_payment_transactions` (`id`, `payment_request_id`, `homeowner_id`, `contractor_id`, `amount`, `currency`, `country_code`, `original_amount`, `original_currency`, `exchange_rate`, `payment_method`, `international_payment`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `payment_status`, `created_at`, `updated_at`) VALUES
(1, 1, 28, 29, 100000.00, 'INR', 'IN', NULL, NULL, NULL, NULL, 0, 'order_S2aZ5HOgHHnN0P', NULL, NULL, 'created', '2026-01-11 13:31:13', '2026-01-11 13:31:13'),
(2, 13, 28, 29, 50000.00, 'INR', 'IN', NULL, NULL, NULL, NULL, 0, 'order_S2d0F19Ce4Ztew', NULL, NULL, 'created', '2026-01-11 15:54:19', '2026-01-11 15:54:19'),
(3, 14, 28, 29, 250.00, 'INR', 'IN', NULL, NULL, NULL, NULL, 0, 'order_S3IrWKXRV403r4', 'pay_S3IrgoUzLJL7Gm', NULL, 'completed', '2026-01-13 08:51:12', '2026-01-14 17:34:02');

-- --------------------------------------------------------

--
-- Table structure for table `stage_payment_verification_logs`
--

CREATE TABLE `stage_payment_verification_logs` (
  `id` int(11) NOT NULL,
  `payment_request_id` int(11) NOT NULL,
  `verifier_id` int(11) NOT NULL,
  `verifier_type` enum('homeowner','contractor','admin') NOT NULL,
  `action` enum('submitted','verified','rejected','updated') NOT NULL,
  `comments` text DEFAULT NULL,
  `attached_files` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attached_files`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stage_payment_verification_logs`
--

INSERT INTO `stage_payment_verification_logs` (`id`, `payment_request_id`, `verifier_id`, `verifier_type`, `action`, `comments`, `attached_files`, `created_at`) VALUES
(1, 13, 28, 'homeowner', 'submitted', 'Receipt uploaded with transaction reference: 122344444444444444444', '[{\"original_name\":\"SHIJIN_THOMAS_MCA2024_2026_House_Plan_layout.png\",\"stored_name\":\"receipt_1768151826_0.png\",\"file_path\":\"uploads\\/payment_receipts\\/13\\/receipt_1768151826_0.png\",\"file_size\":51523,\"file_type\":\"image\\/png\"}]', '2026-01-11 17:17:06'),
(2, 15, 28, 'homeowner', 'submitted', 'Receipt uploaded with transaction reference: 122344444444444444444', '[{\"original_name\":\"682b722c0cd605f66e0aa48b85a4a7a0.jpg\",\"stored_name\":\"receipt_1768903230_0.jpg\",\"file_path\":\"uploads\\/payment_receipts\\/15\\/receipt_1768903230_0.jpg\",\"file_size\":58252,\"file_type\":\"image\\/jpeg\"}]', '2026-01-20 10:00:30'),
(3, 15, 28, 'homeowner', 'submitted', 'Receipt uploaded with transaction reference: 1222222222222222222222222222222222', '[{\"original_name\":\"682b722c0cd605f66e0aa48b85a4a7a0.jpg\",\"stored_name\":\"receipt_1768903670_0.jpg\",\"file_path\":\"uploads\\/payment_receipts\\/15\\/receipt_1768903670_0.jpg\",\"file_size\":58252,\"file_type\":\"image\\/jpeg\"}]', '2026-01-20 10:07:50');

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
-- Table structure for table `technical_details_payments`
--

CREATE TABLE `technical_details_payments` (
  `id` int(11) NOT NULL,
  `house_plan_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'INR',
  `country_code` varchar(2) DEFAULT 'IN',
  `original_amount` decimal(15,2) DEFAULT NULL,
  `original_currency` varchar(3) DEFAULT NULL,
  `exchange_rate` decimal(10,6) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `razorpay_order_id` varchar(255) DEFAULT NULL,
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `international_payment` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `technical_details_payments`
--

INSERT INTO `technical_details_payments` (`id`, `house_plan_id`, `homeowner_id`, `amount`, `currency`, `country_code`, `original_amount`, `original_currency`, `exchange_rate`, `payment_status`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `payment_method`, `international_payment`, `created_at`, `updated_at`) VALUES
(12, 82, 35, 8000.00, 'INR', 'IN', NULL, NULL, NULL, 'completed', 'order_S6DIkByRCyQ6sg', 'pay_S6DIv43ruqeXC5', 'ea9e5f3f6193362f2a15ad6924b896195896b0f27ba9265a4eabb2ef700b79f7', 'razorpay', 0, '2026-01-20 17:21:48', '2026-01-20 17:22:14');

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
(53, 'Priya', 'Patel', NULL, NULL, 'priya.contractor@email.com', '$2y$10$7a4WZwfX9gPbXsC3nc2mWOWE/HX6tEPDbEbEcl9oQ.9Nby7Zqb4W.', 'contractor', 'pending', 0, NULL, NULL, '2025-12-21 08:07:06', '2025-12-21 08:07:06', NULL, NULL, NULL, NULL, NULL, NULL, '9876543222', NULL, NULL, NULL, NULL, NULL),
(999, 'Test', 'User', NULL, NULL, 'test@example.com', '$2y$10$/JfAQOrNyytwg6AGfFFpcuR9gij6lKkqARhC.v4rZb0A/Il3BOJ1C', 'homeowner', 'pending', 0, NULL, NULL, '2026-01-13 14:51:27', '2026-01-13 14:51:27', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

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
-- Table structure for table `weekly_progress_summaries`
--

CREATE TABLE `weekly_progress_summaries` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `week_start_date` date NOT NULL,
  `week_end_date` date NOT NULL,
  `stages_worked` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`stages_worked`)),
  `delays_and_reasons` text DEFAULT NULL,
  `weekly_remarks` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Structure for view `active_split_payments`
--
DROP TABLE IF EXISTS `active_split_payments`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `active_split_payments`  AS SELECT `spg`.`id` AS `id`, `spg`.`payment_type` AS `payment_type`, `spg`.`reference_id` AS `reference_id`, `spg`.`homeowner_id` AS `homeowner_id`, `spg`.`contractor_id` AS `contractor_id`, `spg`.`total_amount` AS `total_amount`, `spg`.`currency` AS `currency`, `spg`.`country_code` AS `country_code`, `spg`.`total_splits` AS `total_splits`, `spg`.`completed_splits` AS `completed_splits`, `spg`.`completed_amount` AS `completed_amount`, `spg`.`status` AS `status`, `spg`.`description` AS `description`, `spg`.`metadata` AS `metadata`, `spg`.`created_at` AS `created_at`, `spg`.`updated_at` AS `updated_at`, coalesce(`u_homeowner`.`first_name`,'Unknown') AS `homeowner_first_name`, coalesce(`u_homeowner`.`last_name`,'User') AS `homeowner_last_name`, coalesce(`u_homeowner`.`email`,'unknown@example.com') AS `homeowner_email`, coalesce(`u_contractor`.`first_name`,'Unknown') AS `contractor_first_name`, coalesce(`u_contractor`.`last_name`,'Contractor') AS `contractor_last_name`, coalesce(`u_contractor`.`email`,'contractor@example.com') AS `contractor_email` FROM ((`split_payment_groups` `spg` left join `users` `u_homeowner` on(`spg`.`homeowner_id` = `u_homeowner`.`id`)) left join `users` `u_contractor` on(`spg`.`contractor_id` = `u_contractor`.`id`)) WHERE `spg`.`status` in ('pending','partial') ORDER BY `spg`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `architect_request_details`
--
DROP TABLE IF EXISTS `architect_request_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `architect_request_details`  AS SELECT `lr`.`id` AS `id`, `lr`.`user_id` AS `user_id`, `lr`.`homeowner_id` AS `homeowner_id`, `lr`.`plot_size` AS `plot_size`, `lr`.`budget_range` AS `budget_range`, `lr`.`location` AS `location`, `lr`.`timeline` AS `timeline`, `lr`.`num_floors` AS `num_floors`, `lr`.`preferred_style` AS `preferred_style`, `lr`.`orientation` AS `orientation`, `lr`.`site_considerations` AS `site_considerations`, `lr`.`material_preferences` AS `material_preferences`, `lr`.`budget_allocation` AS `budget_allocation`, `lr`.`site_images` AS `site_images`, `lr`.`reference_images` AS `reference_images`, `lr`.`room_images` AS `room_images`, `lr`.`floor_rooms` AS `floor_rooms`, `lr`.`requirements` AS `requirements`, `lr`.`status` AS `status`, `lr`.`layout_type` AS `layout_type`, `lr`.`selected_layout_id` AS `selected_layout_id`, `lr`.`layout_file` AS `layout_file`, `lr`.`created_at` AS `created_at`, `lr`.`updated_at` AS `updated_at`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, `u`.`email` AS `email`, `u`.`phone` AS `phone`, `u`.`address` AS `address`, `u`.`city` AS `city`, `u`.`state` AS `state` FROM (`layout_requests` `lr` join `users` `u` on(`lr`.`homeowner_id` = `u`.`id`)) WHERE `lr`.`status` in ('pending','approved','active') ORDER BY `lr`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `split_payment_summary`
--
DROP TABLE IF EXISTS `split_payment_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `split_payment_summary`  AS SELECT `spg`.`id` AS `group_id`, `spg`.`payment_type` AS `payment_type`, `spg`.`reference_id` AS `reference_id`, `spg`.`homeowner_id` AS `homeowner_id`, `spg`.`contractor_id` AS `contractor_id`, `spg`.`total_amount` AS `total_amount`, `spg`.`currency` AS `currency`, `spg`.`total_splits` AS `total_splits`, `spg`.`completed_splits` AS `completed_splits`, `spg`.`completed_amount` AS `completed_amount`, `spg`.`status` AS `group_status`, `spg`.`description` AS `description`, `spg`.`created_at` AS `group_created_at`, round(`spg`.`completed_splits` / `spg`.`total_splits` * 100,1) AS `splits_progress`, round(`spg`.`completed_amount` / `spg`.`total_amount` * 100,1) AS `amount_progress`, `spg`.`total_amount`- `spg`.`completed_amount` AS `remaining_amount`, `spg`.`total_splits`- `spg`.`completed_splits` AS `remaining_splits`, count(`spt`.`id`) AS `total_transactions`, sum(case when `spt`.`payment_status` = 'completed' then 1 else 0 end) AS `completed_transactions`, sum(case when `spt`.`payment_status` = 'failed' then 1 else 0 end) AS `failed_transactions` FROM (`split_payment_groups` `spg` left join `split_payment_transactions` `spt` on(`spg`.`id` = `spt`.`split_group_id`)) GROUP BY `spg`.`id` ;

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
-- Indexes for table `alternative_payments`
--
ALTER TABLE `alternative_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_type` (`payment_type`),
  ADD KEY `idx_reference_id` (`reference_id`),
  ADD KEY `idx_homeowner_id` (`homeowner_id`),
  ADD KEY `idx_contractor_id` (`contractor_id`),
  ADD KEY `idx_payment_method` (`payment_method`),
  ADD KEY `idx_payment_status` (`payment_status`);

--
-- Indexes for table `alternative_payment_notifications`
--
ALTER TABLE `alternative_payment_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_id` (`payment_id`),
  ADD KEY `idx_recipient` (`recipient_id`,`recipient_type`);

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
-- Indexes for table `concept_previews`
--
ALTER TABLE `concept_previews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `house_plan_id` (`house_plan_id`),
  ADD KEY `idx_layout_request` (`layout_request_id`),
  ADD KEY `idx_architect` (`architect_id`),
  ADD KEY `idx_job_id` (`job_id`),
  ADD KEY `idx_status` (`status`);

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
-- Indexes for table `construction_projects`
--
ALTER TABLE `construction_projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `estimate_id` (`estimate_id`),
  ADD KEY `idx_contractor_id` (`contractor_id`),
  ADD KEY `idx_homeowner_id` (`homeowner_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_estimate_id` (`estimate_id`);

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
-- Indexes for table `contractor_bank_details`
--
ALTER TABLE `contractor_bank_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_contractor` (`contractor_id`),
  ADD KEY `idx_contractor_id` (`contractor_id`);

--
-- Indexes for table `contractor_engagements`
--
ALTER TABLE `contractor_engagements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_homeowner` (`homeowner_id`),
  ADD KEY `idx_contractor` (`contractor_id`),
  ADD KEY `idx_layout_request` (`layout_request_id`),
  ADD KEY `idx_house_plan` (`house_plan_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `contractor_estimates`
--
ALTER TABLE `contractor_estimates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contractor` (`contractor_id`),
  ADD KEY `idx_homeowner` (`homeowner_id`),
  ADD KEY `idx_send` (`send_id`);

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
-- Indexes for table `currency_exchange_rates`
--
ALTER TABLE `currency_exchange_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_currency_pair_date` (`from_currency`,`to_currency`,`rate_date`),
  ADD KEY `idx_from_currency` (`from_currency`),
  ADD KEY `idx_to_currency` (`to_currency`),
  ADD KEY `idx_rate_date` (`rate_date`);

--
-- Indexes for table `daily_labour_tracking`
--
ALTER TABLE `daily_labour_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_progress_id` (`daily_progress_id`),
  ADD KEY `idx_worker_type` (`worker_type`),
  ADD KEY `idx_productivity` (`productivity_rating`);

--
-- Indexes for table `daily_progress_updates`
--
ALTER TABLE `daily_progress_updates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_date` (`project_id`,`contractor_id`,`update_date`),
  ADD KEY `idx_project_date` (`project_id`,`update_date`),
  ADD KEY `idx_contractor` (`contractor_id`),
  ADD KEY `idx_homeowner` (`homeowner_id`),
  ADD KEY `idx_stage` (`construction_stage`),
  ADD KEY `idx_date` (`update_date`),
  ADD KEY `idx_location` (`latitude`,`longitude`);

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
-- Indexes for table `enhanced_progress_notifications`
--
ALTER TABLE `enhanced_progress_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_homeowner_status` (`homeowner_id`,`status`),
  ADD KEY `idx_contractor_type` (`contractor_id`,`notification_type`),
  ADD KEY `idx_project_notifications` (`project_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_notifications_photos` (`has_photos`,`geo_photos_count`);

--
-- Indexes for table `estimate_drafts`
--
ALTER TABLE `estimate_drafts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_draft` (`contractor_id`,`send_id`);

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
  ADD KEY `idx_status` (`status`),
  ADD KEY `concept_preview_id` (`concept_preview_id`);

--
-- Indexes for table `house_plan_reviews`
--
ALTER TABLE `house_plan_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `homeowner_id` (`homeowner_id`),
  ADD KEY `idx_plan_homeowner` (`house_plan_id`,`homeowner_id`);

--
-- Indexes for table `inbox_messages`
--
ALTER TABLE `inbox_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipient_id` (`recipient_id`),
  ADD KEY `idx_sender_id` (`sender_id`),
  ADD KEY `idx_message_type` (`message_type`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_priority` (`priority`);

--
-- Indexes for table `international_payment_settings`
--
ALTER TABLE `international_payment_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_country` (`country_code`),
  ADD KEY `idx_currency` (`currency_code`),
  ADD KEY `idx_supported` (`is_supported`);

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
-- Indexes for table `monthly_progress_reports`
--
ALTER TABLE `monthly_progress_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_month` (`project_id`,`contractor_id`,`report_year`,`report_month`),
  ADD KEY `idx_project_month` (`project_id`,`report_year`,`report_month`),
  ADD KEY `idx_contractor` (`contractor_id`),
  ADD KEY `idx_homeowner` (`homeowner_id`);

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
-- Indexes for table `payment_failure_logs`
--
ALTER TABLE `payment_failure_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_type` (`payment_type`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_error_code` (`error_code`),
  ADD KEY `idx_country_code` (`country_code`),
  ADD KEY `idx_created_at` (`created_at`);

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
-- Indexes for table `room_improvement_analyses`
--
ALTER TABLE `room_improvement_analyses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_homeowner_id` (`homeowner_id`),
  ADD KEY `idx_room_type` (`room_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `room_templates`
--
ALTER TABLE `room_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `split_payment_audit_log`
--
ALTER TABLE `split_payment_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_split_group_id` (`split_group_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `split_payment_groups`
--
ALTER TABLE `split_payment_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_type` (`payment_type`),
  ADD KEY `idx_reference_id` (`reference_id`),
  ADD KEY `idx_homeowner_id` (`homeowner_id`),
  ADD KEY `idx_contractor_id` (`contractor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `split_payment_notifications`
--
ALTER TABLE `split_payment_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_split_group_id` (`split_group_id`),
  ADD KEY `idx_recipient` (`recipient_id`,`recipient_type`),
  ADD KEY `idx_notification_type` (`notification_type`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `split_payment_transactions`
--
ALTER TABLE `split_payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_group_sequence` (`split_group_id`,`sequence_number`),
  ADD KEY `idx_split_group_id` (`split_group_id`),
  ADD KEY `idx_sequence_number` (`sequence_number`),
  ADD KEY `idx_razorpay_order_id` (`razorpay_order_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `stage_payment_notifications`
--
ALTER TABLE `stage_payment_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_request_id` (`payment_request_id`);

--
-- Indexes for table `custom_payment_requests`
--
ALTER TABLE `custom_payment_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_contractor` (`project_id`,`contractor_id`),
  ADD KEY `idx_status_date` (`status`,`request_date`);

--
-- Indexes for table `stage_payment_requests`
--
ALTER TABLE `stage_payment_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stage_payment_transactions`
--
ALTER TABLE `stage_payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_request_id` (`payment_request_id`),
  ADD KEY `idx_homeowner_id` (`homeowner_id`),
  ADD KEY `idx_contractor_id` (`contractor_id`),
  ADD KEY `idx_razorpay_order_id` (`razorpay_order_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_currency` (`currency`),
  ADD KEY `idx_country_code` (`country_code`),
  ADD KEY `idx_international_payment` (`international_payment`);

--
-- Indexes for table `stage_payment_verification_logs`
--
ALTER TABLE `stage_payment_verification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_request_id` (`payment_request_id`);

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
-- Indexes for table `technical_details_payments`
--
ALTER TABLE `technical_details_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_payment` (`house_plan_id`,`homeowner_id`),
  ADD KEY `idx_homeowner_payments` (`homeowner_id`),
  ADD KEY `idx_house_plan_payments` (`house_plan_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_technical_payments_created` (`created_at`),
  ADD KEY `idx_technical_payments_amount` (`amount`),
  ADD KEY `idx_currency` (`currency`),
  ADD KEY `idx_country_code` (`country_code`),
  ADD KEY `idx_international_payment` (`international_payment`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `weekly_progress_summaries`
--
ALTER TABLE `weekly_progress_summaries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_week` (`project_id`,`contractor_id`,`week_start_date`),
  ADD KEY `idx_project_week` (`project_id`,`week_start_date`),
  ADD KEY `idx_contractor` (`contractor_id`),
  ADD KEY `idx_homeowner` (`homeowner_id`);

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
-- AUTO_INCREMENT for table `alternative_payments`
--
ALTER TABLE `alternative_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `alternative_payment_notifications`
--
ALTER TABLE `alternative_payment_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

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
-- AUTO_INCREMENT for table `concept_previews`
--
ALTER TABLE `concept_previews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `construction_phases`
--
ALTER TABLE `construction_phases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `construction_progress_updates`
--
ALTER TABLE `construction_progress_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `construction_projects`
--
ALTER TABLE `construction_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- AUTO_INCREMENT for table `contractor_bank_details`
--
ALTER TABLE `contractor_bank_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contractor_engagements`
--
ALTER TABLE `contractor_engagements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contractor_estimates`
--
ALTER TABLE `contractor_estimates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contractor_estimate_payments`
--
ALTER TABLE `contractor_estimate_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `contractor_inbox`
--
ALTER TABLE `contractor_inbox`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contractor_layout_sends`
--
ALTER TABLE `contractor_layout_sends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

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
-- AUTO_INCREMENT for table `currency_exchange_rates`
--
ALTER TABLE `currency_exchange_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `daily_labour_tracking`
--
ALTER TABLE `daily_labour_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `daily_progress_updates`
--
ALTER TABLE `daily_progress_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
-- AUTO_INCREMENT for table `enhanced_progress_notifications`
--
ALTER TABLE `enhanced_progress_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `estimate_drafts`
--
ALTER TABLE `estimate_drafts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `geo_photos`
--
ALTER TABLE `geo_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `homeowner_notifications`
--
ALTER TABLE `homeowner_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `house_plans`
--
ALTER TABLE `house_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `house_plan_reviews`
--
ALTER TABLE `house_plan_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `inbox_messages`
--
ALTER TABLE `inbox_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `international_payment_settings`
--
ALTER TABLE `international_payment_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `layout_request_assignments`
--
ALTER TABLE `layout_request_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `monthly_progress_reports`
--
ALTER TABLE `monthly_progress_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payment_failure_logs`
--
ALTER TABLE `payment_failure_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_notifications`
--
ALTER TABLE `payment_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `phase_worker_requirements`
--
ALTER TABLE `phase_worker_requirements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `progress_reports`
--
ALTER TABLE `progress_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- AUTO_INCREMENT for table `room_improvement_analyses`
--
ALTER TABLE `room_improvement_analyses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `room_templates`
--
ALTER TABLE `room_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `split_payment_audit_log`
--
ALTER TABLE `split_payment_audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `split_payment_groups`
--
ALTER TABLE `split_payment_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `split_payment_notifications`
--
ALTER TABLE `split_payment_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `split_payment_transactions`
--
ALTER TABLE `split_payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stage_payment_notifications`
--
ALTER TABLE `stage_payment_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `custom_payment_requests`
--
ALTER TABLE `custom_payment_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stage_payment_requests`
--
ALTER TABLE `stage_payment_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `stage_payment_transactions`
--
ALTER TABLE `stage_payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stage_payment_verification_logs`
--
ALTER TABLE `stage_payment_verification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- AUTO_INCREMENT for table `technical_details_payments`
--
ALTER TABLE `technical_details_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000;

--
-- AUTO_INCREMENT for table `weekly_progress_summaries`
--
ALTER TABLE `weekly_progress_summaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `concept_previews`
--
ALTER TABLE `concept_previews`
  ADD CONSTRAINT `concept_previews_ibfk_1` FOREIGN KEY (`layout_request_id`) REFERENCES `layout_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `concept_previews_ibfk_2` FOREIGN KEY (`house_plan_id`) REFERENCES `house_plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `concept_previews_ibfk_3` FOREIGN KEY (`architect_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `construction_projects`
--
ALTER TABLE `construction_projects`
  ADD CONSTRAINT `construction_projects_ibfk_1` FOREIGN KEY (`contractor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `construction_projects_ibfk_2` FOREIGN KEY (`homeowner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `construction_projects_ibfk_3` FOREIGN KEY (`estimate_id`) REFERENCES `contractor_send_estimates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contractor_assignments`
--
ALTER TABLE `contractor_assignments`
  ADD CONSTRAINT `contractor_assignments_ibfk_1` FOREIGN KEY (`layout_request_id`) REFERENCES `layout_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contractor_assignments_ibfk_2` FOREIGN KEY (`contractor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contractor_engagements`
--
ALTER TABLE `contractor_engagements`
  ADD CONSTRAINT `contractor_engagements_ibfk_1` FOREIGN KEY (`homeowner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contractor_engagements_ibfk_2` FOREIGN KEY (`contractor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contractor_engagements_ibfk_3` FOREIGN KEY (`layout_request_id`) REFERENCES `layout_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contractor_engagements_ibfk_4` FOREIGN KEY (`house_plan_id`) REFERENCES `house_plans` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `daily_labour_tracking`
--
ALTER TABLE `daily_labour_tracking`
  ADD CONSTRAINT `daily_labour_tracking_ibfk_1` FOREIGN KEY (`daily_progress_id`) REFERENCES `daily_progress_updates` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `house_plans_ibfk_3` FOREIGN KEY (`parent_plan_id`) REFERENCES `house_plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `house_plans_ibfk_4` FOREIGN KEY (`concept_preview_id`) REFERENCES `concept_previews` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `house_plan_reviews`
--
ALTER TABLE `house_plan_reviews`
  ADD CONSTRAINT `house_plan_reviews_ibfk_1` FOREIGN KEY (`house_plan_id`) REFERENCES `house_plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `house_plan_reviews_ibfk_2` FOREIGN KEY (`homeowner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inbox_messages`
--
ALTER TABLE `inbox_messages`
  ADD CONSTRAINT `inbox_messages_ibfk_1` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inbox_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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

--
-- Constraints for table `room_improvement_analyses`
--
ALTER TABLE `room_improvement_analyses`
  ADD CONSTRAINT `room_improvement_analyses_ibfk_1` FOREIGN KEY (`homeowner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stage_payment_notifications`
--
ALTER TABLE `stage_payment_notifications`
  ADD CONSTRAINT `stage_payment_notifications_ibfk_1` FOREIGN KEY (`payment_request_id`) REFERENCES `stage_payment_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stage_payment_verification_logs`
--
ALTER TABLE `stage_payment_verification_logs`
  ADD CONSTRAINT `stage_payment_verification_logs_ibfk_1` FOREIGN KEY (`payment_request_id`) REFERENCES `stage_payment_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `technical_details_payments`
--
ALTER TABLE `technical_details_payments`
  ADD CONSTRAINT `technical_details_payments_ibfk_1` FOREIGN KEY (`house_plan_id`) REFERENCES `house_plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `technical_details_payments_ibfk_2` FOREIGN KEY (`homeowner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
