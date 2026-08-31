<?php

declare(strict_types=1);

namespace Venny\Cartridges\AnthropicClaude;

use Anthropic\Client as AnthropicClient;

final class Client
{
    private readonly AnthropicClient $client;

    public function __construct(Config $config)
    {
        $this->client = new AnthropicClient(apiKey: $config->apiKey());
    }

    public function anthropic(): AnthropicClient
    {
        return $this->client;
    }
}
