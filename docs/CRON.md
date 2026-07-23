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

Удаляет записи старше 10 дней (аргумент — число дней).

## HTTP-cron (если нет CLI)

- `GET /backend/api/cron-yml-feed.php` — логика как у `yml_feed_rules_cron.php` (защитите URL на проде).

Подробнее о Tourvisor: [TOURVISOR.md](TOURVISOR.md).
