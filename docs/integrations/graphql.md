# GraphQL Endpoint

`nextphp/graphql` можно подключить как HTTP endpoint через `nextphp/http` + `nextphp/routing`.

## Быстрый пример

```php
use Nextphp\GraphQL\GraphQL;
use Nextphp\GraphQL\Schema;
use Nextphp\GraphQL\Http\GraphQLRouteRegistrar;
use Nextphp\Http\Kernel\HttpKernel;
use Nextphp\Routing\Router;

$schema = new Schema();
$schema->query('health', fn (array $args) => 'ok');

$router = new Router();
(new GraphQLRouteRegistrar(new GraphQL($schema)))->register($router, '/graphql');

$kernel = new HttpKernel($router);
```

POST body:

```json
{
  "query": "{ health }"
}
```

Response:

```json
{
  "data": {
    "health": "ok"
  }
}
```
