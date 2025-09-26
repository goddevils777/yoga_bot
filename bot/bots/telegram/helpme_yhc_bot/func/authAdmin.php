<?php

/**
 * Авторизация администратора по коду
 * @param array $message
 * @param string $auth_code - код авторизации
 */
function authAdmin($message, $auth_code) {
    $database = Config::getDatabase();
    
    // Получаем правильный код из Config
    $correct_code = Config::getAdminAuthCode();
    
    // Проверяем код
    if ($auth_code !== $correct_code) {
        sendMessage('sendMessage', [
            'chat_id' => $message['from_id'],
            'text' => '❌ Неверный код доступа',
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    
    // Обновляем роль пользователя на admin
    $database->update('users', 
        ['role' => 'admin'],
        ['telegram_id' => $message['from_id']]
    );
    
    if ($database->errorInfo) {
        logError("Ошибка авторизации админа: " . $database->errorInfo);
        return false;
    }
    
    sendMessage('sendMessage', [
        'chat_id' => $message['from_id'],
        'text' => '✅ Вы успешно авторизованы как администратор!',
        'parse_mode' => 'HTML'
    ]);
    
    return true;
}