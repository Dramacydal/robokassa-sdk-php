<?php
namespace Robokassa\Client;

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
