<?php
require_once __DIR__ . '/bot/api/config.php';

$database = Config::getDatabase();

// Додаємо заголовки до турів
$updates = [
    'thailand_retreat' => "<b>🏝 Йога ретрит в Таиланде</b>\n\n",
    'bali_retreat' => "<b>🌺 Йога ретрит на Бали</b>\n\n",
    'nepal_tour' => "<b>🏔 Тур в Непал</b>\n\n",
    'japan_zen_tour' => "<b>🗾 Тур \"Дзен в современной Японии\"</b>\n\n",
    'kailas_tour' => "<b>🗻 Духовный тур на Кайлас</b>\n\n"
];

foreach ($updates as $key => $title) {
    // Отримуємо поточний текст
    $content = $database->get('bot_content', 'text', [
        'content_key' => $key,
        'bot_id' => 1
    ]);
    
    if ($content) {
        // Якщо заголовок вже є - пропускаємо
        if (strpos($content, '<b>') === 0) {
            echo "⏭ Пропущен (заголовок есть): $key\n";
            continue;
        }
        
        // Додаємо заголовок на початок
        $newText = $title . $content;
        
        $database->update('bot_content', [
            'text' => $newText
        ], [
            'content_key' => $key,
            'bot_id' => 1
        ]);
        
        echo "✅ Добавлен заголовок: $key\n";
    }
}

echo "\n✅ Все заголовки добавлены!\n";