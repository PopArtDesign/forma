<?php

namespace PopArtDesign\Forma;

$config['site_name'] = getSiteName();

$config['mail_dsn'] = 'mail://localhost';
$config['mail_subject'] = 'Сообщение с сайта ' . $config['site_name'];
$config['mail_from'] = 'no-reply@' . $config['site_name'];
$config['mail_recipients'] = [ 'user@localhost.localhost' ];
$config['mail_message'] = 'Сообщение с сайта ' . $config['site_name'];
$config['mail_html'] = true;
$config['mail_attachments'] = [];
$config['mail_attachments_max_size'] = 10 * 1024 * 1024;

$config['imnotarobot_value'] = '';
$config['imnotarobot_field'] = 'imnotarobot';

$config['recaptcha_secret'] = '';
$config['recaptcha_action'] = '';
$config['recaptcha_hostname'] = $config['site_name'];
$config['recaptcha_field'] = 'g-recaptcha-response';
$config['recaptcha_threshold'] = 0.5;
$config['recaptcha_timeout'] = 30;
$config['recaptcha_ssl_verifypeer'] = true;
