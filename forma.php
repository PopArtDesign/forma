<?php

namespace PopArtDesign\Forma;

use PHPMailer\PHPMailer\DSNConfigurator;
use PHPMailer\PHPMailer\PHPMailer;
use Rakit\Validation\Rules as Rule;
use Rakit\Validation\Validator;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

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

    $value = \is_string($source[$key]) ? \trim($source[$key]) : $source[$key];

    return $value ?: $default;
}

/**
 * Возвращает все значения из запроса.
 *
 * @return array
 */
function getRequestAll()
{
    $source = $_SERVER['REQUEST_METHOD'] === 'GET' ? $_GET : $_POST;

    \array_walk_recursive($source, function (&$value) {
        if (\is_string($value)) {
            $value = \trim($value);
        }
    });

    return $source;
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
 * Завершает работу приложения с ошибками валидации.
 *
 * @param array $errors Ошибки для отправки клиенту
 */
function invalid($errors)
{
    jsendFail([ 'errors' => $errors ]);
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
    \header('Content-Type: application/json; charset=utf-8');

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
    \header('Content-Type: application/json; charset=utf-8');

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
    \header('Content-Type: application/json; charset=utf-8');

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
        $total = + $attachment['size'];
    }

    return $total;
}

/**
 * Проверяет поле "imnotarobot".
 *
 * @return void
 */
function imnotarobot()
{
    if (!$value = getConfig('imnotarobot_value')) {
        return;
    }

    $field = getConfig('imnotarobot_field', 'forma_imnotarobot');
    if (getRequest($field) !== $value) {
        fail('Некорректное значение антиспам-поля!');
    }
}

/**
 * Проверяет reCaptcha.
 *
 * @return void
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

    $curl = \curl_init('https://www.google.com/recaptcha/api/siteverify');
    \curl_setopt_array($curl, [
        \CURLOPT_POST => true,
        \CURLOPT_POSTFIELDS => \http_build_query($data),
        \CURLOPT_RETURNTRANSFER => true,
        \CURLOPT_SSL_VERIFYPEER => $options['ssl_verifypeer'] ?? true,
        \CURLOPT_TIMEOUT => $options['timeout'] ?? 0,
        \CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-type: application/x-www-form-urlencoded'
        ]
    ]);

    $response = \curl_exec($curl);
    \curl_close($curl);

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
 *
 * @return void
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

/**
 * Возвращает информацию об отправителе: IP-адрес URL, title и т.д.
 *
 * @return array
 */
function getClientInfo()
{
    return \array_merge(
        [
            'ip' => getRemoteIp(),
            'url' => $_SERVER['HTTP_REFERER'] ?? null,
            'title' => null,
            'timestamp' => null,
            'timezone' => null,
            'language' => \strtok($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', ',') ?: null,
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ],
        \json_decode(getRequest('forma_client_info', '{}'), true) ?? []
    );
}

/**
 * Выводит HTML-таблицу с информацией об отправителе.
 *
 * @return void
 */
function clientInfo()
{
    $data = getClientInfo();

    echo '<table><tbody>';
    $tr = '<tr><td><strong>%s:<strong>&nbsp;&nbsp</td><td>%s</td></tr>';
    printf($tr, 'IP-адрес', $data['ip']);
    printf($tr, 'URL', \htmlspecialchars($data['url'] ?? 'нет'));
    printf($tr, 'Заголовок', \htmlspecialchars($data['title'] ?? 'нет'));
    printf($tr, 'Время', \htmlspecialchars($data['timestamp'] ?? 'нет'));
    printf($tr, 'Часовой пояс', \htmlspecialchars($data['timezone'] ?? 'нет'));
    printf($tr, 'Язык', \htmlspecialchars($data['language'] ?? 'нет'));
    printf($tr, 'Браузер', \htmlspecialchars($data['userAgent'] ?? 'нет'));
    echo '</table></tbody>';
}

/**
 * Валидация данных формы.
 *
 * @see https://github.com/rakit/validation
 *
 * @param array $rules Правила валидации
 *
 * @return array Валидные данные
 */
function validate($rules)
{
    $data = getRequestAll() + $_FILES;

    $validation = getValidator()->validate($data, $rules);

    if ($validation->fails()) {
        invalid($validation->errors()->firstOfAll(':message', true));
    }

    return $validation->getValidData();
}

/**
 * Возвращает валидатор.
 *
 * @see https://github.com/rakit/validation
 *
 * @return Validator
 */
function getValidator()
{
    global $config;

    $messages = ($config['validation_messages'] ?? []) + [
        'accepted' => 'Поле должно быть принято.',
        'after' => 'Поле должно содержать дату после :time.',
        'alpha' => 'Поле должно содержать только буквы.',
        'alpha_dash' => 'Поле должно содержать только буквы, цифры, знаки тире и подчёркивания.',
        'alpha_num' => 'Поле должно содержать только буквы и цифры.',
        'alpha_spaces' => 'Поле должно содержать только буквы и пробелы.',
        'array' => 'Поле должно быть массивом.',
        'before' => 'Поле должно содержать дату до :time.',
        'between' => 'Значение должно быть между :min и :max.',
        'boolean' => 'Поле должно быть логического типа.',
        'date' => 'Поле должно быть корректной датой.',
        'different' => 'Поля должны различаться.',
        'digits' => 'Поле должно быть числовым и иметь длину :digits.',
        'digits_between' => 'Поле должно быть числовым и иметь длину между :min и :max.',
        'email' => 'Поле должно содержать корректный email.',
        'extension' => 'Файл должен иметь одно из следующих расширений: :extensions.',
        'in' => 'Выбранное значение недопустимо.',
        'integer' => 'Поле должно быть целым числом.',
        'ip' => 'Поле должно быть корректным IP-адресом.',
        'ipv4' => 'Поле должно быть корректным IPv4-адресом.',
        'ipv6' => 'Поле должно быть корректным IPv6-адресом.',
        'json' => 'Поле должно быть корректной строкой JSON.',
        'lowercase' => 'Поле должно быть в нижнем регистре.',
        'max' => 'Значение должно быть не больше :max.',
        'maxlength' => 'Длина поля должна быть не больше :max.',
        'mimes' => 'Файл должен иметь один из следующих типов: :allowed_types.',
        'min' => 'Значение должно быть не меньше :min.',
        'minlength' => 'Длина поля должна быть не меньше :min.',
        'not_in' => 'Выбранное значение недопустимо.',
        'numeric' => 'Поле должно быть числом.',
        'phone' => 'Поле должно быть корректным номером телефона.',
        'present' => 'Поле должно присутствовать.',
        'regex' => 'Формат поля неправильный.',
        'required' => 'Поле обязательно для заполнения.',
        'required_if' => 'Поле обязательно для заполнения.',
        'required_unless' => 'Поле обязательно для заполнения.',
        'required_with' => 'Поле обязательно для заполнения.',
        'required_with_all' => 'Поле обязательно для заполнения.',
        'required_without' => 'Поле обязательно для заполнения.',
        'required_without_all' => 'Поле обязательно для заполнения.',
        'same' => 'Поля должны совпадать.',
        'uploaded_file' => 'Поле должно быть файлом корректного типа и размера.',
        'uppercase' => 'Поле должно быть в верхнем регистре.',
        'url' => 'Поле должно быть корректным URL-адресом.',
    ];

    $validator = new Validator($messages);

    $validator->addValidator('minlength', new Rule\Min());
    $validator->addValidator('maxlength', new Rule\Max());
    $validator->addValidator('phone', new class () extends \Rakit\Validation\Rule {
        public function check($value): bool
        {
            if (!is_string($value)) {
                return false;
            }

            if (\strlen($value) < 10 || \strlen($value) >= 25) {
                return false;
            }

            if (!\preg_match('/^[0-9+\-\s()]+$/', $value)) {
                return false;
            }

            return true;
        }
    });

    return $validator;
}
