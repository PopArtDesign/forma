<?php

namespace PopArtDesign\Forma;

require_once __DIR__ . '/helpers.php';

define('SITE_NAME', getSiteName());

define('MAILER_DSN', 'mail://localhost');
define('MAILER_SUBJECT', 'Сообщение с сайта '. SITE_NAME);
define('MAILER_FROM', 'no-reply@' . SITE_NAME);
define('MAILER_RECIPIENTS', [ 'user@localhost.localhost' ]);
define('MAILER_HTML', true);

define('ATTACHMENTS_MAX_SIZE', 10 * 1024 * 1024);

define('IMNOTAROBOT_VALUE', '');
define('IMNOTAROBOT_FIELD', 'imnotarobot');

define('RECAPTCHA_SECRET', '');
define('RECAPTCHA_FIELD', 'g-recaptcha-response');
define('RECAPTCHA_THRESHOLD', 0.5);
