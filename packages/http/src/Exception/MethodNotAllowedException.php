<?php

declare(strict_types=1);

namespace Nextphp\Http\Exception;

use Throwable;

final class MethodNotAllowedException extends HttpException
{
    /** @var string[] */
    private array $allowedMethods;

    /**
     * @param string[] $allowedMethods
     */
    public function __construct(array $allowedMethods = [], string $message = '', ?Throwable $previous = null)
    {
        $this->allowedMethods = $allowedMethods;
        parent::__construct(405, $message, $previous);
    }

    /**
     * @return string[]
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
