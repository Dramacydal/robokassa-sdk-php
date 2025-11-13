<?php
namespace Robokassa\Service;

use Robokassa\Client\HttpClientInterface;
use Robokassa\Signature\SignatureService;

class StatusService
{
	/** @var string */
	private $endpoint = 'https://services.robokassa.kz/InvoiceServiceWebApi/api/GetInvoiceInformationList';

	/** @var HttpClientInterface */
	private $http;

	/** @var SignatureService */
	private $sign;

	/** @var string */
	private $merchantLogin;

	/** @var string */
	private $password1;

	/**
	 * @param HttpClientInterface $http
	 * @param string $merchantLogin
	 * @param string $password1
	 */
	public function __construct($http, $merchantLogin, $password1) {
		$this->http          = $http;
		$this->merchantLogin = (string)$merchantLogin;
		$this->password1     = (string)$password1;
		$this->sign          = new SignatureService('md5'); // Robokassa JWT использует MD5
	}

	/**
	 * Получить список счетов/ссылок по фильтрам (дока: GetInvoiceInformationList).
	 * Обязательные поля: CurrentPage, PageSize, InvoiceStatuses, DateFrom, DateTo, InvoiceTypes
	 *
	 * @param array $filters
	 * @return array
	 * @throws \Exception
	 */
	public function getInvoiceInformationList(array $filters) {
		// обязательные поля
		$required = array('CurrentPage','PageSize','InvoiceStatuses','DateFrom','DateTo','InvoiceTypes');
		foreach ($required as $req) {
			if (!array_key_exists($req, $filters)) {
				throw new \Exception('Missing required field: ' . $req);
			}
		}

		// нормализация статусов/типов: принимаем нижний регистр, отправляем как в доке
		$normalizeList = function ($list) {
			if (!is_array($list)) return $list;
			$out = array();
			foreach ($list as $v) {
				$s = strtolower((string)$v);
				if     ($s === 'paid')     { $out[] = 'Paid'; }
				elseif ($s === 'expired')  { $out[] = 'Expired'; }
				elseif ($s === 'notpaid')  { $out[] = 'Notpaid'; }
				elseif ($s === 'onetime')  { $out[] = 'OneTime'; }
				elseif ($s === 'reusable') { $out[] = 'Reusable'; }
				else { $out[] = $v; }
			}
			return $out;
		};

		if (isset($filters['InvoiceStatuses'])) {
			$filters['InvoiceStatuses'] = $normalizeList($filters['InvoiceStatuses']);
		}
		if (isset($filters['InvoiceTypes'])) {
			$filters['InvoiceTypes'] = $normalizeList($filters['InvoiceTypes']);
		}

		// JWT parts
		$header  = array('alg' => 'MD5', 'typ' => 'JWT');
		$payload = array_merge(array('MerchantLogin' => $this->merchantLogin), $filters);

		list($encHeader, $encPayload, $toSign) = $this->sign->encodeJwtParts($header, $payload);
		$signature = $this->sign->jwtSignMd5($toSign, $this->merchantLogin, $this->password1);
		$jwt = $toSign . '.' . $signature;

		$resp = $this->http->post($this->endpoint, json_encode($jwt), array(
			'Content-Type' => 'application/json',
		));

		$data = json_decode($resp->body, true);
		if (!is_array($data)) {
			throw new \Exception('Bad JSON in response: ' . $resp->body);
		}
		return $data;
	}
}
