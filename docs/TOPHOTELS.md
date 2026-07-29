# TopHotels — подготовка к партнёрскому API

Рейтинги и отзывы [TopHotels](https://api.tophotels.ru/) — отдельный слой контента поверх Tourvisor.
Цены и поиск туров **не** зависят от TopHotels. Пока API от Адона не пришло, каркас уже готов: достаточно заполнить `.env` и матчинг.

## Что уже сделано

| Часть | Путь |
|-------|------|
| Конфиг / client stub | `backend/components/tophotels/` |
| Enrichment в прокси | `tourvisor-proxy` → поле `hotel.tophotels` |
| Файловый кэш | `data/tophotels/` |
| Cron sync | `backend/cron/tophotels_sync.php` |
| UI guest-score + фильтр/сорт | `th-tour-card.js`, `th-tour-post-filters.js`, toolbar на главной |
| Sample fixtures | `data/tophotels/*.sample.*` |

## UX (best practice)

См. полный продукт-дизайн: [RESULTS_UX.md](./RESULTS_UX.md).

Кратко (Booking / Level / Onlinetours):

1. Wizard **не трогаем** — адаптация sheets 768 уже ок.
2. Карточка: score-badge на фото → «Отлично» + N отзывов → аспекты → цена → CTA.
3. Mobile/tablet: sticky chips над выдачей (сайдбар &lt;1024 скрыт).
4. Desktop ≥1024: sidebar + sort-seg.
5. Цвета `#1A1A40` / `#5DA9A4`; нет матча → UI скрыт.

## Контракт в JSON поиска

На каждом отеле (если есть матч):

```json
"tophotels": {
  "id": "th-demo-1001",
  "rating": 8.7,
  "scale": 10,
  "reviewsCount": 214,
  "food": 8.5,
  "service": 8.9,
  "placement": 8.6,
  "lastReviewAt": "2026-07-01",
  "matched": true,
  "source": "tophotels"
}
```

Tourvisor `rating` **не перезаписывается**. В UI TopHotels показывается отдельно.

## Когда придёт API

1. Получить от Адона: URL фида рейтингов (XML/JSON), при необходимости ключ, коды виджетов.
2. В `.env`:

```bash
TOPHOTELS_ENABLED=1
TOPHOTELS_RATINGS_URL=https://…   # от партнёра
TOPHOTELS_API_KEY=…              # если нужен
TOPHOTELS_RATING_SCALE=10
# шаблоны виджетов с {tophotels_id}:
# TOPHOTELS_WIDGET_REVIEWS_TMPL=...
```

3. Матчинг Tourvisor ↔ TopHotels (CSV):

```bash
php backend/cron/tophotels_sync.php --import-matches=/path/to/matches.csv
```

Формат CSV: `tourvisor_hotel_id,tophotels_id,hotel_name,country_name`

4. Синк рейтингов:

```bash
php backend/cron/tophotels_sync.php
```

Пишет `data/tophotels/enrichment.json` (и опционально таблицы БД).

5. Cron (раз в сутки):

```
30 3 * * * cd /path/to/travelhub-v2 && php backend/cron/tophotels_sync.php >> data/tophotels_sync.log 2>&1
```

6. Если XML-схема партнёра отличается от заглушки — дописать парсер в `th_tophotels_parse_ratings_payload()` (`client.php`, метка `TODO:API`).

## Локальная проверка без API

```bash
# .env
TOPHOTELS_USE_FIXTURE=1
TOPHOTELS_ENABLED=1

# подставить реальные tourvisor hotel id в matches.sample.json / enrichment.sample.json
# либо импорт CSV и sync с fixture ratings
php backend/cron/tophotels_sync.php --import-matches=data/tophotels/matches.sample.csv
php backend/cron/tophotels_sync.php
```

## Виджеты на странице отеля

```php
require_once __DIR__ . '/../components/tophotels/bootstrap.php';
echo th_tophotels_widget_snippet($tophotelsId, 'reviews');
```

Пока шаблоны в `.env` пустые — функция возвращает `''`.

## Принципы

- Enrichment **после** чтения кэша Tourvisor → cache key не дробится.
- Фильтры `thMinRating` — post-filter на клиенте.
- Cold path поиска не ждёт TopHotels HTTP.
- Go sidecar позже может читать тот же `enrichment.json` или Redis-копию.
