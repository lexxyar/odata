<?php
return [
    'namespace' => [
        'models' => '\\App\\Models',
        'controllers' => '\\App\\Http\\Controllers',
    ],
    'routes' => [
        'register' => false,
        'middlewares' => [
            'auth',
        ]
    ]
];
