# QA summary — travel63test.ru — 2026-08-27

**Стенд:** https://travel63test.ru  
**Детали:** [HTTP smoke](QA-auto-smoke-2026-08-27.md) · [UI / адаптив](QA-ui-adaptation-2026-08-27.md) · скрины `qa-screenshots-2026-08-27/`  

## Статус фиксов (код, 2026-08-27)

Все пункты сводки закрыты в репозитории. После деплоя на travel63test — перепроверить High.

| # | Было | Фикс |
|---|------|------|
| 1 | canonical/og `http` + `/frontend/frontend/` | `seo_head.php`: HTTPS via proxy + canonical = origin+path; schema без double prefix |
| 2 | Absolute `http://` redirects | `.htaccess` X-Forwarded-Proto; nginx `absolute_redirect off` + `/frontend` 301 |
| 3 | Mobile nav clip | `__inner`: `flex:1; min-height:0; overflow-y:auto` |
| 4 | Logo «Tra…» | logo `flex:0 0 auto; overflow:visible` |
| 5 | Sticky bar vs registration | `body.th-page-auth` hides lead bar |
| Med | stubs / legacy 404 / payment prod | PHP 301 stubs; htaccess+nginx; SITE_URL follows request host on test |
| Med | filters under bar / hotels skel | filters margin; 3-card skel + hint |
| Low | calendar / nights / load flash | larger mobile cal text; nights body scroll; no «Загрузка...» wipe |

**Деплой:** обновить nginx snippet из `deploy/nginx-travelhub63.conf` (legacy locations).  
**Лимит:** Chromium viewport ≠ реальный Yandex/Safari/Edge.
