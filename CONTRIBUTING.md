# Contributing to Nextphp Framework

## Branch Strategy

```
main          — stable releases (tags v0.1.0, v0.2.0, ...)
develop       — integration branch
feature/*     — features (feature/core-container, feature/http-pipeline)
fix/*         — bug fixes
```

## Development Setup

```bash
git clone https://github.com/nextphp/nextphp.git
cd nextphp
composer install
```

## Coding Standards

- `declare(strict_types=1)` in every PHP file
- PSR-12 code style (enforced by PHP CS Fixer)
- PHPStan level 8 — zero errors
- Psalm level 1 — zero errors
- 100% test coverage for core packages

## Running Quality Checks

```bash
composer qa          # all checks
composer test        # tests only
composer analyse     # PHPStan only
composer psalm       # Psalm only
composer cs:check    # code style check
composer cs:fix      # auto-fix code style
```

## Pull Request Process

1. Create a branch from `develop`
2. Write tests first (TDD)
3. Ensure all checks pass: `composer qa`
4. Submit PR to `develop`
5. PR requires review and green CI

## Naming Conventions

- Classes: `PascalCase`
- Methods and properties: `camelCase`
- Constants: `UPPER_SNAKE_CASE`
- Namespaces: `Nextphp\ComponentName\SubNamespace`
