<?php

declare(strict_types=1);

return [
    'driver' => 'mysql',
    'host' => getenv('DB_HOST') ?: 'mariadb',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'rchsnd',
    'username' => getenv('DB_USERNAME') ?: 'rchsnd',
    'password' => getenv('DB_PASSWORD') ?: 'rchsnd',
    'charset' => 'utf8mb4',
];
