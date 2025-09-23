<?php

function toursAndRetreatsMessage($message){
    $text = "<b>Туры и ретриты 🛫</b>\n\n" .
    "<b>Откройте для себя новые горизонты с нашими уникальными программами:</b>\n\n" .
    "📅 Календарь всех предстоящих туров\n" .
    "🛫 Йога-ретрит в Таиланде - погружение в практику\n" .
    "🛫 Йога-ретрит на Бали - остров духовности\n" .
    "🛫 Тур в Непал - путешествие к Гималаям\n" .
    "🛫 Тур \"Дзен в современной Японии\"\n" .
    "🛫 Духовный тур на Кайлас - священная гора\n\n" .
    "<i>Выберите направление вашего путешествия:</i>";

    $keyboard = inlineKeyboard([
        [['text' => '📅 Календарь туров', 'callback_data' => '/tours_calendar']],
        [['text' => '🏝 Йога ретрит в Таиланде', 'callback_data' => '/thailand_retreat']],
        [['text' => '🌺 Йога ретрит на Бали', 'callback_data' => '/bali_retreat']],
        [['text' => '🏔 Тур в Непал', 'callback_data' => '/nepal_tour']],
        [['text' => '🗾 Тур “Дзен в современной Японии”', 'callback_data' => '/japan_zen_tour']],
        [['text' => '🗻 Духовный тур на Кайлас', 'callback_data' => '/kailas_tour']],
        [['text' => '💬 Задать вопрос', 'callback_data' => '/ask_question']],
        [['text' => '« Назад в главное меню', 'callback_data' => '/menu']]
    ]);

    $photo_id = 'AgACAgIAAxkBAAIBYWfdWyj95Y8BW5BhPPEG8LtbPVxsAALo7DEb5-LpStXj5F8vVUVOAQADAgADeQADNgQ';

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
