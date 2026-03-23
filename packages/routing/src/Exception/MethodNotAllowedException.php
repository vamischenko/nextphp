<?php

declare(strict_types=1);

namespace Nextphp\Routing\Exception;

use RuntimeException;

final class MethodNotAllowedException extends RuntimeException
{
    /** @var string[] */
    private array $allowedMethods;

    /**
     * @param string[] $allowedMethods
     */
    public function __construct(array $allowedMethods, string $method, string $path)
    {
        $this->allowedMethods = $allowedMethods;
        parent::__construct(sprintf(
            'Method "%s" not allowed for "%s". Allowed: %s.',
            $method,
            $path,
            implode(', ', $allowedMethods),
        ));
    }

    /**
     * @return string[]
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
