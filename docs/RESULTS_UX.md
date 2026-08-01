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
| ≤767.98 | Sheets, 1-col cards |
| ≤1023.98 | Нижняя плашка = **пост-фильтры** (Фильтры + chips + компакт MAX/Заявка); полный sidebar в sheet |
| ≥1024 | Классическая плашка Call/MAX/Заявка; sidebar слева |

## Файлы

- `frontend/css/th-results-ux.css` — dock, sheet, score badge, aspects
- `frontend/js/th-tour-card.js` — badge на фото + «Отлично» + pills
- `frontend/js/th-tour-post-filters.js` — sync chips sidebar ↔ sticky dock
- `frontend/index.php` — `#th-results-sticky-lead--dock`, `#th-results-pf-sheet`

## Mobile dock (≤1023)

При показе выдачи нижняя плашка меняется:

`[Фильтры] [Дешевле|Отзывы|Цена+оценка] [С отзывами|от 8…]` + компакт `MAX` / `Заявка`

Кнопка **Фильтры** открывает sheet с полным sidebar (звёзды, питание, бюджет, курорты, оценки).

Без `hotel.tophotels` в JSON guest-chips скрыты (нужен sync / fixture).
