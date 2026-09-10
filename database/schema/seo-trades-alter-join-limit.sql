-- Review only. The seo_* tables are managed with manual SQL, not Laravel migrations.
-- Run after confirming the existing index definition:
-- SHOW INDEX FROM seo_trade_join_requests
--   WHERE Key_name = 'idx_trade_join_pair';

ALTER TABLE `seo_trade_join_requests`
  DROP INDEX `idx_trade_join_pair`,
  ADD KEY `idx_trade_join_pair` (`requester_user_id`, `owner_user_id`, `listing_id`);
