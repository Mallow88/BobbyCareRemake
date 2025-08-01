-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 01, 2025 at 05:04 AM
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignor_approvals`
--
ALTER TABLE `assignor_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `div_mgr_approvals`
--
ALTER TABLE `div_mgr_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_numbers`
--
ALTER TABLE `document_numbers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_status_logs`
--
ALTER TABLE `document_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gm_approvals`
--
ALTER TABLE `gm_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_attachments`
--
ALTER TABLE `request_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `senior_gm_approvals`
--
ALTER TABLE `senior_gm_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subtask_logs`
--
ALTER TABLE `subtask_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subtask_templates`
--
ALTER TABLE `subtask_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_status_logs`
--
ALTER TABLE `task_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_subtasks`
--
ALTER TABLE `task_subtasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
