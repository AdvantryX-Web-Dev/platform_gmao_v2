<?php
// Database configuration
return [
    'default' => 'MAHDCO_MAINT', // Base de données par défaut
    'connections' => [
        'db_digitex' => [
            'host' => 'mysql',
            'dbname' => 'db_mahdco',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ],
        'MAHDCO_MAINT' => [
            'host' => 'mysql',
            'dbname' => 'MAHDCO_MAINT',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ]
    ]
];
