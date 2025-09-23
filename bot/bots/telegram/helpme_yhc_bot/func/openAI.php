<?php

//Создаем тред
function createThreadApi($client, $user, $content){

    sendMessage('sendChatAction', [
        'chat_id' => $user['telegram_id'],
        'action' => 'typing'
    ]);

    $dataUser = "[[[Имя : ".$user['first_name']." Роль : ".$user['role']."]]]";
    $data = [
        'messages' => [
            [
                'role' => 'user',
                'content' => $dataUser.': '.$content,
                //'file_ids' => [],
            ],
        ],
    ];
    
    $thread = $client->createThread($data);
    //debugMessage(REPORT_ID, "Тред создан: ".$thread);
    $thread = json_decode($thread, true);
    //debugMessage(REPORT_ID, "Тред создан: ".$thread['id']);
    return $thread;
    
}

//Создаем сообщение и помещаем в тред
function createMessageApi($client, $thread, $content){

    sendMessage('sendChatAction', [
        'chat_id' => $user['telegram_id'],
        'action' => 'typing'
    ]);

    //$userFirstname = "[[[".$user['first_name']."]]]";
    $threadId = $thread['id'];
    $data = [
        'role' => 'user',
        'content' => $content,
    ];
    $messageApi = $client->createThreadMessage($threadId, $data);
    $messageApi = json_decode($messageApi, true);
    //debugMessage(REPORT_ID, "Сообщение создано: ".$message['content'][0]['text']['value']);
    return $messageApi;
}

//Запускаем прогон треда к ассистенту
function createRunApi($client, $thread) {

    sendMessage('sendChatAction', [
        'chat_id' => $user['telegram_id'],
        'action' => 'typing'
    ]);

    $threadId = $thread['id'];
    $data = [
        'assistant_id' => GPT_ASSIST
    ];

    $run = $client->createRun($threadId, $data);
    
    //debugMessage(REPORT_ID, "Прогон создан: ".$run);
    $run = json_decode($run, true);
    //debugMessage(REPORT_ID, "Run создан: ".$run['status']);
    return $run;
}

function listMessagesApi($client, $user, $thread, $status, $message){

    sendMessage('sendChatAction', [
        'chat_id' => $user['telegram_id'],
        'action' => 'typing'
    ]);

    $database = Config::getDatabase();
    $threadId = $thread['id'];
    $query = ['limit' => 10];

    $messages = $client->listThreadMessages($threadId, $query);
    $messages = json_decode($messages, true);

    if(isset($messages['data'])) {
        //Сохранение сообщения пользователя
        $messageApi = $messages['data'][0];
        $messageUser = $messages['data'][1];
        $messageAssistant = $messages['data'][0];

        //Очищаем от ссылок на аннотации в ответе ассистента
        $answerAssistant = preg_replace('/【\d+:\d+†source】/', '', $messageAssistant['content'][0]['text']['value']);
        // Удаляем данные пользователи которые отправляются в квадратных скобках для распознавания пользователя
        $cleaned_value = preg_replace('/\[\[\[.*?\]\]\]: /', '', $messageUser['content'][0]['text']['value']);

        $database->insert('gpt_user_requests',[
            'user_id' => $user['id'],
            'thread_id' => $messages['data'][0]['thread_id'],
            'run_id' => $messages['data'][0]['run_id'],
            'role' => 'user',
            'message_id' => $messageUser['id'],
            'value' => $cleaned_value,
            //'created_at' => $messageUser['created_at'],
            'status' => true,
        ]);

        $database->insert('gpt_user_requests',[
            'user_id' => $user['id'],
            'thread_id' => $messages['data'][0]['thread_id'],
            'run_id' => $messages['data'][0]['run_id'],
            'role' => 'assistant',
            'message_id' => $messageAssistant['id'],
            'value' => $answerAssistant,
            //'created_at' => $messageAssistant['created_at'],
            'status' => true,
        ]);

        $database->insert('gpt_dialog_tokens',[
            'run_id' => $status['id'],
            //'created_at' => $status['created_at'],
            'prompt_tokens' => $status['usage']['prompt_tokens'],
            'completion_tokens' => $status['usage']['completion_tokens'],
            'total_tokens' => $status['usage']['total_tokens']
        ]);

        if($database->errorInfo !== NULL) {
            sendMessage('sendMessage',[
                'chat_id' => DEV_ID,
                'text' => "Ошибка записи сообщения диалога: ".$database->errorInfo,
                'disable_notification' => true,
            ]);
            exit();
        }
    }

    //calculateTokens($status['usage']['total_tokens'], $user);//Работаем с токенами на балансе пользователя

    $run_id = $messages['data'][0]['run_id'];

    //Если нужна клавиатура, расскоментить в запросе.
    $keyboard = inlineKeyboard([
        [
            ['text' => '👍', 'callback_data' => '/reaction '.$run_id.' like'],
            ['text' => '👎', 'callback_data' => '/reaction '.$run_id.' dislike']
        ],
    ]);

    //$messageBot = $answerAssistant."\r\n\r\n"."Оцените ответ для улучшения взаимодействия. Был ли этот ответ полезен для вас?";
    $messageBot = $answerAssistant;
    //$messages = json_decode($messages, true);

    $expenseTokens = [
        'run_id' => $run_id,
        'prompt_tokens' => $status['usage']['prompt_tokens'],
        'completion_tokens' => $status['usage']['completion_tokens'],
    ];
    
    sendMessage('sendMessage',[
        'chat_id' => $user['telegram_id'],
        'text' => $messageBot,
        'parse_mode' => 'Markdown',
        'disable_notification' => true,
        'disable_web_page_preview' => true,
        //'reply_markup' => $keyboard
    ]);
    /* На случай если нужно будет подключить разные варианты взаимодейтсвия, например голосовые сообщения.
    if($message['type'] == "message") {

        $expenseTokens = [
            'run_id' => $run_id,
            'prompt_tokens' => $status['usage']['prompt_tokens'],
            'completion_tokens' => $status['usage']['completion_tokens'],
        ];

        //calculateUserGptBalance($expenseTokens, NULL, $user);

        sendMessage('sendMessage',[
            'chat_id' => $user['telegram_id'],
            'text' => $messageBot,
            'parse_mode' => 'Markdown',
            'disable_notification' => true,
            'disable_web_page_preview' => true,
            //'reply_markup' => $keyboard
        ]);
    }else{
        /**
         * $cleaned_value //Вопрос пользователя
         * Посчитать кол-во символов для TTS 0,02 $ / 1K characters
         
        $expenseTokens = [
            'run_id' => $run_id,
            'prompt_tokens' => $status['usage']['prompt_tokens'],//Расход токенов на входящий промт
            'completion_tokens' => $status['usage']['completion_tokens'],//Расход токенов на ответ
            'user_prompt' => $cleaned_value,//Для расчет TTS
        ];
        //textToSpeach($client, $user, $answerAssistant, $expenseTokens);
    }*/
    
}

function retrieveRunApi($client, $thread, $run){

    sendMessage('sendChatAction', [
        'chat_id' => $user['telegram_id'],
        'action' => 'typing'
    ]);

    $threadId = $thread['id'];
    $runId = $run['id'];
    $run = $client->retrieveRun($threadId, $runId);
    $run = json_decode($run, true);
    //debugMessage(REPORT_ID, "Статус прогона: ".$run);
    //debugMessage(REPORT_ID, "Статус Run: ".$run['status']);
    return $run;
}

function cancelRunApi($client, $thread, $run){
    $threadId = $thread['id'];
    $runId = $run['id'];

    $run = $client->cancelRun($threadId, $runId);
    //debugMessage(REPORT_ID, $run);
    //$run = json_decode($run, true);
    return $run;
}


function userDialog($client, $user, $message) {

    sendMessage('sendMessage',[
        'chat_id' => $user['telegram_id'],
        'text' => "⏳ Ожидайте моего ответа ...",
        'parse_mode' => "HTML"
    ]);

    sendMessage('sendChatAction', [
        'chat_id' => $user['telegram_id'],
        'action' => 'typing'
    ]);

    $database = Config::getDatabase();
    //Смотрим есть ли созданные треды у юзера
    $thread = $database->get('gpt_dialog','thread[JSON]',[
        'user_id' => $user['id']
    ]);
    if($database->errorInfo !== NULL) {
        sendMessage('sendMessage',[
            'chat_id' => DEV_ID,
            'text' => "Ошибка получения треда у юзера: ".$user['telegram_id'],
            'disable_notification' => true,
        ]);
        exit();
    }

    // Если в сообщении есть текст, то используем его как промт, иначе используем все сообщение.
    if(isset($message['text'])){
        $prompt = $message['text'];
    }else{
        $prompt = $message;
    }

    //Если тредов нет, создаем тред и записываем его
    if(!$thread) {
        $thread = createThreadApi($client, $user, $prompt);//Создаем тред
        if(isset($thread['id'])){
            $database->insert('gpt_dialog',[
                'user_id' => $user['id'],
                'thread[JSON]' => $thread,
            ]);
            if($database->errorInfo !== NULL) {
                sendMessage('sendMessage',[
                    'chat_id' => DEV_ID,
                    'text' => "Ошибка записи треда у юзера: ".$user['telegram_id'],
                    'disable_notification' => true,
                ]);
                exit();
            }
        }else{
            sendMessage('sendMessage',[
                'chat_id' => DEV_ID,
                'text' => "Ошибка создания треда у юзера: ".$user['telegram_id'],
                'disable_notification' => true,
            ]);
            exit();
        }
    }else{
        //Создаем сообщение и помещаем в тред
        $messageApi = createMessageApi($client, $thread, $prompt);//Создаем сообщение и помещаем в тред

        if(isset($messageApi['error'])) {
            sendMessage('sendMessage',[
                'chat_id' => DEV_ID,
                'text' => "Ошибка создания сообщения у юзера: ".$user['telegram_id'],
                'disable_notification' => true,
            ]);
            exit();
        }
    }

    //Запускаем прогон треда с сообщением к ассистенту
    $run = createRunApi($client, $thread);//Запускаем прогон треда к ассистенту

    if(isset($run['error'])) {
        sendMessage('sendMessage',[
            'chat_id' => DEV_ID,
            'text' => "Ошибка создания прогона к ассистенту у юзера: ".$user['telegram_id'],
            'disable_notification' => true,
        ]);
        exit();
    }

    // Проверяем статус прогона на completed
    $requestCount = 0;
    $maxRequests = 20;
    do {
        $status = retrieveRunApi($client, $thread, $run);
        $requestCount++;

        sendMessage('sendChatAction', [
            'chat_id' => $user['telegram_id'],
            'action' => 'typing'
        ]);

        if (isset($status['error'])) {
            sendMessage('sendMessage',[
                'chat_id' => DEV_ID,
                'text' => "Ошибка при проверке статуса прогона у юзера: " . $user['telegram_id'],
                'disable_notification' => true,
            ]);
            exit();
        }

        if ($requestCount >= $maxRequests) {
            cancelRunApi($client, $thread, $run);//Отменяем run если не удалось получить completed
            sendMessage('sendMessage',[
                'chat_id' => DEV_ID,
                'text' => "Сервер OpenAI недоступен: превышено количество запросов для юзера: " . $user['telegram_id'],
                'disable_notification' => true,
            ]);
            exit();
        }
        sleep(3);
    } while ($status['status'] !== "completed");

    //Передаем run со статусом complete Для записи затраченных токенов
    listMessagesApi($client, $user, $thread, $status, $message);//Список последних 10 сообщений для отправки последнего пользователю
}

?>