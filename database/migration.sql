-- Review and back up first. This migration is additive except that it deliberately invalidates
-- previously issued plaintext reset tokens (see UPDATE below); it does not delete user/case rows.
-- Take a full phpMyAdmin export. Check SELECT VERSION(), @@version_comment; and SHOW ENGINES;
-- Test on a copy first; run only once and confirm index names do not already exist.
-- MyISAM -> InnoDB preserves table rows but may lock/rebuild tables; plan downtime/storage accordingly.
ALTER TABLE `audit_logs` ENGINE=InnoDB;
ALTER TABLE `counseling_schedules` ENGINE=InnoDB;
ALTER TABLE `password_resets` ENGINE=InnoDB;

-- Add composite indexes for current role/status, counselor/student/date, and audit-time queries.
ALTER TABLE `users` ADD KEY `idx_users_role_status` (`role`,`status`);
ALTER TABLE `cases` ADD KEY `idx_cases_student_status_updated` (`student_user_id`,`status`,`updated_at`);
ALTER TABLE `messages` ADD KEY `idx_messages_sender_date` (`sender_id`,`created_at`), ADD KEY `idx_messages_recipient_date` (`recipient_id`,`created_at`);
ALTER TABLE `counseling_schedules` ADD KEY `idx_schedules_student_date` (`student_user_id`,`schedule_date`), ADD KEY `idx_schedules_counselor_date` (`counselor_id`,`schedule_date`);
ALTER TABLE `audit_logs` ADD KEY `idx_audit_created` (`created_at`);

-- Reset implementation stores SHA-256 hashes, not raw URL tokens. Old un-hashed tokens are
-- intentionally revoked; users can request a fresh link after SMTP delivery has been tested.
UPDATE `password_resets` SET `used`=1 WHERE `used`=0;
ALTER TABLE `password_resets` MODIFY `token` char(64) NOT NULL;

-- No collation conversion is issued here. Review uniqueness/case-collision consequences first.
-- Fresh schema uses utf8mb4_unicode_ci (portable on MySQL/MariaDB); legacy source used 0900_ai_ci.
