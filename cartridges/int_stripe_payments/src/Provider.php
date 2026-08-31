<?php

declare(strict_types=1);

namespace Venny\Cartridges\StripePayments;

use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException as StripeAuthenticationException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\RateLimitException as StripeRateLimitException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeObject;
use Stripe\Webhook;
use Throwable;
use Venny\Cartridges\StripePayments\Exceptions\AuthenticationException;
use Venny\Cartridges\StripePayments\Exceptions\ConfigurationException;
use Venny\Cartridges\StripePayments\Exceptions\ProviderException;
use Venny\Cartridges\StripePayments\Exceptions\RateLimitException;
use Venny\Cartridges\StripePayments\Exceptions\ValidationException;
use Venny\Cartridges\StripePayments\Exceptions\WebhookException;

final class Provider
{
    private readonly Client $client;

    public function __construct(
        private readonly Config $config,
        ?Client $client = null,
    ) {
        $this->client = $client ?? new Client($config);
    }

    public function createCustomer(array $params): ProviderResult
    {
        return $this->execute('create_customer', fn() =>
            $this->client->stripe()->customers->create($params)
        );
    }

    public function retrieveCustomer(string $customerId, array $params = []): ProviderResult
    {
        return $this->execute('retrieve_customer', fn() =>
            $this->client->stripe()->customers->retrieve(
                $this->requireId($customerId, 'Customer ID'),
                $params
            )
        );
    }

    public function updateCustomer(string $customerId, array $params): ProviderResult
    {
        return $this->execute('update_customer', fn() =>
            $this->client->stripe()->customers->update(
                $this->requireId($customerId, 'Customer ID'),
                $params
            )
        );
    }

    public function createPaymentIntent(
        array $params,
        ?string $idempotencyKey = null
    ): ProviderResult {
        return $this->execute('create_payment_intent', fn() =>
            $this->client->stripe()->paymentIntents->create(
                $params,
                $this->requestOptions($idempotencyKey)
            )
        );
    }

    public function retrievePaymentIntent(
        string $paymentIntentId,
        array $params = []
    ): ProviderResult {
        return $this->execute('retrieve_payment_intent', fn() =>
            $this->client->stripe()->paymentIntents->retrieve(
                $this->requireId($paymentIntentId, 'PaymentIntent ID'),
                $params
            )
        );
    }

    public function confirmPaymentIntent(
        string $paymentIntentId,
        array $params = [],
        ?string $idempotencyKey = null
    ): ProviderResult {
        return $this->execute('confirm_payment_intent', fn() =>
            $this->client->stripe()->paymentIntents->confirm(
                $this->requireId($paymentIntentId, 'PaymentIntent ID'),
                $params,
                $this->requestOptions($idempotencyKey)
            )
        );
    }

    public function capturePaymentIntent(
        string $paymentIntentId,
        array $params = [],
        ?string $idempotencyKey = null
    ): ProviderResult {
        return $this->execute('capture_payment_intent', fn() =>
            $this->client->stripe()->paymentIntents->capture(
                $this->requireId($paymentIntentId, 'PaymentIntent ID'),
                $params,
                $this->requestOptions($idempotencyKey)
            )
        );
    }

    public function cancelPaymentIntent(
        string $paymentIntentId,
        array $params = []
    ): ProviderResult {
        return $this->execute('cancel_payment_intent', fn() =>
            $this->client->stripe()->paymentIntents->cancel(
                $this->requireId($paymentIntentId, 'PaymentIntent ID'),
                $params
            )
        );
    }

    public function createRefund(
        array $params,
        ?string $idempotencyKey = null
    ): ProviderResult {
        return $this->execute('create_refund', fn() =>
            $this->client->stripe()->refunds->create(
                $params,
                $this->requestOptions($idempotencyKey)
            )
        );
    }

    public function retrieveRefund(string $refundId): ProviderResult
    {
        return $this->execute('retrieve_refund', fn() =>
            $this->client->stripe()->refunds->retrieve(
                $this->requireId($refundId, 'Refund ID')
            )
        );
    }

    public function createCheckoutSession(
        array $params,
        ?string $idempotencyKey = null
    ): ProviderResult {
        return $this->execute('create_checkout_session', fn() =>
            $this->client->stripe()->checkout->sessions->create(
                $params,
                $this->requestOptions($idempotencyKey)
            )
        );
    }

    public function retrieveCheckoutSession(
        string $sessionId,
        array $params = []
    ): ProviderResult {
        return $this->execute('retrieve_checkout_session', fn() =>
            $this->client->stripe()->checkout->sessions->retrieve(
                $this->requireId($sessionId, 'Checkout Session ID'),
                $params
            )
        );
    }

    public function expireCheckoutSession(string $sessionId): ProviderResult
    {
        return $this->execute('expire_checkout_session', fn() =>
            $this->client->stripe()->checkout->sessions->expire(
                $this->requireId($sessionId, 'Checkout Session ID')
            )
        );
    }

    public function createSubscription(
        array $params,
        ?string $idempotencyKey = null
    ): ProviderResult {
        return $this->execute('create_subscription', fn() =>
            $this->client->stripe()->subscriptions->create(
                $params,
                $this->requestOptions($idempotencyKey)
            )
        );
    }

    public function retrieveSubscription(
        string $subscriptionId,
        array $params = []
    ): ProviderResult {
        return $this->execute('retrieve_subscription', fn() =>
            $this->client->stripe()->subscriptions->retrieve(
                $this->requireId($subscriptionId, 'Subscription ID'),
                $params
            )
        );
    }

    public function updateSubscription(
        string $subscriptionId,
        array $params
    ): ProviderResult {
        return $this->execute('update_subscription', fn() =>
            $this->client->stripe()->subscriptions->update(
                $this->requireId($subscriptionId, 'Subscription ID'),
                $params
            )
        );
    }

    public function cancelSubscription(
        string $subscriptionId,
        array $params = []
    ): ProviderResult {
        return $this->execute('cancel_subscription', fn() =>
            $this->client->stripe()->subscriptions->cancel(
                $this->requireId($subscriptionId, 'Subscription ID'),
                $params
            )
        );
    }

    public function createInvoice(
        array $params,
        ?string $idempotencyKey = null
    ): ProviderResult {
        return $this->execute('create_invoice', fn() =>
            $this->client->stripe()->invoices->create(
                $params,
                $this->requestOptions($idempotencyKey)
            )
        );
    }

    public function retrieveInvoice(
        string $invoiceId,
        array $params = []
    ): ProviderResult {
        return $this->execute('retrieve_invoice', fn() =>
            $this->client->stripe()->invoices->retrieve(
                $this->requireId($invoiceId, 'Invoice ID'),
                $params
            )
        );
    }

    public function finalizeInvoice(
        string $invoiceId,
        array $params = []
    ): ProviderResult {
        return $this->execute('finalize_invoice', fn() =>
            $this->client->stripe()->invoices->finalizeInvoice(
                $this->requireId($invoiceId, 'Invoice ID'),
                $params
            )
        );
    }

    public function payInvoice(
        string $invoiceId,
        array $params = []
    ): ProviderResult {
        return $this->execute('pay_invoice', fn() =>
            $this->client->stripe()->invoices->pay(
                $this->requireId($invoiceId, 'Invoice ID'),
                $params
            )
        );
    }

    public function voidInvoice(string $invoiceId): ProviderResult
    {
        return $this->execute('void_invoice', fn() =>
            $this->client->stripe()->invoices->voidInvoice(
                $this->requireId($invoiceId, 'Invoice ID')
            )
        );
    }

    public function verifyWebhook(
        string $payload,
        string $signatureHeader,
        ?int $tolerance = null
    ): ProviderResult {
        if ($this->config->webhookSecret() === null) {
            throw new ConfigurationException(
                'v_STRIPE_WEBHOOK_SECRET is required for webhook verification.'
            );
        }

        if ($payload === '') {
            throw new WebhookException('Stripe webhook payload is empty.');
        }

        if (trim($signatureHeader) === '') {
            throw new WebhookException('Stripe-Signature header is required.');
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signatureHeader,
                $this->config->webhookSecret(),
                $tolerance ?? Webhook::DEFAULT_TOLERANCE
            );

            return ProviderResult::ok(
                'verify_webhook',
                $event->id ?? null,
                [
                    'id' => $event->id ?? null,
                    'type' => $event->type ?? null,
                    'object' => $event->toArray(),
                ],
                [
                    'livemode' => (bool) ($event->livemode ?? false),
                    'api_version' => $event->api_version ?? null,
                    'created' => $event->created ?? null,
                ]
            );
        } catch (SignatureVerificationException $exception) {
            throw new WebhookException(
                'Stripe webhook signature verification failed.',
                (int) $exception->getCode(),
                $exception
            );
        } catch (Throwable $exception) {
            throw new WebhookException(
                'Unable to parse Stripe webhook event.',
                (int) $exception->getCode(),
                $exception
            );
        }
    }

    public function healthCheck(): ProviderResult
    {
        return $this->execute('health_check', function () {
            $account = $this->client->stripe()->accounts->retrieve();

            return $account;
        }, [
            'mode' => $this->config->mode(),
            'publishable_key_configured' => $this->config->publishableKey() !== null,
            'webhook_secret_configured' => $this->config->hasWebhookSecret(),
            'api_version_pinned' => $this->config->apiVersion() !== null,
        ]);
    }

    private function execute(
        string $operation,
        callable $callback,
        array $metadata = []
    ): ProviderResult {
        try {
            $object = $callback();

            if (!$object instanceof StripeObject) {
                throw new ProviderException(
                    sprintf('Stripe operation %s returned an unexpected response type.', $operation)
                );
            }

            $data = $object->toArray();
            $providerId = isset($data['id']) && is_string($data['id'])
                ? $data['id']
                : null;

            $metadata += [
                'livemode' => isset($data['livemode'])
                    ? (bool) $data['livemode']
                    : null,
            ];

            return ProviderResult::ok(
                $operation,
                $providerId,
                ['object' => $data],
                $metadata
            );
        } catch (StripeAuthenticationException $exception) {
            throw new AuthenticationException(
                'Stripe authentication failed.',
                (int) $exception->getCode(),
                $exception,
                $this->stripeContext($exception)
            );
        } catch (StripeRateLimitException $exception) {
            throw new RateLimitException(
                'Stripe rate limit exceeded.',
                (int) $exception->getCode(),
                $exception,
                $this->stripeContext($exception)
            );
        } catch (InvalidRequestException $exception) {
            throw new ValidationException(
                'Stripe rejected the request.',
                (int) $exception->getCode(),
                $exception,
                $this->stripeContext($exception)
            );
        } catch (ApiErrorException $exception) {
            throw new ProviderException(
                'Stripe API request failed.',
                (int) $exception->getCode(),
                $exception,
                $this->stripeContext($exception)
            );
        } catch (ProviderException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ProviderException(
                'Unexpected Stripe provider failure.',
                (int) $exception->getCode(),
                $exception
            );
        }
    }

    private function requestOptions(?string $idempotencyKey): array
    {
        if ($idempotencyKey === null || trim($idempotencyKey) === '') {
            return [];
        }

        return ['idempotency_key' => trim($idempotencyKey)];
    }

    private function requireId(string $id, string $label): string
    {
        $id = trim($id);

        if ($id === '') {
            throw new ValidationException(sprintf('%s is required.', $label));
        }

        return $id;
    }

    private function stripeContext(ApiErrorException $exception): array
    {
        $error = $exception->getError();

        return [
            'http_status' => $exception->getHttpStatus(),
            'stripe_code' => $exception->getStripeCode(),
            'stripe_request_id' => $exception->getRequestId(),
            'error_type' => $error?->type,
            'error_param' => $error?->param,
        ];
    }
}
