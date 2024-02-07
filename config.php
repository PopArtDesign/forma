<?php

namespace PopArtDesign\Forma;

require_once __DIR__ . '/helpers.php';

define('SITE_NAME', getSiteName());

define('MAILER_DSN', 'mail://localhost');
define('MAILER_SUBJECT', 'Сообщение с сайта '. SITE_NAME);
define('MAILER_FROM', 'no-reply@' . SITE_NAME);
define('MAILER_RECIPIENTS', [ 'user@localhost.localhost' ]);
define('MAILER_HTML', true);

define('NOROBOT_KEY', '_secret');
define('NOROBOT_VALUE', 'imnotarobot!');
