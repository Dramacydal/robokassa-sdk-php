<?php
namespace Robokassa\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

final class Response {
	/** @var string */
	public $body;

	/** @var int */
	public $status;

	public function __construct(string $body, int $status) {
		$this->body   = $body;
		$this->status = $status;
	}
}

interface HttpClientInterface {
	public function get(string $url, array $headers = []): Response;
	public function post(string $url, string $body, array $headers = []): Response;
}

final class HttpClient implements HttpClientInterface {
	/** @var Client */
	private $client;

	public function __construct(?Client $client = null) {
		$this->client = $client ?? new Client(['timeout' => 15]);
	}

	public function get(string $url, array $headers = []): Response {
		try {
			$r = $this->client->get($url, ['headers' => $headers]);
			return new Response((string)$r->getBody(), $r->getStatusCode());
		} catch (RequestException $e) {
			$resp = $e->getResponse();
			$msg  = $resp ? (string)$resp->getBody() : $e->getMessage();

			throw new \Exception('Ошибка HTTP GET: ' . $msg, 0, $e);
		}
	}

	public function post(string $url, string $body, array $headers = []): Response {
		try {
			$r = $this->client->post($url, [
				'body'    => $body,
				'headers' => $headers,
			]);
			return new Response((string)$r->getBody(), $r->getStatusCode());
		} catch (RequestException $e) {
			$resp = $e->getResponse();
			$msg  = $resp ? (string)$resp->getBody() : $e->getMessage();

			throw new \Exception('Ошибка HTTP POST: ' . $msg, 0, $e);
		}
	}
}
