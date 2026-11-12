<?php
/**
 * Старые ссылки на пост — редирект на ленту с якорем (раскрытие через posts-page.js).
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/components/posts_helper.php';

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
$post = th_posts_find_by_slug($slug);

if ($post === null) {
    header('Location: /frontend/window/posts.php', true, 302);
    exit;
}

header('Location: /frontend/window/posts.php#' . rawurlencode($slug), true, 302);
exit;
