-- Review only. Do not run automatically.
-- Current lab/prod table already has roblox_user_id, profile_visibility,
-- moderation_status, profile_index_eligible, deleted_at, and uk_trade_users_roblox_user_id.
-- Only profile_id is missing. seo_trade_users is empty, so NOT NULL without DEFAULT is safe.
--
-- Before running: SELECT COUNT(*) FROM seo_trade_users;  -- must be 0
-- After running:  SHOW COLUMNS FROM seo_trade_users LIKE 'profile_id';
--                 SHOW INDEX FROM seo_trade_users WHERE Key_name = 'uk_trade_users_profile_id';

ALTER TABLE `seo_trade_users`
  ADD COLUMN `profile_id` CHAR(26) NOT NULL AFTER `public_id`,
  ADD UNIQUE KEY `uk_trade_users_profile_id` (`profile_id`);
