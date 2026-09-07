-- Review only. Run this if seo_trade_users already exists without password.
-- Do not run artisan migrate.

ALTER TABLE `seo_trade_users`
  ADD COLUMN `password` VARCHAR(255) DEFAULT NULL AFTER `account_status`;
