#!/usr/bin/env bash
# Прогрев search-cached (страницы стран, cover). SpaceWeb: только LF (без CRLF).
# Прямой вызов без bash: /usr/bin/php8.1 backend/cron/warm_home_search_cache.php
set -eu
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"
PHP_BIN="${PHP_BIN:-/usr/bin/php8.1}"
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  for c in /usr/bin/php8.2 /usr/bin/php8.1 /usr/bin/php8.0 /usr/bin/php7.4 php; do
    if command -v "$c" >/dev/null 2>&1; then PHP_BIN="$c"; break; fi
  done
fi
echo "[$(date '+%Y-%m-%dT%H:%M:%S%z')] home search warm start php=$PHP_BIN cwd=$ROOT"
exec "$PHP_BIN" backend/cron/warm_home_search_cache.php "$@"
