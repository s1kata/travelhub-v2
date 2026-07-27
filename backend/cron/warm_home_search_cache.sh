#!/bin/bash
# Прогрев search-cached для главной (см. warm_home_search_cache.php).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"
php backend/cron/warm_home_search_cache.php
