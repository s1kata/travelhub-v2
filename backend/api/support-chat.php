<?php
/**
 * Support chat bot (rule-based, no AI).
 * Shared endpoint for site widget and mobile app.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/components/security_helper.php';
require_once dirname(__DIR__) . '/components/secure_logger.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
security_apply_default_headers();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (security_rate_limit_exceeded('support_chat', 20, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Слишком много сообщений. Попробуйте через минуту.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$guard = security_guard_public_api('support_chat_api', is_string($raw) ? $raw : '', 40, 16384);
if (empty($guard['ok'])) {
    http_response_code((int)($guard['code'] ?? 400));
    echo json_encode(['success' => false, 'error' => (string)($guard['error'] ?? 'Bad request')], JSON_UNESCAPED_UNICODE);
    th_log_write('security_events', 'support_chat_guard_block', [
        'reason' => $guard['reason'] ?? 'unknown',
        'path' => '/backend/api/support-chat.php',
    ], 'warn');
    exit;
}
$in = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
if (!is_array($in)) {
    $in = [];
}

$message = trim((string) ($in['message'] ?? ''));
$sessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($in['sessionId'] ?? ''));
$channel = mb_substr(trim((string) ($in['channel'] ?? 'site')), 0, 24);

if ($sessionId === '') {
    try {
        $sessionId = bin2hex(random_bytes(8));
    } catch (Throwable $e) {
        $sessionId = substr(md5((string) microtime(true)), 0, 16);
    }
}

$quickReplies = ['Горящие туры', 'Оплата', 'Бронирование', 'Документы', 'Цены', 'Связаться с менеджером'];

if ($message === '') {
    echo json_encode([
        'success' => true,
        'sessionId' => $sessionId,
        'intent' => 'greeting',
        'reply' => 'Здравствуйте! Могу подсказать по горящим турам, оплате, бронированию и документам. Выберите тему ниже или напишите вопрос.',
        'handoff' => false,
        'quickReplies' => $quickReplies,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$rules = [
    'hot' => [
        'keywords' => ['горящ', 'акци', 'скидк', 'дешев', 'горящие туры', 'что посоветуете', 'куда полететь'],
        'answer' => 'Горящие туры смотрите на главной в блоке «Горящие туры» — там уже готовые варианты с ценой и датами. Можете сразу открыть карточку и оставить заявку. Нужна помощь с подбором — нажмите «Связаться с менеджером».',
    ],
    'payment' => [
        'keywords' => ['оплат', 'т-касс', 'tinkoff', 'карт', 'чек', 'ссылка на оплату', 'рассрочк', 'кредит'],
        'answer' => 'Оплатить можно онлайн картой (Т-Касса) или в офисе. Рассрочка/кредит — по доступности у оператора, менеджер подскажет. Нужна персональная ссылка на оплату — оставьте телефон.',
    ],
    'booking' => [
        'keywords' => ['брони', 'заброни', 'как купить', 'оформить', 'заявк', 'заказать тур'],
        'answer' => 'Выберите тур → «Забронировать» → имя и телефон. Менеджер проверит места и подтвердит бронь, обычно в течение 15 минут.',
    ],
    'price' => [
        'keywords' => ['цен', 'сколько стоит', 'стоимост', 'бюджет', 'дорого', 'недорого'],
        'answer' => 'Цена на карточке уже за выбранных туристов (обычно 2 взрослых). Итоговая сумма зависит от дат, отеля и курса. Пришлите направление и даты — уточним актуальные варианты.',
    ],
    'documents' => [
        'keywords' => ['документ', 'паспорт', 'виза', 'загран', 'страховк'],
        'answer' => 'Обычно нужны загранпаспорта всех туристов. По визовым странам менеджер пришлёт список и сроки. Страховка часто уже в туре — уточним по выбранному варианту.',
    ],
    'cancel' => [
        'keywords' => ['отмен', 'возврат', 'вернуть деньги', 'штраф', 'перенос'],
        'answer' => 'Условия отмены и возврата зависят от туроператора и даты вылета. Напишите номер брони или телефон — проверим правила и подскажем лучший вариант.',
    ],
    'dates' => [
        'keywords' => ['дат', 'когда вылет', 'на сколько ноч', 'календар'],
        'answer' => 'Даты и ночи можно выбрать в поиске наверху страницы. В горящих турах даты уже указаны на карточке — откройте тур, чтобы увидеть детали.',
    ],
    'kids' => [
        'keywords' => ['дет', 'ребен', 'ребён', 'семь', 'с ребенком'],
        'answer' => 'Для отдыха с детьми выберите в поиске состав туристов и возраст детей — цены пересчитаются. Можем подобрать семейные отели: нажмите «Связаться с менеджером».',
    ],
    'contacts' => [
        'keywords' => ['телефон', 'связаться', 'контакт', 'офис', 'адрес', 'whatsapp', 'ватсап', 'telegram', 'max', 'где находитесь'],
        'answer' => 'Телефон: +7 (846) 254-16-56. Есть Telegram и MAX. Офис в Самаре: Московское шоссе, 81Б (ТЦ «Парк Хаус»). Перезвоним за 15 минут.',
    ],
    'app' => [
        'keywords' => ['приложен', 'app store', 'ios', 'android'],
        'answer' => 'Мобильное приложение TravelHub есть в App Store. На сайте также можно искать и бронировать туры — промокод на скидку выдаём после перехода в магазин приложений.',
    ],
    'handoff' => [
        'keywords' => ['менеджер', 'перезвон', 'живой человек', 'оператор', 'помогите', 'консультант'],
        'answer' => 'Сейчас передам менеджеру. Оставьте номер телефона и удобное время — свяжемся как можно быстрее.',
    ],
];

$hay = function_exists('mb_strtolower') ? mb_strtolower($message, 'UTF-8') : strtolower($message);
$intent = 'fallback';
$reply = 'Могу помочь с горящими турами, оплатой, бронированием, документами или ценами. Выберите тему ниже — или нажмите «Связаться с менеджером».';
foreach ($rules as $key => $rule) {
    $tokens = is_array($rule['keywords'] ?? null) ? $rule['keywords'] : [];
    foreach ($tokens as $token) {
        if ($token !== '' && str_contains($hay, (string) $token)) {
            $intent = $key;
            $reply = (string) ($rule['answer'] ?? $reply);
            break 2;
        }
    }
}
$handoff = ($intent === 'handoff') || str_contains($hay, 'перезвон') || str_contains($hay, 'менеджер');
if ($intent === 'fallback') {
    $handoff = true;
}

$storePlainText = filter_var(getenv('TH_SUPPORT_CHAT_LOG_STORE_TEXT') ?: ($_ENV['TH_SUPPORT_CHAT_LOG_STORE_TEXT'] ?? '0'), FILTER_VALIDATE_BOOLEAN);
$ctx = [
    'sessionIdHash' => th_log_hash($sessionId),
    'channel' => $channel,
    'intent' => $intent,
    'handoff' => $handoff,
    'messageLen' => mb_strlen($message),
    'messageHash' => th_log_hash($message),
];
if ($storePlainText) {
    // Включается только вручную для локальной диагностики.
    $ctx['messagePreview'] = mb_substr($message, 0, 160);
}
th_log_write('support_chat', 'message_processed', $ctx, 'info');

echo json_encode([
    'success' => true,
    'sessionId' => $sessionId,
    'intent' => $intent,
    'reply' => $reply,
    'handoff' => $handoff,
    'managerCta' => $handoff,
    'quickReplies' => $quickReplies,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
