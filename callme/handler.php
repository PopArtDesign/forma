<?php

namespace PopArtDesign\Forma;

require_once __DIR__ . '/../forma.php';

imnotarobot();
recaptcha();

$data = validate([
    'name' => 'required|minlength:2|maxlength:50',
    'phone' => 'required|phone',
]);

$config['mail_message'] = loadTemplate(__DIR__ . '/email.php', $data);

mail();

success();
