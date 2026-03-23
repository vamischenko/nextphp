<?php

declare(strict_types=1);

namespace Nextphp\Routing\Exception;

use RuntimeException;

final class RouteNotFoundException extends RuntimeException
{
    public function __construct(string $method, string $path)
    {
        parent::__construct(sprintf('No route found for "%s %s".', $method, $path));
    }
}
