<?php

declare(strict_types=1);

return [
    'manifest_version' => '2.0.0',
    'name' => 'int_stripe_payments',
    'type' => 'integration',
    'provider' => 'Stripe',
    'domain' => 'stripe_payments',
    'version' => '2.0.0',
    'description' => 'Venny I/O Stripe integration cartridge for customers, PaymentIntents, refunds, Checkout, subscriptions, invoices, webhook verification, and payment diagnostics.',
    'tool' => 'Payments',
    'tool_url' => 'https://docs.stripe.com/api',
    'php' => '>=8.2',
    'requires' => [
        'app_venny_platform',
    ],
    'dependencies' => [
        'php_extensions' => [],
        'composer' => [
            'stripe/stripe-php' => '^21.2',
        ],
        'npm' => [],
    ],
    'configuration' => [
        'v_STRIPE_SECRET_KEY',
        'v_STRIPE_PUBLISHABLE_KEY',
        'v_STRIPE_WEBHOOK_SECRET',
        'v_STRIPE_API_VERSION',
    ],
    'capabilities' => [
        'customers',
        'payment_intents',
        'payment_capture',
        'refunds',
        'checkout_sessions',
        'subscriptions',
        'invoices',
        'webhook_verification',
        'configuration_validation',
        'health_check',
    ],
    'documentation' => [
        'https://docs.stripe.com/api',
        'https://docs.stripe.com/webhooks',
        'https://github.com/stripe/stripe-php',
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
        'Venny\\Cartridges\\StripePayments\\' => __DIR__ . '/src',
    ],
];
