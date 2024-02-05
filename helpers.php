<?php

namespace PopArtDesign\Forma;

const CRLF = "\r\n";
const MAX_LINE_LENGTH = 76;

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

function detectDomain()
{
    return \function_exists('idn_to_utf8') ? idn_to_utf8($_SERVER['SERVER_NAME']) : $_SERVER['SERVER_NAME'];
}

function uploadedFilesToAttachments($keys)
{
    $attachments = [];

    foreach ($keys as $key) {
        if (!isset($_FILES[$key])) {
            break;
        }

        $name = $_FILES[$key]['name'];
        if (is_array($name)) {
            for ($i = 0; $i < count($name); $i++) {
                $path = $_FILES[$key]['tmp_name'][$i];
                if (\is_uploaded_file($path)) {
                    $attachments[] = [
                        'path' => $path,
                        'name' => $_FILES[$key]['name'][$i],
                        'type' => $_FILES[$key]['type'][$i],
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
                ];
            }
        }
    }

    return $attachments;
}

function sendMail($to, $subject, $message, $options = [])
{
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    if ($options['sender'] ?? false) {
        $headers[] = 'Sender: ' . $options['sender'];
    }
    if ($options['from'] ?? false) {
        $headers[] = 'From: ' . $options['from'];
    }

    $attachments = $options['attachments'] ?? [];
    if (count($attachments) > 0) {
        $boundary = generateMailBoundary();
        $headers[] = 'Content-Type: multipart/form-data; boundary=' . $boundary;
        $msg = "--$boundary" . CRLF;
        $msg .= 'Content-Type: text/plain; charset=UTF-8' . CRLF;
        $msg .= 'Content-Transfer-Encoding: base64' . CRLF . CRLF;
        $msg .= encodeMailContent($message) . CRLF;

        foreach ($attachments as $attachment) {
            $path = $attachment['path'];
            $name = $attachment['name'] ?? \basename($path);
            $type = $attachment['type'] ?? 'application/octet-stream';

            $msg .= "--$boundary" . CRLF;
            $msg .= \sprintf('Content-Type: %s; name="%s"', $type, $name) . CRLF;
            $msg .= \sprintf('Content-Disposition: attachment; filename="%s"', $name) . CRLF;
            $msg .= 'Content-Transfer-Encoding: base64' . CRLF . CRLF;
            $msg .= encodeMailAttachment($attachment['path']) . CRLF;
        }

        $msg .= '--' . $boundary . '--';
    } else {
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $msg = $message;
    }

    return \mail($to, $subject, $msg, implode(CRLF, $headers));
}

function encodeMailContent($content)
{
    return \chunk_split(\base64_encode($content), MAX_LINE_LENGTH, CRLF);
}

function encodeMailAttachment($path)
{
    return encodeMailContent(\file_get_contents($path));
}

function generateMailBoundary()
{
    return \md5(\microtime());
}
