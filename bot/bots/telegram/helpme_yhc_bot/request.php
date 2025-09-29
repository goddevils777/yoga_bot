<?php

$message = getRequestData($data);
//Обработка текстовых команд и колбеков
$command = normalizeCommand($message['text']);

// Проверка на блокировку/разблокировку бота
if (!empty($message['type'] == 'status_update')) {
    $new_status = $message['status'];

    switch ($new_status) {
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
if ($command != '/start') {
    $user = userData($message);
    if (!$user) {
        exit();
    }
}

// Определяем, в какую группу отправлять ответ
$target_group = ($message['chat_id'] == TELEGRAM_CHAT_GROUP_ID) ? TELEGRAM_CHAT_GROUP_ID : TELEGRAM_SUPPORT_GROUP;

if ($message['chat_id'] == $target_group) {
    //Проверка сообщений в группе на предмет вопросов
    //Если вопрос, то отправляем в GPT
    if ($user['role'] == 'admin' && $target_group == TELEGRAM_CHAT_GROUP_ID) {
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
switch ($command) {
    case '/test':
        sendMessage('sendMessage', [
            'chat_id' => $message['from_id'],
            'text' => "Work!!!",
            'parse_mode' => 'HTML'
        ]);
        break;

    case '/start':
        $user = userData($message);
        if (!$user) {
            $database = Config::getDatabase();
            $database->insert("users", [
                "telegram_id" => $message['from_id'],
                "first_name" => $message['first_name'],
                "last_name" => $message['last_name'],
                "username" => $message['username'],
                "role" => "guest",
            ]);
        }

        getContentFromDB('start_message', $message);
        sleep(1);
        getContentFromDB('primary_menu', $message);
        break;

    case '/menu':
        getContentFromDB('primary_menu', $message);
        break;

    case '/programs':
    case '/developing_programs':
        getContentFromDB('developing_programs', $message);
        break;

    case '/onlineyoga':
    case '/online_yoga':
        getContentFromDB('online_yoga', $message);
        break;

    case '/retreats':
    case '/tours_and_retreats':
        getContentFromDB('tours_and_retreats', $message);
        break;

    case '/detox':
    case '/detox_programs':
        getContentFromDB('detox_programs', $message);
        break;

    case '/aroma_diagnostics':
        getContentFromDB('aroma_diagnostics', $message);
        break;

    case '/successful_year':
        getContentFromDB('successful_year', $message);
        break;

    case '/longevity_foundation':
        getContentFromDB('longevity_foundation', $message);
        break;

    case '/inner_support':
        getContentFromDB('inner_support', $message);
        break;

    case '/vipassana_online':
        getContentFromDB('vipassana_online', $message);
        break;

    case '/kids_yoga':
        getContentFromDB('kids_yoga', $message);
        break;

    case '/live_yoga':
        getContentFromDB('live_yoga', $message);
        break;

    case '/our_learning_platform':
        getContentFromDB('our_learning_platform', $message);
        break;

    case '/light_detox':
        getContentFromDB('light_detox', $message);
        break;

    case '/detox_3days':
        getContentFromDB('detox_3days', $message);
        break;

    case '/detox_7days':
        getContentFromDB('detox_7days', $message);
        break;

    case '/tours_calendar':
        getContentFromDB('tours_calendar', $message);
        break;

    case '/thailand_retreat':
        getContentFromDB('thailand_retreat', $message);
        break;

    case '/bali_retreat':
        getContentFromDB('bali_retreat', $message);
        break;

    case '/nepal_tour':
        getContentFromDB('nepal_tour', $message);
        break;

    case '/japan_zen_tour':
        getContentFromDB('japan_zen_tour', $message);
        break;

    case '/kailas_tour':
        getContentFromDB('kailas_tour', $message);
        break;

    case '/free_classes':
        getContentFromDB('free_classes', $message);
        break;

    case '/dharma_code':
        getContentFromDB('dharma_code', $message);
        break;

    case '/help':
    case '/ask_question':
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
