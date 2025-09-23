<?php
require_once 'admin/core/database/migrations.php';

echo "Создание таблиц для админ-панели...\n";

try {
    $migrations = new DatabaseMigrations();
    $migrations->createTables();
    
    echo "✅ Таблицы созданы успешно!\n";
    echo "\n📋 Следующие шаги:\n";
    echo "1. Добавьте в bot/api/.env файл: JWT_SECRET=your-secret-key-here\n";
    echo "2. Добавьте в bot/api/.env файл: ADMIN_AUTH_CODE=admin123\n";
    echo "3. Откройте admin/public/index.html в браузере\n";
    echo "4. Используйте ваш Telegram ID и код 'admin123' для входа\n\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>