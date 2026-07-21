#!/usr/bin/env bash
# Прогрев search-cached для главной (топ-направления из Самары).
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"
CRON_SCRIPT="$ROOT/backend/cron/warm_home_search_cache.php"
resolve_php_bin() {
  if [[ -n "${PHP_BIN:-}" ]] && command -v "$PHP_BIN" >/dev/null 2>&1; then echo "$PHP_BIN"; return 0; fi
  for c in /usr/bin/php8.2 /usr/bin/php8.1 /usr/bin/php8.0 /usr/bin/php7.4 php82 php81 php74 php7.4 php; do
    command -v "$c" >/dev/null 2>&1 || continue
    major=$("$c" -r 'echo (int) PHP_MAJOR_VERSION;' 2>/dev/null || echo 0)
    [[ "$major" -ge 7 ]] && { echo "$c"; return 0; }
  done
  return 1
}
PHP_BIN="$(resolve_php_bin)" || { echo "PHP 7+ not found" >&2; exit 1; }
echo "[$(date '+%Y-%m-%dT%H:%M:%S%z')] home search warm start"
"$PHP_BIN" "$CRON_SCRIPT"
echo "[$(date '+%Y-%m-%dT%H:%M:%S%z')] home search warm done"
