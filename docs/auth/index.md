# Auth

`nextphp/auth` — аутентификация, авторизация, 2FA и email-верификация.

## Guards

```php
use Nextphp\Auth\Guard\SessionGuard;
use Nextphp\Auth\Guard\TokenGuard;

// Session
$guard = new SessionGuard($userProvider, $session);
$guard->attempt(['email' => 'user@example.com', 'password' => 'secret']);
$user = $guard->user();
$guard->logout();

// Token (Bearer)
$guard = new TokenGuard($userProvider, headerName: 'Authorization');
$user  = $guard->user(); // читает Bearer из заголовка
```

## Gates & Policies

```php
use Nextphp\Auth\Access\Gate;

$gate = new Gate();

// Простое правило
$gate->define('edit-post', fn(User $user, Post $post) => $user->id === $post->user_id);

// Проверка
if ($gate->allows('edit-post', [$post])) {
    // разрешить
}

$gate->authorize('edit-post', [$post]); // бросает AuthorizationException при отказе
```

## Policies

```php
class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->isAdmin() || $user->id === $post->user_id;
    }
}

$gate->policy(Post::class, PostPolicy::class);
$gate->authorize('update', [$post]);
```

## Email Verification

```php
use Nextphp\Auth\EmailVerification\EmailVerificationService;

$service = new EmailVerificationService(
    store: $tokenStore,
    expiresInSeconds: 3600,
);

// Отправить ссылку
$service->sendVerificationLink(
    userId: '42',
    notifier: fn(string $userId, string $token) => sendEmail(
        to: $user->email,
        subject: 'Verify your email',
        body: "Click: https://example.com/verify?token={$token}",
    ),
);

// Верификация
$ok = $service->verify(
    userId: '42',
    token: $request->get('token'),
    markVerified: fn(string $userId) => User::find($userId)->update(['email_verified_at' => now()]),
);
```

## TOTP (2FA)

```php
use Nextphp\Auth\Totp\TotpGenerator;

$totp = new TotpGenerator(digits: 6, period: 30);

// Первичная настройка
$secret = $totp->generateSecret();                  // случайный Base32-ключ
$uri    = $totp->getUri($secret, 'alice@example.com', 'MyApp');
// otpauth://totp/MyApp:alice@example.com?secret=...&issuer=MyApp

// Сохранить $secret в базу

// Верификация кода
$code = $request->get('code'); // 6 цифр из Google Authenticator
if ($totp->verify($secret, $code)) {
    // 2FA пройдена
}
```
