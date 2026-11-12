<?php
/**
 * Блок «Туры из {страна}» — обычные туры на ближайший месяц для страниц стран.
 * Требует: $countrySlug, $countryData['name']; опционально $country_promo_listing_url (из country_page_main_sections).
 * Данные: search-cached без onlyPromo, dateFrom=сегодня, dateTo=+30 дней; фильтр по категории отеля.
 */
if (empty($countrySlug)) {
    return;
}
require_once __DIR__ . '/tourvisor_proxy_url.php';
require_once dirname(__DIR__) . '/config/departure_defaults.php';
$countryMatchConfig = require __DIR__ . '/country_match_config.php';
$countryNameMap = $countryMatchConfig['names'] ?? [];
$countryAliasMap = $countryMatchConfig['aliases'] ?? [];
$countryFallbackIdMap = $countryMatchConfig['fallback_ids'] ?? [];
$countryNameResolved = $countryData['name'] ?? ($countryNameMap[$countrySlug] ?? $countrySlug);
$countryFallbackId = isset($countryFallbackIdMap[$countrySlug]) ? (int) $countryFallbackIdMap[$countrySlug] : 0;
$tvBase = get_tourvisor_proxy_base_url();
$tvImageProxy = get_tourvisor_image_proxy_base_url();

$countryPromoDepartureId = th_departure_default_id();
$countryPromoDepartureName = th_departure_default_name();

/** Как warm_home_search_cache / cover: today+3 … +42, ночи 5–10. */
$countryPromoSearchNightsFrom = 5;
$countryPromoSearchNightsTo = 10;
$countryPromoDateOffsetDays = 3;
$countryPromoHorizonDays = 42;

$country_promo_listing_url = $country_promo_listing_url ?? '/frontend/window/promotions.php';
$_th_promo_css = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'promotions.css';
$_th_promo_css_v = is_file($_th_promo_css) ? (string) filemtime($_th_promo_css) : '7';
?>
<link rel="stylesheet" href="/frontend/css/pages/promotions.css?v=<?php echo htmlspecialchars($_th_promo_css_v, ENT_QUOTES, 'UTF-8'); ?>">
<section class="py-8 md:py-10" id="country-promo-tours">
    <div class="th-container mx-auto px-6">
        <div class="max-w-6xl mx-auto">
            <header class="th-section-head">
                <div>
                    <h2 class="heading-font">Актуальные туры</h2>
                    <p>Цены на ближайший месяц · <?php echo htmlspecialchars($countryNameResolved); ?></p>
                </div>
                <button type="button"
                        id="country-promo-pick-btn"
                        class="th-promo-pick-tour-btn shrink-0"
                        data-promo-listing-url="<?php echo htmlspecialchars($country_promo_listing_url, ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fas fa-fire-alt" aria-hidden="true"></i>
                    Подобрать тур
                </button>
            </header>

            <div id="country-promo-stars-wrap" class="mb-6 hidden">
                <div id="country-promo-filters-row" class="mt-2">
                    <button type="button" id="country-promo-stars-trigger" class="promo-stars-trigger" aria-haspopup="dialog" aria-expanded="false">
                        <span class="promo-stars-trigger__badge" aria-hidden="true">
                            <i class="fas fa-sliders-h"></i>
                        </span>
                        <span class="promo-stars-trigger__inner">
                            <span class="promo-stars-trigger__title">Категория отеля</span>
                            <span id="country-promo-stars-label" class="promo-stars-trigger__hint" aria-live="polite">Необязательно — можно смотреть все туры</span>
                        </span>
                        <span class="promo-stars-trigger__chev" aria-hidden="true">
                            <i class="fas fa-chevron-down promo-stars-trigger__icon"></i>
                        </span>
                    </button>
                </div>
                <div id="country-promo-stars-backdrop" class="promo-stars-backdrop" style="display:none" aria-hidden="true"></div>
                <div id="country-promo-stars-popup" role="dialog" aria-modal="true" aria-label="Выбор категории отеля" style="display:none" class="promo-stars-popup">
                    <div class="promo-stars-popup__header">
                        <span class="promo-stars-popup__title">Категория отеля</span>
                        <button type="button" id="country-promo-stars-close" class="promo-stars-popup__close" aria-label="Закрыть">✕</button>
                    </div>
                    <div class="promo-stars-popup__list">
                        <label class="promo-stars-popup__item">
                            <input type="checkbox" class="promo-star-cb country-promo-star-cb" value="3">
                            <span class="promo-stars-popup__item-face"><span>★★★</span><small>3 звезды</small></span>
                        </label>
                        <label class="promo-stars-popup__item">
                            <input type="checkbox" class="promo-star-cb country-promo-star-cb" value="4">
                            <span class="promo-stars-popup__item-face"><span>★★★★</span><small>4 звезды</small></span>
                        </label>
                        <label class="promo-stars-popup__item">
                            <input type="checkbox" class="promo-star-cb country-promo-star-cb" value="5">
                            <span class="promo-stars-popup__item-face"><span>★★★★★</span><small>5 звёзд</small></span>
                        </label>
                        <label class="promo-stars-popup__item">
                            <input type="checkbox" class="promo-star-cb country-promo-star-cb" value="all" checked>
                            <span class="promo-stars-popup__item-face"><span>Все</span><small>любая категория</small></span>
                        </label>
                    </div>
                    <button type="button" id="country-promo-stars-apply" class="promo-stars-popup__apply">Показать туры</button>
                </div>
            </div>

            <div id="country-promo-loading" class="text-center py-12">
                <i class="fas fa-spinner fa-spin text-4xl text-sky-500 mb-4"></i>
                <p class="text-slate-600">Загрузка туров...</p>
            </div>
            <div id="country-promo-results" class="th-tour-grid hidden"></div>
            <div id="country-promo-empty" class="hidden text-center py-12">
                <p class="text-slate-600">Туры по этому направлению временно недоступны. Попробуйте позже или оставьте заявку.</p>
                <a href="<?php echo htmlspecialchars($country_promo_listing_url, ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex items-center gap-2 mt-4 text-sky-600 font-medium hover:text-sky-700">
                    Горящие туры <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<?php
$_th_fp_cp = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'tourvisor-flight-pick.js';
$_th_fp_cp_v = is_file($_th_fp_cp) ? (string) filemtime($_th_fp_cp) : '1';
?>
<script src="/frontend/js/tourvisor-flight-pick.js?v=<?php echo htmlspecialchars($_th_fp_cp_v, ENT_QUOTES, 'UTF-8'); ?>"></script>
<script>
(function() {
    window.__countryFlightsByTourId = window.__countryFlightsByTourId || {};
    var TV_BASE = <?php echo json_encode($tvBase); ?>;
    var TV_IMAGE = <?php echo json_encode($tvImageProxy); ?>;
    if (typeof location !== 'undefined' && location.protocol === 'https:' && typeof TV_BASE === 'string' && TV_BASE.indexOf('http://') === 0) {
        TV_BASE = 'https:' + TV_BASE.substring(5);
    }
    if (typeof location !== 'undefined' && location.protocol === 'https:' && typeof TV_IMAGE === 'string' && TV_IMAGE.indexOf('http://') === 0) {
        TV_IMAGE = 'https:' + TV_IMAGE.substring(5);
    }
    var COUNTRY_NAME = <?php echo json_encode($countryNameResolved); ?>;
    var COUNTRY_SLUG = <?php echo json_encode($countrySlug); ?>;
    var COUNTRY_ALIASES = <?php echo json_encode($countryAliasMap); ?>;
    var COUNTRY_ID_FALLBACK = <?php echo (int) $countryFallbackId; ?>;
    var COUNTRY_SEARCH_NIGHTS_FROM = <?php echo (int) $countryPromoSearchNightsFrom; ?>;
    var COUNTRY_SEARCH_NIGHTS_TO = <?php echo (int) $countryPromoSearchNightsTo; ?>;
    var COUNTRY_DATE_OFFSET_DAYS = <?php echo (int) $countryPromoDateOffsetDays; ?>;
    var COUNTRY_HORIZON_DAYS = <?php echo (int) $countryPromoHorizonDays; ?>;
    var PROMO_DEPARTURE_ID = <?php echo (int) $countryPromoDepartureId; ?>;
    var PROMO_DEPARTURE_NAME = <?php echo json_encode($countryPromoDepartureName, JSON_UNESCAPED_UNICODE); ?>;
    try {
        var __lsPid = localStorage.getItem('th_departure_id');
        var __lsPnm = localStorage.getItem('th_departure_name');
        if (__lsPid) {
            var __pParsed = parseInt(String(__lsPid), 10);
            if (!isNaN(__pParsed) && __pParsed > 0) PROMO_DEPARTURE_ID = __pParsed;
        }
        if (__lsPnm) PROMO_DEPARTURE_NAME = String(__lsPnm);
    } catch (__eLsPromo) {}

    function countryPromoAdultsCount() {
        try {
            var raw = localStorage.getItem('tv_tourists');
            if (raw) {
                var j = JSON.parse(raw);
                if (j && typeof j.adults === 'number' && j.adults >= 1 && j.adults <= 9) return j.adults;
            }
        } catch (e) {}
        return 2;
    }
    function countryPromoPriceSuffix(adults) {
        var n = (adults >= 1 && adults <= 9) ? adults : 2;
        return 'за ' + n + ' взрослых';
    }
    function countryPromoPhotoUrls(h) {
        var raw = [];
        if (window.THTourCard && typeof window.THTourCard.collectHotelPhotoRawUrls === 'function') {
            raw = window.THTourCard.collectHotelPhotoRawUrls(h);
        } else if (h) {
            raw.push((h.picturelink || h.pictureLink || '').toString());
            var pics = h.pictures;
            if (pics && Array.isArray(pics)) {
                pics.forEach(function (p) {
                    if (typeof p === 'string') raw.push(p);
                    else if (p && typeof p === 'object') raw.push((p.src || p.url || p.link || p.picturelink || p.pictureLink || '').toString());
                });
            }
            var hid = parseInt(String(h.id || ''), 10);
            if (!raw.filter(function (x) { return x && String(x).trim(); }).length && hid > 0) {
                raw.push('hotel_pics/main400/' + hid + '.jpg');
            }
        }
        var urls = [];
        var seen = {};
        raw.forEach(function (u) {
            if (!u || typeof u !== 'string') return;
            u = u.trim();
            if (!u || seen[u]) return;
            seen[u] = true;
            urls.push(u);
        });
        var max = 4;
        var i = 0;
        while (urls.length > 0 && urls.length < max && i < max) {
            urls.push(urls[urls.length - 1]);
            i++;
        }
        if (urls.length === 0) urls.push('https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400');
        return urls.slice(0, max);
    }

    function formatLocalYMD(d) {
        if (!d || !(d instanceof Date) || isNaN(d.getTime())) return '';
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1);
        if (m.length === 1) m = '0' + m;
        var day = String(d.getDate());
        if (day.length === 1) day = '0' + day;
        return y + '-' + m + '-' + day;
    }

    window.__countryPromoStar = '4-5';
    window.__countryPromoAllHotels = [];

    function imgUrl(src) {
        if (!src) return 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400';
        if (window.THTourCard && typeof window.THTourCard.mapTourvisorImageUrl === 'function') {
            var mapped = window.THTourCard.mapTourvisorImageUrl(src, TV_IMAGE);
            return mapped || 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400';
        }
        var s = String(src).trim();
        if (/^\/\//.test(s)) {
            s = (typeof location !== 'undefined' && location.protocol === 'https:' ? 'https:' : 'http:') + s;
        }
        if (/^https?:\/\/static\.tourvisor\.ru\//i.test(s)) return TV_IMAGE + '?url=' + encodeURIComponent(s.replace(/^https:/i, 'http:'));
        if (/^static\.tourvisor\.ru\//i.test(s)) return TV_IMAGE + '?url=' + encodeURIComponent('http://' + s);
        if (/^\/hotel_pics\//i.test(s) || /^hotel_pics\//i.test(s)) return TV_IMAGE + '?path=' + encodeURIComponent(s.replace(/^\/+/, ''));
        return s;
    }
    function countryPromoCardPrimaryImage(h) {
        var fallback = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400';
        var urls = [];
        var seen = {};
        function add(u) {
            if (!u || typeof u !== 'string') return;
            u = u.trim();
            if (!u || seen[u]) return;
            seen[u] = true;
            urls.push(u);
        }
        if (h) {
            add((h.picturelink || h.pictureLink || '').toString());
            var pics = h.pictures;
            if (pics && Array.isArray(pics)) {
                pics.forEach(function (p) {
                    if (typeof p === 'string') add(p);
                    else if (p && typeof p === 'object') add((p.src || p.url || p.link || p.picturelink || p.pictureLink || '').toString());
                });
            }
        }
        for (var i = 0; i < urls.length; i++) {
            var u = imgUrl(urls[i]);
            if (u && u.indexOf('unsplash.com') === -1) return u;
        }
        return urls.length ? imgUrl(urls[0]) : fallback;
    }
    function formatPrice(n) {
        var num = Number(n);
        if (isNaN(num)) return '0 ₽';
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(num);
    }
    function countryPromoPickFirstPriceNum() {
        var args = Array.prototype.slice.call(arguments);
        for (var i = 0; i < args.length; i++) {
            var v = args[i];
            if (v == null || v === '') continue;
            var n = Number(v);
            if (!isNaN(n) && n > 0) return n;
        }
        return 0;
    }
    function countryPromoHotelListPrice(h) {
        if (!h) return 0;
        var tour = (h.tours && h.tours[0]) ? h.tours[0] : {};
        if (h.tours && h.tours[0]) {
            var n = countryPromoPickFirstPriceNum(
                tour.totalPrice, tour.price, tour.priceRub, tour.cost
            );
            if (n > 0) return Math.round(n);
            n = countryPromoPickFirstPriceNum(h.price);
            return n > 0 ? Math.round(n) : 0;
        }
        return Math.round(countryPromoPickFirstPriceNum(h.price, h.minPrice, h.minprice));
    }

    function normalizeCountryToken(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/ё/g, 'е')
            .replace(/[^a-zа-я0-9]+/g, ' ')
            .trim();
    }
    function getTokenVariants(token) {
        var base = normalizeCountryToken(token);
        if (!base) return [];
        var compact = base.replace(/\s+/g, '');
        return compact && compact !== base ? [base, compact] : [base];
    }
    function getCountryTokens() {
        var tokens = [];
        if (COUNTRY_NAME) tokens = tokens.concat(getTokenVariants(COUNTRY_NAME));
        if (COUNTRY_SLUG) tokens = tokens.concat(getTokenVariants(COUNTRY_SLUG));
        var aliases = (COUNTRY_ALIASES && COUNTRY_SLUG && Array.isArray(COUNTRY_ALIASES[COUNTRY_SLUG])) ? COUNTRY_ALIASES[COUNTRY_SLUG] : [];
        aliases.forEach(function(a) { tokens = tokens.concat(getTokenVariants(a)); });
        return Array.from(new Set(tokens.filter(Boolean)));
    }
    var COUNTRY_RESOLVER_KEY = '__th_country_id_resolver_state';
    var COUNTRY_RESOLVER_MAP = (window[COUNTRY_RESOLVER_KEY] = window[COUNTRY_RESOLVER_KEY] || {});
    var COUNTRY_RESOLVER_TOKEN = normalizeCountryToken(COUNTRY_SLUG || COUNTRY_NAME || '');
    function toCountryId(value) {
        var num = Number(value);
        return Number.isFinite(num) && num > 0 ? num : null;
    }
    function getCountryResolverState() {
        if (!COUNTRY_RESOLVER_MAP[COUNTRY_RESOLVER_TOKEN]) {
            COUNTRY_RESOLVER_MAP[COUNTRY_RESOLVER_TOKEN] = {
                apiId: null,
                fallbackId: toCountryId(COUNTRY_ID_FALLBACK),
                source: null,
                resolvePromise: null
            };
        } else if (!COUNTRY_RESOLVER_MAP[COUNTRY_RESOLVER_TOKEN].fallbackId) {
            COUNTRY_RESOLVER_MAP[COUNTRY_RESOLVER_TOKEN].fallbackId = toCountryId(COUNTRY_ID_FALLBACK);
        }
        return COUNTRY_RESOLVER_MAP[COUNTRY_RESOLVER_TOKEN];
    }
    function setResolvedCountryId(id, source) {
        var state = getCountryResolverState();
        var normalizedId = toCountryId(id);
        if (!normalizedId) return null;
        if (source === 'api') {
            state.apiId = normalizedId;
            state.source = 'api';
            return normalizedId;
        }
        if (!state.apiId) {
            state.fallbackId = normalizedId;
            state.source = 'fallback';
            return normalizedId;
        }
        return state.apiId;
    }
    function resolveCountryIdShared(fetchCountriesFn) {
        var state = getCountryResolverState();
        if (state.apiId) return Promise.resolve(state.apiId);
        if (state.resolvePromise) return state.resolvePromise;
        state.resolvePromise = Promise.resolve()
            .then(function() { return fetchCountriesFn(); })
            .then(function(resp) {
                var countries = resp && Array.isArray(resp.data) ? resp.data : [];
                var apiId = resolveCountryIdFromApi(countries);
                if (apiId) return setResolvedCountryId(apiId, 'api');
                return setResolvedCountryId(state.fallbackId, 'fallback');
            })
            .catch(function() {
                return setResolvedCountryId(state.fallbackId, 'fallback');
            })
            .finally(function() {
                state.resolvePromise = null;
            });
        return state.resolvePromise;
    }
    function resolveCountryIdFromApi(countries) {
        if (!Array.isArray(countries) || countries.length === 0) return null;
        var tokens = getCountryTokens();
        for (var i = 0; i < countries.length; i++) {
            var c = countries[i] || {};
            var values = []
                .concat(getTokenVariants(c.name))
                .concat(getTokenVariants(c.russianName))
                .concat(getTokenVariants(c.englishName))
                .concat(getTokenVariants(c.slug))
                .filter(Boolean);
            var matched = tokens.some(function(t) {
                return values.some(function(v) {
                    return v === t || v.indexOf(t) !== -1 || t.indexOf(v) !== -1;
                });
            });
            if (matched && c.id != null && c.id !== '') return Number(c.id);
        }
        return null;
    }
    function safeFetchJson(url) {
        return fetch(url, { cache: 'no-store' })
            .then(function(r) { return r.text().then(function(t) { return { ok: r.ok, text: t }; }); })
            .then(function(o) {
                var t = (o.text || '').trim();
                if (!o.ok || !t) return null;
                try { return JSON.parse(t); } catch (e) { return null; }
            })
            .catch(function() { return null; });
    }

    var baseSep = TV_BASE.indexOf('?') >= 0 ? '&' : '?';
    var promoDateFromStr = '';
    var promoDateToStr = '';

    function strClip(s, max) {
        s = String(s == null ? '' : s);
        if (s.length <= max) return s;
        return s.slice(0, max);
    }

    function tourStartYmdFromSearch(tour) {
        if (!tour) return '';
        var raw = String(tour.date || tour.startDate || tour.departureDate || tour.departuredate
            || tour.flydate || tour.flyDate || tour.checkIn || tour.checkin || '').trim();
        if (!raw) return '';
        var m = raw.match(/^(\d{4}-\d{2}-\d{2})/);
        if (m) return m[1];
        var dm = raw.match(/^(\d{1,2})[./](\d{1,2})[./](\d{4})/);
        if (dm) {
            var d0 = parseInt(dm[1], 10);
            var mo = parseInt(dm[2], 10);
            var y0 = parseInt(dm[3], 10);
            if (d0 >= 1 && d0 <= 31 && mo >= 1 && mo <= 12 && y0 >= 2000) {
                return y0 + '-' + String(mo).padStart(2, '0') + '-' + String(d0).padStart(2, '0');
            }
        }
        try {
            var d = new Date(raw);
            if (!isNaN(d.getTime())) return formatLocalYMD(d);
        } catch (e0) {}
        return '';
    }
    function countryTourOperatorLabel(tour) {
        if (!tour) return '';
        if (tour.operatorName) return String(tour.operatorName);
        var op = tour.operator;
        if (typeof op === 'string') return op;
        if (op && typeof op === 'object') return String(op.name || op.russianName || op.fullName || '');
        return '';
    }
    function tourReturnYmdFromStart(startYmd, nightsNum) {
        if (!startYmd || !nightsNum) return '';
        var p = startYmd.split('-');
        if (p.length !== 3) return '';
        var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10), 12, 0, 0);
        if (isNaN(d.getTime())) return '';
        d.setDate(d.getDate() + nightsNum);
        var mo = String(d.getMonth() + 1), da = String(d.getDate());
        if (mo.length === 1) mo = '0' + mo;
        if (da.length === 1) da = '0' + da;
        return d.getFullYear() + '-' + mo + '-' + da;
    }

    function fetchRegularSearchCached(countryId, df, dt, cacheOnly) {
        var q = [
            'type=search-cached',
            'cacheScope=country_page',
            'departureId=' + encodeURIComponent(String(PROMO_DEPARTURE_ID)),
            'countryId=' + encodeURIComponent(countryId),
            'countryName=' + encodeURIComponent(COUNTRY_NAME || ''),
            'dateFrom=' + encodeURIComponent(df),
            'dateTo=' + encodeURIComponent(dt),
            'nightsFrom=' + encodeURIComponent(String(COUNTRY_SEARCH_NIGHTS_FROM)),
            'nightsTo=' + encodeURIComponent(String(COUNTRY_SEARCH_NIGHTS_TO)),
            'adults=' + encodeURIComponent(String(countryPromoAdultsCount()))
        ];
        if (cacheOnly) q.push('cacheOnly=1');
        var url = TV_BASE + baseSep + q.join('&');
        return fetch(url, { cache: 'no-store' })
            .then(function(r) { return r.text().then(function(t) { return { ok: r.ok, text: t }; }); })
            .then(function(o) {
                if (!o.ok || !(o.text || '').trim()) return { success: false, data: [] };
                try { return JSON.parse(o.text); } catch (e) { return { success: false, data: [] }; }
            })
            .catch(function() { return { success: false, data: [] }; });
    }

    function extractHotelList(j) {
        if (!j || typeof j !== 'object') return [];
        if (Array.isArray(j.data)) return j.data;
        if (Array.isArray(j.tours)) return j.tours;
        return [];
    }

    function getHotelStarCategory(h) {
        if (!h) return null;
        var raw = h.category;
        if (raw == null || raw === '') raw = h.hotelCategory != null ? h.hotelCategory : h.stars;
        if (raw == null || raw === '') return null;
        var s = String(raw).trim();
        var n = parseInt(s, 10);
        if (!isNaN(n) && n >= 1 && n <= 5) return n;
        var m = s.match(/([1-5])\s*(?:\*|зв|\u2605|★|stars?)?/i);
        if (m) { n = parseInt(m[1], 10); if (!isNaN(n) && n >= 1 && n <= 5) return n; }
        return null;
    }

    window.__countryPromoStarFilter = '';
    window.__countryPromoStarSet = null;

    function countryPromoToursHaveAnyStarRating(list) {
        if (!list || !list.length) return false;
        for (var i = 0; i < list.length; i++) {
            if (getHotelStarCategory(list[i]) != null) return true;
        }
        return false;
    }

    function getFilteredCountryPromoHotels() {
        var list = (window.__countryPromoAllHotels || []).slice();
        var starSet = window.__countryPromoStarSet;
        if (starSet && starSet.size > 0) {
            if (!countryPromoToursHaveAnyStarRating(list)) return list;
            return list.filter(function (h) {
                var c = getHotelStarCategory(h);
                return c !== null && starSet.has(c);
            });
        }
        var starRaw = (typeof window.__countryPromoStarFilter === 'string') ? window.__countryPromoStarFilter.trim() : '';
        if (starRaw === '') return list;
        var want = parseInt(starRaw, 10);
        if (isNaN(want)) return list;
        if (!countryPromoToursHaveAnyStarRating(list)) return list;
        return list.filter(function (h) {
            return getHotelStarCategory(h) === want;
        });
    }

    var __countryPromoStarPopupInited = false;
    function initCountryPromoStarFilterPopup() {
        if (__countryPromoStarPopupInited) return;
        var trigger = document.getElementById('country-promo-stars-trigger');
        var popup = document.getElementById('country-promo-stars-popup');
        var backdrop = document.getElementById('country-promo-stars-backdrop');
        var closeBtn = document.getElementById('country-promo-stars-close');
        var applyBtn = document.getElementById('country-promo-stars-apply');
        var label = document.getElementById('country-promo-stars-label');
        if (!trigger || !popup) return;
        __countryPromoStarPopupInited = true;

        function getStarLabel(set) {
            if (!set || set.size === 0) return '';
            var vals = Array.from(set).sort(function (a, b) { return a - b; });
            if (vals.length === 1) return vals[0] + '★';
            if (vals.length === 2 && vals[0] + 1 === vals[1]) return vals[0] + '–' + vals[1] + '★';
            return vals.join(', ') + '★';
        }
        function syncTrigger() {
            if (!label) return;
            var sel = getStarLabel(window.__countryPromoStarSet);
            label.textContent = sel ? ('Сейчас: ' + sel) : 'Необязательно — можно смотреть все туры';
            trigger.classList.toggle('has-selection', !!sel);
        }
        function openPopup() {
            var cur = window.__countryPromoStarSet;
            popup.querySelectorAll('.country-promo-star-cb').forEach(function (cb) {
                if (cb.value === 'all') cb.checked = (!cur || cur.size === 0);
                else cb.checked = !!(cur && cur.has(parseInt(cb.value, 10)));
            });
            popup.style.display = '';
            if (backdrop) backdrop.style.display = '';
            trigger.setAttribute('aria-expanded', 'true');
        }
        function closePopup() {
            popup.style.display = 'none';
            if (backdrop) backdrop.style.display = 'none';
            trigger.setAttribute('aria-expanded', 'false');
        }

        trigger.addEventListener('click', openPopup);
        if (closeBtn) closeBtn.addEventListener('click', closePopup);
        if (backdrop) backdrop.addEventListener('click', closePopup);
        popup.querySelectorAll('.country-promo-star-cb').forEach(function (cb) {
            cb.addEventListener('change', function () {
                if (cb.value === 'all' && cb.checked) {
                    popup.querySelectorAll('.country-promo-star-cb:not([value="all"])').forEach(function (c) { c.checked = false; });
                } else if (cb.value !== 'all' && cb.checked) {
                    var allCb = popup.querySelector('.country-promo-star-cb[value="all"]');
                    if (allCb) allCb.checked = false;
                }
            });
        });
        if (applyBtn) applyBtn.addEventListener('click', function () {
            var newSet = new Set();
            var allChecked = false;
            popup.querySelectorAll('.country-promo-star-cb').forEach(function (cb) {
                if (!cb.checked) return;
                if (cb.value === 'all') allChecked = true;
                else newSet.add(parseInt(cb.value, 10));
            });
            if (allChecked || newSet.size === 0) {
                window.__countryPromoStarSet = null;
                window.__countryPromoStarFilter = '';
            } else {
                window.__countryPromoStarSet = newSet;
                window.__countryPromoStarFilter = Array.from(newSet).sort(function (a, b) { return a - b; }).join(',');
            }
            syncTrigger();
            closePopup();
            renderCountryPromoResults();
        });
        syncTrigger();
    }

    function renderCountryPromoResults() {
        var results = document.getElementById('country-promo-results');
        var empty = document.getElementById('country-promo-empty');
        if (!results) return;
        var filtered = getFilteredCountryPromoHotels();
        if (filtered.length === 0) {
            results.classList.add('hidden');
            results.innerHTML = '';
            if (empty) {
                empty.querySelector('p').textContent = (window.__countryPromoAllHotels && window.__countryPromoAllHotels.length)
                    ? 'По выбранной категории отеля туров нет. Попробуйте другой фильтр.'
                    : 'Туры по этому направлению временно недоступны. Попробуйте позже или оставьте заявку.';
                empty.classList.remove('hidden');
            }
            return;
        }
        if (empty) empty.classList.add('hidden');
        var tourDetailBase = '/frontend/window';
        var roomCategoryDefault = 'Стандарт';
        var fallbackImg = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&q=80';
        var priceFormatter = new Intl.NumberFormat('ru-RU');
        function fmtPriceSimple(n) { return n > 0 ? priceFormatter.format(n) + '\u00a0\u20bd' : 'Уточняйте'; }
        try {
            results.innerHTML = filtered.map(function(h) {
                var adultsN = countryPromoAdultsCount();
                var img = countryPromoCardPrimaryImage(h);
                var region = (h.region && h.region.name) ? h.region.name : '';
                var country = (h.country && h.country.name) ? h.country.name : '';
                var geoStr = (country + (region ? ', ' + region : '')).replace(/</g, '&lt;');
                var tour = (h.tours && h.tours[0]) ? h.tours[0] : {};
                var meal = (tour.meal && (tour.meal.russianName || tour.meal.name)) ? (tour.meal.russianName || tour.meal.name) : '';
                var nights = tour.nights ? parseInt(String(tour.nights), 10) : 0;
                var nightsLbl = nights ? (nights === 1 ? '1\u00a0ночь' : nights < 5 ? nights + '\u00a0ночи' : nights + '\u00a0ночей') : '';
                var datesMeta = (nightsLbl ? nightsLbl + ', ' : '') + adultsN + '\u00a0взр.';
                var catNum = getHotelStarCategory(h) || 0;
                var starsHtml = catNum > 0 ? '\u2605'.repeat(Math.min(catNum, 5)) : '';
                var priceNum = countryPromoHotelListPrice(h);
                var link = h.hotelDescriptionLink || h.hoteldescriptionlink || h.link || '';
                var desc = strClip((h.description || h.hotelDescription || '').toString().trim().replace(/\s+/g, ' '), 900);
                var roomCategory = strClip((tour.roomType || h.roomCategory || roomCategoryDefault).toString().trim() || roomCategoryDefault, 120);
                var tourId = (tour.id != null && tour.id !== '') ? String(tour.id) : '';
                var startY = tourStartYmdFromSearch(tour);
                var retY = (startY && nights) ? tourReturnYmdFromStart(startY, nights) : '';
                var hotelNameSafe = strClip((h.name || '').toString(), 200);
                var tourDetailParams = {
                    tour_link: strClip(link, 2000), country: strClip(country, 120), hotel_name: hotelNameSafe,
                    price: priceNum > 0 ? String(priceNum) : '',
                    nights: String(tour.nights || ''), meal: strClip(meal, 80), region: strClip(region, 120),
                    departure_city: PROMO_DEPARTURE_NAME, date_from: startY || '', date_to: retY || '',
                    image: strClip(img, 2000), description: desc, rating: String(h.rating || ''),
                    category: strClip(String(h.category || ''), 80),
                    room_category: roomCategory, tour_id: tourId, adults: String(adultsN),
                    tour_operator: countryTourOperatorLabel(tour),
                    from_promo: '1'
                };
                if (h.country && h.country.id != null) tourDetailParams.country_id = String(h.country.id);
                if (tour.meal && typeof tour.meal === 'object' && tour.meal.code != null) {
                    var _mcCp = parseInt(String(tour.meal.code), 10);
                    if (_mcCp > 0) tourDetailParams.meal_code = String(_mcCp);
                }
                if (tour.roomId != null || tour.roomid != null || tour.room_id != null) {
                    tourDetailParams.room_id = String(tour.roomId != null ? tour.roomId : (tour.roomid != null ? tour.roomid : tour.room_id));
                }
                if (window.THTourCard && typeof window.THTourCard.getRoomOptions === 'function') {
                    var roomOptions = window.THTourCard.getRoomOptions(h, tour);
                    if (roomOptions.length > 1) tourDetailParams.room_options = window.THTourCard.encodeRoomOptions(roomOptions);
                }
                if (h.id) tourDetailParams.hotel_id = String(h.id);
                if (PROMO_DEPARTURE_ID) tourDetailParams.departure_id = String(PROMO_DEPARTURE_ID);
            try {
                tourDetailParams.return_url = (window.TourSessionManager && window.TourSessionManager.buildReturnUrl)
                    ? window.TourSessionManager.buildReturnUrl()
                    : strClip(window.location.pathname + window.location.search, 500);
            } catch (e) {}
                var tourDetailUrl = tourDetailBase + '/tour-detail.php?' + new URLSearchParams(tourDetailParams).toString();
                if (tourDetailUrl.length > 8000) {
                    tourDetailParams.description = strClip(tourDetailParams.description, 400);
                    tourDetailUrl = tourDetailBase + '/tour-detail.php?' + new URLSearchParams(tourDetailParams).toString();
                }
                var nameEsc = hotelNameSafe.replace(/</g, '&lt;').replace(/"/g, '&quot;');
                var imgEsc = img.replace(/"/g, '&quot;');
                var fbEsc = fallbackImg.replace(/"/g, '&quot;');
                if (window.THTourCard && typeof window.THTourCard.render === 'function') {
                    var flightMetaCp = (window.__countryFlightsByTourId && tourId) ? window.__countryFlightsByTourId[tourId] : null;
                    return window.THTourCard.render(h, {
                        tour: tour, countryCard: true, skipPromoPatch: true, detailUrl: tourDetailUrl,
                        getImageUrl: imgUrl, imageProxy: TV_IMAGE,
                        adults: adultsN, dateFrom: startY, dateTo: retY, price: priceNum, meal: meal,
                        departureCity: PROMO_DEPARTURE_NAME, departureId: PROMO_DEPARTURE_ID,
                        carousel: true, image: img,
                        flightMeta: flightMetaCp,
                        onlyDirect: true
                    });
                }
                var flightHtmlCp = (window.THTourCard && typeof window.THTourCard.buildFlightBlockHtml === 'function')
                    ? window.THTourCard.buildFlightBlockHtml(PROMO_DEPARTURE_NAME, tourId, {
                        flightMeta: (window.__countryFlightsByTourId && tourId) ? window.__countryFlightsByTourId[tourId] : null
                    })
                    : '<span class="th-tour-card__dep-city">Вылет: ' + PROMO_DEPARTURE_NAME.replace(/</g, '&lt;') + '</span>';
                var tourIdAttrCp = tourId ? ' data-th-tour-id="' + tourId.replace(/"/g, '&quot;') + '"' : '';
                var hotelIdAttrCp = h.id ? ' data-th-hotel-id="' + String(h.id).replace(/"/g, '&quot;') + '"' : '';
                return '<article class="th-tour-card th-tour-card--country" data-promo-patched="skip"' + hotelIdAttrCp + tourIdAttrCp +
                    ' data-th-departure-city="' + PROMO_DEPARTURE_NAME.replace(/"/g, '&quot;') + '">' +
                    '<a href="' + tourDetailUrl.replace(/"/g, '&quot;') + '" class="th-tour-card__link th-tour-card__link--main">' +
                    '<div class="th-tour-card__media">' +
                    '<img src="' + imgEsc + '" alt="' + nameEsc + '" class="th-tour-card__img" loading="eager" decoding="async" onerror="this.onerror=null;this.src=\'' + fbEsc + '\'">' +
                    '</div>' +
                    '<div class="th-tour-card__body">' +
                    (geoStr ? '<p class="th-tour-card__geo">' + geoStr + '</p>' : '') +
                    '<div class="th-tour-card__name-row">' +
                    '<h3 class="th-tour-card__name">' + nameEsc + '</h3>' +
                    (starsHtml ? '<span class="th-tour-card__stars">' + starsHtml + '</span>' : '') +
                    '</div>' +
                    (meal ? '<span class="th-tour-card__meal-badge">' + meal.replace(/</g, '&lt;') + '</span>' : '') +
                    flightHtmlCp +
                    '<div class="th-tour-card__price-block">' +
                    '<span class="th-tour-card__price-label">' + countryPromoPriceSuffix(adultsN) + '</span>' +
                    '<span class="th-tour-card__price">' + fmtPriceSimple(priceNum) + '</span>' +
                    '<span class="th-tour-card__dates">' + datesMeta + '</span>' +
                    '</div>' +
                    '</div></a>' +
                    '<div class="th-tour-card__actions">' +
                    '<a href="' + tourDetailUrl.replace(/"/g, '&quot;') + '" class="th-tour-card__btn th-tour-card__btn--secondary">' + (window.THTourCard && window.THTourCard.DETAIL_BTN_LABEL ? window.THTourCard.DETAIL_BTN_LABEL : 'Просмотреть параметры тура и забронировать') + '</a>' +
                    '</div></article>';
            }).join('');
            results.classList.remove('hidden');
            window.__countryFlightsByTourId = window.__countryFlightsByTourId || {};
            var promoFlightOpts = {
                apiBase: TV_BASE.replace(/\/$/, ''),
                departureCity: PROMO_DEPARTURE_NAME,
                departureId: PROMO_DEPARTURE_ID,
                maxConcurrent: 6,
                flightsFirst: true,
                combinedPackage: false,
                skipEnrich: false,
                getTourId: function (h) {
                    var t = (h.tours && h.tours[0]) ? h.tours[0] : {};
                    return (t.id != null && t.id !== '') ? String(t.id) : '';
                },
                patchContainer: results
            };
            var afterFlights = function () {
                if (window.THTourCard && typeof window.THTourCard.patchDatesInContainer === 'function') {
                    window.THTourCard.patchDatesInContainer(results);
                }
                if (window.THTourCard && typeof window.THTourCard.patchRoomsInContainer === 'function') {
                    window.THTourCard.patchRoomsInContainer(results);
                }
            };
            if (typeof thLoadFlightsVisibleThenAll === 'function' && TV_BASE) {
                thLoadFlightsVisibleThenAll(filtered, Object.assign({}, promoFlightOpts, { maxTours: 0, onDone: afterFlights }));
            } else if (typeof thLoadTourFlightsForHotels === 'function' && TV_BASE) {
                thLoadTourFlightsForHotels(filtered, Object.assign({}, promoFlightOpts, { maxTours: filtered.length, onDone: afterFlights }));
            } else if (window.THTourCard && typeof window.THTourCard.patchFlightsInContainer === 'function') {
                window.THTourCard.patchFlightsInContainer(results);
                afterFlights();
            }
            if (window.THTourCard && typeof window.THTourCard.ensureCarouselsInContainer === 'function') {
                window.THTourCard.ensureCarouselsInContainer(results);
            } else if (window.THTourCard && typeof window.THTourCard.kickImagesInContainer === 'function') {
                window.THTourCard.kickImagesInContainer(results);
            }
        } catch (renderErr) {
            console.error('[country-tours] Ошибка отрисовки:', renderErr);
            if (empty) { empty.querySelector('p').textContent = 'Не удалось отобразить туры. Попробуйте обновить страницу.'; empty.classList.remove('hidden'); }
        }
    }

    function syncPromoDepartureFromStorage(detail) {
        try {
            var idRaw = (detail && detail.id != null) ? String(detail.id) : localStorage.getItem('th_departure_id');
            var nmRaw = (detail && detail.name) ? String(detail.name) : localStorage.getItem('th_departure_name');
            if (idRaw) {
                var pid = parseInt(String(idRaw), 10);
                if (!isNaN(pid) && pid > 0) PROMO_DEPARTURE_ID = pid;
            }
            if (nmRaw) PROMO_DEPARTURE_NAME = String(nmRaw);
        } catch (eSync) {}
    }

    function countryPromoSearchDateFromYmd() {
        var dFrom = new Date();
        dFrom.setDate(dFrom.getDate() + (COUNTRY_DATE_OFFSET_DAYS || 0));
        return formatLocalYMD(dFrom);
    }
    function countryPromoSearchDateToYmd() {
        var dTo = new Date();
        dTo.setDate(dTo.getDate() + (COUNTRY_HORIZON_DAYS || 42));
        return formatLocalYMD(dTo);
    }
    function fetchCountryPromoCachedTours(countryId) {
        var df1 = countryPromoSearchDateFromYmd();
        var dt1 = countryPromoSearchDateToYmd();
        promoDateFromStr = df1;
        promoDateToStr = dt1;
        return fetchRegularSearchCached(countryId, df1, dt1, true);
    }
    function resolveCountryPromoId() {
        if (COUNTRY_ID_FALLBACK > 0) {
            return Promise.resolve(COUNTRY_ID_FALLBACK);
        }
        return resolveCountryIdShared(function() {
            return safeFetchJson(TV_BASE + baseSep + 'type=countries');
        }).then(function(id) {
            var n = Number(id) || 0;
            if (n > 0) return n;
            throw new Error('Country id not resolved');
        });
    }

    function loadCountryPromoBlock() {
        syncPromoDepartureFromStorage(null);
        var loading = document.getElementById('country-promo-loading');
        var results = document.getElementById('country-promo-results');
        var empty = document.getElementById('country-promo-empty');
        var starsWrap = document.getElementById('country-promo-stars-wrap');
        if (loading) {
            loading.classList.remove('hidden');
            loading.querySelector('p').textContent = 'Загрузка туров...';
        }
        if (results) {
            results.classList.add('hidden');
            results.innerHTML = '';
        }
        if (empty) empty.classList.add('hidden');
        if (starsWrap) starsWrap.classList.add('hidden');

        if (!TV_BASE || typeof TV_BASE !== 'string' || !String(TV_BASE).trim()) {
            console.error('[country-promo] TV_BASE пуст — нет URL tourvisor-proxy');
            if (loading) loading.classList.add('hidden');
            if (empty) {
                empty.querySelector('p').textContent = 'Не удалось загрузить туры. Попробуйте позже.';
                empty.classList.remove('hidden');
            }
            return;
        }

        resolveCountryPromoId()
            .then(function(resolvedCountryId) {
                return fetchCountryPromoCachedTours(resolvedCountryId);
            })
            .then(function(j) {
                var loadingEl = document.getElementById('country-promo-loading');
                var resultsEl = document.getElementById('country-promo-results');
                var emptyEl = document.getElementById('country-promo-empty');
                var starsWrapEl = document.getElementById('country-promo-stars-wrap');
                if (loadingEl) loadingEl.classList.add('hidden');

                if (!resultsEl) {
                    console.error('[country-promo] Нет #country-promo-results в DOM');
                    if (emptyEl) { emptyEl.querySelector('p').textContent = 'Не удалось показать блок. Обновите страницу.'; emptyEl.classList.remove('hidden'); }
                    return;
                }

                var data = extractHotelList(j);

                if (data.length === 0) {
                    if (emptyEl) emptyEl.classList.remove('hidden');
                    return;
                }

                data.sort(function (a, b) {
                    return countryPromoHotelListPrice(a) - countryPromoHotelListPrice(b);
                });
                var finish = function (prepared) {
                    window.__countryPromoAllHotels = prepared;
                    window.__countryPromoStarFilter = '';
                    window.__countryPromoStarSet = null;
                    if (starsWrapEl) starsWrapEl.classList.remove('hidden');
                    initCountryPromoStarFilterPopup();
                    renderCountryPromoResults();
                };
                finish(data);
            })
            .catch(function(err) {
                console.error('[country-promo] Загрузка:', err);
                var loadingEl = document.getElementById('country-promo-loading');
                var emptyEl = document.getElementById('country-promo-empty');
                if (loadingEl) loadingEl.classList.add('hidden');
                if (emptyEl) { emptyEl.querySelector('p').textContent = 'Не удалось загрузить туры. Попробуйте позже.'; emptyEl.classList.remove('hidden'); }
            });
    }

    function bootCountryPromoBlock() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadCountryPromoBlock);
        } else {
            loadCountryPromoBlock();
        }
    }
    bootCountryPromoBlock();
    window.addEventListener('th-departure-saved', function (ev) {
        syncPromoDepartureFromStorage(ev && ev.detail ? ev.detail : null);
        loadCountryPromoBlock();
    });

    (function bindCountryPromoPickBtn() {
        var btn = document.getElementById('country-promo-pick-btn');
        if (!btn || btn.__thBound) return;
        btn.__thBound = true;
        btn.addEventListener('click', function () {
            var base = btn.getAttribute('data-promo-listing-url') || '/frontend/window/promotions.php';
            var depId = PROMO_DEPARTURE_ID;
            var depName = PROMO_DEPARTURE_NAME;
            try {
                var lid = localStorage.getItem('th_departure_id');
                var lnm = localStorage.getItem('th_departure_name');
                if (lid && lnm) {
                    depId = parseInt(String(lid), 10) || depId;
                    depName = String(lnm);
                }
            } catch (eLs) {}
            if (depId !== 1) { depId = 7; depName = 'Самара'; }
            try {
                var u = new URL(base, window.location.origin);
                u.searchParams.set('departureId', String(depId));
                u.searchParams.set('departureName', depName);
                window.location.href = u.pathname + u.search + u.hash;
            } catch (eUrl) {
                window.location.href = base;
            }
        });
    })();
})();
</script>
