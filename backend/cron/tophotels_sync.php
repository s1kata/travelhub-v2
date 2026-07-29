<?php
declare(strict_types=1);

/**
 * Cron: sync TopHotels ratings → data/tophotels/enrichment.json
 *
 * Usage:
 *   php backend/cron/tophotels_sync.php
 *   php backend/cron/tophotels_sync.php --import-matches=/path/to/matches.csv
 *
 * Until partner API arrives: TOPHOTELS_USE_FIXTURE=1 to exercise the pipeline.
 */

$root = dirname(__DIR__, 2);
require_once $root . '/backend/config/config.php';
require_once $root . '/backend/components/tophotels/bootstrap.php';

$pdo = $GLOBALS['pdo'] ?? null;
if (!$pdo instanceof PDO) {
    $pdo = null;
}

$importArg = null;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with((string) $arg, '--import-matches=')) {
        $importArg = substr((string) $arg, strlen('--import-matches='));
    }
}

if ($importArg !== null && $importArg !== '') {
    try {
        $res = th_tophotels_import_matches_csv($importArg, $pdo instanceof PDO ? $pdo : null);
        echo json_encode(['matches_import' => $res], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    } catch (Throwable $e) {
        fwrite(STDERR, '[tophotels] match import failed: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

if (!th_tophotels_enabled() && !th_tophotels_use_fixture() && !th_tophotels_client_configured()) {
    echo json_encode([
        'ok' => false,
        'skipped' => true,
        'message' => 'Set TOPHOTELS_ENABLED=1 and TOPHOTELS_RATINGS_URL (or TOPHOTELS_USE_FIXTURE=1 for sample data)',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
}

$result = th_tophotels_sync($pdo instanceof PDO ? $pdo : null);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
exit(!empty($result['ok']) ? 0 : 1);
