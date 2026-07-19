<?php
/**
 * Виджет онлайн-чата Umnico (фиксированный блок внизу справа).
 * Хэш виджета: UMNICO_WIDGET_HASH в .env или значение по умолчанию ниже.
 */
declare(strict_types=1);

if (!function_exists('th_umnico_chat_print_snippet')) {
    function th_umnico_chat_print_snippet(): void
    {
        static $printed = false;
        if ($printed) {
            return;
        }
        $printed = true;

        $hash = trim((string) (getenv('UMNICO_WIDGET_HASH') ?: ($_ENV['UMNICO_WIDGET_HASH'] ?? '27bf08fcb8e0b0555a7d2506e7589da6')));
        if ($hash === '' || $hash === '0') {
            return;
        }
        $hashJs = json_encode($hash, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        ?>
<!-- Umnico chat -->
<div id="th-umnico-anchor" class="th-umnico-widget" aria-hidden="true">
        <a
            href="https://umnico.com/?utm_source=widget&amp;utm_medium=online_chat&amp;utm_campaign=button"
            target="_blank"
            rel="noopener noreferrer"
            draggable="false"
            data-umnico-logo="true"
            class="th-umnico-widget__brand"
        >
            <img
                draggable="false"
                src="https://umnico.com/assets/index/umnico1.svg"
                alt=""
                width="45"
                height="9"
            />
       </a>
            <div data-umnico-loader="true" class="th-umnico-widget__loader">Loading</div>
            <script>
                document.umnicoWidgetHash = <?php echo $hashJs; ?>;
                var x = document.createElement('script');
                x.src = 'https://umnico.com/assets/widget-loader.js';
                x.type = 'text/javascript';
                x.charset = 'UTF-8';
                x.async = true;
                document.body.appendChild(x);
            </script>
</div>
<!-- /Umnico chat -->
<?php
    }
}
