<?php

return [
    'domain' => env('BF2_DOMAIN_NAME', null),
    'encryption' => [
        'algorithm'  => env('BF2_ENCRYPTION_ALGORITHM'),
        'secret_key' => env('BF2_SECRET_KEY'),
        'secret_iv'  => env('BF2_SECRET_IV'),
    ],
    'frontend' => [
        'url' => env('FRONTEND_BASE_URL', 'http://localhost:3000'),
    ],
    'wordpress' => [
        'connection'  => env('WP_DB_CONNECTION', 'wordpress'),
        'db_prefix'   => env('WP_DB_PREFIX', 'wp_'),
        'avatars_dir' => env('WP_AVATARS_DIR', null),
        'base_url'    => env('WP_BASE_URL', null),
        'htaccess'    => [
            'user'     => env('WP_HTACCESS_USER'),
            'password' => env('WP_HTACCESS_PASSWORD'),
        ],
    ],
    'badgr' => [
        'server_url'      => env('BADGR_SERVER_URL'),
        'admin_scopes'    => 'rw:backpack rw:profile rw:issuer rw:serverAdmin',
        'personal_scopes' => 'rw:backpack rw:profile'
    ],
    'user_model'        => env('BF2_USER_MODEL', \Ctrlweb\BadgeFactor2\Models\User::class),
    'search_controller' => env('BF2_SEARCH_CONTROLLER', \Ctrlweb\BadgeFactor2\Http\Controllers\Api\SearchController::class),
];
