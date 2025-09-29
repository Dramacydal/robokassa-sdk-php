<?php
namespace Robokassa\Tests;

use PHPUnit\Framework\TestCase;
use Robokassa\Robokassa;
use Robokassa\Service\StatusService;
use Robokassa\Client\Response;

/**
* Тесты методов из примеров
*/
class ExamplesTest extends TestCase {
	private DummyClient $http;

	/**
	 * Инициализация заглушки HTTP-клиента
	 */
	protected function setUp(): void
	{
		$this->http = new DummyClient();
	}

/**
 * Тест метода webService()->getPaymentMethods()
 */
	public function testGetPaymentMethods(): void {
		$xml = '<Result><Method>Card</Method></Result>';
		$this->http->queueResponse(new Response($xml, 200));
		$robo = $this->createRobo();
		$res = $robo->webService()->getPaymentMethods('ru');
		$this->assertSame('Card', $res['Method']);
	}

/**
 * Тест метода webService()->opState()
 */
	public function testOpState(): void {
		$xml = '<Response><OpState>ok</OpState></Response>';
		$this->http->queueResponse(new Response($xml, 200));
		$robo = $this->createRobo();
		$res = $robo->webService()->opState(1);
		$this->assertSame('ok', $res['OpState']);
	}

	/**
	 * Тест метода payment()->sendCurl()
	 */
	public function testSendCurl(): void {
		$body = json_encode(['invoiceID' => 10]);
		$this->http->queueResponse(new Response($body, 200));
		$robo = $this->createRobo();
		$url = $robo->payment()->sendCurl(['OutSum' => 5, 'Description' => 'test']);
		$this->assertStringContainsString('10', $url);
	}

	/**
	 * Тест метода payment()->sendJwt()
	 */
	public function testSendJwt(): void {
		$body = json_encode(['url' => 'https://pay']);
		$this->http->queueResponse(new Response($body, 200));
		$robo = $this->createRobo();
		$url = $robo->payment()->sendJwt(['InvId' => 1, 'OutSum' => 1]);
		$this->assertSame('https://pay', $url);
	}

	/**
	 * Тест метода receipt()->getCheckStatus()
	 */
	public function testGetCheckStatus(): void {
		$body = json_encode(['state' => 1]);
		$this->http->queueResponse(new Response($body, 200));
		$robo = $this->createRobo();
		$res = $robo->receipt()->getCheckStatus(['merchantId' => 'm', 'id' => '1']);
		$this->assertSame(1, $res['state']);
	}

	/**
	 * Тест метода receipt()->sendSecondCheck()
	 */
	public function testSendSecondCheck(): void {
		$this->http->queueResponse(new Response('ok', 200));
		$robo = $this->createRobo();
		$res = $robo->receipt()->sendSecondCheck(['a' => 'b']);
		$this->assertSame('ok', $res);
	}

	/**
	 * Тест метода StatusService::getInvoiceInformationList()
	 */
	public function testGetInvoiceInformationList(): void {
		$body = json_encode(['items' => []]);
		$this->http->queueResponse(new Response($body, 200));
		$status = new StatusService($this->http, 'login', 'p1');
		$res = $status->getInvoiceInformationList([
			'CurrentPage' => 1,
			'PageSize' => 1,
			'InvoiceStatuses' => ['paid'],
			'DateFrom' => '2024-01-01',
			'DateTo' => '2024-01-02',
			'InvoiceTypes' => ['onetime'],
		]);
		$this->assertIsArray($res);
	}

	/**
	 * Создание экземпляра Robokassa для тестов
	 */
	private function createRobo(): Robokassa {
		return new Robokassa([
			'login' => 'login',
			'password1' => 'p1',
			'password2' => 'p2',
			'hashType' => 'md5',
		], $this->http);
	}
}
