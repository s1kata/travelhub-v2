<?php
declare(strict_types=1);
require dirname(__DIR__) . '/backend/components/th_tour_price.php';

$t = ['price' => 66239, 'nights' => 10, 'adults' => 3];
$h = ['name' => 'EPIC UNAWATUNA', 'tours' => [$t]];
$ctx = th_tour_price_build_batch_context([$h], 3, true);
echo 'reasons: ' . implode(',', th_tour_price_garbage_reasons($t, $h, $ctx)) . PHP_EOL;
echo 'ppna: ' . round(66239 / 10 / 3) . PHP_EOL;
echo 'floor: ' . th_tour_price_ppna_absurd_floor(10) . PHP_EOL;
echo 'filtered: ' . count(th_tour_price_filter_hotels([$h], 3)) . PHP_EOL;
