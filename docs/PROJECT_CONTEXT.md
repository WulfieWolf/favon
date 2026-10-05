# Favon – Project Context

_Last updated: 2026-10-05_

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
- public search/map without login
- login required only for community actions
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

Preferred model: **Telegram-based login**, potentially without e-mail/password accounts at all.

## Intended login flow

- user selects “Mit Telegram anmelden”
- Telegram provides a stable unique account identity
- Favon stores the stable Telegram subject/ID needed to recognize the account
- Favon creates its own internal user record
- an internal generated handle may exist for administration, but is not intended to be publicly visible

Do not rely on Telegram username because it can change.

## Minimal account data

Preferred minimum:

- internal Favon user ID
- stable Telegram identity / subject
- generated internal handle if needed
- role
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

## Camperwolf-derived systems likely to remove

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

- public map
- public search
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

# 25. Current setup status – 2026-10-05

Completed:

- `favon.de` exists
- Favon is intended to run on the existing Plesk server
- GitHub repository `WulfieWolf/favon` exists
- GitHub integration has write access to Favon
- Camperwolf codebase has been copied into Favon as the technical starting point
- Favon and Camperwolf repositories are independent
- Favon product concept has been defined at a high level
- privacy/anonymity direction has been defined
- initial V1 scope has been defined

Current state of copied code:

- much of the repository still contains Camperwolf-specific implementation
- copied documentation under `docs/` may still include obsolete Camperwolf material
- copied GitHub workflows/tests may still target Camperwolf-only features
- copied branding/text/assets still need cleanup
- copied authentication still needs to be replaced/reworked if Telegram-only login is retained

---

# 26. Immediate next phase: controlled slimming

Do not start by randomly deleting files.

Recommended cleanup order:

1. inventory the copied application
2. identify essential Favon foundations
3. identify definitely removable Camperwolf subsystems
4. remove by subsystem, including tests/routes/config references
5. keep application bootable after each major removal group
6. simplify database/migrations deliberately
7. replace branding and terminology
8. redesign authentication around Telegram
9. introduce Favon domain model
10. build the minimal V1 UI around map/search/place/check-in

Useful first classification:

**Keep/adapt**
- core Laravel
- map/geolocation
- place browsing
- place profile shell
- favorites
- admin/roles
- abuse/security basics
- localization infrastructure
- basic legal/privacy structure

**Remove**
- imports
- photos
- camping prices/seasons
- vehicle suitability
- XP/badges
- owner verification
- camping-specific review implementation
- camping-specific help/content
- irrelevant schedulers/jobs/workflows/tests

**Rebuild**
- authentication
- place types
- attribute voting
- health-check feature system
- check-ins
- structured rating model
- Favon moderation rules

---

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
- exact Telegram/OIDC implementation
- whether any e-mail capability remains for administration
- final operator/legal structure
- final youth-protection implementation
- final privacy-retention schedule

Do not silently treat these as settled.

---

# 29. Core non-negotiables

Unless explicitly reconsidered later:

1. **Places, not people.**
2. Public browsing without login.
3. Login required for contributions.
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
