<?php

require_once __DIR__ . '/bootstrap.php';

/**
 * Пример использования метода webService()->opState()
 * Получает текущий статус счёта по его InvoiceID
 */

try {
	$robokassa = createRobokassa();

	$invoiceID = 1973546115;

	$status = $robokassa->webService()->opState($invoiceID);
	echo "Статус счета #$invoiceID:\n";
	print_r($status);

} catch (Exception $e) {
	echo "Ошибка: " . $e->getMessage() . "\n";
}
