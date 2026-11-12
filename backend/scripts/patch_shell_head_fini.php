<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$files = array_merge(
    glob($root . '/frontend/window/**/*.php') ?: [],
    glob($root . '/frontend/window/*.php') ?: [],
    [$root . '/frontend/window/posts.php']
);
$needle = '<?php th_ui_shell_stylesheets_fini(); ?>';
$changed = 0;

foreach (array_unique($files) as $path) {
    if (!is_file($path)) {
        continue;
    }
    $code = file_get_contents($path);
    if ($code === false || strpos($code, $needle) !== false || strpos($code, 'design_system_head.php') === false) {
        continue;
    }
    if (!preg_match('/\s*<\/head>/i', $code)) {
        continue;
    }
    $orig = $code;
    $code = preg_replace(
        '/(\s*)<\/head>/i',
        '$1<?php th_ui_shell_stylesheets_fini(); ?>' . "\n" . '$1</head>',
        $code,
        1
    );
    if ($code !== null && $code !== $orig) {
        file_put_contents($path, $code);
        $changed++;
    }
}
echo "fini appended: {$changed}\n";
