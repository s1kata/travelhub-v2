# Crontab SpaceWeb — prod `hub63_ru`

Корень сайта:

```text
/home/g/garant77li/hub63_ru/public_html
```

PHP: `/usr/bin/php8.1` (метрики OTLP — `php8.3`, если есть в PATH).

Часовой пояс панели: **Europe/Moscow**.

## Активные задания (оставить)

| Мин | Час | День | Мес | DOW | Команда |
|-----|-----|------|-----|-----|---------|
| 5 | 0,12 | * | * | * | `/usr/bin/php8.1 /home/g/garant77li/hub63_ru/public_html/backend/cron/update_promotions_cache.php >> /home/g/garant77li/hub63_ru/public_html/data/promo_warm.log 2>&1` |
| 15 | 3 | * | * | 0 | то же (страховка вс) |
| 30 | 0,8,14,20 | * | * | * | `/usr/bin/php8.1 /home/g/garant77li/hub63_ru/public_html/backend/cron/warm_home_search_cache.php >> /home/g/garant77li/hub63_ru/public_html/data/home_search_warm.log 2>&1` |
| 30 | 1 | * | * | * | `/usr/bin/php8.1 /home/g/garant77li/hub63_ru/public_html/backend/cron/warm_home_search_cache.php --scope=nightly --phase=0 >> /home/g/garant77li/hub63_ru/public_html/data/home_search_warm_nightly.log 2>&1` *(фаза 0 по часу)* |
| 30 | 2 | * | * | * | `/usr/bin/php8.1 /home/g/garant77li/hub63_ru/public_html/backend/cron/warm_home_search_cache.php --scope=nightly --phase=1 >> /home/g/garant77li/hub63_ru/public_html/data/home_search_warm_nightly.log 2>&1` *(фаза 1)* |
| 30 | 3 | * | * | * | `/usr/bin/php8.1 /home/g/garant77li/hub63_ru/public_html/backend/cron/warm_home_search_cache.php --scope=nightly --phase=2 >> /home/g/garant77li/hub63_ru/public_html/data/home_search_warm_nightly.log 2>&1` *(фаза 2)* |
| 20 | 1,9,15,21 | * | * | * | `/usr/bin/php8.1 -d memory_limit=768M /home/g/garant77li/hub63_ru/public_html/backend/cron/warm_calendar_cache.php >> /home/g/garant77li/hub63_ru/public_html/data/calendar_warm.log 2>&1` |
| 20 | 0 | * | * | * | `/usr/bin/php8.1 /home/g/garant77li/hub63_ru/public_html/backend/scripts/yml_feed_rules_cron.php >> /home/g/garant77li/hub63_ru/public_html/data/yandex_yml_rules_cron.log 2>&1` |
| 10 | 0 | * | * | 1 | `/usr/bin/php8.1 /home/g/garant77li/hub63_ru/public_html/backend/cron/warm_yml_rotation_countries.php >> /home/g/garant77li/hub63_ru/public_html/data/yml_rotation_warm.log 2>&1` |
| 0 | 2 | * | * | * | `/usr/bin/php8.1 /home/g/garant77li/hub63_ru/public_html/backend/cron/cleanup_search_cover_cache.php >> /home/g/garant77li/hub63_ru/public_html/data/cover_cleanup.log 2>&1` |
| 30 | 4 | * | * | * | `/usr/bin/php8.1 /home/g/garant77li/hub63_ru/public_html/clear_image_cache.php 14 --trim-mb=1024 >> /home/g/garant77li/hub63_ru/public_html/data/image_cache_cron.log 2>&1` |
| 0 | 5 | * | * | 0 | `/usr/bin/php8.1 /home/g/garant77li/hub63_ru/public_html/clear_cache.php 10 >> /home/g/garant77li/hub63_ru/public_html/data/tourvisor_cache_cleanup.log 2>&1` |
| 30 | 5 | * | * | 1,4 | `/usr/bin/php8.1 /home/g/garant77li/hub63_ru/public_html/backend/cron/firestore_cache_cleanup.php >> /home/g/garant77li/hub63_ru/public_html/data/firestore_cache_cleanup.log 2>&1` *(только если Firestore L1 включён)* |
| * | * | * | * | * | `/usr/bin/php8.3 /home/g/garant77li/hub63_ru/public_html/backend/cron/prometheus_metrics_export.php >> /home/g/garant77li/hub63_ru/public_html/data/logs/prometheus_metrics_cron.log 2>&1` *(если настроен Grafana OTLP в `.env`)* |

## Удалить (legacy / дубли)

- `warm_promotions_cache.sh` без `flock` и/или на `php7.4`
- второй YML в 05:00 на `php7.4`
- любые `tourvisor_background_update`, `promo_tours_refresh`, `sync_yandex_feed_offers`, `warmup_tourvisor_cache`

Полный список: `docs/CRON.md`.

Все команды запускают PHP-файлы напрямую; `.sh`, `flock`, `cd` и env-переопределения не используются. Ночные фазы передаются параметрами PHP-скрипту. Расписание заданий прогрева сохранено.
