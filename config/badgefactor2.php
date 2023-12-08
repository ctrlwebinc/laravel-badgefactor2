<?php

return [
    'domain'     => env('BF2_DOMAIN_NAME', null),
    'encryption' => [
        'algorithm'  => env('BF2_ENCRYPTION_ALGORITHM'),
        'secret_key' => env('BF2_SECRET_KEY'),
        'secret_iv'  => env('BF2_SECRET_IV'),
    ],
    'wordpress' => [
        'connection' => env('WP_DB_CONNECTION', 'wordpress'),
        'db_prefix'  => env('WP_DB_PREFIX', 'wp_'),
    ],
    'badgr' => [
        'server_url'      => env('BADGR_SERVER_URL'),
        'admin_scopes'    => 'rw:backpack rw:profile rw:issuer rw:serverAdmin',
        'personal_scopes' => 'rw:backpack rw:profile',
    ],
    'search_controller' => env('BF2_SEARCH_CONTROLLER', Ctrlweb\BadgeFactor2\Http\Controllers\Api\SearchController::class),
];
