<?php

function developingProgramsMessage($message){
    $text = "<b>Развивающие программы 👩‍🏫</b>\n\n" .
    "<b>Выберите интересующую вас программу:</b>\n\n" .
    "🌱 Аромадиагностика\n" .
    "✨ Метод планирования \"Мой успешный год\"\n" .
    "🧘‍♀️ Курс по йоге \"Фундамент долголетия\"\n" .
    "🙏 Курс по медитации \"Внутренняя опора\"\n" .
    "😌 Випассана онлайн\n" .
    "👶 Онлайн курс для детей \"Играем в йогу\"\n" .
    "📖 Курс по нумерологии \"Дхарма код\"\n" .
    "🥑 Программа очищения \"Легкий Детокс\"";

    $keyboard = inlineKeyboard([
        [['text' => '🌱 Аромадиагностика', 'callback_data' => '/aroma_diagnostics']],
        [['text' => '✨ Метод планирования «Мой успешный год»', 'callback_data' => '/successful_year']],
        [['text' => '🧘‍♀️ Курс по йоге «Фундамент долголетия»', 'callback_data' => '/longevity_foundation']],
        [['text' => '🙏 Курс по медитации «Внутренняя опора»', 'callback_data' => '/inner_support']],
        [['text' => '😌 Випассана онлайн', 'callback_data' => '/vipassana_online']],
        [['text' => '👶 Онлайн курс для детей «Играем в йогу»', 'callback_data' => '/kids_yoga']],
        [['text' => '📖 Астролог Roman Teos', 'callback_data' => '/dharma_code']],
        [['text' => '🥑 Программа очищения «Легкий Детокс»', 'callback_data' => '/light_detox']],
        [['text' => '💬 Задать вопрос', 'callback_data' => '/ask_question']],
        [['text' => '« Назад в главное меню', 'callback_data' => '/menu']]
    ]);

    $photo_id = 'AgACAgIAAxkBAAIBRGfdRhUO4g5ZJeYxj21YEqTJiKshAAIE7DEb5-LpSuhIe7Wl85bWAQADAgADeAADNgQ';

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
    }
    */
}
