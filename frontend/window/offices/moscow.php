<?php
require_once __DIR__ . '/../../../backend/config/config.php';
session_start();
$page_title = "Офис в Москве";
$current_page = "offices";
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo htmlspecialchars($page_title); ?> - Travel Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="/frontend/css/pages/offices-moscow.css?v=1">
    <?php include __DIR__ . '/../../backend/components/design_system_head.php'; ?>
    </head>
<body class="gradient-bg">
    <?php require_once __DIR__ . '/../../../backend/components/header.php'; ?>

    <main class="th-container mx-auto px-4 py-12">
        <div class="max-w-4xl mx-auto">
            <!-- Hero Section -->
            <div class="text-center mb-12">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">Офис Travel Hub в Москве</h1>
                <p class="text-xl text-gray-600">Профессиональная консультация по путешествиям в столице</p>
            </div>

            <!-- Contact Info -->
            <div class="bg-white rounded-2xl card-shadow p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Контактная информация</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Адрес</h3>
                        <p class="text-gray-600">Москва, Первомайская ул., 42</p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Телефон</h3>
                        <p class="text-gray-600"><a href="tel:+74951234567" class="text-sky-600 hover:text-sky-800">+7 (495) 123-45-67</a></p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Email</h3>
                        <p class="text-gray-600"><a href="mailto:moscow@travelhub.ru" class="text-sky-600 hover:text-sky-800">moscow@travelhub.ru</a></p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Часы работы</h3>
                        <p class="text-gray-600">Пн-Пт: 9:00 - 18:00<br>Сб-Вс: 10:00 - 16:00</p>
                    </div>
                </div>
            </div>

            <!-- Map (Яндекс, constructor как в website-main) -->
            <div class="bg-white rounded-2xl card-shadow p-8 mb-8 overflow-hidden">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Как нас найти</h2>
                <div class="w-full rounded-xl overflow-hidden">
                    <?php
                    require_once __DIR__ . '/../../../backend/config/maps.php';
                    $yandex_map_open_url = th_maps()['widget_moscow_iframe'];
                    include __DIR__ . '/../../../backend/components/yandex_map_open_link.php';
                    ?>
                </div>
            </div>

            <!-- Team Section -->
            <div class="bg-white rounded-2xl card-shadow p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Наша команда</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-sky-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-user text-2xl text-sky-600"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800">Иван Петров</h3>
                        <p class="text-gray-600 text-sm">Менеджер по туризму</p>
                        <p class="text-gray-500 text-xs mt-2">Опыт работы 5 лет</p>
                    </div>
                    <div class="text-center">
                        <div class="w-20 h-20 bg-sky-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-user text-2xl text-sky-600"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800">Мария Иванова</h3>
                        <p class="text-gray-600 text-sm">Специалист по визам</p>
                        <p class="text-gray-500 text-xs mt-2">Специалист по оформлению виз</p>
                    </div>
                </div>
            </div>

            <!-- Services -->
            <div class="bg-white rounded-2xl card-shadow p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Наши услуги</h2>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-sky-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-plane text-2xl text-sky-600"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-2">Авиабилеты</h3>
                        <p class="text-gray-600 text-sm">Поиск лучших предложений</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-sky-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-hotel text-2xl text-sky-600"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-2">Отели</h3>
                        <p class="text-gray-600 text-sm">Лучшие варианты размещения</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-sky-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-umbrella-beach text-2xl text-sky-600"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-2">Туры</h3>
                        <p class="text-gray-600 text-sm">Индивидуальные путешествия</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../../../backend/components/footer.php'; ?>

</body>
</html>