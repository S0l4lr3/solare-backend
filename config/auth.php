<?php

return [

    'defaults' => [
        'guard' => 'api',
        'passwords' => 'usuarios_solare',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'usuarios_solare',
        ],
        'api' => [
            'driver' => 'sanctum',
            'provider' => 'usuarios_solare',
        ],
    ],

    'providers' => [
        'usuarios_solare' => [
            'driver' => 'eloquent',
            'model' => App\Models\Usuario::class,
        ],
    ],

    'passwords' => [
        'usuarios_solare' => [
            'provider' => 'usuarios_solare',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
