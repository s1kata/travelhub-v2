#!/usr/bin/env bash
# Календарь из cover. Только LF. Или: php8.1 -d memory_limit=768M backend/cron/warm_calendar_cache.php
set -eu
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"
PHP_BIN="${PHP_BIN:-/usr/bin/php8.1}"
command -v "$PHP_BIN" >/dev/null 2>&1 || PHP_BIN=php
exec "$PHP_BIN" -d memory_limit=768M backend/cron/warm_calendar_cache.php "$@"
