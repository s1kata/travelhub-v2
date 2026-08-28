<?php
declare(strict_types=1);
require dirname(__DIR__) . '/backend/components/th_tour_price.php';

$raw = file_get_contents(__DIR__ . '/_tmp_lk_a1.json');
$hotels = json_decode($raw, true)['data'] ?? [];
$ctx = th_tour_price_build_batch_context($hotels, 1, true);
foreach ($hotels as $h) {
    $t = $h['tours'][0];
    $p = th_tour_price_pick_num($t);
    $ppna = $p / 10;
    $r = th_tour_price_garbage_reasons($t, $h, $ctx);
    echo ($h['name'] ?? '?') . " p=$p ppna=$ppna reasons=" . implode(',', $r) . PHP_EOL;
}
