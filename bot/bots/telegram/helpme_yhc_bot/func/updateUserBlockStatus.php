<?php
/**
 * Обновление статуса блокировки/разблокировки бота
 * @param int $from_id - ID пользователя
 * @param bool $value - Значение статуса (true - разблокирован, false - заблокирован)
 * @param array $message - Массив с данными сообщения
 */
function updateUserBlockStatus($from_id, $value, $message) {
 
    $database = Config::getDatabase();
    
    // Проверяем существование пользователя перед обновлением
    $user = $database->get("users", "id", [
        "telegram_id" => $from_id,
    ]);
    
    if(!$user) {
        logError("Error: updateUserBlockStatus - Пользователь ".$from_id." не найден в базе данных");
        return;
    }
    
    $database->update("users", ["active_bot" => $value],[
        "telegram_id" => $from_id
    ]);
    
    if($database->errorInfo) {
        logError("Error: updateUserBlockStatus - Ошибка обновления статуса: ".$database->errorInfo);
    }
}

?>