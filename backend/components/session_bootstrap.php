<?php
/**
 * Параметры cookie сессии — до любого session_start() (в т.ч. отдача HTML из page cache).
 */
declare(strict_types=1);

if (defined('TH_SESSION_BOOTSTRAP')) {
    return;
}
define('TH_SESSION_BOOTSTRAP', true);

if (php_sapi_name() === 'cli') {
    return;
}

ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax');
$sessionSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if ($sessionSecure) {
    ini_set('session.cookie_secure', '1');
}
if (function_exists('session_set_cookie_params')) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $sessionSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
