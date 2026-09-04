-- Review only. Do not run until confirmed.
-- Creates trade_users + trade_posts for trades.* listings.
-- Does not touch seo_* tables.

CREATE TABLE IF NOT EXISTS `trade_users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `roblox_sub` VARCHAR(64) NOT NULL,
  `username` VARCHAR(255) NOT NULL DEFAULT '',
  `display_name` VARCHAR(255) NOT NULL DEFAULT '',
  `avatar_url` VARCHAR(255) NOT NULL DEFAULT '',
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trade_users_roblox_sub_unique` (`roblox_sub`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trade_posts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `trade_user_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'open',
  `offer_json` JSON NOT NULL,
  `receive_json` JSON NOT NULL,
  `offer_value` INT UNSIGNED NOT NULL DEFAULT 0,
  `receive_value` INT UNSIGNED NOT NULL DEFAULT 0,
  `wfl` VARCHAR(8) NOT NULL DEFAULT 'fair',
  `note` VARCHAR(280) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trade_posts_status_id_index` (`status`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
