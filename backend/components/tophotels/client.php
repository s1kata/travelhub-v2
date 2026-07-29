<?php
declare(strict_types=1);

/**
 * TopHotels HTTP/XML client stub.
 *
 * When Adon/partner delivers credentials, fill:
 *   TOPHOTELS_RATINGS_URL / TOPHOTELS_HOTELS_XML_URL / TOPHOTELS_API_KEY
 * and implement parsers below (marked TODO:API).
 */

require_once __DIR__ . '/config.php';

if (!function_exists('th_tophotels_http_get')) {
    /**
     * @return array{ok: bool, status: int, body: string, error: string}
     */
    function th_tophotels_http_get(string $url, int $timeoutSec = 60): array
    {
        if ($url === '') {
            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'empty_url'];
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'curl_init_failed'];
        }

        $headers = ['Accept: application/xml, text/xml, application/json, */*'];
        $apiKey = th_tophotels_config()['api_key'];
        if ($apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
            $headers[] = 'X-Api-Key: ' . $apiKey;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => min(15, $timeoutSec),
            CURLOPT_TIMEOUT => $timeoutSec,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => 'TravelHub-TopHotels/1.0',
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return ['ok' => false, 'status' => $status, 'body' => '', 'error' => $err !== '' ? $err : 'curl_exec_failed'];
        }

        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'body' => (string) $body,
            'error' => $status >= 200 && $status < 300 ? '' : ('http_' . $status),
        ];
    }
}

if (!function_exists('th_tophotels_parse_ratings_payload')) {
    /**
     * Normalize partner feed into list of rating rows.
     * TODO:API — replace stub once real XML/JSON schema is known.
     *
     * @return list<array{
     *   tophotels_id: string,
     *   rating: ?float,
     *   scale: int,
     *   reviews_count: ?int,
     *   rating_food: ?float,
     *   rating_service: ?float,
     *   rating_placement: ?float,
     *   last_review_at: ?string,
     *   name: ?string,
     *   country: ?string
     * }>
     */
    function th_tophotels_parse_ratings_payload(string $body, string $contentHint = ''): array
    {
        $trim = ltrim($body);
        if ($trim === '') {
            return [];
        }

        // JSON fixture / future JSON API
        if ($trim[0] === '{' || $trim[0] === '[') {
            $decoded = json_decode($body, true);
            if (!is_array($decoded)) {
                return [];
            }
            $items = isset($decoded['hotels']) && is_array($decoded['hotels'])
                ? $decoded['hotels']
                : (array_is_list($decoded) ? $decoded : []);
            $out = [];
            $scale = th_tophotels_config()['scale'];
            foreach ($items as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $id = trim((string) ($row['tophotels_id'] ?? $row['id'] ?? $row['hotelId'] ?? ''));
                if ($id === '') {
                    continue;
                }
                $out[] = [
                    'tophotels_id' => $id,
                    'rating' => isset($row['rating']) ? (float) $row['rating'] : null,
                    'scale' => isset($row['scale']) ? (int) $row['scale'] : $scale,
                    'reviews_count' => isset($row['reviews_count']) ? (int) $row['reviews_count']
                        : (isset($row['reviewsCount']) ? (int) $row['reviewsCount'] : null),
                    'rating_food' => isset($row['rating_food']) ? (float) $row['rating_food']
                        : (isset($row['food']) ? (float) $row['food'] : null),
                    'rating_service' => isset($row['rating_service']) ? (float) $row['rating_service']
                        : (isset($row['service']) ? (float) $row['service'] : null),
                    'rating_placement' => isset($row['rating_placement']) ? (float) $row['rating_placement']
                        : (isset($row['placement']) ? (float) $row['placement'] : null),
                    'last_review_at' => isset($row['last_review_at']) ? (string) $row['last_review_at']
                        : (isset($row['lastReviewAt']) ? (string) $row['lastReviewAt'] : null),
                    'name' => isset($row['name']) ? (string) $row['name'] : null,
                    'country' => isset($row['country']) ? (string) $row['country'] : null,
                ];
            }

            return $out;
        }

        // TODO:API — XML from TopHotels partner feed
        if (stripos($trim, '<') !== false || stripos($contentHint, 'xml') !== false) {
            if (!function_exists('simplexml_load_string')) {
                error_log('[tophotels] simplexml missing; cannot parse XML feed yet');

                return [];
            }
            $prev = libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body);
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
            if ($xml === false) {
                return [];
            }
            // Placeholder: expect <hotel id="…" rating="…" …> under any root.
            $out = [];
            $scale = th_tophotels_config()['scale'];
            $nodes = $xml->xpath('//hotel') ?: [];
            foreach ($nodes as $node) {
                $attrs = $node->attributes();
                $id = trim((string) ($attrs['id'] ?? $node->id ?? ''));
                if ($id === '') {
                    continue;
                }
                $out[] = [
                    'tophotels_id' => $id,
                    'rating' => isset($attrs['rating']) ? (float) $attrs['rating']
                        : (isset($node->rating) ? (float) $node->rating : null),
                    'scale' => $scale,
                    'reviews_count' => isset($attrs['reviews']) ? (int) $attrs['reviews']
                        : (isset($node->reviews) ? (int) $node->reviews : null),
                    'rating_food' => isset($node->food) ? (float) $node->food : null,
                    'rating_service' => isset($node->service) ? (float) $node->service : null,
                    'rating_placement' => isset($node->placement) ? (float) $node->placement : null,
                    'last_review_at' => isset($node->last_review) ? (string) $node->last_review : null,
                    'name' => isset($node->name) ? (string) $node->name : null,
                    'country' => isset($node->country) ? (string) $node->country : null,
                ];
            }

            return $out;
        }

        return [];
    }
}

if (!function_exists('th_tophotels_fetch_ratings_feed')) {
    /**
     * @return list<array<string, mixed>>
     */
    function th_tophotels_fetch_ratings_feed(): array
    {
        $c = th_tophotels_config();
        if ($c['use_fixture']) {
            $path = $c['data_dir'] . DIRECTORY_SEPARATOR . 'ratings.sample.json';
            if (!is_file($path)) {
                throw new RuntimeException('TOPHOTELS_USE_FIXTURE=1 but ratings.sample.json missing');
            }
            $body = (string) file_get_contents($path);

            return th_tophotels_parse_ratings_payload($body, 'json');
        }

        if ($c['ratings_url'] === '') {
            throw new RuntimeException(
                'TopHotels ratings URL not set. Ask Adon for feed URL, then set TOPHOTELS_RATINGS_URL in .env'
            );
        }

        $res = th_tophotels_http_get($c['ratings_url']);
        if (!$res['ok']) {
            throw new RuntimeException('TopHotels ratings fetch failed: ' . ($res['error'] ?: 'unknown'));
        }

        return th_tophotels_parse_ratings_payload($res['body']);
    }
}

if (!function_exists('th_tophotels_widget_snippet')) {
    /**
     * Build partner widget HTML when templates arrive.
     * Template may contain {tophotels_id} / {hotel_id} placeholders.
     */
    function th_tophotels_widget_snippet(string $tophotelsId, string $type = 'reviews'): string
    {
        $tophotelsId = trim($tophotelsId);
        if ($tophotelsId === '') {
            return '';
        }
        $c = th_tophotels_config();
        $tmpl = match ($type) {
            'rating' => $c['widget_rating_tmpl'],
            'services' => $c['widget_services_tmpl'],
            default => $c['widget_reviews_tmpl'],
        };
        if ($tmpl === '') {
            return '';
        }

        return str_replace(
            ['{tophotels_id}', '{hotel_id}', '{{id}}'],
            [$tophotelsId, $tophotelsId, $tophotelsId],
            $tmpl
        );
    }
}
