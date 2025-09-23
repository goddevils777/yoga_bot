<?php

function onlineYogaMessage($message){
    $text = "<b>Йога с нами онлайн 🧘‍♀️</b>\n\n" .
    "<b>Выберите подходящий для вас формат занятий:</b>\n\n" .
    "🎁 Бесплатные занятия - познакомьтесь с нашим подходом\n" .
    "🎯 Йога онлайн - практикуйте с нами в реальном времени\n" .
    "📹 Доступ к занятиям в записи - занимайтесь в удобное для вас время\n\n" .
    "<i>Присоединяйтесь к нашим практикам и трансформируйте свою жизнь!</i>";

    $keyboard = inlineKeyboard([
        [
            ['text' => '🎁 Бесплатные занятия', 'callback_data' => '/free_classes']
        ],
        [
            ['text' => '🎯 Йога онлайн', 'callback_data' => '/live_yoga']
        ],
        [
            ['text' => '📹 Наша обучающая платформа', 'callback_data' => '/our_learning_platform']
        ],
        [
            ['text' => '💬 Задать вопрос', 'callback_data' => '/ask_question']
        ],
        [
            ['text' => '« Назад в главное меню', 'callback_data' => '/menu']
        ]
    ]);

    $photo_id = 'AgACAgIAAxkBAAIBdmfdZvGsRjVdgS59NgaeIsgrKrtFAAJC7TEb5-LpSgABBMlgOdcGBwEAAwIAA3kAAzYE';

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
