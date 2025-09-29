<?php

require_once __DIR__ . '/bootstrap.php';

/**
 * Пример использования метода payment()->sendCurl()
 * Создаёт платёжную ссылку через обычный POST-запрос (не JWT)
 */

try {
	$robokassa = createRobokassa();

	$params = [
	    'OutSum' => 100.00,
	    'InvoiceID' => 123456,
	    'Description' => 'Оплата заказа #123456',
	    'Culture' => 'ru',
	    'Receipt' => [
	        'items' => [
	            [
	                'name' => 'Товар 1',
	                'quantity' => 1,
	                'sum' => 100.00,
	                'payment_method' => 'full_payment',
	                'payment_object' => 'commodity',
	                'tax' => 'none'
	            ]
	        ]
	    ]
	];

	$url = $robokassa->payment()->sendCurl($params);
	echo "Ссылка на оплату: $url\n";

} catch (Exception $e) {
	echo "Ошибка: " . $e->getMessage() . "\n";
}
