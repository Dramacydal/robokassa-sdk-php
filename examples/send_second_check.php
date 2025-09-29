<?php

require_once __DIR__ . '/bootstrap.php';

/**
 * Пример использования метода receipt()->sendSecondCheck()
 * Отправляет второй чек по ранее проведенной операции
 */

$robokassa = createRobokassa();

$payload = [
	'merchantId' => $_ENV['ROBOKASSA_LOGIN'] ?? '',
	'id' => '100',
	'originId' => '162103662',
	'operation' => 'sell',
	'sno' => 'osn',
	'url' => 'https://www.robokassa.ru/',
	'total' => 1,
	'items' => [
	    [
	        'name' => 'Тестовый товар',
	        'quantity' => 1,
	        'sum' => 1,
	        'tax' => 'none',
	        'payment_method' => 'full_payment',
	        'payment_object' => 'payment',
	    ]
	],
	'client' => [
	    'email' => 'test@test.ru',
	],
	'payments' => [
	    [
	        'type' => 2,
	        'sum' => 1
	    ]
	],
	'vats' => [
	    [
	        'type' => 'none',
	        'sum' => 0
	    ]
	]
];

try {
	$result = $robokassa->receipt()->sendSecondCheck($payload);
	echo "Ответ от Robokassa:\n";
	echo $result;
} catch (Exception $e) {
	echo "Ошибка: " . $e->getMessage();
}
