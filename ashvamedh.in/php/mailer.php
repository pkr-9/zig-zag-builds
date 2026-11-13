<?php

declare(strict_types=1);

if (!file_exists(__DIR__ . '/vendor/PHPMailer/src/Exception.php')) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'PHPMailer source files are missing in php/vendor/PHPMailer/src/',
    ]);
    exit;
}

require __DIR__ . '/vendor/PHPMailer/src/Exception.php';
require __DIR__ . '/vendor/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function app_config(): array
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }

    return $config;
}

function clean_text(?string $value): string
{
    $value = trim((string) $value);
    return preg_replace('/\s+/', ' ', $value) ?? '';
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function app_strlen(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function send_app_email(
    string $subject,
    string $htmlBody,
    string $textBody = '',
    ?string $replyToEmail = null,
    ?string $replyToName = null,
    array $attachments = []
): array {
    $config = app_config();
    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];

        $encryption = strtolower((string) $config['smtp_encryption']);
        $mail->SMTPSecure = $encryption === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = (int) $config['smtp_port'];
        $mail->setFrom($config['mail_from'], $config['mail_from_name']);
        $mail->addAddress($config['mail_to'], $config['site_name']);

        if ($replyToEmail && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyToEmail, $replyToName ?: $replyToEmail);
        }

        foreach ($attachments as $attachment) {
            if (!empty($attachment['path']) && is_readable($attachment['path'])) {
                $mail->addAttachment(
                    $attachment['path'],
                    $attachment['name'] ?? basename($attachment['path'])
                );
            }
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody !== '' ? $textBody : strip_tags($htmlBody);

        $mail->send();

        return [
            'success' => true,
            'message' => 'Email sent successfully.',
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $mail->ErrorInfo ?: $e->getMessage(),
        ];
    }
}