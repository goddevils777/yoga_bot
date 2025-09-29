<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../../bot/api/config.php';
require_once __DIR__ . '/../auth/JWTAuth.php';

// Проверка авторизации
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$jwt = new JWTAuth();
$token = $matches[1];

try {
    $jwt->verifyToken($token);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token', 'details' => $e->getMessage()]);
    exit;
}

// Проверка наличия файла
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
$botId = filter_input(INPUT_POST, 'bot_id', FILTER_VALIDATE_INT);

if (!$botId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid bot_id']);
    exit;
}

// Валидация размера (макс 10 МБ)
$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Max 10 MB']);
    exit;
}

// Валидация типа файла
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Use JPG, PNG, GIF or WebP']);
    exit;
}

try {
    $database = Config::getDatabase();

    // Получаем токен бота
    $bot = $database->get('bots', '*', ['id' => $botId]);

    if (!$bot) {
        http_response_code(404);
        echo json_encode(['error' => 'Bot not found']);
        exit;
    }

    // Получаем telegram_id любого пользователя бота для загрузки файла
    // Получаем telegram_id любого пользователя для загрузки файла
    $userTelegramId = $database->get('users', 'telegram_id', [
        'ORDER' => ['id' => 'ASC'],
        'LIMIT' => 1
    ]);

    if (!$userTelegramId) {
        http_response_code(500);
        echo json_encode(['error' => 'No users found. Bot needs at least one user to upload media.']);
        exit;
    }

    // Загружаем фото в Telegram
    $ch = curl_init();
    $cfile = new CURLFile($file['tmp_name'], $mimeType);

    $postData = [
        'photo' => $cfile,
        'chat_id' => $userTelegramId
    ];

    $url = "https://api.telegram.org/bot{$bot['token']}/sendPhoto";

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload to Telegram', 'details' => $result]);
        exit;
    }

    $response = json_decode($result, true);

    if (!$response['ok'] || !isset($response['result']['photo'])) {
        http_response_code(500);
        echo json_encode(['error' => 'Telegram API error', 'details' => $response]);
        exit;
    }

    // Получаем file_id самого большого изображения
    $photos = $response['result']['photo'];
    $largestPhoto = end($photos);
    $fileId = $largestPhoto['file_id'];

    echo json_encode([
        'success' => true,
        'file_id' => $fileId
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
