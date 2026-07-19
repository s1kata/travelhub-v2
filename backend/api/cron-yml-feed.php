<?php
declare(strict_types=1);
/**
 * HTTP-вызов пересборки feed.yml (Яндекс.Бизнес по правилам yandex_yml_feed_rules).
 * Для cron-job.org и аналогов: GET https://<сайт>/backend/api/cron-yml-feed.php?key=<CRON_YML_SECRET>
 *
 * Секрет обязателен (иначе 503). Неверный ключ → 403.
 * Логика совпадает с backend/scripts/yml_feed_rules_cron.php (yandex_yml_rules_run).
 */
header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/components/yandex_yml_rules_runner.php';

/**
 * Ключ из query, body или заголовка X-Cron-Key.
 */
function cron_yml_feed_read_key(): string
{
    $k = '';
    if (isset($_GET['key'])) {
        $k = (string) $_GET['key'];
    } elseif (isset($_POST['key'])) {
        $k = (string) $_POST['key'];
    }
    $k = trim($k);
    if ($k === '' && !empty($_SERVER['HTTP_X_CRON_KEY'])) {
        $k = trim((string) $_SERVER['HTTP_X_CRON_KEY']);
    }

    return $k;
}

$secret = trim((string) (getenv('CRON_YML_SECRET') ?: ($_ENV['CRON_YML_SECRET'] ?? '')));
if ($secret === '') {
    http_response_code(503);
    echo "CRON_YML_SECRET is not set in .env\n";

    exit;
}

$key = cron_yml_feed_read_key();
if ($key === '' || !hash_equals($secret, $key)) {
    http_response_code(403);
    echo "Forbidden\n";

    exit;
}

if (!$pdo) {
    http_response_code(500);
    echo "No database connection\n";

    exit;
}

$res = yandex_yml_rules_run($pdo, false);
if (!empty($res['ok'])) {
    if (!empty($res['stale_kept'])) {
        http_response_code(200);
        echo 'OK stale_kept=1 rules_total=' . (int) ($res['rules_total'] ?? 0)
            . ' rules_ok=' . (int) ($res['rules_ok'] ?? 0)
            . ' offers_candidate=' . (int) ($res['offers_candidate'] ?? 0)
            . ' offers_kept≈' . (int) ($res['offers_written'] ?? 0) . "\n";

        exit;
    }
    http_response_code(200);
    echo 'OK rules_total=' . (int) ($res['rules_total'] ?? 0)
        . ' rules_ok=' . (int) ($res['rules_ok'] ?? 0)
        . ' offers_written=' . (int) ($res['offers_written'] ?? 0) . "\n";

    exit;
}
if (!empty($res['lock_busy'])) {
    http_response_code(200);
    echo "SKIP lock_busy\n";

    exit;
}
http_response_code(500);
echo 'FAIL ' . implode('; ', $res['errors'] ?? []) . "\n";

exit;
