# Console

`nextphp/console` — CLI-приложение с командами, output-хелперами и генераторами кода.

## Создание команды

```php
use Nextphp\Console\Command;

class MigrateCommand extends Command
{
    protected string $signature = 'migrate {--fresh : Drop all tables first} {--seed : Run seeders}';
    protected string $description = 'Run database migrations';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->info('Dropping all tables...');
            // ...
        }

        $this->info('Running migrations...');
        // ...

        if ($this->option('seed')) {
            $this->call('db:seed');
        }

        $this->success('Migrations complete!');
        return 0;
    }
}
```

## Output методы

```php
$this->info('Informational message');      // белый
$this->success('Done!');                   // зелёный
$this->warning('Be careful');             // жёлтый
$this->error('Something went wrong');     // красный

$this->line('Plain text');
$this->newLine();
$this->newLine(2);

// Таблица
$this->table(
    headers: ['ID', 'Name', 'Email'],
    rows: $users->map(fn($u) => [$u->id, $u->name, $u->email])->toArray(),
);

// Progress bar
$bar = $this->progressBar(count($items));
foreach ($items as $item) {
    $process($item);
    $bar->advance();
}
$bar->finish();
```

## Регистрация команд

```php
use Nextphp\Console\Application;

$app = new Application('Nextphp', '1.0.0');
$app->add(new MigrateCommand());
$app->add(new MakeModelCommand());
$app->add(new MakeControllerCommand());

$app->run();
```

## Make-генераторы

```bash
php artisan make:model Post
php artisan make:controller PostController --resource
php artisan make:job SendEmailJob
php artisan make:event UserRegistered
php artisan make:listener SendWelcomeEmail --event=UserRegistered
php artisan make:middleware AuthMiddleware
php artisan make:migration create_posts_table
```

## Installer

```bash
# Создание нового проекта
composer create-project nextphp/nextphp my-app

# Или через CLI
nextphp project:install my-app --template=skeleton
nextphp project:install my-api  --template=api-skeleton
```
