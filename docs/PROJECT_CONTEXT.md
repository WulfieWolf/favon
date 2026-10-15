# Favon – Project Context

_Last updated: 2026-10-15 – Favon-native place workflow live/merged through PR #16_

This file is the authoritative working context for **Favon**.

It must contain only information relevant to Favon itself, Favon's implementation, Favon's deployment, or one-time technical inheritance that is still useful for Favon development.

Do not reintroduce Camperwolf product planning, data-source status, marketing plans, content rules, backlog or unrelated Camperwolf history.

---

# 1. Project identity

**Project:** Favon  
**Domain:** `favon.de`  
**Repository:** `WulfieWolf/favon`  
**Framework:** Laravel  
**Hosting:** Plesk, separate application on the same physical server as Camperwolf

Favon is a login-gated community directory/map for cruising and hookup-relevant places.

Core product principle:

> **Places, not people.**

Favon is intentionally not a dating platform, social network, contact marketplace, escort/prostitution directory, porn site or user-discovery service.

The product describes places while exposing as little information as possible about the people using or maintaining it.

---

# 2. Permanent project separation

Favon and Camperwolf are independent projects.

All Favon writes go only to:

`WulfieWolf/favon`

Camperwolf is read-only reference material at most.

Never:

- commit or push Favon work to Camperwolf;
- modify Camperwolf configuration while working on Favon;
- share Favon database, `.env`, cache/session/storage or application state with Camperwolf;
- run Favon migration/deployment commands in the Camperwolf project;
- assume copied Camperwolf functionality belongs in Favon.

Before destructive operations, positively verify the active project/database.

---

# 3. Working style / development rules

- Prefer the smallest useful Favon-specific implementation over inherited Camperwolf complexity.
- Start deliberately small and add features from real user feedback.
- Mobile-first, map-centric UI.
- PowerShell commands must be one line each.
- SSH/Linux commands may be multiline.
- Normal workflow: feature branch -> local tests/practical check -> PR -> squash merge to `main` -> production pull/deploy.
- Never use `migrate:fresh` on production.
- Production migrations must be normal forward migrations and explicitly reviewed for safety.
- Do not restore deleted Camperwolf systems merely to satisfy stale tests.

---

# 4. Product non-negotiables

Unless explicitly reconsidered:

1. Places, not people.
2. Content is login-gated; unauthenticated users see only the gateway and required public legal/support entry points.
3. Community authentication is Telegram-only.
4. Classic e-mail/password login is reserved for the owner/admin.
5. No public contributor identities.
6. No “who is here”.
7. No public visit histories.
8. No user photos/place photos.
9. No free-text reviews.
10. No chat, DMs, dating, matching or contact ads.
11. No identifiable-person descriptions.
12. No movement profiles.
13. Raw user GPS must not be retained beyond the immediate feature need.
14. Stable place facts and live/temporary conditions are separate concepts.
15. Favon remains technically and operationally separate from Camperwolf.

---

# 5. Authentication / account model

## Implemented

Production uses Telegram OIDC for normal community users.

Flow:

- unauthenticated visitors land on the Favon access gateway;
- gateway offers Telegram login plus a separate Admin Login;
- Telegram uses authorization-code flow with PKCE;
- Favon validates state, nonce, issuer, audience, expiry, RS256 signature and Telegram JWKS;
- authorization code exchange happens server-side;
- only the stable Telegram numeric subject ID needed for account mapping is persisted from Telegram identity data;
- Telegram display name, username, phone number and profile photo are not stored;
- unknown Telegram ID creates a minimal Favon account;
- known Telegram ID reuses the existing account;
- public registration is disabled;
- normal community users do not use password login.

Minimal community identity:

- internal Favon user ID;
- Telegram numeric ID in `community_accounts`;
- generated internal name such as `User-0000001`;
- role/permissions;
- abuse/ban state;
- timestamps/locale/consent state as technically required.

The system owner/admin remains a separate classic account and may log in through the admin route using `FAVON_OWNER_EMAIL`.

Favon is therefore pseudonymous, not mathematically anonymous.

---

# 6. Privacy direction

Favon handles potentially sensitive location/use context and should be privacy-by-design.

Current rules:

- no public user attribution on place creation/editing/voting/favorites/check-ins;
- no public visit history;
- no “who is here” feature;
- no follower/friend/user-discovery model;
- no unnecessary Telegram profile data;
- no third-party behavioral tracking by default;
- no long-term raw location history.

Important implementation caveat discovered in the current nearby/create-here flow:

- `near_lat` / `near_lng` are currently sent in the URL for “In meiner Umgebung”;
- create-from-current-position currently passes `lat` / `lng` in the create URL;
- URLs may appear in browser history, referrers or web-server access logs.

Before broader/public rollout, refactor this so transient user GPS does not need to travel in URLs. Preferred direction: client-side nearby sorting/focus and session/local transient handoff for create-here, while the actual submitted place coordinates remain normal place data.

Do not claim operationally that raw GPS is never retained until this is fixed.

Before public launch, reassess DPIA/GDPR requirements and retention.

---

# 7. Current place catalogue

Initial Favon place types:

1. Gay-Sauna
2. Darkroom / Sexclub
3. Sexkino / Videokabine
4. Fetisch- / Lederbar
5. Park / Wald
6. Rastplatz
7. Öffentliche Toilette / Klappe
8. FKK-Strand / Badesee
9. Parkhaus / Einkaufszentrum

Slugs:

- `gay-sauna`
- `darkroom-sexclub`
- `sex-cinema-video-booth`
- `fetish-leather-bar`
- `park-forest`
- `rest-area`
- `public-toilet`
- `nudist-beach-lake`
- `parking-garage-shopping-center`

V1 exposes exactly one primary type in the UI. The `place_place_types` relation exists so multiple type relations can be added later if product feedback justifies it.

Gloryhole is not a place type; it is a separate structured property.

---

# 8. Place submission – implemented V1

Community users can submit new places. Submissions are moderated before becoming public.

Required:

- place type;
- name;
- precise map position;
- at least one target audience selection: MM and/or MF.

Optional structured/basic information:

- description;
- environment: indoor / outdoor / both;
- gloryhole: yes / no;
- postal address.

The map coordinate is authoritative; address is optional.

Submission form rules explicitly prohibit:

- private homes/private property;
- personal data;
- contact details/contact ads;
- descriptions of identifiable people;
- places that are not legally accessible.

A submission is stored as `publication_status = pending` and is excluded from normal browse/profile results until approved.

There is no normal product quota for legitimate submissions; technical rate limiting exists for abuse prevention.

---

# 9. Target audience model – current

The initial single-choice `target_audience = mm|mf` implementation was replaced on 2026-10-15 because a place can serve both patterns.

Current model uses independent flags:

- `target_mm`
- `target_mf`

UI behavior:

- MM only;
- MF only;
- MM + MF;
- at least one must be selected;
- selection uses checkbox-style tiles in the same visual family as the other compact place properties;
- the form explicitly notes that multiple selection is possible.

Published profiles display e.g.:

- `Zielgruppe: MM`
- `Zielgruppe: MF`
- `Zielgruppe: MM + MF`

The old `target_audience` column is intentionally retained temporarily for migration compatibility but new submissions write the two new flags. The migration backfills existing single-choice data.

**Still open:** MM/MF must be added as a browse/search filter. This is explicitly planned and not yet implemented.

---

# 10. Submission moderation – implemented

Owner/admin moderation queue supports:

- reviewing pending place data;
- correcting obvious mistakes;
- changing place type;
- changing description/location/address/basic properties;
- independently selecting MM and/or MF;
- approving;
- rejecting with mandatory reason.

Approval:

- applies corrections;
- marks the place `published`;
- records approver/time;
- notifies the creator in-app with the place name.

Rejection:

- requires a reason;
- notifies the creator with place name + reason;
- then permanently deletes the pending place;
- there is no rejected-place archive or resubmit workflow in V1.

Published-place community editing is not yet implemented. Later it should use a separate change-suggestion workflow rather than direct editing.

---

# 11. Browse / home UI – current

Favon is intentionally compact and map-first.

Current header/home behavior:

- Favon branding in header;
- language selector;
- notification bell with unread count, capped visually as `99+`;
- compact round user icon;
- normal-user menu effectively contains logout only; owner/admin-specific actions remain permission-based;
- no redundant large Favon title/intro on dashboard;
- search stays visible;
- filters are inside a collapsible filter area;
- map is the main visual focus;
- add-place `+` menu remains available;
- “In meiner Umgebung” button is at lower-right of the map and does not overlap Leaflet controls/attribution.

Current filters include:

- text search;
- favorites;
- place type.

**Not yet implemented:** MM/MF target-audience filter.

## Default map focus

As of PR #15, the normal dashboard map no longer auto-fits to the currently loaded markers.

Without nearby mode it stays focused on Germany, so a single existing place does not visually imply that the entire service exists only for that location.

Nearby mode still focuses on the user's current position.

---

# 12. Location / nearby / create-here – implemented

“In meiner Umgebung”:

- browser requests geolocation;
- current implementation sends coordinates as `near_lat` / `near_lng`;
- server validates coordinate ranges;
- MySQL `ST_Distance_Sphere` calculates distance;
- results are ordered by distance;
- dashboard focuses user position and marks it as “Meine Position”.

“Platz an meiner Position eintragen”:

- browser obtains current position;
- create form receives coordinates;
- marker is prefilled;
- reverse geocoding attempts to fill address fields.

Browser geolocation works correctly on production HTTPS and passed practical testing.

The privacy caveat regarding URL coordinates is documented in section 6 and should be addressed before wider/public rollout.

---

# 13. Address / map entry – implemented

Place creation includes:

- address/place search above the map;
- Photon / OpenStreetMap-backed autocomplete;
- minimum query length and debounce;
- localized search bias/context;
- suggestion selection moves the authoritative marker;
- clicking the map or dragging the marker updates coordinates;
- reverse geocoding fills address components where possible;
- manual edits of address fields do not silently move the marker.

The exact map position remains the authoritative location.

---

# 14. Place profiles – current

Published place profiles currently provide a deliberately small V1 shell:

- place name;
- localized place type;
- target audience MM/MF/MM+MF when known;
- address when available;
- map;
- favorite toggle;
- Google Maps navigation button;
- Apple Maps navigation button.

More place attributes should only be added when the product model is settled.

---

# 15. Favorites and notifications

Favorites are retained as private user state.

Never publicly expose who favorited a place or a user's favorite list.

In-app notifications are active for submission approval/rejection.

Notification bell is visible in the header and shows unread count.

No e-mail notifications are required for the current V1 submission workflow.

---

# 16. Ratings, check-ins and live conditions – planned, not implemented

These remain product direction, not current functionality.

## Check-ins

Goal:

> Is something currently happening here?

Never:

> Who is here?

Expected properties:

- proximity validation;
- short-lived check-ins;
- no public visitor identity;
- no long-term movement history;
- public aggregate activity only.

Exact radius and expiry remain TBD.

## Live/variable conditions

Potential short-lived fields:

- accessible/open now;
- toilet currently available;
- washing facility currently available;
- waste disposal currently available;
- lighting operational.

Current-state confirmations should decay/expire rather than remain permanently authoritative.

## Stable facts

Stable place properties should be maintained separately from check-ins and should eventually use a consensus/change-suggestion model rather than allowing one user to rewrite important data instantly.

## Ratings

Structured only. No free-text reviews.

Potential dimensions remain undecided; likely short mobile 1–5 ratings such as cleanliness, discretion, lighting, public traffic, accessibility/safety.

---

# 17. Photos and free text

Current product decision:

- no place photos;
- no review photos;
- no user-uploaded media;
- no free-text reviews;
- no explicit sexual experience reports.

This is intentional, not merely deferred.

Short factual place descriptions are allowed because they are necessary to identify/find a location; they must not become personal ads or explicit encounter reports.

---

# 18. Moderation/content scope

Favon controls its catalogue.

Reject/avoid:

- private residences;
- trespass-dependent locations;
- personal ads;
- doxxing;
- identifiable visitor descriptions;
- pornographic user content;
- explicit instructions for sexual activity;
- schools/playgrounds and clearly inappropriate family/child-sensitive locations;
- entries outside Favon's place-focused scope.

A robust report/takedown path should remain available for affected parties, operators and legal complaints.

---

# 19. Legal / private beta status

Favon is currently in a small test/beta phase.

The user does not want private operator/imprint information unnecessarily exposed during this limited testing phase.

Current UI intentionally hides persistent legal-footer links in the ordinary authenticated experience/gateway, while legal routes remain available where required.

Before an actual public launch:

- restore/verify appropriately visible legal links;
- review Impressum, Datenschutz and Nutzungsbedingungen;
- review adult/youth-protection requirements;
- review privacy retention and location handling;
- assess whether a DPIA is needed.

Possible future service for a ladungsfähige Impressumsadresse:

`flexdienst.de`

This is only noted, not selected/booked.

---

# 20. Technical cleanup / inherited debt

Favon began as a one-time copy of the Camperwolf Laravel application. The active product surface has been aggressively reduced.

Removed/retired from active Favon behavior include major Camperwolf-specific systems such as:

- Open Data import pipelines;
- photo upload/processing/moderation;
- public profiles;
- XP/levels/badges/achievements;
- Camperwolf devlog/demo helpers;
- camping price/opening-hours/vehicle-feature workflows;
- old camping review/photo flows;
- Camperwolf branding/config namespaces;
- inherited import permissions/metrics/routes/tests that no longer apply.

Do not reintroduce them unless Favon independently needs an equivalent capability.

Some old schema/migration/account/statistics infrastructure still exists as transitional technical debt. Clean it only deliberately; do not rewrite already-applied production migrations casually.

---

# 21. Current repository / PR history

Important merged milestones:

- PR #2 cleanup -> `2ff79304a3d3203e5e7bf736cd17a539e8365398`
- PR #3 Telegram auth gateway -> `82ca340ed7d2a8568068cc54a2d8509b984a0d33`
- gateway footer cleanup -> `87ec7343354a508864fe20e17b92b6fd04677760`
- PR #4 Telegram OIDC -> `645cc86d89ec8867b2f6df9dc518f36989321a2c`
- PR #5 config cleanup -> `25fb29b133e7711faa8528943847e380cfdcb63e`
- PR #6 branding/stale cleanup -> `75608a6016afe010ae5a12ae2005c9527df9f461`
- PR #8 remaining active Camperwolf leftovers -> `de1db624ff0d44ef7f677eda1bc59d03d55d7368`
- PR #9 place submission foundation -> `d6dc2bd589d3a5ec963f6b10adc4d394f2e71d94`
- PR #10 place submission UI + moderation -> `9980b19ce68de7776c045f3cac7aa418898cb412`
- PR #11 browser/location feedback fixes -> `af91f89dfbea731d1ee1aa247f3dcd5e4b82b5de`
- PR #12 compact map-first browse UI -> `fb49bf22d7c235839300e962abf27517fefda554`
- PR #13 missing filter icon fix -> `45a2fcc79eefa9960fc8786d67c3ae190a58ffed`
- PR #14 initial MM/MF target-audience field -> `4d41811e61d8640ec25453f00787ea815688e288`
- PR #15 keep default map focused on Germany -> `64d2ed75986531a32b5dcea4a5c1df252868746a`
- PR #16 allow independent MM + MF selection -> `565265736b27e397802dadbb4f9c28de0ba23735`

**Current latest known `main`:**

`565265736b27e397802dadbb4f9c28de0ba23735`

PR #16 was locally tested successfully and practical target-audience testing passed before merge.

Latest full local test report immediately before PR #16 merge:

**158 passed, 0 failed** after the final test fixes.

---

# 22. Current production/deployment setup

Server:

- Plesk vhost: `/var/www/vhosts/favon.de`
- application checkout: `/var/www/vhosts/favon.de/laravel/current`
- document root: `current/public`
- PHP: Plesk PHP 8.4
- Plesk user: `favon.de_nbbcdda00f9`
- production DB: `favon_app`
- SSL active
- DNS A: `85.215.77.212`

Git deployment uses a Favon-only read-only ED25519 deploy key:

`/var/www/vhosts/favon.de/.ssh/id_ed25519_favon`

Do not use this key for Camperwolf.

Standard production Git update:

```bash
cd /var/www/vhosts/favon.de/laravel/current
sudo -u favon.de_nbbcdda00f9 git fetch origin
sudo -u favon.de_nbbcdda00f9 git switch main
sudo -u favon.de_nbbcdda00f9 git pull --ff-only
```

When a normal forward migration is part of the release:

```bash
sudo -u favon.de_nbbcdda00f9 /opt/plesk/php/8.4/bin/php artisan migrate --force
```

Cache/worker refresh:

```bash
sudo -u favon.de_nbbcdda00f9 /opt/plesk/php/8.4/bin/php artisan optimize:clear
sudo -u favon.de_nbbcdda00f9 /opt/plesk/php/8.4/bin/php artisan optimize
sudo -u favon.de_nbbcdda00f9 /opt/plesk/php/8.4/bin/php artisan queue:restart
```

For frontend/Tailwind/JS changes, also run Node 22 `npm ci` and `npm run build`.

PR #14 introduced the first target-audience migration.  
PR #16 introduced a second non-destructive migration adding `target_mm` / `target_mf` and backfilling the old single-choice values.

Do not run `migrate:fresh` in production.

---

# 23. Current local setup

Typical local project path:

`D:\Dropbox\Eigene Dateien\Dokumente\Server\favon`

Favon has its own local MySQL database/user and its own `.env`.

Laravel Herd exposes:

`http://favon.test`

Local environment is independent from Camperwolf.

Telegram OIDC is primarily tested on production HTTPS; the owner/admin account remains useful for local development.

---

# 24. Current UI decisions from 2026-10-15 testing

Real practical browser/mobile testing drove these changes:

- remove redundant large dashboard title/intro;
- make map the visual center;
- keep search immediately available;
- collapse advanced filters;
- move nearby button away from Leaflet zoom/attribution controls;
- make submission-success modal mobile-safe;
- use a compact circular user icon;
- remove normal-user menu clutter;
- remove duplicate language option from user menu;
- keep owner/admin entries permission-based;
- add missing local Tabler icon definitions (`user-circle`, `adjustments-horizontal`);
- default map view stays on Germany instead of auto-zooming to a single result.

User confirmed the resulting UI works and looks good.

---

# 25. Immediate next work

High-priority continuation items:

1. Add **MM / MF** to the collapsible browse filters. Multiple selection should make sense there as well.
2. Let first test users/colleagues use the product with as little explanation as possible; collect where they hesitate or misunderstand the interface.
3. Fix the transient GPS-in-URL privacy issue before broader/public rollout.
4. Only then expand the place model/features based on actual feedback.

When observing testers, useful questions are behavioral rather than leading:

- Can they find nearby places without help?
- Do they understand the `+` menu?
- Can they submit a place without explanation?
- Do they understand MM/MF and multi-selection?
- Can they find favorites/notifications?
- Where do they backtrack, hesitate or ask what to do next?

Do not add speculative complexity merely because it was planned earlier.

---

# 26. Open decisions

Still intentionally unresolved:

- check-in radius;
- check-in expiry;
- variable-feature TTLs;
- vote/consensus thresholds;
- stable-fact change workflow;
- exact structured rating dimensions;
- exact public aggregate activity presentation;
- final operator/legal structure;
- final youth-protection implementation;
- final privacy retention schedule;
- whether the old `target_audience` compatibility column should be removed in a later cleanup migration;
- whether multiple place types will ever be exposed in the UI.

---

# 27. Documentation policy

`docs/PROJECT_CONTEXT.md` is the authoritative living context.

At meaningful milestones:

- update it;
- replace obsolete statements rather than appending contradictions;
- clearly distinguish implemented from planned;
- keep operational details needed for future sessions;
- remove stale branch names/test counts/production assumptions when they stop being useful;
- keep it Favon-only.

The next session should read this file first before planning or changing Favon.
