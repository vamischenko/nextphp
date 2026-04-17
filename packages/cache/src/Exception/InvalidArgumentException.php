<?php

declare(strict_types=1);

namespace Nextphp\Cache\Exception;

final class InvalidArgumentException extends \InvalidArgumentException implements \Psr\SimpleCache\InvalidArgumentException
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
