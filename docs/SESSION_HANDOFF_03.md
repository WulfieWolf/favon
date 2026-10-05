# Session Handoff

> **Stand:** 2026-09-20  
> **Gültigkeitsbereich:** Übergabe der Session, in der die vorhandenen Handoffs zu `docs/PROJECT_CONTEXT.md` konsolidiert wurden.  
> **Wichtig:** `docs/PROJECT_CONTEXT.md` ist weiterhin der primäre konsolidierte Einstiegspunkt. Dieses Handoff ist neuer als dessen dokumentierter Quellenstand und wurde noch nicht wieder in `PROJECT_CONTEXT.md` eingearbeitet. Es ergänzt den Kontext vor allem um den Dokumentationsvorgang selbst.  
> **Technischer Ausgangsstand dieser Übergabe:** Nach Erstellung von `PROJECT_CONTEXT.md` lag `main` auf Commit `893f63ce4f47d2d73707f38a3dabefc2a720ef52` (`Add consolidated project context`). Durch diese Session wurden bis zur Erstellung dieses Handoffs keine Anwendungsdateien, Migrationen oder Tests verändert.

## 1. Projektüberblick

Camperwolf ist ein gemeinschaftlich gepflegtes Verzeichnis für Campingplätze, Wohnmobilstellplätze, Zeltplätze, Park- und Rastplätze, freie Stellplätze, Servicestationen sowie Camping-/Outdoor-Orte. Deutschland ist der erste Fokus; Datenmodell und Produkt sollen international erweiterbar bleiben.

Das Produkt soll Reisenden strukturierte, nachvollziehbare und möglichst aktuelle Informationen liefern. Anders als viele kommerzielle Verzeichnisse soll Geld weder Score, Ranking noch Sichtbarkeit beeinflussen. Gäste dürfen die Kerninhalte öffentlich nutzen; wer Daten beiträgt, braucht ein Konto. Änderungen sollen nicht im Verborgenen geschehen, sondern moderiert beziehungsweise auditiert und im späteren Platzlog nachvollziehbar sein.

Die wichtigsten übergeordneten Prinzipien sind:

- Qualität vor bloßer Datenmenge, ohne unnötig hohe Hürden beim Vorschlagen neuer Plätze.
- Koordinaten sind die primäre geografische Wahrheit; Adressen sind ergänzend.
- „Unbekannt“ bedeutet nicht „Nein“.
- Community-Beiträge werden moderiert; direkte Änderungen privilegierter Nutzer werden trotzdem protokolliert.
- Externe Datenquellen sind eigenständige Akteure und dürfen Nutzer-, Besitzer- oder Admin-Daten nicht blind überschreiben.
- Historie und Provenienz sind wichtiger als spurloses Löschen.
- Technische und fachliche Entscheidungen sollen klein, nachvollziehbar und über Git reversibel umgesetzt werden.

## 2. Aktueller Gesamtstand

### Konsolidierte Dokumentation

In dieser Session wurden `docs/SESSION_HANDOFF_01.md` und `docs/SESSION_HANDOFF_02.md` vollständig gelesen und in einer neuen zentralen Datei zusammengeführt:

- `docs/PROJECT_CONTEXT.md`
- Commit: `893f63ce4f47d2d73707f38a3dabefc2a720ef52`
- Commit-Nachricht: `Add consolidated project context`

Die Handoffs wurden nicht aneinandergehängt. Dopplungen wurden entfernt, zeitliche Konflikte zugunsten der neueren Information aufgelöst und Unsicherheiten ausdrücklich markiert. Beide bisherigen Handoff-Dateien blieben unverändert als Archiv erhalten.

`PROJECT_CONTEXT.md` enthält 20 Abschnitte und ist nun der primäre Einstiegspunkt für neue Sessions. Es beschreibt Projektstand, aktives beziehungsweise zuletzt abgeschlossenes Teilprojekt, Architektur, Datenmodell, Business Logic, Reviews, Rollen, UX, externe Quellen, finale Entscheidungen, Altansätze, Risiken, Roadmap, nächste Schritte und Zusammenarbeit.

### Bereits umgesetzte Produktbereiche

- Öffentliche Platzliste, Suche/Filter, Kartenansicht und veröffentlichte Platzprofile.
- Authentifizierung sowie Rollen-/Berechtigungsgrundlage.
- Zwei-Schritt-Anlage neuer Plätze mit Duplikatwarnung und vollständig optionalem zweiten Schritt für Merkmale.
- Vollständiger Änderungs-/Vorschlagsweg für veröffentlichte Plätze.
- Gruppierte `change_requests`, Moderation, Anwendung angenommener Änderungen und `audit_logs`.
- Strukturierte Merkmale, saisonale Öffnungszeiten und strukturierte Preise.
- Review-System mit fünf festen Dimensionen, Versionierung, Reports, Moderation, Score und Monatsverlauf.
- Nutzerprofile mit permanenter CW-ID, optionalem Alias, Sichtbarkeitseinstellungen und Profilbild.
- XP-Ledger, Level-Grundlage, Badges/Achievements.
- Favoriten, Benachrichtigungen und Support-System.
- Deutsche und englische Lokalisierungsgrundlage, noch nicht vollständig bereinigt.

### Technisch vorhanden, aber nicht vollständig final

- `photos`, `place_photos` und `Photo` bilden eine Foto-Grundlage; ein vollständiger Foto-MVP samt Moderation ist nicht dokumentiert.
- Merkmal-/Kategoriezuordnungen funktionieren, benötigen vor dem Launch aber eine fachliche Abschlussprüfung.
- Strukturierte Preise sind der Zielpfad; die Legacy-Struktur `place_prices` kann noch parallel existieren.
- Review-„Mehr laden“ erhöht das Limit über einen Seitenreload und ist noch keine echte Append-/Cursor-Paginierung.
- Badge-/Achievement-System ist technisch angelegt, Katalog und Übersetzungen können noch unvollständig sein.
- Karten- und Geocoding-Dienste sind vorhanden; ein amtlicher/Open-Data-Import fehlt.

### Noch nicht beziehungsweise nicht vollständig umgesetzt

- Foto-MVP und Foto-Moderation.
- Owner-Verifizierung und Owner-Bereich.
- Community-Helfer mit eigenem Vertrauens- und Rechtekonzept.
- Check-in/Verified Visit; vorhandene Felder haben in V1 keine Score-Gewichtung.
- Duplikat-Merge.
- Produktionsreife Absicherung von Performance, Rate Limits, Uploads, Datenschutz, Recht, Deployment, Backup und Monitoring.
- Amtlicher/Open-Data-Import als finaler V1-Schritt vor dem Launch.

## 3. Aktuell laufendes Teilprojekt

### Was in dieser Session tatsächlich aktiv war

Das aktive Teilprojekt dieser Session war **Dokumentationskonsolidierung und Übergabefähigkeit**, nicht die Entwicklung eines neuen Produktfeatures.

Ziel war, aus den beiden umfangreichen Handoffs eine belastbare, langfristig wartbare zentrale Kontextdatei zu erstellen, damit neue Chats nicht jedes historische Dokument erneut interpretieren müssen und keine veralteten Konzepte versehentlich reaktivieren.

### Aktueller Implementierungsstand

Die Konsolidierung ist abgeschlossen:

- `docs/PROJECT_CONTEXT.md` wurde erstellt und auf `main` gespeichert.
- Die Datei wurde nach dem Schreiben nochmals aus dem Repository gelesen und geprüft.
- Alle verlangten 20 Abschnitte sind vorhanden.
- `SESSION_HANDOFF_01.md` und `SESSION_HANDOFF_02.md` wurden nicht verändert oder gelöscht.
- Es gab in dieser Session keine Änderung an PHP-, Blade-, JavaScript-, CSS-, Konfigurations-, Migrations- oder Testdateien.
- Deshalb wurden für diese reine Dokumentationsänderung keine Anwendungstests ausgeführt.

Dieses `SESSION_HANDOFF_03.md` ist die unmittelbare Fortsetzung: Es dokumentiert die Konsolidierung selbst und den exakten Übergabepunkt für den nächsten Chat. Es ist bei seiner Erstellung naturgemäß noch nicht in `PROJECT_CONTEXT.md` aufgeführt.

### Letzte konkrete Änderungen

1. Vollständige Lektüre von `SESSION_HANDOFF_01.md` und `SESSION_HANDOFF_02.md`.
2. Abgleich mit dem aktuellen `main`, wichtigen Projektdateien und dem unmittelbar vorher übernommenen Review-Cleanup.
3. Auflösung des zentralen Zeitkonflikts:
   - Handoff 01 beschrieb Profile/Gamification/Reviews teilweise noch als geplant oder unvollständig.
   - Handoff 02 und der aktuelle Code zeigen diese Bereiche als umgesetzt.
   - Maßgeblich ist der neuere Stand.
4. Ergänzung des nach Handoff 02 erfolgten Review-Cleanups (`1321cf4046e7c0cc40b3b00f18ee690a78a1dc4b`).
5. Übernahme der jüngsten Nutzerentscheidung zur Score-Historie:
   - Moderativ entfernte Plätze/Projekte dürfen in historischen Scores verbleiben.
   - Entfernte Reviews zählen ab Entfernung nicht mehr im aktuellen Score; historische Monatswerte bleiben bestehen.
6. Erstellung und Commit von `docs/PROJECT_CONTEXT.md`.

### Zuletzt diskutierte Probleme

- Die vollständige Testsuite war beim Review-Cleanup nicht komplett grün: 98 Tests bestanden, zwei bereits vorhandene `UserProfileTest`-Fälle scheiterten an deutscher Erwartung bei englischer Ausgabe.
- `composer ci:check` meldete 35 bereits vorhandene Pint-Probleme.
- Diese Baseline-Probleme wurden bewusst nicht als Teil der Dokumentationsarbeit oder des Review-Cleanups versteckt mitbereinigt.
- Der nächste V1-Block ist nicht final ausgewählt.

### Aktueller Gedankenstand

Der zuletzt aktive technische Block – vollständige Platzpflege plus Review-Cleanup – ist abgeschlossen. Der Nutzer hat nach dem Cleanup visuell geprüft, dass sich an der Seite nichts unerwartet verändert hat und nach seinem Eindruck weiterhin alles funktioniert.

Der dokumentierte nächste sinnvolle Produktblock ist **noch keine finale Entscheidung**. Der Foto-MVP ist die aktuelle Empfehlung, weil `photos`, `place_photos` und `Photo` bereits eine Grundlage bilden, die eigentliche Nutzung und Moderation aber fehlen. Search/Map-Härtung oder Duplikat-Merge können bei geänderter Nutzerpriorität stattdessen vorgezogen werden. Der offizielle/Open-Data-Import soll bewusst erst launchnah erfolgen.

### Was unmittelbar als Nächstes passieren sollte

1. Ein neuer Chat liest zuerst `docs/PROJECT_CONTEXT.md` vollständig und danach dieses neuere `docs/SESSION_HANDOFF_03.md`.
2. Er prüft den aktuellen `main`, weil nach diesem Handoff weitere Commits hinzugekommen sein können.
3. Falls der Review-Cleanup auf einem lokalen Rechner noch nicht eingespielt/verifiziert wurde: `git pull`, `php artisan migrate`, `php artisan optimize:clear`, `php artisan test --filter=PlaceReviewServiceTest`.
4. Danach wird mit dem Nutzer der nächste V1-Block ausdrücklich festgelegt.
5. Ohne neue Priorität beginnt die Analyse des Foto-MVP mit den bestehenden Foto-Tabellen, `Photo` und den aktuellen Rechten. Die entfernte Legacy-Tabelle `review_photos` darf dabei nicht wieder eingeführt werden.

## 4. Chronologie der wichtigsten Entscheidungen

### Öffentliche, community-geführte Produktbasis

- **Ausgangsproblem:** Bestehende Portale sind stark kommerziell, Inhalte ungleich verteilt und Bewertungsmodelle teils schwer nachvollziehbar.
- **Diskutierte Richtung:** Öffentliches Verzeichnis versus stärker abgeschottete/registrierungspflichtige Nutzung.
- **Entscheidung:** Lesen, Suchen und Karten/Profile sind öffentlich; Beiträge brauchen ein Konto.
- **Begründung:** Der Kernnutzen soll ohne Hürde verfügbar sein, Missbrauch bei Beiträgen aber begrenzbar bleiben.

### Platzanlage

- **Alter Ansatz:** Langer Assistent mit mehreren Detail-/Review-Schritten.
- **Problem:** Zu hohe Einstiegshürde; Gefahr, dass Nutzer neue Plätze gar nicht einreichen.
- **Entscheidung:** Zwei Schritte. Schritt 1 enthält Name, Platztyp und Koordinaten und kann sofort eingereicht werden. Schritt 2 bietet vollständig optional relevante Merkmale.
- **Spätere Präzisierung:** Duplikatprüfung berücksichtigt `pending`, nicht `draft`, sucht in 750 m und warnt nur. Beim Bearbeiten wird der aktuelle Platz ausgeschlossen.

### Merkmale und Platztypen

- **Alter Ansatz:** Fest verdrahtete `QUICK_FEATURES` und gröbere/ältere Typen.
- **Entscheidung:** Typabhängiger zentraler Katalog über `PlaceTypeFeatureService`/Workflow. Acht aktuelle V1-Typen.
- **Begründung:** Relevante Angaben sollen passend zum Platztyp gezeigt werden, ohne Extras zu verlieren.
- **Offen:** Die fachliche Zuordnung des Katalogs gilt noch als vorläufig.

### Vollständige Platzpflege

- **Ausgangsproblem:** Bearbeitung einzelner Bereiche war fragmentiert und ein globaler Link führte zeitweise direkt zu Merkmalen.
- **Entscheidung:** Ein zentraler Änderungsflow für alle relevanten Platzdaten. Normale Nutzer erzeugen gruppierte `change_requests`, privilegierte Nutzer ändern direkt, aber auditiert.
- **Ergebnis:** Stammdaten, Position, rechtlicher/Öffnungsstatus, Betreiber, Stellplatzzahl, Betriebsart, Website, Adresse, Texte, Fahrzeugtypen, Merkmale, Öffnungszeiten und Preise sind abgedeckt.
- **Bestätigung:** Der praktische End-to-End-Weg wurde vom Nutzer getestet und als funktionierend bestätigt.

### Strukturierte Öffnungszeiten und Preise

- **Öffnungszeiten:** Wiederkehrende Jahreszeiträume; ganzjährig, saisonal und jahresübergreifend. Überschneidungen werden bei Annahme aufgeteilt, Restsegmente bleiben erhalten.
- **Preise:** Produkt → Variante → Saison/Periode → Preiszeile mit Abrechnungseinheit, Bedingungen und optionalen Kautionen/Bezügen.
- **Verworfen:** Viele einzelne „Noch keine Angabe“-Blöcke.
- **Aktuell:** Nur tatsächliche Preise anzeigen; bei kompletter Leere genau ein gemeinsamer Leerzustand. Strukturierte Daten haben Vorrang vor Legacy-Preisen.

### Profile und öffentliche Identität

- **Diskutierte Variante:** Öffentlicher Alias als eigentliche Identität.
- **Entscheidung:** Permanente CW-ID `CW-XXXXX`; Alias ist optional und ergänzt die ID.
- **Begründung:** Dauerhaft stabile Referenz bei gleichzeitig persönlicher Anzeige.
- **Weitere Regeln:** Alias nur einmal finalisierbar; Sichtbarkeit `public`/`registered`/`private`; Geburtstag nie direkt öffentlich, höchstens Alter; Social Links vorerst aus.

### XP, Level und Badges

- **Verworfene Varianten:** Aus Audit-Logs berechnete XP oder einfacher Gesamtzähler.
- **Entscheidung:** `xp_ledger` als Buchungsquelle mit Regelversionen, Deduplizierung und Gegenbuchungen.
- **Begründung:** Beiträge müssen reproduzierbar, widerrufbar und sprachunabhängig zuordenbar bleiben.
- **Wichtig:** Level ist Aktivität, kein Trust Score und keine automatische Moderationsberechtigung.

### Review-System

- **Frühe Variante:** Dynamische Fragen/Antworten, Helpful Votes und `review_photos`.
- **Problem:** Paralleles, zu komplexes Schema ohne Nutzen für das nun festgelegte Produktmodell.
- **Entscheidung:** Fünf feste Dimensionen von 1 bis 5: Sauberkeit, Funktionalität, Zustand, Sicherheit, Nutzbarkeit. Gleiches Gewicht; optionaler Text.
- **Technische Umsetzung:** `place_reviews`, `place_review_versions`, `place_review_reports`; eine aktuelle Review pro Nutzer/Platz mit Versionen.
- **Spätere Härtung:** Zentralisierung in `PlaceReviewService::DIMENSIONS`, exakte Schlüssel-/Wertvalidierung, technische XP-Dedupe-Schlüssel, konkurrierende Erstbewertungen abgesichert, Sichtbarkeit/Report-Ziel korrigiert.
- **Cleanup:** Alte Review-Tabellen werden durch `2026_09_20_220000_drop_legacy_review_tables.php` entfernt.

### Review- und Platzhistorie bei Moderation

- **Zuletzt geklärter Konflikt:** Wie entfernte Inhalte historische Scores beeinflussen.
- **FINAL:** Eine entfernte Review fließt ab dem Zeitpunkt der Entfernung nicht mehr in den aktuellen Score ein. Bereits entstandene historische Monatswerte bleiben bestehen.
- **FINAL:** Durch Moderation entfernte Plätze/Projekte dürfen weiterhin in historischen Score-Auswertungen enthalten sein.
- **Begründung:** Der aktuelle Zustand soll bereinigt sein, historische Darstellungen sollen aber die damalige Datenlage nicht rückwirkend umschreiben.

### Externe Datenquellen

- **Entscheidung:** Offizielle/Open-Data-Quellen werden als eigener Akteur mit stabiler Source-Identität behandelt.
- **Begründung:** Transparenz und Schutz unabhängiger Nutzer-/Owner-/Admin-Daten.
- **Roadmap:** Import als finaler V1-Schritt vor Launch, wenn Datenmodell und Katalog stabil genug sind.

### Dokumentationsstrategie

- **Ausgangsproblem:** Mehrere lange Handoffs können sich widersprechen und jeder neue Chat müsste erneut rekonstruieren, was noch gilt.
- **Entscheidung:** Eine konsolidierte `docs/PROJECT_CONTEXT.md` als primärer Einstiegspunkt; Handoffs bleiben unverändertes Archiv.
- **Konfliktregel:** Neuere dokumentierte Entscheidung schlägt ältere; technischer Iststand muss zusätzlich im Code geprüft werden.
- **Ergebnis dieser Session:** Kontextdatei erstellt. Dieses Handoff 03 wird in einer späteren Konsolidierung als neuere Quelle ergänzt.

## 5. Final entschiedene Regeln und Konzepte

- **FINAL / AKTUELL GÜLTIG:** `docs/PROJECT_CONTEXT.md` ist der primäre konsolidierte Einstiegspunkt; Handoffs sind Archiv und inkrementelle Quelle.
- **FINAL / AKTUELL GÜLTIG:** Bei Dokumentationskonflikten gilt grundsätzlich die neuere Entscheidung; bei technischem Widerspruch muss der Code geprüft und die Abweichung benannt werden.
- **FINAL / AKTUELL GÜLTIG:** Öffentliche Kernnutzung ohne Konto, Beiträge nur authentifiziert.
- **FINAL / AKTUELL GÜLTIG:** Koordinaten sind primär, Adressen ergänzend.
- **FINAL / AKTUELL GÜLTIG:** Geld beeinflusst Score, Ranking oder Sichtbarkeit nicht.
- **FINAL / AKTUELL GÜLTIG:** „Unbekannt“ ist nicht „Nein“.
- **FINAL / AKTUELL GÜLTIG:** Zwei-Schritt-Platzanlage; Schritt 2 ist optional.
- **FINAL / AKTUELL GÜLTIG:** Duplikatsuche warnt, blockiert aber nicht.
- **FINAL / AKTUELL GÜLTIG:** Aktuelle V1-Typen sind `campground`, `motorhome-pitch`, `tent-site`, `parking`, `rest-area`, `free-pitch`, `service-station`, `camping-outdoor`.
- **FINAL / AKTUELL GÜLTIG:** `stay`, `stay-service`, `service` sind Legacy; Events sind kein Platztyp.
- **FINAL / AKTUELL GÜLTIG:** Normale Änderungen werden als gruppierte `change_requests` moderiert; privilegierte Direktänderungen bleiben auditiert.
- **FINAL / AKTUELL GÜLTIG:** Strukturierte saisonale Öffnungszeiten und Preise sind das Zielmodell.
- **FINAL / AKTUELL GÜLTIG:** Reviews nutzen fünf feste, gleich gewichtete Dimensionen.
- **FINAL / AKTUELL GÜLTIG:** Aktuelles Review-Schema: `place_reviews`, `place_review_versions`, `place_review_reports`.
- **FINAL / AKTUELL GÜLTIG:** Entfernte Reviews zählen ab Entfernung nicht zum aktuellen Score; frühere historische Monatswerte bleiben.
- **FINAL / AKTUELL GÜLTIG:** Moderativ entfernte Plätze/Projekte dürfen historische Scores weiter beeinflussen.
- **FINAL / AKTUELL GÜLTIG:** Verified Visits gewichten Reviews in V1 nicht.
- **FINAL / AKTUELL GÜLTIG:** Permanente CW-ID; Alias nur ergänzend.
- **FINAL / AKTUELL GÜLTIG:** XP basiert auf `xp_ledger`; Level ist kein Vertrauensscore.
- **FINAL / AKTUELL GÜLTIG:** Externe Quellen überschreiben bestätigte manuelle Daten nicht blind und erscheinen als Quelle/Akteur im Änderungslog.
- **FINAL / AKTUELL GÜLTIG:** Social Links bleiben vorerst deaktiviert.
- **FINAL / AKTUELL GÜLTIG:** Technische Slugs: englisch, lowercase, kebab-case; Datenbankfelder: snake_case.

## 6. Veraltete oder verworfene Ansätze

| Veralteter Ansatz | Warum verworfen | Aktueller Ersatz | Mögliche Reste |
|---|---|---|---|
| Langer Platz-Wizard | Zu hohe Beitragshürde | Zwei-Schritt-Anlage, Schritt 2 optional | `draft-details.blade.php`, `draft-review.blade.php` können noch vorhanden sein |
| Fest verdrahtete `QUICK_FEATURES` | Nicht typspezifisch und schlecht wartbar | Zentraler Katalog/`PlaceTypeFeatureService` | Nach Restverweisen suchen |
| Alias ersetzt CW-ID | Keine stabile öffentliche Identität | CW-ID dauerhaft, Alias ergänzend | `handle_finalized_at` oder ähnliche Feldreste möglich |
| Dynamische Reviews mit Fragen/Antworten | Parallelmodell und unnötige Komplexität | Fünf feste Dimensionen | Alte Erzeugungsmigrationen bleiben als Historie |
| `review_photos` | Teil des verworfenen Review-Schemas | Künftiger Foto-MVP auf `photos`/`place_photos` | Wird durch Cleanup-Migration entfernt |
| Helpful-Vote-Reviewtabellen | Nicht Teil des aktuellen V1-Konzepts | Kein aktueller Ersatz nötig | `review_helpful_votes` wird entfernt |
| Globales Bearbeiten direkt zu `#features` | Unvollständiger/uneinheitlicher Flow | `places.info-suggest.edit` | Alte Links prüfen |
| Viele leere Preisblöcke | Überladen und irreführend | Ein gemeinsamer Leerzustand | Legacy-Views prüfen |
| Events als Platztyp | Zeitlicher Inhalt statt Ortstyp | Später eigenständiges Event-Modul | Nicht in `place_types` reaktivieren |
| XP aus Audit-Log oder Zähler | Nicht sauber deduplizierbar/revidierbar | `xp_ledger` | Alte Berechnungsreste prüfen |
| Marketing-Landingpage oder Login-Zwang | Kernnutzen soll öffentlich sein | Funktionsorientierter öffentlicher Einstieg | Nicht ohne neue Produktentscheidung rückbauen |

Ältere Handoffs können Formulierungen enthalten, wonach Profile, Gamification oder Reviews noch geplant seien. Diese Aussagen sind überholt. Die Bereiche sind inzwischen implementiert; nur die jeweils dokumentierten Restarbeiten bleiben offen.

## 7. Technische Architektur

### Stack

- PHP 8.4
- Composer 2
- Laravel 13
- Livewire 4
- MySQL 8
- Blade, Flux UI, Tailwind CSS
- Leaflet/OpenStreetMap
- OSM/Photon für Geocoding und Reverse Geocoding
- Node 24/npm 11 im dokumentierten lokalen Setup
- Laravel Herd, typische lokale URL `camperwolf.test`
- Locale-Grundlage Deutsch/Englisch, Anwendungstimezone `Europe/Berlin`

Der Lockfile-/CI-Stand benötigt effektiv PHP 8.4; die GitHub-Actions-Konfiguration wurde im Review-Cleanup von PHP 8.3 auf PHP 8.4 angehoben.

### Relevante Verzeichnisse

- `app/Http/Controllers/`: öffentliche, authentifizierte und administrative Webabläufe.
- `app/Services/`: zentrale Business Logic für Änderungen, Features, Zeiten, Preise, Reviews, Rechte, Profile und Gamification.
- `app/Models/`: eher schlanke Eloquent-Models; große Teile der Domäne nutzen Query Builder.
- `resources/views/`: Blade-Ansichten und Partials für Plätze, Reviews, Profile, Support und Admin.
- `routes/`: `web.php`, `settings.php`, `console.php`.
- `database/migrations/`: maßgebliche Schemahistorie.
- `database/seeders/`: Demo-/Grunddaten; Demo-Reset über `DemoDataResetService`.
- `tests/`: Feature-/Service-Tests.
- `docs/`: Projektüberblick, Recovery/Quality-Dokumentation, Handoffs und zentrale Kontextdatei.

### Wichtige Controller

- `PlaceBrowseController`
- `PlaceProfileController`
- `PlaceSuggestionController`
- `PlaceInfoSuggestionController`
- `PlaceFeatureController`
- `PlaceOpeningHoursController`
- `PlacePriceController`
- `PlaceReviewController`
- `UserProfileController`
- `UserProfilePhotoController`
- Admin-`ChangeRequestController`
- Admin-`ReviewReportController`
- Admin-`AuditLogController`

Zusätzlich bestehen Controller für Favoriten, Benachrichtigungen, Rollen/Rechte und Support. Vor Änderungen die aktuellen Dateinamen im Repository prüfen.

### Wichtige Services

- `PlaceTypeFeatureService`, `FeatureWorkflowService`
- `OpeningHoursPeriodService`, `PricePeriodService`
- `ChangeRequestApplyService`, `ChangeRequestModerationService`
- `PermissionService`, `RolePermissionService`
- `UserNotificationService`
- `XpService`, `LevelService`, `BadgeService`
- `PublicHandleService`
- `PlaceReviewService`, `ReviewModerationService`
- `SupportContextService`
- `DemoDataResetService`

### Models

Dokumentiert sind unter anderem `User`, `UserProfile`, `UserProfileSocialLink`, `Photo`, `Role` und `Permission`. Die Domäne ist nicht vollständig modellbasiert; Query-Builder-Code ist normal und sollte nicht ohne konkreten Nutzen in einen großen Eloquent-Umbau überführt werden.

### Views/Komponenten

Platzprofile bestehen aus Teilansichten für Stammdaten, Merkmale, Öffnungszeiten, Preise und Reviews. Für Reviews sind insbesondere relevant:

- `resources/views/places/partials/_reviews.blade.php`
- `resources/views/reviews/history.blade.php`

Die Platzanlage/-pflege besitzt aktuelle Suggest-Views; ältere Draft-Views können als Altlasten vorhanden sein. Zentrale Icons, Begriffe und Übersetzungen sollten nicht mehrfach abweichend implementiert werden.

### Routen

Die maßgeblichen Routen liegen in `routes/web.php`; Einstellungen in `routes/settings.php`. Der globale Platz-Bearbeitenweg soll zu `places.info-suggest.edit` führen. Genaue Namen weiterer Subrouten vor Eingriffen im aktuellen Route-File prüfen.

### Migrations

Wichtig sind die Migrationen für:

- Plätze, Typen, Übersetzungen, Details, Kontakte, Adressen und Fahrzeugtypen.
- Features/Kategorien/Zuordnungen.
- `change_requests`, Whitelists und Audit-Logs.
- Öffnungsperioden/-pläne.
- strukturierte Preise.
- Profile, Einstellungen, XP und Badges/Achievements.
- Reviews.

Besonders relevant:

- `2026_09_19_193500_create_badge_achievement_system.php`
- `2026_09_20_220000_drop_legacy_review_tables.php`

Alte Erzeugungsmigrationen werden nicht nachträglich umgeschrieben. Die spätere Drop-Migration ist der gültige Weg. MySQL-DDL kann nicht zuverlässig als vollständig transaktional behandelt werden.

### Jobs und externe APIs

Laravel-Jobtabellen/-infrastruktur sind vorhanden. Eigene projektbezogene Jobs wurden in den Handoffs nicht eindeutig identifiziert; vor Aussagen im Code prüfen. Vorhandene externe Dienste sind OSM/Leaflet/Photon. Amtliche Datenquellen sind geplant, aber nicht integriert.

### Authentifizierung, Rollen und Rechte

Authentifizierung basiert auf Laravel/Fortify. Grundlagen/Migrationen für 2FA und Passkeys existieren. Rollen und Berechtigungen werden über Services und Tabellen gesteuert. Interne, nicht erlaubte Bereiche sollen neutral mit 404 antworten, damit ihre Existenz nicht unnötig offengelegt wird.

### Tests und CI

- `tests/Feature/PlaceReviewServiceTest.php` ist für den letzten Cleanup zentral.
- Gezielter Stand: 13 bestandene Review-Tests, 46 Assertions.
- Dokumentierter Gesamtlauf: 98 bestanden, zwei bestehende Profiltests fehlgeschlagen.
- `composer ci:check`: 35 bestehende Pint-Probleme.
- Diese Session selbst hat nur Dokumentation geändert und keine Tests ausgeführt.

## 8. Datenmodell und wichtige Strukturen

### Plätze

- `places`: zentrale Identität, Koordinaten, Status und Kernfelder.
- Dokumentierte Statuswerte: `draft`, `pending`, `published`.
- Öffentlich sichtbar sind nur aktive veröffentlichte Plätze.
- Platztyp und rechtlicher Übernachtungsstatus sind getrennt.

### Platztypen

- `place_types` und Übersetzungen.
- Aktuelle V1-Slugs: `campground`, `motorhome-pitch`, `tent-site`, `parking`, `rest-area`, `free-pitch`, `service-station`, `camping-outdoor`.
- Legacy: `stay`, `stay-service`, `service`.

### Versionierte/strukturierte Platzdaten

- `place_addresses`
- `place_translations`
- `place_details`
- `place_contacts`
- `place_vehicle_types`

Exakte Gültigkeits-/Versionsspalten vor Änderungen in den Migrationen prüfen.

### Features

Featuredefinitionen, Kategorien und Typzuordnungen bilden den Katalog. `place_features` speichert bekannte Informationen am Platz. Nicht vorhandene Zeile/Angabe darf nicht automatisch als negatives Merkmal interpretiert werden.

### Änderungswesen

- `change_requests`: unter anderem `group_uuid`, Target, ursprüngliche und vorgeschlagene JSON-Werte, Status, Einreicher und Moderationsbezug.
- `suggestable_fields`: steuert, was vorgeschlagen werden darf.
- `audit_logs`: direkte und moderierte Änderungen.

Gruppierung ist wichtig, damit zusammengehörige Felder als ein fachlicher Vorgang behandelt werden.

### Öffnungszeiten

- `opening_hour_periods`: jährlich wiederkehrende Zeiträume.
- `opening_hours`: Zeitplan innerhalb der Periode.
- `opening_hours.period_schedule`: virtuelles suggestable field; Quelle der Wahrheit bleibt der Elternzeitraum.

### Preise

- `price_products`
- `price_product_variants`
- `price_billing_units`
- `place_price_offers`
- `place_price_periods`
- `place_price_lines`
- Legacy möglich: `place_prices`

Preisstatus: `fixed`, `from`, `included`, `free`, `on_request`, `unknown`.

### Nutzer/Profile

- `users`: Konto und Authentifizierung.
- `user_profiles`: `public_handle`, optionaler `public_alias`, `alias_finalized_at`, Sichtbarkeiten und Profiloptionen.
- `user_settings`: weitere Einstellungen.
- `User::publicName()`: Alias, dann CW-ID, dann Kontoname.

### XP/Badges

- `xp_ledger`: Source of Truth, Regelversionen, technische Dedupe-Schlüssel, Gegenbuchungen.
- Badge-/Achievement-Tabellen laut Migration `2026_09_19_193500_create_badge_achievement_system.php`; exakte Tabellennamen/Felder bei Weiterarbeit direkt prüfen.
- `selected_badge_id` beziehungsweise Auswahl eines angezeigten Titels ist Teil des Systems.

### Reviews

- `place_reviews`: Review-Identität, aktuelle Referenz und Sichtbarkeit.
- `place_review_versions`: historische Versionen, Dimensionen und Text.
- `place_review_reports`: Meldungen zur aktuellen sichtbaren Review.
- Veraltet/entfernt: `reviews`, `review_questions`, `review_answers`, `review_helpful_votes`, `review_photos`.

### Fotos und Quellen

- `photos`, `place_photos`: technische Foto-Grundlage.
- `place_data_sources`: Quellenbezug; genaue Felder/Nutzung im Code prüfen.

### Weitere Domänen

Favoriten, Benachrichtigungen und Support sind implementiert. Dokumentierte Supporttabellen:

- `support_articles`
- `public_support_entries`
- `support_tickets`
- `support_messages`
- `support_events`

## 9. Fachliche Regeln / Business Logic

### Öffentliche Sichtbarkeit

Nur aktive veröffentlichte Plätze werden öffentlich gezeigt. Interne Entwürfe und Moderationszustände dürfen nicht über direkte URLs leaken.

### Platzvorschlag

Schritt 1: Name, Typ, Koordinaten. Sofortige Einreichung möglich. Schritt 2: passende Feature-Kategorien und alle darin enthaltenen Merkmale, ohne Pflichtfelder.

### Duplikate

- Radius: 750 m.
- Namensähnlichkeit wird berücksichtigt.
- `pending` einbeziehen, `draft` ausschließen.
- Aktuellen Platz beim Bearbeiten über `exclude_place_id` ausschließen.
- Warnung statt Blockade.
- Späterer Merge bleibt notwendig.

### Änderungsmoderation

Normale Nutzer erzeugen gruppierte Vorschläge. Privilegierte Nutzer dürfen direkt pflegen. Beide Wege benötigen Nachvollziehbarkeit. Beim Anwenden sind veraltete Ausgangswerte und zusammengehörige Änderungen zu berücksichtigen.

### Merkmale

Der Platztyp bestimmt Standardkategorien. Öffentliche Anzeige zeigt Standardkategorien und Nichtstandard-Kategorien, sobald bekannte Daten vorhanden sind. „Weitere Merkmale“ öffnet den Rest. Gezählt werden Standardmerkmale plus bekannte Extras.

### Öffnungszeiten

Wiederkehrende Jahresperioden, inklusive Jahreswechsel. Überschneidungen werden bei Annahme aufgespalten. Unbekannt erzeugt keine positive Information/XP. `not_provided` bedeutet bewusst, dass der Betreiber keine Auskunft gibt, und kann als nützliche Angabe zählen.

### Preise

Strukturierte Preise haben Vorrang. Öffentlich nur reale Preise anzeigen. Bei kompletter Leere ein gemeinsamer Leerzustand. Legacy-Preise höchstens getrennt als alte/weitere Information. Verhältnis `unknown` zu `not_provided` ist noch nicht abschließend vereinheitlicht.

### Reviews

- Dimensionen: `cleanliness`, `functionality`, `condition`, `safety`, `usability` beziehungsweise die im Service exakt definierten technischen Schlüssel.
- Werte: ganze Zahlen 1–5.
- Gesamtwert: einfaches arithmetisches Mittel.
- Text optional; bei Verwendung 20–2.000 Zeichen.
- Eine aktuelle Review pro Nutzer/Platz, historische Versionen.
- Gültigkeit 12 Monate.
- 30 Minuten Korrektur derselben Version, anschließend 28 Tage Cooldown.
- Standardsortierung neueste zuerst.
- `is_public` muss in öffentlichen Abfragen gelten.
- Reports nur auf aktuelle sichtbare Review.

### Historisierung und Moderation

- Entfernte Review: ab Entfernung nicht im aktuellen Score; vergangene Monatswerte bleiben.
- Moderativ entfernter Platz/Projekt: darf historische Score-Auswertungen weiterhin beeinflussen.
- Problematische/verbotene Orte möglichst markieren/deaktivieren statt historienlos löschen. Umsetzungsstand dieses Platz-Tombstone-Konzepts ist unklar.

### Profile

- CW-ID dauerhaft.
- Alias optional und einmal finalisierbar.
- Sichtbarkeit `public`, `registered`, `private`.
- Geburtsdatum nie öffentlich, nur berechnetes Alter.
- `show_gamification` verbirgt Anzeige, stoppt aber keine XP.
- `show_join_date` steuert Beitrittsdatum.
- Fahrzeugtypen aus zentralem Katalog.
- Social Links deaktiviert.

### Gamification

XP nur für anerkannte/angenommene Beiträge. Deduplizierung und Regelversionen verhindern Sprach-/Versionsprobleme. Rücknahmen über Gegenbuchung, nicht durch rückwirkendes Neuberechnen der gesamten Historie.

### Externe Quellen

Stabile Quellenidentität, wiederholbare Imports, protokollierte Änderungen. Keine pauschale Autorität über manuelle Daten. Endgültige Feldprioritätsmatrix noch offen.

## 10. UI / UX / Produktentscheidungen

- Kompakt und funktionsorientiert statt Marketingseite.
- Gastzugriff auf Suche, Karte und Profile.
- Beitragsschaltflächen dürfen sichtbar sein, führen bei Gästen aber verständlich zur Anmeldung.
- Platzprofil bündelt Informationen, Merkmale, Öffnungszeiten, Preise, Reviews und Änderungsweg.
- Ein globaler Bearbeiten-Button statt widersprüchlicher Links; Ziel `places.info-suggest.edit`.
- Relevante Merkmalkategorien zuerst; Extras bei Bedarf.
- Kartenmarker/Position bearbeitbar; Duplikatwarnung nicht blockierend.
- Bei völlig fehlenden Preisen nur ein Leerzustand.
- Review-Verlauf monatlich sichtbar; echte Pagination noch offen.
- Profil mobil kompakt; Avatar dokumentiert mit 72 × 72 öffentlich und 96 × 96 im Editor.
- Kontextuelle Hilfe-/Bug-Icons und Benachrichtigungsglocke gelten optisch als eingefroren, solange kein konkreter Fehler vorliegt.
- Welcome-Splash nur einmal pro Browser über `localStorage`-Key `camperwolf.welcome-seen.v1`.
- Zentrale Icons, Labels und Übersetzungen; keine leicht auseinanderlaufenden Duplikate.
- DE/EN und Datumsformate brauchen vor V1 einen vollständigen Pass.
- Mobile und Desktop müssen systematisch getestet werden.
- Nutzer prüft sichtbare Änderungen gerne selbst im Browser; erwartete Veränderung beziehungsweise bewusst fehlende sichtbare Veränderung klar benennen.

## 11. Sonderfälle und Edge Cases

- `unknown` niemals automatisch als `false`/„Nein“ behandeln.
- `not_provided` bei Öffnungszeiten ist eine bewusste Information, nicht bloß unbekannt.
- Saison kann über den Jahreswechsel laufen.
- Bei überlappenden Öffnungs-/Preisperioden dürfen Restsegmente nicht verloren gehen.
- Duplikatsuche beim Bearbeiten muss den eigenen Platz ausschließen.
- Zwei parallele erste Reviews desselben Nutzers müssen DB-nah dedupliziert werden.
- Übersetzte Labels dürfen nicht als technische XP-Dedupe-Schlüssel dienen.
- Eine nicht öffentliche Review darf weder in öffentlicher Liste noch als reportbares sichtbares Ziel erscheinen.
- Historischer Monatswert und aktueller Score haben bei späterer Review-Entfernung bewusst unterschiedliche Semantik.
- Historie eines entfernten Platzes darf bestehen bleiben; konkrete Darstellung/Filter muss noch im Code geprüft werden.
- `show_gamification=false` beendet nicht die XP-Buchung.
- Alias-Auflösung darf die permanente CW-ID nicht ungültig machen.
- Alte Migrationen mit verworfenem Review-Schema bleiben als Historie im Repository, obwohl spätere Migrationen die Tabellen entfernen.
- Ein frischer Datenbankaufbau muss die gesamte Migrationskette durchlaufen können.
- MySQL-DDL-Rollback nicht blind als atomar annehmen.
- Demo-/Seed-Daten können veraltete Konzepte spiegeln und sind keine Produktspezifikation.
- Der Foto-MVP darf nicht auf `review_photos` aufgebaut werden.
- Offizielle Imports müssen konfliktbewusst und idempotent sein, sonst drohen Überschreibungen oder Update-Ping-Pong.

## 12. Bekannte Probleme / technische Schulden

- Zwei bekannte `UserProfileTest`-Fehler durch Locale-/Text-Erwartung; fachlich gewünschte Sprache vor Fix klären.
- 35 bestehende Pint-Probleme in `composer ci:check`.
- Review-„Mehr laden“ ist keine echte Pagination.
- Unvollständige DE/EN-Abdeckung und harte deutsche Texte/Datumsformate wahrscheinlich vorhanden.
- Feature-/Kategoriezuordnung noch provisorisch.
- Legacy-`place_prices` kann parallel zur strukturierten Preislogik existieren.
- Alte Draft-Views/-Routen können noch vorhanden sein, obwohl Controller zum neuen Flow leiten.
- Profilfeldreste wie `handle_finalized_at` und Robustheit der Alias-Migration prüfen.
- Badge-Katalog/Übersetzungen eventuell unvollständig.
- Potenzielle N+1-Abfragen, fehlende Indizes, große Karten-/Duplikatabfragen, fehlendes Caching und Pagination.
- Foto-Moderation fehlt.
- Owner-Verifizierung, Community-Helfer und Check-in fehlen.
- Feldgenaue Priorität externer Quellen fehlt.
- Rechte, Rate Limits, Uploads und Missbrauchsschutz brauchen systematischen Security-Pass.
- Datenschutz, Rechtstexte, Auskunft/Löschung und Produktionsbetrieb sind nicht als abgeschlossen dokumentiert.
- Backup, Restore-Test, Deployment und Monitoring offen.
- Vollständiger Status projektbezogener Jobs unklar; Code prüfen.
- Die konsolidierte Kontextdatei enthält dieses neue Handoff 03 noch nicht; später kontrolliert einarbeiten, nicht einfach anhängen.

## 13. Offene Punkte

### Kurzfristig

- Auf jedem Arbeitsrechner aktuellen `main` holen und Review-Cleanup/Migrationen bei Bedarf verifizieren.
- Zwei bekannte Profiltestfehler gegen aktuelle Locale-Anforderung prüfen.
- Nächsten aktiven V1-Block mit dem Nutzer festlegen.
- Falls Foto-MVP: bestehende Foto-Tabellen, `Photo`, Routen, Upload-Konfiguration und Berechtigungen vollständig lesen.
- Dieses Handoff 03 bei der nächsten Kontextpflege in `PROJECT_CONTEXT.md` konsolidieren und dort als eingearbeitet markieren.

### Später für V1

- Foto-Upload, Platzzuordnung, Sichtbarkeit und Moderation.
- Echte Review-Pagination.
- Duplikat-Merge.
- Fachliche Abschlussprüfung von Platztypen/Merkmalskatalog.
- Vollständige DE/EN-, Mobil/Desktop-, Accessibility- und Fehlerzustandsprüfung.
- Performance-/Index-Pass mit realistischen Datenmengen.
- Rollen/Rechte, Rate Limits, Upload- und Anti-Abuse-Härtung.
- Recht, Datenschutz, Löschung/Auskunft.
- Deployment, Backup/Restore, Monitoring.
- Offizielle/Open-Data-Quellen als launchnaher finaler V1-Schritt.

### Nach V1 / Nice-to-have

- Check-in und Verified Visit, eventuell spätere Bewertungsgewichtung.
- Besitzer-Verifizierung und Owner-Bereich.
- Community-Helfer.
- Eigenständiges Event-Modul.
- Social Links nur nach neuer bewusster Entscheidung.
- KI-Zusammenfassungen.
- Komplexere Regeln und Feiertagsausnahmen.
- Weitergehende PWA/native App.
- Komfortablere Moderationsvergleiche und reichere Badge-Statistiken.

## 14. Nächste konkrete Schritte

1. **Am exakten Übergabepunkt ansetzen:** `docs/PROJECT_CONTEXT.md` und dieses neuere `docs/SESSION_HANDOFF_03.md` lesen; danach aktuellen `main` und neue Commits seit `893f63c` prüfen.
2. **Technischen Stand lokal bestätigen**, falls auf dem betreffenden Rechner noch nicht erfolgt:
   ```bash
   git pull
   php artisan migrate
   php artisan optimize:clear
   php artisan test --filter=PlaceReviewServiceTest
   ```
3. **Bekannte Baseline getrennt prüfen:** Sind die zwei `UserProfileTest`-Fehler weiterhin reine Locale-Erwartungen? Nicht nebenbei Produkttexte ändern oder alle 35 Pint-Probleme in einen Fachcommit mischen.
4. **Nächsten V1-Block mit dem Nutzer bestimmen.** Empfehlung, aber nicht final: Foto-MVP.
5. **Bei Foto-MVP zuerst lesen:** `app/Models/Photo.php`, Migrationen für `photos` und `place_photos`, aktuelle Foto-/Profilcontroller, Upload-Konfiguration, Policies/Rechte und relevante Views. Dann fachlich Upload, Zuordnung, Moderationsstatus, Sichtbarkeit und Entfernen definieren.
6. In kleinen reversiblen Commits implementieren, gezielte Tests ergänzen und anschließend Browserprüfung auf Mobil/Desktop durchführen.

Wenn der Nutzer ausdrücklich Search/Map, Duplikat-Merge oder einen anderen Block priorisiert, gilt diese neue Entscheidung statt der Foto-Empfehlung.

## 15. Zusammenarbeit / Session-Charakter

### Kommunikationsstil

- Deutsch, knapp bis mitteldetailliert, faktenorientiert und kollegial.
- Tippfehler des Nutzers inhaltlich verstehen; nicht unnötig korrigieren oder thematisieren.
- Mit dem Ergebnis beginnen und technische Details nur so weit ausführen, wie sie für Entscheidung oder Umsetzung relevant sind.
- Der Nutzer möchte keine bloße Zustimmung. Kritischer Widerspruch ist ausdrücklich erwünscht, wenn eine Idee Datenqualität, Sicherheit, UX oder Wartbarkeit verschlechtert.

### Entscheidungsweise

- Produktlogik wird bei Bedarf kurz explorativ diskutiert.
- Sobald Auswirkungen klar sind, soll eine konkrete Empfehlung folgen.
- Nach einer bewussten Entscheidung nicht in jeder späteren Session wieder alle Alternativen aufrollen.
- Neuere Entscheidungen dürfen alte Dokumentation ersetzen; die Historie bleibt nur dort sichtbar, wo das Verständnis des Wechsels wichtig ist.
- Theoretische Best Practices sind kein Selbstzweck. Bewusst getroffene Produktentscheidungen werden nur mit einem konkreten Problem neu bewertet.

### Technische Arbeitsweise

- Vor Änderungen zuerst den aktuellen `main` und die unmittelbar betroffenen Dateien lesen.
- Dokumentation liefert Absicht/Kontext; Code und Migrationen liefern den technischen Iststand. Widersprüche offen benennen.
- Kleine, nachvollziehbare und über Git reversible Commits bevorzugen.
- Keine unnötigen Großrefactorings eines funktionierenden Systems.
- Testumfang ehrlich benennen. Nicht „alles funktioniert“ behaupten, wenn nur ein gezielter Test gelaufen ist.
- Bestehende Test-/Style-Schulden von neuen Regressionen trennen.
- Nach Migrationen oder Konfigurationsänderungen konkrete lokale Befehle geben.
- Der Nutzer führt häufig die praktische/visuelle Browserprüfung durch. Betroffene Seiten und erwartetes Verhalten klar nennen.

### Was in dieser Session gut funktioniert hat

- Vollständiges Lesen beider Handoffs vor der Konsolidierung.
- Konflikte explizit nach Zeitachse auflösen statt Texte zusammenzukopieren.
- Das nicht final bestimmte nächste Teilprojekt als „unklar/nicht beschlossen“ markieren, statt eine erfundene Entscheidung zu setzen.
- Bekannte Testprobleme ehrlich in die zentrale Dokumentation übernehmen.
- Direkter Commit der neuen Dokumentation auf `main` mit klarer Commit-Nachricht.
- Keine Veränderung der Archiv-Handoffs.

### Reibung vermeiden

- Während technischer Arbeit kurze, verständliche Statusupdates statt schwer nachvollziehbarer Tool-Aktivität geben. Der Nutzer hatte zuvor angemerkt, dass das Zuschauen an umfangreicher technischer Arbeit etwas „gruselig“ wirkte, obwohl Ergebnis und Funktion passten.
- Keine überlange Selbsterklärung der Werkzeuge; wichtiger sind Ziel, Wirkung und Prüfergebnis.
- Nicht unnötig bei jeder sicheren Kleinigkeit rückfragen. Bei echten Produktentscheidungen, Datenrisiko oder größerem Scope dagegen klar abstimmen.
- Git/GitHub als Sicherheitsnetz nutzen, aber nicht als Rechtfertigung für unvorsichtige oder unnötig breite Änderungen.

## 16. Dinge, die der nächste Chat NICHT tun sollte

- `PROJECT_CONTEXT.md` ignorieren und nur aus einem alten Handoff arbeiten.
- Dieses Handoff einfach an `PROJECT_CONTEXT.md` anhängen; neue Informationen konsolidieren und Dopplungen entfernen.
- Bereits finale Regeln ohne konkreten neuen Grund neu diskutieren.
- Alte dynamische Review-Fragen, `review_photos`, Helpful Votes oder das Legacy-Review-Schema reaktivieren.
- Einen langen Platz-Wizard oder `QUICK_FEATURES` zurückbringen.
- Alias an die Stelle der permanenten CW-ID setzen.
- Events wieder als Platztyp behandeln.
- `unknown` in „Nein“ umdeuten.
- Entfernte Review und entfernten Platz bei historischen Scores gleich behandeln.
- Verified Visits in V1 plötzlich gewichten.
- Externe API-Daten blind über Nutzer-/Owner-/Admin-Daten schreiben.
- Level/XP als Berechtigungs- oder Vertrauensscore verwenden.
- Funktionierenden Query-Builder-/Service-Code nur aus Stilgründen groß auf Eloquent umbauen.
- Alte Migrationen nachträglich verändern, um spätere Schemaentscheidungen „aufzuräumen“.
- Alle Pint-Probleme oder alte Tests heimlich in einen fachlich anderen Commit ziehen.
- Demo-/Seed-Daten als verbindliche Produktspezifikation behandeln.
- Erfolg von Tests, Migration oder Browserablauf behaupten, ohne ihn tatsächlich geprüft zu haben.
- Die bestehenden Handoffs verändern oder löschen.

## 17. Schnellstart für den nächsten Chat

1. Zuerst `docs/PROJECT_CONTEXT.md` vollständig lesen.
2. Danach dieses neuere `docs/SESSION_HANDOFF_03.md` lesen; es ist noch nicht in der Kontextdatei eingearbeitet.
3. `main` und Commits nach `893f63c` prüfen.
4. Diese Session änderte nur Dokumentation, keinen Anwendungscode.
5. Letzter technischer Abschluss: Review-Cleanup in `1321cf4`.
6. Letzter Dokumentationsabschluss: `PROJECT_CONTEXT.md` in `893f63c`.
7. Vollständige Platzpflege ist umgesetzt und praktisch bestätigt.
8. Reviews nutzen fünf feste gleich gewichtete Dimensionen.
9. Gültige Review-Tabellen: `place_reviews`, `place_review_versions`, `place_review_reports`.
10. Legacy-Review-Tabellen werden durch `2026_09_20_220000_drop_legacy_review_tables.php` entfernt.
11. Entfernte Reviews zählen ab Entfernung nicht aktuell; vergangene Monatswerte bleiben.
12. Entfernte Plätze/Projekte dürfen historische Scores weiter beeinflussen.
13. Zwei-Schritt-Platzanlage bleibt; Schritt 2 optional; Duplikate warnen nur.
14. CW-ID bleibt dauerhaft; Alias ist Zusatz.
15. XP stammt aus `xp_ledger`; Level ist kein Trust Score.
16. Externe Datenquellen sind eigene Akteure und dürfen manuelle Daten nicht blind überschreiben.
17. Amtlicher Import ist ein launchnaher finaler V1-Schritt.
18. Foto-Grundlage existiert, vollständiger Foto-MVP fehlt.
19. Der nächste aktive V1-Block ist noch nicht final ausgewählt; Foto-MVP ist nur die Empfehlung.
20. Vor Foto-Arbeit `Photo`, `photos`, `place_photos`, Rechte und Uploadpfad prüfen; `review_photos` nicht reaktivieren.
21. Bekannte Testbaseline: 98 bestanden, zwei Locale-bezogene Profiltests fehlgeschlagen.
22. Bekannte Style-Baseline: 35 Pint-Probleme.
23. Keine neuen Tests wurden für die reine Dokumentationsänderung ausgeführt.
24. Nutzer bevorzugt direkte Antworten, konkrete Empfehlungen und ehrlichen kritischen Widerspruch.
25. Kleine reversible Commits; Code/Doku-Widersprüche offen benennen.

## 18. Handoff-Nachricht an den nächsten Chat

Du übernimmst ein laufendes Camperwolf-Projekt. Lies zuerst `docs/PROJECT_CONTEXT.md` vollständig und danach dieses neuere `docs/SESSION_HANDOFF_03.md`. Die Kontextdatei ersetzt die wiederholte Rekonstruktion aus den älteren Handoffs; dieses Handoff ergänzt sie um den letzten Dokumentationsstand und muss bei der nächsten Konsolidierung eingearbeitet werden. Prüfe für technische Arbeiten trotzdem immer den aktuellen Code, die Migrationen und Tests. Wenn Code und Dokumentation abweichen, benenne den Widerspruch, statt stillschweigend eine Seite zu wählen.

Der zuletzt abgeschlossene technische Schwerpunkt war die vollständige Platzpflege mit anschließendem Review-Cleanup. Die letzten fachlichen Entscheidungen waren: Entfernte Reviews zählen ab Entfernung nicht mehr zum aktuellen Score, historische Monatswerte bleiben; moderativ entfernte Plätze/Projekte dürfen historische Scores weiter beeinflussen. Diese Entscheidungen und das aktuelle feste Review-Schema nicht erneut grundsätzlich aufrollen.

Als Nächstes prüfst du den aktuellen `main` und gegebenenfalls den lokalen Migrations-/Review-Teststand. Danach legst du mit dem Nutzer den nächsten V1-Block fest. Ohne neue Priorität ist die Bestandsaufnahme und Planung des Foto-MVP auf Basis von `Photo`, `photos` und `place_photos` der sinnvollste Einstieg. Arbeite klein, nachvollziehbar und reversibel; reaktiviere keine Legacy-Konzepte nur deshalb, weil noch alter Code oder alte Migrationen sichtbar sind.
