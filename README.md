# Robokassa SDK (PHP)

SDK для интеграции с платёжной системой **Robokassa** на PHP.  
Позволяет отправлять платёжные запросы (включая JWT), проверять статус платежа и получать доступные методы оплаты.

## 📦 Установка

Установите SDK через **Composer**:

```sh
composer require robokassa/sdk-php
````

## 🚀 Доступные методы

| Метод                                           | Описание                                                             | Документация                                                                                     |
| ----------------------------------------------- | -------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| `sendPaymentRequestJwt(array $params): string`  | ✅ Рекомендуемый способ. Создаёт ссылку на оплату через JWT-интерфейс | [docs.robokassa.ru/pay-interface/#jwt](https://docs.robokassa.ru/pay-interface/#jwt)             |
| `sendPaymentRequestCurl(array $params): string` | Создаёт ссылку на оплату через стандартный интерфейс                 | —                                                                                                |
| `getPaymentMethods(string $lang = 'ru'): array` | Получает список доступных методов оплаты                             | [docs.robokassa.ru/xml-interfaces/#currency](https://docs.robokassa.ru/xml-interfaces/#currency) |
| `opState(int $invoiceID): array`                | Получает статус оплаты по `InvoiceID`                                | [docs.robokassa.ru/xml-interfaces/#account](https://docs.robokassa.ru/xml-interfaces/#account)   |

## 📂 Примеры использования

Полные примеры использования SDK находятся в папке [`examples/`](./examples):

* [`sendPaymentRequestJwt.php`](./examples/sendPaymentRequestJwt.php) — создание ссылки на оплату через **JWT** (рекомендуется)
* [`sendPaymentRequestCurl.php`](./examples/sendPaymentRequestCurl.php) — создание ссылки на оплату через стандартный CURL-интерфейс
* [`getPaymentMethods.php`](./examples/getPaymentMethods.php) — получение доступных способов оплаты
* [`opState.php`](./examples/opState.php) — проверка статуса счёта

## 📌 Дополнительно

* Метод `sendPaymentRequestJwt()` — предпочтительный способ и рекомендуется к использованию.
* Официальная документация: [docs.robokassa.ru](https://docs.robokassa.ru/)

