<?php
require_once __DIR__ . '/bot/api/config.php';

$database = Config::getDatabase();

echo "=== ПЕРЕВІРКА JSON КНОПОК ===\n\n";

$content = $database->select('bot_content', ['id', 'content_key', 'buttons'], [
    'bot_id' => 1,
    'buttons[!]' => null,
    'buttons[!]' => ''
]);

$errors = [];
$success = [];

foreach ($content as $item) {
    $buttons = $item['buttons'];
    
    // Очищаємо від зайвих лапок
    $buttons = trim($buttons, '"');
    $buttons = stripslashes($buttons);
    
    // Пробуємо декодувати
    $decoded = json_decode($buttons, true);
    $error = json_last_error();
    
    if ($error !== JSON_ERROR_NONE) {
        $errorMsg = json_last_error_msg();
        $errors[] = [
            'key' => $item['content_key'],
            'error' => $errorMsg,
            'buttons' => substr($buttons, 0, 100) . '...'
        ];
    } else {
        $success[] = $item['content_key'];
    }
}

if (empty($errors)) {
    echo "✅ ВСІ КНОПКИ ВАЛІДНІ (" . count($success) . ")\n";
    foreach ($success as $key) {
        echo "  ✓ $key\n";
    }
} else {
    echo "✅ ВАЛІДНІ (" . count($success) . "):\n";
    foreach ($success as $key) {
        echo "  ✓ $key\n";
    }
    
    echo "\n❌ ПОМИЛКИ (" . count($errors) . "):\n";
    foreach ($errors as $err) {
        echo "  ✗ {$err['key']}: {$err['error']}\n";
        echo "    Preview: {$err['buttons']}\n\n";
    }
}

echo "\n✅ ПЕРЕВІРКА ЗАВЕРШЕНА!\n";