<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$rootDir = __DIR__;

echo "Проверяем файлы:\n";
echo ".env.local exists: " . (file_exists($rootDir . '/.env.local') ? 'YES' : 'NO') . "\n";
echo ".env exists: " . (file_exists($rootDir . '/.env') ? 'YES' : 'NO') . "\n\n";

if (file_exists($rootDir . '/.env.local')) {
    $dotenv = Dotenv\Dotenv::createImmutable($rootDir, '.env.local');
    echo "Загружаем: .env.local\n";
} else {
    $dotenv = Dotenv\Dotenv::createImmutable($rootDir, '.env');
    echo "Загружаем: .env\n";
}

$dotenv->load();

echo "\nБаза данных:\n";
echo "dbHost: " . $_ENV['dbHost'] . "\n";
echo "dbDatabase: " . $_ENV['dbDatabase'] . "\n";
echo "dbUsername: " . $_ENV['dbUsername'] . "\n";