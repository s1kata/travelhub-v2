<?php
declare(strict_types=1);

/**
 * TopHotels partner API — configuration helpers.
 * Credentials come later; keep TOPHOTELS_ENABLED=0 until feed/widget codes arrive.
 */

if (!function_exists('th_tophotels_env')) {
    function th_tophotels_env(string $key, string $default = ''): string
    {
        $v = getenv($key);
        if ($v === false || $v === '') {
            $v = $_ENV[$key] ?? $default;
        }

        return is_string($v) ? trim($v) : $default;
    }
}

if (!function_exists('th_tophotels_enabled')) {
    function th_tophotels_enabled(): bool
    {
        return filter_var(th_tophotels_env('TOPHOTELS_ENABLED', '0'), FILTER_VALIDATE_BOOLEAN);
    }
}

if (!function_exists('th_tophotels_use_fixture')) {
    function th_tophotels_use_fixture(): bool
    {
        return filter_var(th_tophotels_env('TOPHOTELS_USE_FIXTURE', '0'), FILTER_VALIDATE_BOOLEAN);
    }
}

if (!function_exists('th_tophotels_data_dir')) {
    function th_tophotels_data_dir(): string
    {
        $explicit = th_tophotels_env('TOPHOTELS_DATA_DIR');
        if ($explicit !== '') {
            return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $explicit), DIRECTORY_SEPARATOR);
        }
        $root = defined('TH_PROJECT_ROOT')
            ? (string) TH_PROJECT_ROOT
            : dirname(__DIR__, 3);

        return $root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'tophotels';
    }
}

if (!function_exists('th_tophotels_config')) {
    /**
     * @return array{
     *   enabled: bool,
     *   use_fixture: bool,
     *   partner_id: string,
     *   api_key: string,
     *   ratings_url: string,
     *   hotels_xml_url: string,
     *   scale: int,
     *   enrich_on_proxy: bool,
     *   widget_reviews_tmpl: string,
     *   widget_rating_tmpl: string,
     *   widget_services_tmpl: string,
     *   data_dir: string
     * }
     */
    function th_tophotels_config(): array
    {
        $scale = (int) th_tophotels_env('TOPHOTELS_RATING_SCALE', '10');
        if ($scale !== 5 && $scale !== 10) {
            $scale = 10;
        }

        return [
            'enabled' => th_tophotels_enabled(),
            'use_fixture' => th_tophotels_use_fixture(),
            'partner_id' => th_tophotels_env('TOPHOTELS_PARTNER_ID'),
            'api_key' => th_tophotels_env('TOPHOTELS_API_KEY'),
            'ratings_url' => th_tophotels_env('TOPHOTELS_RATINGS_URL'),
            'hotels_xml_url' => th_tophotels_env('TOPHOTELS_HOTELS_XML_URL'),
            'scale' => $scale,
            'enrich_on_proxy' => filter_var(
                th_tophotels_env('TOPHOTELS_ENRICH_ON_PROXY', '1'),
                FILTER_VALIDATE_BOOLEAN
            ),
            'widget_reviews_tmpl' => th_tophotels_env('TOPHOTELS_WIDGET_REVIEWS_TMPL'),
            'widget_rating_tmpl' => th_tophotels_env('TOPHOTELS_WIDGET_RATING_TMPL'),
            'widget_services_tmpl' => th_tophotels_env('TOPHOTELS_WIDGET_SERVICES_TMPL'),
            'data_dir' => th_tophotels_data_dir(),
        ];
    }
}

if (!function_exists('th_tophotels_client_configured')) {
    function th_tophotels_client_configured(): bool
    {
        $c = th_tophotels_config();
        if ($c['use_fixture']) {
            return true;
        }

        return $c['ratings_url'] !== '' || $c['hotels_xml_url'] !== '' || $c['api_key'] !== '';
    }
}
