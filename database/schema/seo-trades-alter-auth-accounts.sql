-- Review only. Run this if seo_trade_users already exists without email / auth accounts.
-- Do not run artisan migrate.

ALTER TABLE `seo_trade_users`
  ADD COLUMN `email` VARCHAR(255) DEFAULT NULL AFTER `profile_url`,
  ADD COLUMN `email_verified_at` DATETIME DEFAULT NULL AFTER `email`,
  MODIFY COLUMN `roblox_sub` VARCHAR(64) DEFAULT NULL,
  ADD UNIQUE KEY `uk_trade_users_email` (`email`);

CREATE TABLE IF NOT EXISTS `seo_trade_auth_accounts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `provider` ENUM('email', 'roblox', 'google', 'local') NOT NULL,
  `provider_uid` VARCHAR(255) NOT NULL,
  `provider_username` VARCHAR(100) DEFAULT NULL,
  `avatar_url` VARCHAR(500) DEFAULT NULL,
  `bound_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_auth_provider_uid` (`provider`, `provider_uid`),
  UNIQUE KEY `uk_auth_user_provider` (`user_id`, `provider`),
  KEY `idx_auth_user_id` (`user_id`),
  CONSTRAINT `fk_auth_account_user`
    FOREIGN KEY (`user_id`) REFERENCES `seo_trade_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `seo_trade_email_codes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `code` CHAR(6) NOT NULL,
  `purpose` ENUM('login','bind') NOT NULL DEFAULT 'login',
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_codes_lookup` (`email`, `purpose`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `seo_trade_auth_accounts` (
  `user_id`,
  `provider`,
  `provider_uid`,
  `provider_username`,
  `avatar_url`,
  `bound_at`,
  `last_login_at`
)
SELECT
  `id`,
  CASE WHEN `roblox_sub` LIKE 'local:%' THEN 'local' ELSE 'roblox' END,
  `roblox_sub`,
  `username`,
  `avatar_url`,
  `created_at`,
  `last_login_at`
FROM `seo_trade_users`
WHERE `roblox_sub` IS NOT NULL
  AND `roblox_sub` <> ''
  AND NOT EXISTS (
    SELECT 1
    FROM `seo_trade_auth_accounts` AS `accounts`
    WHERE `accounts`.`user_id` = `seo_trade_users`.`id`
      AND `accounts`.`provider` = CASE WHEN `seo_trade_users`.`roblox_sub` LIKE 'local:%' THEN 'local' ELSE 'roblox' END
  );
