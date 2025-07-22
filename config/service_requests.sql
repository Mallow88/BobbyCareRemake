-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 22, 2025 at 10:19 AM
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
(18, 1, 'ทดสอบ7', 'ทดสอบ77', 'pending', NULL, NULL, '2025-07-21 09:14:29', '2025-07-21 09:14:29'),
(19, 1, 'สร้างโปรเเกรมใหม่', 'โปรเเกรมคิดคำนวณ', '', NULL, NULL, '2025-07-22 06:35:02', '2025-07-22 08:19:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_to_admin_id` (`assigned_to_admin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_requests_ibfk_2` FOREIGN KEY (`assigned_to_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
