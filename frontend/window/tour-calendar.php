<?php
require_once __DIR__ . '/../../backend/config/config.php';
session_start();
$current_page = 'tour-calendar';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="icon" type="image/svg+xml" href="/frontend/favicon.svg">
    <title>Календарь туров | Travel Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include __DIR__ . '/../../backend/components/design_system_head.php'; ?>
</head>
<body class="ds-page text-slate-900 antialiased">
    <?php include __DIR__ . '/../../backend/components/header.php'; ?>

    <main class="relative z-10">
        <!-- Hero -->
        <section class="ds-page-hero pt-8 pb-6 md:pt-10 md:pb-8">
            <div class="th-container mx-auto px-4 sm:px-6 max-w-5xl">
                <span class="pill-badge mb-4">По датам вылета</span>
                <h1 class="heading-font text-3xl sm:text-4xl font-bold text-slate-900 mb-3">Календарь туров</h1>
                <p class="text-slate-600 text-lg max-w-2xl leading-relaxed">Выберите направление и смотрите цены по датам вылета. Удобно планировать отпуск: видно, когда выгоднее лететь и сколько стоит тур на нужные даты.</p>
            </div>
        </section>

        <section class="pb-10 md:pb-14">
            <div class="th-container mx-auto px-4 sm:px-6 max-w-6xl">
                <div class="surface-card p-6 sm:p-8 md:p-10">
                    <?php
                    $tourvisor_widget_module = 'calendar';
                    $tourvisor_widget_container_id = 'tourvisor-calendar';
                    $tourvisor_widget_wrap_class = 'tv-widget-wrap w-full min-h-[480px]';
                    include __DIR__ . '/../../backend/components/tourvisor_widget_embed.php';
                    ?>
                </div>
                <div class="flex flex-wrap justify-center gap-4 mt-8">
                    <a href="/index.php#tour-search-section" class="ds-btn-primary inline-flex items-center gap-2 px-6 py-3 rounded-full shadow-lg">Поиск туров</a>
                    <a href="/frontend/window/offices.php" class="ds-btn-secondary inline-flex items-center gap-2 px-6 py-3 rounded-full">Наши офисы</a>
                    <a href="/frontend/window/promotions.php" class="ds-btn-secondary inline-flex items-center gap-2 px-6 py-3 rounded-full">Акции</a>
                </div>
            </div>
        </section>

        <!-- Информационные блоки вокруг темы календаря -->
        <section class="pb-16 md:pb-20">
            <div class="th-container mx-auto px-4 sm:px-6 max-w-6xl">
                <h2 class="heading-font text-2xl font-bold text-slate-900 mb-8">Зачем смотреть туры по календарю</h2>
                <div class="grid sm:grid-cols-3 gap-6">
                    <div class="surface-card p-6 flex flex-col">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center mb-4 text-indigo-600">
                            <i class="fas fa-calendar-check text-xl"></i>
                        </div>
                        <h3 class="heading-font font-semibold text-slate-900 mb-2">Планирование отпуска</h3>
                        <p class="text-slate-600 text-sm leading-relaxed flex-1">Сравните цены на разные даты вылета и выберите самый выгодный период. Часто сдвиг на неделю даёт существенную экономию.</p>
                    </div>
                    <div class="surface-card p-6 flex flex-col">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center mb-4 text-indigo-600">
                            <i class="fas fa-chart-line text-xl"></i>
                        </div>
                        <h3 class="heading-font font-semibold text-slate-900 mb-2">Актуальные цены</h3>
                        <p class="text-slate-600 text-sm leading-relaxed flex-1">Данные в календаре обновляются регулярно и соответствуют предложениям туроператоров. Вы видите реальную стоимость тура на выбранные даты.</p>
                    </div>
                    <div class="surface-card p-6 flex flex-col">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center mb-4 text-indigo-600">
                            <i class="fas fa-route text-xl"></i>
                        </div>
                        <h3 class="heading-font font-semibold text-slate-900 mb-2">Любое направление</h3>
                        <p class="text-slate-600 text-sm leading-relaxed flex-1">Выберите страну или курорт — календарь покажет доступные даты и цены. При необходимости уточните детали в офисе или через поиск на главной.</p>
                    </div>
                </div>

                <div class="mt-12 surface-card p-6 sm:p-8">
                    <h3 class="heading-font text-xl font-bold text-slate-900 mb-4">Как пользоваться календарём</h3>
                    <ul class="space-y-3 text-slate-600">
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold">1</span>
                            <span>Выберите направление (страну или курорт) в настройках модуля выше.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold">2</span>
                            <span>В календаре отобразятся даты вылета и ориентировочные цены на туры.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold">3</span>
                            <span>Нажмите на интересующую дату или цену — откроется подбор конкретных туров для бронирования.</span>
                        </li>
                    </ul>
                </div>

                <div class="mt-10 text-center">
                    <a href="/frontend/window/offices.php" class="ds-btn-secondary inline-flex items-center gap-2 px-6 py-3 rounded-full mr-3">Наши офисы</a>
                    <a href="/frontend/window/contacts.php" class="ds-btn-primary inline-flex items-center gap-2 px-6 py-3 rounded-full shadow-lg hover:-translate-y-0.5 transition">
                        <i class="fas fa-headset"></i> Задать вопрос
                    </a>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../../backend/components/footer.php'; ?>

</body>
</html>
