# Camperwolf.de – Project Overview

_Last updated: 2026-09-20_

This document records the agreed product behavior, architecture, conventions, implemented state and next work for Camperwolf.de. The Git repository is authoritative for what is actually implemented; this document is authoritative for product intent unless a later decision explicitly changes it.

## 1. Product vision

Camperwolf.de is a quality-focused, map-centric directory and community platform for camping, motorhome, campervan, parking and service locations. Germany is the initial market, but architecture, data structures and localization should remain international-ready.

The existing WordPress blog may continue separately, for example under `blog.camperwolf.de`. The main Camperwolf application is Laravel/Livewire. Website/PWA comes first; native apps may follow later.

Core principle: **quality instead of maximum quantity**. The system should collect information that is useful, reasonably objective and maintainable rather than every conceivable detail.

## 2. Product principles

- End-user use should remain free.
- Money must never influence score, ranking or visibility.
- Guests should eventually be able to search and browse without an account.
- Contributions require an account.
- Place existence and legal/use status are separate concepts.
- Prohibited, unclear or owner-unwanted places remain as marked records/tombstones rather than being silently erased.
- Public data changes should be historically traceable.
- Reference data is deactivated rather than hard-deleted.
- Missing structured information means **unknown**, not automatically `false`.
- Coordinates are the primary location truth; postal address data is optional.
- Owner/operator and administrator edits remain transparent through audit/history.
- Keep the model lean and add specialist fields only when real use shows they are needed.

## 3. Technical conventions

- DB/code identifiers: `snake_case`
- stable technical slugs: English, lowercase kebab-case
- reference sorting in gaps such as 10/20/30
- `is_active` instead of destructive deletion for reference definitions
- localized display text separate from stable identifiers
- English fallback localization
- historical public place data should generally use versioning rather than destructive overwrite
- user input never determines arbitrary table/column names
- centralized icons and reusable reference data where appropriate

Repository: `WulfieWolf/camperwolf`, private, branch `main`.

Local project path currently used:

```text
D:\Dropbox\Eigene Dateien\Dokumente\Server\Camperwolf
```

Local stack:

- Laravel 13
- Livewire 4
- Flux UI / Tailwind starter frontend
- PHP 8.4
- Composer 2.x
- Node 24 / npm 11
- MySQL 8.0
- Laravel Herd
- `http://camperwolf.test`

PowerShell may block `npm.ps1`; use `npm.cmd` when needed. Dropbox synchronization has previously caused Git file locks and is normally disabled while developing.

## 4. Current browse and map UI

The desktop browse UI is map-centric:

- left feature/filter column
- central place list
- right Leaflet/OpenStreetMap map
- resizable list/map split
- classic map pins
- hover interaction between list and map
- faceted feature counts shown as remaining/total
- multiple selected feature filters use AND semantics
- zero-result features are hidden
- map and list use the filtered result set

The map viewport filtering implementation exists in code, but should not be considered finished until it is re-verified in the UI.

The current route setup still keeps browse/profile behind authentication; public guest browse remains an intended later change.

## 5. Place profile

A dedicated place profile exists and currently assembles:

- basic place data
- current address
- localized description/details
- operator/basic details
- contacts
- vehicle types
- features grouped by category

Current limitation: the profile primarily shows feature names. Dynamic values such as prices, amperage, distances, duration and feature comments are not yet presented properly.

### Planned profile feature presentation

The profile should list **all active configured features**, including currently unknown ones, so users can see what information is possible and directly contribute missing data.

For binary/status-like features:

- green = present / allowed / yes
- red = absent / not allowed / no
- grey = unknown

Color must not be the only signal; text/icon meaning must remain accessible.

Dynamic values should be shown inline when available, for example:

- `Stromanschluss – vorhanden · 16 A · CEE blau · 4,00 € / Nacht`
- `Dusche – vorhanden · warm · 1,00 € / 10 Min.`
- `Personengebühr – 5,00 € / Person / Nacht`
- `Max. Aufenthaltsdauer – 2 Tage`

If a feature has an optional free-text comment, show a small `i` icon. Hover should show the comment as a tooltip; mobile must provide an equivalent tap interaction.

Every listed feature should expose the appropriate edit action:

- normal user: **Änderung vorschlagen**
- admin/system owner: **Bearbeiten**

Unknown features should be directly fillable through the same mechanism.

## 6. Place creation and draft workflow

The current place creation flow works as follows:

### Step 1 – Grunddaten & Position

- name
- place type
- exact map coordinates
- optional country/address fields
- Photon/OpenStreetMap address lookup and reverse lookup
- optional moderation/source note

The user can either submit immediately or save a draft.

### Step 2 – Beschreibung & Platzdetails

Draft-only direct editing currently includes:

- localized description
- directions
- access information
- operator name
- pitch count
- operating mode

### Step 3 – Ausstattung & Merkmale

Implemented as a configurable, database-driven feature workflow.

A feature can define:

- status options
- conditional detail fields
- number values
- fixed units
- select options
- price/rate input
- dependent questions
- optional per-feature free-text comment

The default semantic is **unknown**. Omission must not mean absent.

### Step 4 – Prüfen & einreichen

A compact review step summarizes the draft before submission to moderation.

Once a user submits a draft, publication status becomes `pending` and the draft is locked for direct creator editing while moderation is pending.

### Current UX limitation

The Step-3 feature form functions correctly but is visually too wide and not compact enough. It should be redesigned into a more ergonomic, grouped layout with narrower controls and clearer visual hierarchy.

## 7. Hybrid feature/tag model

The existing `features` system is the canonical feature/tag system and should not be replaced casually.

The model supports:

- boolean/status-like features
- numbers
- text
- fixed options
- units
- price/rate concepts
- optional localized notes
- historical `place_features`

`place_features` supports typed values and version history. The current feature workflow adds configurable form behavior through feature workflow metadata rather than hard-coding every feature form in Blade/PHP.

Explicit negative states are important. A feature marked `unavailable`/`no` must not be treated by positive browse filters as though the feature were present.

## 8. Initial structured feature workflow

The first practical feature set was intentionally reduced to information that is useful and reasonably objective.

### Zugang & Fahrzeug

- motorhome access suitability
- entrance height in meters
- entrance width in meters
- maximum vehicle length in meters
- maximum vehicle weight in tonnes

Detailed pitch dimensions/surface/vehicle-size-per-pitch fields were deliberately deferred because many sites contain very different pitches and such values would often be misleading.

### Strom & Energie

**Stromanschluss**

- unknown / present / absent
- amperage in A
- connector: CEE blue / Schuko / CEE red / other
- cost state and structured rate when relevant

**E-Fahrzeug-Ladepunkt**

- unknown / present / absent
- connector/power/cost details where configured

### Wasser & Entsorgung

- fresh water
- grey-water disposal
- toilet/cassette disposal
- floor drain/disposal channel

Relevant services can carry free/paid/unknown cost information.

### Sanitär

- toilet
- shower
- washbasin
- accessible toilet
- accessible shower

Conditional details include warm water, access constraints and prices where relevant.

### Internet

Only WLAN is in the initial reduced set.

### Lage / Entfernungen

Initial objective distances:

- town centre
- shopping
- public transport

Nature/leisure proximity such as beach, lake, forest and mountains is deferred for later refinement.

### Kosten & Gebühren

General cost features are part of the same feature system to avoid maintaining duplicate concepts in a separate user-facing list.

Initial set:

- overnight fee
- person fee
- tourist tax
- dog cost is attached to the dog feature rather than duplicated

Pricing should support amount + currency + quantity + basis, for example:

- `0.80 EUR / 1 kWh`
- `1.00 EUR / 10 min`
- `4.00 EUR / night`
- `2.00 EUR / use`

### Regeln / Nutzung

- dogs allowed
- reservation required
- maximum stay duration
- caravans allowed
- tents allowed
- motorhomes only

Seasonal operation remains a normal place-detail field and should not be duplicated as a tag.

## 9. Categories

The historical feature catalogue contains more categories/features than the current reduced Step-3 workflow. Do not assume every old catalogue feature should automatically appear in the new form.

Current useful top-level concepts include:

- access / vehicle
- utilities / energy
- water / disposal
- sanitary
- internet/facilities
- surroundings/distances
- costs & fees
- rules & use
- accessibility where applicable

The initial workflow should stay lean. Additional structured features should be added when there is a real filtering or information need.

## 10. Units and pricing

Units are centrally modeled. Existing tables include `units`, `unit_type_units` and `feature_allowed_units`.

Examples include:

- `m`, `km`
- `min`, `h`, `day`, `night`
- `A`
- `kW`, `kWh`
- `kg`, `t`
- `l`
- `EUR`

Do not encode compound rate strings such as `€/kWh` as a single unit. Store amount/currency plus quantity and basis separately.

The project also has a broader `place_prices` model. For the interactive feature workflow, prices attached to specific features should remain attached to that feature concept rather than creating a second duplicate UI list. The broader pricing tables can still serve other pricing use cases later.

## 11. Change requests and moderation

Public editable fields are centrally controlled through `suggestable_fields`.

`change_requests` supports:

- create/update/deactivate operations
- original and proposed JSON values
- grouping through `group_uuid`
- submit/review metadata
- result record IDs
- applied timestamps

`ChangeRequestApplyService` is implemented and has been tested for real field changes and versioned records.

It supports applying changes to:

- core `places` fields
- addresses
- translations
- contacts
- details
- vehicle types
- features

Opening-hours and broader price apply logic are not yet complete.

Moderation UI exists for approving/rejecting change requests.

## 12. Roles and permissions

Roles include guest/user/mod/admin, with the system owner handled separately from ordinary admin role membership.

The system owner can simulate another active role through a session-only role preview. Simulation affects both visible controls and route authorization without changing DB role assignments.

Important direction for the next update:

- normal users continue to create suggestions/change requests
- admins and the system owner should be able to create places **directly**, without a moderation round-trip
- admins and the system owner should be able to edit existing places/features **directly** from the same UI
- direct privileged edits must still write full audit history

The UI should reuse the same place/profile forms where possible rather than maintaining separate admin-only editing screens.

## 13. Audit and provenance

`audit_logs` records actual application changes with user, entity, action, source, old/new values and timestamp.

Provenance is separate and modeled through `place_data_sources` / `place_data_source_links`.

Rejected suggestions remain change-request history and must not be represented as applied domain changes.

Sensitive audit-value redaction is not yet fully implemented.

## 14. Address and localization

Coordinates are mandatory for place creation; address is optional.

Address concepts include:

- country code
- region
- postal code
- city
- street
- house number
- address addition/note

Country selection uses stable ISO codes with localized display names.

German and English UI switching is implemented and stored in session. Technical identifiers remain stable regardless of language.

## 15. Devlog/version system

A public bilingual Devlog is implemented with database-backed releases and translations. The visible version is derived from the latest public release rather than hard-coded in `.env`.

Current seeded milestone: **Pre-Alpha 0.11.001 – Structured feature workflow**.

The current Devlog already records:

- the configurable third feature step
- conditional feature details
- the fourth review step
- optional per-feature comments
- the small upper-right version indicator introduced in that milestone

### Pending UI change

The visible **version link should be removed from the normal application UI** in the next update. The Devlog itself should remain available; only the persistent visible version link is to disappear.

## 16. Reviews and quality score

Do not replace the intended review model with a generic overall star rating.

Core questions focus on a small number of quality dimensions such as:

- cleanliness
- functionality
- feeling of safety
- whether the information matches reality

Price and feature quantity/luxury must not affect the quality score.

Repeat reviews are allowed; only the latest valid review by a user/place should count toward the current score while older reviews remain historical.

Verified visit/check-in and anti-fake weighting remain later work.

## 17. Owner verification and community moderation

Owner/operator verification is planned, not yet implemented. Preferred approach: postal code sent to the known business address.

Verified operators may later edit defined public fields more directly, while sensitive location/identity data may remain more restricted. All changes remain transparent.

A later community-helper system may use manual approval, independent votes and escalation for disputed/legal cases. Points/ranks are cosmetic only.

## 18. Community user profiles

A first community-profile foundation is implemented.

Key behavior:

- every user receives a short obfuscated public handle such as `CW-PEJD5`;
- the automatic handle is used until the user chooses a custom display handle;
- a custom display handle is URL-safe, unique and may be chosen only once;
- public route: `/user/{handle}`;
- login/account name and public community handle are separate concepts;
- profile data lives in `user_profiles` / `user_profile_social_links`, not as a large extension of `users`;
- profile picture, bio, hometown, age, gender, vehicle/travel setup and social links have granular visibility;
- visibility levels are public / registered users only / private;
- exact birth date is always private and is never rendered to other users; only calculated age can be shared;
- hometown is limited to city/country and selected through Photon/OpenStreetMap search;
- profile pictures are stored outside the public web root and served through a visibility-aware route;
- social-link backend support remains present, but the UI/public output is currently disabled pending a dedicated safety concept.

Default visibility:

- profile picture: public;
- age and gender: private;
- other voluntary profile fields: registered users only.

Gamification / XP / Level foundation is implemented:

- dedicated immutable-style `xp_ledger` is the source of truth;
- totals are calculated from ledger events, including negative corrections;
- historical XP amounts are never recalculated when rule values change;
- `config/xp.php` centralizes the current rule values;
- one reward per user/place/unique information key prevents repeat-edit farming;
- accepted place information is rewarded when moderation approves it;
- new-place approval rewards the voluntary information actually supplied;
- large text fields currently award 2 XP, normal information/features 1 XP;
- photo/review/rating/helper methods are ready for their upcoming product modules;
- `LevelService` implements the approved progressive curve with a stronger rise after level 50;
- public profile shows level badge, progress bar, total/progress XP and chronological XP log when enabled;
- `show_gamification` lets a user hide only their own gamification presentation while XP continue accruing;
- public help article `Level und XP` documents the rules transparently.

Badges/achievements remain the next gamification layer, followed by ratings/reviews and photos connected to the existing XP helpers.

## 19. Deferred modules

Still planned or incomplete:

- favorites
- public reports / “Stimmt nicht!” flow
- email notifications
- duplicate detection/merge
- owner verification
- community-helper workflow
- verified visit/check-in
- anti-fake review trust model
- automatic review translation
- social login
- production deployment architecture
- complete PWA/offline behavior
- analytics/consent implementation
- full opening-hours and price moderation application logic
- guest/public browse routing

## 20. Next update – concrete TODO

The next development batch should focus on the place profile and editing workflow:

1. **Improve Step-3 form ergonomics**
   - narrower controls
   - clearer grouping
   - less full-width default styling
   - compact progressive disclosure

2. **Render complete feature values on profiles**
   - list all configured active features, even unknown ones
   - green/red/grey status coding with accessible text/icon meaning
   - show dynamic values/rates inline
   - show feature free-text comments through an `i` tooltip/tap control

3. **Feature suggestions from profile**
   - unknown feature -> user can propose a value
   - existing feature -> user can propose changes
   - user can propose removal/deactivation where applicable
   - use the existing change-request/moderation model

4. **Direct admin/system-owner editing**
   - restore/use a direct **Platz eintragen** path for privileged users
   - publish directly without approval round-trip
   - allow direct editing of existing values/features
   - still create complete audit entries
   - normal users continue to see **Platz vorschlagen** / **Änderung vorschlagen**

5. **Keep versioning visible but unobtrusive**
   - Devlog remains publicly available under `/devlog`
   - the current version is shown as a small clickable link at the bottom-right of the authenticated user dropdown

## 21. Development workflow

Changes are normally committed directly to GitHub `main`, then pulled locally.

Typical commands when a change includes code, schema and seed data:

```powershell
git pull
php artisan migrate
php artisan db:seed --class=<relevant-seeder>
php artisan optimize:clear
```

Only run seeders that are actually required for the change.

MySQL DDL is not transactional. A failed migration can leave partial schema changes, so inspect actual schema/migrations before retrying or changing them.

Do not claim local changes are active until the user has pulled/migrated/seeded and confirmed the result.

## 22. Non-negotiable reminders

- Do not turn Camperwolf into a pay-to-rank directory.
- Do not hard-delete reference or historical place data by default.
- Do not treat missing feature data as false.
- Do not collapse quality into a generic single-star score.
- Keep score independent of luxury, feature count and price.
- Keep public editable fields whitelisted.
- Keep provenance separate from audit history.
- Keep technical identifiers stable and international-ready.
- Prefer objective, useful data over overly detailed fields that users cannot reliably provide.
- Preserve the working Photon/map creation flow unless there is a specific reason to change it.


## Badge / Achievement system – implemented foundation

A generic badge system now exists in addition to XP/levels.

Implemented structure:
- progress badges with Bronze / Silber / Gold / Platin tiers and permanent unlock dates;
- current progress continues beyond Platin and is shown as a running counter;
- progress events are deduplicated per user/badge/contribution key;
- current initial thresholds:
  - Entdecker: 5 / 25 / 100 / 250
  - Pfadfinder: 50 / 200 / 1500 / 3000
  - Kenner: 5 / 25 / 100 / 300
  - Fotograf: 10 / 50 / 250 / 1000
  - Spürnase: 10 / 50 / 200 / 750
  - Communityhelfer: 25 / 100 / 500 / 2000
- Entdecker progress is awarded when a new submitted place is published; merges do not retroactively remove earned progress.
- accepted new places now also award a base +5 XP.
- Pfadfinder counts voluntary information supplied with a new place; repeated edits of the same information by the same user do not count again.
- Spürnase counts later additions/corrections to already published places, excluding information that the same user already contributed at creation.
- Fotograf helper supports max 5 active counted photos per user/place; deleting a counted photo reduces the current counter while historical tier unlocks remain.
- future review/photo/community modules already have BadgeService hooks for Kenner, Fotograf and Communityhelfer.
- achievements support visible progress, one-time unlocks, fixed optional XP rewards and rarity (% of users).
- hidden achievements are architecturally supported; visitors only see the found/total count, while only the owner can see the concrete unlocked hidden achievements. Hidden achievements can never be selected as a public profile title.
- manual badges can be granted/revoked in admin user management, have fixed XP, optional public award comments and award dates.
- users can select one unlocked visible badge/achievement/manual award as their profile title.
- the public profile displays Badges & Achievements before the XP history.
- the avatar keeps the level directly visible; a reusable hover/focus status card shows public name, selected title, level/XP and member-since information.
- badge rarity statistics are cached briefly to avoid expensive repeated aggregation on profile views.
- a help article `badges-und-achievements` is created by the badge migration.

The first hidden-achievement catalogue is intentionally not finalized yet. The product owner wants to design those separately later.


## 2026-09-20 – Current V1 state and decisions

This section supersedes older implementation-status statements in this document where they conflict with the current repository state.

### V1 scope

The agreed V1 target is now deliberately narrow and practical:

- retrieve/search place data;
- create new places;
- maintain/correct existing place data;
- complete user/account/profile functionality needed for community contributions;
- German and English;
- desktop and mobile usability;
- moderation, notifications, favorites, reviews and core gamification needed for the community workflow.

Explicitly deferred until after V1:

- verified visits/check-ins and GPS verification;
- verified place-owner/operator accounts and special owner rights;
- community-helper majority-vote moderation;
- native-app expansion;
- AI review summaries;
- social links;
- highly complex booking/price rules;
- detailed holiday-specific opening-hour exceptions.

Before launch, the complete feature/category catalogue must be reviewed again. The current type-to-category mapping is intentionally usable but not considered final. Categories may be specialized more strongly so they can be assigned to place types more precisely.

### Public community profile – current model

The public identity model was refined:

- every account permanently keeps its deterministic Camperwolf ID in the form `CW-XXXXX`;
- a custom public alias is stored separately in `user_profiles.public_alias`;
- choosing an alias no longer replaces the permanent CW ID;
- the alias can still be finalized only once;
- both alias URLs and permanent CW-ID URLs resolve to the same profile;
- `User::publicName()` prefers alias, then CW ID, then account name;
- review/profile links prefer the alias but old permanent-ID links remain valid;
- profile settings show both the current public display name and the permanent Camperwolf ID;
- vehicle/travel type options now come from the central `vehicle_types` catalogue instead of a separate hard-coded list;
- join-date visibility is configurable;
- the profile/settings subsystem received a focused DE/EN localization pass;
- grouped XP-history descriptions are localized;
- mobile profile/header layout was tightened and the edit button is compact with a pencil icon;
- profile layout width no longer changes after Livewire save requests.

Social links remain intentionally disabled.

### Reviews / quality score – implemented V1 foundation

Reviews are implemented with five mandatory 1–5 dimensions:

1. cleanliness;
2. functionality;
3. condition;
4. safety;
5. usability.

Rules:

- registered users only;
- optional free text, 20–2000 characters when supplied;
- equal weighting of all five dimensions for the displayed average;
- one current review per user/place;
- previous versions remain historical;
- review validity is 12 months;
- same-version correction window: 30 minutes;
- after that, a new review version is subject to a 28-day cooldown;
- newest review is the default sort;
- monthly historical score data is available;
- reviewer profile/avatar/level/title can be shown according to profile privacy settings;
- verified-visit weighting is explicitly post-V1.

Reviews award the configured XP/badge progress through the existing gamification services.

### Opening hours – recurring annual periods

Opening hours now use recurring annual periods rather than tying normal schedules to a concrete calendar year.

Important behavior:

- year-round and seasonal periods are supported;
- an incoming range overrides only its overlapping days;
- non-overlapping remainder segments are preserved automatically;
- wrap-around annual ranges are supported;
- normal-user proposals do not mutate/split live data until moderator approval;
- moderation can preview the impact;
- `opening_hours.period_schedule` is the virtual aggregate change-request field;
- "unknown" means Camperwolf simply has no information and is not persisted as affirmative information;
- affirmative operator non-disclosure is stored separately as `not_provided` / “Keine Angabe des Betreibers”;
- empty/unknown-only opening schedules do not earn XP/badge progress.

### Structured pricing – implemented V1 foundation

The structured pricing model is independent of opening-hour periods.

Current structure:

- Product -> optional Variant -> optional Display Name;
- recurring annual price periods;
- multiple price lines/supplements per offer;
- optional link to another offer;
- optional vehicle-length and child-age ranges;
- refundable/deposit marker;
- condition text;
- feature-linked prices where appropriate.

Core tables include:

- `price_products`;
- `price_product_variants`;
- `price_billing_units`;
- `place_price_offers`;
- `place_price_periods`;
- `place_price_lines`.

The catalogue includes classic pitch/person/tax/fee/deposit/other concepts and a generic `pitch` product in addition to vehicle-specific pitches.

Price status supports concepts such as fixed/from/included/free/on-request/unknown. Billing units include night/day/hour/person-night/person-day/pitch-night/vehicle-night/use/week/month/year/kWh/liter/one-time.

On the public place profile, only actual existing price information should be listed. Missing catalogue entries are not rendered individually as “Noch keine Angabe”; if nothing is known, the box should show one general “Noch keine Preisinformationen vorhanden” state.

### Place types – V1 catalogue

New-place creation uses these V1 types:

- Campingplatz / Campground;
- Wohnmobilstellplatz / Motorhome pitch;
- Zeltplatz / Tent site;
- Parkplatz / Parking area;
- Rastplatz / Autohof / Rest area / truck stop;
- Freier Stellplatz / Informal pitch;
- Servicestation / Service station;
- Camping & Outdoor.

Legacy types remain in the database for existing data but are not offered for new places.

The place type answers **what kind of place this is**. Legal/overnight status remains a separate property and must not be encoded into the type.

### Type-driven feature categories

The same place-type relevance logic is used for:

- optional creation step 2;
- the public place profile;
- ongoing feature editing.

Rules:

- the place type defines standard/relevant feature categories;
- in creation step 2, filtering happens only by category, not by a small hand-picked feature list;
- all features in those selected categories are shown;
- step 2 is optional and may be submitted unchanged;
- on the public profile, standard categories are shown even if much is unknown;
- a non-standard category is automatically shown once at least one feature in it contains known data;
- editors can reveal all other categories through “Weitere Merkmale”;
- the “x von y Merkmalen bekannt” counter counts only standard categories plus populated non-standard categories, not the entire global feature catalogue.

The exact category assignment is intentionally provisional until the pre-launch feature-catalogue review.

### Rest-area / travel-service catalogue expansion

Preparation for official rest-area data added vehicle/reference support for:

- PKW;
- Motorrad;
- Kleintransporter;
- LKW 3.5–7.5 t;
- LKW over 7.5 t;
- articulated/combination trucks;
- coaches.

A dedicated `fuel-rest-area` / “Tanken & Rast” feature category exists, with features for fuel types, EV charging, AdBlue, LPG/CNG/hydrogen, truck pumps, shops, hotel, workshop, tyre service, car/truck wash, secure truck parking, trucker lounge, ATM, vending machines, picnic area, dog area, laundry, emergency phone and related travel-stop services.

### New-place creation – simplified two-step V1 flow

The former long four-step draft wizard is no longer the intended V1 flow.

Current target/implementation:

**Step 1 – Name, type and position**

Required core information is intentionally minimal:

- name;
- place type;
- exact map position.

Coordinates remain the primary truth. Address data may still be derived/stored when available but is not intended to create friction.

After step 1 the user can either:

- submit the place directly for moderation; or
- continue to optional step 2.

**Step 2 – Optional relevant features**

- only categories relevant to the selected place type are shown;
- every feature inside those categories is available;
- nothing is mandatory;
- the user can leave everything unchanged and submit immediately.

After submission the place remains pending. The correct user-facing message is that the place was **sent for review** and the user will be notified once it is approved; it must not imply that the place is already published.

Detailed maintenance happens after publication through the normal edit/change-suggestion workflows.

### Duplicate detection

Duplicate checking happens already during place creation and includes:

- published places;
- places currently pending moderation.

Pending suggestions are important because two users may submit the same place before the first moderation completes.

The duplicate warning is advisory, not a hard block, because legitimate distinct places may be close to each other.

The same duplicate endpoint supports excluding the currently edited place so it can also be reused when an existing place position is corrected.

### Place profile / maintenance workflow

The goal is now explicit: a published place must be **100% maintainable** through edit/change-suggestion workflows.

Existing dedicated editors cover:

- features;
- opening hours;
- prices.

The general information workflow covers/should cover:

- name;
- place type;
- map position;
- legal/use status;
- operator;
- pitch count;
- operating mode;
- opening status;
- website;
- address;
- localized description;
- directions/access information;
- suitable vehicle types.

Normal users submit moderated change requests. Privileged direct-edit behavior remains audited.

Recent work added the core fields (name/type/coordinates/legal status) and an editable map to the general change-suggestion workflow. This specific newest extension still requires local regression testing after pull before being treated as fully verified.

Redundant “edit” links that do not lead to a real workflow should be removed; each meaningful profile section should instead have a real edit/change-suggestion action.

### Official/open-data import – final pre-launch step

A final V1 pre-launch task is to seed real base data from official/open sources such as Mobilithek/GovData and later additional APIs.

Rules:

- keep source/API identity separate from normal user identity;
- do not blindly overwrite independent user/owner/admin data;
- make sync/import repeatable and safe;
- log API-driven changes transparently in the place history with the specific source/API as actor;
- preserve provenance separately from audit/history.

### V2 concept – temporary events layer

Temporary events are explicitly deferred to V2 and should not be modeled as permanent place types.

Possible future module:

- fairs;
- city festivals;
- markets;
- music/camping festivals;
- trade fairs;
- other temporary local events.

They should have start/end dates and may appear on an optional map layer so travellers can ask “we are staying here anyway — what is happening nearby?”. An event may optionally link to an existing Camperwolf place but remains a separate domain object.

### Current immediate work

The current V1 workstream is place maintenance/profile hardening:

1. verify the new full basic-data change-suggestion workflow locally;
2. ensure every public place section has a meaningful edit/change action;
3. finish profile-price empty-state cleanup;
4. continue DE/EN and mobile passes where the touched screens still contain hard-coded German;
5. run focused tests, then full test suite;
6. later perform the dedicated pre-launch feature/category catalogue redesign instead of over-optimizing the provisional mapping now.

