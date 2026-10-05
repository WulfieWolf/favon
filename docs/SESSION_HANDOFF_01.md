# Session Handoff

## 1. Projektüberblick

**Camperwolf.de** ist ein Community-geführtes Portal zur Erfassung, Pflege und Bewertung von Camping-, Stell-, Park- und Serviceplätzen. Ausgangspunkt ist Deutschland; die Architektur soll aber international erweiterbar bleiben. Web/PWA hat Vorrang vor einer separaten nativen App.

Das Projekt soll bewusst **nicht** zu einer kommerziell getriebenen Plattform werden, bei der Bezahlung Ranking, Sichtbarkeit oder Bewertung beeinflusst. Endnutzer sollen die Plattform kostenlos verwenden können. Geld darf insbesondere **niemals Score, Ranking oder Sichtbarkeit eines Platzes beeinflussen**.

Das Kernprinzip lautet: **Community-Daten + transparente Historie + Moderation + nachvollziehbare Quellen**.

Die Plattform ist stark kartenzentriert. Hauptfunktion ist das **Finden eines Platzes** über Suche, Filter, Liste und Karte. Nutzer können Daten ergänzen, korrigieren, Plätze vorschlagen, Bewertungen/Rezensionen schreiben und später Fotos beitragen. Datenänderungen werden nachvollziehbar versioniert bzw. historisiert.

### Grundlegende Projektprinzipien

- Koordinaten sind die primäre geografische Wahrheit; Postdaten sind ergänzend.
- Gäste dürfen suchen und lesen.
- Community-Beiträge erfordern ein Konto.
- Änderungen durch Nutzer laufen über einen Genehmigungs-/Moderationsworkflow.
- Admins/Owner können direkt bearbeiten.
- Rechtlicher Status eines Platzes wird nicht mit Bewertung vermischt.
- Verbotene/unerwünschte Plätze sollen nicht einfach verschwinden, sondern als klar markierte Tombstones sichtbar bleiben.
- Änderungen sollen transparent geloggt werden.
- Externe Datenquellen/API-Sync sollen später ebenfalls als Quelle/Actor im Platzlog sichtbar sein.
- Referenzdaten werden deaktiviert statt gelöscht.
- Technische Slugs: Englisch, lowercase, kebab-case.
- DB/Code-Konvention: snake_case.
- Icons zentral verwalten.
- Lokalisierung von Anfang an mitdenken.

---

## 2. Aktueller Gesamtstand

### 2.1 Technische Basis

Aktueller Stack:

- Laravel 13.32
- Livewire 4.4
- PHP 8.4
- Composer 2
- Node 24 / npm 11
- MySQL 8
- Tailwind-basierte UI
- Leaflet + OpenStreetMap für Karten
- lokale Entwicklung unter Laravel Herd
- Windows-Repo lokal typischerweise:
  `D:\Dropbox\Eigene Dateien\Dokumente\Server\Camperwolf`
- URL lokal:
  `http://camperwolf.test`
- Locale aktuell de
- Zeitzone Europe/Berlin

GitHub-Repo:

- `WulfieWolf/camperwolf`
- Default-Branch: `main`

Workflow:
- Änderungen werden häufig direkt per GitHub-MCP nach `main` committed.
- Lokal zieht der Nutzer typischerweise mit:
  ```powershell
  git pull
  php artisan optimize:clear
  ```
- Wenn Migration/Seeder nötig:
  ```powershell
  php artisan migrate
  php artisan db:seed --class=...
  ```

### 2.2 Platzsuche / Dashboard / Karte

Die bisherige Dashboard-Seite ist nun bewusst die **öffentliche Hauptseite** der Anwendung.

Aktuell:

- `/` zeigt direkt die Platzsuche.
- `/dashboard` zeigt dieselbe Platzsuche.
- veröffentlichte Platzprofile sind öffentlich.
- Gäste können:
  - Platzliste sehen
  - Karte verwenden
  - Suche verwenden
  - Tags/Features filtern
  - Platzprofile öffnen
  - Hilfe/Support sehen
- Favoriten bleiben eine Account-Funktion.
- Platzvorschläge und Änderungen bleiben Account-Funktionen.

Relevante Dateien:

- `routes/web.php`
- `app/Http/Controllers/PlaceBrowseController.php`
- `app/Http/Controllers/PlaceProfileController.php`
- `resources/views/dashboard.blade.php`
- `resources/views/places/show.blade.php`
- `resources/views/layouts/app/header.blade.php`

Der Browse-Controller berücksichtigt echte Gäste inzwischen sicher:

- `$isAuthenticated = (bool) $request->user();`
- `favorites`-Filter wird für Gäste ignoriert.
- Favoritenabfragen werden nur bei angemeldeten Nutzern ausgeführt.

### 2.3 Gastzugriff / Registrierungsanreize

Der Gastzugriff wurde zuletzt bewusst so gestaltet, dass die Seite **nicht künstlich eingeschränkt** wird. Gäste dürfen den eigentlichen Nutzwert der Plattform erleben; Registrierung wird über Community-Funktionen attraktiv gemacht.

Für Gäste sichtbar, aber loginpflichtig:

- Favorit setzen
- Nur-Favoriten-Filter
- Platz vorschlagen
- Änderung vorschlagen

Beim Klick erscheint ein zentraler Registrierungsdialog mit kontextabhängigem Text.

Beispiel:
- „Platz als Favorit speichern“
- „Änderung vorschlagen“
- „Platz vorschlagen“

Dialog bietet:
- Kostenlos registrieren
- Anmelden
- Vielleicht später

Der Text soll locker, menschlich und professionell sein; **kein Behörden-/SaaS-Ton**.

Aktueller wichtiger Stand:
- „Platz vorschlagen“ ist für Gäste oben in der Navigation sichtbar.
- Zusätzlich gibt es im Kopf der Platzliste einen Gast-CTA „Platz vorschlagen“.
- Klick öffnet denselben zentralen Auth-Dialog wie Favorit/Änderung.

### 2.4 Welcome-Splash für Gäste

Beim ersten Besuch als Gast auf Startseite/Dashboard erscheint ein einmaliger Welcome-Splash.

Speicherung:
- Browser-`localStorage`
- Key:
  `camperwolf.welcome-seen.v1`

Das bedeutet:
- einmal pro Browser/Device
- privater Modus zeigt ihn erneut
- aktuell keine serverseitige Synchronisation

Der Splash wurde mehrfach optisch korrigiert:

- liegt inzwischen mit sehr hohem Inline-`z-index` garantiert über Karte/Resizer/Leaflet
- dunkler Hintergrund
- dunkle Karte
- bessere Lesbarkeit
- mehr Innenabstand
- Feature-Kacheln wurden durch eine Liste ersetzt

Aktuelle inhaltliche Punkte des Splash:

- Plätze finden
- Favoriten speichern
- Bewerten & Erfahrungen teilen
- fehlende Plätze und Änderungen melden
- Community gemeinsam aktuell halten

Die Gamification wird dort aktuell noch nicht prominent beworben.

### 2.5 Support-/Hilfe-System

Ein umfangreiches Support-/Help-System ist bereits umgesetzt.

Migration:

`database/migrations/2026_09_18_120000_create_support_help_system.php`

Tabellen:

#### `support_articles`
- slug
- title
- summary
- body
- context_key
- sort_order
- is_active
- created_by
- updated_by
- timestamps

#### `public_support_entries`
- slug
- type
- status
- title
- description
- context_key
- is_public
- sort_order
- published_at
- resolved_at
- created_by
- updated_by

#### `support_tickets`
- user_id nullable
- guest_name nullable
- guest_email nullable
- guest_phone nullable
- type
- status
- priority
- subject
- description
- context_key
- module
- route_name
- source_url
- user_agent
- public_entry_id
- assigned_to
- first_response_at
- closed_at
- timestamps

#### `support_ticket_messages`
- ticket_id
- user_id nullable
- message_type
- message
- timestamps

#### `support_ticket_events`
- ticket_id
- user_id
- event_type
- old_value
- new_value
- metadata
- created_at

Relevante Klassen:

- `app/Services/SupportContextService.php`
- `app/Http/Controllers/HelpController.php`
- `app/Http/Controllers/SupportTicketController.php`
- `app/Http/Controllers/Admin/SupportController.php`
- `app/Http/Controllers/Admin/SupportContentController.php`

Views:

- `resources/views/help/index.blade.php`
- `resources/views/help/show.blade.php`
- `resources/views/help/roadmap.blade.php`
- `resources/views/support/create.blade.php`
- `resources/views/support/thanks.blade.php`
- `resources/views/support/my-index.blade.php`
- `resources/views/support/my-show.blade.php`
- `resources/views/admin/support/index.blade.php`
- `resources/views/admin/support/show.blade.php`
- `resources/views/admin/support/content.blade.php`

Support-Ticket-Typen:

- bug
- feature_request
- improvement
- data_issue
- other

Ticket-Status:

- new
- open
- in_progress
- waiting_for_user
- on_hold
- resolved
- closed

Prioritäten:

- low
- normal
- high
- critical

Public-Support-Typen:

- known_bug
- suggested_feature
- planned_feature

Public-Support-Status:

- reported
- confirmed
- suggested
- planned
- in_progress
- resolved
- closed
- not_planned

### 2.6 Gast-Support – aktueller Stand

FINAL / AKTUELL GÜLTIG:

Für Gastmeldungen gilt:

- Name: Pflicht
- E-Mail: Pflicht
- Telefon: optional
- Betreff: optional
- Beschreibung: Pflicht

Wichtiger UX-Text:
- Gast wird ausdrücklich darauf hingewiesen, dass Ticketverwaltung im Profil **nur möglich ist, wenn er sich vor dem Absenden anmeldet/registriert**.
- Es soll nicht der Eindruck entstehen, dass ein nachträglich registriertes Konto automatisch frühere Gasttickets übernimmt.

Der Hinweis enthält direkte Buttons:
- Anmelden
- Kostenlos registrieren

### 2.7 Support-Navigation / Kontext-Hilfe

Im Header existiert ein kompakter Floating-Widget-Block mit zwei Icon-Buttons:

- Hilfe für diese Ansicht
- Fehler melden

Tabler-Icons:
- `help-circle`
- `bug`

Diese Darstellung wurde vom Nutzer ausdrücklich als gut bestätigt und gilt als **eingefroren**, solange kein konkreter Änderungswunsch kommt.

### 2.8 Notifications

Benachrichtigungssystem ist bereits umgesetzt und getestet.

Relevantes:

- `user_notifications`
- Reads
- Events
- `last_seen_at`
- Welcome notification
- Moderationsnotifications
- System announcements
- Favoritenänderungen
- Clustering
- Cleanup
- Preferences

Bestehender Service:
- `UserNotificationService`

Wichtige Präferenzen:
- moderation_decisions
- favorite_changes
- general_system

Bell-Styling gilt als **frozen**.

### 2.9 Favoriten

Favoriten sind implementiert.

Relevante Punkte:

- `place_favorites`
- `notify_changes`
- Filter im Dashboard
- Favorit auf Platzprofil
- Favoritenroute verweist auf Dashboard mit `favorites=1`

Für Gäste:
- Favoriten bleiben sichtbar
- Klick öffnet Registrierungsdialog
- „Nur Favoriten“-Filter ist ebenfalls sichtbar, führt aber zum Auth-Hinweis

### 2.10 QA

Es existiert eine umfangreiche:

`docs/QUALITY_CHECKLIST.md`

Sie wurde zuletzt um einen eigenen Abschnitt für Gastzugriff erweitert.

Aktuelle Prüfpunkte umfassen u. a.:

- öffentlicher Browse-Zugriff
- Gast-Filter/Karte
- Platzprofile öffentlich
- Gast-Registrierungsdialog
- Welcome-Splash
- neutrale 404 für geschützte Bereiche
- Gast-Support Pflichtfelder
- Hinweis auf vorherige Anmeldung

---

## 3. Aktuell laufendes Teilprojekt

### Ziel des Teilprojekts

**Nutzerprofil + Gamification-Grundarchitektur**.

Ganz zuletzt wurde entschieden, dass vor der eigentlichen Gamification ein brauchbares **Userprofil** entstehen soll, weil dort später zusammenlaufen:

- freiwillige Profilinfos
- Sichtbarkeit/Datenschutz
- XP
- Level
- Badges
- Beiträge
- Rezensionen
- Social Links

### Aktueller Implementierungsstand

**Noch nicht implementiert.**

Wir haben bislang die fachliche/technische Richtung diskutiert und finalisiert.

### Letzte konkreten Entscheidungen

#### 1. Gamification soll wie „Google Local Guides“ wirken

Nicht als Spiel im Vordergrund, sondern als subtiler Aktivitätsindikator.

Überall dort, wo ein User angezeigt wird, soll optional sein Level sichtbar sein.

Beispiel:
- `WulfieWolf · Level 12`

Ziel:
- zeigt Aktivität/Erfahrung
- fördert leichte Competition
- soll nicht suggerieren, dass hoher Level automatisch „recht hat“

#### 2. EXP werden immer weiter gesammelt, auch wenn User Gamification ausblendet

Nutzer kann die öffentliche Darstellung deaktivieren.

Wenn deaktiviert:
- Level nicht öffentlich anzeigen
- EXP ggf. nicht öffentlich anzeigen
- Badges ggf. ausblenden

Intern werden EXP aber weiter gesammelt.

#### 3. XP-System bekommt ein eigenes Ledger

FINAL / AKTUELL GÜLTIG:

EXP werden **nicht** dynamisch aus dem Audit-/Action-Log berechnet.

EXP werden auch **nicht** nur als ein einzelner Zähler hochgezählt.

Stattdessen:
- eigener XP-Event-/Ledger-Log
- jede Gutschrift/Rücknahme als eigener Datensatz
- aktueller Gesamt-XP-Wert zusätzlich als Cache/Summe

Das Audit-Log bleibt separat.

Begründung:
- vollständige Transparenz
- Performance
- historische Stabilität
- keine rückwirkenden Levelsprünge bei Regeländerung
- Rücknahmen sauber möglich
- Regelversionen nachvollziehbar

Beispiel:
```text
+100 XP · Neuer Platz angelegt · Platz #4711
+20 XP  · Details ergänzt · Platz #4711
-100 XP · Beitrag später zurückgezogen
```

#### 4. Regeländerungen wirken nicht rückwirkend

FINAL / AKTUELL GÜLTIG:

Wenn früher:
- neuer Platz = 100 XP

und später:
- neuer Platz = 50 XP

dann behalten alte Beiträge ihre damaligen 100 XP.

Neue Regel gilt nur für neue Events.

XP-Events sollen dafür `rule_version` oder vergleichbare Information speichern.

#### 5. EXP erst nach Genehmigung/Verifizierung

FINAL / AKTUELL GÜLTIG:

Keine EXP beim bloßen Absenden.

EXP erst, wenn Beitrag:
- genehmigt
- bestätigt
- verifiziert

wurde.

#### 6. Rücknahmen als Gegenbuchung

Alte XP-Events werden nicht still verändert/gelöscht.

Stattdessen Gegenbuchung:
```text
+100 XP Beitrag bestätigt
-100 XP Beitrag zurückgezogen
```

#### 7. Badges

Geplant sind zwei Hauptklassen:

**Fortschritts-/Achievement-Badges**
- Entdecker Bronze/Silber/Gold/Platin
- Datenpfleger
- Reviewer
- Fotograf
- etc.

**manuelle/besondere Auszeichnungen**
- Beta-Tester
- Founding Member
- Community-Helfer
- Event-Badge
- Admin des Monats
- besondere Community-Leistung

Manuelle Auszeichnungen sollen als solche erkennbar bleiben.

#### 8. Lernen/Guides können XP/Badge geben

Diskutiert und akzeptiert:
- kleine XP für abgeschlossene Anleitungen
- eher niedrige Werte
- teilweise Badge sinnvoller als viel EXP

Anti-Farming beachten.

### Geplante Profilinhalte

Vorgeschlagen und vom Nutzer positiv aufgenommen:

- optionales Profilbild
- optionale Bio
- Level + aktueller XP-Stand
- Fortschrittsbalken zum nächsten Level
- optionale Liste letzter Beiträge
- Liste letzter bzw. später ggf. „hilfreichster“ Rezensionen
- freiwillige Profilinfos:
  - Alter
  - Herkunft
  - Unterwegs mit / Fahrzeug
  - Geschlecht
  - Social Media Links, begrenzt
- Badges
- XP-Log/Chronik
- RPG-artige Darstellung ist ausdrücklich willkommen, solange nicht übertrieben

### Sichtbarkeit / Privacy im Profil

Geplanter Ansatz:

pro Bereich:
- öffentlich
- nur registrierte Nutzer
- privat

Beispiel:
- Profilbild öffentlich
- Bio öffentlich
- Herkunft öffentlich
- Alter privat
- Geschlecht privat
- Fahrzeug öffentlich
- Socials öffentlich
- EXP/Level privat
- Badges öffentlich
- Beiträge nur registrierte Nutzer
- Rezensionen öffentlich

Zusätzlich ein globaler Schalter:
- `Gamification öffentlich anzeigen`

Wenn aus:
- kein Level neben Namen
- keine EXP öffentlich
- keine Badges öffentlich

### Aktueller Gedankenstand

Das Userprofil soll **vor** dem eigentlichen Gamification-Frontend gebaut werden.

Begründung:
- Profil ist später zentrale Darstellungsfläche.
- XP/Badges/Reviews/Beiträge müssen sonst später neu einsortiert werden.
- Bewertungen stehen als nächste große Kernfunktion an und sollen von Anfang an XP-fähig sein.

### Unmittelbar als Nächstes

Der nächste Chat sollte mit einem **Profil-Grundgerüst** beginnen.

Noch benötigter Input vom Nutzer:
- konkrete Sichtbarkeits-Defaults
- welche Profilfelder wirklich V1 sein sollen
- ob Alter als Geburtsjahr/-datum oder direkt als Alter gespeichert wird
- welche Social-Plattformen initial unterstützt werden
- ob „Unterwegs mit“ strukturiert + Freitext sein soll
- Levelkurve noch nicht final
- konkrete XP-Werte noch nicht final
- Badge-Schwellen noch nicht final

---

## 4. Chronologie der wichtigsten Entscheidungen

### Phase A – bestehende Plattform / Datenmodell

Ausgangslage:
- Laravel-Projekt mit Platzdaten, Features, Rollen, Moderation, Versionierung.
- Fokus auf flexible und historisierbare Platzdaten.

Wichtige Richtung:
- generisches Feature-/Tag-Modell statt hartcodierter Spezialfelder
- Änderungsvorschläge als versionierte/moderierte Datenänderungen
- Admin-/Owner-Direktbearbeitung getrennt von Community-Workflow

### Phase B – Bewertungs-/Score-Konzept

Ein älteres, nicht weiter verfolgtes Review-Konzept kann noch im Projekt existieren.

FINAL:
- aktuelles 5-Sterne-System mit Versionierung bleibt
- Rezensionen mit Versionierung bleiben
- Gesamtscore
- Einzelscores
- Scores in Reviews
- Graph/Zeitverlauf
- Ansichten im Platzprofil
- alles soll erhalten bleiben

Moderation:
- durch Moderation entfernte Reviews dürfen in historischen Scores der Vergangenheit weiter enthalten sein
- ab dem Zeitpunkt der Entfernung fließen sie nicht mehr in aktuelle Berechnungen ein

### Phase C – Support / Hilfe / Roadmap

Problem:
- Nutzer brauchen Hilfe, Tickets, öffentliche Bugs/Features und Admin-Bearbeitung.

Entscheidung:
- Support-System mit:
  - Kontext-Hilfe
  - Ticket-Formular
  - User-Ticketübersicht
  - Admin-Queue
  - interne Notizen
  - Status/Assignment
  - öffentliche bekannte Bugs/Features/Roadmap
  - CMS-artige Hilfeartikel

Rich-Text-Editor:
- diskutiert
- Tiptap empfohlen
- **später**, nicht aktuelle Priorität

Kontext-Overlay:
- Idee: Seite abdunkeln + erklärende Boxen an UI-Elementen
- generisch via `data-help-key`
- noch nicht implementiert
- später

### Phase D – Guest Access

Problem:
- Rollen-Vorschau „Gast“ war technisch immer noch eingeloggter User.
- echter ausgeloggter Besucher sah nur Login.

Entscheidung:
- Hauptseite, Karte, Suche, Filter, Platzprofile öffentlich
- Community-Aktionen sichtbar lassen
- bei Klick registrierungsfördernder Dialog
- interne Bereiche neutral 404

Navigation:
- Gäste bekommen grundsätzlich ähnliche Hauptnavigation

### Phase E – Welcome Splash

Idee:
- neuer Gast soll beim ersten Besuch kurz verstehen, worum es geht
- keine klassische Marketing-Landingpage davor

Entscheidung:
- Hauptseite bleibt direkt die Anwendung
- einmaliger Welcome-Splash
- lokal per `localStorage`
- Community-Aspekt im Vordergrund
- Features kurz, nicht detailliert

Optische Iterationen:
1. erster Splash wirkte „janky“
2. Layering-Probleme mit Leaflet/Resizer
3. zu helle Karten
4. z-index mehrfach erhöht
5. schließlich Inline-Max-z-index verwendet
6. Karten durch Liste ersetzt
7. mehr Padding hinzugefügt

### Phase F – Gamification

Problem:
- Community-Motivation steigern, ohne seriöse Nutzer abzuschrecken.

Verglichener Ansatz:
- ähnlich Google Local Guides
- Level überall dezent sichtbar

Entscheidung:
- subtile Gamification
- optional ausblendbar
- EXP transparent
- Badges/Orden
- leichte Competition okay
- keine Dominanz im UI

XP-Berechnung diskutierte Varianten:

**Variante 1: dynamisch aus Actionlog**
- Vorteil: immer neu berechenbar
- Nachteil: Regeländerungen ändern rückwirkend Level

**Variante 2: nur User-Zähler erhöhen**
- Vorteil: simpel
- Nachteil: keine Transparenz/History

FINAL:
- Hybrid mit eigenem XP-Ledger + gecachtem Gesamtwert

### Phase G – Userprofil

Zuletzt:
- Profil soll vor Gamification-Frontend kommen
- zentrale Stelle für XP, Level, Badges, Sichtbarkeit, Socials, Beiträge, Rezensionen
- Klick auf Nutzername oben rechts soll direkt zum Profil führen
- Dropdown bleibt für Profilediting/Settings/etc.

---

## 5. Final entschiedene Regeln und Konzepte

### FINAL / AKTUELL GÜLTIG – Plattform

- Hauptseite ist die Platzsuche / Karte, keine vorgeschaltete Marketing-Landingpage.
- Gäste dürfen Kernfunktion vollständig nutzen.
- Community-Beiträge erfordern Konto.
- Geld darf Score/Ranking/Sichtbarkeit nicht beeinflussen.
- Qualität > Masse.
- Änderungen werden transparent geloggt.
- Referenzdaten deaktivieren, nicht löschen.
- Koordinaten primär.
- Gäste können Supporttickets senden.
- Moderation bleibt Kernbestandteil.

### FINAL / AKTUELL GÜLTIG – Gastzugriff

Öffentlich:
- Startseite
- Platzsuche
- Karte
- Filter
- Platzprofile
- Hilfe
- Roadmap
- Supportformular

Loginpflichtig:
- Favoriten
- Platz vorschlagen
- Änderung vorschlagen
- Benachrichtigungen
- Profil
- Einstellungen
- eigene Tickets
- Admin/Moderation

Registrierungsanreiz:
- geschützte Community-Funktionen sichtbar lassen
- beim Klick kontextbezogener Auth-Dialog

Interne Bereiche:
- neutral 404 statt informative 403

### FINAL / AKTUELL GÜLTIG – Gast-Support

- Name Pflicht
- E-Mail Pflicht
- Telefon optional
- klare Formulierung: erst anmelden, dann Ticket senden, wenn spätere Verwaltung gewünscht

### FINAL / AKTUELL GÜLTIG – Welcome Splash

- nur Gast
- erste Nutzung
- `localStorage`
- Key `camperwolf.welcome-seen.v1`
- Community-Fokus
- Hauptfunktion bleibt direkt nutzbar
- kein Vollbild-Marketing-Flow vor der App

### FINAL / AKTUELL GÜLTIG – Gamification

- dezent wie Local Guides
- Level optional öffentlich
- EXP laufen intern immer weiter
- eigener XP-Ledger
- gecachter Gesamt-XP-Stand
- Regeländerungen nicht rückwirkend
- EXP nur nach Genehmigung/Verifizierung
- Rücknahmen als Gegenbuchung
- XP-Regelversion speichern
- vollständige XP-Herkunft soll transparent einsehbar sein
- Badges möglich
- manuelle Auszeichnungen getrennt erkennbar
- kein direkter Gleichsetzungsmechanismus „hohes Level = vertrauenswürdig“

### FINAL / AKTUELL GÜLTIG – Review/Score

- aktuelles 5-Sterne-System bleibt
- Versionierung bleibt
- Rezensionen versioniert
- Gesamtscore/Einzelscores/Graph/Review-Scores bleiben
- entfernte Reviews beeinflussen historische Vergangenheit weiter, ab Entfernung nicht mehr aktuelle Werte

### FINAL / AKTUELL GÜLTIG – UX-Ton

Texte:
- locker
- menschlich
- freundlich
- professionell
- nicht spießig
- kein sterile SaaS-/Behörden-Sprache

---

## 6. Veraltete oder verworfene Ansätze

### Veraltet: echter Gast = Rollen-Vorschau

Früher wirkte es so, als funktioniere Gastzugriff bereits.

Ursache:
- Owner konnte Rolle „Gast“ simulieren
- technisch blieb Session aber eingeloggt und verifiziert

Ersetzt durch:
- echte öffentliche Routes für Browse/Profile

### Veraltet: Startseite nur Login/Welcome

Früher:
- `Route::view('/', 'welcome')`

Aktuell:
- `/` führt auf PlaceBrowseController
- Hauptseite ist Platzsuche

### Veraltet: Splash mit 3 hellen Kacheln

Wurde verworfen:
- schlechte Lesbarkeit im Dark Mode
- optisch zu „buttonartig“

Ersetzt durch:
- dunkler Splash
- Feature-Liste
- mehr Innenabstand

### Veraltet: Splash nur mit Tailwind-z-index-Klasse

Problem:
- ohne CSS-Neubuild neue Utility evtl. nicht im Build
- Resizer/Leaflet ragten darüber

Ersetzt durch:
- hoher Inline-`z-index`

### Verworfen: XP dynamisch aus Audit-Log berechnen

Grund:
- Regeländerungen würden rückwirkend Userlevel verändern
- Audit-Log hat andere fachliche Bedeutung

Ersetzt durch:
- separates XP-Ledger

### Verworfen: nur `users.xp += x`

Grund:
- keine nachvollziehbare Historie
- keine sauberen Rücknahmen
- keine Regelversionen

Ersetzt durch:
- Ledger + Cache

### Möglicherweise noch im Code vorhanden: altes Review-Konzept

Es wurde ausdrücklich erwähnt, dass noch ältere Review-Ansätze aus sehr frühen Projektphasen vorhanden sein könnten.

Aktueller Review-/Score-Ansatz ist maßgeblich.

---

## 7. Technische Architektur

### Routing

Relevante öffentliche Routes:

- `/` → PlaceBrowseController
- `/dashboard` → PlaceBrowseController
- `/places/{slug}` → PlaceProfileController
- `/help`
- `/help/context`
- `/help/{slug}`
- `/roadmap`
- `/support/report`
- `/support/thanks`

Auth-/verified-Gruppe enthält u. a.:

- Featureänderungen
- Platzvorschläge
- Favoriten
- Notifications
- eigene Tickets
- Role Preview
- Adminbereiche

### Permission-Schutz

Middleware:
- `app/Http/Middleware/RequirePermission.php`

Aktueller Sicherheitsentscheid:
- fehlende Berechtigung → 404

`bootstrap/app.php`:
- Gäste bei Admin-URLs → 404 statt Login-Redirect
- normale Auth-Seiten dürfen weiterhin auf Login führen

### Place Browse

`PlaceBrowseController`:

- published + active places
- Suche über:
  - name
  - city
  - postal_code
  - Features
- Featurefilter
- Kartenausschnittfilter
- Sortierung:
  - newest
  - name
  - city
- Favoriten optional, nur auth
- Marker für Leaflet
- Tag-Katalog inkl. remaining/total counts

### Karte

Frontend:
- Leaflet 1.9.4
- OSM Tiles
- Marker
- Popup mit Profil-Link
- Hover-Sync zwischen Ergebnisliste und Marker
- Kartenausschnittfilter
- draggable Splitter zwischen Liste/Karte

### Browse-Layout

Grid:
- Filter
- Ergebnisse
- Resizer
- Karte

Der Resizer war für Splash-Layering relevant.

### Support Context

`config/camperwolf_support.php`

Context Mapping u. a.:
- dashboard
- place-profile
- place-create steps
- notifications
- account settings
- support tickets

### Seeder

Support:
- `SupportContentSeeder`
- `RolePermissionSeeder`

### Permissions Support

- support.create
- support.view_own
- support.reply_own
- support.view_all
- support.reply
- support.internal_note
- support.change_status
- support.assign
- support.manage_content

Bekannter Punkt:
Support-User-Routes sind auth+verified, aber Permission-Middleware für support.create/view_own/reply_own ist nicht überall explizit angehängt.

---

## 8. Datenmodell und wichtige Strukturen

### Bestehende Kernbereiche

Bekannte Tabellen/Modelle umfassen:

- places
- place_types
- place_addresses
- place_translations
- place_details
- place_features
- feature_categories
- features
- place_vehicle_types
- vehicle_types
- place_contacts
- place_favorites
- translations
- change_requests
- roles
- permissions
- audit logs
- notification structures
- review/photo vorbereitende Strukturen
- consent/versioning structures

### Feature-System

Features:
- `value_type`
- `unit_type`

Place Features:
- numeric/text/unit
- status
- metadata JSON
- valid_until
- active

Feature Workflow:
- `FeatureWorkflowService`

### Versionierung / Historisierung

Viele Platzdaten nutzen:
- `is_active`
- `version_valid_until`

Änderungen:
- Change Requests
- Gruppen über `group_uuid`
- „latest valid current record“ Logik

### Geplante neue Profil-/Gamification-Strukturen

Noch NICHT implementiert, aber vorgeschlagene Architektur:

#### `user_profiles`
für Community-Profil statt alles in `users`

Geplante Inhalte:
- avatar
- bio
- birthday/birth_year oder age-Konzept
- origin
- gender
- travel_vehicle/travel_mode
- profile visibility/global gamification visibility

#### `user_profile_social_links`
- user_id
- platform
- url
- sort_order

#### `user_profile_visibility`
oder strukturierte Visibility-Felder/JSON

Visibility:
- public
- authenticated
- private

#### `user_experience_events`
geplant:
- id
- user_id
- event_type
- source_type
- source_id
- xp
- rule_version
- reason/description
- approved_at/created_at
- awarded_by / system
- reversal_of nullable o. ä.

#### `user_experience_totals`
- user_id
- xp_total
- optional cached level

#### `achievement_definitions`
- badge definitions
- thresholds
- categories
- tier
- active

#### `user_achievements`
- user_id
- achievement_id
- awarded_at
- awarded_by
- metadata
- visible

Diese Namen sind **Vorschläge**, noch nicht final migriert.

---

## 9. Fachliche Regeln / Business Logic

### Platzdaten

- nur published + active öffentlich
- draft/internal nicht öffentlich
- Koordinaten zentral
- Adressdaten versioniert
- Datenänderungen moderierbar
- Besitzer/Admin später eigene Regeln möglich

### Contributions

Grundregel:
- Beitrag ≠ sofort gültige Daten
- Nutzerbeiträge müssen je nach Typ genehmigt/verifiziert werden
- transparente Herkunft wichtig

### XP

Geplanter Lifecycle:

```text
Aktion
↓
Moderation / Verifizierung
↓
XP-Regel zum Zeitpunkt der Genehmigung
↓
XP-Event schreiben
↓
xp_total aktualisieren
↓
Level neu bestimmen
↓
Badges prüfen
```

Kein XP beim bloßen Absenden.

### Anti-Farming

Noch nicht final implementiert, aber fachlich wichtig:

- nicht jede kleine Feldänderung einzeln belohnen
- Contribution als Einheit betrachten
- Limits/Cap pro Contribution möglich
- Guides nur einmal belohnen
- Doppeltevents verhindern

### Vertrauenswürdigkeit

Level:
- Aktivität/Erfahrung
- kein direkter „Trust Score“

Später denkbar:
- separater Contribution Trust
- Genehmigungsquote
- Ablehnungsquote
- Dauer der Mitgliedschaft
- verifizierte Beiträge

Noch nicht implementiert.

---

## 10. UI / UX / Produktentscheidungen

### Hauptnavigation

Gast:
- Plätze
- Karte
- Hilfe & Support
- Platz vorschlagen CTA
- Anmelden
- Registrieren

User:
- analog, plus Accountfunktionen
- Notifications
- Usermenü

### Klick auf Usernamen

Geplant:
- Name/Avatar oben rechts direkt zum eigenen Profil
- Dropdown bleibt für:
  - Profil bearbeiten
  - Einstellungen
  - Benachrichtigungen
  - Meine Meldungen
  - Abmelden

### Profil

Geplante Struktur:

Header:
- Avatar
- Anzeigename
- Level
- XP
- Fortschrittsbalken
- Bio

Stats:
- angelegte Plätze
- Änderungen
- Bewertungen
- ggf. Fotos

Über mich:
- Herkunft
- Alter
- Geschlecht
- unterwegs mit

Socials:
- max. begrenzte Anzahl, z. B. 5

Badges:
- Fortschrittsbadges
- Sonderauszeichnungen

Contribution-Aktivität:
- letzte Beiträge
- später „alle anzeigen“

Reviews:
- letzte 3–5
- später ggf. „hilfreichste“

XP-Chronik:
- RPG-artig, aber nicht kitschig
- vollständige Transparenz

### Mobile

Noch offen.

Geplante Richtung:
- eine responsive Codebase
- keine separate Mobile-Version
- Browse mobil wahrscheinlich:
  - Suche
  - Filter
  - Karte/Liste Toggle
  - Bottom Sheet/Drawer
- echte Smartphone-Tests weiterhin nötig

### Mobile Preview Simulator

Vorgemerkt:
- Admin/Owner-Button
- Desktop ↔ Mobile Preview
- z. B. 390px
- Entwicklungswerkzeug
- ersetzt echten Smartphone-Test nicht

Noch nicht implementiert.

### Contextual Help Overlay

Vorgemerkt:
- Seite dimmen
- erklärende Boxen an UI-Elementen
- `data-help-key`
- generischer globaler Component
- fallback auf Hilfeartikel

Noch nicht implementiert.

---

## 11. Sonderfälle und Edge Cases

### Gastrolle vs echter Gast

Nie vergessen:
- Role Preview „Gast“ ist weiterhin eingeloggt
- echte Gasttests immer in privatem Browserfenster durchführen

### Splash

- `localStorage` ist browser-/geräteabhängig
- neuer Browser zeigt Splash erneut
- privater Modus zeigt Splash erneut
- wenn später serverseitige Synchronisierung gewünscht, eigenes Account-Flag nötig

### Support Gasttickets

- nachträglich registrierter Nutzer sieht altes Gastticket NICHT automatisch
- genau deshalb Hinweis „vor dem Absenden anmelden“

### Permission 404

Bewusst:
- interne Existenz nicht verraten
- Angreifer soll nicht zwischen „existiert aber verboten“ und „gibt es nicht“ unterscheiden können

### XP-Regeländerung

- niemals alte Events neu bewerten
- sonst Levelsprünge ohne Useraktion

### XP-Rücknahme

- Gegenbuchung statt Löschung/Manipulation

### Öffentliche Profile / Privacy

Besonders bei:
- Alter
- Herkunft
- Geschlecht
- Social Links
- Contribution-Historie

Defaults müssen noch festgelegt werden.

---

## 12. Bekannte Probleme / technische Schulden

### Support Permissions

- support.create/view_own/reply_own existieren als Permissions
- User-Support-Routes nutzen aber nicht überall explizite Permission-Middleware
- für Role Preview / Hardening später relevant

### Support Assignee List

`SupportController::show` basiert auf Rollen mit `support.view_all`
- direkte Permission Overrides könnten fehlen

### Help Content

- `support_articles.body` ist aktuell Plaintext
- WYSIWYG/Tiptap später

### Help Search

- aktuell SQL LIKE

### Gast-Support Datenschutz

- user_agent + Kontaktdaten werden gespeichert
- später Datenschutztext explizit berücksichtigen

### Guest Support E-Mail

- registrierte Nutzer bekommen interne Notifications
- Gastantworten per E-Mail noch nicht implementiert

### Versionslink

Im Header existiert noch der sichtbare Dev-Version-Link oben rechts:
- später für RC entfernen oder anders lösen

### Public Home

Die alte Welcome-View existiert vermutlich weiterhin, wird aber aktuell nicht als `/` verwendet.

### Mobile

Browse-Layout ist Desktop-orientiert.
- noch kein systematischer Mobile-Pass

### Foto-MVP

Noch nicht umgesetzt.

### Review-/Score-MVP

Fachlich weit entschieden, UI/Workflow aber noch nicht umgesetzt.

### Legal

Noch offen:
- Impressum
- Datenschutz
- Nutzungsbedingungen
- Community-Regeln
- „Über Camperwolf“

### Deployment

Noch offen:
- Production ENV
- HTTPS
- Mail
- Queue
- Scheduler/Cron
- Backups
- Restore-Test
- Error Pages
- Debug off

---

## 13. Offene Punkte

### Kurzfristig

1. Userprofil Grundgerüst
2. Sichtbarkeits-/Privacy-Modell
3. XP-Ledger Architektur
4. Level-Berechnung
5. Profil-Navigation / Klick auf Usernamen
6. danach Badge-Grundmodell
7. danach Reviews/Score-MVP mit XP-Hooks
8. Foto-MVP

### Später für V1 / 1.0 Beta

- Impressum
- Datenschutz
- Nutzungsbedingungen
- Community-Regeln
- Über Camperwolf
- Mobile-Grundpass
- Permission-/Security-Hardening
- Produktionsdeployment
- E-Mail Zustellbarkeit
- QA komplett
- Dev-Version-Link aufräumen
- reale Seed-/Basisdaten
- offizieller/open-data Import vor Launch

### Finaler Pre-Launch Schritt

Merkliste:
- Import/Sync offizieller/open-data Platzquellen
- z. B. Mobilithek, GovData
- Basisdaten seeden
- API-Quelle als Actor im Platzlog
- nie blind User-/Owner-/Admin-Daten überschreiben

### Nach V1 / Nice-to-have

- Owner-Verifizierung
- Community-Helfer
- Check-in / Geo-Verifizierung
- PWA
- Rich Help Editor
- Contextual Help Overlay
- Mobile Preview Simulator
- Gamification-Polish
- „Hilfreich“-Votes für Rezensionen
- Social-Profil-Feinschliff
- individuelle Event-Badges
- Community-Ränge/Achievements
- ggf. Trust-System getrennt von Level

---

## 14. Nächste konkrete Schritte

### Priorität 1 – genau hier fortsetzen

**Userprofil-Konzept in ein konkretes V1-Datenmodell übersetzen.**

Nächster Chat sollte den Nutzer gezielt, aber knapp zu folgenden Punkten befragen:

1. Welche Profilfelder sind für die erste Version Pflichtbestandteil?
2. Geburtsdatum, Geburtsjahr oder nur frei eingegebenes Alter?
3. Herkunft:
   - Land
   - Region/Bundesland
   - Stadt
   - frei / strukturiert?
4. Geschlecht:
   - welche Optionen?
   - Freitext?
5. „Unterwegs mit“:
   - strukturierte Kategorie
   - optionaler Freitext wie „Ford Transit L3H3“
6. Social Links:
   - welche Plattformen initial?
   - max. Anzahl?
7. Sichtbarkeits-Defaults pro Feld
8. Profil komplett öffentlich oder nur registriert?
9. XP/Level standardmäßig öffentlich? Nutzer hat bereits gesagt: **ja, standardmäßig an**
10. Badges standardmäßig öffentlich? Noch nicht explizit final bestätigt.

### Priorität 2 – Migration/Modelle

Danach implementieren:

- `user_profiles`
- `user_profile_social_links`
- Visibility-Struktur
- Profile Controller/Views
- Profilroute
- eigenes Profil + öffentliches Profil

### Priorität 3 – XP-Ledger

- Migration `user_experience_events`
- Total/Cache-Tabelle oder User-Cache
- zentrale XP Service-Klasse
- Idempotenz
- Reversal
- rule_version
- öffentliche Aufschlüsselung
- Profil Fortschrittsbalken

### Priorität 4 – Levelmodell

Noch gemeinsam definieren:
- Schwellen
- Formel
- maximale oder offene Level

### Priorität 5 – Badges

- Definitions
- Tiers
- automatische + manuelle Awards
- Sichtbarkeit

### Priorität 6 – Reviews

Danach:
- Review/Score-MVP
- XP erst bei Genehmigung
- Badge-Hooks
- Contribution Timeline

---

## 15. Zusammenarbeit / Session-Charakter

### Arbeitsweise

Der Nutzer arbeitet explorativ, aber mit klarer Erwartung an konkrete Umsetzung.

Typisches Muster:
1. Idee diskutieren
2. Varianten kurz abwägen
3. Nutzer entscheidet
4. direkt implementieren
5. Nutzer testet UI lokal
6. kleine Iterationen folgen anhand Screenshot/Feedback

### Was gut funktioniert

- kurze, klare technische Erklärungen
- direkt sagen, warum etwas so gebaut wird
- keine unnötige Theorie
- konkrete Vorschläge mit Defaults
- bei offensichtlicher Richtung nicht dauernd rückfragen
- lieber funktionierende erste Version liefern und gemeinsam nachschärfen
- bei UI-Feedback direkt reagieren

### Ton

- Deutsch
- locker
- sachlich
- ruhig
- kein Marketing-Sprech
- keine übertriebene Begeisterung
- keine herablassende Sprache
- Nutzer schreibt oft mit Tippfehlern; nicht korrigieren oder kommentieren, Inhalt verstehen

### Detailgrad

Der Nutzer möchte:
- genug Technik, um zu verstehen, was passiert
- keine seitenlangen Framework-Grundlagen
- bei Architekturentscheidungen Begründung
- bei Routineänderungen kurze Pull-/Clear-Befehle

### Entscheidungsstil

Der Nutzer mag:
- „Mein Vorschlag wäre …“
- dann konkrete Liste
- er bestätigt oder ändert Punkte
- danach Umsetzung

Nicht gut:
- bereits getroffene Entscheidungen wieder neu aufrollen
- theoretische Best Practices gegen bewusst gewählte Produktentscheidungen stellen
- zu viel Rückversicherung bei klaren Aufgaben

### Kritisches Feedback

Erwünscht.

Wenn etwas:
- exploitbar
- datenschutzrechtlich heikel
- UX-mäßig widersprüchlich
- technisch riskant
ist, soll das klar gesagt werden.

Aber:
- Entscheidung bleibt beim Nutzer
- nicht blockieren, wenn es nur eine Produktpräferenz ist

### Wiederkehrende Konventionen

- Änderungen direkt nach GitHub `main`
- danach lokal:
  `git pull`
  `php artisan optimize:clear`
- Migration/Seeder nur nennen, wenn nötig
- keine PROJECT_OVERVIEW/RECOVERY automatisch anfassen
- QA-Checklist darf bei neuer Funktion mitwachsen
- frozen UI nicht ohne Anlass verändern
- neue UX sollte bestehende Camperwolf-Sprache übernehmen

---

## 16. Dinge, die der nächste Chat NICHT tun sollte

- das aktuelle Review-/Score-Konzept wieder grundsätzlich neu entwerfen
- alte Review-Ansätze reaktivieren
- Gastzugriff wieder hinter Login setzen
- Platzsuche durch Marketing-Landingpage ersetzen
- Community-Funktionen komplett verstecken, nur weil sie Login brauchen
- beim Gast-Support Telefon wieder verpflichtend machen
- Gastticket nachträglich automatisch einem später erstellten Account zuordnen, ohne explizites neues Konzept
- Permission-Fehler wieder als informative 403 anzeigen
- Splash wieder in helle Cards zerlegen
- Bell-Styling verändern
- Profil-Step3/Feature-UX unnötig umbauen
- Geld in Score/Ranking/Visibility einfließen lassen
- XP rückwirkend neu berechnen
- XP nur als untransparenten Einzelzähler behandeln
- Audit-Log und XP-Ledger vermischen
- Level direkt als Trust Score behandeln
- Gamification zu dominant machen
- Nutzer zwingen, Gamification öffentlich zu zeigen
- manuelle Badges und automatische Achievements ununterscheidbar machen
- Support-/Help-System ohne Grund neu strukturieren
- bestehende Architektur aus „Best Practice“-Gründen großflächig umbauen
- überholte Dokumentation höher gewichten als diese Handoff-Datei oder neuere Entscheidungen

---

## 17. Schnellstart für den nächsten Chat

1. Projekt: Camperwolf.de, Community-Stellplatzportal.
2. Repo: `WulfieWolf/camperwolf`, Branch `main`.
3. Laravel 13.32, PHP 8.4, MySQL 8, Livewire 4.4.
4. Hauptseite ist öffentliche Platzsuche.
5. Gäste können Suche, Filter, Karte, Profile nutzen.
6. Community-Aktionen brauchen Konto.
7. Gast-CTAs bleiben sichtbar und öffnen Auth-Dialog.
8. Platz vorschlagen / Änderung vorschlagen / Favorit sind die wichtigsten Registrierungsanreize.
9. Gast-Support: Name+E-Mail Pflicht, Telefon optional.
10. Gasttickets später nicht automatisch im neuen Account sichtbar.
11. interne geschützte Bereiche → neutrale 404.
12. Welcome-Splash einmal pro Browser via `camperwolf.welcome-seen.v1`.
13. Support-/Help-System ist bereits umfangreich umgesetzt.
14. Notifications und Favoriten sind implementiert.
15. Review-/Score-Konzept ist fachlich final; Umsetzung noch offen.
16. Foto-MVP noch offen.
17. Aktuell aktives Teilprojekt: Userprofil + Gamification.
18. Gamification soll wie Google Local Guides wirken.
19. Level dezent neben Usernamen.
20. Gamification öffentlich ausblendbar, EXP laufen intern weiter.
21. EXP erst nach Genehmigung/Verifizierung.
22. EXP eigenes Ledger, nicht Audit-Log.
23. Alte XP bleiben trotz späterer Regeländerungen bestehen.
24. Rücknahmen via Gegenbuchung.
25. Level ≠ Trust Score.
26. Profil soll Avatar, Bio, Level/XP, Progressbar, Profilinfos, Socials, Badges, Contributions, Reviews und XP-Log tragen.
27. Sichtbarkeit pro Bereich: public/auth/private geplant.
28. nächster Schritt: Profil-V1-Felder + Visibility-Defaults konkret festlegen.
29. danach Migration + Profilseiten.
30. danach XP-Ledger/Level/Badges und erst dann Reviews mit XP-Hooks.

---

## 18. Handoff-Nachricht an den nächsten Chat

Du übernimmst hier ein laufendes Projekt. Lies diese Datei vollständig, bevor du neue Architektur oder Produktentscheidungen vorschlägst.

Der aktuelle Schwerpunkt ist **Userprofil + Gamification-Grundarchitektur**. Die letzten Entscheidungen waren:

- Gamification soll dezent wie Google Local Guides funktionieren.
- EXP werden in einem eigenen, transparenten Ledger gespeichert.
- EXP-Regeländerungen wirken nicht rückwirkend.
- EXP gibt es erst nach Genehmigung/Verifizierung.
- Level ist ein Aktivitätsindikator, kein direkter Trust Score.
- Nutzer können Gamification öffentlich ausblenden; EXP laufen intern weiter.
- Das Profil soll die zentrale Oberfläche für XP, Level, Badges, Sichtbarkeit, freiwillige Profildaten, Beiträge und Rezensionen werden.

Als Nächstes solltest du mit dem Nutzer die **konkreten V1-Profilfelder und Sichtbarkeits-Defaults** festlegen und danach das Profil-Datenmodell implementieren.

Diese Datei ersetzt den bisherigen Arbeitskontext. **Neuere Entscheidungen in dieser Datei sind höher zu gewichten als alter Code, ältere Dokumentation oder frühere Ansätze im Repository.** Wenn Code der aktuellen Entscheidung widerspricht, behandle den Code zunächst als möglicherweise veraltet und prüfe ihn gezielt, bevor du die Produktentscheidung infrage stellst.
