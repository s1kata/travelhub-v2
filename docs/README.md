# Документация Travel Hub

Сайт **travelhub63.ru** — PHP + MySQL, поиск туров через Tourvisor, офисы в Самаре и Москве, мобильное приложение с оплатой через Т-Кассу.

## Быстрый старт

| Документ | Содержание |
|----------|------------|
| [DEPLOY.md](DEPLOY.md) | Деплой на хостинг, `.env`, Composer, права |
| [CRON.md](CRON.md) | Задачи по расписанию (промо, YML, кэш) |
| [OFFICES.md](OFFICES.md) | Каталог офисов и фото на диске |
| [LEGAL.md](LEGAL.md) | Юридические страницы и оператор ПДн |
| [TOURVISOR.md](TOURVISOR.md) | API, прокси и кэш поиска туров |
| [BUILD.md](BUILD.md) | Сборка Tailwind CSS перед продом |
| [PERFORMANCE.md](PERFORMANCE.md) | Кэш страниц и оптимизация изображений |
| [SEO.md](SEO.md) | Мета-теги, sitemap, robots |

## Структура репозитория

```
frontend/           Страницы, CSS, JS (публичная часть)
backend/            Компоненты, API, админка, скрипты cron
api/                REST для мобильного приложения (оплата и др.)
export/             YML-фиды для маркетплейсов
data/               Кэш, SQLite, runtime-файлы (часть в .gitignore)
docs/               Документация и исходник согласия ПДн (.docx)
img/                Статика (сотрудники и пр.; фото офисов — см. OFFICES.md)
.htaccess           Роутинг, редиректы, кэш-заголовки
```

## Конфигурация

- **Секреты:** `.env` в корне (шаблон — `.env.example`). Не коммитить.
- **Офисы:** `backend/config/offices_catalog.php`, `backend/config/office_photo_folders.php`
- **Юридические тексты:** `backend/config/legal.php`
- **БД:** MySQL через `.env`; SQLite для части админ-функций — `data/`

## Полезные команды

```bash
composer install --no-dev          # зависимости PHP
npm install && npm run build:css   # опционально: локальный Tailwind
php backend/scripts/promo_tours_refresh.php
php backend/scripts/rebuild_feed.php
php backend/scripts/normalize_office_photos.php   # миграция legacy-фото офисов
```

История изменений v2 — [CHANGELOG.md](../CHANGELOG.md) в корне репозитория.
