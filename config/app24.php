<?php

return [
    'app' => [
        'name' => env('APP_NAME'),
        'def_lang' => env('APP_DEFAULT_LANG', 'en'),
        'views' => env('APP_VIEWS', \Flamix\App24Core\Controllers\ViewsController::class),
    ],
    'access' => [
        'id' => env('APP24_ID', false),
        'secret' => env('APP24_SECRET', false),
        'scope' => env('APP24_SCOPE', 'user'),
        'admin_only' => env('APP_ADMIN_ONLY', 0),
        'access_type' => env('APP_ACCESSED_TYPE', false),
        'deny_type' => env('APP_DENIED_TYPE', false),
        'secret_force_update' => env('APP_TOKEN_UPDATE_FORCE', false), // Do I need to refresh the token when installing and updating?
    ],
    'cache' => [
        'domain' => 600, // 10 minutes
    ]
];