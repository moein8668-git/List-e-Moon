-- Migration: Add external_rating to items
ALTER TABLE `items` ADD `external_rating` DECIMAL(3,1) DEFAULT NULL AFTER `remote_original_image_url`;
