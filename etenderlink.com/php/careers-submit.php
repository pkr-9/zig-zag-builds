<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.',
    ]);
    exit;
}

$config = app_config();

$fullName = clean_text($_POST['fullName'] ?? '');
$email = clean_text($_POST['email'] ?? '');
$phone = preg_replace('/\D+/', '', (string) ($_POST['phone'] ?? ''));
$location = clean_text($_POST['location'] ?? '');
$desiredPosition = clean_text($_POST['desiredPosition'] ?? '');
$otherPosition = clean_text($_POST['otherPosition'] ?? '');
$experience = clean_text($_POST['experience'] ?? '');
$profileLink = clean_text($_POST['profileLink'] ?? '');
$coverNote = trim((string) ($_POST['coverNote'] ?? ''));

$errors = [];

if ($fullName === '' || app_strlen($fullName) < 2) {
    $errors['fullName'] = 'Full name is required.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
}

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    $errors['phone'] = 'Please enter a valid 10-digit mobile number.';
}

if ($location === '') {
    $errors['location'] = 'Current location is required.';
}

if ($desiredPosition === '') {
    $errors['desiredPosition'] = 'Desired job position is required.';
}

if ($desiredPosition === 'Other' && $otherPosition === '') {
    $errors['otherPosition'] = 'Please specify the desired job position.';
}

if ($experience === '') {
    $errors['experience'] = 'Experience is required.';
}

if ($profileLink === '' || !filter_var($profileLink, FILTER_VALIDATE_URL)) {
    $errors['profileLink'] = 'Please enter a valid profile URL.';
}

if ($coverNote === '' || app_strlen($coverNote) < 50) {
    $errors['coverNote'] = 'Cover note must be at least 50 characters.';
}

if (!isset($_FILES['resume']) || !is_array($_FILES['resume'])) {
    $errors['resume'] = 'Resume file is required.';
} else {
    $resume = $_FILES['resume'];

    if (($resume['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors['resume'] = 'Resume upload failed. Please try again.';
    } elseif ((int) $resume['size'] > (int) $config['max_resume_size']) {
        $errors['resume'] = 'Resume size must not exceed 5MB.';
    } else {
        $originalName = (string) ($resume['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $config['allowed_resume_extensions'], true)) {
            $errors['resume'] = 'Only PDF, DOC, and DOCX files are allowed.';
        }
    }
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

$resume = $_FILES['resume'];
$uploadDir = $config['uploads_dir'];

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to create upload directory.',
    ]);
    exit;
}

$originalName = (string) $resume['name'];
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$safeBaseName = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($originalName, PATHINFO_FILENAME)) ?: 'resume';
$storedFileName = $safeBaseName . '_' . uniqid('', true) . '.' . $extension;
$storedPath = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $storedFileName;

if (!move_uploaded_file($resume['tmp_name'], $storedPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save uploaded resume.',
    ]);
    exit;
}

$jobPositionForEmail = $desiredPosition === 'Other' ? $otherPosition : $desiredPosition;

$htmlBody = '
    <h2>New Careers Application</h2>
    <table cellpadding="8" cellspacing="0" border="1" style="border-collapse:collapse;">
        <tr><td><strong>Full Name</strong></td><td>' . esc($fullName) . '</td></tr>
        <tr><td><strong>Email</strong></td><td>' . esc($email) . '</td></tr>
        <tr><td><strong>Mobile</strong></td><td>+91 ' . esc($phone) . '</td></tr>
        <tr><td><strong>Current Location</strong></td><td>' . esc($location) . '</td></tr>
        <tr><td><strong>Desired Position</strong></td><td>' . esc($jobPositionForEmail) . '</td></tr>
        <tr><td><strong>Experience</strong></td><td>' . esc($experience) . '</td></tr>
        <tr><td><strong>Profile Link</strong></td><td><a href="' . esc($profileLink) . '">' . esc($profileLink) . '</a></td></tr>
        <tr><td><strong>Cover Note</strong></td><td>' . nl2br(esc($coverNote)) . '</td></tr>
        <tr><td><strong>Resume File</strong></td><td>' . esc($originalName) . '</td></tr>
    </table>
';

$textBody = implode("\n", [
    'New Careers Application',
    'Full Name: ' . $fullName,
    'Email: ' . $email,
    'Mobile: +91 ' . $phone,
    'Current Location: ' . $location,
    'Desired Position: ' . $jobPositionForEmail,
    'Experience: ' . $experience,
    'Profile Link: ' . $profileLink,
    'Cover Note: ' . $coverNote,
    'Resume File: ' . $originalName,
]);

$result = send_app_email(
    'New Careers Application - ' . $config['site_name'],
    $htmlBody,
    $textBody,
    $email,
    $fullName,
    [
        [
            'path' => $storedPath,
            'name' => $originalName,
        ],
    ]
);

if (is_file($storedPath)) {
    @unlink($storedPath);
}

if (!$result['success']) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send application.',
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Application submitted successfully.',
]);