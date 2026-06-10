<?php

declare(strict_types=1);

return [
    'driver' => 'mysql',
    'host' => getenv('DB_HOST') ?: 'mysql',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'template',
    'username' => getenv('DB_USERNAME') ?: 'template',
    'password' => getenv('DB_PASSWORD') ?: 'template',
    'charset' => 'utf8mb4',
];
