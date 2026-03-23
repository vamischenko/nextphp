<?php

declare(strict_types=1);

namespace Nextphp\Http\Exception;

final class NotFoundException extends HttpException
{
    public function __construct(string $message = '', ?\Throwable $previous = null)
    {
        parent::__construct(404, $message, $previous);
    }
}
