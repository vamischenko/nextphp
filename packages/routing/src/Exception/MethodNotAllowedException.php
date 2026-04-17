<?php

declare(strict_types=1);

namespace Nextphp\Routing\Exception;

use Nextphp\Core\Exception\NextphpException;

final class MethodNotAllowedException extends NextphpException
{
    /**
     * @param string[] $allowedMethods
       * @psalm-mutation-free
     */
    public function __construct(
        private readonly array $allowedMethods,
        string $method,
        string $path,
    ) {
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
