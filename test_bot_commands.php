<?php
require_once __DIR__ . '/bot/api/config.php';

$database = Config::getDatabase();

echo "=== ПЕРЕВІРКА ВСІХ КОМАНД БОТА ===\n\n";

// Всі команди з request.php
$commands = [
    '/start' => ['start_message', 'primary_menu'],
    '/menu' => ['primary_menu'],
    '/programs' => ['developing_programs'],
    '/developing_programs' => ['developing_programs'],
    '/onlineyoga' => ['online_yoga'],
    '/online_yoga' => ['online_yoga'],
    '/retreats' => ['tours_and_retreats'],
    '/tours_and_retreats' => ['tours_and_retreats'],
    '/detox' => ['detox_programs'],
    '/detox_programs' => ['detox_programs'],
    '/aroma_diagnostics' => ['aroma_diagnostics'],
    '/successful_year' => ['successful_year'],
    '/longevity_foundation' => ['longevity_foundation'],
    '/inner_support' => ['inner_support'],
    '/vipassana_online' => ['vipassana_online'],
    '/kids_yoga' => ['kids_yoga'],
    '/live_yoga' => ['live_yoga'],
    '/our_learning_platform' => ['our_learning_platform'],
    '/light_detox' => ['light_detox'],
    '/detox_3days' => ['detox_3days'],
    '/detox_7days' => ['detox_7days'],
    '/tours_calendar' => ['tours_calendar'],
    '/thailand_retreat' => ['thailand_retreat'],
    '/bali_retreat' => ['bali_retreat'],
    '/nepal_tour' => ['nepal_tour'],
    '/japan_zen_tour' => ['japan_zen_tour'],
    '/kailas_tour' => ['kailas_tour'],
    '/free_classes' => ['free_classes'],
    '/dharma_code' => ['dharma_code']
];

$missing = [];
$found = [];

foreach ($commands as $command => $content_keys) {
    foreach ($content_keys as $key) {
        $exists = $database->has('bot_content', [
            'bot_id' => 1,
            'content_key' => $key,
            'status' => 'active'
        ]);
        
        if ($exists) {
            $found[] = "$command → $key";
        } else {
            $missing[] = "$command → $key (ВІДСУТНІЙ!)";
        }
    }
}

echo "✅ ЗНАЙДЕНО В БАЗІ (" . count($found) . "):\n";
foreach ($found as $item) {
    echo "  ✓ $item\n";
}

if (!empty($missing)) {
    echo "\n❌ ВІДСУТНІ В БАЗІ (" . count($missing) . "):\n";
    foreach ($missing as $item) {
        echo "  ✗ $item\n";
    }
} else {
    echo "\n🎉 ВСІ КОМАНДИ Є В БАЗІ!\n";
}

echo "\n=== ПЕРЕВІРКА КНОПОК ===\n";
$contentWithButtons = $database->count('bot_content', [
    'bot_id' => 1,
    'buttons[!]' => null,
    'buttons[!]' => ''
]);

echo "Контент з кнопками: $contentWithButtons\n";

echo "\n=== ПЕРЕВІРКА МЕДІА ===\n";
$contentWithMedia = $database->count('bot_content', [
    'bot_id' => 1,
    'media_id[!]' => null
]);

echo "Контент з зображеннями: $contentWithMedia\n";

echo "\n✅ ТЕСТУВАННЯ ЗАВЕРШЕНО!\n";