<?php
/**
 * Яндекс.Карты — URL виджетов как в website-main (v1) + актуальная точка Самары.
 */
declare(strict_types=1);

if (!function_exists('th_maps')) {
    /**
     * @return array{
     *   widget_default:string,
     *   widget_moscow:string,
     *   widget_samara_hq:string,
     *   api_js:string
     * }
     */
    function th_maps(): array
    {
        static $m = null;
        if ($m !== null) {
            return $m;
        }
        // Constructor с prod/v1 (офисы / страны / главная в website-main)
        $constructorDefault = 'https://yandex.ru/map-widget/v1/?um=constructor%3A68baa4f498b6c1a43d0b2de2ac4ac2740d37afb0db1f2fd56edb4b54883b81cd&source=constructor&pt=37.7975,55.7944';
        // Moscow offices constructor из v1 moscow.php
        $constructorMoscow = 'https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3Acbf3daa2a7dab9c7f5a0b763d2631153918ef62062423d81861b78bd67007229&width=100%25&height=400&lang=ru_RU&scroll=true';
        // HQ Самара, ТРЦ «Парк Хаус», Московское шоссе 81Б
        $samaraHq = 'https://yandex.ru/map-widget/v1/?ll=50.201%2C53.2335&z=16&pt=50.201,53.2335,pm2rdm&lang=ru_RU';

        $m = [
            'widget_default' => $constructorDefault,
            'widget_moscow' => $constructorMoscow,
            'widget_moscow_iframe' => 'https://yandex.ru/map-widget/v1/?um=constructor%3Acbf3daa2a7dab9c7f5a0b763d2631153918ef62062423d81861b78bd67007229&source=constructor',
            'widget_samara_hq' => $samaraHq,
            'api_js' => 'https://api-maps.yandex.ru/2.1/?lang=ru_RU',
        ];
        return $m;
    }
}

if (!function_exists('th_map_widget_url_for_geo')) {
    /** Виджет по текстовому адресу (fallback для детальной страницы офиса). */
    function th_map_widget_url_for_geo(string $geo): string
    {
        $geo = trim($geo);
        if ($geo === '') {
            return th_maps()['widget_samara_hq'];
        }
        return 'https://yandex.ru/map-widget/v1/?text=' . rawurlencode($geo) . '&z=16&lang=ru_RU';
    }
}

if (!function_exists('th_map_widget_url_for_office')) {
    /**
     * Iframe-виджет карты для записи offices_catalog: map_url → yandex_org_id → текстовый geo.
     *
     * @param array<string, mixed> $office
     */
    function th_map_widget_url_for_office(array $office): string
    {
        if (!empty($office['map_url']) && is_string($office['map_url'])) {
            $url = trim($office['map_url']);
            if ($url !== '') {
                return $url;
            }
        }
        $orgId = preg_replace('/[^0-9]/', '', (string) ($office['yandex_org_id'] ?? ''));
        $geo = trim((string) ($office['geo'] ?? $office['address'] ?? ''));
        if ($orgId !== '' && $geo !== '') {
            return 'https://yandex.ru/map-widget/v1/?oid=' . $orgId . '&text=' . rawurlencode($geo) . '&z=16&lang=ru_RU';
        }
        if ($orgId !== '') {
            return 'https://yandex.ru/map-widget/v1/?oid=' . $orgId . '&z=16&lang=ru_RU';
        }
        return th_map_widget_url_for_geo($geo);
    }
}

if (!function_exists('th_yandex_maps_page_url_for_office')) {
    /**
     * Ссылка «Открыть на Яндекс.Картах» — всегда по текстовому адресу (geo), без org URL.
     *
     * @param array<string, mixed> $office
     */
    function th_yandex_maps_page_url_for_office(array $office): string
    {
        $geo = trim((string) ($office['geo'] ?? $office['address'] ?? ''));
        return 'https://yandex.ru/maps/?text=' . rawurlencode($geo);
    }
}
