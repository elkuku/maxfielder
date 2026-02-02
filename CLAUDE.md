# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Maxfielder is a PHP frontend for the maxfield Python script, built with Symfony 8 and PHP 8.3+. It provides an interactive map interface for planning Ingress field operations.

## Common Commands

### Development
```bash
bin/install          # Full setup: Docker, migrations, Symfony server
bin/start            # Start Docker + Symfony dev server
bin/stop             # Stop all services
```

### Testing & Quality
```bash
make tests                     # Full test suite (DB reset, PHPUnit, Rector dry-run, PHPStan)
composer test-phpunit          # PHPUnit only
composer test-phpstan          # PHPStan only
composer ci                    # Run all tests (PHPUnit + PHPStan)
symfony php bin/phpunit --filter=TestName  # Run single test
```

### Database
```bash
symfony console doctrine:migrations:migrate -n
symfony console doctrine:fixtures:load -n
symfony console user-admin     # Create admin user
```

### Assets
```bash
symfony console importmap:install
symfony console asset-map:compile  # Production
```

### Deployment
```bash
bin/deploy           # Production deployment
```

## Architecture

### Backend (Symfony 8)
- **Entities**: `src/Entity/` - User (roles: USER/AGENT/ADMIN), Maxfield, Waypoint
- **Services**: `src/Service/` - Business logic (MaxFieldGenerator, MaxFieldHelper, WayPointHelper)
- **Controllers**: `src/Controller/` - Route handlers; `src/Controller/Admin/` - EasyAdmin CRUD
- **Authentication**: Form login + OAuth2 (GitHub, Google) via custom authenticators in `src/Security/`

### Frontend
- **Templates**: Twig in `templates/`, base layouts: `base.html.twig`, `base-map.html.twig`
- **JavaScript**: Stimulus controllers in `assets/controllers/`
- **Maps**: Leaflet 1.9 + Mapbox GL for interactive mapping
- **CSS**: Bootstrap 5.3 + custom styles in `assets/styles/`
- **Assets**: Managed via Symfony Importmap (see `importmap.php`)

### Key Integration
- Maxfield Python script runs in Docker container (configured via `APP_DOCKER_CONTAINER` env var)
- Results stored in `public/maxfields/`

## Code Quality Configuration

- **PHPStan**: Level 6, 100% type coverage required (`phpstan.neon.dist`)
- **Rector**: PHP 8.2 + Symfony 6.4 rules (`rector.php`)
- Baseline files exist for managing existing issues

## Environment Variables

Key variables in `.env`:
- `DATABASE_URL` - PostgreSQL connection
- `APP_DOCKER_CONTAINER` - Maxfield Docker container ID
- `OAUTH_GOOGLE_*`, `OAUTH_GITHUB_*` - OAuth credentials
- `GOOGLE_API_KEY` - For map services
- `APP_DEFAULT_LAT/LON/ZOOM` - Default map position
