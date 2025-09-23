<?php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('error_log', 'error.log');
//file_put_contents(__DIR__ . '/debug.log', print_r($_REQUEST, true), FILE_APPEND);
//https://api.telegram.org/bot7706921145:AAEz3J6R001wWuFTEYQ6k4u3_9G1seqyN4k/setWebhook?url=https://bot.yoga-hub.club/bots/telegram/helpme_yhc_bot/app.php
//https://api.telegram.org/bot7706921145:AAEz3J6R001wWuFTEYQ6k4u3_9G1seqyN4k/deleteWebhook
//getWebhookInfo
require_once '/home/ej359436/yoga-hub.club/bot/api/config.php';

$telegram_api_bot = Config::getTelegramApiKey();
$report_id = Config::getReportId();
$APPATH = Config::getApPath();

define('APPATH', $APPATH);
define('OPENAI_API_KEY', $openai_api_key);
define('GPT_ASSIST', 'asst_uVV39SgIskXghzKss6QTTZEv');
define('TOKEN', $telegram_api_bot);
define('DEV_ID', $report_id);
define('BOTLOGIN', 'helpme_yhc_bot');
define('DOMAIN', 'https://bot.yoga-hub.club');
define('TELEGRAM_SUPPORT_GROUP', '-1002298789117');//ID группы
define('TELEGRAM_CHAT_GROUP_ID', '-1001993098442');//Чат группы

$data = file_get_contents('php://input');
$data = json_decode($data, true);

function sendMessage($method, $response){
    
    $ch = curl_init('https://api.telegram.org/bot'.TOKEN.'/'.$method);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $res = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curl_error) {
        file_put_contents(APPATH.'/bots/telegram/'.BOTLOGIN.'/error.log', date('Y-m-d H:i:s') . " CURL Error: " . $curl_error . "\n", FILE_APPEND);
        return false;
    }

    $response = json_decode($res, true);

    if ($http_code != 200 || !isset($response['ok']) || !$response['ok']) {
        $error_msg = isset($response['description']) ? $response['description'] : 'Unknown error';
        file_put_contents(APPATH.'/bots/telegram/'.BOTLOGIN.'/error.log', date('Y-m-d H:i:s') . " Response Api Error: " . $error_msg . "\n", FILE_APPEND);
        return false;
    }
 
    return $response;
}

function logError($error) {
    file_put_contents(APPATH.'/bots/telegram/'.BOTLOGIN.'/error.log', date('Y-m-d H:i:s') . " Error: " . $error . "\n", FILE_APPEND);
}

function logMessage($value) {
    file_put_contents(APPATH.'/bots/telegram/'.BOTLOGIN.'/message.log', date('Y-m-d H:i:s') . " Message: " . $value . "\n", FILE_APPEND);
}

if(isset($data) && !empty($data)) {
    logMessage(print_r($data, true));
    require_once 'func/import.php';
    require_once 'request.php';
}