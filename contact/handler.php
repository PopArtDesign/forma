<?php

namespace PopArtDesign\Forma;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../config.php';

imnotarobot();
recaptcha();

$name = getRequest('name');
$email = getRequest('email');
$message = getRequest('message');
$attachments = collectAttachments([ 'files' ]);

if (!$name || !$email || !$message) {
    fail('Правильно заполните все обязательные поля!');
}

$config['mail_message'] = loadTemplate(__DIR__ . '/email.php', [
    'name' => $name,
    'email' => $email,
    'message' => $message,
]);

$config['mail_attachments'] = $attachments;

mail();

success();
