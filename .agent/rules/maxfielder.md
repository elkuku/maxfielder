# Maxfielder Project Rules

## Cross-Platform Development

This project works on both Windows (Laragon) and Linux (EndeavourOS). Key files handle cross-platform execution:

### MaxFieldGenerator.php
- **Location**: `src/Service/MaxFieldGenerator.php`
- **Cross-platform**: Uses `PHP_OS_FAMILY` to detect OS
- **Windows**: Uses `cmd /c start /b` for background execution
- **Linux/macOS**: Uses `sh -c` for background execution

### Test Files
- **Location**: `tests/Service/MaxFieldGeneratorCommandTest.php`
- **Note**: Tests use `implode(' ', $cmd)` because buildCommand() returns raw array (shell wrapping happens in generate())

## Environment

- **PHP**: 8.5.4 (Windows), 8.x (Linux)
- **Framework**: Symfony 8
- **Database**: PostgreSQL (Docker)
- **Engines**: PHP (local), Python, Docker (external)

## Common Commands

```bash
# Windows (Laragon)
symfony serve

# Linux
symfony server:start

# Both
symfony console doctrine:migrations:migrate -n
symfony php bin/phpunit
symfony php vendor/bin/phpstan analyse
```

## Testing

All tests must pass:
```bash
composer test  # PHPUnit + PHPStan
```

## Memory Protocol

- Use engram for persistent context across sessions
- Save architecture decisions and bug fixes with `mem_save`
- Search before starting: `mem_search` for past decisions