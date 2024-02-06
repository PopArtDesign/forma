<?php

namespace PopArtDesign\Forma;

use function preg_replace;

require __DIR__ . '/helpers.php';

if ('imnotarobot!' !== getRequest('_secret')) {
    jsendFail(['message' => 'Некорректное значение антиспам-поля!']);
}

$name  = getRequest('name');
$phone = preg_replace('/[^+0-9]/', '', getRequest('phone'));
$attachments = collectAttachments(['files']);

if (!$name || !$phone || count($attachments) == 0) {
    jsendFail(['message' => 'Пожалуйста, заполните все обязательные поля!']);
}

$domain = detectDomain();

$to      = 'user@localhost';
$from    = 'no-reply@' . $domain;
$subject = 'Сообщение с сайта '. $domain;

$message = loadTemplate(__DIR__ . '/email.php', [
    'name' => $name,
    'phone' => $phone,
]);

$options = [
    'from' => $from,
    'sender' => $from,
    'attachments' => $attachments,
    'html' => true,
];

if (sendMail($to, $subject, $message, $options)) {
    jsendSuccess();
} else {
    jsendError();
}
