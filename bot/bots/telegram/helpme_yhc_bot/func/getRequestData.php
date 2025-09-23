<?php
/**
 * Получение данных запроса
 * @param array $data Данные запроса
 * @return array $result Данные запроса
 */
function getRequestData($data) {

    $result = [
        'type' => null,
        'from_id' => null,
        'chat_id' => null,
        'first_name' => null,
        'last_name' => null,
        'username' => null,
        'text' => null,
        'message_id' => null,
        'date' => null,
        'entities' => null,
        'status' => null,
        // Медиа файлы
        'photo' => null,
        'photo_id' => null,
        'audio' => null,
        'audio_id' => null,
        'video' => null,
        'video_id' => null,
        'voice' => null,
        'voice_id' => null,
        'duration' => null,
        'caption' => null,
        'file_name' => null,
        'reply_to_message' => null,
        // Добавляем поля для документов
        'document' => null,
        'document_id' => null,
        'mime_type' => null,
        'message_thread_id' => null,
        //'chat' => null,
        // Добавляем поле для форума
        'forum_topic_created' => null,
        'forum_topic_edited' => null,
        'forum_topic_closed' => null,
        'forum_topic_reopened' => null,
        'is_topic_message' => null,  // Добавляем поле для определения сообщений форума
    ];

    // Обработка my_chat_member (блокировка/разблокировка бота)
    if (!empty($data['my_chat_member'])) {
        $result['type'] = "status_update";
        $result['chat_id'] = $data['my_chat_member']['chat']['id'];
        $result['from_id'] = $data['my_chat_member']['from']['id'];
        $result['status'] = $data['my_chat_member']['new_chat_member']['status'];
    }
    
    // Обработка обычных сообщений
    elseif (!empty($data['message'])) {
        // Если это сообщение о создании темы форума, устанавливаем специальный тип
        if (isset($data['message']['forum_topic_created'])) {
            $result['type'] = "forum_topic_created";
            $result['from_id'] = $data['message']['from']['id'];
            $result['chat_id'] = $data['message']['chat']['id'];
            $result['message_thread_id'] = $data['message']['message_thread_id'];
            $result['forum_topic_created'] = $data['message']['forum_topic_created'];
        }

        if(isset($data['message']['forum_topic_edited'])){
            $result['type'] = "forum_topic_edited";
            $result['from_id'] = $data['message']['from']['id'];
            $result['chat_id'] = $data['message']['chat']['id'];
            $result['message_thread_id'] = $data['message']['message_thread_id'];
            $result['forum_topic_edited'] = $data['message']['forum_topic_edited'];
            exit();
        }

        if(isset($data['message']['forum_topic_closed'])){
            $result['type'] = "forum_topic_closed";
            $result['from_id'] = $data['message']['from']['id'];
            $result['chat_id'] = $data['message']['chat']['id'];
            $result['message_thread_id'] = $data['message']['message_thread_id'];
            $result['forum_topic_closed'] = $data['message']['forum_topic_closed'];
            exit();
        }

        if(isset($data['message']['forum_topic_reopened'])){
            $result['type'] = "forum_topic_reopened";
            $result['from_id'] = $data['message']['from']['id'];
            $result['chat_id'] = $data['message']['chat']['id'];
            $result['message_thread_id'] = $data['message']['message_thread_id'];
            $result['forum_topic_reopened'] = $data['message']['forum_topic_reopened'];
            exit();
        }
        
        $result['type'] = "message";
        $result['from_id'] = $data['message']['from']['id'];
        $result['chat_id'] = $data['message']['chat']['id'];
        $result['first_name'] = $data['message']['from']['first_name'] ?? null;
        $result['last_name'] = $data['message']['from']['last_name'] ?? null;
        $result['username'] = $data['message']['from']['username'] ?? null;
        $result['message_id'] = $data['message']['message_id'];
        $result['date'] = $data['message']['date'];
        $result['text'] = $data['message']['text'] ?? null;
        $result['message_thread_id'] = $data['message']['message_thread_id'] ?? null;
        //$result['chat'] = $data['message']['chat']['id'] ?? null;//хня какая-то но где-то применяется
        $result['chat_type'] = $data['message']['chat']['type'] ?? null;
        // Добавляем обработку forum_topic_created
        $result['forum_topic_created'] = $data['message']['forum_topic_created'] ?? null;
        $result['is_topic_message'] = $data['message']['is_topic_message'] ?? null;

        //Если сообщение из супергруппы
        if($result['chat_type'] == 'supergroup'){
            $result['type'] = "supergroup";
        }
        
        // Добавляем обработку reply_to_message
        if (isset($data['message']['reply_to_message'])) {
            $result['reply_to_message'] = $data['message']['reply_to_message'];
        }
        
        $result['caption'] = $data['message']['caption'] ?? null;
        $result['entities'] = json_encode($data['message']['caption_entities'] ?? $data['message']['entities'] ?? []);
        
        // Видео
        if (isset($data['message']['video'])) {
            $result['type'] = "video";
            $result['video'] = $data['message']['video'];
            $result['video_id'] = $data['message']['video']['file_id'];
            $result['duration'] = $data['message']['video']['duration'];
            $result['file_name'] = $data['message']['video']['file_name'] ?? null;
            $result['caption'] = $data['message']['caption'] ?? null;
        }
        
        // Аудио
        if (isset($data['message']['audio'])) {
            $result['type'] = "audio";
            $result['audio'] = $data['message']['audio'];
            $result['audio_id'] = $data['message']['audio']['file_id'];
            $result['duration'] = $data['message']['audio']['duration'];
            $result['file_name'] = $data['message']['audio']['file_name'] ?? null;
            $result['caption'] = $data['message']['caption'] ?? null;
        }
        
        // Фото (берем последнее/самое большое фото из массива)
        if (isset($data['message']['photo'])) {
            $result['type'] = "photo";
            $photos = $data['message']['photo'];
            $result['photo'] = end($photos);
            $result['photo_id'] = $result['photo']['file_id'];
            $result['caption'] = $data['message']['caption'] ?? null;
        }
        
        // Добавляем обработку документов
        if (isset($data['message']['document'])) {
            $result['type'] = "document";
            $result['document'] = $data['message']['document'];
            $result['document_id'] = $data['message']['document']['file_id'];
            $result['file_name'] = $data['message']['document']['file_name'] ?? null;
            $result['mime_type'] = $data['message']['document']['mime_type'] ?? null;
            $result['caption'] = $data['message']['caption'] ?? null;
        }

        // Добавляем обработку голосовых сообщений
        if (isset($data['message']['voice'])) {
            $result['type'] = "voice";
            $result['voice'] = $data['message']['voice'];
            $result['voice_id'] = $data['message']['voice']['file_id'];
            $result['duration'] = $data['message']['voice']['duration'];
            $result['caption'] = $data['message']['caption'] ?? null;
        }
    }
    
    // Обработка callback_query
    elseif (!empty($data['callback_query'])) {
        $result['type'] = "callback_query";
        $result['from_id'] = $data['callback_query']['from']['id'];
        $result['chat_id'] = $data['callback_query']['message']['chat']['id'];
        $result['text'] = $data['callback_query']['data'];
        $result['message_id'] = $data['callback_query']['message']['message_id'];
        $result['date'] = $data['callback_query']['message']['date'];
        $result['callback_id'] = $data['callback_query']['id'];
    }
    
    elseif (!empty($data['inline_query'])) {
        $result['type'] = "inline_query"; 
        $result['from_id'] = $data['inline_query']['from']['id'];
        $result['query'] = $data['inline_query']['query'];
        $result['inline_id'] = $data['inline_query']['id'];
        $result['first_name'] = $data['inline_query']['from']['first_name'] ?? null;
        $result['last_name'] = $data['inline_query']['from']['last_name'] ?? null;
        $result['username'] = $data['inline_query']['from']['username'] ?? null;
    }

    return $result;
}

