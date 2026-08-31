<?php

declare(strict_types=1);

return [
    'manifest_version' => '2.0.0',
    'name' => 'int_sendgrid_email',
    'type' => 'integration',
    'provider' => 'Twilio SendGrid',
    'domain' => 'sendgrid_email',
    'version' => '2.0.0',
    'description' => 'Venny I/O SendGrid email transport cartridge for transactional email, templates, attachments, personalization, scheduling, tracking settings, and provider diagnostics.',
    'tool' => 'Mail Send API',
    'tool_url' => 'https://www.twilio.com/docs/sendgrid/api-reference/mail-send/mail-send',
    'php' => '>=8.2',
    'requires' => [
        'app_venny_platform',
    ],
    'dependencies' => [
        'php_extensions' => [],
        'composer' => [
            'sendgrid/sendgrid' => '^8.1',
        ],
        'npm' => [],
    ],
    'configuration' => [
        'v_SENDGRID_API_KEY',
        'v_SENDGRID_FROM_EMAIL',
        'v_SENDGRID_FROM_NAME',
        'v_SENDGRID_REPLY_TO_EMAIL',
        'v_SENDGRID_REPLY_TO_NAME',
        'v_SENDGRID_WEBHOOK_PUBLIC_KEY',
    ],
    'capabilities' => [
        'send_email',
        'send_template_email',
        'multiple_recipients',
        'cc_bcc',
        'reply_to',
        'attachments',
        'custom_headers',
        'custom_args',
        'categories',
        'send_at',
        'tracking_settings',
        'event_webhook_verification',
        'health_check',
    ],
    'documentation' => [
        'https://github.com/sendgrid/sendgrid-php',
        'https://www.twilio.com/docs/sendgrid/api-reference/mail-send/mail-send',
        'https://www.twilio.com/docs/sendgrid/for-developers/sending-email/api-getting-started',
        'https://www.twilio.com/docs/sendgrid/ui/account-and-settings/api-keys',
    ],
    'routes' => null,
    'sql' => [
        'schema' => null,
        'constraints' => null,
        'indexes' => null,
    ],
    'business_manager' => [
        'metadata' => __DIR__ . '/bm/metadata.php',
        'configuration' => __DIR__ . '/bm/configuration.php',
        'health' => __DIR__ . '/bm/health.php',
    ],
    'autoload' => [
        'Venny\\Cartridges\\SendGridEmail\\' => __DIR__ . '/src',
    ],
];
