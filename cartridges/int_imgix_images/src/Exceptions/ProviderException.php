<?php

declare(strict_types=1);

namespace Venny\Cartridges\ImgixImages\Exceptions;

use RuntimeException;
use Throwable;

final class ProviderException extends RuntimeException
{
    public static function fromThrowable(string $message, Throwable $exception): self
    {
        return new self(
            sprintf('%s %s', $message, $exception->getMessage()),
            (int) $exception->getCode(),
            $exception
        );
    }
}
