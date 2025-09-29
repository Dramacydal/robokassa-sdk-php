<?php
namespace Robokassa\Service;

use Robokassa\Client\HttpClientInterface;
use Robokassa\Exception\RobokassaException;
use Robokassa\Signature\SignatureService;

/**
 * Сервис для работы с XML WebService Робокассы
 */
class WebService {
	private HttpClientInterface $http;
	private SignatureService $sign;
	private string $merchantLogin;
	private string $password2;
	private string $hashType;
	private string $url;

	/**
	 * @param HttpClientInterface $http
	 * @param SignatureService $sign
	 * @param string $merchantLogin
	 * @param string $password2
	 * @param string $hashType
	 * @param string $url
	 */
	public function __construct(HttpClientInterface $http, SignatureService $sign, string $merchantLogin, string $password2, string $hashType, string $url) {
		$this->http = $http;
		$this->sign = $sign;
		$this->merchantLogin = $merchantLogin;
		$this->password2 = $password2;
		$this->hashType = $hashType;
		$this->url = $url;
	}

	/**
	 * Получение списка доступных способов оплаты
	 *
	 * @param string $lang
	 * @return array
	 * @throws RobokassaException
	 */
	public function getPaymentMethods(string $lang = 'en'): array {
		if ($lang === '') {
			throw new RobokassaException('Param lang is not defined');
		}
		$query = http_build_query([
			'MerchantLogin' => $this->merchantLogin,
			'Language' => $lang,
		]);
		$resp = $this->http->get($this->buildUrl('GetPaymentMethods', $query));
		if ($resp->status !== 200) {
			throw new RobokassaException('Ошибка запроса: HTTP ' . $resp->status);
		}
		return $this->xmlToArray($resp->body);
	}

	/**
	 * Получение состояния оплаты счёта (OpStateExt)
	 *
	 * @param int $invoiceID
	 * @return array
	 * @throws RobokassaException
	 */
	public function opState(int $invoiceID): array {
		$query = http_build_query([
			'MerchantLogin' => $this->merchantLogin,
			'InvoiceID' => $invoiceID,
			'Signature' => $this->sign->signOpState(
				$this->merchantLogin,
				(string)$invoiceID,
				$this->password2,
				$this->hashType
			),
		]);
		$resp = $this->http->get($this->buildUrl('OpStateExt', $query));
		if ($resp->status !== 200) {
			throw new RobokassaException('Ошибка запроса: HTTP ' . $resp->status);
		}
		return $this->xmlToArray($resp->body);
	}

	private function buildUrl(string $segment, string $query): string {
		return $this->url . '/' . $segment . '?' . $query;
	}

	private function xmlToArray(string $xml): array {
		$res = simplexml_load_string($xml);
		return json_decode(json_encode((array)$res, JSON_NUMERIC_CHECK), true);
	}
}
