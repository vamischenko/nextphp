<?php

declare(strict_types=1);

use Nextphp\Routing\Router;

return static function (Router $router): void {
    $router->get('/', static fn (): string => 'Welcome to Nextphp Skeleton');
};
