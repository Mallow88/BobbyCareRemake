-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 14, 2025 at 10:41 AM
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
-- Database: `bobbycare_remake`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `name`, `created_at`) VALUES
(1, 'sappaya', '$2y$10$xIA2AcwHBDHzKEdE9o2AsONLBdzx2yRiaqWakQ8BZqObixPrSXdrS', 'DEV', '2025-07-21 04:02:53');

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `created_at`) VALUES
(558, 1, 'Login', '2025-08-14 03:48:44'),
(559, 1, 'แก้ไขผู้ใช้ ID 17', '2025-08-14 03:48:53'),
(560, 1, 'Login', '2025-08-14 03:51:02'),
(561, 1, 'แก้ไขผู้ใช้ ID 17', '2025-08-14 03:51:08'),
(562, 1, 'Login', '2025-08-14 03:51:28'),
(563, 1, 'แก้ไขผู้ใช้ ID 17', '2025-08-14 03:51:36'),
(564, 1, 'แก้ไขผู้ใช้ ID 17', '2025-08-14 03:51:41'),
(565, 1, 'Login', '2025-08-14 03:52:49'),
(566, 1, 'แก้ไขผู้ใช้ ID 17', '2025-08-14 03:52:55'),
(567, 1, 'Login', '2025-08-14 04:05:07'),
(568, 1, 'แก้ไขผู้ใช้ ID 17', '2025-08-14 04:05:13'),
(569, 1, 'Login', '2025-08-14 04:33:30'),
(570, 1, 'แก้ไขผู้ใช้ ID 17', '2025-08-14 04:33:38'),
(571, 1, 'Login', '2025-08-14 06:24:02'),
(572, 1, 'แก้ไขผู้ใช้ ID 17', '2025-08-14 06:24:09'),
(573, 1, 'Login', '2025-08-14 06:27:20'),
(574, 1, 'แก้ไขผู้ใช้ ID 17', '2025-08-14 06:27:25'),
(575, 1, 'Login', '2025-08-14 07:52:03'),
(576, 1, 'แก้ไขผู้ใช้ ID 17', '2025-08-14 07:52:25');

-- --------------------------------------------------------

--
-- Table structure for table `assignor_approvals`
--

CREATE TABLE `assignor_approvals` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `assignor_user_id` int(11) NOT NULL,
  `assigned_developer_id` int(11) DEFAULT NULL,
  `status` enum('approved','rejected','pending') DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `estimated_days` int(11) DEFAULT NULL,
  `priority_level` enum('low','medium','high','urgent') DEFAULT 'medium',
  `budget_approved` decimal(10,2) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignor_approvals`
--

INSERT INTO `assignor_approvals` (`id`, `service_request_id`, `assignor_user_id`, `assigned_developer_id`, `status`, `reason`, `estimated_days`, `priority_level`, `budget_approved`, `reviewed_at`, `created_at`) VALUES
(9, 74, 13, 7, 'approved', 'ผ่าน', 2, 'medium', NULL, '2025-08-07 10:32:19', '2025-08-07 10:32:19'),
(10, 75, 15, 15, 'approved', 'ผ่าน', 2, 'urgent', NULL, '2025-08-11 02:12:32', '2025-08-11 02:12:32'),
(11, 80, 15, 1, 'approved', '55', 2, 'high', NULL, '2025-08-13 09:40:54', '2025-08-13 09:40:54'),
(12, 76, 15, 1, 'approved', '88', 2, 'urgent', NULL, '2025-08-13 09:43:41', '2025-08-13 09:43:41'),
(13, 81, 17, 17, 'approved', 'ผ่านครับ', 2, 'urgent', NULL, '2025-08-14 03:52:44', '2025-08-14 03:52:44'),
(14, 83, 17, 17, 'approved', 'ทดสอบเเบบใส่งบ', 2, 'urgent', 2300.00, '2025-08-14 04:13:04', '2025-08-14 04:13:04'),
(15, 82, 17, 17, 'approved', 'ผ่านครับ', 1, 'urgent', 100000.00, '2025-08-14 04:22:37', '2025-08-14 04:22:37'),
(16, 84, 17, 17, 'approved', 'ผ่าน20', 2, 'high', 200.00, '2025-08-14 04:22:56', '2025-08-14 04:22:56'),
(17, 85, 17, 17, 'approved', '200ผ่าน', 1, 'urgent', 200.00, '2025-08-14 04:23:15', '2025-08-14 04:23:15');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `warehouse_number` varchar(10) NOT NULL,
  `code_name` varchar(10) NOT NULL,
  `department_code` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `warehouse_number`, `code_name`, `department_code`, `is_active`, `created_at`) VALUES
(26, '01', 'RID', '', 1, '2025-07-29 08:00:30'),
(27, '01', 'REV', '', 1, '2025-07-29 08:00:30'),
(28, '01', 'SAF', '', 1, '2025-07-29 08:00:30'),
(29, '01', 'ADM', '', 1, '2025-07-29 08:00:30'),
(30, '01', 'RCN', '', 1, '2025-07-29 08:00:30'),
(31, '01', 'O2O', '', 1, '2025-07-29 08:00:30'),
(32, '01', 'ENG', '', 1, '2025-07-29 08:00:30'),
(33, '01', 'TRP', '', 1, '2025-07-29 08:00:30'),
(34, '01', 'ROD', '', 1, '2025-07-29 08:00:30'),
(35, '01', 'SPD', '', 1, '2025-07-29 08:00:30'),
(36, '01', 'TRO', '', 1, '2025-07-29 08:00:30'),
(37, '01', 'DAI', '', 1, '2025-07-29 08:00:30'),
(38, '01', 'FLA', '', 1, '2025-07-29 08:00:30'),
(39, '01', 'DEV', 'เเผนกพัฒนาระบบงาน', 1, '2025-07-29 08:00:30'),
(40, '01', 'MIS', '', 1, '2025-07-29 08:00:30');

-- --------------------------------------------------------

--
-- Table structure for table `div_mgr_approvals`
--

CREATE TABLE `div_mgr_approvals` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `div_mgr_user_id` int(11) NOT NULL,
  `status` enum('approved','rejected','pending') DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `div_mgr_approvals`
--

INSERT INTO `div_mgr_approvals` (`id`, `service_request_id`, `document_number`, `div_mgr_user_id`, `status`, `reason`, `reviewed_at`, `created_at`) VALUES
(14, 74, '01-DEV-25-8-002', 7, 'approved', 'ผ่าน', '2025-08-07 09:58:33', '2025-08-07 09:58:33'),
(15, 76, '01-DEV-25-8-003', 15, 'approved', 'ผ่าน', '2025-08-09 04:31:14', '2025-08-09 04:31:14'),
(16, 75, '01-DAI-25-8-005', 15, 'approved', 'ผ่านครับ', '2025-08-09 04:40:27', '2025-08-09 04:40:27'),
(17, 80, '01-ENG-25-8-001', 15, 'approved', '9999999999999999999999999999999999', '2025-08-13 08:45:08', '2025-08-13 08:45:08'),
(18, 85, '01-ADM-25-8-005', 17, 'approved', 'ผ่านครับ', '2025-08-14 03:47:14', '2025-08-14 03:47:14'),
(19, 84, '01-ADM-25-8-004', 17, 'approved', 'ผ่านครับ', '2025-08-14 03:47:20', '2025-08-14 03:47:20'),
(20, 83, '01-ADM-25-8-003', 17, 'approved', 'ผ่านครับ', '2025-08-14 03:47:27', '2025-08-14 03:47:27'),
(21, 82, '01-ADM-25-8-002', 17, 'approved', 'ผ่านครับ', '2025-08-14 03:47:33', '2025-08-14 03:47:33'),
(22, 81, '01-ADM-25-8-001', 17, 'approved', 'ผ่านครับ', '2025-08-14 03:47:38', '2025-08-14 03:47:38');

-- --------------------------------------------------------

--
-- Table structure for table `document_numbers`
--

CREATE TABLE `document_numbers` (
  `id` int(11) NOT NULL,
  `warehouse_number` varchar(10) NOT NULL,
  `code_name` varchar(10) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `running_number` int(11) NOT NULL,
  `document_number` varchar(50) NOT NULL,
  `service_request_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_numbers`
--

INSERT INTO `document_numbers` (`id`, `warehouse_number`, `code_name`, `year`, `month`, `running_number`, `document_number`, `service_request_id`, `created_at`) VALUES
(59, '01', 'ADM', 25, 8, 1, '01-ADM-25-8-001', 81, '2025-08-14 03:17:46'),
(60, '01', 'ADM', 25, 8, 2, '01-ADM-25-8-002', 82, '2025-08-14 03:19:40'),
(61, '01', 'ADM', 25, 8, 3, '01-ADM-25-8-003', 83, '2025-08-14 03:20:58'),
(62, '01', 'ADM', 25, 8, 4, '01-ADM-25-8-004', 84, '2025-08-14 03:22:05'),
(63, '01', 'ADM', 25, 8, 5, '01-ADM-25-8-005', 85, '2025-08-14 03:23:08'),
(64, '01', 'DAI', 25, 8, 1, '01-DAI-25-8-001', NULL, '2025-08-14 06:36:54'),
(65, '01', 'DAI', 25, 8, 2, '01-DAI-25-8-002', 86, '2025-08-14 06:36:54'),
(66, '01', 'DEV', 25, 8, 1, '01-DEV-25-8-001', 87, '2025-08-14 08:11:00');

-- --------------------------------------------------------

--
-- Table structure for table `document_status_logs`
--

CREATE TABLE `document_status_logs` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `step_name` varchar(100) NOT NULL,
  `status` enum('pending','approved','rejected','in_review') NOT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `reviewer_role` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_status_logs`
--

INSERT INTO `document_status_logs` (`id`, `service_request_id`, `step_name`, `status`, `reviewer_id`, `reviewer_role`, `notes`, `created_at`) VALUES
(36, 74, 'div_mgr_review', 'approved', 7, 'divmgr', 'ผ่าน', '2025-08-07 09:58:33'),
(37, 74, 'assignor_review', 'approved', 13, 'assignor', 'ผ่าน', '2025-08-07 10:32:19'),
(38, 74, 'gm_review', 'approved', 13, 'gmapprover', 'ผ่าน', '2025-08-07 10:34:31'),
(39, 76, 'div_mgr_review', 'approved', 15, 'divmgr', 'ผ่าน', '2025-08-09 04:31:14'),
(40, 75, 'div_mgr_review', 'approved', 15, 'divmgr', 'ผ่านครับ', '2025-08-09 04:40:27'),
(41, 75, 'assignor_review', 'approved', 15, 'assignor', 'ผ่าน', '2025-08-11 02:12:32'),
(42, 75, 'gm_review', 'approved', 15, 'gmapprover', 'ผ่าน', '2025-08-11 02:13:00'),
(43, 75, 'senior_gm_review', 'approved', 15, 'seniorgm', 'ผ่าน ผ่าน', '2025-08-11 02:13:34'),
(44, 80, 'div_mgr_review', 'approved', 15, 'divmgr', '9999999999999999999999999999999999', '2025-08-13 08:45:08'),
(45, 80, 'assignor_review', 'approved', 15, 'assignor', '55', '2025-08-13 09:40:54'),
(46, 76, 'assignor_review', 'approved', 15, 'assignor', '88', '2025-08-13 09:43:41'),
(47, 85, 'div_mgr_review', 'approved', 17, 'divmgr', 'ผ่านครับ', '2025-08-14 03:47:14'),
(48, 84, 'div_mgr_review', 'approved', 17, 'divmgr', 'ผ่านครับ', '2025-08-14 03:47:20'),
(49, 83, 'div_mgr_review', 'approved', 17, 'divmgr', 'ผ่านครับ', '2025-08-14 03:47:27'),
(50, 82, 'div_mgr_review', 'approved', 17, 'divmgr', 'ผ่านครับ', '2025-08-14 03:47:33'),
(51, 81, 'div_mgr_review', 'approved', 17, 'divmgr', 'ผ่านครับ', '2025-08-14 03:47:38'),
(52, 81, 'assignor_review', 'approved', 17, 'assignor', 'ผ่านครับ', '2025-08-14 03:52:44'),
(53, 83, 'assignor_review', 'approved', 17, 'assignor', 'ทดสอบเเบบใส่งบ', '2025-08-14 04:13:04'),
(54, 82, 'assignor_review', 'approved', 17, 'assignor', 'ผ่านครับ', '2025-08-14 04:22:37'),
(55, 84, 'assignor_review', 'approved', 17, 'assignor', 'ผ่าน20', '2025-08-14 04:22:56'),
(56, 85, 'assignor_review', 'approved', 17, 'assignor', '200ผ่าน', '2025-08-14 04:23:15'),
(57, 85, 'gm_review', 'approved', 17, 'gmapprover', '55', '2025-08-14 06:02:34'),
(58, 84, 'gm_review', 'approved', 17, 'gmapprover', 'ผ่าน', '2025-08-14 06:13:32'),
(59, 83, 'gm_review', 'approved', 17, 'gmapprover', 'ผ่าน', '2025-08-14 06:14:12'),
(60, 83, 'senior_gm_review', 'approved', 17, 'seniorgm', 'ไม่มี ไม่มี', '2025-08-14 06:26:54'),
(61, 84, 'senior_gm_review', 'approved', 17, 'seniorgm', 'ผ่าน ผ่านนนนนน', '2025-08-14 06:27:06'),
(62, 85, 'senior_gm_review', 'approved', 17, 'seniorgm', 'ผ่านนน ผ่านนนน4564564', '2025-08-14 06:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `gm_approvals`
--

CREATE TABLE `gm_approvals` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `gm_user_id` int(11) NOT NULL,
  `status` enum('approved','rejected','pending') DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `budget_approved` decimal(10,2) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gm_approvals`
--

INSERT INTO `gm_approvals` (`id`, `service_request_id`, `gm_user_id`, `status`, `reason`, `budget_approved`, `reviewed_at`, `created_at`) VALUES
(9, 74, 13, 'approved', 'ผ่าน', 200.00, '2025-08-07 10:34:31', '2025-08-07 10:34:31'),
(10, 75, 15, 'approved', 'ผ่าน', 3000.00, '2025-08-11 02:13:00', '2025-08-11 02:13:00'),
(11, 85, 17, 'approved', '55', 200.00, '2025-08-14 06:02:34', '2025-08-14 06:02:34'),
(12, 84, 17, 'approved', 'ผ่าน', 2000.00, '2025-08-14 06:13:32', '2025-08-14 06:13:32'),
(13, 83, 17, 'approved', 'ผ่าน', NULL, '2025-08-14 06:14:12', '2025-08-14 06:14:12');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `name`, `is_active`, `created_at`) VALUES
(1, 'Admin Booking จองห้องประชุม, รถ ,เลขที่บันทึก', 1, '2025-07-29 08:00:30'),
(2, 'ARM Share', 1, '2025-07-29 08:00:30'),
(3, 'Battery Mangement', 1, '2025-07-29 08:00:30'),
(4, 'BESAFE', 1, '2025-07-29 08:00:30'),
(5, 'Break Case', 1, '2025-07-29 08:00:30'),
(6, 'Consignment RDC', 1, '2025-07-29 08:00:30'),
(7, 'Consignment CDC', 1, '2025-07-29 08:00:30'),
(8, 'Consignment RDC Monitor', 1, '2025-07-29 08:00:30'),
(9, 'COVID-19', 1, '2025-07-29 08:00:30'),
(10, 'ALL Dashboard', 1, '2025-07-29 08:00:30'),
(11, 'DATA CENTER', 1, '2025-07-29 08:00:30'),
(12, 'DATA ENTRY', 1, '2025-07-29 08:00:30'),
(13, 'Friend\'s Corner', 1, '2025-07-29 08:00:30'),
(14, 'Full Case', 1, '2025-07-29 08:00:30'),
(15, 'iMove', 1, '2025-07-29 08:00:30'),
(16, 'LABEL RDC', 1, '2025-07-29 08:00:30'),
(17, 'LABEL CDC', 1, '2025-07-29 08:00:30'),
(18, 'Loading', 1, '2025-07-29 08:00:30'),
(19, 'Location Hold', 1, '2025-07-29 08:00:30'),
(20, 'LORA', 1, '2025-07-29 08:00:30'),
(21, 'Magic Location', 1, '2025-07-29 08:00:30'),
(22, 'MAYYA', 1, '2025-07-29 08:00:30'),
(23, 'Mermaid', 1, '2025-07-29 08:00:30'),
(24, 'NRS', 1, '2025-07-29 08:00:30'),
(25, 'O2O RDC', 1, '2025-07-29 08:00:30'),
(26, 'O2O CDC', 1, '2025-07-29 08:00:30'),
(27, 'Organization Development', 1, '2025-07-29 08:00:30'),
(28, 'PICK & LOAD', 1, '2025-07-29 08:00:30'),
(29, 'Pick Dang', 1, '2025-07-29 08:00:30'),
(30, 'PIMMY CDC', 1, '2025-07-29 08:00:30'),
(31, 'PIMMY RDC', 1, '2025-07-29 08:00:30'),
(32, 'POP & FOP', 1, '2025-07-29 08:00:30'),
(33, 'Product', 1, '2025-07-29 08:00:30'),
(34, 'Progress RDC', 1, '2025-07-29 08:00:30'),
(35, 'RECEIVE CDC', 1, '2025-07-29 08:00:30'),
(36, 'RECEIVE CHECK-IN', 1, '2025-07-29 08:00:30'),
(37, 'Repick', 1, '2025-07-29 08:00:30'),
(38, 'Replenishment', 1, '2025-07-29 08:00:30'),
(39, 'STOCK Engineer', 1, '2025-07-29 08:00:30'),
(40, 'TO BE SHOP CDC', 1, '2025-07-29 08:00:30'),
(41, 'TO BE SHOP INVENTORY', 1, '2025-07-29 08:00:30'),
(42, 'TO BE SHOP RDC', 1, '2025-07-29 08:00:30'),
(43, 'Transporter RDC', 1, '2025-07-29 08:00:30'),
(44, 'Transporter CDC', 1, '2025-07-29 08:00:30'),
(45, 'TRUCK-AGE', 1, '2025-07-29 08:00:30'),
(46, 'VSM', 1, '2025-07-29 08:00:30'),
(47, 'ADMIN ตรวจนับทรัพย์สิน', 1, '2025-07-29 08:00:30'),
(48, 'ADMIN เบิกวัสดุอุปกรณ์สิ้นเปลือง CDC', 1, '2025-07-29 08:00:30'),
(49, 'ADMIN เบิกวัสดุอุปกรณ์สิ้นเปลือง RDC', 1, '2025-07-29 08:00:30'),
(50, 'รักษ์ RACK', 1, '2025-07-29 08:00:30'),
(51, 'รักษ์ รถ', 1, '2025-07-29 08:00:30'),
(52, 'Remaining Check Stock (RCS)', 1, '2025-07-29 08:00:30');

-- --------------------------------------------------------

--
-- Table structure for table `request_attachments`
--

CREATE TABLE `request_attachments` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request_attachments`
--

INSERT INTO `request_attachments` (`id`, `service_request_id`, `original_filename`, `stored_filename`, `file_size`, `file_type`, `uploaded_at`) VALUES
(50, 73, 'S__458758.jpg', '73_1754558522_0.jpg', 238362, 'jpg', '2025-08-07 09:22:02'),
(51, 74, 'Gmail - เมล์แจ้งรายละเอียดก่อนเริ่มงาน.pdf', '74_1754560195_0.pdf', 404447, 'pdf', '2025-08-07 09:49:55'),
(52, 75, 'LINE_ALBUM_14768_250801_14.jpg', '75_1754640916_0.jpg', 353714, 'jpg', '2025-08-08 08:15:16'),
(53, 76, 'S__458758.jpg', '76_1754646918_0.jpg', 238362, 'jpg', '2025-08-08 09:55:18'),
(54, 80, 'LINE_ALBUM_14768_250801_1.jpg', '80_1755068714_0.jpg', 411384, 'jpg', '2025-08-13 07:05:14'),
(55, 81, 'LINE_ALBUM_14768_250801_1.jpg', '81_1755141466_0.jpg', 411384, 'jpg', '2025-08-14 03:17:46'),
(56, 82, 'LINE_ALBUM_14768_250801_2.jpg', '82_1755141580_0.jpg', 451442, 'jpg', '2025-08-14 03:19:40'),
(57, 83, 'LINE_ALBUM_14768_250801_1.jpg', '83_1755141658_0.jpg', 411384, 'jpg', '2025-08-14 03:20:58'),
(58, 84, 'LINE_ALBUM_14768_250801_3.jpg', '84_1755141725_0.jpg', 394892, 'jpg', '2025-08-14 03:22:05'),
(59, 85, 'LINE_ALBUM_14768_250801_6.jpg', '85_1755141788_0.jpg', 228617, 'jpg', '2025-08-14 03:23:08'),
(60, 86, 'LINE_ALBUM_14768_250801_3.jpg', '86_1755153414_0.jpg', 394892, 'jpg', '2025-08-14 06:36:54'),
(61, 87, 'LINE_ALBUM_14768_250801_2.jpg', '87_1755159060_0.jpg', 451442, 'jpg', '2025-08-14 08:11:00');

-- --------------------------------------------------------

--
-- Table structure for table `senior_gm_approvals`
--

CREATE TABLE `senior_gm_approvals` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `senior_gm_user_id` int(11) NOT NULL,
  `status` enum('approved','rejected','pending') DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `final_notes` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `senior_gm_approvals`
--

INSERT INTO `senior_gm_approvals` (`id`, `service_request_id`, `senior_gm_user_id`, `status`, `reason`, `final_notes`, `reviewed_at`, `created_at`) VALUES
(61, 75, 15, 'approved', 'ผ่าน', 'ผ่าน', '2025-08-11 02:13:34', '2025-08-11 02:13:34'),
(62, 83, 17, 'approved', 'ไม่มี', 'ไม่มี', '2025-08-14 06:26:54', '2025-08-14 06:26:54'),
(63, 84, 17, 'approved', 'ผ่าน', 'ผ่านนนนนน', '2025-08-14 06:27:06', '2025-08-14 06:27:06'),
(64, 85, 17, 'approved', 'ผ่านนน', 'ผ่านนนน4564564', '2025-08-14 06:27:16', '2025-08-14 06:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` enum('development','service') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `category`, `is_active`, `created_at`) VALUES
(1, 'โปรแกรมใหม่', 'development', 1, '2025-07-29 08:00:30'),
(2, 'โปรแกรมเดิม (แก้ปัญหา)', 'development', 1, '2025-07-29 08:00:30'),
(3, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 'development', 1, '2025-07-29 08:00:30'),
(4, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 'development', 1, '2025-07-29 08:00:30'),
(5, 'โปรแกรมเดิม (ตกแต่ง)', 'development', 1, '2025-07-29 08:00:30'),
(6, 'Handheld / RF', 'service', 1, '2025-07-29 08:00:30'),
(7, 'Computer / Notebook', 'service', 1, '2025-07-29 08:00:30'),
(8, 'Microsoft 365 (Microsoft Word,Excel,PowerPoint,andOutlook)', 'service', 1, '2025-07-29 08:00:30'),
(9, 'Network', 'service', 1, '2025-07-29 08:00:30'),
(10, 'Printer', 'service', 1, '2025-07-29 08:00:30'),
(11, 'CCTV', 'service', 1, '2025-07-29 08:00:30'),
(12, 'AccessDoor', 'service', 1, '2025-07-29 08:00:30'),
(13, 'เครื่องเสียง', 'service', 1, '2025-07-29 08:00:30'),
(14, 'อื่นๆ', 'service', 1, '2025-07-29 08:00:30');

-- --------------------------------------------------------

--
-- Table structure for table `service_requests`
--

CREATE TABLE `service_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `work_category` varchar(50) DEFAULT NULL,
  `assigned_div_mgr_id` int(11) DEFAULT NULL,
  `document_number` varchar(50) DEFAULT NULL,
  `status` enum('pending','div_mgr_review','assignor_review','gm_review','senior_gm_review','approved','rejected','developer_assigned','in_progress','completed') DEFAULT 'div_mgr_review',
  `current_step` varchar(50) DEFAULT 'user_submitted',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `estimated_days` int(11) DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `developer_status` enum('not_assigned','pending','received','in_progress','on_hold','completed','user_review','accepted') DEFAULT 'not_assigned',
  `program_purpose` text DEFAULT NULL COMMENT 'วัตถุประสงค์ของโปรแกรม',
  `target_users` text DEFAULT NULL COMMENT 'กลุ่มผู้ใช้งาน',
  `main_functions` text DEFAULT NULL COMMENT 'ฟังก์ชันหลักที่ต้องการ',
  `data_requirements` text DEFAULT NULL COMMENT 'ข้อมูลที่ต้องใช้',
  `current_program_name` varchar(255) DEFAULT NULL COMMENT 'ชื่อโปรแกรมที่มีปัญหา',
  `problem_description` text DEFAULT NULL COMMENT 'รายละเอียดปัญหา',
  `error_frequency` varchar(50) DEFAULT NULL COMMENT 'ความถี่ของปัญหา',
  `steps_to_reproduce` text DEFAULT NULL COMMENT 'ขั้นตอนการทำให้เกิดปัญหา',
  `program_name_change` varchar(255) DEFAULT NULL COMMENT 'ชื่อโปรแกรมที่ต้องการเปลี่ยนข้อมูล',
  `data_to_change` text DEFAULT NULL COMMENT 'ข้อมูลที่ต้องการเปลี่ยน',
  `new_data_value` text DEFAULT NULL COMMENT 'ข้อมูลใหม่ที่ต้องการ',
  `change_reason` text DEFAULT NULL COMMENT 'เหตุผลในการเปลี่ยนแปลง',
  `program_name_function` varchar(255) DEFAULT NULL COMMENT 'ชื่อโปรแกรมที่ต้องการเพิ่มฟังก์ชั่น',
  `new_functions` text DEFAULT NULL COMMENT 'ฟังก์ชั่นใหม่ที่ต้องการ',
  `function_benefits` text DEFAULT NULL COMMENT 'ประโยชน์ของฟังก์ชั่นใหม่',
  `integration_requirements` text DEFAULT NULL COMMENT 'ความต้องการเชื่อมต่อ',
  `program_name_decorate` varchar(255) DEFAULT NULL COMMENT 'ชื่อโปรแกรมที่ต้องการตกแต่ง',
  `decoration_type` text DEFAULT NULL COMMENT 'ประเภทการตกแต่ง',
  `reference_examples` text DEFAULT NULL COMMENT 'ตัวอย่างอ้างอิง',
  `current_workflow` text DEFAULT NULL COMMENT 'ขั้นตอนการทำงานเดิม',
  `approach_ideas` text DEFAULT NULL COMMENT 'แนวทาง/ไอเดีย',
  `related_programs` text DEFAULT NULL COMMENT 'โปรแกรมที่คาดว่าจะเกี่ยวข้อง',
  `current_tools` text DEFAULT NULL COMMENT 'ปกติใช้โปรแกรมอะไรทำงานอยู่',
  `system_impact` text DEFAULT NULL COMMENT 'ผลกระทบต่อระบบ',
  `related_documents` text DEFAULT NULL COMMENT 'เอกสารการทำงานที่เกี่ยวข้อง',
  `expected_benefits` text DEFAULT NULL COMMENT 'ประโยชน์ที่คาดว่าจะได้รับ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_requests`
--

INSERT INTO `service_requests` (`id`, `user_id`, `title`, `service_id`, `work_category`, `assigned_div_mgr_id`, `document_number`, `status`, `current_step`, `priority`, `estimated_days`, `deadline`, `developer_status`, `program_purpose`, `target_users`, `main_functions`, `data_requirements`, `current_program_name`, `problem_description`, `error_frequency`, `steps_to_reproduce`, `program_name_change`, `data_to_change`, `new_data_value`, `change_reason`, `program_name_function`, `new_functions`, `function_benefits`, `integration_requirements`, `program_name_decorate`, `decoration_type`, `reference_examples`, `current_workflow`, `approach_ideas`, `related_programs`, `current_tools`, `system_impact`, `related_documents`, `expected_benefits`, `created_at`, `updated_at`, `description`) VALUES
(81, 17, 'ทดสอบตกแต่ง', 5, '01-ADM', 17, NULL, 'gm_review', 'assignor_approved', 'urgent', 2, '2025-08-14 10:52:00', 'not_assigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, '', NULL, '', NULL, NULL, NULL, '', '2025-08-14 03:17:46', '2025-08-14 03:52:44', 'ประเภทบริการ: โปรแกรมเดิม (ตกแต่ง)\nหัวข้องานคลัง: 01-ADM'),
(82, 17, 'ทดสอบเปลี่ยนข้อมูล', 3, '01-ADM', 17, NULL, 'gm_review', 'assignor_approved', 'urgent', 1, '2025-08-15 11:22:00', 'not_assigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'BESAFE', 'การเปลี่ยนตัวเลข', 'ข้อมูลใหม่ข้อความ', ' เหตุผลดูยาก', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, '', NULL, NULL, NULL, 'เข้าใจง่าย', '2025-08-14 03:19:40', '2025-08-14 04:22:37', 'ประเภทบริการ: โปรแกรมเดิม (เปลี่ยนข้อมูล)\nหัวข้องานคลัง: 01-ADM\nโปรแกรม: BESAFE\nข้อมูลที่ต้องเปลี่ยน: การเปลี่ยนตัวเลข'),
(83, 17, 'ทดสอบเพิ่มฟังก์ชั่น', 4, '01-ADM', 17, NULL, 'approved', 'senior_gm_approved', 'urgent', 2, '2025-08-16 11:12:00', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'iMove', 'ฟังก์ชั่นใหม่ลบข้อมูล', 'สามารถลบได้', 'น่าจะไม่มีครับ', NULL, NULL, NULL, '', NULL, '', NULL, NULL, NULL, '', '2025-08-14 03:20:58', '2025-08-14 06:26:54', 'ประเภทบริการ: โปรแกรมเดิม (เพิ่มฟังก์ชั่น)\nหัวข้องานคลัง: 01-ADM\nโปรแกรม: iMove\nฟังก์ชั่นใหม่: ฟังก์ชั่นใหม่ลบข้อมูล'),
(84, 17, 'ทดสอบแก้ปัญหา', 2, '01-ADM', 17, NULL, 'approved', 'senior_gm_approved', 'high', 2, '2025-08-15 11:22:00', 'pending', NULL, NULL, NULL, NULL, 'ADMIN ตรวจนับทรัพย์สิน', 'ทดสอบรายละเอียดปัญหา ', 'เกิดขึ้นมากกว่า20ครั้ง', 'ทดสอบขั้นตอนการทำให้เกิดปัญหา', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, '', NULL, NULL, NULL, '', '2025-08-14 03:22:05', '2025-08-14 06:27:06', 'ประเภทบริการ: โปรแกรมเดิม (แก้ปัญหา)\nหัวข้องานคลัง: 01-ADM\nโปรแกรม: ADMIN ตรวจนับทรัพย์สิน\nปัญหา: ทดสอบรายละเอียดปัญหา '),
(85, 17, 'ทดสอบโปรแกรมใหม่', 1, '01-ADM', 17, NULL, 'approved', 'senior_gm_approved', 'urgent', 1, '2025-08-15 11:23:00', 'in_progress', 'ทดสอบวัตถุประสงค์', 'ทดสอบกลุ่มผู้ใช้งาน', 'ทดสอบฟังก์ชันหลักที่', 'ทดสอบข้อมูลที่ต้องใช้', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ทดสอบขั้นตอนการทำงานเดิม', NULL, 'ทดสอบโปรแกรมที่คาดว่าจะเกี่ยวข้อง', NULL, NULL, NULL, '', '2025-08-14 03:23:08', '2025-08-14 06:28:24', 'ประเภทบริการ: โปรแกรมใหม่\nหัวข้องานคลัง: 01-ADM\nวัตถุประสงค์: ทดสอบวัตถุประสงค์\nกลุ่มผู้ใช้: ทดสอบกลุ่มผู้ใช้งาน\nฟังก์ชันหลัก: ทดสอบฟังก์ชันหลักที่'),
(86, 17, 'ทด', 11, NULL, NULL, NULL, 'approved', 'developer_self_created', 'urgent', 1, '2025-08-15 06:36:00', 'completed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-14 06:36:54', '2025-08-14 06:36:57', 'ทดสอบบบบบบ'),
(87, 17, '5555', 5, '01-DEV', 17, NULL, 'pending', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, '', NULL, '', NULL, NULL, NULL, '', '2025-08-14 08:11:00', '2025-08-14 08:11:00', 'ประเภทบริการ: โปรแกรมเดิม (ตกแต่ง)\nหัวข้องานคลัง: 01-DEV');

-- --------------------------------------------------------

--
-- Table structure for table `subtask_logs`
--

CREATE TABLE `subtask_logs` (
  `id` int(11) NOT NULL,
  `subtask_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subtask_templates`
--

CREATE TABLE `subtask_templates` (
  `id` int(11) NOT NULL,
  `service_type` varchar(100) NOT NULL COMMENT 'ประเภทบริการ',
  `step_order` int(11) NOT NULL COMMENT 'ลำดับขั้นตอน',
  `step_name` varchar(255) NOT NULL COMMENT 'ชื่อขั้นตอน',
  `step_description` text DEFAULT NULL COMMENT 'รายละเอียดขั้นตอน',
  `percentage` int(11) NOT NULL DEFAULT 0 COMMENT 'เปอร์เซ็นต์ของขั้นตอนนี้',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subtask_templates`
--

INSERT INTO `subtask_templates` (`id`, `service_type`, `step_order`, `step_name`, `step_description`, `percentage`, `is_active`, `created_at`) VALUES
(1, 'โปรแกรมใหม่', 1, 'ส่วนหน้าบ้าน (Frontend)', 'พัฒนา HTML, CSS, JavaScript สำหรับส่วนติดต่อผู้ใช้', 20, 1, '2025-07-31 00:26:58'),
(2, 'โปรแกรมใหม่', 2, 'ส่วนหลังบ้าน (Backend)', 'พัฒนา API, Database, Logic ส่วนหลังบ้าน', 30, 1, '2025-07-31 00:26:58'),
(3, 'โปรแกรมใหม่', 3, 'ทดสอบระบบ', 'ทดสอบการทำงานของระบบและแก้ไข Bug', 20, 1, '2025-07-31 00:26:58'),
(4, 'โปรแกรมใหม่', 4, 'ทดสอบผู้ใช้', 'ทดสอบกับผู้ใช้จริงและปรับปรุง', 20, 1, '2025-07-31 00:26:58'),
(5, 'โปรแกรมใหม่', 5, 'เตรียมเอกสารและส่งมอบ', 'จัดทำเอกสารและส่งมอบระบบ', 10, 1, '2025-07-31 00:26:58'),
(6, 'โปรแกรมเดิม (แก้ปัญหา)', 1, 'วิเคราะห์ปัญหา', 'ศึกษาและวิเคราะห์สาเหตุของปัญหา', 20, 1, '2025-07-31 00:26:58'),
(7, 'โปรแกรมเดิม (แก้ปัญหา)', 2, 'แก้ไขโค้ด', 'แก้ไขโค้ดและปรับปรุงระบบ', 40, 1, '2025-07-31 00:26:58'),
(8, 'โปรแกรมเดิม (แก้ปัญหา)', 3, 'ทดสอบการแก้ไข', 'ทดสอบว่าปัญหาได้รับการแก้ไขแล้ว', 25, 1, '2025-07-31 00:26:58'),
(9, 'โปรแกรมเดิม (แก้ปัญหา)', 4, 'ทดสอบผู้ใช้', 'ให้ผู้ใช้ทดสอบและยืนยันการแก้ไข', 10, 1, '2025-07-31 00:26:58'),
(10, 'โปรแกรมเดิม (แก้ปัญหา)', 5, 'อัปเดตเอกสาร', 'อัปเดตเอกสารและบันทึกการแก้ไข', 5, 1, '2025-07-31 00:26:58'),
(11, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 1, 'วิเคราะห์ข้อมูลเดิม', 'ศึกษาโครงสร้างข้อมูลปัจจุบัน', 15, 1, '2025-07-31 00:26:58'),
(12, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 2, 'สำรองข้อมูล', 'สำรองข้อมูลเดิมก่อนเปลี่ยนแปลง', 10, 1, '2025-07-31 00:26:58'),
(13, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 3, 'แก้ไขข้อมูล', 'ดำเนินการเปลี่ยนแปลงข้อมูล', 50, 1, '2025-07-31 00:26:58'),
(14, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 4, 'ทดสอบข้อมูล', 'ทดสอบความถูกต้องของข้อมูลใหม่', 20, 1, '2025-07-31 00:26:58'),
(15, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 5, 'อัปเดตเอกสาร', 'อัปเดตเอกสารและคู่มือการใช้งาน', 5, 1, '2025-07-31 00:26:58'),
(16, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 1, 'วิเคราะห์ความต้องการ', 'ศึกษาและวิเคราะห์ฟังก์ชั่นที่ต้องการ', 15, 1, '2025-07-31 00:26:58'),
(17, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 2, 'พัฒนาฟังก์ชั่นใหม่', 'เขียนโค้ดและพัฒนาฟังก์ชั่นใหม่', 40, 1, '2025-07-31 00:26:58'),
(18, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 3, 'ทดสอบฟังก์ชั่น', 'ทดสอบฟังก์ชั่นใหม่และการทำงานร่วมกับระบบเดิม', 25, 1, '2025-07-31 00:26:58'),
(19, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 4, 'ทดสอบผู้ใช้', 'ให้ผู้ใช้ทดสอบฟังก์ชั่นใหม่', 15, 1, '2025-07-31 00:26:58'),
(20, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 5, 'อัปเดตเอกสาร', 'อัปเดตคู่มือและเอกสารการใช้งาน', 5, 1, '2025-07-31 00:26:58'),
(21, 'โปรแกรมเดิม (ตกแต่ง)', 1, 'ออกแบบ UI/UX', 'ออกแบบหน้าตาและประสบการณ์ผู้ใช้', 25, 1, '2025-07-31 00:26:58'),
(22, 'โปรแกรมเดิม (ตกแต่ง)', 2, 'พัฒนาส่วนตกแต่ง', 'เขียนโค้ด CSS, JavaScript สำหรับการตกแต่ง', 35, 1, '2025-07-31 00:26:58'),
(23, 'โปรแกรมเดิม (ตกแต่ง)', 3, 'ทดสอบการแสดงผล', 'ทดสอบการแสดงผลในอุปกรณ์ต่างๆ', 25, 1, '2025-07-31 00:26:58'),
(24, 'โปรแกรมเดิม (ตกแต่ง)', 4, 'ปรับปรุงตามข้อเสนอแนะ', 'ปรับปรุงตามความเห็นของผู้ใช้', 10, 1, '2025-07-31 00:26:58'),
(25, 'โปรแกรมเดิม (ตกแต่ง)', 5, 'ส่งมอบและอัปเดตเอกสาร', 'ส่งมอบงานและอัปเดตเอกสาร', 5, 1, '2025-07-31 00:26:58'),
(26, 'โปรแกรมใหม่', 1, 'ส่วนหน้าบ้าน (Frontend)', 'พัฒนา HTML, CSS, JavaScript สำหรับส่วนติดต่อผู้ใช้', 20, 1, '2025-07-31 00:38:33'),
(27, 'โปรแกรมใหม่', 2, 'ส่วนหลังบ้าน (Backend)', 'พัฒนา API, Database, Logic ส่วนหลังบ้าน', 30, 1, '2025-07-31 00:38:33'),
(28, 'โปรแกรมใหม่', 3, 'ทดสอบระบบ', 'ทดสอบการทำงานของระบบและแก้ไข Bug', 20, 1, '2025-07-31 00:38:33'),
(29, 'โปรแกรมใหม่', 4, 'ทดสอบผู้ใช้', 'ทดสอบกับผู้ใช้จริงและปรับปรุง', 20, 1, '2025-07-31 00:38:33'),
(30, 'โปรแกรมใหม่', 5, 'เตรียมเอกสารและส่งมอบ', 'จัดทำเอกสารและส่งมอบระบบ', 10, 1, '2025-07-31 00:38:33'),
(31, 'โปรแกรมเดิม (แก้ปัญหา)', 1, 'วิเคราะห์ปัญหา', 'ศึกษาและวิเคราะห์สาเหตุของปัญหา', 20, 1, '2025-07-31 00:38:33'),
(32, 'โปรแกรมเดิม (แก้ปัญหา)', 2, 'แก้ไขโค้ด', 'แก้ไขโค้ดและปรับปรุงระบบ', 40, 1, '2025-07-31 00:38:33'),
(33, 'โปรแกรมเดิม (แก้ปัญหา)', 3, 'ทดสอบการแก้ไข', 'ทดสอบว่าปัญหาได้รับการแก้ไขแล้ว', 25, 1, '2025-07-31 00:38:33'),
(34, 'โปรแกรมเดิม (แก้ปัญหา)', 4, 'ทดสอบผู้ใช้', 'ให้ผู้ใช้ทดสอบและยืนยันการแก้ไข', 10, 1, '2025-07-31 00:38:33'),
(35, 'โปรแกรมเดิม (แก้ปัญหา)', 5, 'อัปเดตเอกสาร', 'อัปเดตเอกสารและบันทึกการแก้ไข', 5, 1, '2025-07-31 00:38:33'),
(36, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 1, 'วิเคราะห์ข้อมูลเดิม', 'ศึกษาโครงสร้างข้อมูลปัจจุบัน', 15, 1, '2025-07-31 00:38:33'),
(37, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 2, 'สำรองข้อมูล', 'สำรองข้อมูลเดิมก่อนเปลี่ยนแปลง', 10, 1, '2025-07-31 00:38:33'),
(38, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 3, 'แก้ไขข้อมูล', 'ดำเนินการเปลี่ยนแปลงข้อมูล', 50, 1, '2025-07-31 00:38:33'),
(39, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 4, 'ทดสอบข้อมูล', 'ทดสอบความถูกต้องของข้อมูลใหม่', 20, 1, '2025-07-31 00:38:33'),
(40, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 5, 'อัปเดตเอกสาร', 'อัปเดตเอกสารและคู่มือการใช้งาน', 5, 1, '2025-07-31 00:38:33'),
(41, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 1, 'วิเคราะห์ความต้องการ', 'ศึกษาและวิเคราะห์ฟังก์ชั่นที่ต้องการ', 15, 1, '2025-07-31 00:38:33'),
(42, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 2, 'พัฒนาฟังก์ชั่นใหม่', 'เขียนโค้ดและพัฒนาฟังก์ชั่นใหม่', 40, 1, '2025-07-31 00:38:33'),
(43, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 3, 'ทดสอบฟังก์ชั่น', 'ทดสอบฟังก์ชั่นใหม่และการทำงานร่วมกับระบบเดิม', 25, 1, '2025-07-31 00:38:33'),
(44, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 4, 'ทดสอบผู้ใช้', 'ให้ผู้ใช้ทดสอบฟังก์ชั่นใหม่', 15, 1, '2025-07-31 00:38:33'),
(45, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 5, 'อัปเดตเอกสาร', 'อัปเดตคู่มือและเอกสารการใช้งาน', 5, 1, '2025-07-31 00:38:33'),
(46, 'โปรแกรมเดิม (ตกแต่ง)', 1, 'ออกแบบ UI/UX', 'ออกแบบหน้าตาและประสบการณ์ผู้ใช้', 25, 1, '2025-07-31 00:38:33'),
(47, 'โปรแกรมเดิม (ตกแต่ง)', 2, 'พัฒนาส่วนตกแต่ง', 'เขียนโค้ด CSS, JavaScript สำหรับการตกแต่ง', 35, 1, '2025-07-31 00:38:33'),
(48, 'โปรแกรมเดิม (ตกแต่ง)', 3, 'ทดสอบการแสดงผล', 'ทดสอบการแสดงผลในอุปกรณ์ต่างๆ', 25, 1, '2025-07-31 00:38:33'),
(49, 'โปรแกรมเดิม (ตกแต่ง)', 4, 'ปรับปรุงตามข้อเสนอแนะ', 'ปรับปรุงตามความเห็นของผู้ใช้', 10, 1, '2025-07-31 00:38:33'),
(50, 'โปรแกรมเดิม (ตกแต่ง)', 5, 'ส่งมอบและอัปเดตเอกสาร', 'ส่งมอบงานและอัปเดตเอกสาร', 5, 1, '2025-07-31 00:38:33');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `developer_user_id` int(11) NOT NULL,
  `task_status` enum('pending','received','in_progress','on_hold','completed','user_review','accepted','revision_requested','cancelled') DEFAULT 'pending',
  `progress_percentage` int(11) DEFAULT 0,
  `developer_notes` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `estimated_completion` date DEFAULT NULL,
  `actual_hours` decimal(5,2) DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `service_request_id`, `developer_user_id`, `task_status`, `progress_percentage`, `developer_notes`, `started_at`, `completed_at`, `estimated_completion`, `actual_hours`, `accepted_at`, `created_at`, `updated_at`) VALUES
(106, 83, 17, 'pending', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-14 06:26:54', '2025-08-14 06:26:54'),
(107, 84, 17, 'pending', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-14 06:27:06', '2025-08-14 06:27:06'),
(108, 85, 17, 'in_progress', 0, '', NULL, NULL, NULL, NULL, NULL, '2025-08-14 06:27:16', '2025-08-14 06:28:25'),
(109, 86, 17, 'completed', 100, '', '2025-08-14 06:36:54', '2025-08-14 06:36:57', '2025-08-15', NULL, '2025-08-14 13:36:54', '2025-08-14 06:36:54', '2025-08-14 06:36:57');

-- --------------------------------------------------------

--
-- Table structure for table `task_status_logs`
--

CREATE TABLE `task_status_logs` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_status_logs`
--

INSERT INTO `task_status_logs` (`id`, `task_id`, `old_status`, `new_status`, `changed_by`, `notes`, `created_at`) VALUES
(242, 100, 'completed', 'accepted', 13, 'เยี่ยมครับ', '2025-08-07 09:30:28'),
(243, 108, '', 'received', 17, '', '2025-08-14 06:28:19'),
(244, 108, '', 'in_progress', 17, '', '2025-08-14 06:28:24'),
(245, 109, '', 'completed', 17, '', '2025-08-14 06:36:57');

-- --------------------------------------------------------

--
-- Table structure for table `task_subtasks`
--

CREATE TABLE `task_subtasks` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `step_order` int(11) NOT NULL COMMENT 'ลำดับขั้นตอน (1-5)',
  `step_name` varchar(255) NOT NULL COMMENT 'ชื่อขั้นตอน',
  `step_description` text DEFAULT NULL COMMENT 'รายละเอียดขั้นตอน',
  `percentage` int(11) NOT NULL DEFAULT 0 COMMENT 'เปอร์เซ็นต์ของขั้นตอนนี้',
  `status` enum('pending','in_progress','completed') DEFAULT 'pending' COMMENT 'สถานะของ subtask',
  `started_at` timestamp NULL DEFAULT NULL COMMENT 'วันที่เริ่มขั้นตอนนี้',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'วันที่เสร็จขั้นตอนนี้',
  `notes` text DEFAULT NULL COMMENT 'หมายเหตุของขั้นตอนนี้',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_subtasks`
--

INSERT INTO `task_subtasks` (`id`, `task_id`, `step_order`, `step_name`, `step_description`, `percentage`, `status`, `started_at`, `completed_at`, `notes`, `created_at`, `updated_at`) VALUES
(38, 96, 3, 'แก้ไขข้อมูล', 'ดำเนินการเปลี่ยนแปลงข้อมูล', 50, 'completed', '2025-08-07 06:26:42', '2025-08-07 06:28:08', 'ดำเนินเรียบร้อย', '2025-08-07 06:26:19', '2025-08-07 06:28:08'),
(39, 108, 1, 'ส่วนหน้าบ้าน (Frontend)', 'พัฒนา HTML, CSS, JavaScript สำหรับส่วนติดต่อผู้ใช้', 20, 'pending', NULL, NULL, NULL, '2025-08-14 06:28:22', '2025-08-14 06:28:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `line_id` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `created_by_admin_id` int(11) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `employee_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `line_id`, `name`, `lastname`, `position`, `email`, `created_at`, `phone`, `role`, `created_by_admin_id`, `department`, `is_active`, `employee_id`) VALUES
(1, 'U0770f6d5ea92bb7de46d36d18bf976c5', 'DEV', 'NS ', 'เจ้าหน้าที่', 'test@gmail.com', '2025-07-21 02:09:31', '08887898784', 'developer', NULL, 'แผนกพัฒนาระบบงาน (RDC.นครสวรรค์)', 1, '7884657'),
(14, 'U573ad925a4167b13cbeaf2595a3b9784', 'ประหยัด', 'จันทร์อังคารพุธพฤหัสบดีศุกร์เสาร์', 'ผู้จัดการแผนก', 'chonnaweepro@cpall.co.th', '2025-08-07 09:35:13', '000000000', 'userservice', NULL, 'แผนกพัฒนาระบบงาน', 1, '0046998'),
(17, 'Ucdeb083644ac0ea108e85ba498a1c784', 'สัภยา', 'เกสร', 'เจ้าหน้าที่', 'somchai@example.com', '2025-08-14 08:33:49', '0812345678', 'user', NULL, 'แผนกพัฒนาระบบงาน', 1, '7884657');

-- --------------------------------------------------------

--
-- Table structure for table `usertemplate`
--

CREATE TABLE `usertemplate` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `department` varchar(100) DEFAULT NULL,
  `employee_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usertemplate`
--

INSERT INTO `usertemplate` (`id`, `name`, `lastname`, `position`, `email`, `phone`, `role`, `department`, `employee_id`) VALUES
(1, 'สัภยา', 'เกสร', 'เจ้าหน้าที่', 'somchai@example.com', '0812345678', 'user', 'แผนกพัฒนาระบบงาน', '7884657'),
(2, 'สัภยา', 'ทองดี', 'หัวหน้าหน่วย', 'wipa@example.com', '0823456789', 'user', 'แผนกข้อมูลรับ', '7884455'),
(3, 'อนันต์', 'สุขใจ', 'ผู้จัดการแผนก', 'anan@example.com', '0834567890', 'userservice', 'แผนกพัฒนาระบบงาน', 'EMP003');

-- --------------------------------------------------------

--
-- Table structure for table `user_reviews`
--

CREATE TABLE `user_reviews` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_comment` text DEFAULT NULL,
  `status` enum('pending_review','accepted','revision_requested') DEFAULT 'pending_review',
  `revision_notes` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `assignor_approvals`
--
ALTER TABLE `assignor_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_assignor_approvals_request` (`service_request_id`),
  ADD KEY `idx_assignor_approvals_assignor` (`assignor_user_id`),
  ADD KEY `idx_assignor_approvals_developer` (`assigned_developer_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_warehouse_code` (`warehouse_number`,`code_name`),
  ADD KEY `idx_departments_active` (`is_active`);

--
-- Indexes for table `div_mgr_approvals`
--
ALTER TABLE `div_mgr_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_div_mgr_approvals_request` (`service_request_id`),
  ADD KEY `idx_div_mgr_approvals_div_mgr` (`div_mgr_user_id`);

--
-- Indexes for table `document_numbers`
--
ALTER TABLE `document_numbers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_number` (`document_number`),
  ADD UNIQUE KEY `unique_warehouse_code_year_month_running` (`warehouse_number`,`code_name`,`year`,`month`,`running_number`),
  ADD KEY `idx_warehouse_code_year_month` (`warehouse_number`,`code_name`,`year`,`month`),
  ADD KEY `idx_document_number` (`document_number`),
  ADD KEY `idx_running_number` (`running_number`),
  ADD KEY `fk_document_numbers_service_request` (`service_request_id`);

--
-- Indexes for table `document_status_logs`
--
ALTER TABLE `document_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_document_status_logs_request` (`service_request_id`),
  ADD KEY `idx_document_status_logs_reviewer` (`reviewer_id`);

--
-- Indexes for table `gm_approvals`
--
ALTER TABLE `gm_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gm_approvals_request` (`service_request_id`),
  ADD KEY `idx_gm_approvals_gm` (`gm_user_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_programs_active` (`is_active`);

--
-- Indexes for table `request_attachments`
--
ALTER TABLE `request_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request_attachments_service_request` (`service_request_id`);

--
-- Indexes for table `senior_gm_approvals`
--
ALTER TABLE `senior_gm_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_senior_gm_approvals_request` (`service_request_id`),
  ADD KEY `idx_senior_gm_approvals_senior_gm` (`senior_gm_user_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_services_category` (`category`),
  ADD KEY `idx_services_active` (`is_active`);

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_number` (`document_number`),
  ADD KEY `idx_service_requests_user_id` (`user_id`),
  ADD KEY `idx_service_requests_service_id` (`service_id`),
  ADD KEY `idx_service_requests_status` (`status`),
  ADD KEY `idx_service_requests_current_step` (`current_step`),
  ADD KEY `idx_service_requests_work_category` (`work_category`),
  ADD KEY `idx_service_requests_document_number` (`document_number`),
  ADD KEY `idx_service_requests_assigned_div_mgr` (`assigned_div_mgr_id`),
  ADD KEY `idx_service_requests_dev_status` (`developer_status`);

--
-- Indexes for table `subtask_logs`
--
ALTER TABLE `subtask_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subtask_logs_subtask_id` (`subtask_id`),
  ADD KEY `idx_subtask_logs_changed_by` (`changed_by`);

--
-- Indexes for table `subtask_templates`
--
ALTER TABLE `subtask_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tasks_request` (`service_request_id`),
  ADD KEY `idx_tasks_developer` (`developer_user_id`),
  ADD KEY `idx_tasks_status` (`task_status`);

--
-- Indexes for table `task_status_logs`
--
ALTER TABLE `task_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task_status_logs_task` (`task_id`),
  ADD KEY `idx_task_status_logs_changed_by` (`changed_by`);

--
-- Indexes for table `task_subtasks`
--
ALTER TABLE `task_subtasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_task_step` (`task_id`,`step_order`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `line_id` (`line_id`),
  ADD KEY `fk_users_created_by` (`created_by_admin_id`),
  ADD KEY `idx_users_role` (`role`);

--
-- Indexes for table `usertemplate`
--
ALTER TABLE `usertemplate`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_reviews`
--
ALTER TABLE `user_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_reviews_task` (`task_id`),
  ADD KEY `idx_user_reviews_user` (`user_id`),
  ADD KEY `idx_user_reviews_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=577;

--
-- AUTO_INCREMENT for table `assignor_approvals`
--
ALTER TABLE `assignor_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `div_mgr_approvals`
--
ALTER TABLE `div_mgr_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `document_numbers`
--
ALTER TABLE `document_numbers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `document_status_logs`
--
ALTER TABLE `document_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `gm_approvals`
--
ALTER TABLE `gm_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `request_attachments`
--
ALTER TABLE `request_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `senior_gm_approvals`
--
ALTER TABLE `senior_gm_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `subtask_logs`
--
ALTER TABLE `subtask_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT for table `subtask_templates`
--
ALTER TABLE `subtask_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `task_status_logs`
--
ALTER TABLE `task_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=246;

--
-- AUTO_INCREMENT for table `task_subtasks`
--
ALTER TABLE `task_subtasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `usertemplate`
--
ALTER TABLE `usertemplate`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_reviews`
--
ALTER TABLE `user_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
