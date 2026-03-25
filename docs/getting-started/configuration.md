# Конфигурация

Nextphp не требует конфигурационных файлов — всё собирается через **Service Providers** и **DI-контейнер**.

## Переменные окружения

Создайте файл `.env` в корне проекта:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:ваш-случайный-ключ

DB_CONNECTION=sqlite
DB_DATABASE=/storage/database.sqlite

CACHE_DRIVER=file
QUEUE_DRIVER=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=user@example.com
MAIL_PASSWORD=secret
```

## Окружения

| `APP_ENV` | Поведение |
|-----------|-----------|
| `production` | Ошибки скрываются, кэш включён |
| `local` | Детальные исключения, hot-reload |
| `testing` | Все драйверы переключаются на in-memory |

## APP_DEBUG

При `APP_DEBUG=true` ExceptionHandler отдаёт HTML-страницу с цветным стек-трейсом и цепочкой `$previous`.

## Структура проекта (skeleton)

```
app/
├── Http/
│   ├── Controllers/
│   └── Middleware/
├── Models/
├── Providers/
│   └── AppServiceProvider.php
├── Jobs/
└── Listeners/
bootstrap/
├── app.php          # создаёт Container + регистрирует providers
config/
resources/
│   └── views/
routes/
│   ├── web.php
│   └── api.php
storage/
public/
│   └── index.php    # точка входа
```
