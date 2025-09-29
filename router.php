<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Главная страница админки
if ($uri === '/admin' || $uri === '/admin/') {
    readfile(__DIR__ . '/admin/public/index.html');
    exit;
}

// Статические файлы админки - отдаем напрямую
if (preg_match('#^/admin/assets/(.+)$#', $uri, $matches)) {
    $file = __DIR__ . '/admin/public/assets/' . $matches[1];
    
    if (file_exists($file) && is_file($file)) {
        // Определяем MIME type
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'woff2' => 'font/woff2',
        ];
        
        header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
        readfile($file);
        exit;
    }
}

// API логин
if ($uri === '/admin/modules/auth/login.php') {
    require __DIR__ . '/admin/modules/auth/login.php';
    exit;
}

// API endpoints
if (preg_match('#^/admin/modules/api/endpoints\.php(/.*)?$#', $uri, $matches)) {
    $_SERVER['PATH_INFO'] = $matches[1] ?? '';
    require __DIR__ . '/admin/modules/api/endpoints.php';
    exit;
}

// Webhook бота
if ($uri === '/bot/bots/telegram/helpme_yhc_bot/app.php') {
    require __DIR__ . '/bot/bots/telegram/helpme_yhc_bot/app.php';
    exit;
}

// 404
http_response_code(404);
echo '404 Not Found';
?>