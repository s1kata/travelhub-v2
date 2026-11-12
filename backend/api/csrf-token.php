<?php
/**
 * Текущий CSRF-токен сессии (для AJAX после истечения / page cache).
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/components/session_bootstrap.php';
require_once dirname(__DIR__) . '/components/security_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate');

echo json_encode([
    'success' => true,
    'token' => security_csrf_token(),
], JSON_UNESCAPED_UNICODE);
