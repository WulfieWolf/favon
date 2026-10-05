# Favon – Project Context

_Last updated: 2026-10-05_

## Project identity

**Name:** Favon  
**Domain:** favon.de  
**Repository:** `WulfieWolf/favon`  
**Origin:** Favon is a new, separate project. The Camperwolf codebase is being used only as a technical starting point.

### Hard separation from Camperwolf

- Favon and Camperwolf are **two independent projects**.
- Camperwolf must never be modified as part of Favon work.
- `WulfieWolf/camperwolf` is read-only reference material for Favon.
- All Favon development must happen only in `WulfieWolf/favon`.
- Favon will get its own database, `.env`, APP_KEY, session cookie, cache prefix, queues, logs, scheduler configuration and deployment path.
- Shared server infrastructure is acceptable, shared application state is not.
- Before any write operation, explicitly verify whether the target is **Favon** or **Camperwolf**.

---

## Product idea

Favon is a **community-maintained directory of cruising/hookup-related places**.

It is intentionally **not** a dating platform, social network, escort directory, porn site or contact marketplace.

Core principle:

> **Places, not people.**

The platform should help users find and maintain information about relevant public or commercial places without exposing who contributed which data.

### Intended place categories

Keep the catalogue narrow and focused on typical cruising / hookup meeting places.

Likely examples:

- Cruising spot / public cruising area
- Gay sauna
- Gay bar / clearly relevant hookup-oriented venue
- Potentially similar public/commercial locations that fit the same narrow use case

Explicitly avoid expanding Favon into a general adult-business directory.

Not planned:

- Bordell / escort directory
- Private prostitution offers
- Private homes / apartments
- Pornographic venues purely for sexual-content consumption
- General “places where sex can be bought”
- Dating/contact ads
- Profiles of sex workers or private persons
- Schools, playgrounds or other obviously inappropriate sensitive locations
- Private property where use would normally require trespassing

The final place-type catalogue should stay deliberately small.

---

## Product philosophy

Favon should be **much simpler than Camperwolf**.

Primary goals:

- very low technical barrier
- mobile-first
- minimal forms
- public browsing without an account
- contributions only after login
- no public user identity
- no explicit sexual content
- no photos
- no free-text reviews
- minimal moderation burden
- community-maintained freshness through tiny “microtasks”

Favon should feel easy enough that a user can add or maintain a place in a few seconds.

---

## Public anonymity / account model

Favon is designed so that users are **internally accountable but publicly anonymous**.

### Authentication

Preferred model: **Telegram as the primary/only login provider**.

No classic e-mail/password registration is planned unless later required.

Expected flow:

- “Mit Telegram anmelden”
- Telegram provides a stable unique identity
- Favon creates an internal user account
- internal generic username / handle, e.g. `user-random`
- the username is not meant to be visible publicly

### Store only what is needed

Preferred minimal account data:

- internal Favon user ID
- stable Telegram identity / subject
- random internal handle
- role / admin state
- ban / abuse state
- timestamps

Do **not** store Telegram display name, username, profile photo or phone number unless a concrete technical reason appears later.

### Public visibility

Users must never see who did what.

Never show:

- “Peter added this place”
- “Frank checked in”
- “User X voted for toilet”
- “User Y edited this place”
- public contributor histories
- public profiles
- follower/friend systems
- check-in histories

Public output is always aggregated community information.

Admins may internally map activity to accounts when necessary for:

- duplicate-vote prevention
- duplicate-check-in prevention
- abuse investigation
- account bans

Even in admin UI, identity data should be minimized where possible.

Terminology note: technically this is **pseudonymous**, not fully anonymous, because the operator can map an internal account to Telegram.

---

## Place creation

Adding a place must be extremely quick.

### Minimal required information

The current concept is:

Required:

- **place type**
- **location / coordinates**

Optional:

- **name**

If no name is supplied, the display fallback is simply the place type.

Examples:

- no name → “Cruising-Platz”
- named → “Stadtpark Essen”
- list display may optionally use “Stadtpark Essen · Cruising-Platz”

### Small set of stable base attributes

Some fundamental properties may be requested at creation because they normally define the place and do not need a live health check.

Current candidates:

- indoor / outdoor
- intended / typical target audience: male / female / both
- parking available

These should remain deliberately limited.

---

## Two separate kinds of community-maintained data

A central Favon concept is to distinguish **stable place details** from **live / variable conditions**.

### 1. Stable / base information

Examples:

- place type
- position
- name
- indoor / outdoor
- intended / typical audience
- parking available

These are maintained **from the place profile**, not during check-in.

Users can confirm the current value or vote for an alternative.

Example:

Current value: Outdoor

- “Stimmt das?” → Ja / Nein
- If “Nein”, offer the valid alternative(s), e.g. Indoor
- votes accumulate
- if a clear independent community consensus forms, the active value may be changed automatically or queued for moderation depending on the field

Important:

- base attributes are not asked repeatedly during check-in
- position changes should be treated more cautiously than simple binary attributes
- a single user must never be able to rewrite core place data immediately

### 2. Variable / health-check information

These are only things a person can quickly verify **while physically at the location**.

Current examples:

- place currently open / closed / accessible
- toilet still available
- washing facility still available
- waste disposal still available
- potentially lighting operational

Keep this list short.

These values are primarily maintained through **check-in health checks**.

---

## Community voting model

Favon should avoid “one user edits the truth”.

Instead, facts should be derived from **aggregated recent votes / confirmations**.

Internally, individual votes should still be attributable to an account so the system can enforce:

- one vote per user/place/feature/time window
- abuse prevention
- revocation / moderation if necessary

Publicly, only aggregated counts or consensus are shown.

### Vote freshness / expiry

Votes should be time-sensitive so stale data naturally disappears.

Concept:

- a vote has a timestamp
- public values use only votes within a defined recency window
- old votes expire from the active calculation
- if nobody has confirmed a feature for a long time, it can become “unconfirmed” or disappear automatically

Example idea:

```
Place: Parkplatz Essen
Feature: Toilet
Date: 2026-01-01
Votes: 5
```

The actual implementation should likely keep per-user votes internally, then aggregate them for display.

Different properties may later get different freshness windows.

---

## Check-in

Check-in is a core feature, but privacy must come first.

### Purpose

The check-in should answer only:

> “Is something currently happening here?”

It must **not** answer:

> “Who is here?”

### Privacy model

- browser asks for current location
- backend checks whether the user is within an acceptable radius of the place
- raw GPS location is used only for the immediate validation
- raw user location should **not be stored**
- no public user identity
- no public check-in list
- no public check-in history
- no profile history such as “visited 17 times”

Internally, a user association may be kept only as long as technically necessary to prevent repeated check-ins / abuse.

Prefer short-lived check-in records.

Potential minimal fields:

```
place_id
user_id or short-lived pseudonymous token
checked_in_at
expires_at
```

Later consider unlinking or deleting user identity after expiry.

### Public check-in display

Possible outputs:

- “Aktuell 3 Check-ins”
- “Hier ist gerade etwas los”
- “ruhig / etwas los / gut besucht”

Never display user names.

---

## Check-in health check

A check-in should double as a **very short place health check**.

Core principle:

> Check-in = current condition, not place editing.

### Flow

Always ask:

- Is the place currently open / accessible?

Then optionally confirm only currently known variable features.

Examples:

- toilet still available?
- washing facility still available?
- waste disposal still available?
- lighting working?

The ideal default flow:

> **Alles noch aktuell?**  
> [ Ja, passt ] [ Nein, etwas stimmt nicht ]

If “Ja”:

- accessibility plus current variable features receive a fresh confirmation

If “Nein”:

- show only the small list of current variable features
- user marks what no longer matches

### Branching examples

Toilet:

- Yes → fresh positive confirmation
- Unknown → no change
- No → ask:
  - temporarily unavailable
  - permanently removed
  - unknown

Accessibility:

- Yes → fresh confirmation
- Unknown → no change
- No → ask:
  - currently inaccessible / closed
  - permanently inaccessible / closed
  - unknown

One negative response must **not** instantly change the place status.

Use independent-user thresholds / recent consensus for warnings and permanent changes.

### Intelligent microtasks

Do not necessarily ask every visitor every question.

Prefer asking for stale or uncertain data.

Example:

- toilet has 15 fresh confirmations today → skip
- washing facility has not been confirmed in months → ask next visitor

Goal: data maintenance in seconds, not a form.

---

## Structured place rating

Favon should **not have classic free-text reviews**.

Instead use a structured survey / rating form.

Reasons:

- no explicit sexual descriptions
- no descriptions of identifiable people
- minimal moderation
- controlled data quality
- easy comparison
- easy filtering
- lower youth-protection risk

Potential 1–5 rating dimensions:

- cleanliness
- discretion
- lighting
- accessibility
- general foot traffic
- feeling of safety
- atmosphere (optional)

Additional structured observations may include:

- primarily daytime / evening / night
- quiet / variable / busy
- typical audience pattern

Keep the final number of questions manageable.

Terminology should be neutral:

- “Ort bewerten”
- “Einschätzung abgeben”

Avoid encouraging “experience reports”.

### No media / free text

Current concept:

- **no review text**
- **no photos**
- **no pornographic content**
- no sexually explicit descriptions

This is deliberate and should be treated as a core constraint, not a temporary omission.

---

## Moderation and content rules

Favon should use high standards for which places are accepted.

The platform controls its catalogue and does not need to publish every submission.

Reject:

- playgrounds
- schools
- obviously sensitive family/child locations
- private homes
- private personal offers
- doxxing / personal data
- entries requiring trespassing
- clearly unsuitable or abusive submissions
- sexually explicit descriptions
- pornography
- identifiable descriptions of visitors

The catalogue should describe **places**, not people or sexual acts.

---

## Legal / safety positioning

Favon is intended as a neutral **place directory**, not a service that arranges sexual encounters.

Four core legal/product principles:

1. **Places instead of people**
2. **No explicit content**
3. **No contact mediation / dating functions**
4. **Collect as little user data as reasonably necessary**

### Youth protection direction

Favon should avoid:

- pornography
- sexual imagery
- explicit text
- chat
- personal ads
- dating / contact brokerage
- user-uploaded photos

Structured neutral place information and ratings are preferred.

The service may be positioned as an adult-oriented / 18+ service, but the exact final youth-protection implementation should be reviewed before public launch.

The goal is specifically to avoid creating a product that requires identity-based age verification unless legally necessary.

### Privacy direction

Particular caution is required because a check-in at a cruising / gay venue may indirectly reveal information about sexual life or orientation.

Therefore:

- strong data minimization
- no tracking
- no movement profiles
- no long-term check-in histories
- raw GPS not persisted
- short retention for live check-ins
- separate internal abuse identity from public content
- consider whether a DPIA / Datenschutz-Folgenabschätzung is required before launch

### Impressum / private address

The user does not want their private residential address publicly associated with Favon.

Possible later solution to evaluate:

- `flexdienst.de`
- specifically a legally serviceable / ladungsfähige Impressumsadresse

This is **only noted as an option**, not chosen or booked.

The actual operator / legal entity structure is still undecided.

---

## Technical strategy

Favon should initially reuse the proven technical foundation of Camperwolf, then remove everything unnecessary.

### Reuse candidates

Potentially useful existing components:

- Laravel base
- map / geolocation handling
- mobile-first layout patterns
- favorites
- admin basics
- roles / permissions
- abuse protection
- public place browsing
- place profile structure
- deployment conventions
- queue / scheduler infrastructure where actually needed

### Remove / replace from Camperwolf

Favon does not need Camperwolf-specific complexity such as:

- camping feature catalogue
- vehicle suitability
- price / season logic
- camping opening-hours complexity
- open-data import pipelines
- external source staging / review
- image/photo system
- EXIF / image processing
- owner verification
- XP / badges / achievements
- complex camping review model
- camping-place history complexity
- destination.one / NRW TFIS / Bayern ATKIS / Overture / DATEX imports
- camper-specific texts, branding, icons and legal copy
- e-mail-centric registration / password reset flows, if Telegram-only auth is retained

Do not merely “hide” unused Camperwolf functions. Remove them cleanly from Favon so the codebase becomes small and maintainable.

---

## Server / deployment

Favon will live on the **same Plesk server as Camperwolf**, but as a fully separate application.

This is considered technically acceptable.

Expected separation:

```
/var/www/vhosts/camperwolf.de/...
/var/www/vhosts/favon.de/...
```

Favon must have its own:

- document root
- Laravel app
- `.env`
- APP_KEY
- database
- DB user
- session cookie name
- cache prefix
- logs
- queue configuration
- scheduler
- TLS certificate
- storage

Certificates are not expected to be a problem; Favon should use its own Let's Encrypt certificate via Plesk.

Favon is expected to be significantly lighter than Camperwolf because it has no image processing or large import pipelines.

---

## Current setup status – 2026-10-05

- `favon.de` already exists and is on the same server as Camperwolf.
- Plesk domain setup has been started with Laravel hosting in mind.
- GitHub repository `WulfieWolf/favon` has been created.
- GitHub integration now has write access to Favon.
- Camperwolf repository is private and must stay untouched.
- Plan: copy current Camperwolf code into Favon as a one-time starting snapshot, then make Favon independent.
- Recommended local copy process removes Camperwolf's `.git` directory from the **copy only**, initializes a fresh Favon Git history, and pushes to `WulfieWolf/favon`.
- A temporary initial README commit was created in Favon while testing GitHub write access; the first full push may replace it.

---

## Development workflow / guardrail

Before every development task, explicitly determine:

> **Are we working on Favon or Camperwolf?**

For Favon tasks:

- write only to `WulfieWolf/favon`
- never commit/push to `WulfieWolf/camperwolf`
- Camperwolf may be read to reuse patterns or code
- do not assume a Camperwolf feature belongs in Favon
- favor simpler solutions even when Camperwolf already has a more complex implementation

For Camperwolf tasks in other sessions:

- Favon changes must not leak back into Camperwolf unless explicitly requested
- do not reuse Favon-specific concepts automatically

This separation is a permanent project rule.

---

## V1 conceptual scope

Keep V1 intentionally small:

- public map and search
- “near me”
- narrow place-type filters
- optional name, place type + coordinates as minimum place creation
- Telegram login
- anonymous internal user accounts
- favorites
- place profile
- stable-data confirmation / correction voting
- check-in with proximity validation
- short-lived anonymous live activity count
- check-in health check for variable features
- structured place rating
- admin moderation / bans
- place reporting
- legal/privacy pages

Explicitly out of V1:

- chat
- DMs
- dating
- contact ads
- public profiles
- photos
- free-text reviews
- pornography
- extensive gamification
- broad adult-business catalogue
- large external-data imports
