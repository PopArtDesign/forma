<?php

namespace PopArtDesign\Forma;

require_once __DIR__ . '/../forma.php';

imnotarobot();
recaptcha();

$data = validate([
    'name' => 'required|maxlength:50',
    'email' => 'required|email',
    'message' => 'required|maxlength:1000',
    'files.*' => 'uploaded_file:0,1M',
]);

$config['mail_message'] = loadTemplate(__DIR__ . '/email.php', $data);
$config['mail_attachments'] = collectAttachments([ 'files' ]);

mail();

success();
