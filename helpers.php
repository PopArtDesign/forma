<?php

namespace PopArtDesign\Forma;

use PHPMailer\PHPMailer\DSNConfigurator;
use PHPMailer\PHPMailer\PHPMailer;

function getRequest($key, $default = null)
{
    return \trim($_REQUEST[$key] ?? $default);
}

function getConfig($key, $default = null)
{
    if (!\defined($key)) {
        return $default;
    }

    return \constant($key) ?? $default;
}

/**
 * @see https://github.com/omniti-labs/jsend?tab=readme-ov-file#success
 */
function jsendSuccess($data = null)
{
    die(\json_encode([
        'status' => 'success',
        'data' => $data,
    ]));
}

/**
 * @see https://github.com/omniti-labs/jsend?tab=readme-ov-file#fail
 */
function jsendFail($data = null)
{
    die(\json_encode([
        'status' => 'fail',
        'data' => $data,
    ]));
}

/**
 * @see https://github.com/omniti-labs/jsend?tab=readme-ov-file#error
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

function getSiteName()
{
    return \function_exists('idn_to_utf8') ? idn_to_utf8($_SERVER['SERVER_NAME']) : $_SERVER['SERVER_NAME'];
}

function getAttachments($keys)
{
    $attachments = collectAttachments($keys);

    $mb = 1024 * 1024;
    $maxSize = getConfig('ATTACHMENTS_MAX_SIZE', 10 * $mb);
    $attachmentsSize = calculateAttachmentsSize($attachments);

    if ($attachmentsSize > $maxSize) {
        jsendFail([ 'message' => \sprintf(
            'Общий размер файлов не должен превышать %s Мб!',
            $maxSize / $mb
        )]);
    }

    return $attachments;
}

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

function calculateAttachmentsSize($attachments)
{
    $total = 0;
    foreach ($attachments as $attachment) {
        $total =+ $attachment['size'];
    }

    return $total;
}

function imnotarobot()
{
    if (!$value = getConfig('IMNOTAROBOT_VALUE')) {
        return;
    }

    $field = getConfig('IMNOTAROBOT_FIELD');
    if (getRequest($field) !== $value) {
        jsendFail([ 'message' => 'Некорректное значение антиспам-поля!' ]);
    }
}

function recaptcha()
{
    if (!$secret = getConfig('RECAPTCHA_SECRET')) {
        return;
    }

    $field = getConfig('RECAPTCHA_FIELD', 'g-recaptcha-response');
    if (!$token = getRequest($field)) {
        jsendFail([ 'message' => 'Некорректное значение антиспам-поля!' ]);
    }

    $response = recaptchaVerify($token, getConfig('RECAPTCHA_SECRET'));

    if (!($response['success'] ?? false)) {
        jsendError('reCaptcha does not work');
    }

    $action = getConfig('RECAPTCHA_ACTION');
    if ($action && $response['action'] !== $recaptchaAction) {
        jsendFail([ 'message' => 'Не пройдена антиспам проверка!' ]);
    }

    $threshold = getConfig('RECAPTCHA_THRESHOLD', 0.5);
    if ($response['score'] < $threshold) {
        jsendFail([ 'message' => 'Не пройдена антиспам проверка!' ]);
    }
}

/**
 * @see https://developers.google.com/recaptcha/docs/verify
 */
function recaptchaVerify($token, $secret, $remoteIp = null)
{
    $data = [
        'secret' => $secret,
        'response' => $token,
    ];

    if ($remoteIp) {
        $data['remoteip'] = $remoteIp;
    }

    $ch = \curl_init('https://www.google.com/recaptcha/api/siteverify');
    \curl_setopt($ch, \CURLOPT_POST, true);
    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($data));
    \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
    \curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, false);
    \curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, 10);
    \curl_setopt($ch, \CURLOPT_TIMEOUT, 10);
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

function sendMail($message, $attachments = [])
{
    if (!$dsn = getConfig('MAILER_DSN')) {
        return;
    }

    try {
        $mail = DSNConfigurator::mailer($dsn, true);

        $mail->Subject = getConfig('MAILER_SUBJECT');
        $mail->setFrom(getConfig('MAILER_FROM'));
        foreach (getConfig('MAILER_RECIPIENTS') as $recipient) {
            $mail->addAddress($recipient);
        }
        $mail->Body = $message;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->isHtml(getConfig('MAILER_HTML'));

        foreach ($attachments as $attachment) {
            $mail->addAttachment($attachment['path'], $attachment['name']);
        }

        $mail->send();
    } catch (\Exception $e) {
        jsendError("Can't send email");
    }
}
