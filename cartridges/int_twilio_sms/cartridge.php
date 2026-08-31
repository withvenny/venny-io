<?php

declare(strict_types=1);

return [
    'manifest_version' => '2.0.0',
    'name' => 'int_twilio_sms',
    'type' => 'integration',
    'provider' => 'Twilio',
    'domain' => 'twilio_sms',
    'version' => '2.0.0',
    'description' => 'Venny I/O Twilio SMS transport cartridge for outbound SMS/MMS, messaging-service routing, media URLs, status callbacks, inbound webhook validation, and provider diagnostics.',
    'tool' => 'Programmable Messaging',
    'tool_url' => 'https://www.twilio.com/docs/messaging',
    'php' => '>=8.2',
    'requires' => [
        'app_venny_platform',
    ],
    'dependencies' => [
        'php_extensions' => [],
        'composer' => [
            'twilio/sdk' => '^8.0',
        ],
        'npm' => [],
    ],
    'configuration' => [
        'v_TWILIO_ACCOUNT_SID',
        'v_TWILIO_AUTH_TOKEN',
        'v_TWILIO_MESSAGING_SERVICE_SID',
        'v_TWILIO_FROM_NUMBER',
        'v_TWILIO_STATUS_CALLBACK_URL',
    ],
    'capabilities' => [
        'send_sms',
        'send_mms',
        'messaging_service',
        'from_number_fallback',
        'status_callback',
        'retrieve_message',
        'cancel_scheduled_message',
        'webhook_signature_validation',
        'health_check',
    ],
    'documentation' => [
        'https://www.twilio.com/docs/messaging',
        'https://www.twilio.com/docs/messaging/quickstart',
        'https://www.twilio.com/docs/messaging/tutorials/how-to-send-sms-messages',
        'https://www.twilio.com/docs/usage/webhooks/webhooks-security',
        'https://github.com/twilio/twilio-php',
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
        'Venny\\Cartridges\\TwilioSms\\' => __DIR__ . '/src',
    ],
];
