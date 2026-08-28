# QA Full Smoke — travel63test.ru

**Date:** 2026-08-27  
**Base URL:** https://travel63test.ru  
**Method:** Playwright Chromium 1.62.1 headless  
**Viewports:** desktop 1280×800, mobile 390×844 (iPhone UA)  
**Screenshots:** `docs/qa-full-smoke-2026-08-27/` (56 PNG)  
**Raw data:** `docs/qa-full-smoke-2026-08-27/findings.json`, `extras.json`

---

## Executive summary

| Metric | Result |
|--------|--------|
| Pages probed (×2 viewports) | 17 core + tour-detail + VIP |
| Findings | **8** unique (4 High, 3 Med, 1 Low) |
| PASS items | **55** |
| Search flow | Samara → Thailand → dates → search → prices from **195 485 ₽** |
| Console errors (search) | 0 breaking errors |
| Deploy verdict | **Mixed:** homepage/country **SEO fixes landed** (`https`, no `/frontend/frontend/`). **UX bugs from 2026-08-26 QA still open** (mobile nav clip, sticky bar). `tour-detail` canonical still points to **prod** |

---

## Top 10 findings (ranked)

| # | Sev | Issue | Where |
|---|-----|-------|-------|
| 1 | **High** | Mobile hamburger menu clips bottom: `overflow-y: hidden` — «Пользовательское соглашение», phone, **«Войти»**, **«Регистрация»** below fold, no scroll | 390×844 home nav |
| 2 | **High** | Sticky lead bar («Позвонить / MAX / Чат») overlaps auth form submit buttons on login + registration (desktop & mobile) | `login.php`, `registration*.php` |
| 3 | **Med** | `tour-detail.php` canonical → **prod** `https://travelhub63.ru/...` while page served from test host | Search → tour card |
| 4 | **Med** | `vip-tours.php` → **404**; working VIP page is `turkey-vip-hotels.php` (200) | VIP nav guess |
| 5 | **Med** | Footer/header sampled links: **MAX** messenger link fails navigation probe | Footer sticky bar |
| 6 | **Low** | Mobile home: «Найти» / «Фильтры» may sit under bottom sticky UI on first screen | 390×844 home |
| 7 | **Info** | `/frontend/frontend/index.php` → 404 (confirms old bad canonical URL is dead) | SEO regression guard |
| 8 | **Info** | Legacy `login.html` / `registration.html` now redirect to `.php` (200 final) | Auth stubs **fixed** vs 2026-08-27 morning |
| 9 | **PASS** | All 6 home search modals open on desktop **and** mobile (Откуда, Куда, Даты, Ночи, Туристы, Фильтры) | Home search |
| 10 | **PASS** | Home/country canonical now `https://travel63test.ru/...` — **no** double `/frontend/` | SEO B1 appears fixed on test |

---

## Severity table

| ID | Severity | Where | Steps | Expected | Actual | Screenshot |
|----|----------|-------|-------|----------|--------|------------|
| TH-001 | **High** | Mobile hamburger (390×844) | Open burger on home | All items reachable via scroll or fit in panel | `overflow-y: hidden`; clipped: Пользовательское соглашение, +7 (846) 254-16-56, Войти, Регистрация | `mobile-nav-open.png` |
| TH-002 | **High** | Login / Registration forms (desktop + mobile) | Open login.php, registration.php, *-desktop variants | Submit CTA fully visible above sticky bar | Submit button geometry overlaps sticky lead bar on all 4 form URLs × 2 viewports | `desktop-login.png`, `mobile-registration-desktop.png` |
| TH-003 | **Med** | tour-detail.php | Search Samara→Thailand → open result | Canonical on test host `https://travel63test.ru/...` | `canonical: https://travelhub63.ru/frontend/window/tour-detail.php?tour_id=43267624401148` | `desktop-tour-detail.png` |
| TH-004 | **Med** | VIP pages | GET `/frontend/window/vip-tours.php` | 200 or redirect to VIP listing | HTTP 404 «Страница не найдена» | `desktop-vip.png` |
| TH-005 | **Med** | Header/footer nav sample | Follow first 12 unique header+footer links | All 200/301 | MAX messenger link navigation failed (external/deep-link) | — |
| TH-006 | **Low** | Mobile home sticky vs CTAs | First viewport on home | «Найти» and «Фильтры» not obscured | Bottom sticky UI competes with primary search CTAs | `mobile-home-sticky-check.png` |
| TH-007 | **Info** | SEO regression | Inspect home + turkey canonical | `https://`, single `/frontend/` | **PASS** — home `https://travel63test.ru/frontend/index.php`, turkey `.../countries/turkey.php` | `desktop-home.png` |
| TH-008 | **Info** | Legacy auth stubs | GET login.html, registration.html | 301 → .php or tiny stub | **PASS** — final URLs `login.php`, `registration-desktop.php` | — |

---

## PASS checklist

### Pages (HTTP 200, no page-level h-scroll)

- [x] Home `/frontend/index.php` — desktop + mobile
- [x] Countries list, Turkey landing, Popular hotels, Promotions
- [x] Tour calendar (month names visible — 2 headings)
- [x] Services, About, Contacts
- [x] Offices hub + Samara + Moscow
- [x] Login + Registration (+ desktop variants)
- [x] Turkey VIP hotels `turkey-vip-hotels.php` — 200
- [x] Tour detail reachable from search — 200, price 195 485 ₽

### Home search modals (desktop + mobile)

- [x] Departure city picker (`desktop-modal-departure.png`, `mobile-modal-departure.png`)
- [x] Country/destination picker
- [x] Dates calendar (flatpickr)
- [x] Nights modal
- [x] Tourists modal
- [x] Filters modal + charter/direct toggles (`desktop-filters-toggles.png`)

### Logic smoke

- [x] Tours search: Samara + Thailand + date range + nights → results with prices > 0 (от 202 196 ₽ … 206 263 ₽)
- [x] Hotels tab clickable; fields «Без перелёта», «Даты заезда» visible
- [x] Calendar page loads with month labels
- [x] No JS console errors during search flow
- [x] Legacy `.html` auth stubs redirect to `.php`
- [x] `/frontend/frontend/index.php` → 404 (bad historical URL dead)

### SEO

- [x] Home canonical: `https://travel63test.ru/frontend/index.php` (no double `/frontend/`)
- [x] Turkey canonical: `https://travel63test.ru/frontend/window/countries/turkey.php`
- [x] No in-page `frontend/frontend/` links on home

---

## Deploy freshness note

Compared to **2026-08-27 morning** auto-smoke (`QA-auto-smoke-2026-08-27.md`):

| Area | Morning (old) | This run (evening) |
|------|---------------|-------------------|
| Home canonical | `http://…/frontend/frontend/index.php` | **`https://…/frontend/index.php`** ✅ |
| Country canonical | doubled path + http | **clean https single path** ✅ |
| login.html stub | 527 B tiny page | **redirects to login.php** ✅ |
| Mobile nav clip | High (#1 prior QA) | **Still reproduces** ❌ |
| Sticky bar vs forms | High (#3 prior QA) | **Still reproduces** ❌ |
| tour-detail canonical | prod host | **Still prod** ❌ |

**Conclusion:** Test server received **recent SEO/auth fixes** but **UI adaptation bugs remain unfixed**. Not a fully stale deploy.

---

## Search flow evidence

```
Samara → Таиланд → 27.08–30.08 → 6–9 ночей → 2 взрослых → Найти
Prices: от 202 196 ₽, 203 721 ₽, 205 552 ₽, 206 263 ₽
Tour detail: LAMAI APARTMENT, 195 485 ₽, 6 nights, Phuket
Console errors: none
```

Screenshots: `desktop-search-filled.png`, `desktop-search-results.png`, `desktop-tour-detail.png`

---

## Legacy redirects

| URL | Status | Final |
|-----|--------|-------|
| `login.html` | 200 | `…/login.php` |
| `registration.html` | 200 | `…/registration-desktop.php` |
| `/frontend/frontend/index.php` | 404 | — |
| `vip-tours.php` | 404 | — |
| `turkey-vip-hotels.php` | 200 | VIP Отели Турции |

---

## Page status matrix

| Viewport | Page | HTTP | H-scroll | Notes |
|----------|------|------|----------|-------|
| desktop | home | 200 | no | modals OK |
| desktop | countries-list | 200 | no | |
| desktop | country-turkey | 200 | no | canonical OK |
| desktop | popular-hotels | 200 | no | ~8s load wait |
| desktop | promotions | 200 | no | |
| desktop | tour-calendar | 200 | no | months visible |
| desktop | services | 200 | no | |
| desktop | about | 200 | no | |
| desktop | offices / samara / moscow | 200 | no | |
| desktop | login / registration* | 200 | no | sticky overlap |
| desktop | contacts | 200 | no | |
| desktop | vip-tours | 404 | no | use turkey-vip-hotels |
| mobile | *(same set)* | 200* | no | *vip 404; nav clip |

---

## Screenshot index (key)

| File | Description |
|------|-------------|
| `desktop-home.png` / `mobile-home.png` | Home first paint |
| `desktop-modal-*.png` / `mobile-modal-*.png` | All 6 search modals |
| `mobile-nav-open.png` | Hamburger clip bug |
| `desktop-search-results.png` | Search results |
| `desktop-tour-detail.png` | Tour detail from search |
| `desktop-turkey-vip-hotels.png` | VIP hotels page |
| `desktop-login.png` / `mobile-registration-desktop.png` | Sticky bar overlap |
| `mobile-home-sticky-check.png` | Mobile CTA vs sticky |

---

## Gaps / not tested

- Real form submit (login/register/booking) — intentionally not submitted
- Logged-in header (Профиль/Админ) — guest session only
- Native Safari/Yandex/Edge — Chromium only
- Full WCAG contrast audit

---

*Generated by Playwright smoke runner `docs/qa-full-smoke-2026-08-27/run-smoke.cjs`*
