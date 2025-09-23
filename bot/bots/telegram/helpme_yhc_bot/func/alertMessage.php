<?php

/**
 * Всплывающее уведомление
 * 
 * @param string $callback_id - id callback
 * @param string $message - текст уведомления
 */
function alertMessage($callback_id, $message) {
	$res = sendMessage('answerCallbackQuery',[
		'callback_query_id' => $callback_id,
		'text' => $message,
		'show_alert' => true
	]);
	return $res;
}

?>