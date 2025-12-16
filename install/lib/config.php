<?php

$config =  [
    'extensions' => [
        'BCMath',
        'Ctype',
        'Fileinfo',
        'JSON',
        'Mbstring',
        'OpenSSL',
        'PDO',
        'pdo_mysql',
        'pdo_pgsql', // For PostgreSQL support
        'Tokenizer',
        'XML',
        'cURL',
        'GD'
        
    ],

    'permissions' => [
        '../main/bootstrap/cache/',
        '../main/storage/',
        '../main/storage/app/',
        '../main/storage/framework/',
        '../main/storage/logs/'
    ],
    
    'database_types' => [
        'auto' => 'Auto-detect',
        'mysql' => 'MySQL',
        'mariadb' => 'MariaDB',
        'postgresql' => 'PostgreSQL'
    ]
];
