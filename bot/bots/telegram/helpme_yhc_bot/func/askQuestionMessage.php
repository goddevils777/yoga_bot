<?php

function askQuestionMessage($message){
    $database = Config::getDatabase();

    sendMessage('sendChatAction',[
        'chat_id' => $message['from_id'],
        'action' => 'typing'
    ]);

    $database->update('users',['bot_action' => 'ask_question'],[
        'telegram_id' => $message['from_id']
    ]);

    $text = "👩‍💻 Я здесь чтобы помочь! Какой у Вас вопрос?";

    sendMessage('sendMessage', [
        'chat_id' => $message['from_id'],
        'text' => $text,
        'parse_mode' => 'HTML',
    ]);

    $user_id = $database->get("users","id",[
        "telegram_id" => $message['from_id']
    ]);

    $database->delete('gpt_dialog',['user_id' => $user_id],[
        'user_id' => $user_id
    ]);
    /*
    $res = sendMessage('editMessageText', [
        'chat_id' => $message['from_id'],
        'message_id' => $message['message_id'],
        'text' => $text,
        'parse_mode' => 'HTML',
    ]);

    if(!$res){
        sendMessage('sendMessage', [
            'chat_id' => $message['from_id'],
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);
    }*/
}
?>