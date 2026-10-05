# Session Handoff

## 1. Projektüberblick

Camperwolf.de ist ein Community-geführtes Portal zur Erfassung, Pflege, Suche und Bewertung von Camping-, Stell-, Park-, Rast- und Serviceplätzen. Deutschland ist der Startmarkt; Datenmodell, technische Slugs und UI-Struktur sollen international erweiterbar bleiben.

Die Kernidee ist kein klassisches kommerzielles Camping-Verzeichnis, sondern eine gemeinschaftlich gepflegte Datenbasis mit transparenter Herkunft, nachvollziehbarer Historie, Moderation und einem möglichst niedrigen Einstieg für Nutzerbeiträge.

Übergeordnetes Produktziel für V1:

- Nutzer können Plätze finden.
- Nutzer können neue Plätze schnell vorschlagen.
- Nutzer können bestehende Plätze vollständig korrigieren und ergänzen.
- Nutzer können Rezensionen/Bewertungen abgeben.
- Nutzer können Profile, Favoriten, Benachrichtigungen und die nötigen Community-Funktionen nutzen.
- Datenänderungen laufen nachvollziehbar über Moderation, sofern der Nutzer keine privilegierte Direktbearbeitung besitzt.
- Deutsch und Englisch.
- Desktop und Mobile.
- Spätere Module dürfen vorbereitet sein, sollen V1 aber nicht unnötig aufblähen.

Grundprinzipien:

- Qualität statt Masse, aber die Hürde zum Anlegen eines fehlenden Platzes bewusst niedrig halten.
- Koordinaten sind die primäre geografische Wahrheit; Adresse ist ergänzend.
- Fehlende strukturierte Daten bedeuten unbekannt, nicht automatisch nein.
- Geld darf Score, Ranking oder Sichtbarkeit nicht beeinflussen.
- Referenzdaten eher deaktivieren als hart löschen.
- Community-Änderungen sollen moderierbar und auditierbar bleiben.
- Externe/API-Daten sollen später als eigene Quelle/Actor sichtbar sein und unabhängige Community-/Owner-/Admin-Daten nicht blind überschreiben.
- UI soll kompakt bleiben; Icons zentral und konsistent.
- Technische Identifikatoren stabil, englisch/lowercase/kebab-case; DB snake_case.
- Bestehende funktionierende Architektur nicht ohne konkreten Nutzen umbauen.

Repository:

- GitHub: WulfieWolf/camperwolf
- Branch: main
- Lokaler Pfad typischerweise: D:\Dropbox\Eigene Dateien\Dokumente\Server\Camperwolf
- Lokale URL: camperwolf.test

Stack:

- Laravel 13
- Livewire 4
- Flux UI / Tailwind
- PHP 8.4
- MySQL 8
- Node 24 / npm 11
- Herd lokal
- Leaflet + OpenStreetMap für Karten

Wichtig: main ist die maßgebliche technische Quelle. Vor Änderungen betroffene Dateien immer neu aus dem Repository lesen. Nicht aus Erinnerung überschreiben.

---

## 2. Aktueller Gesamtstand

### 2.1 Nutzerprofile

Der Profilbereich wurde in dieser Session für V1 weitgehend fertiggestellt und vom Nutzer optisch geprüft.

Aktueller Identitätsansatz:

- Jeder Nutzer besitzt dauerhaft eine automatisch erzeugte Camperwolf-ID im Format CW-XXXXX.
- Ein optionaler öffentlicher Alias ist davon getrennt.
- Der Alias wird in user_profiles.public_alias gespeichert.
- alias_finalized_at markiert die einmalige Finalisierung.
- Die permanente CW-ID bleibt erhalten und wird nicht mehr durch den Alias ersetzt.
- Beide URL-Varianten sollen weiterhin auf dasselbe Profil auflösen.
- User::publicName() bevorzugt Alias, danach CW-ID, danach Accountname.
- Links aus Reviews/Profilflächen bevorzugen den Alias, alte CW-ID-Links bleiben funktionsfähig.

Wichtige Dateien:

- app/Models/User.php
- app/Models/UserProfile.php
- app/Services/PublicHandleService.php
- app/Http/Controllers/UserProfileController.php
- app/Http/Controllers/UserProfilePhotoController.php
- resources/views/users/profile.blade.php
- resources/views/pages/settings/community-profile.blade.php
- resources/views/components/user-status-avatar.blade.php
- lang/de/community_profile.php
- lang/en/community_profile.php
- tests/Feature/UserProfileTest.php

Wichtige Profilentscheidungen:

- Profilbild, Bio, Heimatort, Alter, Geschlecht, Fahrzeug etc. besitzen granulare Sichtbarkeit.
- Sichtbarkeit: public / registered / private.
- Geburtsdatum wird nie direkt öffentlich ausgegeben; nur berechnetes Alter kann sichtbar sein.
- show_gamification blendet nur Darstellung aus, XP werden weiter gesammelt.
- show_join_date ist steuerbar.
- Fahrzeugauswahl kommt aus dem zentralen vehicle_types-Katalog, nicht mehr aus einer getrennten hartcodierten Liste.
- Social Links bleiben vorerst bewusst deaktiviert.

In dieser Session behoben:

- Alias und CW-ID getrennt.
- Profil-/Settings-Texte DE/EN lokalisiert.
- XP-Gruppierungsbeschreibungen lokalisiert.
- Review-Links konsistent auf Alias/CW-ID umgestellt.
- Profilbild auf der öffentlichen Profilseite mobil auf 72x72 begrenzt.
- Profilbild im Editor auf 96x96 begrenzt.
- Button "Profil bearbeiten" kompakt mit Stift-Icon.
- Livewire-Breitenwechsel nach Speichern behoben: Settings-Layout bekommt wide explizit als Prop statt request()->routeIs().
- Mobile-Profilansicht per Screenshot geprüft; Nutzer bestätigte zuletzt: "jetzt siehts gut aus".

Relevante Profil-Commits aus dieser Session:

- e315bfa8... Migration Alias/CW-ID trennen
- 40711fdf... User publicName/Public URLs
- b7174e38... Controller Alias + Fahrzeuglabel
- e3ae3dc8... Community Profile Settings Alias + zentrale Fahrzeuge
- 87d5bf01... Profiltests
- b8fc5cb9... Fix Fahrzeugoptions-Query
- fc00a7f2... mobiler Profilkopf
- 408b1ae8... stabiles Settings-Layout
- 1ac62632... kompakter Profil-bearbeiten-Button

UserProfileTest wurde nach dem Fahrzeugquery-Fix vom Nutzer erfolgreich ausgeführt.

### 2.2 Reviews / Score

Das Review-Konzept gilt als fachlich weitgehend final und ist technisch umgesetzt/härtet worden.

FINAL / AKTUELL GÜLTIG:

Fünf Pflichtdimensionen, jeweils 1–5:

1. Sauberkeit
2. Funktionalität
3. Zustand
4. Sicherheit
5. Nutzbarkeit

Regeln:

- Nur registrierte Nutzer.
- Optionaler Freitext; wenn vorhanden 20–2000 Zeichen.
- Gesamtscore = einfacher Mittelwert der fünf Dimensionen, gleich gewichtet.
- Eine aktuelle Rezension pro Nutzer/Platz.
- Frühere Versionen bleiben historisch.
- Gültigkeit 12 Monate.
- 30 Minuten Korrekturfenster innerhalb derselben Version.
- Danach neue Version erst nach 28 Tagen Cooldown.
- Standard-Sortierung neueste zuerst.
- Monatlicher historischer Scoreverlauf vorhanden.
- Reviewer kann Profilbild, Level, Titel usw. gemäß Sichtbarkeit zeigen.
- Verified Visit/Check-in/GPS-Gewichtung ausdrücklich nach V1.
- Durch Moderation entfernte Rezensionen dürfen weiterhin im historischen Score der Vergangenheit enthalten sein; ab Entfernung nicht mehr im aktuellen Score.

Bekannte Review-Tech-Schuld:

- "Mehr laden" ist aktuell kein echtes Append/Pagination-Modell, sondern Reload mit erhöhtem Limit.
- Das sollte vor Release auf echte, begrenzte Pagination überprüft werden.

Im aktuellen Repository gibt es nach dieser Session zusätzlich einen neueren Commit zur Review-Härtung:
- 1321cf4046e7c0cc40b3b00f18ee690a78a1dc4b – "Clean up and harden review system"

Da dieser Commit nach den sichtbaren Arbeitsschritten dieser Session liegt, bei weiterer Review-Arbeit zuerst aktuellen main-Code lesen.

### 2.3 Öffnungszeiten

Öffnungszeiten wurden vor diesem aktiven Teilprojekt bereits umfangreich neu aufgebaut.

Aktuelles Modell:

- recurring annual periods, also jahresunabhängige wiederkehrende Zeiträume.
- Ganzjährig und saisonale Perioden.
- Neue Bereiche überschreiben nur den tatsächlich überlappenden Abschnitt.
- Bestehende Restsegmente bleiben erhalten.
- Jahreswechsel/Wrapping unterstützt.
- Beispiel: 01.01–01.07 + neuer Zeitraum 01.02–31.03 ergibt 01.01–31.01 alt, 01.02–31.03 neu, 01.04–01.07 alt.
- Ein neuer ganzjähriger Zeitraum ersetzt alle bestehenden saisonalen Bereiche.
- Community-Vorschläge verändern Live-Daten erst bei Moderatorfreigabe; beim Einreichen wird nichts vorab gesplittet.
- Moderation zeigt Impact-Preview.
- Virtual suggestable field: opening_hours.period_schedule.
- Parent-Period ist Source of Truth; konkrete opening_hours-Zeilen hängen darunter.

Wichtige Semantik:

- "Unbekannt" = Camperwolf/Nutzer kennt die Information nicht. Nicht als affirmative Angabe persistieren; keine XP.
- not_provided = Betreiber macht keine Angabe.
- Öffentliche Bezeichnung: "Keine Angabe des Betreibers".
- not_provided zählt als nützliche Information und kann XP/Badge-Fortschritt geben.

Wichtige Dateien/Services:

- app/Services/OpeningHoursPeriodService.php
- place/opening-hours Controller und Views
- ChangeRequestApplyService
- suggestable_fields
- opening_hour_periods
- opening_hours

### 2.4 Strukturierte Preise

Preise wurden ebenfalls bereits auf ein strukturiertes V1-Modell umgestellt.

FINAL / AKTUELL GÜLTIG:

- unabhängig von Öffnungszeiten.
- Product -> Variant -> optionaler Display Name.
- saisonale wiederkehrende Preisperioden pro Angebot.
- mehrere Preiszeilen/Zuschläge.
- optionales linked_offer_id.
- optionale Fahrzeuglängen- und Altersbereiche.
- Deposit/refundable.
- Conditions-Text.
- Feature-linked prices möglich.
- generisches Produkt "Stellplatz" zusätzlich zu fahrzeugspezifischen Stellplatzprodukten.

Wichtige Tabellen:

- price_products
- price_product_variants
- price_billing_units
- place_price_offers
- place_price_periods
- place_price_lines

Status u.a.:

- fixed
- from
- included
- free
- on_request
- unknown

Billing Units u.a.:

- per night
- day
- hour
- person-night
- person-day
- pitch-night
- vehicle-night
- use
- week
- month
- year
- kWh
- liter
- one-time

Aktuelle Profil-Darstellung wurde in dieser Session geändert:

FINAL / AKTUELL GÜLTIG:

- Im Preisblock nicht jede mögliche Preisart mit "Noch keine Angabe" auflisten.
- Nur tatsächlich vorhandene Preisinformationen anzeigen.
- Wenn gar keine Preisinformation existiert, genau ein allgemeiner Zustand wie "Noch keine Preisinformationen vorhanden".
- Structured Prices haben Vorrang; vorhandene Legacy-Preise können weiterhin unter "Weitere bisherige Preisangaben" erscheinen.

Commit:
- ff04c69cbc56169938d491e0396f77e6224122e6 – "Simplify profile feature and price displays"

Offene Fachfrage:
- structured price "unknown" zählt aktuell noch als persistierte nützliche Info. Das unterscheidet sich von Öffnungszeiten. Falls das wieder aufgegriffen wird, explizit entscheiden, nicht still angleichen.

### 2.5 Fahrzeugtypen / Rastplatz-Katalog

Für Rastplatz-/Autohof-Daten wurden Fahrzeugtypen und die Kategorie "Tanken & Rast" erweitert.

Neue/angepasste vehicle_types u.a.:

- car -> DE "PKW"
- motorcycle
- small-van
- light-truck 3,5–7,5 t
- heavy-truck über 7,5 t
- truck-combination / Sattelzug / Lastzug
- coach / Reisebus

Feature-Kategorie:
- fuel-rest-area
- DE "Tanken & Rast"
- EN "Fuel & rest area"

Darin u.a.:

- fuel-station
- fuel-petrol-e5
- fuel-petrol-e10
- fuel-super-plus
- fuel-diesel
- fuel-premium-diesel
- fuel-adblue
- fuel-lpg
- fuel-cng
- fuel-hydrogen
- fuel-truck-pump
- ev-charging
- rest-convenience-store
- rest-hotel
- rest-workshop
- rest-tire-service
- rest-car-wash
- rest-truck-wash
- rest-secure-truck-parking
- rest-trucker-lounge
- rest-atm
- rest-vending-machines
- rest-picnic-area
- rest-dog-area
- rest-laundry
- rest-emergency-phone

Wichtig:
- Diese aktuelle Merkmals-/Kategorie-Struktur ist für V1 nutzbar, aber noch NICHT final.
- Nutzer möchte kurz vor Launch den gesamten Merkmalskatalog bewusst noch einmal durchgehen, ggf. neu strukturieren und Kategorien stärker spezialisieren.
- Diese spätere Pre-Launch-Runde soll ausdrücklich auch die Zuordnung "welche Kategorie gehört standardmäßig zu welchem Platztyp" prüfen.
- Bis dahin die momentane Zuordnung nicht unnötig perfektionieren.

### 2.6 Platztypen V1

Aktuelle V1-Typen:

- Campingplatz
- Wohnmobilstellplatz
- Zeltplatz
- Parkplatz
- Rastplatz / Autohof
- Freier Stellplatz
- Servicestation
- Camping & Outdoor

Bedeutung:

- Platztyp = Was ist dieser Ort grundsätzlich?
- Merkmale = Was gibt es dort konkret?
- Übernachten erlaubt/geduldet/verboten gehört NICHT in den Platztyp, sondern ist separate Eigenschaft/Legalstatus.

Erläuterungen:

- Campingplatz: klassischer Platz für Wohnmobile, Wohnwagen, Zelte usw.
- Wohnmobilstellplatz: dedizierter Platz für Wohnmobile/Camper, oft Parkplatzcharakter.
- Zeltplatz: speziell für Zelte, z.B. Trekking-, Jugend-, Pfadfinderplätze.
- Parkplatz: klassischer Parkplatz; Übernachten separat bewerten.
- Rastplatz/Autohof: Raststätte, Rastanlage, Autohof, LKW-orientierte Reise-/Pausenplätze.
- Freier Stellplatz: einfacher Stellplatz außerhalb klassischer Camping-/Parkplatzstruktur, z.B. Wiese/Hof/See/Industriegebiet.
- Servicestation: primär Dienstleistungen wie Wasser, Entsorgung, Wasch-/Werkstattservice.
- Camping & Outdoor: dauerhafter campingbezogener Fachhandel/Werkstatt/Anlaufpunkt.

Legacy-Typen:

- stay
- stay-service
- service

Diese bleiben für historische Daten im DB-Bestand, sollen für neue Einträge aber nicht mehr auswählbar sein.

Seeder/Migration:

- database/seeders/PlaceTypeSeeder.php
- database/migrations/2026_09_20_161000_expand_v1_place_type_catalog.php

### 2.7 V2 Event-Idee

FINAL FÜR ROADMAP, NICHT V1:

Temporäre Events sollen später nicht als normaler Place-Type missbraucht werden, sondern als eigenes Modul.

Beispiele:

- Jahrmärkte
- Stadtfeste
- Märkte
- Festivals
- Messen
- lokale Veranstaltungen

Gedanke:

- eigener Event-Datensatz mit Start/Ende, Position, Kategorie.
- optionaler separater Karten-Layer "Events in der Umgebung".
- Reise-Use-Case: "Wenn wir schon hier übernachten, was ist gerade in der Nähe los?"
- Event kann ggf. mit einem bestehenden Camperwolf-Platz verknüpft sein, bleibt aber selbst kein dauerhafter Stellplatz.

---

## 3. Aktuell laufendes Teilprojekt

### Ziel

Aktiver V1-Block: Platz anlegen + vollständige Platzpflege nach Freigabe.

Der Fokus wurde in dieser Session von "bestehenden Wizard weiterpolieren" auf einen bewusst vereinfachten Nutzerfluss umgestellt.

### Aktueller Soll-Workflow beim Anlegen

FINAL / AKTUELL GÜLTIG:

#### Schritt 1 – Platz benennen und positionieren

Minimal notwendige Pflichtinformationen:

- Name
- Platztyp
- Position / Koordinaten

Duplikatprüfung bereits hier.

Ein Platz kann nach Schritt 1 direkt zur Prüfung eingereicht werden.

#### Schritt 2 – Merkmale ergänzen, optional

- Nicht einzelne hartcodierte Quick-Features auswählen.
- Nur nach für den Platztyp relevanten Kategorien filtern.
- Innerhalb dieser Kategorien ALLE Merkmale anzeigen.
- Nutzer muss nichts ausfüllen.
- Nutzer kann ohne Änderung direkt zur Prüfung einreichen.
- "Unbekannt" ist völlig okay.
- Kein langer Detailwizard vor Einreichung.

Danach:

- Platz wird NICHT "angelegt" als wäre er bereits live.
- Rückmeldung sinngemäß:
  "Der Platz wurde zur Überprüfung weitergegeben. Du wirst informiert, sobald er freigeschaltet wurde."
- Erst nach Freigabe normale Detailpflege am veröffentlichten Platz.

### Warum dieser Umbau?

Produktentscheidung des Nutzers:

- Neue Plätze sollen möglichst schnell eingetragen werden können.
- Zu viele Details vor Einreichung erhöhen Hemmschwelle und Abbruchwahrscheinlichkeit.
- "Platz überhaupt erfassen" und "Platz vollständig dokumentieren" sind zwei verschiedene Aufgaben.
- Die Community kann nach Freigabe weitere Details ergänzen.
- Optionaler Schritt 2 erlaubt Nutzern, die gerade vor Ort sind, schnell ein paar Merkmale mitzugeben, ohne sie zu zwingen.

### Duplikatprüfung

FINAL / AKTUELL GÜLTIG:

Duplikatprüfung berücksichtigt:

- veröffentlichte Plätze
- Plätze mit pending / in Prüfung

Grund:
Zwei Nutzer können denselben realen Platz fast zeitgleich einreichen, während der erste noch nicht freigeschaltet ist.

Verhalten:

- Warnung, kein hartes Verbot.
- Zwei reale Plätze können nahe beieinander liegen.
- Bewertung über räumliche Nähe + Namensähnlichkeit.
- Aktuell werden Treffer im Umkreis bis 750 m aufbereitet.
- Beim Bearbeiten eines bestehenden Platzes kann exclude_place_id gesetzt werden, damit der Platz nicht als sein eigenes Duplikat erscheint.

Relevante Route/Controller:

- places.suggest.duplicates
- PlaceSuggestionController::nearbyDuplicates()

### Platztyp -> Feature-Kategorien

FINAL / AKTUELL GÜLTIGES PRINZIP:

Die gleiche Relevanzlogik gilt bei:

1. Schritt 2 beim Anlegen
2. Anzeige im Platzprofil
3. späterer Merkmalsbearbeitung

Regeln:

- Platztyp definiert Standardkategorien.
- Standardkategorien werden regulär angezeigt.
- Nicht-standardmäßige Kategorie wird im öffentlichen Profil trotzdem angezeigt, sobald mindestens ein Merkmal darin bekannt/gepflegt ist.
- Bearbeiter können über "Weitere Merkmale" auch alle übrigen Kategorien einblenden.
- Relevantes standardmäßig zeigen, Ungewöhnliches nicht verstecken.

Beispiel:
Ein Rastplatz zeigt standardmäßig Rastplatz-relevante Kategorien. Hat er zusätzlich Grauwasserentsorgung, soll die entsprechende Versorgungs-Kategorie ebenfalls sichtbar werden.

Service:
- app/Services/PlaceTypeFeatureService.php

Aktuelle Konfiguration ist provisional und wird vor Launch noch einmal fachlich überprüft.

### Schritt-2-Quick-Feature-Änderung dieser Session

Ursprünglich wurde eine kleine QUICK_FEATURES-Liste pro Platztyp gebaut.

Das erwies sich beim Test als zu stark eingeschränkt: Beim Rastplatz erschienen nur wenige Einzelmerkmale, obwohl "Tanken & Rast" viel mehr relevante Features enthält.

Veraltet:
- Einzelne Quick-Feature-Slugs pro Typ hart auswählen.

Aktuell:
- Nur Kategorien filtern.
- Alle Features dieser Kategorien anzeigen.
- Speicherung validiert ebenfalls alle sichtbaren Features dieser Kategorien.

Commits:
- ceec8c87880a7d15c0cd40ed40e389c0af2732a3 – category-based quick filtering
- 49ea21781fe82f10003f05ad71b5cbda3a28b154 – alle Features relevanter Kategorien speichern
- 51c17b564a491c425cc83fe7727ccdf76731c265 – Test angepasst

Der Nutzer testete dies und bestätigte: "sieht gut aus - funktioniert alles."

### Platzprofil: Feature-Zähler

Problem:
"x von y Merkmalen bekannt" zählte den gesamten globalen Feature-Katalog mit, auch versteckte/fachfremde Kategorien.

Entscheidung:
Nur zählen:

- Standardkategorien des Platztyps
- plus nicht-standardmäßige Kategorien, in denen bereits bekannte Daten existieren

Commit:
- 92615ea2f51c9cbd1b123c847c35e0bdde891fb9

### Platzprofil: unnötiger Edit-Link

Im Feature-Header gab es "Informationen ergänzen", der nur auf #features verlinkte und praktisch nichts bewirkte.

Entscheidung:
Entfernen; echte Editiermöglichkeiten sind:

- Stifte direkt an den Features
- "Weitere Merkmale"
- vollständiger Core-Edit-Workflow
- separate Editoren für Öffnungszeiten/Preise

Commit:
- ff04c69cbc56169938d491e0396f77e6224122e6

### Vollständige Editierbarkeit eines veröffentlichten Platzes

Der Nutzer hat ausdrücklich festgelegt:

FINAL / AKTUELL GÜLTIG:
Ein Platz muss zu 100 % editierbar bzw. als Änderungsvorschlag korrigierbar sein.

Nicht nur Beschreibung/Betreiber/Fahrzeugtypen, sondern auch:

- Name
- Platztyp
- Position / Koordinaten
- Legal-/Nutzungsstatus
- Öffnungsstatus
- Betreiber
- Stellplatzzahl
- Betriebsart
- Website
- Adresse
- Beschreibung
- Anfahrt
- Zugang
- Fahrzeugtypen
- Merkmale
- Öffnungszeiten
- Preise
- weitere Bereiche, sobald sie existieren

Die zentralen suggestable_fields für places.name, place_type_id, latitude, longitude, legal_status, opening_status existierten bereits.

Aktuelle Implementierung:

- PlaceInfoSuggestionController wurde um name, place_type_id, latitude, longitude, legal_status erweitert.
- Validierung, Change-Request-Queue und Type-Auswahl wurden ergänzt.
- suggest-info.blade.php besitzt einen Bereich "Grunddaten & Position".
- Leaflet-Karte erlaubt Positionskorrektur per Klick/Marker.
- Duplikatcheck läuft auch beim Editieren und schließt den aktuellen Place per exclude_place_id aus.
- globaler "Bearbeiten"/"Änderung vorschlagen"-Button im Platzprofil führt inzwischen auf places.info-suggest.edit, nicht mehr nur zu #features.
- zusätzliche Tests wurden im Repo ergänzt.

Wichtige Commits:

- 1bf927ad2b7a49e7d43df57a51152faea73993dc – core place data community-editable
- 54eaf5fedb109769c6ec085c6ddc33a0fcf913ec – Fix Validierungsplatzierung
- 42f884d6ff7367fa26eae544be9c667b269c96f6 – duplicate checks exclude edited place
- 2d77f24328d673ad19b1db22960fcd86c45c94e7 – complete core place edit workflow
- ee1f7a6a806d257528f5a9cd1627deb096643b0f – route place editing to complete workflows
- 69ddc80185db50c4cc5947f5664e157e9549b8a9 – tests complete place info suggestions
- 991fceb7b73d9500a81db9f2197d982c6c1121b4 – duplicate edit test

### Der aktuellste Arbeitsstand / genau dort fortsetzen

Zuletzt wurde der Pflege-/Profil-Workflow geprüft und verbessert.

Bereits umgesetzt:

- Feature-Zähler relevant gemacht.
- unnötigen Feature-Header-Link entfernt.
- Preisbox bereinigt.
- Core-Daten vollständig als Änderungsvorschlag editierbar gemacht.
- Positionsbearbeitung + Duplikatwarnung ergänzt.
- globalen Editbutton auf vollständigen Pflegeworkflow geroutet.
- Tests für PlaceInfoSuggestion und Duplicate Editing wurden im Repository ergänzt.

Wichtig: Der Nutzer hat diese allerletzten Core-Edit-Änderungen in diesem Chat noch NICHT per Browser bestätigt. Die zuvor getestete Anlegen-/Quick-Kategorien-Funktion war bestätigt. Der nächste Chat sollte deshalb zuerst den aktuellen main-Stand lesen und diese komplette Bearbeitung praktisch testen lassen bzw. selbst anhand der Tests prüfen.

Unmittelbar als Nächstes:

1. git pull / ggf. Tests auf dem Nutzerrechner.
2. Öffentlichen Platz öffnen.
3. "Änderung vorschlagen" / "Bearbeiten" öffnen.
4. Name, Typ, Rechtsstatus, Position ändern.
5. Prüfen, dass Duplikatwarnung bei verschobener Position funktioniert und der Platz sich selbst nicht meldet.
6. Vorschlag absenden und in Moderation kontrollieren.
7. Prüfen, dass nach Genehmigung alle Core-Felder korrekt angewendet werden.
8. Preise-Box auf Platz ohne Preisdaten und Platz mit Legacy-/Structured-Preisen prüfen.
9. Feature-Zähler und "Weitere Merkmale" an 2–3 Typen prüfen.
10. Danach entscheiden, ob der Pflegeblock V1-seitig abgehakt werden kann.

---

## 4. Chronologie der wichtigsten Entscheidungen

### A. Profilidentität: CW-ID vs Alias

Ausgangsproblem:
Die alte Umsetzung ersetzte public_handle durch einen benutzerdefinierten Alias.

Diskutierter Sollzustand:
Camperwolf-ID sollte dauerhaft bleiben; Alias ist nur öffentlicher Anzeigename/URL.

Entscheidung:
- CW-ID permanent.
- public_alias separat.
- alias_finalized_at separat.
- alte Alias-URLs und CW-ID-URLs auflösbar.

Grund:
Stabile Identität/Referenz, ohne auf nutzerfreundlichen Alias zu verzichten.

### B. Profil-Mobile-UI

Problem:
Profilbild war mobil zu groß; "Profil bearbeiten"-Button wirkte unpassend.

Entscheidung:
- öffentlicher Avatar 72x72
- Editoravatar 96x96
- Stift-Icon + Text
- kompakte natürliche Buttonbreite

Zusätzlich:
Settings-Layout sprang nach Livewire-Speichern von breit auf schmal.

Ursache:
request()->routeIs() ist im Livewire-Request nicht mehr dieselbe Seitenroute.

Lösung:
wide explizit als Layout-Prop übergeben.

### C. V1-Grenzen

Nutzer wollte V1 pragmatisch halten.

V1:
- Kernfunktion der Seite
- Nutzerbezogenes
- Daten abrufen
- Daten eingeben
- Daten pflegen
- DE/EN
- Desktop/Mobile

Explizit post-V1:
- Verified Visits/Check-in
- verified owners/Betreiberrechte
- Community-Helfer-Mehrheitsentscheidungen
- komplexere Social-/AI-/Native-App-Themen

### D. Platzanlage vereinfachen

Alter Ansatz:
Längerer mehrstufiger Wizard mit Details vor Einreichung.

Problem:
Zu hohe Hemmschwelle, Nutzer könnte sich von vielen Detailfragen abschrecken lassen.

Neue Entscheidung:
Zwei Schritte:
1. Name/Typ/Position
2. optionale Features

Einreichung kann bereits nach Schritt 1 erfolgen.

### E. Rückmeldung nach Einreichung

Veraltete Formulierung:
"Platz wurde angelegt."

Nutzer korrigierte ausdrücklich:
Nicht so formulieren, weil der Platz noch nicht live ist.

Aktuell:
"zur Überprüfung weitergegeben/eingereicht; du wirst informiert, sobald er freigeschaltet wurde."

### F. Platztypen stärker differenzieren

Alter Typ "Stellplatz mit Service" wurde als zu kryptisch empfunden.

Entscheidung:
Explizite, für Nutzer verständliche Typen:
Campingplatz, Wohnmobilstellplatz, Zeltplatz, Parkplatz, Rastplatz/Autohof, Freier Stellplatz, Servicestation, Camping & Outdoor.

### G. Events nicht mit Places vermischen

Diskussion:
Events/Festivals könnten unter kommerziellen Orten landen.

Entscheidung:
V2 eigenes Event-Modul, temporär, optionaler Kartenlayer.

### H. Feature-Relevanz nach Platztyp

Grundidee:
Nicht jeden Platz mit allen Kategorien überladen.

Entscheidung:
- Standardkategorien je Platztyp.
- Extra-Kategorien nur bei bekannten Daten oder über "Weitere Merkmale".
- gleiche Logik beim Anlegen, Bearbeiten, Anzeigen.

### I. Quick Features: Einzelmerkmale verworfen

Erster Implementierungsversuch:
Kleine manuell ausgewählte Liste mit wenigen Features je Typ.

Problem:
Bei Rastplatz fehlten viele offensichtlich relevante "Tanken & Rast"-Features.

Entscheidung:
Kategorie filtern, alle Features dieser Kategorie zeigen.

### J. Merkmalskatalog-Zuordnung vor Launch

Diskussion:
Aktuelle Kategorien sind teils allgemein und für Typzuordnung nicht perfekt.

Entscheidung:
Jetzt nicht überoptimieren.
Kurz vor Launch:
- gesamten Merkmalskatalog prüfen
- ggf. Features neu anlegen/umstrukturieren
- Kategorien spezialisieren
- Typ->Kategorie-Mapping finalisieren

### K. Preise auf dem Profil

Problem:
Preisbox listete viele mögliche Preisarten mit "Noch keine Angabe".

Entscheidung:
Nicht vorhandene Preispositionen nicht anzeigen.
Wenn nichts vorhanden:
ein allgemeiner Hinweis "Noch keine Preisinformationen vorhanden."

### L. Vollständige Editierbarkeit

Problem:
Der alte Info-Suggestion-Workflow konnte Name, Typ und Position nicht korrigieren.

Nutzerentscheidung:
Ein Platz muss zu 100 % editierbar sein.

Konsequenz:
Core-Daten und Positionskorrektur in den normalen Änderungsvorschlagsworkflow aufgenommen.

---

## 5. Final entschiedene Regeln und Konzepte

### FINAL / AKTUELL GÜLTIG – Platzanlage

- Zwei Schritte.
- Schritt 1: Name, Typ, Position.
- Schritt 1 reicht zur Einreichung.
- Schritt 2 ist optional.
- Schritt 2 zeigt alle Merkmale aus den für den Typ relevanten Kategorien.
- Nutzer kann Schritt 2 unverändert absenden.
- Keine lange Detailabfrage vor Moderation.
- Erst nach Freigabe normale Detailpflege.

### FINAL / AKTUELL GÜLTIG – Duplikate

- Published + pending prüfen.
- Pending ausdrücklich einbeziehen.
- Nähe ist Warnsignal, kein hartes Verbot.
- Bearbeitung schließt den aktuellen Platz selbst aus.

### FINAL / AKTUELL GÜLTIG – Platztypen

- Campingplatz
- Wohnmobilstellplatz
- Zeltplatz
- Parkplatz
- Rastplatz / Autohof
- Freier Stellplatz
- Servicestation
- Camping & Outdoor

Legal-/Übernachtungsstatus separat.

### FINAL / AKTUELL GÜLTIG – Feature-Anzeige

- Typ definiert Standardkategorien.
- Standardkategorien normal anzeigen.
- Nichtstandardkategorie bei bekannten Daten automatisch anzeigen.
- "Weitere Merkmale" öffnet Rest.
- "x von y" zählt nur relevante Standardkategorien + bekannte Extra-Kategorien.
- Kurz vor Launch gesamtes Mapping nochmals fachlich prüfen.

### FINAL / AKTUELL GÜLTIG – Preise

- Nur vorhandene Preisangaben anzeigen.
- Keine lange Liste leerer Preisarten.
- Wenn nichts bekannt: ein allgemeiner Leerzustand.

### FINAL / AKTUELL GÜLTIG – Pflege

- Ein veröffentlichter Platz muss vollständig korrigierbar sein.
- Normale Community-Nutzer reichen Änderungsvorschläge ein.
- Privilegierte Bearbeiter dürfen je nach Permission direkt bearbeiten, aber Audit bleibt erhalten.
- Core-Daten, Merkmale, Öffnungszeiten, Preise und alle weiteren Bereiche benötigen einen echten Workflow/Editor.

### FINAL / AKTUELL GÜLTIG – Reviews

- 5 Pflichtdimensionen, gleich gewichtet.
- Kein Luxus-/Featurecount-/Preis-Einfluss auf Qualitäts-Score.
- Historisierung bleibt.
- Verified Visit post-V1.

### FINAL / AKTUELL GÜLTIG – Öffnungszeiten

- recurring annual period model.
- delayed splitting until approval.
- Unknown nicht affirmative/persistierte Info.
- Betreiber-keine-Angabe separat.

### FINAL / AKTUELL GÜLTIG – Userprofil

- permanente CW-ID
- separater Alias
- Alias nur einmal finalisieren
- Sichtbarkeit granular
- zentrale vehicle_types
- Gamification-Darstellung optional, XP intern immer weiter

### FINAL / AKTUELL GÜLTIG – Events

- nicht V1
- V2 eigenes temporäres Event-Modul
- optionaler Kartenlayer

---

## 6. Veraltete oder verworfene Ansätze

### Veraltet: Alias ersetzt CW-ID

Alter Ansatz:
public_handle wurde durch Wunschalias überschrieben.

Warum verworfen:
Stabile Camperwolf-ID sollte dauerhaft erhalten bleiben.

Ersetzt durch:
public_handle = permanente CW-ID, public_alias separat.

Mögliche Altlast:
handle_finalized_at bleibt historisch/kompatibel noch im Schema. Neue Logik benutzt alias_finalized_at.

### Veraltet: langer Anlage-Wizard vor Einreichung

Alter Ansatz:
Schritt 1 Grunddaten, weiterer Detail-Schritt, Feature-Schritt, Review-Schritt.

Warum verworfen:
Zu hohe Einstiegshürde.

Ersetzt durch:
2 Schritte, Details erst nach Freigabe.

Mögliche Altlast:
Alte Routen/Views für draft details/review können noch existieren. Controller leitete alte Draft-Detail-/Review-Einstiege auf Schritt 2 um. Vor Cleanup prüfen, ob noch irgendwo verlinkt.

### Veraltet: kleine QUICK_FEATURES-Liste

Alter Ansatz:
pro Platztyp einige handverlesene Features.

Warum verworfen:
zu wenig vollständig, besonders Rastplatz.

Ersetzt durch:
Standardkategorien filtern, alle Features darin anzeigen.

### Veraltet: "Platz wurde angelegt"

Warum verworfen:
irreführend, weil Moderation noch aussteht.

Aktuell:
"zur Überprüfung weitergegeben/eingereicht".

### Veraltet: globaler Editbutton auf #features

Warum verworfen:
"Bearbeiten"/"Änderung vorschlagen" muss kompletten Platzworkflow öffnen.

Aktuell:
Route places.info-suggest.edit.

### Veraltet: Preis-Katalog vollständig mit "Noch keine Angabe"

Warum verworfen:
visuell unnötig, suggeriert Vollständigkeitszwang.

Aktuell:
nur vorhandene Daten oder ein allgemeiner Leerzustand.

### Provisional, nicht final: aktuelle Type->Feature-Category-Zuordnung

Nicht "falsch", aber ausdrücklich vor Launch erneut prüfen.

---

## 7. Technische Architektur

### 7.1 Zentrale Controller

PlaceSuggestionController

Aufgaben:

- neue Plätze anlegen/vorschlagen
- Draft/Schritt-2-Workflow
- Duplikatprüfung
- place types laden
- Veröffentlichungsvorschlag erzeugen

Relevante Methoden:

- create()
- store()
- nearbyDuplicates()
- editDraft()
- updateDraft()
- editDraftFeatures()
- updateDraftFeatures()
- reviewDraft()
- submitDraft()

Legacy editDraft/reviewDraft leiten inzwischen auf den neuen optionalen Feature-Schritt um.

PlaceProfileController

Aufgaben:

- veröffentlichtes Platzprofil laden
- Featuregruppen aufbereiten
- Type-Relevanz markieren
- Fahrzeugtypen, Kontakte, Öffnungszeiten, Preise, Reviews laden
- Counts/Completeness
- Permissions für Direktbearbeitung/Vorschläge

PlaceInfoSuggestionController

Aufgaben:

- allgemeine Platzinformationen korrigieren
- vollständige Core-Daten
- Adresse
- Texte
- Betreiber/Details
- Fahrzeuge
- Website
- Change Requests gruppiert erzeugen

Aktuell ergänzt um:

- name
- place_type_id
- latitude
- longitude
- legal_status
- Karten-Positionseditor
- Duplicate Advisory

PlaceFeatureController

Aufgaben:

- einzelnes Feature direkt bearbeiten oder Änderungsvorschlag erzeugen
- Permission-basiert Direct Edit vs Suggestion
- Historisierung/Audit/Notifications

### 7.2 Zentrale Services

PlaceTypeFeatureService

- Standardkategorien pro Typ.
- quickGroups() filtert Gruppen nach relevanten Kategorien.
- isStandardCategory() für Profilanzeige.
- aktuelle Konfiguration ist bewusst zentralisiert.

FeatureWorkflowService

- Features/Workflows + Übersetzungen laden.
- groupedForPlace()
- validate()
- normalizeFeatureData()
- present()
- saveDraft()
- iconNameFor()
- Detail-/Preisfelder anhand config dynamisch behandeln.

OpeningHoursPeriodService

- recurring periods
- overlap split
- proposals / moderation
- current periods

PricePeriodService

- structured offers
- recurring price periods
- splitting
- proposals / moderation
- priceable features

ChangeRequestApplyService

- genehmigte change_requests anwenden
- stale checks
- Versionierung
- Audit
- XP/Badges
- Core places, addresses, translations, contacts, details, vehicles, features, opening period schedule, pricing aggregate

PermissionService

- can(user, permission)
- trennt Suggest vs Direct Edit

UserNotificationService

- Moderationsentscheidungen, Favoritenänderungen, System-/Welcome-Meldungen usw.

BadgeService / XpService / LevelService

- Gamification-Grundlage
- Approved contribution hooks

### 7.3 Wichtige Views

- resources/views/places/suggest.blade.php
  - Schritt 1 neue Platzanlage
  - Karte
  - Duplicate Advisory
  - direkt einreichen oder Schritt 2

- resources/views/places/draft-features.blade.php
  - optionaler Schritt 2
  - relevante Kategorien komplett

- resources/views/places/partials/draft-feature-tag.blade.php
  - dynamischer Feature-Editor

- resources/views/places/show.blade.php
  - öffentlicher Place Profile
  - Core Info
  - Type-driven Feature Anzeige
  - Opening
  - Prices
  - Reviews

- resources/views/places/suggest-info.blade.php
  - vollständiger Pflege-/Änderungsvorschlagsworkflow
  - Grunddaten & Position
  - Adresse
  - Beschreibung/Zugang
  - Fahrzeuge
  - Kommentar

- resources/views/users/profile.blade.php
- resources/views/pages/settings/community-profile.blade.php
- resources/views/components/user-status-avatar.blade.php

### 7.4 Routen

Wichtige bekannte Routen:

- places.suggest.create
- places.suggest.store
- places.suggest.duplicates
- places.drafts.features.edit
- places.drafts.features.update
- places.info-suggest.edit
- places.info-suggest.update
- places.features.update
- places.opening-hours.edit
- places.prices.edit
- places.show
- users.profile
- community-profile.edit

Vor Änderungen routes/web.php neu lesen; es existieren weitere Support/Admin/Review/Favorite/Notification-Routen.

### 7.5 Externe Dienste

- Leaflet
- OpenStreetMap tiles
- Geocoding/Reverse Geocoding basierend auf OSM/Photon in bestehenden Flows

Keine externe API darf blind Community-Daten überschreiben.

---

## 8. Datenmodell und wichtige Strukturen

### places

Wichtige Felder:

- id
- place_type_id
- name
- slug
- latitude
- longitude
- publication_status
- legal_status
- opening_status
- is_active
- internal_comment
- created_by
- approved_by
- approved_at
- timestamps

publication_status mindestens:

- draft
- pending
- published

legal_status bekannte Werte:

- overnight_allowed
- camping_allowed
- parking_only
- prohibited
- owner_unwanted
- unclear

opening_status:

- open
- temporarily_closed
- seasonally_closed
- permanently_closed
- unclear

### place_types

- id
- slug
- sort_order
- is_active
- is_searchable
- translations

Neue V1-Typen wie oben.

### translations

Generisches Übersetzungsmodell für Referenzdaten:

- entity_type
- entity_id
- locale
- field
- value
- is_active

### place_addresses

Versioniert:

- place_id
- country_code
- region_id
- postal_code
- city
- street
- house_number
- address_addition
- is_active
- version_valid_from
- version_valid_until

### place_translations

Versioniert:

- place_id
- locale
- description
- directions
- access_information
- is_active
- version_valid_from
- version_valid_until

### place_details

Versioniert:

- operator_name
- pitch_count
- operating_mode
- place_id
- validity/versioning

### place_contacts

Versioniert; Website wird als contact_type website/url geführt.

### place_vehicle_types

Versionierte Zuordnung Place -> Vehicle Type.

### feature_categories / features / feature_workflows

Feature-Katalog ist zentral und workflow-getrieben.

FeatureWorkflow config enthält z.B.:

- status_options
- details
- show_for
- type
- Units/Options

### place_features

Versioniert/aktuell über:

- place_id
- feature_id
- status
- metadata JSON
- is_active
- valid_from
- valid_until

Kommentare separat in place_feature_notes.

### change_requests

Zentrale Community-Moderation:

- group_uuid
- place_id
- suggestable_field_id
- target_record_id
- operation
- original_value JSON
- proposed_value JSON
- status
- submitted_by
- submitted_at
- user_comment
- reviewed_by
- reviewed_at
- moderator_comment
- result_record_id
- applied_at
- timestamps

operation:

- create
- update
- deactivate

Gruppierte Änderungen eines Formulars teilen group_uuid.

### suggestable_fields

Whitelist, welche Felder vorgeschlagen/erstellt/geändert/deaktiviert werden dürfen.

Core places u.a.:

- name
- place_type_id
- latitude
- longitude
- legal_status
- opening_status
- publication_status

publication_status ist internes Workflow-Feld und nicht normales Userfeld.

### audit_logs

Domain-/Systemhistorie:

- user_id
- entity_type
- entity_id
- action
- source
- old_values
- new_values
- internal_comment
- created_at

### User-Profil

user_profiles u.a.:

- public_handle
- public_alias
- alias_finalized_at
- selected_badge_id
- bio
- hometown
- birth data
- gender
- vehicle/travel type
- photo relation / settingsbezogene Felder

user_settings u.a.:

- Sichtbarkeiten
- show_gamification
- show_join_date

### XP/Gamification

- xp_ledger als historische Source of Truth
- Level berechnet über LevelService
- Badges/Achievements separat

### Öffnungszeiten

- opening_hour_periods
- opening_hours
- recurring period logic
- virtual change field opening_hours.period_schedule

### Preise

- price_products
- price_product_variants
- price_billing_units
- place_price_offers
- place_price_periods
- place_price_lines
- zusätzlich Legacy place_prices, solange noch vorhanden

---

## 9. Fachliche Regeln / Business Logic

### Neue Plätze

- Nutzer muss nicht vorab alles wissen.
- Minimaldatensatz darf zur Moderation.
- Pflichtgrunddaten ergeben nicht automatisch "vollständig".
- Optionaler Feature-Schritt ist freiwillig.
- Pending-Plätze sind nicht öffentlich live, werden aber bei Duplikaten berücksichtigt.

### Duplikate

- Geografische Nähe + Namensähnlichkeit.
- Kein automatisches Blockieren.
- Benutzer soll bewusst entscheiden können, ob es ein anderer Ort ist.
- Merge bleibt späterer Admin-/Moderationsprozess.

### Features

- unknown ist keine negative Aussage.
- unavailable/no = negativ.
- bekannte Extra-Kategorie sichtbar.
- Standardkategorie darf auch komplett unbekannt sein und bleibt trotzdem für den Typ relevant.
- "Weitere Merkmale" eröffnet Zugriff auf den vollständigen Katalog.

### Change Requests

- Normaler User verändert Live-Daten nicht direkt.
- Vorschläge werden gruppiert.
- Moderatorfreigabe wendet Änderungen an.
- Stale Checks verhindern stilles Überschreiben neuerer Live-Werte.
- Rejection verändert Live-Daten nicht.

### Direct Edit

- places.edit o.ä. kann Direktbearbeitung erlauben.
- Direkte Änderungen müssen auditierbar bleiben.
- UI soll soweit sinnvoll dieselben Editoren nutzen.

### Reviews

Siehe Abschnitt 2.2.

### XP

- XP erst nach genehmigten/verifizierten Beiträgen.
- historisches Ledger nicht rückwirkend umwerten.
- Gegenbuchung statt stilles Entfernen.
- Gamification-Ausblendung ändert nicht XP-Akkumulation.

---

## 10. UI / UX / Produktentscheidungen

### Allgemeiner Stil

- Kompakt.
- Funktion vor Marketing.
- Keine unnötigen Full-width Mega-Controls.
- Stift-Icons für Bearbeitung sind etabliert.
- Text nur dort, wo ein Icon allein nicht klar genug ist.
- Keine verwirrenden Doppel-Editlinks.

### Platzanlage

Schritt 1:

- Name
- Typ
- Karte/Position
- Duplicate Advisory
- Buttons:
  - direkt zur Prüfung
  - optional Merkmale ergänzen

Schritt 2:

- Typrelevante Kategorien
- alle Features darin
- keine Pflichtaktion
- direkt einreichen möglich

### Platzprofil

- Typ und Name prominent.
- Legal-/Openingstatus als separate Information.
- Core Infos editierbar.
- Featuregruppen typabhängig.
- Extra-Gruppen bei bekannten Daten sichtbar.
- Weitere Merkmale manuell einblendbar.
- Opening/Prices eigene Boxen/Editoren.
- Preisbox nur echte Daten.
- Reviews darunter.

### Userprofil

- Alias + permanente CW-ID.
- kompakter Avatar.
- Stift + "Profil bearbeiten".
- Gamification-Block lokalisiert.
- mobile Ansicht bereits per Screenshot abgestimmt.

---

## 11. Sonderfälle und Edge Cases

- Zwei unterschiedliche echte Plätze können direkt nebeneinander liegen -> Duplicate nur Warnung.
- Ein pending Place muss Duplicate-Treffer sein.
- Beim Bearbeiten darf eigener Place nicht als Duplicate auftauchen.
- Legacy place types dürfen alte Datensätze nicht kaputtmachen, aber nicht neu angeboten werden.
- Nichtstandard Featuregruppe mit einem bekannten Merkmal muss sichtbar sein.
- Nichtstandard Featuregruppe ohne bekannte Daten bleibt verborgen.
- unknown darf nicht zu "Nein" werden.
- Opening "unknown" nicht persistieren wie affirmative Info.
- Operator "keine Angabe" ist separat und absichtlich persistiert.
- Review-Moderationslöschung: historischer Score vergangener Monate darf bestehen.
- Public Alias darf CW-Prefix/Systemnamen nicht missbrauchen.
- Beide Profil-URLs müssen funktionieren.
- Vehicle labels immer möglichst aus zentraler Übersetzung.
- MySQL-DDL ist nicht transaktional; Migrationen können bei Fehlern teilweise angewendet worden sein.
- Alte handoff/docs können älteren Stand enthalten; neuere Entscheidungen und aktueller main gewinnen.

---

## 12. Bekannte Probleme / technische Schulden

### Kurzfristig relevant

- Letzte komplette Core-Edit-Änderungen sind in dieser Session noch nicht vom Nutzer manuell bestätigt.
- Voller Testlauf nach den allerletzten Place-Pflege-Änderungen ist in diesem Chat nicht bestätigt.
- Aktuelles Feature-Kategorie-Mapping ist nur provisional.
- Teile von places/show.blade.php enthalten noch hartcodierte deutsche Texte. Vollständige DE/EN-Pass ist V1-Aufgabe.
- suggest-info.blade.php ist ebenfalls noch stark deutsch hartcodiert.
- Datums-/Zahlenformatierung an manchen Stellen noch deutsch fix.
- Legacy Draft-Views/Routes können noch existieren, obwohl der aktive Anlageflow nur zwei Schritte hat.
- Legacy price tables werden noch parallel dargestellt, solange Daten existieren.

### Reviews

- "Mehr laden" noch nicht echte begrenzte Pagination.
- Review-Subsystem wurde nach dieser Session zusätzlich gehärtet; aktuellen main-Code beachten.

### Profil

- alter handle_finalized_at-Feldrest aus früherem Modell.
- Migration für Alias hängt historisch an PublicHandleService; später ggf. robustheitshalber prüfen.
- Badge-/Achievement-Lokalisierung ist möglicherweise noch nicht überall vollständig.

### Prices

- unknown vs not_provided semantisch noch nicht final harmonisiert.

### Features

- gesamter Katalog muss vor Launch redaktionell/fachlich überarbeitet werden.
- Kategorien ggf. stärker spezialisieren, statt universell zu breit zu sein.

### Performance

Vor Release weiter prüfen:

- keine N+1
- Indizes
- Pagination
- Caching
- große Query-Pfade
- Duplicate Query bei hoher Datenmenge
- Map/Search load tests

---

## 13. Offene Punkte

### Kurzfristig

1. Kompletten Pflegeworkflow manuell testen:
   - Name
   - Typ
   - Legalstatus
   - Position
   - Adresse
   - Website
   - Beschreibung/Anfahrt/Zugang
   - Fahrzeuge
   - Features
   - Opening
   - Prices

2. Duplicate Advisory beim Verschieben testen.

3. Moderation:
   - Core-Änderung genehmigen
   - angewendete Werte kontrollieren
   - Audit/XP/Notification prüfen

4. Preisbox prüfen:
   - keine Preise
   - Structured Preise
   - nur Legacy Preis
   - Structured + Legacy

5. Featurezählung an mehreren Platztypen prüfen.

6. Mobile suggest-info / Place Profile prüfen.

### Später für V1

- echte Review-Pagination
- Fotos + Foto-Moderation
- Duplicate Merge
- initialer Datenimport Rastplätze/Open Data
- wiederholbare sichere Imports
- finaler Feature-/Category-Katalog
- finale Type->Category-Zuordnung
- DE/EN Vollpass im gesamten Place-/Moderations-/Support-UI
- Mobile/Desktop QA
- Permission QA
- Rate Limits / Basic Anti-Fake
- DSGVO/Löschung/Legal
- Backup/Restore
- Deployment
- Monitoring
- vollständige Tests/E2E
- Demo-Seed mit aktuellen Strukturen
- kritische Hilfetexte

### Nach V1 / Nice-to-have

- verified visits / Check-in / GPS
- owner verification + besondere Ownerrechte
- Community-Helfer mit Mehrheitsentscheidungen
- Events/temporäre Orte + Kartenlayer
- AI-Review-Zusammenfassungen
- Social Links
- komplexere Booking-/Price Rules
- konkrete Feiertagsausnahmen
- Native App / erweiterte PWA
- komplexe Hidden Achievements

---

## 14. Nächste konkrete Schritte

Priorität 1 – genau dort weitermachen, wo diese Session aufgehört hat:

1. Aktuellen main-Stand von PlaceInfoSuggestionController, suggest-info.blade.php, PlaceProfileController und show.blade.php neu lesen.
2. Sicherstellen, dass commits ee1f7a6..., 69ddc801..., 991fceb... im lokalen Stand des Nutzers angekommen sind.
3. Nutzer ausführen lassen:
   - git pull
   - php artisan optimize:clear
   - gezielte PlaceInfo/Duplicate Tests
   - danach php artisan test
4. Im Browser einen veröffentlichten Platz öffnen und "Änderung vorschlagen" testen.
5. Core-Daten ändern und prüfen, ob Duplicate Advisory korrekt arbeitet.
6. Vorschlag in Moderation öffnen, genehmigen und prüfen, dass alle Änderungen atomar/korrekt ankommen.
7. Preisleerzustand und Featurezählung visuell prüfen.
8. Wenn alles passt, den Platzpflege-Block für V1 als "funktional vollständig, nur Polish/i18n/QA offen" markieren.
9. Danach zum nächsten V1-Baustein weitergehen: Fotos / Foto-Moderation oder Search/Map + Import, je nach aktuellem Projektplan.
10. Pre-Launch-Merkmalskatalog ausdrücklich auf der Roadmap behalten und nicht vergessen.

---

## 15. Zusammenarbeit / Session-Charakter

### Arbeitsweise

Die Zusammenarbeit ist praktisch und iterativ.

Typischer Ablauf:

1. Nutzer beschreibt Produktidee oder Problem oft frei und mit Tippfehlern.
2. Assistant strukturiert den Gedanken und weist auf technische/UX-Konsequenzen hin.
3. Wenn fachlich geklärt, wird direkt im Repository umgesetzt.
4. Änderungen werden in kleine Commits auf main geschrieben.
5. Nutzer macht git pull, ggf. migrate/optimize/test.
6. Nutzer testet sichtbar im Browser und sendet Screenshots.
7. Assistant reagiert konkret auf tatsächliche UI-Probleme.
8. Erst dann nächster Block.

### Was gut funktioniert

- Direkte Aussagen wie "das ist kein Fehler, sondern aktuell absichtlich so gebaut".
- Kurz begründen, warum etwas so ist.
- Danach konkrete Alternative.
- Bei klaren Anforderungen direkt umsetzen, nicht erneut um Erlaubnis fragen.
- Screenshots praktisch analysieren, nicht theoretisch diskutieren.
- Kleine reversible Commits.
- Nach jeder Implementierung klare lokale Befehle nennen.
- Nicht behaupten, dass lokal etwas funktioniert, bevor Nutzer gepullt/getestet hat.
- Wenn ein Fehler im eigenen Code sichtbar wird, sofort korrigieren ohne Ausreden.

### Entscheidungsstil

Nutzer diskutiert gern kurz Produktlogik, entscheidet dann aber relativ schnell.

Muster:

- Erst grobe Idee.
- Assistant prüft Konsistenz/Edge Cases.
- Nutzer bestätigt oder korrigiert.
- Danach gilt die Entscheidung als gesetzt und soll nicht ständig wieder neu aufgerollt werden.

Beispiel:
- Anlageflow vereinfachen.
- Nutzer korrigierte nur die Moderationsformulierung.
- Danach wurde der 2-Step-Flow final.

### Detailgrad

Gewünscht:

- technische Erklärung so detailliert, dass der Zusammenhang verständlich ist.
- keine unnötige Lehrbuchtiefe.
- bei Codeänderungen lieber Ergebnis + relevante Architektur + Testschritte.
- bei komplexer Business Logic darf die Erklärung etwas ausführlicher sein.

### Kritisches Feedback

Explizit erwünscht.

Wenn eine Idee technisch/UX-seitig problematisch ist:

- sachlich sagen.
- konkrete Alternative vorschlagen.
- nicht nur zustimmen.

Gleichzeitig:
Nicht aus "Best Practice"-Reflex bewusst getroffene Produktentscheidungen wieder umwerfen.

### Rückfragen

Nur bei echter Produkt-/Datenmodell-Ambiguität.

Wenn der Nutzer eindeutig sagt "mach so", direkt ausführen.

### Stil

- Deutsch.
- kompakt, sachlich, kollegial.
- keine übertriebene Begeisterung.
- keine unnötige Korrektur seiner Tippfehler.
- Begriffe möglichst konsistent mit dem Projekt verwenden.

### Kleine etablierte Konventionen

- Bearbeiten meist mit Stift-Icon.
- zentrale Editlinks nicht doppelt.
- "Unbekannt" ist legitim.
- "zur Prüfung einreichen" statt so zu tun, als wäre Content bereits live.
- main ist authoritative.
- bei Migrationsbedarf explizit sagen.
- bei reinen View/Controller-Änderungen nicht unnötig migrate empfehlen.
- Tests nicht als bestanden behaupten, wenn sie nicht tatsächlich gelaufen sind.
- User testet gern praktisch und bestätigt danach knapp "funktioniert".

---

## 16. Dinge, die der nächste Chat NICHT tun sollte

- Nicht den Zwei-Schritt-Anlageflow wieder zu einem langen Wizard machen.
- Nicht Schritt 2 verpflichtend machen.
- Nicht wieder einzelne Quick-Features pro Typ hartcodieren, wenn das Konzept auf Kategorieebene finalisiert wurde.
- Nicht pending Places aus der Duplicate-Prüfung entfernen.
- Nicht Duplikate rein aufgrund geografischer Nähe blockieren.
- Nicht Legal-/Übernachtungsstatus in den Place-Type hineinmischen.
- Nicht Events als normalen dauerhaften Place-Type für V1 einbauen.
- Nicht aktuelle Type->Category-Zuordnung als endgültig behandeln; Pre-Launch-Review ist eingeplant.
- Nicht wieder alle Preisarten mit "Noch keine Angabe" rendern.
- Nicht Core-Daten wie Name/Typ/Position aus dem Pflegeworkflow entfernen.
- Nicht Feature-Count gegen den gesamten globalen Katalog berechnen.
- Nicht öffentliche Aliaslogik zurück auf "Alias ersetzt CW-ID" ändern.
- Social Links nicht ohne neues Safety-/Produktkonzept reaktivieren.
- Verified Visit/Owner Verification nicht in V1 ziehen.
- Nicht funktionierende Alt-Routen reaktivieren, nur weil sie noch im Repo existieren.
- Keine großen Architekturumbauten nur aus theoretischer Eleganz.
- Kein Hard Delete historischer Referenz-/Auditdaten ohne sehr guten Grund.
- Keine Aussage "funktioniert" ohne Test/Bestätigung.
- Nicht die existierenden Opening-/Price-Systeme durch vereinfachte Parallelmodelle ersetzen.
- Nicht bei User-Entscheidungen erneut von Grund auf debattieren, wenn nichts Neues dagegen spricht.

---

## 17. Schnellstart für den nächsten Chat

1. Repository: WulfieWolf/camperwolf, main.
2. main vor jedem Edit neu lesen.
3. Aktiver V1-Block: Platzanlage + vollständige Platzpflege.
4. Neue Platzanlage ist FINAL zweistufig.
5. Schritt 1: Name, Typ, Position.
6. Nach Schritt 1 darf direkt zur Moderation eingereicht werden.
7. Schritt 2 optional: relevante Kategorien, darin alle Features.
8. Keine einzelne QUICK_FEATURES-Auswahl mehr.
9. Pending Places gehören in Duplicate Checks.
10. Duplicate ist Warnung, kein Block.
11. Beim Editieren exclude_place_id verwenden.
12. V1 Place Types: campground, motorhome-pitch, tent-site, parking, rest-area, free-pitch, service-station, camping-outdoor.
13. Legalstatus separat vom Typ.
14. Platzprofil zeigt Standardkategorien + bekannte Extra-Kategorien.
15. "Weitere Merkmale" zeigt übrige Kategorien.
16. Feature-Zähler zählt nur relevante Gruppen.
17. Aktuelle Typ-Kategorie-Mappings sind provisional; vor Launch komplett prüfen.
18. Preise: nur vorhandene Daten anzeigen; sonst ein allgemeiner Leerzustand.
19. Ein Platz muss 100 % editierbar sein.
20. PlaceInfoSuggestionController enthält Core-Felder Name/Typ/Position/Legalstatus.
21. suggest-info besitzt Leaflet-Positionseditor + Duplicate Advisory.
22. globaler Editbutton führt auf den vollständigen Info-Suggestion-Workflow.
23. Opening Hours: recurring annual periods, delayed split until approval.
24. Prices: structured product/variant/period model.
25. Profiles: permanente CW-ID + separater Alias; UI bereits mobil abgestimmt.
26. Reviews: 5 Dimensionen, historisiert, 12 Monate, 30-Min-Korrektur, 28-Tage-Cooldown.
27. Verified Visits und verified owners sind post-V1.
28. V2: temporäre Events als eigenes Modul/Kartenlayer.
29. Nächster Schritt: letzte Core-Edit-Änderungen lokal/manuell testen.
30. Danach Place-Pflege-Block abhaken und nächsten V1-Baustein wählen.

---

## 18. Handoff-Nachricht an den nächsten Chat

Du übernimmst hier ein laufendes Camperwolf.de-Projekt. Lies diese Datei vollständig, bevor du Änderungen machst. Diese Datei dokumentiert den Arbeitskontext dieser Session; bei Widersprüchen gelten die zeitlich neueren Entscheidungen und der aktuelle main-Stand vor älterem Code oder älterer Dokumentation.

Der aktuelle Schwerpunkt ist der V1-Workflow zum schnellen Anlegen und anschließenden vollständigen Pflegen von Plätzen. Die Platzanlage wurde bewusst auf zwei Schritte reduziert: Name/Typ/Position und optional typrelevante Merkmale. Pending-Plätze gehören in die Duplikatprüfung. Der Platztyp steuert Standard-Merkmalskategorien; zusätzliche Kategorien erscheinen bei bekannten Daten oder über "Weitere Merkmale". Der gesamte veröffentlichte Platz muss anschließend zu 100 % korrigierbar sein.

Ganz zuletzt wurden Feature-Zählung, Preis-Leerzustand, Core-Place-Editing, Positionskorrektur und Duplicate Checks beim Editieren umgesetzt. Diese letzten Änderungen sind noch der Teil, den du zuerst praktisch gegen den aktuellen main-Stand und die Tests verifizieren solltest. Danach kann der Place-Pflege-Block für V1 abgeschlossen und der nächste V1-Baustein angegangen werden.

Wichtig: Aktuelle Entscheidungen höher gewichten als alte Wizard-/Quick-Feature-/Alias-Implementierungen, die möglicherweise als Altcode noch existieren.