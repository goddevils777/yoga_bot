<?php

/**
 * Нормализация команды
 * @param string $command Команда
 * @return string Нормализованная команда
 */
function normalizeCommand($command) {
    // Проверяем, что $command является строкой и не пустой
    if (!is_string($command) || empty($command)) {
        return '/';
    }
    // Добавляем слеш в начало, если его нет
    return (!str_starts_with($command, '/')) ? '/' . $command : $command;
}

?>