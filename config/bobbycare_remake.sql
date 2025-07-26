-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 26, 2025 at 08:51 AM
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
(292, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-26 01:22:03');

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
(1, 26, 1, 1, 'approved', 'ผู้จัดการแผนก', 6, 'high', '2025-07-23 03:07:08', '2025-07-23 03:07:08'),
(2, 25, 1, 1, 'approved', 'ผู้จัดการแผนก2', 24, 'urgent', '2025-07-23 03:07:38', '2025-07-23 03:07:38'),
(3, 27, 1, 1, 'approved', 'ผู้จัดการแผนก1', 36, 'high', '2025-07-23 03:31:39', '2025-07-23 03:31:39'),
(4, 28, 1, 1, 'approved', 'ผู้จัดการแผนก2', 72, 'urgent', '2025-07-23 03:32:04', '2025-07-23 03:32:04'),
(6, 23, 1, 1, 'approved', '78787878', 8, 'urgent', '2025-07-23 04:03:17', '2025-07-23 04:03:17'),
(7, 31, 1, 1, 'approved', 'เอกสารผู้จัดการแผนก', 32, 'high', '2025-07-23 08:51:23', '2025-07-23 08:51:23'),
(8, 32, 2, 2, 'approved', 'อนุมัติโดยผู้จัดการแผนก', 29, 'urgent', '2025-07-24 02:33:48', '2025-07-24 02:33:48'),
(9, 29, 2, NULL, 'rejected', '55', NULL, 'medium', '2025-07-24 03:06:48', '2025-07-24 03:06:48'),
(10, 34, 2, 1, 'approved', 'ผู้จัดการแผนก2', 2, 'urgent', '2025-07-24 03:07:23', '2025-07-24 03:07:23'),
(11, 33, 2, 1, 'approved', 'ผู้จัดการแผนก1', 3, 'high', '2025-07-24 03:07:45', '2025-07-24 03:07:45'),
(12, 38, 2, 1, 'approved', 'ผู้จัดการแผนกข้อเสนอแนะ:', 3, 'urgent', '2025-07-24 08:46:32', '2025-07-24 08:46:32'),
(13, 48, 2, 2, 'approved', 'ผู้จัดการแผนก', 2, 'high', '2025-07-25 02:39:42', '2025-07-25 02:39:42'),
(14, 35, 2, 2, 'approved', 'ผู้จัดการแผนก2', 2, 'urgent', '2025-07-25 02:40:01', '2025-07-25 02:40:01');

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
(2, 26, 1, 'approved', 'ข้อเสนอแนะ2ฝ่าย', '2025-07-23 02:59:46', '2025-07-23 02:59:46'),
(3, 24, 1, 'rejected', 'asds', '2025-07-23 02:59:55', '2025-07-23 02:59:55'),
(4, 28, 1, 'approved', 'ฝ่าย1', '2025-07-23 03:28:36', '2025-07-23 03:28:36'),
(5, 27, 1, 'approved', 'ฝ่าย4', '2025-07-23 03:28:42', '2025-07-23 03:28:42'),
(6, 29, 1, 'approved', '', '2025-07-23 03:58:11', '2025-07-23 03:58:11'),
(7, 23, 1, 'approved', '555', '2025-07-23 03:58:17', '2025-07-23 03:58:17'),
(9, 31, 1, 'approved', 'ผ่าน', '2025-07-23 08:46:10', '2025-07-23 08:46:10'),
(10, 32, 2, 'approved', 'ผู้จัดการฝ่าย อนุมัติ', '2025-07-24 02:29:24', '2025-07-24 02:29:24'),
(11, 33, 2, 'approved', 'ผู้จัดการฝ่าย1', '2025-07-24 03:04:57', '2025-07-24 03:04:57'),
(12, 34, 2, 'approved', 'ผู้จัดการฝ่าย2', '2025-07-24 03:05:21', '2025-07-24 03:05:21'),
(13, 35, 1, 'approved', 'ผู้จัดการฝ่าย', '2025-07-24 07:17:40', '2025-07-24 07:17:40'),
(14, 38, 2, 'approved', 'เหตุผลผู้จัดการฝ่าย', '2025-07-24 08:24:42', '2025-07-24 08:24:42'),
(15, 48, 2, 'approved', 'ผู้จัดการฝ่าย', '2025-07-25 02:38:17', '2025-07-25 02:38:17');

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
(2, 26, 'div_mgr_review', 'approved', 1, 'divmgr', 'ข้อเสนอแนะ2ฝ่าย', '2025-07-23 02:59:46'),
(3, 24, 'div_mgr_review', 'rejected', 1, 'divmgr', 'asds', '2025-07-23 02:59:55'),
(4, 26, 'assignor_review', 'approved', 1, 'assignor', 'ผู้จัดการแผนก', '2025-07-23 03:07:08'),
(5, 25, 'assignor_review', 'approved', 1, 'assignor', 'ผู้จัดการแผนก2', '2025-07-23 03:07:38'),
(6, 25, 'gm_review', 'approved', 1, 'gmapprover', 'GM1', '2025-07-23 03:10:42'),
(7, 26, 'gm_review', 'rejected', 1, 'gmapprover', 'GM2', '2025-07-23 03:12:03'),
(8, 25, 'senior_gm_review', 'approved', 1, 'seniorgm', 'ผู้จัดการอาวุโส ผู้จัดการอาวุโส ร่งทำ', '2025-07-23 03:15:07'),
(9, 28, 'div_mgr_review', 'approved', 1, 'divmgr', 'ฝ่าย1', '2025-07-23 03:28:36'),
(10, 27, 'div_mgr_review', 'approved', 1, 'divmgr', 'ฝ่าย4', '2025-07-23 03:28:42'),
(11, 27, 'assignor_review', 'approved', 1, 'assignor', 'ผู้จัดการแผนก1', '2025-07-23 03:31:39'),
(12, 28, 'assignor_review', 'approved', 1, 'assignor', 'ผู้จัดการแผนก2', '2025-07-23 03:32:04'),
(13, 28, 'gm_review', 'approved', 1, 'gmapprover', 'อนุมัติคำขอ (GM)1', '2025-07-23 03:33:04'),
(14, 27, 'gm_review', 'approved', 1, 'gmapprover', 'อนุมัติคำขอ (GM)2', '2025-07-23 03:33:18'),
(15, 27, 'senior_gm_review', 'approved', 1, 'seniorgm', 'ผู้จัดการอาวุโส1 เร่ง', '2025-07-23 03:34:15'),
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
(35, 34, 'div_mgr_review', 'approved', 2, 'divmgr', 'ผู้จัดการฝ่าย2', '2025-07-24 03:05:21'),
(36, 29, 'assignor_review', 'rejected', 2, 'assignor', '55', '2025-07-24 03:06:48'),
(37, 34, 'assignor_review', 'approved', 2, 'assignor', 'ผู้จัดการแผนก2', '2025-07-24 03:07:23'),
(38, 33, 'assignor_review', 'approved', 2, 'assignor', 'ผู้จัดการแผนก1', '2025-07-24 03:07:45'),
(39, 33, 'gm_review', 'approved', 2, 'gmapprover', 'ผู้จัดการทั่วไป1', '2025-07-24 03:08:51'),
(40, 34, 'gm_review', 'approved', 2, 'gmapprover', 'ผู้จัดการทั่วไป2', '2025-07-24 03:09:05'),
(41, 34, 'senior_gm_review', 'approved', 2, 'seniorgm', 'ผู้จัดการอาวุโส1 ผู้จัดการอาวุโส111', '2025-07-24 03:09:59'),
(42, 33, 'senior_gm_review', 'approved', 2, 'seniorgm', 'ผู้จัดการอาวุโส2 ผู้จัดการอาวุโส222', '2025-07-24 03:10:10'),
(43, 35, 'div_mgr_review', 'approved', 1, 'divmgr', 'ผู้จัดการฝ่าย', '2025-07-24 07:17:40'),
(44, 38, 'div_mgr_review', 'approved', 2, 'divmgr', 'เหตุผลผู้จัดการฝ่าย', '2025-07-24 08:24:42'),
(45, 38, 'assignor_review', 'approved', 2, 'assignor', 'ผู้จัดการแผนกข้อเสนอแนะ:', '2025-07-24 08:46:32'),
(46, 38, 'gm_review', 'approved', 2, 'gmapprover', 'อนุมัติคำขอ (GM)', '2025-07-24 09:14:11'),
(47, 38, 'senior_gm_review', 'approved', 2, 'seniorgm', 'พิจารณาคำขอขั้นสุดท้าย หมายเหตุสำหรับผู้พัฒนาพิจารณาคำขอขั้นสุดท้าย', '2025-07-24 09:19:13'),
(48, 48, 'div_mgr_review', 'approved', 2, 'divmgr', 'ผู้จัดการฝ่าย', '2025-07-25 02:38:17'),
(49, 48, 'assignor_review', 'approved', 2, 'assignor', 'ผู้จัดการแผนก', '2025-07-25 02:39:42'),
(50, 35, 'assignor_review', 'approved', 2, 'assignor', 'ผู้จัดการแผนก2', '2025-07-25 02:40:01'),
(51, 48, 'gm_review', 'approved', 2, 'gmapprover', 'อนุมัติคำขอ (GM)', '2025-07-25 02:41:21'),
(52, 35, 'gm_review', 'approved', 2, 'gmapprover', 'อนุมัติคำขอ (GM)2', '2025-07-25 02:41:37'),
(53, 48, 'senior_gm_review', 'approved', 2, 'seniorgm', 'ผู้จัดการอาวุโส ผู้จัดการอาวุโสหมายเหตุสำหรับผู้พัฒนา', '2025-07-25 02:42:45'),
(54, 35, 'senior_gm_review', 'approved', 2, 'seniorgm', 'ผู้จัดการอาวุโส ผู้จัดการอาวุโสหมายเหตุสำหรับผู้พัฒนา', '2025-07-25 02:42:59');

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
(2, 26, 1, 'rejected', 'GM2', NULL, '2025-07-23 03:12:03', '2025-07-23 03:12:03'),
(3, 28, 1, 'approved', 'อนุมัติคำขอ (GM)1', 20000.00, '2025-07-23 03:33:04', '2025-07-23 03:33:04'),
(4, 27, 1, 'approved', 'อนุมัติคำขอ (GM)2', 400.00, '2025-07-23 03:33:18', '2025-07-23 03:33:18'),
(5, 23, 1, 'approved', '14564', 20000.00, '2025-07-23 04:04:23', '2025-07-23 04:04:23'),
(7, 31, 1, 'approved', 'เอกสาร อนุมัติคำขอ (GM)', 20000.00, '2025-07-23 08:53:41', '2025-07-23 08:53:41'),
(8, 32, 2, 'approved', 'อนุมัติคำขอ (GM)', 200000.00, '2025-07-24 02:37:04', '2025-07-24 02:37:04'),
(9, 33, 2, 'approved', 'ผู้จัดการทั่วไป1', 30000.00, '2025-07-24 03:08:51', '2025-07-24 03:08:51'),
(10, 34, 2, 'approved', 'ผู้จัดการทั่วไป2', 30.00, '2025-07-24 03:09:05', '2025-07-24 03:09:05'),
(11, 38, 2, 'approved', 'อนุมัติคำขอ (GM)', 300000.00, '2025-07-24 09:14:11', '2025-07-24 09:14:11'),
(12, 48, 2, 'approved', 'อนุมัติคำขอ (GM)', 2000000.00, '2025-07-25 02:41:21', '2025-07-25 02:41:21'),
(13, 35, 2, 'approved', 'อนุมัติคำขอ (GM)2', 100.00, '2025-07-25 02:41:37', '2025-07-25 02:41:37');

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
(4, 34, '131317.jpg', '34_1753326103_0.jpg', 82227, 'jpg', '2025-07-24 03:01:43'),
(5, 35, '131325.jpg', '35_1753326127_0.jpg', 92603, 'jpg', '2025-07-24 03:02:07'),
(6, 35, '131328.jpg', '35_1753326127_1.jpg', 82385, 'jpg', '2025-07-24 03:02:07'),
(7, 36, '131317.jpg', '36_1753326147_0.jpg', 82227, 'jpg', '2025-07-24 03:02:27'),
(8, 36, '131317.pdf', '36_1753326147_1.pdf', 59743, 'pdf', '2025-07-24 03:02:27'),
(9, 37, '131162.jpg', '37_1753326163_0.jpg', 113831, 'jpg', '2025-07-24 03:02:43'),
(10, 38, '131317.pdf', '38_1753344350_0.pdf', 59743, 'pdf', '2025-07-24 08:05:50'),
(11, 48, '131317.jpg', '48_1753411052_0.jpg', 82227, 'jpg', '2025-07-25 02:37:32'),
(12, 48, '131317.pdf', '48_1753411052_1.pdf', 59743, 'pdf', '2025-07-25 02:37:32'),
(13, 49, 'เมล์แจ้งรายละเอียดก่อนเริ่มงาน.pdf', '49_1753429575_0.pdf', 405166, 'pdf', '2025-07-25 07:46:15'),
(14, 49, '131317 (1).pdf', '49_1753429575_1.pdf', 59743, 'pdf', '2025-07-25 07:46:15'),
(15, 49, '131317.pdf', '49_1753429575_2.pdf', 59743, 'pdf', '2025-07-25 07:46:15');

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
(2, 27, 1, 'approved', 'ผู้จัดการอาวุโส1', 'เร่ง', '2025-07-23 03:34:15', '2025-07-23 03:34:15'),
(3, 28, 1, 'approved', 'ผู้จัดการอาวุโส2', 'ทำสะ', '2025-07-23 03:34:30', '2025-07-23 03:34:30'),
(5, 23, 1, 'approved', '489456', '464', '2025-07-23 04:05:14', '2025-07-23 04:05:14'),
(6, 31, 1, 'approved', 'ผู้จัดการอาวุโส เอกสาร', 'รีบๆทำเนาะ', '2025-07-23 08:56:19', '2025-07-23 08:56:19'),
(7, 32, 2, 'approved', 'พิจารณาคำขอขั้นสุดท้าย', 'หมายเหตุสำหรับผู้พัฒนา:\r\n55', '2025-07-24 02:39:41', '2025-07-24 02:39:41'),
(8, 34, 2, 'approved', 'ผู้จัดการอาวุโส1', 'ผู้จัดการอาวุโส111', '2025-07-24 03:09:59', '2025-07-24 03:09:59'),
(9, 33, 2, 'approved', 'ผู้จัดการอาวุโส2', 'ผู้จัดการอาวุโส222', '2025-07-24 03:10:10', '2025-07-24 03:10:10'),
(10, 38, 2, 'approved', 'พิจารณาคำขอขั้นสุดท้าย', 'หมายเหตุสำหรับผู้พัฒนาพิจารณาคำขอขั้นสุดท้าย', '2025-07-24 09:19:13', '2025-07-24 09:19:13'),
(11, 48, 2, 'approved', 'ผู้จัดการอาวุโส', 'ผู้จัดการอาวุโสหมายเหตุสำหรับผู้พัฒนา', '2025-07-25 02:42:45', '2025-07-25 02:42:45'),
(12, 35, 2, 'approved', 'ผู้จัดการอาวุโส', 'ผู้จัดการอาวุโสหมายเหตุสำหรับผู้พัฒนา', '2025-07-25 02:42:59', '2025-07-25 02:42:59');

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
  `employee_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_requests`
--

INSERT INTO `service_requests` (`id`, `user_id`, `title`, `description`, `status`, `rejection_reason`, `assigned_to_admin_id`, `created_at`, `updated_at`, `current_step`, `priority`, `estimated_days`, `deadline`, `developer_status`, `work_category`, `expected_benefits`, `assigned_div_mgr_id`, `service_id`, `employee_id`) VALUES
(23, 1, 'D44', 'DDD', 'approved', NULL, NULL, '2025-07-22 09:09:23', '2025-07-24 10:09:15', 'senior_gm_approved', 'urgent', 8, NULL, 'received', NULL, NULL, NULL, NULL, NULL),
(24, 1, 'E55', '55', 'rejected', NULL, NULL, '2025-07-22 09:09:30', '2025-07-23 02:59:55', 'div_mgr_rejected', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL),
(25, 1, 'ทดสอบbolt', 'bolt1', 'approved', NULL, NULL, '2025-07-23 02:57:11', '2025-07-23 03:15:07', 'senior_gm_approved', 'urgent', 24, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL),
(26, 1, 'bolt2', 'bolt22', 'rejected', NULL, NULL, '2025-07-23 02:57:24', '2025-07-23 03:12:03', 'gm_rejected', 'high', 6, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL),
(27, 1, 'bolt3', 'bolt333', 'approved', NULL, NULL, '2025-07-23 03:26:22', '2025-07-24 01:47:09', 'senior_gm_approved', 'high', 36, NULL, 'completed', NULL, NULL, NULL, NULL, NULL),
(28, 1, 'ระบบล่ม', 'เชื่อมเนตไม่ได้', 'approved', NULL, NULL, '2025-07-23 03:26:41', '2025-07-23 04:25:00', 'senior_gm_approved', 'urgent', 72, NULL, 'accepted', NULL, NULL, NULL, NULL, NULL),
(29, 1, '123', '123', 'rejected', NULL, NULL, '2025-07-23 03:57:22', '2025-07-24 03:06:48', 'assignor_rejected', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL),
(30, 1, 'ทดเอกสารไฟล์', 'ทดเอกสารไฟล์', 'pending', NULL, NULL, '2025-07-23 08:29:26', '2025-07-23 08:29:26', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL),
(31, 1, 'เอกสาร1', 'เอกสาร11', 'approved', NULL, NULL, '2025-07-23 08:43:44', '2025-07-23 09:05:53', 'senior_gm_approved', 'high', 32, NULL, 'accepted', NULL, NULL, NULL, NULL, NULL),
(32, 2, 'ทดสอบวันที่24', 'ทดสอบรายละเอียดวันที่24', 'approved', NULL, NULL, '2025-07-24 02:27:33', '2025-07-24 02:44:25', 'senior_gm_approved', 'urgent', 29, NULL, 'accepted', NULL, NULL, NULL, NULL, NULL),
(33, 2, 'เทส1', 'เทส11', 'approved', NULL, NULL, '2025-07-24 03:01:29', '2025-07-24 06:35:23', 'senior_gm_approved', 'high', 3, NULL, 'accepted', NULL, NULL, NULL, NULL, NULL),
(34, 2, 'เทส2', 'เทส2', 'approved', NULL, NULL, '2025-07-24 03:01:43', '2025-07-24 03:16:04', 'senior_gm_approved', 'urgent', 2, NULL, 'accepted', NULL, NULL, NULL, NULL, NULL),
(35, 2, 'เทส3', 'เทส33', 'approved', NULL, NULL, '2025-07-24 03:02:07', '2025-07-25 09:58:01', 'senior_gm_approved', 'urgent', 2, NULL, 'pending', NULL, NULL, NULL, 5, NULL),
(36, 2, 'เทส4', 'เทส444', 'pending', NULL, NULL, '2025-07-24 03:02:27', '2025-07-24 03:02:27', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL),
(37, 2, 'เทส5', 'เทส55', 'pending', NULL, NULL, '2025-07-24 03:02:43', '2025-07-24 03:02:43', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL),
(38, 1, 'เน็ต่ลมครับ', 'เข้าเว็บไม่ได้', 'approved', NULL, NULL, '2025-07-24 08:05:50', '2025-07-24 10:09:22', 'senior_gm_approved', 'urgent', 3, NULL, 'in_progress', 'CDC', 'เเล่นเกม4Kหัวเเตก', 2, 2, NULL),
(39, 1, 'ทดสอบ', 'ทดสอบครับ44', 'approved', NULL, NULL, '2025-07-24 09:55:07', '2025-07-24 10:09:10', 'developer_self_created', 'high', 4, '2025-07-25', 'received', NULL, NULL, NULL, 12, NULL),
(40, 1, '56456', '4564564', 'approved', NULL, NULL, '2025-07-24 10:03:23', '2025-07-24 10:09:20', 'developer_self_created', 'high', 6, '0000-00-00', 'in_progress', NULL, NULL, NULL, 9, NULL),
(41, 1, '561456', '564', 'approved', NULL, NULL, '2025-07-24 10:04:52', '2025-07-24 10:09:16', 'developer_self_created', 'urgent', 1, '2025-07-25', 'in_progress', NULL, NULL, NULL, 6, NULL),
(42, 2, 'ทดสอบ', 'ไฟดับ', 'approved', NULL, NULL, '2025-07-25 01:30:41', '2025-07-25 02:49:16', 'developer_self_created', 'high', 1, '2025-07-25', 'in_progress', NULL, NULL, NULL, 10, NULL),
(43, 2, 'ทดสอบ', 'ไฟดับ', 'approved', NULL, NULL, '2025-07-25 01:39:09', '2025-07-25 01:39:09', 'developer_self_created', 'high', 1, '2025-07-25', 'received', NULL, NULL, NULL, 10, NULL),
(44, 2, 'ทดสอบ', 'ไฟดับ', 'approved', NULL, NULL, '2025-07-25 01:39:13', '2025-07-25 01:39:13', 'developer_self_created', 'high', 1, '2025-07-25', 'received', NULL, NULL, NULL, 10, NULL),
(45, 2, 'ทดสอบ', 'ไฟดับ', 'approved', NULL, NULL, '2025-07-25 01:39:19', '2025-07-25 01:39:19', 'developer_self_created', 'high', 1, '2025-07-25', 'received', NULL, NULL, NULL, 10, NULL),
(46, 2, 'เทส', 'งานนะ', 'approved', NULL, NULL, '2025-07-25 02:11:15', '2025-07-25 10:07:38', 'developer_self_created', 'urgent', 3, '2025-07-27', 'completed', NULL, NULL, NULL, 9, NULL),
(47, 2, '่ะด่', '่ะ่', 'approved', NULL, NULL, '2025-07-25 02:13:16', '2025-07-25 02:59:03', 'developer_self_created', 'medium', 1, '2025-07-26', 'on_hold', NULL, NULL, NULL, 11, NULL),
(48, 2, 'เเก้ระบบbobby', 'เข้าระบบbobbyไม่ได้', 'approved', NULL, NULL, '2025-07-25 02:37:32', '2025-07-25 10:07:13', 'senior_gm_approved', 'high', 2, NULL, 'received', 'RDC', 'กลับมาเริ่มงานได้', 2, 2, NULL),
(49, 7, 'พพพพ', 'รรรร', 'div_mgr_review', NULL, NULL, '2025-07-25 07:46:15', '2025-07-25 07:46:15', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', 'RDC', 'นนนนนนนนนน', 2, NULL, NULL),
(50, 2, 'เน็ตช้าา', 'หน้างานเข้าไม่ได้', 'approved', NULL, NULL, '2025-07-25 07:58:09', '2025-07-25 10:01:06', 'developer_self_created', 'urgent', 3, '2025-07-30', 'received', NULL, NULL, NULL, 9, NULL),
(51, 2, 'เน็ตช้าา', 'หน้างานเข้าไม่ได้', 'approved', NULL, NULL, '2025-07-25 07:58:18', '2025-07-25 07:58:18', 'developer_self_created', 'urgent', 3, '2025-07-30', 'received', NULL, NULL, NULL, 9, NULL),
(52, 2, 'เน็ตช้าา', 'หน้างานเข้าไม่ได้', 'approved', NULL, NULL, '2025-07-25 07:58:28', '2025-07-25 07:58:28', 'developer_self_created', 'urgent', 3, '2025-07-30', 'received', NULL, NULL, NULL, 9, NULL),
(53, 2, 'ggg', 'ggg', 'approved', NULL, NULL, '2025-07-25 10:00:52', '2025-07-25 10:10:39', 'developer_self_created', 'urgent', 2, '2025-07-25', 'on_hold', NULL, NULL, NULL, 10, NULL);

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
(1, 27, 1, 'completed', 100, '', '2025-07-23 03:36:15', '2025-07-24 01:47:09', NULL, NULL, '2025-07-23 03:34:15', '2025-07-24 01:47:09', '2025-07-23 10:36:15'),
(2, 28, 1, 'accepted', 100, NULL, '2025-07-23 03:35:44', NULL, NULL, NULL, '2025-07-23 03:34:30', '2025-07-23 04:25:00', '2025-07-23 10:35:44'),
(4, 23, 1, 'received', 10, '', '2025-07-24 06:56:51', '2025-07-24 06:57:27', NULL, NULL, '2025-07-23 04:05:14', '2025-07-24 10:09:15', '2025-07-24 13:56:51'),
(5, 31, 1, 'accepted', 100, '', NULL, '2025-07-23 09:04:35', NULL, NULL, '2025-07-23 08:56:19', '2025-07-23 09:05:53', NULL),
(6, 32, 2, 'accepted', 100, '', NULL, '2025-07-24 02:42:34', NULL, NULL, '2025-07-24 02:39:41', '2025-07-24 02:44:25', NULL),
(7, 34, 1, 'accepted', 100, '', '2025-07-24 03:11:28', '2025-07-24 03:12:29', NULL, NULL, '2025-07-24 03:09:59', '2025-07-24 03:16:04', '2025-07-24 10:11:28'),
(8, 33, 1, 'accepted', 100, '', '2025-07-24 03:12:15', '2025-07-24 06:33:24', NULL, NULL, '2025-07-24 03:10:10', '2025-07-24 06:35:23', '2025-07-24 10:12:15'),
(9, 38, 1, 'in_progress', 50, '', NULL, NULL, NULL, NULL, '2025-07-24 09:19:13', '2025-07-24 10:09:22', NULL),
(10, 39, 1, 'received', 10, '', '2025-07-24 09:55:07', '2025-07-24 09:55:21', '2025-07-25', NULL, '2025-07-24 09:55:07', '2025-07-24 10:09:10', '2025-07-24 16:55:07'),
(11, 40, 1, 'in_progress', 50, '', '2025-07-24 10:03:23', NULL, '2025-07-30', NULL, '2025-07-24 10:03:23', '2025-07-24 10:09:20', '2025-07-24 17:03:23'),
(12, 41, 1, 'in_progress', 50, '', '2025-07-24 10:04:52', '2025-07-24 10:05:04', '2025-07-25', NULL, '2025-07-24 10:04:52', '2025-07-24 10:09:16', '2025-07-24 17:04:52'),
(13, 42, 2, 'in_progress', 50, '', '2025-07-25 01:30:41', NULL, '2025-07-25', NULL, '2025-07-25 01:30:41', '2025-07-25 02:49:16', '2025-07-25 08:30:41'),
(17, 46, 2, 'completed', 100, '', '2025-07-25 02:11:15', '2025-07-25 10:07:38', '2025-07-27', NULL, '2025-07-25 02:11:15', '2025-07-25 10:07:38', '2025-07-25 09:11:15'),
(18, 47, 2, 'on_hold', 30, '', '2025-07-25 02:13:16', NULL, '2025-07-26', NULL, '2025-07-25 02:13:16', '2025-07-25 02:59:03', '2025-07-25 09:13:16'),
(19, 48, 2, 'received', 10, '', '2025-07-25 02:43:26', '2025-07-25 02:57:15', NULL, NULL, '2025-07-25 02:42:45', '2025-07-25 10:07:13', '2025-07-25 09:43:26'),
(20, 35, 2, 'pending', 0, '', NULL, NULL, NULL, NULL, '2025-07-25 02:42:59', '2025-07-25 09:58:01', NULL),
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
(2, 1, 'pending', 'received', 1, 'งานได้รับการยอมรับโดยผู้พัฒนา', '2025-07-23 03:36:15'),
(3, 4, 'pending', 'received', 1, 'งานได้รับการยอมรับโดยผู้พัฒนา', '2025-07-23 04:06:11'),
(6, 4, 'received', 'in_progress', 1, '', '2025-07-23 04:06:33'),
(7, 2, 'completed', 'accepted', 1, 'ปิดงานครับ', '2025-07-23 04:25:00'),
(8, 1, '', 'received', 1, '', '2025-07-23 04:35:04'),
(9, 4, '', 'received', 1, '', '2025-07-23 04:35:09'),
(10, 1, '', 'in_progress', 1, '', '2025-07-23 04:36:20'),
(11, 1, '', 'in_progress', 1, '', '2025-07-23 04:36:24'),
(12, 1, '', 'received', 1, '', '2025-07-23 04:42:16'),
(13, 4, '', 'in_progress', 1, '', '2025-07-23 04:42:38'),
(14, 1, '', 'pending', 1, '', '2025-07-23 04:47:46'),
(15, 1, '', 'received', 1, '', '2025-07-23 04:48:28'),
(16, 1, '', 'in_progress', 1, '', '2025-07-23 04:48:35'),
(17, 4, '', 'on_hold', 1, '', '2025-07-23 04:49:08'),
(18, 4, '', 'completed', 1, '', '2025-07-24 04:49:12'),
(19, 1, '', 'pending', 1, '', '2025-07-23 07:15:46'),
(21, 1, '', 'pending', 1, '', '2025-07-23 07:16:35'),
(22, 1, '', 'received', 1, '', '2025-07-23 07:16:38'),
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
(43, 1, '', 'pending', 1, '', '2025-07-23 09:58:44'),
(44, 1, '', 'received', 1, '', '2025-07-23 09:58:53'),
(45, 1, '', 'in_progress', 1, '', '2025-07-24 01:46:47'),
(46, 1, '', 'received', 1, '', '2025-07-24 01:46:52'),
(47, 1, '', 'completed', 1, '', '2025-07-24 01:47:09'),
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
(61, 7, 'pending', 'received', 1, 'งานได้รับการยอมรับโดยผู้พัฒนา', '2025-07-24 03:11:28'),
(62, 8, 'pending', 'received', 1, 'งานได้รับการยอมรับโดยผู้พัฒนา', '2025-07-24 03:12:15'),
(63, 8, '', 'in_progress', 1, '', '2025-07-24 03:12:25'),
(64, 7, '', 'completed', 1, '', '2025-07-24 03:12:29'),
(65, 7, 'completed', 'accepted', 2, 'ดดีีเยี่ยมมม', '2025-07-24 03:16:04'),
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
(83, 9, '', 'received', 1, '', '2025-07-24 09:54:24'),
(84, 9, '', 'pending', 1, '', '2025-07-24 09:54:25'),
(85, 9, '', 'received', 1, '', '2025-07-24 09:54:26'),
(86, 10, '', 'in_progress', 1, '', '2025-07-24 09:55:15'),
(87, 10, '', 'on_hold', 1, '', '2025-07-24 09:55:20'),
(88, 10, '', 'completed', 1, '', '2025-07-24 09:55:21'),
(89, 10, '', 'received', 1, '', '2025-07-24 09:55:23'),
(90, 9, '', 'received', 1, '', '2025-07-24 09:55:28'),
(91, 9, '', 'in_progress', 1, '', '2025-07-24 09:55:30'),
(92, 9, '', 'in_progress', 1, '', '2025-07-24 09:55:31'),
(93, 9, '', 'received', 1, '', '2025-07-24 09:55:32'),
(94, 10, '', 'pending', 1, '', '2025-07-24 09:55:46'),
(95, 10, '', 'received', 1, '', '2025-07-24 09:55:52'),
(96, 10, '', 'in_progress', 1, '', '2025-07-24 09:56:55'),
(97, 10, '', 'in_progress', 1, '', '2025-07-24 10:00:54'),
(98, 10, '', 'on_hold', 1, '', '2025-07-24 10:00:55'),
(99, 10, '', 'in_progress', 1, '', '2025-07-24 10:00:56'),
(100, 9, '', 'received', 1, '', '2025-07-24 10:00:56'),
(101, 11, '', 'received', 1, '', '2025-07-24 10:03:31'),
(102, 11, '', 'received', 1, '', '2025-07-24 10:03:32'),
(103, 10, '', 'received', 1, '', '2025-07-24 10:03:33'),
(104, 10, '', 'in_progress', 1, '', '2025-07-24 10:03:34'),
(105, 11, '', 'received', 1, '', '2025-07-24 10:03:35'),
(106, 11, '', 'in_progress', 1, '', '2025-07-24 10:03:36'),
(107, 9, '', 'on_hold', 1, '', '2025-07-24 10:03:38'),
(108, 11, '', 'received', 1, '', '2025-07-24 10:03:40'),
(109, 10, '', 'received', 1, '', '2025-07-24 10:03:40'),
(110, 9, '', 'in_progress', 1, '', '2025-07-24 10:03:43'),
(111, 11, '', 'received', 1, '', '2025-07-24 10:03:46'),
(112, 9, '', 'pending', 1, '', '2025-07-24 10:03:49'),
(113, 11, '', 'in_progress', 1, '', '2025-07-24 10:03:50'),
(114, 9, '', 'received', 1, '', '2025-07-24 10:04:07'),
(115, 10, '', 'in_progress', 1, '', '2025-07-24 10:04:08'),
(116, 12, '', 'in_progress', 1, '', '2025-07-24 10:04:56'),
(117, 11, '', 'on_hold', 1, '', '2025-07-24 10:04:58'),
(118, 11, '', 'in_progress', 1, '', '2025-07-24 10:04:59'),
(119, 12, '', 'on_hold', 1, '', '2025-07-24 10:05:02'),
(120, 12, '', 'completed', 1, '', '2025-07-24 10:05:04'),
(121, 9, '', 'in_progress', 1, '', '2025-07-24 10:05:58'),
(122, 9, '', 'received', 1, '', '2025-07-24 10:06:00'),
(123, 11, '', 'received', 1, '', '2025-07-24 10:06:14'),
(124, 9, '', 'received', 1, '', '2025-07-24 10:06:15'),
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
(138, 9, '', 'in_progress', 1, '', '2025-07-24 10:06:58'),
(139, 11, '', 'received', 1, '', '2025-07-24 10:07:00'),
(140, 9, '', 'in_progress', 1, '', '2025-07-24 10:07:01'),
(141, 11, '', 'in_progress', 1, '', '2025-07-24 10:07:02'),
(142, 9, '', 'received', 1, '', '2025-07-24 10:07:03'),
(143, 9, '', 'in_progress', 1, '', '2025-07-24 10:07:09'),
(144, 9, '', 'received', 1, '', '2025-07-24 10:07:13'),
(145, 9, '', 'received', 1, '', '2025-07-24 10:07:15'),
(146, 9, '', 'in_progress', 1, '', '2025-07-24 10:07:16'),
(147, 9, '', 'on_hold', 1, '', '2025-07-24 10:07:18'),
(148, 9, '', 'on_hold', 1, '', '2025-07-24 10:07:26'),
(149, 9, '', 'on_hold', 1, '', '2025-07-24 10:07:27'),
(150, 9, '', 'on_hold', 1, '', '2025-07-24 10:07:29'),
(151, 9, '', 'on_hold', 1, '', '2025-07-24 10:07:30'),
(152, 9, '', 'on_hold', 1, '', '2025-07-24 10:07:31'),
(153, 9, '', 'on_hold', 1, '', '2025-07-24 10:07:33'),
(154, 9, '', 'on_hold', 1, '', '2025-07-24 10:07:34'),
(155, 9, '', 'on_hold', 1, '', '2025-07-24 10:07:36'),
(156, 9, '', 'on_hold', 1, '', '2025-07-24 10:07:37'),
(157, 9, '', 'on_hold', 1, '', '2025-07-24 10:07:38'),
(158, 9, '', 'on_hold', 1, '', '2025-07-24 10:07:39'),
(159, 9, '', 'in_progress', 1, '', '2025-07-24 10:07:40'),
(160, 9, '', 'on_hold', 1, '', '2025-07-24 10:07:42'),
(161, 4, '', 'on_hold', 1, '', '2025-07-24 10:07:43'),
(162, 9, '', 'on_hold', 1, '', '2025-07-24 10:07:54'),
(163, 9, '', 'on_hold', 1, '', '2025-07-24 10:07:58'),
(164, 9, '', 'on_hold', 1, '', '2025-07-24 10:07:59'),
(165, 9, '', 'on_hold', 1, '', '2025-07-24 10:07:59'),
(166, 9, '', 'on_hold', 1, '', '2025-07-24 10:08:00'),
(167, 9, '', 'on_hold', 1, '', '2025-07-24 10:08:02'),
(168, 9, '', 'on_hold', 1, '', '2025-07-24 10:08:03'),
(169, 9, '', 'in_progress', 1, '', '2025-07-24 10:08:05'),
(170, 9, '', 'in_progress', 1, '', '2025-07-24 10:08:06'),
(171, 9, '', 'in_progress', 1, '', '2025-07-24 10:08:07'),
(172, 9, '', 'in_progress', 1, '', '2025-07-24 10:08:08'),
(173, 9, '', 'in_progress', 1, '', '2025-07-24 10:08:08'),
(174, 9, '', 'received', 1, '', '2025-07-24 10:08:10'),
(175, 11, '', 'received', 1, '', '2025-07-24 10:08:11'),
(176, 9, '', 'in_progress', 1, '', '2025-07-24 10:08:12'),
(177, 11, '', 'received', 1, '', '2025-07-24 10:08:13'),
(178, 11, '', 'in_progress', 1, '', '2025-07-24 10:08:14'),
(179, 11, '', 'received', 1, '', '2025-07-24 10:08:15'),
(180, 9, '', 'received', 1, '', '2025-07-24 10:08:16'),
(181, 9, '', 'in_progress', 1, '', '2025-07-24 10:08:17'),
(182, 11, '', 'in_progress', 1, '', '2025-07-24 10:08:17'),
(183, 11, '', 'received', 1, '', '2025-07-24 10:08:19'),
(184, 9, '', 'received', 1, '', '2025-07-24 10:08:20'),
(185, 9, '', 'received', 1, '', '2025-07-24 10:08:21'),
(186, 11, '', 'in_progress', 1, '', '2025-07-24 10:08:22'),
(187, 9, '', 'in_progress', 1, '', '2025-07-24 10:08:23'),
(188, 11, '', 'on_hold', 1, '', '2025-07-24 10:08:28'),
(189, 9, '', 'on_hold', 1, '', '2025-07-24 10:08:29'),
(190, 9, '', 'on_hold', 1, '', '2025-07-24 10:08:37'),
(191, 10, '', 'received', 1, '', '2025-07-24 10:08:44'),
(192, 12, '', 'received', 1, '', '2025-07-24 10:08:46'),
(193, 9, '', 'in_progress', 1, '', '2025-07-24 10:08:47'),
(194, 11, '', 'in_progress', 1, '', '2025-07-24 10:08:48'),
(195, 4, '', 'in_progress', 1, '', '2025-07-24 10:08:49'),
(196, 9, '', 'received', 1, '', '2025-07-24 10:08:52'),
(197, 4, '', 'received', 1, '', '2025-07-24 10:08:53'),
(198, 10, '', 'pending', 1, '', '2025-07-24 10:08:59'),
(199, 12, '', 'pending', 1, '', '2025-07-24 10:09:00'),
(200, 9, '', 'in_progress', 1, '', '2025-07-24 10:09:02'),
(201, 4, '', 'in_progress', 1, '', '2025-07-24 10:09:06'),
(202, 12, '', 'received', 1, '', '2025-07-24 10:09:09'),
(203, 10, '', 'received', 1, '', '2025-07-24 10:09:10'),
(204, 11, '', 'received', 1, '', '2025-07-24 10:09:11'),
(205, 9, '', 'received', 1, '', '2025-07-24 10:09:14'),
(206, 4, '', 'received', 1, '', '2025-07-24 10:09:15'),
(207, 12, '', 'in_progress', 1, '', '2025-07-24 10:09:16'),
(208, 11, '', 'in_progress', 1, '', '2025-07-24 10:09:20'),
(209, 9, '', 'in_progress', 1, '', '2025-07-24 10:09:22'),
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
(233, 18, '', 'in_progress', 2, '', '2025-07-25 02:13:22'),
(234, 18, '', 'in_progress', 2, '', '2025-07-25 02:13:25'),
(235, 17, '', 'in_progress', 2, '', '2025-07-25 02:13:27'),
(236, 18, '', 'in_progress', 2, '', '2025-07-25 02:13:27'),
(237, 17, '', 'in_progress', 2, '', '2025-07-25 02:13:28'),
(238, 17, '', 'in_progress', 2, '', '2025-07-25 02:32:00'),
(239, 13, '', 'in_progress', 2, '', '2025-07-25 02:32:01'),
(240, 18, '', 'received', 2, '', '2025-07-25 02:33:41'),
(241, 13, '', 'received', 2, '', '2025-07-25 02:33:43'),
(242, 17, '', 'received', 2, '', '2025-07-25 02:33:44'),
(243, 17, '', 'received', 2, '', '2025-07-25 02:33:45'),
(244, 17, '', 'received', 2, '', '2025-07-25 02:33:46'),
(245, 18, '', 'in_progress', 2, '', '2025-07-25 02:33:46'),
(246, 13, '', 'received', 2, '', '2025-07-25 02:33:47'),
(247, 17, '', 'on_hold', 2, '', '2025-07-25 02:33:49'),
(248, 17, '', 'in_progress', 2, '', '2025-07-25 02:33:50'),
(249, 19, 'pending', 'received', 2, 'งานได้รับการยอมรับโดยผู้พัฒนา', '2025-07-25 02:43:26'),
(250, 19, '', 'in_progress', 2, '', '2025-07-25 02:49:07'),
(251, 18, '', 'in_progress', 2, '', '2025-07-25 02:49:08'),
(252, 17, '', 'in_progress', 2, '', '2025-07-25 02:49:10'),
(253, 18, '', 'in_progress', 2, '', '2025-07-25 02:49:14'),
(254, 19, '', 'in_progress', 2, '', '2025-07-25 02:49:15'),
(255, 13, '', 'in_progress', 2, '', '2025-07-25 02:49:16'),
(256, 19, '', 'received', 2, '', '2025-07-25 02:49:18'),
(257, 17, '', 'in_progress', 2, '', '2025-07-25 02:54:06'),
(258, 19, '', 'in_progress', 2, '', '2025-07-25 02:54:08'),
(259, 19, '', 'received', 2, '', '2025-07-25 02:54:10'),
(260, 20, '', 'received', 2, '', '2025-07-25 02:54:42'),
(261, 19, '', 'received', 2, '', '2025-07-25 02:54:45'),
(262, 20, '', 'in_progress', 2, '', '2025-07-25 02:57:04'),
(263, 19, '', 'in_progress', 2, '', '2025-07-25 02:57:07'),
(264, 19, '', 'completed', 2, '', '2025-07-25 02:57:15'),
(265, 20, '', 'in_progress', 2, '', '2025-07-25 02:57:54'),
(266, 20, '', 'on_hold', 2, '', '2025-07-25 02:58:59'),
(267, 17, '', 'in_progress', 2, '', '2025-07-25 02:59:01'),
(268, 18, '', 'on_hold', 2, '', '2025-07-25 02:59:03'),
(269, 20, '', 'in_progress', 2, '', '2025-07-25 02:59:05'),
(271, 19, '', 'in_progress', 2, '', '2025-07-25 03:00:41'),
(272, 20, '', 'in_progress', 2, '', '2025-07-25 06:27:26'),
(273, 20, '', 'in_progress', 2, '', '2025-07-25 06:27:33'),
(274, 20, '', 'received', 2, '', '2025-07-25 07:53:04'),
(275, 17, '', 'on_hold', 2, '', '2025-07-25 07:53:06'),
(276, 20, '', 'in_progress', 2, '', '2025-07-25 07:58:55'),
(277, 21, '', 'in_progress', 2, '', '2025-07-25 07:59:05'),
(278, 20, '', 'received', 2, '', '2025-07-25 07:59:42'),
(279, 21, '', 'in_progress', 2, '', '2025-07-25 08:01:47'),
(280, 20, '', 'pending', 2, '', '2025-07-25 09:58:01'),
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
(4, 7, 2, 5, 'ดดีีเยี่ยมมม', 'accepted', '', '2025-07-24 03:16:04', '2025-07-24 03:16:04'),
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
-- Indexes for table `div_mgr_approvals`
--
ALTER TABLE `div_mgr_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_request_id` (`service_request_id`),
  ADD KEY `div_mgr_user_id` (`div_mgr_user_id`);

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
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `assigned_div_mgr_id` (`assigned_div_mgr_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=293;

--
-- AUTO_INCREMENT for table `assignor_approvals`
--
ALTER TABLE `assignor_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `div_mgr_approvals`
--
ALTER TABLE `div_mgr_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
-- AUTO_INCREMENT for table `request_attachments`
--
ALTER TABLE `request_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

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
