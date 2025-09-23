<?php

function inlineKeyboard($buttons) {
    $keyboard = ['inline_keyboard' => []];
    
    foreach ($buttons as $row) {
        $keyboardRow = [];
        foreach ($row as $button) {
            $btn = ['text' => $button['text']];
            
            // Добавляем тип кнопки (callback_data, url, и т.д.)
            foreach ($button as $type => $value) {
                if ($type !== 'text') {
                    $btn[$type] = $value;
                }
            }
            
            $keyboardRow[] = $btn;
        }
        $keyboard['inline_keyboard'][] = $keyboardRow;
    }
    
    return json_encode($keyboard);
}
?>