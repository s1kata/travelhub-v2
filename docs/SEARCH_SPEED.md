# Ускорение поиска туров (best practice)

Изменения для главной [travelhub63.ru](https://travelhub63.ru/frontend/index.php):

## 1. Go sidecar `search-cache-reader`

Читает файловый кэш `data/tourvisor_cache/` без bootstrap PHP (~3 s → ~50 ms).

```bash
cd search-cache-reader
go build -o search-cache-reader .
export TOURVISOR_CACHE_DIR=/var/www/html/data/tourvisor_cache
export PHP_TOURVISOR_PROXY_URL=https://travelhub63.ru/frontend/api/tourvisor-proxy.php
export LISTEN_ADDR=:8080
./search-cache-reader
```

Nginx: см. `deploy/nginx-travelhub63.conf` — `location = /frontend/api/tourvisor-proxy.php` → `:8080`.

При cache miss и `live=1` Go проксирует запрос в PHP.

## 2. SWR на главной (`frontend/index.php`)

- Сначала `search-cached` + `cacheScope=country_page` + `cacheOnly` — мгновенная выдача.
- Фоном — `live=1` для обновления цен без блокировки UI.
- Live search (30–60 s) только если кэша нет.

## 3. Cron прогрева

```bash
php backend/cron/warm_home_search_cache.php
```

Прогревает все страны из `popular_countries.php` × Самара + Москва, ночи 6–9, `cacheScope=country_page`.

Cron (2× в сутки):

```
30 0,12 * * * cd /path/to/travelhub-v2 && php backend/cron/warm_home_search_cache.php >> data/home_search_warm.log 2>&1
```

## 4. systemd unit (пример)

```ini
[Unit]
Description=Travel Hub search-cache-reader
After=network.target

[Service]
Type=simple
WorkingDirectory=/opt/search-cache-reader
Environment=TOURVISOR_CACHE_DIR=/var/www/html/data/tourvisor_cache
Environment=PHP_TOURVISOR_PROXY_URL=http://127.0.0.1/frontend/api/tourvisor-proxy.php
Environment=LISTEN_ADDR=127.0.0.1:8080
ExecStart=/opt/search-cache-reader/search-cache-reader
Restart=always

[Install]
WantedBy=multi-user.target
```

## Ожидаемый эффект

| Сценарий | До | После |
|----------|-----|-------|
| Справочники (departures) | 3–4 s | 50–150 ms |
| Поиск (прогретый кэш) | 0.3–31 s | <1 s стабильно |
| Поиск (cold) | 30–60 s блокировка | preview из кэша + фон |

## Откат

1. В nginx вернуть `tourvisor-proxy.php` на php-fpm.
2. Остановить `search-cache-reader`.
3. SWR на фронте безопасен без Go — работает только с PHP.
