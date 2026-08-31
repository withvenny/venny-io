<?php

declare(strict_types=1);

return [
    'name' => 'int_stripe_payments',
    'display_name' => 'Stripe Payments',
    'version' => '1.0.0',
    'type' => 'integration',
    'provider' => 'Stripe',
    'tool' => 'Payments',
    'description' => 'Provides Stripe customer, payment, refund, Checkout, subscription, invoice, webhook, and diagnostics capabilities to Venny I/O.',
    'purpose' => 'Provide Venny I/O application cartridges with a reusable server-side Stripe integration for payment and billing workflows while keeping Stripe SDK calls, authentication, webhook verification, provider errors, and request semantics isolated from application-domain code.',
    'tool_url' => 'https://docs.stripe.com/api',
    'documentation' => [
        'https://docs.stripe.com/api',
        'https://docs.stripe.com/webhooks',
        'https://github.com/stripe/stripe-php',
    ],
    'configuration' => [
        [
            'key' => 'v_STRIPE_SECRET_KEY',
            'required' => true,
            'secret' => true,
        ],
        [
            'key' => 'v_STRIPE_PUBLISHABLE_KEY',
            'required' => false,
            'secret' => false,
        ],
        [
            'key' => 'v_STRIPE_WEBHOOK_SECRET',
            'required' => false,
            'secret' => true,
        ],
        [
            'key' => 'v_STRIPE_API_VERSION',
            'required' => false,
            'secret' => false,
        ],
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
    'dependencies' => [
        'php' => '>=8.1',
        'composer' => [
            'stripe/stripe-php' => '^21.2',
        ],
    ],
    'health_check_available' => true,
    'configuration_screen_available' => true,
    'owns_sql' => false,
];
