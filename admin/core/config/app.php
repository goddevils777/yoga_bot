<?php
// Просто подключаем существующую конфигурацию
require_once __DIR__ . '/../../../bot/api/config.php';

// Для совместимости создаем алиас
class CoreConfig {
    public static function getInstance() {
        return Config::getInstance();
    }
    
    public static function get($key) {
        switch($key) {
            case 'JWT_SECRET':
                return $_ENV['JWT_SECRET'] ?? 'default-jwt-secret-key';
            case 'ADMIN_AUTH_CODE':
                return $_ENV['ADMIN_AUTH_CODE'] ?? 'admin123';
            default:
                return $_ENV[$key] ?? null;
        }
    }
}
?>