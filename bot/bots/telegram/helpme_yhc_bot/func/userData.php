<?php

/**
 * Получение данных пользователя
 * role: administrator, guest
 */

 function userData($message) {
    $database = Config::getDatabase();
    /* Если сообщение получено из группы и имеет тип "supergroup"
    //Пока ничего с этим не делать. Возможно потребуется при проверке прав внутри канала/группы
    //Работает как тех.поддержка, выбирает номер топика клиента/юзера при обращении в поддержку
    //Удалить комментарий если логика не требуется
    if($message['type'] == "supergroup"){
        $where = [
            "client_chat" => $message['message_thread_id']
        ];
    }else{
        $where = [
            "telegram_id" => $message['from_id']
        ];
    }*/

    $where = [
        "telegram_id" => $message['from_id']
    ];

    $user = $database->get("users",[
        "id",
        "telegram_id",//Telegram ID
        "first_name",//Имя
        "last_name",//Фамилия
        "username",//Юзернейм
        "role",//Роль
        "date_register",//Дата регистрации
        "active_bot",//Активный бот
        "bot_action",
    ],$where);

    // Если пользователь существует, проверяем изменения
    if ($user) {
        $updates = [];
        
        if (isset($message['first_name']) && $user['first_name'] != $message['first_name']) {
            $updates['first_name'] = $message['first_name'];
        }
        if (isset($message['last_name']) && $user['last_name'] != $message['last_name']) {
            $updates['last_name'] = $message['last_name'];
        }
        if (isset($message['username']) && $user['username'] != $message['username']) {
            $updates['username'] = $message['username'];
        }

        // Если есть изменения, обновляем данные в базе
        if (!empty($updates)) {
            //debugMessage(A_DEV, "Обновление данных пользователя ".$from_id.": ".json_encode($updates));
            $database->update("users", $updates, [
                "telegram_id" => $message['from_id'],
            ]);

            if ($database->errorInfo) {
                logError($database->errorInfo);
                sendMessage('sendMessage', [
                    'chat_id' => $message['from_id'],
                    'text' => "Ошибка при обновлении данных пользователя: ".$message['from_id'].": ".json_encode($updates),
                    'parse_mode' => 'HTML'
                ]);
            }
        }

        return $user;
    }
}

?>