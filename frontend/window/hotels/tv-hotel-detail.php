<?php
/**
 * Hotel hub mobile-first: коротко об отеле + сразу карточки туров (25 + ещё), без формы поиска.
 */
require_once __DIR__ . '/../../../backend/config/config.php';
require_once __DIR__ . '/../../../backend/components/tourvisor_proxy_url.php';
require_once __DIR__ . '/../../../backend/config/departure_defaults.php';
session_start();

$hotelId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$countryIdHint = isset($_GET['countryId']) ? (int) $_GET['countryId'] : 0;
$hotel = null;
$error = null;
$tvApiBase = get_tourvisor_proxy_base_url();
$imgProxyBase = get_tourvisor_image_proxy_base_url();
$departureId = th_departure_default_id();
$departureName = th_departure_default_name();

function thh_proxy_image(?string $url, string $imgProxyBase): string
{
    $u = trim((string) $url);
    if ($u === '') {
        return '';
    }
    if (strpos($u, '//') === 0) {
        $u = 'https:' . $u;
    }
    if ($imgProxyBase !== '' && stripos($u, 'static.tourvisor.ru') !== false) {
        return rtrim($imgProxyBase, '/') . '?url=' . rawurlencode($u);
    }
    return $u;
}

/** @return array<string, mixed>|null */
function thh_fetch_hotel(string $tvApiBase, int $hotelId): ?array
{
    if ($hotelId <= 0 || $tvApiBase === '') {
        return null;
    }
    $sep = strpos($tvApiBase, '?') !== false ? '&' : '?';
    $url = $tvApiBase . $sep . 'type=hotel&hotelId=' . $hotelId;
    $ch = curl_init($url);
    if ($ch === false) {
        return null;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 400 || !is_string($body) || trim($body) === '') {
        return null;
    }
    $json = json_decode($body, true);
    if (!is_array($json) || empty($json['success']) || !is_array($json['data'] ?? null)) {
        return null;
    }
    return $json['data'];
}

function thh_text(?string $html): string
{
    return trim(strip_tags((string) $html));
}

function thh_clip(string $text, int $limit): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $limit - 1)) . '…';
}

if ($hotelId <= 0) {
    $error = 'Не передан id отеля.';
} else {
    $hotel = thh_fetch_hotel($tvApiBase, $hotelId);
    if (!$hotel) {
        $error = 'Не удалось загрузить отель. Попробуйте позже.';
    }
}

$imagesAll = [];
if (is_array($hotel)) {
    if (!empty($hotel['images']) && is_array($hotel['images'])) {
        foreach ($hotel['images'] as $img) {
            if (is_string($img) && trim($img) !== '') {
                $imagesAll[] = thh_proxy_image($img, $imgProxyBase);
            }
        }
    }
    if ($imagesAll === [] && !empty($hotel['picturelink'])) {
        $pic = thh_proxy_image((string) $hotel['picturelink'], $imgProxyBase);
        if ($pic !== '') {
            $imagesAll[] = $pic;
        }
    }
}
$imagesLb = array_slice($imagesAll, 0, 24);
$mosaic = array_slice($imagesLb, 0, 3);
$totalPhotos = count($imagesAll);

$common = is_array($hotel['common'] ?? null) ? $hotel['common'] : [];
$infra = is_array($hotel['infrastructure'] ?? null) ? $hotel['infrastructure'] : [];
$meals = is_array($hotel['meals'] ?? null) ? $hotel['meals'] : [];
$services = is_array($hotel['services'] ?? null) ? $hotel['services'] : [];
$country = is_array($hotel['country'] ?? null) ? $hotel['country'] : [];
$region = is_array($hotel['region'] ?? null) ? $hotel['region'] : [];

$hotelName = (string) ($hotel['name'] ?? 'Отель');
$hotelCountryId = (int) ($country['id'] ?? 0);
if ($hotelCountryId <= 0) {
    $hotelCountryId = $countryIdHint > 0 ? $countryIdHint : 4;
}
$category = (int) ($hotel['category'] ?? 0);
$rating = isset($hotel['rating']) ? (float) $hotel['rating'] : 0.0;
$placeLabel = trim(implode(', ', array_filter([
    (string) ($region['name'] ?? ''),
    (string) ($country['name'] ?? ''),
])));
$description = thh_text((string) ($common['description'] ?? ''));
$lead = thh_clip($description, 180);

$aboutSections = [];
if ($description !== '') {
    $aboutSections['Описание'] = thh_clip($description, 700);
}
$beach = thh_text((string) ($infra['beach'] ?? ''));
if ($beach !== '') {
    $aboutSections['Пляж'] = thh_clip($beach, 320);
}
$territory = thh_text((string) ($infra['territory'] ?? ''));
if ($territory !== '') {
    $aboutSections['Территория'] = thh_clip($territory, 320);
}
$mealBits = trim(implode("\n", array_filter([
    thh_text((string) ($meals['list'] ?? '')),
    thh_clip(thh_text((string) ($meals['description'] ?? '')), 280),
])));
if ($mealBits !== '') {
    $aboutSections['Питание'] = $mealBits;
}
$svcFree = thh_text((string) ($services['free'] ?? ''));
if ($svcFree !== '') {
    $aboutSections['Услуги'] = thh_clip($svcFree, 280);
}

$windows = [
    [date('Y-m-d', strtotime('+2 days')), date('Y-m-d', strtotime('+46 days'))],
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <title><?php echo htmlspecialchars($hotelName, ENT_QUOTES, 'UTF-8'); ?> — туры | Travel Hub</title>
    <meta name="description" content="<?php echo htmlspecialchars(thh_clip($hotelName . '. Туры с ценами.', 140), ENT_QUOTES, 'UTF-8'); ?>">
    <?php if (!empty($mosaic[0])): ?>
        <link rel="preload" as="image" href="<?php echo htmlspecialchars($mosaic[0], ENT_QUOTES, 'UTF-8'); ?>" fetchpriority="high">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/frontend/css/pages/tv-hotel-hub.css?v=6">
    <?php include __DIR__ . '/../../../backend/components/design_system_head.php'; ?>
</head>
<body class="thh antialiased">
<?php
$current_page = 'popular-hotels';
include __DIR__ . '/../../../backend/components/header.php';
?>

<main class="thh-wrap">
    <a class="thh-back" href="/frontend/window/popular-hotels.php"><i class="fas fa-arrow-left"></i> К отелям</a>

    <?php if ($error): ?>
        <div class="thh-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php else: ?>
        <?php $mosaicClass = 'thh-mosaic' . (count($mosaic) <= 1 ? ' thh-mosaic--one' : ''); ?>
        <section class="<?php echo $mosaicClass; ?>" aria-label="Фото отеля">
            <?php if ($mosaic === []): ?>
                <div class="thh-mosaic__cell thh-mosaic__main thh-mosaic__empty">Нет фото</div>
            <?php else: ?>
                <?php foreach ($mosaic as $i => $src): ?>
                    <button type="button"
                            class="thh-mosaic__cell<?php echo $i === 0 ? ' thh-mosaic__main' : ''; ?>"
                            data-lb-index="<?php echo (int) $i; ?>">
                        <img src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>"
                             alt="<?php echo htmlspecialchars($hotelName, ENT_QUOTES, 'UTF-8'); ?>"
                             loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>"
                             <?php echo $i === 0 ? 'fetchpriority="high"' : ''; ?>>
                        <?php if ($i === count($mosaic) - 1 && $totalPhotos > count($mosaic)): ?>
                            <span class="thh-mosaic__more" data-open-gallery="1">Фото · <?php echo (int) min(24, $totalPhotos); ?></span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="thh-title-block">
            <div>
                <div class="thh-kicker">
                    <?php if ($category > 0): ?><span class="thh-pill"><?php echo (int) $category; ?>★</span><?php endif; ?>
                    <?php if ($rating > 0): ?>
                        <span class="thh-pill thh-pill--star"><i class="fas fa-star"></i> <?php echo htmlspecialchars(number_format($rating, 1, '.', ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                    <?php if ($placeLabel !== ''): ?>
                        <span class="thh-pill"><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($placeLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </div>
                <h1 class="thh-title"><?php echo htmlspecialchars($hotelName, ENT_QUOTES, 'UTF-8'); ?></h1>
                <?php if ($lead !== ''): ?>
                    <p class="thh-lead"><?php echo htmlspecialchars($lead, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <p class="thh-dep-line">Вылет из <?php echo htmlspecialchars($departureName, ENT_QUOTES, 'UTF-8'); ?> · 2 взрослых</p>
            </div>
        </section>

        <section id="thh-tours" class="thh-offers" aria-label="Туры">
            <div class="thh-offers__head">
                <div>
                    <h2>Туры в этот отель</h2>
                    <p class="thh-offers__sub">Готовые предложения на ближайшие даты — без поиска</p>
                </div>
                <select id="thh-sort" class="thh-sort" aria-label="Сортировка">
                    <option value="price-asc">Дешевле</option>
                    <option value="price-desc">Дороже</option>
                    <option value="date">По дате</option>
                </select>
            </div>

            <div id="thh-loading" class="thh-loading"><i class="fas fa-spinner fa-spin"></i> Загружаем туры…</div>
            <div id="thh-error" class="thh-error hidden"></div>
            <div id="thh-empty" class="thh-empty hidden">Сейчас нет туров в этот отель. Загляните позже или выберите другой отель.</div>
            <div id="thh-list" class="thh-tour-grid th-tour-grid" aria-live="polite"></div>
            <div id="thh-more" class="thh-more hidden">
                <button type="button" id="thh-more-btn">Показать ещё</button>
            </div>
        </section>

        <?php if ($aboutSections !== []): ?>
        <section class="thh-about" aria-label="Об отеле">
            <h2>Об отеле</h2>
            <?php $first = true; foreach ($aboutSections as $title => $body): ?>
                <details<?php echo $first ? '' : ''; ?>>
                    <summary><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></summary>
                    <div class="thh-about__body"><?php echo htmlspecialchars($body, ENT_QUOTES, 'UTF-8'); ?></div>
                </details>
            <?php $first = false; endforeach; ?>
        </section>
        <?php endif; ?>

        <div class="thh-sticky">
            <div class="thh-sticky__price">
                Туры
                <strong id="thh-sticky-from">—</strong>
            </div>
            <a href="#thh-tours">К предложениям</a>
        </div>
    <?php endif; ?>
</main>

<?php if (!$error && $imagesLb !== []): ?>
<div class="thh-lb" id="thh-lb" role="dialog" aria-modal="true" aria-label="Галерея">
    <div class="thh-lb__inner">
        <button type="button" class="thh-lb__close" id="thh-lb-close" aria-label="Закрыть">×</button>
        <button type="button" class="thh-lb__nav thh-lb__nav--prev" id="thh-lb-prev" aria-label="Назад">‹</button>
        <img id="thh-lb-img" src="" alt="">
        <button type="button" class="thh-lb__nav thh-lb__nav--next" id="thh-lb-next" aria-label="Далее">›</button>
        <div class="thh-lb__count" id="thh-lb-count"></div>
    </div>
</div>
<?php endif; ?>

<script src="/frontend/js/tour-link-utils.js?v=1"></script>
<script>
(function () {
    if (<?php echo $error ? 'true' : 'false'; ?>) return;

    var CFG = {
        apiBase: <?php echo json_encode($tvApiBase, JSON_UNESCAPED_UNICODE); ?>,
        imageProxy: <?php echo json_encode($imgProxyBase, JSON_UNESCAPED_UNICODE); ?>,
        hotelId: <?php echo (int) $hotelId; ?>,
        hotelName: <?php echo json_encode($hotelName, JSON_UNESCAPED_UNICODE); ?>,
        countryId: <?php echo (int) $hotelCountryId; ?>,
        countryName: <?php echo json_encode((string) ($country['name'] ?? ''), JSON_UNESCAPED_UNICODE); ?>,
        regionName: <?php echo json_encode((string) ($region['name'] ?? ''), JSON_UNESCAPED_UNICODE); ?>,
        category: <?php echo (int) $category; ?>,
        rating: <?php echo json_encode(number_format($rating, 1, '.', ''), JSON_UNESCAPED_UNICODE); ?>,
        departureId: <?php echo (int) $departureId; ?>,
        departureName: <?php echo json_encode($departureName, JSON_UNESCAPED_UNICODE); ?>,
        images: <?php echo json_encode(array_values($imagesLb), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        windows: <?php echo json_encode($windows); ?>,
        pageSize: 25,
        adults: 2
    };

    var state = { tours: [], shown: 0, loading: false, minPrice: 0 };

    var el = {
        list: document.getElementById('thh-list'),
        loading: document.getElementById('thh-loading'),
        empty: document.getElementById('thh-empty'),
        error: document.getElementById('thh-error'),
        sort: document.getElementById('thh-sort'),
        more: document.getElementById('thh-more'),
        moreBtn: document.getElementById('thh-more-btn'),
        stickyFrom: document.getElementById('thh-sticky-from'),
        countHint: null
    };

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;');
    }

    function fmtPrice(n) {
        var num = parseInt(String(n), 10) || 0;
        if (!num) return '—';
        return num.toLocaleString('ru-RU') + ' ₽';
    }

    function ymdAdd(startYmd, nights) {
        var p = String(startYmd).split('-');
        if (p.length !== 3) return '';
        var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
        d.setDate(d.getDate() + (parseInt(nights, 10) || 0));
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + '-' + m + '-' + day;
    }

    function tourStartYmd(t) {
        var d = t && (t.date || t.flyDate || t.checkIn || '');
        if (!d) return '';
        d = String(d);
        if (/^\d{4}-\d{2}-\d{2}/.test(d)) return d.slice(0, 10);
        var m = d.match(/(\d{2})\.(\d{2})\.(\d{4})/);
        if (m) return m[3] + '-' + m[2] + '-' + m[1];
        return '';
    }

    function tvUrl(type, params) {
        var u;
        try { u = new URL(CFG.apiBase); } catch (e) { u = new URL(CFG.apiBase, window.location.origin); }
        u.searchParams.set('type', type);
        Object.keys(params || {}).forEach(function (k) {
            if (params[k] != null && params[k] !== '') u.searchParams.set(k, String(params[k]));
        });
        return u.toString();
    }

    async function tvFetch(type, params) {
        var forceLive = !!(params && params._forceLive);
        var cacheOnly = !!(params && params._cacheOnly);
        var api = Object.assign({}, params || {});
        delete api._forceLive;
        delete api._cacheOnly;
        if (type === 'search-cached') {
            api.cacheScope = 'country_page';
            api.slim = '1';
            if (forceLive) api.live = '1';
            if (cacheOnly) api.cacheOnly = '1';
            api._t = String(Date.now());
        }
        var r = await fetch(tvUrl(type, api), { method: 'GET', cache: 'no-store' });
        var text = await r.text();
        try { return text ? JSON.parse(text) : { success: false }; } catch (e) { return { success: false }; }
    }

    function flattenTours(rawHotels) {
        var out = [];
        var wantId = CFG.hotelId;
        (rawHotels || []).forEach(function (h) {
            if (!h) return;
            var hid = parseInt(h.id, 10) || 0;
            if (wantId && hid && hid !== wantId) return;
            var tours = Array.isArray(h.tours) ? h.tours : [];
            tours.forEach(function (t) {
                if (!t) return;
                var price = parseInt(t.totalPrice || t.price || t.priceRub || t.cost, 10) || 0;
                if (price <= 0) return;
                var key = String(t.id || '') + '|' + tourStartYmd(t) + '|' + String(t.nights || '') + '|' + price;
                out.push({ hotel: h, tour: t, price: price, key: key });
            });
        });
        return out;
    }

    function dedupe(list) {
        var seen = {};
        var out = [];
        list.forEach(function (it) {
            if (seen[it.key]) return;
            seen[it.key] = true;
            out.push(it);
        });
        return out;
    }

    function sortTours(list) {
        var mode = (el.sort && el.sort.value) || 'price-asc';
        var arr = list.slice();
        arr.sort(function (a, b) {
            if (mode === 'price-desc') return b.price - a.price;
            if (mode === 'date') return String(tourStartYmd(a.tour)).localeCompare(String(tourStartYmd(b.tour)));
            return a.price - b.price;
        });
        return arr;
    }

    function tourDetailUrl(item) {
        var h = item.hotel || {};
        var t = item.tour || {};
        var link = h.hotelDescriptionLink || h.hoteldescriptionlink || h.link || '';
        if (window.TourLinkUtils && TourLinkUtils.sanitizeTourLink) {
            link = TourLinkUtils.sanitizeTourLink(link) || '';
        }
        var meal = (t.meal && (t.meal.russianName || t.meal.name)) || '';
        var start = tourStartYmd(t);
        var nights = parseInt(t.nights, 10) || 0;
        var op = (t.operator && (t.operator.russianName || t.operator.name)) || t.operatorName || '';
        var params = new URLSearchParams({
            tour_link: link,
            country: CFG.countryName || (h.country && h.country.name) || '',
            hotel_name: CFG.hotelName || h.name || '',
            price: String(item.price || ''),
            nights: String(nights || ''),
            meal: meal,
            room_category: String(t.roomType || 'Стандарт'),
            region: CFG.regionName || (h.region && h.region.name) || '',
            departure_city: CFG.departureName,
            image: (CFG.images && CFG.images[0]) || h.picturelink || '',
            rating: CFG.rating || String(h.rating || ''),
            category: String(CFG.category || h.category || ''),
            date_from: start,
            date_to: start && nights ? ymdAdd(start, nights) : '',
            tour_id: String(t.id || ''),
            adults: String(CFG.adults),
            hotel_id: String(CFG.hotelId),
            departure_id: String(CFG.departureId),
            return_url: window.location.pathname + window.location.search
        });
        if (op) params.set('tour_operator', op);
        return '/frontend/window/tour-detail.php?' + params.toString();
    }

    function mapImg(src) {
        if (!src) return '';
        var u = String(src).trim();
        if (u.indexOf('//') === 0) u = 'https:' + u;
        if (CFG.imageProxy && /static\.tourvisor\.ru/i.test(u)) {
            return CFG.imageProxy + '?url=' + encodeURIComponent(u);
        }
        return u;
    }

    function cardHtml(item) {
        var h = item.hotel || {};
        var t = item.tour || {};
        var start = tourStartYmd(t);
        var nights = parseInt(t.nights, 10) || 0;
        var detailUrl = tourDetailUrl(item);
        var hotelObj = Object.assign({}, h, {
            name: CFG.hotelName || h.name,
            category: CFG.category || h.category,
            rating: CFG.rating || h.rating,
            picturelink: (CFG.images && CFG.images[0]) || h.picturelink,
            images: CFG.images && CFG.images.length ? CFG.images : h.images
        });

        if (window.THTourCard && typeof window.THTourCard.render === 'function') {
            return window.THTourCard.render(hotelObj, {
                tour: t,
                price: item.price,
                adults: CFG.adults,
                dateFrom: start,
                dateTo: start && nights ? ymdAdd(start, nights) : '',
                detailUrl: detailUrl,
                departureCity: CFG.departureName,
                country: CFG.countryName,
                region: CFG.regionName,
                countryId: CFG.countryId,
                imageProxy: CFG.imageProxy,
                getImageUrl: mapImg,
                carousel: true,
                promo: false
            });
        }

        var meal = (t.meal && (t.meal.russianName || t.meal.name)) || '';
        var op = (t.operator && (t.operator.name || t.operator.russianName)) || '';
        return (
            '<a class="thh-tour" href="' + esc(detailUrl) + '">' +
            '<div class="thh-tour__main">' +
            '<span class="thh-tour__date">' + esc(start || t.date || '') + '</span>' +
            (nights ? '<span class="thh-tour__chip">' + nights + ' н.</span>' : '') +
            (meal ? '<span class="thh-tour__chip">' + esc(meal) + '</span>' : '') +
            (op ? '<span class="thh-tour__chip">' + esc(op) + '</span>' : '') +
            '</div>' +
            '<div class="thh-tour__price-side">' +
            '<div class="thh-tour__price"><span>за 2 взр.</span><strong>' + esc(fmtPrice(item.price)) + '</strong></div>' +
            '<span class="thh-tour__go">Выбрать</span></div></a>'
        );
    }

    function render(reset) {
        if (reset) state.shown = 0;
        state.tours = sortTours(state.tours);
        var sorted = state.tours;
        var min = 0;
        sorted.forEach(function (it) {
            if (it.price > 0 && (min === 0 || it.price < min)) min = it.price;
        });
        state.minPrice = min;
        if (el.stickyFrom) el.stickyFrom.textContent = min ? ('от ' + fmtPrice(min)) : '—';

        var headSub = document.querySelector('.thh-offers__sub');
        if (headSub && sorted.length) {
            headSub.textContent = sorted.length + ' предложений · вылет ' + CFG.departureName;
        }

        if (!sorted.length) {
            el.list.innerHTML = '';
            el.empty.classList.remove('hidden');
            el.more.classList.add('hidden');
            return;
        }
        el.empty.classList.add('hidden');
        var next = Math.min(sorted.length, state.shown + CFG.pageSize);
        var html = sorted.slice(state.shown, next).map(cardHtml).join('');
        if (state.shown === 0) el.list.innerHTML = html;
        else el.list.insertAdjacentHTML('beforeend', html);
        state.shown = next;
        el.more.classList.toggle('hidden', state.shown >= sorted.length);
        if (window.THTourCard && typeof window.THTourCard.initCarouselsInContainer === 'function') {
            window.THTourCard.initCarouselsInContainer(el.list);
        }
    }

    async function loadAllWindows() {
        state.loading = true;
        el.loading.classList.remove('hidden');
        el.error.classList.add('hidden');
        el.empty.classList.add('hidden');
        el.list.innerHTML = '';
        el.more.classList.add('hidden');
        state.tours = [];
        state.shown = 0;

        var baseParams = {
            departureId: CFG.departureId,
            countryId: CFG.countryId,
            nightsFrom: 6,
            nightsTo: 14,
            adults: CFG.adults,
            currency: 'RUB',
            hotelIds: String(CFG.hotelId)
        };

        async function fetchWindow(w, opts) {
            var params = Object.assign({}, baseParams, {
                dateFrom: w[0],
                dateTo: w[1]
            });
            return tvFetch('search-cached', Object.assign({}, params, opts || {}));
        }

        try {
            var collected = [];
            var windows = CFG.windows || [];
            if (!windows.length) {
                el.empty.classList.remove('hidden');
                return;
            }

            /* Сначала cache-only по всем окнам параллельно — быстрый первый экран */
            var cacheJobs = windows.map(function (w) { return fetchWindow(w, { _cacheOnly: true }); });
            var cacheResults = await Promise.all(cacheJobs);
            cacheResults.forEach(function (j) {
                if (j && j.success && Array.isArray(j.data)) collected = collected.concat(flattenTours(j.data));
            });
            collected = dedupe(collected);
            if (collected.length) {
                state.tours = collected;
                render(true);
            }

            /* Если кэш пуст — один live-запрос на первое окно (не N live подряд) */
            if (!collected.length) {
                var liveJ = await fetchWindow(windows[0], { _forceLive: true });
                if (liveJ && liveJ.success && Array.isArray(liveJ.data)) {
                    collected = dedupe(flattenTours(liveJ.data));
                }
            }

            state.tours = collected;
            if (!state.tours.length) {
                el.empty.classList.remove('hidden');
            } else if (!state.shown) {
                render(true);
            } else {
                render(true);
            }
        } catch (e) {
            el.error.textContent = e.message || 'Не удалось загрузить туры';
            el.error.classList.remove('hidden');
        } finally {
            state.loading = false;
            el.loading.classList.add('hidden');
        }
    }

    el.sort.addEventListener('change', function () { render(true); });
    el.moreBtn.addEventListener('click', function () { render(false); });

    /* Lightbox */
    var lb = document.getElementById('thh-lb');
    var lbImg = document.getElementById('thh-lb-img');
    var lbCount = document.getElementById('thh-lb-count');
    var lbIdx = 0;
    function openLb(i) {
        if (!lb || !CFG.images.length) return;
        lbIdx = Math.max(0, Math.min(CFG.images.length - 1, i || 0));
        lbImg.src = CFG.images[lbIdx];
        lbCount.textContent = (lbIdx + 1) + ' / ' + CFG.images.length;
        lb.classList.add('is-open');
    }
    function closeLb() { if (lb) lb.classList.remove('is-open'); }
    function stepLb(d) {
        if (!CFG.images.length) return;
        lbIdx = (lbIdx + d + CFG.images.length) % CFG.images.length;
        lbImg.src = CFG.images[lbIdx];
        lbCount.textContent = (lbIdx + 1) + ' / ' + CFG.images.length;
    }
    document.querySelectorAll('[data-lb-index]').forEach(function (node) {
        node.addEventListener('click', function (e) {
            e.preventDefault();
            if (e.target && e.target.getAttribute && e.target.getAttribute('data-open-gallery')) openLb(0);
            else openLb(parseInt(node.getAttribute('data-lb-index'), 10) || 0);
        });
    });
    var lbClose = document.getElementById('thh-lb-close');
    var lbPrev = document.getElementById('thh-lb-prev');
    var lbNext = document.getElementById('thh-lb-next');
    if (lbClose) lbClose.addEventListener('click', closeLb);
    if (lbPrev) lbPrev.addEventListener('click', function () { stepLb(-1); });
    if (lbNext) lbNext.addEventListener('click', function () { stepLb(1); });
    if (lb) lb.addEventListener('click', function (e) { if (e.target === lb) closeLb(); });

    loadAllWindows();
})();
</script>

<?php include __DIR__ . '/../../../backend/components/footer.php'; ?>
</body>
</html>
