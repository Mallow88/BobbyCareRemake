-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 01, 2025 at 06:40 AM
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
(294, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-29 01:07:55'),
(295, 1, 'Login', '2025-07-29 08:50:48'),
(296, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-29 08:50:54'),
(297, 1, 'Login', '2025-07-30 01:11:03'),
(298, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-30 01:11:12'),
(299, 1, 'Login', '2025-07-30 02:25:35'),
(300, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-30 02:25:43'),
(301, 1, 'Login', '2025-07-30 03:41:48'),
(302, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-30 03:42:05'),
(303, 1, 'Login', '2025-07-30 04:34:26'),
(304, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-30 04:34:32'),
(305, 1, 'Login', '2025-07-30 07:29:53'),
(306, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-30 07:30:05'),
(307, 1, 'Login', '2025-07-30 07:45:22'),
(308, 1, 'Login', '2025-07-30 07:47:21'),
(309, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-30 07:47:31'),
(310, 1, 'Login', '2025-07-30 07:50:57'),
(311, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-30 07:51:05'),
(312, 1, 'Login', '2025-07-30 08:18:15'),
(313, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-30 08:18:30'),
(314, 1, 'Login', '2025-07-30 08:21:59'),
(315, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-30 08:22:14'),
(316, 1, 'Login', '2025-07-30 08:22:43'),
(317, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-30 08:23:04'),
(318, 1, 'Login', '2025-07-30 08:38:04'),
(319, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-30 08:38:38'),
(320, 1, 'Login', '2025-07-30 08:39:56'),
(321, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-30 08:40:01'),
(322, 1, 'Login', '2025-07-30 08:40:38'),
(323, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-30 08:40:48'),
(324, 1, 'Login', '2025-07-30 08:42:45'),
(325, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-30 08:42:51'),
(326, 1, 'Login', '2025-07-31 01:09:13'),
(327, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-31 01:09:19'),
(328, 1, 'Login', '2025-07-31 01:14:22'),
(329, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-31 01:14:33'),
(330, 1, 'Login', '2025-07-31 02:55:35'),
(331, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-31 02:55:43'),
(332, 1, 'Login', '2025-07-31 06:28:26'),
(333, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-31 06:28:37'),
(334, 1, 'Login', '2025-07-31 06:31:01'),
(335, 1, 'Login', '2025-07-31 06:36:13'),
(336, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-31 06:36:21'),
(337, 1, 'Login', '2025-07-31 06:37:25'),
(338, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-31 06:37:34'),
(339, 1, 'Login', '2025-07-31 06:47:52'),
(340, 1, 'Login', '2025-07-31 06:48:18'),
(341, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-31 06:48:23'),
(342, 1, 'Login', '2025-07-31 06:51:18'),
(343, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-31 06:51:24'),
(344, 1, 'Login', '2025-07-31 06:58:30'),
(345, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-31 06:58:37'),
(346, 1, 'Login', '2025-07-31 07:02:52'),
(347, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-31 07:03:01'),
(348, 1, 'Login', '2025-07-31 07:05:29'),
(349, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-31 07:05:38'),
(350, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-31 07:05:45'),
(351, 1, 'Login', '2025-07-31 07:08:08'),
(352, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-31 07:08:14'),
(353, 1, 'Login', '2025-07-31 08:09:30'),
(354, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-31 08:09:36'),
(355, 1, 'Login', '2025-07-31 08:12:23'),
(356, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-31 08:12:29'),
(357, 1, 'Login', '2025-08-01 04:27:58'),
(358, 1, 'แก้ไขผู้ใช้ ID 2', '2025-08-01 04:28:02'),
(359, 1, 'Login', '2025-08-01 04:30:59'),
(360, 1, 'Login', '2025-08-01 04:31:24'),
(361, 1, 'แก้ไขผู้ใช้ ID 2', '2025-08-01 04:31:33'),
(362, 1, 'ลบผู้ใช้ ID 11', '2025-08-01 04:31:43'),
(363, 1, 'ลบผู้ใช้ ID 8', '2025-08-01 04:31:48'),
(364, 1, 'Login', '2025-08-01 04:32:57'),
(365, 1, 'แก้ไขผู้ใช้ ID 2', '2025-08-01 04:33:05'),
(366, 1, 'Login', '2025-08-01 04:33:36'),
(367, 1, 'แก้ไขผู้ใช้ ID 2', '2025-08-01 04:33:44');

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
(1, 12, 2, 2, 'approved', 'เหตุผล/ข้อเสนอแนะ:ผู้จัดการแผนก', 3, 'urgent', '2025-07-30 08:18:00', '2025-07-30 08:18:00'),
(2, 20, 2, 2, 'approved', 'การพิจารณาผู้จัดการแผนก', 1, 'urgent', '2025-08-01 04:32:51', '2025-08-01 04:32:51');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `warehouse_number` varchar(10) NOT NULL,
  `code_name` varchar(10) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `warehouse_number`, `code_name`, `is_active`, `created_at`) VALUES
(1, '03', 'TRO', 1, '2025-07-29 08:00:30'),
(2, '03', 'PIC', 1, '2025-07-29 08:00:30'),
(3, '03', 'DAN', 1, '2025-07-29 08:00:30'),
(4, '03', 'RCV', 1, '2025-07-29 08:00:30'),
(5, '03', 'BQC', 1, '2025-07-29 08:00:30'),
(6, '02', 'PIC', 1, '2025-07-29 08:00:30'),
(7, '02', 'LOA', 1, '2025-07-29 08:00:30'),
(8, '02', 'TRI', 1, '2025-07-29 08:00:30'),
(9, '02', 'RCV', 1, '2025-07-29 08:00:30'),
(10, '02', 'DAI', 1, '2025-07-29 08:00:30'),
(11, '02', 'TRO', 1, '2025-07-29 08:00:30'),
(12, '02', 'MIS', 1, '2025-07-29 08:00:30'),
(13, '02', 'CQC', 1, '2025-07-29 08:00:30'),
(14, '02', 'SAF', 1, '2025-07-29 08:00:30'),
(15, '02', 'DAO', 1, '2025-07-29 08:00:30'),
(16, '02', 'ENG', 1, '2025-07-29 08:00:30'),
(17, '01', 'RFC', 1, '2025-07-29 08:00:30'),
(18, '01', 'LOA', 1, '2025-07-29 08:00:30'),
(19, '01', 'TRI', 1, '2025-07-29 08:00:30'),
(20, '01', 'SPC', 1, '2025-07-29 08:00:30'),
(21, '01', 'SEQ', 1, '2025-07-29 08:00:30'),
(22, '01', 'INV', 1, '2025-07-29 08:00:30'),
(23, '01', 'RBC', 1, '2025-07-29 08:00:30'),
(24, '01', 'RFL', 1, '2025-07-29 08:00:30'),
(25, '01', 'DAO', 1, '2025-07-29 08:00:30'),
(26, '01', 'RID', 1, '2025-07-29 08:00:30'),
(27, '01', 'REV', 1, '2025-07-29 08:00:30'),
(28, '01', 'SAF', 1, '2025-07-29 08:00:30'),
(29, '01', 'ADM', 1, '2025-07-29 08:00:30'),
(30, '01', 'RCN', 1, '2025-07-29 08:00:30'),
(31, '01', 'O2O', 1, '2025-07-29 08:00:30'),
(32, '01', 'ENG', 1, '2025-07-29 08:00:30'),
(33, '01', 'TRP', 1, '2025-07-29 08:00:30'),
(34, '01', 'ROD', 1, '2025-07-29 08:00:30'),
(35, '01', 'SPD', 1, '2025-07-29 08:00:30'),
(36, '01', 'TRO', 1, '2025-07-29 08:00:30'),
(37, '01', 'DAI', 1, '2025-07-29 08:00:30'),
(38, '01', 'FLA', 1, '2025-07-29 08:00:30'),
(39, '01', 'DEV', 1, '2025-07-29 08:00:30'),
(40, '01', 'MIS', 1, '2025-07-29 08:00:30');

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
(6, 12, '01-DAO-25-7-001', 2, 'approved', 'ไม่อนุมัติ\r\nเหตุผล/ข้อเสนอแนะ:', '2025-07-30 07:46:50', '2025-07-30 07:46:50'),
(7, 20, '01-DAO-25-8-001', 2, 'approved', 'ผู้จัดการฝ่ายการพิจารณา', '2025-08-01 04:31:21', '2025-08-01 04:31:21');

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
(1, '01', 'REV', 25, 7, 1, '01REV2507', NULL, '2025-07-29 08:01:26'),
(3, '03', 'RCV', 25, 7, 1, '03-RCV-25-7-001', NULL, '2025-07-29 08:12:20'),
(4, '01', 'RID', 25, 7, 1, '01-RID-25-7-001', NULL, '2025-07-29 08:17:57'),
(5, '01', 'DEV', 25, 7, 1, '01-DEV-25-7-001', NULL, '2025-07-29 08:19:18'),
(6, '01', 'DAI', 25, 7, 1, '01-DAI-25-7-001', NULL, '2025-07-29 08:21:33'),
(7, '01', 'DEV', 25, 7, 2, '01-DEV-25-7-002', NULL, '2025-07-29 08:22:32'),
(8, '01', 'DAI', 25, 7, 2, '01-DAI-25-7-002', NULL, '2025-07-29 08:50:31'),
(9, '01', 'DAI', 25, 7, 3, '01-DAI-25-7-003', NULL, '2025-07-30 02:09:26'),
(10, '01', 'ADM', 25, 7, 1, '01-ADM-25-7-001', NULL, '2025-07-30 02:23:11'),
(11, '01', 'RFL', 25, 7, 1, '01-RFL-25-7-001', NULL, '2025-07-30 04:43:06'),
(12, '01', 'RFL', 25, 7, 2, '01-RFL-25-7-002', NULL, '2025-07-30 04:45:41'),
(13, '01', 'DAI', 25, 7, 4, '01-DAI-25-7-004', NULL, '2025-07-30 05:01:42'),
(14, '01', 'ROD', 25, 7, 1, '01-ROD-25-7-001', NULL, '2025-07-30 06:06:30'),
(15, '01', 'DAO', 25, 7, 1, '01-DAO-25-7-001', 12, '2025-07-30 07:44:10'),
(16, '01', 'ADM', 25, 7, 2, '01-ADM-25-7-002', NULL, '2025-07-31 04:43:49'),
(17, '01', 'DAO', 25, 7, 2, '01-DAO-25-7-002', 15, '2025-07-31 06:18:37'),
(18, '01', 'DAO', 25, 8, 1, '01-DAO-25-8-001', 20, '2025-08-01 04:30:47');

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
(4, 12, 'div_mgr_review', 'approved', 2, 'divmgr', 'ไม่อนุมัติ\r\nเหตุผล/ข้อเสนอแนะ:', '2025-07-30 07:46:50'),
(5, 12, 'assignor_review', 'approved', 2, 'assignor', 'เหตุผล/ข้อเสนอแนะ:ผู้จัดการแผนก', '2025-07-30 08:18:00'),
(6, 12, 'gm_review', 'approved', 2, 'gmapprover', 'เหตุผล/ข้อเสนอแนะ:อนุมัติคำขอ (GM)', '2025-07-30 08:37:58'),
(7, 12, 'senior_gm_review', 'approved', 2, 'seniorgm', 'เหตุผล/ข้อเสนอแนะ:พิจารณาคำขอขั้นสุดท้าย หมายเหตุสำหรับผู้พัฒนา:นสุดท้าย', '2025-07-30 08:39:45'),
(8, 20, 'div_mgr_review', 'approved', 2, 'divmgr', 'ผู้จัดการฝ่ายการพิจารณา', '2025-08-01 04:31:21'),
(9, 20, 'assignor_review', 'approved', 2, 'assignor', 'การพิจารณาผู้จัดการแผนก', '2025-08-01 04:32:51'),
(10, 20, 'gm_review', 'approved', 2, 'gmapprover', '(GM)การพิจารณา', '2025-08-01 04:33:32');

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
(1, 12, 2, 'approved', 'เหตุผล/ข้อเสนอแนะ:อนุมัติคำขอ (GM)', 200000.00, '2025-07-30 08:37:58', '2025-07-30 08:37:58'),
(2, 20, 2, 'approved', '(GM)การพิจารณา', 3000.00, '2025-08-01 04:33:32', '2025-08-01 04:33:32');

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
(12, 12, 'เมล์แจ้งรายละเอียดก่อนเริ่มงาน.pdf', '12_1753861450_0.pdf', 405166, 'pdf', '2025-07-30 07:44:10'),
(14, 15, 'LINE_ALBUM_10768-AM_250731_4.jpg', '15_1753942717_0.jpg', 316852, 'jpg', '2025-07-31 06:18:37'),
(15, 20, 'LINE_ALBUM_14768_250801_9.jpg', '20_1754022647_0.jpg', 411384, 'jpg', '2025-08-01 04:30:47');

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
(1, 12, 2, 'approved', 'เหตุผล/ข้อเสนอแนะ:พิจารณาคำขอขั้นสุดท้าย', 'หมายเหตุสำหรับผู้พัฒนา:นสุดท้าย', '2025-07-30 08:39:45', '2025-07-30 08:39:45');

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
  `deadline` date DEFAULT NULL,
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
(12, 2, 'ทดสอบ', 1, '01-DAO', 2, NULL, 'approved', 'senior_gm_approved', 'urgent', 3, NULL, 'in_progress', 'วัตถุประสงค์', 'กลุ่มผู้ใช้งาน', 'ฟังก์ชันหลักที่ต้องการ', 'ข้อมูลที่ต้องใช้', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ขั้นตอนการทำงานเดิม', NULL, 'โปรแกรมที่คาดว่าจะเกี่ยวข้อง', NULL, NULL, NULL, '', '2025-07-30 07:44:10', '2025-08-01 03:18:16', 'ประเภทบริการ: โปรแกรมใหม่\nหัวข้องานคลัง: 01-DAO\nวัตถุประสงค์: วัตถุประสงค์\nกลุ่มผู้ใช้: กลุ่มผู้ใช้งาน\nฟังก์ชันหลัก: ฟังก์ชันหลักที่ต้องการ'),
(13, 2, 'บริการ', 12, NULL, NULL, NULL, 'approved', 'developer_self_created', 'high', 1, '2025-07-30', 'in_progress', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-30 08:42:05', '2025-08-01 02:57:12', '555555'),
(15, 2, 'ทดสอบ', 3, '01-DAO', 6, NULL, 'pending', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', '', '', '', '', '', '', '', '', 'ARM Share', '0000', '111111', '5555555566655', '', '', '', '', '', '', '', '', '', '', '', '', '', 'เท่', '2025-07-31 06:18:37', '2025-07-31 06:19:50', 'ประเภทบริการ: โปรแกรมเดิม (เปลี่ยนข้อมูล)\nหัวข้องานคลัง: 01-DAO\nโปรแกรม: ARM Share\nข้อมูลที่ต้องเปลี่ยน: 0000'),
(20, 2, 'Fx DD', 1, '01-DAO', 2, NULL, 'senior_gm_review', 'gm_approved', 'urgent', 1, NULL, 'not_assigned', 'อยากได้ครับ', 'เจ้าหน้าที่', 'คิดตอบถาม', 'คลังสินค้า', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'บึกทุกไปมาเเยกเเยะ', NULL, 'ไม่มีครับ', NULL, NULL, NULL, '', '2025-08-01 04:30:47', '2025-08-01 04:33:32', 'ประเภทบริการ: โปรแกรมใหม่\nหัวข้องานคลัง: 01-DAO\nวัตถุประสงค์: อยากได้ครับ\nกลุ่มผู้ใช้: เจ้าหน้าที่\nฟังก์ชันหลัก: คิดตอบถาม');

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

--
-- Dumping data for table `subtask_logs`
--

INSERT INTO `subtask_logs` (`id`, `subtask_id`, `old_status`, `new_status`, `changed_by`, `notes`, `created_at`) VALUES
(42, 44, 'in_progress', 'in_progress', 2, '่า่า', '2025-08-01 04:27:02'),
(43, 44, 'completed', 'completed', 2, '่า่า', '2025-08-01 04:27:08'),
(44, 46, 'in_progress', 'in_progress', 2, '56464564', '2025-08-01 04:27:14'),
(45, 46, 'completed', 'completed', 2, '56464564', '2025-08-01 04:27:18'),
(46, 48, 'in_progress', 'in_progress', 2, '', '2025-08-01 04:27:22'),
(47, 48, 'completed', 'completed', 2, '561561561', '2025-08-01 04:27:27');

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
(1, 'โปรแกรมใหม่', 1, 'ส่วนหน้าบ้าน (Frontend)', 'พัฒนา HTML, CSS, JavaScript สำหรับส่วนติดต่อผู้ใช้', 20, 1, '2025-07-31 07:26:58'),
(2, 'โปรแกรมใหม่', 2, 'ส่วนหลังบ้าน (Backend)', 'พัฒนา API, Database, Logic ส่วนหลังบ้าน', 30, 1, '2025-07-31 07:26:58'),
(3, 'โปรแกรมใหม่', 3, 'ทดสอบระบบ', 'ทดสอบการทำงานของระบบและแก้ไข Bug', 20, 1, '2025-07-31 07:26:58'),
(4, 'โปรแกรมใหม่', 4, 'ทดสอบผู้ใช้', 'ทดสอบกับผู้ใช้จริงและปรับปรุง', 20, 1, '2025-07-31 07:26:58'),
(5, 'โปรแกรมใหม่', 5, 'เตรียมเอกสารและส่งมอบ', 'จัดทำเอกสารและส่งมอบระบบ', 10, 1, '2025-07-31 07:26:58'),
(6, 'โปรแกรมเดิม (แก้ปัญหา)', 1, 'วิเคราะห์ปัญหา', 'ศึกษาและวิเคราะห์สาเหตุของปัญหา', 20, 1, '2025-07-31 07:26:58'),
(7, 'โปรแกรมเดิม (แก้ปัญหา)', 2, 'แก้ไขโค้ด', 'แก้ไขโค้ดและปรับปรุงระบบ', 40, 1, '2025-07-31 07:26:58'),
(8, 'โปรแกรมเดิม (แก้ปัญหา)', 3, 'ทดสอบการแก้ไข', 'ทดสอบว่าปัญหาได้รับการแก้ไขแล้ว', 25, 1, '2025-07-31 07:26:58'),
(9, 'โปรแกรมเดิม (แก้ปัญหา)', 4, 'ทดสอบผู้ใช้', 'ให้ผู้ใช้ทดสอบและยืนยันการแก้ไข', 10, 1, '2025-07-31 07:26:58'),
(10, 'โปรแกรมเดิม (แก้ปัญหา)', 5, 'อัปเดตเอกสาร', 'อัปเดตเอกสารและบันทึกการแก้ไข', 5, 1, '2025-07-31 07:26:58'),
(11, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 1, 'วิเคราะห์ข้อมูลเดิม', 'ศึกษาโครงสร้างข้อมูลปัจจุบัน', 15, 1, '2025-07-31 07:26:58'),
(12, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 2, 'สำรองข้อมูล', 'สำรองข้อมูลเดิมก่อนเปลี่ยนแปลง', 10, 1, '2025-07-31 07:26:58'),
(13, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 3, 'แก้ไขข้อมูล', 'ดำเนินการเปลี่ยนแปลงข้อมูล', 50, 1, '2025-07-31 07:26:58'),
(14, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 4, 'ทดสอบข้อมูล', 'ทดสอบความถูกต้องของข้อมูลใหม่', 20, 1, '2025-07-31 07:26:58'),
(15, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 5, 'อัปเดตเอกสาร', 'อัปเดตเอกสารและคู่มือการใช้งาน', 5, 1, '2025-07-31 07:26:58'),
(16, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 1, 'วิเคราะห์ความต้องการ', 'ศึกษาและวิเคราะห์ฟังก์ชั่นที่ต้องการ', 15, 1, '2025-07-31 07:26:58'),
(17, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 2, 'พัฒนาฟังก์ชั่นใหม่', 'เขียนโค้ดและพัฒนาฟังก์ชั่นใหม่', 40, 1, '2025-07-31 07:26:58'),
(18, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 3, 'ทดสอบฟังก์ชั่น', 'ทดสอบฟังก์ชั่นใหม่และการทำงานร่วมกับระบบเดิม', 25, 1, '2025-07-31 07:26:58'),
(19, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 4, 'ทดสอบผู้ใช้', 'ให้ผู้ใช้ทดสอบฟังก์ชั่นใหม่', 15, 1, '2025-07-31 07:26:58'),
(20, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 5, 'อัปเดตเอกสาร', 'อัปเดตคู่มือและเอกสารการใช้งาน', 5, 1, '2025-07-31 07:26:58'),
(21, 'โปรแกรมเดิม (ตกแต่ง)', 1, 'ออกแบบ UI/UX', 'ออกแบบหน้าตาและประสบการณ์ผู้ใช้', 25, 1, '2025-07-31 07:26:58'),
(22, 'โปรแกรมเดิม (ตกแต่ง)', 2, 'พัฒนาส่วนตกแต่ง', 'เขียนโค้ด CSS, JavaScript สำหรับการตกแต่ง', 35, 1, '2025-07-31 07:26:58'),
(23, 'โปรแกรมเดิม (ตกแต่ง)', 3, 'ทดสอบการแสดงผล', 'ทดสอบการแสดงผลในอุปกรณ์ต่างๆ', 25, 1, '2025-07-31 07:26:58'),
(24, 'โปรแกรมเดิม (ตกแต่ง)', 4, 'ปรับปรุงตามข้อเสนอแนะ', 'ปรับปรุงตามความเห็นของผู้ใช้', 10, 1, '2025-07-31 07:26:58'),
(25, 'โปรแกรมเดิม (ตกแต่ง)', 5, 'ส่งมอบและอัปเดตเอกสาร', 'ส่งมอบงานและอัปเดตเอกสาร', 5, 1, '2025-07-31 07:26:58'),
(26, 'โปรแกรมใหม่', 1, 'ส่วนหน้าบ้าน (Frontend)', 'พัฒนา HTML, CSS, JavaScript สำหรับส่วนติดต่อผู้ใช้', 20, 1, '2025-07-31 07:38:33'),
(27, 'โปรแกรมใหม่', 2, 'ส่วนหลังบ้าน (Backend)', 'พัฒนา API, Database, Logic ส่วนหลังบ้าน', 30, 1, '2025-07-31 07:38:33'),
(28, 'โปรแกรมใหม่', 3, 'ทดสอบระบบ', 'ทดสอบการทำงานของระบบและแก้ไข Bug', 20, 1, '2025-07-31 07:38:33'),
(29, 'โปรแกรมใหม่', 4, 'ทดสอบผู้ใช้', 'ทดสอบกับผู้ใช้จริงและปรับปรุง', 20, 1, '2025-07-31 07:38:33'),
(30, 'โปรแกรมใหม่', 5, 'เตรียมเอกสารและส่งมอบ', 'จัดทำเอกสารและส่งมอบระบบ', 10, 1, '2025-07-31 07:38:33'),
(31, 'โปรแกรมเดิม (แก้ปัญหา)', 1, 'วิเคราะห์ปัญหา', 'ศึกษาและวิเคราะห์สาเหตุของปัญหา', 20, 1, '2025-07-31 07:38:33'),
(32, 'โปรแกรมเดิม (แก้ปัญหา)', 2, 'แก้ไขโค้ด', 'แก้ไขโค้ดและปรับปรุงระบบ', 40, 1, '2025-07-31 07:38:33'),
(33, 'โปรแกรมเดิม (แก้ปัญหา)', 3, 'ทดสอบการแก้ไข', 'ทดสอบว่าปัญหาได้รับการแก้ไขแล้ว', 25, 1, '2025-07-31 07:38:33'),
(34, 'โปรแกรมเดิม (แก้ปัญหา)', 4, 'ทดสอบผู้ใช้', 'ให้ผู้ใช้ทดสอบและยืนยันการแก้ไข', 10, 1, '2025-07-31 07:38:33'),
(35, 'โปรแกรมเดิม (แก้ปัญหา)', 5, 'อัปเดตเอกสาร', 'อัปเดตเอกสารและบันทึกการแก้ไข', 5, 1, '2025-07-31 07:38:33'),
(36, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 1, 'วิเคราะห์ข้อมูลเดิม', 'ศึกษาโครงสร้างข้อมูลปัจจุบัน', 15, 1, '2025-07-31 07:38:33'),
(37, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 2, 'สำรองข้อมูล', 'สำรองข้อมูลเดิมก่อนเปลี่ยนแปลง', 10, 1, '2025-07-31 07:38:33'),
(38, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 3, 'แก้ไขข้อมูล', 'ดำเนินการเปลี่ยนแปลงข้อมูล', 50, 1, '2025-07-31 07:38:33'),
(39, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 4, 'ทดสอบข้อมูล', 'ทดสอบความถูกต้องของข้อมูลใหม่', 20, 1, '2025-07-31 07:38:33'),
(40, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', 5, 'อัปเดตเอกสาร', 'อัปเดตเอกสารและคู่มือการใช้งาน', 5, 1, '2025-07-31 07:38:33'),
(41, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 1, 'วิเคราะห์ความต้องการ', 'ศึกษาและวิเคราะห์ฟังก์ชั่นที่ต้องการ', 15, 1, '2025-07-31 07:38:33'),
(42, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 2, 'พัฒนาฟังก์ชั่นใหม่', 'เขียนโค้ดและพัฒนาฟังก์ชั่นใหม่', 40, 1, '2025-07-31 07:38:33'),
(43, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 3, 'ทดสอบฟังก์ชั่น', 'ทดสอบฟังก์ชั่นใหม่และการทำงานร่วมกับระบบเดิม', 25, 1, '2025-07-31 07:38:33'),
(44, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 4, 'ทดสอบผู้ใช้', 'ให้ผู้ใช้ทดสอบฟังก์ชั่นใหม่', 15, 1, '2025-07-31 07:38:33'),
(45, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', 5, 'อัปเดตเอกสาร', 'อัปเดตคู่มือและเอกสารการใช้งาน', 5, 1, '2025-07-31 07:38:33'),
(46, 'โปรแกรมเดิม (ตกแต่ง)', 1, 'ออกแบบ UI/UX', 'ออกแบบหน้าตาและประสบการณ์ผู้ใช้', 25, 1, '2025-07-31 07:38:33'),
(47, 'โปรแกรมเดิม (ตกแต่ง)', 2, 'พัฒนาส่วนตกแต่ง', 'เขียนโค้ด CSS, JavaScript สำหรับการตกแต่ง', 35, 1, '2025-07-31 07:38:33'),
(48, 'โปรแกรมเดิม (ตกแต่ง)', 3, 'ทดสอบการแสดงผล', 'ทดสอบการแสดงผลในอุปกรณ์ต่างๆ', 25, 1, '2025-07-31 07:38:33'),
(49, 'โปรแกรมเดิม (ตกแต่ง)', 4, 'ปรับปรุงตามข้อเสนอแนะ', 'ปรับปรุงตามความเห็นของผู้ใช้', 10, 1, '2025-07-31 07:38:33'),
(50, 'โปรแกรมเดิม (ตกแต่ง)', 5, 'ส่งมอบและอัปเดตเอกสาร', 'ส่งมอบงานและอัปเดตเอกสาร', 5, 1, '2025-07-31 07:38:33');

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
(1, 12, 2, 'in_progress', 70, '', NULL, '2025-08-01 03:18:11', NULL, NULL, NULL, '2025-07-30 08:39:45', '2025-08-01 04:27:27'),
(2, 13, 2, 'in_progress', 50, '', '2025-07-30 08:42:05', '2025-08-01 02:57:10', '2025-07-30', NULL, '2025-07-30 15:42:05', '2025-07-30 08:42:05', '2025-08-01 02:57:12');

--
-- Triggers `tasks`
--
DELIMITER $$
CREATE TRIGGER `create_subtasks_after_task_insert` AFTER INSERT ON `tasks` FOR EACH ROW BEGIN
    DECLARE service_name VARCHAR(255);
    DECLARE done INT DEFAULT FALSE;
    DECLARE step_order INT;
    DECLARE step_name VARCHAR(255);
    DECLARE step_description TEXT;
    DECLARE percentage INT;

    DECLARE template_cursor CURSOR FOR 
        SELECT st.step_order, st.step_name, st.step_description, st.percentage
        FROM subtask_templates st
        JOIN service_requests sr ON sr.id = NEW.service_request_id
        JOIN services s ON s.id = sr.service_id
        WHERE st.service_type = s.name AND st.is_active = 1
        ORDER BY st.step_order;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    -- ดึงชื่อบริการ
    SELECT s.name INTO service_name
    FROM service_requests sr
    JOIN services s ON s.id = sr.service_id
    WHERE sr.id = NEW.service_request_id;

    -- ถ้าเป็นงาน development และไม่ใช่งานส่วนตัว
    IF service_name LIKE 'โปรแกรม%' AND NEW.service_request_id IS NOT NULL THEN
        OPEN template_cursor;
        read_loop: LOOP
            FETCH template_cursor INTO step_order, step_name, step_description, percentage;
            IF done THEN
                LEAVE read_loop;
            END IF;

            INSERT INTO task_subtasks (task_id, step_order, step_name, step_description, percentage, status)
            VALUES (NEW.id, step_order, step_name, step_description, percentage, 'pending');
        END LOOP;
        CLOSE template_cursor;
    END IF;
END
$$
DELIMITER ;

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
(1, 1, '', 'received', 2, '', '2025-07-30 08:41:38'),
(2, 1, '', 'in_progress', 2, '', '2025-07-30 08:41:40'),
(3, 2, '', 'completed', 2, '', '2025-07-30 08:42:10'),
(4, 1, '', 'received', 2, '', '2025-07-31 01:14:52'),
(5, 1, '', 'in_progress', 2, '', '2025-07-31 01:14:53'),
(6, 1, '', 'on_hold', 2, '', '2025-07-31 01:15:02'),
(7, 1, '', 'in_progress', 2, '', '2025-07-31 01:15:03'),
(8, 1, '', 'received', 2, '', '2025-07-31 06:29:30'),
(9, 1, '', 'in_progress', 2, '', '2025-07-31 06:29:39'),
(10, 1, '', 'received', 2, '', '2025-07-31 06:29:40'),
(11, 1, '', 'in_progress', 2, '', '2025-07-31 06:29:41'),
(12, 1, '', 'received', 2, '', '2025-07-31 06:29:42'),
(13, 1, '', 'in_progress', 2, '', '2025-07-31 06:29:48'),
(14, 1, '', 'received', 2, '', '2025-07-31 06:30:45'),
(15, 1, '', 'in_progress', 2, '', '2025-07-31 06:30:46'),
(16, 2, '', 'on_hold', 2, '', '2025-07-31 06:30:48'),
(17, 2, '', 'completed', 2, '', '2025-07-31 06:30:49'),
(18, 2, '', 'on_hold', 2, '', '2025-07-31 06:30:50'),
(19, 1, '', 'received', 2, '', '2025-07-31 06:30:52'),
(20, 1, '', 'in_progress', 2, '', '2025-07-31 06:30:52'),
(27, 2, '', 'received', 2, '', '2025-07-31 06:39:19'),
(28, 2, '', 'on_hold', 2, '', '2025-07-31 06:39:20'),
(29, 2, '', 'in_progress', 2, '', '2025-07-31 06:40:51'),
(30, 1, '', 'on_hold', 2, '', '2025-07-31 06:41:01'),
(32, 2, '', 'in_progress', 2, '', '2025-07-31 06:42:04'),
(33, 1, '', 'on_hold', 2, '', '2025-07-31 06:42:05'),
(34, 1, '', 'on_hold', 2, '', '2025-07-31 06:42:10'),
(35, 2, '', 'completed', 2, '', '2025-07-31 06:44:39'),
(42, 1, '', 'in_progress', 2, '', '2025-07-31 06:45:28'),
(43, 1, '', 'received', 2, '', '2025-07-31 06:45:29'),
(46, 1, '', 'in_progress', 2, '', '2025-07-31 06:45:33'),
(48, 1, '', 'on_hold', 2, '', '2025-07-31 06:45:37'),
(51, 1, '', 'on_hold', 2, '', '2025-07-31 06:46:23'),
(53, 1, '', 'in_progress', 2, '', '2025-07-31 06:46:44'),
(55, 1, '', 'in_progress', 2, '', '2025-07-31 06:46:48'),
(57, 1, '', 'in_progress', 2, '', '2025-07-31 06:46:51'),
(59, 1, '', 'in_progress', 2, '', '2025-07-31 06:47:34'),
(62, 1, '', 'received', 2, '', '2025-07-31 06:51:40'),
(63, 1, '', 'in_progress', 2, '', '2025-07-31 06:51:56'),
(64, 1, '', 'on_hold', 2, '', '2025-07-31 06:51:56'),
(65, 1, '', 'received', 2, '', '2025-07-31 06:51:57'),
(66, 1, '', 'received', 2, '', '2025-07-31 06:52:15'),
(67, 1, '', 'in_progress', 2, '', '2025-07-31 06:52:16'),
(68, 1, '', 'on_hold', 2, '', '2025-07-31 06:52:17'),
(69, 1, '', 'in_progress', 2, '', '2025-07-31 06:52:18'),
(70, 1, '', 'in_progress', 2, '', '2025-07-31 06:52:19'),
(71, 1, '', 'in_progress', 2, '', '2025-07-31 06:52:20'),
(72, 1, '', 'in_progress', 2, '', '2025-07-31 06:52:21'),
(73, 1, '', 'in_progress', 2, '', '2025-07-31 06:52:40'),
(74, 1, '', 'received', 2, '', '2025-07-31 06:52:46'),
(75, 1, '', 'received', 2, '', '2025-07-31 06:53:09'),
(78, 1, '', 'received', 2, '', '2025-07-31 07:11:31'),
(79, 1, '', 'in_progress', 2, '', '2025-07-31 07:30:52'),
(80, 1, '', 'in_progress', 2, '', '2025-07-31 07:30:56'),
(83, 1, '', 'received', 2, '', '2025-07-31 07:31:21'),
(84, 1, '', 'received', 2, '', '2025-07-31 07:32:35'),
(85, 1, '', 'received', 2, '', '2025-07-31 07:54:09'),
(86, 1, '', 'received', 2, '', '2025-07-31 07:54:55'),
(87, 1, '', 'received', 2, '', '2025-07-31 07:54:57'),
(88, 1, '', 'in_progress', 2, '', '2025-07-31 07:54:58'),
(89, 1, '', 'in_progress', 2, '', '2025-07-31 08:15:15'),
(90, 1, '', 'received', 2, '', '2025-07-31 08:15:18'),
(91, 1, '', 'in_progress', 2, '', '2025-07-31 08:15:26'),
(92, 1, '', 'completed', 2, '', '2025-07-31 09:00:02'),
(93, 2, '', 'on_hold', 2, '', '2025-07-31 09:00:11'),
(94, 1, '', 'on_hold', 2, '', '2025-07-31 09:00:11'),
(95, 1, '', 'completed', 2, '', '2025-07-31 09:00:13'),
(96, 1, '', 'on_hold', 2, '', '2025-07-31 09:00:17'),
(97, 1, '', 'in_progress', 2, '', '2025-07-31 09:00:19'),
(98, 1, '', 'on_hold', 2, '', '2025-07-31 09:00:21'),
(99, 1, '', 'in_progress', 2, '', '2025-07-31 09:00:22'),
(100, 1, '', 'in_progress', 2, '', '2025-07-31 09:12:03'),
(101, 2, '', 'in_progress', 2, '', '2025-07-31 09:13:50'),
(102, 1, '', 'in_progress', 2, '', '2025-07-31 09:13:51'),
(103, 1, '', 'received', 2, '', '2025-07-31 10:11:39'),
(104, 1, '', 'in_progress', 2, '', '2025-07-31 10:11:43'),
(105, 1, '', 'received', 2, '', '2025-07-31 10:11:45'),
(106, 1, '', 'in_progress', 2, '', '2025-07-31 10:11:47'),
(107, 1, '', 'received', 2, '', '2025-07-31 10:11:48'),
(108, 1, '', 'in_progress', 2, '', '2025-07-31 10:11:50'),
(109, 1, '', 'received', 2, '', '2025-07-31 10:11:51'),
(110, 1, '', 'in_progress', 2, '', '2025-07-31 10:11:57'),
(111, 1, '', 'received', 2, '', '2025-07-31 10:11:58'),
(112, 1, '', 'in_progress', 2, '', '2025-08-01 02:49:22'),
(113, 1, '', 'received', 2, '', '2025-08-01 02:49:23'),
(114, 2, '', 'completed', 2, '', '2025-08-01 02:57:10'),
(115, 2, '', 'in_progress', 2, '', '2025-08-01 02:57:12'),
(116, 1, '', 'in_progress', 2, '', '2025-08-01 03:18:00'),
(117, 1, '', 'completed', 2, '', '2025-08-01 03:18:07'),
(118, 1, '', 'in_progress', 2, '', '2025-08-01 03:18:10'),
(119, 1, '', 'completed', 2, '', '2025-08-01 03:18:11'),
(120, 1, '', 'in_progress', 2, '', '2025-08-01 03:18:16');

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
(44, 1, 1, 'ส่วนหน้าบ้าน (Frontend)', 'พัฒนา HTML, CSS, JavaScript สำหรับส่วนติดต่อผู้ใช้', 20, 'completed', '2025-08-01 04:27:02', '2025-08-01 04:27:08', '่า่า', '2025-08-01 04:24:53', '2025-08-01 04:27:08'),
(46, 1, 2, 'ส่วนหลังบ้าน (Backend)', 'พัฒนา API, Database, Logic ส่วนหลังบ้าน', 30, 'completed', '2025-08-01 04:27:14', '2025-08-01 04:27:18', '56464564', '2025-08-01 04:24:56', '2025-08-01 04:27:18'),
(48, 1, 3, 'ทดสอบระบบ', 'ทดสอบการทำงานของระบบและแก้ไข Bug', 20, 'completed', '2025-08-01 04:27:22', '2025-08-01 04:27:27', '561561561', '2025-08-01 04:24:59', '2025-08-01 04:27:27'),
(50, 1, 4, 'ทดสอบผู้ใช้', 'ทดสอบกับผู้ใช้จริงและปรับปรุง', 20, 'pending', NULL, NULL, NULL, '2025-08-01 04:25:21', '2025-08-01 04:25:21'),
(52, 1, 5, 'เตรียมเอกสารและส่งมอบ', 'จัดทำเอกสารและส่งมอบระบบ', 10, 'pending', NULL, NULL, NULL, '2025-08-01 04:25:23', '2025-08-01 04:25:23');

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
(2, 'Ucdeb083644ac0ea108e85ba498a1c784', 'สัภยา5', 'เกสร', 'ผู้จัดการเเผนก', 'sappaya@gmail.com', '2025-07-21 03:35:15', '088777777', 'seniorgm', NULL, 'แผนกพัฒนาระบบงาน (RDC.นครสวรรค์)', 1, '7884657'),
(3, 'Udev001', 'พี่เอ็ม', 'developer', 'นักพัฒนา', 'somchai.dev@example.com', '2025-07-21 06:50:40', '0811111111', 'developer', NULL, NULL, 1, NULL),
(4, 'Udev002', 'มาร์ช', 'developer', 'นักพัฒนา', 'somying.dev@example.com', '2025-07-21 06:50:40', '0822222222', 'developer', NULL, NULL, 1, NULL),
(6, 'U04bc163e497a4e5e929b426356d1d599', 'Test010', 'Test010', NULL, 'test1@gmail.com', '2025-07-25 06:25:26', '08887898784', 'divmgr', NULL, NULL, 1, NULL),
(7, 'U98f1b10aebfb7778015146b266640344', 'พี่เอ็ม', 'DEV', 'เจ้าหน้าที่', 'test2@gmail.com', '2025-07-25 07:42:20', '09875678743', 'user', NULL, 'แผนกพัฒนาระบบงาน', 1, '123456'),
(9, 'Ugmapprover001', 'ผู้จัดการทั่วไป', 'ทดสอบ', 'ผู้จัดการทั่วไป', 'gm@test.com', '2025-07-29 04:20:12', '0800000002', 'gmapprover', NULL, 'Management', 1, NULL),
(10, 'Udivmgr002', 'ผู้จัดการฝ่าย', 'ทดสอบ 2', 'ผู้จัดการฝ่าย', 'divmgr2@test.com', '2025-07-29 08:00:31', '0800000002', 'divmgr', NULL, 'Management', 1, NULL);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subtask_templates_service_type` (`service_type`),
  ADD KEY `idx_subtask_templates_step_order` (`step_order`);

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
  ADD UNIQUE KEY `unique_task_step` (`task_id`,`step_order`),
  ADD KEY `idx_task_subtasks_task_id` (`task_id`),
  ADD KEY `idx_task_subtasks_step_order` (`step_order`),
  ADD KEY `idx_task_subtasks_status` (`status`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=368;

--
-- AUTO_INCREMENT for table `assignor_approvals`
--
ALTER TABLE `assignor_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `div_mgr_approvals`
--
ALTER TABLE `div_mgr_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `document_numbers`
--
ALTER TABLE `document_numbers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `document_status_logs`
--
ALTER TABLE `document_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `gm_approvals`
--
ALTER TABLE `gm_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `request_attachments`
--
ALTER TABLE `request_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `senior_gm_approvals`
--
ALTER TABLE `senior_gm_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `subtask_logs`
--
ALTER TABLE `subtask_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `subtask_templates`
--
ALTER TABLE `subtask_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `task_status_logs`
--
ALTER TABLE `task_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `task_subtasks`
--
ALTER TABLE `task_subtasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `user_reviews`
--
ALTER TABLE `user_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  ADD CONSTRAINT `fk_document_numbers_service_request` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_requests_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_requests_ibfk_3` FOREIGN KEY (`assigned_div_mgr_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `subtask_logs`
--
ALTER TABLE `subtask_logs`
  ADD CONSTRAINT `subtask_logs_ibfk_1` FOREIGN KEY (`subtask_id`) REFERENCES `task_subtasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subtask_logs_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `task_subtasks`
--
ALTER TABLE `task_subtasks`
  ADD CONSTRAINT `task_subtasks_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

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
