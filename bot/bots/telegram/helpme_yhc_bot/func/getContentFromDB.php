<?php

function getContentFromDB($content_key, $message) {
    $database = Config::getDatabase();
    
    // Лог: Что ищем
    file_put_contents(__DIR__ . '/../debug.log', 
        date('Y-m-d H:i:s') . " Ищем контент: $content_key для user: {$message['from_id']}\n", 
        FILE_APPEND
    );
    
    $content = $database->get('bot_content', '*', [
        'content_key' => $content_key,
        'status' => 'active'
    ]);
    
    // Лог: Что нашли
    file_put_contents(__DIR__ . '/../debug.log', 
        date('Y-m-d H:i:s') . " Найдено: " . ($content ? json_encode($content) : 'NULL') . "\n", 
        FILE_APPEND
    );
    
    if (!$content) {
        file_put_contents(__DIR__ . '/../debug.log', 
            date('Y-m-d H:i:s') . " ОШИБКА: Контент не найден\n", 
            FILE_APPEND
        );
        
        sendMessage('sendMessage', [
            'chat_id' => $message['from_id'],
            'text' => 'Контент не найден',
            'parse_mode' => 'HTML'
        ]);
        return;
    }
    
    // Формируем клавиатуру
    $keyboard = null;
    if ($content['buttons']) {
        $buttons = json_decode($content['buttons'], true);
        if ($buttons) {
            $keyboard = inlineKeyboard(array_map(function($btn) {
                return [$btn];
            }, $buttons));
        }
    }
    
    // Лог: Отправка
    file_put_contents(__DIR__ . '/../debug.log', 
        date('Y-m-d H:i:s') . " Отправляем сообщение с медиа: " . ($content['media_id'] ? 'Да' : 'Нет') . "\n", 
        FILE_APPEND
    );
    
    // Если есть медиа - отправляем с медиа
    if ($content['media_id']) {
        $result = sendMessage('sendPhoto', [
            'chat_id' => $message['from_id'],
            'photo' => $content['media_id'],
            'caption' => $content['text'],
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard
        ]);
        
        file_put_contents(__DIR__ . '/../debug.log', 
            date('Y-m-d H:i:s') . " Результат отправки фото: " . ($result ? 'OK' : 'FAIL') . "\n", 
            FILE_APPEND
        );
    } else {
        // Без медиа - просто текст
        $result = sendMessage('sendMessage', [
            'chat_id' => $message['from_id'],
            'text' => $content['text'],
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard
        ]);
        
        file_put_contents(__DIR__ . '/../debug.log', 
            date('Y-m-d H:i:s') . " Результат отправки текста: " . ($result ? 'OK' : 'FAIL') . "\n", 
            FILE_APPEND
        );
    }
}

?>