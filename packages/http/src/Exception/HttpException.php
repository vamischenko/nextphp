<?php

declare(strict_types=1);

namespace Nextphp\Http\Exception;

use Nextphp\Core\Exception\NextphpException;
use Throwable;

class HttpException extends NextphpException
{
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message ?: $this->defaultMessage(), 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    protected function defaultMessage(): string
    {
        return match ($this->statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            default => 'HTTP Error',
        };
    }
}
