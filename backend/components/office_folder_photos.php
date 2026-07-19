<?php
/**
 * Фото офиса из папки на диске.
 * Ищет в frontend/window/img/offices (раскладка репозитория), затем в /img/offices у DOCUMENT_ROOT (легаси).
 */
declare(strict_types=1);

/**
 * @param string|null $folderSlug Явный slug подкаталога (латиница, цифры, дефисы). Если задан — имя офиса не используется для пути.
 * @return list<array{image_url: string, filename: string, title: string}>
 */
function get_office_photos_from_folder(string $city, string $officeNameOrSlug, ?string $folderSlug = null): array
{
    if ($folderSlug !== null && $folderSlug !== '') {
        $officeSlug = strtolower(preg_replace('/[^a-z0-9\-]/', '', $folderSlug));
    } else {
        $officeSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $officeNameOrSlug));
    }
    if ($officeSlug === '') {
        $officeSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $officeNameOrSlug));
    }

    $roots = [];
    $repo = realpath(__DIR__ . '/../..');
    if ($repo !== false) {
        $roots[] = str_replace('\\', '/', $repo);
    }
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $dr = str_replace('\\', '/', rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/'));
        if ($dr !== '' && !in_array($dr, $roots, true)) {
            $roots[] = $dr;
        }
    }

    $candidates = [];
    foreach ($roots as $root) {
        $candidates[] = [$root . '/frontend/window/img/offices/' . $city . '/' . $officeSlug, '/frontend/window/img/offices/' . $city . '/' . $officeSlug];
        $candidates[] = [$root . '/img/offices/' . $city . '/' . $officeSlug, '/img/offices/' . $city . '/' . $officeSlug];
    }

    foreach ($candidates as [$baseDir, $urlPrefix]) {
        if (!is_dir($baseDir)) {
            continue;
        }
        $files = @scandir($baseDir);
        if ($files === false) {
            continue;
        }
        $photos = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.gitkeep') {
                continue;
            }
            $filePath = $baseDir . '/' . $file;
            if (!is_file($filePath)) {
                continue;
            }
            $ext = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                continue;
            }
            $photos[] = [
                'image_url' => $urlPrefix . '/' . $file,
                'filename' => $file,
                'title' => pathinfo($file, PATHINFO_FILENAME),
            ];
        }
        usort($photos, static function (array $a, array $b): int {
            return strcmp($a['filename'], $b['filename']);
        });

        return $photos;
    }

    return [];
}
