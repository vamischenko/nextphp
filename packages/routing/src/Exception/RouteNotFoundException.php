<?php

declare(strict_types=1);

namespace Nextphp\Routing\Exception;

use Nextphp\Core\Exception\NextphpException;

final class RouteNotFoundException extends NextphpException
{
    public function __construct(string $method, string $path)
    {
        parent::__construct(sprintf('No route found for "%s %s".', $method, $path));
    }
}
