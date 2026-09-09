-- Review only. Do not run until confirmed.
-- SABExistCount Trades (PRD v1.0, adapted to this repo).
-- Does not touch seo_items / seo_item_variants / price JSON.
-- Skips seo_trade_sessions and seo_trade_oauth_states
-- (Laravel session + PKCE in session; tokens are not stored).
-- Login identities live in seo_trade_auth_accounts (email / roblox / google / local).
--
-- Adaptations vs PRD:
--   roblox_user_id BIGINT -> roblox_sub VARCHAR(64)
--   brainrot_id -> seo_item_id (+ slug_snapshot)
--   mutation_id -> seo_item_variant_id
--   trait_id -> trait_name

CREATE TABLE IF NOT EXISTS `seo_trade_users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `public_id` CHAR(26) NOT NULL,
  `profile_id` CHAR(26) NOT NULL,
  `roblox_sub` VARCHAR(64) DEFAULT NULL,
  `roblox_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `username` VARCHAR(100) NOT NULL,
  `display_name` VARCHAR(100) DEFAULT NULL,
  `avatar_url` VARCHAR(500) DEFAULT NULL,
  `profile_url` VARCHAR(500) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `email_verified_at` DATETIME DEFAULT NULL,
  `account_status` ENUM('active', 'suspended', 'banned', 'deleted') NOT NULL DEFAULT 'active',
  `profile_visibility` ENUM('public', 'unlisted', 'private') NOT NULL DEFAULT 'public',
  `moderation_status` ENUM('clear', 'review', 'restricted') NOT NULL DEFAULT 'clear',
  `profile_index_eligible` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME DEFAULT NULL,
  `posting_approved` TINYINT(1) NOT NULL DEFAULT 1,
  `posting_approved_at` DATETIME DEFAULT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `remember_token` VARCHAR(100) DEFAULT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_trade_users_public_id` (`public_id`),
  UNIQUE KEY `uk_trade_users_profile_id` (`profile_id`),
  UNIQUE KEY `uk_trade_users_roblox_sub` (`roblox_sub`),
  UNIQUE KEY `uk_trade_users_roblox_user_id` (`roblox_user_id`),
  UNIQUE KEY `uk_trade_users_email` (`email`),
  KEY `idx_trade_users_username` (`username`),
  KEY `idx_trade_users_status` (`account_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `seo_trade_listings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `public_id` CHAR(26) NOT NULL,
  `owner_user_id` BIGINT UNSIGNED NOT NULL,
  `counterparty_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `status` ENUM(
    'open',
    'pending',
    'pending_confirmation',
    'completed',
    'failed',
    'disputed',
    'cancelled',
    'expired',
    'hidden'
  ) NOT NULL DEFAULT 'open',
  `result_snapshot` ENUM('win', 'fair', 'lose', 'na') NOT NULL DEFAULT 'na',
  `offering_value_snapshot` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `looking_value_snapshot` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `value_difference_snapshot` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `difference_percent_snapshot` DECIMAL(12,4) DEFAULT NULL,
  `note` VARCHAR(280) DEFAULT NULL,
  `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_hot` ENUM('Y', 'N') NOT NULL DEFAULT 'N',
  `is_top` ENUM('Y', 'N') NOT NULL DEFAULT 'N',
  `accepted_at` DATETIME DEFAULT NULL,
  `pending_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `failed_at` DATETIME DEFAULT NULL,
  `cancelled_at` DATETIME DEFAULT NULL,
  `expires_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_trade_listings_public_id` (`public_id`),
  KEY `idx_trade_listings_owner_status` (`owner_user_id`, `status`, `created_at`),
  KEY `idx_trade_listings_counterparty_status` (`counterparty_user_id`, `status`),
  KEY `idx_trade_listings_status_created` (`status`, `created_at`),
  KEY `idx_trade_listings_completed_at` (`completed_at`),
  KEY `idx_trade_listings_pin` (`is_top`, `is_hot`, `sort_order`),
  CONSTRAINT `fk_trade_listing_owner`
    FOREIGN KEY (`owner_user_id`) REFERENCES `seo_trade_users` (`id`),
  CONSTRAINT `fk_trade_listing_counterparty`
    FOREIGN KEY (`counterparty_user_id`) REFERENCES `seo_trade_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `seo_trade_listing_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `listing_id` BIGINT UNSIGNED NOT NULL,
  `side` ENUM('offering', 'looking_for') NOT NULL,
  `slot_no` TINYINT UNSIGNED NOT NULL,
  `seo_item_id` BIGINT UNSIGNED NOT NULL,
  `slug_snapshot` VARCHAR(190) NOT NULL,
  `brainrot_name_snapshot` VARCHAR(190) NOT NULL,
  `image_url_snapshot` VARCHAR(500) DEFAULT NULL,
  `seo_item_variant_id` BIGINT UNSIGNED DEFAULT NULL,
  `mutation_name_snapshot` VARCHAR(190) DEFAULT NULL,
  `base_value_snapshot` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `mutation_value_multiplier_snapshot` DECIMAL(12,6) NOT NULL DEFAULT 1,
  `trait_value_multiplier_snapshot` DECIMAL(12,6) NOT NULL DEFAULT 1,
  `final_value_snapshot` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `base_income_snapshot` DECIMAL(30,4) DEFAULT NULL,
  `final_income_snapshot` DECIMAL(30,4) DEFAULT NULL,
  `demand_snapshot` VARCHAR(50) DEFAULT NULL,
  `exist_count_snapshot` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_trade_item_slot` (`listing_id`, `side`, `slot_no`),
  KEY `idx_trade_items_listing_side` (`listing_id`, `side`),
  KEY `idx_trade_items_seo_item_side` (`seo_item_id`, `side`, `listing_id`),
  KEY `idx_trade_items_variant` (`seo_item_variant_id`),
  CONSTRAINT `fk_trade_item_listing`
    FOREIGN KEY (`listing_id`) REFERENCES `seo_trade_listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_trade_item_slot` CHECK (`slot_no` BETWEEN 1 AND 9)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `seo_trade_listing_item_traits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `listing_item_id` BIGINT UNSIGNED NOT NULL,
  `trait_name` VARCHAR(100) NOT NULL,
  `trait_name_snapshot` VARCHAR(190) NOT NULL,
  `value_multiplier_snapshot` DECIMAL(12,6) NOT NULL DEFAULT 1,
  `income_multiplier_snapshot` DECIMAL(12,6) NOT NULL DEFAULT 1,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_trade_item_trait` (`listing_item_id`, `trait_name`),
  KEY `idx_trade_trait_name` (`trait_name`),
  CONSTRAINT `fk_trade_item_traits_item`
    FOREIGN KEY (`listing_item_id`) REFERENCES `seo_trade_listing_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `seo_trade_join_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `public_id` CHAR(26) NOT NULL,
  `listing_id` BIGINT UNSIGNED NOT NULL,
  `requester_user_id` BIGINT UNSIGNED NOT NULL,
  `owner_user_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('requested', 'accepted', 'rejected', 'auto_rejected', 'cancelled', 'expired') NOT NULL DEFAULT 'requested',
  `note` VARCHAR(280) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `accepted_at` DATETIME DEFAULT NULL,
  `rejected_at` DATETIME DEFAULT NULL,
  `cancelled_at` DATETIME DEFAULT NULL,
  `expires_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_trade_join_public_id` (`public_id`),
  KEY `idx_trade_join_listing_status` (`listing_id`, `status`, `created_at`),
  KEY `idx_trade_join_requester_status` (`requester_user_id`, `status`, `created_at`),
  KEY `idx_trade_join_owner_status` (`owner_user_id`, `status`),
  KEY `idx_trade_join_pair` (`requester_user_id`, `owner_user_id`),
  CONSTRAINT `fk_trade_join_listing`
    FOREIGN KEY (`listing_id`) REFERENCES `seo_trade_listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_trade_join_requester`
    FOREIGN KEY (`requester_user_id`) REFERENCES `seo_trade_users` (`id`),
  CONSTRAINT `fk_trade_join_owner`
    FOREIGN KEY (`owner_user_id`) REFERENCES `seo_trade_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `seo_trade_confirmations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `listing_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `confirmation` ENUM('completed', 'failed') NOT NULL,
  `note` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_trade_confirmation_user` (`listing_id`, `user_id`),
  KEY `idx_trade_confirmations_listing` (`listing_id`),
  CONSTRAINT `fk_trade_confirmation_listing`
    FOREIGN KEY (`listing_id`) REFERENCES `seo_trade_listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_trade_confirmation_user`
    FOREIGN KEY (`user_id`) REFERENCES `seo_trade_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `seo_trade_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `listing_id` BIGINT UNSIGNED NOT NULL,
  `actor_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `event_type` VARCHAR(64) NOT NULL,
  `metadata` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_trade_events_listing_created` (`listing_id`, `created_at`),
  KEY `idx_trade_events_actor` (`actor_user_id`, `created_at`),
  KEY `idx_trade_events_type` (`event_type`, `created_at`),
  CONSTRAINT `fk_trade_event_listing`
    FOREIGN KEY (`listing_id`) REFERENCES `seo_trade_listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_trade_event_actor`
    FOREIGN KEY (`actor_user_id`) REFERENCES `seo_trade_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `seo_trade_notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `type` VARCHAR(64) NOT NULL,
  `listing_id` BIGINT UNSIGNED DEFAULT NULL,
  `join_request_id` BIGINT UNSIGNED DEFAULT NULL,
  `actor_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(190) NOT NULL,
  `message` VARCHAR(500) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_trade_notifications_user_read` (`user_id`, `is_read`, `created_at`),
  KEY `idx_trade_notifications_listing` (`listing_id`),
  KEY `idx_trade_notifications_message_pair` (`actor_user_id`, `user_id`, `type`),
  CONSTRAINT `fk_trade_notification_user`
    FOREIGN KEY (`user_id`) REFERENCES `seo_trade_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_trade_notification_listing`
    FOREIGN KEY (`listing_id`) REFERENCES `seo_trade_listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_trade_notification_join`
    FOREIGN KEY (`join_request_id`) REFERENCES `seo_trade_join_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_trade_notification_actor`
    FOREIGN KEY (`actor_user_id`) REFERENCES `seo_trade_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `seo_trade_reports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `public_id` CHAR(26) NOT NULL,
  `reporter_user_id` BIGINT UNSIGNED NOT NULL,
  `listing_id` BIGINT UNSIGNED DEFAULT NULL,
  `reported_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `reason` ENUM('spam', 'fake_trade', 'scam', 'abuse', 'inappropriate', 'duplicate', 'other') NOT NULL,
  `description` VARCHAR(1000) DEFAULT NULL,
  `status` ENUM('open', 'reviewing', 'resolved', 'dismissed') NOT NULL DEFAULT 'open',
  `resolution_note` VARCHAR(1000) DEFAULT NULL,
  `reviewed_by` BIGINT UNSIGNED DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_trade_report_public_id` (`public_id`),
  KEY `idx_trade_reports_status_created` (`status`, `created_at`),
  KEY `idx_trade_reports_listing` (`listing_id`),
  KEY `idx_trade_reports_reported_user` (`reported_user_id`),
  CONSTRAINT `fk_trade_report_reporter`
    FOREIGN KEY (`reporter_user_id`) REFERENCES `seo_trade_users` (`id`),
  CONSTRAINT `fk_trade_report_listing`
    FOREIGN KEY (`listing_id`) REFERENCES `seo_trade_listings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_trade_report_reported_user`
    FOREIGN KEY (`reported_user_id`) REFERENCES `seo_trade_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `seo_trade_user_blocks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `blocked_user_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_trade_user_block` (`user_id`, `blocked_user_id`),
  KEY `idx_trade_blocks_blocked_user` (`blocked_user_id`),
  CONSTRAINT `fk_trade_block_owner`
    FOREIGN KEY (`user_id`) REFERENCES `seo_trade_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_trade_block_target`
    FOREIGN KEY (`blocked_user_id`) REFERENCES `seo_trade_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_trade_block_not_self` CHECK (`user_id` <> `blocked_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `seo_trade_user_stats` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `trades_posted` INT UNSIGNED NOT NULL DEFAULT 0,
  `trades_joined` INT UNSIGNED NOT NULL DEFAULT 0,
  `trades_accepted` INT UNSIGNED NOT NULL DEFAULT 0,
  `trades_completed` INT UNSIGNED NOT NULL DEFAULT 0,
  `trades_failed` INT UNSIGNED NOT NULL DEFAULT 0,
  `trades_disputed` INT UNSIGNED NOT NULL DEFAULT 0,
  `completion_rate` DECIMAL(8,4) DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_trade_stats_user`
    FOREIGN KEY (`user_id`) REFERENCES `seo_trade_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
