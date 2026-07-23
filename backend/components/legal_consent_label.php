<?php
/**
 * Текст чекбокса согласия для форм (HTML).
 */
declare(strict_types=1);

if (!function_exists('th_legal_consent_checkbox_html')) {
    function th_legal_consent_checkbox_html(): string
    {
        return 'Согласен на <a href="/frontend/window/consent.php" target="_blank" rel="noopener">обработку персональных данных</a> '
            . 'и <a href="/frontend/window/terms.php" target="_blank" rel="noopener">Пользовательское соглашение</a>';
    }
}
