# Поиск туров: Tourvisor

**API:** https://api.tourvisor.ru/search/docs

Запросы `GET /tours/search` с параметрами `departureId`, `countryId`, `dateFrom`, `dateTo`, `nightsFrom`, `nightsTo`, `adults`, `currency`, `onlyCharter` и фильтрами. В ответе у тура есть поле `isPromo`.

## Прокси и кэш

Все «свои» запросы идут через `backend/api/tourvisor-proxy.php` (алиас `frontend/api/tourvisor-proxy.php`).

На проде перед PHP стоит **двухслойный** путь (Go cache reader → PHP fallback): см. [SEARCH_SPEED.md](SEARCH_SPEED.md) и `deploy/nginx-travelhub63.conf`.

Чтение кэша поиска/справочников внутри PHP: **L1 Firestore → L2 файл → live** — см. [CACHE_LAYERS.md](CACHE_LAYERS.md).

| Тип | Цепочка |
|-----|---------|
| Справочники (departures, countries) | L1 Firestore `dictionaryCache` → L2 файл → API |
| Остальные справочники | файловый кэш → API |
| Поиск (`type=search-cached`) | L1 Firestore `searchCache` → L2 файл → `all_tours` → API |

Переменные `.env`: `TOURVISOR_TOKEN`, `TOURVISOR_API_URL`, `TOURVISOR_CACHE_TTL_HOURS`, `TOURVISOR_SEARCH_CACHE_TTL_HOURS`, `TOURVISOR_ALL_TOURS_CACHE_TTL_HOURS`, `FIREBASE_PROJECT_ID`.

Базовый URL прокси: `backend/components/tourvisor_proxy_url.php` → `get_tourvisor_proxy_base_url()`.

## Где используется прокси

| Место | Назначение |
|-------|------------|
| `frontend/index.php` | Форма поиска на главной |
| `backend/components/country_tour_search.php` | Блок поиска на страницах стран |

## Виджет Tourvisor (без нашего кэша)

Скрипт `//tourvisor.ru/module/init.js` — запросы напрямую в api.tourvisor.ru:

- `frontend/minimal_prices.php`
- `frontend/window/tour-calendar.php`
- `frontend/window/hotels/hotel-detail.php`
- `frontend/window/offices/*.php`
- `frontend/window/countries/country.php`
- `frontend/guest-template.php`

## Кэш картинок Tourvisor

Прокси: `backend/api/tourvisor-image-proxy.php` → папка `data/tourvisor_image_cache/`.

- TTL: `TOURVISOR_IMAGE_CACHE_TTL_DAYS` (по умолчанию 14)
- Лимит диска: `TOURVISOR_IMAGE_CACHE_MAX_MB` (0 = без лимита)
- Очистка: `php clear_image_cache.php` — см. [CRON.md](CRON.md)

После удаления старых файлов картинки **не пропадают** — при первом просмотре тура прокси снова скачает их с static.tourvisor.ru и положит в кэш.

## Прогрев и промо

- Прогрев: `php backend/scripts/tourvisor_background_update.php` или `GET /backend/scripts/warmup_tourvisor_cache.php`
- Промо-туры: `php backend/scripts/promo_tours_refresh.php` — см. [CRON.md](CRON.md)
