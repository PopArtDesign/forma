<?php

namespace PopArtDesign\Forma;

require_once __DIR__ . '/../forma.php';

// Mailpit: https://github.com/axllent/mailpit
$config['mail_dsn'] = 'smtp://localhost:1025';
$config['mail_from'] = 'no-reply@test.wip';

imnotarobot();
recaptcha();

$data = validate([
    'name' => 'required|minlength:2|maxlength:50',
    'phone' => 'required|phone',
    'email' => 'nullable|email',
    'message' => 'required|maxlength:1000',
    'file' => 'nullable|uploaded_file:0,1M',
]);

$config['mail_message'] = loadTemplate(__DIR__ . '/email.php', $data);
$config['mail_attachments'] = collectAttachments([ 'file' ]);

mail();

success();
