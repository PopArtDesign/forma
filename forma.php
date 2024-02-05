<?php

namespace PopArtDesign\Forma;

require 'helpers.php';

$name  = \trim($_REQUEST['name']);
$phone = \preg_replace('/[^+0-9]/', '', $_REQUEST['phone']);

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
    'sender' => $from,
    'from' => $from,
    'attachments' => $attachments,
];

if (sendMail($to, $subject, $message, $options)) {
    jsendSuccess();
} else {
    jsendError();
}
