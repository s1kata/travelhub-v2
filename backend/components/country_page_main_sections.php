<?php
/**
 * Общая вёрстка страниц стран: hero, акции, фотогалерея + CTA, кратко о стране, поиск TourVisor.
 *
 * Ожидает: $countryData (name, slug, flag, description, bio, images, highlights,
 * bestTime, currency, language, visa), функцию getCountryCode($slug) в области видимости.
 */
if (empty($countryData) || empty($countryData['name'])) {
    return;
}
$countrySlug = $countryData['slug'] ?? '';
$heroImg = $countryData['images'][0] ?? '';
$codeMobile = function_exists('getCountryCode')
    ? htmlspecialchars(getCountryCode($countrySlug), ENT_QUOTES, 'UTF-8')
    : htmlspecialchars(strtoupper(substr((string) $countrySlug, 0, 2)), ENT_QUOTES, 'UTF-8');

/** Ссылка на страницу акций с предвыбранной страной (как в promotions.php). */
$country_promo_listing_url = '/frontend/window/promotions.php';
if (!empty($countrySlug)) {
    $promoMapFile = __DIR__ . '/../config/country_promo_tourvisor_map.php';
    $promoPack = is_file($promoMapFile) ? require $promoMapFile : [];
    $slugToId = $promoPack['slug_to_id'] ?? [];
    $tvCid = (int) ($slugToId[$countrySlug] ?? 0);
    if ($tvCid > 0) {
        // Город вылета берётся из localStorage на promotions.php — не фиксируем PROMO_* из .env в ссылке.
        $country_promo_listing_url = '/frontend/window/promotions.php?countryId=' . $tvCid
            . '&countryName=' . rawurlencode((string) ($countryData['name'] ?? ''));
    }
}

$bioSnippet = '';
if (!empty($countryData['bio'])) {
    $parts = preg_split("/\r\n|\n|\r/", trim((string) $countryData['bio']));
    $buf = '';
    foreach ($parts as $line) {
        $line = trim($line);
        if ($line === '') {
            if ($buf !== '') {
                break;
            }
            continue;
        }
        $buf .= ($buf === '' ? '' : ' ') . $line;
        $maxLen = 420;
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($buf, 'UTF-8') > $maxLen) {
                $buf = mb_substr($buf, 0, $maxLen - 1, 'UTF-8') . '…';
                break;
            }
        } elseif (strlen($buf) > $maxLen) {
            $buf = substr($buf, 0, $maxLen - 3) . '…';
            break;
        }
    }
    $bioSnippet = $buf;
}

if (!function_exists('th_country_page_image_src')) {
    /**
     * Абсолютный URL картинки для src (пути ../img/... из шаблонов стран).
     */
    function th_country_page_image_src(string $path): string
    {
        $p = trim($path);
        if ($p === '') {
            return '';
        }
        if (preg_match('#\Ahttps?://#i', $p)) {
            return htmlspecialchars($p, ENT_QUOTES, 'UTF-8');
        }
        if (isset($p[0]) && $p[0] === '/') {
            return htmlspecialchars($p, ENT_QUOTES, 'UTF-8');
        }
        if (strncmp($p, '../', 3) === 0) {
            return htmlspecialchars('/frontend/window/' . substr($p, 3), ENT_QUOTES, 'UTF-8');
        }

        return htmlspecialchars('/frontend/window/' . ltrim($p, '/'), ENT_QUOTES, 'UTF-8');
    }
}
if (!defined('TH_COUNTRY_SHELL_CSS_LINKED')) {
    define('TH_COUNTRY_SHELL_CSS_LINKED', true);
    if (!defined('TH_ASSET_VERSION')) {
        require_once dirname(__DIR__) . '/config/asset_version.php';
    }
    $thCountryShellCssVer = defined('TH_ASSET_VERSION') ? TH_ASSET_VERSION : '1';
    echo '<link rel="stylesheet" href="/frontend/css/pages/country-shell.css?v=' . htmlspecialchars($thCountryShellCssVer, ENT_QUOTES, 'UTF-8') . '">';
}
?>
<div class="th-country-page">
    <section class="th-country-hero relative flex items-center overflow-hidden text-white">
        <div class="absolute inset-0 z-0">
            <?php if ($heroImg !== ''): ?>
            <img src="<?php echo th_country_page_image_src($heroImg); ?>"
                 alt="<?php echo htmlspecialchars($countryData['name'] . ' — фон', ENT_QUOTES, 'UTF-8'); ?>"
                 class="absolute inset-0 w-full h-full object-cover scale-105"
                 loading="eager"
                 decoding="async"
                 aria-hidden="true">
            <?php else: ?>
            <div class="absolute inset-0 bg-gradient-to-br from-slate-800 to-slate-900" aria-hidden="true"></div>
            <?php endif; ?>
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/95 via-slate-900/78 to-slate-900/52 th-country-hero__overlay" aria-hidden="true"></div>
        </div>
        <div class="th-container mx-auto px-6 relative z-10 w-full">
            <div class="max-w-4xl mx-auto text-center space-y-5">
                <div class="inline-flex items-center gap-3 mb-1 th-country-hero__badge-row">
                    <?php /* Один код страны (ISO): эмодзи-флаг на Windows/Edge рисуется как второй «ID» */ ?>
                    <div class="th-country-hero__iso" aria-label="<?php echo $codeMobile; ?>">
                        <span class="th-country-hero__iso-code"><?php echo $codeMobile; ?></span>
                    </div>
                </div>
                <h1 class="heading-font text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold text-white leading-tight drop-shadow-md">
                    <?php echo htmlspecialchars($countryData['name']); ?>
                </h1>
                <?php if (!empty($countryData['description'])): ?>
                <p class="text-lg md:text-xl text-white/90 max-w-3xl mx-auto leading-relaxed drop-shadow">
                    <?php echo htmlspecialchars($countryData['description']); ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="th-country-page__actions" aria-label="Быстрые действия">
        <div class="th-country-page__actions-inner">
            <a href="<?php echo htmlspecialchars($country_promo_listing_url, ENT_QUOTES, 'UTF-8'); ?>"
               data-th-promo-cta="1"
               data-th-country-slug="<?php echo htmlspecialchars((string) $countrySlug, ENT_QUOTES, 'UTF-8'); ?>"
               data-th-country-name="<?php echo htmlspecialchars((string) ($countryData['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
               class="th-country-page__btn th-country-page__btn--primary">
                <i class="fas fa-fire-alt" aria-hidden="true"></i>
                Акции и туры
            </a>
            <a href="/frontend/window/offices.php" class="th-country-page__btn th-country-page__btn--secondary">
                <i class="fas fa-building" aria-hidden="true"></i>
                Офисы
            </a>
            <button type="button"
                    id="th-country-page-lead-btn"
                    data-th-country-name="<?php echo htmlspecialchars((string) ($countryData['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                    class="th-country-page__btn th-country-page__btn--ghost th-country-page__action--full">
                <i class="fas fa-headset" aria-hidden="true"></i>
                Оставить заявку
            </button>
        </div>
    </section>

    <?php include __DIR__ . '/country_promo_tours.php'; ?>

    <?php
    $hasFacts = $bioSnippet !== ''
        || !empty($countryData['bestTime'])
        || !empty($countryData['visa'])
        || !empty($countryData['currency'])
        || !empty($countryData['highlights']);
    if ($hasFacts):
    ?>
    <section class="th-country-page__facts" aria-label="Кратко о стране">
        <div class="th-container mx-auto px-6">
            <div class="th-country-page__facts-grid">
                <?php if ($bioSnippet !== ''): ?>
                <div class="th-country-page__fact-card th-country-page__fact-card--wide">
                    <h3>О направлении</h3>
                    <p><?php echo htmlspecialchars($bioSnippet, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if (!empty($countryData['highlights']) && is_array($countryData['highlights'])): ?>
                    <div class="th-country-page__highlights">
                        <?php foreach (array_slice($countryData['highlights'], 0, 6) as $hl): ?>
                        <span class="th-country-page__highlight"><?php echo htmlspecialchars((string) $hl, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($countryData['bestTime'])): ?>
                <div class="th-country-page__fact-card">
                    <h3>Лучшее время</h3>
                    <p><?php echo htmlspecialchars((string) $countryData['bestTime'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($countryData['visa'])): ?>
                <div class="th-country-page__fact-card">
                    <h3>Виза</h3>
                    <p><?php echo htmlspecialchars((string) $countryData['visa'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($countryData['currency'])): ?>
                <div class="th-country-page__fact-card">
                    <h3>Валюта</h3>
                    <p><?php echo htmlspecialchars((string) $countryData['currency'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (($countryData['slug'] ?? '') === 'turkey'): ?>
    <section class="py-8 md:py-10 bg-gradient-to-br from-amber-50/80 via-white to-indigo-50/60 border-b border-amber-100/80" id="turkey-vip-hotels-banner" aria-label="VIP отели Турции">
        <div class="th-container mx-auto px-6">
            <div class="max-w-5xl mx-auto flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 rounded-2xl border border-white/80 bg-white/95 p-6 md:p-8 shadow-sm ring-1 ring-slate-100">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-700 mb-1.5">Премиум</p>
                    <h2 class="heading-font text-2xl md:text-3xl font-bold text-slate-900">VIP отели Турции</h2>
                    <p class="text-slate-600 mt-2 text-sm md:text-base leading-relaxed">Подборка отелей Антальи, Белека и Кемера. Описания и фото — из каталога; <strong class="text-slate-800">цена «от»</strong> подгружается для каждого отеля отдельно из поиска Tourvisor.</p>
                </div>
                <a href="/frontend/window/turkey-vip-hotels.php"
                   class="inline-flex items-center justify-center gap-2 shrink-0 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 text-white px-6 py-3.5 font-semibold shadow-md shadow-amber-500/25 hover:opacity-95 transition min-h-[48px]">
                    <i class="fas fa-hotel" aria-hidden="true"></i>
                    Смотреть VIP отели
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($countryData['images']) && is_array($countryData['images'])): ?>
    <section class="th-country-page__gallery" id="country-photo-gallery" aria-label="Фотогалерея">
        <div class="th-container mx-auto px-6">
            <header class="th-section-head mb-4">
                <div>
                    <h2 class="heading-font">Фото <?php echo htmlspecialchars($countryData['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p>Нажмите на снимок для полноэкранного просмотра</p>
                </div>
            </header>
                <div class="th-country-gallery__grid">
                        <?php foreach ($countryData['images'] as $index => $image): ?>
                        <?php $imgSrc = th_country_page_image_src((string) $image); ?>
                        <?php $fallback = 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800'; ?>
                        <div class="th-country-gallery__item gallery-image group"
                             data-image="<?php echo $imgSrc !== '' ? $imgSrc : $fallback; ?>"
                             data-index="<?php echo (int) $index; ?>">
                            <img src="<?php echo $imgSrc !== '' ? $imgSrc : $fallback; ?>"
                                 alt="<?php echo htmlspecialchars($countryData['name'] . ' — фото ' . ($index + 1), ENT_QUOTES, 'UTF-8'); ?>"
                                 loading="lazy"
                                 decoding="async"
                                 onerror="this.src='<?php echo $fallback; ?>'">
                        </div>
                        <?php endforeach; ?>
                </div>

                <div id="gallery-lightbox" class="fixed inset-0 bg-black/90 z-[60] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Просмотр фото">
                    <button type="button" class="absolute top-4 right-4 text-white hover:text-sky-300 transition text-3xl z-10" id="close-lightbox" aria-label="Закрыть">
                        <i class="fas fa-times"></i>
                    </button>
                    <button type="button" class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 text-white hover:text-sky-300 transition text-2xl sm:text-3xl z-10" id="prev-image" aria-label="Предыдущее фото">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button type="button" class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 text-white hover:text-sky-300 transition text-2xl sm:text-3xl z-10" id="next-image" aria-label="Следующее фото">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <div class="max-w-7xl w-full h-full flex items-center justify-center pt-10 pb-14">
                        <img id="lightbox-image" src="" alt="" class="max-w-full max-h-[85vh] object-contain rounded-lg">
                    </div>
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white/90 text-sm" id="image-counter"></div>
                </div>
        </div>
    </section>
    <?php endif; ?>

    <script>
    (function () {
        'use strict';
        function safeLsGet(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }
        function buildUrl(baseHref, depId, depName) {
            try {
                var u = new URL(baseHref, window.location.origin);
                if (depId) u.searchParams.set('departureId', String(depId));
                if (depName) u.searchParams.set('departureName', String(depName));
                return u.pathname + (u.search ? u.search : '') + (u.hash ? u.hash : '');
            } catch (e) {
                return baseHref;
            }
        }
        function apply() {
            var a = document.querySelector('a[data-th-promo-cta="1"]');
            if (!a) return;
            var depId = safeLsGet('th_departure_id');
            var depName = safeLsGet('th_departure_name');
            if (!depId || !depName) return;
            var baseHref = a.getAttribute('href') || '';
            if (!baseHref) return;
            a.setAttribute('href', buildUrl(baseHref, depId, depName));
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', apply);
        else apply();
        window.addEventListener('th-departure-saved', function () { apply(); });
    })();
    </script>
    <script>
    (function () {
        'use strict';
        function openCountryLead() {
            var btn = document.getElementById('th-country-page-lead-btn');
            if (!btn) return;
            var country = String(btn.getAttribute('data-th-country-name') || '').trim();
            var tourLink = '';
            try {
                tourLink = window.location.origin + '/frontend/window/tour-detail.php?country='
                    + encodeURIComponent(country || 'Подбор тура');
            } catch (e) {}
            if (window.THTourBookingModal && typeof window.THTourBookingModal.open === 'function') {
                window.THTourBookingModal.open({
                    country: country || 'Подбор тура',
                    hotel_name: country ? ('Подбор тура: ' + country) : 'Подбор тура',
                    tour_link: tourLink,
                    departure_city: (typeof localStorage !== 'undefined' && localStorage.getItem('th_departure_name'))
                        ? String(localStorage.getItem('th_departure_name')) : 'Самара'
                });
            } else if (window.openQuickLeadModalWithIntent) {
                window.openQuickLeadModalWithIntent('home_quick_modal');
            } else if (typeof window.openSiteFeedbackModal === 'function') {
                window.openSiteFeedbackModal({ source: 'country_page' });
            }
        }
        function bind() {
            var btn = document.getElementById('th-country-page-lead-btn');
            if (!btn || btn.__thCountryLeadBound) return;
            btn.__thCountryLeadBound = true;
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openCountryLead();
            });
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind);
        else bind();
    })();
    </script>

</div>
