<?php

use Robokassa\Client\HttpClient;
use Robokassa\Exception\RobokassaException;
use Robokassa\Robokassa;

require_once __DIR__ . '/../vendor/autoload.php';

loadEnv(__DIR__ . '/../.env');

/**
 * Загружает переменные окружения из файла формата KEY=VALUE.
 *
 * @param string $path
 *
 * @return void
 */
function loadEnv(string $path): void {
	if (!is_file($path)) {
		return;
	}
	$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if ($lines === false) {
		throw new RuntimeException('Не удалось прочитать файл окружения: ' . $path);
	}
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
			continue;
		}
		[$name, $value] = array_map('trim', explode('=', $line, 2));
		if ($name === '') {
			continue;
		}
		if (!array_key_exists($name, $_ENV)) {
			$_ENV[$name] = $value;
		}
		if (getenv($name) === false) {
			putenv($name . '=' . $value);
		}
	}
}

/**
 * Создаёт клиента Robokassa на основе переменных окружения.
 *
 * @return Robokassa
 * @throws RobokassaException
 */
function createRobokassa(): Robokassa {
	return new Robokassa(
		[
			'login'     => $_ENV['ROBOKASSA_LOGIN'] ?? '',
			'password1' => $_ENV['ROBOKASSA_PASSWORD1'] ?? '',
			'password2' => $_ENV['ROBOKASSA_PASSWORD2'] ?? '',
			'hashType'  => 'md5',
		],
		new HttpClient()
	);
}