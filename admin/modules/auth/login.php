<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['telegram_id']) || !isset($input['auth_code'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    require_once __DIR__ . '/../../../bot/api/config.php';
    
    $database = Config::getDatabase();
    $telegram_id = (int)$input['telegram_id'];
    $auth_code = trim($input['auth_code']);
    
    // Проверяем код доступа
    // Ищем админа в БД
    $admin = $database->get('admins', '*', ['telegram_id' => $telegram_id]);

    // Если админ НЕ найден - проверяем возможность регистрации
    if (!$admin) {
        // Проверяем, есть ли уже owner
        $ownerExists = $database->has('admins', ['role' => 'owner']);
        
        if ($ownerExists) {
            throw new Exception('Registration closed. Contact owner to get access.');
        }
        
        // Проверяем код ТОЛЬКО для первого owner
        $correct_code = $_ENV['ADMIN_AUTH_CODE'] ?? 'admin123';
        if ($auth_code !== $correct_code) {
            throw new Exception('Invalid auth code');
        }
        
        // Создаем первого owner
        $database->insert('admins', [
            'telegram_id' => $telegram_id,
            'role' => 'owner',
            'bot_access' => json_encode([]),
            'is_active' => 1
        ]);
        
        $admin = [
            'telegram_id' => $telegram_id,
            'role' => 'owner'
        ];
    } else {
        // Админ существует - проверяем активен ли
        if (!$admin['is_active']) {
            throw new Exception('Account is disabled');
        }
        
        // Проверяем код доступа для входа
        $correct_code = $_ENV['ADMIN_AUTH_CODE'] ?? 'admin123';
        if ($auth_code !== $correct_code) {
            throw new Exception('Invalid auth code');
        }
    }
    
    // Генерируем токен
    require_once __DIR__ . '/JWTAuth.php';
    $jwt = new JWTAuth();
    $token = $jwt->generateToken($admin);
    
    echo json_encode([
        'token' => $token,
        'role' => $admin['role'],
        'status' => 'success'
    ]);
    
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => $e->getMessage()]);
}
?>