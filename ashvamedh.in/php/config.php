<?php

declare(strict_types=1);

return [
    'site_name' => 'TenderLink',

    // Email where submissions should arrive
    'mail_to' => 'contact@etenderlink.in',

    // SMTP sender address from your domain email account
    'mail_from' => 'contact@etenderlink.in',
    'mail_from_name' => 'etenderlink.com Website',

    // SMTP settings from your hosting/email provider
    'smtp_host' => 'mail.etenderlink.in',
    'smtp_username' => 'contact@etenderlink.in',
    'smtp_password' => 'P,`,!,~,8?x?*^,vS,/%=+u?',
    'smtp_port' => 587,
    'smtp_encryption' => 'tls', // use 'tls' or 'ssl'

    // Upload settings
    'max_resume_size' => 5 * 1024 * 1024, // 5MB
    'allowed_resume_extensions' => ['pdf', 'doc', 'docx'],
    'uploads_dir' => __DIR__ . '/../uploads/resumes',
];
