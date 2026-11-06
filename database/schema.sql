-- ==============================================================
-- MITS Alumni Portal - Database Schema
-- Database: `mits_alumni_portal`
-- Engine: InnoDB, Charset: utf8mb4
-- ==============================================================

CREATE DATABASE IF NOT EXISTS `mits_alumni_portal` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `mits_alumni_portal`;

-- --------------------------------------------------------------
-- 1. Table structure for table `users`
-- --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `google_id` VARCHAR(100) UNIQUE NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `avatar_url` VARCHAR(255) NULL,
  `role` ENUM('alumni', 'student', 'admin') NOT NULL DEFAULT 'alumni',
  `status` ENUM('pending', 'verified', 'rejected', 'suspended') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 2. Table structure for table `alumni_profiles`
-- --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `alumni_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `roll_number` VARCHAR(50) NULL,
  `department` VARCHAR(100) NOT NULL,
  `graduation_year` YEAR NOT NULL,
  `current_company` VARCHAR(150) NULL,
  `designation` VARCHAR(100) NULL,
  `industry` VARCHAR(100) NULL,
  `location` VARCHAR(100) NULL,
  `linkedin_url` VARCHAR(255) NULL,
  `bio` TEXT NULL,
  `skills` TEXT NULL,
  `is_mentor` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_dept_batch` (`department`, `graduation_year`),
  INDEX `idx_company` (`current_company`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 3. Table structure for table `posts`
-- --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `content` TEXT NOT NULL,
  `image_url` VARCHAR(255) NULL,
  `post_type` ENUM('general', 'achievement', 'hiring', 'event') NOT NULL DEFAULT 'general',
  `likes_count` INT NOT NULL DEFAULT 0,
  `comments_count` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_post_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_post_created` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 4. Table structure for table `likes`
-- --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `likes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_post_user_like` (`post_id`, `user_id`),
  CONSTRAINT `fk_like_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_like_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 5. Table structure for table `comments`
-- --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_comment_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_comment_post` (`post_id`, `created_at` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 6. Table structure for table `jobs`
-- --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `posted_by` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `company` VARCHAR(150) NOT NULL,
  `location` VARCHAR(100) NOT NULL,
  `job_type` ENUM('full-time', 'part-time', 'internship', 'remote') NOT NULL DEFAULT 'full-time',
  `experience_level` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `requirements` TEXT NULL,
  `apply_link` VARCHAR(255) NULL,
  `contact_email` VARCHAR(150) NULL,
  `status` ENUM('active', 'closed', 'under_review') NOT NULL DEFAULT 'active',
  `deadline` DATE NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_job_poster` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_job_status_created` (`status`, `created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 7. Table structure for table `connections`
-- --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `connections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `status` ENUM('pending', 'accepted', 'rejected', 'blocked') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_connection_pair` (`sender_id`, `receiver_id`),
  CONSTRAINT `fk_conn_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conn_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_conn_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 8. Table structure for table `notifications`
-- --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `actor_id` INT NOT NULL,
  `type` ENUM('like', 'comment', 'connection_request', 'connection_accepted', 'job_alert', 'admin_verification') NOT NULL,
  `entity_id` INT NULL,
  `message` VARCHAR(255) NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_notif_user_read` (`user_id`, `is_read`, `created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 9. Table structure for table `events`
-- --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `event_date` DATE NOT NULL,
  `venue` VARCHAR(150) NULL,
  `cover_image` VARCHAR(255) NULL,
  `created_by` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_events_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_events_date` (`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 10. Table structure for table `event_photos`
-- --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `event_photos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `photo_url` VARCHAR(255) NOT NULL,
  `caption` VARCHAR(255) NULL,
  `uploaded_by` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_photos_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_photos_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_photos_event` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
