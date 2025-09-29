<?php
require_once __DIR__ . '/bot/api/config.php';

$database = Config::getDatabase();

// Контент для додавання
$missing_content = [
    [
        'content_key' => 'online_yoga',
        'text' => "<b>👩‍🏫 Йога с нами онлайн</b>\n\nВыберите формат занятий:",
        'buttons' => json_encode([
            ['text' => '🧘‍♀️ Живые занятия', 'callback_data' => '/live_yoga'],
            ['text' => '📚 Наша обучающая платформа', 'callback_data' => '/our_learning_platform'],
            ['text' => '🆓 Бесплатные занятия', 'callback_data' => '/free_classes'],
            ['text' => '💬 Задать вопрос', 'callback_data' => '/ask_question'],
            ['text' => '« Назад в главное меню', 'callback_data' => '/menu']
        ])
    ],
    [
        'content_key' => 'tours_and_retreats',
        'text' => "<b>Туры и ретриты 🛫</b>\n\n<b>Откройте для себя новые горизонты с нашими уникальными программами:</b>\n\n📅 Календарь всех предстоящих туров\n🛫 Йога-ретрит в Таиланде - погружение в практику\n🛫 Йога-ретрит на Бали - остров духовности\n🛫 Тур в Непал - путешествие к Гималаям\n🛫 Тур \"Дзен в современной Японии\"\n🛫 Духовный тур на Кайлас - священная гора\n\n<i>Выберите направление вашего путешествия:</i>",
        'media_id' => 'AgACAgIAAxkBAAIBYWfdWyj95Y8BW5BhPPEG8LtbPVxsAALo7DEb5-LpStXj5F8vVUVOAQADAgADeQADNgQ',
        'media_type' => 'photo',
        'buttons' => json_encode([
            ['text' => '📅 Календарь туров', 'callback_data' => '/tours_calendar'],
            ['text' => '🏝 Йога ретрит в Таиланде', 'callback_data' => '/thailand_retreat'],
            ['text' => '🌺 Йога ретрит на Бали', 'callback_data' => '/bali_retreat'],
            ['text' => '🏔 Тур в Непал', 'callback_data' => '/nepal_tour'],
            ['text' => '🗾 Тур "Дзен в современной Японии"', 'callback_data' => '/japan_zen_tour'],
            ['text' => '🗻 Духовный тур на Кайлас', 'callback_data' => '/kailas_tour'],
            ['text' => '💬 Задать вопрос', 'callback_data' => '/ask_question'],
            ['text' => '« Назад в главное меню', 'callback_data' => '/menu']
        ])
    ],
    [
        'content_key' => 'detox_programs',
        'text' => "<b>💚 Детокс программы</b>\n\nВыберите программу:",
        'buttons' => json_encode([
            ['text' => '🌿 Легкий детокс', 'callback_data' => '/light_detox'],
            ['text' => '💧 Детокс на 3 дня', 'callback_data' => '/detox_3days'],
            ['text' => '💪 Детокс на 7 дней', 'callback_data' => '/detox_7days'],
            ['text' => '💬 Задать вопрос', 'callback_data' => '/ask_question'],
            ['text' => '« Назад в главное меню', 'callback_data' => '/menu']
        ])
    ]
];

foreach ($missing_content as $content) {
    $content['bot_id'] = 1;
    $content['status'] = 'active';
    
    $database->insert('bot_content', $content);
    echo "✅ Добавлен: {$content['content_key']}\n";
}

echo "\n✅ Все отсутствующие content_key добавлены!\n";