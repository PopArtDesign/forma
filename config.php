<?php

namespace PopArtDesign\Forma;

require_once __DIR__ . '/helpers.php';

define('SITE_NAME', getSiteName());

define('MAILER_DSN', 'mail://localhost');
define('MAILER_SUBJECT', 'Сообщение с сайта '. SITE_NAME);
define('MAILER_FROM', 'no-reply@' . SITE_NAME);
define('MAILER_RECIPIENTS', [ 'user@localhost.localhost' ]);
define('MAILER_HTML', true);

define('IMNOTAROBOT_FIELD', 'imnotarobot');
define('IMNOTAROBOT_VALUE', 'imnotarobot!');

define('RECAPTCHA_FIELD', 'g-recaptcha-response');
define('RECAPTCHA_SECRET', '');
define('RECAPTCHA_THRESHOLD', 0.5);
define('RECAPTCHA_VERIFY_URL', 'https://www.google.com/recaptcha/api/siteverify');
