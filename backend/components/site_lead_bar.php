<?php
/**
 * Глобальный мобильный бар: Звонок / MAX / Заявка.
 * Подключается из footer на всех страницах.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/contacts.php';
$thc = th_contacts();

if (!defined('TH_SITE_LEAD_CSS')) {
    define('TH_SITE_LEAD_CSS', true);
    $_th_sl_css = dirname(__DIR__, 2) . '/frontend/css/th-site-lead.css';
    $_th_sl_v = is_file($_th_sl_css) ? (string) filemtime($_th_sl_css) : '1';
    echo '<link rel="stylesheet" href="/frontend/css/th-site-lead.css?v=' . htmlspecialchars($_th_sl_v, ENT_QUOTES, 'UTF-8') . '">' . "\n";
}
if (!defined('TH_LEAD_CAPTURE_JS')) {
    define('TH_LEAD_CAPTURE_JS', true);
    $_th_lc = dirname(__DIR__, 2) . '/frontend/js/th-lead-capture.js';
    $_th_lc_v = is_file($_th_lc) ? (string) filemtime($_th_lc) : '1';
    echo '<script src="/frontend/js/th-lead-capture.js?v=' . htmlspecialchars($_th_lc_v, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
}
?>
<nav class="th-site-lead-bar" aria-label="Быстрая связь" data-th-site-lead-bar>
    <a class="th-site-lead-bar__btn th-site-lead-bar__btn--call"
       href="tel:<?php echo htmlspecialchars($thc['phone_tel'], ENT_QUOTES, 'UTF-8'); ?>"
       data-th-track="call_bar">
        <i class="fas fa-phone" aria-hidden="true"></i>
        <span>Позвонить</span>
    </a>
    <a class="th-site-lead-bar__btn th-site-lead-bar__btn--max"
       href="<?php echo htmlspecialchars($thc['max_url'], ENT_QUOTES, 'UTF-8'); ?>"
       target="_blank" rel="noopener noreferrer"
       data-th-track="max_bar">
        <span>MAX</span>
    </a>
    <button type="button" class="th-site-lead-bar__btn th-site-lead-bar__btn--lead"
            data-th-site-feedback
            data-th-track="lead_bar">
        <i class="fas fa-comment-dots" aria-hidden="true"></i>
        <span>Заявка</span>
    </button>
</nav>
<script>
(function () {
  try { document.body.classList.add('has-th-lead-bar'); } catch (e) {}
  document.addEventListener('click', function (e) {
    var el = e.target && e.target.closest ? e.target.closest('[data-th-track]') : null;
    if (!el || !window.THLeadCapture) return;
    var g = el.getAttribute('data-th-track');
    if (g === 'max_bar' || g === 'wa_bar') THLeadCapture.reachGoal('max_click');
    if (g === 'lead_bar') THLeadCapture.reachGoal('lead_bar_click');
  }, true);
})();
</script>
