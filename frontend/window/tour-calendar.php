<<<<<<< HEAD
<?php
/**
 * Календарь выгодных дат — свод лучших цен по всем направлениям на даты вперёд.
 * Минимальный фильтр: только город вылета.
=======
<?php
/**
 * Календарь выгодных дат — heatmap по promo_cache + туры на день.
 * Без виджета Tourvisor / без live-поиска.
>>>>>>> origin/master
 */
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/components/tourvisor_proxy_url.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = 'tour-calendar';

$qDepartureId = isset($_GET['departureId']) ? (int) $_GET['departureId'] : 0;
$defaultDepartureId = ($qDepartureId === 1) ? 1 : 7;
$imageProxy = get_tourvisor_image_proxy_base_url();
require_once __DIR__ . '/../../backend/components/deals_calendar.php';
$ladder = th_deals_calendar_ladder();
$assetV = '8';

$popularCountries = [];
$countriesFile = __DIR__ . '/../../backend/config/popular_countries.php';
if (is_file($countriesFile)) {
    $loaded = require $countriesFile;
    if (is_array($loaded)) {
        $popularCountries = $loaded;
    }
}
if ($popularCountries === []) {
    $popularCountries = [
        ['id' => 4, 'name' => 'Турция'],
        ['id' => 1, 'name' => 'Египет'],
        ['id' => 2, 'name' => 'Таиланд'],
    ];
}

$defaultCountryId = (int) ($popularCountries[0]['id'] ?? 4);
$defaultCountryName = (string) ($popularCountries[0]['name'] ?? 'Турция');
$qCountryId = isset($_GET['countryId']) ? (int) $_GET['countryId'] : 0;
$qDepartureId = isset($_GET['departureId']) ? (int) $_GET['departureId'] : 0;
if ($qCountryId > 0) {
    foreach ($popularCountries as $c) {
        if ((int) ($c['id'] ?? 0) === $qCountryId) {
            $defaultCountryId = $qCountryId;
            $defaultCountryName = (string) ($c['name'] ?? $defaultCountryName);
            break;
        }
    }
}
$defaultDepartureId = ($qDepartureId === 1) ? 1 : 7;
$imageProxy = get_tourvisor_image_proxy_base_url();
$assetV = '1';

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="icon" type="image/svg+xml" href="/frontend/favicon.svg">
    <title>Календарь выгодных дат | Travel Hub</title>
<<<<<<< HEAD
    <meta name="description" content="Календарь выгодных туров Travel Hub: выгодные и пониженные цены по всем направлениям на ближайшие месяцы. Самара и Москва.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include __DIR__ . '/../../backend/components/design_system_head.php'; ?>
    <link rel="stylesheet" href="/frontend/css/pages/deals-calendar.css?v=<?php echo htmlspecialchars($assetV, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="ds-page antialiased th-deals-cal-page">
    <?php include __DIR__ . '/../../backend/components/header.php'; ?>

    <main class="th-deals-cal-main relative z-10">
        <div class="th-deals-cal-shell" id="th-deals-cal">
            <header class="th-deals-cal-hero">
                <div class="th-deals-cal-hero__glow" aria-hidden="true"></div>
                <div class="th-deals-cal-hero__row">
                    <div class="th-deals-cal-hero__inner">
                        <p class="th-deals-cal-hero__brand">Travel Hub</p>
                        <h1 class="th-deals-cal-hero__title">Календарь выгодных туров</h1>
                        <p class="th-deals-cal-hero__lead">Свод лучших цен по всем направлениям. Две метки: выгодная и пониженная цена. Окно месяцев сдвигается вперёд вместе с сезоном.</p>
                    </div>
                    <div class="th-deals-cal-hero__tools">
                        <span class="th-deals-cal-hero__tools-label">Вылет</span>
                        <div class="th-deals-cal__chips th-deals-cal__chips--hero" id="th-dc-departure-chips" role="group" aria-label="Город вылета">
                            <button type="button" class="th-deals-cal__chip<?php echo $defaultDepartureId === 7 ? ' is-active' : ''; ?>" data-dep="7">Самара</button>
                            <button type="button" class="th-deals-cal__chip<?php echo $defaultDepartureId === 1 ? ' is-active' : ''; ?>" data-dep="1">Москва</button>
                        </div>
                        <select id="th-dc-departure" class="th-deals-cal__sr-only" tabindex="-1" aria-hidden="true">
                            <option value="7"<?php echo $defaultDepartureId === 7 ? ' selected' : ''; ?>>Самара</option>
                            <option value="1"<?php echo $defaultDepartureId === 1 ? ' selected' : ''; ?>>Москва</option>
                        </select>
                        <p class="th-deals-cal-hero__summary" id="th-dc-summary" aria-live="polite">Самара · все направления</p>
                    </div>
                </div>
            </header>

            <section class="th-deals-cal-board th-deals-cal-board--full" aria-label="Календарь и туры">
                <div class="th-deals-cal__panel th-deals-cal__panel--cal" id="th-dc-cal-panel">
                    <div class="th-deals-cal__month-head">
                        <div>
                            <p class="th-deals-cal__eyebrow">Лучшие цены по дням</p>
                            <h2 class="th-deals-cal__month-title" id="th-dc-month-title">Месяц</h2>
                        </div>
                        <div class="th-deals-cal__nav">
                            <button type="button" id="th-dc-prev" aria-label="Предыдущий месяц"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
                            <button type="button" id="th-dc-next" aria-label="Следующий месяц"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <div class="th-deals-cal__weekdays" id="th-dc-weekdays" aria-hidden="true"></div>
                    <div class="th-deals-cal__grid" id="th-dc-grid" role="grid" aria-label="Календарь выгодных туров"></div>
                    <div class="th-deals-cal__legend">
                        <span><i class="lg-deal" aria-hidden="true"></i>Выгодная цена</span>
                        <span><i class="lg-reduced" aria-hidden="true"></i>Пониженная цена</span>
                        <span><i class="lg-empty" aria-hidden="true"></i>Пока пусто</span>
                    </div>
                </div>

                <div class="th-deals-cal__panel th-deals-cal__panel--results" id="th-dc-results-panel">
                    <div class="th-deals-cal__results-head">
                        <div>
                            <p class="th-deals-cal__eyebrow">Туры на день</p>
                            <h2 class="th-deals-cal__results-title" id="th-dc-results-title">Выберите дату</h2>
                        </div>
                        <p class="th-deals-cal__results-sub" id="th-dc-results-sub"></p>
                    </div>
                    <div class="th-deals-cal__empty-state" id="th-dc-empty">
                        <div class="th-deals-cal__empty-icon" aria-hidden="true"><i class="fas fa-calendar-day"></i></div>
                        <p class="th-deals-cal__empty-title">Выберите дату с ценой</p>
                        <p class="th-deals-cal__empty-text">Красная метка — выгодная цена, бирюзовая — пониженная. Нажмите день — покажем туры.</p>
                    </div>
                    <div class="th-deals-cal__list" id="th-dc-list"></div>
                    <div class="th-deals-cal__cta-row">
                        <a href="/frontend/window/promotions.php" class="th-deals-cal__btn th-deals-cal__btn--ghost">Все акции</a>
                        <a href="/index.php#tour-search-section" class="th-deals-cal__btn th-deals-cal__btn--primary">Полный поиск</a>
                    </div>
=======
    <meta name="description" content="Календарь выгодных дат вылета: сравните цены по дням и откройте туры из кэша акций Travel Hub. Самара и Москва.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include __DIR__ . '/../../backend/components/design_system_head.php'; ?>
    <link rel="stylesheet" href="/frontend/css/pages/deals-calendar.css?v=2">
</head>
<body class="ds-page text-slate-900 antialiased th-deals-cal-page">
    <?php include __DIR__ . '/../../backend/components/header.php'; ?>

    <main class="relative z-10">
        <section class="ds-page-hero pt-8 pb-5 md:pt-10 md:pb-6">
            <div class="th-container mx-auto px-4 sm:px-6 max-w-5xl">
                <span class="pill-badge mb-4">По датам вылета</span>
                <h1 class="heading-font text-3xl sm:text-4xl font-bold text-slate-900 mb-3">Календарь выгодных дат</h1>
                <p class="text-slate-600 text-lg max-w-2xl leading-relaxed">
                    Смотрите ориентировочные цены по дням и сразу открывайте туры. Данные из кэша акций — без ожидания живого поиска.
                </p>
            </div>
        </section>

        <section class="pb-10 md:pb-14">
            <div class="th-container mx-auto px-4 sm:px-6 max-w-6xl">
                <div class="th-deals-cal" id="th-deals-cal">
                    <div class="th-deals-cal__filters">
                        <div class="th-deals-cal__field">
                            <span class="th-deals-cal__label">Вылет</span>
                            <div class="th-deals-cal__chips" id="th-dc-departure-chips" role="group" aria-label="Город вылета">
                                <button type="button" class="th-deals-cal__chip<?php echo $defaultDepartureId === 7 ? ' is-active' : ''; ?>" data-dep="7">Самара</button>
                                <button type="button" class="th-deals-cal__chip<?php echo $defaultDepartureId === 1 ? ' is-active' : ''; ?>" data-dep="1">Москва</button>
                            </div>
                            <select id="th-dc-departure" class="th-deals-cal__sr-only" tabindex="-1" aria-hidden="true">
                                <option value="7"<?php echo $defaultDepartureId === 7 ? ' selected' : ''; ?>>Самара</option>
                                <option value="1"<?php echo $defaultDepartureId === 1 ? ' selected' : ''; ?>>Москва</option>
                            </select>
                        </div>
                        <div class="th-deals-cal__field th-deals-cal__field--grow">
                            <span class="th-deals-cal__label">Направление</span>
                            <div class="th-deals-cal__chips th-deals-cal__chips--wrap" id="th-dc-country-chips" role="group" aria-label="Страна">
                                <?php foreach ($popularCountries as $c):
                                    $cid = (int) ($c['id'] ?? 0);
                                    $cname = (string) ($c['name'] ?? '');
                                    if ($cid <= 0 || $cname === '') {
                                        continue;
                                    }
                                    ?>
                                <button type="button"
                                        class="th-deals-cal__chip<?php echo $cid === $defaultCountryId ? ' is-active' : ''; ?>"
                                        data-country="<?php echo $cid; ?>"
                                        data-name="<?php echo htmlspecialchars($cname, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($cname, ENT_QUOTES, 'UTF-8'); ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                            <select id="th-dc-country" class="th-deals-cal__sr-only" tabindex="-1" aria-hidden="true">
                                <?php foreach ($popularCountries as $c):
                                    $cid = (int) ($c['id'] ?? 0);
                                    $cname = (string) ($c['name'] ?? '');
                                    if ($cid <= 0 || $cname === '') {
                                        continue;
                                    }
                                    ?>
                                <option value="<?php echo $cid; ?>"
                                        data-name="<?php echo htmlspecialchars($cname, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php echo $cid === $defaultCountryId ? ' selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cname, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="th-deals-cal__field">
                            <span class="th-deals-cal__label">Ночи</span>
                            <div class="th-deals-cal__chips" id="th-dc-nights-chips" role="group" aria-label="Ночи">
                                <button type="button" class="th-deals-cal__chip" data-nights="5-8">5–8</button>
                                <button type="button" class="th-deals-cal__chip is-active" data-nights="6-9">6–9</button>
                                <button type="button" class="th-deals-cal__chip" data-nights="7-10">7–10</button>
                                <button type="button" class="th-deals-cal__chip" data-nights="10-14">10–14</button>
                            </div>
                            <select id="th-dc-nights" class="th-deals-cal__sr-only" tabindex="-1" aria-hidden="true">
                                <option value="5-8">5–8</option>
                                <option value="6-9" selected>6–9</option>
                                <option value="7-10">7–10</option>
                                <option value="10-14">10–14</option>
                            </select>
                        </div>
                    </div>

                    <div class="th-deals-cal__layout">
                        <div class="th-deals-cal__panel" id="th-dc-cal-panel">
                            <div class="th-deals-cal__month-head">
                                <h2 class="th-deals-cal__month-title" id="th-dc-month-title">Месяц</h2>
                                <div class="th-deals-cal__nav">
                                    <button type="button" id="th-dc-prev" aria-label="Предыдущий месяц">‹</button>
                                    <button type="button" id="th-dc-next" aria-label="Следующий месяц">›</button>
                                </div>
                            </div>
                            <div class="th-deals-cal__weekdays" id="th-dc-weekdays" aria-hidden="true"></div>
                            <div class="th-deals-cal__grid" id="th-dc-grid" role="grid" aria-label="Календарь цен"></div>
                            <div class="th-deals-cal__legend">
                                <span><i class="lg-deal" aria-hidden="true"></i>Выгодно</span>
                                <span><i class="lg-ok" aria-hidden="true"></i>Есть цена</span>
                                <span><i class="lg-empty" aria-hidden="true"></i>Нет в кэше</span>
                            </div>
                            <p class="th-deals-cal__note">Цены ориентировочные, из прогретого кэша акций. Для точной брони — карточка тура или офис.</p>
                        </div>

                        <div class="th-deals-cal__panel" id="th-dc-results-panel">
                            <div class="th-deals-cal__results-head">
                                <h2 class="th-deals-cal__results-title" id="th-dc-results-title">Туры на дату</h2>
                                <p class="th-deals-cal__results-sub" id="th-dc-results-sub"></p>
                            </div>
                            <p class="th-deals-cal__empty" id="th-dc-empty">Выберите дату с ценой — покажем туры.</p>
                            <div class="th-deals-cal__list" id="th-dc-list"></div>
                            <div class="th-deals-cal__cta-row">
                                <a href="/frontend/window/promotions.php" class="ds-btn-secondary inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold">Все акции</a>
                                <a href="/index.php#tour-search-section" class="ds-btn-primary inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold shadow-lg">Поиск туров</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="pb-16 md:pb-20">
            <div class="th-container mx-auto px-4 sm:px-6 max-w-6xl">
                <h2 class="heading-font text-2xl font-bold text-slate-900 mb-8">Зачем смотреть даты заранее</h2>
                <div class="grid sm:grid-cols-3 gap-6">
                    <div class="surface-card p-6 flex flex-col">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style="background:rgba(93,169,164,0.12);color:#5DA9A4;">
                            <i class="fas fa-calendar-check text-xl" aria-hidden="true"></i>
                        </div>
                        <h3 class="heading-font font-semibold text-slate-900 mb-2">Сдвиг на пару дней</h3>
                        <p class="text-slate-600 text-sm leading-relaxed flex-1">Часто соседняя дата дешевле — календарь показывает это сразу, без десятка поисков.</p>
                    </div>
                    <div class="surface-card p-6 flex flex-col">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style="background:rgba(93,169,164,0.12);color:#5DA9A4;">
                            <i class="fas fa-bolt text-xl" aria-hidden="true"></i>
                        </div>
                        <h3 class="heading-font font-semibold text-slate-900 mb-2">Быстрый ответ</h3>
                        <p class="text-slate-600 text-sm leading-relaxed flex-1">Цены из кэша акций: открыли страницу — уже видите карту дат, без ожидания оператора.</p>
                    </div>
                    <div class="surface-card p-6 flex flex-col">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style="background:rgba(93,169,164,0.12);color:#5DA9A4;">
                            <i class="fas fa-map-marked-alt text-xl" aria-hidden="true"></i>
                        </div>
                        <h3 class="heading-font font-semibold text-slate-900 mb-2">Популярные страны</h3>
                        <p class="text-slate-600 text-sm leading-relaxed flex-1">Турция, Египет, Таиланд и другие направления из подборки акций — переключайте фильтр сверху.</p>
                    </div>
                </div>

                <div class="mt-12 surface-card p-6 sm:p-8">
                    <h3 class="heading-font text-xl font-bold text-slate-900 mb-4">Как пользоваться</h3>
                    <ul class="space-y-3 text-slate-600">
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold" style="background:rgba(93,169,164,0.12);color:#5DA9A4;">1</span>
                            <span>Выберите город вылета, страну и диапазон ночей.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold" style="background:rgba(93,169,164,0.12);color:#5DA9A4;">2</span>
                            <span>В календаре нажмите день с ценой — справа появятся туры.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold" style="background:rgba(93,169,164,0.12);color:#5DA9A4;">3</span>
                            <span>Откройте карточку тура или перейдите в акции / полный поиск для брони.</span>
                        </li>
                    </ul>
>>>>>>> origin/master
                </div>
            </section>

<<<<<<< HEAD
            <p class="th-deals-cal-footnote">Цены ориентировочные из кэша акций по популярным направлениям. Точная бронь — в карточке тура или в офисе.</p>
        </div>
=======
                <div class="mt-10 text-center">
                    <a href="/frontend/window/offices.php" class="ds-btn-secondary inline-flex items-center gap-2 px-6 py-3 rounded-full mr-3">Наши офисы</a>
                    <a href="/frontend/window/contacts.php" class="ds-btn-primary inline-flex items-center gap-2 px-6 py-3 rounded-full shadow-lg">
                        <i class="fas fa-headset" aria-hidden="true"></i> Задать вопрос
                    </a>
                </div>
            </div>
        </section>
>>>>>>> origin/master
    </main>

    <?php include __DIR__ . '/../../backend/components/footer.php'; ?>

    <script>
    window.TH_DEALS_CAL = {
        departureId: <?php echo (int) $defaultDepartureId; ?>,
<<<<<<< HEAD
        countryId: 0,
        monthsAhead: <?php echo (int) $ladder['monthsAhead']; ?>,
        viewMaxYm: <?php echo json_encode($ladder['viewMaxYm'], JSON_UNESCAPED_UNICODE); ?>,
=======
        countryId: <?php echo (int) $defaultCountryId; ?>,
        countryName: <?php echo json_encode($defaultCountryName, JSON_UNESCAPED_UNICODE); ?>,
>>>>>>> origin/master
        priceMapUrl: '/backend/api/calendar_price_map.php',
        dayToursUrl: '/backend/api/calendar_day_tours.php',
        imageProxy: <?php echo json_encode($imageProxy, JSON_UNESCAPED_SLASHES); ?>
    };
    </script>
<<<<<<< HEAD
    <script src="/frontend/js/deals-calendar.js?v=<?php echo htmlspecialchars($assetV, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
=======
    <script src="/frontend/js/deals-calendar.js?v=2" defer></script>
>>>>>>> origin/master
</body>
</html>
