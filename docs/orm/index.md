# ORM

`nextphp/orm` — ActiveRecord-style ORM с QueryBuilder, связями, Observer'ами и N+1 предупреждениями.

## Модели

```php
use Nextphp\Orm\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';

    /** @var string[] */
    protected array $fillable = ['name', 'email', 'password'];

    /** @var string[] */
    protected array $hidden = ['password'];
}
```

## CRUD

```php
// Создание
$user = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);

// Поиск
$user  = User::find(1);
$users = User::all();
$user  = User::where('email', 'alice@example.com')->first();

// Обновление
$user->update(['name' => 'Alice Smith']);

// Удаление
$user->delete();
```

## QueryBuilder

```php
$users = User::query()
    ->where('active', true)
    ->where('age', '>', 18)
    ->orderBy('name')
    ->limit(10)
    ->offset(20)
    ->get();

// Агрегаты
$count = User::query()->where('active', true)->count();
$avg   = User::query()->avg('age');
```

## Связи

```php
class Post extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag');
    }
}

class Comment extends Model
{
    // Полиморфная связь
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}
```

## Eager loading (N+1 prevention)

```php
// Без eager loading — N+1 проблема (ORM выдаст предупреждение в debug-режиме)
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->user->name; // N запросов!
}

// С eager loading — 2 запроса
$posts = Post::with('user', 'comments')->get();
```

## Observers

```php
use Nextphp\Orm\Observer\ModelObserver;

class UserObserver extends ModelObserver
{
    public function creating(User $model): void
    {
        $model->password = password_hash($model->password, PASSWORD_BCRYPT);
    }

    public function created(User $model): void
    {
        event(new UserRegistered($model));
    }

    public function deleting(User $model): void
    {
        // очистить связанные данные
    }
}

// Регистрация
User::observe(UserObserver::class);
```

## Транзакции

```php
use Nextphp\Orm\Connection\Connection;

$connection->transaction(function () {
    User::create(['name' => 'Bob']);
    Order::create(['user_id' => 1, 'total' => 100]);
});
```

## Scopes

```php
class User extends Model
{
    public function scopeActive(QueryBuilder $query): QueryBuilder
    {
        return $query->where('active', true);
    }

    public function scopeAdults(QueryBuilder $query): QueryBuilder
    {
        return $query->where('age', '>=', 18);
    }
}

$users = User::active()->adults()->get();
```
