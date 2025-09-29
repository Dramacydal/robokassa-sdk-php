<?php
require_once __DIR__ . '/bootstrap.php';

/**
 * Пример использования метода receipt()->getCheckStatus()
 * Получает статус фискализации чека по InvId
 */

$robokassa = createRobokassa();

$payload = [
	'merchantId' => $_ENV['ROBOKASSA_LOGIN'] ?? '',
	'id' => '1337'
];

try {
	$status = $robokassa->receipt()->getCheckStatus($payload);

	echo "Ответ Robokassa (Status):\n";
	echo json_encode($status, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
	echo "Ошибка: " . $e->getMessage();
}
