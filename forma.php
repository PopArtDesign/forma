<?php

namespace PopArtDesign\Forma;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/config.php';

imnotarobot();
recaptcha();

$name  = getRequest('name');
$phone = getRequest('phone');
$attachments = getAttachments([ 'files' ]);

if (!$name || !$phone || count($attachments) == 0) {
    jsendFail([ 'message' => 'Правильно заполните все обязательные поля!' ]);
}

$message = loadTemplate(__DIR__ . '/email.php', [
    'name' => $name,
    'phone' => $phone,
]);

sendMail($message, $attachments);

jsendSuccess();
