# Favon

Favon is a privacy-oriented, community-maintained directory and map for cruising and hookup-relevant places.

The product principle is **“Places, not people.”** Favon describes places and their current condition while deliberately avoiding public contributor identities, visit histories, chat, dating features, photos and free-text sexual content.

## Current access model

- community users sign in with Telegram via OIDC
- Favon stores only the stable Telegram numeric ID required for account mapping
- community accounts receive internal names such as `User-0000001`
- classic e-mail/password login is reserved for the system owner and administrators
- public Fortify registration is disabled
- place/map content is login-gated

## Technical stack

- Laravel 13
- Livewire 4
- Flux UI / Tailwind
- MySQL 8
- PHP 8.4 in production
- Node/npm frontend tooling
- Leaflet bundled locally

## Documentation

The authoritative project and development context is:

- [`docs/PROJECT_CONTEXT.md`](docs/PROJECT_CONTEXT.md)

The code and migrations on `main` remain authoritative for implemented behavior.

## Repository separation

Favon is technically and organizationally separate from Camperwolf. Camperwolf may be used as read-only reference material during the one-time cleanup of inherited code, but Favon changes must only be written to this repository.
