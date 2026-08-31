<?php

declare(strict_types=1);

namespace Venny\Cartridges\SendGridEmail;

final class Client
{
    private readonly \SendGrid $sendGrid;

    public function __construct(Config $config)
    {
        $this->sendGrid = new \SendGrid($config->apiKey());
    }

    public function sendGrid(): \SendGrid
    {
        return $this->sendGrid;
    }
}
