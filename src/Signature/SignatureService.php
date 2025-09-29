<?php
namespace Robokassa\Signature;

class SignatureService {
	/** @var string[] */
	private static $ALLOWED = array('md5','sha256','sha512');

	/** @var string */
	private $defaultAlgo;

	public function __construct($defaultAlgo = 'md5') {
		$this->defaultAlgo = $defaultAlgo;
	}

	/** Base64URL без паддинга */
	public function b64url($data) {
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	/**
	 * Схема “второго чека / статуса чека”:
	 * hash(base64(payload) . secret) -> HEX, затем base64url(HEX)
	 *
	 * @param string      $base64Payload
	 * @param string      $secret
	 * @param string|null $algo
	 * @return string
	 */
	public function signFiscal($base64Payload, $secret, $algo = null) {
		$algo = strtolower($algo ? $algo : $this->defaultAlgo);
		if (!in_array($algo, self::$ALLOWED, true)) {
			$algo = 'md5';
		}
		$hashHex = hash($algo, $base64Payload . $secret, false);
		return $this->b64url($hashHex);
	}

	/**
	 * Подпись JWT (CreateInvoice / GetInvoiceInformationList):
	 * HMAC-MD5 (binary) -> base64url
	 *
	 * @param string $dataToSign
	 * @param string $merchantLogin
	 * @param string $password1
	 * @return string
	 */
	public function jwtSignMd5($dataToSign, $merchantLogin, $password1) {
		$raw = hash_hmac('md5', $dataToSign, $merchantLogin . ':' . $password1, true);
		return $this->b64url($raw);
	}

	/**
	 * Возвращает [encodedHeader, encodedPayload, dataToSign]
	 *
	 * @param array $header
	 * @param array $payload
	 * @return array{0:string,1:string,2:string}
	 */
	public function encodeJwtParts(array $header, array $payload) {
		$encHeader  = $this->b64url(json_encode($header,  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		$encPayload = $this->b64url(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		return array($encHeader, $encPayload, $encHeader . '.' . $encPayload);
	}

	/**
	 * Подпись для платежного запроса (Indexjson.aspx).
	 *
	 * Формат строки для хэша:
	 *   login:OutSum:InvoiceID[:Receipt]:password1[:Shp_key=value ...]
	 *
	 * Где пары Shp_* добавляются как key=value и сортируются по ключу (lexicographically).
	 *
	 * @param array       $params     Должны содержать OutSum, InvoiceID, опционально Receipt и Shp_* поля
	 * @param string      $login
	 * @param string      $password1
	 * @param string|null $algo       md5|sha256|sha512 (иначе md5)
	 * @return string                 HEX-хеш
	 */
	public function createPaymentSignature(array $params, $login, $password1, $algo = null) {
		$required = array($login, $params['OutSum'], $params['InvoiceID']);

		if (!empty($params['Receipt'])) {
			$required[] = $params['Receipt'];
		}

		$required[] = $password1;

		// собрать пары Shp_* в виде key=value и отсортировать
		$pairs = array();
		foreach ($params as $k => $v) {
			if (preg_match('~^Shp_~iu', $k)) {
				$pairs[] = $k . '=' . $v;
			}
		}
		sort($pairs);

		$hashString = implode(':', $required);
		if (!empty($pairs)) {
			$hashString .= ':' . implode(':', $pairs);
		}

		$algo = strtolower($algo ? $algo : $this->defaultAlgo);
		if (!in_array($algo, self::$ALLOWED, true)) {
			$algo = 'md5';
		}

		return hash($algo, $hashString);
	}

	/**
	 * Подпись для WebService OpStateExt.
	 *
	 * Формат:
	 *   hash(algo, "{login}:{invoiceID}:{password2}")
	 *
	 * @param string      $login
	 * @param string      $invoiceID
	 * @param string      $password2
	 * @param string|null $algo       md5|sha256|sha512 (иначе md5)
	 * @return string                 HEX-хеш
	 */
	public function signOpState($login, $invoiceID, $password2, $algo = null) {
		$algo = strtolower($algo ? $algo : $this->defaultAlgo);
		if (!in_array($algo, self::$ALLOWED, true)) {
			$algo = 'md5';
		}

		return hash($algo, $login . ':' . $invoiceID . ':' . $password2);
	}
}