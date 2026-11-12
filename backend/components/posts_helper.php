<?php
/**
 * Посты: демо-лента, метаданные (время чтения, страна).
 */
declare(strict_types=1);

if (!function_exists('th_posts_demo_list')) {
    /**
     * @return list<array<string,mixed>>
     */
    function th_posts_demo_list(): array
    {
        $path = dirname(__DIR__) . '/config/posts_demo.php';
        if (!is_file($path)) {
            return [];
        }
        $data = require $path;
        return is_array($data) ? $data : [];
    }
}

if (!function_exists('th_posts_find_by_slug')) {
    /**
     * @return array<string,mixed>|null
     */
    function th_posts_find_by_slug(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }
        foreach (th_posts_demo_list() as $post) {
            if (!empty($post['slug']) && (string) $post['slug'] === $slug) {
                return th_posts_enrich($post);
            }
        }
        return null;
    }
}

if (!function_exists('th_posts_format_date_ru')) {
    function th_posts_format_date_ru(string $isoDate): string
    {
        $ts = strtotime($isoDate);
        if ($ts === false) {
            return $isoDate;
        }
        static $months = [
            1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
            5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
            9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
        ];
        $m = (int) date('n', $ts);
        return (int) date('j', $ts) . ' ' . ($months[$m] ?? '') . ' ' . date('Y', $ts);
    }
}

if (!function_exists('th_posts_word_count_ru')) {
  /**
   * Приблизительный подсчёт слов для русского текста.
   */
  function th_posts_word_count_ru(string $text): int
  {
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
    if ($text === '') {
      return 0;
    }
    $parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    return is_array($parts) ? count($parts) : 0;
  }
}

if (!function_exists('th_posts_estimate_read_minutes')) {
  /**
   * Время чтения из текста поста (~190 слов/мин для русского).
   *
   * @param array<string,mixed> $post
   */
  function th_posts_estimate_read_minutes(array $post): int
  {
    $chunks = [
      (string) ($post['title'] ?? ''),
      (string) ($post['excerpt'] ?? ''),
    ];
    $body = $post['body'] ?? [];
    if (is_array($body)) {
      foreach ($body as $p) {
        $chunks[] = (string) $p;
      }
    }
    $words = th_posts_word_count_ru(implode(' ', $chunks));
    $mins = (int) max(1, (int) ceil($words / 190));
    return $mins;
  }
}

if (!function_exists('th_posts_country_catalog')) {
  /**
   * Страна/направление → ссылки для витрины.
   *
   * @return array<string, array{label:string,country_slug:string,promo_tv_id:int}>
   */
  function th_posts_country_catalog(): array
  {
    static $cat = null;
    if ($cat !== null) {
      return $cat;
    }
    $cat = [
      'Турция' => ['label' => 'Турция', 'country_slug' => 'turkey', 'promo_tv_id' => 4],
      'Египет' => ['label' => 'Египет', 'country_slug' => 'egypt', 'promo_tv_id' => 1],
      'Таиланд' => ['label' => 'Таиланд', 'country_slug' => 'thailand', 'promo_tv_id' => 2],
      'ОАЭ' => ['label' => 'ОАЭ', 'country_slug' => 'uae', 'promo_tv_id' => 9],
      'Вьетнам' => ['label' => 'Вьетнам', 'country_slug' => 'vietnam', 'promo_tv_id' => 16],
      'Шри-Ланка' => ['label' => 'Шри-Ланка', 'country_slug' => 'sri-lanka', 'promo_tv_id' => 12],
      'Мальдивы' => ['label' => 'Мальдивы', 'country_slug' => 'maldives', 'promo_tv_id' => 8],
      'Индия' => ['label' => 'Индия', 'country_slug' => 'india', 'promo_tv_id' => 3],
      'Тунис' => ['label' => 'Тунис', 'country_slug' => 'tunisia', 'promo_tv_id' => 5],
      'Индонезия' => ['label' => 'Индонезия', 'country_slug' => 'indonesia', 'promo_tv_id' => 7],
      'Куба' => ['label' => 'Куба', 'country_slug' => 'cuba', 'promo_tv_id' => 10],
      'Абхазия' => ['label' => 'Абхазия', 'country_slug' => 'abkhazia', 'promo_tv_id' => 46],
    ];
    $aliases = [
      'Пхукет' => 'Таиланд',
      'Паттайя' => 'Таиланд',
      'Хургада' => 'Египет',
      'Шарм' => 'Египет',
      'Дубай' => 'ОАЭ',
      'Фукуок' => 'Вьетнам',
      'Анталья' => 'Турция',
      'Бодрум' => 'Турция',
    ];
    foreach ($aliases as $alias => $canonical) {
      if (isset($cat[$canonical])) {
        $cat[$alias] = $cat[$canonical];
      }
    }
    return $cat;
  }
}

if (!function_exists('th_posts_detect_country')) {
  /**
   * Определяет направление по тегу, заголовку и тексту.
   *
   * @param array<string,mixed> $post
   * @return array{label:string,country_slug:string,tours_href:string,promo_href:string}|null
   */
  function th_posts_detect_country(array $post): ?array
  {
    $catalog = th_posts_country_catalog();
    $tag = trim((string) ($post['tag'] ?? ''));
    if ($tag !== '' && isset($catalog[$tag])) {
      return th_posts_country_links($catalog[$tag]);
    }
    $hay = mb_strtolower(
      (string) ($post['title'] ?? '') . ' '
      . (string) ($post['excerpt'] ?? '') . ' '
      . (is_array($post['body'] ?? null) ? implode(' ', $post['body']) : ''),
      'UTF-8'
    );
  // Сначала длинные ключи (Шри-Ланка и т.д.)
    $keys = array_keys($catalog);
    usort($keys, static function (string $a, string $b): int {
      return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
    });
    foreach ($keys as $key) {
      $needle = mb_strtolower($key, 'UTF-8');
      if ($needle !== '' && mb_strpos($hay, $needle, 0, 'UTF-8') !== false) {
        return th_posts_country_links($catalog[$key]);
      }
    }
    return null;
  }
}

if (!function_exists('th_posts_country_links')) {
  /**
   * @param array{label:string,country_slug:string,promo_tv_id:int} $row
   * @return array{label:string,country_slug:string,tours_href:string,promo_href:string}
   */
  function th_posts_country_links(array $row): array
  {
    $label = (string) $row['label'];
    $slug = (string) $row['country_slug'];
    $tvId = (int) $row['promo_tv_id'];
    $depId = 7;
    $searchQs = 'countryId=' . rawurlencode((string) $tvId)
      . '&countryName=' . rawurlencode($label)
      . '&departureId=' . rawurlencode((string) $depId)
      . '&th_wizard_step=2';
    return [
      'label' => $label,
      'country_slug' => $slug,
      'tours_href' => '/frontend/window/countries/' . rawurlencode($slug) . '.php',
      'promo_href' => '/frontend/window/promotions.php?countryId=' . $tvId . '&countryName=' . rawurlencode($label),
      'search_href' => '/?' . $searchQs,
    ];
  }
}

if (!function_exists('th_posts_enrich')) {
  /**
   * @param array<string,mixed> $post
   * @return array<string,mixed>
   */
  function th_posts_enrich(array $post): array
  {
    $post['read_minutes'] = th_posts_estimate_read_minutes($post);
    $country = th_posts_detect_country($post);
    $post['country_detected'] = $country;
    if ($country !== null && trim((string) ($post['tag'] ?? '')) === '') {
      $post['tag'] = $country['label'];
    }
    return $post;
  }
}

if (!function_exists('th_posts_demo_list_enriched')) {
  /**
   * @return list<array<string,mixed>>
   */
  function th_posts_demo_list_enriched(): array
  {
    $out = [];
    foreach (th_posts_demo_list() as $post) {
      $out[] = th_posts_enrich($post);
    }
    usort($out, static function (array $a, array $b): int {
      return strcmp((string) ($b['published_at'] ?? ''), (string) ($a['published_at'] ?? ''));
    });
    return $out;
  }
}
