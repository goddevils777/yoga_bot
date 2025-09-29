<?php
// Получаем URI запроса
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Удаляем /admin из пути если есть
$uri = preg_replace('#^/admin#', '', $uri);

// Если это статический файл (CSS, JS, изображения) - отдаем его напрямую
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf)$/', $uri)) {
    $file = __DIR__ . $uri;
    if (file_exists($file) && !is_dir($file)) {
        return false; // PHP встроенный сервер обработает
    }
}

// Если это корень или index.html
if ($uri === '/' || $uri === '/index.html' || $uri === '') {
    require __DIR__ . '/index.html';
    exit;
}

// Если это API endpoint
if (preg_match('#^/modules/api/endpoints\.php(/.*)?$#', $uri, $matches)) {
    $_SERVER['PATH_INFO'] = $matches[1] ?? '';
    require __DIR__ . '/../modules/api/endpoints.php';
    exit;
}

// Если это auth endpoint
if (preg_match('#^/modules/auth/login\.php$#', $uri)) {
    require __DIR__ . '/../modules/auth/login.php';
    exit;
}

// 404 для остальных
http_response_code(404);
echo json_encode(['error' => 'Route not found']);