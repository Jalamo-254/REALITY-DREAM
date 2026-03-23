-- Reality Dream Institute database setup for XAMPP / MariaDB
-- Updated: 2026-03-02
-- Run this file in phpMyAdmin SQL tab.

CREATE DATABASE IF NOT EXISTS `Reality_Dream`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `Reality_Dream`;

-- Contacts submitted from contact.php
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

-- Enrollments submitted from enroll.php
CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `course` VARCHAR(200) NOT NULL,
  `study_mode` VARCHAR(50) DEFAULT NULL,
  `intake_month` VARCHAR(30) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `attachment` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(20) DEFAULT 'New',
  `submitted_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_enrollments_status` (`status`),
  KEY `idx_enrollments_submitted_at` (`submitted_at`),
  KEY `idx_enrollments_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin login table used by login.php/admin.php
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Safe migration helpers for older databases
ALTER TABLE `contacts` ADD COLUMN IF NOT EXISTS `attachment` VARCHAR(255) DEFAULT NULL AFTER `message`;
ALTER TABLE `contacts` ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) DEFAULT 'New' AFTER `submitted_date`;
ALTER TABLE `enrollments` ADD COLUMN IF NOT EXISTS `study_mode` VARCHAR(50) DEFAULT NULL AFTER `course`;
ALTER TABLE `enrollments` ADD COLUMN IF NOT EXISTS `intake_month` VARCHAR(30) DEFAULT NULL AFTER `study_mode`;
ALTER TABLE `enrollments` ADD COLUMN IF NOT EXISTS `notes` TEXT DEFAULT NULL AFTER `intake_month`;
ALTER TABLE `enrollments` ADD COLUMN IF NOT EXISTS `attachment` VARCHAR(255) DEFAULT NULL AFTER `notes`;
ALTER TABLE `enrollments` ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) DEFAULT 'New' AFTER `attachment`;

-- Index safety for older databases
ALTER TABLE `contacts` ADD INDEX IF NOT EXISTS `idx_contacts_status` (`status`);
ALTER TABLE `contacts` ADD INDEX IF NOT EXISTS `idx_contacts_submitted_date` (`submitted_date`);
ALTER TABLE `contacts` ADD INDEX IF NOT EXISTS `idx_contacts_email` (`email`);
ALTER TABLE `enrollments` ADD INDEX IF NOT EXISTS `idx_enrollments_status` (`status`);
ALTER TABLE `enrollments` ADD INDEX IF NOT EXISTS `idx_enrollments_submitted_at` (`submitted_at`);
ALTER TABLE `enrollments` ADD INDEX IF NOT EXISTS `idx_enrollments_email` (`email`);

-- Seed default admin if missing (password: Admin@2026)
INSERT INTO `admin_users` (`username`, `password_hash`)
SELECT 'admin', '$2y$10$lil4RSMxmAjZ.PNP0jO8wOxWTj3Kyz1/KiOzz4CUaP/0xZDuJ5mmi'
WHERE NOT EXISTS (
  SELECT 1 FROM `admin_users` WHERE `username` = 'admin'
);
