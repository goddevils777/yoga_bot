<?php
header('Content-Type: image/jpeg');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../../bot/api/config.php';

$file_id = $_GET['file_id'] ?? null;

if (!$file_id) {
    http_response_code(400);
    exit;
}

$database = Config::getDatabase();
$bot = $database->get('bots', 'token', ['id' => 1]);

if (!$bot) {
    http_response_code(404);
    exit;
}

// Получаем file_path из Telegram
$url = "https://api.telegram.org/bot{$bot}/getFile?file_id={$file_id}";
$response = file_get_contents($url);
$data = json_decode($response, true);

if (!$data['ok']) {
    http_response_code(404);
    exit;
}

$file_path = $data['result']['file_path'];

// Загружаем файл
$file_url = "https://api.telegram.org/file/bot{$bot}/{$file_path}";
$image = file_get_contents($file_url);

echo $image;