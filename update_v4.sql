-- Migration: Add Avatars, XP, and Watchlist
ALTER TABLE `users` ADD `avatar_path` VARCHAR(255) DEFAULT NULL AFTER `username`;
ALTER TABLE `users` ADD `xp` INT NOT NULL DEFAULT 0 AFTER `is_active`;
ALTER TABLE `users` ADD `last_seen_level` INT NOT NULL DEFAULT 1 AFTER `xp`;

CREATE TABLE `watchlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_item` (`user_id`, `item_id`),
  CONSTRAINT `fk_watchlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_watchlist_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
