<?php
/**
 * Яндекс.Метрика: единый числовой ID и HTML/JS-сниппет для всех публичных страниц.
 * Переопределение: переменная окружения YANDEX_METRIKA_ID (только цифры). Если не задана — 109291068.
 */
declare(strict_types=1);

if (!function_exists('th_yandex_metrika_counter_id')) {
    function th_yandex_metrika_counter_id(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $raw = trim((string) (getenv('YANDEX_METRIKA_ID') ?: ($_ENV['YANDEX_METRIKA_ID'] ?? '')));
        $digits = preg_replace('/\D/', '', $raw) ?? '';
        $cached = $digits !== '' ? $digits : '109291068';
        return $cached;
    }
}

if (!function_exists('th_yandex_metrika_print_snippet')) {
    function th_yandex_metrika_print_snippet(): void
    {
        $id = th_yandex_metrika_counter_id();
        if ($id === '' || $id === '0') {
            return;
        }
        $tagSrc = 'https://mc.yandex.ru/metrika/tag.js?id=' . rawurlencode($id);
        $watchSrc = 'https://mc.yandex.ru/watch/' . rawurlencode($id);
        ?>
<!-- Yandex.Metrika counter -->
<script>
(function(m,e,t,r,i,k,a){
    m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
    m[i].l=1*new Date();
    for (var j = 0; j < document.scripts.length; j++) { if (document.scripts[j].src === r) { return; } }
    k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
})(window, document, 'script', <?php echo json_encode($tagSrc, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES); ?>, 'ym');
window.dataLayer = window.dataLayer || [];
ym(<?php echo (int) $id; ?>, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
</script>
<noscript><div><img src="<?php echo htmlspecialchars($watchSrc, ENT_QUOTES, 'UTF-8'); ?>" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->
<?php
    }
}
