-- Review only. Run this if seo_trade_users already exists without posting_approved.
-- Do not run artisan migrate.

ALTER TABLE `seo_trade_users`
  ADD COLUMN `posting_approved` TINYINT(1) NOT NULL DEFAULT 1
    AFTER `account_status`,
  ADD COLUMN `posting_approved_at` DATETIME NULL DEFAULT NULL
    AFTER `posting_approved`;

UPDATE `seo_trade_users`
  SET `posting_approved` = 0,
      `posting_approved_at` = NULL
  WHERE `roblox_sub` LIKE 'local:%';
