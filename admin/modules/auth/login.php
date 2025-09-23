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

// Логируем запрос
file_put_contents('login.log', date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

file_put_contents('login.log', date('Y-m-d H:i:s') . " - Input: " . print_r($input, true) . "\n", FILE_APPEND);

if (!isset($input['telegram_id']) || !isset($input['auth_code'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Простая проверка без базы данных пока
    if ($input['auth_code'] !== 'admin123') {
        throw new Exception('Invalid auth code');
    }
    
    // Генерируем простой токен
    $token = base64_encode(json_encode([
        'user_id' => $input['telegram_id'],
        'role' => 'owner',
        'exp' => time() + (24 * 60 * 60)
    ]));
    
    echo json_encode([
        'token' => $token,
        'role' => 'owner',
        'status' => 'success'
    ]);
    
    file_put_contents('login.log', date('Y-m-d H:i:s') . " - Success login\n", FILE_APPEND);
    
} catch (Exception $e) {
    file_put_contents('login.log', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(401);
    echo json_encode(['error' => $e->getMessage()]);
}
?>