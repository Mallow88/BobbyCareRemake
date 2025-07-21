-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 21, 2025 at 11:59 AM
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
(29, 1, 'แก้ไขผู้ใช้ ID 2', '2025-07-21 09:16:27');

-- --------------------------------------------------------

--
-- Table structure for table `approval_logs`
--

CREATE TABLE `approval_logs` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
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

INSERT INTO `approval_logs` (`id`, `service_request_id`, `assigned_to_user_id`, `status`, `reason`, `approved_by_assignor_id`, `created_at`, `div_mgr_approval_status`, `div_mgr_approval_reason`) VALUES
(8, 12, 3, 'approved', NULL, 2, '2025-07-21 09:17:26', 'approved', ''),
(9, 14, NULL, 'rejected', 'ไม่ผ่าน', 2, '2025-07-21 09:23:11', 'approved', '');

-- --------------------------------------------------------

--
-- Table structure for table `div_mgr_logs`
--

CREATE TABLE `div_mgr_logs` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `status` enum('approved','rejected') NOT NULL,
  `reason` text DEFAULT NULL,
  `approved_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `div_mgr_logs`
--

INSERT INTO `div_mgr_logs` (`id`, `service_request_id`, `status`, `reason`, `approved_by`, `created_at`) VALUES
(7, 12, 'approved', '', 2, '2025-07-21 09:15:14'),
(8, 13, 'rejected', 'ไม่ผ่าน', 2, '2025-07-21 09:15:24'),
(9, 14, 'approved', '', 2, '2025-07-21 09:15:30');

-- --------------------------------------------------------

--
-- Table structure for table `service_requests`
--

CREATE TABLE `service_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed','rejected') DEFAULT 'pending',
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
(18, 1, 'ทดสอบ7', 'ทดสอบ77', 'pending', NULL, NULL, '2025-07-21 09:14:29', '2025-07-21 09:14:29');

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
(1, 'U0770f6d5ea92bb7de46d36d18bf976c5', 'DEV', 'NS ', NULL, 'test@gmail.com', '2025-07-21 02:09:31', '08887898784', 'user', NULL),
(2, 'Ucdeb083644ac0ea108e85ba498a1c784', 'สัภยา', 'เกสร', 'ผู้จัดการเเผนก', 'sappaya@gmail.com', '2025-07-21 03:35:15', '088777777', 'assignor', NULL),
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
  ADD KEY `approved_by_assignor_id` (`approved_by_assignor_id`);

--
-- Indexes for table `div_mgr_logs`
--
ALTER TABLE `div_mgr_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_request_id` (`service_request_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_to_admin_id` (`assigned_to_admin_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `approval_logs`
--
ALTER TABLE `approval_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `div_mgr_logs`
--
ALTER TABLE `div_mgr_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

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
  ADD CONSTRAINT `approval_logs_ibfk_3` FOREIGN KEY (`approved_by_assignor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `div_mgr_logs`
--
ALTER TABLE `div_mgr_logs`
  ADD CONSTRAINT `div_mgr_logs_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `div_mgr_logs_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_requests_ibfk_2` FOREIGN KEY (`assigned_to_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
