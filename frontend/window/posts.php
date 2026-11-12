<?php
/**
 * Лента постов: раскрытие на месте, без отдельного перехода.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/components/posts_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$posts = th_posts_demo_list_enriched();
$current_page = 'posts';

$_th_posts_css = dirname(__DIR__) . '/css/pages/posts.css';
$_th_posts_js = dirname(__DIR__) . '/js/posts-page.js';
$_th_posts_css_v = is_file($_th_posts_css) ? (string) filemtime($_th_posts_css) : '1';
$_th_posts_js_v = is_file($_th_posts_js) ? (string) filemtime($_th_posts_js) : '1';

$page_title = 'Идеи для отдыха — Travel Hub';
$page_description = 'Короткие гиды по странам и сезонам: откройте заметку и сразу подберите тур с вылетом из Самары или Москвы.';
$page_type = 'website';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <?php include __DIR__ . '/../../backend/components/seo_head.php'; ?>
    <link rel="icon" type="image/svg+xml" href="/frontend/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/frontend/css/pages/posts.css?v=<?php echo htmlspecialchars($_th_posts_css_v, ENT_QUOTES, 'UTF-8'); ?>">
    <?php include __DIR__ . '/../../backend/components/design_system_head.php'; ?>
<?php th_ui_shell_stylesheets_fini(); ?>

</head>
<body class="th-posts antialiased text-slate-900<?php echo htmlspecialchars(th_ui_body_class_attr(), ENT_QUOTES, 'UTF-8'); ?>">
<?php include __DIR__ . '/../../backend/components/header.php'; ?>

<section class="th-posts-promo" aria-label="Заголовок раздела">
    <div class="th-posts-promo__inner">
        <p class="th-posts-promo__eyebrow"><i class="fas fa-compass" aria-hidden="true"></i> Travel Hub · заметки о турах</p>
        <h1 class="th-posts-promo__title">Куда полететь сейчас — без длинных статей</h1>
        <p class="th-posts-promo__lead">Откройте карточку, прочитайте за пару минут и сразу перейдите к турам по направлению. Время чтения и страна определяются автоматически из текста.</p>
        <div class="th-posts-promo__actions">
            <a class="th-posts-promo__btn th-posts-promo__btn--primary" href="/">Подобрать тур на главной</a>
            <a class="th-posts-promo__btn th-posts-promo__btn--ghost" href="/frontend/window/promotions.php">Горящие акции</a>
        </div>
    </div>
</section>

<main class="th-posts-feed" id="th-posts-feed">
    <?php if ($posts === []): ?>
        <p class="th-posts-empty">Скоро здесь появятся первые публикации.</p>
    <?php else: ?>
        <?php foreach ($posts as $i => $post): ?>
            <?php
            $slugRaw = (string) ($post['slug'] ?? '');
            $slug = htmlspecialchars($slugRaw, ENT_QUOTES, 'UTF-8');
            $title = htmlspecialchars((string) ($post['title'] ?? ''), ENT_QUOTES, 'UTF-8');
            $excerpt = htmlspecialchars((string) ($post['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8');
            $cover = htmlspecialchars((string) ($post['cover'] ?? ''), ENT_QUOTES, 'UTF-8');
            $tag = htmlspecialchars((string) ($post['tag'] ?? ''), ENT_QUOTES, 'UTF-8');
            $date = th_posts_format_date_ru((string) ($post['published_at'] ?? ''));
            $mins = (int) ($post['read_minutes'] ?? 1);
            $country = $post['country_detected'] ?? null;
            $body = $post['body'] ?? [];
            if (!is_array($body)) {
                $body = [];
            }
            $gallery = $post['gallery'] ?? [];
            if (!is_array($gallery) || $gallery === []) {
                $gallery = [(string) ($post['cover'] ?? '')];
            }
            $gallery = array_values(array_filter($gallery, static function ($u) {
                return is_string($u) && $u !== '';
            }));
            $searchHref = is_array($country) ? (string) ($country['search_href'] ?? '/') : '/';
            ?>
            <article class="th-post-card" id="post-<?php echo $slug; ?>" data-post-slug="<?php echo $slug; ?>">
                <div class="th-post-card__visual">
                    <img src="<?php echo $cover; ?>" alt="<?php echo $title; ?>" loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>" width="720" height="480">
                    <?php if ($tag !== ''): ?>
                        <span class="th-post-card__country"><i class="fas fa-location-dot" aria-hidden="true"></i> <?php echo $tag; ?></span>
                    <?php endif; ?>
                    <span class="th-post-card__read"><i class="far fa-clock" aria-hidden="true"></i> ~<?php echo $mins; ?> мин</span>
                </div>

                <div class="th-post-card__summary">
                    <time class="th-post-card__date" datetime="<?php echo htmlspecialchars((string) ($post['published_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?></time>
                    <h2 class="th-post-card__title"><?php echo $title; ?></h2>
                    <p class="th-post-card__excerpt"><?php echo $excerpt; ?></p>
                    <button type="button"
                            class="th-post-card__toggle"
                            aria-expanded="false"
                            aria-controls="post-panel-<?php echo $slug; ?>"
                            data-label-open="Читать полностью"
                            data-label-close="Свернуть">
                        <span class="th-post-card__toggle-text">Читать полностью</span>
                        <span class="th-post-card__chevron" aria-hidden="true"></span>
                    </button>
                </div>

                <div class="th-post-card__panel" id="post-panel-<?php echo $slug; ?>" hidden aria-hidden="true">
                    <?php if (count($gallery) > 1): ?>
                        <div class="th-post-card__gallery">
                            <?php foreach (array_slice($gallery, 0, 3) as $gi => $img): ?>
                                <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="" loading="lazy">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="th-post-card__prose">
                        <?php foreach ($body as $para): ?>
                            <p><?php echo htmlspecialchars((string) $para, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endforeach; ?>
                    </div>

                    <div class="th-post-card__sell">
                        <p class="th-post-card__sell-title">
                            <?php if ($tag !== ''): ?>
                                Готовы в <?php echo $tag; ?>?
                            <?php else: ?>
                                Подберём тур под вас
                            <?php endif; ?>
                        </p>
                        <p class="th-post-card__sell-text">Актуальные цены с вылетом из Самары — на главной за пару кликов. Или смотрите акции по направлению.</p>
                        <div class="th-post-card__sell-actions">
                            <a class="th-posts-promo__btn th-posts-promo__btn--primary" href="<?php echo htmlspecialchars($searchHref, ENT_QUOTES, 'UTF-8'); ?>">Туры<?php echo $tag !== '' ? ' · ' . $tag : ''; ?></a>
                            <a class="th-posts-promo__btn th-posts-promo__btn--ghost" href="<?php echo htmlspecialchars($searchHref, ENT_QUOTES, 'UTF-8'); ?>">Подбор на главной</a>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<section class="th-posts-bottom-cta" aria-label="Подбор тура">
    <div class="th-posts-bottom-cta__inner">
        <h2 class="th-posts-bottom-cta__title">Не нашли ответ в заметках?</h2>
        <p class="th-posts-bottom-cta__text">Позвоните или оставьте заявку — менеджер подберёт вариант с перелётом и отелем.</p>
        <a class="th-posts-promo__btn th-posts-promo__btn--primary" href="tel:+78462541656"><i class="fas fa-phone" aria-hidden="true"></i> +7 (846) 254-16-56</a>
        <a class="th-posts-promo__btn th-posts-promo__btn--ghost" href="/">Открыть поиск туров</a>
    </div>
</section>

<?php include __DIR__ . '/../../backend/components/footer.php'; ?>
<script src="/frontend/js/posts-page.js?v=<?php echo htmlspecialchars($_th_posts_js_v, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
</body>
</html>
