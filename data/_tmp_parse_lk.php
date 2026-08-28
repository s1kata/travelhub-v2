<?php
$raw = file_get_contents(__DIR__ . '/_tmp_lk_live.json');
$j = json_decode($raw, true);
echo 'success=' . (!empty($j['success']) ? '1' : '0') . ' count=' . count($j['data'] ?? []) . PHP_EOL;
foreach (array_slice($j['data'] ?? [], 0, 15) as $h) {
    $t = $h['tours'][0] ?? [];
    echo ($h['name'] ?? '?') . ' | price=' . ($t['price'] ?? '?')
        . ' total=' . ($t['totalPrice'] ?? '-')
        . ' rub=' . ($t['priceRub'] ?? '-')
        . ' adults=' . ($t['adults'] ?? '?')
        . ' nights=' . ($t['nights'] ?? '?') . PHP_EOL;
}
