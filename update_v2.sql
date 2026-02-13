-- Migration: Add is_active to users
ALTER TABLE `users` ADD `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_admin`;
