-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 23, 2025 at 12:04 PM
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
(1, 1, 'Login', '2025-07-21 04:03:01'),
(2, 1, 'Login', '2025-07-21 04:04:01'),
(3, 1, 'Login', '2025-07-21 04:04:48'),
(4, 1, 'Login', '2025-07-21 04:05:24'),
(5, 1, 'Login', '2025-07-21 04:09:00'),
(6, 1, 'Login', '2025-07-21 04:11:32'),
(7, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-21 04:13:25'),
(8, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-21 04:13:33'),
(9, 1, 'Login', '2025-07-21 08:16:03'),
(10, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-21 08:17:58'),
(11, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-21 08:18:36'),
(12, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-21 08:19:59'),
(13, 1, 'Login', '2025-07-21 08:35:19'),
(14, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-21 08:35:34'),
(15, 1, 'Login', '2025-07-21 08:37:16'),
(16, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-21 08:37:27'),
(17, 1, 'Login', '2025-07-21 08:39:42'),
(18, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-21 08:39:49'),
(19, 1, 'Login', '2025-07-21 08:44:28'),
(20, 1, 'Login', '2025-07-21 08:47:57'),
(21, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-21 08:50:48'),
(22, 1, 'Login', '2025-07-21 08:53:04'),
(23, 1, 'แก้ไขผู้ใช้ ID 5', '2025-07-21 08:53:18'),
(24, 1, 'Login', '2025-07-21 08:54:01'),
(25, 1, 'แก้ไขผู้ใช้ ID 5', '2025-07-21 08:54:10'),
(26, 1, 'Login', '2025-07-21 09:12:02'),
(27, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-21 09:12:21'),
(28, 1, 'Login', '2025-07-21 09:16:17'),
(29, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-21 09:16:27'),
(30, 1, 'Login', '2025-07-22 01:44:18'),
(31, 1, 'Login', '2025-07-22 01:44:47'),
(32, 1, 'Login', '2025-07-22 01:45:14'),
(33, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-22 01:45:26'),
(34, 1, 'Login', '2025-07-22 02:07:15'),
(35, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-22 02:07:26'),
(36, 1, 'Login', '2025-07-22 02:11:34'),
(37, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-22 02:11:44'),
(38, 1, 'Login', '2025-07-22 02:29:38'),
(39, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-22 02:29:59'),
(40, 1, 'Login', '2025-07-22 02:36:46'),
(41, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-22 02:36:54'),
(42, 1, 'Login', '2025-07-22 03:53:41'),
(43, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-22 03:53:56'),
(44, 1, 'Login', '2025-07-22 03:56:33'),
(45, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-22 03:56:41'),
(46, 1, 'Login', '2025-07-22 06:35:26'),
(47, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-22 06:35:41'),
(48, 1, 'Login', '2025-07-22 06:36:57'),
(49, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-22 06:37:19'),
(50, 1, 'Login', '2025-07-22 06:46:50'),
(51, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-22 06:47:01'),
(52, 1, 'Login', '2025-07-22 06:58:05'),
(53, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-22 06:58:13'),
(54, 1, 'Login', '2025-07-22 08:06:14'),
(55, 1, 'Login', '2025-07-22 09:08:16'),
(56, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-22 09:08:29'),
(57, 1, 'Login', '2025-07-22 09:09:34'),
(58, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-22 09:10:01'),
(59, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-22 09:10:20'),
(60, 1, 'Login', '2025-07-22 09:14:25'),
(61, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-22 09:14:40'),
(62, 1, 'Login', '2025-07-22 09:16:23'),
(63, 1, 'Login', '2025-07-22 09:27:42'),
(64, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-22 09:27:51'),
(65, 1, 'Login', '2025-07-22 09:31:47'),
(66, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-22 09:31:58'),
(67, 1, 'Login', '2025-07-22 09:34:55'),
(68, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-22 09:35:03'),
(69, 1, 'Login', '2025-07-23 01:58:25'),
(70, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-23 01:58:43'),
(71, 1, 'Login', '2025-07-23 02:32:00'),
(72, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-23 02:32:50'),
(73, 1, 'Login', '2025-07-23 02:37:32'),
(74, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-23 02:37:43'),
(75, 1, 'Login', '2025-07-23 02:37:53'),
(76, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 02:38:02'),
(77, 1, 'Login', '2025-07-23 02:47:52'),
(78, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 02:48:02'),
(79, 1, 'Login', '2025-07-23 02:53:45'),
(80, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 02:53:52'),
(81, 1, 'Login', '2025-07-23 02:58:07'),
(82, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 02:58:22'),
(83, 1, 'Login', '2025-07-23 03:00:59'),
(84, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:01:06'),
(85, 1, 'Login', '2025-07-23 03:02:48'),
(86, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:03:01'),
(87, 1, 'Login', '2025-07-23 03:07:48'),
(88, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:08:09'),
(89, 1, 'Login', '2025-07-23 03:12:09'),
(90, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:12:16'),
(91, 1, 'Login', '2025-07-23 03:12:52'),
(92, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:13:13'),
(93, 1, 'Login', '2025-07-23 03:15:25'),
(94, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:15:33'),
(95, 1, 'Login', '2025-07-23 03:22:52'),
(96, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:23:01'),
(97, 1, 'Login', '2025-07-23 03:23:46'),
(98, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:24:01'),
(99, 1, 'Login', '2025-07-23 03:24:54'),
(100, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:25:01'),
(101, 1, 'Login', '2025-07-23 03:27:31'),
(102, 1, 'Login', '2025-07-23 03:27:53'),
(103, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:28:02'),
(104, 1, 'Login', '2025-07-23 03:28:49'),
(105, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:28:56'),
(106, 1, 'Login', '2025-07-23 03:30:10'),
(107, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:30:19'),
(108, 1, 'Login', '2025-07-23 03:32:21'),
(109, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:32:31'),
(110, 1, 'Login', '2025-07-23 03:33:24'),
(111, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:33:31'),
(112, 1, 'Login', '2025-07-23 03:34:38'),
(113, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:34:45'),
(114, 1, 'Login', '2025-07-23 03:35:17'),
(115, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:35:25'),
(116, 1, 'Login', '2025-07-23 03:36:35'),
(117, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:36:41'),
(118, 1, 'Login', '2025-07-23 03:38:48'),
(119, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:39:02'),
(120, 1, 'Login', '2025-07-23 03:40:03'),
(121, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:40:54'),
(122, 1, 'Login', '2025-07-23 03:42:00'),
(123, 1, 'Login', '2025-07-23 03:57:38'),
(124, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:57:58'),
(125, 1, 'Login', '2025-07-23 03:58:25'),
(126, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 03:58:34'),
(127, 1, 'Login', '2025-07-23 04:01:59'),
(128, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 04:02:08'),
(129, 1, 'Login', '2025-07-23 04:03:34'),
(130, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 04:03:46'),
(131, 1, 'Login', '2025-07-23 04:04:36'),
(132, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 04:04:51'),
(133, 1, 'Login', '2025-07-23 04:05:21'),
(134, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 04:05:28'),
(135, 1, 'Login', '2025-07-23 04:06:48'),
(136, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 04:06:57'),
(137, 1, 'Login', '2025-07-23 04:25:28'),
(138, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 04:25:36'),
(139, 1, 'Login', '2025-07-23 05:40:13'),
(140, 1, 'Login', '2025-07-23 05:40:18'),
(141, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 05:40:24'),
(142, 1, 'Login', '2025-07-23 05:48:10'),
(143, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 05:48:18'),
(144, 1, 'Login', '2025-07-23 06:05:21'),
(145, 1, 'Login', '2025-07-23 06:51:42'),
(146, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 06:52:05'),
(147, 1, 'Login', '2025-07-23 07:15:23'),
(148, 1, 'Login', '2025-07-23 07:26:21'),
(149, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 07:26:53'),
(150, 1, 'Login', '2025-07-23 08:04:30'),
(151, 1, 'Login', '2025-07-23 08:04:55'),
(152, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 08:05:00'),
(153, 1, 'Login', '2025-07-23 08:30:36'),
(154, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 08:30:47'),
(155, 1, 'Login', '2025-07-23 08:38:33'),
(156, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 08:38:41'),
(157, 1, 'Login', '2025-07-23 08:42:20'),
(158, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 08:42:39'),
(159, 1, 'Login', '2025-07-23 08:43:09'),
(160, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 08:43:14'),
(161, 1, 'Login', '2025-07-23 08:43:59'),
(162, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 08:44:08'),
(163, 1, 'Login', '2025-07-23 08:46:18'),
(164, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 08:46:40'),
(165, 1, 'Login', '2025-07-23 08:51:39'),
(166, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 08:51:48'),
(167, 1, 'Login', '2025-07-23 08:54:18'),
(168, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 08:54:27'),
(169, 1, 'Login', '2025-07-23 08:56:30'),
(170, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 08:56:37'),
(171, 1, 'Login', '2025-07-23 09:04:53'),
(172, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 09:05:03'),
(173, 1, 'Login', '2025-07-23 09:38:34'),
(174, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 09:38:42'),
(175, 1, 'Login', '2025-07-23 09:43:20'),
(176, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 09:43:36'),
(177, 1, 'Login', '2025-07-23 09:43:58'),
(178, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 09:44:05'),
(179, 1, 'Login', '2025-07-23 09:59:29'),
(180, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-23 09:59:35');

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
  `estimated_hours` int(11) DEFAULT NULL,
  `priority_level` enum('low','medium','high','urgent') DEFAULT 'medium',
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignor_approvals`
--

INSERT INTO `assignor_approvals` (`id`, `service_request_id`, `assignor_user_id`, `assigned_developer_id`, `status`, `reason`, `estimated_hours`, `priority_level`, `reviewed_at`, `created_at`) VALUES
(1, 26, 1, 1, 'approved', 'ผู้จัดการแผนก', 6, 'high', '2025-07-23 03:07:08', '2025-07-23 03:07:08'),
(2, 25, 1, 1, 'approved', 'ผู้จัดการแผนก2', 24, 'urgent', '2025-07-23 03:07:38', '2025-07-23 03:07:38'),
(3, 27, 1, 1, 'approved', 'ผู้จัดการแผนก1', 36, 'high', '2025-07-23 03:31:39', '2025-07-23 03:31:39'),
(4, 28, 1, 1, 'approved', 'ผู้จัดการแผนก2', 72, 'urgent', '2025-07-23 03:32:04', '2025-07-23 03:32:04'),
(6, 23, 1, 1, 'approved', '78787878', 8, 'urgent', '2025-07-23 04:03:17', '2025-07-23 04:03:17'),
(7, 31, 1, 1, 'approved', 'เอกสารผู้จัดการแผนก', 32, 'high', '2025-07-23 08:51:23', '2025-07-23 08:51:23');

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
(9, 31, 1, 'approved', 'ผ่าน', '2025-07-23 08:46:10', '2025-07-23 08:46:10');

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
(29, 31, 'senior_gm_review', 'approved', 1, 'seniorgm', 'ผู้จัดการอาวุโส เอกสาร รีบๆทำเนาะ', '2025-07-23 08:56:19');

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
(7, 31, 1, 'approved', 'เอกสาร อนุมัติคำขอ (GM)', 20000.00, '2025-07-23 08:53:41', '2025-07-23 08:53:41');

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
(1, 31, '131317.pdf', '31_1753260224_0.pdf', 59743, 'pdf', '2025-07-23 08:43:44');

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
(6, 31, 1, 'approved', 'ผู้จัดการอาวุโส เอกสาร', 'รีบๆทำเนาะ', '2025-07-23 08:56:19', '2025-07-23 08:56:19');

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
  `estimated_hours` int(11) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `developer_status` enum('not_assigned','pending','received','in_progress','on_hold','completed','user_review','accepted') DEFAULT 'not_assigned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_requests`
--

INSERT INTO `service_requests` (`id`, `user_id`, `title`, `description`, `status`, `rejection_reason`, `assigned_to_admin_id`, `created_at`, `updated_at`, `current_step`, `priority`, `estimated_hours`, `deadline`, `developer_status`) VALUES
(23, 1, 'D44', 'DDD', 'approved', NULL, NULL, '2025-07-22 09:09:23', '2025-07-23 09:42:26', 'senior_gm_approved', 'urgent', 8, NULL, 'completed'),
(24, 1, 'E55', '55', 'rejected', NULL, NULL, '2025-07-22 09:09:30', '2025-07-23 02:59:55', 'div_mgr_rejected', 'medium', NULL, NULL, 'not_assigned'),
(25, 1, 'ทดสอบbolt', 'bolt1', 'approved', NULL, NULL, '2025-07-23 02:57:11', '2025-07-23 03:15:07', 'senior_gm_approved', 'urgent', 24, NULL, 'not_assigned'),
(26, 1, 'bolt2', 'bolt22', 'rejected', NULL, NULL, '2025-07-23 02:57:24', '2025-07-23 03:12:03', 'gm_rejected', 'high', 6, NULL, 'not_assigned'),
(27, 1, 'bolt3', 'bolt333', 'approved', NULL, NULL, '2025-07-23 03:26:22', '2025-07-23 09:58:53', 'senior_gm_approved', 'high', 36, NULL, 'received'),
(28, 1, 'ระบบล่ม', 'เชื่อมเนตไม่ได้', 'approved', NULL, NULL, '2025-07-23 03:26:41', '2025-07-23 04:25:00', 'senior_gm_approved', 'urgent', 72, NULL, 'accepted'),
(29, 1, '123', '123', 'assignor_review', NULL, NULL, '2025-07-23 03:57:22', '2025-07-23 03:58:11', 'div_mgr_approved', 'medium', NULL, NULL, 'not_assigned'),
(30, 1, 'ทดเอกสารไฟล์', 'ทดเอกสารไฟล์', 'pending', NULL, NULL, '2025-07-23 08:29:26', '2025-07-23 08:29:26', 'user_submitted', 'medium', NULL, NULL, 'not_assigned'),
(31, 1, 'เอกสาร1', 'เอกสาร11', 'approved', NULL, NULL, '2025-07-23 08:43:44', '2025-07-23 09:05:53', 'senior_gm_approved', 'high', 32, NULL, 'accepted');

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
(1, 27, 1, 'received', 10, '', '2025-07-23 03:36:15', NULL, NULL, NULL, '2025-07-23 03:34:15', '2025-07-23 09:58:53', '2025-07-23 10:36:15'),
(2, 28, 1, 'accepted', 100, NULL, '2025-07-23 03:35:44', NULL, NULL, NULL, '2025-07-23 03:34:30', '2025-07-23 04:25:00', '2025-07-23 10:35:44'),
(4, 23, 1, 'completed', 100, '', '2025-07-23 04:06:11', '2025-07-23 09:42:26', NULL, NULL, '2025-07-23 04:05:14', '2025-07-23 09:42:26', '2025-07-23 11:06:11'),
(5, 31, 1, 'accepted', 100, '', NULL, '2025-07-23 09:04:35', NULL, NULL, '2025-07-23 08:56:19', '2025-07-23 09:05:53', NULL);

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
(44, 1, '', 'received', 1, '', '2025-07-23 09:58:53');

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
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `line_id`, `name`, `lastname`, `position`, `email`, `created_at`, `phone`, `role`, `created_by_admin_id`, `department`, `is_active`) VALUES
(1, 'U0770f6d5ea92bb7de46d36d18bf976c5', 'DEV', 'NS ', NULL, 'test@gmail.com', '2025-07-21 02:09:31', '08887898784', 'user', NULL, NULL, 1),
(2, 'Ucdeb083644ac0ea108e85ba498a1c784', 'สัภยา', 'เกสร', 'ผู้จัดการเเผนก', 'sappaya@gmail.com', '2025-07-21 03:35:15', '088777777', 'assignor', NULL, NULL, 1),
(3, 'Udev001', 'พี่เอ็ม', 'developer', 'นักพัฒนา', 'somchai.dev@example.com', '2025-07-21 06:50:40', '0811111111', 'developer', NULL, NULL, 1),
(4, 'Udev002', 'มาร์ช', 'developer', 'นักพัฒนา', 'somying.dev@example.com', '2025-07-21 06:50:40', '0822222222', 'developer', NULL, NULL, 1),
(5, 'U98f1b10aebfb7778015146b266640344', 'พี่เอม', 'พี่', NULL, 'a@gmail.com', '2025-07-21 08:52:27', '', 'assignor', NULL, NULL, 1);

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
(2, 5, 1, 5, 'ดีๆ', 'accepted', '', '2025-07-23 09:05:53', '2025-07-23 09:05:53');

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
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_to_admin_id` (`assigned_to_admin_id`),
  ADD KEY `idx_service_requests_status` (`status`),
  ADD KEY `idx_service_requests_current_step` (`current_step`),
  ADD KEY `idx_service_requests_dev_status` (`developer_status`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT for table `assignor_approvals`
--
ALTER TABLE `assignor_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `div_mgr_approvals`
--
ALTER TABLE `div_mgr_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `document_status_logs`
--
ALTER TABLE `document_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `gm_approvals`
--
ALTER TABLE `gm_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `request_attachments`
--
ALTER TABLE `request_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `senior_gm_approvals`
--
ALTER TABLE `senior_gm_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `task_status_logs`
--
ALTER TABLE `task_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_reviews`
--
ALTER TABLE `user_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  ADD CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_requests_ibfk_2` FOREIGN KEY (`assigned_to_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
