#!/bin/bash
# Прогрев search-cached для главной (см. warm_home_search_cache.php).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"
if command -v php8.1 >/dev/null 2>&1; then
  php8.1 backend/cron/warm_home_search_cache.php
else
  php backend/cron/warm_home_search_cache.php
fi
