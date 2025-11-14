<?php
namespace Robokassa;

use Robokassa\Client\HttpClientInterface;
use Robokassa\Exception\RobokassaException;
use Robokassa\Signature\SignatureService;
use Robokassa\Service\PaymentService;
use Robokassa\Service\ReceiptService;
use Robokassa\Service\WebService;

class Robokassa
{
    private HttpClientInterface $httpClient;

    /** @var SignatureService */
    private SignatureService $signer;

    private bool $is_test = false;

    protected string $password1;

    protected string $password2;

    private string $hashType = 'md5';

    private string $login;

    private array $hashAlgoList = [ 'md5', 'ripemd160', 'sha1', 'sha256', 'sha384', 'sha512' ];

    private PaymentService $paymentService;

    private ReceiptService $receiptService;

    private WebService $webService;

    /**
     * @param array                 $params
     * @param HttpClientInterface   $httpClient Реализация передаётся снаружи (фасад не знает про Guzzle)
     * @param SignatureService|null $signer
     *
     * @throws RobokassaException
     */
    public function __construct(array $params, HttpClientInterface $httpClient, ?SignatureService $signer = null)
    {
        $this->httpClient = $httpClient;

        if (empty($params['login'])) {
            throw new RobokassaException('Param login is not defined');
        }
        if (empty($params['password1'])) {
            throw new RobokassaException('Param password1 is not defined');
        }
        if (empty($params['password2'])) {
            throw new RobokassaException('Param password2 is not defined');
        }

        if (!empty($params['is_test'])) {
            if (empty($params['test_password1'])) {
                throw new RobokassaException('Param test_password1 is not defined');
            }
            if (empty($params['test_password2'])) {
                throw new RobokassaException('Param test_password2 is not defined');
            }
            $this->is_test = true;
        }

        if (!empty($params['hashType'])) {
            if (!in_array($params['hashType'], $this->hashAlgoList, true)) {
                $except = implode(', ', $this->hashAlgoList);
                throw new RobokassaException("The hashType parameter can only the values: $except");
            }
            $this->hashType = $params['hashType'];
        }

        $this->login = (string)$params['login'];
        $this->password1 = $this->is_test ? (string)$params['test_password1'] : (string)$params['password1'];
        $this->password2 = $this->is_test ? (string)$params['test_password2'] : (string)$params['password2'];

        $this->signer = $signer ?? new SignatureService($this->hashType);
        $this->paymentService = new PaymentService(
            $this->httpClient,
            $this->signer,
            $this->login,
            $this->password1,
            $this->password2,
            $this->is_test,
            $this->hashType
        );
        $this->receiptService = new ReceiptService(
            $this->httpClient,
            $this->signer,
            $this->password1,
            $this->hashType
        );
        $this->webService = new WebService(
            $this->httpClient,
            $this->signer,
            $this->login,
            $this->password2,
            $this->hashType,
        );
    }

    /**
     * Сервис для работы с платежами
     */
    public function payment(): PaymentService
    {
        return $this->paymentService;
    }

    /**
     * Сервис для работы с чеками
     */
    public function receipt(): ReceiptService
    {
        return $this->receiptService;
    }

    /**
     * Сервис для работы с XML интерфейсами
     */
    public function webService(): WebService
    {
        return $this->webService;
    }

    public function signature(): SignatureService
    {
        return $this->signer;
    }
}
