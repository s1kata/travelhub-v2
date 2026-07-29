# Results UX — Tourvisor + TopHotels

Единый дизайн выдачи по паттернам Booking / Level.Travel / Onlinetours, без ломки адаптации сайта.

## Product principles

1. **Wizard не трогаем** — 5 шагов coral как есть (адаптация 768 sheets уже ок).
2. **Решение на карточке:** фото → trust (оценка) → имя → аспекты → цена → CTA.
3. **Score + число отзывов + словесная метка** («Отлично») — как Booking guest review.
4. **Mobile first filters:** sticky chips над выдачей (&lt;1024 сайдбар скрыт).
5. **Desktop:** sticky sidebar ≥1024 + компактный sort-seg.
6. Цвета бренда: `#1A1A40` / `#5DA9A4` / `#FF6B6B`.

## Breakpoints (не ломать)

| Брейк | Поведение |
|-------|-----------|
| ≤767.98 | Sheets, 1-col cards, sticky control bar |
| 768–1023 | 2-col cards, control bar с guest-chips |
| ≥1024 | Sidebar + 2-col в layout; guest-chips только в sidebar |

## Файлы

- `frontend/css/th-results-ux.css` — control bar + score badge + aspects
- `frontend/js/th-tour-card.js` — badge на фото + «Отлично» + pills
- `frontend/js/th-tour-post-filters.js` — sync chips sidebar ↔ mobile
- `frontend/index.php` — `#tv-results-control`

Без `hotel.tophotels` в JSON UI скрыт (нужен sync / fixture).
