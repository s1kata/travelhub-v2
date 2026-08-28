<?php
declare(strict_types=1);
require dirname(__DIR__) . '/backend/components/th_tour_price.php';

$raw = file_get_contents(__DIR__ . '/_tmp_lk_a1.json');
$j = json_decode($raw, true);
$hotels = $j['data'] ?? [];
$ratio = 66239 / 41583;
foreach ($hotels as &$h) {
    foreach ($h['tours'] as &$t) {
        $t['adults'] = 3;
        $t['price'] = (int) round(((int) ($t['price'] ?? 0)) * $ratio);
        unset($t['totalPrice'], $t['priceRub']);
    }
}
unset($h, $t);
echo 'simulated hotels: ' . count($hotels) . PHP_EOL;
$filtered = th_tour_price_filter_hotels($hotels, 3);
echo 'after filter: ' . count($filtered) . PHP_EOL;
foreach ($filtered as $h) {
    $t = $h['tours'][0] ?? [];
    $p = th_tour_price_pick_num($t);
    $ppna = $p / (max(1, (int) ($t['nights'] ?? 1)) * 3);
    echo ($h['name'] ?? '?') . " price=$p ppna=" . round($ppna) . PHP_EOL;
}
