<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Nextphp\Routing\Exception\MethodNotAllowedException;
use Nextphp\Routing\Exception\RouteNotFoundException;
use Nextphp\Routing\Router;

// FrankenPHP may keep workers alive; avoid leaking state between requests.
$router = null;

$router = new Router();

$registerRoutes = require __DIR__ . '/../routes/web.php';
$registerRoutes($router);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) ? $path : '/';

try {
    $match = $router->dispatch($method, $path);
    $handler = $match->route->getHandler();

    if (is_callable($handler)) {
        $result = $handler(...array_values($match->params));
        header('Content-Type: text/html; charset=utf-8');
        echo is_string($result) ? $result : (string) $result;

        return;
    }

    http_response_code(500);
    echo 'Route handler is not callable.';
} catch (MethodNotAllowedException $e) {
    http_response_code(405);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Method not allowed';
} catch (RouteNotFoundException $e) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
}
