<?php

declare(strict_types=1);

use Nextphp\Routing\Router;

return static function (Router $router): void {
    $router->get('/api/health', static fn (): array => ['ok' => true]);
};
