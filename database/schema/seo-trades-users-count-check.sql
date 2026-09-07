-- Review only. Do not run ALTER until this returns 0 twice.
-- Step 1 of deploy: stop the old trades host, then run this query.
-- If COUNT(*) is not 0, stop. Do not execute seo-trades-alter-users-v3.sql.

SELECT COUNT(*) AS seo_trade_users_count FROM seo_trade_users;
