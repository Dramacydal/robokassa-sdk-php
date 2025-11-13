<?php
namespace Robokassa\Signature;

use Robokassa\Exception\RobokassaException;

class SignatureService {
	/** @var string[] */
	private static $allowedAlgorithms = [ 'md5','sha256','sha512' ];

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
		$algo = strtolower($algo ?: $this->defaultAlgo);
		if (!in_array($algo, self::$allowedAlgorithms, true)) {
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
	 * @param string      $password1
	 * @param string|null $algo       md5|sha256|sha512 (иначе md5)
	 * @return string                 HEX-хеш
	 */
	public function createPaymentSignature(array $params, string $password1, ?string $algo = null): string
    {
        if (!isset($params['OutSum'])) {
            throw new RobokassaException('OutSum not set');
        }

        if (empty($params['InvoiceID']) ?? empty($params['InvId'])) {
            $params['InvoiceID'] = '';
        }

        static $signatureOrder = [
            'MerchantLogin',
            'OutSum',
            'InvoiceID', 'InvId',
            'Receipt',
            'ResultUrl2',
            'SuccessUrl2',
            'SuccessUrl2Method',
        ];

        $hashElements = [];
        foreach ($signatureOrder as $key) {
            if (isset($params[$key])) {
                $hashElements[] = is_array($params[$key]) ? json_encode($params[$key]) : $params[$key];
            }
        }

		$hashElements[] = $password1;

		// собрать пары Shp_* в виде key=value и отсортировать
		$pairs = [];
		foreach ($params as $k => $v) {
            if (preg_match('~^Shp_~iu', $k)) {
                $pairs[] = $k . '=' . $v;
            }
        }

		sort($pairs);

		if (!empty($pairs)) {
            $hashElements = array_merge($hashElements, $pairs);
		}

		return $this->hash($hashElements, $algo);
	}

    public function hash(array $params, ?string $algo = null): string
    {
        $algo = strtolower($algo ?: $this->defaultAlgo);
        if (!in_array($algo, self::$allowedAlgorithms, true)) {
            $algo = 'md5';
        }

        return hash($algo, implode(':', $params));
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
	 * @param string|null $algo md5|sha256|sha512 (иначе md5)
	 *
	 * @return string                 HEX-хеш
	 */
	public function signOpState(string $login, string $invoiceID, string $password2, ?string $algo = null): string
    {
        return $this->hash([ $login, $invoiceID, $password2 ], $algo);
    }
}