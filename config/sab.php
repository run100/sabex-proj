<?php

return [
    'console_path' => env('CONSOLE_PATH', 'j8xq-4n2m-w9kp'),
    'admin_allow_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ADMIN_ALLOW_IPS', '127.0.0.1,::1'))
    ))),
    'url_prefix' => env('SAB_URL_PREFIX', ''),
    'hosts' => [
        'www' => env('SAB_WWW_HOST', 'www.sabex.lab'),
        'admin' => env('SAB_ADMIN_HOST', 'x.sabex.lab'),
        'trades' => env('SAB_TRADES_HOST', 'trades.sabex.lab'),
    ],
    'geoflow_root' => env('GEOFLOW_ROOT', dirname(base_path()).'/geo-ant-design-pro'),
    'geoflow_export_script' => env(
        'GEOFLOW_EXPORT_SCRIPT',
        dirname(base_path()).'/geo-ant-design-pro/scripts/export-sab-to-mysql.php'
    ),
];
