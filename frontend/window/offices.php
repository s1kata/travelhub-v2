<?php
require_once __DIR__ . '/../../backend/components/page_cache_early.php';
if (PageCache::get()) exit;
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/config/contacts.php';
require_once __DIR__ . '/../../backend/config/offices_catalog.php';
if (session_status() === PHP_SESSION_NONE) session_start();
PageCache::start();

$thc = th_contacts();
$offices = th_offices_catalog();
$samaraCount = count(th_offices_by_city('samara'));
$moscowCount = count(th_offices_by_city('moscow'));
$heroCover = $offices[0]['cover'] ?? '/frontend/window/img/hero/e978c0767c0fe7bc778596c86b2b54f3%201.png';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <title>Офисы Travel Hub — Самара и Москва</title>
    <meta name="description" content="Офисы Travel Hub в Самаре и Москве: адреса, фото, телефоны. Позвоните или оставьте заявку — подберём тур за 15 минут.">
    <link rel="icon" type="image/svg+xml" href="/frontend/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/frontend/css/th-offices.css?v=3">
    <?php include __DIR__ . '/../../backend/components/design_system_head.php'; ?>
</head>
<body class="th-off-page text-slate-900">
    <?php
    $current_page = 'offices';
    include __DIR__ . '/../../backend/components/header.php';
    ?>

    <header class="th-off-hero">
        <img class="th-off-hero__bg" src="<?php echo htmlspecialchars($heroCover, ENT_QUOTES, 'UTF-8'); ?>" alt="Офис Travel Hub" loading="eager">
        <div class="th-off-hero__shade" aria-hidden="true"></div>
        <div class="th-off-hero__inner">
            <p class="th-off-hero__brand">Travel Hub</p>
            <p class="th-off-hero__lead">Офисы в Самаре и Москве — заходите или оставьте заявку, менеджер перезвонит за 15 минут.</p>
            <div class="th-off-hero__cta">
                <a class="th-off-hero__cta-primary" href="#th-offices-cta-form">Оставить заявку</a>
                <a class="th-off-hero__cta-ghost" href="tel:<?php echo htmlspecialchars($thc['phone_tel'], ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fas fa-phone" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($thc['phone_display'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        </div>
    </header>

    <main class="th-off-wrap">
        <div class="th-off-filters" role="tablist" aria-label="Город">
            <button type="button" class="th-off-filter is-active" data-city="all" role="tab" aria-selected="true">
                Все · <?php echo count($offices); ?>
            </button>
            <button type="button" class="th-off-filter" data-city="samara" role="tab" aria-selected="false">
                Самара · <?php echo (int) $samaraCount; ?>
            </button>
            <button type="button" class="th-off-filter" data-city="moscow" role="tab" aria-selected="false">
                Москва · <?php echo (int) $moscowCount; ?>
            </button>
        </div>

        <div class="th-off-grid" id="th-offices-grid">
            <?php foreach ($offices as $i => $o):
                $maxHref = $thc['max_url'];
                $delay = min(0.08 * ($i % 6), 0.4);
            ?>
            <article class="th-off-card" data-city="<?php echo htmlspecialchars($o['city'], ENT_QUOTES, 'UTF-8'); ?>" style="animation-delay:<?php echo $delay; ?>s">
                <a class="th-off-card__media" href="<?php echo htmlspecialchars($o['page_url'], ENT_QUOTES, 'UTF-8'); ?>">
                    <img src="<?php echo htmlspecialchars($o['cover'], ENT_QUOTES, 'UTF-8'); ?>"
                         alt="<?php echo htmlspecialchars($o['name'], ENT_QUOTES, 'UTF-8'); ?>"
                         loading="<?php echo $i < 3 ? 'eager' : 'lazy'; ?>"
                         decoding="async">
                    <span class="th-off-card__badge"><?php echo htmlspecialchars($o['brand'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="th-off-card__city"><?php echo htmlspecialchars($o['city_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php if (!empty($o['gallery']) && count($o['gallery']) > 1): ?>
                    <div class="th-off-thumbs" aria-hidden="true">
                        <?php foreach (array_slice($o['gallery'], 0, 4) as $gi => $_): ?>
                            <span></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </a>
                <div class="th-off-card__body">
                    <h2 class="th-off-card__name">
                        <a href="<?php echo htmlspecialchars($o['page_url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($o['name'], ENT_QUOTES, 'UTF-8'); ?></a>
                    </h2>
                    <p class="th-off-card__meta">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i><?php echo htmlspecialchars($o['address'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                    <p class="th-off-card__meta">
                        <i class="fas fa-clock" aria-hidden="true"></i><?php echo htmlspecialchars($o['hours'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                    <p class="th-off-card__blurb"><?php echo htmlspecialchars($o['blurb'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="th-off-card__actions">
                        <a class="th-off-card__call" href="tel:<?php echo htmlspecialchars($o['phone_tel'], ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="fas fa-phone" aria-hidden="true"></i> Позвонить
                        </a>
                        <a class="th-off-card__wa th-off-card__max" href="<?php echo htmlspecialchars($maxHref, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                            MAX
                        </a>
                        <button type="button" class="th-off-card__lead"
                                data-th-office-lead-btn="1"
                                data-office-city="<?php echo htmlspecialchars($o['city_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-office-name="<?php echo htmlspecialchars($o['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            Заявка в этот офис
                        </button>
                        <a class="th-off-card__more" href="<?php echo htmlspecialchars($o['page_url'], ENT_QUOTES, 'UTF-8'); ?>">
                            Фото и детали <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <p class="th-off-empty hidden" id="th-offices-empty">В этом городе офисов пока нет в списке.</p>

        <section class="mt-10 rounded-2xl overflow-hidden border border-slate-200 bg-white shadow-sm">
            <div class="px-4 py-4 sm:px-6 border-b border-slate-100">
                <h2 class="heading-font text-xl sm:text-2xl font-bold text-slate-900 m-0">Главный офис на карте</h2>
                <p class="text-slate-600 mt-1 mb-0">Самара, Московское шоссе, 81Б — и <a class="text-[#5DA9A4] font-semibold underline" href="/frontend/window/offices/samara-offices.php">все офисы Самары</a> / <a class="text-[#5DA9A4] font-semibold underline" href="/frontend/window/offices/moscow-offices.php">Москвы</a></p>
            </div>
            <?php
            require_once __DIR__ . '/../../backend/config/maps.php';
            $yandex_map_open_url = th_maps()['widget_samara_hq'];
            include __DIR__ . '/../../backend/components/yandex_map_open_link.php';
            ?>
        </section>
    </main>

    <?php
    $th_cta_source = 'offices_hub';
    $th_cta_title = 'Не можете выбрать офис? Напишите — подскажем ближайший';
    $th_cta_sub = 'Или сразу подберём тур удалённо: 2–3 варианта под даты и бюджет.';
    $th_cta_id = 'th-offices-cta';
    include __DIR__ . '/../../backend/components/page_cta_band.php';
    ?>

    <?php include __DIR__ . '/../../backend/components/footer.php'; ?>
    <script src="/frontend/js/office-lead-modal.js?v=2" defer></script>
    <script>
    (function () {
      var filters = document.querySelectorAll('.th-off-filter');
      var cards = document.querySelectorAll('.th-off-card');
      var empty = document.getElementById('th-offices-empty');
      filters.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var city = btn.getAttribute('data-city') || 'all';
          filters.forEach(function (b) {
            b.classList.toggle('is-active', b === btn);
            b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
          });
          var visible = 0;
          cards.forEach(function (card) {
            var show = city === 'all' || card.getAttribute('data-city') === city;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
          });
          if (empty) empty.classList.toggle('hidden', visible > 0);
        });
      });
    })();
    </script>
<?php PageCache::end(); ?>
</body>
</html>
