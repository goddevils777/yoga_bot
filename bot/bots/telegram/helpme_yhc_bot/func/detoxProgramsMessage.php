<?php

function detoxProgramsMessage($message){
    $text = "<b>Детокс программы 💚</b>\n\n" .
    "<b>Выберите подходящую для вас программу очищения:</b>\n\n" .
    "🌱 Лёгкий детокс - мягкое очищение организма\n" .
    "🌿 Программа голодания 3 дня - короткий интенсивный курс\n" .
    "🌳 Программа голодания 7 дней - глубокое очищение\n" .
    "🎁 Пробный день - познакомьтесь с нашим подходом бесплатно\n\n" .
    "<i>Начните свой путь к здоровью и легкости!</i>";

    $keyboard = inlineKeyboard([
        [
            ['text' => '🌱 Лёгкий детокс', 'callback_data' => '/light_detox']
        ],
        [
            ['text' => '🌿 Детокс программа голодание 3 дня', 'callback_data' => '/detox_3days']
        ],
        [
            ['text' => '🌳 Детокс программа голодание 7 дней', 'callback_data' => '/detox_7days']
        ],
        [
            ['text' => '🎁 Попробовать 1 день бесплатно', 'url' => 'https://pro.yoga-hub.club/courses/water-fasting-1day/']
        ],
        [
            ['text' => '💬 Задать вопрос', 'callback_data' => '/ask_question']
        ],
        [
            ['text' => '« Назад в главное меню', 'callback_data' => '/menu']
        ]
    ]);

    $photo_id = 'AgACAgIAAxkBAAIBWWfdWhKYt6f7ecIMpFEHEc4idqGGAALj7DEb5-LpSpFbfYoDwkVOAQADAgADeQADNgQ';

    // Отправляем фото с текстом
    $res = sendMessage('editMessageMedia', [
        'chat_id' => $message['from_id'],
        'message_id' => $message['message_id'],
        'media' => json_encode([
            'type' => 'photo',
            'media' => $photo_id,
            'caption' => $text,
            'parse_mode' => 'HTML'
        ]),
        'reply_markup' => $keyboard
    ]);

    if (!$res) {
        sendMessage('sendPhoto', [
            'chat_id' => $message['from_id'],
            'photo' => $photo_id,
            'caption' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard
        ]);
    }

    /*
    $res = sendMessage('editMessageText', [    
        'chat_id' => $message['from_id'],
        'message_id' => $message['message_id'],
        'text' => $text,
        'parse_mode' => 'HTML',
        'reply_markup' => $keyboard
    ]);

    if(!$res){
        sendMessage('sendMessage', [    
            'chat_id' => $message['from_id'],
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard
        ]);
    }*/
}
