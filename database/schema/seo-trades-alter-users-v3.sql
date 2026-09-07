-- Review only. Do not run automatically.
-- Historical batch ADD. Do not re-run if columns already exist.
-- Remaining column: seo-trades-alter-profile-id.sql
-- Deploy:
--   1. Stop the old trades host so OAuth cannot create users.
--   2. Run seo-trades-users-count-check.sql twice; both results must be 0.
--   3. Run this file only on a table that lacks these columns.
--   4. Verify profile_id is NOT NULL, uk_trade_users_profile_id exists,
--      and defaults are profile_visibility='public', moderation_status='clear'.
--   5. Deploy application code, then open /trading.
-- ADD only. No UPDATE, DROP, or backfill.

ALTER TABLE `seo_trade_users`
  ADD COLUMN `profile_id` CHAR(26) NOT NULL AFTER `public_id`,
  ADD COLUMN `roblox_user_id` BIGINT UNSIGNED DEFAULT NULL AFTER `roblox_sub`,
  ADD COLUMN `profile_visibility` ENUM('public', 'unlisted', 'private') NOT NULL DEFAULT 'public' AFTER `account_status`,
  ADD COLUMN `moderation_status` ENUM('clear', 'review', 'restricted') NOT NULL DEFAULT 'clear' AFTER `profile_visibility`,
  ADD COLUMN `profile_index_eligible` TINYINT(1) NOT NULL DEFAULT 0 AFTER `moderation_status`,
  ADD COLUMN `deleted_at` DATETIME DEFAULT NULL AFTER `profile_index_eligible`,
  ADD UNIQUE KEY `uk_trade_users_profile_id` (`profile_id`),
  ADD UNIQUE KEY `uk_trade_users_roblox_user_id` (`roblox_user_id`);
