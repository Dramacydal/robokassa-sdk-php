<?php
require_once __DIR__ . '/bootstrap.php';

use Robokassa\Client\HttpClient;
use Robokassa\Service\StatusService;

$http = new HttpClient();
$status = new StatusService($http, $_ENV['ROBOKASSA_LOGIN'] ?? '', $_ENV['ROBOKASSA_PASSWORD1'] ?? '');

try {
	$result = $status->getInvoiceInformationList([
	    'MerchantLogin' => $_ENV['ROBOKASSA_LOGIN'] ?? '',
	    'CurrentPage' => 1,
	    'PageSize' => 10,
	    'InvoiceStatuses' => ['paid','expired','notpaid'],
	    'DateFrom' => '2023-01-01',
	    'DateTo' => '2025-09-05',
	    'IsAscending' => true,
	    'InvoiceTypes' => ['onetime','reusable'],
	    'PaymentAliases' => ['BankCard'],
	    'SumFrom' => 1,
	    'SumTo' => 10000,
	]);

	print_r($result);
} catch (\Throwable $e) {
	echo "Error: " . $e->getMessage() . PHP_EOL;
}
