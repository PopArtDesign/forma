<?php

namespace PopArtDesign\Forma;

use PHPMailer\PHPMailer\DSNConfigurator;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Возвращает значение из запроса.
 *
 * @param string $key     Ключ
 * @param mixed  $default Значение по умолчанию
 *
 * @return mixed
 */
function getRequest($key, $default = null)
{
    $source = $_SERVER['REQUEST_METHOD'] === 'GET' ? $_GET : $_POST;

    if (!isset($source[$key])) {
        return $default;
    }

    if ('' === $value = \trim((string) $source[$key])) {
        return $default;
    }

    return $value;
}

/**
 * Возвращает значение конфигурации.
 *
 * @param string $key     Ключ
 * @param mixed  $default Значение по умолчанию
 *
 * @return mixed
 */
function getConfig($key, $default = null)
{
    global $config;

    return $config[$key] ?? $default;
}

/**
 * Успешно завершает работу приложения.
 *
 * @param string $message Сообщение для отправки клиенту
 */
function success($message = null)
{
    jsendSuccess($message ? [ 'message' => $message ] : null);
}

/**
 * Завершает работу приложения с ошибкой.
 *
 * @param string $message Сообщение для отправки клиенту
 */
function fail($message = 'Форма заполнена неправильно!')
{
    jsendFail([ 'message' => $message ]);
}

/**
 * Завершает работу приложения с фатальной ошибкой.
 *
 * @param string $message Сообщение для отправки клиенту
 */
function error($message = null)
{
    jsendError($message);
}

/**
 * Отправляет JSend success-ответ и завершает работу приложения.
 *
 * @see https://github.com/omniti-labs/jsend?tab=readme-ov-file#success
 *
 * @param array $data Массив данных для ответа
 */
function jsendSuccess($data = null)
{
    die(\json_encode([
        'status' => 'success',
        'data' => $data,
    ]));
}

/**
 * Отправляет JSend fail-ответ и завершает работу приложения.
 *
 * @see https://github.com/omniti-labs/jsend?tab=readme-ov-file#fail
 *
 * @param array $data Массив данных для ответа
 */
function jsendFail($data = null)
{
    die(\json_encode([
        'status' => 'fail',
        'data' => $data,
    ]));
}

/**
 * Отправляет JSend fail-ответ и завершает работу приложения.
 *
 * @see https://github.com/omniti-labs/jsend?tab=readme-ov-file#error
 *
 * @param array $data Массив данных для ответа
 */
function jsendError($message = 'An error occurred. Please try again later!')
{
    die(\json_encode([
        'status' => 'error',
        'message' => $message,
    ]));
}

function loadTemplate($filename, $params = [])
{
    \extract($params);

    \ob_start();

    require $filename;

    return \ob_get_clean();
}

/**
 * Определяет доменное имя текущего сайта.
 *
 * @return string
 */
function getSiteName()
{
    return \function_exists('idn_to_utf8') ? \idn_to_utf8($_SERVER['SERVER_NAME']) : $_SERVER['SERVER_NAME'];
}

/**
 * Собирает загруженные файлы в массив вложений.
 *
 * @param array $keys Имена полей формы
 *
 * @return array
 */
function collectAttachments($keys)
{
    $attachments = [];

    foreach ($keys as $key) {
        if (!isset($_FILES[$key])) {
            break;
        }

        $name = $_FILES[$key]['name'];
        if (\is_array($name)) {
            for ($i = 0; $i < \count($name); $i++) {
                $path = $_FILES[$key]['tmp_name'][$i];
                if (\is_uploaded_file($path)) {
                    $attachments[] = [
                        'path' => $path,
                        'name' => $_FILES[$key]['name'][$i],
                        'type' => $_FILES[$key]['type'][$i],
                        'size' => $_FILES[$key]['size'][$i],
                    ];
                }
            }
        } else {
            $path = $_FILES[$key]['tmp_name'];
            if (\is_uploaded_file($path)) {
                $attachments[] = [
                    'path' => $path,
                    'name' => $name,
                    'type' => $_FILES[$key]['type'],
                    'size' => $_FILES[$key]['size'],
                ];
            }
        }
    }

    return $attachments;
}

/**
 * Подсчитывает общий объём вложений в байтах.
 *
 * @param array $attachments Массив с вложениями
 *
 * @return int Количество байт
 */
function calculateAttachmentsSize($attachments)
{
    $total = 0;
    foreach ($attachments as $attachment) {
        $total =+ $attachment['size'];
    }

    return $total;
}

/**
 * Проверяет поле "imnotarobot".
 */
function imnotarobot()
{
    if (!$value = getConfig('imnotarobot_value')) {
        return;
    }

    $field = getConfig('imnotarobot_field', 'imnotarobot');
    if (getRequest($field) !== $value) {
        fail('Некорректное значение антиспам-поля!');
    }
}

/**
 * Проверяет reCaptcha.
 */
function recaptcha()
{
    if (!$secret = getConfig('recaptcha_secret')) {
        return;
    }

    $field = getConfig('recaptcha_field', 'g-recaptcha-response');
    if (!$token = getRequest($field)) {
        fail('Некорректное значение антиспам-поля!');
    }

    $options = [
        'timeout' => getConfig('recaptcha_timeout', 30),
        'ssl_verifypeer' => getConfig('recaptcha_ssl_verifypeer', true),
        'remoteip' => getRemoteIp(),
    ];

    $response = recaptchaVerify($token, $secret);

    if (!($response['success'] ?? false)) {
        error('reCaptcha does not work');
    }

    $hostname = getConfig('recaptcha_hostname', getConfig('site_name'));
    if ($hostname && $response['hostname'] !== $hostname) {
        fail('Не пройдена антиспам проверка!');
    }

    $action = getConfig('recaptcha_action');
    if ($action && $response['action'] !== $action) {
        fail('Не пройдена антиспам проверка!');
    }

    $threshold = getConfig('recaptcha_threshold', 0.5);
    if ($response['score'] < $threshold) {
        fail('Не пройдена антиспам проверка!');
    }
}

/**
 * Верифицирует токен reCaptcha.
 *
 * @see https://developers.google.com/recaptcha/docs/verify
 *
 * @param string $token   Токен полученный от посетителя
 * @param string $secret  Секретное значение
 * @param array  $options Массив с доп. настройками
 *
 * @return array Результат проверки
 */
function recaptchaVerify($token, $secret, $options = [])
{
    $data = [
        'secret' => $secret,
        'response' => $token,
    ];

    if ($options['remoteip'] ?? null) {
        $data['remoteip'] = $options['remoteip'];
    }

    $ch = \curl_init('https://www.google.com/recaptcha/api/siteverify');
    \curl_setopt($ch, \CURLOPT_POST, true);
    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($data));
    \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
    \curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, $options['ssl_verifypeer'] ?? true);
    \curl_setopt($ch, \CURLOPT_TIMEOUT, $options['timeout'] ?? 0);
    \curl_setopt($ch, \CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-type: application/x-www-form-urlencoded'
    ]);

    $response = \curl_exec($ch);
    \curl_close($ch);

    if (!$response) {
        return null;
    }

    return \json_decode($response, true);
}

/**
 * Возвращает IP-адрес посетителя.
 *
 * @see https://robert-michalski.com/blog/php-get-ip-of-request/
 *
 * @return string
 */
function getRemoteIp()
{
    $ipKeys = [
        // Providers
        'HTTP_CF_CONNECTING_IP',    // Cloudflare
        'HTTP_INCAP_CLIENT_IP',     // Incapsula
        'HTTP_X_CLUSTER_CLIENT_IP', // RackSpace
        'HTTP_TRUE_CLIENT_IP',      // Akamai

        // Proxies
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_CLIENT_IP',
        'HTTP_X_REAL_IP',
        'HTTP_FORWARDED',
        'HTTP_FORWARDED_FOR',

        // Fallback
        'REMOTE_ADDR'
    ];

    foreach ($ipKeys as $key) {
        if ($ip = $_SERVER[$key] ?? null) {
            return $ip;
        }
    }

    return null;
}

/**
 * Отправляет письмо.
 */
function mail()
{
    if (!$dsn = getConfig('mail_dsn')) {
        return;
    }

    try {
        $mail = DSNConfigurator::mailer($dsn, true);

        $mail->Subject = getConfig('mail_subject');
        $mail->setFrom(getConfig('mail_from'));
        foreach (getConfig('mail_recipients') as $recipient) {
            $mail->addAddress($recipient);
        }
        $mail->Body = getConfig('mail_message');
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->isHtml(getConfig('mail_html', false));

        $attachments = getConfig('mail_attachments', []);
        if (\count($attachments) > 0) {
            $mb = 1024 * 1024;
            $maxSize = getConfig('mail_attachments_max_size', 10 * $mb);
            $attachmentsSize = calculateAttachmentsSize($attachments);

            if ($attachmentsSize > $maxSize) {
                fail(\sprintf(
                    'Общий размер файлов не должен превышать %s Мб!',
                    $maxSize / $mb
                ));
            }

            foreach ($attachments as $attachment) {
                $mail->addAttachment($attachment['path'], $attachment['name']);
            }
        }

        $mail->send();
    } catch (\Exception $e) {
        error("Can't send email");
    }
}
