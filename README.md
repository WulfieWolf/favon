# Camperwolf.de

Camperwolf.de is a quality-focused, map-centric directory and community platform for camping, motorhome, campervan and service locations.

Germany is the initial market, but the project is designed for later European and international expansion. The application is built with Laravel and Livewire and is intended to become a mobile-first website/PWA before any native-app work.

The project deliberately focuses on structured, current and transparent place information rather than maximizing the number of listings or features. Public place data is designed to be historically traceable, community-maintained and moderated.

## Documentation

The detailed product and architecture documentation is maintained here:

- [`docs/PROJECT_OVERVIEW.md`](docs/PROJECT_OVERVIEW.md) – product concept, data model, conventions, implemented features, roadmap and current development status.
- [`docs/RECOVERY_PROMPT.md`](docs/RECOVERY_PROMPT.md) – context-recovery prompt for continuing the project if the original development chat/context is lost.

For implemented behavior, the code and migrations on `main` remain authoritative. The project overview records the intended product behavior and architectural decisions.

## Current stack

- Laravel 13
- Livewire 4
- Flux UI / Tailwind
- MySQL 8
- PHP 8.3+ application requirement; local development currently uses PHP 8.4
- Node/npm frontend tooling

## Development status

The project currently has the database foundation for places, localization, features/tags, typed units, pricing, opening hours, reviews, photos, audit history, provenance, user suggestions and historical/versioned public place data.

The next major implementation step is the backend service for safely reviewing and applying grouped `change_requests`.

See [`docs/PROJECT_OVERVIEW.md`](docs/PROJECT_OVERVIEW.md) for the full status and design decisions.
