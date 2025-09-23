<?php

/**
 * Старт бота
 * 
 * @param array $message
 * @return void
 */

function startBot($message){
    $user = userData($message);

    $text = "<b>Намастэ 🙏🏽</b>\nВы в клубе ведических знаний 🕉\r\n\r\n" .
    "👉 Даем практики для духовного роста\n" .
    "👉 Мантры для разных сфер жизни\n" .
    "👉 Ведические ритуалы\n" .
    "👉 Рекомендации джйотиш астролога\n" .
    "👉 Советы профессионального нутрициолога\n";

    if(isset($user)){
        //Юзер есть, приветствуем.
        sendMessage('sendMessage', [
            'chat_id' => $message['chat_id'],
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_notification' => true
        ]);
        setMyCommands($message);
    }else{
        //Юзер не найден, регистрируем. приветствуем и продолжаем.
        $database = Config::getDatabase();
        $database->insert("users", [
            "telegram_id" => $message['from_id'],
            "first_name" => $message['first_name'],
            "last_name" => $message['last_name'],
            "username" => $message['username'],
            "role" => "guest",
        ]);

        $id = $database->id();

        if($id){
            sendMessage('sendMessage', [
                'chat_id' => $message['chat_id'],
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_notification' => true
            ]);
            setMyCommands($message);
        }else{
            //Юзер не зарегистрирован, выводим ошибку и продолжаем.
            debugMessage("Ошибка при регистрации пользователя: ".$message['from_id'].": ".json_encode($message));
        }
    }

}

?>