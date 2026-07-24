-- =====================================================
-- ChatApp - Complete Unified Database Schema
-- MySQL 8+ | Production Ready
-- =====================================================
-- Single import file containing ALL tables, indexes,
-- foreign keys, and default data.
-- =====================================================
-- Usage: Import this file into MySQL to set up the
-- entire database from scratch.
-- =====================================================

SET SQL_MODE = "STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION";
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- Database
-- =====================================================
CREATE DATABASE IF NOT EXISTS `chatapp_db`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `chatapp_db`;

-- =====================================================
-- 1. Users
-- =====================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `friend_code` VARCHAR(20) NOT NULL,
    `avatar` VARCHAR(500) DEFAULT NULL,
    `cover_photo` VARCHAR(500) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `about` TEXT DEFAULT NULL,
    `is_online` TINYINT(1) DEFAULT 0,
    `user_status` ENUM('online', 'busy', 'away', 'invisible') DEFAULT 'online',
    `theme` ENUM('dark', 'light', 'midnight', 'ocean') DEFAULT 'dark',
    `settings` JSON DEFAULT NULL,
    `timezone` VARCHAR(50) DEFAULT 'UTC',
    `language` VARCHAR(10) DEFAULT 'en',
    `is_active` TINYINT(1) DEFAULT 1,
    `deactivated_at` DATETIME DEFAULT NULL,
    `last_seen` DATETIME DEFAULT NULL,
    `last_password_change` DATETIME DEFAULT NULL,
    `reset_token` VARCHAR(64) DEFAULT NULL,
    `reset_token_expires` DATETIME DEFAULT NULL,
    `remember_token` VARCHAR(64) DEFAULT NULL,
    `email_verified` TINYINT(1) DEFAULT 0,
    `verification_token` VARCHAR(64) DEFAULT NULL,
    `status` ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_username` (`username`),
    UNIQUE KEY `uk_email` (`email`),
    UNIQUE KEY `uk_friend_code` (`friend_code`),
    INDEX `idx_status` (`status`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_reset_token` (`reset_token`),
    INDEX `idx_remember_token` (`remember_token`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_last_seen` (`last_seen`),
    FULLTEXT INDEX `idx_user_search` (`username`, `bio`, `about`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. Rate Limiting
-- =====================================================
DROP TABLE IF EXISTS `rate_limits`;
CREATE TABLE `rate_limits` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `action_type` VARCHAR(50) NOT NULL,
    `attempts` INT DEFAULT 1,
    `first_attempt_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_attempt_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_ip_action` (`ip_address`, `action_type`),
    INDEX `idx_first_attempt` (`first_attempt_at`),
    INDEX `idx_last_attempt` (`last_attempt_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. User Sessions
-- =====================================================
DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE `user_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `session_token` VARCHAR(128) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_session_token` (`session_token`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_expires` (`expires_at`),
    CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. Activity Log
-- =====================================================
DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT,
    `details` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_ip_address` (`ip_address`),
    CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. Friendships
-- =====================================================
DROP TABLE IF EXISTS `friendships`;
CREATE TABLE `friendships` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `friend_id` INT UNSIGNED NOT NULL,
    `status` ENUM('pending', 'accepted', 'blocked') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_friendship` (`user_id`, `friend_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_friend_id` (`friend_id`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_friendship_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_friendship_friend` FOREIGN KEY (`friend_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. Media (must exist before messages/group_messages)
-- =====================================================
DROP TABLE IF EXISTS `media`;
CREATE TABLE `media` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `thumbnail_path` VARCHAR(500) DEFAULT NULL,
    `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
    `file_type` VARCHAR(100) NOT NULL,
    `file_extension` VARCHAR(20) NOT NULL,
    `category` ENUM('images', 'videos', 'documents', 'archives') NOT NULL,
    `receiver_id` INT UNSIGNED DEFAULT NULL,
    `group_id` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_receiver_id` (`receiver_id`),
    INDEX `idx_group_id` (`group_id`),
    INDEX `idx_category` (`category`),
    INDEX `idx_created_at` (`created_at`),
    CONSTRAINT `fk_media_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_media_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_media_group` FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. Groups
-- =====================================================
DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `status` ENUM('active', 'archived', 'deleted') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_created_by` (`created_by`),
    INDEX `idx_status` (`status`),
    FULLTEXT INDEX `idx_group_search` (`name`, `description`),
    CONSTRAINT `fk_group_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. Group Members
-- =====================================================
DROP TABLE IF EXISTS `group_members`;
CREATE TABLE `group_members` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `group_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `role` ENUM('admin', 'moderator', 'member') DEFAULT 'member',
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_group_member` (`group_id`, `user_id`),
    INDEX `idx_group_id` (`group_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_role` (`role`),
    CONSTRAINT `fk_groupmember_group` FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_groupmember_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. Messages
-- =====================================================
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT UNSIGNED NOT NULL,
    `receiver_id` INT UNSIGNED DEFAULT NULL,
    `group_id` INT UNSIGNED DEFAULT NULL,
    `content` TEXT NOT NULL,
    `message_type` ENUM('text', 'image', 'file', 'video') DEFAULT 'text',
    `reply_to_id` INT UNSIGNED DEFAULT NULL,
    `media_id` INT UNSIGNED DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `is_deleted` TINYINT(1) DEFAULT 0,
    `deleted_for_sender` TINYINT(1) DEFAULT 0,
    `deleted_for_receiver` TINYINT(1) DEFAULT 0,
    `delivered_at` DATETIME DEFAULT NULL,
    `seen_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_sender` (`sender_id`),
    INDEX `idx_receiver` (`receiver_id`),
    INDEX `idx_group` (`group_id`),
    INDEX `idx_reply_to` (`reply_to_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_is_read` (`is_read`),
    INDEX `idx_is_deleted` (`is_deleted`),
    INDEX `idx_sender_receiver` (`sender_id`, `receiver_id`),
    INDEX `idx_content_search` (`content`(100)),
    CONSTRAINT `fk_message_sender` FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_message_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_message_reply` FOREIGN KEY (`reply_to_id`) REFERENCES `messages`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_message_media` FOREIGN KEY (`media_id`) REFERENCES `media`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. Group Messages
-- =====================================================
DROP TABLE IF EXISTS `group_messages`;
CREATE TABLE `group_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `group_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `message` TEXT NOT NULL,
    `media_id` INT UNSIGNED DEFAULT NULL,
    `reply_to` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_group_id` (`group_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_group_created` (`group_id`, `created_at`),
    CONSTRAINT `fk_groupmsg_group` FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_groupmsg_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_groupmsg_media` FOREIGN KEY (`media_id`) REFERENCES `media`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_groupmsg_reply` FOREIGN KEY (`reply_to`) REFERENCES `group_messages`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. Typing Status
-- =====================================================
DROP TABLE IF EXISTS `typing_status`;
CREATE TABLE `typing_status` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `chat_with_user_id` INT UNSIGNED DEFAULT NULL,
    `group_id` INT UNSIGNED DEFAULT NULL,
    `is_typing` TINYINT(1) DEFAULT 0,
    `last_typing_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_typing_user` (`user_id`, `chat_with_user_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_chat_with` (`chat_with_user_id`),
    INDEX `idx_group` (`group_id`),
    INDEX `idx_is_typing` (`is_typing`),
    CONSTRAINT `fk_typing_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_typing_chat_with` FOREIGN KEY (`chat_with_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 12. Block List
-- =====================================================
DROP TABLE IF EXISTS `block_list`;
CREATE TABLE `block_list` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `blocked_user_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_block` (`user_id`, `blocked_user_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_blocked_user` (`blocked_user_id`),
    CONSTRAINT `fk_block_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_block_blocked` FOREIGN KEY (`blocked_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 13. Group Notifications
-- =====================================================
DROP TABLE IF EXISTS `group_notifications`;
CREATE TABLE `group_notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `group_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `notification_type` ENUM('member_joined', 'member_left', 'member_removed', 'role_changed', 'group_updated', 'new_message') NOT NULL,
    `message` TEXT DEFAULT NULL,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_group_id` (`group_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_is_read` (`is_read`),
    INDEX `idx_created_at` (`created_at`),
    CONSTRAINT `fk_notif_group` FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notif_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 14. Group Messages Read Status
-- =====================================================
DROP TABLE IF EXISTS `group_messages_read`;
CREATE TABLE `group_messages_read` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `group_id` INT UNSIGNED NOT NULL,
    `message_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `read_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_group_msg_user` (`group_id`, `message_id`, `user_id`),
    INDEX `idx_group_id` (`group_id`),
    INDEX `idx_message_id` (`message_id`),
    INDEX `idx_user_id` (`user_id`),
    CONSTRAINT `fk_read_group` FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_read_message` FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_read_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 15. Notifications
-- =====================================================
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `sender_id` INT UNSIGNED DEFAULT NULL,
    `type` ENUM('friend_request', 'friend_accept', 'message', 'mention', 'group_invite', 'group_join', 'group_leave', 'group_remove', 'group_role_change', 'system') NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT DEFAULT NULL,
    `data` JSON DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `read_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_notif_user_id` (`user_id`),
    INDEX `idx_notif_sender_id` (`sender_id`),
    INDEX `idx_notif_type` (`type`),
    INDEX `idx_notif_is_read` (`is_read`),
    INDEX `idx_notif_created_at` (`created_at`),
    INDEX `idx_notif_user_read` (`user_id`, `is_read`),
    CONSTRAINT `fk_notif_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notif_sender` FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 16. Notification Preferences
-- =====================================================
DROP TABLE IF EXISTS `notification_preferences`;
CREATE TABLE `notification_preferences` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `friend_requests` TINYINT(1) DEFAULT 1,
    `messages` TINYINT(1) DEFAULT 1,
    `mentions` TINYINT(1) DEFAULT 1,
    `group_invites` TINYINT(1) DEFAULT 1,
    `group_messages` TINYINT(1) DEFAULT 1,
    `system` TINYINT(1) DEFAULT 1,
    `email_notifications` TINYINT(1) DEFAULT 0,
    `push_notifications` TINYINT(1) DEFAULT 1,
    `sound_enabled` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_user_prefs` (`user_id`),
    CONSTRAINT `fk_prefs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 17. Recent Searches
-- =====================================================
DROP TABLE IF EXISTS `recent_searches`;
CREATE TABLE `recent_searches` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `search_type` ENUM('user', 'group', 'message') NOT NULL,
    `search_query` VARCHAR(255) NOT NULL,
    `result_id` INT UNSIGNED DEFAULT NULL,
    `result_name` VARCHAR(255) DEFAULT NULL,
    `result_data` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_search_user_id` (`user_id`),
    INDEX `idx_search_type` (`search_type`),
    INDEX `idx_search_created_at` (`created_at`),
    INDEX `idx_search_user_type` (`user_id`, `search_type`),
    CONSTRAINT `fk_search_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 18. Login Attempts (Security)
-- =====================================================
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `identifier` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_identifier` (`identifier`),
    INDEX `idx_ip_address` (`ip_address`),
    INDEX `idx_attempted_at` (`attempted_at`),
    INDEX `idx_identifier_attempted` (`identifier`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 19. Login Lockouts (Security)
-- =====================================================
DROP TABLE IF EXISTS `login_lockouts`;
CREATE TABLE `login_lockouts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `identifier` VARCHAR(255) NOT NULL,
    `locked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `locked_until` TIMESTAMP NOT NULL,

    UNIQUE KEY `uk_identifier` (`identifier`),
    INDEX `idx_locked_until` (`locked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 20. Password History (Security)
-- =====================================================
DROP TABLE IF EXISTS `password_history`;
CREATE TABLE `password_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`),
    CONSTRAINT `fk_history_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 21. Security Log
-- =====================================================
DROP TABLE IF EXISTS `security_log`;
CREATE TABLE `security_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event` VARCHAR(100) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `details` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_event` (`event`),
    INDEX `idx_ip_address` (`ip_address`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 22. Admin Users
-- =====================================================
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) DEFAULT NULL,
    `role` ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    `avatar` VARCHAR(500) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` DATETIME DEFAULT NULL,
    `login_attempts` INT DEFAULT 0,
    `locked_until` DATETIME DEFAULT NULL,
    `remember_token` VARCHAR(64) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_admin_username` (`username`),
    UNIQUE KEY `uk_admin_email` (`email`),
    INDEX `idx_role` (`role`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 23. Admin Activity Log
-- =====================================================
DROP TABLE IF EXISTS `admin_activity_log`;
CREATE TABLE `admin_activity_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `target_type` VARCHAR(50) DEFAULT NULL,
    `target_id` INT UNSIGNED DEFAULT NULL,
    `details` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_admin_id` (`admin_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_target` (`target_type`, `target_id`),
    INDEX `idx_created_at` (`created_at`),
    CONSTRAINT `fk_adminlog_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 24. User Reports
-- =====================================================
DROP TABLE IF EXISTS `user_reports`;
CREATE TABLE `user_reports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reporter_id` INT UNSIGNED NOT NULL,
    `reported_user_id` INT UNSIGNED NOT NULL,
    `reason` ENUM('spam', 'harassment', 'inappropriate_content', 'fake_account', 'other') NOT NULL,
    `description` TEXT DEFAULT NULL,
    `status` ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
    `reviewed_by` INT UNSIGNED DEFAULT NULL,
    `reviewed_at` DATETIME DEFAULT NULL,
    `resolution_notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_reporter` (`reporter_id`),
    INDEX `idx_reported_user` (`reported_user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`),
    CONSTRAINT `fk_report_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_report_reported` FOREIGN KEY (`reported_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_report_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 25. Message Reports
-- =====================================================
DROP TABLE IF EXISTS `message_reports`;
CREATE TABLE `message_reports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reporter_id` INT UNSIGNED NOT NULL,
    `message_id` INT UNSIGNED NOT NULL,
    `message_type` ENUM('personal', 'group') NOT NULL,
    `reason` ENUM('spam', 'harassment', 'inappropriate_content', 'other') NOT NULL,
    `description` TEXT DEFAULT NULL,
    `status` ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
    `reviewed_by` INT UNSIGNED DEFAULT NULL,
    `reviewed_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_reporter` (`reporter_id`),
    INDEX `idx_message` (`message_id`, `message_type`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_msgreport_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_msgreport_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 26. System Settings
-- =====================================================
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    `description` TEXT DEFAULT NULL,
    `updated_by` INT UNSIGNED DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_setting_key` (`setting_key`),
    CONSTRAINT `fk_setting_admin` FOREIGN KEY (`updated_by`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Re-enable foreign key checks
-- =====================================================
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Default Data
-- =====================================================

-- Default Super Admin (Password: Admin@123)
INSERT INTO `admin_users` (`username`, `email`, `password`, `full_name`, `role`) VALUES
('superadmin', 'admin@chatapp.com', '$2y$12$LJ3m4ys3Lz0YbY1.xTqKq.xKzQKxKxKxKxKxKxKxKxKxKxKxKx', 'System Administrator', 'super_admin');

-- Default Demo User (Password: User@123)
INSERT INTO `users` (`username`, `email`, `password`, `friend_code`, `status`, `email_verified`) VALUES
('demo', 'demo@chatapp.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'USR-DEMO01', 'active', 1);

-- Default System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', 'ChatApp', 'string', 'Website name'),
('site_description', 'Real-time chat application', 'string', 'Website description'),
('maintenance_mode', '0', 'boolean', 'Enable maintenance mode'),
('registration_enabled', '1', 'boolean', 'Allow new registrations'),
('max_upload_size', '20971520', 'integer', 'Maximum upload size in bytes'),
('session_lifetime', '86400', 'integer', 'Session lifetime in seconds'),
('rate_limit_window', '3600', 'integer', 'Rate limit window in seconds');
