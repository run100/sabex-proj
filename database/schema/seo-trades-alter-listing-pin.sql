-- Review only. Do not run automatically.
-- Adds pin/hot sort columns on seo_trade_listings.
-- is_* values are Y/N only.
--
-- Before running: SHOW COLUMNS FROM seo_trade_listings LIKE 'is_top';
-- After running:  SHOW COLUMNS FROM seo_trade_listings LIKE 'is_%';
--                 SHOW INDEX FROM seo_trade_listings WHERE Key_name = 'idx_trade_listings_pin';
-- ADD only. No UPDATE, DROP, or backfill.

ALTER TABLE `seo_trade_listings`
  ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0 AFTER `views_count`,
  ADD COLUMN `is_hot` ENUM('Y', 'N') NOT NULL DEFAULT 'N' AFTER `sort_order`,
  ADD COLUMN `is_top` ENUM('Y', 'N') NOT NULL DEFAULT 'N' AFTER `is_hot`,
  ADD KEY `idx_trade_listings_pin` (`is_top`, `is_hot`, `sort_order`);
