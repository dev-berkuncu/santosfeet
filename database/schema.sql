-- GTA 5 Fictional Character Photo Gallery - Database Schema
-- Import this file via phpMyAdmin

CREATE DATABASE IF NOT EXISTS `wikifeet_gta` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `wikifeet_gta`;

-- Admins table
CREATE TABLE `admins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Characters table
CREATE TABLE `characters` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL UNIQUE,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_name` (`name`)
) ENGINE=InnoDB;

-- Photos table
CREATE TABLE `photos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `character_id` INT UNSIGNED NOT NULL,
    `image_url` TEXT NOT NULL,
    `source_url` TEXT NULL,
    `caption` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT `fk_photos_character` FOREIGN KEY (`character_id`) REFERENCES `characters`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uniq_character_image` (`character_id`, `image_url`(255))
) ENGINE=InnoDB;

-- Requests table (contact / takedown)
CREATE TABLE `requests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `page_url` VARCHAR(500) NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('open','closed') NOT NULL DEFAULT 'open',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
