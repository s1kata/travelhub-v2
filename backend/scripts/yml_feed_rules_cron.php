<?php
/**
 * Крон: YML для Яндекс.Бизнеса по правилам из админки (таблица yandex_yml_feed_rules):
 * город вылета (Tourvisor departureId), страна (countryId), лимит туров (tour_limit).
 *
 * Рекомендуемое расписание — каждый день в полночь по времени сервера:
 *   0 0 * * * cd /path/to/website-main && /usr/bin/php backend/scripts/yml_feed_rules_cron.php >> data/yandex_yml_rules_cron.log 2>&1
 *
 * Внешний HTTP-крон (cron-job.org): задайте CRON_YML_SECRET в .env и вызывайте
 *   GET https://<SITE>/backend/api/cron-yml-feed.php?key=<CRON_YML_SECRET>
 *
 * Пересборка пишет снимок в data/yandex_rules_feed_snapshot.yml (основа для /feed.yml); по каждому departure_id — data/yandex_feed_dep_{id}.yml и при YML_FEED_DEPARTURE_ALIASES — data/yandex_feed_{slug}.yml; копии в export/ и корень — опционально только для главного.
 * Публичный URL /feed.yml только читает снимок; /feed-samara.yml и /feed-moscow.yml — export/feed-by-departure.php (см. .htaccess). Ручная пересборка: php backend/scripts/rebuild_feed.php или php rebuild_feed.php из корня репозитория.
 *
 * Блокировка: data/yandex_yml_rules_feed.lock — повторный запуск не выполнится, пока идёт предыдущий.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/yandex_yml_rules_runner.php';

if (!$pdo) {
    fwrite(STDERR, "No database connection.\n");
    exit(1);
}

$res = yandex_yml_rules_run($pdo, false);
if (!empty($res['ok'])) {
    $files = implode(', ', $res['files'] ?? []);
    $depLine = '';
    if (!empty($res['departure_feeds']) && is_array($res['departure_feeds'])) {
        $parts = [];
        foreach ($res['departure_feeds'] as $df) {
            if (!is_array($df)) {
                continue;
            }
            $parts[] = 'dep' . (int) ($df['departure_id'] ?? 0) . '=' . (int) ($df['offers_written'] ?? 0);
        }
        if ($parts !== []) {
            $depLine = ' departures[' . implode(',', $parts) . ']';
        }
    }
    if (!empty($res['stale_kept'])) {
        echo date('c') . ' stale_kept rules=' . ($res['rules_total'] ?? 0) . ' ok=' . ($res['rules_ok'] ?? 0)
            . ' offers_candidate=' . (int) ($res['offers_candidate'] ?? 0)
            . ' offers_kept≈' . (int) ($res['offers_written'] ?? 0) . $depLine . ' files=' . $files . "\n";
        exit(0);
    }
    echo date('c') . ' rules=' . ($res['rules_total'] ?? 0) . ' ok=' . ($res['rules_ok'] ?? 0) . ' offers=' . ($res['offers_written'] ?? 0) . $depLine . ' files=' . $files . "\n";
    exit(0);
}
if (!empty($res['lock_busy'])) {
    fwrite(STDERR, "Lock busy, skipped.\n");
    exit(0);
}
fwrite(STDERR, 'Failed: ' . implode('; ', $res['errors'] ?? []) . "\n");
exit(1);
