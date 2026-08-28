# TravelHub UI / adaptation QA — 2026-08-27

> **Стенд:** https://travel63test.ru  
> **Аккаунт (сессия):** gememix76142@gmail.com (пароль в отчёт не включался)  
> **Инструменты:** Cursor IDE Browser MCP (Chromium, сессия logged-in) + Playwright Chromium headless (guest), viewports **1280×800** и **390×844** (`Emulation.setDeviceMetricsOverride` / Playwright viewport)  
> **Ограничение:** нет реальных приложений Yandex Browser / Safari / Edge — только Chromium + эмуляция viewport. Mid-session Cursor Browser MCP отвалился; оставшийся crawl продолжен Playwright’ом без cookie сессии.

Скриншоты: `docs/qa-screenshots-2026-08-27/`  
Машиночитаемые замеры: `findings.json`, `deep.json`.

---

## Краткий вердикт

Адаптация ключевых публичных страниц в целом живая (200 OK, без горизонтального скролла страницы). Поиск на главной (даты / ночи / туристы / фильтры / вкладка «Отели») на desktop открывается. **Критичные UX-баги:** мобильное меню обрезает низ (включая «Войти» / «Регистрация») без скролла; при logged-in шапке логотип уезжает в ellipsis; sticky-бар «Позвонить / MAX / Чат» перекрывает низ форм.

---

## Severity-ranked bugs

### Critical / High

| # | Severity | Где | Проблема | Доказательство |
|---|----------|-----|----------|----------------|
| **1** | **High** | Mobile header nav (390×844) | Панель `.site-header-mobile-panel` с `overflow-y: hidden`: низ меню **недоступен**. Обрезаются «Политика…», «Пользовательское соглашение», телефон, **«Войти»**, **«Регистрация»**. | Замер: ссылки с `top` 817–1187 при `vh=844`, `inView=false`; `canScroll=false`. Скрин: `mobile-home-after-toggle.png` |
| **2** | **High** | Desktop header, **logged-in** | При «Профиль + Менеджерам + Админ + Выход» логотип `.header__logo` сжимается (`scrollWidth` 114 → `clientWidth` ~67), текст «Travel Hub» → ellipsis («Tra…»). У гостя на 1280 логотип полный. | Cursor MCP CDP: `logo sw=114 cw=67`; скрин `qa-home-desktop-1280.png` |
| **3** | **High** | Registration (+ др. длинные формы) | Sticky bar «Позвонить / MAX / Чат» сидит внизу viewport; кнопка «Зарегистрироваться» уходит под fold (`top≈862` при `vh=800`). Форма визуально «обрезана» баром. | `desktop-registration.png`; Playwright overlap check |

### Medium

| # | Severity | Где | Проблема | Доказательство |
|---|----------|-----|----------|----------------|
| **4** | Medium | Structured data / URL | В HTML есть битые URL `frontend/frontend/index.php` (+ `#breadcrumb`, `?search=…`) → **404** «Страница не найдена». | grep HTML; HTTP 404 |
| **5** | Medium | Legacy paths | 404: `/frontend/window/hotels.php`, `register.php`, `cabinet.php`, `passport.php`. Актуальные: `popular-hotels.php`, `registration-desktop.php`, `profile.php` / `dashboard.php`, `passport-data.php`. | HTTP HEAD/GET |
| **6** | Medium | Mobile home | Кнопка «Фильтры» частично перекрывается нижним sticky/chat UI при первом экране. | `mobile-home.png` |
| **7** | Medium | `popular-hotels.php` | Долгий skeleton + «Загрузка…» (несколько секунд); на медленной сети выглядит как «зависло». После ~5–8 с карточки появляются. | `desktop-hotels.png`; retest после 8s → 96 отелей |

### Low

| # | Severity | Где | Проблема | Доказательство |
|---|----------|-----|----------|----------------|
| **8** | Low | Mobile calendar | Текст в ячейках («от 309к Фукуок») очень мелкий; легенда у края экрана; низкий контраст teal на сером. | `mobile-calendar.png` |
| **9** | Low | Mobile nav labels | Секции «ЕЩЁ» / «ДОКУМЕНТЫ» — низкий контраст на тёмном фоне. | `mobile-home-after-toggle.png` |
| **10** | Low | Home nights modal (desktop 800h) | Внутренний scrollbar у модалки «Сколько ночей?» — контент чуть выше панели. | `qa-home-nights-modal-desktop.png` |
| **11** | Low | Home (first paint) | Краткий flash «Загрузка…» в Откуда/Куда до гидрации списков. | Cursor first paint |

---

## Чеклист страниц

| Страница | URL | Desktop 1280 | Mobile 390 | Заметки |
|----------|-----|--------------|------------|---------|
| Главная | `/frontend/index.php` | OK + modals | OK, nav bug #1 | Logged-in: Профиль/Выход (не «Войти») — Cursor session |
| Акции | `.../promotions.php` | 200 | 200 | Layout OK |
| Страны | `.../countries-list.php` | 200 | 200 | Без page h-scroll |
| Отели | `.../popular-hotels.php` | 200 | 200 | Медленная загрузка #7 |
| Офисы | `.../offices.php` | 200 | 200 | OK |
| Календарь дат | `.../tour-calendar.php` | 200 | 200 | Mobile readability #8 |
| Услуги | `.../services.php` | 200 | 200 | OK |
| О нас | `.../about.php` | 200 | 200 | OK |
| Контакты | `.../contacts.php` | 200 | 200 | OK |
| Профиль / кабинет | `.../profile.php` | Login gate (guest) | Login gate | Logged-in: кабинет + карточка паспорта → `passport-data.php` (код/ссылки) |
| Паспорт | `.../passport-data.php` | Login gate (guest) | Login gate | Не `passport.php` (404) |
| Регистрация | `.../registration-desktop.php` | Layout + bar #3 | 200, no h-scroll | Новый пользователь **не** создавался |

### Home search / modals (desktop)

| Контрол | Результат |
|---------|-----------|
| Даты вылета | Модалка «Когда вылетаете?» + flatpickr range — OK |
| Ночей | «Сколько ночей?» — OK (minor scrollbar) |
| Туристы | «Туристы» +/- / ребёнок — OK |
| Фильтры | Expand: питание / курорт / звёзды / чартер / прямой — OK |
| Вкладка Отели | Selected; поля «Без перелёта», «Даты заезда» — OK |

### Header auth

| Состояние | Ожидание | Факт |
|-----------|----------|------|
| Guest | «Войти» + «Регистрация» | OK (Playwright) |
| Logged-in (gememix…) | «Профиль» (+ роли), не «Войти» | OK в Cursor MCP: Профиль / Менеджерам / Админ / Выход |
| Mobile guest | Auth в burger | **Сломано** — пункты ниже fold (#1) |

---

## Что не тестировалось / gaps

- Реальная отправка лидов/заявок — **намеренно не спамили**; формы только visually.
- Создание нового пользователя на регистрации — не делали.
- Полный logged-in обход profile/passport UI после падения Browser MCP — частично (шапка + URL mapping в коде).
- Нативные Yandex / Safari / Edge.
- Контраст WCAG formal audit (только визуальные заметки).

---

## Рекомендации (коротко)

1. Mobile panel: `overflow-y: auto` (+ safe-area), чтобы «Войти/Регистрация» и документы были достижимы.  
2. Logged-in header: свернуть вторичные кнопки (Админ/Менеджерам) в меню или уменьшить padding — не резать логотип.  
3. Sticky lead-bar: `padding-bottom` на `body` / формах ≥ высоты бара.  
4. Убрать/починить `frontend/frontend/...` в JSON-LD/schema; 301/redirect со старых `hotels.php` / `register.php` / `passport.php`.  
5. Hotels: скелетон + явный empty/error timeout, если API > N сек.
