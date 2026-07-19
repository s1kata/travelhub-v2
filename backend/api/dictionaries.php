<?php
/**
 * Справочники для формы поиска: города вылета и страны.
 * Сначала загружаются из Firestore (через прокси), при отсутствии — из API Tourvisor с сохранением в Firestore.
 * Один запрос возвращает и departures, и countries для быстрого заполнения селектов на главной.
 */
declare(strict_types=1);

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../components/tourvisor_proxy_url.php';
    if (!defined('TH_TOURVISOR_PROXY_EMBED')) {
        define('TH_TOURVISOR_PROXY_EMBED', true);
    }
    require_once __DIR__ . '/../components/api/tourvisor-proxy.php';
} catch (Throwable $e) {
    error_log('[dictionaries] bootstrap: ' . $e->getMessage());
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'departures' => [],
        'countries' => [],
        '_debug' => [
            'error' => 'Server error',
            'bootstrap' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$proxyBase = get_tourvisor_proxy_base_url();

$departures = [];
$countries = [];
$meals = [];
$debug = ['proxy_base' => $proxyBase, 'proxy_mode' => 'embed', 'dep1' => null, 'dep2' => null, 'countries1' => null, 'countries2' => null, 'meals' => null];

$fetchJson = static function (array $params) use (&$debug): ?array {
    if (!function_exists('tourvisor_proxy_dispatch_get')) {
        return ['_fetch_failed' => true, '_php_error' => 'tourvisor_proxy_dispatch_get not available'];
    }
    try {
        return tourvisor_proxy_dispatch_get($params);
    } catch (Throwable $e) {
        error_log('[dictionaries] proxy: ' . $e->getMessage());
        return [
            '_fetch_failed' => true,
            '_php_error' => $e->getMessage(),
        ];
    }
};

$formatDebug = static function (?array $d): string {
    if ($d === null) {
        return 'null';
    }
    if (!empty($d['_fetch_failed'])) {
        return 'fetch_failed: ' . ($d['_php_error'] ?? '');
    }
    if (!empty($d['_parse_failed'])) {
        return 'parse_failed';
    }
    if (!empty($d['success'])) {
        return 'ok,' . count($d['data'] ?? []) . ' items';
    }
    $msg = (string) ($d['error'] ?? '');
    if (!empty($d['error_detail'])) {
        $msg .= ' | ' . $d['error_detail'];
    }

    return 'success=false, ' . $msg;
};

// Города вылета: два запроса и объединение по id (как на фронте)
$dep1 = $fetchJson(['type' => 'departures']);
$dep2 = $fetchJson(['type' => 'departures', 'departureCountryId' => '1']);
$debug['dep1'] = $formatDebug($dep1);
$debug['dep2'] = $formatDebug($dep2);
$r1 = (!empty($dep1['_fetch_failed']) || !empty($dep1['_parse_failed'])) ? null : $dep1;
$r2 = (!empty($dep2['_fetch_failed']) || !empty($dep2['_parse_failed'])) ? null : $dep2;
$byId = [];
foreach ([$r1, $r2] as $j) {
    $list = (isset($j['success']) && $j['success'] && isset($j['data']) && is_array($j['data'])) ? $j['data'] : [];
    foreach ($list as $d) {
        if (isset($d['id'])) {
            $byId[$d['id']] = $d;
        }
    }
}
$departures = array_values($byId);

// Страны: два запроса и объединение
$cnt1 = $fetchJson(['type' => 'countries']);
$cnt2 = $fetchJson(['type' => 'countries', 'onlyCharter' => '1']);
$debug['countries1'] = $formatDebug($cnt1);
$debug['countries2'] = $formatDebug($cnt2);
$r1 = (!empty($cnt1['_fetch_failed']) || !empty($cnt1['_parse_failed'])) ? null : $cnt1;
$r2 = (!empty($cnt2['_fetch_failed']) || !empty($cnt2['_parse_failed'])) ? null : $cnt2;
$byId = [];
foreach ([$r1, $r2] as $j) {
    $list = (isset($j['success']) && $j['success'] && isset($j['data']) && is_array($j['data'])) ? $j['data'] : [];
    foreach ($list as $c) {
        if (isset($c['id'])) {
            $byId[$c['id']] = $c;
        }
    }
}
$countries = array_values($byId);

// Турция первой в списке (только если есть реальные данные)
$turkey = null;
foreach ($countries as $i => $c) {
    if (isset($c['name']) && (stripos($c['name'], 'Турци') !== false || (int) ($c['id'] ?? 0) === 12 || (int) ($c['id'] ?? 0) === 4)) {
        $turkey = $countries[$i];
        array_splice($countries, $i, 1);
        break;
    }
}
if ($turkey !== null) {
    array_unshift($countries, $turkey);
}

$mealsRaw = $fetchJson(['type' => 'meals']);
$debug['meals'] = $formatDebug($mealsRaw);
if (is_array($mealsRaw) && !empty($mealsRaw['success']) && is_array($mealsRaw['data'] ?? null)) {
    $meals = $mealsRaw['data'];
}

$out = [
    'success' => true,
    'departures' => $departures,
    'countries' => $countries,
    'meals' => $meals,
];
if (empty($departures) || empty($countries) || empty($meals)) {
    $out['_debug'] = $debug;
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
