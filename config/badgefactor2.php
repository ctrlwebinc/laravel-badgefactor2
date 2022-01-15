<?php

return [
    'domain' => env('BF2_DOMAIN_NAME', null),
    'wordpress' => [
        'connection' => env('WORDPRESS_CONNECTION', 'wordpress'),
        'db_prefix' => env('WORDPRESS_DB_PREFIX'), 'wp_',
    ],
];
