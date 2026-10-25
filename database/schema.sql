-- Clean schema reconstructed from the supplied application dump. No INSERT rows or credentials.
-- The source used utf8mb4_0900_ai_ci (MySQL 8-only) and mixed engines.
-- This portable schema uses utf8mb4_unicode_ci. See migration.sql before upgrading an existing DB.
SET NAMES utf8mb4;
SET time_zone = '+08:00';

CREATE TABLE `admin_profiles` (
  `admin_id` int NOT NULL,
  `user_id` int NOT NULL,
  `department` varchar(100) DEFAULT NULL COMMENT 'e.g., csdl staff, Management, Guidance Head',
  `admin_level` enum('Super Admin','System Admin') DEFAULT 'System Admin',
  `phone_number` varchar(20) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT 'default_admin.png',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `announcements` (
  `announcement_id` int NOT NULL,
  `counselor_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `object_id` varchar(50) DEFAULT NULL,
  `changed_data` json DEFAULT NULL,
  `sql_text` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cases` (
  `case_id` int NOT NULL,
  `student_user_id` int NOT NULL,
  `counselor_id` int DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` enum('Open','In Progress','Pending','Resolved','Closed') DEFAULT 'Open',
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `counseling_schedules` (
  `schedule_id` int NOT NULL,
  `counselor_id` int NOT NULL,
  `student_user_id` int NOT NULL,
  `student_id` varchar(25) NOT NULL,
  `schedule_date` date NOT NULL,
  `schedule_time` time NOT NULL,
  `status` enum('Pending','Confirmed','Completed','Cancelled') DEFAULT 'Pending',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `counselor_profiles` (
  `profile_id` int NOT NULL,
  `user_id` int NOT NULL,
  `specialization` varchar(100) DEFAULT NULL COMMENT 'e.g. Career Guidance, Mental Health',
  `office_location` varchar(100) DEFAULT NULL COMMENT 'e.g. Guidance Office Room 101',
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('Male','Female','Other','Prefer not to say') DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT 'default_counselor.png',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `messages` (
  `message_id` int NOT NULL,
  `case_id` int DEFAULT NULL,
  `sender_id` int NOT NULL,
  `recipient_id` int NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `agenda` enum('Self-Referral','Referring a Friend','General Inquiry','Follow-up','Academic Concerns') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'General Inquiry',
  `status` enum('Unread','Read') DEFAULT 'Unread',
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `student_profiles` (
  `profile_id` int NOT NULL,
  `user_id` int NOT NULL,
  `student_id` varchar(25) NOT NULL,
  `program` varchar(100) DEFAULT NULL COMMENT 'e.g. BS Information Technology',
  `year_level` enum('1st Year','2nd Year','3rd Year','4th Year','Irregular') DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('Male','Female','Other','Prefer not to say') DEFAULT NULL,
  `guardian_name` varchar(150) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT 'default_student.png',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `division` enum('ADMIN','COUNSELOR','STUDENT','REPORT') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('ADMIN','COUNSELOR','STUDENT','ADMIN_REPORTS') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `admin_profiles`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `counselor_id` (`counselor_id`);
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);
ALTER TABLE `cases`
  ADD PRIMARY KEY (`case_id`),
  ADD KEY `fk_student` (`student_user_id`),
  ADD KEY `fk_counselor` (`counselor_id`);
ALTER TABLE `counseling_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `counselor_id` (`counselor_id`),
  ADD KEY `student_user_id` (`student_user_id`),
  ADD KEY `student_id` (`student_id`);
ALTER TABLE `counselor_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `fk_message_case` (`case_id`),
  ADD KEY `fk_sender` (`sender_id`),
  ADD KEY `fk_recipient` (`recipient_id`);
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);
ALTER TABLE `admin_profiles` MODIFY `admin_id` int NOT NULL AUTO_INCREMENT;
ALTER TABLE `announcements` MODIFY `announcement_id` int NOT NULL AUTO_INCREMENT;
ALTER TABLE `audit_logs` MODIFY `id` int NOT NULL AUTO_INCREMENT;
ALTER TABLE `cases` MODIFY `case_id` int NOT NULL AUTO_INCREMENT;
ALTER TABLE `counseling_schedules` MODIFY `schedule_id` int NOT NULL AUTO_INCREMENT;
ALTER TABLE `counselor_profiles` MODIFY `profile_id` int NOT NULL AUTO_INCREMENT;
ALTER TABLE `messages` MODIFY `message_id` int NOT NULL AUTO_INCREMENT;
ALTER TABLE `password_resets` MODIFY `id` int NOT NULL AUTO_INCREMENT;
ALTER TABLE `student_profiles` MODIFY `profile_id` int NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` MODIFY `user_id` int NOT NULL AUTO_INCREMENT;
ALTER TABLE `admin_profiles`
  ADD CONSTRAINT `fk_admin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_counselor_announcement` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
ALTER TABLE `cases`
  ADD CONSTRAINT `fk_counselor` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_student` FOREIGN KEY (`student_user_id`) REFERENCES `users` (`user_id`);
ALTER TABLE `counselor_profiles`
  ADD CONSTRAINT `fk_counselor_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_message_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`);
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `fk_student_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

-- Additional composite indexes for actual list filters and timestamp ordering.
ALTER TABLE `users` ADD KEY `idx_users_role_status` (`role`,`status`);
ALTER TABLE `cases` ADD KEY `idx_cases_student_status_updated` (`student_user_id`,`status`,`updated_at`);
ALTER TABLE `messages` ADD KEY `idx_messages_sender_date` (`sender_id`,`created_at`), ADD KEY `idx_messages_recipient_date` (`recipient_id`,`created_at`);
ALTER TABLE `counseling_schedules` ADD KEY `idx_schedules_student_date` (`student_user_id`,`schedule_date`), ADD KEY `idx_schedules_counselor_date` (`counselor_id`,`schedule_date`);
ALTER TABLE `audit_logs` ADD KEY `idx_audit_created` (`created_at`);
