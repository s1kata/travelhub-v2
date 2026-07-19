<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/components/tourvisor_proxy_url.php';
require_once __DIR__ . '/../../backend/components/security_helper.php';
require_once __DIR__ . '/../../backend/components/tour_link_sanitize.php';
require_once __DIR__ . '/../../backend/components/yandex_metrika.php';
session_start();

$th_ym_id = th_yandex_metrika_counter_id();

$tour_link = isset($_GET['tour_link']) ? trim((string) $_GET['tour_link']) : '';
$tour_link = tour_link_sanitize_for_app($tour_link);
$country = isset($_GET['country']) ? trim((string) $_GET['country']) : '';
$hotel_name = isset($_GET['hotel_name']) ? trim((string) $_GET['hotel_name']) : '';
$price = isset($_GET['price']) ? trim((string) $_GET['price']) : '';
$nights = isset($_GET['nights']) ? trim((string) $_GET['nights']) : '';
$meal_raw = isset($_GET['meal']) ? trim((string) $_GET['meal']) : '';
$meal_map = ['AI'=>'Всё включено','UAI'=>'Ультра всё включено','BB'=>'Завтрак','HB'=>'Завтрак + ужин','HB+'=>'Завтрак + ужин (улучш.)','FB'=>'Завтрак + обед + ужин','RO'=>'Без питания','SC'=>'Самообслуживание','AL'=>'Всё включено'];
$meal = isset($meal_map[strtoupper($meal_raw)]) ? $meal_map[strtoupper($meal_raw)] : $meal_raw;
$region = isset($_GET['region']) ? trim((string) $_GET['region']) : '';
$departure_city = isset($_GET['departure_city']) ? trim((string) $_GET['departure_city']) : '';
$departure_id_param = isset($_GET['departure_id']) ? (int) $_GET['departure_id'] : 0;
$date_from = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : '';
$from_promo = isset($_GET['from_promo']) && (string) $_GET['from_promo'] === '1';
$image = isset($_GET['image']) ? trim((string) $_GET['image']) : '';
$description = isset($_GET['description']) ? trim((string) $_GET['description']) : '';
$rating = isset($_GET['rating']) ? trim((string) $_GET['rating']) : '';
$category = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
$room_category = isset($_GET['room_category']) ? trim((string) $_GET['room_category']) : 'Стандарт';
$flight_info = isset($_GET['flight_info']) ? trim((string) $_GET['flight_info']) : '';
$tour_id = isset($_GET['tour_id']) ? trim((string) $_GET['tour_id']) : '';
$hotel_id = isset($_GET['hotel_id']) ? trim((string) $_GET['hotel_id']) : '';
$return_url = isset($_GET['return_url']) ? (string) $_GET['return_url'] : '';
$tour_search_adults = null;
if (isset($_GET['adults']) && (string) $_GET['adults'] !== '') {
    $tour_search_adults = max(1, min(9, (int) $_GET['adults']));
}
$tour_search_childs = isset($_GET['childs']) ? trim((string) $_GET['childs']) : '';
$tour_tourists_label = '';
if ($tour_search_adults !== null || $tour_search_childs !== '') {
    $parts_t = [];
    if ($tour_search_adults !== null) {
        $parts_t[] = $tour_search_adults . ' ' . ($tour_search_adults === 1 ? 'взрослый' : 'взрослых');
    }
    if ($tour_search_childs !== '') {
        $ages = [];
        foreach (explode(',', $tour_search_childs) as $seg) {
            $seg = trim($seg);
            if ($seg === '' || !preg_match('/^-?\d+$/', $seg)) {
                continue;
            }
            $ai = (int) $seg;
            if ($ai >= 0 && $ai <= 17) {
                $ages[] = $ai;
            }
        }
        if ($ages !== []) {
            $parts_t[] = 'Дети (возраст, лет): ' . implode(', ', $ages);
        }
    }
    $tour_tourists_label = implode(', ', $parts_t);
}

$tour_price_adults_n = ($tour_search_adults !== null) ? $tour_search_adults : 2;

$months_ru = [ 1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля', 5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа', 9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря' ];
function format_date_ru($dateStr, $months) {
    if ($dateStr === '') return '';
    $s = trim($dateStr);
    $t = false;
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s)) {
        $t = strtotime($s);
    } elseif (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $s, $m)) {
        /* dd-mm-yyyy из URL (карточки поиска) */
        $t = mktime(0, 0, 0, (int) $m[2], (int) $m[1], (int) $m[3]);
    } else {
        $t = strtotime($s);
    }
    if ($t === false) return $dateStr;
    $d = (int) date('j', $t);
    $m = (int) date('n', $t);
    $y = date('Y', $t);
    return $d . ' ' . ($months[$m] ?? $m) . ' ' . $y;
}

/** Квадратные метры: &#178; / &sup2; → символ ² (после htmlspecialchars). */
function th_tour_desc_html_entities(string $escaped): string {
    $s = str_replace(
        ['&amp;#178;', '&#178;', '&amp;#xb2;', '&#xb2;', '&amp;sup2;', '&sup2;'],
        '²',
        $escaped
    );
    return str_replace(
        ['&amp;#179;', '&#179;', '&amp;#xb3;', '&#xb3;', '&amp;sup3;', '&sup3;'],
        '³',
        $s
    );
}

/** Значение поля даты для календаря: 20-04-2026 (из Y-m-d из URL). */
function th_input_date_dmY(string $raw): string {
    if ($raw === '') {
        return '';
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $raw)) {
        return $raw;
    }

    return $raw;
}
/** Цена для бейджа: без дубля «₽», если в параметре уже валюта из Intl */
function th_tour_price_html(string $raw): string {
    $s = trim($raw);
    if ($s === '') {
        return '';
    }
    if (preg_match('/[₽]|руб/u', $s)) {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8') . ' ₽';
}

$date_from_fmt = format_date_ru($date_from, $months_ru);
$date_to_fmt = format_date_ru($date_to, $months_ru);
$date_range_display = ($date_from_fmt && $date_to_fmt) ? ($date_from_fmt === $date_to_fmt ? $date_from_fmt : $date_from_fmt . ' — ' . $date_to_fmt) : ($date_from_fmt ?: $date_to_fmt ?: '');
$nights_display = $nights !== '' && is_numeric($nights) ? ($nights . ' ночей') : $nights;

$is_logged_in = isset($_SESSION['user_id']);
$tinkoff_terminal_key = trim((string) (getenv('TINKOFF_TERMINAL_KEY') ?: ($_ENV['TINKOFF_TERMINAL_KEY'] ?? '')));
$tinkoff_password = trim((string) (getenv('TINKOFF_PASSWORD') ?: ($_ENV['TINKOFF_PASSWORD'] ?? '')));
$online_payment_enabled = ($tinkoff_terminal_key !== '' && $tinkoff_password !== '');

$current_page = 'tour-detail';
$tv_image_proxy = get_tourvisor_image_proxy_base_url();

function normalize_tourvisor_image_url(string $src, string $proxy): string {
    $src = trim($src);
    if ($src === '') return $src;
    if ($proxy === '') return $src;

    // Поддерживаем https://, http://, //static..., static... и прямой path hotel_pics/...
    if (preg_match('#^https?://static\.tourvisor\.ru/#i', $src)) {
        return $proxy . '?url=' . rawurlencode(preg_replace('#^https:#i', 'http:', $src));
    }
    if (strpos($src, '//static.tourvisor.ru/') === 0) {
        return $proxy . '?url=' . rawurlencode('http:' . $src);
    }
    if (strpos($src, 'static.tourvisor.ru/') === 0) {
        return $proxy . '?url=' . rawurlencode('http://' . $src);
    }
    if (strpos($src, '/hotel_pics/') === 0) {
        return $proxy . '?path=' . rawurlencode(ltrim($src, '/'));
    }
    if (strpos($src, 'hotel_pics/') === 0) {
        return $proxy . '?path=' . rawurlencode($src);
    }
    return $src;
}

$image = normalize_tourvisor_image_url($image, $tv_image_proxy);

/** До 6 уникальных фото из gallery_b64 (передаётся с карточки тура). */
$gallery_images = [];
if (!empty($_GET['gallery_b64'])) {
    $gRaw = base64_decode((string) $_GET['gallery_b64'], true);
    if ($gRaw !== false) {
        $gDecoded = json_decode($gRaw, true);
        if (is_array($gDecoded)) {
            $seenGallery = [];
            foreach ($gDecoded as $gItem) {
                $gSrc = normalize_tourvisor_image_url(trim((string) $gItem), $tv_image_proxy);
                if ($gSrc === '' || isset($seenGallery[$gSrc])) {
                    continue;
                }
                $seenGallery[$gSrc] = true;
                $gallery_images[] = $gSrc;
                if (count($gallery_images) >= 6) {
                    break;
                }
            }
        }
    }
}
if ($gallery_images === [] && $image !== '') {
    $gallery_images[] = $image;
}
$gallery_count = count($gallery_images);
$gallery_multi = $gallery_count > 1;

// SEO (seo_head.php)
$page_title = ($hotel_name !== '') ? ($hotel_name . ' — Travel Hub') : 'Тур — Travel Hub';
$desc_plain = $description !== '' ? preg_replace('/\s+/u', ' ', trim(strip_tags(html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8')))) : '';
$page_description = $desc_plain !== '' ? mb_substr($desc_plain, 0, 160) : 'Бронирование тура в Travel Hub. Подбор отелей, виз и трансферов. Персональный сервис.';
$page_keywords = 'тур, бронирование, отель, Travel Hub';
if ($image !== '' && preg_match('#\Ahttps?://#i', $image)) {
    $page_image = $image;
} elseif ($image !== '') {
    $page_image = '/' . ltrim($image, '/');
} else {
    $page_image = '/frontend/favicon.svg';
}
$canonical_url = 'https://travelhub63.ru/frontend/window/tour-detail.php';
$tour_id_q = isset($_GET['tour_id']) ? trim((string) $_GET['tour_id']) : '';
if ($tour_id_q !== '') {
    $canonical_url .= '?tour_id=' . rawurlencode($tour_id_q);
} elseif (!empty($_SERVER['QUERY_STRING'])) {
    $canonical_url .= '?' . $_SERVER['QUERY_STRING'];
}
$page_lang = 'ru';

$booking_profile = [];
if ($is_logged_in) {
    try {
        $stmt = $pdo->prepare('SELECT name, email, phone, city, passport_series, passport_number, passport_issued_by, passport_issue_date, passport_expiry_date FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $_SESSION['user_id']]);
        $booking_profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $booking_profile = [];
    }
}

$tour_booked = false;
$page_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
if ($is_logged_in && ($tour_link !== '' || $page_url !== '')) {
    try {
        $stmt = $pdo->prepare('SELECT 1 FROM tour_bookings WHERE user_id = ? AND (tour_link = ? OR tour_link = ?) LIMIT 1');
        $stmt->execute([(int) $_SESSION['user_id'], $page_url, $tour_link]);
        $tour_booked = (bool) $stmt->fetch();
    } catch (Throwable $e) {
        // таблица может отсутствовать
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <?php include __DIR__ . '/../../backend/components/seo_head.php'; ?>
    <link rel="icon" type="image/svg+xml" href="/frontend/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="/frontend/css/pages/tour-detail.css?v=1">
    <?php include __DIR__ . '/../../backend/components/design_system_head.php'; ?>
    <script>window.__TH_YM_ID=<?php echo json_encode((string)$th_ym_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"></head>
<body class="th-page-tour-detail">
<?php
$back_href = 'javascript:history.back()';
if ($return_url !== '' && $return_url[0] === '/') {
    $back_href = $return_url;
    if (stripos($back_href, 'tv_restore=') === false) {
        $back_href .= (strpos($back_href, '?') !== false ? '&' : '?') . 'tv_restore=1';
    }
}
?>
    <?php
    $login_redirect = '/frontend/window/login.php?redirect=' . rawurlencode(isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/frontend/window/tour-detail.php');
    /* Старая цена для акционных туров: +15% округлённая до 100 */
    $td_price_digits = preg_replace('/[^\d]/', '', $price);
    $td_price_num = $td_price_digits !== '' ? (int)$td_price_digits : 0;
    // Hard funnel: не показываем выдуманную «старую» цену
    $td_old_price  = 0;
    ?>
    <main class="th-detail__page">
        <div class="th-detail__container">

            <!-- Кнопка «Назад» -->
            <a href="<?php echo htmlspecialchars($back_href); ?>" class="th-detail__back">
                <i class="fas fa-arrow-left"></i>
                <span>Назад к результатам</span>
            </a>

            <div class="th-detail__layout">

                <!-- ═══ ЛЕВАЯ КОЛОНКА ═══ -->
                <div class="th-detail__main">

                    <!-- 1. Галерея -->
                    <div class="th-detail__gallery" id="th-detail-gallery">
                        <div class="th-detail__gallery-track" id="th-gallery-track">
                            <?php if ($gallery_images !== []): ?>
                            <?php foreach ($gallery_images as $gi => $gimg): ?>
                            <img class="th-detail__gallery-slide<?php echo $gi === 0 ? ' is-active' : ''; ?>"<?php echo $gi === 0 ? ' id="th-gallery-img-main" fetchpriority="high"' : ''; ?>
                                 src="<?php echo htmlspecialchars($gimg); ?>"
                                 alt="<?php echo htmlspecialchars($hotel_name ?: 'Фото отеля'); ?>"
                                 loading="eager"
                                 decoding="async"
                                 onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&q=80'">
                            <?php endforeach; ?>
                            <?php else: ?>
                            <div class="th-detail__gallery-slide" style="background:linear-gradient(135deg,#1e293b,#334155);display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-hotel" style="font-size:64px;color:rgba(255,255,255,0.15)"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($from_promo): ?><span class="th-detail__badge th-detail__badge--promo">Акция</span><?php endif; ?>
                        <button class="th-detail__gallery-arrow th-detail__gallery-arrow--prev" id="th-gallery-prev"<?php echo $gallery_multi ? ' style="display:flex"' : ' style="display:none"'; ?> aria-label="Предыдущее фото">&#8249;</button>
                        <button class="th-detail__gallery-arrow th-detail__gallery-arrow--next" id="th-gallery-next"<?php echo $gallery_multi ? ' style="display:flex"' : ' style="display:none"'; ?> aria-label="Следующее фото">&#8250;</button>
                        <div class="th-detail__gallery-dots" id="th-gallery-dots"><?php if ($gallery_multi): ?><?php foreach ($gallery_images as $gdi => $_gimg): ?><button type="button" class="th-detail__gallery-dot<?php echo $gdi === 0 ? ' active' : ''; ?>" aria-label="Фото <?php echo (int) $gdi + 1; ?>"></button><?php endforeach; ?><?php endif; ?></div>
                        <span class="th-detail__gallery-counter" id="th-gallery-counter"<?php echo $gallery_multi ? '' : ' style="display:none"'; ?>>1 / <?php echo max(1, $gallery_count); ?></span>
                    </div>

                    <!-- 2. Название + гео + звёзды -->
                    <div class="th-detail__header">
                        <div class="th-detail__header-info">
                            <?php if ($hotel_name): ?>
                            <h1 class="th-detail__hotel-name"><?php echo htmlspecialchars($hotel_name); ?></h1>
                            <?php endif; ?>
                            <?php if ($country || $region): ?>
                            <p class="th-detail__geo">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($country); ?><?php if ($region): ?>, <?php echo htmlspecialchars($region); ?><?php endif; ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php if ($category !== ''): ?>
                        <div class="th-detail__stars"><?php echo str_repeat('★', min(max((int)$category, 1), 5)); ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if ($price !== '' || $td_old_price > 0): ?>
                    <div class="th-detail__top-price" id="th-detail-top-price">
                        <?php if ($td_old_price > 0): ?>
                        <span class="th-detail__top-old-price"><?php echo number_format($td_old_price, 0, '', "\xc2\xa0"); ?>&nbsp;₽</span>
                        <?php endif; ?>
                        <span class="th-detail__top-price-main"><?php echo th_tour_price_html($price); ?></span>
                        <span class="th-detail__top-price-note">за <?php echo (int)$tour_price_adults_n; ?> взрослых</span>
                        <?php if ($from_promo): ?><span class="th-detail__promo-label">Акционная цена</span><?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- 3. Чипсы ключевой информации -->
                    <?php if ($date_range_display || $nights !== '' || $tour_tourists_label !== '' || $meal || $room_category || $departure_city || $flight_info || $tour_id): ?>
                    <div class="th-detail__chips">
                        <?php if ($date_range_display): ?>
                        <div class="th-detail__chip"><span>📅</span><div><b>Даты</b><span><?php echo htmlspecialchars($date_range_display); ?></span></div></div>
                        <?php endif; ?>
                        <?php if ($nights !== ''): ?>
                        <div class="th-detail__chip"><span>🌙</span><div><b>Ночей</b><span><?php echo htmlspecialchars($nights_display ?: $nights); ?></span></div></div>
                        <?php endif; ?>
                        <?php if ($tour_tourists_label !== ''): ?>
                        <div class="th-detail__chip"><span>👥</span><div><b>Туристы</b><span><?php echo htmlspecialchars($tour_tourists_label); ?></span></div></div>
                        <?php endif; ?>
                        <?php if ($meal): ?>
                        <div class="th-detail__chip"><span>🍽</span><div><b>Питание</b><span><?php echo htmlspecialchars($meal); ?></span></div></div>
                        <?php endif; ?>
                        <?php if ($room_category): ?>
                        <div class="th-detail__chip"><span>🏨</span><div><b>Номер</b><span><?php echo htmlspecialchars($room_category); ?></span></div></div>
                        <?php endif; ?>
                        <?php if ($departure_city): ?>
                        <div class="th-detail__chip"><span>✈</span><div><b>Вылет</b><span><?php echo htmlspecialchars($departure_city); ?></span></div></div>
                        <?php endif; ?>
                        <?php if ($flight_info && !$tour_id): ?>
                        <div class="th-detail__chip th-detail__chip--flight"><div><span><?php echo htmlspecialchars($flight_info); ?></span></div></div>
                        <?php endif; ?>
                        <?php if ($tour_id): ?>
                        <div id="flight-info-list-item" class="th-detail__chip th-detail__chip--flight">
                            <span aria-hidden="true">✈</span>
                            <div>
                                <b>Перелёт</b>
                                <span id="flight-airline-chip-label" class="th-detail__chip-airline"></span>
                                <span id="flight-info-from-api" class="th-detail__flight-lines">Уточните детали перелёта у нашего менеджера</span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div id="operator-chip" class="th-detail__chip hidden"><span>🏢</span><div><b>Туроператор</b><span id="operator-chip-value"></span></div></div>
                    </div>
                    <?php endif; ?>

                    <!-- CTA перенесены в сайдбар (под промокод) -->

                    <!-- 4. Описание (из URL-параметров) -->
                    <?php if ($description !== ''): ?>
                    <section class="th-detail__section">
                        <h2 class="th-detail__section-title">Об отеле</h2>
                        <div class="th-detail__desc"><?php echo nl2br(th_tour_desc_html_entities(htmlspecialchars($description, ENT_QUOTES, 'UTF-8'))); ?></div>
                    </section>
                    <?php endif; ?>

                    <?php /* Перелёт только в чипах выше — дубль #tour-flights-block убран по макету */ ?>

                    <!-- 6. Информация об отеле из API (галерея, описание, инфраструктура) — ниже блока рейса -->
                    <div id="tour-hotel-info-block" class="th-detail__section hidden">
                        <h2 class="th-detail__section-title">Информация об отеле</h2>
                        <div id="tour-hotel-info-content"></div>
                    </div>

                    <!-- 8. Дубль CTA убран из потока: один primary в сайдбаре / mobile sticky -->
                    <section id="th-detail-lead-bottom" class="th-detail__section th-detail__lead-bottom" hidden aria-hidden="true"></section>

                </div><!-- end .th-detail__main -->

                <!-- ═══ ПРАВАЯ КОЛОНКА (sticky) ═══ -->
                <aside class="th-detail__sidebar">
                    <div class="th-detail__price-card" id="th-detail-price-card">

                        <!-- Блок цены -->
                        <?php if ($price !== '' || $td_old_price > 0): ?>
                        <div class="th-detail__price-wrap">
                            <?php if ($td_old_price > 0): ?>
                            <span class="th-detail__old-price"><?php echo number_format($td_old_price, 0, '', "\xc2\xa0"); ?>&nbsp;₽</span>
                            <?php endif; ?>
                            <span class="th-detail__price-label">за <?php echo (int)$tour_price_adults_n; ?> взрослых</span>
                            <span class="th-detail__price" id="tour-detail-hero-price"><?php echo th_tour_price_html($price); ?></span>
                            <span id="tour-detail-hero-price-noimg" style="display:none"></span>
                            <?php if ($from_promo): ?><span class="th-detail__promo-label">Акционная цена</span><?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="th-detail__promo-block mt-4 pt-4 border-t border-slate-200/80" id="th-detail-promo-block">
                            <label for="th-td-promo-input" class="th-detail__promo-label-text">Промокод</label>
                            <div class="th-detail__promo-row">
                                <input type="text" id="th-td-promo-input" autocomplete="off" spellcheck="false" placeholder="Введите промокод" class="th-detail__promo-input">
                                <button type="button" id="th-td-promo-apply" class="th-detail__promo-apply">Применить</button>
                            </div>
                            <p id="th-td-promo-msg" class="th-detail__promo-msg hidden" role="alert"></p>
                        </div>

                        <div id="form-message" class="hidden p-3 rounded-lg text-sm mb-3"></div>
                        <?php if ($tour_booked): ?>
                        <div class="th-detail__booked th-detail__booked--sidebar">
                            <i class="fas fa-check-circle text-emerald-500"></i>
                            <div>
                                <strong>Тур забронирован</strong>
                                <p>Заявка отправлена менеджеру. Мы свяжемся с вами для подтверждения.</p>
                            </div>
                        </div>
                        <?php else: ?>
                        <div id="booking-buttons-wrap" class="th-detail__cta-wrap th-detail__cta-wrap--sidebar">
                            <button type="button" id="btn-booking-without" class="th-detail__cta-btn">
                                <i class="fas fa-plane mr-2"></i> Забронировать
                            </button>
                            <p class="text-xs text-slate-500 mt-1 mb-1 text-center">Имя и телефон — перезвоним за 15 минут. Ни к чему не обязывает.</p>
                            <?php if ($online_payment_enabled): ?>
                            <button type="button" id="btn-booking-with" class="th-detail__cta-btn th-detail__cta-btn--pay-primary"
                                    title="Забронировать тур и оплатить картой онлайн (Т-Банк)">
                                <i class="fas fa-credit-card mr-1"></i>
                                Забронировать и оплатить
                                <span id="cta-price-inline" class="ml-2 inline-flex items-center px-2 py-0.5 rounded bg-white/20 text-sm font-semibold hidden"></span>
                            </button>
                            <p class="th-detail__pay-hint-msg text-xs text-slate-500 mt-1" role="status">
                                Безопасная оплата картой через Т-Банк. Заявка и оплата — одним шагом.
                            </p>
                            <?php endif; ?>
                            <p id="cta-price-note" class="text-xs text-slate-500 hidden mt-1 text-center"></p>
                        </div>
                        <?php endif; ?>

                        <!-- Параметры тура в сайдбаре -->
                        <?php if ($hotel_name || $country || $nights !== '' || $meal || $departure_city || $date_range_display): ?>
                        <div class="th-detail__sidebar-params">
                            <?php if ($hotel_name): ?>
                            <div class="th-detail__sidebar-param"><i class="fas fa-hotel"></i><div><b>Отель</b><span><?php echo htmlspecialchars($hotel_name); ?></span></div></div>
                            <?php endif; ?>
                            <?php if ($country || $region): ?>
                            <div class="th-detail__sidebar-param"><i class="fas fa-globe"></i><div><b>Страна</b><span><?php echo htmlspecialchars($country); ?><?php if ($region): ?>, <?php echo htmlspecialchars($region); ?><?php endif; ?></span></div></div>
                            <?php endif; ?>
                            <?php if ($date_range_display): ?>
                            <div class="th-detail__sidebar-param"><i class="fas fa-calendar"></i><div><b>Даты</b><span><?php echo htmlspecialchars($date_range_display); ?></span></div></div>
                            <?php endif; ?>
                            <?php if ($nights !== ''): ?>
                            <div class="th-detail__sidebar-param"><i class="fas fa-moon"></i><div><b>Ночей</b><span><?php echo htmlspecialchars($nights_display ?: $nights); ?></span></div></div>
                            <?php endif; ?>
                            <?php if ($meal): ?>
                            <div class="th-detail__sidebar-param"><i class="fas fa-utensils"></i><div><b>Питание</b><span><?php echo htmlspecialchars($meal); ?></span></div></div>
                            <?php endif; ?>
                            <?php if ($departure_city): ?>
                            <div class="th-detail__sidebar-param"><i class="fas fa-plane-departure"></i><div><b>Вылет</b><span><?php echo htmlspecialchars($departure_city); ?></span></div></div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                    </div><!-- end .th-detail__price-card -->
                </aside><!-- end .th-detail__sidebar -->

            </div><!-- end .th-detail__layout -->
        </div><!-- end .th-detail__container -->

        <!-- Мобильная липкая кнопка -->
        <div class="th-detail__mobile-sticky" id="th-detail-mobile-sticky">
            <button type="button" class="th-detail__mobile-sticky-btn" id="th-detail-mobile-sticky-btn">
                <i class="fas fa-plane mr-2"></i> Забронировать
            </button>
        </div>

                    <!-- Модальное окно формы заявки -->
                    <div id="booking-modal" class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 bg-black/50 backdrop-blur-sm" aria-modal="true" role="dialog" style="display: none;">
                        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[min(90vh,100dvh)] overflow-y-auto min-w-0">
                            <div class="sticky top-0 bg-white border-b border-slate-200 px-4 py-3 flex justify-between items-center rounded-t-2xl z-10">
                                <h2 class="heading-font font-bold text-slate-800 text-lg" id="booking-modal-title"><?php echo $from_promo ? 'Заявка на акционный тур' : 'Забронировать тур'; ?></h2>
                                <button type="button" id="booking-modal-close" class="w-9 h-9 rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 flex items-center justify-center" aria-label="Закрыть"><i class="fas fa-times"></i></button>
                            </div>
                            <div id="booking-form-step">
                            <form id="booking-form" class="p-4 space-y-3">
                                <input type="hidden" name="booking_type" id="booking-type" value="without_payment">
                                <p class="text-xs text-slate-500 -mt-1 mb-1" id="booking-modal-subtitle">Перезвоним за 15 минут. Без спама.</p>
                                <div>
                                    <label for="b-manager-name" class="block text-sm font-semibold text-slate-700 mb-1">Ваше имя <span class="text-red-500">*</span></label>
                                    <input type="text" id="b-manager-name" autocomplete="name" class="w-full px-4 py-3 rounded-xl border border-slate-300 text-base focus:ring-2 focus:ring-sky-300 focus:border-sky-400" placeholder="Например: Мария">
                                </div>
                                <div>
                                    <label for="b-phone" class="block text-sm font-semibold text-slate-700 mb-1">Номер телефона <span class="text-red-500">*</span></label>
                                    <input type="tel" name="phone" id="b-phone" autocomplete="tel" inputmode="tel" class="w-full px-4 py-3 rounded-xl border border-slate-300 text-base focus:ring-2 focus:ring-sky-300 focus:border-sky-400" placeholder="+7 (999) 123-45-67">
                                    <p class="text-xs text-slate-500 mt-1">Позвоним только по этой заявке — никакой рекламы.</p>
                                </div>
                                <label class="flex items-start gap-2 text-sm text-slate-600 cursor-pointer">
                                    <input type="checkbox" id="b-agree-contact" class="mt-1 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                    <span>Согласие на обработку персональных данных и обратный звонок</span>
                                </label>
                                <div id="booking-modal-msg" class="hidden p-2 rounded-lg text-sm"></div>
                                <button type="submit" id="booking-submit-btn" class="th-btn-manager-request w-full">
                                    ✈ <span id="booking-submit-label">Отправить заявку менеджеру</span>
                                </button>
                            </form>
                            </div>
                        </div>
                    </div>
                </div>
    </main>

    <!-- Лайтбокс для фото отеля -->
    <div id="hotel-image-lightbox" class="fixed inset-0 bg-black/80 flex items-center justify-center z-[121000] hidden">
        <button type="button" data-role="close" class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/90 text-slate-700 flex items-center justify-center shadow"><i class="fas fa-times"></i></button>
        <button type="button" data-role="prev" class="absolute left-4 md:left-10 w-10 h-10 rounded-full bg-white/80 text-slate-700 flex items-center justify-center shadow"><i class="fas fa-chevron-left"></i></button>
        <img src="" alt="<?php echo htmlspecialchars($hotel_name ?: 'Фото отеля', ENT_QUOTES, 'UTF-8'); ?> — фото" class="max-w-[90vw] max-h-[80vh] rounded-2xl shadow-2xl object-contain">
        <button type="button" data-role="next" class="absolute right-4 md:right-10 w-10 h-10 rounded-full bg-white/80 text-slate-700 flex items-center justify-center shadow"><i class="fas fa-chevron-right"></i></button>
    </div>

    <?php include __DIR__ . '/../../backend/components/footer.php'; ?>

    <?php
    $tv_api_base = get_tourvisor_proxy_base_url();
    $tour_detail_config = [
        'csrfToken' => security_csrf_token(),
        'ymId' => $th_ym_id,
        'profile' => $booking_profile,
        'tourLink' => $tour_link,
        'isLoggedIn' => $is_logged_in,
        'loginRedirect' => $login_redirect,
        'paymentEnabled' => $online_payment_enabled,
        'country' => $country,
        'defaultDeparture' => $departure_city,
        'departureId' => $departure_id_param,
        'dateFrom' => $date_from,
        'dateTo' => $date_to,
        'hotelName' => $hotel_name,
        'defaultPrice' => $price,
        'defaultNights' => $nights,
        'defaultMeal' => $meal,
        'fromPromo' => $from_promo,
        'roomCategory' => $room_category,
        'flightInfo' => $flight_info,
        'tourId' => $tour_id,
        'hotelId' => $hotel_id,
        'tvApiBase' => $tv_api_base,
        'tvImageProxy' => $tv_image_proxy,
        'galleryUrls' => $gallery_images,
        'searchAdults' => $tour_search_adults,
        'searchChilds' => $tour_search_childs,
        'searchTouristsLabel' => $tour_tourists_label,
    ];
    ?>
    <script type="application/json" id="tour-detail-config"><?php echo json_encode($tour_detail_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
    <?php
    $_th_fpick_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'tourvisor-flight-pick.js';
    $_th_fpick_ver = is_file($_th_fpick_path) ? (string) filemtime($_th_fpick_path) : '1';
    ?>
    <script src="/frontend/js/th-payment-api.js?v=<?php echo htmlspecialchars(is_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'th-payment-api.js') ? (string) filemtime(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'th-payment-api.js') : '1', ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php
    $_th_promo_apply_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'th-promo-apply.js';
    $_th_promo_apply_ver = is_file($_th_promo_apply_path) ? (string) filemtime($_th_promo_apply_path) : '1';
    ?>
    <script src="/frontend/js/th-promo-apply.js?v=<?php echo htmlspecialchars($_th_promo_apply_ver, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="/frontend/js/th-tour-card.js?v=<?php echo htmlspecialchars(is_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'th-tour-card.js') ? (string) filemtime(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'th-tour-card.js') : '1', ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="/frontend/js/tourvisor-flight-pick.js?v=<?php echo htmlspecialchars($_th_fpick_ver, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/ru.js" defer></script>
    <script>
    (function() {
        var cfgEl = document.getElementById('tour-detail-config');
        var cfg = cfgEl ? (function() { try { return JSON.parse(cfgEl.textContent); } catch (e) { return {}; } })() : {};
        var profile = cfg.profile || {};
        var tourLink = (typeof cfg.tourLink === 'string') ? cfg.tourLink : '';
        var isLoggedIn = !!cfg.isLoggedIn;
        var loginRedirect = (typeof cfg.loginRedirect === 'string') ? cfg.loginRedirect : '/frontend/window/login.php';
        var paymentEnabled = !!cfg.paymentEnabled;
        var csrfToken = (typeof cfg.csrfToken === 'string') ? cfg.csrfToken : '';
        var country = (typeof cfg.country === 'string') ? cfg.country : '';
        var defaultDeparture = (typeof cfg.defaultDeparture === 'string') ? cfg.defaultDeparture : '';
        var dateFrom = (typeof cfg.dateFrom === 'string') ? cfg.dateFrom : '';
        var dateTo = (typeof cfg.dateTo === 'string') ? cfg.dateTo : '';
        var hotelName = (typeof cfg.hotelName === 'string') ? cfg.hotelName : '';
        var defaultPrice = (typeof cfg.defaultPrice === 'string') ? cfg.defaultPrice : '';
        var defaultNights = (typeof cfg.defaultNights === 'string') ? cfg.defaultNights : '';
        var defaultMeal = (typeof cfg.defaultMeal === 'string') ? cfg.defaultMeal : '';
        var roomCategory = (typeof cfg.roomCategory === 'string') ? cfg.roomCategory : '';
        var fromPromo = !!cfg.fromPromo;
        var tourId = (typeof cfg.tourId === 'string') ? cfg.tourId : '';
        var hotelId = (typeof cfg.hotelId === 'string') ? cfg.hotelId : '';
        var tvApiBase = (typeof cfg.tvApiBase === 'string') ? cfg.tvApiBase : '';
        var tvImageProxy = (typeof cfg.tvImageProxy === 'string') ? cfg.tvImageProxy : '';
        var searchAdults = cfg.searchAdults != null ? Number(cfg.searchAdults) : null;
        if (!Number.isFinite(searchAdults) || searchAdults < 1) searchAdults = null;
        var searchChilds = (typeof cfg.searchChilds === 'string') ? cfg.searchChilds.trim() : '';
        var searchTouristsLabel = (typeof cfg.searchTouristsLabel === 'string') ? cfg.searchTouristsLabel.trim() : '';
        var lastTourOperator = '';
        var lastTourPlacement = '';
        var appliedPromoCode = '';
        /** Как на карточках акций: totalPrice раньше price (совпадает с логикой promotions-page.js). */
        function tvTourDetailPickPriceNum(t) {
            if (!t || typeof t !== 'object') return 0;
            var keys = ['totalPrice', 'price', 'priceRub', 'cost'];
            for (var i = 0; i < keys.length; i++) {
                var v = t[keys[i]];
                if (v == null || v === '') continue;
                var n = Number(v);
                if (!isNaN(n) && n > 0) return n;
            }
            return 0;
        }
        function normalizeTvImageUrl(src) {
            src = (typeof src === 'string') ? src.trim() : '';
            if (!src) return '';
            if (/^\/\//.test(src)) {
                src = (typeof location !== 'undefined' && location.protocol === 'https:' ? 'https:' : 'http:') + src;
            }
            if (!/^https?:\/\//i.test(src) && /^hotel_pics\//i.test(src)) {
                src = 'https://static.tourvisor.ru/' + src.replace(/^\/+/, '');
            }
            if (!tvImageProxy) return src;
            if (/^https?:\/\/static\.tourvisor\.ru\//i.test(src)) {
                return tvImageProxy + '?url=' + encodeURIComponent(src.replace(/^https:/i, 'http:'));
            }
            if (/^static\.tourvisor\.ru\//i.test(src)) {
                return tvImageProxy + '?url=' + encodeURIComponent('http://' + src);
            }
            if (/^\/hotel_pics\//i.test(src) || /^hotel_pics\//i.test(src)) {
                return tvImageProxy + '?path=' + encodeURIComponent(src.replace(/^\/+/, ''));
            }
            return src;
        }

        function thTourPhotoNormalizeKey(u) {
            if (!u || typeof u !== 'string') return '';
            var s = u.trim();
            if (!s) return '';
            try {
                var abs = /^https?:/i.test(s) ? s : (s.indexOf('//') === 0 ? 'https:' + s : 'https://' + s.replace(/^\/+/, ''));
                var x = new URL(abs, (typeof location !== 'undefined' ? location.href : 'https://travelhub63.ru/'));
                if (x.searchParams.has('path')) {
                    return 'p:' + String(x.searchParams.get('path') || '').toLowerCase();
                }
                if (x.searchParams.has('url')) {
                    return 'u:' + String(x.searchParams.get('url') || '').replace(/^https:/i, 'http:').toLowerCase();
                }
                var path = (x.pathname || '/').replace(/\/+/g, '/');
                if (path.length > 1) path = path.replace(/\/+$/, '');
                return (x.hostname + path).toLowerCase();
            } catch (e) {
                return s.toLowerCase().replace(/\/+$/, '');
            }
        }

        function collectHotelImagesFromApiData(d, hid) {
            var urls = [];
            var seen = {};
            function add(raw) {
                if (raw == null || raw === '') return;
                var mapped = normalizeTvImageUrl(String(raw).trim());
                if (!mapped) return;
                var k = thTourPhotoNormalizeKey(mapped);
                if (!k || seen[k]) return;
                seen[k] = true;
                urls.push(mapped);
            }
            function addFromObj(obj) {
                if (!obj || typeof obj !== 'object') return;
                if (window.THTourCard && typeof window.THTourCard.collectHotelPhotoRawUrls === 'function') {
                    window.THTourCard.collectHotelPhotoRawUrls(obj).forEach(function (u) { add(u); });
                    return;
                }
                ['picturelink', 'pictureLink', 'mainpicture', 'mainPicture', 'picture', 'photo', 'image', 'img'].forEach(function (k) {
                    if (Object.prototype.hasOwnProperty.call(obj, k)) add(obj[k]);
                });
                var pics = obj.pictures || obj.images || obj.photos || obj.gallery;
                if (pics && Array.isArray(pics)) {
                    pics.forEach(function (p) {
                        if (typeof p === 'string') add(p);
                        else if (p && typeof p === 'object') {
                            add(p.src || p.url || p.link || p.picturelink || p.pictureLink || p.picture || '');
                        }
                    });
                } else if (pics && typeof pics === 'object') {
                    Object.keys(pics).forEach(function (k) { add(pics[k]); });
                }
            }
            addFromObj(d);
            if (d && d.common && typeof d.common === 'object') addFromObj(d.common);
            return urls.slice(0, 6);
        }

        var modal = document.getElementById('booking-modal');
        var modalTitle = document.getElementById('booking-modal-title');
        var form = document.getElementById('booking-form');
        var bookingType = document.getElementById('booking-type');
        var msgPage = document.getElementById('form-message');
        var msgModal = document.getElementById('booking-modal-msg');
        var submitBtn = document.getElementById('booking-submit-btn');
        var bookingFormMode = 'short';
        /** Текст после успешной отправки заявки без оплаты (без автосабмита). */
        var BOOKING_SUCCESS_MSG_SHORT = 'Заявка принята. Перезвоним в течение 15 минут.';

        function tourYmGoal(goal) {
            try {
                if (window.THLeadCapture && typeof window.THLeadCapture.reachGoal === 'function') {
                    window.THLeadCapture.reachGoal(goal);
                    return;
                }
                var raw = (cfg && cfg.ymId != null) ? String(cfg.ymId).replace(/\D/g, '') : '';
                var id = raw ? parseInt(raw, 10) : 0;
                if (id && typeof ym === 'function') ym(id, 'reachGoal', goal);
            } catch (eYm) {}
        }

        function setBookingFormLayout(mode) {
            var submitLabel = document.getElementById('booking-submit-label');
            if (!submitLabel) return;
            if (mode === 'with_payment') submitLabel.textContent = 'Забронировать и оплатить';
            else if (mode === 'book') submitLabel.textContent = 'Забронировать тур';
            else if (mode === 'quick') submitLabel.textContent = 'Отправить заявку';
            else if (mode === 'full') submitLabel.textContent = 'Забронировать тур';
            else submitLabel.textContent = 'Отправить заявку менеджеру';
        }

        function bookingModalSubtitleFor(type) {
            if (type === 'quick_pick') return 'Перезвоним за 15 минут. Без спама.';
            if (type === 'with_payment') return 'Заполните контакты — после заявки откроется оплата картой (Т-Банк).';
            if (type === 'book') return 'Заполните контакты — оформим бронирование тура.';
            return 'Перезвоним за 15 минут. Без спама.';
        }

        function bookingModalTitleFor(type) {
            if (type === 'quick_pick') return 'Подберём тур за 15 минут';
            if (type === 'with_payment') return 'Забронировать и оплатить картой';
            if (type === 'book') return 'Забронировать тур';
            if (fromPromo) return 'Заявка на акционный тур';
            return 'Заявка менеджеру';
        }

        function showPageMsg(text, isError) {
            if (!msgPage) return;
            msgPage.textContent = text;
            msgPage.className = 'p-3 rounded-lg text-sm ' + (isError ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800');
            msgPage.classList.remove('hidden');
        }

        function fillForm() {
            var name = (profile && profile.name) ? String(profile.name).trim() : '';
            var phone = (profile && profile.phone) ? String(profile.phone) : '';
            var elMgrName = document.getElementById('b-manager-name');
            var elPhone = document.getElementById('b-phone');
            if (elMgrName && !elMgrName.value) elMgrName.value = name;
            if (elPhone && !elPhone.value) elPhone.value = phone;
        }

        var btnWithout = document.getElementById('btn-booking-without');
        var btnWith = document.getElementById('btn-booking-with');
        var formStep = document.getElementById('booking-form-step');
        function openModal(type) {
            if (!modal) return;
            if (type === 'with_payment') {
                if (!paymentEnabled) return;
                if (!isLoggedIn) {
                    window.location.href = loginRedirect;
                    return;
                }
            }
            var effectiveType = type;
            var layoutMode = (effectiveType === 'with_payment' || effectiveType === 'book') ? 'book' : (effectiveType === 'quick_pick' ? 'quick' : 'short');
            bookingFormMode = (effectiveType === 'with_payment' || effectiveType === 'book') ? 'full' : 'short';
            setBookingFormLayout(effectiveType === 'with_payment' ? 'with_payment' : layoutMode);
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            if (window.THMobile && window.THMobile.lockScroll) window.THMobile.lockScroll(true);
            if (formStep) formStep.style.display = '';
            if (bookingType) {
                bookingType.value = (effectiveType === 'with_payment') ? 'with_payment' : 'without_payment';
            }
            if (modalTitle) modalTitle.textContent = bookingModalTitleFor(effectiveType);
            var modalSub = document.getElementById('booking-modal-subtitle');
            if (modalSub) modalSub.textContent = bookingModalSubtitleFor(effectiveType);
            fillForm();
            tourYmGoal('tour_booking_modal_open');
            if (msgModal) { msgModal.classList.add('hidden'); msgModal.textContent = ''; }
            /* Восстановление полей после CSRF-релоада — только при явном открытии модалки */
            var pr = window.__thPendingBookingRestore;
            if (pr) {
                window.__thPendingBookingRestore = null;
                setTimeout(function() {
                    var nameEl2 = document.getElementById('b-manager-name');
                    var phoneEl2 = document.getElementById('b-phone');
                    var agreeEl2 = document.getElementById('b-agree-contact');
                    if (nameEl2 && pr.name) nameEl2.value = pr.name;
                    if (phoneEl2 && pr.phone) phoneEl2.value = pr.phone;
                    if (agreeEl2 && pr.agree) agreeEl2.checked = true;
                    if (msgModal) {
                        msgModal.textContent = 'Сессия обновлена. Ваши данные восстановлены — нажмите «Отправить заявку менеджеру».';
                        msgModal.className = 'p-2 rounded-lg text-sm bg-green-100 text-green-800';
                        msgModal.classList.remove('hidden');
                    }
                }, 50);
            }
            var hasTourRef = tourLink || (typeof tourId === 'string' && tourId.trim() !== '');
            if (!hasTourRef || !country) {
                if (msgModal) {
                    msgModal.textContent = 'Не указаны ссылка на тур или страна. Вернитесь к результатам поиска и выберите тур по ссылке «Выбрать».';
                    msgModal.className = 'p-2 rounded-lg text-sm bg-amber-100 text-amber-800';
                    msgModal.classList.remove('hidden');
                }
                if (submitBtn) submitBtn.disabled = true;
            } else {
                if (submitBtn) submitBtn.disabled = false;
            }
        }
        window.openBookingModal = openModal;
        /* Hard funnel: все primary CTA → короткий lead (without_payment), не full/pay */
        if (btnWithout) btnWithout.addEventListener('click', function() { openModal('without_payment'); });
        if (btnWith) btnWith.addEventListener('click', function() {
            openModal('with_payment');
            tourYmGoal('pay_cta_click');
        });
        var mobileStickyBtn = document.getElementById('th-detail-mobile-sticky-btn');
        if (mobileStickyBtn) mobileStickyBtn.addEventListener('click', function() { openModal('without_payment'); });
        (function syncDetailStickyPad() {
            var bar = document.getElementById('th-detail-mobile-sticky');
            if (!bar) return;
            function apply() {
                document.documentElement.style.setProperty('--th-detail-sticky-h', String(bar.offsetHeight + 52) + 'px');
            }
            apply();
            window.addEventListener('resize', apply, { passive: true });
        })();

        function showModalMsg(txt, isError) {
            if (!msgModal) return;
            msgModal.textContent = txt;
            msgModal.className = 'p-2 rounded-lg text-sm ' + (isError ? 'bg-red-100 text-red-800' : 'bg-sky-50 text-sky-800');
            msgModal.classList.remove('hidden');
        }

        var paymentRedirectPending = false;

        function startTbankPayment() {
            if (!paymentEnabled) {
                showModalMsg('Онлайн-оплата временно недоступна.', true);
                return;
            }
            if (!isLoggedIn) {
                window.location.href = loginRedirect;
                return;
            }
            if (typeof ThPaymentApi === 'undefined') {
                showModalMsg('Модуль оплаты не загружен. Обновите страницу.', true);
                return;
            }
            var orderId = ThPaymentApi.generateOrderId('WEB');
            var amount = currentPrice && currentPrice > 0 ? Number(currentPrice) : (parseFloat(String(defaultPrice).replace(/[^\d.,]/g, '').replace(',', '.')) || 0);
            if (amount < 1) amount = 1;
            var desc = (hotelName || 'Тур') + (country ? ', ' + country : '');
            desc = desc.slice(0, 140);
            var origin = window.location.origin || '';
            var returnUrl = origin + '/payment-success?orderId=' + encodeURIComponent(orderId);
            var failReturnUrl = origin + '/payment-fail?orderId=' + encodeURIComponent(orderId);
            if (modalTitle) modalTitle.textContent = 'Переход к оплате';
            showModalMsg('Заявка принята. Открываем страницу Т-Банка…', false);
            if (submitBtn) submitBtn.disabled = true;
            paymentRedirectPending = true;
            ThPaymentApi.createPayment({
                csrfToken: csrfToken,
                amount: amount,
                orderId: orderId,
                description: desc,
                returnUrl: returnUrl,
                failReturnUrl: failReturnUrl
            }).then(function(res) {
                var data = res.data || {};
                if (!data.success || !data.paymentUrl) {
                    throw new Error(data.error || 'Не удалось создать платёж');
                }
                try {
                    sessionStorage.setItem('th_payment_tx', String(data.transactionId || ''));
                    sessionStorage.setItem('th_payment_order', orderId);
                } catch (eSt) {}
                window.location.href = data.paymentUrl;
            }).catch(function(err) {
                paymentRedirectPending = false;
                if (submitBtn) submitBtn.disabled = false;
                var errMsg = (err && err.message) ? String(err.message) : 'Ошибка оплаты. Попробуйте позже.';
                if (/<!DOCTYPE|<html/i.test(errMsg)) {
                    errMsg = 'Сервис оплаты недоступен. Проверьте MOBILE_JWT_SECRET и mobile API на сервере.';
                }
                showModalMsg(errMsg, true);
            });
        }

        function closeModal() {
            if (modal) {
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
            }
            if (window.THMobile && window.THMobile.lockScroll) window.THMobile.lockScroll(false);
        }

        var modalCloseBtn = document.getElementById('booking-modal-close');
        if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
        if (modal) modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });
        (function initBookingFlatpickrWhenReady() {
            var tries = 0;
            function go() {
                var bDateFromEl = document.getElementById('b-date-from');
                var bDateToEl = document.getElementById('b-date-to');
                if (typeof flatpickr !== 'function' || (!bDateFromEl && !bDateToEl)) {
                    if (++tries < 80) { setTimeout(go, 25); }
                    return;
                }
                var today = new Date();
                if (bDateFromEl) {
                    var fpFrom = flatpickr(bDateFromEl, { dateFormat: 'd-m-Y', locale: 'ru', allowInput: false, clickOpens: true, minDate: today, disableMobile: true });
                    if (fpFrom) {
                        bDateFromEl.addEventListener('focus', function() { fpFrom.open(); });
                        bDateFromEl.closest('.b-date-wrap') && bDateFromEl.closest('.b-date-wrap').addEventListener('click', function(e) { e.preventDefault(); bDateFromEl.focus(); fpFrom.open(); });
                    }
                }
                if (bDateToEl) {
                    var fpTo = flatpickr(bDateToEl, { dateFormat: 'd-m-Y', locale: 'ru', allowInput: false, clickOpens: true, minDate: today, disableMobile: true });
                    if (fpTo) {
                        bDateToEl.addEventListener('focus', function() { fpTo.open(); });
                        bDateToEl.closest('.b-date-wrap') && bDateToEl.closest('.b-date-wrap').addEventListener('click', function(e) { e.preventDefault(); bDateToEl.focus(); fpTo.open(); });
                    }
                }
            }
            go();
        })();

        if (form) form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var type = bookingType ? bookingType.value : 'without_payment';
            var mgrNameEl = document.getElementById('b-manager-name');
            var phoneEl = document.getElementById('b-phone');
            var nameVal = mgrNameEl ? mgrNameEl.value.trim() : '';
            var phoneVal = phoneEl ? phoneEl.value.trim() : '';
            if (!nameVal) { showModalMsg('Укажите имя.', true); return; }
            if (!phoneVal) { showModalMsg('Укажите телефон.', true); return; }
            var emailVal = '';
            function thDateToYmd(s) {
                s = (s || '').trim();
                if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;
                var m = s.match(/^(\d{2})-(\d{2})-(\d{4})$/);
                return m ? (m[3] + '-' + m[2] + '-' + m[1]) : s;
            }
            var dateFromVal = (document.getElementById('b-date-from') && document.getElementById('b-date-from').value.trim()) || dateFrom || undefined;
            var dateToVal = (document.getElementById('b-date-to') && document.getElementById('b-date-to').value.trim()) || dateTo || undefined;
            dateFromVal = dateFromVal ? thDateToYmd(dateFromVal) : dateFromVal;
            dateToVal = dateToVal ? thDateToYmd(dateToVal) : dateToVal;
            var nightsVal = (document.getElementById('b-nights') && document.getElementById('b-nights').value.trim()) || defaultNights || '';
            var mealVal = (document.getElementById('b-meal') && document.getElementById('b-meal').value.trim()) || defaultMeal || '';
            var roomVal = (document.getElementById('b-room-category') && document.getElementById('b-room-category').value.trim()) || roomCategory || '';
            function effectiveTourLinkForBooking() {
                try {
                    var loc = window.location;
                    var path = (loc.pathname || '').replace(/\\/g, '/');
                    if (path.indexOf('tour-detail') !== -1) {
                        return loc.origin + path + (loc.search || '');
                    }
                } catch (e0) {}
                var raw = (typeof tourLink === 'string') ? tourLink.trim() : '';
                var sanitized = (typeof TourLinkUtils !== 'undefined' && TourLinkUtils.sanitizeTourLink) ? TourLinkUtils.sanitizeTourLink(raw) : raw;
                if (sanitized) return sanitized;
                if (tourId) return 'tourvisor:tour:' + tourId;
                try {
                    return window.location.origin + window.location.pathname + (window.location.search || '');
                } catch (e) {
                    return '';
                }
            }
            var payload = {
                _csrf_token: (cfg && cfg.csrfToken) ? cfg.csrfToken : '',
                booking_type: type,
                tour_link: effectiveTourLinkForBooking(),
                tour_id: (typeof tourId === 'string' && tourId.trim() !== '') ? tourId.trim() : undefined,
                country: country,
                hotel_name: (typeof hotelName !== 'undefined' ? hotelName : '') || '',
                price: (typeof defaultPrice !== 'undefined' ? defaultPrice : '') || '',
                nights: (typeof defaultNights !== 'undefined' ? defaultNights : '') || '',
                meal: (typeof defaultMeal !== 'undefined' ? defaultMeal : '') || '',
                room_category: (typeof roomCategory !== 'undefined' ? roomCategory : '') || '',
                tour_operator: lastTourOperator || undefined,
                placement: lastTourPlacement || undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
                name: nameVal,
                email: emailVal || undefined,
                phone: phoneVal,
                departure_city: (typeof defaultDeparture !== 'undefined' ? defaultDeparture : '') || undefined,
                search_adults: searchAdults != null ? searchAdults : undefined,
                search_childs: searchChilds || undefined,
                applied_promo: appliedPromoCode || undefined
            };
            if (msgModal) { msgModal.classList.add('hidden'); msgModal.textContent = ''; }
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Отправка...';
            }

            var url = '/backend/api/uon-booking.php';
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(function(r) {
                return r.text().then(function(text) {
                    try {
                        return { ok: r.ok, status: r.status, data: JSON.parse(text) };
                    } catch (err) {
                        return { ok: false, status: r.status, data: { success: false, error: r.status === 404 ? 'Сервис недоступен (404).' : (text || 'Ошибка ' + r.status) } };
                    }
                });
            })
            .then(function(result) {
                if (result.status === 403 ||
                    (result.data && result.data.error && result.data.error.indexOf('CSRF') !== -1)) {
                    /* Сохраняем данные формы в sessionStorage до перезагрузки */
                    try {
                        var savedForm = {
                            name:  (document.getElementById('b-manager-name') || {}).value || '',
                            phone: (document.getElementById('b-phone') || {}).value || '',
                            agree: !!(document.getElementById('b-agree-contact') || {}).checked,
                        };
                        sessionStorage.setItem('th_csrf_restore', JSON.stringify(savedForm));
                    } catch (_) {}
                    if (msgModal) {
                        msgModal.textContent = 'Сессия истекла. Сохраняем данные и обновляем страницу…';
                        msgModal.className = 'p-2 rounded-lg text-sm bg-amber-100 text-amber-800';
                        msgModal.classList.remove('hidden');
                    }
                    setTimeout(function() { window.location.reload(); }, 1300);
                    return;
                }
                var data = result.data;
                if (data && data.success) {
                    tourYmGoal('tour_booking_success');
                    tourYmGoal('lead_ok');
                    if (type === 'with_payment') {
                        startTbankPayment();
                    } else {
                        closeModal();
                        showPageMsg(BOOKING_SUCCESS_MSG_SHORT, false);
                        setTimeout(function() { window.location.reload(); }, 2200);
                    }
                } else {
                    if (msgModal) {
                        msgModal.textContent = (data && data.error) ? data.error : ('Ошибка ' + (result.status || '') + '. Попробуйте позже.');
                        msgModal.className = 'p-2 rounded-lg text-sm bg-red-100 text-red-800';
                        msgModal.classList.remove('hidden');
                    }
                }
            })
            .catch(function(err) {
                if (msgModal) {
                    msgModal.textContent = 'Нет связи с сервером. Проверьте интернет или попробуйте позже.';
                    msgModal.className = 'p-2 rounded-lg text-sm bg-red-100 text-red-800';
                    msgModal.classList.remove('hidden');
                }
            })
            .finally(function() {
                if (paymentRedirectPending) return;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> <span id="booking-submit-label"></span>';
                    if (bookingType && bookingType.value === 'with_payment') {
                        setBookingFormLayout('with_payment');
                    } else if (bookingFormMode === 'full') {
                        setBookingFormLayout('book');
                    } else {
                        setBookingFormLayout('short');
                    }
                }
            });
        });

        if (hotelId && tvApiBase) {
            var sep = tvApiBase.indexOf('?') >= 0 ? '&' : '?';
            var hotelUrl = tvApiBase + sep + 'type=hotel&hotelId=' + encodeURIComponent(hotelId);
            fetch(hotelUrl, { cache: 'no-store' })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (!j.success || !j.data) return;
                    var d = j.data;
                    var blockEl = document.getElementById('tour-hotel-info-block');
                    var contentEl = document.getElementById('tour-hotel-info-content');
                    if (!blockEl || !contentEl) return;
                    var parts = [];
                    var desc = (d.common && d.common.description) ? d.common.description : (d.description || '');
                    if (desc) {
                        var safeDesc = String(desc).replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
                        safeDesc = safeDesc.replace(/&amp;#178;/gi, '²').replace(/&#178;/g, '²').replace(/&amp;#xb2;/gi, '²').replace(/&#xb2;/g, '²')
                            .replace(/&amp;sup2;/gi, '²').replace(/&sup2;/g, '²')
                            .replace(/&amp;#179;/gi, '³').replace(/&#179;/g, '³').replace(/&amp;#xb3;/gi, '³').replace(/&#xb3;/g, '³');
                        parts.push('<div class="hotel-desc">' + safeDesc + '</div>');
                    }
                    var images = collectHotelImagesFromApiData(d, hotelId);
                    if (d.infrastructure && (d.infrastructure.beach || d.infrastructure.territory)) {
                        function cleanInfra(text) {
                            var s = String(text || '');
                            s = s.replace(/<\/li>/gi, '\n');
                            s = s.replace(/<li[^>]*>/gi, '• ');
                            s = s.replace(/<br\s*\/?>/gi, '\n');
                            s = s.replace(/<[^>]+>/g, '');
                            return s.trim().replace(/\n{2,}/g, '\n');
                        }
                        function infraToHtml(raw) {
                            var h = cleanInfra(raw).replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
                            return h.replace(/&amp;#178;/gi, '²').replace(/&#178;/g, '²').replace(/&amp;#xb2;/gi, '²').replace(/&#xb2;/g, '²')
                                .replace(/&amp;sup2;/gi, '²').replace(/&sup2;/g, '²');
                        }
                        var infraHtml = '<div class="hotel-infra">';
                        if (d.infrastructure.beach) {
                            infraHtml += '<div class="hotel-infra-item"><strong>Пляж</strong>' + infraToHtml(d.infrastructure.beach) + '</div>';
                        }
                        if (d.infrastructure.territory) {
                            infraHtml += '<div class="hotel-infra-item"><strong>Территория</strong>' + infraToHtml(d.infrastructure.territory) + '</div>';
                        }
                        infraHtml += '</div>';
                        parts.push(infraHtml);
                    }
                    if (parts.length) {
                        contentEl.innerHTML = parts.join('');
                        blockEl.classList.remove('hidden');
                    }
                    if (images.length && typeof window.__thGalleryMergeFromUrls === 'function') {
                        window.__thGalleryMergeFromUrls(images);
                    }
                })
                .catch(function() {});
        }

        if (tourId && tvApiBase) {
            var sepTour = tvApiBase.indexOf('?') >= 0 ? '&' : '?';
            var tourUrl = tvApiBase + sepTour + 'type=tour&tourId=' + encodeURIComponent(tourId) + '&currency=RUB';
            fetch(tourUrl, { cache: 'no-store' })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (!j.success || !j.data) return;
                    var t = j.data;
                    lastTourPlacement = t.placement ? String(t.placement).trim() : '';
                    if (t.operator && (t.operator.russianName || t.operator.name)) {
                        lastTourOperator = String(t.operator.russianName || t.operator.name).trim();
                    } else {
                        lastTourOperator = '';
                    }
                    /* Обновляем chip туроператора */
                    if (t.operator && (t.operator.russianName || t.operator.name)) {
                        var opChip = document.getElementById('operator-chip');
                        var opVal = document.getElementById('operator-chip-value');
                        if (opChip && opVal) {
                            opVal.textContent = String(t.operator.russianName || t.operator.name);
                            opChip.classList.remove('hidden');
                        }
                    }
                    /* Акции: цена с карточки (URL) — истина; live Tourvisor только для оператора/номера */
                    if (!fromPromo) {
                        var livePriceNum = tvTourDetailPickPriceNum(t);
                        if (livePriceNum > 0) {
                            currentPrice = livePriceNum;
                            tdPriceBeforePromo = livePriceNum;
                            appliedPromoCode = '';
                            var pmClear = document.getElementById('th-td-promo-msg');
                            if (pmClear) {
                                pmClear.classList.add('hidden');
                                pmClear.textContent = '';
                            }
                            updateCtaPrice();
                        }
                    }
                })
                .catch(function() {});
        }

        function thTdFlightEsc(s) {
            return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        function thTdFlightManagerFallback() {
            var listItemEl = document.getElementById('flight-info-list-item');
            var apiSpan = document.getElementById('flight-info-from-api');
            var chipAirline = document.getElementById('flight-airline-chip-label');
            if (chipAirline) chipAirline.textContent = '';
            if (apiSpan) apiSpan.textContent = 'Уточните детали перелёта у нашего менеджера';
            if (listItemEl) listItemEl.classList.remove('hidden');
        }
        if (tourId && tvApiBase) {
            var sepFl = tvApiBase.indexOf('?') >= 0 ? '&' : '?';
            var flightsUrl = tvApiBase + sepFl + 'type=tour-flights&tourId=' + encodeURIComponent(tourId) + '&currency=RUB';
            fetch(flightsUrl, { cache: 'no-store' })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (!j.success || !j.flights || !j.flights.length) {
                        thTdFlightManagerFallback();
                        return;
                    }
                    var first = (typeof thPickTourvisorFlightPackage === 'function')
                        ? thPickTourvisorFlightPackage(j.flights, defaultDeparture)
                        : j.flights[0];
                    if (!first) {
                        thTdFlightManagerFallback();
                        return;
                    }
                    var lineParts = [];
                    var summaryParts = [];
                    function legLine(leg) {
                        var company = (leg.company && leg.company.name) ? leg.company.name : '';
                        if (company) summaryParts.push(company);
                        var legDep = leg.departure ? ((leg.departure.port && leg.departure.port.shortName) ? leg.departure.port.shortName : '') + ' ' + (leg.departure.time || '') : '';
                        var legArr = leg.arrival ? ((leg.arrival.port && leg.arrival.port.shortName) ? leg.arrival.port.shortName : '') + ' ' + (leg.arrival.time || '') : '';
                        return (legDep ? legDep + ' \u2192 ' : '') + (legArr || '');
                    }
                    if (first.forward && first.forward.length) {
                        var fFirst = first.forward[0];
                        var fLast = first.forward[first.forward.length - 1];
                        var dep = fFirst.departure ? ((fFirst.departure.port && fFirst.departure.port.shortName) ? fFirst.departure.port.shortName : '') + ' ' + (fFirst.departure.time || '') : '';
                        var arr = fLast.arrival ? ((fLast.arrival.port && fLast.arrival.port.shortName) ? fLast.arrival.port.shortName : '') + ' ' + (fLast.arrival.time || '') : '';
                        var fwd = (dep ? dep + ' \u2192 ' : '') + (arr || '');
                        if (fwd) lineParts.push(fwd);
                    }
                    if (first.backward && first.backward.length) {
                        var bFirst = first.backward[0];
                        var bLast = first.backward[first.backward.length - 1];
                        var depB = bFirst.departure ? ((bFirst.departure.port && bFirst.departure.port.shortName) ? bFirst.departure.port.shortName : '') + ' ' + (bFirst.departure.time || '') : '';
                        var arrB = bLast.arrival ? ((bLast.arrival.port && bLast.arrival.port.shortName) ? bLast.arrival.port.shortName : '') + ' ' + (bLast.arrival.time || '') : '';
                        var bwd = (depB ? depB + ' \u2192 ' : '') + (arrB || '');
                        if (bwd) lineParts.push(bwd);
                    }
                    if (!lineParts.length) {
                        thTdFlightManagerFallback();
                        return;
                    }
                    var uniqAir = [];
                    summaryParts.forEach(function(c) {
                        if (c && uniqAir.indexOf(c) === -1) uniqAir.push(c);
                    });
                    var airlineLabel = uniqAir.join(' \u00b7 ');
                    var listItemEl = document.getElementById('flight-info-list-item');
                    var apiSpan = document.getElementById('flight-info-from-api');
                    var chipAirline = document.getElementById('flight-airline-chip-label');
                    if (chipAirline) chipAirline.textContent = airlineLabel;
                    if (apiSpan) {
                        apiSpan.innerHTML = lineParts.map(function(ln) {
                            return '<span class="th-detail__flight-line">' + thTdFlightEsc(ln) + '</span>';
                        }).join('');
                    }
                    if (listItemEl) listItemEl.classList.remove('hidden');
                })
                .catch(function() { thTdFlightManagerFallback(); });
        }

        // Цена возле CTA
        var tdPriceBeforePromo = (function() {
            var num = parseFloat(String(defaultPrice || '').replace(/[^\d.,]/g, '').replace(',', '.'));
            return (!isNaN(num) && num > 0) ? Math.round(num) : 0;
        })();
        var currentPrice = tdPriceBeforePromo > 0 ? tdPriceBeforePromo : 0;
        function formatRub(n) {
            try { return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(n); }
            catch (e) { return Math.round(n) + ' ₽'; }
        }
        function tdUpdateAllPriceDisplays() {
            var rub = (currentPrice && currentPrice > 0) ? formatRub(currentPrice) : '';
            var heroPrice = document.getElementById('tour-detail-hero-price');
            var heroPriceNoImg = document.getElementById('tour-detail-hero-price-noimg');
            var topMain = document.querySelector('.th-detail__top-price-main');
            if (heroPrice && rub) heroPrice.textContent = rub;
            if (heroPriceNoImg && rub) heroPriceNoImg.textContent = rub;
            if (topMain && rub) topMain.textContent = rub;
            if (appliedPromoCode && tdPriceBeforePromo > 0 && currentPrice > 0 && currentPrice < tdPriceBeforePromo) {
                var oldSidebar = document.querySelector('.th-detail__old-price');
                var oldTop = document.querySelector('.th-detail__top-old-price');
                var oldTxt = formatRub(tdPriceBeforePromo);
                if (oldSidebar) oldSidebar.textContent = oldTxt;
                if (oldTop) oldTop.textContent = oldTxt;
            }
        }

        function updateCtaPrice() {
            var inline = document.getElementById('cta-price-inline');
            var note = document.getElementById('cta-price-note');
            tdUpdateAllPriceDisplays();
            if (!inline) return;
            var rub = (currentPrice && currentPrice > 0) ? formatRub(currentPrice) : '';
            if (currentPrice && currentPrice > 0) {
                inline.textContent = rub;
                inline.classList.remove('hidden');
                if (note) {
                    note.textContent = appliedPromoCode
                        ? ('Цена со скидкой по промокоду ' + appliedPromoCode)
                        : 'Цена обновляется автоматически по данным туроператора';
                    note.classList.remove('hidden');
                }
                defaultPrice = rub;
            } else {
                inline.classList.add('hidden');
                if (note) note.classList.add('hidden');
            }
        }
        updateCtaPrice();

        (function initTourDetailPromo() {
            var promoApplyBtn = document.getElementById('th-td-promo-apply');
            var promoInput = document.getElementById('th-td-promo-input');
            var promoMsg = document.getElementById('th-td-promo-msg');
            var promoApi = window.TH_PROMO_APPLY;

            function tdShowPromoMsg(txt, isErr) {
                if (!promoMsg) return;
                promoMsg.textContent = txt;
                promoMsg.className = 'th-detail__promo-msg ' + (isErr ? 'th-detail__promo-msg--err' : 'th-detail__promo-msg--ok');
                promoMsg.classList.remove('hidden');
            }
            function tdHidePromoMsg() {
                if (!promoMsg) return;
                promoMsg.classList.add('hidden');
                promoMsg.textContent = '';
            }

            function tdApplyPromoCode(raw, opts) {
                opts = opts || {};
                tdHidePromoMsg();
                raw = String(raw || '').trim();
                if (!raw) {
                    tdShowPromoMsg('Введите промокод', true);
                    return false;
                }
                var base = tdPriceBeforePromo > 0 ? tdPriceBeforePromo : (currentPrice && currentPrice > 0 ? currentPrice : 0);
                if (!base) {
                    tdShowPromoMsg('Сначала дождитесь загрузки цены тура', true);
                    return false;
                }
                var pct = promoApi && promoApi.getPromoPct ? promoApi.getPromoPct(raw) : 0;
                if (!pct) {
                    tdShowPromoMsg(
                        promoApi && promoApi.invalidMessage ? promoApi.invalidMessage(raw) : 'Промокод недействителен',
                        true
                    );
                    return false;
                }
                var newP = promoApi && promoApi.calcPriceAfterPromo
                    ? promoApi.calcPriceAfterPromo(base, pct)
                    : 0;
                if (!newP) {
                    tdShowPromoMsg('Промокод недействителен', true);
                    return false;
                }
                if (!tdPriceBeforePromo || tdPriceBeforePromo <= 0) tdPriceBeforePromo = base;
                currentPrice = newP;
                appliedPromoCode = raw.toUpperCase();
                if (promoInput && !opts.skipInputSync) promoInput.value = appliedPromoCode;
                updateCtaPrice();
                tdShowPromoMsg('Промокод применён: ' + appliedPromoCode + '. Скидка учтена в цене.', false);
                return true;
            }

            if (!promoApplyBtn || !promoInput) return;

            promoApplyBtn.addEventListener('click', function () {
                tdApplyPromoCode(promoInput.value.trim());
            });
            promoInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    tdApplyPromoCode(promoInput.value.trim());
                }
            });

            window.TH_TOUR_DETAIL_PROMO = {
                apply: tdApplyPromoCode,
                fillAndApply: function (code) {
                    if (promoInput) promoInput.value = code;
                    return tdApplyPromoCode(code);
                }
            };

            var pending = promoApi && promoApi.takePendingCode ? promoApi.takePendingCode() : '';
            if (pending) {
                promoInput.value = pending;
                setTimeout(function () { tdApplyPromoCode(pending); }, 120);
            }
        })();

        // Делает плашки «Номер/Питание/Даты» кликабельными: открываем форму и фокусируем поле
        function wireChipToField(selector, fieldId) {
            var chip = document.querySelector(selector);
            var field = document.getElementById(fieldId);
            if (!chip || !field) return;
            chip.style.cursor = 'pointer';
            chip.addEventListener('click', function() {
                openModal('without_payment');
                setTimeout(function() { try { field.focus(); } catch (e) {} }, 50);
            });
            chip.setAttribute('role', 'button');
            chip.setAttribute('tabindex', '0');
            chip.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    chip.click();
                }
            });
        }
        wireChipToField('.tour-detail-param-chip .fa-bed', 'b-room-category');
        wireChipToField('.tour-detail-param-chip .fa-utensils', 'b-meal');
        wireChipToField('.tour-detail-param-chip .fa-calendar-days', 'b-date-from');

        /* ─── Галерея ─── */
        (function initGallery() {
            var track   = document.getElementById('th-gallery-track');
            var dotsEl  = document.getElementById('th-gallery-dots');
            var prevBtn = document.getElementById('th-gallery-prev');
            var nextBtn = document.getElementById('th-gallery-next');
            var counter = document.getElementById('th-gallery-counter');
            if (!track) return;

            var slides = [];
            var current = 0;
            var queryImg = document.getElementById('th-gallery-img-main');
            if (queryImg && queryImg.src) {
                var normQ = normalizeTvImageUrl(queryImg.getAttribute('src') || queryImg.src);
                if (normQ && normQ !== queryImg.src) queryImg.src = normQ;
            }

            /* Синхронизация после смены слайда */
            function preloadGalleryImage(img) {
                if (!img) return;
                img.loading = 'eager';
                var src = img.getAttribute('src') || '';
                if (!src) return;
                if (img.complete) return;
                if (!img.src) img.src = src;
            }

            function syncUi() {
                var n = slides.length;
                track.style.transform = 'translateX(-' + (current * 100) + '%)';
                if (dotsEl) {
                    var dots = dotsEl.querySelectorAll('.th-detail__gallery-dot');
                    dots.forEach(function(d, i) { d.classList.toggle('active', i === current); });
                }
                if (counter) counter.textContent = (current + 1) + ' / ' + n;
                preloadGalleryImage(slides[current]);
                if (n > 1) {
                    preloadGalleryImage(slides[(current + 1) % n]);
                    preloadGalleryImage(slides[(current - 1 + n) % n]);
                }
            }

            /* Построить точки */
            function buildDots(n) {
                if (!dotsEl) return;
                if (n <= 1) {
                    dotsEl.innerHTML = '';
                    return;
                }
                if (dotsEl.children.length !== n) {
                    dotsEl.innerHTML = '';
                    for (var i = 0; i < n; i++) {
                        (function(idx) {
                            var btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'th-detail__gallery-dot' + (idx === 0 ? ' active' : '');
                            btn.setAttribute('aria-label', 'Фото ' + (idx + 1));
                            btn.addEventListener('click', function() { current = idx; syncUi(); });
                            dotsEl.appendChild(btn);
                        })(i);
                    }
                    return;
                }
                var existingDots = dotsEl.querySelectorAll('.th-detail__gallery-dot');
                existingDots.forEach(function(btn, idx) {
                    btn.classList.toggle('active', idx === current);
                    if (btn.getAttribute('data-th-wired') === '1') return;
                    btn.setAttribute('data-th-wired', '1');
                    btn.addEventListener('click', function() { current = idx; syncUi(); });
                });
            }

            /* Инициализировать с текущими img в track */
            function initSlides() {
                slides = Array.prototype.slice.call(track.querySelectorAll('img.th-detail__gallery-slide'));
                var n = slides.length;
                buildDots(n);
                if (prevBtn) prevBtn.style.display = n > 1 ? 'flex' : 'none';
                if (nextBtn) nextBtn.style.display = n > 1 ? 'flex' : 'none';
                if (counter) counter.style.display = n > 1 ? '' : 'none';
                if (current >= n) current = 0;
                syncUi();
                slides.forEach(preloadGalleryImage);
            }

            /* Добавить слайды по URL из API (без <img> в блоке «Информация об отеле») */
            var lightboxWired = false;
            function gallerySlideImages() {
                return Array.prototype.slice.call(track.querySelectorAll('img.th-detail__gallery-slide'));
            }
            function wireGalleryLightbox() {
                if (lightboxWired) return;
                var overlay = document.getElementById('hotel-image-lightbox');
                if (!overlay || !track) return;
                lightboxWired = true;
                var overlayImg = overlay.querySelector('img');
                var lbIdx = 0;
                function openLbAt(i) {
                    var imgs = gallerySlideImages();
                    if (!overlayImg || !imgs.length) return;
                    lbIdx = (i + imgs.length) % imgs.length;
                    overlayImg.src = imgs[lbIdx].src;
                    overlay.classList.remove('hidden');
                }
                track.addEventListener('click', function(e) {
                    var im = e.target.closest('img.th-detail__gallery-slide');
                    if (!im) return;
                    var imgs = gallerySlideImages();
                    openLbAt(imgs.indexOf(im));
                });
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay || (e.target.getAttribute && e.target.getAttribute('data-role') === 'close')) {
                        overlay.classList.add('hidden');
                    }
                });
                var prevLb = overlay.querySelector('[data-role="prev"]');
                var nextLb = overlay.querySelector('[data-role="next"]');
                if (prevLb) prevLb.addEventListener('click', function(e) {
                    e.stopPropagation();
                    openLbAt(lbIdx - 1);
                });
                if (nextLb) nextLb.addEventListener('click', function(e) {
                    e.stopPropagation();
                    openLbAt(lbIdx + 1);
                });
            }

            function mergeFromUrls(urlList) {
                if (!urlList || !urlList.length || !track) return;
                var altBase = (typeof hotelName === 'string' && hotelName.trim()) ? hotelName.trim() : 'Фото отеля';
                var existingKeys = {};
                Array.prototype.slice.call(track.querySelectorAll('img.th-detail__gallery-slide')).forEach(function(el) {
                    var src = el.getAttribute('src') || el.src || '';
                    if (!src) return;
                    var k = thTourPhotoNormalizeKey(src);
                    if (k) existingKeys[k] = true;
                });
                var added = 0;
                urlList.slice(0, 6).forEach(function(srcRaw) {
                    if (!srcRaw) return;
                    var src = (typeof srcRaw === 'string' && /^https?:\/\//i.test(srcRaw.trim()))
                        ? srcRaw.trim()
                        : normalizeTvImageUrl(String(srcRaw));
                    if (!src) return;
                    var key = thTourPhotoNormalizeKey(src);
                    if (!key || existingKeys[key]) return;
                    existingKeys[key] = true;
                    added++;
                    var slide = document.createElement('img');
                    slide.className = 'th-detail__gallery-slide';
                    slide.src = src;
                    slide.alt = altBase + ' — фото ' + added;
                    slide.loading = 'eager';
                    slide.decoding = 'async';
                    slide.style.cursor = 'zoom-in';
                    slide.onerror = function () {
                        this.onerror = null;
                        this.src = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&q=80';
                    };
                    track.appendChild(slide);
                });
                initSlides();
                wireGalleryLightbox();
            }
            window.__thGalleryMergeFromUrls = mergeFromUrls;

            /* Стрелки */
            if (prevBtn) prevBtn.addEventListener('click', function() {
                current = (current - 1 + slides.length) % slides.length;
                syncUi();
            });
            if (nextBtn) nextBtn.addEventListener('click', function() {
                current = (current + 1) % slides.length;
                syncUi();
            });

            /* Свайп */
            var touchStartX = 0;
            var galLockUntil = 0;
            track.addEventListener('touchstart', function(e) { touchStartX = e.changedTouches[0].clientX; }, { passive: true });
            track.addEventListener('touchend', function(e) {
                var now = Date.now();
                if (now < galLockUntil) return;
                var diff = touchStartX - e.changedTouches[0].clientX;
                if (Math.abs(diff) > 40) {
                    galLockUntil = now + 280;
                    current = diff > 0
                        ? (current + 1) % slides.length
                        : (current - 1 + slides.length) % slides.length;
                    syncUi();
                }
            }, { passive: true });

            /* Инициализация: фото из URL (gallery_b64), затем дополняем из API / типовых путей Tourvisor */
            initSlides();
            wireGalleryLightbox();
            if (hotelId && typeof collectHotelImagesFromApiData === 'function') {
                mergeFromUrls(collectHotelImagesFromApiData({ id: hotelId }, hotelId));
            }
        })();

        /* ─── Восстановление данных формы после CSRF-релоада (модалка не открывается сама) ─── */
        (function restoreCsrfFormData() {
            var raw = sessionStorage.getItem('th_csrf_restore');
            if (!raw) return;
            try {
                sessionStorage.removeItem('th_csrf_restore');
                var d = JSON.parse(raw);
                if (!d) return;
                window.__thPendingBookingRestore = d;
                showPageMsg('Сессия обновлена. Нажмите «Оставить заявку», проверьте данные и отправьте форму.', false);
            } catch (_) {}
        })();
    })();
    </script>
</body>
</html>