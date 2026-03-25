# Cache

`nextphp/cache` — PSR-16 кэш с поддержкой тегов, TTL и метода `remember`.

## Драйверы

| Класс | Описание |
|-------|----------|
| `FileCache` | Файловый кэш (по умолчанию) |
| `RedisCache` | Redis через ext-redis |
| `MemcachedCache` | Memcached через ext-memcached |
| `DatabaseCache` | PDO (SQLite / MySQL / PostgreSQL) |
| `ArrayCache` | In-memory (для тестов) |
| `NullCache` | Ничего не кэширует |

## Создание через фабрику

```php
use Nextphp\Cache\CacheFactory;

$cache = CacheFactory::file('/storage/cache');
$cache = CacheFactory::redis($redisInstance, prefix: 'app:');
$cache = CacheFactory::memcached($memcachedInstance);
$cache = CacheFactory::database($pdo);
$cache = CacheFactory::array();
```

## Базовое использование (PSR-16)

```php
// Запись
$cache->set('key', $value, ttl: 3600);
$cache->set('key', $value, ttl: new DateInterval('PT1H'));

// Чтение
$value = $cache->get('key', default: null);

// Проверка
$exists = $cache->has('key');

// Удаление
$cache->delete('key');
$cache->clear();

// Множественные операции
$cache->setMultiple(['a' => 1, 'b' => 2], ttl: 60);
$values = $cache->getMultiple(['a', 'b'], default: 0);
```

## Remember

```php
$user = $cache->remember("user:{$id}", ttl: 3600, function () use ($id) {
    return User::find($id);
});
```

## Теги

```php
// Установить кэш с тегами
$cache->set('user:1', $user, 3600);
$cache->tag('user:1', ['users', 'user:1']);

$cache->set('user:2', $user2, 3600);
$cache->tag('user:2', ['users', 'user:2']);

// Сбросить все записи с тегом
$cache->flushTag('users'); // удалит user:1 и user:2
```
