<?php
/**
 * YML / Яндекс: офферы из таблицы + правила фида по городам/странам (feed.yml).
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/yandex_feed_schema.php';
require_once __DIR__ . '/../components/yandex_yml_rules_schema.php';
require_once __DIR__ . '/../components/tourvisor_proxy_http_base.php';
require_once __DIR__ . '/../components/yandex_feed_sync.php';
require_once __DIR__ . '/../components/yandex_yml_rules_runner.php';
require_once __DIR__ . '/../components/th_feature_flags.php';

session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? null) !== 'admin') {
    header('Location: ../../frontend/window/login.php');
    exit;
}

if (!$pdo) {
    die('Ошибка подключения к БД');
}

$legacyOffersSync = yandex_feed_legacy_table_sync_enabled();

$message = '';
$messageType = '';

$ymlRulesInitError = '';
try {
    yandex_feed_ensure_table($pdo);
} catch (Throwable $e) {
    $message = 'Таблица yandex_feed_offers: ' . $e->getMessage();
    $messageType = 'error';
}
try {
    yandex_yml_rules_ensure_table($pdo);
} catch (Throwable $e) {
    $ymlRulesInitError = $e->getMessage();
}

/**
 * @return list<array{id: int, name: string}>
 */
function th_yml_rules_fetch_departures_for_admin(): array
{
    $base = rtrim(get_tourvisor_proxy_http_base_url(), '/');
    $sep = strpos($base, '?') !== false ? '&' : '?';
    $byId = [];
    foreach (['type=departures', 'type=departures&departureCountryId=1'] as $q) {
        $raw = yandex_feed_http_get($base . $sep . $q, 45);
        if ($raw === null) {
            continue;
        }
        $j = json_decode($raw, true);
        if (!is_array($j) || empty($j['success']) || !is_array($j['data'] ?? null)) {
            continue;
        }
        foreach ($j['data'] as $d) {
            if (!is_array($d) || !isset($d['id'])) {
                continue;
            }
            $id = (int) $d['id'];
            $name = trim((string) ($d['russianName'] ?? $d['name'] ?? ''));
            if ($id > 0 && $name !== '') {
                $byId[$id] = ['id' => $id, 'name' => $name];
            }
        }
    }
    $list = array_values($byId);
    usort($list, static function (array $a, array $b): int {
        return strcmp($a['name'], $b['name']);
    });

    return $list;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preAction = (string) ($_POST['action'] ?? '');
    $legacyPostActions = ['full_sync', 'stop_sync', 'toggle', 'disable_all_offers', 'regenerate_yml', 'cleanup_invalid_offers', 'cleanup_disabled_offers'];
    if (in_array($preAction, $legacyPostActions, true) && !$legacyOffersSync) {
        $message = 'Действие относится к таблице yandex_feed_offers (синхронизация Tourvisor для export/services_yml.php). Сейчас она отключена в .env: YANDEX_LEGACY_OFFERS_TABLE_SYNC=0. Для Яндекс.Бизнеса используйте блок «Фид по правилам», кнопку «Запустить парсер фида по правилам» и публичный URL /feed.yml.';
        $messageType = 'warning';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $message === '') {
    $action = $_POST['action'] ?? '';

    if ($action === 'full_sync') {
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        @ignore_user_abort(true);

        require_once __DIR__ . '/../components/yandex_feed_sync.php';
        try {
            $webLimit = yandex_feed_web_country_limit();
            $sync = yandex_feed_sync_from_tourvisor($pdo, ['country_limit' => $webLimit, 'honor_stop_file' => true]);
            require_once __DIR__ . '/../scripts/generate_yml.php';
            $gen = generate_services_yml($pdo);
            $errPart = '';
            if (!empty($sync['errors'])) {
                $errPart = ' Предупреждения: ' . implode('; ', array_slice($sync['errors'], 0, 5));
                if (count($sync['errors']) > 5) {
                    $errPart .= '…';
                }
            }
            $cp = (int) ($sync['countries_processed'] ?? 0);
            $ct = (int) ($sync['countries_total_list'] ?? 0);
            $cap = !empty($sync['countries_capped']);
            $geoPart = ' Направлений обработано: ' . $cp . ($cap && $ct > $cp ? ' из ' . $ct . ' (лимит веб-интерфейса; полный список — крон или CLI).' : '.');
            $stopPart = !empty($sync['stopped_by_user']) ? ' Синхронизация прервана по кнопке «Остановить».' : '';

            if (!empty($gen['ok'])) {
                $p = (int) ($gen['promo'] ?? 0);
                $s = (int) ($gen['services'] ?? 0);
                $message = 'Синхронизация Tourvisor: upsert ' . (int) ($sync['inserted'] ?? 0) . '.' . $geoPart . ' YML: акции ' . $p . ', услуги ' . $s . '.' . $stopPart . $errPart;
                $messageType = 'success';
            } else {
                $message = 'Синк выполнен, ошибка YML: ' . ($gen['error'] ?? '') . $geoPart . $stopPart . $errPart;
                $messageType = 'error';
            }
        } catch (Throwable $e) {
            $message = 'Ошибка полного синка: ' . $e->getMessage() . ' (файл: ' . basename($e->getFile()) . ':' . $e->getLine() . ')';
            $messageType = 'error';
        }
    } elseif ($action === 'stop_sync') {
        require_once __DIR__ . '/../components/yandex_feed_sync.php';
        yandex_feed_sync_request_stop();
        $message = 'Сигнал остановки записан. Синхронизация прервётся перед обработкой следующей страны (откройте эту страницу во второй вкладке и нажмите во время долгой синхронизации).';
        $messageType = 'success';
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $enabled = ((string) ($_POST['enabled'] ?? '0') === '1') ? 1 : 0;
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare('UPDATE yandex_feed_offers SET enabled = ? WHERE id = ?');
                $stmt->execute([$enabled, $id]);
                $message = $enabled ? 'Оффер включён в фид' : 'Оффер скрыт из фида';
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'Ошибка: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'disable_all_offers') {
        @ini_set('max_execution_time', '120');
        @set_time_limit(120);
        @ini_set('memory_limit', '256M');
        try {
            $stmt = $pdo->prepare('UPDATE yandex_feed_offers SET enabled = 0');
            $stmt->execute();
            $n = (int) $stmt->rowCount();
            require_once __DIR__ . '/../scripts/generate_yml.php';
            $r = generate_services_yml($pdo);
            if (!empty($r['ok'])) {
                $p = (int) ($r['promo'] ?? 0);
                $s = (int) ($r['services'] ?? 0);
                $message = 'Выключено строк в таблице: ' . $n . '. YML пересобран: акции ' . $p . ', услуги ' . $s . '.';
                $messageType = 'success';
            } else {
                $message = 'Офферы выключены (' . $n . ' строк). Ошибка YML: ' . ($r['error'] ?? '');
                $messageType = 'error';
            }
        } catch (Throwable $e) {
            $message = 'Ошибка: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'regenerate_yml') {
        @ini_set('max_execution_time', '120');
        @set_time_limit(120);
        @ini_set('memory_limit', '256M');
        try {
            require_once __DIR__ . '/../scripts/generate_yml.php';
            $r = generate_services_yml($pdo);
            if (!empty($r['ok'])) {
                $p = (int) ($r['promo'] ?? 0);
                $s = (int) ($r['services'] ?? 0);
                $message = "YML пересобран: акции {$p}, услуги {$s}";
                $messageType = 'success';
            } else {
                $message = 'Ошибка генерации YML: ' . ($r['error'] ?? '');
                $messageType = 'error';
            }
        } catch (Throwable $e) {
            $message = 'Ошибка YML: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'cleanup_invalid_offers') {
        require_once __DIR__ . '/../components/yandex_feed_sync.php';
        try {
            $deleted = yandex_feed_delete_invalid_offers($pdo);
            require_once __DIR__ . '/../scripts/generate_yml.php';
            $r = generate_services_yml($pdo);
            if (!empty($r['ok'])) {
                $p = (int) ($r['promo'] ?? 0);
                $s = (int) ($r['services'] ?? 0);
                $message = 'Удалено невалидных офферов: ' . $deleted . '. YML пересобран: акции ' . $p . ', услуги ' . $s . '.';
                $messageType = 'success';
            } else {
                $message = 'Удалено строк: ' . $deleted . '. Ошибка YML: ' . ($r['error'] ?? '');
                $messageType = 'error';
            }
        } catch (Throwable $e) {
            $message = 'Ошибка очистки: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'cleanup_disabled_offers') {
        require_once __DIR__ . '/../components/yandex_feed_sync.php';
        try {
            $deleted = yandex_feed_delete_disabled_offers($pdo);
            require_once __DIR__ . '/../scripts/generate_yml.php';
            $r = generate_services_yml($pdo);
            if (!empty($r['ok'])) {
                $p = (int) ($r['promo'] ?? 0);
                $s = (int) ($r['services'] ?? 0);
                $message = 'Удалено выключенных офферов: ' . $deleted . '. YML пересобран: акции ' . $p . ', услуги ' . $s . '.';
                $messageType = 'success';
            } else {
                $message = 'Удалено строк: ' . $deleted . '. Ошибка YML: ' . ($r['error'] ?? '');
                $messageType = 'error';
            }
        } catch (Throwable $e) {
            $message = 'Ошибка очистки: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'save_rule') {
        $depId = (int) ($_POST['source_departure_id'] ?? 0);
        $city = trim((string) ($_POST['source_city'] ?? ''));
        $countryId = (int) ($_POST['target_country_id'] ?? 0);
        $country = trim((string) ($_POST['target_country'] ?? ''));
        $lim = (int) ($_POST['tour_limit'] ?? 20);
        $lim = max(1, min(500, $lim));
        $enabled = ((string) ($_POST['enabled'] ?? '1') === '1') ? 1 : 0;
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $ruleId = (int) ($_POST['rule_id'] ?? 0);
        $starsRaw = isset($_POST['hotel_stars_filter']) ? trim((string) $_POST['hotel_stars_filter']) : '';
        $hotelStars = null;
        if ($starsRaw !== '' && in_array((int) $starsRaw, [3, 4, 5], true)) {
            $hotelStars = (int) $starsRaw;
        }

        if ($depId <= 0 || $countryId <= 0 || $city === '' || $country === '') {
            $message = 'Заполните город вылета, страну и названия.';
            $messageType = 'error';
        } else {
            try {
                if ($ruleId > 0) {
                    $stmt = $pdo->prepare('UPDATE yandex_yml_feed_rules SET source_departure_id=?, source_city=?, target_country_id=?, target_country=?, tour_limit=?, enabled=?, sort_order=?, hotel_stars_filter=? WHERE id=?');
                    $stmt->execute([$depId, $city, $countryId, $country, $lim, $enabled, $sort, $hotelStars, $ruleId]);
                    $message = 'Правило #' . $ruleId . ' обновлено.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO yandex_yml_feed_rules (source_departure_id, source_city, target_country_id, target_country, tour_limit, enabled, sort_order, hotel_stars_filter) VALUES (?,?,?,?,?,?,?,?)');
                    $stmt->execute([$depId, $city, $countryId, $country, $lim, $enabled, $sort, $hotelStars]);
                    $message = 'Правило добавлено (id ' . $pdo->lastInsertId() . ').';
                }
                $messageType = 'success';
            } catch (Throwable $e) {
                $message = 'Ошибка сохранения правила: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete_rule') {
        $ruleId = (int) ($_POST['rule_id'] ?? 0);
        if ($ruleId > 0) {
            try {
                $stmt = $pdo->prepare('DELETE FROM yandex_yml_feed_rules WHERE id = ?');
                $stmt->execute([$ruleId]);
                $message = 'Правило удалено.';
                $messageType = 'success';
            } catch (Throwable $e) {
                $message = 'Ошибка: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'toggle_rule') {
        $ruleId = (int) ($_POST['rule_id'] ?? 0);
        $en = ((string) ($_POST['enabled'] ?? '0') === '1') ? 1 : 0;
        if ($ruleId > 0) {
            try {
                $stmt = $pdo->prepare('UPDATE yandex_yml_feed_rules SET enabled = ? WHERE id = ?');
                $stmt->execute([$en, $ruleId]);
                $message = $en ? 'Правило включено.' : 'Правило выключено.';
                $messageType = 'success';
            } catch (Throwable $e) {
                $message = 'Ошибка: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'run_parser') {
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        require_once __DIR__ . '/../components/yandex_yml_rules_runner.php';
        try {
            $res = yandex_yml_rules_run($pdo, false);
            if (!empty($res['lock_busy'])) {
                $message = 'Парсер уже запущен (lock). Дождитесь завершения.';
                $messageType = 'error';
            } elseif (!empty($res['ok'])) {
                if (!empty($res['stale_kept'])) {
                    $message = 'Новая сборка не прошла порог публикации; снимок в data/ не обновлён (stale_kept). '
                        . 'Правил обработано: ' . ($res['rules_ok'] ?? 0) . ' из ' . ($res['rules_total'] ?? 0)
                        . ', в черновике офферов: ' . (int) ($res['offers_candidate'] ?? 0)
                        . ', в текущем файле ≈' . (int) ($res['offers_written'] ?? 0) . '.';
                    $depFeeds = $res['departure_feeds'] ?? [];
                    if ($depFeeds !== []) {
                        $bits = [];
                        foreach ($depFeeds as $df) {
                            if (!is_array($df)) {
                                continue;
                            }
                            $bits[] = 'dep ' . (int) ($df['departure_id'] ?? 0) . ' offers=' . (int) ($df['offers_written'] ?? 0)
                                . (!empty($df['stale_kept']) ? ' (stale)' : '');
                        }
                        if ($bits !== []) {
                            $message .= ' По вылетам: ' . implode('; ', $bits) . '.';
                        }
                    }
                    $messageType = 'success';
                } else {
                    $errTail = ($res['errors'] ?? []) !== [] ? (' Предупреждения: ' . implode('; ', array_slice($res['errors'], 0, 3))) : '';
                    $message = 'Фид по правилам: обработано правил ' . ($res['rules_ok'] ?? 0) . ' из ' . ($res['rules_total'] ?? 0) . ', офферов в YML: ' . ($res['offers_written'] ?? 0) . '.' . $errTail;
                    $depFeeds = $res['departure_feeds'] ?? [];
                    if ($depFeeds !== []) {
                        $bits = [];
                        foreach ($depFeeds as $df) {
                            if (!is_array($df)) {
                                continue;
                            }
                            $bits[] = 'dep ' . (int) ($df['departure_id'] ?? 0) . ' offers=' . (int) ($df['offers_written'] ?? 0);
                        }
                        if ($bits !== []) {
                            $message .= ' По вылетам: ' . implode('; ', $bits) . '.';
                        }
                    }
                    $messageType = 'success';
                }
            } else {
                $message = 'Ошибка: ' . implode('; ', $res['errors'] ?? ['unknown']);
                $messageType = 'error';
            }
        } catch (Throwable $e) {
            $message = 'Исключение: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$rows = [];
if ($legacyOffersSync) {
    try {
        $stmt = $pdo->query('
        SELECT id, tourvisor_tour_id, country_name, title, price, enabled, synced_at, offer_url
        FROM yandex_feed_offers
        ORDER BY country_name ASC, price ASC, id DESC
        LIMIT 2000
    ');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $rows = [];
    }
}

$feedUrl = rtrim((string) (getenv('SITE_URL') ?: ''), '/');
if ($feedUrl === '') {
    $feedUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'example.com');
}
$feedUrl = preg_replace('#/frontend/?$#i', '', $feedUrl);
$feedUrl = rtrim($feedUrl, '/') . '/export/services_yml.php';

$ymlDepartures = [];
try {
    $ymlDepartures = th_yml_rules_fetch_departures_for_admin();
} catch (Throwable $e) {
    $ymlDepartures = [];
}

$ymlFeedRules = [];
try {
    $ymlFeedRules = $pdo->query('SELECT * FROM yandex_yml_feed_rules ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $ymlFeedRules = [];
}

$siteBaseRules = rtrim((string) (getenv('SITE_URL') ?: ($_ENV['SITE_URL'] ?? '')), '/');
if ($siteBaseRules === '') {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $siteBaseRules = $proto . '://' . ($_SERVER['HTTP_HOST'] ?? '');
}
$rulesFeedUrl = preg_replace('#/frontend/?$#i', '', $siteBaseRules);
$rulesFeedUrl = rtrim((string) $rulesFeedUrl, '/') . '/feed.yml';
$rulesFeedUrlBase = (string) preg_replace('#/feed\.yml\z#', '', $rulesFeedUrl);
$rulesFeedUrlSamara = $rulesFeedUrlBase . '/feed-samara.yml';
$rulesFeedUrlMoscow = $rulesFeedUrlBase . '/feed-moscow.yml';
$ymlDepartureAliasesConfigured = yandex_yml_rules_departure_aliases_configured();
$ymlCityFeedStatusSamara = yandex_yml_rules_city_feed_status('samara');
$ymlCityFeedStatusMoscow = yandex_yml_rules_city_feed_status('moscow');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Фид Яндекс (YML) | Travel Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen">
    <header class="border-b bg-white">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
            <a href="admin.php" class="text-sky-600 font-semibold"><i class="fas fa-arrow-left"></i> Админка</a>
            <span class="text-sm text-slate-500">YML / Яндекс.Бизнес</span>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8 space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Фид Яндекс (YML)</h1>
            <p class="text-slate-600 mt-2 text-sm space-y-2">
                <span class="block"><strong>Услуги и акции</strong> (динамический YML из БД, если включён классический синк): <a class="text-sky-600 underline break-all" href="<?php echo htmlspecialchars($feedUrl); ?>"><?php echo htmlspecialchars($feedUrl); ?></a></span>
                <span class="block"><strong>Туры по правилам</strong> (основной режим при <code class="bg-slate-100 px-1 rounded">YANDEX_LEGACY_OFFERS_TABLE_SYNC=0</code>): <a class="text-indigo-600 font-medium underline break-all" href="<?php echo htmlspecialchars($rulesFeedUrl); ?>"><?php echo htmlspecialchars($rulesFeedUrl); ?></a> — снимок <code class="bg-slate-100 px-1 rounded">data/yandex_rules_feed_snapshot.yml</code>, пересборка по кнопке или крону.</span>
                <span class="block"><strong>По вылетам</strong> (при <code class="bg-slate-100 px-1 rounded">YML_FEED_DEPARTURE_ALIASES</code>, напр. <code class="bg-slate-100 px-1 rounded">samara:12,moscow:28</code>): <a class="text-indigo-600 font-medium underline break-all" href="<?php echo htmlspecialchars($rulesFeedUrlSamara); ?>"><?php echo htmlspecialchars($rulesFeedUrlSamara); ?></a>, <a class="text-indigo-600 font-medium underline break-all" href="<?php echo htmlspecialchars($rulesFeedUrlMoscow); ?>"><?php echo htmlspecialchars($rulesFeedUrlMoscow); ?></a> — снимки <code class="bg-slate-100 px-1 rounded">data/yandex_feed_samara.yml</code> / <code class="bg-slate-100 px-1 rounded">data/yandex_feed_moscow.yml</code> (копии с <code class="bg-slate-100 px-1 rounded">data/yandex_feed_dep_{id}.yml</code>).</span>
            </p>
            <?php if (!$legacyOffersSync): ?>
                <p class="mt-3 text-sm rounded-lg border border-amber-200 bg-amber-50 text-amber-900 px-4 py-3">
                    Включён <strong>только фид по правилам</strong> (<code class="bg-amber-100 px-1 rounded">YANDEX_LEGACY_OFFERS_TABLE_SYNC=0</code>): таблица <code class="bg-amber-100 px-1 rounded">yandex_feed_offers</code> и кнопки полного синка Tourvisor скрыты ниже.
                </p>
            <?php endif; ?>
        </div>

        <?php if ($message !== ''): ?>
            <div class="rounded-lg px-4 py-3 <?php echo $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : ($messageType === 'warning' ? 'bg-amber-50 text-amber-900 border border-amber-200' : 'bg-red-50 text-red-800 border border-red-200'); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($ymlRulesInitError !== ''): ?>
            <div class="rounded-lg px-4 py-3 bg-amber-50 text-amber-900 border border-amber-200 text-sm">
                Таблица правил YML: <?php echo htmlspecialchars($ymlRulesInitError); ?>
            </div>
        <?php endif; ?>

        <section id="yml-feed-rules" class="rounded-xl border border-indigo-200 bg-white shadow-sm p-5 md:p-6 space-y-5">
            <h2 class="text-lg font-semibold text-slate-900"><i class="fas fa-sliders-h text-indigo-500 mr-2"></i>Фид по правилам (Турвизор → <code class="text-sm bg-slate-100 px-1 rounded">/feed.yml</code>)</h2>
            <p class="text-slate-600 text-sm">
                Публичный URL для кабинета Яндекса: <strong class="break-all"><?php echo htmlspecialchars($rulesFeedUrl); ?></strong>.
                Отдельные URL по городам вылета: <strong class="break-all"><?php echo htmlspecialchars($rulesFeedUrlSamara); ?></strong>, <strong class="break-all"><?php echo htmlspecialchars($rulesFeedUrlMoscow); ?></strong> (нужен <code class="bg-slate-100 px-1 rounded">YML_FEED_DEPARTURE_ALIASES</code> в <code class="bg-slate-100 px-1 rounded">.env</code>).
                Кэш на диске (для выдачи): <code class="bg-slate-100 px-1 rounded">data/yandex_rules_feed_snapshot.yml</code>; при доступных правах дополнительно пишутся <code class="bg-slate-100 px-1 rounded">export/yandex_business_rules_feed.yml</code> и <code class="bg-slate-100 px-1 rounded">feed.yml</code> в корне.
                По каждому <code class="bg-slate-100 px-1 rounded">source_departure_id</code> из правил: <code class="bg-slate-100 px-1 rounded">data/yandex_feed_dep_{id}.yml</code>.
                Лог: <code class="bg-slate-100 px-1 rounded">data/yandex_yml_rules_feed.log</code>.
            </p>
            <p class="text-slate-600 text-sm">Крон CLI: <code class="block mt-1 bg-slate-900 text-green-400 p-3 rounded-lg text-xs overflow-x-auto">0 0 * * * cd /path/to/project &amp;&amp; php backend/scripts/yml_feed_rules_cron.php &gt;&gt; data/yandex_yml_rules_cron.log 2&gt;&amp;1</code></p>
            <p class="text-slate-600 text-sm">HTTP-крон: в <code class="bg-slate-100 px-1 rounded">.env</code> — <code class="bg-slate-100 px-1 rounded">CRON_YML_SECRET</code>, URL <code class="bg-slate-100 px-1 rounded break-all">/backend/api/cron-yml-feed.php?key=…</code></p>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 space-y-3">
                <h3 class="font-semibold text-slate-800">Статус фидов по городам</h3>
                <?php if (!$ymlDepartureAliasesConfigured): ?>
                    <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        В <code class="bg-amber-100 px-1 rounded">.env</code> не задан <code class="bg-amber-100 px-1 rounded">YML_FEED_DEPARTURE_ALIASES</code>
                        (например <code class="bg-amber-100 px-1 rounded">samara:12,moscow:28</code>).
                        Публичные URL <code class="bg-amber-100 px-1 rounded">/feed-samara.yml</code> и <code class="bg-amber-100 px-1 rounded">/feed-moscow.yml</code> не будут работать.
                    </p>
                <?php endif; ?>
                <div class="grid gap-3 md:grid-cols-2 text-sm">
                    <?php
                    foreach ([$ymlCityFeedStatusSamara, $ymlCityFeedStatusMoscow] as $citySt):
                        $ready = !empty($citySt['ready']);
                        $exists = !empty($citySt['exists']);
                        ?>
                    <div class="rounded-lg border <?php echo $ready ? 'border-green-200 bg-white' : 'border-slate-200 bg-white'; ?> p-3">
                        <div class="font-medium text-slate-900"><?php echo htmlspecialchars((string) $citySt['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <ul class="mt-2 space-y-1 text-slate-600">
                            <li>Снимок: <code class="text-xs bg-slate-100 px-1 rounded">data/yandex_feed_<?php echo htmlspecialchars((string) $citySt['slug'], ENT_QUOTES, 'UTF-8'); ?>.yml</code></li>
                            <li><?php echo $exists ? 'Файл есть' : 'Файл отсутствует'; ?><?php echo $exists ? ' (' . (int) $citySt['file_size'] . ' байт)' : ''; ?></li>
                            <li>Офферов: <strong><?php echo (int) $citySt['offers_count']; ?></strong></li>
                            <li>Дата каталога: <?php echo ($citySt['catalog_date'] ?? '') !== '' ? htmlspecialchars((string) $citySt['catalog_date'], ENT_QUOTES, 'UTF-8') : '—'; ?></li>
                            <li>Публичный URL: <a class="text-indigo-600 underline break-all" href="<?php echo htmlspecialchars($rulesFeedUrlBase . (string) $citySt['public_url_path'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($rulesFeedUrlBase . (string) $citySt['public_url_path'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                            <li class="<?php echo $ready ? 'text-green-700' : 'text-amber-700'; ?>">
                                <?php echo $ready ? 'Готов к выдаче' : 'Не готов (нужен крон или парсер; мин. 100 байт)'; ?>
                            </li>
                        </ul>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-slate-500">Пороги замены снимка: <code class="bg-slate-100 px-1 rounded">YML_RULES_MIN_OFFERS_SAMARA</code>,
                    <code class="bg-slate-100 px-1 rounded">YML_RULES_MIN_OFFERS_MOSCOW</code> (по умолчанию 5). Лог: <code class="bg-slate-100 px-1 rounded">stale_kept_samara</code> / <code class="bg-slate-100 px-1 rounded">stale_kept_moscow</code>.</p>
            </div>

            <form method="post" class="space-y-2 border-t border-slate-100 pt-4">
                <input type="hidden" name="action" value="run_parser">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-500 text-white font-semibold shadow hover:opacity-95">
                    <i class="fas fa-play"></i> Запустить парсер фида по правилам
                </button>
                <span class="text-xs text-slate-500 block">Повторный запуск во время работы блокируется lock-файлом.</span>
            </form>

            <div class="border-t border-slate-100 pt-4 space-y-4">
                <h3 class="font-semibold text-slate-800">Новое правило</h3>
                <form method="post" class="grid gap-4 md:grid-cols-2">
                    <input type="hidden" name="action" value="save_rule">
                    <input type="hidden" name="rule_id" value="0">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Город вылета</label>
                        <select name="source_departure_id" id="f-departure" required class="w-full border border-slate-200 rounded-lg px-3 py-2">
                            <option value="">— выберите —</option>
                            <?php foreach ($ymlDepartures as $d): ?>
                            <option value="<?php echo (int) $d['id']; ?>" data-name="<?php echo htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="source_city" id="f-source-city" value="">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Страна назначения</label>
                        <select name="target_country_id" id="f-country" required class="w-full border border-slate-200 rounded-lg px-3 py-2">
                            <option value="">— сначала выберите город —</option>
                        </select>
                        <input type="hidden" name="target_country" id="f-target-country-name" value="">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Звёздность отеля в фиде (необязательно)</label>
                        <select name="hotel_stars_filter" class="w-full border border-slate-200 rounded-lg px-3 py-2">
                            <option value="">— без фильтра —</option>
                            <option value="3">3★</option>
                            <option value="4">4★</option>
                            <option value="5">5★</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">Если выбрано — в YML попадут только отели этой категории. Отключение логики: <code class="bg-slate-100 px-1 rounded">TH_FEATURE_YML_RULE_HOTEL_STARS_FILTER=0</code> в .env.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Лимит туров (1–500)</label>
                        <input type="number" name="tour_limit" value="20" min="1" max="500" class="w-full border border-slate-200 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Порядок сортировки</label>
                        <input type="number" name="sort_order" value="0" class="w-full border border-slate-200 rounded-lg px-3 py-2">
                    </div>
                    <div class="flex items-center gap-2 md:col-span-2">
                        <input type="checkbox" name="enabled" value="1" checked id="f-en" class="rounded border-slate-300">
                        <label for="f-en" class="text-sm text-slate-700">Активно</label>
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-900">Сохранить правило</button>
                    </div>
                </form>
            </div>

            <div class="border-t border-slate-100 pt-4">
                <h3 class="font-semibold text-slate-800 mb-3">Правила парса туров (таблица <code class="text-xs bg-slate-100 px-1 rounded">yandex_yml_feed_rules</code>)</h3>
                <?php if ($ymlFeedRules === []): ?>
                    <p class="text-slate-500 text-sm">Пока нет правил. Добавьте строку ниже и нажмите «Запустить парсер фида по правилам».</p>
                <?php else: ?>
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-100 text-left text-slate-600">
                                <tr>
                                    <th class="px-3 py-2">Вкл</th>
                                    <th class="px-3 py-2">ID</th>
                                    <th class="px-3 py-2">Город вылета</th>
                                    <th class="px-3 py-2">Страна</th>
                                    <th class="px-3 py-2">Лимит туров</th>
                                    <th class="px-3 py-2">★</th>
                                    <th class="px-3 py-2">Sort</th>
                                    <th class="px-3 py-2">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ymlFeedRules as $r): ?>
                                <tr class="border-t border-slate-100 hover:bg-slate-50">
                                    <td class="px-3 py-2 align-middle">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?php echo (int) $r['enabled'] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'; ?>">
                                            <?php echo (int) $r['enabled'] ? 'да' : 'нет'; ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 align-middle font-mono text-xs"><?php echo (int) $r['id']; ?></td>
                                    <td class="px-3 py-2 align-middle">
                                        <div class="font-medium text-slate-900"><?php echo htmlspecialchars((string) $r['source_city'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="text-xs text-slate-500">departureId <?php echo (int) $r['source_departure_id']; ?></div>
                                    </td>
                                    <td class="px-3 py-2 align-middle">
                                        <div><?php echo htmlspecialchars((string) $r['target_country'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="text-xs text-slate-500">countryId <?php echo (int) $r['target_country_id']; ?></div>
                                    </td>
                                    <td class="px-3 py-2 align-middle whitespace-nowrap"><?php echo (int) $r['tour_limit']; ?></td>
                                    <td class="px-3 py-2 align-middle whitespace-nowrap text-slate-600"><?php
                                        $sf = isset($r['hotel_stars_filter']) ? (int) $r['hotel_stars_filter'] : 0;
                                        echo ($sf >= 3 && $sf <= 5) ? $sf . '★' : '—';
                                        ?></td>
                                    <td class="px-3 py-2 align-middle whitespace-nowrap"><?php echo (int) $r['sort_order']; ?></td>
                                    <td class="px-3 py-2 align-middle">
                                        <div class="flex flex-wrap gap-2">
                                            <form method="post" class="inline">
                                                <input type="hidden" name="action" value="toggle_rule">
                                                <input type="hidden" name="rule_id" value="<?php echo (int) $r['id']; ?>">
                                                <input type="hidden" name="enabled" value="<?php echo (int) $r['enabled'] ? '0' : '1'; ?>">
                                                <button type="submit" class="text-xs px-3 py-1 rounded-lg border <?php echo (int) $r['enabled'] ? 'border-amber-200 text-amber-800 hover:bg-amber-50' : 'border-emerald-200 text-emerald-800 hover:bg-emerald-50'; ?>">
                                                    <?php echo (int) $r['enabled'] ? 'Выключить' : 'Включить'; ?>
                                                </button>
                                            </form>
                                            <form method="post" class="inline" onsubmit="return confirm('Удалить правило #<?php echo (int) $r['id']; ?>?');">
                                                <input type="hidden" name="action" value="delete_rule">
                                                <input type="hidden" name="rule_id" value="<?php echo (int) $r['id']; ?>">
                                                <button type="submit" class="text-xs px-3 py-1 rounded-lg border border-red-200 text-red-700 hover:bg-red-50">Удалить</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($legacyOffersSync): ?>
        <h2 class="text-xl font-semibold text-slate-900 pt-2">Акционные офферы (таблица yandex_feed_offers)</h2>
        <p class="text-slate-600 text-sm">Данные при акционном поиске на сайте, кроне (<code class="bg-slate-100 px-1 rounded">promo_tours_refresh.php</code> / <code class="bg-slate-100 px-1 rounded">sync_yandex_feed_offers.php</code>) и по кнопке ниже. Чтобы скрыть этот блок и отключить синк, в <code class="bg-slate-100 px-1 rounded">.env</code>: <code class="bg-slate-100 px-1 rounded">YANDEX_LEGACY_OFFERS_TABLE_SYNC=0</code>.</p>

        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <strong>Остановка синхронизации:</strong> пока идёт долгая «Синхронизация Tourvisor + YML», откройте эту же страницу <strong>во второй вкладке</strong> и нажмите «Остановить» — цикл прервётся после текущего направления.
        </div>

        <div class="flex flex-wrap gap-3 items-center">
            <form method="post" class="inline" onsubmit="return confirm('Запустить синхронизацию (по умолчанию часть направлений за раз, чтобы не оборвало по таймауту)?');">
                <input type="hidden" name="action" value="full_sync">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 text-white px-4 py-2 text-sm font-medium hover:bg-indigo-700">
                    <i class="fas fa-cloud-download-alt"></i> Синхронизация Tourvisor + YML
                </button>
            </form>
            <form method="post" class="inline" onsubmit="return confirm('Отправить сигнал остановки? (нужна вторая вкладка с этой страницей во время синка.)');">
                <input type="hidden" name="action" value="stop_sync">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-amber-300 bg-white text-amber-900 px-4 py-2 text-sm font-medium hover:bg-amber-50">
                    <i class="fas fa-stop-circle"></i> Остановить синхронизацию
                </button>
            </form>
            <form method="post" class="inline">
                <input type="hidden" name="action" value="regenerate_yml">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-sky-200 bg-white text-sky-700 px-4 py-2 text-sm font-medium hover:bg-sky-50">
                    <i class="fas fa-sync"></i> Только пересобрать YML
                </button>
            </form>
            <form method="post" class="inline" onsubmit="return confirm('Выключить переключатель «Вкл» у всех офферов в таблице? Акционные позиции исчезнут из YML, пока вы не включите нужные или не придёт синк (новые строки снова с «Вкл»). Затем YML пересоберётся.');">
                <input type="hidden" name="action" value="disable_all_offers">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white text-slate-800 px-4 py-2 text-sm font-medium hover:bg-slate-50">
                    <i class="fas fa-toggle-off"></i> Выключить все офферы
                </button>
            </form>
            <form method="post" class="inline" onsubmit="return confirm('Удалить из БД строки без названия, без URL, без tour ID или с ценой ≤ 0? Затем YML пересоберётся.');">
                <input type="hidden" name="action" value="cleanup_invalid_offers">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-white text-rose-800 px-4 py-2 text-sm font-medium hover:bg-rose-50">
                    <i class="fas fa-broom"></i> Удалить невалидные офферы
                </button>
            </form>
            <form method="post" class="inline" onsubmit="return confirm('Удалить из БД все офферы с выключенным «Вкл»? Их tour-id попадёт в список исключений — синк и поиск не вернут эти туры, пока вы не удалите строки из таблицы yandex_feed_suppressed_tour_ids (SQL в БД). Затем пересоберётся YML.');">
                <input type="hidden" name="action" value="cleanup_disabled_offers">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-rose-700 text-white px-4 py-2 text-sm font-medium hover:bg-rose-800">
                    <i class="fas fa-trash-alt"></i> Удалить выключенные из БД
                </button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-left text-slate-600">
                    <tr>
                        <th class="px-3 py-2">Вкл</th>
                        <th class="px-3 py-2">Страна</th>
                        <th class="px-3 py-2">Название</th>
                        <th class="px-3 py-2">Цена</th>
                        <th class="px-3 py-2">Tour ID</th>
                        <th class="px-3 py-2">Обновлено</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-slate-500">Нет данных. Нажмите «Синхронизация Tourvisor + YML», крон или выполните акционный поиск на сайте.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr class="border-t border-slate-100 hover:bg-slate-50">
                                <td class="px-3 py-2 align-top">
                                    <form method="post" class="inline">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
                                        <input type="hidden" name="enabled" value="0">
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="enabled" value="1" class="rounded" <?php echo !empty($r['enabled']) ? 'checked' : ''; ?> onchange="this.form.submit()">
                                        </label>
                                    </form>
                                </td>
                                <td class="px-3 py-2 align-top whitespace-nowrap"><?php echo htmlspecialchars((string) ($r['country_name'] ?? '')); ?></td>
                                <td class="px-3 py-2 align-top">
                                    <div class="font-medium text-slate-900"><?php echo htmlspecialchars(mb_substr((string) ($r['title'] ?? ''), 0, 120)); ?></div>
                                    <?php if (!empty($r['offer_url'])): ?>
                                        <a href="<?php echo htmlspecialchars((string) $r['offer_url']); ?>" target="_blank" rel="noopener" class="text-xs text-sky-600 hover:underline break-all">Страница тура</a>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2 align-top whitespace-nowrap"><?php echo number_format((float) ($r['price'] ?? 0), 0, '.', ' '); ?> ₽</td>
                                <td class="px-3 py-2 align-top font-mono text-xs"><?php echo htmlspecialchars((string) ($r['tourvisor_tour_id'] ?? '')); ?></td>
                                <td class="px-3 py-2 align-top text-xs text-slate-500"><?php echo htmlspecialchars((string) ($r['synced_at'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>
    <script>
    (function() {
        var proxy = <?php echo json_encode(get_tourvisor_proxy_http_base_url(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        var dep = document.getElementById('f-departure');
        var country = document.getElementById('f-country');
        if (!dep || !country) return;
        var hidCity = document.getElementById('f-source-city');
        var hidCountryName = document.getElementById('f-target-country-name');

        function sep(u) { return u.indexOf('?') >= 0 ? '&' : '?'; }

        function fillCountries(depId) {
            country.innerHTML = '<option value="">Загрузка…</option>';
            hidCountryName.value = '';
            fetch(proxy + sep(proxy) + 'type=countries&departureId=' + encodeURIComponent(depId), { cache: 'no-store' })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    country.innerHTML = '';
                    if (!j || !j.success || !Array.isArray(j.data)) {
                        country.innerHTML = '<option value="">Нет данных</option>';
                        return;
                    }
                    var opt0 = document.createElement('option');
                    opt0.value = '';
                    opt0.textContent = '— выберите страну —';
                    country.appendChild(opt0);
                    j.data.forEach(function(c) {
                        if (!c || c.id == null) return;
                        var o = document.createElement('option');
                        o.value = String(c.id);
                        o.textContent = (c.russianName || c.name || ('id ' + c.id));
                        o.setAttribute('data-name', o.textContent);
                        country.appendChild(o);
                    });
                })
                .catch(function() {
                    country.innerHTML = '<option value="">Ошибка загрузки</option>';
                });
        }

        dep.addEventListener('change', function() {
            var opt = dep.options[dep.selectedIndex];
            hidCity.value = opt ? (opt.getAttribute('data-name') || opt.textContent || '').trim() : '';
            if (dep.value) fillCountries(dep.value);
            else {
                country.innerHTML = '<option value="">— сначала выберите город —</option>';
            }
        });

        country.addEventListener('change', function() {
            var opt = country.options[country.selectedIndex];
            hidCountryName.value = opt ? (opt.getAttribute('data-name') || opt.textContent || '').trim() : '';
        });
    })();
    </script>
</body>
</html>
