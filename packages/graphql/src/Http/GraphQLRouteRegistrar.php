<?php

declare(strict_types=1);

namespace Nextphp\GraphQL\Http;

use Nextphp\GraphQL\GraphQL;
use Nextphp\Http\Message\Response;
use Nextphp\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final class GraphQLRouteRegistrar
{
    public function __construct(
        private readonly GraphQL $graphql,
    ) {
    }

    public function register(Router $router, string $path = '/graphql'): void
    {
        $router->post($path, function (ServerRequestInterface $request): Response {
            $payload = $request->getParsedBody();
            if (! is_array($payload)) {
                return Response::json(['errors' => [['message' => 'Invalid GraphQL request body.']]], 422);
            }

            $query = (string) ($payload['query'] ?? '');
            $variables = $payload['variables'] ?? [];
            if (! is_array($variables)) {
                $variables = [];
            }

            $result = $this->graphql->execute($query, $variables);

            return Response::json($result);
        });
    }
}
