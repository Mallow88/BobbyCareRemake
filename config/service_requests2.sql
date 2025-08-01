-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 29, 2025 at 09:02 AM
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
  `related_documents` text DEFAULT NULL COMMENT 'เอกสารการทำงานที่เกี่ยวข้อง',
  `document_number` varchar(50) DEFAULT NULL,
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
  `reference_examples` text DEFAULT NULL COMMENT 'ตัวอย่างอ้างอิง'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_requests`
--

INSERT INTO `service_requests` (`id`, `user_id`, `title`, `description`, `status`, `rejection_reason`, `assigned_to_admin_id`, `created_at`, `updated_at`, `current_step`, `priority`, `estimated_days`, `deadline`, `developer_status`, `work_category`, `expected_benefits`, `assigned_div_mgr_id`, `service_id`, `employee_id`, `current_workflow`, `approach_ideas`, `related_programs`, `current_tools`, `system_impact`, `related_documents`, `document_number`, `program_purpose`, `target_users`, `main_functions`, `data_requirements`, `current_program_name`, `problem_description`, `error_frequency`, `steps_to_reproduce`, `program_name_change`, `data_to_change`, `new_data_value`, `change_reason`, `program_name_function`, `new_functions`, `function_benefits`, `integration_requirements`, `program_name_decorate`, `decoration_type`, `reference_examples`) VALUES
(30, 1, 'ทดเอกสารไฟล์', 'ทดเอกสารไฟล์', 'pending', NULL, NULL, '2025-07-23 08:29:26', '2025-07-23 08:29:26', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(46, 2, 'เทส', 'งานนะ', 'approved', NULL, NULL, '2025-07-25 02:11:15', '2025-07-25 10:07:38', 'developer_self_created', 'urgent', 3, '2025-07-27', 'completed', NULL, NULL, NULL, 9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(48, 2, 'เเก้ระบบbobby', 'เข้าระบบbobbyไม่ได้', 'approved', NULL, NULL, '2025-07-25 02:37:32', '2025-07-25 10:07:13', 'senior_gm_approved', 'high', 2, NULL, 'received', 'RDC', 'กลับมาเริ่มงานได้', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(49, 7, 'พพพพ', 'รรรร', 'div_mgr_review', NULL, NULL, '2025-07-25 07:46:15', '2025-07-25 07:46:15', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', 'RDC', 'นนนนนนนนนน', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(63, 2, 'โปรแกรมเดิม (แก้ปัญหา)', NULL, 'div_mgr_review', NULL, NULL, '2025-07-29 06:27:22', '2025-07-29 06:27:22', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', '', NULL, 2, 2, NULL, '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(64, 2, 'โปรแกรมเดิม (แก้ปัญหา)', NULL, 'div_mgr_review', NULL, NULL, '2025-07-29 06:27:42', '2025-07-29 06:27:42', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', '', NULL, 2, 2, NULL, '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(65, 2, 'โปรแกรมเดิม (แก้ปัญหา)', NULL, 'div_mgr_review', NULL, NULL, '2025-07-29 06:32:26', '2025-07-29 06:32:26', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', '', NULL, 2, 2, NULL, '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(66, 2, 'โปรแกรมเดิม (แก้ปัญหา)', NULL, 'div_mgr_review', NULL, NULL, '2025-07-29 06:33:30', '2025-07-29 06:33:30', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', '', NULL, 2, 2, NULL, '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(67, 2, 'โปรแกรมใหม่', NULL, 'div_mgr_review', NULL, NULL, '2025-07-29 06:33:55', '2025-07-29 06:33:55', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', '', NULL, 2, 1, NULL, 'าีราีร', '', 'าีราีรา', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(68, 2, 'โปรแกรมเดิม (เพิ่มฟังก์ชั่น)', NULL, 'div_mgr_review', NULL, NULL, '2025-07-29 06:34:33', '2025-07-29 06:34:33', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', '', NULL, 2, 4, NULL, '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(69, 2, 'โปรแกรมใหม่', NULL, 'div_mgr_review', NULL, NULL, '2025-07-29 06:35:45', '2025-07-29 06:35:45', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', '', NULL, 2, 1, NULL, 'าัีาัี', '', 'ัีาา', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(70, 2, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', NULL, 'div_mgr_review', NULL, NULL, '2025-07-29 06:39:02', '2025-07-29 06:39:02', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', '', NULL, 2, 3, NULL, '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(71, 2, 'โปรแกรมเดิม (แก้ปัญหา)', NULL, 'div_mgr_review', NULL, NULL, '2025-07-29 06:40:14', '2025-07-29 06:40:14', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', '', NULL, 2, 2, NULL, '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(73, 2, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', NULL, 'div_mgr_review', NULL, NULL, '2025-07-29 06:55:44', '2025-07-29 06:55:44', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', '', NULL, 2, 3, NULL, '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(74, 2, 'โปรแกรมเดิม (เปลี่ยนข้อมูล)', NULL, 'div_mgr_review', NULL, NULL, '2025-07-29 06:56:52', '2025-07-29 06:56:52', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', '', NULL, 2, 3, NULL, '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(75, 2, 'โปรแกรมใหม่', NULL, 'div_mgr_review', NULL, NULL, '2025-07-29 06:58:04', '2025-07-29 06:58:04', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', '', NULL, 2, 1, NULL, '55555555', '', '555555', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_number` (`document_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_to_admin_id` (`assigned_to_admin_id`),
  ADD KEY `idx_service_requests_status` (`status`),
  ADD KEY `idx_service_requests_current_step` (`current_step`),
  ADD KEY `idx_service_requests_dev_status` (`developer_status`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `assigned_div_mgr_id` (`assigned_div_mgr_id`),
  ADD KEY `idx_service_requests_service_id` (`service_id`),
  ADD KEY `idx_service_requests_work_category` (`work_category`),
  ADD KEY `idx_service_requests_current_program` (`current_program_name`),
  ADD KEY `idx_service_requests_program_change` (`program_name_change`),
  ADD KEY `idx_service_requests_program_function` (`program_name_function`),
  ADD KEY `idx_service_requests_program_decorate` (`program_name_decorate`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD CONSTRAINT `fk_div_mgr` FOREIGN KEY (`assigned_div_mgr_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_service_requests_assigned_div_mgr_id` FOREIGN KEY (`assigned_div_mgr_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_service_requests_service_id` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_requests_ibfk_2` FOREIGN KEY (`assigned_to_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_requests_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `service_requests_ibfk_4` FOREIGN KEY (`assigned_div_mgr_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
