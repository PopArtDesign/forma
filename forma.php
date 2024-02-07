<?php

namespace PopArtDesign\Forma;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_once __DIR__ . '/norobot.php';
require_once __DIR__ . '/recaptcha.php';

$name  = getRequest('name');
$phone = getRequest('phone');
$attachments = collectAttachments([ 'files' ]);

if (!$name || !$phone || count($attachments) == 0) {
    jsendFail([ 'message' => 'Правильно заполните все обязательные поля!' ]);
}

$attachmentsSize = calculateAttachmentsSize($attachments);
if ($attachmentsSize > 10 * 1024 * 1024) {
    jsendFail([ 'message' => 'Общий размер файлов не должен превышать 10 Мб!' ]);
}

$message = loadTemplate(__DIR__ . '/email.php', [
    'name' => $name,
    'phone' => $phone,
]);

require_once __DIR__ . '/mailer.php';

jsendSuccess();
