<?php

declare(strict_types=1);

namespace Venny\Cartridges\TwilioSms;

use Twilio\Rest\Client as TwilioClient;

final class Client
{
    private readonly TwilioClient $twilio;

    public function __construct(Config $config)
    {
        $this->twilio = new TwilioClient(
            $config->accountSid(),
            $config->authToken()
        );
    }

    public function twilio(): TwilioClient
    {
        return $this->twilio;
    }
}
