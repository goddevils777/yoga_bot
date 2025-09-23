<?php

$message = getRequestData($data);
//Обработка текстовых команд и колбеков
$command = normalizeCommand($message['text']);

// Проверка на блокировку/разблокировку бота
if (!empty($message['type'] == 'status_update')) {
    $new_status = $message['status'];
    
    switch($new_status) {
        case 'kicked': // Бот заблокирован
            updateUserBlockStatus($message['from_id'], false, $message);
            break;
        case 'member': // Бот разблокирован
            updateUserBlockStatus($message['from_id'], true, $message);
            break;
    }

    exit();
}

//Если команда не /start, то проверяем наличие пользователя в базе
//Если пользователь не найден, то выходим из скрипта
if($command != '/start'){
    $user = userData($message);
    if(!$user){exit();}
}

// Определяем, в какую группу отправлять ответ
$target_group = ($message['chat_id'] == TELEGRAM_CHAT_GROUP_ID) ? TELEGRAM_CHAT_GROUP_ID : TELEGRAM_SUPPORT_GROUP;

if($message['chat_id'] == $target_group) {
    //Проверка сообщений в группе на предмет вопросов
    //Если вопрос, то отправляем в GPT
    if($user['role'] == 'admin' && $target_group == TELEGRAM_CHAT_GROUP_ID){
        exit();
    }
    handleGroupMessage($message, $client, $user);
    exit();
}

// Проверяем, является ли сообщение командой
if (!empty($message['text']) && $message['text'][0] !== '/' && $user['bot_action'] == 'ask_question') {
    // Обработка обычного текстового сообщения
    // Логика общения с GPT OpenAI
    userDialog($client, $user, $message);
    exit();
}

switch($command){
    case '/test':
        sendMessage('sendMessage', [
            'chat_id' => $message['from_id'],
            'text' => "Work!!!",
            'parse_mode' => 'HTML'
        ]);
        break;
    case '/start':
        startBot($message);
        sleep(2);
        primaryMenuMessage($message);
        break;
    case '/menu':
        primaryMenuMessage($message);
        break;
    case '/programs':
    case '/developing_programs'://Развивающие программы
        developingProgramsMessage($message);
        break;
    case '/onlineyoga':
    case '/online_yoga'://Йога с нами онлайн
        onlineYogaMessage($message);
        break;
    case '/retreats':
    case '/tours_and_retreats'://Туры и ретриты
        toursAndRetreatsMessage($message);
        break;
    case '/detox':
    case '/detox_programs'://Детокс программы
        detoxProgramsMessage($message);
        break;
    case '/aroma_diagnostics'://Аромадиагностика
    case '/successful_year'://Мой успешный год
    case '/longevity_foundation'://Фундамент долголетия
    case '/inner_support'://Внутренняя опора
    case '/vipassana_online'://Випассана онлайн
    case '/kids_yoga'://Играем в йогу
    case '/live_yoga'://Йога онлайн
    case '/our_learning_platform'://Наша обучающая платформа
    case '/light_detox'://Легкий Детокс
    case '/detox_3days'://Детокс программа голодание 3 дня
    case '/detox_7days'://Детокс программа голодание 7 дней
    case '/tours_calendar'://Календарь туров
    case '/thailand_retreat'://Йога-ретрит в Таиланде
    case '/bali_retreat'://Йога-ретрит на Бали
    case '/nepal_tour'://Тур в Непал
    case '/japan_zen_tour'://Тур в Японию
    case '/kailas_tour'://Духовный тур на Кайлас
    case '/free_classes'://Бесплатные занятия
    case '/dharma_code'://Дхарма код //Астролог Roman Teos
        contentMessage($message, $command);
        break;
    case '/help':
    case '/ask_question'://Задать вопрос
        askQuestionMessage($message);
        break;
}

/**
 * Отправка ID медиафайла указанному пользователю
 * @param array $message
 * @param string $media_id
 * @param string $media_type
 * @param string $from_user
 * @param string $username
 */
if (isset($message['photo']) || isset($message['video']) && $message['from_id'] == DEV_ID && $message['chat_id'] != TELEGRAM_SUPPORT_GROUP) {
    // Получаем ID медиафайла
    $media_id = isset($message['photo']) ? $message['photo_id'] : $message['video_id'];
    
    // Формируем текст сообщения
    $media_type = isset($message['photo']) ? 'Фото' : 'Видео';
    $from_user = $message['first_name'] . 
        (isset($message['last_name']) ? ' ' . $message['last_name'] : '');
    $username = isset($message['username']) ? '@' . $message['username'] : 'нет username';
    
    $text = "{$media_type} от пользователя:\n" .
            "Имя: {$from_user}\n" .
            "Username: {$username}\n" .
            "ID файла: <code>{$media_id}</code>";

    // Отправляем сообщение админу
    sendMessage('sendMessage', [
        'chat_id' => DEV_ID,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_notification' => true
    ]);
}




