<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/BotManager.php';
require_once __DIR__ . '/../auth/JWTAuth.php';

$botManager = new BotManager();
$auth = new JWTAuth();

$method = $_SERVER['REQUEST_METHOD'];
$path = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
$input = json_decode(file_get_contents('php://input'), true);

// Проверка авторизации
$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $token);

try {
    $decoded = $auth->verifyToken($token);
    $user_id = $decoded['user_id'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Маршрутизация
switch ($path[0]) {
    case 'bots':
        handleBotsAPI($method, $path, $input, $botManager, $user_id);
        break;
    case 'content':
        handleContentAPI($method, $path, $input, $botManager, $user_id);
        break;
    case 'broadcast':
        handleBroadcastAPI($method, $path, $input, $botManager, $user_id);
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
}

function handleBotsAPI($method, $path, $input, $botManager, $user_id) {
    switch ($method) {
        case 'GET':
            if (!$botManager->checkAccess($user_id)) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                return;
            }
            echo json_encode($botManager->getAllBots());
            break;
            
        case 'POST':
            if (!$botManager->checkAccess($user_id)) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                return;
            }
            $bot_id = $botManager->addBot($input);
            echo json_encode(['id' => $bot_id, 'status' => 'created']);
            break;
    }
}

function handleContentAPI($method, $path, $input, $botManager, $user_id) {
    $bot_id = $path[1] ?? null;
    $content_key = $path[2] ?? null;
    
    if (!$bot_id || !$botManager->checkAccess($user_id, $bot_id)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    switch ($method) {
        case 'GET':
            $content = $botManager->getBotContent($bot_id, $content_key);
            echo json_encode($content);
            break;
            
        case 'POST':
        case 'PUT':
            if (!$content_key) {
                http_response_code(400);
                echo json_encode(['error' => 'Content key required']);
                return;
            }
            $id = $botManager->saveContent($bot_id, $content_key, $input);
            echo json_encode(['id' => $id, 'status' => 'saved']);
            break;
    }
}

function handleBroadcastAPI($method, $path, $input, $botManager, $user_id) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $bot_id = $input['bot_id'] ?? null;
    if (!$bot_id || !$botManager->checkAccess($user_id, $bot_id)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    // TODO: Implement broadcast logic
    echo json_encode(['status' => 'broadcast_queued', 'recipients' => 0]);
}
?>