<?php

namespace PopArtDesign\Forma;

require_once __DIR__ . '/helpers.php';

if (!\defined('IMNOTAROBOT_FIELD') || !IMNOTAROBOT_FIELD) {
    return;
}

if (!\defined('IMNOTAROBOT_VALUE') || !IMNOTAROBOT_VALUE) {
    return;
}

if (getRequest(IMNOTAROBOT_FIELD) !== IMNOTAROBOT_VALUE) {
    jsendFail([ 'message' => 'Некорректное значение антиспам-поля!' ]);
}
