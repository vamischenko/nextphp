# Skeletons и Installer

## Nextphp Skeleton App

Шаблон находится в `templates/skeleton` и включает:

- базовую структуру приложения (`public`, `routes`, `resources`);
- стартовый `composer.json` для веб-приложения;
- Vite-конфигурацию (`package.json`, `vite.config.js`, `resources/assets/*`).

## Nextphp API Skeleton

Шаблон находится в `templates/api-skeleton` и включает:

- базовую API-структуру (`public`, `routes`);
- стартовый `composer.json` для API-приложения;
- Vite-конфигурацию для фронтенд-ассетов API-проекта.

## Installer (CLI)

Installer реализован в `nextphp/console`:

- `Nextphp\Console\Installer\ProjectInstaller`
- `Nextphp\Console\Installer\InstallProjectCommand`

Поддерживаемые шаблоны:

- `skeleton`
- `api-skeleton`

## Пример запуска installer

```php
<?php

use Nextphp\Console\Application;
use Nextphp\Console\Installer\InstallProjectCommand;
use Nextphp\Console\Installer\ProjectInstaller;

$app = new Application();
$installer = new ProjectInstaller(__DIR__ . '/templates');
$app->add(new InstallProjectCommand($installer));

$app->run(['nextphp', 'project:install', 'skeleton', '/tmp/my-nextphp-app']);
```
