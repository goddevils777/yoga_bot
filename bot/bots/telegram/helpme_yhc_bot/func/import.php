<?php 
// Рекурсивная функция для обхода директорий
function requireAllPhpFiles($dir) {
    // Получаем все PHP файлы в текущей директории
    $files = glob($dir . '/*.php');
    
    foreach ($files as $file) {
        if ($file !== __FILE__) {
            require_once $file;
        }
    }
    
    // Получаем все поддиректории
    $directories = glob($dir . '/*', GLOB_ONLYDIR);
    
    // Рекурсивно обходим каждую поддиректорию
    foreach ($directories as $directory) {
        requireAllPhpFiles($directory);
    }
}

// Запускаем рекурсивное подключение файлов
requireAllPhpFiles(__DIR__);

//Fast message for dev admin
function debugMessage($text) {
    sendMessage('sendMessage', [
        'chat_id' => DEV_ID,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_notification' => true
    ]);
}

?>