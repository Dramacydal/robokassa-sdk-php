<?php

require_once __DIR__ . '/bootstrap.php';

/**
 * Пример использования метода webService()->getPaymentMethods()
 * Получает список доступных способов оплаты
 */

try {
	$robokassa = createRobokassa();

	$methods = $robokassa->webService()->getPaymentMethods();
	echo "Доступные методы оплаты:\n";
	print_r($methods);

} catch (Exception $e) {
	echo "Ошибка: " . $e->getMessage() . "\n";
}
