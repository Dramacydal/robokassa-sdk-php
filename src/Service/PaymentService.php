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
	private string $password2;
	private bool $isTest;
	private string $hashType;

    private string $paymentUrl = 'https://auth.robokassa.kz/Merchant/Index/';

    private string $paymentCurl = 'https://auth.robokassa.kz/Merchant/Indexjson.aspx';

    private string $paymentGenerator = 'https://auth.robokassa.kz/Merchant/Index.aspx';

    private string $jwtApiUrl = 'https://services.robokassa.kz/InvoiceServiceWebApi/api/CreateInvoice';


	public function __construct(HttpClientInterface $http, SignatureService $sign, string $login, string $password1, string $password2, bool $isTest, string $hashType) {
		$this->http = $http;
		$this->sign = $sign;
		$this->merchantLogin = $login;
		$this->password1 = $password1;
		$this->password2 = $password2;
		$this->isTest = $isTest;
		$this->hashType = $hashType;
	}

	/**
	 * Отправка платёжного запроса через CURL (Indexjson.aspx)
	 * @param array $params
	 * @return array
	 * @throws RobokassaException
	 */
	public function sendCurl(array $params): array
    {
        $curlParams = $this->prepareCurlParams($params);
        $curlParams['SignatureValue'] = $this->sign->createPaymentSignature(
            array_merge($params, [ 'Login' => $this->merchantLogin ]),
            $this->password1,
            $this->hashType
        );
        $resp = $this->http->post($this->paymentCurl, http_build_query($curlParams), [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        if ($resp->status === 200) {
            $data = json_decode($resp->body, true);
            if (!empty($data['invoiceID'])) {
                $data['url'] = $this->paymentUrl . $data['invoiceID'];;

                return $data;
            }

            throw new RobokassaException('Invoice ID not found in response: ' . json_encode($resp));
        }
        throw new RobokassaException('Failed to send payment request. HTTP Status: ' . $resp->status);
    }

    public function validatePaymentSignature(array $params, string $signature): bool
    {
        $calculated = $this->sign->createPaymentSignature(
            $params,
            $this->password2,
            $this->hashType
        );

        return mb_strtoupper($calculated) ===  mb_strtoupper($signature);
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
	public function sendJwt(array $params): array
    {
        $payload = $this->buildJwtPayload($params);
        [ $eh, $ep, $toSign ] = $this->sign->encodeJwtParts([ 'alg' => 'MD5', 'typ' => 'JWT' ], $payload);
        $sig = $this->sign->jwtSignMd5($toSign, $this->merchantLogin, $this->password1);
        $jwt = $toSign . '.' . $sig;
        $resp = $this->http->post($this->jwtApiUrl, json_encode($jwt), [ 'Content-Type' => 'application/json' ]);
        $data = json_decode($resp->body, true);
        if (!empty($data['url'])) {
            return $data;
        }

        throw new RobokassaException('JWT request failed: ' . $resp->body);
    }

    /**
     * Подготовка payload для JWT
	 * @param array $params
	 * @return array
	 * @throws RobokassaException
	 */
	private function buildJwtPayload(array $params): array
    {
        if (empty($params['OutSum']) || !isset($params['InvId'])) {
            throw new RobokassaException('Required parameters: OutSum, InvId');
        }
        $payload = [
            'MerchantLogin' => $this->merchantLogin,
            'InvoiceType' => $params['InvoiceType'] ?? 'OneTime',
            //			'Culture'		=> $params['Culture'] ?? 'ru',
            'InvId' => (int)$params['InvId'],
            'OutSum' => (float)$params['OutSum'],
        ];

        if ($this->isTest) {
            $payload['IsTest'] = '1';
        }

        $optional = [ 'Description', 'MerchantComments', 'InvoiceItems', 'UserFields', 'SuccessUrl2Data', 'FailUrl2Data' ];
        foreach ($optional as $key) {
            if (!empty($params[$key])) {
                $payload[$key] = $params[$key];
            }
        }

        return $payload;
    }

    public function createPaymentLink(array $params): array
    {
        if ($this->isTest) {
            $params['IsTest'] = '1';
        }
        
        $params['MerchantLogin'] = $this->merchantLogin;

        $signature = $this->sign->createPaymentSignature(
            $params,
            $this->password1,
            $this->hashType
        );

        $params['SignatureValue'] = $signature;

        $params = array_map(fn($x) => is_array($x) ? json_encode($x) : $x, $params);

        return [
            'link' => $this->paymentGenerator . '?' . http_build_query($params, '', '&'),
            'url' => $this->paymentGenerator,
            'params' => $params,
        ];
    }
}
