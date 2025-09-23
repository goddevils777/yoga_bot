<?php

$pathAutoload = '/home/ej359436/vendor/autoload.php';
require_once $pathAutoload;

//Декодирование entity в HTML
use lucadevelop\TelegramEntitiesDecoder\EntityDecoder;
$entity_decoder = new EntityDecoder('HTML');

class Config {
    private static $instance = null;
    private static $openai_api_key;
    //PROD SERVICE
    public static $telegram_api_bot;
    ////DEBUG MESSAGE TELEGRAM ID
    public static $report_id;
    public static $database;
    public static $APPATH;

    private function __construct() {
        // Файл config.php находится в той же директории, где и .env файлы
        $rootDir = __DIR__;
        
        // Определяем, какой .env загружать на основе пути текущего скрипта
        $isDevelopment = strpos($_SERVER['SCRIPT_FILENAME'], '/dev-') !== false;
        
        if ($isDevelopment && file_exists($rootDir . '/.env.development')) {
            $dotenv = Dotenv\Dotenv::createImmutable($rootDir, '.env.development');
        } else {
            $dotenv = Dotenv\Dotenv::createImmutable($rootDir, '.env');
        }
        
        try {
            $dotenv->load();
        } catch (Exception $e) {
            throw new RuntimeException('Ошибка загрузки .env файла: ' . $e->getMessage());
        }
        
        self::loadFromEnv();
        self::initDatabase();
    }

    // Загрузка конфигурации из .env файла
    private static function loadFromEnv() {
        self::$APPATH = $_ENV['APPATH'];
        //PROD SERVICE
        self::$openai_api_key = $_ENV['OPENAI_API_KEY'];
        self::$telegram_api_bot = $_ENV['TELEGRAM_API_KEY'];
        //DEBUG MESSAGE TELEGRAM ID
        self::$report_id = $_ENV['DEV_ID'];//dev report Alex
    }

    // Инициализация базы данных
    private static function initDatabase() {
        try {
            self::$database = new Medoo\Medoo([
                'type'     => $_ENV['dbType'],
                'host'     => $_ENV['dbHost'],
                'database' => $_ENV['dbDatabase'],
                'username' => $_ENV['dbUsername'],
                'password' => $_ENV['dbPassword'],
                'port'     => 3306,
                'charset'  => 'utf8mb4',
                'collation' => 'utf8mb4_general_ci',
                'logging'  => true,
                'option'   => [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
                'error'    => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (PDOException $e) {
            error_log("Ошибка подключения к БД: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Метод для получения экземпляра конфигурации
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    // Получить соединение с базой данных
    public static function getDatabase() {
        return self::$database;
    }
    // Получить API ключ для OpenAI
    public static function getOpenaiApiKey() {
        return self::$openai_api_key;
    }
    // API ключ для Telegram бота
    public static function getTelegramApiKey() {
        return self::$telegram_api_bot;
    }
    // ID модератора для отправки отчетов
    public static function getReportId() {
        return self::$report_id;
    }

    public static function getApPath() {
        return self::$APPATH;
    }

}

// Инициализация конфигурации
Config::getInstance();
// Теперь все получаем через геттеры
//$database = Config::getDatabase();
//Api Open AI
use Orhanerday\OpenAi\OpenAi;
$openai_api_key = $_ENV['OPENAI_API_KEY'];
$client = new OpenAi($openai_api_key);
$client->setAssistantsBetaVersion("v2");

?>