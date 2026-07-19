<?php
/**
 * Конфигурация для приёма платежей через Альфа-Банк
 *
 * Все чувствительные данные берутся из .env (никогда не храните пароли в коде!).
 * Настройки применяются при подключении этого файла в create_payment.php и payment_callback.php.
 */

declare(strict_types=1);

// Подключаем загрузчик .env (если ещё не загружен)
$envPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
if (file_exists($envPath) && !function_exists('load_env_file')) {
    require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
}

// ============ РЕКВИЗИТЫ МАГАЗИНА (от Альфа-Банка) ============
// Логин и пароль выдаются банком при подключении интернет-эквайринга

$alfaUsername = getenv('PAYMENT_ALFA_MERCHANT') ?: ($_ENV['PAYMENT_ALFA_MERCHANT'] ?? '');
$alfaPassword = getenv('PAYMENT_ALFA_SECRET') ?: ($_ENV['PAYMENT_ALFA_SECRET'] ?? '');

// ============ РЕЖИМ РАБОТЫ ============
// true — тестовый стенд (для проверки без реальных списаний)
// false — боевой режим (реальные платежи)

$alfaTestMode = filter_var(
    getenv('PAYMENT_ALFA_TEST') ?: ($_ENV['PAYMENT_ALFA_TEST'] ?? '1'),
    FILTER_VALIDATE_BOOLEAN
);

// ============ URL ДЛЯ РЕДИРЕКТА ПОСЛЕ ОПЛАТЫ ============
// Куда банк перенаправляет пользователя после оплаты (обработчик — payment_callback.php)
// И куда мы редиректим пользователя после проверки статуса (страницы успеха/ошибки)

$siteUrl = rtrim(getenv('SITE_URL') ?: ($_ENV['SITE_URL'] ?? 'https://travelhub63.ru'), '/');

// URL, который мы передаём банку в returnUrl (банк редиректит сюда после оплаты)
define('ALFABANK_CALLBACK_URL', $siteUrl . '/backend/payment/payment_callback.php');
// Финальные страницы для пользователя (после проверки в callback)
define('ALFABANK_SUCCESS_URL', $siteUrl . '/frontend/window/payment-success.php');
define('ALFABANK_FAIL_URL', $siteUrl . '/frontend/window/payment-fail.php');

// URL для редиректа в мобильном приложении (банк редиректит сюда, страница перенаправляет в travelhub://)
define('ALFABANK_APP_REDIRECT_URL', $siteUrl . '/frontend/window/app-payment-redirect.php');
