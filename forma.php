<?php

namespace PopArtDesign\Forma;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/helpers.php';

use function preg_replace;
use PHPMailer\PHPMailer\DSNConfigurator;
use PHPMailer\PHPMailer\PHPMailer;

if ('imnotarobot!' !== getRequest('_secret')) {
    jsendFail(['message' => 'Некорректное значение антиспам-поля!']);
}

$name  = getRequest('name');
$phone = preg_replace('/[^+0-9]/', '', getRequest('phone'));
$attachments = collectAttachments(['files']);

if (!$name || !$phone || count($attachments) == 0) {
    jsendFail(['message' => 'Правильно заполните все обязательные поля!']);
}

$domain = detectDomain();

$to      = 'user@localhost.local';
$from    = 'no-reply@' . $domain;
$subject = 'Сообщение с сайта '. $domain;
$message = loadTemplate(__DIR__ . '/email.php', [
    'name' => $name,
    'phone' => $phone,
]);

try {
    $mail = DSNConfigurator::mailer('mail://localhost', true);

    $mail->addAddress($to);
    $mail->setFrom($from);
    $mail->Subject = $subject;
    $mail->Body = $message;
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->isHtml(true);

    foreach ($attachments as $attachment) {
        $mail->addAttachment($attachment['path'], $attachment['name']);
    }

    $mail->send();

    jsendSuccess();
} catch (\Exception $e) {
    jsendError();
}
