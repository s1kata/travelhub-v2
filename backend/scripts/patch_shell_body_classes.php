<?php
/**
 * Однократно: суффикс shell-классов на <body> и class на <html lang="ru">.
 * php backend/scripts/patch_shell_body_classes.php
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$dirs = [
    $root . '/frontend/window',
    $root . '/frontend/window/countries',
    $root . '/frontend/window/offices',
    $root . '/frontend/window/hotels',
];
$files = [$root . '/frontend/index.php'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    foreach (glob($dir . '/*.php') ?: [] as $f) {
        $files[] = $f;
    }
}

$suffix = '<?php echo th_ui_body_class_attr(); ?>';
$htmlNeedle = '<html lang="ru">';
$htmlRepl = '<html lang="ru"<?php echo th_ui_html_class_attr() !== \'\' ? \' class="\' . trim(th_ui_html_class_attr()) . \'"\' : \'\'; ?>>';

$changed = 0;
foreach ($files as $path) {
    $code = file_get_contents($path);
    if ($code === false || strpos($code, 'th_ui_body_class_attr') !== false && preg_match('/<body[^>]*th_ui_body_class_attr/', $code)) {
        continue;
    }
    $orig = $code;
    if (strpos($code, $htmlNeedle) !== false && strpos($code, 'th_ui_html_class_attr') === false) {
        $code = str_replace($htmlNeedle, $htmlRepl, $code);
    }
    if (preg_match('/<body\s+class="([^"]*)"\s*>/', $code, $m) && strpos($code, 'th_ui_body_class_attr') === false) {
        $code = preg_replace(
            '/<body\s+class="([^"]*)"\s*>/',
            '<body class="$1' . $suffix . '">',
            $code,
            1
        );
    } elseif (preg_match('/<body>\s*\n/', $code) && strpos($code, 'th_ui_body_class_attr') === false) {
        $code = preg_replace('/<body>\s*\n/', '<body class="<?php echo trim(th_ui_body_class_attr()); ?>">' . "\n", $code, 1);
    }
    if ($code !== $orig) {
        file_put_contents($path, $code);
        $changed++;
        echo "patched: " . str_replace($root . DIRECTORY_SEPARATOR, '', $path) . "\n";
    }
}
echo "Done. Files changed: {$changed}\n";
