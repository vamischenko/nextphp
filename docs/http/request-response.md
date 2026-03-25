# Request & Response

`nextphp/http` — PSR-7/15/17 совместимые HTTP примитивы.

## ServerRequest

```php
use Nextphp\Http\Message\ServerRequest;

// Создание из глобальных переменных PHP
$request = ServerRequest::fromGlobals();

// Основные методы
$method  = $request->getMethod();            // 'GET', 'POST', ...
$uri     = $request->getUri();               // UriInterface
$path    = $request->getUri()->getPath();    // '/users/42'
$query   = $request->getQueryParams();       // ['page' => '1']
$body    = $request->getParsedBody();        // array из $_POST
$json    = json_decode((string) $request->getBody(), true);
$files   = $request->getUploadedFiles();     // UploadedFileInterface[]
$headers = $request->getHeaders();

// Атрибуты (устанавливаются роутером)
$userId = $request->getAttribute('id');
```

## Response

```php
use Nextphp\Http\Message\Response;

$response = new Response(200, [], 'Hello World');

// JSON
$response = new Response(200, ['Content-Type' => 'application/json'], json_encode(['ok' => true]));

// Redirect
$response = new Response(302, ['Location' => '/login']);
```

## File Upload

```php
$file = $request->getUploadedFiles()['avatar'];

if ($file->getError() === UPLOAD_ERR_OK) {
    $file->moveTo('/storage/avatars/' . $file->getClientFilename());
}

$size = $file->getSize();
$mime = $file->getClientMediaType();
```

## Cookies

```php
use Nextphp\Http\Cookie\Cookie;
use Nextphp\Http\Cookie\CookieJar;

$jar = new CookieJar();
$jar->set(new Cookie('session', 'abc123', httpOnly: true, secure: true));

// CookieMiddleware автоматически читает и добавляет Set-Cookie
```

## Session

```php
use Nextphp\Http\Session\FileSession;

$session = new FileSession('/storage/sessions');
$session->set('user_id', 42);
$userId = $session->get('user_id');
$session->forget('user_id');
$session->destroy();
```
