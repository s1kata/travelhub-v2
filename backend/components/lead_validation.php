<?php
/**
 * Общая серверная валидация лидов (ФИО + телефон РФ).
 * Используется uon-lead, office-lead, uon-booking, registration и др. — не дублировать правила.
 *
 * Правила (синхронизировать с frontend/js/th-lead-capture.js):
 * - ФИО: буквы/пробелы/дефис/апостроф, минимум 2 кириллические буквы (латиница-only отклоняется)
 * - Телефон: мобильный РФ +79XXXXXXXXX, без «одинаковых» и очевидных последовательностей
 */
declare(strict_types=1);

if (!function_exists('th_lead_normalize_ru_phone')) {
    /**
     * Нормализует российский мобильный номер к виду +79XXXXXXXXX или '' если невалиден.
     */
    function th_lead_normalize_ru_phone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw);
        if (!is_string($digits) || $digits === '') {
            return '';
        }
        if (strlen($digits) === 11 && $digits[0] === '8') {
            $digits = '7' . substr($digits, 1);
        }
        if (strlen($digits) !== 11 || $digits[0] !== '7') {
            return '';
        }
        $rest = substr($digits, 1);
        // Только мобильные РФ: +7 9XX …
        if ($rest === '' || $rest[0] !== '9') {
            return '';
        }
        if (preg_match('/^(\d)\1{9}$/', $rest)) {
            return '';
        }
        // Очевидные последовательности / «тестовые» хвосты
        if ($rest === '9012345678' || $rest === '9876543210' || $rest === '9123456789') {
            return '';
        }
        if (preg_match('/^9(\d)\1{8}$/', $rest)) {
            return '';
        }
        return '+' . $digits;
    }
}

if (!function_exists('th_lead_validate_person_name')) {
    /**
     * @return string|null текст ошибки на русском или null если ок
     */
    function th_lead_validate_person_name(string $name, int $minLen = 2, int $maxLen = 100): ?string
    {
        $name = trim($name);
        if ($name === '') {
            return 'Укажите ФИО';
        }
        if (mb_strlen($name) < $minLen) {
            return 'Укажите корректные ФИО';
        }
        if (mb_strlen($name) > $maxLen) {
            return 'ФИО слишком длинное';
        }
        if (!preg_match('/^[\p{L}\s\-\'.]+$/u', $name)) {
            return 'Укажите корректные ФИО';
        }
        $compact = preg_replace('/[\s\-\'.]+/u', '', $name);
        if (!is_string($compact)) {
            $compact = '';
        }
        if (mb_strlen($compact) < 2) {
            return 'Укажите корректные ФИО';
        }
        if ($compact !== '' && preg_match('/^(.)\1+$/u', $compact)) {
            return 'Укажите корректные ФИО';
        }
        // Сайт на русском: отклоняем чисто латинский/немецкий ввод без кириллицы
        $cyrCount = preg_match_all('/\p{Cyrillic}/u', $name, $m);
        if ($cyrCount === false || $cyrCount < 2) {
            return 'Укажите ФИО русскими буквами';
        }
        return null;
    }
}

if (!function_exists('th_lead_validate_ru_phone')) {
    /**
     * @return array{ok:bool, phone:string, error:?string}
     */
    function th_lead_validate_ru_phone(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return ['ok' => false, 'phone' => '', 'error' => 'Укажите телефон'];
        }
        $phone = th_lead_normalize_ru_phone($raw);
        if ($phone === '') {
            return ['ok' => false, 'phone' => '', 'error' => 'Укажите корректный мобильный телефон РФ (+7 9XX…)'];
        }
        return ['ok' => true, 'phone' => $phone, 'error' => null];
    }
}

if (!function_exists('th_lead_is_agree_accepted')) {
    /**
     * Принимает типичные значения чекбокса/JSON: true, 1, "1", "on", "yes", "true".
     * @param mixed $value
     */
    function th_lead_is_agree_accepted($value): bool
    {
        if ($value === true || $value === 1) {
            return true;
        }
        if (is_string($value)) {
            $v = strtolower(trim($value));
            return $v === '1' || $v === 'on' || $v === 'yes' || $v === 'true';
        }
        return false;
    }
}

if (!function_exists('th_lead_require_agree')) {
    /**
     * @param array<string, mixed> $input
     * @return string|null текст ошибки или null если согласие есть
     */
    function th_lead_require_agree(array $input, string $key = 'agree'): ?string
    {
        if (th_lead_is_agree_accepted($input[$key] ?? null)) {
            return null;
        }
        return 'Нужно согласие на обработку персональных данных';
    }
}
