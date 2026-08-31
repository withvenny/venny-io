<?php
declare(strict_types=1);
namespace Venny\Cartridges\TwilioSms\Exceptions;
use RuntimeException;
use Throwable;
class ProviderException extends RuntimeException {
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null, private readonly array $providerContext = []) {
        parent::__construct($message, $code, $previous);
    }
    public function providerContext(): array { return $this->providerContext; }
}
