# Self-QA retest round 2 — TravelHub website

> **Дата:** 2026-08-17  
> **Стенд:** https://travel63test.ru  
> **Кто:** Ilyas (self-QA после деплоя Danik round2 fixes)  
> **Браузер:** Cursor IDE browser (Chromium), hard refresh mindset  
> **Чеклист:** `docs/QA-Danik-retest-round2-2026-08-17.md`

---

## Deploy status

**Y — deploy confirmed working.** Главная, акции, страны, офисы, регистрация, политика открываются без 5xx. Round2 фиксы на стенде.

---

## Blocker retest (#6, #7, #10, #11)

| # | Что | Статус | Комментарий |
|---|-----|--------|-------------|
| **6** | Акции → тур → «Уточнить цену», кривые/пустые данные | ✅ PASS | `promotions.php?countryId=4` → «Уточнить цену» на ARES DREAM. `John Müller` → «Укажите ФИО русскими буквами» (`.th-sf-msg.th-error`, `aria-invalid`). `+7 111…` → «Укажите корректный мобильный телефон РФ (+7 9XX…)». Success/«заявка принята» нет, модалка остаётся. |
| **7** | Страны → «Все страны», ISO внутри карточки | ✅ PASS | `countries-list.php?nocache=1`. Desktop + viewport 390px: бейджи TR/EG/VN/AM/BH внутри скругления. JS overflow-check: 20/20 badges — без выхода за bounds. |
| **10** | Офисы → «Заявка в этот офис», некорректные данные | ✅ PASS | `offices.php` → Fun&Sun Парк Хаус. `John Smith` + `+7 111…` → «Укажите ФИО русскими буквами» в модалке. «Заявка принята» нет. Шапка белая, поле «ФИО *». |
| **11** | Регистрация, кривые данные | ✅ PASS | `registration-desktop.php`. Ошибки **по полям**: ФИО, email, пароль (≥6), телефон. Баннер «временная ошибка сервера» **не** показывался. |

**Итог blockers:** 4/4 ✅

---

## Smoke (регрессии round 1)

| # | Что | Статус | Комментарий |
|---|-----|--------|-------------|
| 3 | Подпись «ФИО» в формах | ✅ | Регистрация: «ФИО *»; модалка «Уточнить цену»: «ФИО *»; офисная модалка: «ФИО *». |
| 12 | Футер → Политика конфиденциальности | ✅ | `privacy.php` — читаемый текст, inline-ссылки на согласие/политику, `hello@travelhub63.ru`. |
| 13 | Футер → Пользовательское соглашение | ✅ | Ссылка в футере ведёт на `terms.php` (не проверялся body — только доступность из футера). |

---

## Скриншоты / заметки

| Тест | Файл / заметка |
|------|----------------|
| #6 FIO error | `page-2026-08-17T08-16-00-073Z.png` — красная рамка ФИО, `.th-sf-msg` |
| #6 phone error | DOM: «Укажите корректный мобильный телефон РФ (+7 9XX…)» |
| #7 desktop | `qa-countries-desktop.png` — ISO TR/AE/VN внутри карточек |
| #7 mobile 390px | `qa-countries-all-mobile.png` — AM/BH внутри карточек «Все страны» |
| #10 office modal | `page-2026-08-17T08-18-06-229Z.png` — модалка офиса, ошибка ФИО |
| #11 registration | `page-2026-08-17T08-18-41-017Z.png` — 4 field-level errors |

Скриншоты сохранены Cursor browser MCP в `%LOCALAPPDATA%\Temp\cursor\screenshots\`.

---

## Замечания (не blockers)

- На `promotions.php` кнопка «Уточнить цену» перекрывается sticky lead-bar — клик через overlay; UX для Даника не критично, валидация работает.
- На странах при первом заходе всплывает lead-popup «Не нашли тур?» — закрывается, на ISO не влияет.
- `/frontend/window/register.php` → 404; регистрация на `registration-desktop.php`.

---

## Вердикт

**Ретест round2 OK, 17.08.** Деплой на travel63test.ru принят. Danik может подтвердить на своём Edge при возможности.
