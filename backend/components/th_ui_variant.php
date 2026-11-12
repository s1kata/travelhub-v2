<?php
/**
 * Вариант публичного UI (shell v3 / marketplace-preview).
 * По умолчанию: классический сайт (ВЫКЛ).
 * Включить превью: ?ui=airbnb или env TH_UI_VARIANT=airbnb
 * Явно классика: ?ui=classic или TH_UI_VARIANT=classic
 */
declare(strict_types=1);

if (!function_exists('th_ui_variant_is_airbnb')) {
    function th_ui_variant_is_airbnb(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $q = isset($_GET['ui']) ? strtolower(trim((string) $_GET['ui'])) : '';
        if ($q === 'classic' || $q === 'default' || $q === 'legacy-ui') {
            $cached = false;
            return false;
        }
        if ($q === 'airbnb') {
            $cached = true;
            return true;
        }
        $env = strtolower(trim((string) (getenv('TH_UI_VARIANT') ?: ($_ENV['TH_UI_VARIANT'] ?? ''))));
        if ($env === 'classic' || $env === 'default' || $env === 'legacy-ui') {
            $cached = false;
            return false;
        }
        if ($env === 'airbnb') {
            $cached = true;
            return true;
        }
        $cached = false;
        return false;
    }
}

if (!function_exists('th_ui_body_class_attr')) {
    function th_ui_body_class_attr(): string
    {
        if (!th_ui_variant_is_airbnb()) {
            return '';
        }
        return ' th-ui-airbnb th-global-shell-v3';
    }
}

if (!function_exists('th_ui_html_class_attr')) {
    function th_ui_html_class_attr(): string
    {
        return th_ui_variant_is_airbnb() ? ' th-global-shell-v3 th-ui-airbnb' : '';
    }
}

if (!function_exists('th_ui_shell_stylesheets_tag')) {
    /**
     * Глобальная оболочка v3 — последним слоем поверх legacy CSS (все публичные страницы).
     */
    function th_ui_shell_stylesheets_tag(): void
    {
        if (!th_ui_variant_is_airbnb()) {
            return;
        }
        if (defined('TH_GLOBAL_SHELL_CSS')) {
            return;
        }
        define('TH_GLOBAL_SHELL_CSS', true);
        $root = dirname(__DIR__, 2) . '/frontend/css';
        foreach (['th-global-shell.css', 'th-global-shell-pages.css'] as $file) {
            $path = $root . '/' . $file;
            $ver = is_file($path) ? (string) filemtime($path) : '1';
            echo '<link rel="stylesheet" href="/frontend/css/' . htmlspecialchars($file, ENT_QUOTES, 'UTF-8')
                . '?v=' . htmlspecialchars($ver, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
    }
}

if (!function_exists('th_ui_shell_main_open')) {
    function th_ui_shell_main_open(): void
    {
        if (!th_ui_variant_is_airbnb() || defined('TH_SHELL_MAIN_OPEN')) {
            return;
        }
        define('TH_SHELL_MAIN_OPEN', true);
        echo '<main class="th-shell-main" id="th-shell-main">' . "\n";
    }
}

if (!function_exists('th_ui_shell_main_close')) {
    function th_ui_shell_main_close(): void
    {
        if (!defined('TH_SHELL_MAIN_OPEN') || defined('TH_SHELL_MAIN_CLOSED')) {
            return;
        }
        define('TH_SHELL_MAIN_CLOSED', true);
        echo '</main>' . "\n";
    }
}

if (!function_exists('th_ui_is_country_route')) {
    function th_ui_is_country_route(): bool
    {
        $sn = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        return (bool) preg_match('#/frontend/window/countries/[a-z0-9\-]+\.php$#i', $sn);
    }
}

if (!function_exists('th_ui_shell_stylesheets_fini')) {
    /**
     * Повторно подключает shell после page-specific CSS (каскад последним).
     */
    function th_ui_shell_stylesheets_fini(): void
    {
        if (!th_ui_variant_is_airbnb()) {
            return;
        }
        $root = dirname(__DIR__, 2) . '/frontend/css';
        $files = ['th-global-shell.css', 'th-global-shell-pages.css'];
        if (th_ui_is_country_route()) {
            $files[] = 'pages/country-shell.css';
        }
        foreach ($files as $file) {
            $path = $root . '/' . $file;
            $ver = is_file($path) ? (string) filemtime($path) : '1';
            $href = '/frontend/css/' . $file;
            echo '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8')
                . '?v=' . htmlspecialchars($ver, ENT_QUOTES, 'UTF-8') . '" data-th-shell-fini="1">' . "\n";
        }
    }
}

if (!function_exists('th_ui_airbnb_stylesheet_tag')) {
    /** @deprecated alias */
    function th_ui_airbnb_stylesheet_tag(): void
    {
        th_ui_shell_stylesheets_tag();
    }
}
