<?php

namespace PopArtDesign\Forma;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../config.php';

imnotarobot();
recaptcha();

$name = getRequest('name');
$phone = getRequest('phone');

if (!$name || !$phone) {
    fail('Правильно заполните все обязательные поля!');
}

$config['mail_message'] = loadTemplate(__DIR__ . '/email.php', [
    'name' => $name,
    'phone' => $phone,
]);

mail();

success();
