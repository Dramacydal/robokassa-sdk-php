<?php
namespace Robokassa\Tests;

use Robokassa\Client\HttpClient;
use Robokassa\Client\HttpClientInterface;
use Robokassa\Client\Response;

// Подгрузка файла HttpClient с описанием интерфейса
class_exists(HttpClient::class);

/**
	* Заглушка HTTP-клиента для тестов
	*/
class DummyClient implements HttpClientInterface {
	/** @var Response[] */
	private array $responses = [];
	public string $lastUrl = '';
	public string $lastBody = '';
	public array $lastHeaders = [];

	/**
	 * Добавить ответ в очередь
	 */
	public function queueResponse(Response $response): void {
		$this->responses[] = $response;
	}

	/**
	 * Имитация запроса GET
	 */
	public function get(string $url, array $headers = []): Response {
		$this->lastUrl = $url;
		$this->lastHeaders = $headers;
		return array_shift($this->responses);
	}

	/**
	 * Имитация запроса POST
	 */
	public function post(string $url, string $body, array $headers = []): Response {
		$this->lastUrl = $url;
		$this->lastBody = $body;
		$this->lastHeaders = $headers;
		return array_shift($this->responses);
	}
}
