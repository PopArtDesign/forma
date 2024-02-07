<?php

namespace PopArtDesign\Forma;

use PHPMailer\PHPMailer\DSNConfigurator;
use PHPMailer\PHPMailer\PHPMailer;

if (!\defined('MAILER_DSN') || !MAILER_DSN) {
    return;
}

try {
    $mail = DSNConfigurator::mailer(MAILER_DSN, true);

    $mail->Subject = MAILER_SUBJECT;
    $mail->setFrom(MAILER_FROM);
    foreach (MAILER_RECIPIENTS as $recipient) {
        $mail->addAddress($recipient);
    }
    $mail->Body = $message;
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->isHtml(MAILER_HTML);

    foreach ($attachments as $attachment) {
        $mail->addAttachment($attachment['path'], $attachment['name']);
    }

    $mail->send();
} catch (\Exception $e) {
    jsendError();
}
