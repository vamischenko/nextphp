<?php

declare(strict_types=1);

namespace Nextphp\Http\Exception;

use Nextphp\Http\Message\Response;
use Nextphp\Routing\Exception\MethodNotAllowedException as RoutingMethodNotAllowedException;
use Nextphp\Routing\Exception\RouteNotFoundException as RoutingRouteNotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class ExceptionHandler
{
    public function handle(Throwable $exception, ?ServerRequestInterface $request = null): ResponseInterface
    {
        $status = match (true) {
            $exception instanceof HttpException => $exception->getStatusCode(),
            $exception instanceof RoutingRouteNotFoundException => 404,
            $exception instanceof RoutingMethodNotAllowedException => 405,
            default => 500,
        };
        $accept = $request?->getHeaderLine('Accept') ?? '';
        $wantsJson = str_contains($accept, 'application/json');

        if ($wantsJson) {
            return Response::json([
                'error' => $exception->getMessage(),
                'status' => $status,
            ], $status);
        }

        $body = sprintf(
            "<html><body><h1>%d</h1><p>%s</p></body></html>",
            $status,
            htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'),
        );

        return new Response($status, ['Content-Type' => 'text/html; charset=UTF-8'], $body);
    }
}
