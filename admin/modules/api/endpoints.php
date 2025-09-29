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

    case 'users':
        if ($method === 'GET') {
            $bot_id = filter_input(INPUT_GET, 'bot_id', FILTER_VALIDATE_INT);
            $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS);
            $date_from = filter_input(INPUT_GET, 'date_from', FILTER_SANITIZE_SPECIAL_CHARS);
            $date_to = filter_input(INPUT_GET, 'date_to', FILTER_SANITIZE_SPECIAL_CHARS);
            $status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
            $sort = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'date_register';
            $order = filter_input(INPUT_GET, 'order', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'DESC';

            if (!$bot_id) {
                http_response_code(400);
                echo json_encode(['error' => 'bot_id required']);
                exit;
            }

            require_once __DIR__ . '/../../../bot/api/config.php';
            $database = Config::getDatabase();

            $where = [];

            if ($search) {
                $where['OR'] = [
                    'username[~]' => $search,
                    'telegram_id[~]' => $search,
                    'first_name[~]' => $search,
                    'last_name[~]' => $search
                ];
            }

            if ($date_from) {
                $where['date_register[>=]'] = $date_from . ' 00:00:00';
            }
            if ($date_to) {
                $where['date_register[<=]'] = $date_to . ' 23:59:59';
            }

            if ($status === 'active') {
                $where['active_bot'] = 1;
            } elseif ($status === 'blocked') {
                $where['active_bot'] = 0;
            }

            $where['ORDER'] = [$sort => $order];

            $users = $database->select('users', [
                'id',
                'telegram_id',
                'username',
                'first_name',
                'last_name',
                'role',
                'active_bot',
                'date_register',
                'bot_action'
            ], $where);

            $stats = [
                'total' => $database->count('users'),
                'active' => $database->count('users', ['active_bot' => 1]),
                'blocked' => $database->count('users', ['active_bot' => 0])
            ];

            echo json_encode([
                'users' => $users,
                'stats' => $stats
            ]);
        }
        break;

    case 'block':
        if ($path[0] === 'block' && $method === 'POST') {
            require_once __DIR__ . '/../../../bot/api/config.php';
            $database = Config::getDatabase();

            $bot_id = $input['bot_id'] ?? null;
            $telegram_id = $input['telegram_id'] ?? null;
            $reason = $input['reason'] ?? '';

            if (!$bot_id || !$telegram_id) {
                http_response_code(400);
                echo json_encode(['error' => 'bot_id and telegram_id required']);
                exit;
            }

            $database->update('users', ['active_bot' => 0], ['telegram_id' => $telegram_id]);
            $database->insert('user_actions_log', [
                'bot_id' => $bot_id,
                'telegram_id' => $telegram_id,
                'action' => 'block',
                'reason' => $reason,
                'admin_id' => $user_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            echo json_encode(['success' => true, 'message' => 'User blocked']);
        }
        break;

    case 'unblock':
        if ($method === 'POST') {
            require_once __DIR__ . '/../../../bot/api/config.php';
            $database = Config::getDatabase();

            $bot_id = $input['bot_id'] ?? null;
            $telegram_id = $input['telegram_id'] ?? null;

            if (!$bot_id || !$telegram_id) {
                http_response_code(400);
                echo json_encode(['error' => 'bot_id and telegram_id required']);
                exit;
            }

            $database->update('users', ['active_bot' => 1], ['telegram_id' => $telegram_id]);
            $database->insert('user_actions_log', [
                'bot_id' => $bot_id,
                'telegram_id' => $telegram_id,
                'action' => 'unblock',
                'reason' => '',
                'admin_id' => $user_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            echo json_encode(['success' => true, 'message' => 'User unblocked']);
        }
        break;

    case 'delete':
        if ($method === 'POST') {
            require_once __DIR__ . '/../../../bot/api/config.php';
            $database = Config::getDatabase();

            $telegram_id = $input['telegram_id'] ?? null;

            if (!$telegram_id) {
                http_response_code(400);
                echo json_encode(['error' => 'telegram_id required']);
                exit;
            }

            $database->delete('users', ['telegram_id' => $telegram_id]);
            echo json_encode(['success' => true, 'message' => 'User deleted']);
        }
        break;

    case 'broadcast':
        handleBroadcastAPI($method, $path, $input, $botManager, $user_id);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
}

function handleBotsAPI($method, $path, $input, $botManager, $user_id)
{
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

function handleContentAPI($method, $path, $input, $botManager, $user_id)
{
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

            if (array_key_exists('media_id', $input) && $input['media_id'] === null) {
                $input['media_id'] = null;
                $input['media_type'] = null;
            }

            $id = $botManager->saveContent($bot_id, $content_key, $input);
            echo json_encode(['id' => $id, 'status' => 'saved']);
            break;
    }
}

function handleBroadcastAPI($method, $path, $input, $botManager, $user_id)
{
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

    echo json_encode(['status' => 'broadcast_queued', 'recipients' => 0]);
}
