# Прогрев на hub63 (SpaceWeb) — без поломки CRLF

Если в логе `$'\r': command not found` — на сервере Windows-переводы строк в `.sh`.
**Не полагайтесь на bash**, пока не почините или не зальёте LF из git.

## 1. Починка .sh на сервере (один раз)

```bash
cd ~/hub63_ru/public_html
sed -i 's/\r$//' backend/cron/*.sh
chmod +x backend/cron/*.sh
```

## 2. Прогрев стран (`cacheScope=country_page`) — только PHP

```bash
cd ~/hub63_ru/public_html
mkdir -p data
nohup /usr/bin/php8.1 backend/cron/warm_home_search_cache.php >> data/home_search_warm.log 2>&1 &
tail -f data/home_search_warm.log
```

В конце JSON-строка с `"warmed"`, `"errors"`. Проверка:

```bash
ls -la data/tourvisor_search_cover/ 2>/dev/null | head
grep -E '"ok":true|"hotels":[1-9]' data/home_search_warm.log | tail -5
```

## 3. Акции

```bash
cd ~/hub63_ru/public_html
nohup env PROMO_WARM_FORCE=1 /usr/bin/php8.1 backend/cron/update_promotions_cache.php >> data/promo_warm.log 2>&1 &
tail -f data/promo_warm.log
```

## 4. В `.env` на сервере должно быть

```env
SITE_URL=https://travelhub63.ru
TH_SEARCH_COVER_ENABLED=1
TH_WARM_COVER_ENABLED=1
TOURVISOR_PROXY_RELATIVE_PATH=frontend/api/tourvisor-proxy.php
```

Без `TH_WARM_COVER_ENABLED=1` прогрев cover не пишет файлы для страниц стран.
