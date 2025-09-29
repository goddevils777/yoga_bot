<?php
// Получаем URI запроса
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Если запрос к modules/api/endpoints.php
if (preg_match('#^/modules/api/endpoints\.php(/.*)?$#', $uri, $matches)) {
    $_SERVER['PATH_INFO'] = $matches[1] ?? '';
    require __DIR__ . '/../modules/api/endpoints.php';
    exit;
}

// Удаляем admin из пути если есть
$uri = preg_replace('#^/admin#', '', $uri);

// Если это корень или index.html
if ($uri === '/' || $uri === '/index.html' || $uri === '') {
    require __DIR__ . '/index.html';
    exit;
}

// Определяем физический путь к файлу
$file = __DIR__ . $uri;

// Если файл существует и это не PHP
if (file_exists($file) && !is_dir($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    return false; // PHP встроенный сервер обработает
}

// 404 для остальных
http_response_code(404);
echo json_encode(['error' => 'Route not found']);