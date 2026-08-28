# QA auto smoke — travel63test.ru

**Date:** 2026-08-27
**Base:** https://travel63test.ru
**Method:** GET, follow redirects (max 10), no auth / no credentials
**Client:** curl.exe (-L)
**Sources:** `docs/QA-Danik-travel63test.md` + scan `frontend/window/**/*.php|html` + main entries `/frontend`, `/frontend/`, `/frontend/index.php` + QA query variants
**Tiny threshold:** < 2048 bytes

## Summary

| Metric | Value |
|--------|-------|
| URLs probed | 92 |
| Clean (no flags) | 81 |
| Flagged | 11 |
| non-200 | 6 |
| final `http://` | 2 |
| double `/frontend/` | 1 |
| tiny (<2 KB) | 5 |
| Total wall (sum) | 37318 ms |
| Avg latency | 406 ms |
| Slowest | `/frontend` — 729 ms |

## Failure / flag summary

| Severity | Issue | URLs | Notes |
|----------|-------|------|-------|
| **High** | Homepage canonical/og:url still broken | `/frontend/index.php` HTML head | `http://travel63test.ru/frontend/frontend/index.php` (http + double `/frontend/`) — B1 still open |
| **High** | Country landing canonical path doubled | All `countries/*.php` landings (except template) | e.g. turkey → `http://…/frontend/window/countries/frontend/window/countries/turkey.php` (http + path dup). Spot-checked egypt/uae/thailand/russia/vietnam/turkey |
| **High** | Absolute `http://` Location on redirect | `/frontend` → `http://…/frontend/`; `samara-anex-apelsin.php` → `http://…/samara-offices.php` | Mixed-content / HSTS risk; other office aliases use relative Location |
| **Med** | Auth HTML stubs tiny | `login.html` (527 B), `registration.html` (594 B) | B2 still open — use `.php` |
| **Med** | Dead / missing pages | `debug.php`, `employee-photo.php` | HTTP 404, 0 bytes |
| **Med** | `payment.php` redirects to **prod** | final `https://travelhub63.ru/frontend/window/payment-fail.php?error=amount` | Test smoke landed off-host |
| **Low** | Intentional error pages non-200 | `404.php` → 404 (65 KB), `500.php` → 500 (30 KB) | B3: body present, status matches page |
| **Low** | Router without slug | `countries/country.php` → 404 (83 KB) | Expected without slug / pretty URL |
| **Low** | Probe of broken canonical path | `/frontend/frontend/index.php` → 404 (65 KB) | Confirms double-prefix is a real 404 URL |
| **Info** | Dev stub tiny | `simple.php` → 200, 36 B | Not a user page |
| **Info** | `tour-detail` SEO mismatches | canonical/og → **prod** `travelhub63.ru`; JSON-LD/images still `http://travel63test.ru` | Page itself 200 ~120 KB |
| **Info** | Guest cabinet redirects to login | `profile`, `personal-info`, `passport-data`, `dashboard`, `edit-user-data` | 302 → `login.php` (followed; final 200 ~64 KB) — no credentials used |
| **Info** | Body-level `http://` SEO | home + countries-list + ~26 country pages + tour-detail | Absolute insecure host URLs in head/JSON-LD (status still 200) |

### Country canonical spot-check (2026-08-27)

| Slug | Canonical |
|------|-----------|
| turkey | `http://travel63test.ru/frontend/window/countries/frontend/window/countries/turkey.php` |
| egypt | `http://travel63test.ru/frontend/window/countries/frontend/window/countries/egypt.php` |
| uae | `http://travel63test.ru/frontend/window/countries/frontend/window/countries/uae.php` |
| thailand | `http://travel63test.ru/frontend/window/countries/frontend/window/countries/thailand.php` |
| russia | `http://travel63test.ru/frontend/window/countries/frontend/window/countries/russia.php` |
| vietnam | `http://travel63test.ru/frontend/window/countries/frontend/window/countries/vietnam.php` |

### Flagged rows (detail)

| Request path | Status | Final URL | Bytes | ms | Flags |
|--------------|--------|-----------|-------|----|-------|
| `/frontend` | 200 | `http://travel63test.ru/frontend/` | 646947 | 729 | http:// |
| `/frontend/window/countries/country.php` | 404 | `https://travel63test.ru/frontend/window/countries/country.php` | 83460 | 521 | non-200 |
| `/frontend/window/offices/samara-anex-apelsin.php` | 200 | `http://travel63test.ru/frontend/window/offices/samara-offices.php` | 82883 | 455 | http:// |
| `/frontend/window/404.php` | 404 | `https://travel63test.ru/frontend/window/404.php` | 65044 | 392 | non-200 |
| `/frontend/window/500.php` | 500 | `https://travel63test.ru/frontend/window/500.php` | 30581 | 346 | non-200 |
| `/frontend/window/debug.php` | 404 | `https://travel63test.ru/frontend/window/debug.php` | 0 | 228 | non-200, tiny |
| `/frontend/window/employee-photo.php` | 404 | `https://travel63test.ru/frontend/window/employee-photo.php` | 0 | 240 | non-200, tiny |
| `/frontend/window/login.html` | 200 | `https://travel63test.ru/frontend/window/login.html` | 527 | 211 | tiny |
| `/frontend/window/registration.html` | 200 | `https://travel63test.ru/frontend/window/registration.html` | 594 | 284 | tiny |
| `/frontend/window/simple.php` | 200 | `https://travel63test.ru/frontend/window/simple.php` | 36 | 220 | tiny |
| `/frontend/frontend/index.php` | 404 | `https://travel63test.ru/frontend/frontend/index.php` | 65044 | 342 | non-200, double-/frontend/ |

## SEO check (homepage head)

```html
<link rel="canonical" href="http://travel63test.ru/frontend/frontend/index.php">
<meta property="og:url" content="http://travel63test.ru/frontend/frontend/index.php">
```

Expected: `https://travel63test.ru/frontend/index.php`

## Notable redirects (followed)

| From | To (Location / effective) |
|------|---------------------------|
| `/frontend` | **http://** `travel63test.ru/frontend/` (301) |
| `samara-anex-apelsin.php` | **http://** `…/samara-offices.php` (301) |
| Most `offices/*.php` aliases | relative `/frontend/window/offices/…` (301) |
| `countries.php` | `/frontend/window/countries-list.php` |
| `bank_rekvesit.php` | `/frontend/window/banks_rekvesit.php` |
| `office.php` (no slug) | `/frontend/window/offices.php` |
| Guest cabinet pages | `/frontend/window/login.php` (302) |

## Full results

| # | Path | Status | Final URL | Bytes | ms | Redirects | Flags |
|---|------|--------|-----------|-------|----|-----------|-------|
| 1 | `/frontend/index.php` | 200 | `/frontend/index.php` | 646998 | 646 | 0 |  |
| 2 | `/frontend/` | 200 | `/frontend/` | 646947 | 577 | 0 |  |
| 3 | `/frontend` | 200 | `http://travel63test.ru/frontend/` | 646947 | 729 | 1 | http:// |
| 4 | `/frontend/window/countries/abkhazia.php` | 200 | `/frontend/window/countries/abkhazia.php` | 228618 | 442 | 0 |  |
| 5 | `/frontend/window/countries/armenia.php` | 200 | `/frontend/window/countries/armenia.php` | 229916 | 469 | 0 |  |
| 6 | `/frontend/window/countries/bahrain.php` | 200 | `/frontend/window/countries/bahrain.php` | 231142 | 444 | 0 |  |
| 7 | `/frontend/window/countries/china.php` | 200 | `/frontend/window/countries/china.php` | 229756 | 464 | 0 |  |
| 8 | `/frontend/window/countries/country.php` | 404 | `/frontend/window/countries/country.php` | 83460 | 521 | 0 | non-200 |
| 9 | `/frontend/window/countries/cuba.php` | 200 | `/frontend/window/countries/cuba.php` | 229696 | 600 | 0 |  |
| 10 | `/frontend/window/countries/egypt.php` | 200 | `/frontend/window/countries/egypt.php` | 230072 | 453 | 0 |  |
| 11 | `/frontend/window/countries/india.php` | 200 | `/frontend/window/countries/india.php` | 231115 | 460 | 0 |  |
| 12 | `/frontend/window/countries/indonesia.php` | 200 | `/frontend/window/countries/indonesia.php` | 230366 | 533 | 0 |  |
| 13 | `/frontend/window/countries/jordan.php` | 200 | `/frontend/window/countries/jordan.php` | 230135 | 427 | 0 |  |
| 14 | `/frontend/window/countries/maldives.php` | 200 | `/frontend/window/countries/maldives.php` | 230370 | 421 | 0 |  |
| 15 | `/frontend/window/countries/mauritius.php` | 200 | `/frontend/window/countries/mauritius.php` | 231574 | 637 | 0 |  |
| 16 | `/frontend/window/countries/montenegro.php` | 200 | `/frontend/window/countries/montenegro.php` | 231956 | 523 | 0 |  |
| 17 | `/frontend/window/countries/oman.php` | 200 | `/frontend/window/countries/oman.php` | 230838 | 469 | 0 |  |
| 18 | `/frontend/window/countries/philippines.php` | 200 | `/frontend/window/countries/philippines.php` | 230310 | 486 | 0 |  |
| 19 | `/frontend/window/countries/qatar.php` | 200 | `/frontend/window/countries/qatar.php` | 229714 | 446 | 0 |  |
| 20 | `/frontend/window/countries/russia.php` | 200 | `/frontend/window/countries/russia.php` | 230062 | 454 | 0 |  |
| 21 | `/frontend/window/countries/seychelles.php` | 200 | `/frontend/window/countries/seychelles.php` | 230375 | 451 | 0 |  |
| 22 | `/frontend/window/countries/sri-lanka.php` | 200 | `/frontend/window/countries/sri-lanka.php` | 230177 | 478 | 0 |  |
| 23 | `/frontend/window/countries/tanzania.php` | 200 | `/frontend/window/countries/tanzania.php` | 230183 | 520 | 0 |  |
| 24 | `/frontend/window/countries/thailand.php` | 200 | `/frontend/window/countries/thailand.php` | 229070 | 541 | 0 |  |
| 25 | `/frontend/window/countries/tunisia.php` | 200 | `/frontend/window/countries/tunisia.php` | 229640 | 449 | 0 |  |
| 26 | `/frontend/window/countries/turkey.php` | 200 | `/frontend/window/countries/turkey.php` | 231806 | 447 | 0 |  |
| 27 | `/frontend/window/countries/uae.php` | 200 | `/frontend/window/countries/uae.php` | 229738 | 446 | 0 |  |
| 28 | `/frontend/window/countries/venezuela.php` | 200 | `/frontend/window/countries/venezuela.php` | 230246 | 500 | 0 |  |
| 29 | `/frontend/window/countries/vietnam.php` | 200 | `/frontend/window/countries/vietnam.php` | 230190 | 425 | 0 |  |
| 30 | `/frontend/window/hotels/hotel-detail.php` | 200 | `/frontend/window/hotels/hotel-detail.php` | 58134 | 351 | 0 |  |
| 31 | `/frontend/window/hotels/tv-hotel-detail.php` | 200 | `/frontend/window/hotels/tv-hotel-detail.php` | 76604 | 324 | 0 |  |
| 32 | `/frontend/window/offices/moscow-anex.php` | 200 | `/frontend/window/offices/office.php?slug=moscow-anex` | 70836 | 417 | 1 |  |
| 33 | `/frontend/window/offices/moscow-coral-elite.php` | 200 | `/frontend/window/offices/office.php?slug=moscow-coral-elite` | 72943 | 373 | 1 |  |
| 34 | `/frontend/window/offices/moscow-offices.php` | 200 | `/frontend/window/offices/moscow-offices.php` | 71842 | 323 | 0 |  |
| 35 | `/frontend/window/offices/moscow.php` | 200 | `/frontend/window/offices/office.php?slug=moscow-coral-elite` | 72943 | 401 | 1 |  |
| 36 | `/frontend/window/offices/office.php` | 200 | `/frontend/window/offices.php` | 93964 | 384 | 1 |  |
| 37 | `/frontend/window/offices/samara-anex-apelsin.php` | 200 | `http://travel63test.ru/frontend/window/offices/samara-offices.php` | 82883 | 455 | 1 | http:// |
| 38 | `/frontend/window/offices/samara-anex-moskovskoe.php` | 200 | `/frontend/window/offices/office.php?slug=samara-anex-moskovskoe` | 73928 | 386 | 1 |  |
| 39 | `/frontend/window/offices/samara-anex.php` | 200 | `/frontend/window/offices/office.php?slug=samara-funsun-gudok` | 72097 | 410 | 1 |  |
| 40 | `/frontend/window/offices/samara-coral-elite.php` | 200 | `/frontend/window/offices/samara-offices.php` | 82885 | 432 | 1 |  |
| 41 | `/frontend/window/offices/samara-coral.php` | 200 | `/frontend/window/offices/office.php?slug=samara-coral` | 74099 | 412 | 1 |  |
| 42 | `/frontend/window/offices/samara-funsun-gudok.php` | 200 | `/frontend/window/offices/office.php?slug=samara-funsun-gudok` | 72097 | 436 | 1 |  |
| 43 | `/frontend/window/offices/samara-funsun.php` | 200 | `/frontend/window/offices/office.php?slug=samara-funsun` | 75536 | 510 | 1 |  |
| 44 | `/frontend/window/offices/samara-offices.php` | 200 | `/frontend/window/offices/samara-offices.php` | 82885 | 418 | 0 |  |
| 45 | `/frontend/window/offices/samara.php` | 200 | `/frontend/window/offices/samara.php` | 86195 | 320 | 0 |  |
| 46 | `/frontend/window/404.php` | 404 | `/frontend/window/404.php` | 65044 | 392 | 0 | non-200 |
| 47 | `/frontend/window/500.php` | 500 | `/frontend/window/500.php` | 30581 | 346 | 0 | non-200 |
| 48 | `/frontend/window/about.php` | 200 | `/frontend/window/about.php` | 94560 | 329 | 0 |  |
| 49 | `/frontend/window/app-payment-redirect.php` | 200 | `/frontend/window/app-payment-redirect.php` | 30052 | 293 | 0 |  |
| 50 | `/frontend/window/banks_rekvesit.php` | 200 | `/frontend/window/banks_rekvesit.php` | 63870 | 394 | 0 |  |
| 51 | `/frontend/window/bank_rekvesit.php` | 200 | `/frontend/window/banks_rekvesit.php` | 63870 | 395 | 1 |  |
| 52 | `/frontend/window/consent.php` | 200 | `/frontend/window/consent.php` | 65549 | 321 | 0 |  |
| 53 | `/frontend/window/contacts.php` | 200 | `/frontend/window/about.php#contact` | 94560 | 387 | 1 |  |
| 54 | `/frontend/window/countries-list.php` | 200 | `/frontend/window/countries-list.php` | 112126 | 476 | 0 |  |
| 55 | `/frontend/window/countries.php` | 200 | `/frontend/window/countries-list.php` | 112126 | 531 | 1 |  |
| 56 | `/frontend/window/dashboard.php` | 200 | `/frontend/window/login.php` | 64292 | 384 | 1 |  |
| 57 | `/frontend/window/debug.php` | 404 | `/frontend/window/debug.php` | 0 | 228 | 0 | non-200, tiny |
| 58 | `/frontend/window/edit-user-data.php` | 200 | `/frontend/window/login.php` | 64292 | 367 | 1 |  |
| 59 | `/frontend/window/employee-photo.php` | 404 | `/frontend/window/employee-photo.php` | 0 | 240 | 0 | non-200, tiny |
| 60 | `/frontend/window/for-operators.php` | 200 | `/frontend/window/for-operators.php` | 58891 | 330 | 0 |  |
| 61 | `/frontend/window/forgot-password.php` | 200 | `/frontend/window/forgot-password.php` | 61717 | 322 | 0 |  |
| 62 | `/frontend/window/login-desktop.php` | 200 | `/frontend/window/login-desktop.php` | 63089 | 326 | 0 |  |
| 63 | `/frontend/window/login.html` | 200 | `/frontend/window/login.html` | 527 | 211 | 0 | tiny |
| 64 | `/frontend/window/login.php` | 200 | `/frontend/window/login.php` | 64292 | 378 | 0 |  |
| 65 | `/frontend/window/offices.php` | 200 | `/frontend/window/offices.php` | 93964 | 321 | 0 |  |
| 66 | `/frontend/window/passport-data.php` | 200 | `/frontend/window/login.php` | 64292 | 411 | 1 |  |
| 67 | `/frontend/window/payment-fail.php` | 200 | `/frontend/window/payment-fail.php` | 31096 | 268 | 0 |  |
| 68 | `/frontend/window/payment-form-example.php` | 200 | `/frontend/window/payment-form-example.php` | 30705 | 278 | 0 |  |
| 69 | `/frontend/window/payment-success.php` | 200 | `/frontend/window/payment-success.php` | 31026 | 290 | 0 |  |
| 70 | `/frontend/window/payment.php` | 200 | `https://travelhub63.ru/frontend/window/payment-fail.php?error=amount` | 25785 | 534 | 1 |  |
| 71 | `/frontend/window/personal-info.php` | 200 | `/frontend/window/login.php` | 64292 | 386 | 1 |  |
| 72 | `/frontend/window/popular-hotels.php` | 200 | `/frontend/window/popular-hotels.php` | 79640 | 345 | 0 |  |
| 73 | `/frontend/window/privacy.php` | 200 | `/frontend/window/privacy.php` | 69095 | 317 | 0 |  |
| 74 | `/frontend/window/profile.php` | 200 | `/frontend/window/login.php` | 64292 | 447 | 1 |  |
| 75 | `/frontend/window/promotions.php` | 200 | `/frontend/window/promotions.php` | 91386 | 393 | 0 |  |
| 76 | `/frontend/window/registration-desktop.php` | 200 | `/frontend/window/registration-desktop.php` | 71500 | 325 | 0 |  |
| 77 | `/frontend/window/registration.html` | 200 | `/frontend/window/registration.html` | 594 | 284 | 0 | tiny |
| 78 | `/frontend/window/registration.php` | 200 | `/frontend/window/registration-desktop.php` | 71500 | 444 | 1 |  |
| 79 | `/frontend/window/reset-password.php` | 200 | `/frontend/window/reset-password.php` | 60357 | 314 | 0 |  |
| 80 | `/frontend/window/services.php` | 200 | `/frontend/window/services.php` | 70281 | 332 | 0 |  |
| 81 | `/frontend/window/simple.php` | 200 | `/frontend/window/simple.php` | 36 | 220 | 0 | tiny |
| 82 | `/frontend/window/terms.php` | 200 | `/frontend/window/terms.php` | 65307 | 330 | 0 |  |
| 83 | `/frontend/window/tour-calendar.php` | 200 | `/frontend/window/tour-calendar.php` | 63695 | 401 | 0 |  |
| 84 | `/frontend/window/tour-detail.php` | 200 | `/frontend/window/tour-detail.php` | 119554 | 375 | 0 |  |
| 85 | `/frontend/window/turkey-vip-hotels.php` | 200 | `/frontend/window/turkey-vip-hotels.php` | 70614 | 330 | 0 |  |
| 86 | `/frontend/window/video-tutorials.php` | 200 | `/frontend/window/video-tutorials.php` | 76873 | 350 | 0 |  |
| 87 | `/frontend/window/popular-hotels.php?country=4` | 200 | `/frontend/window/popular-hotels.php?country=4` | 79640 | 324 | 0 |  |
| 88 | `/frontend/window/popular-hotels.php?country=1` | 200 | `/frontend/window/popular-hotels.php?country=1` | 79640 | 322 | 0 |  |
| 89 | `/frontend/window/popular-hotels.php?country=9` | 200 | `/frontend/window/popular-hotels.php?country=9` | 79640 | 370 | 0 |  |
| 90 | `/frontend/window/promotions.php?choose_departure=1` | 200 | `/frontend/window/promotions.php?choose_departure=1` | 91386 | 480 | 0 |  |
| 91 | `/frontend/window/tour-calendar.php?departureId=2` | 200 | `/frontend/window/tour-calendar.php?departureId=2` | 63695 | 325 | 0 |  |
| 92 | `/frontend/frontend/index.php` | 404 | `/frontend/frontend/index.php` | 65044 | 342 | 0 | non-200, double-/frontend/ |

## Coverage notes

- Included all `frontend/window/**/*.php` and `*.html` on disk + 3 main entries + QA query variants / double-`frontend` probe = **92** unique request paths.
- Excluded: `frontend/search-legacy/*` snapshots, `frontend/guest-template.php` (include template), backups.
- Query variants from QA: `popular-hotels?country=1|4|9`, `promotions?choose_departure=1`, `tour-calendar?departureId=2` — all **200**.
- No login attempted; cabinet pages observed only as guest redirects.

## Verdict

**81/92** HTTP-status-clean (no non-200 / final-http / tiny / double-URL flags). Core content pages (home ~647 KB, countries, offices, hotels, promotions, legal, auth `.php`) are **200**.

**Must-fix:** B1 home canonical; **country path-doubled canonicals**; absolute `http://` redirects (`/frontend`, `samara-anex-apelsin.php`); B2 auth `.html` stubs; dead `debug.php` / `employee-photo.php`; `payment.php` jumping to prod.

_Generated automatically 2026-08-27. Related checklist: `docs/QA-Danik-travel63test.md`. Raw metrics: `docs/_smoke-raw-2026-08-27.json`._
