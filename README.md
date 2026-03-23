# Nextphp Framework

[![CI](https://github.com/nextphp/nextphp/actions/workflows/ci.yml/badge.svg)](https://github.com/nextphp/nextphp/actions/workflows/ci.yml)
[![Static Analysis](https://github.com/nextphp/nextphp/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/nextphp/nextphp/actions/workflows/static-analysis.yml)
[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue)](https://www.php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

Modern PHP framework combining the best of Laravel, Yii2, and Symfony.

## Philosophy

| Source | What we took |
|--------|-------------|
| **Laravel** | DX, Eloquent ORM, Artisan CLI, Facades, Blade |
| **Yii2** | Performance, lazy loading, batch operations |
| **Symfony** | DI Container, EventDispatcher, strict architecture |

## Requirements

- PHP 8.2+
- Composer 2.x

## Installation

### End-user project

```bash
composer create-project nextphp/skeleton my-app
cd my-app
php nextphp serve
```

### Framework development (monorepo)

```bash
git clone https://github.com/nextphp/nextphp.git
cd nextphp
composer install
composer test
```

Detailed guide: `docs/getting-started/installation.md`

## Packages

| Package | Description | Status |
|---------|-------------|--------|
| `nextphp/core` | IoC Container, Service Providers | WIP |
| `nextphp/http` | PSR-7/PSR-15 HTTP Layer | WIP |
| `nextphp/routing` | RadixTree Router + PHP Attributes | WIP |
| `nextphp/orm` | Active Record ORM | Planned |
| `nextphp/view` | Blade-like template engine | Planned |
| `nextphp/console` | CLI with generators | Planned |
| `nextphp/cache` | PSR-16 Cache | Planned |
| `nextphp/queue` | Jobs, Workers, Batching | Planned |
| `nextphp/octane` | Octane runtime (Swoole/RoadRunner) | Planned |
| `nextphp/websocket` | WebSocket server + Ratchet/Swoole adapters | Planned |
| `nextphp/graphql` | Lightweight GraphQL schema + executor | Planned |
| `nextphp/auth` | Guards, Gates, Policies | Planned |
| `nextphp/validation` | Rule-based + Attribute validation | Planned |
| `nextphp/events` | PSR-14 Event Dispatcher | Planned |
| `nextphp/filesystem` | Flysystem-based Storage | Planned |
| `nextphp/mail` | Mailable classes, SMTP/SES/Mailgun | Planned |
| `nextphp/testing` | TestCase, HTTP Testing, Factories | Planned |

## Development

```bash
# Install dependencies
composer install

# Run all tests
composer test

# Static analysis
composer analyse

# Code style check
composer cs:check

# Fix code style
composer cs:fix
```

## Runtime Integrations

- GraphQL HTTP endpoint (`POST /graphql`) через `nextphp/http` + `nextphp/routing`:
  см. `docs/integrations/graphql.md`
- WebSocket bootstrap script:
  `php bin/ws-server swoole` или `php bin/ws-server ratchet`
- Octane worker bridge (`HttpKernel` + lifecycle hooks + scoped reset):
  см. `docs/integrations/octane.md`
  demo bootstrap: `php bin/octane-server swoole` или `php bin/octane-server roadrunner`

## Quick Start Runtime

```bash
# 1) Установить зависимости в корне монорепо
composer install

# 2) WebSocket runtime demo
php bin/ws-server swoole

# 3) Octane runtime demo (Swoole/RoadRunner)
php bin/octane-server swoole
# или
php bin/octane-server roadrunner
```

Для GraphQL endpoint см. быстрый пример подключения маршрута:
`docs/integrations/graphql.md`

## Standards

- PHP 8.2+ with strict types
- PSR-1, PSR-4, PSR-7, PSR-11, PSR-12, PSR-14, PSR-15
- PHPStan level 8
- Psalm level 1
- 100% test coverage for core

## License

MIT
