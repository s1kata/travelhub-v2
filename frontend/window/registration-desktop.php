<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/components/security_helper.php';
session_start();
$page_title = "Регистрация";
$current_page = "registration";

// Получаем данные из параметров запроса (если есть ошибки)
$formData = [];
$errors = [];
if (isset($_GET['data'])) {
    $formData = json_decode(urldecode($_GET['data']), true) ?: [];
    if (isset($formData['errors'])) {
        $errors = $formData['errors'];
    }
}

$name = $formData['name'] ?? '';
$email = $formData['email'] ?? '';
$phone = $formData['phone'] ?? '';
$city = $formData['city'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo htmlspecialchars($page_title); ?> - Travel Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="/frontend/css/pages/registration-desktop.css?v=1">
    <?php include __DIR__ . '/../../backend/components/design_system_head.php'; ?>
    </head>
<body class="gradient-bg min-h-screen">
    <?php require_once __DIR__ . '/../../backend/components/header.php'; ?>

    <!-- Main Content -->
    <main class="login-page-main th-container mx-auto px-4">
        <div class="max-w-md mx-auto">
            <!-- Logo & Title -->
            <div class="text-center mb-10">
                <div class="w-20 h-20 mx-auto rounded-full bg-gradient-to-r from-sky-400 to-sky-600 flex items-center justify-center shadow-xl mb-6">
                    <i class="fas fa-user-plus text-2xl text-white"></i>
                </div>
                <h1 class="text-3xl font-bold text-sky-800 mb-3">Создать аккаунт</h1>
                <p class="text-slate-600">Присоединяйтесь к Travel Hub</p>
            </div>

            <!-- Registration Form -->
            <div class="bg-white rounded-2xl card-shadow p-8">
                <?php
                // Показываем общие ошибки
                if (isset($errors['database'])) {
                    echo '<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">';
                    echo '<i class="fas fa-exclamation-circle mr-2"></i>' . htmlspecialchars($errors['database']);
                    echo '</div>';
                }
                if (isset($errors['duplicate'])) {
                    echo '<div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl text-yellow-700 text-sm">';
                    echo '<i class="fas fa-exclamation-triangle mr-2"></i>Пользователь с такими данными уже существует.';
                    echo '</div>';
                }
                ?>

                <form id="registrationForm" method="POST" action="/backend/scripts/process_registration.php">
                    <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars(security_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="submit" value="1">

                    <!-- Name -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            <i class="fas fa-user text-sky-500 mr-2"></i>Имя <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               required
                               minlength="2"
                               maxlength="60"
                               value="<?php echo htmlspecialchars($name); ?>"
                               class="w-full px-4 py-3 rounded-xl border <?php echo isset($errors['name']) ? 'border-red-300' : 'border-slate-200'; ?> bg-slate-50 text-slate-700 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 transition"
                               placeholder="Ваше имя">
                        <?php if (isset($errors['name'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo htmlspecialchars($errors['name']); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            <i class="fas fa-envelope text-sky-500 mr-2"></i>Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email"
                               id="email"
                               name="email"
                               required
                               value="<?php echo htmlspecialchars($email); ?>"
                               class="w-full px-4 py-3 rounded-xl border <?php echo isset($errors['email']) ? 'border-red-300' : 'border-slate-200'; ?> bg-slate-50 text-slate-700 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 transition"
                               placeholder="ваш@email.com">
                        <?php if (isset($errors['email'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo htmlspecialchars($errors['email']); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Password -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            <i class="fas fa-lock text-sky-500 mr-2"></i>Пароль <span class="text-red-500">*</span>
                        </label>
                        <div class="login-password-wrap <?php echo isset($errors['password']) ? 'is-error' : ''; ?>">
                            <input type="password"
                                   id="password"
                                   name="password"
                                   required
                                   minlength="6"
                                   class="px-4 py-3.5 text-slate-700 placeholder-slate-400"
                                   placeholder="Минимум 6 символов"
                                   autocomplete="new-password">
                            <button type="button"
                                    onclick="togglePassword('password')"
                                    class="login-password-toggle th-password-toggle"
                                    aria-label="Показать или скрыть пароль">
                                <i class="fas fa-eye" id="passwordEye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo htmlspecialchars($errors['password']); ?></p>
                        <?php else: ?>
                            <p class="mt-1 text-xs text-slate-500">Пароль должен содержать не менее 6 символов</p>
                        <?php endif; ?>
                    </div>

                    <!-- Phone (optional) -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            <i class="fas fa-phone text-sky-500 mr-2"></i>Телефон
                        </label>
                        <input type="tel"
                               id="phone"
                               name="phone"
                               value="<?php echo htmlspecialchars($phone); ?>"
                               class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-700 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 transition"
                               placeholder="+7 (999) 123-45-67">
                    </div>

                    <!-- City (optional) -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            <i class="fas fa-map-marker-alt text-sky-500 mr-2"></i>Город
                        </label>
                        <input type="text"
                               id="city"
                               name="city"
                               value="<?php echo htmlspecialchars($city); ?>"
                               class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-700 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 transition"
                               placeholder="Ваш город">
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="animated-button w-full font-semibold text-lg mb-6">
                        <i class="fas fa-user-plus mr-2"></i>Зарегистрироваться
                    </button>

                    <!-- Login Link -->
                    <div class="text-center">
                        <p class="text-slate-600">
                            Уже есть аккаунт?
                            <a href="login-desktop.php" class="text-sky-600 font-semibold hover:text-sky-800">
                                Войти
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Password toggle
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const eye = document.getElementById(inputId + 'Eye');

            if (input.type === 'password') {
                input.type = 'text';
                eye.classList.remove('fa-eye');
                eye.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                eye.classList.remove('fa-eye-slash');
                eye.classList.add('fa-eye');
            }
        }

        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            let isValid = true;
            let errorMessage = '';

            // Validate name
            if (name.length < 2) {
                isValid = false;
                errorMessage = 'Имя должно содержать минимум 2 символа.';
            } else if (name.length > 60) {
                isValid = false;
                errorMessage = 'Имя не должно превышать 60 символов.';
            }

            // Validate email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                isValid = false;
                errorMessage = 'Пожалуйста, введите корректный email.';
            }

            // Validate password
            if (password.length < 6) {
                isValid = false;
                errorMessage = 'Пароль должен содержать не менее 6 символов.';
            }

            if (!isValid) {
                e.preventDefault();
                alert(errorMessage);
                return false;
            }

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Регистрация...';
            submitBtn.disabled = true;
        });
    </script>
    <?php include __DIR__ . '/../../backend/components/footer.php'; ?>
</body>
</html>