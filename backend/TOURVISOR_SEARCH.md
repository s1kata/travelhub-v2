# Поиск туров: API и кэш Tourvisor

**Документация API:** https://api.tourvisor.ru/search/docs — запросы к `GET /tours/search` с параметрами `departureId`, `countryId`, `dateFrom`, `dateTo`, `nightsFrom`, `nightsTo`, `adults`, `currency`, `onlyCharter` и опциональными фильтрами принимаются API. В ответе у каждого тура есть поле `isPromo` (признак акции/скидки).

## Как устроено

Все запросы к Tourvisor идут через **прокси** `backend/api/tourvisor-proxy.php` (и алиас `frontend/api/tourvisor-proxy.php`). Прокси использует:

- **Справочники** (departures, countries, meals, regions, dates): для **городов вылета** и **стран** порядок: **сразу Firestore** — коллекция **`dictionaryCache`**, документы `departures`, `countries` (или с параметрами, например `countries_departureId=28_onlyCharter=false`); при промахе — файловый кэш, затем API. Остальные справочники — только файловый кэш.
- **Поиск туров** (`type=search-cached`): цепочка кэша  
  файл по ключу (14 дн) → Firestore `searchCache` (14 дн) → кэш `all_tours` (14 дн) → при промахе живой запрос к API Tourvisor с сохранением в кэш.

Переменные окружения (`.env`): `TOURVISOR_TOKEN`, `TOURVISOR_API_URL`, `TOURVISOR_CACHE_TTL_HOURS`, `TOURVISOR_SEARCH_CACHE_TTL_HOURS`, `TOURVISOR_ALL_TOURS_CACHE_TTL_HOURS`, `FIREBASE_PROJECT_ID` (или путь к сервисному аккаунту для Firestore).

## Где подключено (прокси + кэш)

Единый базовый URL задаётся в `backend/components/tourvisor_proxy_url.php` (функция `get_tourvisor_proxy_base_url()`). Его используют:

| Страница / компонент | Описание |
|----------------------|----------|
| **frontend/index.php** | Главная: форма поиска (вылет, страна, даты, питание и т.д.) → запросы через прокси. |
| **backend/components/country_tour_search.php** | Блок «Найдите свой идеальный тур» на страницах стран (Турция, Египет, Таиланд, ОАЭ, Россия и все остальные из `frontend/window/countries/*.php`). Форма отправляет запросы через тот же прокси. |

То есть **все «свои» поисковики** (главная + страницы стран) уже ходят в один прокси с кэшем.

## Где виджет Tourvisor (без нашего кэша)

На этих страницах подключается скрипт `//tourvisor.ru/module/init.js` и виджет (календарь, минимальные цены, форма поиска в iframe). Запросы из виджета идут **напрямую в api.tourvisor.ru**, не через наш прокси:

- `frontend/minimal_prices.php` — минимальные цены по направлениям
- `frontend/window/tour-calendar.php` — календарь туров
- `frontend/window/hotels/hotel-detail.php` — форма поиска у отеля
- `frontend/window/offices/*.php` (самара, москва и т.д.) — форма поиска в офисах
- `frontend/window/countries/country.php` — универсальная страница страны с виджетом
- `frontend/guest-template.php` — гостевая форма

Чтобы и там использовать наш API и кэш, нужно либо заменить виджет на свою форму (как на главной и в country_tour_search), либо настроить виджет Tourvisor на свой URL прокси, если у них есть такая опция.

## Прогрев кэша

- Справочники и all_tours: один раз или по крону запускать  
  `php backend/scripts/tourvisor_background_update.php`  
  (или через браузер/задачу по расписанию, если у вас настроен вызов этого скрипта).
- Либо разовый прогрев:  
  `GET /backend/scripts/warmup_tourvisor_cache.php`  
  (если скрипт доступен по HTTP).

## Акционные туры (промо)

Страница акций (`/frontend/window/promotions.php`) показывает туры со скидкой (isPromo). Данные обновляются **фоново 2 раза в сутки**:

- **12:00** по Москве  
- **00:05** по Москве (12:05 ночи)

Свежесть кэша — 24 часа.

Скрипт: `php backend/scripts/promo_tours_refresh.php`

**Cron (часовой пояс Europe/Moscow):**
```
0 12 * * * cd /path/to/project && php backend/scripts/promo_tours_refresh.php
5 0 * * * cd /path/to/project && php backend/scripts/promo_tours_refresh.php
```

Если сервер в UTC: `0 9 * * *` (12:00 МСК) и `5 21 * * *` (00:05 МСК).

Требуется веб-сервер и в `.env`:
```env
SITE_URL=https://travelhub63.ru
```
