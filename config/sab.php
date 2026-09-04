<?php

return [
    'console_path' => env('CONSOLE_PATH', 'j8xq-4n2m-w9kp'),
    'admin_allow_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ADMIN_ALLOW_IPS', '127.0.0.1,::1'))
    ))),
    'url_prefix' => env('SAB_URL_PREFIX', ''),
];
