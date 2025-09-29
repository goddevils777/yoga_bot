<?php
require_once __DIR__ . '/bot/api/config.php';

$database = Config::getDatabase();

// Отримуємо всі записи без заголовка
$content = $database->select('bot_content', '*', [
    'bot_id' => 1,
    'OR' => [
        'title' => '',
        'title' => null
    ]
]);

foreach ($content as $item) {
    // Витягуємо заголовок з тексту (перший <b>...</b>)
    if (preg_match('/<b>(.*?)<\/b>/', $item['text'], $match)) {
        $title = strip_tags($match[1]);
    } else {
        // Якщо немає <b> - беремо перші 50 символів тексту
        $title = mb_substr(strip_tags($item['text']), 0, 50);
    }
    
    // Оновлюємо заголовок
    $database->update('bot_content', [
        'title' => $title
    ], [
        'id' => $item['id']
    ]);
    
    echo "✅ {$item['content_key']}: $title\n";
}

echo "\n✅ Все заголовки добавлены!\n";