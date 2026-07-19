<?php
/**
 * API для отдачи оптимизированного hero-изображения (WebP, уменьшенный размер)
 * Уменьшает LCP и экономит ~12 МБ трафика
 * Использование: /backend/api/hero-image.php?w=1920
 */
$heroDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'window' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'hero';
$heroPath = null;
foreach (['e978c0767c0fe7bc778596c86b2b54f3 1.png', 'e978c0767c0fe7bc778596c86b2b54f3%201.png', 'e978c0767c0fe7bc778596c86b2b54f31.png'] as $f) {
    $p = $heroDir . DIRECTORY_SEPARATOR . str_replace('%20', ' ', $f);
    if (file_exists($p)) { $heroPath = $p; break; }
    if (file_exists($heroDir . DIRECTORY_SEPARATOR . $f)) { $heroPath = $heroDir . DIRECTORY_SEPARATOR . $f; break; }
}
$maxWidth = min(1920, max(320, (int)($_GET['w'] ?? 1920)));
$cacheDir = dirname(__DIR__, 2) . '/data/cache';
$cacheFile = $cacheDir . '/hero-' . $maxWidth . '.webp';

if (!$heroPath || !file_exists($heroPath)) {
    $fallbackUrl = 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=' . $maxWidth . '&q=80&auto=format&fit=crop';
    header('Location: ' . $fallbackUrl, true, 302);
    header('Cache-Control: public, max-age=86400');
    exit;
}

if (file_exists($cacheFile) && filemtime($cacheFile) >= filemtime($heroPath)) {
    header('Content-Type: image/webp');
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Content-Length: ' . filesize($cacheFile));
    readfile($cacheFile);
    exit;
}

if (!function_exists('imagewebp') || !function_exists('imagecreatefrompng')) {
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400');
    readfile($heroPath);
    exit;
}

$img = @imagecreatefrompng($heroPath);
if (!$img) {
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}

$w = imagesx($img);
$h = imagesy($img);
if ($w > $maxWidth) {
    $ratio = $maxWidth / $w;
    $newW = $maxWidth;
    $newH = (int)($h * $ratio);
    $resized = imagecreatetruecolor($newW, $newH);
    if ($resized) {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transp = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefill($resized, 0, 0, $transp);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($img);
        $img = $resized;
    }
}

if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
if (is_dir($cacheDir) && is_writable($cacheDir)) {
    imagewebp($img, $cacheFile, 80);
}

header('Content-Type: image/webp');
header('Cache-Control: public, max-age=31536000, immutable');
imagewebp($img, null, 80);
imagedestroy($img);
exit;
