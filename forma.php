<?php

namespace PopArtDesign\Forma;

use function preg_replace;

require 'helpers.php';

if ('imnotarobot!' !== getRequest('_secret')) {
    jsendFail([ 'message' => 'Secret value is invalid!' ]);
}

$name  = getRequest('name');
$phone = preg_replace('/[^+0-9]/', '', getRequest('phone'));

if (!$name || !$phone) {
    jsendFail([ 'message' => 'Please fill out all required fields' ]);
}

$attachments = uploadedFilesToAttachments(['files']);

$domain = detectDomain();

$to      = 'user@localhost';
$from    = 'no-reply@' . $domain;
$subject = 'Call me from '. $domain;
$message = "Name: $name, phone: $phone";
$options = [
    'from' => $from,
    'sender' => $from,
    'attachments' => $attachments,
];

if (sendMail($to, $subject, $message, $options)) {
    jsendSuccess();
} else {
    jsendError();
}
