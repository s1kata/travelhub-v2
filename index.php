<?php
/**
 * Точка входа с корня домена (и подкаталога /website): отдаём главную без HTTP-редиректа,
 * чтобы рекламные метки (UTM, yclid, gclid и т.д.) не терялись при заходе на travelhub63.ru.
 */
require __DIR__ . '/frontend/index.php';
