<?php

namespace PopArtDesign\Forma;

const CRLF = "\r\n";
const MAX_LINE_LENGTH = 76;

function getRequest($key, $default = null)
{
    return \trim($_REQUEST[$key] ?? $default);
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
    if (!\is_file($filename)) {
        return false;
    }

    \extract($params);

    \ob_start();

    include $filename;

    return \ob_get_clean();
}

function getSiteName()
{
    return \function_exists('idn_to_utf8') ? idn_to_utf8($_SERVER['SERVER_NAME']) : $_SERVER['SERVER_NAME'];
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
            for ($i = 0; $i < count($name); $i++) {
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
