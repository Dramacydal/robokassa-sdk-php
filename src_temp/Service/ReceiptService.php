<?php
namespace Robokassa\Service;

use Robokassa\Client\HttpClientInterface;
use Robokassa\Exception\RobokassaException;
use Robokassa\Signature\SignatureService;

class ReceiptService {
	private const SECOND_CHECK_URL = 'https://ws.roboxchange.com/RoboFiscal/Receipt/Attach';
	private const CHECK_STATUS_URL = 'https://ws.roboxchange.com/RoboFiscal/Receipt/Status';

	private HttpClientInterface $http;
	private SignatureService $sign;
	private string $password1;
	private string $hashType;

	public function __construct(HttpClientInterface $http, SignatureService $sign, string $password1, string $hashType) {
		$this->http = $http;
		$this->sign = $sign;
		$this->password1 = $password1;
		$this->hashType = $hashType;
	}

	/**
	 * Генерация строки второго чека
	 * @param array $payload
	 * @return string
	 * @throws RobokassaException
	 */
	public function getSecondCheckUrl(array $payload): string {
		$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($json === false) {
			throw new RobokassaException('Ошибка кодирования JSON');
		}
		$base64Payload = $this->sign->b64url($json);
		$base64Signature = $this->sign->signFiscal($base64Payload, $this->password1, $this->hashType);
		return $base64Payload . '.' . $base64Signature;
	}

	/**
	 * Отправка второго чека в RoboFiscal
	 * @param array $payload
	 * @return string
	 * @throws RobokassaException
	 */
	public function sendSecondCheck(array $payload): string {
		$body = $this->getSecondCheckUrl($payload);
		$resp = $this->http->post(self::SECOND_CHECK_URL, $body, array('Content-Type' => 'application/json'));
		return $resp->body;
	}

	/**
	 * Получение статуса чека (RoboFiscal)
	 * @param array $payload
	 * @return array
	 * @throws RobokassaException
	 */
	public function getCheckStatus(array $payload): array {
		if (empty($payload['merchantId']) || empty($payload['id'])) {
			throw new RobokassaException('Не указаны обязательные параметры: merchantId и id (InvId).');
		}
		$json = json_encode($payload, JSON_UNESCAPED_UNICODE);
		if ($json === false) {
			throw new RobokassaException('Ошибка кодирования JSON');
		}
		$base64Payload = $this->sign->b64url($json);
		$base64Signature = $this->sign->signFiscal($base64Payload, $this->password1, $this->hashType);
		$body = $base64Payload . '.' . $base64Signature;
		$resp = $this->http->post(self::CHECK_STATUS_URL, $body, array('Content-Type' => 'application/json; charset=utf-8'));
		$data = json_decode($resp->body, true);
		if ($data === null) {
			throw new RobokassaException('Некорректный JSON в ответе: ' . $resp->body);
		}
		return $data;
	}
}
