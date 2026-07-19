#!/usr/bin/env bash
# Прогрев кэша акций (data/promo_cache_{countryId}_{departureId}.json).
# Нужен PHP >= 7.4 (на хостинге «php» часто = 5.2 — скрипт сам ищет php7.4).
#
# Запуск по SSH:
#   cd ~/travel63test_ru/public_html && bash backend/cron/warm_promotions_cache.sh
#
# Явно указать PHP:
#   PHP_BIN=/usr/bin/php7.4 bash backend/cron/warm_promotions_cache.sh
#
# Cron (2 раза в сутки):
#   0 0,12 * * * cd /home/g/garant77li/travel63test_ru/public_html && bash backend/cron/warm_promotions_cache.sh >> data/promo_warm.log 2>&1

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

CRON_SCRIPT="$ROOT/backend/cron/update_promotions_cache.php"

if [[ ! -f "$CRON_SCRIPT" ]]; then
  echo "Не найден: $CRON_SCRIPT" >&2
  exit 1
fi

resolve_php_bin() {
  if [[ -n "${PHP_BIN:-}" ]] && command -v "$PHP_BIN" >/dev/null 2>&1; then
    echo "$PHP_BIN"
    return 0
  fi
  local c major
  for c in /usr/bin/php8.2 /usr/bin/php8.1 /usr/bin/php8.0 /usr/bin/php7.4 \
           /usr/local/bin/php74 php82 php81 php80 php74 php7.4 php; do
    if ! command -v "$c" >/dev/null 2>&1; then
      continue
    fi
    major=$("$c" -r 'echo (int) PHP_MAJOR_VERSION;' 2>/dev/null || echo 0)
    if [[ "$major" -ge 7 ]]; then
      echo "$c"
      return 0
    fi
  done
  return 1
}

PHP_BIN="$(resolve_php_bin)" || {
  echo "Не найден PHP 7+. Укажите: PHP_BIN=/usr/bin/php7.4 bash backend/cron/warm_promotions_cache.sh" >&2
  echo "Текущий php: $(command -v php 2>/dev/null || echo none) — $(php -v 2>/dev/null | head -1 || true)" >&2
  exit 1
}

PHP_VER=$("$PHP_BIN" -r 'echo PHP_VERSION;' 2>/dev/null || echo unknown)
echo "[$(date '+%Y-%m-%dT%H:%M:%S%z')] promo warm start php=$PHP_BIN ($PHP_VER) cwd=$ROOT"
"$PHP_BIN" "$CRON_SCRIPT"
echo "[$(date '+%Y-%m-%dT%H:%M:%S%z')] promo warm done"
# После прогрева акций пересоберите YML: php rebuild_feed.php (или yml_feed_rules_cron.php)
