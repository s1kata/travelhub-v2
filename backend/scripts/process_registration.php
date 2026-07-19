<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/security_helper.php';

session_start();

// Rate limit: 3 регистрации за 60 минут с одного IP (анти-спам)
if (security_rate_limit_exceeded('registration', 3, 3600)) {
    header('Location: /frontend/window/registration-desktop.php?errors=' . urlencode(json_encode(['name' => 'Слишком много попыток. Попробуйте позже.'])));
    exit;
}

if (!defined('REMEMBER_TOKEN_SALT')) {
    define('REMEMBER_TOKEN_SALT', getenv('AUTH_REMEMBER_SALT') ?: 'travelhub-remember-token');
}

$errors = [];
$successMessage = '';
$name = $email = $phone = $city = $gender = '';
$age = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (!security_csrf_verify()) {
        header('Location: /frontend/window/registration-desktop.php?errors=' . urlencode(json_encode(['name' => 'Сессия истекла. Обновите страницу.'])));
        exit;
    }
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $passwordValue = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $age = (int) ($_POST['age'] ?? 0);
    $gender = trim($_POST['gender'] ?? '');

    // Валидация имени
    if ($name === '') {
        $errors['name'] = 'Пожалуйста, введите имя.';
    } elseif (mb_strlen($name) > 60) {
        $errors['name'] = 'Имя не должно превышать 60 символов.';
    } elseif (!preg_match('/^[\p{L}\s\-]+$/u', $name)) {
        $errors['name'] = 'Имя может содержать только буквы и дефисы.';
    }

    // Валидация email
    if ($email === '') {
        $errors['email'] = 'Пожалуйста, введите email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Пожалуйста, введите корректный email.';
    }

    // Валидация пароля
    if ($passwordValue === '') {
        $errors['password'] = 'Пожалуйста, введите пароль.';
    } elseif (mb_strlen($passwordValue) < 6) {
        $errors['password'] = 'Пароль должен содержать не менее 6 символов.';
    }

    // Валидация возраста
    if ($age < 0 || $age > 120) {
        $age = 0;
    }

    // Валидация пола
    $allowedGenders = ['male', 'female', 'other', 'prefer_not_to_say'];
    if (!in_array($gender, $allowedGenders, true)) {
        $gender = 'prefer_not_to_say';
    }

    // Если нет ошибок валидации
    if (empty($errors)) {
        try {
            if (!$pdo) {
                $errors['database'] = 'База данных недоступна.';
            } else {
                // Проверяем существование таблицы users
                try {
                    $dbDriver = strtolower(getenv('DB_DRIVER') ?: 'sqlite');
                    if ($dbDriver === 'sqlite') {
                        $tableCheck = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
                        if (!$tableCheck->fetchColumn()) {
                            $errors['database'] = 'Таблица users не существует. База данных не инициализирована.';
                        }
                    } else {
                        $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'");
                        if ($tableCheck->rowCount() === 0) {
                            $errors['database'] = 'Таблица users не существует. База данных не инициализирована.';
                        }
                    }
                } catch (PDOException $e) {
                    // Продолжаем выполнение даже если проверка не удалась
                }
                
                if (empty($errors)) {
                    $duplicateFields = [];
                    
                    // Проверка email на дубликат
                    try {
                        $dbDriver = strtolower(getenv('DB_DRIVER') ?: 'sqlite');
                        if ($dbDriver === 'sqlite') {
                            $checkEmail = $pdo->prepare('SELECT id, email FROM users WHERE email LIKE :email');
                            $checkEmail->execute([':email' => $email]);
                            $existingUser = $checkEmail->fetch();
                            if ($existingUser && strtolower($existingUser['email']) === strtolower($email)) {
                                $duplicateFields[] = 'email';
                                $errors['email'] = 'Этот email уже зарегистрирован.';
                            }
                        } else {
                            $checkEmail = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
                            $checkEmail->execute([':email' => $email]);
                            if ($checkEmail->fetch()) {
                                $duplicateFields[] = 'email';
                                $errors['email'] = 'Этот email уже зарегистрирован.';
                            }
                        }
                    } catch (PDOException $e) {
                        $errors['database'] = 'Ошибка проверки email: ' . $e->getMessage();
                    }
                    
                    // Проверка телефона на дубликат
                    if (empty($errors) && !empty($phone)) {
                        try {
                            $checkPhone = $pdo->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
                            $checkPhone->execute([':phone' => $phone]);
                            if ($checkPhone->fetch()) {
                                $duplicateFields[] = 'phone';
                                $errors['phone'] = 'Этот телефон уже зарегистрирован.';
                            }
                        } catch (PDOException $e) {}
                    }
                
                    // Если есть дубликаты - возвращаем ошибку
                    if (!empty($duplicateFields)) {
                        $errors['duplicate'] = true;
                    } else {
                        // Определяем роль пользователя
                        $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
                        $userRole = ($userCount === 0) ? 'admin' : 'user';
                        
                        // Создаем пользователя
                        try {
                            $hashedPassword = password_hash($passwordValue, PASSWORD_DEFAULT);
                            $insert = $pdo->prepare('INSERT INTO users (name, email, password, phone, city, gender, role) VALUES (:name, :email, :password, :phone, :city, :gender, :role)');
                            $result = $insert->execute([
                                ':name' => $name,
                                ':email' => $email,
                                ':password' => $hashedPassword,
                                ':phone' => !empty($phone) ? $phone : null,
                                ':city' => !empty($city) ? $city : null,
                                ':gender' => $gender,
                                ':role' => $userRole,
                            ]);

                            if (!$result) {
                                $errorInfo = $insert->errorInfo();
                                throw new PDOException('Не удалось создать пользователя. Код ошибки: ' . ($errorInfo[0] ?? 'unknown'));
                            }

                            $userId = $pdo->lastInsertId();
                            
                            // Проверяем создание пользователя
                            $verifyStmt = $pdo->prepare('SELECT id, email, role FROM users WHERE id = :id');
                            $verifyStmt->execute([':id' => $userId]);
                            $verifyStmt->fetch();
                            
                            // УСПЕШНАЯ РЕГИСТРАЦИЯ - РЕДИРЕКТ НА LOGIN
                            header('Location: /frontend/window/login-desktop.php');
                            exit;
                            
                        } catch (PDOException $e) {
                            throw $e;
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $errors['database'] = 'Временная ошибка сервера. Попробуйте позже.';
        } catch (Exception $e) {
            $errors['database'] = 'Временная ошибка сервера. Попробуйте позже.';
        }
    }
    
    // ЕСЛИ ЕСТЬ ОШИБКИ - ВОЗВРАЩАЕМ НА ФОРМУ
    if (!empty($errors)) {
        $redirectData = [
            'errors' => $errors,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'city' => $city,
            'age' => $age,
            'gender' => $gender,
        ];
        
        header('Location: /frontend/window/registration-desktop.php?data=' . urlencode(json_encode($redirectData)));
        exit;
    }
} else {
    // Если запрос не POST - возвращаем на форму
    header('Location: /frontend/window/registration-desktop.php');
    exit;
}
?>