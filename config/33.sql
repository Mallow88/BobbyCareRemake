-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 30, 2025 at 09:00 AM
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
(4, 2, 'ดฟหดด', 5, '01-DEV', 6, NULL, 'pending', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Friend\'s Corner', 'ui_design', 'หฟดฟหด', '', NULL, '', NULL, NULL, NULL, 'ดฟหด', '2025-07-29 08:22:32', '2025-07-30 03:00:36', 'ประเภทบริการ: โปรแกรมเดิม (ตกแต่ง)\nหัวข้องานคลัง: 01-DEV\nโปรแกรม: Friend\'s Corner\nประเภทการตกแต่ง: ui_design'),
(5, 2, 'fsdfsfsdf', 2, '01-DAI', 6, NULL, 'pending', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, 'Full Case', 'fsdfsdf', 'always', 'fsdf', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, '', NULL, NULL, NULL, '', '2025-07-29 08:50:31', '2025-07-30 03:00:20', 'ประเภทบริการ: โปรแกรมเดิม (แก้ปัญหา)\nหัวข้องานคลัง: 01-DAI\nโปรแกรม: Full Case\nปัญหา: fsdfsdf'),
(6, 2, 'ทดสอบ30', 3, '01-DAI', 2, NULL, 'div_mgr_review', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full Case', 'ตัวเลข', 'ตัวเลขอัปเกต', 'มันไม่เท่', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, '', NULL, NULL, NULL, '', '2025-07-30 02:09:26', '2025-07-30 03:43:06', 'ประเภทบริการ: โปรแกรมเดิม (เปลี่ยนข้อมูล)\nหัวข้องานคลัง: 01-DAI\nโปรแกรม: Full Case\nข้อมูลที่ต้องเปลี่ยน: ตัวเลข'),
(7, 2, 'ทดสอบ30ครั้ง2', 3, '01-ADM', 2, NULL, 'pending', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full Case', 'เปลี่ยนข้อความ', 'ตัวเลขเเทน', 'ไม่เท่', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, '', NULL, NULL, NULL, '', '2025-07-30 02:23:11', '2025-07-30 03:00:09', 'ประเภทบริการ: โปรแกรมเดิม (เปลี่ยนข้อมูล)\nหัวข้องานคลัง: 01-ADM\nโปรแกรม: Full Case\nข้อมูลที่ต้องเปลี่ยน: เปลี่ยนข้อความ'),
(8, 2, 'ทดสอบ30ครั้ง3', 3, '01-RFL', 2, NULL, 'pending', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Transporter CDC', 'รายการสินค้า', 'ข้อความหัวข้อครับ', 'มันไม่สวยไม่เข้าท่า', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, '', NULL, NULL, NULL, '', '2025-07-30 04:43:06', '2025-07-30 04:43:06', 'ประเภทบริการ: โปรแกรมเดิม (เปลี่ยนข้อมูล)\nหัวข้องานคลัง: 01-RFL\nโปรแกรม: Transporter CDC\nข้อมูลที่ต้องเปลี่ยน: รายการสินค้า'),
(9, 2, 'ทดสอบ30ครั้ง3', 3, '01-RFL', 2, NULL, 'pending', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Transporter CDC', 'รายการสินค้า', 'ข้อความหัวข้อครับ', 'มันไม่สวยไม่เข้าท่า', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, '', NULL, NULL, NULL, '', '2025-07-30 04:45:41', '2025-07-30 04:45:41', 'ประเภทบริการ: โปรแกรมเดิม (เปลี่ยนข้อมูล)\nหัวข้องานคลัง: 01-RFL\nโปรแกรม: Transporter CDC\nข้อมูลที่ต้องเปลี่ยน: รายการสินค้า'),
(10, 2, 'นนน', 3, '01-DAI', 2, NULL, 'pending', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'DATA ENTRY', 'นนน', 'นนน', 'นนน', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, '', NULL, NULL, NULL, '', '2025-07-30 05:01:42', '2025-07-30 05:01:42', 'ประเภทบริการ: โปรแกรมเดิม (เปลี่ยนข้อมูล)\nหัวข้องานคลัง: 01-DAI\nโปรแกรม: DATA ENTRY\nข้อมูลที่ต้องเปลี่ยน: นนน'),
(11, 2, 'โปรเเกรมทดสอบใหม่', 1, '01-ROD', 2, NULL, 'pending', 'user_submitted', 'medium', NULL, NULL, 'not_assigned', 'ลดขั้นตอนการทำงานของคลัง', 'เจ้าหน้าที่คลัง', 'การบันทึกข้อมูล', 'ข้อมูลสินค้า', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ต้องเเยกได้ชัดเจน', NULL, 'F/C', NULL, NULL, NULL, 'เท่', '2025-07-30 06:06:30', '2025-07-30 06:16:16', 'ประเภทบริการ: โปรแกรมใหม่\nหัวข้องานคลัง: 01-ROD\nวัตถุประสงค์: ลดขั้นตอนการทำงานของคลัง\nกลุ่มผู้ใช้: เจ้าหน้าที่คลัง\nฟังก์ชันหลัก: การบันทึกข้อมูล');

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
(2, 'Ucdeb083644ac0ea108e85ba498a1c784', 'สัภยา5', 'เกสร', 'ผู้จัดการเเผนก', 'sappaya@gmail.com', '2025-07-21 03:35:15', '088777777', 'divmgr', NULL, 'แผนกพัฒนาระบบงาน (RDC.นครสวรรค์)', 1, '7884657'),
(3, 'Udev001', 'พี่เอ็ม', 'developer', 'นักพัฒนา', 'somchai.dev@example.com', '2025-07-21 06:50:40', '0811111111', 'developer', NULL, NULL, 1, NULL),
(4, 'Udev002', 'มาร์ช', 'developer', 'นักพัฒนา', 'somying.dev@example.com', '2025-07-21 06:50:40', '0822222222', 'developer', NULL, NULL, 1, NULL),
(6, 'U04bc163e497a4e5e929b426356d1d599', 'Test010', 'Test010', NULL, 'test1@gmail.com', '2025-07-25 06:25:26', '08887898784', 'divmgr', NULL, NULL, 1, NULL),
(7, 'U98f1b10aebfb7778015146b266640344', 'พี่เอ็ม', 'DEV', 'เจ้าหน้าที่', 'test2@gmail.com', '2025-07-25 07:42:20', '09875678743', 'user', NULL, 'แผนกพัฒนาระบบงาน', 1, '123456'),
(8, 'Udivmgr001', 'ผู้จัดการฝ่าย', 'ทดสอบ', 'ผู้จัดการฝ่าย', 'divmgr@test.com', '2025-07-29 04:20:12', '0800000001', 'divmgr', NULL, 'Management', 1, NULL),
(9, 'Ugmapprover001', 'ผู้จัดการทั่วไป', 'ทดสอบ', 'ผู้จัดการทั่วไป', 'gm@test.com', '2025-07-29 04:20:12', '0800000002', 'gmapprover', NULL, 'Management', 1, NULL),
(10, 'Udivmgr002', 'ผู้จัดการฝ่าย', 'ทดสอบ 2', 'ผู้จัดการฝ่าย', 'divmgr2@test.com', '2025-07-29 08:00:31', '0800000002', 'divmgr', NULL, 'Management', 1, NULL),
(11, 'Udivmgr003', 'ผู้จัดการฝ่าย', 'ทดสอบ 3', 'ผู้จัดการฝ่าย', 'divmgr3@test.com', '2025-07-29 08:00:31', '0800000003', 'divmgr', NULL, 'Management', 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `div_mgr_approvals`
--
ALTER TABLE `div_mgr_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_div_mgr_approvals_request` (`service_request_id`),
  ADD KEY `idx_div_mgr_approvals_div_mgr` (`div_mgr_user_id`);

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `line_id` (`line_id`),
  ADD KEY `fk_users_created_by` (`created_by_admin_id`),
  ADD KEY `idx_users_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `div_mgr_approvals`
--
ALTER TABLE `div_mgr_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `div_mgr_approvals`
--
ALTER TABLE `div_mgr_approvals`
  ADD CONSTRAINT `div_mgr_approvals_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `div_mgr_approvals_ibfk_2` FOREIGN KEY (`div_mgr_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_requests_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_requests_ibfk_3` FOREIGN KEY (`assigned_div_mgr_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
