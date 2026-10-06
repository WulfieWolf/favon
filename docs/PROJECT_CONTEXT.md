# Favon – Project Context

_Last updated: 2026-10-06 – local runtime/build/test validation complete for cleanup baseline_

This file is the authoritative working context for **Favon**.

It must contain only information that is relevant to Favon itself, Favon's implementation, Favon's deployment, or the one-time technical inheritance from Camperwolf that is still useful for Favon development.

Do not reintroduce Camperwolf product planning, Camperwolf import-source status, Camperwolf marketing plans, Camperwolf content rules, Camperwolf data-maintenance backlog, or other unrelated Camperwolf project history here.

---

# 1. Project identity

**Project:** Favon  
**Domain:** favon.de  
**GitHub repository:** `WulfieWolf/favon`  
**Framework:** Laravel  
**Hosting:** same Plesk server as Camperwolf, but fully separate application

Favon is a community-maintained directory/map for cruising and hookup-relevant places.

The central product principle is:

> **Places, not people.**

Favon is intentionally **not**:

- a dating platform
- a social network
- a hookup/contact marketplace
- an escort or prostitution directory
- a porn site
- a user-discovery service

The service should describe and maintain information about **places**, while revealing as little as possible about the people who use or maintain the service.

---

# 2. Hard project separation: Favon vs. Camperwolf

This is a permanent development rule.

Favon and Camperwolf are **two independent projects**.

## Favon repository

All Favon code changes belong in:

```
WulfieWolf/favon
```

## Camperwolf repository

Camperwolf remains:

```
WulfieWolf/camperwolf
```

For Favon work, Camperwolf is **read-only reference material**.

Never:

- commit Favon changes to Camperwolf
- push to Camperwolf while working on Favon
- modify Camperwolf configuration for Favon
- share Favon's database, .env, application state, session state, cache namespace or storage with Camperwolf
- assume a Camperwolf product feature belongs in Favon just because code for it exists

Before any write operation, verify which project is currently being worked on.

If the current task is Favon, writes go only to `WulfieWolf/favon`.

Camperwolf may be read when useful for:

- extracting proven Laravel patterns
- reusing map/geolocation code
- reusing security/abuse patterns
- reusing deployment conventions
- comparing behavior during the initial slimming phase

Favon should then diverge freely.

---

# 3. Origin of the codebase

Favon was initialized from the current Camperwolf Laravel codebase as a **one-time starting snapshot**.

This is not intended to create a continuing fork relationship.

The goal is:

1. reuse a proven application foundation
2. remove Camperwolf-specific complexity aggressively
3. rebuild Favon around a much smaller domain model
4. keep only infrastructure that materially helps Favon

The original copied Camperwolf code is therefore not a specification for Favon.

When deciding between:

- keeping a complex Camperwolf subsystem
- replacing it with a much simpler Favon-specific implementation

prefer the simpler Favon implementation unless there is a strong technical reason not to.

---

# 4. Product philosophy

Favon should be substantially simpler than Camperwolf.

Primary goals:

- mobile-first
- very low interaction friction
- site content is login-gated; unauthenticated visitors see only the access gateway plus required public legal/support entry points
- community users authenticate via Telegram; classic password login is reserved for admins/system owner
- minimal forms
- no public user identities
- no user profiles as social objects
- no free-text sexual content
- no photos
- no tracking/profiling
- minimal moderation burden
- current information maintained through short community microtasks

The service should be usable without needing to understand a complex contribution workflow.

A user who is physically at a place should be able to contribute useful data within seconds.

Core interaction principle:

> **On site: quickly confirm whether things still fit. At home: work on details.**

---

# 5. Scope of places

Favon should stay narrowly focused on typical cruising/hookup-oriented places.

Likely categories include:

- public cruising spots / cruising areas
- gay saunas
- gay bars or similar venues where the hookup/cruising relevance is genuinely part of the venue's purpose or common use
- other closely related public/commercial places that fit the same narrow use case

The exact place-type catalogue is still to be finalized.

## Out of scope

Do not turn Favon into a broad adult-industry directory.

Reject or avoid:

- brothel directory
- escort directory
- private prostitution offers
- private apartments or homes
- personal ads
- profiles of sex workers
- pornographic venues included only because pornography is consumed there
- generic “places where sex can be purchased”
- schools
- playgrounds
- other clearly inappropriate child/family-sensitive locations
- private property where normal use would imply trespassing
- doxxing or personally identifying information

Public outdoor places may be listed if the location itself is legitimate to enter/use and the entry can be described neutrally.

Favon must describe **where a place is and what kind of place it is**, not provide instructions for how to engage in sexual activity unnoticed.

---

# 6. Authentication and account model

Implemented V1 model: **Telegram-only login for community users**, while the existing e-mail/password login remains available only for administrators/system owner.

## Implemented login flow

- unauthenticated visitors land on the Favon access gateway
- gateway offers “Mit Telegram anmelden” and a separate “Admin-Login”
- Telegram Login Widget authenticates community users
- Favon verifies the signed Telegram payload server-side with the bot token
- only the stable Telegram numeric ID is persisted for community authentication
- Telegram first name, last name, username and profile photo are not stored
- callback data is POSTed to Favon rather than placed in the callback URL, reducing the risk of profile fields appearing in access logs
- unknown Telegram ID -> a minimal Favon account is created automatically
- known Telegram ID -> the existing Favon account is reused
- public Fortify registration is disabled
- password login is rejected for normal community users even if password credentials were ever present

Do not rely on Telegram username because it can change.

## Minimal account data

Implemented/current minimum direction:

- internal Favon user ID
- stable Telegram numeric ID in a separate `community_accounts` mapping
- generated readable internal name such as `User-0000001`
- role / permissions
- ban/abuse state
- timestamps

Avoid storing unless technically required:

- Telegram display name
- Telegram username
- profile photo
- phone number

## Public anonymity

Users must never publicly see who:

- created a place
- edited a place
- voted on a property
- rated a place
- checked in
- favorited a place
- reported a place

No public contributor history.

No public visit history.

No public user discovery.

No follower/friend model.

No “who is here” feature.

The system is therefore **pseudonymous**, not mathematically anonymous: administrators may still need internal account mapping for abuse prevention and bans.

---

# 7. Place creation

Place creation must be extremely low-friction.

## Minimum

Required:

- place type
- location / coordinates

Optional:

- name

If no name exists, the display name can fall back to the place type.

Examples:

```
Cruising-Platz
```

or:

```
Stadtpark Essen · Cruising-Platz
```

## Small set of basic characteristics

A few stable characteristics may be collected at creation because they define the place and are unlikely to need a live health check.

Current candidates:

- indoor / outdoor
- typical/intended audience
- parking available

Potential audience model is still undecided; current conceptual options include male / female / mixed or equivalent neutral terminology.

Keep this set small.

---

# 8. Data model principle: stable facts vs. live conditions

Favon distinguishes two fundamentally different categories of information.

## 8.1 Stable/base place information

Examples:

- location
- place type
- name
- indoor/outdoor
- typical audience
- parking available

These are maintained from the **place profile**, not during every check-in.

Users can confirm whether the current value is still correct or vote for a valid alternative.

Example:

```
Current: Outdoor
Stimmt das?
[Ja] [Nein]
```

If “Nein”, the user can choose a valid alternative such as Indoor.

A single user must not be able to rewrite important core facts immediately.

Consensus / moderation thresholds can depend on field sensitivity.

Position changes deserve more caution than a simple binary property.

## 8.2 Variable/live information

These are things that can meaningfully change and that a person on site can verify quickly.

Current examples:

- currently accessible/open
- toilet currently available
- washing facility currently available
- waste disposal currently available
- lighting operational

This catalogue should remain deliberately short.

Variable information is primarily refreshed through check-in health checks.

---

# 9. Community voting / consensus

Favon should avoid an “authoritative editor” model for ordinary facts.

Instead, current state is derived from community confirmations.

Internally, individual votes should remain linked to an account where necessary for:

- duplicate-vote prevention
- rate limits
- abuse investigation
- vote replacement/update rules
- moderation

Publicly, only aggregate state is shown.

A conceptual vote record may contain:

```
place_id
feature_or_field_id
user_id
value
voted_at
```

The exact schema is still open.

## Temporal freshness

Variable information should decay over time.

Example:

If a toilet was confirmed repeatedly a year ago but nobody has confirmed it recently, it should eventually stop appearing as confidently current.

Concept:

- each vote has a timestamp
- only sufficiently recent votes affect current state
- old votes expire from active consensus
- stale features become unconfirmed
- different features may use different TTL/freshness windows

Stable properties such as indoor/outdoor do not need the same short expiry behavior.

---

# 10. Check-in

Check-in is a core Favon feature.

Its purpose is to answer:

> **Is something currently happening here?**

It must never answer:

> **Who is here?**

## Proximity validation

Expected flow:

1. user taps check-in
2. browser requests current position
3. server verifies that the user is within an acceptable radius of the place
4. raw position is used only for this validation
5. raw GPS coordinates are not stored

Exact radius is still TBD.

Earlier conceptual range only: roughly 100–300 m depending on practical testing.

## Check-in lifetime

Check-ins are short-lived and expire automatically.

Exact lifetime is still TBD.

Earlier conceptual range only: roughly 60–90 minutes.

## Minimal check-in record

Possible model:

```
place_id
user_id or temporary pseudonymous token
checked_in_at
expires_at
```

The account link should exist only as long as technically needed to prevent duplicate active check-ins and abuse.

Long-term movement histories should not be built.

## Public output

Possible public presentation:

- “Aktuell 3 Check-ins”
- “Hier ist gerade etwas los”
- quiet / some activity / busy

Never show:

- user names
- profile links
- visitor lists
- who arrived when
- how often one specific user visits

---

# 11. Check-in health check

A check-in should double as a very quick health check of current place conditions.

Core rule:

> **Check-in is for current condition, not stable place editing.**

## Default flow

Always establish whether the place is currently accessible/open.

Then optionally confirm only relevant variable features.

Ideal compact interaction:

> **Alles noch aktuell?**  
> [ Ja, passt ] [ Nein, etwas stimmt nicht ]

If “Ja”:

- current accessibility is freshly confirmed
- currently listed variable features may receive a fresh confirmation

If “Nein”:

- show only the few variable features that could currently be wrong
- user identifies the affected feature(s)

## Example: toilet

Options may be:

- still available
- unknown
- not available

If unavailable, optionally distinguish:

- temporarily unavailable
- permanently removed
- unknown

## Example: accessibility

Options may be:

- accessible/open
- unknown
- inaccessible/closed

If closed:

- temporary
- permanent
- unknown

One negative vote must not instantly rewrite the public state.

Prefer recent independent-user consensus.

## Intelligent microtasks

Do not ask every visitor every possible question.

Prioritize stale/uncertain information.

Example:

- toilet confirmed 15 times today → do not ask again
- washing facility unconfirmed for months → ask the next suitable visitor

Goal: useful maintenance in seconds.

---

# 12. Ratings

Favon should have **structured ratings only**.

No free-text reviews.

No user photos.

No sexually explicit “experience reports”.

## Potential rating dimensions

Current candidates:

- cleanliness
- discretion/privacy
- lighting
- accessibility
- public/foot traffic
- feeling of safety
- atmosphere (optional)

Likely scale: 1–5.

Additional structured observations may include:

- mostly daytime / evening / night
- quiet / variable / busy
- open / partly screened / discreet
- typical audience pattern

Final questionnaire should remain short enough for mobile use.

Preferred wording:

- “Ort bewerten”
- “Einschätzung abgeben”

Avoid wording that encourages explicit descriptions of sexual encounters.

---

# 13. Favorites

Favorites are useful and can remain part of Favon.

They are private user state.

Never publicly reveal:

- who favorited a place
- how many favorites a specific user has
- a user's public favorite list

Aggregate favorite counts should only be exposed if there is a clear product reason later.

---

# 14. Photos and free text

Current product decision:

- **no place photos**
- **no review photos**
- **no user-uploaded media**
- **no free-text reviews**
- **no explicit sexual descriptions**

This is a deliberate architectural/product constraint, not merely deferred functionality.

Benefits:

- substantially lower moderation burden
- fewer privacy risks
- less risk of identifiable people appearing in media
- less explicit sexual content
- simpler youth-protection position
- smaller storage/infrastructure footprint

Owner-specific media uploads are not currently part of the concept.

---

# 15. Moderation

Favon controls its catalogue.

Not every submitted place needs to be published.

Reject entries involving:

- private residences
- personal ads
- doxxing
- identifiable visitor descriptions
- pornographic content
- explicit sexual instructions
- child/family-sensitive locations
- obvious abuse/trolling
- locations requiring trespassing
- entries outside Favon's defined place scope

A robust report/takedown route should exist for:

- wrong places
- inappropriate entries
- owners/operators
- neighbours/affected parties
- legal complaints

Administrative identity mapping may be used where needed to enforce bans.

---

# 16. Privacy / GDPR direction

Favon requires stronger privacy-by-design than an ordinary place directory because a check-in at a cruising/gay venue can potentially reveal information about sexual life or orientation.

This may involve special-category data implications under GDPR.

Design accordingly.

## Core privacy rules

- raw GPS location is not stored
- no movement profiles
- no public visit history
- no public account attribution
- no unnecessary Telegram profile data
- short retention for active check-ins
- collect only data needed for a specific function
- separate public aggregate data from internal abuse-prevention identity
- no third-party tracking unless later deliberately introduced and legally assessed

Before launch, assess whether a DPIA / Datenschutz-Folgenabschätzung is required.

Do not describe Favon as fully anonymous if an operator can internally map activity to an account.

Use “pseudonymous” internally/legalistically where accuracy matters.

---

# 17. Youth-protection direction

Favon should deliberately avoid the features that would turn it into an explicit sexual-content or matchmaking service.

Avoid:

- pornography
- explicit user-generated descriptions
- sexual images
- chat
- DMs
- contact ads
- dating/matching
- person search
- hookup requests/offers

Structured neutral location data is preferred.

The final public presentation may be adult-oriented / 18+, but exact youth-protection requirements should be reviewed before launch.

The product should not collect intrusive identity documents unless a later legal assessment shows that this is necessary.

---

# 18. Legal/operator notes

Favon will need normal German legal pages such as:

- Impressum
- Datenschutzerklärung
- Nutzungsbedingungen / content rules as appropriate

The operator does not want a private residential address exposed publicly if avoidable.

Possible later option to evaluate:

```
flexdienst.de
```

as a service for a ladungsfähige Impressumsadresse / Zustellungsvollmacht.

This is only a noted possibility; it has not been selected or booked.

Exact operator/legal-entity structure is still open.

---

# 19. Technical architecture direction

Favon should reuse only the parts of the copied Laravel application that materially help.

## Likely reusable foundations

- Laravel application structure
- map/geolocation logic
- mobile-first UI patterns
- public browse/search architecture
- place profile architecture
- favorites
- admin/roles basics
- abuse/rate-limit concepts
- moderation patterns
- deployment conventions
- queue/scheduler infrastructure where genuinely needed
- DE/EN localization architecture if retained

## Camperwolf-derived systems removed from active paths or still pending schema cleanup

Favon does not need:

- camping-specific feature catalogue
- vehicle suitability
- camping price models
- camping season logic
- complex camping opening-hours system
- Open Data import center
- external_sources / external_records import pipelines
- destination.one integration
- NRW TFIS integration
- Bayern ATKIS integration
- Overture import
- DATEX-II import
- photo import
- photo moderation
- EXIF/image processing
- thumbnails/helpful photo votes
- owner-verification workflows
- XP
- badges
- achievements
- complex review-photo/report systems
- camping-specific e-mail flows
- camping-specific legal/help content
- Camperwolf branding/assets
- Camperwolf-specific statistics dimensions
- any copied test suite for features that are removed

Do not simply hide these systems in navigation.

Delete unused code, routes, controllers, models, migrations, services, jobs, commands, views, translations, tests and configuration when it is safe to do so.

The end goal is a genuinely smaller codebase.

---

# 20. Planned core domain objects

The exact schema is not yet finalized.

Current likely core objects:

- users
- places
- place_types
- stable place attributes
- stable attribute votes / confirmations
- variable features
- variable feature votes / confirmations
- check_ins
- structured ratings
- favorites
- reports
- moderation actions / bans

Avoid adding extra entities unless a real product requirement exists.

---

# 21. V1 target scope

Favon V1 should remain intentionally small.

Target:

- authenticated map
- authenticated search
- near-me discovery
- simple place-type filters
- place profile
- minimal place creation
- Telegram login
- pseudonymous internal accounts
- favorites
- stable-data confirmation/correction voting
- proximity-validated check-in
- short-lived current activity signal
- check-in health check
- structured ratings
- place reports
- admin moderation / bans
- privacy/legal pages

Out of V1:

- chat
- DMs
- dating/matching
- public profiles
- follower/friend systems
- free-text reviews
- photos/media uploads
- pornographic content
- XP/gamification
- broad adult-business catalogue
- large external-data imports
- owner verification unless a concrete later need appears

---

# 22. Server and deployment

Favon runs on the **same physical/Plesk server** as Camperwolf but must remain operationally separate.

Expected structure conceptually:

```
/var/www/vhosts/camperwolf.de/...
/var/www/vhosts/favon.de/...
```

Favon needs its own:

- Laravel application path
- document root
- .env
- APP_KEY
- database
- database user
- storage
- logs
- TLS certificate
- queue configuration
- scheduler configuration
- cache namespace
- session cookie name
- session/cache prefixes when shared backing services are used

Important collision prevention if shared Redis/cache/session infrastructure is ever used:

- unique `SESSION_COOKIE`
- unique `CACHE_PREFIX`
- distinct queue names/configuration where useful

Favon is expected to be materially lighter than Camperwolf because there are no photos and no large import pipelines.

---

# 23. Development environment / working method

Current preferred collaboration style:

- assistant may make code changes directly in GitHub
- user pulls/merges locally
- user clears/builds/tests locally
- user reports runtime/test results back
- changes should be incremental and testable

Command formatting preference:

- **PowerShell:** no multiline commands
- **SSH/Linux:** multiline commands are fine when useful

Do not dump large command batches unless necessary.

Prefer a short sequence where each result can be checked before the next destructive/structural action.

---

# 24. Git / repository working rules

Primary repository:

```
WulfieWolf/favon
```

Primary branch:

```
main
```

Use branches/PRs for meaningful development work once the initial cleanup baseline is established.

During the first slimming phase, take care that removed Camperwolf systems do not leave:

- dead routes
- broken service-provider registrations
- orphaned migrations
- missing foreign-key dependencies
- stale Blade references
- stale translation keys
- failing GitHub Actions
- test factories that depend on removed models
- scheduler entries pointing at deleted commands
- queue workers expecting removed jobs

Keep commits logically grouped.

---

# 25. Local setup / current status – 2026-10-06

Verified:

- Independent Favon repo, active branch `cleanup/remove-camperwolf-subsystems`; Camperwolf repo was **not modified**.
- Project folder: `D:\Dropbox\Eigene Dateien\Dokumente\Server\favon`.
- `composer install` succeeded (143 packages); Laravel package discovery succeeded. Composer warned about `composer.lock` vs. `composer.json` consistency.
- `npm.cmd ci` succeeded (132 packages). npm reported 1 high + 3 critical vulnerabilities, not yet fixed or assessed.
- Favon-local `.env` copied from example; new `APP_KEY` generated.
- Windows MySQL 8.0 service `MySQL80` running and client `mysql.exe` 8.0.46 available from PATH.
- Root/MySQL admin access recovered locally; **separate MySQL database `favon`** and **separate user `favon`**, granted only `favon.*`; user login verified in Workbench.
- Favon `.env` configured for MySQL `favon` database / user. No credentials stored in GitHub.
- Initial `migrate:status` correctly reported missing migration table on new blank database.
- After Favon-only migration repairs, **`php artisan migrate:fresh --seed` succeeded on 2026-10-06**. Current database can be built fresh from this branch.

Additional local validation completed on 2026-10-06:

- Laravel Herd linked Favon independently as `http://favon.test` using PHP 8.4. Existing Camperwolf Herd configuration was not modified.
- `npm.cmd run build` completed successfully and generated the Vite manifest/assets.
- Public Favon dashboard renders in the browser; Leaflet map initializes and OSM tiles load.
- Help pages render after the inherited Camperwolf help seeder/content was removed from active Favon behavior.
- Login and registration pages render successfully.
- Runtime cleanup fixed stale Blade/controller references including DevLog, public community profiles, legacy locale namespace usage and removed data-score tools.
- `php artisan test` is now fully green after removing/adapting obsolete Camperwolf tests and repairing Favon-relevant tests. Latest local run: **148 passed, 0 failed**.
- Test repairs intentionally did not restore removed Camperwolf subsystems merely to satisfy old expectations.
- Transitional mail tests explicitly enable the guarded mail circuit breaker inside the tests; production mail remains fail-closed unless configured.
- Statistics date grouping was made compatible with both MySQL and the SQLite in-memory test database.
- Emergency site access command is now `php artisan favon:mode normal`, replacing the obsolete Camperwolf command name.

Still not finalized:

- Final Favon place-type catalogue or demo/place seed data.
- Clean Favon-first schema/migration baseline.
- Favon production deployment on Plesk; local readiness does not mean the site is live.
- Final privacy treatment for OSM tiles, place-level analytics references and retained transitional account/history code.

Database isolation is crucial: never run destructive commands on Camperwolf or production. Favon has independent `.env`, `APP_KEY`, database and DB credentials.

# 26. Cleanup phase: completed work and remaining roadmap

The copied application was systematically inventoried and slimmed; the active code shell and static permissions/statistics/test workflow leftovers were audited. Multiple blocks of incompatible historical migrations were removed or rewritten after local fresh-install errors. The **local migration chain now succeeds**, but final domain cleanup is not finished.

Do not repeat the initial import/photo/devlog subsystem deletion pass: it has already been done. Do not treat the old Camperwolf schema as Favon's future data model.

Proceed with local boot/build/browser/testing first; then replace the inherited schema and build native features in this order: minimal pseudonymous user model and Telegram authentication; Favon place-type catalogue; stable attribute voting; variable feature freshness and check-in health-check; proximity-validated short-lived check-ins; structured rating; moderation and privacy-safe reporting; refactor merges/statistics/account workflows.

Key rule: `docs/PROJECT_CONTEXT.md` must distinguish implemented from planned behavior. Keep this file Favon-only.

# 27. Documentation policy

`docs/PROJECT_CONTEXT.md` is the authoritative living context for Favon.

When significant decisions are made:

- update this file
- remove obsolete concepts rather than endlessly appending contradictions
- preserve unresolved decisions explicitly as TBD
- distinguish implemented behavior from planned behavior
- keep operational notes that will be useful in later sessions

Do not copy unrelated Camperwolf history back into this file.

Other copied documents under `docs/` should later be reviewed individually.

If a document is purely Camperwolf-specific and no longer useful to Favon, remove it.

If it contains reusable technical knowledge, rewrite it as Favon documentation rather than preserving misleading Camperwolf wording.

---

# 28. Open decisions

Still intentionally undecided:

- exact final place-type catalogue
- exact target-audience schema/wording
- check-in radius
- check-in expiry duration
- variable-feature TTLs
- vote thresholds
- when consensus may auto-update vs. require moderation
- exact rating dimensions
- whether aggregate activity should be exact counts or coarse categories
- final operator/legal structure
- final youth-protection implementation
- final privacy-retention schedule

Do not silently treat these as settled.

---

# 29. Core non-negotiables

Unless explicitly reconsidered later:

1. **Places, not people.**
2. Favon content is login-gated; guests only see the access gateway and required public legal/support entry points.
3. Community authentication is Telegram-only; classic password login is reserved for admins/system owner.
4. No public contributor identity.
5. No “who is here”.
6. No public visit histories.
7. No photos.
8. No free-text reviews.
9. No chat/DM/dating/contact ads.
10. Raw GPS is not retained after proximity validation.
11. Variable information ages out.
12. Stable facts and live health-check data are separate.
13. Favon remains technically separate from Camperwolf.
14. Favon development never writes to the Camperwolf repository.
15. Prefer simplicity over inherited Camperwolf complexity.


---

# 30. Repository inventory result – 2026-10-05

A full Favon-only repository inventory was performed against `WulfieWolf/favon`.

The inventory confirmed that the copied repository was still largely Camperwolf product code on top of a useful Laravel/infrastructure foundation.

## KEEP / ADAPT

Retained as useful Favon foundations:

- Laravel application foundation
- Livewire / Flux / Tailwind / Vite stack
- Leaflet/map foundation
- public place browse/search foundation
- basic place profile shell
- favorites
- localization infrastructure
- admin foundation
- roles / permissions
- account suspension and abuse protection
- security headers / public browse hardening
- account deletion concepts
- audit logging
- support/takedown infrastructure
- legal-page infrastructure
- sitemap
- queue/scheduler foundation
- usage statistics / internal analytics

### Statistics decision

The internal statistics system is explicitly **KEEP / ADAPT**, not REMOVE.

The current `usage_events` tracking does not store a concrete user ID. It records event/category information such as:

- event type
- area / route context
- optional content reference
- coarse audience category
- traffic type
- optional metadata
- timestamp

This provides useful operator insight without building per-user usage histories.

For Favon, statistics must still be reviewed before launch because a content reference may identify which sensitive place page was viewed. The desired direction is therefore:

- keep operator insights
- do not add per-user analytics
- avoid persistent individual behavior profiles
- review whether place-level content references should be coarsened or retained
- document retention and privacy treatment before production launch

## REBUILD

Inherited code may provide ideas, but these areas should be redesigned for Favon:

- authentication -> Telegram-based account model
- user/account schema -> minimal pseudonymous internal identity
- place types
- stable place attributes and voting
- variable health-check features and freshness/TTL
- check-ins and proximity validation
- structured ratings
- Favon moderation/content rules
- place merge behavior after the new Favon schema exists
- statistics dimensions for the final Favon UI

## REMOVE

The following inherited product areas were classified as Camperwolf-specific and unnecessary:

- Open Data import center and source pipelines
- Niedersachsen / NRW TFIS / Bayern ATKIS / RVR / Overture / DATEX-II tooling
- research/import scripts
- photo upload / processing / moderation / helpful votes
- review-photo system
- public user profiles
- profile photos
- XP / levels / badges / achievements
- Camperwolf development log
- Camperwolf demo/performance seed/report helpers
- Camperwolf feature catalogue UI/workflows
- Camperwolf place suggestion workflow
- Camperwolf free-text/current review implementation
- camping prices
- camping opening-hours editing
- vehicle/camping-specific feature editing
- related obsolete routes, views, services, jobs, tests and seeders

---

# 31. Cleanup branch and verified implementation status

Repository: `WulfieWolf/favon`  
Cleanup PR #2 was validated and squash-merged into `main` on 2026-10-06.  
Merge commit: `2ff79304a3d3203e5e7bf736cd17a539e8365398`.

Current feature branch:

`feature/telegram-auth-gateway`

All writes remain Favon-only; Camperwolf is untouched.

## Initial slimming commits

- `6ff65a40` – copied Open Data importer removed
- `d5e62553` – DevLog/performance helpers removed
- `816ff330` – public profiles and gamification removed
- `29a756bb` – simplified directory shell; old active review/photo/price/opening/feature workflows removed
- `8148996c` – global Favon transition UI
- `1d68a3a7` – stale photo/contact hooks removed
- `653a6485` – initial context/local validation plan
- `ed03900b` – stale data-score history hook removed

## Follow-up code/static audit

- `c9bbfb51` – import permissions removed
- `13a24aea` – photo/badge permissions and obsolete translation files removed
- `50189436` – admin photo/profile references removed
- `dbef15e2` – old review/feature/suggestion/price permissions, translation and rule leftovers removed
- `ed2b3da3` – old moderation notification and public-profile hooks removed
- `f2666a72` – statistics and page-view tracking tailored to Favon; old Camperwolf review/photo/import metrics removed
- `9b255c2d` – stale tests and GitHub Actions references cleaned
- `aea6f7c1` – obsolete rate limiters and locale-test expectations cleaned

Stats now focus on place, user, support, favorites, moderation/merge/audit and usage events. They do not store a user ID per `usage_events` record, but page-level content references and retention still require privacy review.

## Local migration troubleshooting and fixes – 2026-10-06

Fresh `favon` MySQL DB originally exposed removed-seeder dependencies and orphaned schema references. Repairs were committed only to Favon:

- `91b5754f` – removed 5 obsolete DevRelease migrations referencing deleted `DevReleaseSeeder`
- `8f773e75` – removed 7 obsolete Camperwolf rest-area/place-type/feature/help migrations
- `069cd6bc` – removed 9 dependent feature filter/data-score/legacy beta-devlog migrations after missing `feature_place_types`
- `066b9401` – removed 7 external import follow-up migrations; reduced former import schema migration to independent `place_history`; adapted service/presenter to remove external-source FK/join
- `ae7d61bc` – removed 4 DATEX/vehicle/camping-specific migrations
- `fd192ebd` – kept the generic drop of `place_details.operating_mode` while removing obsolete DATEX and suggestion-field data updates

**Result:** `php artisan migrate:fresh --seed` completed successfully on the isolated local Favon DB.

## Current size / transitional debt

The active app is a minimal **transitional** place directory/map with browse, place profiles, favorites, admin, notifications/support, legal/help and analytics/security foundations.

The cleanup branch has now passed the local runtime/build/test validation milestone. The exact repository/test file counts are no longer treated as durable context because cleanup continues to remove obsolete files.

Remaining issues:

- The **68 successful migrations still create many obsolete Camperwolf tables** (photos, reviews, prices, features, XP, etc.). They need a deliberate clean Favon schema replacement; migration success alone is not final cleanup.
- `PlaceMergeService`, `PlaceDeletionService`, account deletion/export still assume older tables.
- Fortify/email/password/passkey remain transitional for administrative access only; normal community authentication is now Telegram-only on the feature branch. `UserProfile`/`PublicHandleService` still require later schema cleanup.
- Place-history presenter still contains inherited public handle/author logic: must honor **no public contributor identity** before public history is exposed.
- `TouchLastSeen`, notifications, mail/support `camperwolf.*` configs, some permissions and branding/docs need review.
- Map currently uses external OSM tiles; privacy-safe final approach TBD.
- Pulse/Telescope and other dependencies need final need/security audit.
- Actual Favon place types/seeder, voting, check-ins, structured ratings and reporting are **not yet implemented**.
- Browser smoke checks completed successfully for the public dashboard/map, help and authentication entry pages.
- Frontend build is successful.
- Laravel/PHPUnit suite is fully green locally: **148 passed, 0 failed** at the end of this cleanup validation pass.
- Remaining transitional debt is architectural/domain debt, not a currently known failing-test baseline.

Avoid restoring removed Camperwolf subsystems merely to make tests pass. Address surviving dependencies and tests deliberately.

# 32. Exact continuation point – 2026-10-06

**Current state:** Cleanup PR #2 is merged into `main`. Active development continues on `feature/telegram-auth-gateway`. The Telegram auth migration was applied locally with normal `php artisan migrate`, and after the final 2FA test adaptation the complete local Laravel/PHPUnit suite is green again. Herd continues to serve Favon independently at `favon.test`.

Important cleanup/runtime fixes made during this validation pass include:

- fixed Favon dashboard map JSON rendering
- fixed locale configuration namespace in the app header
- removed inherited XP/achievement help content and converted the old help seeder into legacy-help cleanup only
- removed stale DevLog links from auth/error layouts
- removed stale public community-profile links
- removed obsolete data-score admin tooling
- removed obsolete Camperwolf-only tests for review/photo/import/deletion behaviors
- localized the transitional Favon browse/admin UI
- added `favon:mode` emergency access command
- made statistics grouping SQLite-compatible for tests
- aligned registration welcome flow with Favon's non-public-profile direction
- kept guarded mail behavior while explicitly enabling it inside delivery tests only

## Current Telegram-auth implementation

- new branch `feature/telegram-auth-gateway`
- Telegram bot/domain configured externally through BotFather; local secret is stored only in Favon `.env` as `TELEGRAM_BOT_TOKEN`
- `config/telegram.php` defines bot username, token and authentication payload max age
- `community_accounts` maps one internal Favon user to one unique Telegram ID
- normal Telegram users have nullable `email` and `password`
- automatically generated internal names use a readable sequential format: `User-0000001`, `User-0000002`, ...
- the sequence is based on the community-account row, independent of admin user IDs
- Telegram first/last name, username, profile photo and phone number are not persisted
- signed payload verification rejects invalid or stale login data
- public Fortify registration is disabled
- classic e-mail/password login is allowed only for administrator/system-owner accounts
- `registration_closed` mode blocks creation of new Telegram accounts while allowing the gateway itself to remain available
- Favon directory/map/help/place routes are authentication-gated
- legal pages and selected public support/legal entry points remain public
- robots policy now disallows authenticated directory content
- full local test suite is green after the auth changes

## Next concrete steps

1. Browser-smoke-test the new gateway locally: guest landing page, Admin-Login, redirects from dashboard/place/help, public legal pages.
2. Real Telegram widget round-trip must be tested on the configured `https://favon.de` domain; `favon.test` is not registered with BotFather.
3. Review account deletion/ban semantics for Telegram IDs: ordinary voluntary deletion versus abuse-ban retention.
4. Review whether admin password reset, passkeys, e-mail verification and 2FA should all remain for administrators or be reduced further.
5. Review `composer.lock` consistency and the reported npm vulnerabilities (1 high, 3 critical); do **not** blindly run `npm audit fix --force`.
6. Design the clean Favon-first schema/migration baseline. The inherited migration set is still only a transitional bootable baseline.
7. Define and seed the Favon place-type catalogue.
8. Implement Favon-native stable attribute voting and variable feature freshness/health checks.
9. Implement proximity-validated short-lived check-ins without retaining raw GPS.
10. Implement structured ratings and privacy-safe moderation/reporting.
11. Refactor merge, statistics, account deletion/export and place history against the new Favon schema; ensure no public contributor identity is exposed.
12. Review final privacy treatment for OSM tiles, analytics content references, retention and remaining `camperwolf.*` configuration names before production deployment.

## Safety / working style

- **PowerShell commands must always be one line each.** Multiline SSH/Linux commands are acceptable.
- Assistant commits to **Favon GitHub only**; user pulls/tests locally. Camperwolf is read-only.
- `migrate:fresh` is destructive: use **only** when the active project and `.env` are positively verified to target the isolated local `favon` DB. Never use it against Camperwolf or production.
- Update this living context at important milestones; do not copy back obsolete Camperwolf concepts.
