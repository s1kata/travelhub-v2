<?php
/**
 * Разметка и атрибуты глобального shell v3 для публичных страниц.
 */
declare(strict_types=1);

require_once __DIR__ . '/th_ui_variant.php';

if (!function_exists('th_shell_html_open')) {
    /**
     * @param string $lang Код языка документа
     */
    function th_shell_html_open(string $lang = 'ru'): void
    {
        $langAttr = htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');
        $class = trim(th_ui_html_class_attr());
        if ($class !== '') {
            echo '<html lang="' . $langAttr . '" class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">' . "\n";
            return;
        }
        echo '<html lang="' . $langAttr . '">' . "\n";
    }
}

if (!function_exists('th_shell_body_open')) {
    /**
     * @param string $extraClasses Доп. классы страницы (promo, auth, …)
     */
    function th_shell_body_open(string $extraClasses = ''): void
    {
        $classes = trim($extraClasses . th_ui_body_class_attr());
        $classes = preg_replace('/\s+/u', ' ', $classes) ?? $classes;
        echo '<body class="' . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
}

if (!function_exists('th_shell_page_hero')) {
    /**
     * Унифицированный hero блока (внутренние страницы).
     *
     * @param array{title:string,subtitle?:string,badge?:string,modifier?:string} $opts
     */
    function th_shell_page_hero(array $opts): void
    {
        $title = $opts['title'] ?? '';
        $subtitle = $opts['subtitle'] ?? '';
        $badge = $opts['badge'] ?? '';
        $modifier = $opts['modifier'] ?? '';
        $modClass = $modifier !== '' ? ' ' . htmlspecialchars($modifier, ENT_QUOTES, 'UTF-8') : '';
        ?>
<section class="th-shell-page-hero<?php echo $modClass; ?>" aria-label="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="th-shell-page-hero__inner th-container">
        <?php if ($badge !== ''): ?>
            <p class="th-shell-page-hero__badge"><?php echo htmlspecialchars($badge, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <h1 class="th-shell-page-hero__title heading-font"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if ($subtitle !== ''): ?>
            <p class="th-shell-page-hero__lead"><?php echo htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </div>
</section>
        <?php
    }
}
