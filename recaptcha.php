<?php

namespace PopArtDesign\Forma;

require_once __DIR__ . '/helpers.php';

if (!defined('RECAPTCHA_KEY') || !RECAPTCHA_KEY) {
    return;
}

if (!defined('RECAPTCHA_SECRET') || !RECAPTCHA_SECRET) {
    return;
}

if (!$token = getRequest(RECAPTCHA_KEY)) {
    jsendFail([ 'message' => 'Некорректное значение антиспам-поля!' ]);
}

$response = verifyRecaptcha($token, RECAPTCHA_SECRET);

if (!($response['success'] ?? false)) {
    jsendError('reCaptcha does not work');
}

if ($response['score'] < RECAPTCHA_THRESHOLD) {
    jsendFail([ 'message' => 'Не пройдена антиспам проверка!' ]);
}
