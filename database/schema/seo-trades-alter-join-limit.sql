-- Review only. The seo_* tables are managed with manual SQL, not Laravel migrations.
-- Run after confirming the index is not already present:
-- SHOW INDEX FROM seo_trade_join_requests
--   WHERE Key_name = 'idx_trade_join_pair';

ALTER TABLE `seo_trade_join_requests`
  ADD KEY `idx_trade_join_pair` (`requester_user_id`, `owner_user_id`);
