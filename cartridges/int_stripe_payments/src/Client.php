<?php

declare(strict_types=1);

namespace Venny\Cartridges\StripePayments;

use Stripe\StripeClient;

final class Client
{
    private readonly StripeClient $stripe;

    public function __construct(private readonly Config $config)
    {
        $clientConfig = [
            'api_key' => $config->secretKey(),
        ];

        if ($config->apiVersion() !== null) {
            $clientConfig['stripe_version'] = $config->apiVersion();
        }

        $this->stripe = new StripeClient($clientConfig);
    }

    public function stripe(): StripeClient
    {
        return $this->stripe;
    }
}
