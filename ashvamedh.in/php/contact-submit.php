<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => $result['message'],
    ]);
    exit;
}

$name = clean_text($_POST['name'] ?? '');
$email = clean_text($_POST['email'] ?? '');
$phone = preg_replace('/\D+/', '', (string) ($_POST['phone'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

$errors = [];

if ($name === '' || app_strlen($name) < 2) {
    $errors['name'] = 'Name must be at least 2 characters.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
}

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    $errors['phone'] = 'Please enter a valid 10-digit mobile number.';
}

if ($message === '' || app_strlen($message) < 10) {
    $errors['message'] = 'Message must be at least 10 characters.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed.',
        'errors' => $errors,
    ]);
    exit;
}

$config = app_config();

$htmlBody = '
    <h2>New Contact Enquiry</h2>
    <table cellpadding="8" cellspacing="0" border="1" style="border-collapse:collapse;">
        <tr><td><strong>Name</strong></td><td>' . esc($name) . '</td></tr>
        <tr><td><strong>Email</strong></td><td>' . esc($email) . '</td></tr>
        <tr><td><strong>Mobile</strong></td><td>+91 ' . esc($phone) . '</td></tr>
        <tr><td><strong>Message</strong></td><td>' . nl2br(esc($message)) . '</td></tr>
    </table>
';

$textBody = implode("\n", [
    'New Contact Enquiry Form Submission',
    'Name: ' . $name,
    'Email: ' . $email,
    'Mobile: +91 ' . $phone,
    'Message: ' . $message,
]);

$result = send_app_email(
    'New Contact Enquiry Form Submission - ' . $config['site_name'],
    $htmlBody,
    $textBody,
    $email,
    $name
);

if (!$result['success']) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send message.',
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Message sent successfully.',
]);