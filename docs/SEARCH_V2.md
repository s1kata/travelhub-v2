# Search UI

По умолчанию на главной — **исходный coral wizard** (5 шагов).

Опциональный эксперимент v2:

```text
?search=v2
# или TH_SEARCH_UI=v2
```

Снапшот до эксперимента: [`frontend/search-legacy/`](../frontend/search-legacy/README.md).

## TopHotels в выдаче (не wizard)

Цены и поиск — Tourvisor. Оценки гостей — слой TopHotels в цветах сайта (`#1A1A40` / `#5DA9A4`):

- карточка: блок «Оценки гостей» + аспекты (еда / сервис / расположение);
- фильтры: «Только с отзывами», от 8.0 / 8.5 / 9.0;
- сортировка: «По отзывам гостей», «Цена + оценка».

Без `hotel.tophotels` в JSON блок скрыт. Включение — см. [TOPHOTELS.md](./TOPHOTELS.md).
