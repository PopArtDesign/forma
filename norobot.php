<?php

namespace PopArtDesign\Forma;

require_once __DIR__ . '/helpers.php';

if (!defined('NOROBOT_KEY') || !NOROBOT_KEY) {
    return;
}

if (!defined('NOROBOT_VALUE') || !NOROBOT_VALUE) {
    return;
}

if (getRequest(NOROBOT_KEY) !== NOROBOT_VALUE) {
    jsendFail([ 'message' => 'Некорректное значение антиспам-поля!' ]);
}
