#!/usr/bin/env bash
# Прогрев promo_cache_* (акции). SpaceWeb: только LF (без CRLF).
# Прямой вызов: env PROMO_WARM_FORCE=1 /usr/bin/php8.1 backend/cron/update_promotions_cache.php
set -eu
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"
mkdir -p "$ROOT/data"
PHP_BIN="${PHP_BIN:-/usr/bin/php8.1}"
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  for c in /usr/bin/php8.2 /usr/bin/php8.1 /usr/bin/php8.0 /usr/bin/php7.4 php; do
    if command -v "$c" >/dev/null 2>&1; then PHP_BIN="$c"; break; fi
  done
fi
LOCK_FILE="$ROOT/data/promo_warm.lock"
run_warm() {
  echo "[$(date '+%Y-%m-%dT%H:%M:%S%z')] promo warm start php=$PHP_BIN cwd=$ROOT"
  exec "$PHP_BIN" backend/cron/update_promotions_cache.php
}
if [[ "${PROMO_WARM_NO_FLOCK:-}" == "1" ]]; then
  run_warm
elif command -v flock >/dev/null 2>&1; then
  exec 9>"$LOCK_FILE"
  flock -n 9 || { echo "promo warm already running"; exit 0; }
  run_warm
else
  run_warm
fi
