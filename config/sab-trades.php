<?php

return [
    'fair_threshold_percent' => (float) env('FAIR_THRESHOLD_PERCENT', 5),
    'trade_expire_hours' => (int) env('TRADE_EXPIRE_HOURS', 72),
    'max_items_per_side' => (int) env('MAX_ITEMS_PER_SIDE', 9),
    'max_active_listings_per_user' => (int) env('MAX_ACTIVE_LISTINGS_PER_USER', 10),
    'post_trade_limit_per_hour' => (int) env('POST_TRADE_LIMIT_PER_HOUR', 10),
    'post_trade_limit_per_day' => (int) env('POST_TRADE_LIMIT_PER_DAY', 2),
    'join_limit_per_hour' => (int) env('JOIN_LIMIT_PER_HOUR', 30),
    'report_limit_per_day' => (int) env('REPORT_LIMIT_PER_DAY', 20),
    'contact_limit_per_user' => 5,
    'moderator_roblox_subs' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRADE_MODERATOR_ROBLOX_SUBS', ''))
    ))),
    'allow_email_login' => filter_var(env('ALLOW_EMAIL_LOGIN', false), FILTER_VALIDATE_BOOL),
    'allow_email_bind' => filter_var(env('ALLOW_EMAIL_BIND', false), FILTER_VALIDATE_BOOL),
];
