<?php
// Сначала только кэш — без config и без сессии, чтобы при отдаче из кэша не тратить время
require_once __DIR__ . '/../backend/components/page_cache.php';
// Сессия только если есть cookie (чтобы проверить авторизацию для isAdminRequest)
if (isset($_COOKIE[session_name()]) && $_COOKIE[session_name()] !== '') {
    session_start();
}
if (PageCache::get()) {
    exit; // Страница отдана из кэша
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../backend/config/config.php';
PageCache::start();

require_once __DIR__ . '/../backend/components/yandex_metrika.php';
$th_ym_id = th_yandex_metrika_counter_id();

// Language support
$lang = $_GET['lang'] ?? 'ru';
$translations = [
    'ru' => [
        'home' => 'Главная',
        'tours' => 'Туры',
        'services' => 'Услуги',
        'about' => 'О нас',
        'contacts' => 'Контакты',
        'login' => 'Войти',
        'register' => 'Регистрация',
        'special_offers' => 'Специальные предложения',
        
    ],
    'en' => [
        'home' => 'Home',
        'tours' => 'Tours',
        'services' => 'Services',
        'about' => 'About',
        'contacts' => 'Contacts',
        'login' => 'Login',
        'register' => 'Register',
        'special_offers' => 'Special Offers',
        
    ]
];

$current_page = 'home';
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/svg+xml" href="/frontend/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/frontend/favicon.svg">
    <link rel="alternate icon" type="image/svg+xml" href="/frontend/favicon.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="/frontend/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <?php
    // SEO настройки для главной страницы
    $page_title = 'Туры и горящие туры — Travel Hub';
    $page_description = 'Туры и горящие туры от Travel Hub: подбор отелей, перелётов, виз, страхования и трансферов. Путешествия по всему миру с персональным консьержем. Ответим за 15 минут.';
    $page_keywords = 'туры, путешествия, горящие туры, премиум туры, эксклюзивные туры, отели, перелёты, визы, страхование, трансферы, турагентство, Travel Hub, отдых, туризм';
    // Путь от корня домена; seo_head превратит в абсолютный URL для Open Graph
    $page_image = '/frontend/window/img/hero/e978c0767c0fe7bc778596c86b2b54f3%201.png';
    $page_type = 'website';
    $page_lang = $lang;

    $schema_public_base = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $schema_public_base .= '://' . ($_SERVER['HTTP_HOST'] ?? 'travelhub63.ru');
    
    // Дополнительная Schema.org разметка для главной страницы
    $schema_data = [
        [
            '@type' => 'TravelAgency',
            'name' => 'Travel Hub',
            'description' => 'Премиум туристическое агентство с персональным консьерж-сервисом',
            'url' => $schema_public_base . '/frontend/index.php',
            'sameAs' => [
                'https://t.me/TravelHub63',
                'https://vk.ru/hubtravel',
                'https://max.ru/u/f9LHodD0cOJpBbwh-zr3lqTmDxZiZMLDP-FuyTUa8fyzWO3S2tgc4_Mirnk',
            ],
            'email' => 'hello@travelhub63.ru',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'Московское шоссе, 81Б, ТЦ «Парк Хаус»',
                'addressLocality' => 'Самара',
                'addressCountry' => 'RU'
            ],
            'telephone' => '+78462541656',
            'priceRange' => '$$',
            'currenciesAccepted' => 'RUB, USD, EUR',
            'serviceType' => [
                'Туры',
                'Горящие туры',
                'Подбор отелей',
                'Визы',
                'Страхование'
            ]
        ]
    ];
    
    include __DIR__ . '/../backend/components/seo_head.php';
    ?>
    <?php include __DIR__ . '/../backend/components/tailwind_css.php'; ?>
    <!-- Системные шрифты — отложенная загрузка -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    <!-- Critical CSS inline — первый экран сразу -->
    <?php include __DIR__ . '/../backend/components/critical_css.php'; ?>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Work+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Work+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap"></noscript>
    <link rel="preconnect" href="https://firebase.googleapis.com">
    <link rel="preconnect" href="https://firebaseinstallations.googleapis.com">
    <link rel="preconnect" href="https://maps.yastatic.net">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preload" href="/backend/api/hero-image.php?w=1920" as="image" fetchpriority="high">
    
    <!-- Отложенная загрузка некритических стилей -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css"></noscript>
    
    <link rel="stylesheet" href="/frontend/css/tokens.css?v=2">
    <link rel="stylesheet" href="/frontend/css/responsive.css?v=17" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="/frontend/css/responsive.css?v=17"></noscript>
    <link rel="stylesheet" href="/frontend/css/design-system.css?v=12">
    <link rel="stylesheet" href="/frontend/css/redesign.css?v=22">
    <link rel="stylesheet" href="/frontend/css/v2-theme.css?v=1">
    <link rel="stylesheet" href="/frontend/css/tour-search-wizard.css?v=3">
    <link rel="stylesheet" href="/frontend/css/th-hard-funnel.css?v=4">
    <link rel="stylesheet" href="/frontend/css/mobile-adult.css?v=7">
    <link rel="stylesheet" href="/frontend/css/th-site-lead.css?v=5">
    <link rel="stylesheet" href="/frontend/css/yandex-mobile.css?v=6">
    <link rel="stylesheet" href="/frontend/css/pages/home.css?v=4">
    <link rel="stylesheet" href="/frontend/css/th-sheet.css?v=2">
    <?php include __DIR__ . '/../backend/components/mobile_site_head.php'; ?>
    <link rel="stylesheet" href="/frontend/css/th-unified-ui.css?v=2">
    <script>window.__TH_YM_ID=<?php echo json_encode((string)$th_ym_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
    <script src="/frontend/js/v2-theme.js?v=1" defer></script>
    <script src="/frontend/js/tour-search-wizard.js?v=3" defer></script>
    <script src="/frontend/js/th-lead-capture.js?v=2" defer></script>
    <script src="/frontend/js/th-mobile.js?v=13" defer></script>
    <script src="/frontend/js/th-modal.js?v=2" defer></script>
    <script src="/frontend/js/th-gallery.js?v=1" defer></script>
    
</head>
<body class="text-[#111827] antialiased">
    <?php include __DIR__ . '/../backend/components/header.php'; ?>
    <!-- Загрузчик поиска туров (процентный) -->
    <div id="tv-search-loader" class="tv-search-loader" aria-hidden="true">
        <div class="tv-search-loader-box">
            <div class="tv-search-loader-ring">
                <svg viewBox="0 0 120 120">
                    <defs>
                        <linearGradient id="tv-loader-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#1A1A40"/>
                            <stop offset="100%" stop-color="#5DA9A4"/>
                        </linearGradient>
                    </defs>
                    <circle class="bg" cx="60" cy="60" r="52"/>
                    <circle id="tv-loader-fill" class="fill" cx="60" cy="60" r="52"/>
                </svg>
                <span id="tv-loader-percent" class="tv-search-loader-percent">0</span>
            </div>
            <p id="tv-loader-msg" class="tv-search-loader-msg">Подготовка поиска...</p>
            <p id="tv-loader-sub" class="tv-search-loader-sub tv-search-loader-sub--sla">Обычно 30–60 сек · ищем лучшие предложения</p>
            <p id="tv-loader-instant" aria-live="polite"></p>
            <div id="tv-loader-slow" class="tv-search-loader-slow">
                <p>Поиск занимает дольше обычного — можно подождать<br>или оставить телефон, менеджер подберёт тур.</p>
                <form id="tv-loader-lead-form" class="tv-search-loader-slow__form">
                    <input type="tel" name="phone" required class="tv-search-loader-slow__input" placeholder="+7 (___) ___-__-__" autocomplete="tel">
                    <label class="tv-search-loader-slow__agree"><input type="checkbox" name="agree" checked required> Согласие на обработку данных</label>
                    <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0;height:0;width:0">
                    <p id="tv-loader-lead-msg" class="tv-search-loader-slow__msg hidden"></p>
                    <button type="submit" class="tv-search-loader-slow__submit"><i class="fas fa-headset" aria-hidden="true"></i>Подобрать за меня</button>
                </form>
                <button type="button" data-open-lead-modal="slow-search" style="margin-top:8px;background:transparent;border:none;color:rgba(255,255,255,0.75);font-size:0.8125rem;cursor:pointer;text-decoration:underline">Открыть форму с комментарием</button>
            </div>
        </div>
    </div>
    <!-- Баннер геолокации: только по кнопке «Определить» в форме, не показываем при загрузке -->
    <div id="geo-banner" class="geo-banner hidden" style="display:none">
        <p><i class="fas fa-map-marker-alt text-indigo-600 mr-1"></i>Определить город вылета по местоположению?</p>
        <div class="geo-banner-btns">
            <button type="button" id="geo-allow" class="geo-allow">Разрешить</button>
            <button type="button" id="geo-deny" class="geo-deny">Нет</button>
        </div>
    </div>

    <!-- Hero: полноэкранный фон + поиск в карточке (на мобильных — контент от верха; с md — по центру hero) -->
    <section class="home-hero-section relative flex flex-col justify-start md:justify-center pt-24 pb-12 sm:pt-28 sm:pb-16 md:pb-20 overflow-x-hidden">
        <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1920&amp;q=80&amp;auto=format&amp;fit=crop"
                 srcset="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=960&amp;q=80&amp;auto=format&amp;fit=crop 960w, https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1920&amp;q=80&amp;auto=format&amp;fit=crop 1920w"
                 sizes="100vw"
                 alt="Бирюзовое море и песчаный пляж"
                 class="hero-background-img"
                 width="1920"
                 height="1080"
                 loading="eager"
                 fetchpriority="high"
                 decoding="async"
                 data-fallback1="/frontend/window/img/hero/e978c0767c0fe7bc778596c86b2b54f3%201.png"
                 data-fallback2="/frontend/window/img/сейшелы/a605d9c888f456b0bd001f4b3ef79d68.jpg"
                 data-fallback3="https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=1920&amp;q=80"
                 onerror="if(this.dataset.fallback1){this.removeAttribute('srcset');this.src=this.dataset.fallback1;this.dataset.fallback1='';}else if(this.dataset.fallback2){this.src=this.dataset.fallback2;this.dataset.fallback2='';}else if(this.dataset.fallback3){this.onerror=null;this.src=this.dataset.fallback3.replace('&amp;','&');}">
        <div class="hero-overlay"></div>

        <div class="th-container home-hero-inner mx-auto px-4 sm:px-6 md:px-8 relative z-10 w-full flex flex-col flex-1 min-h-0 justify-start md:justify-center items-center">
            <div class="w-full max-w-3xl mx-auto text-center hero-content mb-6 sm:mb-8 md:mb-10">
                <p class="heading-font text-white/90 text-sm sm:text-base font-semibold tracking-[0.18em] uppercase mb-3 drop-shadow-[0_1px_10px_rgba(0,0,0,0.35)]">Travel Hub</p>
                <h1 class="heading-font text-[2rem] sm:text-4xl md:text-5xl lg:text-[2.5rem] font-bold text-white mb-4 leading-[1.2] tracking-tight drop-shadow-[0_2px_24px_rgba(0,0,0,0.45)]">
                    Найдите тур за 4 простых шага
                </h1>
                <p class="text-[15px] sm:text-lg md:text-xl text-white/95 max-w-2xl mx-auto leading-relaxed drop-shadow-[0_1px_12px_rgba(0,0,0,0.35)]">
                    Когда → откуда → куда → кто едет. Фильтры — на шаге «Куда».
                </p>
            </div>

            <!-- ===================================================
                 ПОИСКОВИК-ВИЗАРД v2 (ID полей сохранены для Tourvisor JS)
                 =================================================== -->
            <div id="tour-search-section" class="tv-sc-shell th-wizard w-full" data-th-wizard="home" data-step="1" data-start-step="1">
                <?php
                if (!function_exists('th_departure_default_id')) {
                    require_once __DIR__ . '/../backend/config/departure_defaults.php';
                }
                ?>
                <div class="th-wizard__head">
                    <h2 class="th-wizard__title">Подбор тура</h2>
                    <p class="th-wizard__sub">Ответьте на 4 коротких вопроса — и увидите предложения</p>
                </div>

                <nav class="th-wizard__progress" aria-label="Шаги поиска">
                    <button type="button" class="th-wizard__dot is-active" data-thw-goto="1" aria-current="step">
                        <span class="th-wizard__dot-num">1</span>
                        <span class="th-wizard__dot-label">Когда</span>
                    </button>
                    <button type="button" class="th-wizard__dot" data-thw-goto="2">
                        <span class="th-wizard__dot-num">2</span>
                        <span class="th-wizard__dot-label">Откуда</span>
                    </button>
                    <button type="button" class="th-wizard__dot" data-thw-goto="3">
                        <span class="th-wizard__dot-num">3</span>
                        <span class="th-wizard__dot-label">Куда</span>
                    </button>
                    <button type="button" class="th-wizard__dot" data-thw-goto="4">
                        <span class="th-wizard__dot-num">4</span>
                        <span class="th-wizard__dot-label">Кто</span>
                    </button>
                </nav>

                <div class="th-wizard__summary" id="th-wizard-summary" aria-live="polite"></div>

                <div class="th-wizard__panels">
                    <!-- Шаг 1: когда -->
                    <div class="th-wizard__panel is-active" data-panel="1">
                        <h3 class="th-wizard__panel-title" tabindex="-1">Когда и на сколько?</h3>
                        <p class="th-wizard__panel-hint">Период вылета и сколько ночей в отеле</p>
                        <div class="th-wizard__when-grid">
                            <div class="tv-sc-field tv-sc-field--pop" id="tv-sc-dates-field">
                                <span class="tv-sc-field-label" aria-hidden="true">Когда</span>
                                <button type="button" class="tv-sc-trigger" id="tv-sc-dates-btn"
                                        aria-haspopup="true" aria-expanded="false" aria-controls="tv-sc-date-popup"
                                        aria-label="Даты вылета">
                                    <i class="fas fa-calendar-alt tv-sc-ico" aria-hidden="true"></i>
                                    <span id="tv-sc-dates-display">Даты</span>
                                    <i class="fas fa-chevron-down tv-sc-chevron" aria-hidden="true"></i>
                                </button>
                                <div id="tv-dates-wrap" class="tv-sc-fp-hidden" aria-hidden="true">
                                    <input type="text" id="tv-dates" class="tv-search-control"
                                           placeholder="Выберите период" data-input readonly autocomplete="off">
                                </div>
                            </div>
                            <div class="tv-sc-field tv-sc-field--pop" id="tv-nights-trigger">
                                <span class="tv-sc-field-label" aria-hidden="true">Ночей</span>
                                <button type="button" class="tv-sc-trigger" id="tv-nights-summary" aria-haspopup="true"
                                        aria-label="Количество ночей">
                                    <i class="far fa-moon tv-sc-ico" aria-hidden="true"></i>
                                    <span id="tv-nights-summary-text">7–14 ночей</span>
                                    <i class="fas fa-chevron-down tv-sc-chevron" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <div class="th-wizard__nav">
                            <button type="button" class="th-wizard__back" data-thw-back hidden>Назад</button>
                            <button type="button" class="th-wizard__next" data-thw-next>Далее <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
                        </div>
                    </div>

                    <!-- Шаг 2: откуда -->
                    <div class="th-wizard__panel" data-panel="2" hidden>
                        <h3 class="th-wizard__panel-title" tabindex="-1">Откуда летим?</h3>
                        <p class="th-wizard__panel-hint">Город вылета</p>
                        <div class="tv-sc-field tv-sc-field--sel tv-sc-field--departure">
                            <span class="tv-sc-field-label" aria-hidden="true">Город вылета</span>
                            <i class="fas fa-plane-departure tv-sc-ico" aria-hidden="true"></i>
                            <select id="tv-departure" name="departureId" class="tv-sc-select tv-select" aria-label="Город вылета">
                                <option value="<?php echo (int) th_departure_default_id(); ?>"><?php echo htmlspecialchars(th_departure_default_name(), ENT_QUOTES, 'UTF-8'); ?></option>
                            </select>
                        </div>
                        <div class="th-wizard__nav">
                            <button type="button" class="th-wizard__back" data-thw-back>Назад</button>
                            <button type="button" class="th-wizard__next" data-thw-next>Далее <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
                        </div>
                    </div>

                    <!-- Шаг 3: куда + фильтры -->
                    <div class="th-wizard__panel" data-panel="3" hidden>
                        <h3 class="th-wizard__panel-title" tabindex="-1">Куда хотим?</h3>
                        <p class="th-wizard__panel-hint">Страна отдыха и доп. параметры</p>
                        <div class="tv-sc-field tv-sc-field--sel">
                            <span class="tv-sc-field-label" aria-hidden="true">Страна</span>
                            <i class="fas fa-globe tv-sc-ico" aria-hidden="true"></i>
                            <select id="tv-country" name="countryId" class="tv-sc-select tv-select" aria-label="Страна отдыха">
                                <option value="">Страна</option>
                            </select>
                        </div>
                        <div class="tv-sc-field tv-sc-field--filters th-wizard__legacy-fields" aria-hidden="true">
                            <button type="button" id="tv-filters-modal-open" class="tv-sc-trigger tv-filters-modal-open-btn"
                                    aria-haspopup="dialog" aria-controls="tv-filters-modal" aria-expanded="false">
                                <span class="tv-sc-ico" aria-hidden="true">⚙</span>
                                <span class="tv-sc-filters-label">Фильтры</span>
                            </button>
                        </div>
                        <div class="th-wizard__advanced">
                            <button type="button" class="th-wizard__advanced-btn" id="th-wizard-open-filters">
                                Доп. параметры (звёзды, питание, бюджет)
                            </button>
                        </div>
                        <div class="th-wizard__nav">
                            <button type="button" class="th-wizard__back" data-thw-back>Назад</button>
                            <button type="button" class="th-wizard__next" data-thw-next>Далее <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
                        </div>
                    </div>

                    <!-- Шаг 4: кто + поиск -->
                    <div class="th-wizard__panel" data-panel="4" hidden>
                        <h3 class="th-wizard__panel-title" tabindex="-1">Кто едет?</h3>
                        <p class="th-wizard__panel-hint">Состав туристов — и можно искать</p>
                        <div class="tv-sc-field tv-sc-field--pop" id="tv-tourists-trigger">
                            <span class="tv-sc-field-label" aria-hidden="true">Туристы</span>
                            <button type="button" class="tv-sc-trigger" id="tv-tourists-summary" aria-haspopup="true"
                                    aria-label="Количество туристов">
                                <i class="fas fa-users tv-sc-ico" aria-hidden="true"></i>
                                <span id="tv-tourists-summary-text">2 взрослых</span>
                                <i class="fas fa-chevron-down tv-sc-chevron" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="th-wizard__nav">
                            <button type="button" class="th-wizard__back" data-thw-back>Назад</button>
                            <button id="tv-search-btn" type="button" class="button button-primary tv-sc-search-btn">
                                <i class="fas fa-search" aria-hidden="true"></i>
                                <span class="tv-sc-search-text">Найти туры</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Legacy row hidden — IDs moved into panels; keep empty marker for old CSS hooks -->
                <div class="tv-sc-row th-wizard__legacy-fields" id="tv-sc-main-row" aria-hidden="true"></div>

                <!-- ─── ПОПАП: ТУРИСТЫ ─── -->
                <!-- JS toggles class "hidden". CSS позиционирует как popup когда не hidden -->
                <div id="tv-tourists-block" class="hidden tv-sc-tourists-popup">
                    <div class="tv-sc-popup-hd">
                        <span class="tv-sc-popup-title">Туристы</span>
                        <button type="button" id="tv-tourists-close-btn" class="tv-sc-x-btn" aria-label="Закрыть">✕</button>
                    </div>
                    <!-- Взрослые: счётчик +/– -->
                    <div class="tv-sc-counter-row">
                        <span class="tv-sc-counter-label">Взрослые</span>
                        <div class="tv-sc-counter-ctrl">
                            <button type="button" id="tv-adults-minus"
                                    class="tv-sc-cnt-btn" aria-label="Меньше">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="tv-sc-cnt-val" id="tv-adults-value">2</span>
                            <button type="button" id="tv-adults-plus"
                                    class="tv-sc-cnt-btn" aria-label="Больше">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- Дети -->
                    <div id="tv-children-rows" class="tv-sc-children-rows"></div>
                    <button type="button" id="tv-add-child-btn" class="tv-sc-add-child">
                        <i class="fas fa-plus"></i> Добавить ребёнка
                    </button>
                    <!-- Пикер возраста -->
                    <div id="tv-child-age-picker" class="hidden tv-sc-age-picker">
                        <p class="tv-sc-age-hint">Возраст ребёнка</p>
                        <div id="tv-child-age-grid" class="tv-sc-age-grid"></div>
                    </div>
                    <label class="tv-sc-remember">
                        <input type="checkbox" id="tv-remember-tourists">
                        <span>Запомнить</span>
                    </label>
                    <button type="button" id="tv-tourists-apply" class="tv-sc-apply-btn">
                        <i class="fas fa-check mr-1"></i>Выбрать
                    </button>
                </div>

                <!-- ─── ПОПАП: ДАТЫ (пресеты спереди, календарь за «Свои даты») ─── -->
                <div id="tv-sc-date-popup" class="tv-sc-date-popup tv-sc-date-popup--v2 tv-sc-date-popup--simple" style="display:none"
                     role="dialog" aria-label="Когда вылетаете" aria-modal="true">
                    <div class="tv-sc-popup-hd">
                        <div class="tv-sc-popup-hd__text">
                            <span class="tv-sc-popup-title">Когда вылетаете?</span>
                            <p class="tv-sc-popup-sub">Нажмите одну кнопку — и готово</p>
                        </div>
                        <button type="button" class="tv-sc-x-btn"
                                data-sc-close="tv-sc-date-popup" aria-label="Закрыть">✕</button>
                    </div>
                    <p id="tv-sc-dates-preview" class="tv-sc-dates-preview" aria-live="polite"></p>

                    <button type="button" id="tv-sc-dates-custom-toggle" class="tv-sc-exact-dates tv-sc-exact-dates--top" aria-expanded="false" aria-controls="tv-sc-cal-panel">
                        <i class="fas fa-calendar-day" aria-hidden="true"></i> Свои даты
                    </button>

                    <div class="tv-sc-date-chips tv-sc-date-chips--quick" id="tv-sc-date-chips-row">
                        <button type="button" class="tv-sc-chip tv-sc-chip--lg active" data-date-preset="14d" aria-pressed="true">
                            <span class="tv-sc-chip__main">Ближайшие 2 недели</span>
                        </button>
                        <button type="button" class="tv-sc-chip tv-sc-chip--lg" data-date-preset="week">
                            <span class="tv-sc-chip__main">Неделя</span>
                        </button>
                    </div>

                    <div class="tv-sc-date-chips tv-sc-date-chips--months" id="tv-sc-date-months-row"></div>

                    <div id="tv-sc-cal-panel" class="tv-sc-cal-panel" hidden>
                        <p id="tv-sc-dates-step" class="tv-sc-dates-step" aria-live="polite">Нажмите день начала, потом конец</p>
                        <div id="tv-sc-cal-container" class="tv-sc-cal-container"></div>
                        <button type="button" id="tv-sc-dates-apply" class="tv-sc-apply-btn tv-sc-apply-btn--sticky">
                            <i class="fas fa-check mr-1" aria-hidden="true"></i>Готово
                        </button>
                    </div>
                </div>

                <!-- Overlay для попапов на мобиле -->
                <div id="tv-sc-overlay" class="tv-sc-overlay" style="display:none" aria-hidden="true"></div>

            </div>
                <div id="main-quick-lead" class="hidden" aria-hidden="true">
                    <p class="hidden">removed</p>
                    <form id="main-quick-lead-form" class="space-y-3 relative">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <input type="text" name="name" required placeholder="Имя" autocomplete="name" class="rounded-xl px-3 py-2.5 text-slate-900 text-sm border-0 shadow-inner">
                            <input type="tel" name="phone" required placeholder="Телефон" autocomplete="tel" class="rounded-xl px-3 py-2.5 text-slate-900 text-sm border-0 shadow-inner">
                        </div>
                        <label class="flex items-start gap-2 text-xs text-white/90 cursor-pointer">
                            <input type="checkbox" name="agree" required class="mt-0.5 rounded border-white/40">
                            <span>Согласие на обработку персональных данных</span>
                        </label>
                        <input type="text" name="website" class="absolute opacity-0 pointer-events-none w-px h-px overflow-hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
                        <button type="submit" class="w-full rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 text-sm transition-colors">Отправить</button>
                        <p id="main-quick-lead-msg" class="hidden text-xs rounded-lg p-2"></p>
                    </form>
                </div>
            </div>
    </section>

            <!-- Результаты поиска -->
            <section id="tv-results-section" class="bg-[#F9FAFB] py-4 md:py-6 border-t border-gray-200/60">
            <div class="th-container mx-auto px-4 sm:px-6 md:px-8 max-w-7xl">
            <div id="tv-results-wrapper" class="tv-results-shell hidden">
                <div class="tv-results-lead-bar hidden" id="tv-results-lead-bar">
                    <button type="button" class="tv-results-lead-bar__btn" data-open-lead-modal="results-toolbar">
                        <i class="fas fa-phone" aria-hidden="true"></i>
                        Не нашли идеальный тур? Оставьте телефон — подберём
                    </button>
                </div>
                <div id="tv-search-alt-banner" class="tv-search-alt-banner hidden" role="status"></div>
                <!-- Двухколоночный макет: sidebar слева + карточки справа -->
                <div class="tv-results-layout">
                    <!-- Sidebar с фильтрами (только на десктопе) -->
                    <aside class="tv-results-sidebar tv-post-filters" id="tv-results-sidebar" aria-label="Фильтры результатов">
                        <div class="tv-results-sidebar__title">
                            <i class="fas fa-sliders-h"></i>
                            Уточнить выдачу
                        </div>
                        <div class="tv-sidebar-filter-group">
                            <span class="tv-sidebar-filter-label th-filter-stars-label">Звёздность</span>
                            <div class="tv-pf-chips">
                                <button type="button" class="tv-pf-chip" data-pf-star data-pf-value="5" aria-pressed="false">5 звёзд</button>
                                <button type="button" class="tv-pf-chip" data-pf-star data-pf-value="4" aria-pressed="false">4 звезды</button>
                                <button type="button" class="tv-pf-chip" data-pf-star data-pf-value="3plus" aria-pressed="false">3 и выше</button>
                            </div>
                        </div>
                        <div class="tv-sidebar-filter-group">
                            <span class="tv-sidebar-filter-label">Питание</span>
                            <div class="tv-pf-chips" data-pf-meals>
                                <button type="button" class="tv-pf-chip" data-pf-meal data-pf-value="AI" aria-pressed="false">Всё включено</button>
                                <button type="button" class="tv-pf-chip" data-pf-meal data-pf-value="HB" aria-pressed="false">Завтрак + ужин</button>
                                <button type="button" class="tv-pf-chip" data-pf-meal data-pf-value="BB" aria-pressed="false">Завтрак</button>
                                <button type="button" class="tv-pf-chip" data-pf-meal data-pf-value="RO" aria-pressed="false">Без питания</button>
                            </div>
                        </div>
                        <div class="tv-sidebar-filter-group">
                            <span class="tv-sidebar-filter-label">Бюджет, ₽</span>
                            <div class="tv-pf-budget-row">
                                <input type="number" data-pf-price-min class="tv-filter-field" inputmode="numeric" min="0" step="1000" placeholder="От" autocomplete="off">
                                <span aria-hidden="true">—</span>
                                <input type="number" data-pf-price-max class="tv-filter-field" inputmode="numeric" min="0" step="1000" placeholder="До" autocomplete="off">
                            </div>
                            <div class="tv-pf-chips tv-pf-chips--budget">
                                <button type="button" class="tv-pf-chip tv-pf-chip--sm" data-pf-budget-quick="150000">до 150 тыс.</button>
                                <button type="button" class="tv-pf-chip tv-pf-chip--sm" data-pf-budget-quick="200000">до 200 тыс.</button>
                                <button type="button" class="tv-pf-chip tv-pf-chip--sm" data-pf-budget-quick="300000">до 300 тыс.</button>
                            </div>
                        </div>
                        <div class="tv-sidebar-filter-group">
                            <span class="tv-sidebar-filter-label">Курорты</span>
                            <div class="tv-pf-regions" data-pf-regions><p class="tv-pf-hint">Появятся после поиска</p></div>
                        </div>
                        <div class="tv-sidebar-filter-group" data-pf-beach-group style="display:none">
                            <span class="tv-sidebar-filter-label">Линия пляжа</span>
                            <div class="tv-pf-chips">
                                <button type="button" class="tv-pf-chip" data-pf-beach="1" aria-pressed="false">1-я линия (у моря)</button>
                                <button type="button" class="tv-pf-chip" data-pf-beach="2" aria-pressed="false">2-я линия</button>
                            </div>
                        </div>
                        <button type="button" data-pf-reset class="tv-pf-reset">Сбросить фильтры</button>
                    </aside>

                    <!-- Основная колонка: прогресс + карточки -->
                    <div class="tv-results-main" style="min-width:0">
                        <div class="tv-results-toolbar">
                            <h3 class="tv-results-toolbar__title heading-font text-xl font-bold text-slate-900">
                                Найдено <span id="tv-result-count">0</span> туров
                            </h3>
                            <div class="tv-sort-rail">
                                <select id="tv-sort" class="tv-select tv-sort-select px-3 py-2 rounded-xl border border-slate-200 text-slate-700">
                                    <option value="price-asc">Сначала дешевые</option>
                                    <option value="price-desc">Сначала дорогие</option>
                                    <option value="rating">По рейтингу</option>
                                </select>
                            </div>
                        </div>
                        <div id="tv-search-progress" class="hidden mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200">
                            <div class="flex items-center gap-3">
                                <div class="animate-spin w-5 h-5 border-2 border-[#FF6B6B] border-t-transparent rounded-full"></div>
                                <span class="text-slate-600">Поиск туров...</span>
                                <span id="tv-progress-text" class="text-slate-500 text-sm"></span>
                            </div>
                        </div>
                        <div id="tv-search-results" class="tv-search-results-grid th-tour-grid">
                            <!-- Карточки туров подставляются JS -->
                        </div>
                        <div id="tv-load-more-wrapper" class="mt-10 text-center hidden">
                            <button type="button" id="tv-load-more-btn" class="button button-primary px-8 py-3.5 text-sm disabled:opacity-70 disabled:pointer-events-none disabled:hover:scale-100">
                                <i class="fas fa-plus-circle mr-2"></i><span id="tv-load-more-text">Загрузить ещё туры</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Как это работает: 3 простых шага (UX для новых и пожилых пользователей) -->
    <section class="how-it-works bg-white" aria-labelledby="how-heading">
        <div class="th-container mx-auto px-4 sm:px-6 md:px-8 max-w-7xl">
            <div class="text-center mb-8 md:mb-10">
                <h2 id="how-heading" class="heading-font text-2xl sm:text-3xl md:text-4xl font-bold text-[#111827] mb-3">Как купить тур — 3 простых шага</h2>
                <p class="text-[#6B7280] text-base md:text-lg max-w-2xl mx-auto">Ничего сложного: всё как в обычном магазине, только туры</p>
            </div>
            <div class="how-it-works__grid">
                <div class="how-step reveal-on-scroll">
                    <span class="how-step__num" aria-hidden="true">1</span>
                    <h3 class="how-step__title">Найдите тур</h3>
                    <p class="how-step__text">Выберите, откуда и куда хотите полететь, укажите даты — и нажмите «Найти туры».</p>
                </div>
                <div class="how-step reveal-on-scroll">
                    <span class="how-step__num" aria-hidden="true">2</span>
                    <h3 class="how-step__title">Нажмите «Забронировать»</h3>
                    <p class="how-step__text">Понравился вариант — оставьте имя и телефон. Менеджер подтвердит наличие мест.</p>
                </div>
                <div class="how-step reveal-on-scroll">
                    <span class="how-step__num" aria-hidden="true">3</span>
                    <h3 class="how-step__title">Оплатите онлайн</h3>
                    <p class="how-step__text">Оплата картой через Т-Банк — безопасно. Или приходите в наш офис в Самаре.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Баннер мобильного приложения -->
    <section class="py-10 md:py-14 bg-white">
        <div class="th-container mx-auto px-4 sm:px-6 md:px-8 max-w-7xl">
            <a href="https://apps.apple.com/ru/app/travelhub/id6786282632"
               id="th-app-install-link"
               target="_blank" rel="noopener"
               class="th-app-banner group block rounded-3xl overflow-hidden">
                <div class="th-app-banner__inner flex flex-col sm:flex-row items-center gap-6 sm:gap-8 p-7 sm:p-9 md:p-10">
                    <div class="th-app-banner__icon flex-shrink-0 flex items-center justify-center">
                        <i class="fas fa-mobile-screen-button" aria-hidden="true"></i>
                    </div>
                    <div class="flex-1 text-center sm:text-left">
                        <p class="th-app-banner__eyebrow">Мобильное приложение TravelHub</p>
                        <h2 class="th-app-banner__title heading-font">Бронируйте самостоятельно в нашем приложении</h2>
                        <p class="th-app-banner__text">Подбор и оплата туров прямо со смартфона — быстро и в любое время.</p>
                    </div>
                    <span class="th-app-banner__cta flex-shrink-0 inline-flex items-center gap-2">
                        <i class="fab fa-apple" aria-hidden="true"></i>
                        <span>Скачать в App Store</span>
                    </span>
                </div>
            </a>
            <div id="th-app-promo-teaser" class="th-app-banner-note">
                <p class="th-app-banner-note__lead">
                    Нажмите <strong>«Скачать в App Store»</strong> — откроется магазин приложений, а мы сразу выдадим промокод на скидку <strong>10%</strong> для бронирования на сайте.
                </p>
                <p class="th-app-banner-note__disclaimer">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    Промокоды скоро появятся в приложении. Пока скидка распространяется только при бронировании на сайте.
                </p>
            </div>
            <div id="th-app-promo-unlocked" class="th-app-banner-promo th-app-banner-promo--unlocked" hidden>
                <span class="th-app-banner-promo__badge" aria-hidden="true">−10%</span>
                <div class="th-app-banner-promo__body">
                    <p class="th-app-banner-promo__text">
                        Спасибо, что доверяете нам! Ваш промокод:
                        <button type="button" class="th-app-banner-promo__code" id="th-app-promo-copy" data-promo-code="TRAVELAPP" aria-label="Скопировать промокод TRAVELAPP">TRAVELAPP</button>
                    </p>
                    <p class="th-app-banner-note__disclaimer th-app-banner-note__disclaimer--compact">
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        Действует на сайте без таймера. В приложении промокоды появятся позже.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Популярные направления (5 направлений по городу вылета; кеш на сервере 21 день) -->
    <?php
    $homePopularDefaultCards = is_file(__DIR__ . '/../backend/config/home_popular_destinations_fallback.php')
        ? require __DIR__ . '/../backend/config/home_popular_destinations_fallback.php'
        : [];
    ?>
    <section class="py-16 md:py-24 bg-[#F9FAFB]" aria-labelledby="dest-heading">
        <div class="th-container mx-auto px-4 sm:px-6 md:px-8 max-w-7xl">
            <div class="text-center mb-10 md:mb-14">
                <h2 id="dest-heading" class="heading-font text-2xl sm:text-3xl md:text-4xl font-bold text-[#111827] mb-3">Популярные направления</h2>
                <p class="text-[#6B7280] text-base md:text-lg max-w-2xl mx-auto">Страны, куда есть туры из вашего города вылета — расскажем про отели и сезоны</p>
            </div>
            <div id="home-popular-destinations-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 md:gap-6">
                <?php foreach ($homePopularDefaultCards as $hpCard): ?>
                <a href="<?php echo htmlspecialchars($hpCard['href'], ENT_QUOTES, 'UTF-8'); ?>" class="dest-card group reveal-on-scroll">
                    <div class="dest-card-bg" style="background-image:url('<?php echo htmlspecialchars($hpCard['image'], ENT_QUOTES, 'UTF-8'); ?>');"></div>
                    <span class="dest-card-title"><?php echo htmlspecialchars($hpCard['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Преимущества -->
    <section class="py-16 md:py-20 bg-white border-y border-gray-100">
        <div class="th-container mx-auto px-4 sm:px-6 md:px-8 max-w-6xl">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 md:gap-10">
                <div class="text-center sm:text-left reveal-on-scroll">
                    <div class="inline-flex w-12 h-12 rounded-[12px] bg-indigo-50 text-indigo-600 items-center justify-center mb-4 mx-auto sm:mx-0">
                        <i class="fas fa-bolt text-xl"></i>
                    </div>
                    <h3 class="heading-font font-semibold text-lg text-[#111827] mb-2">Быстрое бронирование</h3>
                    <p class="text-[#6B7280] text-sm leading-relaxed">Оформление заявки за пару минут и ответ менеджера в течение 15 минут.</p>
                </div>
                <div class="text-center sm:text-left reveal-on-scroll">
                    <div class="inline-flex w-12 h-12 rounded-[12px] bg-indigo-50 text-indigo-600 items-center justify-center mb-4 mx-auto sm:mx-0">
                        <i class="fas fa-tags text-xl"></i>
                    </div>
                    <h3 class="heading-font font-semibold text-lg text-[#111827] mb-2">Лучшие цены</h3>
                    <p class="text-[#6B7280] text-sm leading-relaxed">Актуальные предложения от туроператоров и прозрачная стоимость без скрытых платежей.</p>
                </div>
                <div class="text-center sm:text-left reveal-on-scroll">
                    <div class="inline-flex w-12 h-12 rounded-[12px] bg-indigo-50 text-indigo-600 items-center justify-center mb-4 mx-auto sm:mx-0">
                        <i class="fas fa-headset text-xl"></i>
                    </div>
                    <h3 class="heading-font font-semibold text-lg text-[#111827] mb-2">Поддержка 24/7</h3>
                    <p class="text-[#6B7280] text-sm leading-relaxed">На связи в мессенджерах и по телефону — поможем до и во время поездки.</p>
                </div>
                <div class="text-center sm:text-left reveal-on-scroll">
                    <div class="inline-flex w-12 h-12 rounded-[12px] bg-indigo-50 text-indigo-600 items-center justify-center mb-4 mx-auto sm:mx-0">
                        <i class="fas fa-shield-alt text-xl"></i>
                    </div>
                    <h3 class="heading-font font-semibold text-lg text-[#111827] mb-2">Проверенные партнёры</h3>
                    <p class="text-[#6B7280] text-sm leading-relaxed">Работаем с надёжными туроператорами и официальными поставщиками услуг.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-20 bg-[#F9FAFB]">
        <div class="th-container mx-auto px-6">
            <div class="max-w-4xl mx-auto text-center mb-12">
                <span class="pill-badge mb-4">Отзывы гостей</span>
                <h2 class="heading-font text-3xl sm:text-4xl font-bold text-slate-900 mb-4">Travel Hub — это команда, которая предвосхищает желания</h2>
                <p class="text-slate-700 text-lg max-w-2xl mx-auto">Актуальные отзывы наших клиентов.</p>
            </div>
            <div class="max-w-6xl mx-auto">
                <div class="surface-card p-6 sm:p-8">
                    <script src="https://res.smartwidgets.ru/app.js" async></script>
                    <div class="w-full min-h-[280px]">
                        <div class="sw-app" data-app="c8bf82eb261ead0450f68aa38e8c94f2"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Наши партнёры -->
    <section class="partners-section py-20">
        <div class="th-container mx-auto px-6">
            <div class="max-w-4xl mx-auto text-center mb-12">
                <span class="pill-badge mb-4">Партнёры</span>
                <h2 class="heading-font text-3xl sm:text-4xl font-bold text-slate-900 mb-4">Наши партнёры</h2>
                <p class="text-slate-700 text-lg max-w-2xl mx-auto">Надёжные коллеги, которым мы доверяем</p>
            </div>
            <div class="max-w-6xl mx-auto partners-grid">
                <article class="partners-card">
                    <div class="partners-logo-box">
                        <img src="/frontend/window/img/tour-operators/coral-travel.svg" alt="Coral Travel" width="200" height="80" loading="lazy" decoding="async">
                    </div>
                    <h3 class="partners-title">Coral Travel</h3>
                    <p class="partners-desc">Крупный туроператор с широкой линейкой пляжных и семейных направлений.</p>
                    <a class="partners-link" href="https://www.coral.ru/" target="_blank" rel="noopener">Узнать <i class="fas fa-arrow-right text-[11px]"></i></a>
                </article>

                <article class="partners-card">
                    <div class="partners-logo-box">
                        <img src="/frontend/window/img/tour-operators/pegas-touristik.svg" alt="Pegas Touristik" width="200" height="80" loading="lazy" decoding="async">
                    </div>
                    <h3 class="partners-title">Pegas Touristik</h3>
                    <p class="partners-desc">Надёжные пакетные туры, чартерные программы и удобные вылеты из разных городов.</p>
                    <a class="partners-link" href="https://pegast.ru/" target="_blank" rel="noopener">Узнать <i class="fas fa-arrow-right text-[11px]"></i></a>
                </article>

                <article class="partners-card">
                    <div class="partners-logo-box">
                        <img src="/frontend/window/img/tour-operators/fun-sun.svg" alt="FUN&SUN" width="200" height="80" loading="lazy" decoding="async">
                    </div>
                    <h3 class="partners-title">FUN&SUN</h3>
                    <p class="partners-desc">Современные форматы отдыха с акцентом на сервис, комфорт и актуальные спецпредложения.</p>
                    <a class="partners-link" href="https://fstravel.com/" target="_blank" rel="noopener">Узнать <i class="fas fa-arrow-right text-[11px]"></i></a>
                </article>

                <article class="partners-card">
                    <div class="partners-logo-box">
                        <img src="/frontend/window/img/tour-operators/anex-tour.svg" alt="Anex Tour" width="200" height="80" loading="lazy" decoding="async">
                    </div>
                    <h3 class="partners-title">Anex Tour</h3>
                    <p class="partners-desc">Проверенные туры по популярным направлениям с конкурентными тарифами и поддержкой.</p>
                    <a class="partners-link" href="https://anextour.com/" target="_blank" rel="noopener">Узнать <i class="fas fa-arrow-right text-[11px]"></i></a>
                </article>
            </div>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="py-20 bg-white">
        <div class="th-container mx-auto px-6">
            <div class="max-w-2xl mx-auto text-center mb-12">
                <span class="pill-badge mb-4">Свяжитесь с нами</span>
                <h2 class="heading-font text-3xl font-bold text-slate-900 mb-4">Оставьте заявку</h2>
                <p class="text-slate-700">Оставьте контакты, и мы свяжемся с вами в течение 15 минут. Подготовим 2-3 концепции путешествия с расчётом бюджета.</p>
            </div>
            <div class="max-w-3xl mx-auto">
                <div class="surface-card p-6 md:p-10">
                    <?php include __DIR__ . '/../backend/components/lead_form.php'; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Info with Map -->
    <section id="contact" class="relative py-20 bg-gradient-to-br from-gray-50 via-white to-indigo-50/30">
        <div class="th-container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-12">
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-50 border border-indigo-100 text-xs uppercase tracking-[0.28em] text-indigo-600 mb-6">
                        <i class="fas fa-map-marker-alt"></i>
                        Travel Hub Office
                    </span>
                    <h2 class="heading-font text-3xl sm:text-4xl md:text-5xl font-bold text-slate-900 mb-4">Свяжитесь с нами любым удобным способом</h2>
                    <p class="text-xl text-slate-700 max-w-2xl mx-auto">Вы можете приехать в офис Travel Hub или запросить встречу онлайн. Мы подготовим презентацию и варианты путешествий заранее.</p>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-10 items-start">
                    <div class="space-y-6">
                        <div class="surface-card p-6 lg:p-8 space-y-4 bg-gradient-to-br from-indigo-50/80 via-white to-sky-50/70 border border-indigo-100/80">
                            <h3 class="heading-font text-xl font-bold text-slate-900 mb-4">Контакты</h3>
                            <div class="space-y-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center flex-shrink-0 shadow-md shadow-indigo-500/20 text-[10px] font-extrabold text-white">
                                        MAX
                                    </div>
                                    <div>
                                        <p class="text-sm text-slate-600 mb-1">MAX</p>
                                        <p class="text-slate-800 font-semibold"><a href="https://max.ru/u/f9LHodD0cOJpBbwh-zr3lqTmDxZiZMLDP-FuyTUa8fyzWO3S2tgc4_Mirnk" target="_blank" rel="noopener noreferrer" class="hover:text-indigo-600 transition-colors">Написать в MAX</a></p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center flex-shrink-0 shadow-md shadow-indigo-500/20">
                                        <i class="fab fa-telegram text-white"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-slate-600 mb-1">Telegram</p>
                                        <p class="text-slate-800 font-semibold"><a href="https://t.me/TravelHub63" target="_blank" rel="noopener noreferrer" class="hover:text-indigo-600 transition-colors">@TravelHub63</a></p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center flex-shrink-0 shadow-md shadow-indigo-500/20">
                                        <i class="fas fa-envelope text-white"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-slate-600 mb-1">Email</p>
                                        <p class="text-slate-800"><a href="mailto:hello@travelhub63.ru" class="hover:text-indigo-600 transition-colors">hello@travelhub63.ru</a></p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center flex-shrink-0 shadow-md shadow-indigo-500/20">
                                        <i class="fas fa-map-marker-alt text-white"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-slate-600 mb-1">Офис (Самара)</p>
                                        <p class="text-slate-800">Самара, Московское шоссе, 81Б, ТЦ «Парк Хаус»</p>
                                        <p class="text-xs text-slate-500 mt-1"><a href="/frontend/window/offices.php" class="text-[#5DA9A4] hover:underline">Все офисы в Самаре и Москве</a></p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-3 pt-4 border-t border-gray-100">
                                <a href="https://max.ru/u/f9LHodD0cOJpBbwh-zr3lqTmDxZiZMLDP-FuyTUa8fyzWO3S2tgc4_Mirnk" class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white flex items-center justify-center hover:from-indigo-600 hover:to-indigo-800 transition text-xs font-extrabold shadow-md shadow-indigo-500/20" aria-label="MAX" target="_blank" rel="noopener noreferrer">MAX</a>
                                <a href="https://t.me/TravelHub63" class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center hover:bg-indigo-100 transition" aria-label="Telegram" target="_blank" rel="noopener noreferrer">
                                    <i class="fab fa-telegram text-xl"></i>
                                </a>
                                <a href="https://vk.ru/hubtravel" class="w-12 h-12 rounded-xl bg-sky-100 text-[#0077FF] flex items-center justify-center hover:bg-sky-200 transition" aria-label="VK" target="_blank" rel="noopener noreferrer">
                                    <i class="fab fa-vk text-xl"></i>
                                </a>
                                <a href="tel:+78462541656" class="w-12 h-12 rounded-xl bg-[#FF6B6B]/10 text-[#FF6B6B] flex items-center justify-center hover:bg-[#FF6B6B]/20 transition" aria-label="Позвонить">
                                    <i class="fas fa-phone text-lg"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-2">
                        <div class="surface-card p-0 overflow-hidden">
                            <?php
                            require_once __DIR__ . '/../backend/config/maps.php';
                            $yandex_map_open_url = th_maps()['widget_samara_hq'];
                            include __DIR__ . '/../backend/components/yandex_map_open_link.php';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div id="th-results-sticky-lead" class="th-results-sticky-lead" aria-label="Быстрая заявка">
        <button type="button" class="th-results-sticky-lead__btn" data-open-lead-modal="results-sticky">
            <i class="fas fa-phone" aria-hidden="true"></i>
            Оставить телефон — подберём тур
        </button>
    </div>

    <?php include __DIR__ . '/../backend/components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/ru.js" defer></script>
    <?php
    $_th_fpick_path_idx = __DIR__ . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'tourvisor-flight-pick.js';
    $_th_fpick_ver_idx = is_file($_th_fpick_path_idx) ? (string) filemtime($_th_fpick_path_idx) : '1';
    ?>
    <script src="/frontend/js/tourvisor-flight-pick.js?v=<?php echo htmlspecialchars($_th_fpick_ver_idx, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script>
        <?php
        require_once __DIR__ . '/../backend/components/tourvisor_proxy_url.php';
        $tv_proxy_base = get_tourvisor_proxy_base_url();
        $tv_image_proxy_base = get_tourvisor_image_proxy_base_url();
        $departure_city_images = is_file(__DIR__ . '/../backend/config/departure_city_images.php')
            ? require __DIR__ . '/../backend/config/departure_city_images.php'
            : [];
        ?>
        var TV_API_BASE = <?php echo json_encode($tv_proxy_base); ?>;
        var DEPARTURE_CITY_IMAGES = <?php echo json_encode($departure_city_images); ?>;
        if (typeof location !== 'undefined' && location.protocol === 'https:' && typeof TV_API_BASE === 'string' && TV_API_BASE.indexOf('http://') === 0) {
            TV_API_BASE = 'https:' + TV_API_BASE.substring(5);
        }
        var TV_IMAGE_PROXY = <?php echo json_encode($tv_image_proxy_base); ?>;
        if (typeof location !== 'undefined' && location.protocol === 'https:' && typeof TV_IMAGE_PROXY === 'string' && TV_IMAGE_PROXY.indexOf('http://') === 0) {
            TV_IMAGE_PROXY = 'https:' + TV_IMAGE_PROXY.substring(5);
        }
        const TOUR_DETAIL_BASE = '<?php $sn = $_SERVER["SCRIPT_NAME"] ?? ""; echo (strpos($sn, "frontend") !== false) ? "/frontend" : ""; ?>';
        var TV_IMG_FALLBACK = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&q=80';
        function getTourvisorImageUrl(src) {
            var s = (src == null || src === '') ? '' : String(src).trim();
            if (!s) return TV_IMG_FALLBACK;
            if (window.THTourCard && typeof window.THTourCard.mapTourvisorImageUrl === 'function') {
                var mapped = window.THTourCard.mapTourvisorImageUrl(s, TV_IMAGE_PROXY);
                return mapped || TV_IMG_FALLBACK;
            }
            if (/^\/\//.test(s)) {
                s = (typeof location !== 'undefined' && location.protocol === 'https:' ? 'https:' : 'http:') + s;
            }
            if (/^https?:\/\/static\.tourvisor\.ru\//i.test(s)) {
                return TV_IMAGE_PROXY + '?url=' + encodeURIComponent(s.replace(/^https:/i, 'http:'));
            }
            if (/^static\.tourvisor\.ru\//i.test(s)) {
                return TV_IMAGE_PROXY + '?url=' + encodeURIComponent('http://' + s);
            }
            if (/^https?:\/\//i.test(s)) return s;
            if (/^\/hotel_pics\//i.test(s) || /^hotel_pics\//i.test(s)) {
                return TV_IMAGE_PROXY + '?path=' + encodeURIComponent(s.replace(/^\/+/, ''));
            }
            return TV_IMG_FALLBACK;
        }

        // Справочники: прокси (Firestore → файл → API Tourvisor). Два источника — сначала dictionaries.php, при ошибке напрямую прокси.
        var DICTIONARIES_URL = <?php
            $sn = $_SERVER['SCRIPT_NAME'] ?? '';
            $proto = 'http';
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                $proto = 'https';
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
                $proto = 'https';
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
                $proto = 'https';
            } elseif (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] === '443') {
                $proto = 'https';
            }
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $path = (strpos($sn, '/frontend/') !== false) ? rtrim(dirname($sn), '/') . '/api/dictionaries.php' : '/backend/api/dictionaries.php';
            echo json_encode($proto . '://' . $host . $path);
        ?>;
        if (typeof location !== 'undefined' && location.protocol === 'https:' && typeof DICTIONARIES_URL === 'string' && DICTIONARIES_URL.indexOf('http://') === 0) {
            DICTIONARIES_URL = 'https:' + DICTIONARIES_URL.substring(5);
        }
        (function tvRefsEarlyFetch() {
            const base = TV_API_BASE;
            if (!base || typeof base !== 'string') return;
            const sep = base.indexOf('?') >= 0 ? '&' : '?';
            function safeFetchJson(url, fallback) {
                fallback = fallback || { success: false, data: null };
                return fetch(url, { method: 'GET', cache: 'no-store' })
                    .then(function(r) { return r.text().then(function(t) { return { ok: r.ok, text: t }; }); })
                    .then(function(o) {
                        var t = (o.text || '').trim();
                        if (!t) return fallback;
                        try { return JSON.parse(t); } catch (e) { return fallback; }
                    })
                    .catch(function() { return fallback; });
            }
            var dictPromise = safeFetchJson(DICTIONARIES_URL).then(function(j) {
                window.__tv_dictionaries_raw = j;
                if (j && j.success && Array.isArray(j.departures) && Array.isArray(j.countries)) {
                    return {
                        success: true,
                        departures: j.departures,
                        countries: j.countries,
                        meals: Array.isArray(j.meals) ? j.meals : []
                    };
                }
                return null;
            });
            var depPromise = dictPromise.then(function(o) {
                if (o && o.departures && o.departures.length) return { success: true, data: o.departures };
                return Promise.all([
                    safeFetchJson(base + sep + 'type=departures', { success: false, data: [] }),
                    safeFetchJson(base + sep + 'type=departures&departureCountryId=1', { success: false, data: [] })
                ]).then(function(res) {
                    var byId = {};
                    res.forEach(function(j) {
                        var list = (j && j.success && Array.isArray(j.data)) ? j.data : [];
                        list.forEach(function(d) { if (d && d.id != null) byId[d.id] = d; });
                    });
                    return { success: true, data: Object.values(byId) };
                });
            });
            var countriesPromise = dictPromise.then(function(o) {
                if (o && o.countries && o.countries.length) return { success: true, data: o.countries };
                return Promise.all([
                    safeFetchJson(base + sep + 'type=countries', { success: false, data: [] }),
                    safeFetchJson(base + sep + 'type=countries&onlyCharter=1', { success: false, data: [] })
                ]).then(function(res) {
                    var byId = {};
                    res.forEach(function(j) {
                        var list = (j && j.success && Array.isArray(j.data)) ? j.data : [];
                        list.forEach(function(c) { if (c && c.id != null) byId[c.id] = c; });
                    });
                    return { success: true, data: Object.values(byId) };
                });
            });
            var mealsPromise = dictPromise.then(function(o) {
                if (o && o.meals && o.meals.length) return { success: true, data: o.meals };
                return safeFetchJson(base + sep + 'type=meals', { success: false, data: [] });
            });
            window.__tv_refsPromises = {
                dep: depPromise,
                countries: countriesPromise,
                meals: mealsPromise
            };
        })();
        function tvFetchSummary(type, j) {
            if (!j) return '—';
            const d = j.data;
            if (Array.isArray(d)) {
                if (type === 'departures') return `Города вылета: ${d.length} шт.`;
                if (type === 'countries') return `Страны: ${d.length} шт.`;
                if (type === 'meals') return `Типы питания: ${d.length} шт.`;
                if (type === 'regions') return `Курорты: ${d.length} шт.`;
                if (type === 'dates') return `Доступные даты: ${d.length} шт.`;
                if (type === 'results' || type === 'search-cached') return `Туры/отели: ${d.length} шт.`;
                return `Массив: ${d.length} элементов`;
            }
            if (type === 'search' && j.searchId) return `Поиск запущен, searchId: ${j.searchId}`;
            const sd = j.data;
            if (type === 'status' && sd) return `Статус: ${sd.status || '—'}, прогресс: ${sd.progress ?? '—'}%, мин. цена: ${sd.minPrice ?? '—'}`;
            return j.success ? 'OK' : (j.error || 'Ошибка');
        }

        async function tvFetch(type, params = {}, opts) {
            opts = opts || {};
            const base = TV_API_BASE;
            const u = new URL(base);
            u.searchParams.set('type', type);
            Object.entries(params).forEach(([k, v]) => {
                if (k === 'childs') {
                    if (v === undefined || v === null) return;
                    const s = String(v).trim();
                    if (s !== '') u.searchParams.set(k, s);
                    return;
                }
                if (v != null && v !== '') u.searchParams.set(k, String(v));
            });
            if (type === 'search-cached') {
                if (!opts.cacheOnly) u.searchParams.set('live', '1');
                u.searchParams.set('_t', String(Date.now()));
            }
            const url = u.toString();
            const paramsStr = Object.keys(params).length ? JSON.stringify(params) : '{}';
            console.log('%c[Tourvisor] Запрос', 'color: #1A1A40; font-weight: bold', 'type:', type, 'params:', paramsStr);
            try {
                const r = await fetch(url, { method: 'GET', cache: 'no-store' });
                const cacheHeader = r.headers.get('X-Tourvisor-Cache');
                const cacheSaved = r.headers.get('X-Tourvisor-Cache-Saved');
                const itemsCount = r.headers.get('X-Tourvisor-Items');
                const text = await r.text();
                if (!r.ok) throw new Error('HTTP ' + r.status);
                let j;
                try { j = text ? JSON.parse(text) : { success: false, error: 'Empty response', data: null }; } catch (e) { j = { success: false, error: 'Invalid JSON', data: null }; }
                const summary = tvFetchSummary(type, j);
                if (j.success) {
                    const cacheInfo = cacheHeader ? ` | кэш: ${cacheHeader}${cacheSaved ? ', сохранён в кэш: ' + cacheSaved : ''}${itemsCount ? ', записей: ' + itemsCount : ''}` : '';
                    console.log('%c[Tourvisor] Ответ ✓', 'color: #22c55e; font-weight: bold', 'type:', type, '|', summary + cacheInfo, j);
                } else if (type === 'search-cached' && (j.error === 'Cache miss' || j.fromCache === false)) {
                    console.log('%c[Tourvisor] Кэш пуст', 'color: #94a3b8', 'type: search-cached | для этих параметров кэша нет, будет выполнен живой поиск');
                } else {
                    console.warn('%c[Tourvisor] Ответ с ошибкой', 'color: #ef4444', 'type:', type, '| error:', j.error || j, j);
                    if (typeof j.error === 'string' && (j.error.includes('SSL') || j.error.includes('timeout'))) {
                        console.info('%c[API → сайт] На localhost SSL/timeout бывает из-за сети или фаервола. На продакшене обычно стабильнее. Настройка верная.', 'color: #64748b; font-size: 10px;');
                    }
                }
                return j;
            } catch (e) {
                console.error('%c[Tourvisor] Ошибка запроса ✗', 'color: #ef4444; font-weight: bold', 'type:', type, '|', e.message, '| URL:', url);
                return { success: false, error: String(e.message) };
            }
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', minimumFractionDigits: 0 }).format(price || 0);
        }

        let tvDatePicker = null;
        var tvNightsFrom = 6;
        var tvNightsTo = 9;
        /** Состав туристов для поиска и карточек: должен быть вне DOMContentLoaded — performTvSearch/renderTvResults вызываются снаружи */
        var tvAdultsCount = 2;
        var tvChildrenAges = [];
        document.addEventListener('DOMContentLoaded', async function() {
            var depPopup = document.getElementById('tv-departure-popup');
            var countryPopup = document.getElementById('tv-country-popup');
            if (depPopup && depPopup.parentNode !== document.body) document.body.appendChild(depPopup);
            if (countryPopup && countryPopup.parentNode !== document.body) document.body.appendChild(countryPopup);

            const depSel = document.getElementById('tv-departure');
            const countrySel = document.getElementById('tv-country');
            const datesInp = document.getElementById('tv-dates');
            const mealSel = document.getElementById('tv-meal');
            const regionSel = document.getElementById('tv-region');

            var homePopularGrid = document.getElementById('home-popular-destinations-grid');
            var homePopularSeq = 0;
            function escHomePopularAttr(s) {
                return String(s || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
            }
            function renderHomePopularCards(items) {
                if (!homePopularGrid || !Array.isArray(items) || items.length === 0) return;
                homePopularGrid.innerHTML = items.slice(0, 5).map(function(it) {
                    var href = escHomePopularAttr(it.href || '');
                    var img = escHomePopularAttr(it.image || '');
                    var name = escHomePopularAttr(it.name || '');
                    return '<a href="' + href + '" class="dest-card group reveal-on-scroll">' +
                        '<div class="dest-card-bg" style="background-image:url(\'' + img + '\');"></div>' +
                        '<span class="dest-card-title">' + name + '</span></a>';
                }).join('');
            }
            async function loadHomePopularDestinations(depId) {
                var id = parseInt(String(depId || ''), 10);
                if (!id || !homePopularGrid) return;
                var my = ++homePopularSeq;
                try {
                    var url = '/backend/api/home_popular_destinations.php?departureId=' + encodeURIComponent(id);
                    var r = await fetch(url, { method: 'GET', cache: 'no-store' });
                    var t = await r.text();
                    var j = {};
                    if ((t || '').trim()) { try { j = JSON.parse(t); } catch (e) {} }
                    if (my !== homePopularSeq) return;
                    if (j.success && Array.isArray(j.items) && j.items.length) renderHomePopularCards(j.items);
                } catch (e) {}
            }

            function defaultDepartureIdStr() {
                return String((window.TH_DEPARTURE && window.TH_DEPARTURE.id) || 7);
            }
            function ensureDepartureSelected() {
                if (!depSel || depSel.tagName !== 'SELECT') return;
                var cur = String(depSel.value || '').trim();
                if (cur && depSel.options[depSel.selectedIndex]) return;
                if (window.THDeparturePreference && typeof window.THDeparturePreference.ensureSelectValue === 'function') {
                    window.THDeparturePreference.ensureSelectValue(depSel, departuresList);
                    return;
                }
                var defId = defaultDepartureIdStr();
                var hit = departuresList.find(function (d) { return String(d.id) === defId; })
                    || departuresList.find(function (d) { return /самара/i.test(String(d.name || '')); })
                    || departuresList.find(function (d) { return d.id && !isBlockedDepartureName(d.name); });
                if (hit) depSel.value = String(hit.id);
                else if (depSel.options.length > 1) depSel.selectedIndex = 1;
            }

            if (depSel && depSel.tagName === 'SELECT') depSel.innerHTML = '<option value="">Загрузка...</option>';
            if (countrySel) countrySel.innerHTML = '<option value="">Загрузка...</option>';

            // Приоритет: города вылета, страны и питание — из кэша (__tv_refsPromises). Полный список стран: объединение onlyCharter=0 и onlyCharter=1.
            const refsPromises = window.__tv_refsPromises;
            const pDep = refsPromises ? refsPromises.dep : Promise.all([tvFetch('departures'), tvFetch('departures', { departureCountryId: 1 })]).then(function(res) {
                const byId = {};
                res.forEach(function(j) {
                    const list = (j && j.success && Array.isArray(j.data)) ? j.data : [];
                    list.forEach(function(d) { if (d && d.id != null) byId[d.id] = d; });
                });
                return { success: true, data: Object.values(byId) };
            });
            const pCountries = refsPromises ? refsPromises.countries : Promise.all([tvFetch('countries'), tvFetch('countries', { onlyCharter: '1' })]).then(function(res) {
                const byId = {};
                res.forEach(function(j) {
                    const list = (j && j.success && Array.isArray(j.data)) ? j.data : [];
                    list.forEach(function(c) { if (c && c.id != null) byId[c.id] = c; });
                });
                return { success: true, data: Object.values(byId) };
            });
            const pMeal = refsPromises ? refsPromises.meals : tvFetch('meals');

            const today = new Date();
            const defaultFrom = new Date(today);
            const defaultTo = new Date(today); defaultTo.setDate(today.getDate() + 14);
            function initTvDatePicker() {
                if (!datesInp) return;
                if (typeof flatpickr !== 'function') return;
                try {
                    if (tvDatePicker) return;
                    tvDatePicker = flatpickr(datesInp, { mode: 'range', dateFormat: 'd-m-Y', locale: 'ru', allowInput: false, clickOpens: true, minDate: today, defaultDate: [defaultFrom, defaultTo], disableMobile: true });
                    if (tvDatePicker) {
                        try { window.tvDatePicker = tvDatePicker; } catch (e) {}
                        datesInp.addEventListener('focus', function() { tvDatePicker.open(); });
                        var wrap = document.getElementById('tv-dates-wrap');
                        if (wrap) wrap.addEventListener('click', function(e) { e.preventDefault(); datesInp.focus(); tvDatePicker.open(); });
                        var dispEl = document.getElementById('tv-sc-dates-display');
                        if (dispEl && tvDatePicker.selectedDates && tvDatePicker.selectedDates.length >= 2) {
                            dispEl.textContent = fmtCompactRange(tvDatePicker.selectedDates[0], tvDatePicker.selectedDates[1]);
                        }
                    }
                } catch (_) {}
            }
            initTvDatePicker();
            if (!tvDatePicker && datesInp) window.addEventListener('load', initTvDatePicker);

            var rDep, rCountries, rMeal;
            try {
                var results = await Promise.all([pDep, pCountries, pMeal]);
                rDep = results[0]; rCountries = results[1]; rMeal = results[2];
            } catch (err) {
                console.error('[Tourvisor] Ошибка загрузки справочников', err);
                rDep = { success: false, data: [] }; rCountries = { success: false, data: [] }; rMeal = { success: false, data: [] };
            }
            console.log('%c[API → сайт] Ответы API получены', 'color: #5DA9A4; font-weight: bold', { departures: rDep.success ? (rDep.data?.length ?? 0) + ' шт.' : 'ошибка', countries: rCountries.success ? (rCountries.data?.length ?? 0) + ' шт.' : 'ошибка', meals: rMeal.success ? (rMeal.data?.length ?? 0) + ' шт.' : 'ошибка' });
            var needDebug = !rDep.success || !rCountries.success || !rMeal.success || !(rDep.data && rDep.data.length) || !(rCountries.data && rCountries.data.length) || !(rMeal.data && rMeal.data.length);
            if (needDebug) {
                var debugPayload = {
                    dictionaries_response: window.__tv_dictionaries_raw || null,
                    dep: { success: rDep.success, error: rDep.error, error_detail: rDep.error_detail || null, dataLength: Array.isArray(rDep.data) ? rDep.data.length : 0, raw: rDep },
                    countries: { success: rCountries.success, error: rCountries.error, error_detail: rCountries.error_detail || null, dataLength: Array.isArray(rCountries.data) ? rCountries.data.length : 0, raw: rCountries },
                    meals: { success: rMeal.success, error: rMeal.error, error_detail: rMeal.error_detail || null, dataLength: Array.isArray(rMeal.data) ? rMeal.data.length : 0, raw: rMeal },
                    urls: { DICTIONARIES_URL: typeof DICTIONARIES_URL !== 'undefined' ? DICTIONARIES_URL : '—', TV_API_BASE: typeof TV_API_BASE !== 'undefined' ? TV_API_BASE : '—' }
                };
                console.group('%c[API → сайт] Подробная отладка: почему данные пустые или с ошибкой', 'color: #f59e0b; font-weight: bold');
                if (window.__tv_dictionaries_raw && window.__tv_dictionaries_raw._debug) {
                    console.log('%c▼ Ответ dictionaries.php с сервера (_debug):', 'color: #ef4444; font-weight: bold', window.__tv_dictionaries_raw._debug);
                }
                console.log('Города вылета (departures):', debugPayload.dep);
                if (rDep && rDep.error_detail) console.log('%c▼ Ошибка сервера (departures):', 'color: #ef4444', rDep.error_detail);
                console.log('Страны (countries):', debugPayload.countries);
                if (rCountries && rCountries.error_detail) console.log('%c▼ Ошибка сервера (countries):', 'color: #ef4444', rCountries.error_detail);
                console.log('Питание (meals):', debugPayload.meals);
                if (rMeal && rMeal.error_detail) console.log('%c▼ Ошибка сервера (meals) — скинь это разработчику:', 'color: #ef4444; font-weight: bold', rMeal.error_detail);
                console.log('URL справочников:', debugPayload.urls.DICTIONARIES_URL);
                console.log('Базовый URL прокси:', debugPayload.urls.TV_API_BASE);
                console.log('%c▼ СКОПИРУЙ ВЕСЬ ЭТОТ ОБЪЕКТ И ОТПРАВЬ (Ctrl+A в поддереве, правый клик → Copy object):', 'color: #5DA9A4; font-weight: bold');
                console.log(debugPayload);
                console.groupEnd();
            }
            let departuresList = [];
            let countriesList = [];
            function filterDepartureList(list) {
                if (window.THDeparturePreference && typeof window.THDeparturePreference.filterDepartures === 'function') {
                    return window.THDeparturePreference.filterDepartures(list || []);
                }
                return (list || []).filter(function (d) {
                    var n = String((d && d.name) || '').toLowerCase().trim();
                    return n !== 'красноярск' && n !== 'krasnoyarsk';
                });
            }
            function isBlockedDepartureName(name) {
                if (window.THDeparturePreference && typeof window.THDeparturePreference.isBlockedDepartureName === 'function') {
                    return window.THDeparturePreference.isBlockedDepartureName(name);
                }
                var n = String(name || '').toLowerCase().trim();
                return n === 'красноярск' || n === 'krasnoyarsk';
            }
            if (rDep.success && Array.isArray(rDep.data) && rDep.data.length > 0) {
                departuresList = filterDepartureList(rDep.data);
                if (depSel && depSel.tagName === 'SELECT') {
                    depSel.innerHTML = '<option value="">— Выберите город —</option>' + departuresList.map(d =>
                        `<option value="${d.id}">${d.name || ''}</option>`
                    ).join('');
                }
                console.log('%c[API → сайт] Справочник городов вылета', 'color: #22c55e', departuresList.length, 'шт.');
            } else {
                if (depSel && depSel.tagName === 'SELECT') {
                    var defId = defaultDepartureIdStr();
                    var defNm = (window.TH_DEPARTURE && window.TH_DEPARTURE.name) || 'Самара';
                    depSel.innerHTML = '<option value="' + defId + '">' + defNm + '</option>';
                    depSel.value = defId;
                }
                console.warn('[API → сайт] Города вылета: данные с API не получены. Причина:', rDep && rDep.error ? rDep.error : (rDep && Array.isArray(rDep.data) && rDep.data.length === 0 ? 'массив пустой' : (rDep && rDep.success === false ? 'success=false' : 'нет данных')), 'Ответ:', rDep);
            }

            if (window.THDeparturePreference) {
                window.THDeparturePreference.onDeparturesReady(departuresList);
            }
            ensureDepartureSelected();

            if (rCountries.success && Array.isArray(rCountries.data) && rCountries.data.length > 0) {
                let countries = rCountries.data;
                const turkey = countries.find(c => (c.name || '').toLowerCase().includes('турци') || c.id == 12 || c.id == 4);
                if (turkey) {
                    countries = [turkey, ...countries.filter(c => c.id !== turkey.id && c.id !== (turkey.id === 12 ? 4 : 12))];
                }
                countrySel.innerHTML = '<option value="">— Страна —</option>' + countries.map(c =>
                    `<option value="${c.id}">${c.name || ''}</option>`
                ).join('');
                countrySel.value = turkey ? turkey.id : (countries[0]?.id || '');
                if (!depSel.value) {
                    var samId = defaultDepartureIdStr();
                    var sam = departuresList.find(function(d) { return String(d.id) === String(samId); })
                        || departuresList.find(function(d) { return /самара/i.test(String(d.name || '')); });
                    if (sam) depSel.value = sam.id;
                    else {
                        var depFb = departuresList.find(function(d) { return !isBlockedDepartureName(d.name); });
                        if (depFb) depSel.value = depFb.id;
                    }
                }
                ensureDepartureSelected();
                if (countrySel.value && depSel.value) countrySel.dispatchEvent(new Event('change'));
                console.log('%c[API → сайт] Данные с API применены: страны', 'color: #22c55e', countries.length, 'стран', countries.slice(0, 5).map(c => c.name));
            } else if (rCountries.success && Array.isArray(rCountries.data)) {
                countrySel.innerHTML = '<option value="">— Нет стран —</option>';
                console.warn('[API → сайт] Страны: список пуст от API (success=true, но data пустой). Ответ:', rCountries);
            } else {
                countrySel.innerHTML = '<option value="">— Не удалось загрузить —</option>';
                console.warn('[API → сайт] Страны: данные с API не получены. Причина:', rCountries && rCountries.error ? rCountries.error : (rCountries && rCountries.success === false ? 'success=false' : 'нет данных'), 'Ответ:', rCountries);
            }

            if (rMeal.success && Array.isArray(rMeal.data) && rMeal.data.length > 0) {
                mealSel.innerHTML = '<option value="">Любое</option>' + rMeal.data.map(m =>
                    `<option value="${m.id}">${m.russianName || m.name || ''}</option>`
                ).join('');
                console.log('%c[API → сайт] Данные с API применены: питание', 'color: #22c55e', rMeal.data.length, 'типов', rMeal.data.map(m => m.russianName || m.name));
                console.log('[Фильтры] meals:', rMeal.data.length, 'записей');
            } else {
                var mealFallback = [{ id: 1, name: 'RO', russianName: 'Без питания' }, { id: 2, name: 'BB', russianName: 'Завтрак' }, { id: 3, name: 'HB', russianName: 'Завтрак + ужин' }, { id: 4, name: 'FB', russianName: 'Полный пансион' }, { id: 5, name: 'AI', russianName: 'Всё включено' }, { id: 6, name: 'UAI', russianName: 'Ультра всё включено' }];
                mealSel.innerHTML = '<option value="">Любое</option>' + mealFallback.map(m => '<option value="' + m.id + '">' + (m.russianName || m.name) + '</option>').join('');
                console.warn('[API → сайт] Питание: данные с API не получены, подставлен fallback. Причина:', rMeal && rMeal.error ? rMeal.error : (rMeal && rMeal.success === false ? 'success=false' : 'нет данных'));
                console.log('[Фильтры] Ошибка meals: использован fallback', mealFallback.length, 'записей');
            }

            regionSel.innerHTML = '<option value="">Любой</option>';

            if (rCountries.success && Array.isArray(rCountries.data)) {
                countriesList = rCountries.data;
                const turkey = countriesList.find(c => (c.name || '').toLowerCase().includes('турци') || c.id == 12 || c.id == 4);
                if (turkey) countriesList = [turkey, ...countriesList.filter(c => c.id !== turkey.id && c.id !== (turkey.id === 12 ? 4 : 12))];
            }

            function resolveHomePopularDepartureId() {
                if (!departuresList || !departuresList.length) return 0;
                try {
                    var sid = localStorage.getItem('th_departure_id');
                    var sname = localStorage.getItem('th_departure_name');
                    if (sname && isBlockedDepartureName(sname)) {
                        sid = null;
                        sname = null;
                    }
                    if (sid) {
                        var idNum = parseInt(String(sid), 10);
                        if (idNum && departuresList.some(function (d) { return parseInt(String(d.id), 10) === idNum; })) {
                            return idNum;
                        }
                    }
                    if (sname && window.THDeparturePreference && typeof window.THDeparturePreference.matchDeparture === 'function') {
                        var m = window.THDeparturePreference.matchDeparture(sname, departuresList);
                        if (m && m.id != null) return parseInt(String(m.id), 10);
                    }
                } catch (e) {}
                if (depSel && depSel.value) return parseInt(String(depSel.value), 10) || 0;
                return 0;
            }
            var homePopularDepId = resolveHomePopularDepartureId();
            if (homePopularDepId) loadHomePopularDestinations(homePopularDepId);

            console.log('%c[API → сайт] Итог: форма заполнена данными с API. Курорты и расширенные фильтры подгрузятся при выборе страны.', 'color: #5DA9A4; font-weight: bold');

            function renderDepList(filter) {
                var listEl = document.getElementById('tv-departure-list');
                if (!listEl) return;
                var q = (filter || '').toLowerCase().trim();
                var list = q ? departuresList.filter(function(d) { return (d.name || '').toLowerCase().indexOf(q) !== -1; }) : departuresList;
                listEl.innerHTML = list.map(function(d) {
                    var name = (d.name || '').toString().replace(/</g, '&lt;').replace(/"/g, '&quot;');
                    return '<button type="button" class="tv-choice-item" data-id="' + d.id + '">' + name + '</button>';
                }).join('');
                listEl.querySelectorAll('.tv-choice-item').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        depSel.value = btn.getAttribute('data-id');
                        var p = document.getElementById('tv-departure-popup');
                        if (p) p.style.display = 'none';
                        depSel.dispatchEvent(new Event('change'));
                    });
                });
            }
            function renderCountryList(filter) {
                var listEl = document.getElementById('tv-country-list');
                if (!listEl) return;
                var q = (filter || '').toLowerCase().trim();
                var list = q ? countriesList.filter(function(c) { return (c.name || '').toLowerCase().indexOf(q) !== -1; }) : countriesList;
                listEl.innerHTML = list.map(function(c) {
                    var name = (c.name || '').toString().replace(/</g, '&lt;').replace(/"/g, '&quot;');
                    return '<button type="button" class="tv-choice-item" data-id="' + c.id + '">' + name + '</button>';
                }).join('');
                listEl.querySelectorAll('.tv-choice-item').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        countrySel.value = btn.getAttribute('data-id');
                        var p = document.getElementById('tv-country-popup');
                        if (p) p.style.display = 'none';
                        countrySel.dispatchEvent(new Event('change'));
                    });
                });
            }
            window.__tv_hotelServicesCache = window.__tv_hotelServicesCache || {};
            function mapGroupToCategory(name) {
                var n = (name || '').toLowerCase();
                if (/пляж|расположен|линия|берег|побереж/i.test(n)) return 'beach';
                if (/отель|территор|бассейн|спорт|ресторан|бар/i.test(n)) return 'hotel';
                if (/удобств|номер|комнат|ванн|wi-fi|кондицион|телевизор|балкон|кухн/i.test(n)) return 'room';
                if (/дет|ребен|клуб|анимац/i.test(n)) return 'children';
                return 'hotel';
            }
            var tvSelectedServiceIds = [];
            var tvServiceIdToName = {};
            function renderSelectedFilterTags() {
                var wrap = document.getElementById('tv-adv-selected-tags');
                if (!wrap) return;
                wrap.innerHTML = tvSelectedServiceIds.map(function(id) {
                    var name = tvServiceIdToName[id] || ('ID ' + id);
                    return '<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-sky-100 text-sky-800 text-sm">' + name + ' <button type="button" class="tv-adv-remove-tag hover:text-red-600" data-id="' + id + '" aria-label="Снять">×</button></span>';
                }).join('');
                wrap.querySelectorAll('.tv-adv-remove-tag').forEach(function(b) {
                    b.addEventListener('click', function() {
                        var id = parseInt(this.dataset.id, 10);
                        tvSelectedServiceIds = tvSelectedServiceIds.filter(function(x) { return x !== id; });
                        var cb = document.querySelector('.tv-adv-service-cb[data-id="' + id + '"]');
                        if (cb) cb.checked = false;
                        renderSelectedFilterTags();
                    });
                });
            }
            function renderAdvancedFilters(groups) {
                var beach = document.getElementById('tv-adv-cat-beach');
                var hotel = document.getElementById('tv-adv-cat-hotel');
                var room = document.getElementById('tv-adv-cat-room');
                var children = document.getElementById('tv-adv-cat-children');
                if (!beach || !hotel) return;
                [beach, hotel, room, children].forEach(el => { if (el) el.innerHTML = ''; });
                if (!Array.isArray(groups) || groups.length === 0) {
                    var msg = document.createElement('p');
                    msg.className = 'text-slate-500 text-sm';
                    msg.textContent = 'Нет данных по услугам для этой страны. Данные подставляются из API.';
                    if (beach) beach.appendChild(msg);
                    return;
                }
                groups.forEach(function(gr) {
                    var cat = mapGroupToCategory(gr.name);
                    var container = cat === 'beach' ? beach : (cat === 'room' ? room : (cat === 'children' ? children : hotel));
                    var items = gr.items || [];
                    items.forEach(function(item) {
                        var id = parseInt(item.id, 10);
                        var name = (item.name || item.russianName || '').toString();
                        if (!name || !container) return;
                        tvServiceIdToName[id] = name;
                        var label = document.createElement('label');
                        label.className = 'inline-flex items-center gap-2 cursor-pointer text-slate-700';
                        var cb = document.createElement('input');
                        cb.type = 'checkbox';
                        cb.className = 'tv-adv-service-cb rounded border-slate-300 text-[#1A1A40] focus:ring-[#1A1A40]';
                        cb.dataset.id = id;
                        if (tvSelectedServiceIds.indexOf(id) >= 0) cb.checked = true;
                        cb.addEventListener('change', function() {
                            if (this.checked) {
                                if (tvSelectedServiceIds.indexOf(id) < 0) tvSelectedServiceIds.push(id);
                            } else {
                                tvSelectedServiceIds = tvSelectedServiceIds.filter(function(x) { return x !== id; });
                            }
                            renderSelectedFilterTags();
                        });
                        label.appendChild(cb);
                        label.appendChild(document.createTextNode(name));
                        container.appendChild(label);
                    });
                });
                renderSelectedFilterTags();
            }
            async function loadAdvancedFilters(countryId) {
                if (!countryId) return;
                var beach = document.getElementById('tv-adv-cat-beach');
                if (beach) beach.innerHTML = '<p class="text-slate-500 text-sm">Загрузка из API…</p>';
                if (window.__tv_hotelServicesCache[countryId]) {
                    [document.getElementById('tv-adv-cat-beach'), document.getElementById('tv-adv-cat-hotel'), document.getElementById('tv-adv-cat-room'), document.getElementById('tv-adv-cat-children')].forEach(el => { if (el) el.innerHTML = ''; });
                    renderAdvancedFilters(window.__tv_hotelServicesCache[countryId]);
                    console.log('%c[API → сайт] Расширенные фильтры: данные из кэша (ранее загружены с API)', 'color: #22c55e', 'countryId', countryId);
                    return;
                }
                var r = await tvFetch('hotel-services', { countryId: countryId });
                [document.getElementById('tv-adv-cat-beach'), document.getElementById('tv-adv-cat-hotel'), document.getElementById('tv-adv-cat-room'), document.getElementById('tv-adv-cat-children')].forEach(el => { if (el) el.innerHTML = ''; });
                if (r.success && Array.isArray(r.data) && r.data.length > 0) {
                    window.__tv_hotelServicesCache[countryId] = r.data;
                    renderAdvancedFilters(r.data);
                    var totalItems = r.data.reduce(function(sum, gr) { return sum + (gr.items && gr.items.length ? gr.items.length : 0); }, 0);
                    console.log('%c[API → сайт] Данные с API применены: расширенные фильтры (услуги)', 'color: #22c55e', r.data.length, 'категорий,', totalItems, 'услуг для countryId', countryId);
                } else {
                    renderAdvancedFilters([]);
                    console.warn('[API → сайт] Расширенные фильтры: API не вернул данные для countryId', countryId);
                }
            }
            countrySel.addEventListener('change', async function() {
                const cid = this.value;
                const did = depSel.value;
                regionSel.innerHTML = '<option value="">Загрузка...</option>';
                if (!cid || !did) { regionSel.innerHTML = '<option value="">Любой</option>'; datesInp.placeholder = 'Период'; return; }
                let rReg = await tvFetch('regions', { countryId: cid });
                if (!rReg.success && (!rReg.error || String(rReg.error).indexOf('timeout') >= 0 || String(rReg.error).indexOf('failed') >= 0)) {
                    await new Promise(r => setTimeout(r, 800));
                    rReg = await tvFetch('regions', { countryId: cid });
                }
                if (rReg.success && Array.isArray(rReg.data) && rReg.data.length > 0) {
                    regionSel.innerHTML = '<option value="">Любой</option>' + rReg.data.map(r =>
                        `<option value="${r.id}">${r.name || ''}</option>`
                    ).join('');
                    console.log('%c[API → сайт] Данные с API применены: курорты', 'color: #22c55e', rReg.data.length, 'курортов для countryId', cid);
                    console.log('[Фильтры] regions:', rReg.data.length, 'записей для countryId', cid);
                } else {
                    regionSel.innerHTML = '<option value="">Любой</option>';
                    console.warn('[API → сайт] Курорты: пусто или ошибка, оставлен «Любой»');
                    console.log('[Фильтры] Ошибка regions:', rReg.error || 'пустой ответ', '— курорты временно недоступны');
                }
                loadAdvancedFilters(cid);
                const rDates = await tvFetch('dates', { departureId: did, countryId: cid });
                if (rDates.success && Array.isArray(rDates.data) && rDates.data.length > 0) {
                    const first = rDates.data[0];
                    if (typeof first === 'string') {
                        const d1 = new Date(String(first).replace(/-/g, '/') + 'T12:00:00');
                        const d2 = new Date(d1);
                        if (!isNaN(d1.getTime())) {
                            d2.setDate(d1.getDate() + 23);
                            if (tvDatePicker) tvDatePicker.setDate([d1, d2]);
                        }
                    }
                } else {
                    const d1 = new Date();
                    const d2 = new Date(); d2.setDate(d2.getDate() + 14);
                    if (tvDatePicker) tvDatePicker.setDate([d1, d2]);
                }
            });
            if (countrySel.value) loadAdvancedFilters(countrySel.value);

            document.getElementById('tv-search-btn').addEventListener('click', function() { performTvSearch(true); });
            document.getElementById('tv-sort').addEventListener('change', applyTvSort);
            document.getElementById('tv-load-more-btn').addEventListener('click', () => loadMoreTvResults());
            
            tvNightsFrom = 7;
            tvNightsTo = 14;
            var tvNightsPopup = document.getElementById('tv-nights-popup');
            if (tvNightsPopup && tvNightsPopup.parentNode !== document.body) {
                document.body.appendChild(tvNightsPopup);
            }
            var tvNightsGrid = document.getElementById('tv-nights-grid');
            var tvNightsQuick = document.getElementById('tv-nights-quick');
            var tvNightsSelectFrom = true;
            function closeTvNightsPopup() {
                if (tvNightsPopup) {
                    tvNightsPopup.classList.add('hidden');
                    tvNightsPopup.style.display = 'none';
                    tvNightsPopup.setAttribute('aria-hidden', 'true');
                }
            }
            function updateTvNightsSummary() {
                var el = document.getElementById('tv-nights-summary-text');
                function nWord(n) { return n % 10 === 1 && n % 100 !== 11 ? 'ночь' : (n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20) ? 'ночи' : 'ночей'); }
                if (el) el.textContent = tvNightsFrom === tvNightsTo ? (tvNightsFrom + ' ' + nWord(tvNightsFrom)) : (tvNightsFrom + '–' + tvNightsTo + ' ' + nWord(tvNightsTo));
                var fromLbl = document.getElementById('tv-nights-from-label');
                var toLbl = document.getElementById('tv-nights-to-label');
                var hintEl = document.getElementById('tv-nights-hint');
                if (fromLbl) fromLbl.textContent = tvNightsFrom;
                if (toLbl) toLbl.textContent = tvNightsTo;
                if (hintEl) {
                    hintEl.textContent = tvNightsSelectFrom
                        ? 'Свой диапазон: нажмите число «от»'
                        : 'Теперь нажмите число «до»';
                }
            }
            function applyTvNightsQuick(from, to, autoClose) {
                tvNightsFrom = from;
                tvNightsTo = to;
                tvNightsSelectFrom = true;
                window.tvNightsFrom = from;
                window.tvNightsTo = to;
                renderTvNightsGrid();
                updateTvNightsSummary();
                if (autoClose) closeTvNightsPopup();
            }
            if (tvNightsQuick) {
                if (window.THDatePresets && typeof window.THDatePresets.renderNightsChips === 'function') {
                    window.THDatePresets.renderNightsChips(tvNightsQuick, function (from, to) {
                        applyTvNightsQuick(from, to, true);
                    });
                } else {
                    [[7, 7, '7 ночей'], [7, 10, '7–10'], [10, 14, '10–14'], [14, 21, '14–21']].forEach(function (p) {
                        var b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'tv-nights-quick__chip';
                        b.textContent = p[2];
                        b.addEventListener('click', function () { applyTvNightsQuick(p[0], p[1], true); });
                        tvNightsQuick.appendChild(b);
                    });
                }
            }
            function renderTvNightsGrid() {
                if (!tvNightsGrid) return;
                tvNightsGrid.querySelectorAll('.tv-nights-cell').forEach(function(btn) {
                    var n = parseInt(btn.getAttribute('data-n'), 10);
                    var lbl = btn.querySelector('.cell-label');
                    btn.style.backgroundColor = '';
                    btn.style.color = '';
                    btn.style.border = '';
                    btn.classList.remove('text-white');
                    btn.classList.add('bg-slate-100', 'text-slate-700');
                    if (lbl) lbl.classList.add('opacity-0');
                    if (n === tvNightsFrom) {
                        btn.classList.remove('bg-slate-100', 'text-slate-700');
                        btn.style.backgroundColor = '#1A1A40';
                        btn.style.color = '#fff';
                        btn.style.border = '2px solid #2a8ad4';
                        btn.classList.add('text-white');
                        if (lbl) lbl.classList.remove('opacity-0');
                    } else if (n === tvNightsTo && tvNightsTo !== tvNightsFrom) {
                        btn.classList.remove('bg-slate-100', 'text-slate-700');
                        btn.style.backgroundColor = '#1e6bb8';
                        btn.style.color = '#fff';
                        btn.style.border = '2px solid #185a9e';
                        btn.classList.add('text-white');
                        if (lbl) lbl.classList.remove('opacity-0');
                    } else if (n > tvNightsFrom && n < tvNightsTo) {
                        btn.classList.remove('bg-slate-100', 'text-slate-700');
                        btn.style.backgroundColor = 'rgba(121, 188, 183, 0.5)';
                        btn.style.color = '#0c4a6e';
                        btn.classList.add('text-sky-800');
                        if (lbl) lbl.classList.remove('opacity-0');
                    }
                });
            }
            function openTvNightsPopup() {
                tvNightsSelectFrom = true;
                updateTvNightsSummary();
                renderTvNightsGrid();
                if (tvNightsPopup) { tvNightsPopup.classList.remove('hidden'); tvNightsPopup.style.display = 'flex'; tvNightsPopup.setAttribute('aria-hidden', 'false'); }
            }
            var tvNightsTrigger = document.getElementById('tv-nights-trigger');
            var tvNightsSummaryBtn = document.getElementById('tv-nights-summary');
            if (tvNightsTrigger) tvNightsTrigger.addEventListener('click', openTvNightsPopup);
            if (tvNightsSummaryBtn) tvNightsSummaryBtn.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); openTvNightsPopup(); });
            tvNightsGrid && tvNightsGrid.addEventListener('click', function(e) {
                var btn = e.target.closest('.tv-nights-cell');
                if (!btn) return;
                var n = parseInt(btn.getAttribute('data-n'), 10);
                if (n < 1 || n > 28) return;
                var done = false;
                if (tvNightsSelectFrom) {
                    tvNightsFrom = n;
                    tvNightsTo = n;
                    tvNightsSelectFrom = false;
                } else {
                    if (n < tvNightsFrom) {
                        tvNightsTo = tvNightsFrom;
                        tvNightsFrom = n;
                    } else {
                        tvNightsTo = n;
                    }
                    tvNightsSelectFrom = true;
                    done = true;
                }
                window.tvNightsFrom = tvNightsFrom;
                window.tvNightsTo = tvNightsTo;
                renderTvNightsGrid();
                updateTvNightsSummary();
                if (done) setTimeout(closeTvNightsPopup, 220);
            });
            document.getElementById('tv-nights-apply').addEventListener('click', function() {
                if (tvNightsFrom > 28) tvNightsFrom = 28;
                if (tvNightsTo > 28) tvNightsTo = 28;
                if (tvNightsTo < tvNightsFrom) tvNightsTo = tvNightsFrom;
                window.tvNightsFrom = tvNightsFrom;
                window.tvNightsTo = tvNightsTo;
                updateTvNightsSummary();
                closeTvNightsPopup();
            });
            tvNightsPopup && tvNightsPopup.addEventListener('click', function(e) {
                if (e.target === tvNightsPopup) document.getElementById('tv-nights-apply').click();
            });
            updateTvNightsSummary();
            
            // Блок ТУРИСТЫ: tvAdultsCount / tvChildrenAges объявлены выше (общая область с performTvSearch)
            var tvAgeLabels = {0:'до 2 лет',2:'2 года',3:'3 года',4:'4 года',5:'5 лет',6:'6 лет',7:'7 лет',8:'8 лет',9:'9 лет',10:'10 лет',11:'11 лет',12:'12 лет',13:'13 лет',14:'14 лет',15:'15 лет'};
            var tvAdultsValueEl = document.getElementById('tv-adults-value');
            var tvTouristsSummaryText = document.getElementById('tv-tourists-summary-text');
            var tvTouristsBlock = document.getElementById('tv-tourists-block');
            var tvChildrenRows = document.getElementById('tv-children-rows');
            var tvAddChildBtn = document.getElementById('tv-add-child-btn');
            var tvChildAgePicker = document.getElementById('tv-child-age-picker');
            var tvChildAgeGrid = document.getElementById('tv-child-age-grid');
            var tvChildAgePickerIndex = -1;
            function updateTouristsSummary() {
                var t = tvAdultsCount === 1 ? '1 взрослый' : tvAdultsCount + ' взрослых';
                if (tvChildrenAges.length > 0) t += ', ' + (tvChildrenAges.length === 1 ? '1 ребёнок' : tvChildrenAges.length + ' детей');
                if (tvTouristsSummaryText) tvTouristsSummaryText.textContent = t;
                if (tvAdultsValueEl) tvAdultsValueEl.textContent = tvAdultsCount + ' взрослых';
            }
            function renderChildrenRows() {
                if (!tvChildrenRows) return;
                tvChildrenRows.innerHTML = tvChildrenAges.map(function(age, i) {
                    var label = tvAgeLabels[age] || ('возраст ' + age);
                    return '<div class="flex items-center gap-2">' +
                        '<button type="button" class="tv-child-remove w-9 h-9 rounded-full bg-sky-500 text-white flex items-center justify-center hover:bg-sky-600 flex-shrink-0 transition-colors" data-index="' + i + '" aria-label="Удалить">−</button>' +
                        '<div class="flex-1 min-w-0 px-4 py-2.5 rounded-full bg-sky-100 text-slate-800 text-sm text-center">' +
                        '<button type="button" class="tv-child-age-btn w-full text-left font-medium" data-index="' + i + '">Ребенок ' + label + '</button>' +
                        '</div></div>';
                }).join('');
                tvChildrenRows.querySelectorAll('.tv-child-remove').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var i = parseInt(this.dataset.index, 10);
                        tvChildrenAges.splice(i, 1);
                        renderChildrenRows();
                        updateTouristsSummary();
                        tvChildAgePicker.classList.add('hidden');
                    });
                });
                tvChildrenRows.querySelectorAll('.tv-child-age-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        tvChildAgePickerIndex = parseInt(this.dataset.index, 10);
                        tvChildAgePicker.classList.remove('hidden');
                        var grid = document.getElementById('tv-child-age-grid');
                        if (grid && grid.children.length === 0) {
                            [0,2,3,4,5,6,7,8,9,10,11,12,13,14,15].forEach(function(a) {
                                var b = document.createElement('button');
                                b.type = 'button';
                                b.className = 'px-3 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm bg-white hover:border-[#1A1A40] hover:bg-sky-50';
                                b.textContent = tvAgeLabels[a] || a;
                                b.dataset.age = a;
                                b.addEventListener('click', function() {
                                    if (tvChildAgePickerIndex >= 0 && tvChildAgePickerIndex < tvChildrenAges.length) {
                                        tvChildrenAges[tvChildAgePickerIndex] = parseInt(this.dataset.age, 10);
                                        renderChildrenRows();
                                        updateTouristsSummary();
                                    }
                                    tvChildAgePicker.classList.add('hidden');
                                });
                                grid.appendChild(b);
                            });
                        }
                    });
                });
                if (tvAddChildBtn) tvAddChildBtn.style.display = tvChildrenAges.length >= 3 ? 'none' : 'block';
            }
            document.getElementById('tv-tourists-trigger')?.addEventListener('click', function() {
                tvTouristsBlock.classList.toggle('hidden');
            });
            document.getElementById('tv-adults-minus')?.addEventListener('click', function() {
                if (tvAdultsCount > 1) { tvAdultsCount--; updateTouristsSummary(); }
            });
            document.getElementById('tv-adults-plus')?.addEventListener('click', function() {
                if (tvAdultsCount < 6) { tvAdultsCount++; updateTouristsSummary(); }
            });
            tvAddChildBtn?.addEventListener('click', function() {
                if (tvChildrenAges.length < 3) { tvChildrenAges.push(0); renderChildrenRows(); updateTouristsSummary(); }
            });
            document.getElementById('tv-tourists-apply')?.addEventListener('click', function() {
                if (document.getElementById('tv-remember-tourists')?.checked) {
                    try {
                        localStorage.setItem('tv_tourists', JSON.stringify({ adults: tvAdultsCount, childrenAges: tvChildrenAges }));
                    } catch (e) {}
                }
                tvTouristsBlock.classList.add('hidden');
            });
            try {
                var saved = localStorage.getItem('tv_tourists');
                if (saved) {
                    var d = JSON.parse(saved);
                    if (d && typeof d.adults === 'number' && d.adults >= 1 && d.adults <= 6) tvAdultsCount = d.adults;
                    if (d && Array.isArray(d.childrenAges)) tvChildrenAges = d.childrenAges.filter(function(a) { var n = parseInt(a,10); return n >= 0 && n <= 17; }).slice(0, 3);
                }
            } catch (e) {}
            renderChildrenRows();
            updateTouristsSummary();

            const cityToDeparture = {
                'москва':'Москва','moscow':'Москва','moskva':'Москва',
                'санкт-петербург':'Санкт-Петербург','saint petersburg':'Санкт-Петербург','st petersburg':'Санкт-Петербург','sankt-peterburg':'Санкт-Петербург','spb':'Санкт-Петербург',
                'казань':'Казань','kazan':'Казань',
                'екатеринбург':'Екатеринбург','yekaterinburg':'Екатеринбург','ekaterinburg':'Екатеринбург',
                'краснодар':'Краснодар','krasnodar':'Краснодар',
                'сочи':'Сочи','sochi':'Сочи',
                'минеральные воды':'Минеральные Воды','mineralnye vody':'Минеральные Воды',
                'ростов-на-дону':'Ростов-на-Дону','rostov':'Ростов-на-Дону',
                'самара':'Самара','samara':'Самара',
                'воронеж':'Воронеж','voronezh':'Воронеж',
                'нижний новгород':'Нижний Новгород','nizhny novgorod':'Нижний Новгород',
                'новосибирск':'Новосибирск','novosibirsk':'Новосибирск',
                'уфа':'Уфа','ufa':'Уфа',
                'волгоград':'Волгоград','volgograd':'Волгоград',
                'астрахань':'Астрахань','astrakhan':'Астрахань',
                'калининград':'Калининград','kaliningrad':'Калининград',
                'мурманск':'Мурманск','murmansk':'Мурманск',
                'симферополь':'Симферополь','simferopol':'Симферополь',
                'минераловодск':'Минеральные Воды','mineralovodsk':'Минеральные Воды'
            };
            async function detectCityByGeolocation() {
                if (!navigator.geolocation) return false;
                return new Promise((resolve) => {
                    navigator.geolocation.getCurrentPosition(
                        async (pos) => {
                            const lat = pos.coords.latitude, lon = pos.coords.longitude;
                            try {
                                const r = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json&accept-language=ru`, { headers: { 'Accept': 'application/json' } });
                                const text = await r.text();
                                let j = {};
                                if ((text || '').trim()) { try { j = JSON.parse(text); } catch (e) {} }
                                const addr = j.address || {};
                                const cityRaw = addr.city || addr.town || addr.village || addr.municipality || addr.county || addr.state || '';
                                const cityLower = String(cityRaw).toLowerCase().trim();
                                let matchName = cityToDeparture[cityLower];
                                if (!matchName && cityLower) {
                                    const found = departuresList.find(d => {
                                        if (isBlockedDepartureName(d.name)) return false;
                                        const dn = (d.name || '').toLowerCase();
                                        return dn.includes(cityLower) || cityLower.includes(dn);
                                    });
                                    if (found) matchName = found.name;
                                }
                                if (matchName && !isBlockedDepartureName(matchName)) {
                                    const dep = departuresList.find(d => (d.name || '').toLowerCase() === matchName.toLowerCase());
                                    if (dep) { depSel.value = dep.id; resolve(true); return; }
                                }
                            } catch (_) {}
                            resolve(false);
                            return;
                        },
                        () => resolve(false),
                        { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
                    );
                });
            }
            const geoBanner = document.getElementById('geo-banner');
            const geoAllow = document.getElementById('geo-allow');
            const geoDeny = document.getElementById('geo-deny');
            const geoDetectBtn = document.getElementById('geo-detect-btn');
            if (geoBanner && !sessionStorage.geoAnswered) {
                geoAllow?.addEventListener('click', async () => {
                    geoBanner.querySelector('p').textContent = 'Определяем город...';
                    geoAllow.disabled = true;
                    await detectCityByGeolocation();
                    var hpGeo = resolveHomePopularDepartureId();
                    if (hpGeo) loadHomePopularDestinations(hpGeo);
                    sessionStorage.geoAnswered = 'allowed';
                    geoBanner.classList.add('hidden');
                });
                geoDeny?.addEventListener('click', () => {
                    sessionStorage.geoAnswered = 'denied';
                    geoBanner.classList.add('hidden');
                });
            } else {
                geoBanner?.classList.add('hidden');
            }
            geoDetectBtn?.addEventListener('click', async () => {
                geoDetectBtn.textContent = 'Определяю...';
                geoDetectBtn.disabled = true;
                let set = false;
                try {
                    const r = await fetch((typeof TOUR_DETAIL_BASE !== 'undefined' && TOUR_DETAIL_BASE ? '' : '') + '/backend/api/geo.php', { method: 'GET' });
                    const text = await r.text();
                    let j = {};
                    if ((text || '').trim()) { try { j = JSON.parse(text); } catch (e) {} }
                    if (j.success && j.city && departuresList.length) {
                        const cityLower = String(j.city).toLowerCase().trim();
                        if (!isBlockedDepartureName(j.city)) {
                            const found = departuresList.find(d => {
                                if (isBlockedDepartureName(d.name)) return false;
                                const n = (d.name || '').toLowerCase();
                                return n === cityLower || n.includes(cityLower) || cityLower.includes(n);
                            });
                            if (found) { depSel.value = found.id; set = true; }
                        }
                    }
                } catch (_) {}
                if (!set) await detectCityByGeolocation();
                var hpBtn = resolveHomePopularDepartureId();
                if (hpBtn) loadHomePopularDestinations(hpBtn);
                geoDetectBtn.textContent = '📍 Определить';
                geoDetectBtn.disabled = false;
            });
            depSel?.addEventListener('change', function() {
                var v = parseInt(String(this.value || ''), 10);
                if (!v) return;
                var opt = this.options[this.selectedIndex];
                var depName = opt ? (opt.textContent || '').trim() : '';
                if (window.THDeparturePreference && typeof window.THDeparturePreference.save === 'function') {
                    window.THDeparturePreference.save(v, depName);
                }
                if (window.__tvRestoringFromBack) {
                    loadHomePopularDestinations(v);
                    return;
                }
                if (countrySel && countrySel.value) {
                    try { countrySel.dispatchEvent(new Event('change', { bubbles: true })); } catch (e) {}
                }
                var resultsWrap = document.getElementById('tv-results-wrapper');
                if (resultsWrap && !resultsWrap.classList.contains('hidden') && typeof performTvSearch === 'function') {
                    performTvSearch();
                }
                loadHomePopularDestinations(v);
            });
            window.addEventListener('th-departure-saved', function(ev) {
                var id = ev && ev.detail && ev.detail.id;
                if (!id) return;
                window.setTimeout(function() {
                    loadHomePopularDestinations(parseInt(String(id), 10));
                }, 0);
            });

            var pfRoot = document.getElementById('tv-results-sidebar');
            if (pfRoot && window.THTourPostFilters && typeof window.THTourPostFilters.mount === 'function') {
                tvPostFiltersCtrl = window.THTourPostFilters.mount({
                    root: pfRoot,
                    onChange: function () {
                        if (tvHotelsBeforeBudgetFilter && tvHotelsBeforeBudgetFilter.length) tvApplyClientFiltersAndRender();
                    }
                });
            }

            if (typeof tryRestoreTvMainSearchFromSnapshot === 'function') {
                var hasRestoreParam = false;
                try {
                    var trp = new URLSearchParams(window.location.search).get('tv_restore');
                    hasRestoreParam = trp === '1' || String(trp || '').toLowerCase() === 'true';
                } catch (eTrp) {}
                var restoredFromSnap = tryRestoreTvMainSearchFromSnapshot(function() {
                    updateTouristsSummary();
                    renderChildrenRows();
                    showTvResultsChrome();
                    if (tvPostFiltersCtrl && tvHotelsBeforeBudgetFilter.length) {
                        tvPostFiltersCtrl.updateFromHotels(tvHotelsBeforeBudgetFilter);
                    }
                });
                if (hasRestoreParam && window.TourSessionManager && typeof window.TourSessionManager.restore === 'function') {
                    setTimeout(function() {
                        window.TourSessionManager.restore();
                    }, restoredFromSnap ? 200 : 500);
                } else if (!hasRestoreParam) {
                    window.__tvRestoringFromBack = false;
                }
            }
        });

        let tvLastResults = [];
        /** Сырые отели с последнего поиска (до клиентского фильтра по бюджету) */
        let tvHotelsBeforeBudgetFilter = [];
        let tvPostFiltersCtrl = null;
        let tvSearchAltNightsBanner = false;
        const TV_PAGE_SIZE = 25;
        let tvDisplayedCount = 0;
        var TV_MAIN_SNAPSHOT_KEY = 'tv_main_search_snapshot_v1';
        var TV_MAIN_SNAPSHOT_TTL_MS = 45 * 60 * 1000;

        function saveTvMainSearchSnapshot() {
            try {
                if (!tvLastResults || tvLastResults.length === 0) return;
                var sortEl = document.getElementById('tv-sort');
                var snap = {
                    ts: Date.now(),
                    hotels: tvLastResults,
                    displayed: tvDisplayedCount,
                    sort: (sortEl && sortEl.value) ? sortEl.value : 'price-asc',
                    flights: window.__mainFlightsByTourId || {},
                    adults: tvAdultsCount,
                    childAges: tvChildrenAges ? tvChildrenAges.slice() : [],
                    scrollY: window.scrollY || window.pageYOffset || 0
                };
                sessionStorage.setItem(TV_MAIN_SNAPSHOT_KEY, JSON.stringify(snap));
            } catch (e) {}
        }
        /* Экспорт для overlay и session-manager */
        window.saveTvMainSearchSnapshot = saveTvMainSearchSnapshot;

        function scrollMainResultsIntoView(behavior) {
            var w = document.getElementById('tv-results-wrapper');
            if (!w || w.classList.contains('hidden')) return false;
            w.scrollIntoView({ behavior: behavior || 'smooth', block: 'start' });
            return true;
        }

        function tryRestoreTvMainSearchFromSnapshot(onAfter) {
            try {
                var sp = new URLSearchParams(window.location.search);
                var tr = sp.get('tv_restore');
                if (tr !== '1' && String(tr || '').toLowerCase() !== 'true') return false;
                var raw = sessionStorage.getItem(TV_MAIN_SNAPSHOT_KEY);
                if (!raw) return false;
                var p = JSON.parse(raw);
                if (!p || !Array.isArray(p.hotels) || p.hotels.length === 0) return false;
                if (p.ts && (Date.now() - p.ts > TV_MAIN_SNAPSHOT_TTL_MS)) return false;
                window.__tvRestoringFromBack = true;
                tvLastResults = p.hotels;
                tvHotelsBeforeBudgetFilter = Array.isArray(p.hotels) ? p.hotels.slice() : [];
                tvDisplayedCount = Math.min(Math.max(parseInt(p.displayed, 10) || TV_PAGE_SIZE, 1), tvLastResults.length);
                if (typeof p.adults === 'number' && p.adults >= 1 && p.adults <= 9) tvAdultsCount = p.adults;
                if (Array.isArray(p.childAges)) {
                    tvChildrenAges = p.childAges.filter(function(a) {
                        var n = parseInt(a, 10);
                        return !isNaN(n) && n >= 0 && n <= 17;
                    }).slice(0, 3);
                }
                window.__mainFlightsByTourId = (p.flights && typeof p.flights === 'object') ? p.flights : {};
                var sortEl2 = document.getElementById('tv-sort');
                if (sortEl2 && p.sort) sortEl2.value = p.sort;
                var wrapper = document.getElementById('tv-results-wrapper');
                if (wrapper) wrapper.classList.remove('hidden');
                applyTvSort();
                updateTvLoadMoreButton();
                var rC = document.getElementById('tv-result-count');
                if (rC) rC.textContent = String(tvLastResults.length);
                if (typeof onAfter === 'function') onAfter();
                sp.delete('tv_restore');
                var np = sp.toString();
                var clean = window.location.pathname + (np ? ('?' + np) : '') + window.location.hash;
                if (history.replaceState) history.replaceState(null, '', clean);
                if (!(window.TourSessionManager && window.TourSessionManager.hasPendingScrollRestore && window.TourSessionManager.hasPendingScrollRestore())) {
                    setTimeout(function() {
                        var scrolled = scrollMainResultsIntoView('smooth');
                        if (!scrolled && p.scrollY > 80) {
                            window.scrollTo({ top: p.scrollY, behavior: 'smooth' });
                        }
                        window.__tvRestoringFromBack = false;
                    }, 150);
                }
                return true;
            } catch (e) {
                return false;
            }
        }

        window.addEventListener('pageshow', function(ev) {
            if (!ev.persisted) return;
            if (window.TourSessionManager && window.TourSessionManager.hasPendingScrollRestore && window.TourSessionManager.hasPendingScrollRestore()) return;
            var w = document.getElementById('tv-results-wrapper');
            if (!w || w.classList.contains('hidden')) return;
            requestAnimationFrame(function() {
                scrollMainResultsIntoView('auto');
            });
        });

        const TV_LOADER_CIRCLE = 2 * Math.PI * 52;
        let tvLoaderRotateInterval = null;
        const TV_LOADER_WAIT_MESSAGES = [
            'Ищем для вас хорошее предложение...',
            'Проверяем доступность мест...',
            'Сравниваем цены отелей...',
            'Подбираем лучшие варианты...',
            'Уточняем детали перелётов...',
            'Почти готово, ещё секунду...'
        ];
        let tvLoaderSlowTimer = null;
        function tvLoaderShow() {
            const el = document.getElementById('tv-search-loader');
            const msg = document.getElementById('tv-loader-msg');
            const sub = document.getElementById('tv-loader-sub');
            if (el) { el.classList.add('active'); el.setAttribute('aria-hidden', 'false'); }
            if (msg) msg.classList.remove('done');
            if (sub) sub.textContent = 'Ищем лучшие предложения';
            if (tvLoaderRotateInterval) { clearInterval(tvLoaderRotateInterval); tvLoaderRotateInterval = null; }
            /* UX: при долгом поиске (>10с) предлагаем inline-лид */
            const slow = document.getElementById('tv-loader-slow');
            if (slow) slow.classList.remove('show');
            if (tvLoaderSlowTimer) clearTimeout(tvLoaderSlowTimer);
            tvLoaderSlowTimer = setTimeout(function () {
                const s = document.getElementById('tv-loader-slow');
                const loaderEl = document.getElementById('tv-search-loader');
                if (s && loaderEl && loaderEl.classList.contains('active')) s.classList.add('show');
            }, 10000);
            const instant = document.getElementById('tv-loader-instant');
            if (instant) instant.textContent = '';
            tvLoaderSetProgress(0, 'Подготовка поиска...', 'Обычно 30–60 сек · ищем лучшие предложения');
        }
        function tvLoaderSetProgress(percent, text, subText) {
            const p = Math.min(100, Math.max(0, Math.round(percent)));
            const fill = document.getElementById('tv-loader-fill');
            const percentEl = document.getElementById('tv-loader-percent');
            const msg = document.getElementById('tv-loader-msg');
            const sub = document.getElementById('tv-loader-sub');
            if (fill) fill.style.strokeDashoffset = String(TV_LOADER_CIRCLE * (1 - p / 100));
            if (percentEl) percentEl.textContent = p;
            if (msg) { msg.textContent = text || msg.textContent; if (p === 100) msg.classList.add('done'); }
            if (sub && subText !== undefined) sub.textContent = subText;
            if (p >= 90 && !tvLoaderRotateInterval) {
                let idx = 0;
                tvLoaderRotateInterval = setInterval(function() {
                    if (!document.getElementById('tv-search-loader') || !document.getElementById('tv-search-loader').classList.contains('active')) {
                        if (tvLoaderRotateInterval) { clearInterval(tvLoaderRotateInterval); tvLoaderRotateInterval = null; }
                        return;
                    }
                    const subEl = document.getElementById('tv-loader-sub');
                    if (subEl) subEl.textContent = TV_LOADER_WAIT_MESSAGES[idx % TV_LOADER_WAIT_MESSAGES.length];
                    idx++;
                }, 2200);
            }
        }
        function tvLoaderHide() {
            const el = document.getElementById('tv-search-loader');
            if (tvLoaderRotateInterval) { clearInterval(tvLoaderRotateInterval); tvLoaderRotateInterval = null; }
            if (tvLoaderSlowTimer) { clearTimeout(tvLoaderSlowTimer); tvLoaderSlowTimer = null; }
            const slow = document.getElementById('tv-loader-slow');
            if (slow) slow.classList.remove('show');
            if (el) { el.classList.remove('active'); el.setAttribute('aria-hidden', 'true'); }
        }
        function tvLoaderMessage(percent) {
            if (percent >= 100) return 'Готово!';
            if (percent >= 85) return 'Почти готово...';
            if (percent >= 50) return 'Подбираем лучшие варианты...';
            if (percent >= 25) return 'Проверяем отели и цены...';
            if (percent >= 1) return 'Ищем туры...';
            return 'Подготовка поиска...';
        }

        async function performTvSearch(manual) {
            if (window.__tvRestoringFromBack && !manual) return;
            if (manual) window.__tvRestoringFromBack = false;
            if (window.THLeadCapture) window.THLeadCapture.reachGoal('search_start');
            const dep = document.getElementById('tv-departure')?.value || (window.TH_DEPARTURE && window.TH_DEPARTURE.id) || '7';
            const country = document.getElementById('tv-country')?.value;
            if (!country) { alert('Выберите страну'); return; }
            
            let nFrom = typeof tvNightsFrom !== 'undefined' ? tvNightsFrom : 6;
            let nTo = typeof tvNightsTo !== 'undefined' ? tvNightsTo : 9;
            const origNFrom = nFrom;
            const origNTo = nTo;
            if (nTo < nFrom) nTo = nFrom;
            
            // Туристы: взрослые и возрасты детей (tvAdultsCount / tvChildrenAges — общие var выше)
            let adults = Math.max(1, Math.min(9, parseInt(tvAdultsCount, 10) || 2));
            let childs = '';
            if (tvChildrenAges && tvChildrenAges.length > 0) {
                childs = tvChildrenAges.slice(0, 3).map(function(a) {
                    var n = parseInt(a, 10);
                    if (isNaN(n)) n = 0;
                    return String(Math.max(0, Math.min(17, n)));
                }).join(',');
            }
            const datesVal = (document.getElementById('tv-dates')?.value || '').trim();
            let dateFrom, dateTo;
            const parseD = (s) => {
                const t = (s || '').trim();
                if (/^\d{4}-\d{2}-\d{2}$/.test(t)) return t;
                if (/^\d{2}-\d{2}-\d{4}$/.test(t)) {
                    const p = t.split('-');
                    return p[2] + '-' + p[1] + '-' + p[0];
                }
                const m = t.replace(/\./g, '-').match(/^(\d{1,2})-(\d{1,2})-(\d{4})$/);
                return m ? m[3] + '-' + m[2].padStart(2,'0') + '-' + m[1].padStart(2,'0') : t;
            };
            if (datesVal) {
                const parts = datesVal.split(/\s+(?:по|to)\s+|\s+[-–]\s+/i);
                if (parts.length >= 2) {
                    dateFrom = parseD(parts[0]);
                    dateTo = parseD(parts[1]);
                }
            }
            if ((!dateFrom || !dateTo) && typeof flatpickr !== 'undefined' && tvDatePicker && tvDatePicker.selectedDates && tvDatePicker.selectedDates.length >= 1) {
                const sel = tvDatePicker.selectedDates;
                dateFrom = flatpickr.formatDate(sel[0], 'Y-m-d');
                dateTo = sel.length >= 2 ? flatpickr.formatDate(sel[1], 'Y-m-d') : flatpickr.formatDate(new Date(sel[0].getTime() + 30*864e5), 'Y-m-d');
            }
            if (!dateFrom || !dateTo) {
                alert('Выберите даты вылета и возвращения в календаре.');
                return;
            }
            
            const params = new URLSearchParams({
                type: 'search',
                departureId: dep,
                countryId: country,
                dateFrom, dateTo,
                nightsFrom: nFrom || 7,
                nightsTo: nTo || 14,
                adults,
                currency: 'RUB'
            });
            if (childs) params.set('childs', childs);
            const meal = document.getElementById('tv-meal')?.value;
            if (meal) params.set('meal', meal);
            const category = document.getElementById('tv-category')?.value;
            if (category) params.set('hotelCategory', category);
            const region = document.getElementById('tv-region')?.value;
            if (region) params.set('regionIds', region);
            if (typeof tvSelectedServiceIds !== 'undefined' && tvSelectedServiceIds.length > 0) {
                params.set('hotelServices', tvSelectedServiceIds.join(','));
            }

            const wrapper = document.getElementById('tv-results-wrapper');
            const resultsDiv = document.getElementById('tv-search-results');
            showTvResultsChrome();
            if (resultsDiv) resultsDiv.innerHTML = '';
            tvDisplayedCount = 0;
            tvHotelsBeforeBudgetFilter = [];
            tvLastResults = [];
            tvShowAltNightsBanner(false);
            if (tvPostFiltersCtrl && typeof tvPostFiltersCtrl.reset === 'function') tvPostFiltersCtrl.reset();

            tvLoaderShow();
            if (wrapper) wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });

            let progressPercent = 0;
            const progressInterval = setInterval(function() {
                progressPercent += 2 + Math.random() * 4;
                if (progressPercent >= 95) progressPercent = 95;
                tvLoaderSetProgress(progressPercent, tvLoaderMessage(progressPercent));
            }, 90);

            let rCache = { success: false, data: [] };
            try {
                const countryNameForCache = document.getElementById('tv-country')?.selectedOptions?.[0]?.textContent?.trim() || '';
                const cacheParams = {
                    departureId: dep,
                    countryId: country,
                    countryName: countryNameForCache,
                    dateFrom, dateTo,
                    nightsFrom: nFrom || 7,
                    nightsTo: nTo || 14,
                    adults
                };
                if (childs) cacheParams.childs = childs;
                if (meal) cacheParams.meal = meal;
                if (category) cacheParams.hotelCategory = category;
                if (region) cacheParams.regionIds = region;
                if (typeof tvSelectedServiceIds !== 'undefined' && tvSelectedServiceIds.length > 0) {
                    cacheParams.hotelServices = tvSelectedServiceIds.join(',');
                }
                console.log('%c[API → сайт] Параметры поиска отправляются в API', 'color: #5DA9A4; font-weight: bold', cacheParams);
                console.log('[Фильтры] Поиск с параметрами:', { meal: meal || '—', regionIds: region || '—', hotelCategory: category || '—', hotelServices: (typeof tvSelectedServiceIds !== 'undefined' && tvSelectedServiceIds.length) ? tvSelectedServiceIds.join(',') : '—' });
                tvLoaderUpdateInstantPreview(countryNameForCache, dep);
                rCache = await tvFetch('search-cached', cacheParams, { cacheOnly: true });
                if (!rCache.success || !Array.isArray(rCache.data) || rCache.data.length === 0) {
                    rCache = await tvFetch('search-cached', cacheParams);
                }
                if ((!rCache.success || !Array.isArray(rCache.data) || rCache.data.length === 0) && origNFrom === 6 && origNTo === 9) {
                    const altParams = Object.assign({}, cacheParams, { nightsFrom: 5, nightsTo: 10 });
                    const rAlt = await tvFetch('search-cached', altParams, { cacheOnly: true });
                    if (!rAlt.success || !Array.isArray(rAlt.data) || rAlt.data.length === 0) {
                        var rAltLive = await tvFetch('search-cached', altParams);
                        if (rAltLive.success && Array.isArray(rAltLive.data) && rAltLive.data.length > 0) rAlt = rAltLive;
                    }
                    if (rAlt.success && Array.isArray(rAlt.data) && rAlt.data.length > 0) {
                        rCache = rAlt;
                        tvShowAltNightsBanner(true);
                    }
                }
            } catch (err) {
                console.error('[Главная · Поиск] Ошибка запроса поиска', err);
                rCache = { success: false, data: [], error: String(err && err.message ? err.message : err) };
            } finally {
                clearInterval(progressInterval);
                tvLoaderSetProgress(100, 'Готово!', 'Показываем результаты...');
                await new Promise(r => setTimeout(r, 550));
                tvLoaderHide();
            }

            if (rCache.success && Array.isArray(rCache.data) && rCache.data.length > 0) {
                tvHotelsBeforeBudgetFilter = rCache.data.slice();
                if (tvPostFiltersCtrl && typeof tvPostFiltersCtrl.updateFromHotels === 'function') {
                    tvPostFiltersCtrl.updateFromHotels(tvHotelsBeforeBudgetFilter);
                }
                tvApplyClientFiltersAndRender();
                showTvResultsChrome();
                document.getElementById('tv-results-wrapper').scrollIntoView({ behavior: 'smooth', block: 'start' });
                const fromCache = rCache.fromCache === true;
                console.log('%c[API → сайт] Результаты с API получены и отображены на сайте', 'color: #22c55e; font-weight: bold', { туров: tvLastResults.length, изКэша: fromCache });
                console.log('%c[Главная · Поиск] ' + (fromCache ? 'Показаны данные из кэша:' : 'Показаны результаты поиска Tourvisor (актуальные данные):'), 'color: #22c55e', tvLastResults.length, 'туров');
                return;
            }

            tvHotelsBeforeBudgetFilter = [];
            tvLastResults = [];
            document.getElementById('tv-result-count').textContent = '0';
            var isApiErr = rCache && rCache.error && String(rCache.error).length > 0;
            if (resultsDiv) resultsDiv.innerHTML = isApiErr ? tvSearchErrorHtml(rCache.error) : tvEmptyResultsHtml(origNFrom, origNTo, false);
            showTvResultsChrome();
            updateTvLoadMoreButton();
            if (isApiErr && window.THLeadCapture) window.THLeadCapture.reachGoal('search_error');
            console.warn('[Главная · Поиск] Нет результатов по выбранным параметрам.', rCache.error || '');
            return;
        }

        function tvLoaderUpdateInstantPreview(countryName, depId) {
            var el = document.getElementById('tv-loader-instant');
            if (!el || !countryName) return;
            el.textContent = 'Ищем туры: ' + countryName + '…';
            fetch('/backend/api/home_popular_destinations.php?departureId=' + encodeURIComponent(depId || '7'), { cache: 'no-store' })
                .then(function(r) { return r.json(); })
                .then(function(j) {
                    if (!el || !j || !j.success) return;
                    var hit = (j.items || []).find(function(it) {
                        return String(it.name || '').toLowerCase().indexOf(String(countryName).toLowerCase().slice(0, 4)) >= 0;
                    });
                    if (hit && hit.minPrice) {
                        el.textContent = 'Туры в ' + countryName + ' от ~' + new Intl.NumberFormat('ru-RU').format(hit.minPrice) + ' ₽ · уточняем цены…';
                    }
                }).catch(function() {});
        }
        function tvSearchErrorHtml(errMsg) {
            var sub = String(errMsg || 'Сервис временно недоступен').replace(/</g, '&lt;');
            return '<div class="tv-empty-state tv-empty-state--error"><div class="tv-empty-state__icon">⚠️</div><div class="tv-empty-state__title">Не удалось загрузить туры</div><div class="tv-empty-state__sub">' + sub + '</div><button type="button" class="tv-empty-state__btn" data-open-lead-modal="search-error"><i class="fas fa-headset"></i>Помочь с подбором</button></div>';
        }

                /** Как promoHotelListPrice / страница акций: totalPrice ближе к деталям тура, чем поле price из поиска. */
        function thPickFirstPositivePriceNum() {
            const args = Array.from(arguments);
            for (let i = 0; i < args.length; i++) {
                const v = args[i];
                if (v == null || v === '') continue;
                const n = Number(v);
                if (!Number.isNaN(n) && n > 0) return n;
            }
            return 0;
        }
        function tvHotelListPrice(h) {
            if (!h) return 0;
            const tour = (h.tours || [])[0] || {};
            if (h.tours && h.tours[0]) {
                let n = thPickFirstPositivePriceNum(
                    tour.totalPrice,
                    tour.price,
                    tour.priceRub,
                    tour.cost
                );
                if (n > 0) return Math.round(n);
                n = thPickFirstPositivePriceNum(h.price);
                return n > 0 ? Math.round(n) : 0;
            }
            return Math.round(thPickFirstPositivePriceNum(h.price, h.minPrice, h.minprice));
        }
        function showTvResultsChrome() {
            var wrapper = document.getElementById('tv-results-wrapper');
            var leadBar = document.getElementById('tv-results-lead-bar');
            if (wrapper) wrapper.classList.remove('hidden');
            if (leadBar) leadBar.classList.remove('hidden');
            var stickyLead = document.getElementById('th-results-sticky-lead');
            if (stickyLead) stickyLead.classList.add('is-visible');
            if (window.THMobile && typeof window.THMobile.sync === 'function') window.THMobile.sync();
            if (window.THLeadCapture) window.THLeadCapture.reachGoal('search_results_shown');
        }
        function tvApplyClientFiltersAndRender() {
            var list = Array.isArray(tvHotelsBeforeBudgetFilter) ? tvHotelsBeforeBudgetFilter.slice() : [];
            if (window.THTourPostFilters && tvPostFiltersCtrl) {
                list = window.THTourPostFilters.filterHotels(list, tvPostFiltersCtrl.state, { getPrice: tvHotelListPrice });
            }
            list = applyBudgetFilterToHotels(list);
            tvLastResults = list;
            tvDisplayedCount = Math.min(TV_PAGE_SIZE, Math.max(tvLastResults.length, 0));
            var rC = document.getElementById('tv-result-count');
            if (rC) rC.textContent = String(tvLastResults.length);
            applyTvSort();
            updateTvLoadMoreButton();
        }
        function tvShowAltNightsBanner(show) {
            var el = document.getElementById('tv-search-alt-banner');
            if (!el) return;
            if (show) {
                el.textContent = 'Подобраны ближайшие альтернативы (диапазон ночей расширен).';
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
                el.textContent = '';
            }
        }
        function tvEmptyResultsHtml(nFrom, nTo, triedAlt) {
            var sub = (nFrom === 6 && nTo === 9 && !triedAlt)
                ? 'Нет туров на 6–9 ночей. Попробуйте другой диапазон'
                : 'Попробуйте изменить даты, страну или город вылета — и мы найдём подходящий вариант.';
            return '<div class="tv-empty-state"><div class="tv-empty-state__icon">🔍</div><div class="tv-empty-state__title">Туров не найдено</div><div class="tv-empty-state__sub">' + sub.replace(/</g, '&lt;') + '</div><button type="button" class="tv-empty-state__btn" data-open-lead-modal="empty-state"><i class="fas fa-headset"></i>Получить подбор от менеджера</button></div>';
        }
        function applyBudgetFilterToHotels(arr) {
            if (!Array.isArray(arr)) return [];
            var minInp = document.querySelector('[data-pf-price-min]') || document.getElementById('tv-price-min');
            var maxInp = document.querySelector('[data-pf-price-max]') || document.getElementById('tv-price-max');
            var minRaw = minInp ? String(minInp.value || '').trim() : '';
            var maxRaw = maxInp ? String(maxInp.value || '').trim() : '';
            var minV = minRaw ? parseInt(minRaw, 10) : NaN;
            var maxV = maxRaw ? parseInt(maxRaw, 10) : NaN;
            var out = arr.slice();
            if (!isNaN(minV) && minV > 0) {
                out = out.filter(function(h) { return tvHotelListPrice(h) >= minV; });
            }
            if (!isNaN(maxV) && maxV > 0) {
                out = out.filter(function(h) { return tvHotelListPrice(h) <= maxV; });
            }
            return out;
        }
        function applyTvSort() {
            const sortVal = document.getElementById('tv-sort')?.value || 'price-asc';
            let arr = [...tvLastResults];
            if (sortVal === 'price-asc') arr.sort((a, b) => tvHotelListPrice(a) - tvHotelListPrice(b));
            else if (sortVal === 'price-desc') arr.sort((a, b) => tvHotelListPrice(b) - tvHotelListPrice(a));
            else if (sortVal === 'rating') arr.sort((a, b) => (b.rating || 0) - (a.rating || 0));
            var slice = arr.slice(0, tvDisplayedCount);
            renderTvResults(slice);
            var tvResEl = document.getElementById('tv-search-results');
            var skipFlightFetch = window.__tvRestoringFromBack
                && window.__mainFlightsByTourId
                && Object.keys(window.__mainFlightsByTourId).length > 0;
            function afterFlightsPatch() {
                if (tvResEl && window.THTourCard && typeof window.THTourCard.patchFlightsInContainer === 'function') {
                    window.THTourCard.patchFlightsInContainer(tvResEl);
                }
                if (tvResEl && window.THTourCard && typeof window.THTourCard.ensureCarouselsInContainer === 'function') {
                    window.THTourCard.ensureCarouselsInContainer(tvResEl);
                } else if (tvResEl && window.THTourCard && typeof window.THTourCard.kickImagesInContainer === 'function') {
                    window.THTourCard.kickImagesInContainer(tvResEl);
                }
            }
            if (skipFlightFetch) {
                afterFlightsPatch();
            } else if (typeof loadMainFlightsForTours === 'function') {
                loadMainFlightsForTours(slice, afterFlightsPatch);
            } else {
                afterFlightsPatch();
            }
            if (!window.__tvRestoringFromBack) saveTvMainSearchSnapshot();
        }

        function updateTvLoadMoreButton() {
            const wrapper = document.getElementById('tv-load-more-wrapper');
            const btn = document.getElementById('tv-load-more-btn');
            const textEl = document.getElementById('tv-load-more-text');
            if (!wrapper || !btn) return;
            const hasResults = tvLastResults.length > 0;
            const canLoadMore = tvDisplayedCount < tvLastResults.length;
            if (hasResults) {
                wrapper.classList.remove('hidden');
                btn.disabled = !canLoadMore;
                if (textEl) textEl.textContent = canLoadMore ? 'Загрузить ещё туры' : 'Загрузить ещё туры';
            } else {
                wrapper.classList.add('hidden');
                if (textEl) textEl.textContent = 'Загрузить ещё туры';
            }
        }

        function loadMoreTvResults() {
            if (tvDisplayedCount >= tvLastResults.length) return;
            tvDisplayedCount = Math.min(tvDisplayedCount + TV_PAGE_SIZE, tvLastResults.length);
            applyTvSort();
            updateTvLoadMoreButton();
        }

        window.__mainFlightsByTourId = window.__mainFlightsByTourId || {};
        function getMainTourId(h) {
            const tour = (h.tours && h.tours[0]) ? h.tours[0] : {};
            const tourId = tour.id ?? tour.tourId ?? tour.tourid ?? '';
            return (tourId != null && tourId !== '') ? String(tourId) : '';
        }
        function loadMainFlightsForTours(hotels, callback) {
            const base = typeof TV_API_BASE !== 'undefined' ? TV_API_BASE : '';
            const depCityMainFl = (window.TH_DEPARTURE && window.TH_DEPARTURE.name) || 'Самара';
            if (typeof thLoadTourFlightsForHotels === 'function' && base) {
                thLoadTourFlightsForHotels(hotels, {
                    apiBase: base,
                    departureCity: depCityMainFl,
                    maxTours: Math.min(hotels.length, 40),
                    getTourId: getMainTourId,
                    patchContainer: document.getElementById('tv-search-results'),
                    onDone: callback
                });
                return;
            }
            if (callback) callback();
        }

        function tvTourStartYmd(tour) {
            if (!tour) return '';
            const raw = String(tour.date || tour.startDate || tour.departureDate || '').trim();
            const m = raw.match(/^(\d{4}-\d{2}-\d{2})/);
            return m ? m[1] : '';
        }
        function tvTourReturnYmd(startYmd, nightsNum) {
            if (!startYmd || !nightsNum) return '';
            const p = startYmd.split('-');
            if (p.length !== 3) return '';
            const d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10), 12, 0, 0);
            if (isNaN(d.getTime())) return '';
            d.setDate(d.getDate() + nightsNum);
            const pad = (n) => String(n).padStart(2, '0');
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        }

        function thTourPhotoNormalizeKey(u) {
            if (!u || typeof u !== 'string') return '';
            const s = u.trim();
            if (!s) return '';
            try {
                const abs = /^https?:/i.test(s) ? s : (s.indexOf('//') === 0 ? 'https:' + s : 'https://' + s.replace(/^\/+/, ''));
                const x = new URL(abs);
                const host = x.hostname.toLowerCase().replace(/^www\./, '');
                let path = (x.pathname || '/').replace(/\/+/g, '/');
                if (path.length > 1) path = path.replace(/\/+$/, '');
                const search = x.search || '';
                return host + path + search;
            } catch (e) {
                return s.toLowerCase().replace(/\/+$/, '');
            }
        }
        function tvHotelPhotoUrls(h) {
            const fallback = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&q=80';
            let raw = [];
            if (window.THTourCard && typeof window.THTourCard.collectHotelPhotoRawUrls === 'function') {
                raw = window.THTourCard.collectHotelPhotoRawUrls(h);
            } else if (h) {
                raw.push((h.picturelink || h.pictureLink || '').toString());
                const pics = h.pictures;
                if (pics && Array.isArray(pics)) {
                    pics.forEach((p) => {
                        if (typeof p === 'string') raw.push(p);
                        else if (p && typeof p === 'object') raw.push(String(p.src || p.url || p.link || p.picturelink || p.pictureLink || ''));
                    });
                }
                const hid = parseInt(String(h.id || ''), 10);
                if (!raw.filter(Boolean).length && hid > 0) raw.push('hotel_pics/main400/' + hid + '.jpg');
            }
            const urls = [];
            const seen = {};
            const add = (u) => {
                if (!u || typeof u !== 'string') return;
                const t = u.trim();
                if (!t) return;
                const k = thTourPhotoNormalizeKey(t);
                if (!k || seen[k]) return;
                seen[k] = true;
                urls.push(t);
            };
            if (!h) return [fallback];
            raw.forEach(add);
            if (urls.length === 0) urls.push(fallback);
            return urls.slice(0, (window.THTourCard && window.THTourCard.PHOTO_SLIDE_MAX) ? window.THTourCard.PHOTO_SLIDE_MAX : 6);
        }

        function tvCardPrimaryImage(h) {
            const raw = tvHotelPhotoUrls(h);
            for (let i = 0; i < raw.length; i++) {
                const u = getTourvisorImageUrl(raw[i]);
                if (u && u.indexOf('unsplash.com') === -1) return u;
            }
            return raw.length ? getTourvisorImageUrl(raw[0]) : getTourvisorImageUrl('');
        }

        function renderTvResults(hotels) {
            const container = document.getElementById('tv-search-results');
            if (!container) return;
                        if (hotels.length === 0) {
                var nf0 = typeof tvNightsFrom !== 'undefined' ? tvNightsFrom : 6;
                var nt0 = typeof tvNightsTo !== 'undefined' ? tvNightsTo : 9;
                container.innerHTML = tvEmptyResultsHtml(nf0, nt0, false);
                return;
            }
            const depEl = document.getElementById('tv-departure');
            const departureCity = (window.TH_DEPARTURE && window.TH_DEPARTURE.name) || 'Самара';
            const departureIdMain = (depEl && depEl.value) ? String(depEl.value).trim() : ((window.TH_DEPARTURE && window.TH_DEPARTURE.id) || '7');
            const tourDetailBase = (typeof TOUR_DETAIL_BASE !== 'undefined' ? TOUR_DETAIL_BASE : '') || '/frontend';
            if (window.THTourCard && typeof window.THTourCard.render === 'function') {
                const priceAdults = Math.max(1, Math.min(9, parseInt(tvAdultsCount, 10) || 2));
                container.innerHTML = hotels.map(h => {
                    const tour = (h.tours || [])[0] || {};
                    const region = h.region?.name || '';
                    const country = h.country?.name || '';
                    const meal = tour.meal?.russianName || tour.meal?.name || '';
                    const nightsNum = parseInt(String(tour.nights || ''), 10) || 0;
                    const startYmd = tvTourStartYmd(tour);
                    const retYmd = (startYmd && nightsNum) ? tvTourReturnYmd(startYmd, nightsNum) : '';
                    const price = tvHotelListPrice(h);
                    let link = h.hotelDescriptionLink || h.hoteldescriptionlink || h.link || '';
                    if (typeof TourLinkUtils !== 'undefined' && TourLinkUtils.sanitizeTourLink) {
                        link = TourLinkUtils.sanitizeTourLink(link) || '';
                    }
                    const tourId = getMainTourId(h);
                    const cardImg = tvCardPrimaryImage(h);
                    const params = {
                        tour_link: link, country, hotel_name: (h.name || ''),
                        price: String(price), nights: String(tour.nights || ''), meal,
                        room_category: (tour.roomType || h.roomCategory || 'Стандарт').toString().trim() || 'Стандарт',
                        region, departure_city: departureCity,
                        image: cardImg || '',
                        rating: String(h.rating || ''), category: String(h.category || ''),
                        adults: String(priceAdults), tour_id: tourId
                    };
                    if (startYmd) params.date_from = startYmd;
                    if (retYmd) params.date_to = retYmd;
                    if (departureIdMain) params.departure_id = departureIdMain;
                    if (h.id) params.hotel_id = String(h.id);
                    try {
                        var retU = new URL(window.location.href);
                        retU.searchParams.set('tv_restore', '1');
                        params.return_url = retU.pathname + (retU.search || '');
                    } catch (eRet) {}
                    const cardHref = country ? (tourDetailBase + '/window/tour-detail.php?' + new URLSearchParams(params).toString()) : (link || '#');
                    return window.THTourCard.render(h, {
                        tour, getImageUrl: getTourvisorImageUrl, imageProxy: TV_IMAGE_PROXY,
                        image: cardImg, detailUrl: cardHref,
                        adults: priceAdults, dateFrom: startYmd, dateTo: retYmd,
                        price, departureCity, departureId: departureIdMain, carousel: true,
                        flightMeta: (window.__mainFlightsByTourId && tourId) ? window.__mainFlightsByTourId[tourId] : null
                    });
                }).join('');
                document.getElementById('tv-result-count').textContent = tvLastResults.length;
                updateTvLoadMoreButton();
                if (window.THTourCard && typeof window.THTourCard.ensureCarouselsInContainer === 'function') {
                    window.THTourCard.ensureCarouselsInContainer(container);
                } else if (window.THTourCard && typeof window.THTourCard.kickImagesInContainer === 'function') {
                    window.THTourCard.kickImagesInContainer(container);
                }
                return;
            }
            container.innerHTML = hotels.map(h => {
                const photoUrls = tvHotelPhotoUrls(h);
                const slideDedup = {};
                const slideSrcs = [];
                photoUrls.forEach((u) => {
                    const s = getTourvisorImageUrl(u);
                    if (!s || !String(s).trim()) return;
                    if (slideDedup[s]) return;
                    slideDedup[s] = true;
                    slideSrcs.push(s);
                });
                const fallbackImg = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&q=80';
                const slideMax = (window.THTourCard && window.THTourCard.PHOTO_SLIDE_MAX) ? window.THTourCard.PHOTO_SLIDE_MAX : 6;
                const slidesForCard = (slideSrcs.length ? slideSrcs : [fallbackImg]).slice(0, slideMax);
                const img = slidesForCard[0];
                const region = h.region?.name || '';
                const country = h.country?.name || '';
                const tour = (h.tours || [])[0] || {};
                const meal = tour.meal?.russianName || tour.meal?.name || '';
                const nights = tour.nights || '';
                const nightsNum = parseInt(String(nights), 10) || 0;
                const startYmd = tvTourStartYmd(tour);
                const retYmd = (startYmd && nightsNum) ? tvTourReturnYmd(startYmd, nightsNum) : '';
                const price = tvHotelListPrice(h);
                const rating = h.rating || 0;
                let link = h.hotelDescriptionLink || h.hoteldescriptionlink || h.link || '';
                if (typeof TourLinkUtils !== 'undefined' && TourLinkUtils.sanitizeTourLink) {
                    link = TourLinkUtils.sanitizeTourLink(link) || '';
                }
                const hasCountry = country && country.length > 0;
                const desc = (h.description || h.hotelDescription || h.descr || '').toString().trim();
                const tourId = getMainTourId(h);
                const flightData = window.__mainFlightsByTourId[tourId];
                const airlineLabel = (flightData && flightData.companies && flightData.companies[0]) ? flightData.companies[0] : '—';
                var pickTourists = tvAdultsCount + ' взр.';
                if (tvChildrenAges && tvChildrenAges.length > 0) pickTourists += ', ' + tvChildrenAges.length + ' реб.';
                const roomCategory = (tour.roomType || h.roomCategory || 'Стандарт').toString().trim() || 'Стандарт';
                const params = {
                    tour_link: link,
                    country: country,
                    hotel_name: (h.name || ''),
                    price: formatPrice(price),
                    nights: String(nights),
                    meal: meal,
                    room_category: roomCategory,
                    region: region,
                    departure_city: departureCity,
                    image: img,
                    description: desc ? desc.substring(0, 4000) : '',
                    rating: String(h.rating || ''),
                    category: String(h.category || ''),
                };
                if (startYmd) params.date_from = startYmd;
                if (retYmd) params.date_to = retYmd;
                try {
                    var retU = new URL(window.location.href);
                    retU.searchParams.set('tv_restore', '1');
                    params.return_url = retU.pathname + (retU.search || '');
                } catch (e) {
                    try {
                        params.return_url = window.location.pathname + window.location.search + (window.location.search.indexOf('?') >= 0 ? '&' : '?') + 'tv_restore=1';
                    } catch (e2) {}
                }
                if (tourId) params.tour_id = tourId;
                if (h.id) params.hotel_id = String(h.id);
                params.adults = String(Math.max(1, Math.min(9, parseInt(tvAdultsCount, 10) || 2)));
                if (tvChildrenAges && tvChildrenAges.length > 0) {
                    params.childs = tvChildrenAges.slice(0, 3).map(function(a) {
                        var n = parseInt(a, 10);
                        if (isNaN(n)) n = 0;
                        return String(Math.max(0, Math.min(17, n)));
                    }).join(',');
                }
                if (departureIdMain) params.departure_id = departureIdMain;
                const tourDetailUrl = hasCountry ? (tourDetailBase + '/window/tour-detail.php?' + new URLSearchParams(params).toString()) : '#';
                let cardHref = tourDetailUrl !== '#' ? tourDetailUrl : (link || '#');
                if (window.THTourCard && typeof window.THTourCard.appendGalleryToDetailUrl === 'function') {
                    cardHref = window.THTourCard.appendGalleryToDetailUrl(cardHref, slidesForCard);
                }
                const priceAdults = Math.max(1, Math.min(9, parseInt(tvAdultsCount, 10) || 2));
                // Звёзды отеля
                const catNum = parseInt(String(h.category || ''), 10) || 0;
                const starsHtml = catNum > 0 ? '★'.repeat(Math.min(catNum, 5)) : '';
                // Форматирование дат
                const fmtDate = (ymd) => { if (!ymd) return ''; const [y,m,d] = ymd.split('-'); return `${d}.${m}.${String(y).slice(2)}`; };
                const adultsWord = priceAdults === 1 ? '1 взрослый' : `${priceAdults} взрослых`;
                const datesMeta = (startYmd && retYmd)
                    ? `${fmtDate(startYmd)} – ${fmtDate(retYmd)}, ${nightsNum} ${nightsNum === 1 ? 'ночь' : (nightsNum < 5 ? 'ночи' : 'ночей')}, ${adultsWord}`
                    : (nights ? `${nights} ${nightsNum < 5 ? 'ночи' : 'ночей'}, ${adultsWord}` : adultsWord);
                const mediaHtml = (window.THTourCard && typeof window.THTourCard.buildCarouselMediaHtml === 'function')
                    ? window.THTourCard.buildCarouselMediaHtml(slidesForCard, { fallbackImg, hotelName: h.name || '' })
                    : `<div class="th-tour-card__media th-tour-card__media--carousel"><div class="th-tour-card__strip-scroll" tabindex="-1">${slidesForCard.map((src, idx) => {
                        const esc = String(src).replace(/"/g, '&quot;');
                        return `<img src="${esc}" alt="" class="th-tour-card__strip-img" loading="eager" decoding="async" onerror="this.onerror=null;this.src='${fallbackImg}'">`;
                    }).join('')}</div></div>`;
                return `
                <article class="th-tour-card"${h.id ? ' data-th-hotel-id="' + String(h.id).replace(/"/g, '&quot;') + '"' : ''}>
                    <a href="${cardHref}" class="th-tour-card__link th-tour-card__link--main">
                        ${mediaHtml}
                        <div class="th-tour-card__body">
                            <p class="th-tour-card__geo">${(country + (region ? ', ' + region : '')).replace(/</g,'&lt;')}</p>
                            <div class="th-tour-card__name-row">
                                <h3 class="th-tour-card__name">${(h.name || '').replace(/</g,'&lt;')}</h3>
                                ${starsHtml ? `<span class="th-tour-card__stars">${starsHtml}</span>` : ''}
                            </div>
                            ${meal ? `<span class="th-tour-card__meal-badge">${meal.replace(/</g,'&lt;')}</span>` : ''}
                            <div class="th-tour-card__price-block">
                                <span class="th-tour-card__price-label">цена за ${adultsWord}</span>
                                <span class="th-tour-card__price">${formatPrice(price)}</span>
                                ${datesMeta ? `<span class="th-tour-card__dates">${datesMeta.replace(/</g,'&lt;')}</span>` : ''}
                            </div>
                            <span class="th-tour-card__btn">Выбрать этот тур</span>
                        </div>
                    </a>
                </article>`;
            }).join('');
            document.getElementById('tv-result-count').textContent = tvLastResults.length;
            updateTvLoadMoreButton();
            if (window.THTourCard && typeof window.THTourCard.ensureCarouselsInContainer === 'function') {
                window.THTourCard.ensureCarouselsInContainer(container);
            } else if (window.THTourCard && typeof window.THTourCard.kickImagesInContainer === 'function') {
                window.THTourCard.kickImagesInContainer(container);
            }
        }
    </script>

    <script>
        (function () {
            var supportsIO = ('IntersectionObserver' in window);
            var nodes = document.querySelectorAll('.reveal-on-scroll');
            if (!nodes || !nodes.length) {
                // Даже если сейчас нет элементов, будем отслеживать появление
                nodes = [];
            }

            if (!supportsIO) {
                (nodes || []).forEach(function (el) { el.classList.add('in-view'); });
                return;
            }

            var obs = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in-view');
                        obs.unobserve(entry.target);
                    }
                });
            /* threshold/rootMargin: мягче для мобильных WebView (в т.ч. Яндекс.Браузер), иначе блоки остаются opacity:0 */
            }, { threshold: 0, rootMargin: '0px 0px 8% 0px' });

            function watch(root) {
                if (!root || root.nodeType !== 1) return;
                if (!root.querySelectorAll) return;
                /* querySelectorAll не включает сам root — динамические карточки (напр. «Популярные направления») иначе остаются opacity:0 */
                if (root.matches && root.classList && root.classList.contains('reveal-on-scroll')) {
                    obs.observe(root);
                }
                var list = root.querySelectorAll('.reveal-on-scroll');
                if (!list || !list.length) return;
                list.forEach(function (el) { obs.observe(el); });
            }

            // Старт: что уже есть в DOM
            watch(document);

            // Динамика: что добавляется позже JS-ом
            var mo = new MutationObserver(function (muts) {
                muts.forEach(function (m) {
                    if (m.addedNodes && m.addedNodes.length) {
                        m.addedNodes.forEach(function (n) { watch(n); });
                    }
                });
            });
            mo.observe(document.body, { childList: true, subtree: true });

            /* UX-страховка: контент не должен оставаться невидимым, даже если
               IntersectionObserver не сработал (старые WebView, ошибки). Через 2.5с
               показываем всё, что так и не получило .in-view. */
            function revealFallback() {
                document.querySelectorAll('.reveal-on-scroll:not(.in-view)').forEach(function (el) {
                    el.classList.add('in-view');
                });
            }
            setTimeout(revealFallback, 2500);
            window.addEventListener('load', function () { setTimeout(revealFallback, 1200); });
        })();
    </script>

    <script>
        (function () {
            var link = document.querySelector('a[href="#tour-search-section"]');
            var target = document.getElementById('tour-search-section');
            if (!link || !target) return;

            link.addEventListener('click', function (e) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                try {
                    if (history && history.pushState) history.pushState(null, '', '#tour-search-section');
                    else window.location.hash = '#tour-search-section';
                } catch (err) {}
            }, { passive: false });
        })();
    </script>
    <script>
        (function () {
            function bindNightsPopupFallback() {
                var trigger = document.getElementById('tv-nights-trigger');
                var summaryBtn = document.getElementById('tv-nights-summary');
                var popup = document.getElementById('tv-nights-popup');
                var popupCard = document.getElementById('tv-nights-popup-card');
                var grid = document.getElementById('tv-nights-grid');
                var summaryText = document.getElementById('tv-nights-summary-text');
                var applyBtn = document.getElementById('tv-nights-apply');
                if (!trigger || !popup || !grid || !applyBtn) return;
                if (popup.dataset.fallbackBound === '1') return;
                popup.dataset.fallbackBound = '1';
                var selectFrom = true;
                var nightsFrom = Number.isFinite(parseInt(window.tvNightsFrom, 10)) ? parseInt(window.tvNightsFrom, 10) : 7;
                var nightsTo = Number.isFinite(parseInt(window.tvNightsTo, 10)) ? parseInt(window.tvNightsTo, 10) : 14;

                function syncState() {
                    window.tvNightsFrom = nightsFrom;
                    window.tvNightsTo = nightsTo;
                    if (summaryText) {
                        summaryText.textContent = nightsFrom === nightsTo ? String(nightsFrom) : (nightsFrom + ' — ' + nightsTo);
                    }
                }

                function paintGrid() {
                    var cells = grid.querySelectorAll('.tv-nights-cell');
                    cells.forEach(function (cell) {
                        var n = parseInt(cell.getAttribute('data-n'), 10);
                        cell.style.pointerEvents = 'auto';
                        cell.classList.remove('text-white');
                        cell.style.backgroundColor = '';
                        cell.style.color = '';
                        cell.style.border = '';
                        if (n === nightsFrom) {
                            cell.style.backgroundColor = '#1A1A40';
                            cell.style.color = '#ffffff';
                            cell.style.border = '2px solid #10102E';
                            cell.classList.add('text-white');
                        } else if (n === nightsTo && nightsTo !== nightsFrom) {
                            cell.style.backgroundColor = '#1e6bb8';
                            cell.style.color = '#ffffff';
                            cell.style.border = '2px solid #185a9e';
                            cell.classList.add('text-white');
                        } else if (n > nightsFrom && n < nightsTo) {
                            cell.style.backgroundColor = 'rgba(93, 169, 164, 0.2)';
                            cell.style.color = '#312e81';
                            cell.style.border = '1px solid rgba(93, 169, 164, 0.32)';
                        }
                    });
                }

                function openPopup(e) {
                    if (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    popup.style.zIndex = '10050';
                    popup.style.pointerEvents = 'auto';
                    popup.classList.remove('hidden');
                    popup.style.display = 'flex';
                    popup.setAttribute('aria-hidden', 'false');
                    if (popupCard) popupCard.style.pointerEvents = 'auto';
                    selectFrom = true;
                    syncState();
                    paintGrid();
                }

                function closePopup() {
                    popup.classList.add('hidden');
                    popup.style.display = 'none';
                    popup.setAttribute('aria-hidden', 'true');
                }

                window.__thWizardOpenNightsPopup = openPopup;

                trigger.addEventListener('click', openPopup);
                if (summaryBtn) summaryBtn.addEventListener('click', openPopup);
                function handleCellSelect(btn, e) {
                    if (!btn) return;
                    if (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    var n = parseInt(btn.getAttribute('data-n'), 10);
                    if (!Number.isFinite(n) || n < 1 || n > 28) return;
                    if (selectFrom) {
                        nightsFrom = n;
                        nightsTo = n;
                        selectFrom = false;
                    } else {
                        if (n < nightsFrom) {
                            nightsTo = nightsFrom;
                            nightsFrom = n;
                        } else {
                            nightsTo = n;
                        }
                        selectFrom = true;
                    }
                    syncState();
                    paintGrid();
                }
                grid.addEventListener('click', function (e) {
                    var btn = e.target.closest('.tv-nights-cell');
                    handleCellSelect(btn, e);
                });
                grid.querySelectorAll('.tv-nights-cell').forEach(function (btn) {
                    btn.addEventListener('touchend', function (e) { handleCellSelect(btn, e); }, { passive: false });
                });
                applyBtn.addEventListener('click', closePopup);
                popup.addEventListener('click', function (e) {
                    if (e.target === popup) closePopup();
                });
                if (popupCard) {
                    popupCard.addEventListener('click', function (e) { e.stopPropagation(); });
                    popupCard.addEventListener('touchend', function (e) { e.stopPropagation(); }, { passive: true });
                }
                syncState();
                paintGrid();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bindNightsPopupFallback);
            } else {
                bindNightsPopupFallback();
            }
        })();
    </script>
    <!-- Модалка «Ночей» вне hero/карточки: иначе overflow/transform/backdrop в предках обрезают fixed в WebKit/Яндекс.Браузер -->
    <div id="tv-nights-popup" class="hidden fixed inset-0 flex items-center justify-center p-4 backdrop-blur-[2px]" aria-hidden="true">
        <div id="tv-nights-popup-card" class="tv-nights-popup-card bg-white rounded-2xl shadow-2xl w-full min-w-0 p-6 border border-gray-100 mx-auto" role="dialog" aria-label="Сколько ночей в отеле">
            <h3 class="heading-font text-lg font-bold text-slate-900 m-0 mb-1">Сколько ночей?</h3>
            <p class="text-sm text-slate-500 m-0 mb-3">Один тап — или свой диапазон ниже</p>
            <div id="tv-nights-quick" class="tv-nights-quick mb-4" aria-label="Быстрый выбор ночей"></div>
            <p id="tv-nights-hint" class="text-xs text-slate-500 mb-2">Свой диапазон: сначала «от», потом «до»</p>
            <div id="tv-nights-grid" class="tv-nights-grid gap-2 mb-4">
                <?php for ($n = 1; $n <= 28; $n++): ?>
                <button type="button" class="tv-nights-cell min-w-0 min-h-[2.75rem] px-0 py-1 rounded-[10px] text-sm font-semibold transition-colors flex flex-col items-center justify-center gap-0 leading-none bg-gray-100 text-slate-700 hover:bg-indigo-100 hover:text-indigo-800" data-n="<?php echo $n; ?>">
                    <span class="cell-num"><?php echo $n; ?></span>
                    <span class="cell-label text-[10px] opacity-0 leading-tight"><?php
                        if ($n === 1) echo 'ночь';
                        elseif ($n >= 2 && $n <= 4) echo 'ночи';
                        else echo 'ночей';
                    ?></span>
                </button>
                <?php endfor; ?>
            </div>
            <button type="button" id="tv-nights-apply" class="button button-primary w-full py-3.5 text-base font-bold min-h-[52px]">Готово</button>
        </div>
    </div>
    <?php include __DIR__ . '/../backend/components/performance_scripts.php'; ?>
    <script>
    (function () {
        var YM = <?php echo json_encode($th_ym_id, JSON_UNESCAPED_UNICODE); ?>;
        function ymg(g) {
            try {
                var id = YM && String(YM).replace(/\D/g, '');
                if (id && typeof ym === 'function') ym(parseInt(id, 10), 'reachGoal', g);
            } catch (e) {}
        }
        document.addEventListener('DOMContentLoaded', function () {
            var f = document.getElementById('main-quick-lead-form');
            var msg = document.getElementById('main-quick-lead-msg');
            if (!f) return;
            f.addEventListener('submit', function (ev) {
                ev.preventDefault();
                var fd = new FormData(f);
                var payload = {
                    name: String(fd.get('name') || '').trim(),
                    phone: String(fd.get('phone') || '').trim(),
                    agree: !!fd.get('agree'),
                    website: String(fd.get('website') || ''),
                    message: 'Главная: быстрая заявка «подберём сами».'
                };
                if (!payload.name || !payload.phone) {
                    if (msg) { msg.textContent = 'Укажите имя и телефон.'; msg.className = 'text-xs rounded-lg p-2 bg-red-100 text-red-900 block'; msg.classList.remove('hidden'); }
                    return;
                }
                if (!payload.agree) {
                    if (msg) { msg.textContent = 'Нужно согласие на обработку данных.'; msg.className = 'text-xs rounded-lg p-2 bg-red-100 text-red-900 block'; msg.classList.remove('hidden'); }
                    return;
                }
                fetch('/backend/api/uon-lead.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                    .then(function (r) { return r.json().catch(function () { return { success: false }; }); })
                    .then(function (data) {
                        if (data && data.success) {
                            ymg('main_quick_lead_ok');
                            if (window.THLeadCapture) window.THLeadCapture.reachGoal('lead_ok');
                            else ymg('lead_ok');
                            f.reset();
                            if (msg) {
                                msg.textContent = 'Заявка отправлена. Перезвоним за 15 минут.';
                                msg.className = 'text-xs rounded-lg p-2 bg-emerald-100 text-emerald-900 block';
                                msg.classList.remove('hidden');
                            }
                        } else {
                            ymg('main_quick_lead_err');
                            if (msg) {
                                msg.textContent = (data && data.error) ? data.error : 'Ошибка отправки.';
                                msg.className = 'text-xs rounded-lg p-2 bg-red-100 text-red-900 block';
                                msg.classList.remove('hidden');
                            }
                        }
                    })
                    .catch(function () {
                        ymg('main_quick_lead_err');
                        if (msg) {
                            msg.textContent = 'Нет связи. Попробуйте позже.';
                            msg.className = 'text-xs rounded-lg p-2 bg-red-100 text-red-900 block';
                            msg.classList.remove('hidden');
                        }
                    });
            });
        });
    })();
    </script>


    <!-- ===== QUICK BOOKING MODAL (упрощённый) ===== -->
    <div id="quick-booking-modal" class="th-qbm-modal" style="display:none;">
        <div class="th-qbm-modal__backdrop" id="qbm-backdrop"></div>
        <div class="th-qbm-modal__panel">
            <button type="button" id="qbm-close" class="th-qbm-modal__close" aria-label="Закрыть">✕</button>
        <h3 class="th-qbm-modal__title">Подберём тур для вас</h3>
        <p class="th-qbm-modal__sub">Перезвоним за 15 минут. Без спама.</p>
        <p class="th-qbm-modal__proof"><i class="fas fa-clock"></i> Ответ за 15 минут · <i class="fas fa-shield-alt"></i> Без спама</p>
        <form id="qbm-form" class="th-qbm-modal__form">
            <div class="th-qbm-modal__field"><input type="text" name="name" placeholder="Ваше имя" required class="th-qbm-modal__input"></div>
            <input type="tel" name="phone" placeholder="+7 (___) ___-__-__" required class="th-qbm-modal__input">
                <label class="th-qbm-modal__agree">
                    <input type="checkbox" name="agree" required>
                    <span>Согласен на <a href="/frontend/window/privacy.php" target="_blank" rel="noopener">обработку персональных данных</a></span>
                </label>
                <input type="text" name="website" class="th-qbm-modal__hp" tabindex="-1" autocomplete="off">
                <div id="qbm-msg" class="th-qbm-modal__msg"></div>
                <button type="submit" id="qbm-submit" class="th-qbm-modal__submit">Отправить заявку менеджеру</button>
            </form>
        </div>
    </div>

    <!-- ===== ФИЛЬТРЫ: центральное модальное окно (оверлей + диалог) ===== -->
    <div id="tv-filters-modal-overlay" class="tv-filters-modal-overlay" aria-hidden="true"></div>
    <div id="tv-filters-modal" class="tv-filters-modal" role="dialog" aria-modal="true" aria-labelledby="tv-filters-modal-h" hidden>
        <button type="button" id="tv-filters-modal-close" class="tv-filters-modal__close" aria-label="Закрыть">×</button>
        <h2 id="tv-filters-modal-h" class="tv-filters-modal__title">Фильтры</h2>
        <div class="tv-filters-modal__field">
            <label class="tv-filters-modal__lbl" for="tv-meal">Питание</label>
            <select id="tv-meal" class="tv-filters-modal__select tv-select tv-filter-field">
                <option value="">Любое</option>
            </select>
        </div>
        <div class="tv-filters-modal__field">
            <label class="tv-filters-modal__lbl" for="tv-region">Курорт</label>
            <select id="tv-region" class="tv-filters-modal__select tv-select tv-filter-field">
                <option value="">Любой</option>
            </select>
        </div>
        <div class="tv-filters-modal__field">
            <span class="tv-filters-modal__lbl th-filter-stars-label" id="tv-filters-stars-lbl">Звёздность</span>
            <div class="tv-filters-modal__stars" role="group" aria-labelledby="tv-filters-stars-lbl">
                <button type="button" class="tv-filters-modal__star-chip is-active" data-tv-stars="" aria-pressed="true">Все</button>
                <button type="button" class="tv-filters-modal__star-chip" data-tv-stars="3" aria-pressed="false">3★</button>
                <button type="button" class="tv-filters-modal__star-chip" data-tv-stars="4" aria-pressed="false">4★</button>
                <button type="button" class="tv-filters-modal__star-chip" data-tv-stars="5" aria-pressed="false">5★</button>
            </div>
            <label for="tv-category" class="sr-only">Звёздность (значение для поиска)</label>
            <select id="tv-category" class="tv-filters-modal__category-sr tv-select tv-filter-field" aria-hidden="true" tabindex="-1">
                <option value="">Любая</option>
                <option value="3">3★</option>
                <option value="4">4★</option>
                <option value="5">5★</option>
            </select>
        </div>
        <div class="tv-filters-modal__field">
            <span class="tv-filters-modal__lbl">Береговая линия</span>
            <div class="tv-filters-modal__stars tv-filters-modal__beach" role="group" aria-label="Береговая линия">
                <button type="button" class="tv-filters-modal__star-chip is-active" data-tv-beach="" aria-pressed="true">Любая</button>
                <button type="button" class="tv-filters-modal__star-chip" data-tv-beach="1" aria-pressed="false">1-я линия</button>
                <button type="button" class="tv-filters-modal__star-chip" data-tv-beach="2" aria-pressed="false">2-я линия</button>
            </div>
        </div>
        <div class="tv-filters-modal__field">
            <span class="tv-filters-modal__lbl">Бюджет за тур, ₽</span>
            <div class="tv-filters-modal__budget-row">
                <input type="number" id="tv-price-min" class="tv-filter-field" inputmode="numeric" min="0" step="1000" placeholder="От" autocomplete="off">
                <span aria-hidden="true">—</span>
                <input type="number" id="tv-price-max" class="tv-filter-field" inputmode="numeric" min="0" step="1000" placeholder="До" autocomplete="off">
            </div>
        </div>
        <button type="button" id="tv-filters-modal-apply" class="tv-filters-modal__apply">Применить</button>
        <button type="button" id="tv-filters-modal-reset" class="tv-filters-modal__reset">Сбросить</button>
    </div>

    <script>
    /* ===== REDESIGN v2: Compact Search + Popups + Sticky CTA ===== */
    (function() {
        'use strict';

        /* ══════════════════════════════════════════════
           ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
        ══════════════════════════════════════════════ */

        /** Форматирует Date → 'dd-mm-yyyy' (формат flatpickr) */
        function fmtDate(d) {
            return String(d.getDate()).padStart(2,'0') + '-' +
                   String(d.getMonth()+1).padStart(2,'0') + '-' +
                   d.getFullYear();
        }

        /** Красивый лейбл дат для кнопки-триггера */
        function fmtLabel(d) {
            return String(d.getDate()).padStart(2,'0') + '.' + String(d.getMonth()+1).padStart(2,'0');
        }
        /** Компактный диапазон для узкой кнопки (без пробелов вокруг тире) */
        function fmtCompactRange(d0, d1) {
            if (!d0 || !d1) return '';
            return fmtLabel(d0) + '–' + fmtLabel(d1);
        }

        /** Возвращает [from, to] для пресета */
        function presetDates(preset) {
            if (window.THDatePresets && typeof window.THDatePresets.getRange === 'function') {
                return window.THDatePresets.getRange(preset);
            }
            var t = new Date(), f = new Date(t), to = new Date(t);
            var key = String(preset || '');
            if (/^m(\d+)$/.test(key)) {
                var off = parseInt(RegExp.$1, 10);
                var y = t.getFullYear(), m = t.getMonth() + off;
                var y2 = y + Math.floor(m / 12), m2 = ((m % 12) + 12) % 12;
                if (off === 0) {
                    f = new Date(t.getFullYear(), t.getMonth(), t.getDate());
                    to = new Date(y2, m2 + 1, 0);
                } else {
                    f = new Date(y2, m2, 1);
                    to = new Date(y2, m2 + 1, 0);
                }
                return [f, to];
            }
            if (key === '3d') {
                f.setDate(t.getDate() + 1); to.setDate(t.getDate() + 3);
            } else if (key === '14d' || key === 'soon') {
                f.setTime(t.getTime()); to.setDate(t.getDate() + 14);
            } else if (key === 'week' || key === '7d') {
                f.setDate(t.getDate() + 1); to.setDate(t.getDate() + 7);
            } else if (key === 'month' || key === 'endmonth') {
                var yy = t.getFullYear(), mm = t.getMonth();
                f = new Date(yy, mm, t.getDate());
                to = new Date(yy, mm + 1, 0);
                if (f > to) { f = new Date(yy, mm + 1, 1); to = new Date(yy, mm + 2, 0); }
            } else {
                f.setDate(t.getDate() + 1); to.setDate(t.getDate() + 7);
            }
            return [f, to];
        }

        /* Попапы дат и туристов — в document.body: иначе overflow у hero обрезает fixed. */
        (function relocTvScPopupsToBody() {
            var dp = document.getElementById('tv-sc-date-popup');
            var tb = document.getElementById('tv-tourists-block');
            if (dp && dp.parentNode !== document.body) document.body.appendChild(dp);
            if (tb && tb.parentNode !== document.body) document.body.appendChild(tb);
        })();

        /* ══════════════════════════════════════════════
           ОВЕРЛЕЙ (общий для всех попапов на мобиле)
        ══════════════════════════════════════════════ */
        var overlay = document.getElementById('tv-sc-overlay');
        function showOverlay(onClose) {
            if (!overlay) return;
            overlay.style.display = 'block';
            overlay._onClose = onClose;
        }
        function hideOverlay() {
            if (!overlay) return;
            overlay.style.display = 'none';
            overlay._onClose = null;
        }
        if (overlay) {
            overlay.addEventListener('click', function() {
                if (typeof overlay._onClose === 'function') overlay._onClose();
                hideOverlay();
            });
        }

        /* ══════════════════════════════════════════════
           1. ПОПАП ДАТ
        ══════════════════════════════════════════════ */
        function initDatePopup() {
            var triggerBtn   = document.getElementById('tv-sc-dates-btn');
            var popup        = document.getElementById('tv-sc-date-popup');
            var display      = document.getElementById('tv-sc-dates-display');
            var preview      = document.getElementById('tv-sc-dates-preview');
            var stepEl       = document.getElementById('tv-sc-dates-step');
            var chipsRow     = document.getElementById('tv-sc-date-chips-row');
            var monthsRow    = document.getElementById('tv-sc-date-months-row');
            var calPanel     = document.getElementById('tv-sc-cal-panel');
            var calContainer = document.getElementById('tv-sc-cal-container');
            var customToggle = document.getElementById('tv-sc-dates-custom-toggle');
            var datesInp     = document.getElementById('tv-dates');
            if (!triggerBtn || !popup) return;

            var isOpen = false;
            var calOpen = false;
            var mountCalTries = 0;
            var closeTimer = null;

            function clearChipActive() {
                [chipsRow, monthsRow].forEach(function (row) {
                    if (!row) return;
                    row.querySelectorAll('.tv-sc-chip, .tv-date-preset-chip').forEach(function (c) {
                        c.classList.remove('active');
                        c.setAttribute('aria-pressed', 'false');
                    });
                });
            }

            function setStep(msg) {
                if (stepEl) stepEl.textContent = msg || '';
            }

            function updateDateLabels(d0, d1) {
                if (!d0 || !d1) return;
                var verbose = fmtLabel(d0) + ' — ' + fmtLabel(d1);
                if (display) display.textContent = fmtCompactRange(d0, d1);
                if (preview) preview.textContent = 'Выбрано: ' + verbose;
            }

            function setCustomToggleLabel(open) {
                if (!customToggle) return;
                customToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                customToggle.innerHTML = open
                    ? '<i class="fas fa-chevron-up" aria-hidden="true"></i> Скрыть календарь'
                    : '<i class="fas fa-calendar-day" aria-hidden="true"></i> Свои даты';
            }

            function hideCustomCalendar() {
                calOpen = false;
                if (calPanel) calPanel.hidden = true;
                setCustomToggleLabel(false);
            }

            function applyDates(dates, opts) {
                opts = opts || {};
                if (!dates || dates.length < 2) return;
                if (window.tvDatePicker && typeof window.tvDatePicker.setDate === 'function') {
                    window.tvDatePicker.setDate(dates, true);
                } else if (datesInp) {
                    datesInp.value = fmtDate(dates[0]) + ' — ' + fmtDate(dates[1]);
                    try { datesInp.dispatchEvent(new Event('change', { bubbles: true })); } catch (e) {}
                }
                if (window.tvDatePickerInline && typeof window.tvDatePickerInline.setDate === 'function') {
                    try { window.tvDatePickerInline.setDate(dates, false); } catch (e2) {}
                }
                updateDateLabels(dates[0], dates[1]);
                if (opts.autoClose) {
                    if (closeTimer) clearTimeout(closeTimer);
                    closeTimer = setTimeout(closePopup, opts.delay != null ? opts.delay : 260);
                }
            }

            function bindPresetChip(chip) {
                chip.addEventListener('click', function () {
                    var key = chip.getAttribute('data-date-preset') || chip.getAttribute('data-preset');
                    var dates = presetDates(key);
                    clearChipActive();
                    chip.classList.add('active');
                    chip.setAttribute('aria-pressed', 'true');
                    hideCustomCalendar();
                    applyDates(dates, { autoClose: true, delay: 220 });
                });
            }

            /* Месяцы — крупные кнопки */
            if (monthsRow) {
                monthsRow.innerHTML = '';
                var months = (window.THDatePresets && window.THDatePresets.monthPresets)
                    ? window.THDatePresets.monthPresets(4)
                    : [];
                if (!months.length) {
                    var names = ['Этот месяц', 'След. месяц', '+2 мес.', '+3 мес.'];
                    for (var mi = 0; mi < 4; mi++) {
                        months.push({ key: 'm' + mi, label: names[mi], short: names[mi] });
                    }
                }
                months.forEach(function (p) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'tv-sc-chip tv-sc-chip--lg';
                    btn.setAttribute('data-date-preset', p.key);
                    btn.setAttribute('aria-pressed', 'false');
                    btn.innerHTML = '<span class="tv-sc-chip__main">' + (p.label || p.short) + '</span>';
                    monthsRow.appendChild(btn);
                    bindPresetChip(btn);
                });
            }
            if (chipsRow) {
                chipsRow.querySelectorAll('.tv-sc-chip[data-date-preset]').forEach(bindPresetChip);
            }

            function syncInlineFromMain() {
                var inl = window.tvDatePickerInline;
                if (!inl || typeof inl.setDate !== 'function') return;
                var main = window.tvDatePicker;
                if (main && main.selectedDates && main.selectedDates.length >= 1) {
                    inl.setDate(main.selectedDates, false);
                    return;
                }
                if (datesInp && datesInp.value && typeof flatpickr !== 'undefined' && flatpickr.parseDate) {
                    var raw = String(datesInp.value).trim();
                    var parts = raw.split(/\s+(?:по|to)\s+|\s+[-–]\s+/i);
                    if (parts.length >= 2) {
                        var d0 = flatpickr.parseDate(parts[0].trim(), 'd-m-Y');
                        var d1 = flatpickr.parseDate(parts[1].trim(), 'd-m-Y');
                        if (d0 && d1) inl.setDate([d0, d1], false);
                    }
                }
            }

            function mountInlineCalendar() {
                if (!calContainer) return;
                if (typeof flatpickr !== 'function') {
                    mountCalTries++;
                    if (mountCalTries < 80) window.setTimeout(mountInlineCalendar, 80);
                    return;
                }
                mountCalTries = 0;
                if (!window.tvDatePickerInline) {
                    var inlineInput = document.createElement('input');
                    inlineInput.type = 'text';
                    inlineInput.setAttribute('aria-hidden', 'true');
                    inlineInput.style.cssText = 'position:absolute;opacity:0;pointer-events:none;width:0;height:0;overflow:hidden';
                    calContainer.appendChild(inlineInput);
                    window.tvDatePickerInline = flatpickr(inlineInput, {
                        inline: true,
                        mode: 'range',
                        dateFormat: 'd-m-Y',
                        locale: 'ru',
                        minDate: 'today',
                        showMonths: 1,
                        onChange: function (selectedDates) {
                            if (selectedDates.length === 1) {
                                if (preview) preview.textContent = 'С: ' + fmtLabel(selectedDates[0]);
                                setStep('Теперь нажмите день конца');
                                return;
                            }
                            if (selectedDates.length === 2) {
                                clearChipActive();
                                applyDates(selectedDates, { autoClose: true, delay: 280 });
                            }
                        }
                    });
                }
                syncInlineFromMain();
                setStep('Нажмите день начала, потом конец');
            }

            function showCustomCalendar() {
                calOpen = true;
                if (calPanel) calPanel.hidden = false;
                setCustomToggleLabel(true);
                mountCalTries = 0;
                requestAnimationFrame(function () { mountInlineCalendar(); });
            }

            if (customToggle) {
                customToggle.addEventListener('click', function () {
                    if (calOpen) hideCustomCalendar();
                    else showCustomCalendar();
                });
            }

            function openPopup() {
                if (isOpen) { closePopup(); return; }
                if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
                isOpen = true;
                hideCustomCalendar();
                popup.style.display = 'block';
                popup.classList.add('is-open');
                triggerBtn.setAttribute('aria-expanded', 'true');
                showOverlay(closePopup);
                if (window.tvDatePicker && window.tvDatePicker.selectedDates && window.tvDatePicker.selectedDates.length >= 2) {
                    updateDateLabels(window.tvDatePicker.selectedDates[0], window.tvDatePicker.selectedDates[1]);
                } else if (preview) {
                    preview.textContent = '';
                }
            }
            function closePopup() {
                isOpen = false;
                popup.style.display = 'none';
                popup.classList.remove('is-open');
                hideCustomCalendar();
                triggerBtn.setAttribute('aria-expanded', 'false');
                hideOverlay();
            }

            triggerBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                openPopup();
            });

            if (datesInp) {
                datesInp.addEventListener('change', function () {
                    var val = datesInp.value;
                    if (val && display) {
                        var parts = val.split(/\s+(?:по|to)\s+|\s+[-–—]\s+/i);
                        if (parts.length === 2) {
                            var f = parts[0].split('-'), t2 = parts[1].split('-');
                            if (f.length >= 2 && t2.length >= 2) {
                                display.textContent = f[0] + '.' + f[1] + '–' + t2[0] + '.' + t2[1];
                                if (preview) preview.textContent = 'Выбрано: ' + f[0] + '.' + f[1] + ' — ' + t2[0] + '.' + t2[1];
                            }
                        } else {
                            display.textContent = val;
                        }
                    }
                    clearChipActive();
                });
            }

            document.addEventListener('click', function (e) {
                if (!isOpen) return;
                var field = document.getElementById('tv-sc-dates-field');
                if (field && !field.contains(e.target) && !popup.contains(e.target)) {
                    closePopup();
                }
            });

            var closeBtn = popup.querySelector('[data-sc-close]');
            if (closeBtn) closeBtn.addEventListener('click', closePopup);
            var applyBtn = document.getElementById('tv-sc-dates-apply');
            if (applyBtn) applyBtn.addEventListener('click', closePopup);

            window.__thWizardOpenDatePopup = openPopup;
        }

        /* ══════════════════════════════════════════════
           2. ТУРИСТЫ-ПОПАП: показываем оверлей на мобиле,
              добавляем кнопку-закрывашку
        ══════════════════════════════════════════════ */
        function initTouristsPopup() {
            var trigger      = document.getElementById('tv-tourists-trigger');
            var touristsBlock= document.getElementById('tv-tourists-block');
            var closeNewBtn  = document.getElementById('tv-tourists-close-btn');
            var applyBtn     = document.getElementById('tv-tourists-apply');
            if (!touristsBlock) return;

            /* Существующий JS уже вешает handler на trigger.
               Наблюдаем изменение класса 'hidden' → показываем/скрываем оверлей */
            var mo = new MutationObserver(function(muts) {
                muts.forEach(function(m) {
                    if (m.attributeName === 'class') {
                        if (!touristsBlock.classList.contains('hidden')) {
                            showOverlay(closeTotourists);
                        } else {
                            hideOverlay();
                        }
                    }
                });
            });
            mo.observe(touristsBlock, { attributes: true });

            function closeTotourists() {
                touristsBlock.classList.add('hidden');
                hideOverlay();
            }

            if (closeNewBtn) closeNewBtn.addEventListener('click', closeTotourists);

            /* Закрытие по клику вне попапа */
            document.addEventListener('click', function(e) {
                if (touristsBlock.classList.contains('hidden')) return;
                if (!touristsBlock.contains(e.target) && !trigger.contains(e.target)) {
                    closeTotourists();
                }
            });

            window.__thWizardOpenTouristsPopup = function () {
                touristsBlock.classList.remove('hidden');
            };
        }

        /* ══════════════════════════════════════════════
           3. ФИЛЬТРЫ: центральное модальное окно
        ══════════════════════════════════════════════ */
        function initTvFiltersModal() {
            var overlay = document.getElementById('tv-filters-modal-overlay');
            var modal = document.getElementById('tv-filters-modal');
            var openBtn = document.getElementById('tv-filters-modal-open');
            var closeBtn = document.getElementById('tv-filters-modal-close');
            var applyBtn = document.getElementById('tv-filters-modal-apply');
            var resetBtn = document.getElementById('tv-filters-modal-reset');
            if (!overlay || !modal) return;

            function syncBeachChips(val) {
                modal.querySelectorAll('[data-tv-beach]').forEach(function (b) {
                    var sv = b.getAttribute('data-tv-beach');
                    if (sv === null) return;
                    var active = (sv === '' && !val) || (sv !== '' && sv === String(val));
                    b.classList.toggle('is-active', active);
                    b.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
            }
            function getBeachValue() {
                var active = modal.querySelector('[data-tv-beach].is-active');
                return active ? (active.getAttribute('data-tv-beach') || '') : '';
            }
            function setBeachValue(val) {
                window.__tvWizardBeachLine = val ? String(val) : '';
                syncBeachChips(window.__tvWizardBeachLine);
                if (window.tvPostFiltersCtrl && window.tvPostFiltersCtrl.state) {
                    window.tvPostFiltersCtrl.state.beachLine = window.__tvWizardBeachLine;
                }
            }

            function syncStarsFromSelect() {
                var sel = document.getElementById('tv-category');
                var v = sel ? String(sel.value || '') : '';
                modal.querySelectorAll('[data-tv-stars]').forEach(function(b) {
                    var sv = b.getAttribute('data-tv-stars');
                    if (sv === null) return;
                    var active = (sv === '' && v === '') || (sv !== '' && sv === v);
                    b.classList.toggle('is-active', active);
                    b.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
            }
            function setStarsValue(val) {
                var sel = document.getElementById('tv-category');
                if (!sel) return;
                sel.value = val;
                try { sel.dispatchEvent(new Event('change', { bubbles: true })); } catch (e) {}
                syncStarsFromSelect();
            }

            function openModal() {
                syncStarsFromSelect();
                syncBeachChips(window.__tvWizardBeachLine || '');
                var countrySel = document.getElementById('tv-country');
                var depSel = document.getElementById('tv-departure');
                var regionEl = document.getElementById('tv-region');
                if (countrySel && depSel && regionEl && countrySel.value && depSel.value && regionEl.options.length <= 1) {
                    try { countrySel.dispatchEvent(new Event('change', { bubbles: true })); } catch (e) {}
                }
                overlay.classList.add('tv-filters-modal--show');
                modal.removeAttribute('hidden');
                modal.classList.add('tv-filters-modal--show');
                overlay.setAttribute('aria-hidden', 'false');
                document.body.classList.add('th-modal-open');
            if (window.THMobile && window.THMobile.sync) window.THMobile.sync();
                if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
            }
            function closeModal() {
                overlay.classList.remove('tv-filters-modal--show');
                modal.classList.remove('tv-filters-modal--show');
                modal.setAttribute('hidden', '');
                overlay.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('th-modal-open');
            if (window.THMobile && window.THMobile.sync) window.THMobile.sync();
                if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
            }

            window.openTvFiltersModal = openModal;

            if (openBtn) {
                openBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openModal();
                });
            }
            if (closeBtn) closeBtn.addEventListener('click', function(e) { e.preventDefault(); closeModal(); });
            overlay.addEventListener('click', function() { closeModal(); });
            modal.addEventListener('click', function(e) { e.stopPropagation(); });

            modal.querySelectorAll('.tv-filters-modal__star-chip').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (btn.hasAttribute('data-tv-beach')) {
                        setBeachValue(btn.getAttribute('data-tv-beach') || '');
                        return;
                    }
                    var v = btn.getAttribute('data-tv-stars');
                    setStarsValue(v === null ? '' : v);
                });
            });

            if (applyBtn) {
                applyBtn.addEventListener('click', function() {
                    setBeachValue(getBeachValue());
                    closeModal();
                    var w = document.getElementById('tv-results-wrapper');
                    if (w && !w.classList.contains('hidden') && typeof performTvSearch === 'function') {
                        performTvSearch();
                    }
                });
            }
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    var meal = document.getElementById('tv-meal');
                    var region = document.getElementById('tv-region');
                    var pmin = document.getElementById('tv-price-min');
                    var pmax = document.getElementById('tv-price-max');
                    if (meal) { meal.value = ''; try { meal.dispatchEvent(new Event('change', { bubbles: true })); } catch (e) {} }
                    if (region) { region.value = ''; try { region.dispatchEvent(new Event('change', { bubbles: true })); } catch (e) {} }
                    if (pmin) pmin.value = '';
                    if (pmax) pmax.value = '';
                    setStarsValue('');
                    setBeachValue('');
                    if (typeof tvSelectedServiceIds !== 'undefined' && tvSelectedServiceIds.length) {
                        tvSelectedServiceIds.length = 0;
                    }
                    var w = document.getElementById('tv-results-wrapper');
                    if (w && !w.classList.contains('hidden') && typeof performTvSearch === 'function') {
                        performTvSearch();
                    }
                });
            }
        }

        /* ══════════════════════════════════════════════
           4. Nights popup: стиль обновления ячеек на orange
        ══════════════════════════════════════════════ */
        function patchNightsPopupStyle() {
            /* Перехватываем patinGrid из существующего JS — 
               меняем синий цвет на оранжевый через inline-стили наблюдателем */
            var grid = document.getElementById('tv-nights-grid');
            if (!grid) return;
            var mo = new MutationObserver(function() {
                grid.querySelectorAll('.tv-nights-cell').forEach(function(cell) {
                    var bg = cell.style.backgroundColor;
                    if (bg && bg.indexOf('79, 70, 229') !== -1) {
                        cell.style.backgroundColor = '#FF6B6B';
                        cell.style.borderColor = '#F65252';
                    } else if (bg && bg.indexOf('30, 107, 184') !== -1) {
                        cell.style.backgroundColor = '#CC5200';
                        cell.style.borderColor = '#B04800';
                    } else if (bg && bg.indexOf('99, 102, 241') !== -1) {
                        cell.style.backgroundColor = 'rgba(255,107,107,0.18)';
                        cell.style.color = '#9a3a00';
                        cell.style.borderColor = 'rgba(255,107,107,0.35)';
                    }
                });
            });
            mo.observe(grid, { attributes: true, subtree: true, attributeFilter: ['style'] });
        }

        /* ══════════════════════════════════════════════
           5. Закрытие всех попапов по Escape
        ══════════════════════════════════════════════ */
        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Escape') return;
            /* Дата-попап */
            var dp = document.getElementById('tv-sc-date-popup');
            if (dp && dp.style.display !== 'none') { dp.style.display = 'none'; hideOverlay(); }
            /* Туристы */
            var tb = document.getElementById('tv-tourists-block');
            if (tb && !tb.classList.contains('hidden')) { tb.classList.add('hidden'); hideOverlay(); }
            /* Фильтры: модальное окно */
            var fm = document.getElementById('tv-filters-modal');
            var fo = document.getElementById('tv-filters-modal-overlay');
            if (fm && fm.classList.contains('tv-filters-modal--show')) {
                fm.classList.remove('tv-filters-modal--show');
                fm.setAttribute('hidden', '');
                if (fo) { fo.classList.remove('tv-filters-modal--show'); fo.setAttribute('aria-hidden', 'true'); }
                document.body.classList.remove('th-modal-open');
            if (window.THMobile && window.THMobile.sync) window.THMobile.sync();
                var ob = document.getElementById('tv-filters-modal-open');
                if (ob) ob.setAttribute('aria-expanded', 'false');
            }
        });


        /* ===== ФИЛЬТРЫ: центральное модальное окно (оверлей + диалог) ===== */
        function openQuickModal(source) {
            if (window.THConversionBoost && window.THConversionBoost.applyIntent) {
                window.THConversionBoost.applyIntent(source || 'home_quick_modal');
            }
            var modal = document.getElementById('quick-booking-modal');
            if (!modal) return;
            modal.dataset.thLeadSource = source || 'home_quick_modal';
            modal.style.display = 'flex';
            document.body.classList.add('th-modal-open');
            if (window.THMobile && window.THMobile.sync) window.THMobile.sync();
            var msg = document.getElementById('qbm-msg');
            if (msg) msg.style.display = 'none';
            var phoneInp = modal.querySelector('[name="phone"]');
            if (phoneInp) try { phoneInp.focus(); } catch (e) {}
        }
        function closeQuickModal() {
            var modal = document.getElementById('quick-booking-modal');
            if (!modal) return;
            modal.style.display = 'none';
            document.body.classList.remove('th-modal-open');
            if (window.THMobile && window.THMobile.sync) window.THMobile.sync();
        }
        function initQuickModal() {
            var backdrop = document.getElementById('qbm-backdrop');
            var closeBtn = document.getElementById('qbm-close');
            var form     = document.getElementById('qbm-form');
            var submit   = document.getElementById('qbm-submit');
            var msgEl    = document.getElementById('qbm-msg');
            if (backdrop) backdrop.addEventListener('click', closeQuickModal);
            if (closeBtn) closeBtn.addEventListener('click', closeQuickModal);
            document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeQuickModal(); });
            if (form) {
                form.setAttribute('data-th-lead-source', 'home_quick_modal');
                if (window.THLeadCapture && window.THLeadCapture.formatPhoneInput) {
                    window.THLeadCapture.formatPhoneInput(form.querySelector('[name="phone"]'));
                }
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (submit.disabled) return;
                    var fd = new FormData(form);
                    function showMsg(t, ok) {
                        if (!msgEl) return;
                        msgEl.textContent = t; msgEl.style.display = 'block';
                        msgEl.style.background = ok ? '#D1FAE5' : '#FEE2E2';
                        msgEl.style.color = ok ? '#065F46' : '#991B1B';
                    }
                    submit.disabled = true; submit.textContent = 'Отправка…';
                    var send = (window.THLeadCapture && window.THLeadCapture.submit)
                        ? window.THLeadCapture.submit({
                            name: String(fd.get('name') || '').trim() || 'Клиент сайта',
                            phone: String(fd.get('phone') || '').trim(),
                            agree: !!fd.get('agree'),
                            website: String(fd.get('website') || ''),
                            source: (document.getElementById('quick-booking-modal') || {}).dataset?.thLeadSource || 'home_quick_modal',
                            phoneOnly: (document.getElementById('quick-booking-modal') || {}).dataset?.thPhoneOnly === '1',
                            message: 'Заявка из модального окна (жёсткая воронка)'
                        })
                        : Promise.resolve({ success: false, error: 'Модуль заявки не загружен' });
                    send.then(function (data) {
                        if (data && data.success) {
                            showMsg(data.message || 'Заявка принята! Перезвоним за 15 минут.', true);
                            form.reset();
                            document.dispatchEvent(new Event('leadSubmitted'));
                            setTimeout(closeQuickModal, 2500);
                        } else {
                            showMsg((data && data.error) || 'Ошибка отправки', false);
                        }
                    }).finally(function () {
                        submit.disabled = false;
                        submit.textContent = 'Отправить заявку менеджеру';
                    });
                });
            }
            document.addEventListener('click', function(e) {
                var t=e.target.closest('[data-open-lead-modal]');
                if(t){e.preventDefault();openQuickModal(t.dataset.openLeadModal||'trigger');}
            });
            window.openQuickLeadModal = openQuickModal;
        }

        /* ══════════════════════════════════════════════
           8. Пустые результаты
        ══════════════════════════════════════════════ */
        function initEmptyState() {
            document.addEventListener('click', function(e) {
                if (e.target.closest('.tv-empty-state__btn')) {
                    e.preventDefault();
                    openQuickModal('empty-state');
                }
            });
        }

        /* ══════════════════════════════════════════════
           9. Sidebar sync (результаты поиска)
        ══════════════════════════════════════════════ */
        function initSidebarSync() {
            ['tv-meal','tv-region','tv-category'].forEach(function(id) {
                var main    = document.getElementById(id);
                var sidebar = document.getElementById(id+'-sidebar');
                if (!main || !sidebar) return;
                if (main.options.length > 1) { sidebar.innerHTML = main.innerHTML; sidebar.value = main.value; }
                new MutationObserver(function() {
                    var v = main.value; sidebar.innerHTML = main.innerHTML; sidebar.value = v;
                }).observe(main, { childList: true, subtree: true });
                sidebar.addEventListener('change', function() { main.value = sidebar.value; });
                main.addEventListener('change', function() { sidebar.value = main.value; });
            });
            var sidebarBtn = document.getElementById('tv-sidebar-search-btn');
            var mainBtn    = document.getElementById('tv-search-btn');
            if (sidebarBtn && mainBtn) sidebarBtn.addEventListener('click', function() { mainBtn.click(); });
        }

        function initLoaderLeadForm() {
            var form = document.getElementById('tv-loader-lead-form');
            if (!form || form.__thBound) return;
            form.__thBound = true;
            var phone = form.querySelector('[name="phone"]');
            var msg = document.getElementById('tv-loader-lead-msg');
            if (window.THLeadCapture && phone) window.THLeadCapture.formatPhoneInput(phone);
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var btn = form.querySelector('[type="submit"]');
                var fd = new FormData(form);
                if (btn) { btn.disabled = true; }
                if (window.THLeadCapture) window.THLeadCapture.reachGoal('slow_search_lead_attempt');
                var send = (window.THLeadCapture && window.THLeadCapture.submit)
                    ? window.THLeadCapture.submit({
                        name: 'Клиент сайта',
                        phone: String(fd.get('phone') || '').trim(),
                        agree: !!fd.get('agree'),
                        website: String(fd.get('website') || ''),
                        source: 'slow_search_lead',
                        phoneOnly: true,
                        message: 'Лид во время долгого поиска на главной'
                    })
                    : Promise.resolve({ success: false, error: 'Форма недоступна' });
                send.then(function(res) {
                    if (msg) {
                        msg.classList.remove('hidden');
                        msg.textContent = res.success ? (res.message || 'Заявка принята! Поиск продолжается…') : (res.error || 'Ошибка');
                    }
                    if (res.success) form.reset();
                }).finally(function() {
                    if (btn) btn.disabled = false;
                });
            });
        }

        /* ══════════════════════════════════════════════
           INIT
        ══════════════════════════════════════════════ */
        function init() {
            initDatePopup();
            initTouristsPopup();
            initTvFiltersModal();
            patchNightsPopupStyle();
            initQuickModal();
            initEmptyState();
            initSidebarSync();
            initLoaderLeadForm();
        }

        /* --- 1. Date Presets (legacy — оставляем для совместимости) --- */
        function initDatePresets() {
            var presets = document.querySelectorAll('.tv-date-preset-chip[data-preset]');
            if (!presets.length) return;

            function getPresetDates(preset) {
                var today = new Date();
                var from = new Date(today);
                var to   = new Date(today);
                if (preset === '3d') {
                    from.setDate(today.getDate() + 1);
                    to.setDate(today.getDate() + 3);
                } else if (preset === '7d') {
                    from.setDate(today.getDate() + 1);
                    to.setDate(today.getDate() + 7);
                } else if (preset === '14d') {
                    from.setDate(today.getDate() + 1);
                    to.setDate(today.getDate() + 14);
                } else if (preset === 'endmay') {
                    var y = today.getFullYear();
                    // Если сейчас уже после мая — берём май следующего года
                    if (today.getMonth() >= 5) y++;
                    from = new Date(y, 4, 20); // 20 мая
                    to   = new Date(y, 4, 31); // 31 мая
                }
                return [from, to];
            }

            function fmt(d) {
                var dd = String(d.getDate()).padStart(2, '0');
                var mm = String(d.getMonth() + 1).padStart(2, '0');
                var yyyy = d.getFullYear();
                return dd + '-' + mm + '-' + yyyy;
            }

            presets.forEach(function(chip) {
                chip.addEventListener('click', function() {
                    var preset = chip.dataset.preset;
                    var dates  = getPresetDates(preset);
                    // Снимаем active со всех
                    presets.forEach(function(c) { c.classList.remove('active'); });
                    chip.classList.add('active');

                    // Обновляем flatpickr если уже инициализирован
                    if (window.tvDatePicker && typeof window.tvDatePicker.setDate === 'function') {
                        window.tvDatePicker.setDate(dates, true);
                    } else {
                        // Fallback: обновляем текстовое значение напрямую
                        var inp = document.getElementById('tv-dates');
                        if (inp) inp.value = fmt(dates[0]) + ' — ' + fmt(dates[1]);
                    }
                });
            });

            // Снимаем active при ручном изменении дат
            var datesInp = document.getElementById('tv-dates');
            if (datesInp) {
                datesInp.addEventListener('change', function() {
                    presets.forEach(function(c) { c.classList.remove('active'); });
                });
            }
        }

        /* --- 2. Sidebar filters sync (синхронизируем с основными селектами) --- */
        function initSidebarSync() {
            var pairs = [
                ['tv-meal',     'tv-meal-sidebar'],
                ['tv-region',   'tv-region-sidebar'],
                ['tv-category', 'tv-category-sidebar'],
            ];
            pairs.forEach(function(pair) {
                var main    = document.getElementById(pair[0]);
                var sidebar = document.getElementById(pair[1]);
                if (!main || !sidebar) return;

                // Копируем текущие options если уже есть
                if (main.options.length > 1) {
                    sidebar.innerHTML = main.innerHTML;
                    sidebar.value     = main.value;
                }

                // Отслеживаем наполнение основного select → копируем в sidebar
                var ob = new MutationObserver(function() {
                    var val = main.value;
                    sidebar.innerHTML = main.innerHTML;
                    sidebar.value     = val;
                });
                ob.observe(main, { childList: true, subtree: true });

                // Sync value: sidebar → main
                sidebar.addEventListener('change', function() {
                    main.value = sidebar.value;
                });
                // Sync value: main → sidebar
                main.addEventListener('change', function() {
                    sidebar.value = main.value;
                });
            });

            // Кнопка поиска в сайдбаре — кликает на основную
            var sidebarBtn = document.getElementById('tv-sidebar-search-btn');
            var mainBtn    = document.getElementById('tv-search-btn');
            if (sidebarBtn && mainBtn) {
                sidebarBtn.addEventListener('click', function() {
                    mainBtn.click();
                });
            }
        }

        /* --- 3. Sticky Mobile CTA: см. initStickyCta выше --- */

        /* --- 5. Quick Booking Modal --- */
        function openQuickModal(source) {
            if (window.THConversionBoost && window.THConversionBoost.applyIntent) {
                window.THConversionBoost.applyIntent(source || 'home_quick_modal');
            }
            var modal = document.getElementById('quick-booking-modal');
            if (!modal) return;
            modal.dataset.thLeadSource = source || 'home_quick_modal';
            modal.style.display = 'flex';
            document.body.classList.add('th-modal-open');
            if (window.THMobile && window.THMobile.sync) window.THMobile.sync();
            var msg = document.getElementById('qbm-msg');
            if (msg) msg.style.display = 'none';
            var phoneInp = modal.querySelector('[name="phone"]');
            if (phoneInp) try { phoneInp.focus(); } catch (e) {}
        }

        function closeQuickModal() {
            var modal = document.getElementById('quick-booking-modal');
            if (!modal) return;
            modal.style.display = 'none';
            document.body.classList.remove('th-modal-open');
            if (window.THMobile && window.THMobile.sync) window.THMobile.sync();
        }

        function initQuickModal() {
            var backdrop = document.getElementById('qbm-backdrop');
            var closeBtn = document.getElementById('qbm-close');
            var form     = document.getElementById('qbm-form');
            var submit   = document.getElementById('qbm-submit');
            var msgEl    = document.getElementById('qbm-msg');

            if (backdrop) backdrop.addEventListener('click', closeQuickModal);
            if (closeBtn) closeBtn.addEventListener('click', closeQuickModal);

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeQuickModal();
            });

            if (form) {
                // Маска телефона
                var phoneInp = form.querySelector('[name="phone"]');
                if (phoneInp) {
                    phoneInp.addEventListener('input', function() {
                        var v = this.value.replace(/\D/g, '');
                        if (v.length > 0 && v[0] === '8') v = '7' + v.slice(1);
                        if (v.length > 0 && v[0] !== '7') v = '7' + v;
                        if (v.length > 11) v = v.slice(0, 11);
                        var f2 = '';
                        if (v.length > 0)  f2 += '+7';
                        if (v.length > 1)  f2 += ' (' + v.slice(1, 4);
                        if (v.length > 4)  f2 += ') ' + v.slice(4, 7);
                        if (v.length > 7)  f2 += '-' + v.slice(7, 9);
                        if (v.length > 9)  f2 += '-' + v.slice(9, 11);
                        this.value = f2;
                    });
                }

                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (submit.disabled) return;
                    var fd      = new FormData(form);
                    var name    = String(fd.get('name')  || '').trim();
                    var phone   = String(fd.get('phone') || '').trim();
                    var agree   = !!fd.get('agree');
                    var website = String(fd.get('website') || '');

                    function showMsg(text, ok) {
                        if (!msgEl) return;
                        msgEl.textContent = text;
                        msgEl.style.display = 'block';
                        msgEl.style.background = ok ? '#D1FAE5' : '#FEE2E2';
                        msgEl.style.color      = ok ? '#065F46' : '#991B1B';
                    }

                    if (!name || !phone) { showMsg('Укажите имя и телефон', false); return; }
                    if (!agree)         { showMsg('Нужно согласие на обработку данных', false); return; }

                    submit.disabled = true;
                    submit.textContent = 'Отправка...';

                    fetch('/backend/api/uon-lead.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ name: name, phone: phone, agree: true, website: website, message: 'Заявка из модального окна (главная)' })
                    })
                    .then(function(r) { return r.json().catch(function() { return { success: false }; }); })
                    .then(function(data) {
                        if (data && data.success) {
                            showMsg('Заявка принята! Перезвоним за 15 минут.', true);
                            form.reset();
                            document.dispatchEvent(new Event('leadSubmitted'));
                            setTimeout(closeQuickModal, 2500);
                        } else {
                            showMsg((data && data.error) || 'Ошибка отправки, попробуйте ещё раз', false);
                        }
                    })
                    .catch(function() {
                        showMsg('Ошибка сети. Попробуйте позже.', false);
                    })
                    .finally(function() {
                        submit.disabled = false;
                        submit.textContent = 'Отправить заявку менеджеру';
                    });
                });
            }

            // Открыть модал через кнопки с data-open-lead-modal
            document.addEventListener('click', function(e) {
                var trigger = e.target.closest('[data-open-lead-modal]');
                if (trigger) {
                    e.preventDefault();
                    /* slow-search: не закрываем loader — поиск продолжается */
                    if (trigger.dataset.openLeadModal !== 'slow-search' || typeof tvLoaderHide !== 'function') {
                        /* no-op for slow-search inline form path */
                    }
                    openQuickModal(trigger.dataset.openLeadModal || 'trigger');
                }
            });

            // Экспорт для других модулей
            window.openQuickLeadModal = openQuickModal;
        }

        /* --- 6. Пустые результаты поиска --- */
        function initEmptyState() {
            // Кнопка «Подберём сами» в пустом состоянии открывает modal
            document.addEventListener('click', function(e) {
                if (e.target.closest('.tv-empty-state__btn')) {
                    e.preventDefault();
                    openQuickModal('empty-state');
                }
            });
        }

        /* --- Legacy date preset chips (оставляем) --- */
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initDatePresets();
                init();
            });
        } else {
            initDatePresets();
            init();
        }
    })();
    </script>
    <?php
    $_th_app_promo_path = __DIR__ . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'th-app-promo.js';
    $_th_app_promo_ver = is_file($_th_app_promo_path) ? (string) filemtime($_th_app_promo_path) : '1';
    ?>
    <script src="/frontend/js/th-app-promo.js?v=<?php echo htmlspecialchars($_th_app_promo_ver, ENT_QUOTES, 'UTF-8'); ?>" defer></script>

    <?php
    // Завершаем буферизацию и сохраняем в кэш
    PageCache::end();
    ?>
</body>
</html>