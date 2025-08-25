<?php
// Database configuration
return [
    'default' => 'db_digitex', // Base de données par défaut
    'connections' => [
        'db_digitex' => [
            'host' => '127.0.0.1',
            'dbname' => 'db_mahdco',
            'username' => 'root',
            'password' => 'Testing321',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ],
        // 'MAHDCO_MAINT' => [
        //     'host' => '127.0.0.1',
        //     'dbname' => 'MAHDCO_MAINT',
        //     'username' => 'root',
        //     'password' => 'Testing321',
        //     'charset' => 'utf8mb4',
        //     'options' => [
        //         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        //         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        //         PDO::ATTR_EMULATE_PREPARES => false,
        //     ]
        // ]
    ]
];
