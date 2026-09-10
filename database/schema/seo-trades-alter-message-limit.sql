-- Review only. The seo_* tables are managed with manual SQL, not Laravel migrations.
-- Run after confirming the existing index definition:
-- SHOW INDEX FROM seo_trade_notifications
--   WHERE Key_name = 'idx_trade_notifications_message_pair';

ALTER TABLE `seo_trade_notifications`
  DROP INDEX `idx_trade_notifications_message_pair`,
  ADD KEY `idx_trade_notifications_message_pair` (`actor_user_id`, `user_id`, `listing_id`, `type`);
