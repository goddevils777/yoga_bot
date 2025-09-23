<?php

function primaryMenuMessage($message){
    $text = "<b>Добро пожаловать в Yoga Hub Club! 🕉</b>\n\n" .
    "Мы предлагаем комплексный подход к здоровью и духовному развитию:\n\n" .
    "🔅 Развивающие программы для личностного роста\n" .
    "🔅 Профессиональные онлайн-занятия йогой\n" .
    "🔅 Уникальные туры и ретриты\n" .
    "🔅 Специальные детокс-программы\n\n" .
    "Задайте нам вопрос, и мы поможем вам выбрать подходящее направление для вашего развития!";

    $keyboard = inlineKeyboard  ([
        [
            ['text' => '👩‍🏫 Развивающие программы', 'callback_data' => '/developing_programs'],
        ],  
        [
            ['text' => '🧘‍♀️ Йога с нами онлайн', 'callback_data' => '/online_yoga'],
        ],
        [
            ['text' => '🛫 Туры и ретриты', 'callback_data' => '/tours_and_retreats'],
        ],
        [
            ['text' => '💚 Детокс программы', 'callback_data' => '/detox_programs'],
        ],
        [
            ['text' => '💬 Задать вопрос', 'callback_data' => '/ask_question'],
        ]
    ]);

    $photo_id = 'AgACAgIAAxkBAAIBcWfdZmKGDamvp2YxkfuysAABpnFFkgACP-0xG-fi6UpYdJPcihCc0gEAAwIAA3kAAzYE';

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
?>