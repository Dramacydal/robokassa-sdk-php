<?php
namespace Robokassa\Service;

use Robokassa\Client\HttpClientInterface;
use Robokassa\Exception\RobokassaException;
use Robokassa\Signature\SignatureService;

class PaymentService {
	private HttpClientInterface $http;
	private SignatureService $sign;
	private string $merchantLogin;
	private string $password1;
	private bool $isTest;
	private string $paymentUrl;
	private string $paymentCurl;
	private string $jwtApiUrl;
	private string $hashType;

	public function __construct(HttpClientInterface $http, SignatureService $sign, string $login, string $password1, bool $isTest, string $paymentUrl, string $paymentCurl, string $jwtApiUrl, string $hashType) {
		$this->http = $http;
		$this->sign = $sign;
		$this->merchantLogin = $login;
		$this->password1 = $password1;
		$this->isTest = $isTest;
		$this->paymentUrl = $paymentUrl;
		$this->paymentCurl = $paymentCurl;
		$this->jwtApiUrl = $jwtApiUrl;
		$this->hashType = $hashType;
	}

	/**
	 * Отправка платёжного запроса через CURL (Indexjson.aspx)
	 * @param array $params
	 * @return string
	 * @throws RobokassaException
	 */
	public function sendCurl(array $params): string {
		$params = $this->prepareCurlParams($params);
		$sigParams = $this->buildCurlSignature($params);
		$params['SignatureValue'] = $this->sign->createPaymentSignature(
			$sigParams,
			$this->merchantLogin,
			$this->password1,
			$this->hashType
		);
		$resp = $this->http->post($this->paymentCurl, http_build_query($params), array(
			'Content-Type' => 'application/x-www-form-urlencoded',
		));
		if ($resp->status === 200) {
			$data = json_decode($resp->body, true);
			if (!empty($data['invoiceID'])) {
				return $this->paymentUrl . $data['invoiceID'];
			}
			throw new RobokassaException('Invoice ID not found in response.');
		}
		throw new RobokassaException('Failed to send payment request. HTTP Status: ' . $resp->status);
	}

	/**
	 * Подготовка параметров для CURL-запроса
	 * @param array $params
	 * @return array
	 * @throws RobokassaException
	 */
	private function prepareCurlParams(array $params): array {
		if (empty($params['OutSum']) || empty($params['Description'])) {
			throw new RobokassaException('Required parameters are missing: OutSum, Description');
		}
		$params['MerchantLogin'] = $this->merchantLogin;
		if (!empty($params['Receipt'])) {
			$encoded = urlencode(json_encode($params['Receipt']));
			$params['Receipt'] = urlencode($encoded);
		}
		if ($this->isTest) {
			$params['IsTest'] = '1';
		}
		foreach ($params as $name => $value) {
			if (preg_match('~^Shp_~iu', $name)) {
				$params[$name] = urlencode($value);
			}
		}
		return $params;
	}

	/**
	 * Формирование массива для подписи
	 * @param array $params
	 * @return array
	 */
	private function buildCurlSignature(array $params): array {
		$sig = array(
			'OutSum'	=> $params['OutSum'],
			'InvoiceID' => $params['InvoiceID'] ?? '',
		);
		if (!empty($params['Receipt'])) {
			$sig['Receipt'] = urldecode($params['Receipt']);
		}
		foreach ($params as $name => $value) {
			if (preg_match('~^Shp_~iu', $name)) {
				$sig[$name] = $value;
			}
		}
		return $sig;
	}

	/**
	 * Создание счёта через JWT интерфейс
	 * @param array $params
	 * @return string
	 * @throws RobokassaException
	 */
	public function sendJwt(array $params): string {
		$payload = $this->buildJwtPayload($params);
		[$eh, $ep, $toSign] = $this->sign->encodeJwtParts(array('alg' => 'MD5', 'typ' => 'JWT'), $payload);
		$sig = $this->sign->jwtSignMd5($toSign, $this->merchantLogin, $this->password1);
		$jwt = $toSign . '.' . $sig;
		$resp = $this->http->post($this->jwtApiUrl, json_encode($jwt), array('Content-Type' => 'application/json'));
		$data = json_decode($resp->body, true);
		if (!empty($data['url'])) {
			return $data['url'];
		}
		throw new RobokassaException('JWT request failed: ' . $resp->body);
	}

	/**
	 * Подготовка payload для JWT
	 * @param array $params
	 * @return array
	 * @throws RobokassaException
	 */
	private function buildJwtPayload(array $params): array {
		if (empty($params['OutSum']) || !isset($params['InvId'])) {
			throw new RobokassaException('Required parameters: OutSum, InvId');
		}
		$payload = array(
			'MerchantLogin' => $this->merchantLogin,
			'InvoiceType'	=> $params['InvoiceType'] ?? 'OneTime',
			'Culture'		=> $params['Culture'] ?? 'ru',
			'InvId'			=> (int)$params['InvId'],
			'OutSum'		=> (float)$params['OutSum'],
		);
		$optional = array('Description','MerchantComments','InvoiceItems','UserFields','SuccessUrl2Data','FailUrl2Data');
		foreach ($optional as $key) {
			if (!empty($params[$key])) {
				$payload[$key] = $params[$key];
			}
		}
		return $payload;
	}
}
