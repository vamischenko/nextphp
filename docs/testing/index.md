# Testing

`nextphp/testing` — интеграционное и unit тестирование с HTTP-клиентом, моками и snapshot-ассертами.

## TestCase

```php
use Nextphp\Testing\TestCase;

class UserControllerTest extends TestCase
{
    public function testCreateUser(): void
    {
        $client = $this->routingClient($router);

        $response = $client->post('/users', ['name' => 'Alice', 'email' => 'alice@example.com']);

        $response->assertStatus(201)
                 ->assertJson(['name' => 'Alice'])
                 ->assertHeader('Content-Type', 'application/json');
    }
}
```

## HTTP-клиент

```php
// Простой callable-клиент
$client = $this->client(function (string $method, string $uri, array $body) {
    return ['status' => 200, 'json' => ['ok' => true]];
});

// Клиент с роутером
$client = $this->routingClient($router);
$client->withHeader('Authorization', 'Bearer token123');

// Клиент с HttpKernel (полный стек)
$client = $this->kernelClient($kernel);
```

## TestResponse ассерты

```php
$response->assertStatus(200);
$response->assertOk();          // 200
$response->assertCreated();     // 201
$response->assertNotFound();    // 404

$response->assertJson(['user' => ['id' => 1]]);
$response->assertJsonPath('user.email', 'alice@example.com');
$response->assertHeader('Content-Type', 'application/json');
$response->assertRedirect('/login');
$response->assertBodyContains('Welcome');
```

## Mocking

### Встроенные моки

```php
interface PaymentGateway
{
    public function charge(int $amount): bool;
    public function refund(string $txId): void;
}

$mock = $this->mock(PaymentGateway::class);

$mock->expects('charge')->with(1000)->andReturn(true)->once();
$mock->expects('refund')->never();

$service = new OrderService($mock);
$service->checkout(1000);

$mock->verify(); // проверяет все ожидания
```

### Дополнительные возможности

```php
// Проверка вызовов
$mock->wasCalled('charge');          // bool
$mock->callCount('charge');          // int
$mock->callArgs('charge', 0);        // [1000]

// Возврат значения через callback
$mock->expects('charge')->andReturnUsing(fn(int $amount) => $amount < 500);

// Количество вызовов
$mock->expects('charge')->twice();
$mock->expects('charge')->times(5);
$mock->expects('charge')->atLeast(2);
$mock->expects('charge')->zeroOrMoreTimes();
```

### Интеграция с Mockery

```php
use Mockery;

class ServiceTest extends TestCase
{
    public function testWithMockery(): void
    {
        $mock = $this->mockery(PaymentGateway::class);
        $mock->shouldReceive('charge')->with(1000)->once()->andReturn(true);

        // ...
    }
    // tearDown() автоматически вызывает Mockery::close()
}
```

## Database Testing

```php
use Nextphp\Testing\Database\RefreshDatabase;
use Nextphp\Testing\Database\DatabaseTransactions;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->refreshSqliteSchema($connection, ['users', 'posts']);
    }
}
```

## Snapshot Testing

```php
use Nextphp\Testing\Snapshot\SnapshotAssert;

class ApiTest extends TestCase
{
    use SnapshotAssert;

    public function testUserJson(): void
    {
        $data = $api->getUser(1);
        $this->assertMatchesSnapshot(json_encode($data, JSON_PRETTY_PRINT));
        // при первом запуске — создаёт snapshot файл
        // при последующих — сравнивает
    }
}
```

## Browser Testing (Panther)

```php
use Nextphp\Testing\Browser\BrowserTestCase;

class LoginTest extends BrowserTestCase
{
    public function testLogin(): void
    {
        $browser = $this->browser(); // Symfony Panther Client

        $browser->request('GET', 'http://localhost:8000/login');
        $browser->submitForm('Login', [
            'email'    => 'user@example.com',
            'password' => 'secret',
        ]);

        $this->assertStringContainsString('Dashboard', $browser->getPageSource());
    }
}
```

> **Требование:** `symfony/panther` должен быть установлен, и ChromeDriver должен быть доступен.
