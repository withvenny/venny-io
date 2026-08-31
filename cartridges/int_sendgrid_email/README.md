# int_sendgrid_email

## Purpose

`int_sendgrid_email` gives Venny I/O a reusable transactional-email transport for Twilio SendGrid without coupling application or communications cartridges directly to SendGrid's PHP SDK. It accepts normalized email requests and exposes SendGrid's documented mail-send capabilities including recipients, CC/BCC, reply-to, plain-text and HTML bodies, transactional templates, dynamic template data, attachments, headers, custom arguments, categories, scheduled delivery, and tracking settings. The cartridge owns provider authentication, SendGrid request construction, provider-response normalization, and webhook signature verification. It intentionally does not own message queues, retries, communication history, templates as Venny business objects, contacts, campaign logic, or delivery-state persistence. Those remain with the Venny communications and application-domain cartridges.

## Provider

Twilio SendGrid

## Tool

Mail Send API

Official API:

https://www.twilio.com/docs/sendgrid/api-reference/mail-send/mail-send

Official PHP library:

https://github.com/sendgrid/sendgrid-php

## Installation

```bash
composer install
```

Or from the parent Venny I/O project:

```bash
composer require sendgrid/sendgrid:^8.1
composer dump-autoload
```

## Architecture

This cartridge is a transport adapter.

Expected Venny flow:

```text
application cartridge
    -> app_venny_communications
        -> communications queue / worker
            -> int_sendgrid_email
                -> Twilio SendGrid
```

The cartridge should not create a second message queue or become the Venny communications system of record.

## Configuration

### `v_SENDGRID_API_KEY`

Required.

Server-side API key used to authenticate SendGrid API calls.

The API key should have only the permissions required for this cartridge, normally Mail Send access for transactional delivery.

### `v_SENDGRID_FROM_EMAIL`

Required.

Default verified sender email address used when a send request does not explicitly provide a from address.

The address must comply with SendGrid sender authentication requirements.

### `v_SENDGRID_FROM_NAME`

Optional.

Default human-readable sender name.

### `v_SENDGRID_REPLY_TO_EMAIL`

Optional.

Default reply-to email address.

### `v_SENDGRID_REPLY_TO_NAME`

Optional.

Default human-readable reply-to name.

### `v_SENDGRID_WEBHOOK_PUBLIC_KEY`

Optional.

Public verification key used to verify signed SendGrid Event Webhook requests when webhook verification is enabled.

## Capabilities

### Send email

```php
send(array $message)
```

Normalized input can include:

```text
from
to
cc
bcc
reply_to
subject
text
html
template_id
dynamic_template_data
attachments
headers
custom_args
categories
send_at
tracking_settings
```

### Transactional template email

Pass:

```php
[
    'to' => [['email' => 'person@example.com']],
    'template_id' => 'd-...',
    'dynamic_template_data' => [
        'first_name' => 'Example'
    ]
]
```

When a template ID is supplied, the caller may omit ordinary content when the SendGrid template provides it.

### Multiple recipients

Recipient collections use normalized arrays:

```php
[
    ['email' => 'one@example.com', 'name' => 'One'],
    ['email' => 'two@example.com', 'name' => 'Two']
]
```

### Attachments

Attachments may be supplied as:

```php
[
    [
        'content' => base64_encode($bytes),
        'type' => 'application/pdf',
        'filename' => 'report.pdf',
        'disposition' => 'attachment',
        'content_id' => null
    ]
]
```

The integration expects attachment content to already be available. It does not directly depend on `int_aws_s3`; an upstream communications worker may retrieve an attachment through Venny's storage abstraction before invoking this transport.

### Scheduled send

`send_at` accepts a Unix timestamp compatible with SendGrid's documented scheduling field.

### Tracking settings

Documented SendGrid tracking settings may be passed in normalized form for open tracking, click tracking, subscription tracking, and related supported settings.

## Usage

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Venny\Cartridges\SendGridEmail\Config;
use Venny\Cartridges\SendGridEmail\Provider;

$provider = new Provider(Config::fromEnvironment());

$result = $provider->send([
    'to' => [
        ['email' => 'customer@example.com', 'name' => 'Customer']
    ],
    'subject' => 'Your receipt',
    'text' => 'Thank you for your purchase.',
    'html' => '<p>Thank you for your purchase.</p>',
]);

if ($result->success()) {
    $messageId = $result->providerId();
}
```

Template example:

```php
$result = $provider->send([
    'to' => [
        ['email' => 'customer@example.com']
    ],
    'template_id' => 'd-example',
    'dynamic_template_data' => [
        'first_name' => 'Customer',
        'order_number' => '12345'
    ]
]);
```

## Provider Result

Normalized successful response:

```php
[
    'success' => true,
    'provider' => 'sendgrid',
    'tool' => 'email',
    'operation' => 'send',
    'provider_id' => 'provider-message-id',
    'data' => [
        'status_code' => 202
    ],
    'metadata' => [
        'headers' => [...]
    ]
]
```

SendGrid commonly acknowledges accepted Mail Send requests with HTTP `202 Accepted`. Acceptance is not the same as final delivery; delivery, bounce, block, and other lifecycle outcomes arrive asynchronously through SendGrid events.

## Event Webhook

The cartridge can verify SendGrid Event Webhook signatures when `v_SENDGRID_WEBHOOK_PUBLIC_KEY` is configured.

Provider-specific signature verification belongs here.

Persisting webhook events, routing them, updating message status, and correlating them to Venny communications belong upstream in Venny's communications/event infrastructure.

## Error Handling

Exceptions:

```text
ConfigurationException
AuthenticationException
RateLimitException
ValidationException
WebhookException
ProviderException
```

Provider responses preserve useful HTTP status, headers, and body without exposing the SendGrid API key.

## Health Check

The health check is intentionally local and non-destructive.

It validates:

```text
required configuration
API key presence and recognizable format
default sender configuration
SendGrid client construction
webhook verification configuration state
```

It does not send a test email automatically because health checks must not create communications side effects.

A future explicit Business Manager "Send test email" action should be implemented separately from health checks.

## Business Manager

Mandatory metadata:

```text
bm/metadata.php
```

Configuration state:

```text
bm/configuration.php
```

Health diagnostics:

```text
bm/health.php
```

Every configuration field includes instructions explaining where to obtain the value, required provider-side setup, expected format, whether the value is secret, and relevant SendGrid documentation.

## Persistence

This cartridge owns no PostgreSQL tables.

Message requests, retries, attempts, recipients, provider message IDs, webhook events, communication history, and final delivery state should live in Venny's communications/event domain where appropriate.

## Security

Never log:

```text
SendGrid API keys
authorization headers
sensitive attachment contents
full message bodies unless explicitly required by upstream policy
```

Use least-privilege SendGrid API keys.

Use authenticated sender domains or verified senders according to SendGrid requirements.

## Documentation

Official PHP SDK:

https://github.com/sendgrid/sendgrid-php

Mail Send API:

https://www.twilio.com/docs/sendgrid/api-reference/mail-send/mail-send

API keys:

https://www.twilio.com/docs/sendgrid/ui/account-and-settings/api-keys

Sender authentication:

https://www.twilio.com/docs/sendgrid/ui/account-and-settings/how-to-set-up-domain-authentication
