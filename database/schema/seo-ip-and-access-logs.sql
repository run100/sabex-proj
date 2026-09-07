-- Review only. Do not run until confirmed.
-- Adds publish/register/login IP columns and a write-only access log.
-- Does not backfill existing rows.

ALTER TABLE `seo_trade_listings`
  ADD COLUMN `posted_ip` VARCHAR(45) DEFAULT NULL AFTER `note`;

ALTER TABLE `seo_trade_users`
  ADD COLUMN `registered_ip` VARCHAR(45) DEFAULT NULL AFTER `remember_token`,
  ADD COLUMN `last_login_ip` VARCHAR(45) DEFAULT NULL AFTER `last_login_at`;

ALTER TABLE `seo_users`
  ADD COLUMN `last_login_ip` VARCHAR(45) DEFAULT NULL AFTER `last_login_at`;

CREATE TABLE IF NOT EXISTS `seo_access_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_type` VARCHAR(16) NOT NULL,
  `actor_id` BIGINT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(32) NOT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `subject_type` VARCHAR(32) DEFAULT NULL,
  `subject_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_access_logs_created` (`created_at`),
  KEY `idx_access_logs_actor` (`actor_type`, `actor_id`, `created_at`),
  KEY `idx_access_logs_action` (`action`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
