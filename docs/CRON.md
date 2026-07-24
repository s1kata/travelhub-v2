# Задачи по расписанию (cron)

Замените `/path/to/travelhub-v2` на абсолютный путь к проекту на сервере. Часовой пояс cron должен совпадать с поясом сервера (ниже указаны варианты для **Europe/Moscow** и **UTC**).

## Обязательные

### Акционные туры (промо)

Страница `/frontend/window/promotions.php`. Свежесть кэша — 24 ч.

```bash
php backend/scripts/promo_tours_refresh.php
```

| Время (МСК) | Cron (МСК) | Cron (UTC) |
|-------------|------------|------------|
| 12:00 | `0 12 * * *` | `0 9 * * *` |
| 00:05 | `5 0 * * *` | `5 21 * * *` |

Пример:

```
0 12 * * * cd /path/to/travelhub-v2 && php backend/scripts/promo_tours_refresh.php
5 0 * * * cd /path/to/travelhub-v2 && php backend/scripts/promo_tours_refresh.php
```

Требуется `SITE_URL=https://travelhub63.ru` в `.env`.

### YML-фид по правилам админки

Генерация снимка для `/feed.yml` и связанных URL.

```bash
php backend/scripts/yml_feed_rules_cron.php
```

Рекомендуется **ежедневно в 00:00**:

```
0 0 * * * cd /path/to/travelhub-v2 && php backend/scripts/yml_feed_rules_cron.php >> data/yandex_yml_rules_cron.log 2>&1
```

Альтернатива вручную: `php backend/scripts/rebuild_feed.php` или `php rebuild_feed.php` из корня.

### Синхронизация офферов Yandex

```bash
php backend/scripts/sync_yandex_feed_offers.php
```

Пример (ежедневно в 12:00):

```
0 12 * * * cd /path/to/travelhub-v2 && php backend/scripts/sync_yandex_feed_offers.php
```

## Рекомендуемые

### Прогрев кэша Tourvisor

Справочники и `all_tours`:

```bash
php backend/scripts/tourvisor_background_update.php
```

Пример (ночью):

```
0 3 * * * cd /path/to/travelhub-v2 && php backend/scripts/tourvisor_background_update.php
```

Разовый HTTP-прогрев: `GET /backend/scripts/warmup_tourvisor_cache.php`

### Очистка устаревшего кэша страниц

```bash
php clear_cache.php 10
```

Удаляет JSON в `data/tourvisor_cache` старше 10 дней.

### Кэш картинок Tourvisor (hotel_pics)

Папка `data/tourvisor_image_cache/` — прокси `tourvisor-image-proxy.php`. На проде может занимать гигабайты: просроченные файлы удаляются только при повторном запросе.

**Разовая чистка (освободить место сейчас):**

```bash
php clear_image_cache.php --stats
php clear_image_cache.php 14 --trim-mb=1024
```

**Cron (рекомендуется, раз в сутки):**

```
30 4 * * * cd /path/to/travelhub-v2 && php clear_image_cache.php >> data/image_cache_cron.log 2>&1
```

В `.env`: `TOURVISOR_IMAGE_CACHE_TTL_DAYS=14`, `TOURVISOR_IMAGE_CACHE_MAX_MB=1024` — лимит диска; популярные фото перекачаются автоматически при просмотре (первая загрузка ~0.5–2 с, дальше снова из кэша).

## HTTP-cron (если нет CLI)

- `GET /backend/api/cron-yml-feed.php` — логика как у `yml_feed_rules_cron.php` (защитите URL на проде).

Подробнее о Tourvisor: [TOURVISOR.md](TOURVISOR.md).
