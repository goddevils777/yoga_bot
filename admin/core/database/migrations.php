<?php
class DatabaseMigrations {
    private $pdo;
    
    public function __construct() {
        // Читаем локальный .env файл
        $envFile = __DIR__ . '/../../../bot/api/.env.local';
        if (!file_exists($envFile)) {
            throw new Exception(".env.local file not found! Create it first.");
        }
        
        $env = $this->loadEnv($envFile);
        
        echo "Подключаюсь к БД: {$env['dbHost']}:{$env['dbDatabase']}\n";
        
        // Подключаемся к БД через PDO
        $dsn = "mysql:host={$env['dbHost']};dbname={$env['dbDatabase']};charset=utf8mb4";
        try {
            $this->pdo = new PDO($dsn, $env['dbUsername'], $env['dbPassword'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            echo "✅ Подключение к БД установлено\n";
        } catch (PDOException $e) {
            throw new Exception("DB Connection failed: " . $e->getMessage());
        }
    }
    
    private function loadEnv($file) {
        $env = [];
        if (file_exists($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && $line[0] !== '#') {
                    list($key, $value) = explode('=', $line, 2);
                    $env[trim($key)] = trim($value);
                }
            }
        } else {
            throw new Exception("Env file not found: " . $file);
        }
        return $env;
    }

    // Остальные методы остаются теми же...
    public function createTables() {
        echo "Создаю таблицы для админ-панели...\n";
        
        // Таблица ботов
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS bots (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            token VARCHAR(255) NOT NULL,
            webhook_url VARCHAR(255) NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            theme VARCHAR(100) DEFAULT 'general',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "✅ Таблица bots создана\n";
        
        // Таблица админов
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            telegram_id BIGINT UNIQUE NOT NULL,
            username VARCHAR(100),
            first_name VARCHAR(100),
            role ENUM('owner', 'admin', 'manager') NOT NULL,
            bot_access TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "✅ Таблица admins создана\n";
        
        // Таблица контента
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS bot_content (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bot_id INT NOT NULL,
            content_key VARCHAR(100) NOT NULL,
            title VARCHAR(255),
            text TEXT,
            media_id VARCHAR(255),
            media_type ENUM('photo', 'video', 'document'),
            buttons JSON,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_bot_content (bot_id, content_key)
        )");
        echo "✅ Таблица bot_content создана\n";
        
        // Таблица рассылок
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS broadcasts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bot_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            media_id VARCHAR(255),
            media_type ENUM('photo', 'video', 'document'),
            target_type ENUM('all', 'active', 'group', 'custom') DEFAULT 'all',
            target_groups JSON,
            status ENUM('draft', 'sending', 'completed', 'failed') DEFAULT 'draft',
            total_recipients INT DEFAULT 0,
            sent_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            sent_at TIMESTAMP NULL,
            INDEX idx_bot_broadcasts (bot_id)
        )");
        echo "✅ Таблица broadcasts создана\n";
        
        // Таблица действий в группах
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS group_actions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bot_id INT NOT NULL,
            action_type ENUM('block', 'unblock', 'restrict') NOT NULL,
            target_id BIGINT NOT NULL,
            reason VARCHAR(255),
            admin_id BIGINT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_group_actions (bot_id, target_id)
        )");
        echo "✅ Таблица group_actions создана\n";
        
        // Добавим первого бота
        $this->insertDefaultBot();
        
        echo "\n🎉 Все таблицы созданы успешно!\n";
    }
    
    private function insertDefaultBot() {
        // Проверим есть ли боты
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM bots");
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $stmt = $this->pdo->prepare("INSERT INTO bots (name, token, webhook_url, theme) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                'Yoga Hub Bot',
                '7706921145:AAEz3J6R001wWuFTEYQ6k4u3_9G1seqyN4k',
                'https://bot.yoga-hub.club/bots/telegram/helpme_yhc_bot/app.php',
                'yoga'
            ]);
            echo "✅ Добавлен существующий бот в админку\n";
        }
    }
}
?>