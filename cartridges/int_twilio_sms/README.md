# int_twilio_sms

## Purpose

`int_twilio_sms` gives Venny I/O a reusable SMS/MMS transport for Twilio Programmable Messaging without coupling application or communications cartridges directly to Twilio's PHP SDK. It sends outbound messages through a configured Messaging Service or direct Twilio sender number, supports media URLs, status callbacks, scheduled-message cancellation where supported by Twilio, message retrieval, and verification of Twilio-signed inbound/status webhooks. The cartridge owns Twilio authentication, provider request construction, response normalization, and signature validation. It intentionally does not own Venny message queues, retry scheduling, recipient/contact records, consent records, conversation state, opt-out policy, message history, or final delivery-state persistence. Those responsibilities remain with Venny's communications and application-domain cartridges.

## Provider

Twilio

## Tool

Programmable Messaging

Official documentation:

https://www.twilio.com/docs/messaging

Official PHP SDK:

https://github.com/twilio/twilio-php

## Installation

```bash
composer install
```

Or from the parent Venny I/O project:

```bash
composer require twilio/sdk:^8.0
composer dump-autoload
```

## Architecture

Expected Venny flow:

```text
application cartridge
    -> app_venny_communications
        -> communications queue / worker
            -> int_twilio_sms
                -> Twilio Programmable Messaging
```

The provider cartridge does not own a second queue or communications history.

## Configuration

### `v_TWILIO_ACCOUNT_SID`

Required.

Twilio Account SID used to identify the Twilio account.

### `v_TWILIO_AUTH_TOKEN`

Required.

Secret Twilio Auth Token used for server-side REST API authentication and Twilio webhook signature validation.

### `v_TWILIO_MESSAGING_SERVICE_SID`

Recommended.

Messaging Service SID used as the preferred sender abstraction.

Using a Messaging Service lets Twilio select/manage sender resources according to the service configuration rather than coupling Venny directly to one phone number.

### `v_TWILIO_FROM_NUMBER`

Optional fallback.

Twilio-capable sender number in E.164 format.

Use this only when a Messaging Service SID is not configured or when a specific Venny workflow deliberately requires a fixed sender.

### `v_TWILIO_STATUS_CALLBACK_URL`

Optional.

Public HTTPS URL Twilio should call with asynchronous message status updates.

The receiving Venny route should validate the `X-Twilio-Signature` before trusting the callback.

## Capabilities

### Send SMS

```php
send(array $message)
```

Normalized input:

```text
to
body
from
messaging_service_sid
status_callback
media_urls
validity_period
max_price
provide_feedback
send_at
schedule_type
```

Minimum SMS example:

```php
[
    'to' => '+12145551212',
    'body' => 'Your verification code is 123456.'
]
```

### Messaging Service routing

If the message does not explicitly provide a sender, the cartridge prefers:

```text
v_TWILIO_MESSAGING_SERVICE_SID
```

and falls back to:

```text
v_TWILIO_FROM_NUMBER
```

when configured.

### MMS

Pass one or more public media URLs:

```php
[
    'to' => '+12145551212',
    'body' => 'Your image is ready.',
    'media_urls' => [
        'https://cdn.example.com/image.jpg'
    ]
]
```

The cartridge does not retrieve media from storage itself. Upstream Venny logic should provide an accessible URL, potentially through Venny's storage/CDN abstraction.

### Status callbacks

Twilio can send asynchronous status updates to a callback URL.

The cartridge can attach either a message-specific callback or the configured default:

```text
v_TWILIO_STATUS_CALLBACK_URL
```

Venny's webhook/event infrastructure should persist and route the validated event.

### Retrieve message

```php
retrieveMessage(string $messageSid)
```

Reads the current provider-side message resource.

### Cancel scheduled message

```php
cancelScheduledMessage(string $messageSid)
```

Requests cancellation of a scheduled Twilio message by updating it to the documented canceled state.

This capability only applies to messages that Twilio allows to be canceled.

### Webhook signature validation

```php
validateWebhook(
    string $url,
    array $params,
    string $signature
)
```

Twilio signs webhook requests with `X-Twilio-Signature`. This cartridge uses the official SDK request validator rather than reimplementing Twilio's signature algorithm.

The URL must match the exact externally visible URL Twilio called.

## Usage

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Venny\Cartridges\TwilioSms\Config;
use Venny\Cartridges\TwilioSms\Provider;

$provider = new Provider(Config::fromEnvironment());

$result = $provider->send([
    'to' => '+12145551212',
    'body' => 'Your order has shipped.'
]);

echo $result->providerId();
```

Explicit sender example:

```php
$result = $provider->send([
    'to' => '+12145551212',
    'from' => '+19725551212',
    'body' => 'Your appointment is tomorrow at 10:00 AM.'
]);
```

MMS example:

```php
$result = $provider->send([
    'to' => '+12145551212',
    'body' => 'Here is your receipt.',
    'media_urls' => [
        'https://cdn.example.com/receipts/123.png'
    ]
]);
```

## Provider Result

Normalized output:

```php
[
    'success' => true,
    'provider' => 'twilio',
    'tool' => 'sms',
    'operation' => 'send',
    'provider_id' => 'SM...',
    'data' => [
        'sid' => 'SM...',
        'status' => 'queued',
        'to' => '+12145551212'
    ],
    'metadata' => []
]
```

Twilio accepting a message does not mean final handset delivery. Status callbacks should be used for asynchronous delivery lifecycle updates.

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

Provider context may include:

```text
Twilio status
Twilio error code
provider exception class
```

The Auth Token is never exposed.

## Health Check

The health check is read-only.

It validates configuration, constructs the Twilio REST client, and retrieves the configured account resource.

It does not send an SMS.

A Business Manager **Send test SMS** action, if desired later, should be an explicit administrator action rather than part of health checking.

## Business Manager

Mandatory metadata:

```text
bm/metadata.php
```

Configuration status:

```text
bm/configuration.php
```

Health:

```text
bm/health.php
```

Every configuration field includes instructions for obtaining/configuring the value, provider-side prerequisites, expected format, secret classification, validation guidance, and Twilio documentation.

## Webhooks

Inbound SMS and message status callbacks are Twilio webhooks.

The cartridge validates their signature. Venny's event/communications layer should normalize, persist, correlate, and act on the resulting events.

Twilio can add webhook parameters over time. Do not hard-code signature validation to a static parameter list; pass the complete request parameter set to the Twilio SDK validator.

## Consent and compliance

This cartridge provides transport, not messaging-consent policy.

The consuming Venny communications/application layer remains responsible for recipient consent, applicable A2P registration, campaign requirements, opt-out handling, quiet-hours/business rules, message purpose, and jurisdiction-specific compliance.

## Persistence

This cartridge owns no PostgreSQL tables.

Provider message SIDs and delivery lifecycle information should be persisted by the communications domain as needed.

## Security

Never log:

```text
Twilio Auth Token
Authorization headers
sensitive message bodies unless required by approved Venny logging policy
full inbound webhook payloads without considering content sensitivity
```

Always validate `X-Twilio-Signature` on Twilio callbacks before trusting their contents.

## Documentation

Programmable Messaging:

https://www.twilio.com/docs/messaging

SMS quickstart:

https://www.twilio.com/docs/messaging/quickstart

Sending SMS/MMS:

https://www.twilio.com/docs/messaging/tutorials/how-to-send-sms-messages

Webhook security:

https://www.twilio.com/docs/usage/webhooks/webhooks-security

PHP SDK:

https://github.com/twilio/twilio-php
