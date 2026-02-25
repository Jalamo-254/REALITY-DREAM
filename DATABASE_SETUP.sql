-- Reality Dream Institute database setup for XAMPP / MariaDB
-- Date: 2026-02-25

CREATE DATABASE IF NOT EXISTS `Reality_Dream`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'ouma'@'localhost' IDENTIFIED BY 'jalamo@2025';
GRANT ALL PRIVILEGES ON `Reality_Dream`.* TO 'ouma'@'localhost';
FLUSH PRIVILEGES;

USE `Reality_Dream`;

CREATE TABLE IF NOT EXISTS `contacts` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `course` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `attachment` VARCHAR(255) DEFAULT NULL,
  `submitted_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `status` VARCHAR(20) DEFAULT 'New',
  PRIMARY KEY (`id`),
  KEY `idx_contacts_status` (`status`),
  KEY `idx_contacts_submitted_date` (`submitted_date`),
  KEY `idx_contacts_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- If no admin user exists yet, open:
--   http://localhost/Reality-Dream-Institute-main-main/login.php
-- The app will auto-create:
--   Username: admin
--   Password: Admin@2026
