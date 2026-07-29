# Ускорение поиска туров (best practice)

Двухслойная схема: **L1 Go** (быстрый cache read) + **L2 PHP** (полный монолит как fallback).  
Публичный URL для фронта не меняется: `/frontend/api/tourvisor-proxy.php`.

```
Браузер
   │
   ▼
Nginx
   │
   ├─ L1  Go :8080  ── cache HIT → JSON (~30–80 ms)
   │         │
   │         └─ miss / live=1 → HTTP на /_internal/tourvisor-proxy.php → PHP
   │
   └─ L2  если Go 502/503/504 / connect timeout → сразу PHP-FPM
            (тот же tourvisor-proxy.php, заголовок X-Cache-Reader: php-nginx-fallback)
```

Конфиг: [`deploy/nginx-travelhub63.conf`](../deploy/nginx-travelhub63.conf).

---

## 1. Go sidecar `search-cache-reader`

Читает файловый кэш `data/tourvisor_cache/` без bootstrap PHP (~3 s → ~50 ms).

```bash
cd search-cache-reader   # репозиторий s1kata/microservice
go build -o search-cache-reader .
export TOURVISOR_CACHE_DIR=/var/www/html/data/tourvisor_cache
# ВАЖНО: loop-safe URL (не публичный /frontend/api/… — иначе Nginx→Go→Nginx цикл)
export PHP_TOURVISOR_PROXY_URL=http://127.0.0.1/_internal/tourvisor-proxy.php
export LISTEN_ADDR=127.0.0.1:8080
./search-cache-reader
```

Проверка слоёв:

```bash
# L1 жив
curl -s http://127.0.0.1:8080/health

# Публичный URL — смотрим X-Cache-Reader (go | php-nginx-fallback | php-go-upstream | php)
curl -sI "https://travelhub63.ru/frontend/api/tourvisor-proxy.php?type=departures" | grep -iE 'x-cache-reader|x-tourvisor'

# Симуляция падения Go: systemctl stop search-cache-reader → снова curl — должен быть php-nginx-fallback
```

---

## 2. SWR на главной (`frontend/index.php`)

- Сначала `search-cached` + `cacheScope=country_page` + `cacheOnly` — мгновенная выдача.
- Фоном — `live=1` для обновления цен без блокировки UI.
- Live search (30–60 s) только если кэша нет.

SWR работает и при L2-only (Go выключен) — просто медленнее cache hit.

---

## 3. Cron прогрева

```bash
php backend/cron/warm_home_search_cache.php
```

Прогревает все страны из `popular_countries.php` × Самара + Москва, ночи 6–9, `cacheScope=country_page`.

```
30 0,12 * * * cd /path/to/travelhub-v2 && php backend/cron/warm_home_search_cache.php >> data/home_search_warm.log 2>&1
```

Акции (speed-cache): см. `backend/cron/update_promotions_cache.php` / `warm_promotions_cache.sh`.

---

## 4. systemd unit (пример)

```ini
[Unit]
Description=Travel Hub search-cache-reader (L1)
After=network.target

[Service]
Type=simple
WorkingDirectory=/opt/search-cache-reader
Environment=TOURVISOR_CACHE_DIR=/var/www/html/data/tourvisor_cache
Environment=PHP_TOURVISOR_PROXY_URL=http://127.0.0.1/_internal/tourvisor-proxy.php
Environment=LISTEN_ADDR=127.0.0.1:8080
ExecStart=/opt/search-cache-reader/search-cache-reader
Restart=always
RestartSec=2

[Install]
WantedBy=multi-user.target
```

После смены nginx:

```bash
sudo nginx -t && sudo systemctl reload nginx
sudo systemctl enable --now search-cache-reader
```

---

## Заголовки ответа

| Заголовок | Значения |
|-----------|----------|
| `X-Cache-Reader` | `go` (sidecar), `php-nginx-fallback` (Go down), `php-go-upstream` (Go→PHP miss/live), `php` (прямой PHP) |
| `X-Tourvisor-Cache` | `hit` / `miss` / `n/a` |
| `X-Tourvisor-Search-Mode` | `cache` / `live` |

---

## Ожидаемый эффект

| Сценарий | До | После |
|----------|-----|-------|
| Справочники / cache hit, Go up | 3–4 s | 50–150 ms |
| Cache hit, Go down | 502 | PHP L2, обычно &lt;1–3 s |
| Поиск (прогретый кэш) | нестабильно | &lt;1 s + SWR |
| Поиск (cold) | 30–60 s блокировка | preview из кэша + фон (SWR) |

---

## Откат

1. В nginx заменить `location = /frontend/api/tourvisor-proxy.php` на обычный `fastcgi_pass` (или закомментировать `proxy_pass` / `error_page` и оставить только `@tourvisor_php_fallback` логику как основной location).
2. `sudo systemctl stop search-cache-reader`
3. SWR на фронте безопасен без Go.

Полный откат к PHP-only location:

```nginx
location = /frontend/api/tourvisor-proxy.php {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root/frontend/api/tourvisor-proxy.php;
    fastcgi_pass unix:/run/php/php-fpm.sock;
    fastcgi_read_timeout 130s;
}
```

---

## Roadmap (не блокирует L1/L2)

1. Promo cache read в Go (`promo_cache_*`)
2. Async search jobs (TTFB &lt;200 ms на cold)
3. Redis вместо файлового кэша при нескольких нодах
