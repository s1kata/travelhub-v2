# Трёхуровневый кэш поиска Tourvisor

Публичный контракт не меняется: `/frontend/api/tourvisor-proxy.php`.  
Заголовок ответа: `X-Tourvisor-Cache-Layer`.

| Уровень | Где | Когда |
|---------|-----|--------|
| **L1** `L1-firestore` | Firestore `searchCache` / `dictionaryCache` | Первый read (remote), SpaceWeb «Взлёт» |
| **L2** `L2-file` | `data/tourvisor_cache/*.json` | Miss/timeout Firestore → локальный файл |
| **L3** `L3-go` | Go `search-cache-reader` | Позже на VPS (сейчас не на shared) |
| `live` | Tourvisor API | Полный miss + не `cacheOnly` |

```
Запрос search-cached / справочники
   │
   ├─ L1 Firestore  (TH_CACHE_FIRESTORE_FIRST=1, timeout ~2.5s)
   │     hit → JSON + прогрев L2 файла
   │
   ├─ L2 файл на диске
   │     hit → JSON
   │
   ├─ L3 Go (когда будет VPS + nginx)
   │
   └─ live Tourvisor → пишем L2 + L1
```

SWR на фронте (`cacheOnly` → paint → `live=1`) работает поверх всех слоёв.

## Миграция всего кэша с хостинга → Firestore

Кэш лежит в `data/tourvisor_cache/` на SpaceWeb. Чтобы **перегнать** его в облако:

```bash
# SSH / панель → в корне сайта
php backend/scripts/firestore_migrate_tourvisor_cache.php --dry-run
php backend/scripts/firestore_migrate_tourvisor_cache.php --delay-ms=150
```

| Флаг | Значение |
|------|----------|
| `--dry-run` | Только список, без записи |
| `--only=search` | Только `search_*.json` → `searchCache` |
| `--only=dictionaries` | Справочники → `dictionaryCache` |
| `--purge-local` | Удалить файл после успешной заливки (осторожно) |
| `--delay-ms=150` | Пауза между документами (меньше 429) |

Документы больше ~900 KB Firestore не примет — такие файлы останутся на диске как L2.

После миграции L1 начнёт отдавать прогретые поиски без повторного Tourvisor live.

## Очистка Firestore по cron (2× в неделю)

```bash
php backend/cron/firestore_cache_cleanup.php
# --dry-run  — показать что удалится
```

Удаляет документы с `expiresAt` в прошлом (searchCache + dictionaryCache).

```
# Пн и Чт 03:00 МСК
0 3 * * 1,4 cd /path/to/travelhub-v2 && php backend/cron/firestore_cache_cleanup.php >> data/firestore_cache_cleanup.log 2>&1
```

## Настройка (SpaceWeb / локально)

1. Service account JSON → `backend/config/firebase-service-account.json` (в `.gitignore`).
2. В корневом `.env`:

```bash
FIREBASE_PROJECT_ID=travelhub-mobile-5dade
TH_CACHE_FIRESTORE_FIRST=1
TH_CACHE_FIRESTORE_TIMEOUT_SEC=2.5
```

3. В Firebase Console создайте **Firestore** (Native mode), если ещё нет.
4. После первого live-поиска / warm cron документы появятся в `searchCache` и `dictionaryCache`.

Проверка:

```bash
curl -sI "https://YOUR_HOST/frontend/api/tourvisor-proxy.php?type=departures" \
  | grep -iE 'x-tourvisor-cache-layer|x-tourvisor-firestore'
# ожидаемо: L1-firestore или L2-file; Firestore не off
```

Прогрев (пишет файл + Firestore при live):

```bash
php backend/cron/warm_home_search_cache.php
```

## Фото Tourvisor

Не в Firestore. Остаются в `data/tourvisor_image_cache/` + cron `clear_image_cache.php`.

## Безопасность

- JSON service account только на сервере, chmod 600, не в git.
- Если ключ светился — перевыпустите в Firebase Console.
- Client web config (`apiKey`) ≠ server credentials.
