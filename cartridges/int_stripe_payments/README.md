# int_stripe_payments

## Purpose

`int_stripe_payments` gives Venny I/O a reusable server-side adapter for Stripe payment and billing capabilities without coupling application cartridges directly to Stripe's PHP SDK. It exposes customer management, PaymentIntents, confirmation and capture, refunds, Checkout Sessions, subscriptions, invoices, and webhook-signature verification through a consistent PHP surface. The cartridge owns provider communication and provider-specific validation, but it does not become Venny I/O's system of record for orders, subscriptions, invoices, customers, entitlements, or accounting data. Those business records remain with the Venny cartridge that owns the corresponding domain.

## Provider

Stripe

## Tool

Stripe Payments and Billing APIs

Official API reference:

https://docs.stripe.com/api

Official PHP SDK:

https://github.com/stripe/stripe-php

Webhook documentation:

https://docs.stripe.com/webhooks

## Installation

This cartridge uses vanilla PHP with Composer.

From the cartridge directory:

```bash
composer install
```

Or when dependencies are managed by the parent Venny I/O project:

```bash
composer require stripe/stripe-php:^21.2
composer dump-autoload
```

Venny I/O requires PHP 8.1 or newer for this cartridge.

## Configuration

### `v_STRIPE_SECRET_KEY`

Required.

Server-side Stripe secret API key.

Example prefix:

```text
sk_test_
sk_live_
```

Never expose this value to a browser, client application, log, README, or Business Manager response.

### `v_STRIPE_PUBLISHABLE_KEY`

Optional for server-side cartridge operation, but useful to consuming browser/mobile applications that use Stripe client libraries.

Example prefix:

```text
pk_test_
pk_live_
```

This value is not secret, but the cartridge still treats configuration output conservatively.

### `v_STRIPE_WEBHOOK_SECRET`

Required when `verifyWebhook()` is used.

Stripe webhook endpoint signing secret.

Example prefix:

```text
whsec_
```

This value is secret.

### `v_STRIPE_API_VERSION`

Optional.

Explicit Stripe API version to send through the Stripe PHP client.

If omitted, the installed Stripe PHP SDK's API-version behavior applies.

Venny I/O does not invent an API version on behalf of the installation. Pin one deliberately when the application requires stable API semantics.

## Capabilities

### Customers

```php
createCustomer(array $params)
retrieveCustomer(string $customerId, array $params = [])
updateCustomer(string $customerId, array $params)
```

### PaymentIntents

```php
createPaymentIntent(array $params, ?string $idempotencyKey = null)
retrievePaymentIntent(string $paymentIntentId, array $params = [])
confirmPaymentIntent(string $paymentIntentId, array $params = [], ?string $idempotencyKey = null)
capturePaymentIntent(string $paymentIntentId, array $params = [], ?string $idempotencyKey = null)
cancelPaymentIntent(string $paymentIntentId, array $params = [])
```

The cartridge uses Stripe's PaymentIntent model as the primary direct-payment primitive.

### Refunds

```php
createRefund(array $params, ?string $idempotencyKey = null)
retrieveRefund(string $refundId)
```

Refund parameters are passed through to Stripe so callers can use documented Stripe refund fields without waiting for a cartridge release for every optional parameter.

### Checkout Sessions

```php
createCheckoutSession(array $params, ?string $idempotencyKey = null)
retrieveCheckoutSession(string $sessionId, array $params = [])
expireCheckoutSession(string $sessionId)
```

The caller remains responsible for choosing whether the Checkout Session represents a payment, setup, or subscription flow and for supplying the corresponding documented Stripe fields.

### Subscriptions

```php
createSubscription(array $params, ?string $idempotencyKey = null)
retrieveSubscription(string $subscriptionId, array $params = [])
updateSubscription(string $subscriptionId, array $params)
cancelSubscription(string $subscriptionId, array $params = [])
```

### Invoices

```php
createInvoice(array $params, ?string $idempotencyKey = null)
retrieveInvoice(string $invoiceId, array $params = [])
finalizeInvoice(string $invoiceId, array $params = [])
payInvoice(string $invoiceId, array $params = [])
voidInvoice(string $invoiceId)
```

### Webhook Verification

```php
verifyWebhook(string $payload, string $signatureHeader, ?int $tolerance = null)
```

Stripe signs webhook deliveries. This method delegates verification to Stripe's official PHP SDK using `v_STRIPE_WEBHOOK_SECRET`.

The result contains normalized event information and preserves the Stripe event object data. A consuming Venny event/router layer should decide what application behavior follows.

### Health Check

```php
healthCheck()
```

The health check validates local configuration and, when a secret key is present, performs a read-only Stripe account retrieval. It does not create a customer, PaymentIntent, charge, refund, subscription, invoice, or other mutable Stripe object.

## Usage

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Venny\Cartridges\StripePayments\Config;
use Venny\Cartridges\StripePayments\Provider;

$provider = new Provider(Config::fromEnvironment());

$result = $provider->createPaymentIntent(
    [
        'amount' => 2500,
        'currency' => 'usd',
        'automatic_payment_methods' => [
            'enabled' => true,
        ],
        'metadata' => [
            'venny_reference' => 'order_1234',
        ],
    ],
    'order_1234-payment-v1'
);

$paymentIntent = $result->data()['object'];
```

Customer example:

```php
$customer = $provider->createCustomer([
    'email' => 'customer@example.com',
    'name' => 'Example Customer',
]);
```

Checkout example:

```php
$session = $provider->createCheckoutSession([
    'mode' => 'payment',
    'success_url' => 'https://example.com/success?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => 'https://example.com/cancel',
    'line_items' => [
        [
            'price' => 'price_123',
            'quantity' => 1,
        ],
    ],
]);
```

Webhook example:

```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

$event = $provider->verifyWebhook($payload, $signature);

$type = $event->data()['type'];
```

## Idempotency

Stripe supports idempotency keys for mutating requests.

Where transaction duplication would be consequential, the cartridge accepts an optional idempotency key and passes it as a Stripe request option.

Consuming Venny cartridges should generate idempotency keys from stable business-operation identifiers rather than random values when a request may be retried.

## Error Handling

The cartridge translates Stripe SDK exceptions into Venny-specific exceptions while retaining useful provider context.

Exception types include:

```text
ConfigurationException
AuthenticationException
RateLimitException
ValidationException
WebhookException
ProviderException
```

`ProviderException` can expose normalized provider metadata such as:

```text
Stripe error code
HTTP status
Stripe request ID
exception class
```

Secret keys and webhook signing secrets are never included.

## Provider Results

Successful operations return `ProviderResult`.

Normalized output includes:

```php
[
    'success' => true,
    'provider' => 'stripe',
    'tool' => 'payments',
    'operation' => 'create_payment_intent',
    'provider_id' => 'pi_...',
    'data' => [
        'object' => [...]
    ],
    'metadata' => [
        'livemode' => false
    ]
]
```

The Stripe object is normalized to an array before being returned.

## Health Check

Business Manager can invoke:

```text
bm/health.php
```

The health diagnostic reports:

```text
configuration validity
test or live mode inferred from configured key prefix
whether publishable-key configuration exists
whether webhook verification is configured
Stripe account connectivity
Stripe account identifier when retrieval succeeds
```

No payment or billing object is created.

## Business Manager

Mandatory cartridge metadata lives at:

```text
bm/metadata.php
```

Business Manager configuration status lives at:

```text
bm/configuration.php
```

Business Manager must never display:

```text
v_STRIPE_SECRET_KEY
v_STRIPE_WEBHOOK_SECRET
```

as raw values.

## Webhooks

Stripe sends asynchronous events for payment, dispute, refund, invoice, subscription, Checkout, and other provider events.

This cartridge verifies Stripe signatures and normalizes validated events.

It intentionally does not own webhook-event persistence or application routing. Those responsibilities belong to Venny I/O's integration/event infrastructure.

Typical events a consuming application may choose to subscribe to include:

```text
payment_intent.succeeded
payment_intent.payment_failed
charge.refunded
checkout.session.completed
invoice.paid
invoice.payment_failed
customer.subscription.created
customer.subscription.updated
customer.subscription.deleted
```

The exact enabled event list should be determined by the consuming Venny application rather than hard-coded globally into this cartridge.

## Persistence

This cartridge owns no PostgreSQL tables.

Stripe identifiers and business/payment state should be persisted by the application or platform cartridge that owns the underlying customer, order, invoice, subscription, transaction, or entitlement.

## Security

Do not collect or transmit raw card details through Venny server forms when Stripe-hosted or Stripe client-side collection mechanisms can be used.

Never log:

```text
secret keys
webhook signing secrets
Authorization headers
raw card data
CVC values
full sensitive payment-method data
```

Always verify Stripe webhook signatures before treating a payload as a trusted Stripe event.

## Documentation

Stripe API:

https://docs.stripe.com/api

Stripe webhooks:

https://docs.stripe.com/webhooks

Stripe PHP SDK:

https://github.com/stripe/stripe-php

The cartridge exposes documented Stripe parameter arrays instead of attempting to reproduce every Stripe option as a custom Venny method signature. This preserves Stripe's intended API surface while keeping provider-specific SDK usage inside the integration cartridge.
