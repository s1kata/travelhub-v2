<?php
declare(strict_types=1);
require dirname(__DIR__) . '/backend/components/th_tour_price.php';

$raw = file_get_contents(__DIR__ . '/_tmp_lk_a1.json');
$j = json_decode($raw, true);
$hotels = $j['data'] ?? [];
$filtered = th_tour_price_filter_hotels($hotels, 1);
echo 'a1 raw=' . count($hotels) . ' filtered=' . count($filtered) . PHP_EOL;
foreach (array_slice($filtered, 0, 8) as $x) {
    $t = $x['tours'][0] ?? [];
    $p = th_tour_price_pick_num($t);
    $n = max(1, (int) ($t['nights'] ?? 1));
    echo ($x['name'] ?? '?') . " price=$p ppna=" . round($p / $n) . PHP_EOL;
}
