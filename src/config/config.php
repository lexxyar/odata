<?php
return [
    'namespace' => [
        'models' => '\\App\\Models',
        'controllers' => '\\App\\Http\\Controllers',
    ],
    'routes' => [
        'middlewares' => [
            'auth',
        ]
    ]
];
