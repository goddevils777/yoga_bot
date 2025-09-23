<?php
/**
 * Добавляет глобальные команды в бота
 * @param array $message
 */
function setMyCommands($message){
    $commands = [
        [
            'command' => '/menu',
                'description' => '🏠 Главное меню'
        ],
        [
            'command' => '/programs',
            'description' => '👩‍🏫 Развивающие программы'
        ],
        [
            'command' => '/onlineyoga',
            'description' => '🧘‍♀️ Йога с нами онлайн'
        ],
        [
            'command' => '/retreats',
            'description' => '🛫 Туры и ретриты'
        ],
        [
            'command' => '/detox',
            'description' => '💚 Детокс программы'
        ],
        [
            'command' => '/help',
            'description' => '💬 Задать вопрос'
        ]
    ];

    sendMessage('setMyCommands', [
        'commands' => $commands
    ]);
}

?>