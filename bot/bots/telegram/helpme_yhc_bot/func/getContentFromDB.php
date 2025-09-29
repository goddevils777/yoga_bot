<?php

function getContentFromDB($content_key, $message) {
    $database = Config::getDatabase();
    
    if (!$database) {
        file_put_contents(__DIR__ . '/../debug.log', 
            date('Y-m-d H:i:s') . " ОШИБКА: Database is NULL\n", 
            FILE_APPEND
        );
        sendMessage('sendMessage', [
            'chat_id' => $message['from_id'],
            'text' => 'Ошибка подключения к БД',
            'parse_mode' => 'HTML'
        ]);
        return;
    }
    
    file_put_contents(__DIR__ . '/../debug.log', 
        date('Y-m-d H:i:s') . " Database OK, ищем: $content_key для user: {$message['from_id']}\n", 
        FILE_APPEND
    );

    $content = $database->get('bot_content', '*', [
        'bot_id' => 1,
        'content_key' => $content_key,
        'status' => 'active'
    ]);
    
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
    
 
    $keyboard = null;
    if (!empty($content['buttons'])) {
        // Убираем лишние кавычки если JSON обернут в строку
        $buttonsData = trim($content['buttons'], '"');
        $buttonsData = stripslashes($buttonsData);
            
        file_put_contents(__DIR__ . '/../debug.log', 
            date('Y-m-d H:i:s') . " Buttons RAW: " . $buttonsData . "\n", 
            FILE_APPEND
        );
        
        $buttons = json_decode($buttonsData, true);
        $jsonError = 'Error code: ' . json_last_error();
        
        file_put_contents(__DIR__ . '/../debug.log', 
            date('Y-m-d H:i:s') . " Buttons PARSED: " . ($buttons ? json_encode($buttons) : 'NULL') . "\n", 
            FILE_APPEND
        );
        
        file_put_contents(__DIR__ . '/../debug.log', 
            date('Y-m-d H:i:s') . " JSON Error: " . $jsonError . "\n", 
            FILE_APPEND
        );
        
        if ($buttons && is_array($buttons)) {
            $keyboard = inlineKeyboard(array_map(function($btn) {
                return [$btn];
            }, $buttons));
            
            file_put_contents(__DIR__ . '/../debug.log', 
                date('Y-m-d H:i:s') . " Keyboard created successfully\n", 
                FILE_APPEND
            );
        } else {
            file_put_contents(__DIR__ . '/../debug.log', 
                date('Y-m-d H:i:s') . " ERROR: Buttons is not array or empty\n", 
                FILE_APPEND
            );
        }
    }
    
    file_put_contents(__DIR__ . '/../debug.log', 
        date('Y-m-d H:i:s') . " Отправляем сообщение. Медиа: " . ($content['media_id'] ? 'Да' : 'Нет') . ", Кнопки: " . ($keyboard ? 'Да' : 'Нет') . "\n", 
        FILE_APPEND
    );
    
    if ($content['media_id']) {
        $params = [
            'chat_id' => $message['from_id'],
            'photo' => $content['media_id'],
            'caption' => $content['text'],
            'parse_mode' => 'HTML'
        ];
        
        if ($keyboard) {
            $params['reply_markup'] = $keyboard;
        }
        
        $result = sendMessage('sendPhoto', $params);
        
        file_put_contents(__DIR__ . '/../debug.log', 
            date('Y-m-d H:i:s') . " Результат отправки фото: " . ($result ? 'OK' : 'FAIL') . "\n", 
            FILE_APPEND
        );
    } else {
        $params = [
            'chat_id' => $message['from_id'],
            'text' => $content['text'],
            'parse_mode' => 'HTML'
        ];
        
        if ($keyboard) {
            $params['reply_markup'] = $keyboard;
        }
        
        $result = sendMessage('sendMessage', $params);
        
        file_put_contents(__DIR__ . '/../debug.log', 
            date('Y-m-d H:i:s') . " Результат отправки текста: " . ($result ? 'OK' : 'FAIL') . "\n", 
            FILE_APPEND
        );
    }
}

?>