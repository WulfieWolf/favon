# Camperwolf.de - Recovery Prompt

_Last updated: 2026-09-28_

This file restores the current Camperwolf.de project context for a new ChatGPT development session.

The repository `WulfieWolf/camperwolf` on branch `main` is authoritative for implemented behavior. Read current code before editing. `docs/PROJECT_OVERVIEW.md` contains useful product history but can lag behind this recovery file and the actual code.

## 1. Project and working style

Camperwolf.de is a free, community-driven portal for camping, motorhome, campervan, parking, rest-area and service locations.

Core principles:

- quality over sheer quantity;
- no paywall and no paid ranking/visibility advantage;
- guests can search and browse;
- contributions require an account;
- missing structured data means unknown, never automatically false;
- public place changes remain historically traceable;
- owner/admin changes remain audited;
- imported/open data never silently overwrites independent community data;
- prohibited, unclear or unwanted places are marked rather than silently erased;
- German and English are first-class UI languages;
- desktop and mobile are both V1 requirements.

User-facing language should be simple, direct and adult. Prefer everyday language over technical or bureaucratic wording. The user describes the target as "ob meine Mutter das versteht".

The user prefers direct changes to GitHub `main`, then pulls locally and tests. Do not casually redesign already approved UI.

Normal prose formatting preference:

- straight double quotes `"`;
- short hyphen/minus `-`;
- avoid typographic curly quotes and long dashes unless technically required.

For Linux/SSH work, explain only the purpose at a high level and give one logical command/group at a time. Do not make risky server changes without checking state first.

## 2. Local and production environments

### Local Windows development

Typical project path:

`D:\Dropbox\Eigene Dateien\Dokumente\Server\Camperwolf`

Current local stack:

- Laravel 13;
- Livewire 4;
- Flux UI / Tailwind;
- PHP 8.4;
- MySQL 8;
- Node 24 / npm 11;
- Laravel Herd;
- local URL `http://camperwolf.test`.

PowerShell may block `npm.ps1`; use `npm.cmd` when needed.

Typical commands:

```powershell
git pull --ff-only
php artisan migrate
php artisan optimize:clear
npm.cmd run build
```

Only run seeders that are actually required. Never casually use `migrate:fresh --seed`.

### Production

Production Laravel app:

- URL: `https://camperwolf.de`;
- path: `/var/www/vhosts/camperwolf.de/laravel/current`;
- Plesk web user: `camperwolf.de_qpfpse9uwk:psacln`;
- Plesk PHP: 8.4.25;
- MySQL DB: `camperwolf_app`, DB user `cw_app`;
- `.env`: production, debug false, DB-backed session/cache/queue, Telescope off, Brevo SMTP;
- HTTPS/HSTS/security headers active;
- `/up` returns 200.

WordPress was moved to `https://blog.camperwolf.de`. The old WordPress tree remains in `httpdocs` as rollback material.

Do not disturb:

- `blog.camperwolf.de`;
- `ferienhaus-dolp.de`;
- `slg-bbs-essen.de`;
- Plesk itself.

Git deploy uses a read-only deploy key. Run production git operations as the Plesk web user, not root.

Standard deployment pattern:

1. check status/log;
2. as web user: `git pull --ff-only`;
3. `php artisan migrate --force` if needed;
4. Node 22 build if frontend changed: `npm ci && npm run build`;
5. `php artisan optimize:clear`;
6. live checks;
7. `php artisan optimize`;
8. `php artisan queue:restart` after relevant job/service changes.

Queue and scheduler are already configured through systemd/minutely scheduling.

Backups: Plesk full backup daily 02:00, 7-day retention. Restore test is still pending.

## 3. Current V1 product scope

V1 is now feature-complete enough that the next major milestone is beta acceptance, not another large feature cycle.

V1 includes:

- public place search/browse/map;
- place profiles;
- place suggestions and moderated changes;
- structured features;
- recurring opening hours;
- structured seasonal pricing;
- reviews;
- photo workflow;
- favorites;
- notifications;
- profiles;
- XP/levels/badges/achievements;
- help/support;
- legal/privacy/account lifecycle;
- DE/EN;
- desktop/mobile layouts;
- open-data import foundation;
- production deployment foundation.

Explicitly later / post-V1 unless separately requested:

- verified GPS/check-in;
- verified place owners/operators and special owner rights;
- community-helper majority voting;
- AI review summaries;
- social profile links;
- native app work;
- advanced event/festival objects;
- complex booking logic.

## 4. Public browse and place UI

The browse page is the public home page and is usable by logged-out guests.

Current browse/filter behavior includes:

- map + result list;
- place-type multi-select;
- "Geeignet für" vehicle filters;
- always-visible priority area "Das Wichtigste" when values exist;
- price filtering;
- score filtering;
- favorites;
- "Nur Favoriten";
- map-viewport filtering on desktop;
- removable active-filter chips;
- session persistence;
- type-icon fallback where no place photo exists;
- mobile layout with map on top and results below;
- mobile filter/profile controls;
- result cards with name, type, address, operating/open status, tags, score and thumbnail.

Current price basis for list/display:

- "Ab-Preis" = cheapest current general overnight price + cheapest current general vehicle price;
- child price is not part of that base;
- unknown is not zero.

Operating state is separate from opening state.

Operating states include:

- unknown;
- in operation;
- permanently closed;
- temporarily closed;
- seasonally closed.

Opening display includes open, closed, closes within 60 minutes, opens within 60 minutes.

## 5. Place workflow and maintenance

### New place

Current V1 creation flow is intentionally short:

1. name, place type, map position/basic data;
2. optional relevant features.

The user can submit after minimal required data.

After submission:

- normal-user place is pending moderation;
- do not describe it as published before approval;
- duplicate detection includes published and pending places;
- duplicate warning is advisory, not a hard block.

### Published place maintenance

Every published place is intended to be effectively maintainable.

Dedicated workflows exist for:

- general place information;
- features;
- opening hours;
- structured prices;
- reviews;
- photos.

Normal users create moderated change requests. Privileged direct edits are audited.

Place history is visible in "Basisdaten" and includes author/source, date/time, before -> after and approval time. Reviews/photos are not part of this place-data history.

### Place types

Selectable V1 place types:

- campground;
- motorhome-pitch;
- tent-site;
- parking;
- rest-area;
- free-pitch;
- service-station;
- camping-outdoor.

Legacy types remain only for old data where applicable.

## 6. Features, reviews, photos and duplicates

### Features

The canonical feature model is the existing `features` + versioned `place_features` system.

Place-type/category mapping controls which categories are standard.

Public profile behavior:

- standard categories are shown;
- non-standard categories remain hidden while completely unknown;
- as soon as a feature in an extra category is known, that category appears;
- editors can expose additional categories.

### Reviews

Review MVP is implemented.

Five required 1-5 dimensions:

- cleanliness;
- functionality;
- condition;
- safety;
- usability.

Rules include:

- registered users only;
- optional text;
- one current review per user/place;
- version history;
- correction window;
- later version cooldown;
- validity window;
- score history;
- newest-first default sorting.

Quality score must not be distorted by luxury/feature quantity.

### Photos

Photo MVP is implemented:

- private originals;
- processing to metadata-free WebP;
- moderation workflow;
- max 5 review photos per review/place context as designed;
- helpful votes;
- automatic thumbnail selection by helpful votes;
- admin thumbnail override;
- UUID names.

### Duplicate merge

Duplicate-place merge is implemented and tested. Imported-source identity/provenance remains separate even when the visible place is merged/consolidated.

## 7. Profiles, XP, badges and community identity

Permanent identity model:

- every user has a permanent `CW-XXXXX` Camperwolf ID;
- optional public alias is separate;
- alias can be finalized once;
- both CW-ID and alias routes resolve;
- normal display prefers alias when available.

Profile data includes optional picture, bio, hometown, birth date/age visibility, gender, vehicle/travel setup and privacy settings.

Exact birth date is never publicly rendered. Social links remain intentionally disabled.

Gamification:

- XP source of truth is immutable-style `xp_ledger`;
- current XP is derived from ledger sum;
- corrections use separate positive/negative events;
- level is derived, not stored;
- profile can hide gamification display without stopping XP collection.

Badges/achievements are implemented:

- progress badges with Bronze/Silver/Gold/Platinum tiers;
- achievements;
- hidden achievements support;
- manual awards;
- public selected profile title;
- rarity statistics.

Tier labels are localized correctly:

- DE: Bronze, Silber, Gold, Platin;
- EN: Bronze, Silver, Gold, Platinum.

## 8. Notifications

Notification system is implemented with:

- unread counts;
- dropdown;
- list/detail pages;
- settings;
- system announcements;
- moderation results;
- favorite changes;
- support;
- badge unlocks;
- helpful-photo milestones.

New registrations during the public beta also receive a `beta_welcome` notification.

Clicking that notification opens the same global beta-information modal via `?beta=1`.

Known minor behavior, not necessarily V1-blocking:

- helpful-photo milestone notifications can repeat if counts fall and later cross a threshold again;
- badge revoke does not send a notification;
- some direct gamification/helpful notifications bypass ordinary preference categories by design/current implementation.

## 9. Help, public information and beta messaging

Help system is substantial and DB-backed.

Canonical help seeder: `SupportContentSeeder`.

Important warning: rerunning it overwrites seeded article body content by slug. Do not casually reseed production after admins have edited content without checking strategy.

Public help currently covers core workflows, notifications/settings, profile/account/security, photo/review rules, support/tickets, XP, badges, data export, account deletion, FAQ and "Über Camperwolf".

A transparency article exists:

- DE: "Verwendete Software, Dienste & Lizenzen";
- EN: "Software, services & licences".

It lists major user/privacy/license-relevant technology such as Laravel, Livewire, Flux, Tailwind, Leaflet, OpenStreetMap, Photon and Brevo. It deliberately does not claim to list every internal dependency.

"Über Camperwolf" is public and linked from the header.

First-visit welcome splash is implemented and accepted.

Global beta notice is implemented as a permanent floating "Beta" button in the lower-left. It opens a modal explaining:

- Camperwolf is in beta;
- data should be treated as real/live data;
- larger technical/structural changes may still require individual data adjustments/resets;
- user accounts should remain where possible;
- feedback via Telegram or bug reporting.

## 10. Admin user management and access control

Admin user management is implemented:

- search/filter;
- account details;
- role/permission management;
- suspend/unsuspend;
- manual badges;
- account deletion/anonymization.

Important protections added 2026-09-28:

1. Admin account audit logs no longer store old/new names or email addresses for account edits/deletions.
2. Suspension reason is treated as internal and is not shown to the suspended user.
3. System Owner email is protected from being changed through normal profile settings or admin user management, because owner identity currently depends on configured owner email.

Suspended users see only that the account is suspended and, if set, the planned end time.

Auto-expired suspension is cleared on the next request. This automatic transition is currently not separately audited.

## 11. Devlog and public version history

Public Devlog is implemented and starts at the V1 Beta release.

Old Pre-Alpha releases are retained internally but hidden publicly.

Important fix:

- `DevReleaseSeeder` now keeps all old Pre-Alpha entries `is_public=false`;
- a regression test ensures reseeding cannot accidentally republish them.

The current beta label special-case renders as `v1 Beta`.

The fixed global footer links to the Devlog/version and legal pages.

Potential cleanup before final stable launch:

- beta release `released_at` came from migration execution time rather than an explicitly selected public-launch timestamp.

## 12. Branding and framework-neutral UI

Visible Laravel/starter-kit branding has been removed from the normal UI.

Current branding state:

- app-name fallback is "Camperwolf";
- old Laravel favicon assets are removed;
- current temporary favicon/app mark is a simple "CW" monogram;
- starter-kit Repository/Documentation links were removed;
- `composer.json` package metadata is now Camperwolf-specific;
- user manually checked common screens and no visible Laravel branding remains.

Still intentionally pending cosmetic replacement:

- final Camperwolf/wolf logo instead of temporary CW monogram;
- final error-page photo of Merlin.

### Error pages

Custom Camperwolf error pages exist for:

- 403;
- 404;
- 419;
- 500;
- 503;
- generic remaining 4xx;
- generic remaining 5xx.

Design:

- simple human-readable message;
- technical status line;
- actions such as home/reload/report;
- reserved image area for a sad-looking Merlin photo.

Planned image path:

`public/images/error-merlin.jpg`

Do not expose stack traces, file paths or raw internal exceptions in production error pages.

## 13. Legal/privacy/account lifecycle

Public pages exist for:

- Impressum;
- Datenschutz;
- Nutzungsbedingungen.

Account data export/deletion and GDPR-oriented support flows exist.

IMPORTANT final legal review item:

The privacy text must be rechecked before public beta/stable launch against the actual production services. Earlier wording may still claim that final hosting/email providers are not selected, while production already uses Brevo and the current hosting environment is known. Fix factual inconsistencies before launch.

Also review `THIRD_PARTY_LICENSES.md` during the final legal/license pass. Do not claim mandatory third-party notices are complete without verifying actual bundled license requirements.

## 14. Open-data import

Strategy:

- external sources/records stay separately identifiable;
- never overwrite community/owner/admin values blindly;
- conflicts are visible/reviewable;
- place history keeps source attribution;
- import is repeatable;
- no automatic deletion of missing external records;
- format changes should fail safely.

SID DATEX-II import work is implemented far enough to ingest real data.

Static SID sample result previously observed:

- 2,612 records;
- 57 without coordinates;
- vehicle/access/feature mappings parsed;
- zero capacity treated as unknown;
- invalid XML rejected.

Imported data has been successfully brought into the main visible place layer while preserving its imported-source identity/provenance.

SID formal approval/permission was still not confirmed at the last check, so do not assume unrestricted production use without verifying licensing/approval.

DZT API access remains pending/unclear and is not required for the immediate beta acceptance.

## 15. Production operations status

Completed:

- server prepared;
- WordPress moved to blog subdomain;
- Laravel production clone;
- production `.env`;
- TLS/HSTS;
- Brevo transactional mail;
- SPF/DKIM/DMARC;
- queue worker;
- scheduler;
- lockdown/registration-closed modes;
- emergency CLI return to normal mode;
- cache ownership fixes;
- Plesk backups scheduled.

Still planned before final stable launch, and preferably after beta acceptance:

- storage/upload permission review;
- restore test;
- monitoring/error-worker checks;
- formal rollback path;
- security hardening review;
- final production rehearsal.

Do not treat these as beta UI features. They are operations readiness work.

## 16. GitHub Actions / CI

Main workflow is optimized to reduce Actions usage:

- push/PR ignores docs/markdown/dependabot-only changes where configured;
- manual dispatch;
- concurrency cancellation;
- read-only contents;
- 15-minute timeout;
- npm/composer caching;
- `composer ci:check`.

Strategy:

- targeted tests for focused changes;
- one full CI for larger coherent states;
- documentation-only context commits should skip CI;
- full acceptance test before beta.

## 17. Beta-readiness roadmap

The pre-beta product checklist was:

1. Help content - complete.
2. First-visit welcome splash - complete.
3. Notifications incl. badges/helpful/community events - complete for V1 scope.
4. Admin user management - complete.
5. Public info/feature overview/devlog - complete.
6. Global Impressum/Datenschutz/Nutzungsbedingungen links - complete.
7. Clean user dropdown - complete.
8. Global beta notice + beta welcome notification - complete.
9. Branding/framework-neutral UI - functionally complete; final logo and Merlin image are cosmetic follow-ups.
10. Beta acceptance/opening - NEXT.

## 18. Immediate next-session task: beta acceptance

The next session should start with a compact beta smoke-test/acceptance pass. This is not a full re-test of every edge case. Automated tests already cover much of the internal logic.

Goal: "einmal alles Wichtige anfassen" from a real user's perspective and confirm the main flows still work after the final integration changes.

Recommended acceptance packages:

### Package 1 - account/auth

- registration;
- verification email;
- login;
- logout;
- forgot/reset password;
- change email;
- suspended-account behavior;
- account deletion.

### Package 2 - browse/place discovery

- guest home/browse;
- search;
- place-type/vehicle/priority filters;
- score/price filters;
- favorites;
- map/list interaction;
- open place profile.

### Package 3 - contributions

- suggest new place;
- duplicate warning;
- moderation approval;
- suggest/edit place information;
- features;
- opening hours;
- prices;
- place history.

### Package 4 - reviews/photos/community

- create/update review;
- photo upload/moderation;
- helpful vote;
- thumbnail behavior;
- XP/badge effects;
- notifications;
- profile display.

### Package 5 - support/legal/language

- help;
- bug report/support ticket;
- beta notice;
- DE/EN spot checks;
- legal footer/pages;
- custom error pages.

### Package 6 - device/UI

- desktop browser;
- Samsung Galaxy S22 Ultra / Firefox Android;
- fixed footer overlap;
- mobile map/results;
- modals and floating buttons.

If acceptance finds no blocking issue, proceed to beta opening/deployment.

## 19. Known final checks before stable V1

These are not all beta blockers, but should not be forgotten:

- final Camperwolf/wolf logo;
- Merlin error-page photo;
- privacy/legal text factual provider review;
- `THIRD_PARTY_LICENSES.md` / license obligation check;
- beta release date cleanup if desired;
- restore test;
- storage permissions;
- monitoring;
- rollback/rehearsal;
- final database/content strategy for stable launch;
- official data licensing/approval verification;
- final feature/category catalogue review only if still necessary after beta feedback.

## 20. Do not regress these decisions

- Keep Camperwolf free for end users.
- Do not introduce paid ranking advantages.
- Do not turn unknown into false/zero.
- Do not expose internal admin routes or permission details unnecessarily.
- Do not expose internal suspension reasons.
- Do not store unnecessary account PII in audit logs.
- Do not allow the System Owner to accidentally lose owner rights through a normal email edit.
- Do not republish old Pre-Alpha Devlog entries.
- Do not reintroduce visible Laravel branding.
- Do not expose production stack traces.
- Do not run destructive DB resets on production.
- Do not overwrite community data blindly with imports.
- Do not redesign approved UI without a concrete reason.

## 21. Current handoff

The last completed work before this recovery update:

- custom branded error-page system;
- framework-neutral branding pass;
- account audit PII minimization;
- System Owner email-change protection;
- DevReleaseSeeder privacy regression fix;
- DE/EN badge-tier localization;
- internal-only suspension reason.

All corresponding focused tests reported green by the user.

The next agreed action is to begin the beta acceptance smoke test in a fresh session.
