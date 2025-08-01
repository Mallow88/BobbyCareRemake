-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 29, 2025 at 04:57 AM
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
(186, 1, 'Login', '2025-07-24 01:53:06'),
(187, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-24 01:53:13'),
(188, 1, 'Login', '2025-07-24 02:17:50'),
(189, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 02:18:07'),
(190, 1, 'Login', '2025-07-24 02:28:07'),
(191, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 02:28:27'),
(192, 1, 'Login', '2025-07-24 02:29:32'),
(193, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 02:29:49'),
(194, 1, 'Login', '2025-07-24 02:31:34'),
(195, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 02:31:45'),
(196, 1, 'Login', '2025-07-24 02:33:56'),
(197, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 02:34:06'),
(198, 1, 'Login', '2025-07-24 02:34:57'),
(199, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 02:35:11'),
(200, 1, 'Login', '2025-07-24 02:37:13'),
(201, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 02:37:23'),
(202, 1, 'Login', '2025-07-24 02:38:07'),
(203, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 02:38:18'),
(204, 1, 'Login', '2025-07-24 02:38:31'),
(205, 1, 'Login', '2025-07-24 02:39:45'),
(206, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 02:39:53'),
(207, 1, 'Login', '2025-07-24 02:40:41'),
(208, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 02:40:50'),
(209, 1, 'Login', '2025-07-24 02:42:53'),
(210, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 02:43:03'),
(211, 1, 'Login', '2025-07-24 02:56:04'),
(212, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 02:56:22'),
(213, 1, 'Login', '2025-07-24 02:59:01'),
(214, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 02:59:23'),
(215, 1, 'Login', '2025-07-24 03:03:07'),
(216, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 03:04:00'),
(217, 1, 'Login', '2025-07-24 03:05:32'),
(218, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 03:06:05'),
(219, 1, 'Login', '2025-07-24 03:07:50'),
(220, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 03:08:05'),
(221, 1, 'Login', '2025-07-24 03:09:09'),
(222, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 03:09:20'),
(223, 1, 'Login', '2025-07-24 03:10:30'),
(224, 1, 'Login', '2025-07-24 03:13:03'),
(225, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 03:13:12'),
(226, 1, 'Login', '2025-07-24 04:01:22'),
(227, 1, 'Login', '2025-07-24 04:04:48'),
(228, 1, 'Login', '2025-07-24 04:08:20'),
(229, 1, 'Login', '2025-07-24 04:23:54'),
(230, 1, 'Login', '2025-07-24 04:23:59'),
(231, 1, 'Login', '2025-07-24 06:27:27'),
(232, 1, 'Login', '2025-07-24 06:28:39'),
(233, 1, 'Login', '2025-07-24 06:31:19'),
(234, 1, 'Login', '2025-07-24 06:33:32'),
(235, 1, 'Login', '2025-07-24 06:33:38'),
(236, 1, 'Login', '2025-07-24 06:42:43'),
(237, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-24 06:42:57'),
(238, 1, 'Login', '2025-07-24 06:43:51'),
(239, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-24 06:44:01'),
(240, 1, 'Login', '2025-07-24 06:52:20'),
(241, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-24 06:52:28'),
(242, 1, 'Login', '2025-07-24 07:01:57'),
(243, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-24 07:02:21'),
(244, 1, 'Login', '2025-07-24 07:14:05'),
(245, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-24 07:14:24'),
(246, 1, 'Login', '2025-07-24 07:17:49'),
(247, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-24 07:18:15'),
(248, 1, 'Login', '2025-07-24 07:57:53'),
(249, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-24 07:58:02'),
(250, 1, 'Login', '2025-07-24 08:03:45'),
(251, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 08:04:07'),
(252, 1, 'Login', '2025-07-24 08:07:32'),
(253, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-24 08:07:52'),
(254, 1, 'Login', '2025-07-24 08:25:11'),
(255, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 08:25:25'),
(256, 1, 'Login', '2025-07-24 08:47:12'),
(257, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 08:47:22'),
(258, 1, 'Login', '2025-07-24 09:14:20'),
(259, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 09:14:31'),
(260, 1, 'Login', '2025-07-24 09:19:23'),
(261, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-24 09:19:33'),
(262, 1, 'Login', '2025-07-25 01:24:50'),
(263, 1, 'Login', '2025-07-25 02:34:01'),
(264, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-25 02:34:09'),
(265, 1, 'Login', '2025-07-25 02:38:21'),
(266, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-25 02:38:30'),
(267, 1, 'Login', '2025-07-25 02:40:06'),
(268, 1, 'Login', '2025-07-25 02:40:22'),
(269, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-25 02:40:33'),
(270, 1, 'Login', '2025-07-25 02:41:42'),
(271, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-25 02:41:50'),
(272, 1, 'Login', '2025-07-25 02:43:03'),
(273, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-25 02:43:10'),
(274, 1, 'Login', '2025-07-25 06:25:35'),
(275, 1, 'แก้ไขผู้ใช้ ID 6', '2025-07-25 06:25:56'),
(276, 1, 'Login', '2025-07-25 07:49:40'),
(277, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-25 07:49:58'),
(278, 1, 'Login', '2025-07-25 08:45:15'),
(279, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-25 08:49:10'),
(280, 1, 'Login', '2025-07-25 08:56:31'),
(281, 1, 'Login', '2025-07-25 09:28:59'),
(282, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-25 09:42:42'),
(283, 1, 'Login', '2025-07-25 09:47:41'),
(284, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-25 09:47:48'),
(285, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-25 09:56:55'),
(286, 1, 'Login', '2025-07-25 09:57:39'),
(287, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-25 09:57:49'),
(288, 1, 'Login', '2025-07-25 10:11:09'),
(289, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-25 10:11:18'),
(290, 1, 'Login', '2025-07-26 01:10:55'),
(291, 1, 'Login', '2025-07-26 01:21:55'),
(292, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-26 01:22:03'),
(293, 1, 'Login', '2025-07-29 01:07:49'),
(294, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-29 01:07:55');

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
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignor_approvals`
--

INSERT INTO `assignor_approvals` (`id`, `service_request_id`, `assignor_user_id`, `assigned_developer_id`, `status`, `reason`, `estimated_days`, `priority_level`, `reviewed_at`, `created_at`) VALUES
(2, 25, 1, 1, 'approved', 'ผู้จัดการแผนก2', 24, 'urgent', '2025-07-23 03:07:38', '2025-07-23 03:07:38'),
(4, 28, 1, 1, 'approved', 'ผู้จัดการแผนก2', 72, 'urgent', '2025-07-23 03:32:04', '2025-07-23 03:32:04'),
(6, 23, 1, 1, 'approved', '78787878', 8, 'urgent', '2025-07-23 04:03:17', '2025-07-23 04:03:17'),
(7, 31, 1, 1, 'approved', 'เอกสารผู้จัดการแผนก', 32, 'high', '2025-07-23 08:51:23', '2025-07-23 08:51:23'),
(8, 32, 2, 2, 'approved', 'อนุมัติโดยผู้จัดการแผนก', 29, 'urgent', '2025-07-24 02:33:48', '2025-07-24 02:33:48'),
(9, 29, 2, NULL, 'rejected', '55', NULL, 'medium', '2025-07-24 03:06:48', '2025-07-24 03:06:48'),
(11, 33, 2, 1, 'approved', 'ผู้จัดการแผนก1', 3, 'high', '2025-07-24 03:07:45', '2025-07-24 03:07:45'),
(13, 48, 2, 2, 'approved', 'ผู้จัดการแผนก', 2, 'high', '2025-07-25 02:39:42', '2025-07-25 02:39:42');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `warehouse_number` varchar(10) NOT NULL,
  `code_name` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `warehouse_number`, `code_name`, `created_at`, `is_active`) VALUES
(1, '03', 'TRO', '2025-07-29 02:32:07', 1),
(2, '03', 'PIC', '2025-07-29 02:32:07', 1),
(3, '03', 'DAN', '2025-07-29 02:32:07', 1),
(4, '03', 'RCV', '2025-07-29 02:32:07', 1),
(5, '03', 'BQC', '2025-07-29 02:32:07', 1),
(6, '02', 'PIC', '2025-07-29 02:32:07', 1),
(7, '02', 'LOA', '2025-07-29 02:32:07', 1),
(8, '02', 'TRI', '2025-07-29 02:32:07', 1),
(9, '02', 'RCV', '2025-07-29 02:32:07', 1),
(10, '02', 'DAI', '2025-07-29 02:32:07', 1),
(11, '02', 'TRO', '2025-07-29 02:32:07', 1),
(12, '02', 'MIS', '2025-07-29 02:32:07', 1),
(13, '02', 'CQC', '2025-07-29 02:32:07', 1),
(14, '02', 'SAF', '2025-07-29 02:32:07', 1),
(15, '02', 'DAO', '2025-07-29 02:32:07', 1),
(16, '02', 'ENG', '2025-07-29 02:32:07', 1),
(17, '01', 'RFC', '2025-07-29 02:32:07', 1),
(18, '01', 'LOA', '2025-07-29 02:32:07', 1),
(19, '01', 'TRI', '2025-07-29 02:32:07', 1),
(20, '01', 'SPC', '2025-07-29 02:32:07', 1),
(21, '01', 'SEQ', '2025-07-29 02:32:07', 1),
(22, '01', 'INV', '2025-07-29 02:32:07', 1),
(23, '01', 'RBC', '2025-07-29 02:32:07', 1),
(24, '01', 'RFL', '2025-07-29 02:32:07', 1),
(25, '01', 'DAO', '2025-07-29 02:32:07', 1),
(26, '01', 'RID', '2025-07-29 02:32:07', 1),
(27, '01', 'REV', '2025-07-29 02:32:07', 1),
(28, '01', 'SAF', '2025-07-29 02:32:07', 1),
(29, '01', 'ADM', '2025-07-29 02:32:07', 1),
(30, '01', 'RCN', '2025-07-29 02:32:07', 1),
(31, '01', 'O2O', '2025-07-29 02:32:07', 1),
(32, '01', 'ENG', '2025-07-29 02:32:07', 1),
(33, '01', 'TRP', '2025-07-29 02:32:07', 1),
(34, '01', 'ROD', '2025-07-29 02:32:07', 1),
(35, '01', 'SPD', '2025-07-29 02:32:07', 1),
(36, '01', 'TRO', '2025-07-29 02:32:07', 1),
(37, '01', 'DAI', '2025-07-29 02:32:07', 1),
(38, '01', 'FLA', '2025-07-29 02:32:07', 1),
(39, '01', 'DEV', '2025-07-29 02:32:07', 1),
(40, '01', 'MIS', '2025-07-29 02:32:07', 1);

-- --------------------------------------------------------

--
-- Table structure for table `div_mgr_approvals`
--

CREATE TABLE `div_mgr_approvals` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `div_mgr_user_id` int(11) NOT NULL,
  `status` enum('approved','rejected','pending') DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `div_mgr_approvals`
--

INSERT INTO `div_mgr_approvals` (`id`, `service_request_id`, `div_mgr_user_id`, `status`, `reason`, `reviewed_at`, `created_at`) VALUES
(1, 25, 1, 'approved', 'bolt1ข้อเสนอแนะ', '2025-07-23 02:59:29', '2025-07-23 02:59:29'),
(3, 24, 1, 'rejected', 'asds', '2025-07-23 02:59:55', '2025-07-23 02:59:55'),
(4, 28, 1, 'approved', 'ฝ่าย1', '2025-07-23 03:28:36', '2025-07-23 03:28:36'),
(6, 29, 1, 'approved', '', '2025-07-23 03:58:11', '2025-07-23 03:58:11'),
(7, 23, 1, 'approved', '555', '2025-07-23 03:58:17', '2025-07-23 03:58:17'),
(9, 31, 1, 'approved', 'ผ่าน', '2025-07-23 08:46:10', '2025-07-23 08:46:10'),
(10, 32, 2, 'approved', 'ผู้จัดการฝ่าย อนุมัติ', '2025-07-24 02:29:24', '2025-07-24 02:29:24'),
(11, 33, 2, 'approved', 'ผู้จัดการฝ่าย1', '2025-07-24 03:04:57', '2025-07-24 03:04:57'),
(15, 48, 2, 'approved', 'ผู้จัดการฝ่าย', '2025-07-25 02:38:17', '2025-07-25 02:38:17');

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
(1, 25, 'div_mgr_review', 'approved', 1, 'divmgr', 'bolt1ข้อเสนอแนะ', '2025-07-23 02:59:29'),
(3, 24, 'div_mgr_review', 'rejected', 1, 'divmgr', 'asds', '2025-07-23 02:59:55'),
(5, 25, 'assignor_review', 'approved', 1, 'assignor', 'ผู้จัดการแผนก2', '2025-07-23 03:07:38'),
(6, 25, 'gm_review', 'approved', 1, 'gmapprover', 'GM1', '2025-07-23 03:10:42'),
(8, 25, 'senior_gm_review', 'approved', 1, 'seniorgm', 'ผู้จัดการอาวุโส ผู้จัดการอาวุโส ร่งทำ', '2025-07-23 03:15:07'),
(9, 28, 'div_mgr_review', 'approved', 1, 'divmgr', 'ฝ่าย1', '2025-07-23 03:28:36'),
(12, 28, 'assignor_review', 'approved', 1, 'assignor', 'ผู้จัดการแผนก2', '2025-07-23 03:32:04'),
(13, 28, 'gm_review', 'approved', 1, 'gmapprover', 'อนุมัติคำขอ (GM)1', '2025-07-23 03:33:04'),
(16, 28, 'senior_gm_review', 'approved', 1, 'seniorgm', 'ผู้จัดการอาวุโส2 ทำสะ', '2025-07-23 03:34:30'),
(17, 29, 'div_mgr_review', 'approved', 1, 'divmgr', '', '2025-07-23 03:58:11'),
(18, 23, 'div_mgr_review', 'approved', 1, 'divmgr', '555', '2025-07-23 03:58:17'),
(21, 23, 'assignor_review', 'approved', 1, 'assignor', '78787878', '2025-07-23 04:03:17'),
(22, 23, 'gm_review', 'approved', 1, 'gmapprover', '14564', '2025-07-23 04:04:23'),
(25, 23, 'senior_gm_review', 'approved', 1, 'seniorgm', '489456 464', '2025-07-23 04:05:14'),
(26, 31, 'div_mgr_review', 'approved', 1, 'divmgr', 'ผ่าน', '2025-07-23 08:46:10'),
(27, 31, 'assignor_review', 'approved', 1, 'assignor', 'เอกสารผู้จัดการแผนก', '2025-07-23 08:51:23'),
(28, 31, 'gm_review', 'approved', 1, 'gmapprover', 'เอกสาร อนุมัติคำขอ (GM)', '2025-07-23 08:53:41'),
(29, 31, 'senior_gm_review', 'approved', 1, 'seniorgm', 'ผู้จัดการอาวุโส เอกสาร รีบๆทำเนาะ', '2025-07-23 08:56:19'),
(30, 32, 'div_mgr_review', 'approved', 2, 'divmgr', 'ผู้จัดการฝ่าย อนุมัติ', '2025-07-24 02:29:24'),
(31, 32, 'assignor_review', 'approved', 2, 'assignor', 'อนุมัติโดยผู้จัดการแผนก', '2025-07-24 02:33:48'),
(32, 32, 'gm_review', 'approved', 2, 'gmapprover', 'อนุมัติคำขอ (GM)', '2025-07-24 02:37:04'),
(33, 32, 'senior_gm_review', 'approved', 2, 'seniorgm', 'พิจารณาคำขอขั้นสุดท้าย หมายเหตุสำหรับผู้พัฒนา:\r\n55', '2025-07-24 02:39:41'),
(34, 33, 'div_mgr_review', 'approved', 2, 'divmgr', 'ผู้จัดการฝ่าย1', '2025-07-24 03:04:57'),
(36, 29, 'assignor_review', 'rejected', 2, 'assignor', '55', '2025-07-24 03:06:48'),
(38, 33, 'assignor_review', 'approved', 2, 'assignor', 'ผู้จัดการแผนก1', '2025-07-24 03:07:45'),
(39, 33, 'gm_review', 'approved', 2, 'gmapprover', 'ผู้จัดการทั่วไป1', '2025-07-24 03:08:51'),
(42, 33, 'senior_gm_review', 'approved', 2, 'seniorgm', 'ผู้จัดการอาวุโส2 ผู้จัดการอาวุโส222', '2025-07-24 03:10:10'),
(48, 48, 'div_mgr_review', 'approved', 2, 'divmgr', 'ผู้จัดการฝ่าย', '2025-07-25 02:38:17'),
(49, 48, 'assignor_review', 'approved', 2, 'assignor', 'ผู้จัดการแผนก', '2025-07-25 02:39:42'),
(51, 48, 'gm_review', 'approved', 2, 'gmapprover', 'อนุมัติคำขอ (GM)', '2025-07-25 02:41:21'),
(53, 48, 'senior_gm_review', 'approved', 2, 'seniorgm', 'ผู้จัดการอาวุโส ผู้จัดการอาวุโสหมายเหตุสำหรับผู้พัฒนา', '2025-07-25 02:42:45');

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
(1, 25, 1, 'approved', 'GM1', 20000.00, '2025-07-23 03:10:42', '2025-07-23 03:10:42'),
(3, 28, 1, 'approved', 'อนุมัติคำขอ (GM)1', 20000.00, '2025-07-23 03:33:04', '2025-07-23 03:33:04'),
(5, 23, 1, 'approved', '14564', 20000.00, '2025-07-23 04:04:23', '2025-07-23 04:04:23'),
(7, 31, 1, 'approved', 'เอกสาร อนุมัติคำขอ (GM)', 20000.00, '2025-07-23 08:53:41', '2025-07-23 08:53:41'),
(8, 32, 2, 'approved', 'อนุมัติคำขอ (GM)', 200000.00, '2025-07-24 02:37:04', '2025-07-24 02:37:04'),
(9, 33, 2, 'approved', 'ผู้จัดการทั่วไป1', 30000.00, '2025-07-24 03:08:51', '2025-07-24 03:08:51'),
(12, 48, 2, 'approved', 'อนุมัติคำขอ (GM)', 2000000.00, '2025-07-25 02:41:21', '2025-07-25 02:41:21');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `name`) VALUES
(1, 'Admin Booking จองห้องประชุม, รถ ,เลขที่บันทึก'),
(2, 'ARM Share'),
(3, 'Battery Mangement'),
(4, 'BESAFE'),
(5, 'Break Case'),
(6, 'Consignment RDC'),
(7, 'Consignment CDC'),
(8, 'Consignment RDC Monitor'),
(9, 'COVID-19'),
(10, 'ALL Dashboard'),
(11, 'DATA CENTER'),
(12, 'DATA ENTRY'),
(13, 'Friend\'s Corner'),
(14, 'Full Case'),
(15, 'iMove'),
(16, 'LABEL RDC'),
(17, 'LABEL CDC'),
(18, 'Loading'),
(19, 'Location Hold'),
(20, 'LORA'),
(21, 'Magic Location'),
(22, 'MAYYA'),
(23, 'Mermaid'),
(24, 'NRS'),
(25, 'O2O RDC'),
(26, 'O2O CDC'),
(27, 'Organization Development'),
(28, 'PICK & LOAD'),
(29, 'Pick Dang'),
(30, 'PIMMY CDC'),
(31, 'PIMMY RDC'),
(32, 'POP & FOP'),
(33, 'Product'),
(34, 'Progress RDC'),
(35, 'RECEIVE CDC'),
(36, 'RECEIVE CHECK-IN'),
(37, 'Repick'),
(38, 'Replenishment'),
(39, 'STOCK Engineer'),
(40, 'TO BE SHOP CDC'),
(41, 'TO BE SHOP INVENTORY'),
(42, 'TO BE SHOP RDC'),
(43, 'Transporter RDC'),
(44, 'Transporter CDC'),
(45, 'TRUCK-AGE'),
(46, 'VSM'),
(47, 'ADMIN ตรวจนับทรัพย์สิน'),
(48, 'ADMIN เบิกวัสดุอุปกรณ์สิ้นเปลือง CDC'),
(49, 'ADMIN เบิกวัสดุอุปกรณ์สิ้นเปลือง RDC'),
(50, 'รักษ์ RACK'),
(51, 'รักษ์ รถ'),
(52, 'Remaining Check Stock (RCS)');

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
(1, 31, '131317.pdf', '31_1753260224_0.pdf', 59743, 'pdf', '2025-07-23 08:43:44'),
(2, 32, '131317.pdf', '32_1753324053_0.pdf', 59743, 'pdf', '2025-07-24 02:27:33'),
(3, 33, '131162.jpg', '33_1753326089_0.jpg', 113831, 'jpg', '2025-07-24 03:01:29'),
(7, 36, '131317.jpg', '36_1753326147_0.jpg', 82227, 'jpg', '2025-07-24 03:02:27'),
(8, 36, '131317.pdf', '36_1753326147_1.pdf', 59743, 'pdf', '2025-07-24 03:02:27'),
(9, 37, '131162.jpg', '37_1753326163_0.jpg', 113831, 'jpg', '2025-07-24 03:02:43'),
(11, 48, '131317.jpg', '48_1753411052_0.jpg', 82227, 'jpg', '2025-07-25 02:37:32'),
(12, 48, '131317.pdf', '48_1753411052_1.pdf', 59743, 'pdf', '2025-07-25 02:37:32'),
(13, 49, 'เมล์แจ้งรายละเอียดก่อนเริ่มงาน.pdf', '49_1753429575_0.pdf', 405166, 'pdf', '2025-07-25 07:46:15'),
(14, 49, '131317 (1).pdf', '49_1753429575_1.pdf', 59743, 'pdf', '2025-07-25 07:46:15'),
(15, 49, '131317.pdf', '49_1753429575_2.pdf', 59743, 'pdf', '2025-07-25 07:46:15'),
(16, 54, '131317.pdf', '54_1753683166_0.pdf', 59743, 'pdf', '2025-07-28 06:12:46'),
(17, 54, 'ใบเสร็จค่าเดินทาง.pdf', '54_1753683166_1.pdf', 102535, 'pdf', '2025-07-28 06:12:46');

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
(1, 25, 1, 'approved', 'ผู้จัดการอาวุโส', 'ผู้จัดการอาวุโส ร่งทำ', '2025-07-23 03:15:07', '2025-07-23 03:15:07'),
(3, 28, 1, 'approved', 'ผู้จัดการอาวุโส2', 'ทำสะ', '2025-07-23 03:34:30', '2025-07-23 03:34:30'),
(5, 23, 1, 'approved', '489456', '464', '2025-07-23 04:05:14', '2025-07-23 04:05:14'),
(6, 31, 1, 'approved', 'ผู้จัดการอาวุโส เอกสาร', 'รีบๆทำเนาะ', '2025-07-23 08:56:19', '2025-07-23 08:56:19'),
(7, 32, 2, 'approved', 'พิจารณาคำขอขั้นสุดท้าย', 'หมายเหตุสำหรับผู้พัฒนา:\r\n55', '2025-07-24 02:39:41', '2025-07-24 02:39:41'),
(9, 33, 2, 'approved', 'ผู้จัดการอาวุโส2', 'ผู้จัดการอาวุโส222', '2025-07-24 03:10:10', '2025-07-24 03:10:10'),
(11, 48, 2, 'approved', 'ผู้จัดการอาวุโส', 'ผู้จัดการอาวุโสหมายเหตุสำหรับผู้พัฒนา', '2025-07-25 02:42:45', '2025-07-25 02:42:45');

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
(1, 'โปรแกรมใหม่', 'development', 1, '2025-07-24 08:16:23'),
(2, 'โปรแกรมเดิม (แก้ปัญหา)', 'development', 1, '2025-07-24 08:16:23'),
(3, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 'development', 1, '2025-07-24 08:16:23'),
(4, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 'development', 1, '2025-07-24 08:16:23'),
(5, 'โปรแกรมเดิม (ตกแต่ง)', 'development', 1, '2025-07-24 08:16:23'),
(6, 'Handheld / RF', 'service', 1, '2025-07-24 08:16:23'),
(7, 'Computer / Notebook', 'service', 1, '2025-07-24 08:16:23'),
(8, 'Microsoft 365 (Microsoft Word,Excel,PowerPoint,andOutlook)', 'service', 1, '2025-07-24 08:16:23'),
(9, 'Network', 'service', 1, '2025-07-24 08:16:23'),
(10, 'Printer', 'service', 1, '2025-07-24 08:16:23'),
(11, 'CCTV', 'service', 1, '2025-07-24 08:16:23'),
(12, 'AccessDoor', 'service', 1, '2025-07-24 08:16:23'),
(13, 'เครื่องเสียง', 'service', 1, '2025-07-24 08:16:23'),
(14, 'อื่นๆ', 'service', 1, '2025-07-24 08:16:23');

-- --------------------------------------------------------

--
-- Table structure for table `service_requests`
--

CREATE TABLE `service_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','div_mgr_review','assignor_review','gm_review','senior_gm_review','approved','rejected','developer_assigned','in_progress','completed') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `assigned_to_admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `current_step` varchar(50) DEFAULT 'user_submitted',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `estimated_days` int(11) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `developer_status` enum('not_assigned','pending','received','in_progress','on_hold','completed','user_review','accepted') DEFAULT 'not_assigned',
  `work_category` enum('RDC','CDC','BDC') DEFAULT NULL,
  `expected_benefits` text DEFAULT NULL,
  `assigned_div_mgr_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `current_workflow` text DEFAULT NULL COMMENT 'ขั้นตอนการทำงานเดิม',
  `approach_ideas` text DEFAULT NULL COMMENT 'แนวทาง/ไอเดีย',
  `related_programs` text DEFAULT NULL COMMENT 'โปรแกรมที่คาดว่าจะเกี่ยวข้อง',
  `current_tools` text DEFAULT NULL COMMENT 'ปกติใช้โปรแกรมอะไรทำงานอยู่',
  `system_impact` text DEFAULT NULL COMMENT 'ถ้ากรณีต้องระบบหรือปิด Server จะกระทบต่อกระบวนการอะไรบ้าง',
  `related_documents` text DEFAULT NULL COMMENT 'เอกสารการทำงานที่เกี่ยวข้อง'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_requests`
--

INSERT INTO `service_requests` (`id`, `user_id`, `title`, `description`, `status`, `rejection_reason`, `assigned_to_admin_id`, `created_at`, `updated_at`, `current_step`, `priority`, `estimated_days`, `deadline`, `developer_status`, `work_category`, `expected_benefits`, `assigned_div_mgr_id`, `service_id`, `employee_id`, `current_workflow`, `approach_ideas`, `related_programs`, `current_tools`, `system_impact`, `related_documents`) VALUES
(23, 1, 'D44', 'DDD', 'approved', NULL, NULL, '2025-07-22 09:09:23', '2025-07-24 10:09:15', 'senior_gm_approved', 'urgent', 8, NULL, 'received', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(24, 1, 'E55', '55', 'rejected', NULL, NULL, '2025-07-22 09:09:30', '2025-07-23 02:59:55', 'div_mgr_rejected', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(25, 1, 'ทดสอบbolt', 'bolt1', 'approved', NULL, NULL, '2025-07-23 02:57:11', '2025-07-23 03:15:07', 'senior_gm_approved', 'urgent', 24, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(28, 1, 'ระบบล่ม', 'เชื่อมเนตไม่ได้', 'approved', NULL, NULL, '2025-07-23 03:26:41', '2025-07-23 04:25:00', 'senior_gm_approved', 'urgent', 72, NULL, 'accepted', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(29, 1, '123', '123', 'rejected', NULL, NULL, '2025-07-23 03:57:22', '2025-07-24 03:06:48', 'assignor_rejected', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(30, 1, 'ทดเอกสารไฟล์', 'ทดเอกสารไฟล์', 'pending', NULL, NULL, '2025-07-23 08:29:26', '2025-07-23 08:29:26', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(31, 1, 'เอกสาร1', 'เอกสาร11', 'approved', NULL, NULL, '2025-07-23 08:43:44', '2025-07-23 09:05:53', 'senior_gm_approved', 'high', 32, NULL, 'accepted', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(32, 2, 'ทดสอบวันที่24', 'ทดสอบรายละเอียดวันที่24', 'approved', NULL, NULL, '2025-07-24 02:27:33', '2025-07-24 02:44:25', 'senior_gm_approved', 'urgent', 29, NULL, 'accepted', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(33, 2, 'เทส1', 'เทส11', 'approved', NULL, NULL, '2025-07-24 03:01:29', '2025-07-24 06:35:23', 'senior_gm_approved', 'high', 3, NULL, 'accepted', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(36, 2, 'เทส4', 'เทส444', 'pending', NULL, NULL, '2025-07-24 03:02:27', '2025-07-24 03:02:27', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(37, 2, 'เทส5', 'เทส55', 'pending', NULL, NULL, '2025-07-24 03:02:43', '2025-07-24 03:02:43', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(39, 1, 'ทดสอบ', 'ทดสอบครับ44', 'approved', NULL, NULL, '2025-07-24 09:55:07', '2025-07-24 10:09:10', 'developer_self_created', 'high', 4, '2025-07-25', 'received', NULL, NULL, NULL, 12, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(40, 1, '56456', '4564564', 'approved', NULL, NULL, '2025-07-24 10:03:23', '2025-07-24 10:09:20', 'developer_self_created', 'high', 6, '0000-00-00', 'in_progress', NULL, NULL, NULL, 9, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(41, 1, '561456', '564', 'approved', NULL, NULL, '2025-07-24 10:04:52', '2025-07-24 10:09:16', 'developer_self_created', 'urgent', 1, '2025-07-25', 'in_progress', NULL, NULL, NULL, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(42, 2, 'ทดสอบ', 'ไฟดับ', 'approved', NULL, NULL, '2025-07-25 01:30:41', '2025-07-25 02:49:16', 'developer_self_created', 'high', 1, '2025-07-25', 'in_progress', NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(43, 2, 'ทดสอบ', 'ไฟดับ', 'approved', NULL, NULL, '2025-07-25 01:39:09', '2025-07-25 01:39:09', 'developer_self_created', 'high', 1, '2025-07-25', 'received', NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(44, 2, 'ทดสอบ', 'ไฟดับ', 'approved', NULL, NULL, '2025-07-25 01:39:13', '2025-07-25 01:39:13', 'developer_self_created', 'high', 1, '2025-07-25', 'received', NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(45, 2, 'ทดสอบ', 'ไฟดับ', 'approved', NULL, NULL, '2025-07-25 01:39:19', '2025-07-25 01:39:19', 'developer_self_created', 'high', 1, '2025-07-25', 'received', NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(46, 2, 'เทส', 'งานนะ', 'approved', NULL, NULL, '2025-07-25 02:11:15', '2025-07-25 10:07:38', 'developer_self_created', 'urgent', 3, '2025-07-27', 'completed', NULL, NULL, NULL, 9, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(48, 2, 'เเก้ระบบbobby', 'เข้าระบบbobbyไม่ได้', 'approved', NULL, NULL, '2025-07-25 02:37:32', '2025-07-25 10:07:13', 'senior_gm_approved', 'high', 2, NULL, 'received', 'RDC', 'กลับมาเริ่มงานได้', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(49, 7, 'พพพพ', 'รรรร', 'div_mgr_review', NULL, NULL, '2025-07-25 07:46:15', '2025-07-25 07:46:15', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', 'RDC', 'นนนนนนนนนน', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(50, 2, 'เน็ตช้าา', 'หน้างานเข้าไม่ได้', 'approved', NULL, NULL, '2025-07-25 07:58:09', '2025-07-25 10:01:06', 'developer_self_created', 'urgent', 3, '2025-07-30', 'received', NULL, NULL, NULL, 9, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(51, 2, 'เน็ตช้าา', 'หน้างานเข้าไม่ได้', 'approved', NULL, NULL, '2025-07-25 07:58:18', '2025-07-25 07:58:18', 'developer_self_created', 'urgent', 3, '2025-07-30', 'received', NULL, NULL, NULL, 9, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(52, 2, 'เน็ตช้าา', 'หน้างานเข้าไม่ได้', 'approved', NULL, NULL, '2025-07-25 07:58:28', '2025-07-25 07:58:28', 'developer_self_created', 'urgent', 3, '2025-07-30', 'received', NULL, NULL, NULL, 9, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(53, 2, 'ggg', 'ggg', 'approved', NULL, NULL, '2025-07-25 10:00:52', '2025-07-25 10:10:39', 'developer_self_created', 'urgent', 2, '2025-07-25', 'on_hold', NULL, NULL, NULL, 10, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(54, 2, 'โปรเเกรมคำนวณสินค้า', 'โปรเเกรมที่สามารถคำนวณได้อัตโนมัติ', 'div_mgr_review', NULL, NULL, '2025-07-28 06:12:46', '2025-07-28 06:12:46', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', 'RDC', 'อยากมารถทำงานได้ไวขึ้น', 2, 1, NULL, 'ใช้โปรเเกรมคิดเอง', 'อยากได้ที่คำนวณได้อัตโนมัติ', 'ไม่ทราบครับ', 'word', 'bobbyบางโปรเเกรม', '');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `accepted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `service_request_id`, `developer_user_id`, `task_status`, `progress_percentage`, `developer_notes`, `started_at`, `completed_at`, `estimated_completion`, `actual_hours`, `created_at`, `updated_at`, `accepted_at`) VALUES
(2, 28, 1, 'accepted', 100, NULL, '2025-07-23 03:35:44', NULL, NULL, NULL, '2025-07-23 03:34:30', '2025-07-23 04:25:00', '2025-07-23 10:35:44'),
(4, 23, 1, 'received', 10, '', '2025-07-24 06:56:51', '2025-07-24 06:57:27', NULL, NULL, '2025-07-23 04:05:14', '2025-07-24 10:09:15', '2025-07-24 13:56:51'),
(5, 31, 1, 'accepted', 100, '', NULL, '2025-07-23 09:04:35', NULL, NULL, '2025-07-23 08:56:19', '2025-07-23 09:05:53', NULL),
(6, 32, 2, 'accepted', 100, '', NULL, '2025-07-24 02:42:34', NULL, NULL, '2025-07-24 02:39:41', '2025-07-24 02:44:25', NULL),
(8, 33, 1, 'accepted', 100, '', '2025-07-24 03:12:15', '2025-07-24 06:33:24', NULL, NULL, '2025-07-24 03:10:10', '2025-07-24 06:35:23', '2025-07-24 10:12:15'),
(10, 39, 1, 'received', 10, '', '2025-07-24 09:55:07', '2025-07-24 09:55:21', '2025-07-25', NULL, '2025-07-24 09:55:07', '2025-07-24 10:09:10', '2025-07-24 16:55:07'),
(11, 40, 1, 'in_progress', 50, '', '2025-07-24 10:03:23', NULL, '2025-07-30', NULL, '2025-07-24 10:03:23', '2025-07-24 10:09:20', '2025-07-24 17:03:23'),
(12, 41, 1, 'in_progress', 50, '', '2025-07-24 10:04:52', '2025-07-24 10:05:04', '2025-07-25', NULL, '2025-07-24 10:04:52', '2025-07-24 10:09:16', '2025-07-24 17:04:52'),
(13, 42, 2, 'in_progress', 50, '', '2025-07-25 01:30:41', NULL, '2025-07-25', NULL, '2025-07-25 01:30:41', '2025-07-25 02:49:16', '2025-07-25 08:30:41'),
(17, 46, 2, 'completed', 100, '', '2025-07-25 02:11:15', '2025-07-25 10:07:38', '2025-07-27', NULL, '2025-07-25 02:11:15', '2025-07-25 10:07:38', '2025-07-25 09:11:15'),
(19, 48, 2, 'received', 10, '', '2025-07-25 02:43:26', '2025-07-25 02:57:15', NULL, NULL, '2025-07-25 02:42:45', '2025-07-25 10:07:13', '2025-07-25 09:43:26'),
(21, 50, 2, 'received', 10, '', '2025-07-25 07:58:09', NULL, '2025-07-30', NULL, '2025-07-25 07:58:09', '2025-07-25 10:01:06', '2025-07-25 14:58:09'),
(24, 53, 2, 'on_hold', 30, '', '2025-07-25 10:00:52', NULL, '2025-07-25', NULL, '2025-07-25 10:00:52', '2025-07-25 10:10:40', '2025-07-25 17:00:52');

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
(1, 2, 'pending', 'received', 1, 'งานได้รับการยอมรับโดยผู้พัฒนา', '2025-07-23 03:35:44'),
(3, 4, 'pending', 'received', 1, 'งานได้รับการยอมรับโดยผู้พัฒนา', '2025-07-23 04:06:11'),
(6, 4, 'received', 'in_progress', 1, '', '2025-07-23 04:06:33'),
(7, 2, 'completed', 'accepted', 1, 'ปิดงานครับ', '2025-07-23 04:25:00'),
(9, 4, '', 'received', 1, '', '2025-07-23 04:35:09'),
(13, 4, '', 'in_progress', 1, '', '2025-07-23 04:42:38'),
(17, 4, '', 'on_hold', 1, '', '2025-07-23 04:49:08'),
(18, 4, '', 'completed', 1, '', '2025-07-24 04:49:12'),
(25, 5, '', 'received', 1, '', '2025-07-23 08:57:47'),
(26, 5, '', 'pending', 1, '', '2025-07-23 08:58:06'),
(27, 5, '', 'pending', 1, '', '2025-07-23 08:59:10'),
(28, 5, '', 'received', 1, '', '2025-07-23 08:59:14'),
(29, 5, '', 'received', 1, '', '2025-07-23 08:59:18'),
(30, 5, '', 'received', 1, '', '2025-07-23 08:59:25'),
(31, 5, '', 'in_progress', 1, '', '2025-07-23 08:59:29'),
(32, 5, '', 'on_hold', 1, '', '2025-07-23 08:59:33'),
(33, 5, '', 'in_progress', 1, '', '2025-07-23 08:59:39'),
(34, 5, '', 'on_hold', 1, '', '2025-07-23 08:59:44'),
(35, 5, '', 'pending', 1, '', '2025-07-23 09:01:02'),
(36, 5, '', 'received', 1, '', '2025-07-23 09:04:02'),
(37, 5, '', 'in_progress', 1, '', '2025-07-23 09:04:12'),
(38, 5, '', 'on_hold', 1, '', '2025-07-23 09:04:21'),
(39, 5, '', 'completed', 1, '', '2025-07-23 09:04:35'),
(40, 5, 'completed', 'accepted', 1, 'ดีๆ', '2025-07-23 09:05:53'),
(41, 4, '', 'pending', 1, '', '2025-07-23 09:42:22'),
(42, 4, '', 'completed', 1, '', '2025-07-23 09:42:26'),
(48, 4, '', 'in_progress', 1, '', '2025-07-24 01:51:48'),
(49, 4, '', 'received', 1, '', '2025-07-24 01:51:59'),
(50, 4, '', 'pending', 1, '', '2025-07-24 01:52:06'),
(51, 4, '', 'received', 1, '', '2025-07-24 01:52:13'),
(52, 4, '', 'in_progress', 1, '', '2025-07-24 01:52:23'),
(53, 4, '', 'received', 1, '', '2025-07-24 01:52:58'),
(54, 6, '', 'received', 2, '', '2025-07-24 02:41:22'),
(55, 6, '', 'in_progress', 2, '', '2025-07-24 02:42:10'),
(56, 6, '', 'completed', 2, '', '2025-07-24 02:42:34'),
(57, 6, 'completed', 'accepted', 2, 'ดีครับ', '2025-07-24 02:44:25'),
(62, 8, 'pending', 'received', 1, 'งานได้รับการยอมรับโดยผู้พัฒนา', '2025-07-24 03:12:15'),
(63, 8, '', 'in_progress', 1, '', '2025-07-24 03:12:25'),
(66, 8, '', 'on_hold', 1, '', '2025-07-24 03:53:09'),
(67, 8, '', 'in_progress', 1, '', '2025-07-24 03:53:15'),
(68, 8, '', 'on_hold', 1, '', '2025-07-24 06:10:03'),
(69, 8, '', 'in_progress', 1, '', '2025-07-24 06:33:09'),
(70, 8, '', 'on_hold', 1, '', '2025-07-24 06:33:12'),
(71, 8, '', 'on_hold', 1, '', '2025-07-24 06:33:19'),
(72, 8, '', 'completed', 1, '', '2025-07-24 06:33:24'),
(73, 8, 'completed', 'accepted', 2, '3645345', '2025-07-24 06:35:23'),
(74, 4, '', 'pending', 1, '', '2025-07-24 06:53:09'),
(75, 4, '', 'received', 1, '', '2025-07-24 06:53:26'),
(76, 4, '', 'pending', 1, '', '2025-07-24 06:53:38'),
(77, 4, 'pending', 'received', 1, 'งานได้รับการยอมรับโดยผู้พัฒนา', '2025-07-24 06:56:51'),
(78, 4, '', 'in_progress', 1, '', '2025-07-24 06:57:01'),
(79, 4, '', 'on_hold', 1, '', '2025-07-24 06:57:07'),
(80, 4, '', 'on_hold', 1, '', '2025-07-24 06:57:16'),
(81, 4, '', 'in_progress', 1, '', '2025-07-24 06:57:19'),
(82, 4, '', 'completed', 1, '1531351', '2025-07-24 06:57:27'),
(86, 10, '', 'in_progress', 1, '', '2025-07-24 09:55:15'),
(87, 10, '', 'on_hold', 1, '', '2025-07-24 09:55:20'),
(88, 10, '', 'completed', 1, '', '2025-07-24 09:55:21'),
(89, 10, '', 'received', 1, '', '2025-07-24 09:55:23'),
(94, 10, '', 'pending', 1, '', '2025-07-24 09:55:46'),
(95, 10, '', 'received', 1, '', '2025-07-24 09:55:52'),
(96, 10, '', 'in_progress', 1, '', '2025-07-24 09:56:55'),
(97, 10, '', 'in_progress', 1, '', '2025-07-24 10:00:54'),
(98, 10, '', 'on_hold', 1, '', '2025-07-24 10:00:55'),
(99, 10, '', 'in_progress', 1, '', '2025-07-24 10:00:56'),
(101, 11, '', 'received', 1, '', '2025-07-24 10:03:31'),
(102, 11, '', 'received', 1, '', '2025-07-24 10:03:32'),
(103, 10, '', 'received', 1, '', '2025-07-24 10:03:33'),
(104, 10, '', 'in_progress', 1, '', '2025-07-24 10:03:34'),
(105, 11, '', 'received', 1, '', '2025-07-24 10:03:35'),
(106, 11, '', 'in_progress', 1, '', '2025-07-24 10:03:36'),
(108, 11, '', 'received', 1, '', '2025-07-24 10:03:40'),
(109, 10, '', 'received', 1, '', '2025-07-24 10:03:40'),
(111, 11, '', 'received', 1, '', '2025-07-24 10:03:46'),
(113, 11, '', 'in_progress', 1, '', '2025-07-24 10:03:50'),
(115, 10, '', 'in_progress', 1, '', '2025-07-24 10:04:08'),
(116, 12, '', 'in_progress', 1, '', '2025-07-24 10:04:56'),
(117, 11, '', 'on_hold', 1, '', '2025-07-24 10:04:58'),
(118, 11, '', 'in_progress', 1, '', '2025-07-24 10:04:59'),
(119, 12, '', 'on_hold', 1, '', '2025-07-24 10:05:02'),
(120, 12, '', 'completed', 1, '', '2025-07-24 10:05:04'),
(123, 11, '', 'received', 1, '', '2025-07-24 10:06:14'),
(125, 10, '', 'received', 1, '', '2025-07-24 10:06:16'),
(126, 11, '', 'in_progress', 1, '', '2025-07-24 10:06:17'),
(127, 11, '', 'received', 1, '', '2025-07-24 10:06:28'),
(128, 11, '', 'in_progress', 1, '', '2025-07-24 10:06:29'),
(129, 12, '', 'on_hold', 1, '', '2025-07-24 10:06:31'),
(130, 4, '', 'on_hold', 1, '', '2025-07-24 10:06:33'),
(131, 4, '', 'on_hold', 1, '', '2025-07-24 10:06:35'),
(132, 11, '', 'pending', 1, '', '2025-07-24 10:06:42'),
(133, 10, '', 'pending', 1, '', '2025-07-24 10:06:43'),
(134, 11, '', 'received', 1, '', '2025-07-24 10:06:44'),
(135, 11, '', 'in_progress', 1, '', '2025-07-24 10:06:55'),
(136, 11, '', 'received', 1, '', '2025-07-24 10:06:56'),
(137, 11, '', 'in_progress', 1, '', '2025-07-24 10:06:57'),
(139, 11, '', 'received', 1, '', '2025-07-24 10:07:00'),
(141, 11, '', 'in_progress', 1, '', '2025-07-24 10:07:02'),
(161, 4, '', 'on_hold', 1, '', '2025-07-24 10:07:43'),
(175, 11, '', 'received', 1, '', '2025-07-24 10:08:11'),
(177, 11, '', 'received', 1, '', '2025-07-24 10:08:13'),
(178, 11, '', 'in_progress', 1, '', '2025-07-24 10:08:14'),
(179, 11, '', 'received', 1, '', '2025-07-24 10:08:15'),
(182, 11, '', 'in_progress', 1, '', '2025-07-24 10:08:17'),
(183, 11, '', 'received', 1, '', '2025-07-24 10:08:19'),
(186, 11, '', 'in_progress', 1, '', '2025-07-24 10:08:22'),
(188, 11, '', 'on_hold', 1, '', '2025-07-24 10:08:28'),
(191, 10, '', 'received', 1, '', '2025-07-24 10:08:44'),
(192, 12, '', 'received', 1, '', '2025-07-24 10:08:46'),
(194, 11, '', 'in_progress', 1, '', '2025-07-24 10:08:48'),
(195, 4, '', 'in_progress', 1, '', '2025-07-24 10:08:49'),
(197, 4, '', 'received', 1, '', '2025-07-24 10:08:53'),
(198, 10, '', 'pending', 1, '', '2025-07-24 10:08:59'),
(199, 12, '', 'pending', 1, '', '2025-07-24 10:09:00'),
(201, 4, '', 'in_progress', 1, '', '2025-07-24 10:09:06'),
(202, 12, '', 'received', 1, '', '2025-07-24 10:09:09'),
(203, 10, '', 'received', 1, '', '2025-07-24 10:09:10'),
(204, 11, '', 'received', 1, '', '2025-07-24 10:09:11'),
(206, 4, '', 'received', 1, '', '2025-07-24 10:09:15'),
(207, 12, '', 'in_progress', 1, '', '2025-07-24 10:09:16'),
(208, 11, '', 'in_progress', 1, '', '2025-07-24 10:09:20'),
(211, 13, '', 'in_progress', 2, '', '2025-07-25 01:40:34'),
(212, 13, '', 'received', 2, '', '2025-07-25 01:40:36'),
(213, 13, '', 'in_progress', 2, '', '2025-07-25 01:40:37'),
(214, 13, '', 'received', 2, '', '2025-07-25 01:40:38'),
(215, 13, '', 'in_progress', 2, '', '2025-07-25 01:40:39'),
(216, 13, '', 'received', 2, '', '2025-07-25 01:40:40'),
(217, 13, '', 'in_progress', 2, '', '2025-07-25 01:40:40'),
(218, 13, '', 'on_hold', 2, '', '2025-07-25 01:40:41'),
(219, 13, '', 'in_progress', 2, '', '2025-07-25 01:40:43'),
(220, 13, '', 'received', 2, '', '2025-07-25 01:40:44'),
(221, 13, '', 'pending', 2, '', '2025-07-25 01:40:45'),
(222, 13, '', 'in_progress', 2, '', '2025-07-25 01:40:47'),
(223, 13, '', 'on_hold', 2, '', '2025-07-25 01:40:58'),
(224, 13, '', 'in_progress', 2, '', '2025-07-25 01:40:59'),
(225, 13, '', 'on_hold', 2, '', '2025-07-25 01:41:38'),
(226, 17, '', 'on_hold', 2, '', '2025-07-25 02:12:57'),
(227, 13, '', 'on_hold', 2, '', '2025-07-25 02:13:02'),
(228, 13, '', 'on_hold', 2, '', '2025-07-25 02:13:03'),
(229, 13, '', 'on_hold', 2, '', '2025-07-25 02:13:05'),
(230, 17, '', 'on_hold', 2, '', '2025-07-25 02:13:06'),
(231, 17, '', 'in_progress', 2, '', '2025-07-25 02:13:20'),
(232, 13, '', 'in_progress', 2, '', '2025-07-25 02:13:21'),
(235, 17, '', 'in_progress', 2, '', '2025-07-25 02:13:27'),
(237, 17, '', 'in_progress', 2, '', '2025-07-25 02:13:28'),
(238, 17, '', 'in_progress', 2, '', '2025-07-25 02:32:00'),
(239, 13, '', 'in_progress', 2, '', '2025-07-25 02:32:01'),
(241, 13, '', 'received', 2, '', '2025-07-25 02:33:43'),
(242, 17, '', 'received', 2, '', '2025-07-25 02:33:44'),
(243, 17, '', 'received', 2, '', '2025-07-25 02:33:45'),
(244, 17, '', 'received', 2, '', '2025-07-25 02:33:46'),
(246, 13, '', 'received', 2, '', '2025-07-25 02:33:47'),
(247, 17, '', 'on_hold', 2, '', '2025-07-25 02:33:49'),
(248, 17, '', 'in_progress', 2, '', '2025-07-25 02:33:50'),
(249, 19, 'pending', 'received', 2, 'งานได้รับการยอมรับโดยผู้พัฒนา', '2025-07-25 02:43:26'),
(250, 19, '', 'in_progress', 2, '', '2025-07-25 02:49:07'),
(252, 17, '', 'in_progress', 2, '', '2025-07-25 02:49:10'),
(254, 19, '', 'in_progress', 2, '', '2025-07-25 02:49:15'),
(255, 13, '', 'in_progress', 2, '', '2025-07-25 02:49:16'),
(256, 19, '', 'received', 2, '', '2025-07-25 02:49:18'),
(257, 17, '', 'in_progress', 2, '', '2025-07-25 02:54:06'),
(258, 19, '', 'in_progress', 2, '', '2025-07-25 02:54:08'),
(259, 19, '', 'received', 2, '', '2025-07-25 02:54:10'),
(261, 19, '', 'received', 2, '', '2025-07-25 02:54:45'),
(263, 19, '', 'in_progress', 2, '', '2025-07-25 02:57:07'),
(264, 19, '', 'completed', 2, '', '2025-07-25 02:57:15'),
(267, 17, '', 'in_progress', 2, '', '2025-07-25 02:59:01'),
(271, 19, '', 'in_progress', 2, '', '2025-07-25 03:00:41'),
(275, 17, '', 'on_hold', 2, '', '2025-07-25 07:53:06'),
(277, 21, '', 'in_progress', 2, '', '2025-07-25 07:59:05'),
(279, 21, '', 'in_progress', 2, '', '2025-07-25 08:01:47'),
(281, 19, '', 'received', 2, '', '2025-07-25 09:58:03'),
(282, 21, '', 'received', 2, '', '2025-07-25 10:01:06'),
(283, 24, '', 'in_progress', 2, '', '2025-07-25 10:01:07'),
(284, 19, '', 'received', 2, '', '2025-07-25 10:02:04'),
(285, 19, '', 'in_progress', 2, '', '2025-07-25 10:07:10'),
(286, 19, '', 'in_progress', 2, '', '2025-07-25 10:07:12'),
(287, 19, '', 'received', 2, '', '2025-07-25 10:07:13'),
(288, 17, '', 'completed', 2, '', '2025-07-25 10:07:38'),
(289, 24, '', 'on_hold', 2, '', '2025-07-25 10:10:36'),
(290, 24, '', 'in_progress', 2, '', '2025-07-25 10:10:37'),
(291, 24, '', 'on_hold', 2, '', '2025-07-25 10:10:39'),
(292, 24, '', 'on_hold', 2, '', '2025-07-25 10:10:40');

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
(2, 'Ucdeb083644ac0ea108e85ba498a1c784', 'สัภยา', 'เกสร', 'ผู้จัดการเเผนก', 'sappaya@gmail.com', '2025-07-21 03:35:15', '088777777', 'user', NULL, 'แผนกพัฒนาระบบงาน (RDC.นครสวรรค์)', 1, '7884657'),
(3, 'Udev001', 'พี่เอ็ม', 'developer', 'นักพัฒนา', 'somchai.dev@example.com', '2025-07-21 06:50:40', '0811111111', 'developer', NULL, NULL, 1, NULL),
(4, 'Udev002', 'มาร์ช', 'developer', 'นักพัฒนา', 'somying.dev@example.com', '2025-07-21 06:50:40', '0822222222', 'developer', NULL, NULL, 1, NULL),
(6, 'U04bc163e497a4e5e929b426356d1d599', 'Test010', 'Test010', NULL, 'test1@gmail.com', '2025-07-25 06:25:26', '08887898784', 'user', NULL, NULL, 1, NULL),
(7, 'U98f1b10aebfb7778015146b266640344', 'พี่เอ็ม', 'DEV', 'เจ้าหน้าที่', 'test2@gmail.com', '2025-07-25 07:42:20', '09875678743', 'user', NULL, 'แผนกพัฒนาระบบงาน', 1, '123456');

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
-- Dumping data for table `user_reviews`
--

INSERT INTO `user_reviews` (`id`, `task_id`, `user_id`, `rating`, `review_comment`, `status`, `revision_notes`, `reviewed_at`, `created_at`) VALUES
(1, 2, 1, 4, 'ปิดงานครับ', 'accepted', '', '2025-07-23 04:25:00', '2025-07-23 04:25:00'),
(2, 5, 1, 5, 'ดีๆ', 'accepted', '', '2025-07-23 09:05:53', '2025-07-23 09:05:53'),
(3, 6, 2, 5, 'ดีครับ', 'accepted', '', '2025-07-24 02:44:25', '2025-07-24 02:44:25'),
(5, 8, 2, 5, '3645345', 'accepted', '', '2025-07-24 06:35:23', '2025-07-24 06:35:23');

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
  ADD KEY `service_request_id` (`service_request_id`),
  ADD KEY `assignor_user_id` (`assignor_user_id`),
  ADD KEY `assigned_developer_id` (`assigned_developer_id`);

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
  ADD KEY `service_request_id` (`service_request_id`),
  ADD KEY `div_mgr_user_id` (`div_mgr_user_id`);

--
-- Indexes for table `document_numbers`
--
ALTER TABLE `document_numbers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_number` (`document_number`),
  ADD KEY `service_request_id` (`service_request_id`),
  ADD KEY `idx_warehouse_code_year_month` (`warehouse_number`,`code_name`,`year`,`month`);

--
-- Indexes for table `document_status_logs`
--
ALTER TABLE `document_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_request_id` (`service_request_id`),
  ADD KEY `reviewer_id` (`reviewer_id`);

--
-- Indexes for table `gm_approvals`
--
ALTER TABLE `gm_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_request_id` (`service_request_id`),
  ADD KEY `gm_user_id` (`gm_user_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `service_request_id` (`service_request_id`),
  ADD KEY `senior_gm_user_id` (`senior_gm_user_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_services_active` (`is_active`);

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_to_admin_id` (`assigned_to_admin_id`),
  ADD KEY `idx_service_requests_status` (`status`),
  ADD KEY `idx_service_requests_current_step` (`current_step`),
  ADD KEY `idx_service_requests_dev_status` (`developer_status`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `assigned_div_mgr_id` (`assigned_div_mgr_id`),
  ADD KEY `idx_service_requests_service_id` (`service_id`),
  ADD KEY `idx_service_requests_work_category` (`work_category`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_request_id` (`service_request_id`),
  ADD KEY `developer_user_id` (`developer_user_id`),
  ADD KEY `idx_tasks_status` (`task_status`),
  ADD KEY `idx_tasks_developer_status` (`task_status`);

--
-- Indexes for table `task_status_logs`
--
ALTER TABLE `task_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `line_id` (`line_id`),
  ADD KEY `fk_users_created_by` (`created_by_admin_id`),
  ADD KEY `idx_users_role` (`role`);

--
-- Indexes for table `user_reviews`
--
ALTER TABLE `user_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=295;

--
-- AUTO_INCREMENT for table `assignor_approvals`
--
ALTER TABLE `assignor_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `div_mgr_approvals`
--
ALTER TABLE `div_mgr_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `document_numbers`
--
ALTER TABLE `document_numbers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_status_logs`
--
ALTER TABLE `document_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `senior_gm_approvals`
--
ALTER TABLE `senior_gm_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `task_status_logs`
--
ALTER TABLE `task_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=293;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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

--
-- Constraints for table `assignor_approvals`
--
ALTER TABLE `assignor_approvals`
  ADD CONSTRAINT `assignor_approvals_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignor_approvals_ibfk_2` FOREIGN KEY (`assignor_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignor_approvals_ibfk_3` FOREIGN KEY (`assigned_developer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `div_mgr_approvals`
--
ALTER TABLE `div_mgr_approvals`
  ADD CONSTRAINT `div_mgr_approvals_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `div_mgr_approvals_ibfk_2` FOREIGN KEY (`div_mgr_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `document_numbers`
--
ALTER TABLE `document_numbers`
  ADD CONSTRAINT `document_numbers_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `document_status_logs`
--
ALTER TABLE `document_status_logs`
  ADD CONSTRAINT `document_status_logs_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_status_logs_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `gm_approvals`
--
ALTER TABLE `gm_approvals`
  ADD CONSTRAINT `gm_approvals_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `gm_approvals_ibfk_2` FOREIGN KEY (`gm_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `request_attachments`
--
ALTER TABLE `request_attachments`
  ADD CONSTRAINT `request_attachments_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `senior_gm_approvals`
--
ALTER TABLE `senior_gm_approvals`
  ADD CONSTRAINT `senior_gm_approvals_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `senior_gm_approvals_ibfk_2` FOREIGN KEY (`senior_gm_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD CONSTRAINT `fk_div_mgr` FOREIGN KEY (`assigned_div_mgr_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_requests_ibfk_2` FOREIGN KEY (`assigned_to_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_requests_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `service_requests_ibfk_4` FOREIGN KEY (`assigned_div_mgr_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`developer_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_status_logs`
--
ALTER TABLE `task_status_logs`
  ADD CONSTRAINT `task_status_logs_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_status_logs_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_reviews`
--
ALTER TABLE `user_reviews`
  ADD CONSTRAINT `user_reviews_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
