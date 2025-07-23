-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 23, 2025 at 03:46 AM
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
(68, 1, 'แก้ไขผู้ใช้ ID 1', '2025-07-22 09:35:03');

-- --------------------------------------------------------

--
-- Table structure for table `approval_logs`
--

CREATE TABLE `approval_logs` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `assignor_id` int(11) DEFAULT NULL,
  `assigned_to_user_id` int(11) DEFAULT NULL,
  `status` enum('approved','rejected') NOT NULL,
  `reason` text DEFAULT NULL,
  `approved_by_assignor_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `div_mgr_approval_status` enum('approved','rejected') DEFAULT NULL,
  `div_mgr_approval_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_logs`
--

INSERT INTO `approval_logs` (`id`, `service_request_id`, `assignor_id`, `assigned_to_user_id`, `status`, `reason`, `approved_by_assignor_id`, `created_at`, `div_mgr_approval_status`, `div_mgr_approval_reason`) VALUES
(8, 12, NULL, 3, 'approved', NULL, 2, '2025-07-21 09:17:26', 'approved', ''),
(10, 19, NULL, 1, 'approved', NULL, 2, '2025-07-22 06:46:11', 'approved', 'ดำเนินเรื่องครับ'),
(11, 20, NULL, 1, 'approved', 'เเผนก1', 2, '2025-07-22 09:15:24', 'approved', 'ฝ่าย1'),
(12, 21, NULL, 1, 'approved', 'เเผนก2', 2, '2025-07-22 09:15:44', 'approved', 'ฝ่าย2'),
(13, 14, NULL, NULL, 'rejected', 'เเผนก456', 2, '2025-07-22 09:15:58', 'approved', ''),
(14, 22, NULL, 1, 'approved', 'เเผนก3', 2, '2025-07-22 09:16:08', 'approved', 'ฝ่าย3');

-- --------------------------------------------------------

--
-- Table structure for table `div_mgr_logs`
--

CREATE TABLE `div_mgr_logs` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `div_mgr_user_id` int(11) DEFAULT NULL,
  `status` enum('approved','rejected') NOT NULL,
  `reason` text DEFAULT NULL,
  `approved_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `div_mgr_logs`
--

INSERT INTO `div_mgr_logs` (`id`, `service_request_id`, `div_mgr_user_id`, `status`, `reason`, `approved_by`, `created_at`) VALUES
(7, 12, NULL, 'approved', '', 2, '2025-07-21 09:15:14'),
(8, 13, NULL, 'rejected', 'ไม่ผ่าน', 2, '2025-07-21 09:15:24'),
(9, 14, NULL, 'approved', '', 2, '2025-07-21 09:15:30'),
(10, 19, NULL, 'approved', 'ดำเนินเรื่องครับ', 2, '2025-07-22 06:36:39'),
(11, 20, NULL, 'approved', 'ฝ่าย1', 2, '2025-07-22 09:13:38'),
(12, 21, NULL, 'approved', 'ฝ่าย2', 2, '2025-07-22 09:13:58'),
(13, 22, NULL, 'approved', 'ฝ่าย3', 2, '2025-07-22 09:14:16');

-- --------------------------------------------------------

--
-- Table structure for table `gm_approval_logs`
--

CREATE TABLE `gm_approval_logs` (
  `id` int(11) NOT NULL,
  `approval_log_id` int(11) NOT NULL,
  `status` enum('approved','rejected') NOT NULL,
  `reason` text DEFAULT NULL,
  `gm_user_id` int(11) DEFAULT NULL,
  `dept_mgr_status` enum('approved','rejected') DEFAULT NULL,
  `dept_mgr_reason` text DEFAULT NULL,
  `div_mgr_approval_status` enum('approved','rejected') DEFAULT NULL,
  `div_mgr_approval_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gm_approval_logs`
--

INSERT INTO `gm_approval_logs` (`id`, `approval_log_id`, `status`, `reason`, `gm_user_id`, `dept_mgr_status`, `dept_mgr_reason`, `div_mgr_approval_status`, `div_mgr_approval_reason`, `created_at`) VALUES
(1, 8, 'approved', '', 2, 'approved', NULL, 'approved', '', '2025-07-22 03:33:30'),
(2, 10, 'approved', 'ผ่านครับ', 2, 'approved', NULL, 'approved', 'ดำเนินเรื่องครับ', '2025-07-22 06:49:57'),
(3, 11, 'approved', 'gm1', 2, 'approved', 'เเผนก1', 'approved', 'ฝ่าย1', '2025-07-22 09:29:17'),
(4, 12, 'approved', 'gm2', 2, 'approved', 'เเผนก2', 'approved', 'ฝ่าย2', '2025-07-22 09:29:31'),
(5, 14, 'approved', 'gm3', 2, 'approved', 'เเผนก3', 'approved', 'ฝ่าย3', '2025-07-22 09:29:44');

-- --------------------------------------------------------

--
-- Table structure for table `senior_approval_logs`
--

CREATE TABLE `senior_approval_logs` (
  `id` int(11) NOT NULL,
  `gm_approval_log_id` int(11) NOT NULL,
  `senior_gm_user_id` int(11) NOT NULL,
  `status` enum('approved','rejected') NOT NULL,
  `reason` text DEFAULT NULL,
  `gm_status` enum('approved','rejected') NOT NULL,
  `gm_reason` text DEFAULT NULL,
  `div_mgr_status` enum('approved','rejected') NOT NULL,
  `div_mgr_reason` text DEFAULT NULL,
  `assignor_status` enum('approved','rejected') NOT NULL,
  `assignor_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `senior_approval_logs`
--

INSERT INTO `senior_approval_logs` (`id`, `gm_approval_log_id`, `senior_gm_user_id`, `status`, `reason`, `gm_status`, `gm_reason`, `div_mgr_status`, `div_mgr_reason`, `assignor_status`, `assignor_reason`, `created_at`) VALUES
(1, 1, 2, 'approved', '', 'approved', NULL, 'approved', NULL, 'approved', NULL, '2025-07-22 04:21:31'),
(2, 2, 2, 'approved', 'สร้างได้เลย', 'approved', NULL, 'approved', NULL, 'approved', NULL, '2025-07-22 06:59:08'),
(3, 3, 2, 'approved', 'gmS1', 'approved', NULL, 'approved', NULL, 'approved', NULL, '2025-07-22 09:32:38'),
(4, 4, 2, 'approved', 'gmS2', 'approved', NULL, 'approved', NULL, 'approved', NULL, '2025-07-22 09:32:51'),
(5, 5, 2, 'approved', 'gmS3', 'approved', NULL, 'approved', NULL, 'approved', NULL, '2025-07-22 09:33:04');

-- --------------------------------------------------------

--
-- Table structure for table `service_requests`
--

CREATE TABLE `service_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','received','in_progress','on_hold','completed','rejected') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `assigned_to_admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_requests`
--

INSERT INTO `service_requests` (`id`, `user_id`, `title`, `description`, `status`, `rejection_reason`, `assigned_to_admin_id`, `created_at`, `updated_at`) VALUES
(12, 1, 'ทดอสอบ1', 'ทดอสอบรายละเอียด1', 'pending', NULL, NULL, '2025-07-21 09:13:03', '2025-07-21 09:13:03'),
(13, 1, 'ทดสอบ2', 'ทดสอบรายละเอียด2', 'pending', NULL, NULL, '2025-07-21 09:13:23', '2025-07-21 09:13:23'),
(14, 1, 'ทดสอบ3', 'ทดสอบ33', 'pending', NULL, NULL, '2025-07-21 09:13:32', '2025-07-21 09:13:32'),
(15, 1, 'ทดสอบ4', 'ทดสอบ44', 'pending', NULL, NULL, '2025-07-21 09:13:42', '2025-07-21 09:13:42'),
(16, 1, 'ทดสอบ5', 'ทดสอบ55', 'pending', NULL, NULL, '2025-07-21 09:13:50', '2025-07-21 09:13:50'),
(17, 1, 'ทดสอบ6', 'ทดสอบ66', 'pending', NULL, NULL, '2025-07-21 09:14:18', '2025-07-21 09:14:18'),
(18, 1, 'ทดสอบ7', 'ทดสอบ77', 'pending', NULL, NULL, '2025-07-21 09:14:29', '2025-07-21 09:14:29'),
(19, 1, 'สร้างโปรเเกรมใหม่', 'โปรเเกรมคิดคำนวณ', 'received', NULL, NULL, '2025-07-22 06:35:02', '2025-07-22 08:59:22'),
(20, 1, 'A1', 'aa', 'received', NULL, NULL, '2025-07-22 09:08:55', '2025-07-22 09:36:02'),
(21, 1, 'B2', 'BB', 'pending', NULL, NULL, '2025-07-22 09:09:06', '2025-07-22 09:09:06'),
(22, 1, 'C33', 'CC', 'pending', NULL, NULL, '2025-07-22 09:09:15', '2025-07-22 09:09:15'),
(23, 1, 'D44', 'DDD', 'pending', NULL, NULL, '2025-07-22 09:09:23', '2025-07-22 09:09:23'),
(24, 1, 'E55', '55', 'pending', NULL, NULL, '2025-07-22 09:09:30', '2025-07-22 09:09:30');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `developer_user_id` int(11) NOT NULL,
  `task_status` enum('pending','received','in_progress','on_hold','completed','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `accepted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `service_request_id`, `developer_user_id`, `task_status`, `created_at`, `updated_at`, `accepted_at`) VALUES
(3, 19, 1, 'in_progress', '2025-07-22 08:59:22', '2025-07-22 09:38:10', '2025-07-22 15:59:22'),
(4, 20, 1, 'on_hold', '2025-07-22 09:36:02', '2025-07-22 09:52:46', '2025-07-22 16:36:02');

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
  `created_by_admin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `line_id`, `name`, `lastname`, `position`, `email`, `created_at`, `phone`, `role`, `created_by_admin_id`) VALUES
(1, 'U0770f6d5ea92bb7de46d36d18bf976c5', 'DEV', 'NS ', NULL, 'test@gmail.com', '2025-07-21 02:09:31', '08887898784', 'developer', NULL),
(2, 'Ucdeb083644ac0ea108e85ba498a1c784', 'สัภยา', 'เกสร', 'ผู้จัดการเเผนก', 'sappaya@gmail.com', '2025-07-21 03:35:15', '088777777', 'seniorgm', NULL),
(3, 'Udev001', 'สมชาย', 'นักพัฒนา', 'นักพัฒนา', 'somchai.dev@example.com', '2025-07-21 06:50:40', '0811111111', 'developer', NULL),
(4, 'Udev002', 'สมหญิง', 'เขียนโค้ด', 'นักพัฒนา', 'somying.dev@example.com', '2025-07-21 06:50:40', '0822222222', 'developer', NULL),
(5, 'U98f1b10aebfb7778015146b266640344', 'พี่เอม', 'พี่', NULL, 'a@gmail.com', '2025-07-21 08:52:27', '', 'assignor', NULL);

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
-- Indexes for table `approval_logs`
--
ALTER TABLE `approval_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_request_id` (`service_request_id`),
  ADD KEY `assigned_to_user_id` (`assigned_to_user_id`),
  ADD KEY `approved_by_assignor_id` (`approved_by_assignor_id`),
  ADD KEY `assignor_id` (`assignor_id`);

--
-- Indexes for table `div_mgr_logs`
--
ALTER TABLE `div_mgr_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_request_id` (`service_request_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `div_mgr_user_id` (`div_mgr_user_id`);

--
-- Indexes for table `gm_approval_logs`
--
ALTER TABLE `gm_approval_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approval_log_id` (`approval_log_id`),
  ADD KEY `gm_user_id` (`gm_user_id`);

--
-- Indexes for table `senior_approval_logs`
--
ALTER TABLE `senior_approval_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gm_approval_log_id` (`gm_approval_log_id`),
  ADD KEY `senior_gm_user_id` (`senior_gm_user_id`);

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_to_admin_id` (`assigned_to_admin_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_request_id` (`service_request_id`),
  ADD KEY `developer_user_id` (`developer_user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `line_id` (`line_id`),
  ADD KEY `fk_users_created_by` (`created_by_admin_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `approval_logs`
--
ALTER TABLE `approval_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `div_mgr_logs`
--
ALTER TABLE `div_mgr_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `gm_approval_logs`
--
ALTER TABLE `gm_approval_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `senior_approval_logs`
--
ALTER TABLE `senior_approval_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
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
-- Constraints for table `approval_logs`
--
ALTER TABLE `approval_logs`
  ADD CONSTRAINT `approval_logs_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `approval_logs_ibfk_2` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `approval_logs_ibfk_3` FOREIGN KEY (`approved_by_assignor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `approval_logs_ibfk_4` FOREIGN KEY (`assignor_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `div_mgr_logs`
--
ALTER TABLE `div_mgr_logs`
  ADD CONSTRAINT `div_mgr_logs_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `div_mgr_logs_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `div_mgr_logs_ibfk_3` FOREIGN KEY (`div_mgr_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `gm_approval_logs`
--
ALTER TABLE `gm_approval_logs`
  ADD CONSTRAINT `gm_approval_logs_ibfk_1` FOREIGN KEY (`approval_log_id`) REFERENCES `approval_logs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `gm_approval_logs_ibfk_2` FOREIGN KEY (`gm_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `senior_approval_logs`
--
ALTER TABLE `senior_approval_logs`
  ADD CONSTRAINT `senior_approval_logs_ibfk_1` FOREIGN KEY (`gm_approval_log_id`) REFERENCES `gm_approval_logs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `senior_approval_logs_ibfk_2` FOREIGN KEY (`senior_gm_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`),
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`developer_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
