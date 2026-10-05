# Camperwolf Project Context


## Aktueller Arbeitsstand - 2026-10-03

Camperwolf läuft inzwischen öffentlich als Beta auf `main`. Der frühere Pre-Beta-/Lockdown-Stand und der gestapelte Entwicklungs-Branch-Stack sind historisch; neue Arbeiten basieren direkt auf dem aktuellen `main`.

Aktueller technischer Referenzstand vor dieser Dokumentationsaktualisierung:
- GitHub-`main`: `b608f2fe2f4b7da6daa4520754a5617472ff1032` ("Localize structured research history changes").
- Production läuft aus `/var/www/vhosts/camperwolf.de/laravel/current` auf Branch `main`.
- Produktiver Datenbestand zuletzt ca. 5.567 Plätze; Niedersachsen, NRW und Bayern sind produktive Importquellen.
- Google Search Console ist angebunden; der Nutzer meldete erfolgreiche Sitemap-Übertragung und Indexierung. Es gibt weiterhin keine Google-Analytics-/Werbetracker.
- Öffentliche Beta ist erreichbar; Bot-/Crawler-Traffic ist deshalb real und wird in der internen Nutzungsstatistik sichtbar.

Wichtige Änderungen vom 2026-10-03:
- Niedersachsen wird bei gelisteten Datensätzen als "in Betrieb" behandelt. Ein regulärer Folgesync soll bestehende `unclear`-Fälle entsprechend auffüllen; explizit geschlossene Camperwolf-Plätze werden nicht stillschweigend wieder geöffnet.
- Vollständige Quellen-Snapshots von Niedersachsen, NRW und Bayern erzeugen bei verschwundenen verknüpften Datensätzen `source_missing`-Reviews statt Plätze automatisch zu schließen.
- Wenn eine Quelle einen Platz als offen meldet, Camperwolf ihn aber als temporär, saisonal oder dauerhaft geschlossen führt, wird `possible_reopen` zur manuellen Prüfung erzeugt.
- Die Import-Review-Queue besitzt kontextbezogene Werkzeuge für fehlende Koordinaten, Dubletten und mögliche Wiedereröffnungen. Alte zuvor ignorierte Fälle mit fehlenden Koordinaten wurden einmalig wieder geöffnet. Der Nutzer hat den Koordinaten-Workflow produktiv geprüft; verbleibend waren zuletzt 55 manuell zu recherchierende Koordinatenfälle.
- Manuell ergänzte Import-Koordinaten werden in `external_records.manual_overrides` gespeichert und bleiben bei späteren Syncs erhalten, solange die Quelle selbst keine gültigen Koordinaten liefert. Echte spätere Quellkoordinaten haben Vorrang.
- GPS-basierte Suche nach den drei nächsten aktuell gefilterten Plätzen ist produktiv. Die Koordinaten bleiben browserseitig und werden nicht an Camperwolf gesendet; Datenschutzerklärung und Devlog wurden entsprechend ergänzt.
- Kontaktfelder Telefon/E-Mail und Adresszusatz sind im Platzpflege-Workflow vorhanden. E-Mail-Adressen auf Platzprofilen werden lazy über einen gedrosselten Endpunkt ausgeliefert.
- Nutzungsstatistik unterscheidet bei Gästen `human`, `bot` und unklassifiziert. Die Aktivitäts-Zeitreihe wurde am 2026-10-03 von PHP-seitigem Laden aller `created_at`-Werte auf SQL-Aggregation umgestellt; der zuvor sporadische 500/503-Fehler der Statistikseite war danach für den Nutzer nicht mehr reproduzierbar.
- `/support/report` und `/support/datenschutz-recht` sind mit `X-Robots-Tag: noindex, nofollow, noarchive` versehen und in `robots.txt` ausgeschlossen, weil Crawler den global verlinkten Report-Einstieg stark abgerufen und die Statistik verzerrt hatten.
- Ein repo-weiter Lokalisierungs-Pass wurde auf `main` begonnen und weitgehend umgesetzt: öffentliche harte DE-Texte, Datenexport-README, Import-Center, Import-Controller/-Fehler, XP-Beschreibungen und öffentliche Import-/Recherche-History wurden auf Übersetzungsstrukturen bzw. sprachabhängige Darstellung umgestellt. Dieser große Block ist **noch nicht vom Nutzer als Gesamtpaket lokal getestet oder deployed bestätigt** und ist der unmittelbare nächste Prüfpunkt.

Wichtiger Deployment-Merksatz:
- `SupportContentSeeder` verwendet für bestehende Hilfeinhalte `updateOrInsert` und kann manuelle Admin-Änderungen an bereits geseedeten Artikeln überschreiben. Er darf daher nicht routinemäßig bei jedem Deployment laufen. Vor einem gezielten Lauf immer prüfen, ob bestehende redaktionelle Änderungen erhalten werden müssen.

## V2-Vormerkung: Tankstellen und aktuelle Kraftstoffpreise

- Für V1 ausdrücklich **nicht** weiterverfolgen; als eigener V2-Entwicklungsblock vormerken.
- Camperwolf soll perspektivisch auch bekannte öffentliche Tankstellen als eigene reise-/fahrzeugbezogene Orte aufnehmen, nicht nur Tankstellen als Merkmal von Rastplätzen.
- Eigener Platztyp vorgesehen, z. B. `fuel-station`; nicht mit der bestehenden Camper-Ver-/Entsorgungs-`service-station` vermischen.
- Relevante Kraftstoffe zunächst insbesondere Diesel, Super E5 und Super E10; weitere Kraftstoffe/Angebote später möglich.
- Perspektivisch aktuelle Kraftstoffpreise über die Markttransparenzstelle für Kraftstoffe (MTS-K) bzw. deren vorgesehenen Datenweg integrieren. Voraussetzungen/Zulassung und Nutzungsbedingungen vor Umsetzung aktuell prüfen.
- Externe Kraftstoffdaten sollen in das bestehende source-aware External-Layer-Konzept passen und Community-/Owner-/Admin-Daten nicht blind überschreiben.
- Tankstellen auf Rastplätzen bleiben grundsätzlich eigenständige Tankstellen-Datensätze. Rastplatz und Tankstelle können über eine Relation miteinander verknüpft werden, statt aus beiden Quellen Dubletten zu erzeugen.
- Für solche Beziehungen perspektivisch ein allgemeines Place-Relations-Modell prüfen (z. B. `part_of`, `located_at`, `nearby`) statt einer nur für Tankstellen gebauten Sonderlösung.
- Automatisches Matching kann Koordinaten, Namen und weitere Quelldaten nutzen; räumliche Nähe allein darf wegen getrennter Autobahnseiten/Zu- und Abfahrten nicht als sicherer Beweis gelten. Unsichere Fälle sollen prüfbar bleiben.
- Darstellungsidee: Tankstellen können selbst in der Suche erscheinen; auf einem verknüpften Rastplatz können Tankstelle, verfügbare Kraftstoffe und aktuelle Preise zusätzlich angezeigt werden.
- Scope-Grenze: Daraus soll kein allgemeines beliebiges POI-Verzeichnis entstehen. Tankstellen passen wegen ihres unmittelbaren Reise-/Fahrzeugbezugs zum Camperwolf-Kern.

## 1. Zweck dieser Datei

Diese Datei ist der konsolidierte Einstiegspunkt in den aktuell bekannten Stand von Camperwolf. Sie fasst Produktentscheidungen, Implementierung, offene Arbeiten und die bewährte Zusammenarbeit zusammen. Sie ist kein Sitzungsprotokoll.

Die älteren Handoffs bleiben unverändert als historische Quellen erhalten. Bei Widersprüchen gilt die zeitlich neuere dokumentierte Entscheidung. Für technische Aussagen ist zusätzlich der tatsächliche Code auf dem aktuellen `main` zu prüfen; Code, Migrationen und Tests können der Dokumentation voraus sein oder von ihr abweichen. Eine Abweichung ist offenzulegen, nicht stillschweigend zugunsten einer Quelle aufzulösen.

Die chronologischen Fortschreibungen ab Abschnitt 21 bleiben bewusst als Entwicklungsverlauf erhalten und können Aussagen enthalten, die zum damaligen Zeitpunkt korrekt waren, heute aber überholt sind. Für den **aktuellen** Stand sind zuerst "Aktueller Arbeitsstand", die konsolidierten Abschnitte 2-16 und danach die jeweils neueste Fortschreibung maßgeblich; aktuell ist das Abschnitt 37.

- Letzte Aktualisierung: 2026-10-03
- Eingearbeitet: `docs/SESSION_HANDOFF_01.md`, `docs/SESSION_HANDOFF_02.md`, `docs/SESSION_HANDOFF_03.md`
- Zusätzlich berücksichtigt: Review-Cleanup (`1321cf4046e7c0cc40b3b00f18ee690a78a1dc4b`), abgenommener Foto-MVP (Squash-Merge `46a4a5175a306e5e22dcf3e726cfb7a77b543739`), abgenommene Review-Paginierung (Squash-Merge `f79245d32e8b3fced784936356c1c733b064549f`), abgenommene Platz-Zusammenführung und die Implementierung des V1-Merkmalskatalogs
- Technischer Referenzstand des GitHub-Codes vor dieser Dokumentationsaktualisierung: `b608f2fe2f4b7da6daa4520754a5617472ff1032`. Der tatsächliche Production-Stand ist bei Deploymentfragen weiterhin mit `git status` / `git log -1` auf dem Server zu prüfen.

### 1.1 Verbindliche Arbeitsweise für Entwicklungs-Sessions

Diese Zusammenarbeit soll zwischen Sessions möglichst gleich ablaufen, sofern nicht ausdrücklich etwas anderes vereinbart wird:

- Der Assistant nimmt Code- und Dokumentationsänderungen direkt im GitHub-Repository auf einem eigenen Branch vor und erstellt dafür einen PR.
- Der Nutzer arbeitet lokal unter Windows/PowerShell: Branch holen/wechseln, bei Bedarf Cache leeren oder Assets bauen, Tests ausführen und die Änderung im Browser praktisch prüfen.
- PowerShell-Kommandos immer einzeilig liefern. Wenn mehrere Schritte nötig sind, mit Semikolon in einer einzigen Zeile verketten. Keine mehrzeiligen PowerShell-Blöcke.
- SSH-Kommandos dürfen und sollen bei mehreren Schritten mehrzeilig sein. Auf dem Server sind lesbare mehrzeilige Shell-Blöcke ausdrücklich erwünscht.
- Vor Merge und Production-Deploy zuerst fokussierte lokale Tests und - wenn UI betroffen ist - einen Browser-Sichttest durchführen. Nutzerfeedback wie "passt" oder "funktioniert" gilt als praktische Abnahme.
- Nach erfolgreichem Test wird der PR gemerged. Wenn der GitHub-Connector den Merge nicht ausführen kann, kann der Nutzer den PR in GitHub selbst mergen.
- Production-Deployment erfolgt nicht autonom durch den Assistant. Der Assistant nennt die konkreten SSH-Schritte; der Nutzer führt sie auf dem Server aus.
- Bei Deployments immer ausdrücklich nennen, ob Migrationen, Seeder, Asset-Build, Cache-/Optimize-Schritte oder sonstige Sonderaktionen nötig sind. Nichts vorsorglich ausführen.
- SupportContentSeeder bleibt ein Sonderfall und wird nur gezielt ausgeführt, weil er bestehende redaktionelle Hilfeinhalte überschreiben kann.
- GitHub Actions sind hilfreich, aber nicht der alleinige Freigabepunkt. Für kleine Beta-Nachpflege zählt vor allem: fokussierte Tests lokal grün, praktischer Browsercheck und klare Deployment-Schritte.

## 2. Projektüberblick

Camperwolf ist ein gemeinschaftlich gepflegtes Verzeichnis für Camping-, Stell-, Übernachtungs- und Versorgungsorte. Es soll Reisenden verlässliche, strukturierte und nachvollziehbar gepflegte Informationen liefern, ohne Rankings oder Sichtbarkeit käuflich zu machen. Deutschland ist der erste Schwerpunkt; Datenmodell und Produkt sollen international erweiterbar bleiben.

Grundlegende Produktprinzipien:

- Öffentliche Kernfunktionen: Plätze suchen, filtern, auf der Karte sehen und Profile lesen. Beiträge erfordern ein Konto.
- Datenqualität vor bloßer Datenmenge, bei gleichzeitig niedriger Hürde für das Anlegen neuer Plätze.
- Koordinaten sind die primäre geografische Wahrheit; Adressen sind ergänzend.
- „Unbekannt“ ist nicht gleich „Nein“.
- Community-Änderungen sind moderiert und nachvollziehbar. Direkte Änderungen privilegierter Nutzer bleiben ebenfalls auditierbar.
- Externe Quellen sind eigene Quellen/Akteure und dürfen Nutzer-, Besitzer- oder Admin-Daten nicht blind überschreiben.
- Geld darf weder Score, Ranking noch Sichtbarkeit beeinflussen.
- Referenzdaten werden grundsätzlich deaktiviert statt hart gelöscht, wenn Historie oder Beziehungen betroffen sind.

V1 umfasst im Kern öffentliche Platzsuche und -profile, Konto/Profil, Vorschlagen und Pflegen von Plätzen, strukturierte Merkmale und Öffnungszeiten, Reviews, Fotos, Favoriten, Support, Moderation/Audit und Benachrichtigungen. Das strukturierte Preisbackend bleibt vollständig erhalten, der öffentliche Preisblock und Preisfilter sind für die erste Beta aber bewusst ausgeblendet und werden voraussichtlich erst mit dem späteren Owner-/Betreiberbereich wieder produktseitig aufgegriffen. Besitzer-Verifizierung, Community-Helfer, Check-ins und Verified Visits gehören nicht zur ersten öffentlichen Beta.

## 3. Aktueller Gesamtstand

### Bereits umgesetzt

- Laravel-Anwendung mit Authentifizierung, Rollen/Rechten, Admin-Grundlagen und neutralen 404-Antworten für geschützte interne Bereiche.
- Öffentliche Platzliste, Suche/Filter, Kartenansicht und veröffentlichte Platzprofile.
- Zwei-Schritt-Anlage neuer Plätze mit Duplikatwarnung und optionaler Merkmalsauswahl.
- Vollständiger Vorschlags-/Änderungsweg für veröffentlichte Plätze einschließlich Stammdaten, Position, Status, Kontakt/Adresse, Beschreibungen, Fahrzeugtypen, Merkmalen, Öffnungszeiten und Preisen.
- Gruppierte `change_requests`, Moderation, Anwendung akzeptierter Änderungen und Audit-Logging.
- Strukturierte saisonale Öffnungszeiten und Preise.
- Reviews mit fünf Dimensionen, Versionierung, Reports, Moderation, Score und Zeitverlauf.
- Review-Liste mit stabiler Cursor-Paginierung: zunächst fünf Rezensionen, anschließend jeweils fünf weitere ohne vollständigen Seitenreload.
- Foto-MVP mit privater Verarbeitung, Moderation, Helpful-Stimmen, Platz-Titelbildern, Galerie/Lightbox und zentraler administrativer Bildverwaltung.
- Auditierbare Platz-Zusammenführung mit Datenvergleich, feldweiser Konfliktentscheidung, Relationstransfer, Review-/Foto-Sonderfällen und Weiterleitung des alten Platzes; praktisch vom Nutzer abgenommen.
- Bereinigter V1-Merkmalskatalog mit eigener Hunde-Kategorie, expliziter Sichtbarkeit je Platztyp, einmalig angewendetem Katalog-Release und administrativer Merkmal-/Kategorieverwaltung. Migration, gezielte Tests und Browserabnahme wurden erfolgreich abgeschlossen.
- Nutzerprofile mit permanenter CW-ID, optionalem Alias, Sichtbarkeitseinstellungen und Profilbild.
- XP-Ledger, Level-Grundlage, Badges/Achievements, Favoriten, Benachrichtigungen und Support-System.
- Deutsche und englische Oberfläche für die V1-Bereiche ist weitgehend vorhanden. Ein erneuter Repo-Scan am 2026-10-03 fand jedoch noch statisch deutsche Resttexte; daraufhin wurde ein großer Lokalisierungs-Pass auf `main` umgesetzt. Der Gesamtblock muss noch lokal getestet und im englischen UI stichprobenartig geprüft werden, bevor "vollständig" wieder behauptet werden sollte.
- Responsive Startseite und Platzprofil wurden auf dem realen Referenzgerät Samsung Galaxy S22 Ultra / Firefox Android praktisch geprüft und für den ersten mobilen Durchgang abgenommen.
- Das Platzprofil besitzt inzwischen kategorieweise Merkmalsbearbeitung mit direkter Statusauswahl, lokalem Entwurf, Sammel-Einreichen pro Kategorie, sichtbaren ausstehenden Eigenvorschlägen und Ersetzung älterer noch offener Eigenvorschläge.
- Persistierter **DatenScore** zur Vollständigkeit von Platzdaten: 75 % Basisdaten, 25 % bekannte relevante Merkmale, Anzeige 0-10 mit getrennten Teilwerten, Sortierung auf der Startseite, Dirty-Recalculation alle fünf Minuten und administrativer Full-Rebuild. Der DatenScore bewertet ausdrücklich nur Vollständigkeit, nicht Richtigkeit, Aktualität oder Vertrauenswürdigkeit.

### Technisch vorhanden, aber noch nicht final

- Merkmal- und Kategoriezuordnungen sind fachlich bereinigt und administrativ pflegbar. Die Bedienung der Verwaltung ist funktional, soll nach V1 beziehungsweise in einem späteren Ergonomie-Pass verbessert werden.
- Strukturierte Preise sind weiterhin das Zielmodell; Legacy-`place_prices` kann technisch noch parallel existieren. Für die öffentliche Beta werden Preisblock und Preisfilter jedoch bewusst **nicht angezeigt**. Preis-Services, Tabellen, Editor und Änderungslogik bleiben für die spätere Owner-/Betreiberphase erhalten.
- Badge-/Achievement-Grundlage ist vorhanden; Katalog, Übersetzungen und langfristige Balancierung sind nicht endgültig.
- Externe Geocoding-/Kartendienste werden genutzt. Die amtliche/Open-Data-Importstrecke ist produktiv: Niedersachsen, NRW TFIS und Bayern ATKIS laufen als getrennte Quellen mit eigener Provenienz, Review-/Dublettenlogik und Scheduler. DZT bleibt wegen fehlender verlässlicher API-Freigabe eine spätere zusätzliche Quelle. OSM bleibt für Karte/Geocoding im Einsatz, wird aber wegen ODbL-/Share-Alike-Komplexität vorerst nicht als allgemeine Camperwolf-Datenimportquelle verwendet.

### Fachlich entschieden, aber noch nicht oder nicht vollständig umgesetzt

- Externe Importe müssen wiederholbar, quellenidentifiziert und konfliktbewusst sein und dürfen höherwertige manuelle Daten nicht blind überschreiben.
- Verbotene oder nicht gewünschte Plätze sollen als nachvollziehbarer Datensatz bestehen bleiben und markiert/filterbar sein, statt spurlos zu verschwinden. Der konkrete Implementierungsstand ist unklar und muss im Code geprüft werden.
- Besitzer-Verifizierung und Community-Helfer benötigen eigene Rechte- und Vertrauensmodelle; sie sind noch nicht implementiert.
- Verified Visits/Check-ins können später Bewertungen stärken, sind aber für V1 weder Gewichtungsfaktor noch Voraussetzung.

### Geplant

- Such-/Karten-Härtung und Performance-Baseline sind für Roadmap-Punkt 8 abgeschlossen; weitere Detailoptimierungen erfolgen nur bei konkretem Befund.
- Responsive QA, Accessibility- und UX-Nacharbeit einschließlich leerer und fehlerhafter Zustände.
- Rate Limits, Missbrauchsschutz und Datenschutz/Recht sind für V1 weitgehend umgesetzt. Deployment, Queue/Scheduler, Storage, Restore-Test, Logs und der Camperwolf-spezifische Security-Pass wurden auf Production praktisch geprüft. Es existieren bereits vollständige Backups; nach den letzten Änderungen vom 2026-10-02 ist unmittelbar vor Öffnung der Beta noch einmal ein manuelles Full Backup als finaler Wiederherstellungspunkt vorgesehen. Serverweite Härtung von Firewall/Ports/Diensten bleibt bewusst getrennt, weil weitere Projekte auf demselben Plesk-Server laufen. Offsite-Backup bleibt ein späterer Operationspunkt.
- Amtlicher/Open-Data-Import ist als vorgezogener Pre-Launch-Block technisch umgesetzt und mit DATEX-II-Echtdaten praktisch validiert. Zusätzliche Quellen werden nach Freigabe ergänzt.

## 4. Aktuell laufendes Teilprojekt

### Zuletzt bearbeitet

Der aktive Block ist jetzt **Beta-Nachpflege / Qualitätsbereinigung**. Die öffentliche Beta läuft; größere neue Funktionsblöcke sollen weiterhin nicht ohne konkreten Anlass begonnen werden.

Am 2026-10-03 wurden insbesondere folgende Punkte bearbeitet:
- Import-Review-Semantik für `source_missing` und `possible_reopen` sowie kontextbezogene Review-Werkzeuge;
- persistente manuelle Koordinatenkorrekturen für unvollständige externe Datensätze;
- Wiederaufnahme alter ignorierter Missing-Coordinate-Fälle;
- Niedersachsen-Regel "gelistet => in Betrieb" für bisher unklare Stati;
- GPS-/Nearby-Suche und passende Datenschutz-/Devlog-Ergänzung;
- Kontakt-/Adresspflege und lazy E-Mail-Reveal;
- Botklassifizierung in der Nutzungsstatistik;
- SQL-Aggregation der Statistik-Zeitreihe zur Beseitigung sporadischer 500/503-Ausfälle;
- Ausschluss der Support-Einstiegsformulare aus Suchmaschinenindex/Crawl;
- anschließend repo-weite Lokalisierungsinventur und ein großer Übersetzungs-Pass über öffentliche UI, Datenexport, XP, Import-Center, Importfehler und History-Darstellung.

Der Nutzer bestätigte die Import-Koordinatenwerkzeuge produktiv als funktionsfähig und die fokussierten Tests bis einschließlich Statistik-/Security-Härtung als grün. Der danach entstandene große Lokalisierungs-Commitblock ist noch nicht als Gesamtheit lokal abgenommen.

Unmittelbar als Nächstes:
1. aktuellen `main` lokal ziehen und den Lokalisierungsblock mit fokussierten Locale-/Import-/XP-/Export-/History-Tests plus sinnvoller Gesamtsuite prüfen;
2. danach einen erneuten Scan nach statisch deutschen UI-Texten durchführen und nur echte Rest-Leaks korrigieren; technische/local-only CLI-Texte getrennt bewerten statt blind zu übersetzen;
3. englische Oberfläche beim normalen Platzpflege-/Admin-Durchgang stichprobenartig beobachten;
4. bei grünem Stand deployen;
5. beim nächsten regulären Niedersachsen-Sync ausdrücklich prüfen, ob bisher `unclear` gelistete Datensätze auf "In Betrieb" aufgefüllt werden und ob `possible_reopen` nur in den erwarteten Fällen entsteht.

### Ziel und aktueller Implementierungsstand

Ziel der Platzpflege war ein einheitlicher, nachvollziehbarer Änderungsweg für Community und privilegierte Bearbeiter. Normale Nutzer erzeugen gruppierte Vorschläge; privilegierte Nutzer können direkt ändern, ohne die Auditierbarkeit zu verlieren. Dies ist umgesetzt.

Ziel des anschließenden Dokumentationsblocks war ein belastbarer primärer Einstiegspunkt für neue Chats, ohne drei lange Handoffs jedes Mal erneut gegeneinander auslegen zu müssen. Diese Konsolidierung ist mit der vorliegenden Aktualisierung abgeschlossen. `PROJECT_CONTEXT.md` ist die aktuelle Quelle für den Gesamtstand; die Handoffs bleiben historische und inkrementelle Quellen.

Ziel des Review-Cleanups war, nur noch das tatsächlich genutzte Review-Modell zu behalten und dessen Regeln gegen inkonsistente oder parallele Eingaben zu härten. Dies ist ebenfalls umgesetzt:

- Die fünf Bewertungsdimensionen sind in `PlaceReviewService::DIMENSIONS` zentralisiert.
- Eingaben müssen exakt diese Schlüssel und Ganzzahlen von 1 bis 5 enthalten.
- XP-Deduplizierung verwendet technische Schlüssel statt übersetzter Labels.
- Gleichzeitige erste Reviews werden durch `insertOrIgnore` plus Sperrlogik gehärtet.
- Öffentliche Abfragen respektieren `is_public`; Reports beziehen sich nur auf die aktuelle sichtbare Review-Version.
- Die alten Review-Tabellen werden durch `2026_09_20_220000_drop_legacy_review_tables.php` entfernt.

Der Foto-MVP ist abgeschlossen. Er umfasst privaten Upload, Queue-Verarbeitung, Moderation, öffentliche/private Bildauslieferung, Helpful-Stimmen, Meldungen, automatische Platz-Thumbnails, Banner, paginierte Galerie mit Lightbox und eine sortier-/filterbare administrative Bildverwaltung. Bilder werden proportional ohne Beschnitt verkleinert, zu WebP konvertiert und vollständig von Metadaten bereinigt. Legacy-Fotodatensätze ohne UUID bleiben administrativ sichtbar und löschbar, werden aber nicht öffentlich ausgeliefert oder als Titelbild verwendet.

Die Review-Paginierung ist ebenfalls abgeschlossen. `PlaceReviewService::reviewsForPlace()` verwendet eine stabile Cursor-Paginierung. Im Platzprofil erscheinen zunächst fünf Rezensionen; der öffentliche Feed-Endpunkt liefert jeweils fünf weitere Karten ohne vollständigen Seitenreload. Profil-, Gamification-, Foto-, Melde- und Adminfunktionen bleiben in nachgeladenen Karten erhalten. Der Nutzer bestätigte gezielte Tests und Browserfunktion als fehlerfrei.

Die Platz-Zusammenführung ist ebenfalls abgeschlossen und praktisch abgenommen. Danach wurde der bestehende, historisch über mehrere Seeder gewachsene Merkmalsbestand bereinigt. Die Implementierung führt `feature_place_types` mit `standard`, `extended` und `hidden`, eine einmalige Release-Sperre über `catalog_releases` sowie `is_system` an Kategorien und Merkmalen ein. Der neue `V1FeatureCatalogSeeder` erzeugt einen reproduzierbaren Initialkatalog, überschreibt nach angewendetem Release aber keine späteren Adminänderungen. Die Admin-Oberfläche erlaubt Filtern, Sortieren, Anlegen und Bearbeiten von Kategorien, Merkmalen, DE/EN-Namen, Statusoptionen, Details und Platztyp-Sichtbarkeit. Verwendete Slugs sind geschützt; Einträge werden deaktiviert statt hart gelöscht, und Änderungen werden auditiert.

Die fokussierten Katalog-, Platztyp- und Vorschlagstests sowie Pint für die betroffenen Dateien sind grün. Ganz zuletzt wurden drei Regressionen behoben: der Legacy-Fallback für Schnellgruppen, fehlertoleranter Zugriff auf optionale Adminformularfelder und der Erhalt des Status `unlimited` samt Stunden-/Tage-Einheit bei `max-stay-duration`. Der Nutzer bestätigte anschließend Migration, Tests und Browserfunktion.

Die DE/EN-Arbeit ist für die aktuell vorhandenen V1-Oberflächen technisch abgeschlossen. Globale Navigation, Authentifizierung, öffentliche Seiten, Konto-/Profilbereiche, Platzpflege, Reviews/Fotos, Benachrichtigungen, Support, Administration sowie sichtbare Validierungs- und Workflow-Fehler verwenden Sprachdateien oder übersetzbare Datenstrukturen. Neue Besucher erhalten anhand ihrer Browsersprache Deutsch oder Englisch; eine manuelle Auswahl hat Vorrang und wird dauerhaft gespeichert. Die unterstützten Sprachen werden zentral über `config/locales.php` verwaltet; Middleware, Validierung und Auswahl lesen diese Konfiguration dynamisch. Vorerst sind ausschließlich Deutsch und Englisch aktiviert, weitere Sprachen sollen später ohne erneuten Architekturumbau ergänzt werden können. Freie Nutzerinhalte bleiben in ihrer Originalsprache. Die manuelle Zwei-Sprachen-Abnahme ist Bestandteil des Betatests in `docs/QUALITY_CHECKLIST.md`.

### Relevante Dateien, Klassen und Tabellen

- Dokumentation: `docs/PROJECT_CONTEXT.md`, `docs/SESSION_HANDOFF_01.md`, `docs/SESSION_HANDOFF_02.md`, `docs/SESSION_HANDOFF_03.md`
- Platzpflege: `app/Http/Controllers/PlaceSuggestionController.php`, `PlaceInfoSuggestionController.php`, `PlaceFeatureController.php`, `PlaceOpeningHoursController.php`, `PlacePriceController.php`
- Moderation/Anwendung: `app/Services/ChangeRequestApplyService.php`, `ChangeRequestModerationService.php`, Admin-`ChangeRequestController`
- Reviews: `app/Services/PlaceReviewService.php`, `ReviewModerationService.php`, `app/Http/Controllers/PlaceReviewController.php`, `PlaceProfileController.php`, `app/Http/Controllers/Admin/ReviewReportController.php`
- Review-UI: `resources/views/places/_reviews.blade.php`, `resources/views/places/_review-cards.blade.php`, `resources/views/reviews/history.blade.php`
- Kern-Tabellen: `places`, `change_requests`, `audit_logs`, `place_reviews`, `place_review_versions`, `place_review_reports`
- Review-Cleanup-Migration: `database/migrations/2026_09_20_220000_drop_legacy_review_tables.php`
- Tests: insbesondere `tests/Feature/PlaceReviewServiceTest.php`
- Foto-MVP: `config/photos.php`, `app/Services/PlacePhotoService.php`, `PhotoProcessingService.php`, `app/Jobs/ProcessPhotoUpload.php`, `PhotoAssetController.php`, `PlacePhotoController.php`, `PhotoHelpfulVoteController.php`, `PhotoReportController.php`, Admin-`PhotoModerationController.php`
- Foto-Daten: `photos`, `place_photos`, `photo_helpful_votes`, `photo_reports`, `place_photo_settings`; Migration `2026_09_20_230000_create_photo_mvp.php`
- Foto-UI/Tests: `resources/views/places/_photo-picker.blade.php`, `resources/views/places/_reviews.blade.php`, `resources/views/places/show.blade.php`, `resources/views/dashboard.blade.php`, `resources/views/admin/photos/index.blade.php`, `resources/views/admin/photos/library.blade.php`, `tests/Feature/PlacePhotoServiceTest.php`
- Platz-Zusammenführung: `app/Services/PlaceMergeService.php`, `PhotoMergeConflictService.php`, Admin-`PlaceMergeController.php`, Migration `2026_09_21_120000_create_place_merge_tables.php`, `tests/Feature/PlaceMergeServiceTest.php`
- V1-Merkmalskatalog: `database/seeders/V1FeatureCatalogSeeder.php`, Migration `2026_09_21_180000_finalize_v1_feature_catalog.php`, `app/Http/Controllers/Admin/FeatureCatalogController.php`, `app/Services/FeatureWorkflowService.php`, `PlaceTypeFeatureService.php`, `resources/views/admin/features/`, `tests/Feature/FeatureCatalogAdminTest.php`
- Merkmalsdaten: `feature_categories`, `features`, `feature_workflows`, `feature_place_types`, `catalog_releases`, `place_features`, `translations`, `audit_logs`

### Zuletzt getroffene Entscheidungen

- Durch Moderation entfernte Projekte/Plätze dürfen weiterhin historische Scores beeinflussen, soweit diese historischen Werte bewusst erhalten werden.
- Wird dagegen eine einzelne Review samt Bewertung entfernt, fließt sie ab dem Entfernungszeitpunkt nicht mehr in den aktuellen Score ein; bereits gebildete historische Monatswerte bleiben bestehen.
- Das sichtbare Verhalten sollte sich durch den technischen Review-Cleanup nicht ändern.
- Die drei Handoff-Dateien bleiben als unverändertes Archiv bestehen; diese Datei ist der primäre konsolidierte Einstiegspunkt.
- Review-Fotos werden erst nach Freigabe durch einen Moderator oder höheren Rang öffentlich; maximal fünf aktive Fotos pro Nutzerreview und Platz.
- Helpful-Stimmen sind pro Nutzer und Foto eindeutig; der Autor darf das eigene Foto nicht bewerten.
- Titelbildpriorität: persistenter Zufallsfallback, Helpful-Stimmen, Admin-Override, später verifizierter Owner. Ein Gleichstand ersetzt das bereits gewählte Bild nicht.
- Verifizierte Besuche und verifizierte Platzbesitzer bleiben ausdrücklich nach V1.
- Rezensionen werden im Platzprofil verbindlich in Paketen zu fünf per Cursor nachgeladen; kein automatisches Endlos-Scrollen.
- Merkmale werden je Platztyp ausdrücklich als `standard`, `extended` oder `hidden` eingeordnet; „unbekannt“ bleibt ein eigener Zustand und ist nicht „nein“.
- Die Kategorie `dogs` / „Mit Hund“ / „Travelling with dogs“ ist Teil des V1-Katalogs; dazu gehört ausdrücklich `dog-water-station` / „Hundetränke“.
- Der Initial-Seeder darf einen katalogisierten Release nur einmal anwenden und spätere Adminpflege nicht bei jedem Seed-Lauf zurücksetzen.
- Die Iconzuordnung von Merkmalen ist derzeit in `FeatureWorkflowService::iconNameFor()` hartcodiert. Später soll jedes Merkmal ein administrativ auswählbares Icon mit Vorschau erhalten; die bestehende Zuordnung bleibt dann nur Fallback.
- Für neue Besucher gilt bei der Sprache: vorhandene manuelle Auswahl vor gespeicherter Auswahl vor Browsersprache; Deutsch bei deutscher Browsersprache, ansonsten Englisch.
- Vorerst sind nur Deutsch und Englisch aktiv. Systemseitige und redaktionelle Inhalte müssen jedoch locale-unabhängig angelegt werden, damit später weitere Sprachen wie Französisch ergänzt werden können. Kurze Referenztexte verwenden die generische `translations`-Struktur; längere Inhalte erhalten eigene `*_translations`-Tabellen.
- Freie Nutzerinhalte wie Rezensionen, Platzbeschreibungen und Supportnachrichten bleiben zunächst in ihrer Originalsprache. Eine automatische Übersetzung ist ein möglicher späterer, separater Layer.

### Offene Punkte und bekannte Probleme

- Für den realen Betrieb müssen Queue-Worker, Imagick mit WebP und optional HEIC/HEIF sowie PHP-Uploadgrenzen von mindestens 50 MB pro Datei verifiziert werden.
- Die gezielten Foto-, Review- und Locale-Tests sowie die bisherigen Browserprüfungen liefen lokal erfolgreich. Die früheren Locale-bezogenen `UserProfileTest`-Fehler sind behoben. Die bekannte repositoryweite Pint-Baseline muss unabhängig davon weiterhin separat bewertet werden.
- Die **administrative Katalogverwaltung** für Kategorien/Merkmale ist funktional abgenommen, ihre Bedienung bleibt aber als späterer Ergonomie-Pass vorgemerkt. Die **Merkmalsbearbeitung im Platzprofil** wurde dagegen inzwischen auf kategorieweises Quick-Edit umgebaut und vom Nutzer praktisch sehr positiv abgenommen.
- Die technische DE/EN-Umstellung ist abgeschlossen; die manuelle Zwei-Sprachen-Abnahme läuft im Betatest. Performance-Härtung bleibt offen.

### Konkret nächster sinnvoller Schritt

Im aktiven V1-Block **Mobile/Desktop, Accessibility und UX-Fehlerzustände** sind Startseite, Platzprofil sowie die Platz-anlegen/-bearbeiten-Flows für den ersten mobilen Durchgang praktisch abgenommen. Die Merkmalsbearbeitung wurde dabei wiederholt direkt auf dem Smartphone getestet. Als nächste offene Teile dieses Blocks bleiben vor allem Accessibility, Fokusführung, Tastaturbedienung sowie Lade-, Leer-, Validierungs- und technische Fehlerzustände; weitere Restbereiche wie Reviews/Fotos, Auth/Profil, Favoriten/Benachrichtigungen und Administration werden nur noch gezielt nach verbleibenden mobilen/UX-Auffälligkeiten geprüft.

## 5. Architektur und technischer Aufbau

### Stack und Laufzeit

- PHP 8.4, Composer 2
- Laravel 13, Livewire 4
- MySQL 8
- Blade, Flux UI und Tailwind CSS
- Leaflet mit OpenStreetMap; OSM/Photon für Geocoding beziehungsweise Reverse Geocoding
- Node 24 und npm 11 im dokumentierten lokalen Setup
- Laravel Herd als lokales Setup; typische URL `camperwolf.test`
- Aktive Sprachen Deutsch und Englisch, technisch erweiterbar über `config/locales.php`; Anwendungstimezone `Europe/Berlin`

`composer.json` kann noch eine breitere PHP-Anforderung enthalten, der aktuelle Lockfile-/CI-Stand benötigt effektiv PHP 8.4. Die GitHub-Actions-Konfiguration wurde deshalb auf PHP 8.4 angehoben.

### Struktur und Zusammenspiel

`routes/web.php` enthält die öffentlichen, authentifizierten und administrativen Webrouten; `routes/settings.php` die Kontoeinstellungen, `routes/console.php` Konsolenrouten. Controller koordinieren häufig Query-Builder-basierte Domänenlogik mit Services. Nur ein Teil der Domäne besitzt Eloquent-Models; vorhandene Models sind bewusst eher schlank.

Wichtige Controller:

- Browse/Profile: `PlaceBrowseController`, `PlaceProfileController`
- Anlage/Pflege: `PlaceSuggestionController`, `PlaceInfoSuggestionController`, `PlaceFeatureController`, `PlaceOpeningHoursController`, `PlacePriceController`
- Reviews: `PlaceReviewController`, Admin-`ReviewReportController`
- Profile: `UserProfileController`, `UserProfilePhotoController`
- Administration: `ChangeRequestController`, `AuditLogController` und weitere Rollen-/Support-Controller

Wichtige Services:

- Merkmale: `PlaceTypeFeatureService`, `FeatureWorkflowService`
- Zeit/Preis: `OpeningHoursPeriodService`, `PricePeriodService`
- Änderungen: `ChangeRequestApplyService`, `ChangeRequestModerationService`
- Rechte: `PermissionService`, `RolePermissionService`
- Nutzer: `PublicHandleService`, `UserNotificationService`
- Gamification: `XpService`, `LevelService`, `BadgeService`
- Reviews: `PlaceReviewService`, `ReviewModerationService`
- Sonstiges: `SupportContextService`, `DemoDataResetService`

Wichtige Models sind `User`, `UserProfile`, `UserProfileSocialLink`, `Photo`, `Role` und `Permission`. Ob seit den Handoffs weitere Models hinzugekommen sind, muss im aktuellen Code geprüft werden.

### Views und Komponenten

Platzlisten, Karte und Profile sind Blade-/Livewire-basiert. Platzprofile setzen sich aus Teilansichten für Informationen, Merkmale, Öffnungszeiten, Preise und Reviews zusammen. Wiederverwendbare Komponenten und Icons sollen zentral bleiben; dieselbe Aktion soll nicht an mehreren Stellen mit abweichendem Verhalten dupliziert werden.

### Migrationen und Seeder

Migrationen bilden die eigentliche Schemahistorie. Besonders wichtig sind die Migrationen für Platzversionen, Change Requests, Öffnungszeiten, strukturierte Preise, Nutzerprofile/Gamification und Reviews. `2026_09_20_220000_drop_legacy_review_tables.php` entfernt die nicht mehr verwendete alte Review-Struktur. Wegen nicht-transaktionaler MySQL-DDL sind Schemaänderungen defensiv und wiederanlauffähig zu planen.

Seeder und Demo-Reset existieren. Alte Seed-/Demo-Daten dürfen nicht als Beleg für aktuelles Produktverhalten verwendet werden. Vor Änderungen an Seedern immer aktuelle Migrationen und UI-Flows prüfen.

### Jobs, APIs, Authentifizierung und Tests

Die Laravel-Job-Infrastruktur verarbeitet Foto-Uploads über `ProcessPhotoUpload`; bis zur produktiven Worker-/Cron-Konfiguration kann `composer background:run` fällige Scheduler- und Queue-Aufgaben einmalig ausführen. Extern werden derzeit Karten- und Geocoding-Dienste genutzt; ein amtlicher Datensync ist noch nicht implementiert.

Authentifizierung basiert auf Laravel/Fortify. Migrationen beziehungsweise Grundlagen für Zwei-Faktor-Authentifizierung und Passkeys sind vorhanden. Autorisierung erfolgt über Rollen, Berechtigungen und verifizierte Auth-Gruppen; unerlaubte interne Ziele sollen keine Informationen über ihre Existenz preisgeben.

Feature- und Service-Tests decken zentrale Flows ab. Zuletzt liefen lokal `PlaceReviewServiceTest` mit 14 Tests/48 Assertions und `PlacePhotoServiceTest` vollständig erfolgreich. Der letzte dokumentierte Gesamtlauf hatte zuvor 98 bestandene und zwei bereits vorhandene fehlschlagende Profiltests; daher nicht behaupten, die vollständige Suite sei grün.

## 6. Datenmodell

### Plätze und Typen

- `places`: zentraler Ort mit Koordinaten, Status und Kernidentität. Dokumentierte Statuswerte sind `draft`, `pending`, `published`.
- `place_types` plus Übersetzungen: V1-Typen `campground`, `motorhome-pitch`, `tent-site`, `parking`, `rest-area`, `free-pitch`, `service-station`, `camping-outdoor`.
- Legacy-Typen `stay`, `stay-service`, `service` bleiben für Historie/Bestandsdaten erhalten, sind aber nicht mehr auswählbar.
- Rechtlicher Übernachtungsstatus und Platztyp sind getrennte Konzepte.

### Versionierte Platzdaten

Adressen, Übersetzungen, Details, Kontakte und Fahrzeugzuordnungen sind als eigene beziehungsweise versionierbare Strukturen angelegt, darunter `place_addresses`, `place_translations`, `place_details`, `place_contacts` und `place_vehicle_types`. Genaue Spalten und Gültigkeitsmechanismen müssen bei Änderungen direkt in den Migrationen geprüft werden.

### Merkmale

Merkmaldefinitionen, Kategorien und Typzuordnungen bilden den Katalog. `place_features` speichert bekannte Zustände beziehungsweise Werte am Platz; Notizen und Workflow-Metadaten ergänzen dies. Ein nicht gesetzter Wert darf nicht automatisch als „Nein“ interpretiert werden.

### Änderungen und Historie

- `change_requests`: enthält unter anderem Gruppierung (`group_uuid`), Ziel/Target, Original- und Vorschlagswerte als JSON, Status sowie Antragsteller-/Moderationsbezug.
- `suggestable_fields`: Whitelist beziehungsweise Steuerung dessen, was vorgeschlagen werden darf.
- `audit_logs`: nachvollziehbare direkte und moderierte Änderungen.
- Mehrere Felder eines Vorgangs werden gruppiert, damit eine fachlich zusammengehörige Änderung gemeinsam moderiert und angewendet werden kann.

### Öffnungszeiten

- `opening_hour_periods`: wiederkehrende Jahreszeiträume, ganzjährig, saisonal oder über den Jahreswechsel.
- `opening_hours`: Tages-/Zeitplan innerhalb eines Zeitraums.
- `opening_hours.period_schedule` ist ein virtuelles Vorschlagsfeld; der Elternzeitraum bleibt die Quelle der Wahrheit.

### Preise

- `price_products`
- `price_product_variants`
- `price_billing_units`
- `place_price_offers`
- `place_price_periods`
- `place_price_lines`

Die Struktur bildet Produkt, Variante, optionalen Anzeigenamen, Saison, Preiszeile, Abrechnungseinheit, Alters-/Längenbezug, Kautionen, Bedingungen und Merkmalsbezug ab. Preisstatus: `fixed`, `from`, `included`, `free`, `on_request`, `unknown`. Die ältere Tabelle `place_prices` kann noch existieren und muss als Legacy-Pfad behandelt werden.

### Nutzer, Rollen und Gamification

- `users`: Konto/Authentifizierung.
- `user_profiles`: öffentliche CW-ID (`public_handle`), optionaler Alias, Sichtbarkeiten, Profilfelder und Profiloptionen.
- `user_settings`: weitere nutzerspezifische Einstellungen.
- Rollen-/Berechtigungstabellen: Zuordnung von `Role` und `Permission` zu Nutzern.
- `xp_ledger`: unveränderliche Buchungsquelle für XP einschließlich Regelversion, Deduplizierung und Gegenbuchungen.
- Badge-/Achievement-Tabellen stammen aus `2026_09_19_193500_create_badge_achievement_system.php`; exakte Namen/Felder vor Änderungen in der Migration prüfen. `selected_badge_id` beziehungsweise die Auswahl eines angezeigten Titels ist Teil des Konzepts.

### Reviews

- `place_reviews`: aktuelle Review-Identität pro Nutzer und Platz, Sichtbarkeit und aktuelle Referenz.
- `place_review_versions`: historische Versionen samt fünf Dimensionen und Text.
- `place_review_reports`: Meldungen zur aktuellen sichtbaren Bewertung.

Die Legacy-Tabellen `reviews`, `review_questions`, `review_answers`, `review_helpful_votes` und `review_photos` werden durch die Cleanup-Migration entfernt. Alte Erzeugungsmigrationen bleiben korrekterweise als Schemahistorie erhalten.

### Fotos, Quellen und weitere Domänen

- `photos`: sichere Bildidentität über UUID, Verarbeitungs-/Moderationsstatus, private Pfade, Maße, Format, Größen und Moderationsinformationen.
- `place_photos`: Zuordnung zu Platz und Review einschließlich Aktivität, Sortierung und Eignung als Titelbild.
- `photo_helpful_votes`, `photo_reports` und `place_photo_settings`: eindeutige Helpful-Stimmen, Meldungen sowie persistenter Fallback/Vote/Admin-Titelbildstatus.
- `place_data_sources`: Zuordnung von Quellen zu Platzdaten; die Detailsemantik muss im Code/Migrationen geprüft werden.
- Favoriten, Benachrichtigungen sowie Supporttabellen (`support_articles`, `public_support_entries`, `support_tickets`, `support_messages`, `support_events`) sind implementiert.

User-, Owner-, Admin- und externe Quellidentitäten sind fachlich getrennt zu behandeln. Owner-Verifizierung ist noch kein fertiger Produktpfad.

## 7. Fachliche Regeln und Business Logic

### Platzanlage und Duplikate

Neue Plätze werden in zwei Schritten angelegt:

1. Name, Typ und Koordinaten. Danach kann bereits eingereicht werden.
2. Optional relevante Merkmale: zuerst Standardkategorien des Platztyps, darin alle Merkmale; keine Pflichtangaben.

Die Duplikatsuche berücksichtigt auch `pending`, ignoriert `draft`, sucht im Radius von 750 Metern und gewichtet Namensähnlichkeit. Sie warnt, blockiert aber nicht. Beim Bearbeiten wird der aktuelle Platz über `exclude_place_id` ausgeschlossen.

### Änderungen und Moderation

Normale Nutzer erzeugen gruppierte Änderungsvorschläge. Privilegierte Nutzer dürfen direkt ändern, aber auch diese Änderungen werden protokolliert. Beim Anwenden müssen veraltete Ausgangswerte erkannt werden; zusammengehörige Daten sollen nicht teilweise in einen inkonsistenten Zustand geraten. Details der atomaren Anwendung im aktuellen Service prüfen.

### Merkmale

Der Platztyp definiert Standardkategorien. Im öffentlichen Profil erscheinen Standardkategorien sowie Nichtstandard-Kategorien, sobald bekannte Daten vorliegen. „Weitere Merkmale“ öffnet den restlichen Katalog. Gezählt werden Standardmerkmale plus bekannte Extras. Die aktuelle Katalogzuordnung ist noch nicht fachlich final.

### Öffnungszeiten

Zeiträume wiederholen sich jährlich und dürfen ganzjährig, saisonal oder jahresübergreifend sein. Überschneidungen werden erst bei Annahme einer Änderung aufgeteilt; verbleibende Segmente bleiben erhalten. Unbekannte Angaben werden nicht als positive Information gespeichert und geben keine XP. `not_provided` bedeutet ausdrücklich, dass der Betreiber keine Angabe macht, und kann als nützliche Information gelten.

### Preise

Das strukturierte Preisobjekt ist der aktuelle Zielpfad. Im öffentlichen Profil werden nur tatsächliche Preise gezeigt; gibt es keine, erscheint genau ein allgemeiner Leerzustand. Strukturierte Preise haben Vorrang, Legacy-Preise dürfen höchstens getrennt als alte/weitere Preisinformation erscheinen. Die genaue Semantik von `unknown` gegenüber dem Öffnungszeitenstatus `not_provided` ist noch nicht abschließend geklärt.

### Historisierung, Löschung und Sperren

Änderungen müssen nachvollziehbar bleiben. Referenzdaten und problematische Orte sollen nach Möglichkeit deaktiviert, markiert oder gefiltert statt historienlos gelöscht werden. Moderativ entfernte Plätze/Projekte dürfen die historische Score-Darstellung weiter beeinflussen. Entfernte Reviews werden dagegen ab Entfernung nicht mehr im aktuellen Score berücksichtigt; historische Monatswerte bleiben unverändert erhalten.

### Review-Fotos

- Pro Nutzerreview und Platz sind höchstens fünf aktive Fotos erlaubt. Abgelehnte oder entfernte Fotos geben den Slot wieder frei; Fotos bleiben bei normalen Review-Versionen derselben Review-Identität erhalten.
- Neue Uploads haben die Statusfolge `processing` → `pending` → `approved`; zusätzlich existieren `rejected`, `removed` und `processing_failed`.
- Erst `approved` ist öffentlich. `processing` und `pending` sind nur für Autor und Moderation erreichbar. Löschen der Review deaktiviert ihre Fotozuordnungen und macht auch bekannte öffentliche UUID-URLs ungültig.
- Freigeben darf Rang Mod oder höher. Autor und Moderation dürfen im jeweiligen Berechtigungsumfang entfernen; freigegebene Fotos können gemeldet werden.
- Jeder Nutzer außer dem Autor hat genau eine Helpful-Stimme je Foto.
- Automatische Platz-Thumbnails berücksichtigen nur freigegebene, aktive und nicht administrativ ausgeschlossene Fotos. Priorität von niedrig nach hoch: einmalig persistierter Zufallsfallback, Helpful-Stimmen, Admin-Auswahl, später verifizierter Owner. Bei Stimmengleichstand bleibt die bisherige Auswahl bestehen, solange kein anderes Foto strikt mehr Stimmen hat.
- Upload: maximal 50 MB und 80 Megapixel; mindestens 900.000 Pixel insgesamt und keine Seite kleiner als 600 Pixel. Animierte oder mehrseitige Dateien werden abgelehnt.
- Verarbeitung: Auto-Orientierung, sRGB, vollständiges Entfernen von EXIF/GPS/XMP/Kommentaren und sonstigen Metadaten, proportionale Verkleinerung ohne Beschnitt oder Upscaling. Ausgabe als WebP: Detail maximal 1.920 px bei Qualität 82, Preview maximal 640 px bei Qualität 76. Das Original wird nach erfolgreicher oder endgültig fehlgeschlagener Verarbeitung gelöscht.
- Öffentliche Pfade verwenden UUID v4 ohne User-ID, Originalname oder Zeitstempel. JPEG, PNG und WebP werden unterstützt; HEIC/HEIF nur, wenn das installierte Imagick dies tatsächlich dekodieren kann.

### Externe Updates

Jede Quelle braucht eine stabile Identität. Imports müssen wiederholbar sein und Änderungen protokollieren. Externe Daten dürfen nicht ohne Konfliktregel über bestätigte Nutzer-, Besitzer- oder Admin-Daten geschrieben werden. Die konkrete Feldpriorisierung ist noch zu definieren.

### Besitzer, Community-Helfer und Vertrauen

Owner, Community-Helfer, Moderatoren und Admins sind unterschiedliche Rollen/Konzepte. Owner-Verifizierung und Community-Helfer sind spätere Ausbaustufen. XP oder Level allein sind kein Vertrauens- oder Berechtigungsscore.

## 8. Reviews und Bewertungssystem

### Aktuell gültige Regeln

- Fünf gleich gewichtete Dimensionen, jeweils 1 bis 5: Sauberkeit, Funktionalität, Zustand, Sicherheit und Nutzbarkeit.
- Der Gesamtwert ist das einfache arithmetische Mittel der fünf Dimensionen.
- Freitext ist optional, bei Verwendung 20 bis 2.000 Zeichen.
- Pro Nutzer und Platz gibt es genau eine aktuelle Review-Identität mit historisierten Versionen.
- Gültigkeitszeitraum: 12 Monate.
- Korrektur derselben Version: 30 Minuten; danach gilt eine Wartezeit von 28 Tagen bis zur nächsten regulären Änderung.
- Standardsortierung: neueste zuerst.
- Monatswerte werden für den zeitlichen Verlauf gespeichert beziehungsweise abgeleitet.
- Moderativ entfernte Reviews zählen ab Entfernung nicht mehr zum aktuellen Score. Historische Monatswerte vor der Entfernung bleiben bestehen.
- Durch Moderation entfernte Plätze/Projekte können dagegen in historischen Gesamtdarstellungen verbleiben.
- `verified_visit`-Felder dürfen existieren, haben in V1 aber weder Logik noch Gewichtung.

### Technische Umsetzung

Die Quelle der Bewertungsdimensionen ist `PlaceReviewService::DIMENSIONS`. Der Service validiert exakt den erwarteten Schlüsselsatz und ganzzahlige Werte von 1 bis 5. Er behandelt Erstbewertung, Versionierung, Sperrfristen, Score-Aggregation und XP-Auslöser. Öffentliche Abfragen müssen `is_public` beachten. Reports dürfen nur die aktuelle sichtbare Review-Version referenzieren.

`place_reviews`, `place_review_versions` und `place_review_reports` sind das gültige Modell. Die alten dynamischen Fragen-/Antworttabellen sind entfernt. Alte Migrationen, die diese Tabellen ursprünglich anlegen, sind keine Aufforderung, das alte Konzept wiederzubeleben.

### Sonderfälle

- Gleichzeitige erste Reviews werden durch DB-nahe Deduplizierung und Sperrlogik abgefangen.
- XP-Deduplizierung basiert auf technischen, sprachunabhängigen Schlüsseln.
- Die Review-Liste verwendet Cursor statt Seitenzahlen. Die technische `pr.id` wird zusätzlich zu `review_id` selektiert, weil Laravel sie zur eindeutigen Cursorbildung benötigt.
- Das Zusammenspiel von Review-Löschung, bereits vergebenem XP und eventuellen Gegenbuchungen muss bei künftiger Moderationsarbeit im aktuellen Code geprüft werden.
- Eine Review kann sofort veröffentlicht werden, während ihre Fotos asynchron verarbeitet und moderiert werden. Ein Fotofehler darf die bereits gespeicherte Review nicht duplizieren oder zurückrollen; die Oberfläche zeigt dafür eine separate Warnung.

### Historische/veraltete Varianten

Das frühere dynamische Modell mit `review_questions`, `review_answers`, Helpful Votes und `review_photos` wurde bewusst verworfen. Es soll nicht parallel weiterentwickelt oder für neue Features reaktiviert werden.

## 9. Nutzerprofile / Rollen / Rechte

Jeder Nutzer besitzt eine dauerhafte öffentliche CW-ID im Format `CW-XXXXX` über `user_profiles.public_handle`. Ein optionaler `public_alias` kann einmal finalisiert werden (`alias_finalized_at`), ersetzt die CW-ID aber nicht. Routen können CW-ID und Alias auflösen. `User::publicName()` bevorzugt Alias, dann CW-ID, dann Kontoname.

Profilfelder besitzen granulare Sichtbarkeit `public`, `registered` oder `private`. Das Geburtsdatum wird nie öffentlich angezeigt; höchstens ein berechnetes Alter. `show_join_date` steuert das Beitrittsdatum. `show_gamification` verbirgt nur die Darstellung; XP werden weiter gesammelt. Fahrzeugtypen stammen aus dem zentralen Katalog. Social Links sind bewusst vorerst deaktiviert.

Rollen:

- Normale Nutzer: Beiträge, Reviews und Änderungsvorschläge im erlaubten Umfang.
- Besitzer: fachlich vorgesehen, aber Verifizierung und endgültige Rechte noch nicht implementiert.
- Community-Helfer: vorgesehen, noch nicht implementiert; nicht automatisch aus XP ableiten.
- Moderatoren: prüfen Meldungen und Änderungsvorschläge gemäß Berechtigungen.
- Admins: direkte Pflege und Administration, weiterhin mit Audit-Log.

Interne, nicht erlaubte Bereiche antworten neutral mit 404. Rechte dürfen nicht nur in der UI versteckt, sondern müssen serverseitig erzwungen werden.

## 10. UI / UX / Produktentscheidungen

- Funktionsorientierter, kompakter Einstieg statt Marketing-Landingpage oder Login-Zwang.
- Gäste können Kerninhalte lesen und suchen; beitragende Aktionen führen verständlich zur Anmeldung.
- Platzprofile bündeln Stammdaten, Merkmale, Öffnungszeiten, Reviews und Änderungswege. Der eigenständige öffentliche Preisblock ist für die erste Beta ausgeblendet; das Backend bleibt erhalten.
- Ein globaler Bearbeiten-Button führt in den zentralen Informations-/Änderungsflow; keine widersprüchlichen Doppel-Links.
- Merkmalsanzeige priorisiert platztyprelevante Kategorien und blendet weitere bei Bedarf ein.
- Kartenposition ist direkt bearbeitbar; währenddessen erscheint die nicht blockierende Duplikatprüfung.
- Platztyp- und Fahrzeugfilter bleiben als kompakte Mehrfachauswahl-Dropdowns. Platztypen zeigen im neutralen Zustand alle Optionen ausgewählt; alle oder keine Auswahl bedeuten jeweils "keine Einschränkung". Fahrzeugtypen starten neutral ohne Auswahl. Für diese beiden Filter werden keine zusätzlichen aktiven Filterchips erzeugt.
- Reviews zeigen aktuelle Werte, Details und Monatsverlauf. Zunächst werden fünf angezeigt; weitere Pakete zu je fünf werden per stabilem Cursor ohne vollständigen Seitenreload angehängt.
- Review-Formulare erlauben bis zu fünf Fotos; Status und Nachupload werden bei der eigenen Review verwaltet. Freigegebene Fotos erscheinen direkt bei der Review mit Helpful- und Meldefunktion. Platz-Ergebnislisten verwenden das nach der festgelegten Priorität bestimmte Preview als Thumbnail.
- Profile sind mobil kompakt; dokumentierte Avatargrößen: 72 × 72 öffentlich, 96 × 96 im Editor. Der Bearbeiten-Button soll kompakt bleiben.
- Begriffe, Icons und Übersetzungen sollen zentral sein. Harte deutsche Texte und uneinheitliche Datumsformate müssen noch bereinigt werden.
- Systemseitige und redaktionelle Inhalte einschließlich Hilfe, Roadmap und späterer News müssen übersetzbar sein. Nutzerinhalte bleiben im Original; automatische Übersetzung gehört nicht zum aktuellen V1-Block.
- Der einmalige Welcome-Splash nutzt `localStorage` mit `camperwolf.welcome-seen.v1`.
- Kontextuelle Hilfe-/Bug-Icons und die Glockenoptik gelten als eingefroren und sollten ohne konkreten Anlass nicht neu gestaltet werden.
- Mobile und Desktop wurden wiederholt praktisch geprüft; weitere UX-Arbeit erfolgt ab Beta gezielt anhand realer Auffälligkeiten statt als eigener großer Pre-Beta-Block.
- Referenzgerät für den realen Mobiltest: Samsung Galaxy S22 Ultra mit Firefox unter Android; lokale Entwicklungsinstanz wird im selben WLAN direkt auf dem Gerät getestet.

## 11. Externe Datenquellen / APIs

Aktuell vorhanden:

- Leaflet/OSM für Karten.
- OSM/Photon für Geo- beziehungsweise Reverse-Geocoding.

Produktiv beziehungsweise aktuell umgesetzt:

- wiederholbare Import-/Sync-Läufe mit stabiler Source-Identität;
- Niedersachsen-Hub/Destination-One, NRW TFIS und Bayern ATKIS als getrennte produktive Quellen;
- External-Record-/Source-Layer mit Provenienz, Missing-Markierung statt automatischem Löschen sowie Review-/Dubletten-Workflows;
- quellenübergreifende Dublettengruppen und manuelle dauerhafte Zuordnung externer Records zu Camperwolf-Plätzen;
- Scheduler für die produktiven Quellen;
- Konfliktprinzip: externe Daten überschreiben bestätigte manuelle Community-/Owner-/Admin-Daten nicht blind.

Weitere Quellen werden nur ergänzt, wenn Datenqualität, Lizenz und Wartungsaufwand passen. DZT ist wegen fehlender verlässlicher API-Freigabe zurückgestellt. OSM bleibt Kartengrundlage/Geocoding-Helfer, wird wegen der ODbL-Share-Alike-Fragen aber vorerst nicht als allgemeine Importquelle für die Camperwolf-Datenbank verwendet.

Prioritätsgrundsatz: Externe Daten sind ein eigener Informationsgeber. Bestätigte Nutzer-, Owner- und Admin-Angaben dürfen nicht pauschal durch einen neueren Import ersetzt werden.

## 12. Final entschiedene Punkte

Die folgenden Punkte gelten als **FINAL / AKTUELL GÜLTIG**, solange kein neuer, ausdrücklich dokumentierter Grund sie ändert:

- **FINAL / AKTUELL GÜLTIG:** `docs/PROJECT_CONTEXT.md` ist der primäre konsolidierte Einstiegspunkt; die drei Handoff-Dateien bleiben unveränderte historische Quellen.
- **FINAL / AKTUELL GÜLTIG:** Öffentliche Kernnutzung ohne Konto; Beiträge benötigen Authentifizierung.
- **FINAL / AKTUELL GÜLTIG:** Koordinaten sind primär, Adresse ist ergänzend.
- **FINAL / AKTUELL GÜLTIG:** Geld beeinflusst Score, Ranking und Sichtbarkeit nicht.
- **FINAL / AKTUELL GÜLTIG:** „Unbekannt“ ist nicht „Nein“.
- **FINAL / AKTUELL GÜLTIG:** Neue Plätze werden in zwei Schritten angelegt; Schritt 2 ist vollständig optional.
- **FINAL / AKTUELL GÜLTIG:** Duplikate erzeugen eine Warnung, keinen harten Block.
- **FINAL / AKTUELL GÜLTIG:** Die acht aktuellen V1-Platztypen sind maßgeblich; Events sind kein Platztyp.
- **FINAL / AKTUELL GÜLTIG:** Normale Änderungen laufen als moderierte, gruppierte `change_requests`; privilegierte direkte Änderungen bleiben auditiert.
- **FINAL / AKTUELL GÜLTIG:** Öffnungszeiten und Preise bleiben technisch strukturierte, saisonale Daten; Legacy-Preise sind kein Zielmodell. Für die erste öffentliche Beta sind der eigenständige Preisblock und der Preisfilter jedoch ausgeblendet. Das Preisbackend bleibt für eine spätere Owner-/Betreiberphase erhalten.
- **FINAL / AKTUELL GÜLTIG:** Das Review-System nutzt fünf feste, gleich gewichtete Dimensionen und die drei aktuellen Review-Tabellen.
- **FINAL / AKTUELL GÜLTIG:** Gelöschte/moderativ entfernte Reviews zählen ab Entfernung nicht zum aktuellen Score; ihre frühere historische Wirkung bleibt bestehen.
- **FINAL / AKTUELL GÜLTIG:** Moderativ entfernte Plätze/Projekte dürfen historische Scores weiter beeinflussen.
- **FINAL / AKTUELL GÜLTIG:** Verified Visits gewichten Reviews in V1 nicht.
- **FINAL / AKTUELL GÜLTIG:** Verifizierte Besuche und verifizierte Platzbesitzer werden vollständig auf nach V1 verschoben.
- **FINAL / AKTUELL GÜLTIG:** Review-Fotos werden ohne Mod-Freigabe nie öffentlich; maximal fünf aktive Fotos je Nutzerreview/Platz.
- **FINAL / AKTUELL GÜLTIG:** Fotoverarbeitung erfolgt proportional, ohne Beschnitt, ohne Upscaling und ohne Metadaten; gespeichert werden nur die beiden WebP-Varianten.
- **FINAL / AKTUELL GÜLTIG:** Foto-Helpful-Votes sind eindeutig pro Nutzer/Foto und für den Autor ausgeschlossen.
- **FINAL / AKTUELL GÜLTIG:** Thumbnail-Priorität ist Zufallsfallback → Helpful-Votes → Admin-Override → später verifizierter Owner; Moderationsausschluss bleibt immer vorrangig und ein Vote-Gleichstand wechselt das aktuelle Bild nicht.
- **FINAL / AKTUELL GÜLTIG:** Die CW-ID bleibt dauerhaft; ein Alias ergänzt sie nur.
- **FINAL / AKTUELL GÜLTIG:** XP stammen aus einem Ledger mit Regelversionen, Deduplizierung und Gegenbuchungen; Level sind kein Trust Score.
- **FINAL / AKTUELL GÜLTIG:** Externe Quellen sind getrennte Akteure und überschreiben manuelle Daten nicht blind.
- **FINAL / AKTUELL GÜLTIG:** Der DatenScore misst ausschließlich Vollständigkeit. Basisdaten zählen 75 %, relevante bekannte Merkmale 25 %. Er ist kein Qualitäts-, Aktualitäts- oder Vertrauensscore.
- **FINAL / AKTUELL GÜLTIG:** Social Links sind vorerst deaktiviert.
- **FINAL / AKTUELL GÜLTIG:** Technische Slugs sind englisch, kleingeschrieben und kebab-case; Datenbankfelder snake_case.

## 13. Veraltete oder verworfene Ansätze

| Alter Ansatz | Warum verworfen/ersetzt | Aktueller Ersatz | Möglicher Altcode |
|---|---|---|---|
| Langer Assistent zur Platzanlage | Zu hohe Einstiegshürde | Zwei-Schritt-Anlage, Schritt 2 optional | `draft-details.blade.php` und `draft-review.blade.php` können noch vorhanden sein; Controller-Verhalten prüfen |
| Fest verdrahtete `QUICK_FEATURES` | Nicht typ- und kataloggerecht | `PlaceTypeFeatureService` und zentraler Merkmalkatalog | Nach Restverweisen suchen |
| Alias ersetzt CW-ID | Verliert stabile Identität | Dauerhafte CW-ID plus optionaler Alias | Feldreste wie `handle_finalized_at` möglich |
| Dynamische Review-Fragen/Antworten | Parallelmodell, unnötige Komplexität | Fünf feste Dimensionen in `PlaceReviewService` | Alte Erzeugungsmigrationen bleiben; Tabellen werden später gedroppt |
| `review_photos` als Review-Fotopfad | Gehört zum verworfenen Review-Modell | Foto-MVP auf Basis `photos`/`place_photos` plus `place_review_id` | Tabelle wird durch Cleanup-Migration entfernt; nicht reaktivieren |
| Globaler Bearbeiten-Link direkt auf `#features` | Umgeht vollständigen Änderungsflow | Route zu `places.info-suggest.edit` | Alte Links suchen |
| Öffentlicher Preisblock/-filter in der ersten Beta | Für normale Nutzer zu komplex und vor Owner-Verifizierung noch wenig sinnvoll | Vorläufig vollständig aus öffentlichem Profil und Browse ausgeblendet; Backend bleibt erhalten | `PlacePriceController`, Preisservices/-tabellen und Editor bewusst nicht löschen |
| Events als Platztyp | Events sind zeitliche Inhalte, keine Orte | Späteres separates Event-Modul | Keine Reaktivierung in `place_types` |
| XP aus Audit-Log oder einfachem Zähler | Nicht sauber deduplizier-/revidierbar | `xp_ledger` mit Gegenbuchungen | Alte Berechnungsreste prüfen |
| Marketing-Landingpage/Login-only | Kernnutzen soll öffentlich erreichbar sein | Öffentliche Suche/Karte/Profile | Kein Rückbau ohne neue Produktentscheidung |

## 14. Bekannte Probleme / Risiken / technische Schulden

- Der generische Repository-Workflow `composer ci:check` kann weiterhin an der historischen Pint-Baseline scheitern, bevor PHPUnit läuft. Fachfremde Dateien deshalb nicht massenhaft nur für Style anfassen; für neue Änderungen gezielte Tests/Formatter verwenden.
- Strukturierte und Legacy-Preisstrukturen können technisch parallel existieren. Das ist aktuell akzeptiert, weil die öffentliche Preis-UI deaktiviert ist; vor einer späteren Owner-Freigabe erneut konsolidieren.
- Die DataScore-Dirty-Invalidierung hängt zentral an `PlaceHistoryService`. Neue Mutationspfade, die Platzdaten ohne History ändern, müssen deshalb entweder History schreiben oder den Score explizit dirty markieren. Ein Full-Rebuild bleibt als Sicherheitswerkzeug verfügbar.
- Bei Platztypen ohne relevante sichtbare Merkmale wird der Merkmalsteil des DatenScores technisch als vollständig behandelt. Das ist für die aktuelle Formel bewusst so entschieden.
- Der Admin-Full-Rebuild des DatenScores läuft als Artisan-Job ohne persistenten Batch-Fortschritt.
- Badge-/Achievement-Katalog und langfristige Balancierung sind nicht endgültig.
- Die administrative Merkmalsverwaltung ist funktional, aber ein späterer Ergonomie-/Icon-Pass bleibt sinnvoll.
- MySQL-DDL ist nicht zuverlässig transaktional; größere Migrationen benötigen weiterhin Backup- und Wiederanlaufplan.
- Fotoverarbeitung benötigt den produktiven Queue-Worker und Imagick/WebP; HEIC/HEIF bleibt abhängig von der installierten Serverdelegation.
- Offsite-Backup und weitergehende serverweite Härtung bleiben Operations-Themen nach dem Beta-Start. Keine pauschalen Firewall-/Portänderungen auf dem gemeinsam genutzten Plesk-Server.
- Owner-Verifizierung, Community-Helfer, Check-in/Verified Visit und weitergehende Trust-Mechaniken bleiben bewusst nach der ersten Beta.
- Weitere Datenquellen nur nach Lizenz-/Qualitätsprüfung aufnehmen. Öffentliche Erreichbarkeit einer Quelle ist keine Nutzungslizenz.
- Vor dem bewussten Beta-Start nach dem letzten Code-Stand noch ein manuelles Plesk-Full-Backup erstellen.

## 15. Offene Punkte und Roadmap

### Unmittelbar vor öffentlicher Beta

1. finaler visueller Gegencheck des aktuellen Production-`main`;
2. manuelles vollständiges Plesk-Backup als Pre-Beta-Wiederherstellungspunkt;
3. Lockdown bewusst deaktivieren;
4. kurzer Gast-Gegencheck Registrierung/Login/Suche/Karte/Platzprofil sowie User-/Admin-Kernpfade;
5. Queue/Logs kontrollieren;
6. Beta-Gruppe informieren.

Keine weiteren Feature-Blöcke mehr vor diesem Schritt beginnen, sofern kein echter Fehler auftaucht.

### Während der Beta

- Fehler und Bedienungsprobleme priorisiert beheben;
- reale Rückmeldungen für v1.1-beta sammeln;
- Datenqualität und Import-/Dublettenfälle beobachten;
- Usage-Statistik nur anonym und intern wie bereits umgesetzt verwenden;
- Marketing/Community-Akquise bleibt auf der Agenda, wird aber separat geplant und hier nicht im Detail dokumentiert;
- Performance nur bei realen Befunden weiter optimieren.

### Nach erster Beta / später

- Owner-/Betreiber-Verifizierung und darauf aufbauend öffentliche strukturierte Preisangaben erneut aufnehmen;
- Check-in und Verified Visit;
- Community-Helfer mit eigenem Vertrauens-/Rechtemodell;
- Gruppen/Community-Funktionen und Events;
- zusätzliche geeignete Datenquellen;
- Tankstellen/Kraftstoffpreise als eigener V2-Block;
- Social Links nur bei neuer bewusster Entscheidung;
- native App bzw. weitergehende PWA-Funktionen;
- Offsite-Backup und weitere Operations-Automatisierung.

## 16. Nächste konkrete Schritte

Für die nächste Session gilt nicht mehr die alte nummerierte V1-Roadmap als Arbeitsreihenfolge. Die früheren Punkte 1-12 sind weitgehend abgeschlossen und bleiben weiter unten nur als historische Entwicklung erhalten.

Aktuelle Reihenfolge:
1. Pre-Beta-Full-Backup.
2. Lockdown aufheben und öffentliche Beta starten.
3. Erste reale Nutzer-Rückmeldungen sammeln.
4. Nur echte Fehler/Regressions sofort korrigieren.
5. Danach den nächsten Beta-Block aus Feedback und beobachteter Nutzung ableiten.

## 16a. Merkmals-Pflege – aktueller Stand und spätere Ideen

Die früher hier vorgemerkte Quick-Edit-Idee ist **nicht mehr nur geplant, sondern umgesetzt**. Verbindlicher aktueller Stand im Platzprofil:

- Jede Merkmalskategorie ist eine eigene Bearbeitungseinheit beziehungsweise ein eigenes Formular.
- Klick auf den Kategorien-Stift schaltet nur diese Kategorie in den Editmodus; es wird nicht automatisch jedes Merkmal als Formular aufgeklappt.
- Einzelne Merkmale werden erst durch Klick geöffnet. Statuswerte erscheinen direkt als Buttons/Chips; das frühere zusätzliche Status-Select im Platzprofil ist damit fachlich abgelöst.
- Status ohne relevante Zusatzfelder kann im Entwurf direkt gewählt werden. Bei Status mit Zusatzdaten erscheinen nur die durch den Workflow (`show_for`) relevanten Felder. Zusatzinformationen bleiben grundsätzlich optional, sofern die bestehende Workflow-Validierung nichts anderes verlangt.
- Freitext/Hinweis bleibt pro Merkmal erhalten, aktuell kompakt als einzeiliges Feld.
- Änderungen wirken sofort visuell auf die Chip-Farbe und erhalten einen blauen Änderungs-Punkt. Erst `Kategorie speichern` beziehungsweise `Änderungen einreichen` schreibt/sendet den Block.
- Nur tatsächlich gegenüber dem Ausgangszustand veränderte Merkmale werden übertragen.
- Pro Seite soll immer nur eine Kategorie gleichzeitig im Bearbeitungsmodus sein.
- `Unbekannt` bleibt eine bewusst auswählbare, gleichwertige Option.
- Kategorieentwürfe werden zusätzlich in `sessionStorage` gesichert und nach F5 wiederhergestellt; bei ungespeicherten Änderungen gibt es außerdem die Browser-Verlassen-Warnung.
- Bekannte Merkmale stehen zuerst; unbekannte Merkmale bleiben immer sichtbar, sind aber als eigener Block `x unbekannte Merkmale:` darunter abgetrennt.
- Eigene offene Vorschläge werden dem Einreicher sofort mit ihrem vorgeschlagenen Wert und Kennzeichnung `Ausstehend` gezeigt. Andere Nutzer sehen weiterhin nur den freigegebenen Stand.
- Sendet derselbe Nutzer für denselben Platz und dasselbe Merkmal vor Moderation erneut einen Vorschlag, wird der ältere offene Request auf `superseded` gesetzt. In der Moderationswarteschlange bleibt damit nur der neueste Vorschlag offen; die Historie bleibt nachvollziehbar.

Später weiterhin vorgemerkt ist eine **Massenpflege-/Arbeitslistenansicht** für Datenlücken: eine Zeile pro Platz mit Basisdaten wie Name/Adresse/Position plus einem gezielt unbekannten Merkmal und direkter Quick-Auswahl. Damit sollen Nutzer oder Helfer beispielsweise systematisch „alle Plätze ohne bekannten Hundestatus“ oder andere fehlende Felder abarbeiten können. Eine Kennzeichnung „von dir bereits bearbeitet / Vorschläge ausstehend“ ist dafür fachlich sinnvoll.

## 17. Zusammenarbeit / Session-Charakter

- Arbeitssprache ist Deutsch. Tippfehler des Nutzers sind inhaltlich zu verstehen und nicht unnötig zu kommentieren.
- Antworten sollen direkt, sachlich und kollegial sein. Der Nutzer möchte kritisches Feedback, wenn eine Idee fachliche oder technische Nachteile hat.
- Bei Produktlogik zuerst die konkrete Entscheidung und ihre Folgen erklären; bei klarer Lage eine Empfehlung geben statt viele gleichwertige Varianten offen zu lassen.
- Alternativen sind sinnvoll, wenn sie die Produktwirkung wirklich verändern. Abstrakte Best Practices allein sind kein Grund, bewusst getroffene Entscheidungen umzustoßen.
- Final entschiedene Punkte nicht ohne neuen Anlass wieder öffnen.
- Vor Änderungen den aktuellen `main`, relevante Migrationen, Services, Views und Tests lesen. Dokumentation ist Kontext, nicht automatisch technische Wahrheit.
- Kleine, reversible Änderungen und verständliche Commits haben sich bewährt. Große Refactorings ohne unmittelbaren Nutzen vermeiden.
- Genau sagen, was geprüft wurde. Lokale Tests oder Browsererfolg nicht behaupten, wenn sie nicht ausgeführt wurden.
- Nach Änderungen konkrete lokale Befehle nennen, besonders wenn Migration, Cache-Leerung oder Frontend-Build nötig sind.
- Der Nutzer übernimmt häufig die visuelle Browserprüfung. Dafür die betroffenen Seiten und erwarteten sichtbaren Änderungen knapp benennen.
- Während längerer technischer Arbeit kurze, verständliche Statusupdates geben. Umfangreiche oder schwer nachvollziehbare Werkzeugaktivität wirkte auf den Nutzer schon einmal unnötig beunruhigend, obwohl das Ergebnis korrekt war.
- Git/GitHub als Sicherheitsnetz und für reversible Arbeit nutzen, aber nicht als Begründung für unvorsichtige oder unnötig breite Änderungen.
- Entscheidungen und Begründungen so dokumentieren, dass ein neuer Chat nicht dieselben Grundsatzdiskussionen wiederholt.
- Bei Konflikten: neuere Produktentscheidung für die Absicht, aktueller Code für den technischen Iststand; die Differenz sichtbar machen.

## 18. Dinge, die ein neuer Chat vermeiden sollte

- Final entschiedene Konzepte ohne neuen sachlichen Grund erneut aufrollen.
- Legacy-Review-Tabellen, langen Platzassistenten, `QUICK_FEATURES` oder Alias-als-Ersatz-für-CW-ID reaktivieren.
- Funktionierenden Code nur zur stilistischen Vereinheitlichung großflächig umbauen.
- Handoff oder Dokumentation ungeprüft als alleinige technische Wahrheit behandeln.
- Umgekehrt aus vorhandenem Altcode ableiten, dass eine verworfene Produktidee wieder gültig sei.
- Alte Seed-/Testdaten mit gewünschtem aktuellem Verhalten verwechseln.
- „Unknown“ als „No“ speichern oder anzeigen.
- Externe Imports als pauschal autoritativ behandeln.
- XP/Level mit Vertrauen, Moderationsrecht oder Review-Qualität gleichsetzen.
- Bei Review-Moderation aktuelle und historische Score-Wirkung vermischen.
- Alte Migrationen löschen oder umschreiben, nur weil spätere Migrationen ein Modell ablösen.
- Bestehende Pint-/Testschulden versteckt in einen fachlichen Commit ziehen.
- Handoffs verändern oder löschen; sie bleiben Archiv.
- Die neue kategorieweise Merkmalsbearbeitung im Platzprofil wieder auf einzelne sofort absendende Merkmal-Formulare oder das alte Status-Dropdown zurückbauen.
- Unbekannte Merkmale im Platzprofil mit bekannten Merkmalen ungegliedert vermischen; sie sollen als eigener Block unter den bekannten Merkmalen sichtbar bleiben.

## 19. Schnellstart für neue Sessions

1. Camperwolf ist ein öffentlich nutzbares, community-gepflegtes Verzeichnis für Camping-, Stell- und Versorgungsorte.
2. Deutschland ist Startmarkt; das Modell soll international erweiterbar sein.
3. Aktueller Stack: Laravel 13, Livewire 4, PHP 8.4, MySQL 8, Blade/Flux/Tailwind, Leaflet/OSM.
4. `main` und tatsächlicher Code sind vor jeder Änderung neu zu prüfen.
5. Gäste dürfen suchen, filtern, Karte und Profile nutzen; Beiträge brauchen ein Konto.
6. Koordinaten sind primär; Adresse ist ergänzend.
7. Geld beeinflusst Score, Ranking und Sichtbarkeit nie.
8. „Unbekannt“ ist nicht „Nein“.
9. Die acht aktuellen Platztypen sind fest; Events gehören später in ein separates Modul.
10. Neue Plätze: zwei Schritte, zweiter Schritt optional, Duplikate nur warnen.
11. Veröffentlichte Plätze können vollständig geändert/vorgeschlagen werden; dieser Flow wurde praktisch bestätigt.
12. Community-Änderungen laufen gruppiert über `change_requests`; privilegierte Direktänderungen bleiben auditiert.
13. Öffnungszeiten und Preise sind strukturiert und saisonfähig.
14. Reviews nutzen fünf feste gleich gewichtete Dimensionen, Versionen und Reports.
15. Alte dynamische Review-Tabellen sind verworfen und werden durch Cleanup-Migration entfernt.
16. Entfernte Reviews zählen ab Entfernung nicht zum aktuellen Score; historische Monatswerte bleiben.
17. Moderativ entfernte Plätze/Projekte dürfen historische Scores weiter beeinflussen.
18. Verified Visits haben in V1 keine Review-Gewichtung.
19. Nutzer behalten eine permanente CW-ID; Alias ist optional und ergänzend.
20. XP kommt aus `xp_ledger`; Level ist kein Vertrauensscore.
21. Profile, Gamification, Favoriten, Benachrichtigungen und Support sind vorhanden.
22. Foto-MVP und Review-Cursor-Paginierung sind getestet, abgenommen und Bestandteil von `main`; fünf Review-Fotos, Mod-Freigabe, private Verarbeitung, Helpful-Votes und Thumbnail-Priorität sind fest.
23. Externe Open-Data-Importe kommen launchnah und dürfen manuelle Daten nicht blind überschreiben.
24. Die Platz-Zusammenführung ist umgesetzt, getestet und praktisch abgenommen; ihre Fachregeln nicht neu aufrollen.
25. Die vollständige DE/EN-Oberfläche ist technisch abgeschlossen; Deutsch und Englisch sind aktiv, weitere Sprachen bleiben architektonisch ergänzbar, Nutzerinhalte bleiben im Original.
26. Aktiver Block ist Mobile/Desktop, Accessibility und UX-Fehlerzustände; die manuelle DE/EN-Abnahme läuft parallel über die Qualitäts-Checkliste.
27. Startseite, Platzprofil sowie Platz anlegen/bearbeiten sind für den ersten mobilen Durchgang auf Galaxy S22 Ultra / Firefox praktisch abgenommen; die Merkmalsbearbeitung wurde dabei laufend auf dem Smartphone mitgetestet.
28. Merkmale im veröffentlichten Platzprofil werden kategorieweise bearbeitet: Kategorie aktivieren, einzelne Chips öffnen, Status direkt wählen, nur Änderungen gesammelt speichern/einreichen.
29. Eigene offene Merkmalsvorschläge bleiben sichtbar und `Ausstehend`; neuere Vorschläge desselben Nutzers für dasselbe Merkmal ersetzen ältere offene Requests (`superseded`).
30. Administration, Rollen-Vorschau und Sprache liegen im User-Dropdown; mobil sitzt die Benachrichtigungsglocke neben dem Profilbild.

## 20. Startanweisung für einen neuen Chat

Lies diese Datei vollständig, bevor du am Projekt arbeitest. Sie beschreibt den aktuell konsolidierten Stand; die drei Session-Handoffs sind historische Quellen und bereits eingearbeitet. Prüfe bei technischen Änderungen zusätzlich den tatsächlichen Code, Migrationen und Tests und benenne Widersprüche. Foto-MVP, Review-Paginierung, Platz-Zusammenführung, V1-Merkmalskatalog und die technische DE/EN-Oberfläche sind abgeschlossen und dürfen nicht auf veraltete Pfade zurückgebaut werden. Das aktive Teilprojekt ist Mobile/Desktop, Accessibility und UX-Fehlerzustände. Die manuelle Zwei-Sprachen-Abnahme läuft parallel über `docs/QUALITY_CHECKLIST.md`. Mische die bekannte Pint-Baseline nicht mit neuen Regressionen.

## 21. Fortschreibung – Karten-, Rollen- und erweiterte Filterarbeiten vom 2026-09-21

Dieser Abschnitt ergänzt den bisherigen Inhalt, ohne ältere Dokumentation zu ersetzen. Bei Widersprüchen zu früheren Status- oder „Nächste Schritte“-Abschnitten gilt diese Fortschreibung als neuerer Stand. Technischer Referenzstand ist `main` einschließlich Commit `628d20025f80941ba02014a15d234c28372e36b3`.

### Seit der letzten Konsolidierung umgesetzt und abgenommen

- Die vollständige deutsche und englische Oberfläche ist technisch abgeschlossen. Die zugehörigen gezielten Tests sowie anschließend die vollständige Testsuite wurden vom Nutzer mehrfach lokal erfolgreich ausgeführt.
- Nutzerinhalte bleiben vorerst in der Sprache, in der sie verfasst wurden. Eine spätere Übersetzungsschnittstelle kann als separater Layer alle Nutzerinhalte in die eingestellte Zielsprache übersetzen, gehört aber nicht zu V1.
- Das Locale-System bleibt über `config/locales.php`, Sprachdateien und Datenbankübersetzungen grundsätzlich um weitere Sprachen erweiterbar. Hilfetexte und spätere redaktionelle Inhalte wie News müssen ebenfalls übersetzbar angelegt werden; kurze Referenztexte können die generische `translations`-Struktur verwenden, längere Inhalte eigene `*_translations`-Tabellen.
- Favorisierte Plätze besitzen auf der Startseitenkarte eine eigene Pin-Farbe und bleiben dadurch zwischen den übrigen Treffern sichtbar. Der Hover-Zustand funktioniert weiterhin unabhängig davon.
- Die globale Navigationsleiste liegt nun verbindlich über Karten, Bildern und sonstigen Seitenelementen. Dies gilt insbesondere beim Scrollen auf Startseite und Platzprofil.
- Die ungefilterte Startseitenkarte beginnt mit einem stabilen Deutschland-Fokus. Sie versucht nicht mehr automatisch, sämtliche vorhandenen Pins in den sichtbaren Ausschnitt einzupassen. Andere Regionen bleiben durch Verschieben und Zoomen erreichbar.
- Der Kartenausschnittsfilter filtert die Ergebnisliste nach den aktuell sichtbaren Kartenkoordinaten. Programmatische Kartenbewegungen lösen keinen wiederholten Reload mehr aus; nur echte Nutzerinteraktionen aktualisieren den Ausschnitt. Ein einzelnes kurzes Neuladen nach einer Kartenbewegung ist das derzeit erwartete Verhalten.
- Der überflüssige Navigationslink „Karte“, der lediglich auf einen Anker der Startseite zeigte, wurde entfernt.
- Admin und Superadmin überspringen bei aktiv verwendeter Rolle den Vorschlags-/Moderationsschritt. Plätze, Fotos sowie Platzdetail-, Karten-, Öffnungszeiten-, Preis- und Merkmalsänderungen werden direkt veröffentlicht beziehungsweise angewendet. Audit-Logging, XP und Achievement-Zähler bleiben erhalten. Moderatoren erhalten diesen Bypass ausdrücklich nicht.
- Die Beschriftung für die Platzanlage unterscheidet privilegierte Direktanlage von normalem „Platz vorschlagen“.
- Der Support-Adminbereich verwendet keine MySQL-spezifische `FIELD()`-Sortierung mehr und bleibt dadurch auch unter SQLite testbar.
- Rollen-Vorschau und zugehörige Zugriffs-/Statusmeldungen folgen der gewählten Sprache.
- Profilaktivitäten und XP-Texte sind locale-fähig; die zuvor betroffenen `UserProfileTest`-Erwartungen wurden angepasst und anschließend bestätigt.

### Variable Merkmals- und Preisfilter

Die Startseitenfilter wurden um variable Werte erweitert. Die bisherigen einfachen Merkmalsfilter bleiben unverändert: Ein Klick auf ein normales Merkmal filtert weiterhin auf „vorhanden“ und schließt „unbekannt“ beziehungsweise „nicht vorhanden“ aus.

Neu hinzugekommen:

- `PlaceBrowseFacetService` ermittelt generische Facetten für numerische, Number/Unit- und Auswahlfelder aus den vorhandenen Merkmals-Workflows.
- Variable Merkmale erscheinen nur, wenn im aktuell relevanten Ergebnisset tatsächlich verwertbare Werte vorhanden sind.
- Zahlenwerte können über Min/Max-Eingaben und Bereichsregler eingeschränkt werden. Damit sind sowohl Mindestwerte, etwa Durchfahrtshöhe oder Stromstärke, als auch Höchstwerte, etwa Entfernungen oder Preise, ohne separate Sonderlogik möglich.
- Auswahlwerte erscheinen als aufklappbare Checkbox-Liste samt Trefferzahlen.
- Preise erscheinen nur für aktive, ausdrücklich über `price_products.is_searchable` freigegebene Preisarten und nur dann, wenn passende aktuelle Werte vorhanden sind.
- Maßgeblicher Preis ist je Platz und Preisart der aktuell gültige günstigste Ab-Preis. Aktive Saisonzeiträume werden gegen das aktuelle Datum ausgewertet; `fixed`, `from`, `included` und `free` werden berücksichtigt.
- Werden vorgeschlagene Min-/Max-Grenzen unverändert übernommen, bedeutet der Filter fachlich „Preis/Wert ist bekannt“.
- Facetten werden auf Basis der bereits gesetzten Suche, Kartenfläche, Favoriten und normalen Merkmale berechnet und lassen sich miteinander kombinieren.
- Aktive Wertefilter erscheinen als entfernbare Tags und sind optisch identisch zu den bisherigen aktiven Merkmalstags.
- Die verschachtelten Query-Parameter werden über `resources/views/partials/query-hidden-fields.blade.php` bei Sortierung und weiteren Filteraktionen erhalten.
- Es war keine Datenbankmigration erforderlich.
- Relevante Dateien: `app/Services/PlaceBrowseFacetService.php`, `app/Http/Controllers/PlaceBrowseController.php`, `resources/views/dashboard.blade.php`, `resources/views/partials/query-hidden-fields.blade.php`, `tests/Feature/PlaceBrowseVariableFilterTest.php`.
- Während der Einführung wurden ein Blade-Verzweigungsfehler und fehlende Trefferzahlen bei Auswahloptionen behoben. Der Regressionstest enthält nun auch einen Auswahlwert und deckt beide Darstellungsarten ab.
- Der Nutzer bestätigte zuletzt, dass der gezielte Test und die Startseitenfunktion nach den Korrekturen funktionieren.

### Aktuelle verbindliche V1-Roadmap

Erledigte Punkte werden weiterhin durchgestrichen und nicht aus der Roadmap entfernt:

- ~~Foto-MVP~~
- ~~Review-Paginierung und Review-UX~~
- ~~Duplikate sicher zusammenführen~~
- ~~Platztypen und Merkmalskatalog finalisieren~~
- ~~Vollständige deutsche und englische Oberfläche~~
- ~~Mobile/Desktop, Accessibility und UX-Fehlerzustände~~
- ~~Performance und Datenbankindizes~~
- **Rechte, Rate Limits und Missbrauchsschutz**
- Datenschutz, Rechtstexte und Löschprozesse
- Deployment, Backups und Monitoring
- Open-Data-Import als letzter größerer V1-Block

Der Block **Mobile/Desktop, Accessibility und UX-Fehlerzustände** gilt für V1 als abgeschlossen. Weitere Detailfehler oder Bedienungsauffälligkeiten werden im späteren Betatest als Bugfixing/Finetuning behandelt. **Performance und Datenbankindizes** sind nach vollständigem lokalen Regressionstest und Browsercheck endgültig abgeschlossen. Der aktive nächste Roadmap-Block ist **Rechte, Rate Limits und Missbrauchsschutz**.

### Verbindliche Arbeits- und Testweise

- Der Nutzer kann und möchte die Laravel-Tests auf seiner eigenen lokalen Windows-Installation selbst ausführen.
- Für die normale Weiterentwicklung wird **keine temporäre Testumgebung** benötigt. PHP, Composer oder Laravel Boost sollen dafür nicht erneut in einer temporären Arbeitsumgebung installiert werden.
- Nach Änderungen sind dem Nutzer die konkreten lokalen Befehle zu nennen, typischerweise `git pull`, bei UI-/Cache-Änderungen `php artisan optimize:clear`, gegebenenfalls `php artisan migrate` und anschließend ein gezielter `php artisan test --filter=...`.
- Der Nutzer meldet Testergebnisse und Browserbeobachtungen zurück; Fehler werden anhand dieser realen lokalen Ausgabe korrigiert.
- Eine Migration ist nur zu nennen, wenn der neue Commit tatsächlich eine Migration enthält.
- Tests dürfen nicht als lokal ausgeführt oder bestanden bezeichnet werden, wenn sie lediglich ergänzt und anschließend vom Nutzer ausgeführt wurden.
- Eine temporäre Laufzeit- oder Testumgebung soll nur dann erneut vorbereitet werden, wenn der Nutzer dies später ausdrücklich verlangt.

### Weiterhin vorgemerkt

- Die Iconzuordnung für Merkmale soll in einem späteren Ergonomie-Durchgang als administratives Feld „Icon“, idealerweise als Dropdown mit Vorschau, ergänzt werden. Der gespeicherte Iconname ist dann maßgeblich; die aktuelle hartcodierte Zuordnung bleibt Fallback.
- Die Bedienung der Merkmalsverwaltung ist funktional, aber noch fummelig und bleibt für denselben Ergonomie-Durchgang vorgemerkt.
- Performance der Facettenabfragen wurde im Roadmap-Block „Performance und Datenbankindizes“ unter realistischen Datenmengen geprüft und optimiert; weitere Indexänderungen sollen nur nach messbarem Nutzen erfolgen.

## 22. Fortschreibung – Mobile UX und kategorieweise Merkmalsbearbeitung vom 2026-09-22

Dieser Abschnitt ist für Mobile-/Profil-UX neuer als Abschnitt 21 und ersetzt dortige oder frühere Aussagen, falls sie widersprechen. Technischer Referenzstand: `main` bis einschließlich `0bb69ff88e2e9c00ae33c0be0e81ac57a9f57110`.

### Mobile Startseite

- Referenzgerät: Samsung Galaxy S22 Ultra, Firefox Android, reale lokale Instanz über dasselbe WLAN.
- Mobile Headerstruktur: links auf der Startseite Filter-Funnel, mittig Bereichsauswahl, rechts Benachrichtigungsglocke und Profilbild. Auf anderen Seiten führt links `CW` zur Startseite.
- Administration, Rollen-Vorschau und Sprache wurden aus separaten Desktop-Headerkontrollen in das gemeinsame User-Dropdown verschoben und stehen dort auf Desktop und Mobile zur Verfügung.
- Die mobile Startseite zeigt Karte oben und Ergebnisse darunter. Die Karte ist über einen sehr schmalen mittigen CSS-Doppelchevron ein-/ausklappbar; offen = Doppelchevron nach oben, eingeklappt = nach unten.
- Der Desktop-Kartenausschnittsfilter ist mobil verborgen. Eine spätere Nähe-/Radius-Suche per Browser-Geolocation ist vorgesehen, aber nicht Teil des aktuellen Schritts.
- Ergebnisüberschrift mobil: nur Trefferzahl, kein zusätzliches „Plätze“. Sortierung inklusive Score bleibt in derselben kompakten Zeile.
- Ergebniskarten sind mobil vertikal; Bilder etwa 16:9, Desktop bleibt kompakter.
- Mobiler Platz-vorschlagen/-anlegen-FAB ist rund, blau und unabhängig von der Karte positioniert.
- Die Startseite wurde vom Nutzer praktisch als sehr gut funktionierend bestätigt.

### Mobiles Platzprofil

- Außenabstände wurden reduziert, Titel erhält mobil die volle Breite; Statuschips stehen darunter statt den Titel einzuengen.
- Touchziele für Editaktionen wurden vergrößert.
- Merkmalskategorien sind mobil standardmäßig eingeklappt und zeigen `Kategorie (bekannt/gesamt)` plus einfachen Pfeil.
- Der Kategorien-Stift ist mobil im eingeklappten Zustand verborgen und erscheint erst nach dem Aufklappen rechts im Kategorienkopf; Desktop zeigt ihn weiterhin direkt.
- Review-/Score-Darstellung wurde bereits als gut lesbar beurteilt.
- Startseite, Platzprofil sowie Platz anlegen/bearbeiten gelten für den ersten mobilen Durchgang als abgenommen; die Merkmalsbearbeitung wurde dabei laufend auf dem Smartphone praktisch mitgetestet. Detailfeinschliff bleibt möglich.

### Kategorieweise Merkmalsbearbeitung

- Implementierung: `PlaceFeatureController::updateCategory`, Route `places.features.category.update`, Partial `resources/views/places/_feature-category.blade.php`, JavaScript in `resources/views/places/show.blade.php`.
- Kategorie = Formular. Einzelmerkmal = nur bei Klick geöffneter Inline-Editor.
- Statusoptionen werden als direkte Choice-Chips gerendert. `FeatureWorkflowService` bleibt Quelle für Statusoptionen, Detailtypen und `show_for`.
- Unterstützt werden damit generisch einfache Availability-/Ja-Nein-Status, Messwerte, Selectwerte, `number_unit`, Pricing/Money sowie Sonderworkflows wie Hunde und maximale Aufenthaltsdauer.
- Nur geänderte Feature-Shells werden beim Kategorie-Submit übertragen; unveränderte Inputs werden vor Submit deaktiviert.
- `sessionStorage` schützt lokale Entwürfe gegen F5; `beforeunload` warnt zusätzlich beim Verlassen. Wiederhergestellte Änderungen müssen weiterhin als ungespeichert erkennbar sein (blauer Punkt).
- Bekannte und unbekannte Merkmale bleiben optisch getrennt; unbekannte Merkmale sind auch außerhalb des Editmodus sichtbar.
- Community-Pending-Vorschläge werden für den Einreicher in `PlaceProfileController` überlagert und als `Ausstehend` dargestellt.
- Bei erneutem Vorschlag desselben Nutzers für dasselbe Merkmal werden ältere `pending`-Requests auf `superseded` gesetzt; Adminlabels für diesen Status existieren in DE/EN.
- Privilegierte Direktbearbeiter verwenden weiterhin direkte versionierte Speicherung und Audit-Logging; normale Nutzer weiterhin `change_requests`.
- Der Nutzer hat den neuen Workflow inklusive F5-Wiederherstellung und Verlassen-Warnung praktisch erfolgreich getestet und beschreibt ihn als deutlich komfortabler als die frühere Einzelbearbeitung.

### Test-/Abnahmestatus

- Die jüngsten Responsive- und Kategorie-Editänderungen wurden visuell/praktisch vom Nutzer geprüft.
- Der Assistent hat dafür keine lokale temporäre PHP-/Composer-Testumgebung angelegt und keine automatisierten Tests selbst ausgeführt.
- Der Nutzer führt Laravel-Tests lokal selbst aus; vor einem größeren Abschlusscheckpoint des Blocks sollten passende Feature-/Controller-Regressionstests ergänzt beziehungsweise gezielt ausgeführt werden.
- Keine Migration ist für die Kategorie-Quick-Edit-Änderungen erforderlich.



## 23. Fortschreibung – Devlog und Versionsführung vom 2026-09-22

- Der öffentliche zweisprachige Devlog bleibt unter `/devlog` bestehen und basiert auf `dev_releases` plus Übersetzungstabellen.
- Die sichtbare Versionsnummer wird weiterhin automatisch aus dem neuesten öffentlichen Release über `DevReleaseService` abgeleitet; sie ist nicht hartcodiert.
- Der alte winzige Fixed-Link oben rechts im Seitenlayout wurde entfernt.
- Die aktuelle Versionsnummer steht stattdessen unauffällig und klickbar unten rechts im User-Dropdown und führt direkt zum Devlog. Das gilt für Desktop und Mobile.
- Der Devlog wurde bis zum aktuellen Stand **Pre-Alpha 0.18.001** fortgeschrieben. Die neueren Meilensteine fassen größere Produktblöcke zusammen statt einzelne Commits aufzulisten.
- Verbindliche Arbeitsweise ab jetzt: Nach einem größeren abgeschlossenen Funktions-/UX-Block soll geprüft werden, ob ein neuer Devlog-Meilenstein sinnvoll ist. Kleine Bugfixes oder Einzelkorrekturen erzeugen nicht automatisch eine neue Versionsnummer.
- `DevReleaseSeeder` bleibt die konsolidierte Quelle für die Release-Historie. Bestehende Installationen erhalten neue Devlog-Daten über eine gezielte Datenmigration; neue Test-/Entwicklungsdaten können weiterhin über den Seeder aufgebaut werden.
- Regressionstest: `tests/Feature/DevLogTest.php` prüft die aktuelle DE/EN-Ausgabe und Versionsnummer.


## 24. Fortschreibung – Zentrales Feedback, Validierung und Session-Abschluss vom 2026-09-22

Dieser Abschnitt ergänzt den aktuellen UX-/Fehlerzustandsblock und dokumentiert den in dieser Session fertiggestellten zentralen Feedback-Unterbau.

### Zentrales Feedback-System

- Formular- und Validierungsfehler werden zentral über `resources/views/components/ui-feedback-dialog.blade.php` dargestellt.
- Der Fehlerdialog liegt mit maximalem Z-Index zuverlässig über Karte, Leaflet-Layern und sonstiger UI.
- Bei Validierungsfehlern werden betroffene Felder rot markiert; nach Schließen des Dialogs wird zum ersten passenden Feld gescrollt und der Fokus gesetzt.
- Die rote Markierung verschwindet nach der ersten Änderung am jeweiligen Feld.
- Der allgemeine Validierungstext ist bewusst kurz gehalten: keine erklärenden Sätze wie „Nach dem Schließen springen wir ...“.
- Wichtige abgeschlossene Aktionen verwenden `session('ui_dialog')` und einen zentralen Bestätigungsdialog.
- Kleine bzw. sofortige Routineaktionen verwenden `session('ui_toast')` und einen unaufdringlichen globalen Toast.
- Der zentrale Feedback-Layer ist in den normalen App-, Auth- und Sidebar-Layouts eingebunden.
- Klassische POST-Formulare werden standardmäßig auf serverseitige Validierung gelenkt; native Browser-Validierung bleibt nur dort aktiv, wo sie explizit über `data-native-validation` gewünscht ist.

### Bereinigung alter Feedback-Varianten

- Alte sichtbare grüne/rote Status- und Fehlerboxen wurden aus den wesentlichen Camperwolf- und Admin-Views entfernt, soweit sie nur Aktionsergebnisse oder globale Validierungsfehler duplizierten.
- Spezielle fachliche Inline-Hinweise bleiben erhalten, wenn sie Teil des eigentlichen Seiteninhalts sind, z. B. Konflikt-/Overlap-Hinweise oder Warnungen innerhalb eines Workflows.
- Bestehende Controller-Flashmeldungen wurden systematisch klassifiziert:
  - größere abgeschlossene Vorgänge -> `ui_dialog`;
  - kleine Sofort-/Routineaktionen -> `ui_toast`;
  - Validierungsfehler -> zentraler Error-Dialog über den Laravel-ErrorBag.
- Framework-spezifische Auth-/Livewire-Zustände wurden nicht unnötig umgebaut, wenn dort bereits ein eigener sinnvoller Mechanismus existiert.

### Validierung und Lokalisierung

- Projektweite Validierungs-Fallbacks existieren nun unter `lang/de/validation.php` und `lang/en/validation.php`.
- Damit sollen rohe Laravel-Standardtexte wie `The ... field is required.` in der deutschen Oberfläche vermieden werden.
- Für Reviews existieren zusätzlich fachlich verständliche Meldungen mit dem sichtbaren Kriteriennamen, z. B. „Bitte bewerte auch ‚Nutzbarkeit‘.“
- Auch die Platzanlage verwendet eigene verständliche DE/EN-Meldungen für Name, Platztyp und Kartenposition.
- Die zentrale Feedback-Komponente behandelt fehlende `$errors` defensiv als leeren ErrorBag, damit sie in allen Layouts/Views sicher verwendet werden kann.

### Abnahme und Tests

- Der Nutzer hat die zentralen Flows praktisch geprüft:
  - Platzanlage mit Validierungsfehler;
  - kategorieweise Merkmalsbearbeitung mit Erfolgsmeldung;
  - Review mit fehlender Pflichtbewertung;
  - weitere Stichproben mit Dialogen und Toasts.
- Karte/Overlay-Z-Index, Fehlermeldungsformat, lokalisierte Reviewtexte und Feld-Fokus wurden praktisch bestätigt.
- Nach der projektweiten Umstellung traten zunächst zwei Regressionen auf:
  - Syntaxfehler in `lang/en/places.php`;
  - fehlender `$errors`-ErrorBag in einzelnen Views.
  Beide wurden behoben.
- Zusätzlich wurde der Browse-Wortlaut „bei :count Bewertung(en)“ wiederhergestellt, weil ein Regressionstest den bisherigen sichtbaren Text absichert.
- Abschließender lokaler Teststand des Nutzers: **alle Tests erfolgreich** (150/150 bzw. vollständige Suite ohne Fehler nach den Korrekturen).
- Der Nutzer hat außerdem mehrere Browser-Stichproben durchgeführt und das neue Feedback-System als funktionierend und optisch passend bestätigt.
- Für spätere Beta-Tests ist zu erwarten, dass einzelne Randfälle oder Formulierungen noch als Bugreports auftauchen; diese sollen gezielt nach echtem Nutzerfeedback feinjustiert werden.

### Bereinigung veralteter Konzepte – verbindlicher Stand

- Die frühere Aufräumaktion der Kontextdatei bleibt verbindlich: abgelöste Quick-Edit-, Einzel-Submit- und Status-Dropdown-Konzepte sollen nicht erneut als aktuelle Produktidee behandelt werden.
- Maßgeblich ist die kategorieweise Merkmalsbearbeitung aus Abschnitt 22 mit direkter Chip-Auswahl, gesammelt übertragenen Änderungen, `sessionStorage`-Entwürfen, F5-Wiederherstellung, Verlassen-Warnung, `Ausstehend`/`superseded` und getrennt dargestellten unbekannten Merkmalen.
- Historische Handoffs und ältere Dokumentationsabschnitte bleiben als Archiv erhalten, dürfen aber bei Widerspruch nicht gegen neuere Fortschreibungen oder den aktuellen Code interpretiert werden.
- Allgemeine Regel: Wenn ein altes Konzept technisch und fachlich durch ein neues ersetzt wurde, soll die konsolidierte Context-Datei den neuen Stand eindeutig als verbindlich markieren und das alte Konzept nicht mehr als offenen oder aktuellen Lösungsweg präsentieren.


### Abschluss Roadmap-Punkt 7 – Mobile/Desktop, Accessibility und UX-Fehlerzustände

- Der Block gilt für V1 als abgeschlossen.
- Startseite, Platzprofil, Platzanlage/-pflege und die neue kategorieweise Merkmalsbearbeitung wurden auf Desktop und Mobil praktisch geprüft.
- Das zentrale Dialog-/Toast-/Validierungssystem wurde anhand mehrerer realer Stichproben geprüft und funktionierte dabei wie erwartet.
- Eine vollständige Beta-Abnahme durch externe Tester findet erst nach Bereitstellung der V1 statt; externe Betatester haben aktuell noch keinen Zugriff.
- Spätere Auffälligkeiten bei Accessibility, Keyboard-Navigation, Randzuständen, Texten oder einzelnen seltenen Workflows werden als normale Beta-Bugfixes beziehungsweise Finetuning behandelt und halten den Roadmap-Punkt nicht offen.
- Nächster regulärer Roadmap-Block nach Abschluss von Punkt 8: **Rechte, Rate Limits und Missbrauchsschutz**.


## 25. Fortschreibung – Abschluss Roadmap-Punkt 8: Performance und Datenbankindizes vom 2026-09-23

Dieser Abschnitt ist für Performance, Monitoring und Diagnose neuer als frühere Aussagen und gilt bei Widersprüchen als verbindlich.

### Realistische Testbasis

- Für reproduzierbare Performance-Tests existiert ein eigener skalierbarer Performance-Datensatz über `camperwolf:performance-seed`.
- Verbindliche lokale Baseline: etwa **10.000 Plätze, 2.500 Nutzer, 44.000 Reviews, rund 15.000 Fotos, 13.700 Favoriten und 7.500 Preisangebote**.
- Die Daten können mit `php artisan camperwolf:performance-clear --force` wieder entfernt werden.
- Dokumentation: `docs/PERFORMANCE_TEST_DATA.md`.
- Größere Datenmengen wie 25k oder 50k Plätze bleiben für spätere Lasttests möglich; für den V1-Performanceblock war die 10k-Baseline ausreichend.

### Browse-Optimierungen

- Unnötige Review-Score-Aggregation wurde aus dem normalen Browse-Pfad entfernt und nur noch für Sortierung nach Score ausgeführt.
- Die ehemals monolithische Facettenzählung wurde strukturell zerlegt und über SQL-Subqueries/Joins statt große PHP-ID-`WHERE IN`-Listen angebunden.
- Ein eigener EXPLAIN-Werkzeugweg existiert über `camperwolf:performance-explain`.
- Typische Messwerte verbesserten sich in den untersuchten Szenarien deutlich:
  - Base-IDs von grob ~170 ms auf etwa ~25 ms,
  - Marker von grob ~139 ms auf etwa ~25–35 ms,
  - gefilterte Facetten von grob ~1,3 s auf etwa ~60 ms,
  - ungefilterte Facetten auf grob ~250 ms vor Cache.
- Ein testweise ergänzter zusammengesetzter `place_features`-Index brachte keinen messbaren Nutzen und wurde wieder entfernt. Neue Indizes sollen deshalb nur auf Basis reproduzierbarer EXPLAIN-/Profiler-Messungen eingeführt werden.

### Ergebnis- und Kartenlimits

- Filter und Suche arbeiten weiterhin gegen den **vollständigen passenden Datenbestand**.
- Angezeigt werden maximal **1.000 Ergebnisdatensätze** und maximal **1.000 Kartenmarker**.
- Die UI weist bei mehr Treffern darauf hin, Suche und/oder Filter weiter einzugrenzen.
- Das Limit dient der Skalierbarkeit der Darstellung und ändert nicht die fachliche Filtermenge.

### Feature-Zähler

- Zählersemantik ist verbindlich: `X / Y` bedeutet **X aktuell gefilterte Plätze mit Merkmal / Y aktuell gefilterte Plätze insgesamt**.
- Der Nenner ist damit kein globaler Merkmalsgesamtbestand mehr.
- Für die komplett ungefilterte Startansicht wird ein direkterer Zählpfad verwendet.

### Kurzzeit-Cache für die ungefilterte Startseite

- Nur die komplett ungefilterte Basissicht erhält einen einfachen **60-Sekunden-Cache** für Facetten und Feature-Zähler.
- Suche, Kartenfilter, Favoriten, Platztypen, normale Merkmale und variable Facetten umgehen diesen Cache.
- Unter `APP_ENV=testing` ist der Browse-Cache ausdrücklich deaktiviert, damit Testfälle keinen Application-Cache-Zustand miteinander teilen.
- Warm gemessene Startseite lag zuletzt serverseitig grob bei **~165 ms**, gegenüber etwa **~735–770 ms** bei kalter Berechnung.
- Cache-Status erscheint im Admin-Debugmodus als `HIT`, `MISS`, `BYPASS` beziehungsweise `DISABLED`.

### Profiling von Platz- und Userprofilen

- Controller und Blade-Rendering wurden getrennt gemessen, nachdem Pulse bei einzelnen Requests hohe Gesamtlaufzeiten meldete.
- Normale warme Messungen lagen zuletzt ungefähr bei:
  - Platzprofil Controller ~27 ms,
  - Platzprofil Blade-Inhalt ~40 ms,
  - Platzprofil inklusive Layout ~59 ms,
  - Userprofil Controller ~23 ms,
  - Userprofil Blade-Inhalt ~7 ms,
  - Userprofil inklusive Layout ~28 ms.
- Sehr hohe Einzelwerte nach `optimize:clear` wurden als Cold-Compile-/Profiling-Effekt identifiziert und nicht als dauerhafte Seitenlatenz bewertet.
- Das Platzprofil selbst zeigte nach Aufwärmung keinen strukturellen Blade-Flaschenhals; die Feature-Gruppen lagen im gemessenen Beispiel bei rund 31 ms.

### Monitoring und Diagnose

- Laravel Pulse bleibt als leichtgewichtiges laufendes Monitoring aktiv.
- Retention: 30 Tage; Slow-Query-Schwelle 100 ms; Slow-Request-Schwelle 500 ms.
- Eigener Bericht: `php artisan camperwolf:performance-report` mit optionalen Zeitfenstern und `--full`.
- Telescope ist weiterhin installiert, aber **standardmäßig deaktiviert**.
- Aktivierung nur bei konkretem Analysebedarf über `.env`:
  - `TELESCOPE_ENABLED=true`
  - anschließend `php artisan config:clear`
- Danach wieder auf `false` zurückstellen.
- Produktion soll Telescope nicht dauerhaft mitsammeln lassen.

### Admin-System- und Debugbereich

- Im Adminbereich existiert nun **System & Debug**.
- Zugriff: Rolle `admin` oder System Owner im normalen Owner-Modus; niedrigere Rollen sehen den Bereich nicht und werden bereits durch die bestehende Admin-Middleware neutral mit 404 abgefangen.
- Der Debug-Modus ist **sessionbasiert** und betrifft ausschließlich den aktuell angemeldeten Administrator.
- Ist Debug aus, werden die Performance-Messblöcke nicht ausgeführt; übrig bleibt nur ein sehr kleiner Session-Flag-Check.
- Ist Debug aktiv, zeigen unterstützte Seiten ihre internen Performancewerte automatisch; `?profile=1` ist nicht mehr nötig.
- Aktuell angebunden:
  - Browse-/Startseitenprofil,
  - Platzprofil,
  - Userprofil.
- Der Bereich ist absichtlich als später erweiterbare Heimat für Admin-Komfortfunktionen und grundlegende Systemstatistiken angelegt.
- Der Nutzer hat den Admin-Systembereich praktisch getestet; Ein-/Ausschalten des Debug-Modus funktioniert.
- `AdminSystemToolsTest` wurde vom Nutzer lokal erfolgreich ausgeführt.

### Profiler-Korrektheit

- Der frühere Messwert `Facet service total` war durch interne Segmentmarken irreführend.
- Er misst nun explizit Start bis Ende der kompletten Facet-Verarbeitung; interne Teilsegmente können weiterhin separat angezeigt werden.
- Damit ist der dauerhafte Admin-Debugmodus für spätere Ad-hoc-Diagnosen verwendbar.

### Abschlussstatus Roadmap-Punkt 8

- **Roadmap-Punkt 8 „Performance und Datenbankindizes“ ist vollständig abgeschlossen.**
- Der Nutzer hat nach den letzten Cache-/Profiler-/Devlog-Änderungen lokal erfolgreich ausgeführt:
  - `php artisan test --filter=PlaceBrowse`
  - `php artisan test --filter=DevLogTest`
  - `php artisan test`
- Die neue Devlog-Migration wurde erfolgreich angewendet; die Anwendung funktioniert im anschließenden Browsercheck weiterhin wie erwartet.
- Dieser Abschluss ist praktisch bestätigt, nicht nur dokumentarisch angenommen.
- Bekannte repositoryweite Pint-Altlasten sind **kein Bestandteil von Punkt 8** und sollen separat bereinigt werden.
- Nächster regulärer V1-Roadmap-Punkt: **Rechte, Rate Limits und Missbrauchsschutz**.

### Praktisch bestätigter Abschluss

- Am 2026-09-23 bestätigte der Nutzer nach `git pull`, Migration, gezielten Browse-/Devlog-Tests und vollständiger Testsuite: **alles grün**.
- Zusätzlich wurde die Anwendung im Browser geprüft; die Seite funktioniert weiterhin fehlerfrei.
- Roadmap-Punkt 8 ist damit nicht mehr „vorbehaltlich Test“, sondern endgültig erledigt.
- Nächster aktiver Roadmap-Punkt: **Rechte, Rate Limits und Missbrauchsschutz**.

### Devlog

- Performance-/Diagnoseabschluss ist ein eigener größerer Meilenstein:
  - **Pre-Alpha 0.19.001 – Performance und Diagnose / Performance and diagnostics**
- `DevReleaseSeeder` enthält den neuen Meilenstein.
- Bestehende Installationen erhalten ihn über `2026_09_23_104500_seed_performance_dev_release.php`.


## 26. Fortschreibung – Roadmap-Punkt 9: Rechte, Rate Limits und Missbrauchsschutz vom 2026-09-23

Dieser Abschnitt ist für den aktuellen Sicherheitsblock neuer als frühere allgemeine Aussagen.

### 9.1 Rechte-Audit – abgeschlossen

- Routen, Permission-Matrix und privilegierte Direktpfade wurden systematisch gegengeprüft.
- User-seitige Routen für Reviews, Benachrichtigungen und eigenen Support erzwingen nun die dafür bereits vorhandenen fachlichen Permissions auch bei direktem Request.
- Admin-Routen verwenden nicht mehr pauschal nur `admin.access`, wenn bereits spezifische fachliche Permissions existieren.
- Platz-Zusammenführung benötigt jetzt tatsächlich `places.merge`; Moderatoren mit `places.approve_changes` dürfen dadurch nicht mehr automatisch mergen.
- Manuelle Badges besitzen die neue Permission `users.manage_badges`; standardmäßig nur Admin/System Owner.
- Gast-Support bleibt bewusst öffentlich zugänglich. Die öffentliche Formular-/POST-Route verwendet keine User-Permission, weil anonyme Besucher kein User-Objekt besitzen; Missbrauch wird dort über den benannten `support-submit`-Limiter abgefangen.
- Admin/System Owner dürfen moderationspflichtige Platzdaten direkt ändern:
  - Platzanlage,
  - allgemeine Platzinformationen,
  - Merkmale,
  - Öffnungszeiten,
  - Preise,
  - administrative Foto-Uploads.
- Allgemeine Platzinformationen werden bei Admin/System Owner sofort angewendet; zur Nachvollziehbarkeit bleiben direkt genehmigte `change_requests`/Audit-Einträge erhalten, aber es entsteht kein offener Moderationsfall.
- Admin-Fotos durchlaufen weiterhin alle technischen Sicherheits-/Bildverarbeitungsschritte und werden erst anschließend automatisch freigegeben.
- Der Nutzer hat die Rechte-/Direktmodus-Tests lokal erfolgreich ausgeführt und bestätigt.

### 9.2 Zentrale Rate Limits – implementiert

- Die bisherigen verstreuten numerischen `throttle:x,y`-Angaben wurden durch benannte zentrale Limiter in `AppServiceProvider` ersetzt.
- Aktuelle Regeln:
  - `support-submit`: 2/min, 5/h, 15/Tag;
  - `place-create`: 2/min; neue Accounts 3/h und 8/Tag; ältere Accounts 5/h und 15/Tag; zusätzlich 30/Tag pro IP;
  - `community-write`: 20/min, 100/h;
  - `review-write`: 5/h, 15/Tag;
  - `photo-upload`: 5/min, 15/h, 50/Tag;
  - `report-create`: 3/min, 10/h, 30/Tag;
  - `support-reply`: 20/h;
  - `engagement-write`: 60/min, 300/h.
- Admin und System Owner umgehen diese normalen Community-Limits.
- Besonders missbrauchsanfällige Platzanlage kombiniert User- und IP-Limits, damit Account-Farmen nicht einfach die User-Grenze umgehen.
- Neue Accounts (<24h) erhalten bei Platzanlage strengere Grenzen.
- Fortify schützt Login bereits mit 5 Versuchen/min pro E-Mail+IP und die 2FA-Challenge mit 5/min.
- Registrierung wurde zusätzlich auf 3 Konten/h und 10 Konten/Tag pro IP begrenzt.
- Für die neuen Regeln existiert `tests/Feature/AbuseRateLimitTest.php`; Registrierungsschutz wurde in `tests/Feature/Auth/RegistrationTest.php` ergänzt.
- Diese letzten Rate-Limit-Tests müssen vom Nutzer noch lokal bestätigt werden, bevor 9.2 als vollständig abgenommen gilt.

### Öffentliche Lesezugriffe und Scraping

- Öffentliche Platzprofile, Review-Feeds/-Historien und Userprofile sind technisch bereits an einen benannten `public-read`-Limiter angebunden.
- Dieser ist **standardmäßig deaktiviert**, damit normale Besucher und Suchmaschinen aktuell keinerlei serverseitige Lese-Drosselung erfahren.
- Konfiguration:
  - `CAMPERWOLF_PUBLIC_READ_LIMIT_ENABLED=false`
  - `CAMPERWOLF_PUBLIC_READ_PER_MINUTE=180`
- Der Laravel-`public-read`-Limiter ist nur als Notfall-Fallback vorgesehen.
- Regulärer Bot-/Scraping-Schutz soll später beim Produktions-Go-live über Cloudflare erfolgen, weil dort legitime/verifizierte Suchmaschinen besser von verdächtigen Bots getrennt werden können.

### Cloudflare – bewusst auf Go-live verschoben

- Während der lokalen Entwicklung wird Cloudflare nicht benötigt und nicht als technische Abhängigkeit eingebaut.
- Ziel für den späteren Server-Go-live: Cloudflare Free als äußere Schutzschicht, konservative Regeln, keine unnötigen Challenges für normale Besucher, Suchmaschinen weiterhin frei zugänglich.
- Kritischer Deployment-Punkt: Laravel muss hinter Cloudflare die echte Client-IP zuverlässig sehen und ausschließlich vertrauenswürdigen Proxy-Headern glauben. Sonst würden IP-basierte Limits fehlerhaft alle Besucher zusammenfassen oder spoofbare Header akzeptieren.
- Die endgültige Trusted-Proxy-Konfiguration wird daher erst passend zur echten Server-/Cloudflare-Topologie gesetzt.
- Detaillierte aktuelle Dokumentation: `docs/SECURITY_ABUSE_PROTECTION.md`.

### Aktueller Roadmap-Status

- 9.1 Rechte: **abgeschlossen und lokal bestätigt**.
- 9.2 Rate Limits: **implementiert, lokale Tests noch ausstehend**.
- Danach folgen 9.3 Missbrauchs-/Spam-Härtung, 9.4 Scraping/Bot-Go-live-Vorbereitung und 9.5 Security-/Attack-Hardening, soweit nach dem aktuellen Code-Audit noch zusätzliche Maßnahmen nötig sind.


### 9.3 Missbrauchs-/Spam-Härtung – abgeschlossen

- Neue generische Tabelle `abuse_flags` trennt Missbrauchserkennung von den eigentlichen Fachdaten.
- V1 verwendet bewusst transparente Regeln statt eines undurchsichtigen Trust-/Spam-Scores.
- Aktive Regeln für neue Platzvorschläge:
  - sehr wahrscheinliches Duplikat: gleicher normalisierter Name im Radius von 75 m zu veröffentlichtem/offenem Platz;
  - neuer Account (<24h) mit mindestens drei offenen Platzvorschlägen in einer Stunde;
  - mindestens acht offene Platzvorschläge desselben Accounts innerhalb von 24 Stunden.
- Verdächtige Vorschläge werden **nicht automatisch gelöscht oder abgelehnt**. Sie bleiben vollständig erhalten, werden aber aus der normalen Änderungswarteschlange in eine eigene Quarantäneansicht verschoben.
- Die Admin-Änderungswarteschlange besitzt dafür `queue=normal` und `queue=quarantine`; die Detailansicht zeigt die konkreten Regel-Auslöser.
- Der Admin-Überblick zeigt die Anzahl aktuell quarantänisierter Platzvorschläge.
- Bei Genehmigung oder Ablehnung der eigentlichen Publikationsanfrage werden zugehörige offene Abuse-Flags als erledigt markiert.
- Quell-IP wird bei Abuse-Flags nicht roh gespeichert, sondern nur als SHA-256-Hash mit App-Key-Salz zur späteren Korrelation.
- Admin/System Owner umgehen Quarantäne und Community-Limits im normalen privilegierten Modus; die Rollen-Vorschau respektiert weiterhin die simulierte Rolle.
- Identische Platzanlage-POSTs desselben Nutzers werden innerhalb von zehn Minuten dedupliziert, wenn Name, Platztyp und Position identisch sind und bereits ein eigener Draft/Pending-Datensatz existiert. Dadurch erzeugen Doppelklicks/Request-Replays keinen zweiten Moderationsfall.
- Bestehende DB-Deduplizierung wurde bestätigt:
  - Review-Meldungen per Unique-Constraint + `insertOrIgnore`;
  - Foto-Meldungen per Unique-Constraint + `insertOrIgnore`;
  - ein Review je Platz/Nutzer per Unique-Constraint.
- Foto-Uploads waren bereits technisch gehärtet: Uploadstatus, Größenlimit, erlaubte MIME-Typen, Imagick-Prüfung, Einzelbild, Pixelober-/untergrenze und Metadatenbereinigung bleiben bestehen.
- Neue Regressionstests: `tests/Feature/AbuseProtectionTest.php`.
- Lokale Migration und Tests durch den Nutzer stehen für diesen Block noch aus.


### 9.4 Scraping-/Bot-Go-live-Vorbereitung – implementiert

- Die öffentliche Browse-Suche wurde gegen absichtlich teure Query-Konstrukte gehärtet:
  - Query-String maximal 8 KiB;
  - maximal 120 Query-Leaf-Werte;
  - maximal 5 Verschachtelungsebenen;
  - Suchtext `q` maximal 120 Zeichen.
- Die Prüfung läuft vor den teureren Browse-/Facet-Datenbankabfragen.
- Startseite und Dashboard hängen nun ebenfalls am benannten `public-read`-Limiter.
- `public-read` bleibt standardmäßig deaktiviert, damit normale Besucher und Suchmaschinen nicht eingeschränkt werden. Er ist nur als serverseitiger Notfall-Fallback vorgesehen.
- `robots.txt` lässt öffentliche Inhalte crawlbar, schließt aber Admin-, Auth- und private Accountpfade für kooperative Crawler aus.
- Private Oberflächen senden zusätzlich `X-Robots-Tag: noindex, nofollow, noarchive`.
- Aktiver Scraper-/Bot-/DDoS-Schutz mit verifizierter Suchmaschinen-Erkennung bleibt bewusst Cloudflare-Aufgabe beim späteren Go-live.

### 9.5 Security-/Attack-Hardening – implementiert

- Globale Basis-Security-Header:
  - `X-Content-Type-Options: nosniff`;
  - `X-Frame-Options: SAMEORIGIN`;
  - `Referrer-Policy: strict-origin-when-cross-origin`;
  - restriktive `Permissions-Policy` mit Geolocation nur für die eigene Origin;
  - HSTS nur in Produktion und nur bei HTTPS;
  - Application-`X-Powered-By` wird entfernt.
- Passwort-Reset-Mailmissbrauch wird zusätzlich auf 20 POST-Anfragen pro Stunde je IP begrenzt; Laravels bestehendes Reset-Token-Throttle pro Zieladresse bleibt erhalten.
- Login, 2FA, Registrierung, Uploads, Reports und Community-Schreibaktionen besitzen bereits die zuvor dokumentierten Limits.
- Foto-Upload-Härtung und serverseitige Request-/Validierungsmechanismen bleiben unverändert aktiv.
- Ein globaler CSP-Header wurde bewusst noch nicht erzwungen, weil Livewire/Flux/Map-Skripte vor einem CSP-Rollout separat geprüft werden müssen.
- Regressionstests: `tests/Feature/SecurityHardeningTest.php`.
- Lokale Abnahme für 9.4/9.5 durch den Nutzer steht noch aus.


### 9.6 Monitoring, Integration und Abschluss – abgeschlossen

- Neue Tabelle `security_events` für schlankes technisches Security-Monitoring.
- Erfasst werden:
  - ausgelöste 429-Rate-Limits;
  - abgewiesene übergroße/komplexe Browse-Queries;
  - blockierte Registrierungsversuche.
- Gespeichert werden keine Request-Payloads und keine rohen IPs; Quell-IP nur als mit App-Key gesalzener SHA-256-Hash.
- **System & Debug** zeigt:
  - offene Quarantäne;
  - Abuse-Flags 24h;
  - 429-Antworten 24h;
  - abgewiesene Browse-Queries 24h;
  - Registrierungs-Limits 24h;
  - letzte 20 Security-Ereignisse.
- Security-Ereignisse werden nach 90 Tagen automatisch entfernt:
  - Artisan: `php artisan security:cleanup`;
  - Scheduler täglich 03:50.
- System & Debug wurde auf `admin.access` vereinheitlicht, damit Admin und System Owner konsistent Zugriff haben und Role Preview weiterhin korrekt simuliert.
- Regressionstests:
  - `SecurityHardeningTest` prüft nun auch Event-Erfassung;
  - `RegistrationTest` prüft Registrierungslimit-Event;
  - neu `SecurityMonitoringTest` für Adminanzeige, 90-Tage-Retention und System-Owner-Zugriff.
- Lokale Migration, gezielte Security-Tests und vollständige Testsuite wurden erfolgreich ausgeführt. Roadmap-Punkt 9 ist vollständig abgeschlossen.


## ROADMAP-STATUS: Punkt 9 abgeschlossen

Punkt 9 „Rechte, Rate Limits und Missbrauchsschutz“ ist vollständig umgesetzt und lokal abgenommen.

Abgeschlossen:
- 9.1 Rechte-Audit und Permission-Härtung
- 9.2 Rate Limits
- 9.3 Missbrauchs-/Spam-Härtung
- 9.4 Scraping-/Bot-Go-live-Vorbereitung
- 9.5 Security-/Attack-Hardening
- 9.6 Monitoring, Integration und Abschluss

Der vollständige PHPUnit-Lauf ist grün. Der neue Security-&-Abuse-Bereich unter Administration → System & Debug wurde zusätzlich manuell geprüft und funktioniert wie vorgesehen.

Offen bleiben nur die bewusst auf den späteren Server-/Go-live-Zeitpunkt verschobenen Infrastrukturthemen wie Cloudflare, Trusted Proxy / echte Client-IP und Origin-/WAF-Konfiguration.


## 27. Fortschreibung – Roadmap-Punkt 10.1: Datenschutz-Dateninventar vom 2026-09-23

Der technische Datenbestand wurde systematisch inventarisiert und in `docs/DATA_PRIVACY_INVENTORY.md` dokumentiert.

Erfasst sind insbesondere:
- Konto/Auth/Sessions inklusive roher Session-IP und User-Agent;
- Community-Profil inklusive CW-ID, Alias, Bio, Heimatort, Geburtsdatum, Geschlecht, Fahrzeug und Social Links;
- User Settings und vorhandene Consent-Tabelle;
- Community-Beiträge, Change Requests und Audit-Historie;
- Reviews, Fotos, Reports und Helpful-Stimmen;
- Favoriten, Benachrichtigungen, Support und Gamification;
- Abuse Flags und Security Events;
- später deploymentabhängige Server-/Frameworklogs sowie externe Karten-/Geocoding-Dienste.

Wichtige bestehende Datenschutzmaßnahmen:
- Fotoverarbeitung entfernt Metadaten/EXIF und Originaldateinamen; temporäre Originale werden gelöscht.
- Security Events und Abuse Flags speichern Quell-IP nur gehasht, nicht roh.
- Security Events haben bereits 90 Tage Retention.
- Profilbereiche besitzen abgestufte Sichtbarkeit; Alter und Geschlecht sind standardmäßig privat.

Für 10.2 sind bewusst noch Produktentscheidungen offen:
- Reviews nach Accountlöschung löschen oder anonymisiert erhalten;
- Fotos nach Accountlöschung löschen oder anonymisiert erhalten;
- Verhalten von CW-ID/Alias nach Löschung;
- vollständiges Geburtsdatum beibehalten/reduzieren/entfernen;
- fachlichen Nutzen von Geschlecht prüfen;
- präzise Heimatort-Koordinaten hinterfragen;
- Retention für Support, Abuse Flags, Sessions und spätere Serverlogs;
- Audit-/Change-Request-Historie fachlich erhalten, direkten Nutzerbezug voraussichtlich anonymisieren.

Roadmap-Punkt 10.1 ist damit technisch abgeschlossen; die nächsten Schritte sind Produktentscheidungen und anschließend 10.2 Account-Löschung/Anonymisierung.


### 10.2 Nutzerkonto-Lifecycle – abgeschlossen

- Neuer Statusfluss für Nutzerkonten mit 30 Tagen Karenz oder sofortigem Abschluss.
- Während der Karenz ist das öffentliche Profil verborgen; der Vorgang kann noch abgebrochen werden.
- Beim finalen Abschluss bleibt nur ein technischer Tombstone für bestehende Relationen.
- Persönliche Profil- und Kontofelder werden bereinigt.
- Community-Platzdaten bleiben erhalten.
- Bilddateien und Review-Freitexte werden entsprechend dem vereinbarten Modell behandelt; historische Score-Zeiträume bleiben konsistent.
- Stündlicher Job: `accounts:finalize-deletions`.
- Neue Einstellungsseite mit Bestandszahlen und Folgenbeschreibung.
- Tests: `AccountDeletionTest` und `AccountDeletionContentTest`.
- Lokale Tests und Browserabnahme des Lösch-/Karenz-/Reaktivierungsflows wurden erfolgreich durchgeführt.
- Nächster Teilblock: verbindliche Inhaltsregeln für Fotos und Rezensionen.


### 10.3 Foto-Inhaltsregeln – abgeschlossen

Zentrale Regeln:
- neuer Hilfeartikel `regeln-und-empfehlungen-fuer-fotouploads`, deutsch/englisch;
- verbindliche Regeln gegen Nacktheit/sexualisierte Inhalte, rechtswidrige oder jugendgefährdende Inhalte, Persönlichkeits-/Datenschutzverletzungen, fehlende Bildrechte, themenfremde Platzfotos, vertrauliche Informationen, irreführende/manipulierte Bilder sowie Werbung/Spam;
- Bezug auf anwendbares deutsches Recht und unmittelbar geltendes EU-Recht; Camperwolf darf zusätzlich strengere Plattformregeln anwenden;
- getrennte Empfehlungen für Platzfotos und Profilbilder.

Single Source of Truth:
- Hilfeartikel ist die vollständige Regelquelle;
- wiederverwendbare Blade-Komponente `x-photo-upload-rules` lädt genau diesen Artikel;
- Schnellinfo + Modal an allen aktuellen Uploadstellen:
  - Review-/Platzfotos;
  - Profilbild;
- Platzfoto-Schnellinfo betont hilfreiche Fotos zum Kennenlernen/Einschätzen des Platzes;
- Modal und Hilfe-Seite verwenden denselben DB-Inhalt.

Moderation:
- feste Ablehnungsgründe: Nacktheit/sexualisiert, rechtswidrig/jugendgefährdend, Persönlichkeits-/Datenschutz, Bild-/Urheberrechte, fehlender Platzbezug, irreführend/manipuliert, Werbung/Spam, sonstiger Regelverstoß;
- optionaler Zusatzhinweis bleibt möglich;
- Ablehnungsgrund wird in der Sprache des Fotoautors erzeugt.

Migration:
- `2026_09_23_140000_add_photo_upload_rules_help_article.php`

Test:
- `PhotoRulesTest` prüft deutschen und englischen Hilfeartikel.
- Lokale Browserabnahme: Hilfeartikel, Modal, Review-/Platzfoto-Upload, Profilbild-Hinweis und Fotomoderation funktionieren wie vorgesehen.

Als nächster fachlicher Inhaltsblock folgen die Regeln für Rezensionen.


### 10.4 Rezensions-Inhaltsregeln – abgeschlossen

Zentrale Regeln:
- neuer Hilfeartikel `regeln-und-empfehlungen-fuer-rezensionen`, deutsch/englisch;
- verbindliche Regeln gegen rechtswidrige/unzulässige Inhalte, Beleidigung/Bedrohung/Diskriminierung, persönliche oder vertrauliche Daten, erfundene Erfahrungen bzw. wissentlich falsche Tatsachenbehauptungen, unbelegte schwere Anschuldigungen, persönliche Streit-/Rachebewertungen, fehlenden Platzbezug, Werbung/Spam, manipulierte Bewertungen/Interessenkonflikte und fremde Text-/Urheberrechte;
- ältere Rezensionen werden nicht allein dadurch regelwidrig, dass sich der Platz später verändert;
- sachliche negative Kritik ist ausdrücklich erlaubt und muss nicht künstlich positiv „ausgeglichen“ werden;
- Empfehlungen betonen konkrete eigene Erfahrungen, relevante Umstände und eine echte Entscheidungshilfe für andere Camper.

Single Source of Truth:
- Hilfeartikel ist die vollständige Regelquelle;
- wiederverwendbare Blade-Komponente `x-review-content-rules` lädt genau diesen Artikel;
- Schnellinfo + Modal direkt am Rezensionstext;
- Hilfeartikel bleibt zusätzlich regulär über Hilfe & Support auffindbar.

Moderation und Meldungen:
- feste Moderationsgründe beim Entfernen gemeldeter Rezensionen; optionaler Zusatzhinweis;
- Nutzer-Meldegründe wurden an dieselben Regelkategorien angeglichen;
- Legacy-Meldegründe bleiben in der Adminansicht übersetzbar.

Migration:
- `2026_09_23_141000_add_review_rules_help_article.php`

Test:
- `ReviewRulesTest` prüft deutschen und englischen Hilfeartikel.

Lokale Tests und Browserabnahme wurden erfolgreich durchgeführt.
- V1-Entscheidung: Rezensionen werden weiterhin sofort veröffentlicht und nur nach Meldung moderiert. Falls dieses Live-Modell später erkennbar missbraucht wird, kann auf einen Vorab-Freigabe-Workflow umgestellt werden.


### 10.5 Datenexport / Auskunft – abgeschlossen

Self-Service-Datenexport:
- neue Einstellungsseite `settings/data-export`;
- Zugriff nur authentifiziert/verifiziert und nach Passwortbestätigung;
- asynchrone Erstellung über `GenerateUserDataExport`;
- ZIP im privaten Laravel-Storage, kein öffentlicher Pfad;
- Download nur für den Eigentümer des Export-Requests;
- ein laufender Export pro Nutzer;
- neuer Self-Service-Export frühestens nach 7 Tagen;
- fertiger Download 72 Stunden verfügbar, danach automatische Löschung;
- fehlgeschlagene Exporte lösen keine neue Wartefrist aus;
- bei endgültiger Accountlöschung werden bestehende Export-ZIPs und Export-Requests sofort entfernt;
- Cleanup-Command `data-exports:cleanup`, stündlich geplant.

Exportformat / Anti-Spionage:
- ZIP mit `README.txt`, strukturierten JSON-Dateien und vorhandenen eigenen Bilddateien;
- bewusst definierte Exportstruktur statt Datenbank-Dump;
- keine Passwort-Hashes, Sessions, Tabellen-/Spaltenstrukturen, API-Schlüssel, Server-/Security-Interna, internen Administrator-IDs oder fremden internen Moderationsnotizen;
- fachliche Referenzen werden soweit sinnvoll als Platzname/-Slug bzw. öffentliche Foto-UUID ausgegeben;
- aktuell enthalten: Account/Profil, Einstellungen/Consents, Reviews inkl. Versionen, angelegte Plätze, Change Requests, Favoriten/Helpful-Votes/Meldungen, Gamification, Benachrichtigungen, eigene Supportinhalte, eigene Fotos.

Rechtlicher/operativer Ansatz:
- der Self-Service-Export ist eine Komfortfunktion und begrenzt gesetzliche Betroffenenrechte nicht;
- formelle oder weitergehende Datenschutzanfragen bleiben über Hilfe & Support möglich und werden separat geprüft, insbesondere wenn Rechte Dritter, interne Moderationsinformationen oder Security-Daten betroffen sind;
- dadurch kann das 7-Tage-Limit den automatischen Ressourcenverbrauch begrenzen, ohne ein starres Limit für DSGVO-Auskunftsersuchen zu behaupten.

Technik:
- Migration `2026_09_23_150000_create_data_export_requests_table.php`;
- `UserDataExportService`, `UserDataExportController`, `GenerateUserDataExport`;
- Tests: `UserDataExportTest`.

Lokale Tests und Browserabnahme wurden erfolgreich durchgeführt.


### 10.6 Rechtstexte – abgeschlossen

Öffentliche Rechtstexte:
- `/impressum` → Impressum / Anbieterinformationen;
- `/datenschutz` → Datenschutzerklärung;
- `/nutzungsbedingungen` → Nutzungsbedingungen / Community-Regeln;
- Deutsch und Englisch;
- zentrale Betreiberangaben in `config/legal.php`, damit spätere Änderungen von Betreiberstatus/Rechtsform/Kontakt nicht in mehreren Views gepflegt werden müssen;
- aktuelle Betreiberangaben: Sascha Schwarz, Giesebrechstr. 59, 45144 Essen, schwarz.sascha@gmx.de; derzeit privat betriebenes Projekt ohne Gewerbe.

Rechtliche/technische Verknüpfung:
- Rechtstexte sind über einen globalen Footer in App- und Auth-Layouts direkt erreichbar;
- Registrierung verlinkt Nutzungsbedingungen und Datenschutzerklärung, ohne bereits einen ausdrücklichen Consent-Workflow vorzutäuschen;
- Nutzungsbedingungen verweisen auf die bestehenden Foto- und Rezensionsregeln;
- Datenschutzerklärung bildet den aktuellen technischen Ist-Zustand ab: Konto/Profil, Community-Beiträge, Fotos, Reviews, Support, Benachrichtigungen/Gamification, Löschung/Export, Security-Daten, OSM-Karten und Photon-Geocoding;
- OpenStreetMap-Tile-URLs wurden auf den aktuell vorgesehenen Host `https://tile.openstreetmap.org/{z}/{x}/{y}.png` umgestellt.

Bewusst noch offen für 10.7 / Deployment:
- Welcome-Splash speichert aktuell `camperwolf.welcome-seen.v1` in `localStorage`; Einwilligungs-/Erforderlichkeitsprüfung nach § 25 TDDDG folgt in 10.7;
- versionierte Zustimmung zu Nutzungsbedingungen/Datenschutz bei Registrierung ist noch nicht implementiert und gehört in 10.7;
- finaler Produktions-Hosting- und E-Mail-Dienstleister steht noch nicht fest und muss vor Go-live in der Datenschutzerklärung konkretisiert bzw. geprüft werden;
- Photon/OSM-Einbindung und etwaige Consent-/Proxy-Entscheidungen werden in 10.7 final bewertet;
- Betreiberstatus/Rechtsform kann später über die zentrale Legal-Konfiguration geändert werden.

Tests:
- `tests/Feature/LegalPagesTest.php` prüft öffentliche DE/EN-Seiten, Betreiberangaben und Registrierungslinks.

Der gezielte `LegalPagesTest` wurde lokal erfolgreich ausgeführt. Für 10.6 sind keine fachlichen oder technischen Teilaufgaben mehr offen; die bewusst ausgeklammerten Consent-/Drittanbieter-/Deployment-Themen gehören zu 10.7 beziehungsweise zum späteren Go-live.


### Übergabe für nächste Session – Start bei 10.7

Roadmap-Punkt 10 steht aktuell bei **10.7 Einwilligungen, Cookies und externe Dienste**.

Abgeschlossen und lokal bestätigt:
- 10.1 Datenschutz-Dateninventar;
- 10.2 Account-Löschung/Anonymisierung;
- 10.3 Foto-Inhaltsregeln;
- 10.4 Rezensions-Inhaltsregeln;
- 10.5 Datenexport/Auskunft;
- 10.6 Rechtstexte.

10.6 – finaler Stand:
- Impressum, Datenschutzerklärung und Nutzungsbedingungen sind öffentlich in DE/EN vorhanden;
- Betreiberangaben sind zentral über `config/legal.php` gepflegt;
- aktueller Projektstatus: privat betrieben, kein Gewerbe;
- globaler Footer verlinkt Impressum, Datenschutz und Nutzungsbedingungen;
- Registrierung verlinkt Nutzungsbedingungen und Datenschutz, ohne bereits eine ausdrückliche Zustimmung zu speichern;
- Nutzungsbedingungen verweisen auf Foto- und Rezensionsregeln;
- Datenschutzerklärung beschreibt den aktuellen technischen Ist-Zustand;
- `LegalPagesTest` wurde lokal erfolgreich ausgeführt.

Bewusst **nicht** mehr Teil von 10.6 und daher Startpunkt für 10.7:
1. `localStorage`-Eintrag `camperwolf.welcome-seen.v1` rechtlich/technisch bewerten und ggf. Consent-/Erforderlichkeitslogik anpassen.
2. Entscheiden und implementieren, ob/wie Nutzungsbedingungen und Datenschutz bei Registrierung versioniert bestätigt/protokolliert werden; vorhandene Tabelle `user_consents` kann dafür genutzt werden, sofern fachlich passend.
3. OpenStreetMap-Tiles und Photon-Geocoding technisch exakt prüfen:
   - Browserdirektverbindung vs. serverseitiger Proxy;
   - übertragene Daten;
   - Erforderlichkeit einer Einwilligung;
   - ggf. datenschutzfreundlichere Einbindung.
4. Prüfen, ob weitere Browser-Speicher, Cookies oder externe Requests vorhanden sind.
5. Finalen Hosting-/Mail-Provider erst vor Go-live in Datenschutz/AV-Vertragsprüfung konkretisieren.
6. Erst nach dem technischen Audit entscheiden, ob Camperwolf überhaupt ein Cookie-/Consent-Banner benötigt; kein Banner nur „vorsichtshalber“.

Für die nächste Session ist `docs/PROJECT_CONTEXT.md` wieder der primäre Einstiegspunkt. Der tatsächliche aktuelle Code auf `main` bleibt bei technischen Details maßgeblich.


### 10.7 – Einwilligungen, Browser-Speicher und externe Dienste – Umsetzung 2026-09-23

Technischer Audit auf aktuellem `main`:
- keine Werbe-, Marketing- oder Analyse-Tracker gefunden;
- keine Google-Fonts/-Analytics, Matomo, reCAPTCHA, YouTube-/iframe-Einbettungen oder vergleichbare Tracking-Dienste gefunden;
- technisch/funktional verwendete Browser-Speicher:
  - Session-/CSRF-Cookies für Anmeldung und Sicherheit;
  - `locale`-Cookie für die ausdrücklich gewählte Sprache;
  - `localStorage: camperwolf.welcome-seen.v1`;
  - `localStorage: camperwolf.browse-layout`;
  - `sessionStorage: camperwolf.feature-draft.*`;
- externe Laufzeitdienste:
  - OpenStreetMap-Tiles über `https://tile.openstreetmap.org/{z}/{x}/{y}.png`;
  - Photon/komoot für Geocoding und Reverse-Geocoding über `https://photon.komoot.io`;
- die bisherige Leaflet-Laufzeitabhängigkeit von `unpkg.com` wurde entfernt.

Umgesetzte Änderungen:
- Leaflet `^1.9.4` ist lokale npm/Vite-Abhängigkeit; CSS, JavaScript und Leaflet-Assets werden über den eigenen Build ausgeliefert;
- alle vier Karten-Views laden Leaflet nicht mehr von `unpkg.com`;
- `resources/js/app.js` stellt `window.L` bereit und signalisiert `camperwolf:leaflet-ready`; die sofort initialisierte Platz-vorschlagen-Karte wartet bei Bedarf auf dieses Ereignis;
- Welcome-Splash bleibt erhalten;
- nur der explizite Hauptbutton „Los geht’s – nicht erneut anzeigen“ schreibt `camperwolf.welcome-seen.v1 = 1`;
- X, Klick auf den Hintergrund und Escape schließen den Splash nur temporär und speichern keine dauerhafte Präferenz;
- Welcome-Splash enthält einen kurzen Hinweis, dass Camperwolf derzeit keine Werbe-, Marketing- oder Analyse-Tracker einsetzt und deshalb keinen unnötigen Cookie-Banner zeigt;
- Welcome-Splash verlinkt Nutzungsbedingungen, Datenschutzerklärung und Impressum;
- Datenschutzerklärung DE/EN beschreibt die aktuell verwendeten Cookies und Browser-Speicher nun konkret und hält fest, dass für den aktuellen Stand kein allgemeiner Consent-/Cookie-Banner vorgesehen ist;
- Registrierung verlangt nun ausdrücklich:
  - Akzeptanz der Nutzungsbedingungen;
  - Kenntnisnahme der Datenschutzerklärung;
- diese beiden Vorgänge werden versioniert in `user_consents` gespeichert:
  - `terms_acceptance` mit `config('legal.versions.terms')`;
  - `privacy_notice_acknowledgement` mit `config('legal.versions.privacy')`;
- die Datenschutzerklärung wird dabei bewusst nicht als „Einwilligung in Datenschutz“ bezeichnet;
- neue/erweiterte Tests:
  - `tests/Feature/Auth/RegistrationTest.php`;
  - `tests/Feature/ConsentAndExternalServicesTest.php`.

Fachliche Entscheidung für den aktuellen V1-Stand:
- kein allgemeiner Cookie-/Consent-Banner;
- OSM-Karten bleiben als direkte Kartenkachel-Einbindung bestehen und sind in der Datenschutzerklärung beschrieben;
- Photon wird nur bei tatsächlicher Adress-/Ortssuche angesprochen und bleibt ebenfalls transparent dokumentiert;
- finaler Produktions-Hosting- und E-Mail-Dienstleister bleibt bewusst ein Go-live-/Deployment-Punkt und ist nicht durch 10.7 vorzutäuschen.

Noch offen zur endgültigen Abnahme von 10.7:
- lokal `npm install` bzw. Abgleich des Lockfiles durchführen, falls die Arbeitskopie die neue Leaflet-Abhängigkeit noch nicht installiert hat;
- `npm run build` ausführen;
- gezielte Tests `php artisan test --filter=ConsentAndExternalServicesTest` und `php artisan test --filter=RegistrationTest` ausführen;
- anschließend sinnvollerweise kompletter `php artisan test`-Lauf.


### 10.8 – Operative Datenschutz-/Rechtsanfragen – Umsetzung 2026-09-24

Ziel:
- keine zweite Fallverwaltung neben dem bestehenden Supportsystem;
- Datenschutz-/Rechtsanfragen werden als spezielle Supporttickets geführt;
- vorhandene Self-Service-Prozesse für Datenexport und Kontolöschung bleiben die bevorzugten technischen Wege und werden nicht dupliziert.

Öffentlicher Einstieg:
- eigener öffentlicher Einstieg `support/datenschutz-recht` / Route `support.privacy-legal`;
- erreichbar über Hilfe & Support;
- auch für Gäste nutzbar;
- eigene Anfragearten:
  - Auskunft;
  - Löschung;
  - Berichtigung;
  - Widerspruch;
  - Einschränkung der Verarbeitung;
  - Datenübertragbarkeit;
  - sonstige Datenschutzanfrage;
  - sonstige rechtliche Anfrage;
- eingeloggte Nutzer erhalten direkte Links zu vorhandenem Datenexport und Kontolöschungsprozess;
- Hinweis, keine Ausweis-/Identitätsdokumente vorsorglich einzureichen.

Fall-Metadaten:
- neue Tabelle `support_ticket_privacy_cases` als 1:1-Erweiterung von `support_tickets`;
- speichert Identitätsstatus, Eingangszeitpunkt, ursprüngliche Frist, aktuelle Frist, optionale Begründung einer Fristverlängerung und Abschlusszeitpunkt;
- reguläre Supporttickets erhalten keinen Datensatz in dieser Tabelle;
- für die eigentlichen DSGVO-Betroffenenrechte (Auskunft, Löschung, Berichtigung, Widerspruch, Einschränkung, Datenübertragbarkeit) wird automatisch eine Monatsfrist bis zum Tagesende hinterlegt;
- eine Verlängerung dieser DSGVO-Frist ist im Admin auf höchstens zwei weitere Monate begrenzt und verlangt eine Begründung;
- sonstige Datenschutz- und sonstige rechtliche Anfragen werden ebenfalls als Spezialfall geführt, erhalten aber bewusst keine erfundene gesetzliche Monatsfrist; bei Bedarf kann intern eine manuelle Frist gesetzt werden;
- Status `resolved` oder `closed` setzt den Abschlusszeitpunkt, Wiederöffnung entfernt ihn wieder.

Identitätsprüfung:
- Zustände: keine zusätzliche Prüfung nötig / Prüfung offen / Identität bestätigt;
- bei eingeloggten, ihrem Account zugeordneten Anfragen startet der Fall mit „keine zusätzliche Prüfung nötig“;
- bei Gastanfragen startet er mit „Prüfung offen“;
- zusätzliche Identitätsprüfung nur bei Bedarf.

Admin:
- Datenschutz-/Rechtsfälle im bestehenden Supportbereich filterbar;
- Frist wird in der Ticketliste angezeigt, überfällige offene Fälle werden hervorgehoben und priorisiert;
- Detailansicht zeigt Eingangsdatum, ursprüngliche Frist, aktuelle Frist, Identitätsstatus, Verlängerungsbegründung und Abschlusszeitpunkt;
- Änderungen dieser Metadaten werden in der bestehenden Ticket-Historie protokolliert;
- bei Gastfällen wird ausdrücklich gewarnt, dass Supportantworten aktuell noch nicht automatisch per E-Mail zugestellt werden. Bis zum finalen Produktions-Mail-Setup muss die Antwort zusätzlich über die angegebene E-Mail-Adresse versendet und intern dokumentiert werden.

Rechtlicher Rahmen für die technische Fristenhilfe:
- Art. 12 Abs. 3 DSGVO: Reaktion auf Betroffenenanträge grundsätzlich unverzüglich, spätestens innerhalb eines Monats;
- bei erforderlicher Verlängerung bis zu zwei weitere Monate; Information über Verlängerung und Gründe innerhalb des ersten Monats;
- Art. 12 Abs. 6 DSGVO: zusätzliche Identitätsinformationen nur bei begründeten Zweifeln an der Identität.

Tests:
- neuer `tests/Feature/PrivacyLegalSupportTest.php`;
- prüft separaten öffentlichen Einstieg, Gast- und eingeloggte Fälle, Monatsfrist für Betroffenenrechte, fehlende automatische DSGVO-Frist bei allgemeinen Rechtsanfragen, Nicht-Erzeugung bei regulärem Support, Adminfilter/-bearbeitung sowie Regeln für Fristverlängerung.

Abnahme 10.8:
- lokale Migration erfolgreich ausgeführt;
- `PrivacyLegalSupportTest` vollständig grün;
- `SupportLocaleTest` vollständig grün;
- `SupportAdminLocaleTest` vollständig grün;
- öffentlicher Einstieg und Admin-Datenschutzfall im Browser geprüft; Darstellung und Funktionen passen;
- 10.8 ist damit fachlich und technisch abgeschlossen.

### Lokaler Standardablauf bei Repo-Änderungen

Für Änderungen, die aus einer Chat-/Entwicklungssession direkt nach GitHub committed wurden, gilt lokal grundsätzlich dieser kurze Ablauf:

```powershell
git pull
php artisan optimize:clear
```

Zusätzlich nur wenn erforderlich:
- bei neuen oder geänderten Datenbankmigrationen:
```powershell
php artisan migrate
```
- bei Änderungen an Frontend-Abhängigkeiten oder gebündelten Assets:
```powershell
npm install
npm run build
```
- anschließend die für den jeweiligen Punkt genannten gezielten Tests ausführen.

Für 10.8 waren erforderlich:

```powershell
git pull
php artisan optimize:clear
php artisan migrate

php artisan test --filter=PrivacyLegalSupportTest
php artisan test --filter=SupportLocaleTest
php artisan test --filter=SupportAdminLocaleTest
```

Sascha führt lokale Tests selbst aus; eine separate temporäre Testumgebung ist nicht erforderlich.


### Abschluss Roadmap-Punkt 10 – Datenschutz & Recht

10.9 Abschlussprüfung wurde am 2026-09-24 erfolgreich durchgeführt.

Abgeschlossen:
- 10.1 Datenschutz-Dateninventar;
- 10.2 Account-Löschung/Anonymisierung;
- 10.3 Foto-Inhaltsregeln;
- 10.4 Rezensions-Inhaltsregeln;
- 10.5 Datenexport/Auskunft;
- 10.6 Rechtstexte;
- 10.7 Einwilligungen, Browser-Speicher und externe Dienste;
- 10.8 Operative Datenschutz-/Rechtsanfragen;
- 10.9 Gesamtprüfung und Regressionstest.

Abnahme 10.9:
- gezielte Tests der Teilbereiche wurden erfolgreich ausgeführt;
- anschließend wurde die vollständige Testsuite mit `php artisan test` ausgeführt;
- die komplette Testsuite lief grün durch;
- ein veralteter Erwartungswert in `PhotoAdminLocaleTest` wurde an die aktuelle englische Fotomoderationsoberfläche angepasst;
- die Fotomoderation selbst war dabei funktional unverändert und fehlerfrei;
- die zentralen Datenschutz-/Rechtsseiten und Prozesse wurden bereits während 10.1–10.8 manuell im Browser geprüft.

**Roadmap-Punkt 10 ist damit vollständig abgeschlossen.**

### Roadmap-Reihenfolge ab 2026-09-24

Der ursprünglich als nächster Schritt vorgesehene Deployment-/Operations-Block bleibt erhalten, wird aber bewusst nach hinten verschoben.

**Der vorgezogene externe Datenimport ist für den aktuellen V1-Stand praktisch abgeschlossen. Als nächster aktiver Roadmap-Punkt folgt wieder der Deployment-/Operations-Block.**

Ziel des vorgezogenen Import-Blocks:
- offizielle/open-data Quellen für Stell-/Rast-/Campingplätze anbinden;
- zunächst Deutschland, später weitere Länder/Quellen;
- mögliche Quellen u. a. Mobilithek, GovData und weitere öffentliche APIs/Datenbestände;
- reale Basisdaten für V1 importieren, damit Camperwolf nicht mit leerem Datenbestand startet;
- Quellensystem/API als eigene Herkunft führen;
- API-/Importdaten niemals blind über unabhängige Nutzer-, Betreiber- oder Adminänderungen schreiben;
- Änderungen aus Importen transparent im Platz-/Änderungslog mit der jeweiligen Quelle als Akteur protokollieren;
- Duplikate/nahe bestehende Plätze vor Neuanlage prüfen;
- Import so strukturieren, dass weitere Quellen später ergänzt werden können.

Der Deployment-/Operations-Block folgt danach weiterhin vor dem eigentlichen Go-live und umfasst u. a. Produktionshosting, Mailversand, Backups, Monitoring/Logging, Produktionskonfiguration und finale Anbieter-/Datenschutzangaben.


### Externe Datenimporte – Grundgerüst 2026-09-24

Nach Abschluss von Roadmap-Punkt 10 wurde der externe Datenimport vorgezogen. Für den Launch sollen zunächst zwei breite offizielle Quellen angebunden werden:
- **DZT Knowledge Graph / Open Data Germany** für Camping-, Wohnmobil- und touristische Basisdaten; API-Zugang ist angefragt und noch nicht freigeschaltet.
- **Mobilithek / SID** für Parkplätze und Rastanlagen; Zugang/Freischaltung ist ebenfalls noch ausstehend. Zuerst werden statische Standort-/Bedingungsdaten betrachtet, dynamische Verfügbarkeit später getrennt.

Festgelegte Importprinzipien:
- wenige große Bundes-/Landesquellen werden vielen kleinen Kommunalquellen vorgezogen;
- zusätzliche kleine Quellen nur, wenn sie relevanten Mehrwert liefern;
- möglichst viele für Camperwolf relevante Angaben übernehmen (z. B. Ausstattung, Betreiber, Öffnungszeiten, Preise, Fahrzeugtypen), irrelevante Verwaltungsdaten nicht;
- Mapping erfolgt je Quelle;
- Community-/Camperwolf-Werte und externe Quellenwerte werden parallel geführt;
- vorhandene Communitywerte haben Vorrang und werden durch APIs nicht blind überschrieben;
- bei mehreren externen Quellen können mehrere Werte zum selben Feld gespeichert werden;
- widersprüchliche ältere externe Werte dürfen ignoriert werden, wenn eine aktuellere externe Quelle den Communitywert bestätigt;
- fehlende/unbekannte Camperwolf-Werte dürfen aus der aktuellsten geeigneten Quelle ergänzt werden;
- neue Plätze dürfen nach Duplikatprüfung automatisch angelegt werden;
- **keine automatische Löschung, Archivierung oder Deaktivierung von Plätzen durch externe Quellen**;
- verschwindet ein externer Datensatz, entsteht höchstens ein Moderationshinweis;
- regelmäßiger Basissync ungefähr monatlich; dynamische Daten können später häufiger synchronisiert werden;
- ohne Änderungen entsteht kein Platzlog-Eintrag; ein separates „zuletzt geprüft“ darf den erfolgreichen Prüfzeitpunkt anzeigen;
- Rohdaten mindestens bis zum nächsten Abruf behalten, soweit Lizenz/Nutzungsbedingungen nichts anderes verlangen;
- auffällige Imports werden fail-closed gestoppt: Schema-/Datentypfehler, viele fehlende Pflichtfelder, extreme Mengenänderungen, ungültige Payloads usw.;
- Duplikat-Matching wird anhand realer Importdaten kalibriert; unsichere Fälle gehen in Moderation und bestehende Merge-Funktion bleibt Fallback;
- öffentliche/verständliche Platzhistorie ist getrennt vom technischen Auditlog.

Umgesetztes technisches Grundgerüst:
- Migration `2026_09_24_093000_create_external_import_tables.php`;
- `external_sources`: Quelle, Anbieter, Adapter, Lizenz, Intervall, Konfiguration, letzter Check/Erfolg;
- `external_import_runs`: Dry-Run/Apply, Status, Mengen, Fehler, Validierungsreport und Statistiken;
- `external_raw_snapshots`: Rohsnapshot-Metadaten und Storage-Pfad;
- `external_records`: externe stabile IDs, Zuordnung zum Camperwolf-Platz, Status active/missing/etc., normalisierte Daten;
- `external_record_fields`: feldweise externe Werte, damit mehrere Quellen parallel bestehen können;
- `external_import_review_items`: Moderations-/Prüfqueue für Duplikate, Missing Records, Konflikte und Anomalien;
- `place_history`: eigener verständlicher Platzverlauf mit User/System/externer Quelle als Akteur, ausdrücklich getrennt von `audit_logs`;
- `ExternalImportGuard`: deterministische Plausibilitätschecks für Datensatzmenge, Pflichtfelder und Datentypen;
- `ExternalImportRunService`: Lebenszyklus eines Importlaufs;
- `PlaceHistoryService`: Einträge für Nutzer, System oder konkrete externe Quelle;
- `ExternalImportFoundationTest`: prüft fail-closed Validierung, getrennten Platzverlauf und dass fehlende externe Datensätze keine Plätze löschen.

Noch **nicht** umgesetzt:
- konkreter SID-/DATEX-II-Adapter;
- konkreter DZT-Adapter;
- echtes Feldmapping;
- Duplikat-Scoring;
- automatische Synchronisationsjobs;
- Admin-UI für Quellen/Importläufe/Review-Items;
- öffentliche Anzeige des Platzverlaufs;
- Rohsnapshot-Speicherung/Cleanup selbst (Tabellenbasis ist vorhanden).

Lokale Abnahme:
- Migration erfolgreich ausgeführt;
- `ExternalImportFoundationTest` vollständig grün;
- anschließend vollständige Testsuite mit `php artisan test` ausgeführt;
- komplette Testsuite grün.

Nächster Schritt:
- ersten realen Adapter auf Basis der zuerst freigeschalteten Quelle bauen; aktuell wird SID/Mobilithek priorisiert, solange auf DZT gewartet wird.


### DATEX-II / Mobilithek – Mapping & Staging 2026-09-24

Der bundesweite statische ITP-Testbestand wurde lokal erfolgreich analysiert:
- 2.612 Parking Records;
- 0 ohne Namen;
- 57 ohne Hauptkoordinaten;
- 310 mit unbekannter Gesamtkapazität (0 wird bewusst als unbekannt behandelt);
- 6.795 Ausstattungseinträge;
- 3.972 Zufahrten;
- Validierung erfolgreich.

Im Vollbestand vorkommende Ausstattung:
- refuseBin, picnicFacilities, toilet, shower, playground, defibrillator, firstAidEquipment, freshWater, tollTerminal, dumpingStation, informatonStele, iceFreeScaffold.

Feature-Katalog erweitert:
- vorhandene Features werden wiederverwendet: `waste-bins`, `rest-picnic-area`, `toilet`, `shower`, `playground`, `fresh-water`;
- neue Kategorie `medical-first-aid` / „Erste Hilfe & Medizin“;
- neue boolesche Features `defibrillator`, `first-aid-equipment`, `dumping-station`;
- `dumpingStation` bleibt bewusst generisch, da DATEX-II nicht sicher zwischen Grau-/Schwarzwasser unterscheidet;
- tollTerminal, informatonStele und iceFreeScaffold bleiben vorerst als externe Originaldaten erhalten, aber ohne Camperwolf-Feature-Mapping.

DATEX-II-Mapping:
- Zieltyp als Vorschlag: `rest-area`;
- Hauptkoordinate wird bevorzugt;
- fehlt sie, darf eine vorhandene vehicleEntrance-, danach vehicleExit-, danach sonstige Access-Koordinate als ausdrücklich markierter Fallback verwendet werden;
- `available` -> `available`, `notAvailable` -> `unavailable`, unbekannt -> `unknown`;
- Kapazität 0 gilt als unbekannt;
- Fahrzeugkapazitäten >0 werden als externe Quelldaten erhalten;
- nur DATEX `car` wird als direkter Camperwolf-Fahrzeughinweis `car` verstanden;
- `carWithTrailer` wird ausdrücklich **nicht** automatisch als Wohnwagen/Caravan interpretiert;
- Lkw/Schwertransport/Bus-Daten bleiben als externe Kapazitätsdaten erhalten und werden nicht als Camper-Eignung erfunden;
- `labelSecurityLevel=none`, `labelServiceLevel=none` und `certifiedSecureParking=false` werden nicht als Camperwolf-Sicherheitsurteil interpretiert.

Neue Services:
- `Datex2ParkingMapper`: fachliches Normalisieren auf Camperwolf-nahe externe Daten;
- `ExternalRecordStagingService`: idempotentes Speichern in `external_records` und `external_record_fields`, ohne `places` zu verändern;
- `Datex2ParkingCandidateService`: unverbindliche Kandidatensuche bis 1 km mit Distanz/Namensähnlichkeit, keine automatische Verknüpfung;
- `Datex2ParkingStageService`: Datei-/Ordnerverarbeitung, Fail-Closed-Validierung, Mapping, Kandidatenanalyse und Staging.

CLI:
- `php artisan imports:datex2-parking-stage "D:\sampledata"` verarbeitet einen Teilbestand und verändert fehlende externe IDs nicht;
- `php artisan imports:datex2-parking-stage "D:\sampledata" --complete` behandelt den Inhalt als vollständigen Snapshot und markiert bisher bekannte, jetzt fehlende externe IDs ausschließlich in `external_records.status=missing`;
- beide Modi ändern **keine** Camperwolf-Plätze und **keine** Communitywerte.

Noch offen:
- exaktes Duplicate-Scoring/Auto-Linking anhand der realen Kandidaten;
- Anlegen neuer Camperwolf-Plätze aus validierten externen Records;
- Übernahme externer Werte als Fallback, wenn Communitywert unbekannt ist;
- Konfliktanzeige bei abweichenden Community-/API-Werten;
- API-Abruf statt lokaler XML-Datei;
- Admin-UI für Importquellen/-läufe/-Reviewqueue.


### Externe Record-Klassifizierung / Review-Queue 2026-09-24

Nach erfolgreichem Staging des bundesweiten DATEX-II-ITP-Snapshots wurde die nächste Sicherheitsstufe umgesetzt.

Neue Klassifizierung an `external_records`:
- `new_candidate`: externer Record hat verwertbare Koordinaten und aktuell keinen Camperwolf-Kandidaten im Suchfenster;
- `possible_duplicate`: mindestens ein aktiver Camperwolf-Platz wurde im Kandidatenfenster gefunden;
- `needs_review`: Record ist fachlich/technisch nicht automatisch verwertbar, aktuell insbesondere fehlende Koordinaten oder ungültige normalisierte Daten.

Umsetzung:
- Migration `2026_09_24_113000_add_external_record_classification.php` ergänzt `classification` und `classified_at`;
- `ExternalRecordClassificationService` klassifiziert aktive, noch nicht mit einem Platz verknüpfte externe Records;
- unsichere Fälle werden in `external_import_review_items` als `pending` angelegt;
- Wiederholung ist idempotent: vorherige offene Review-Items desselben Records werden auf `superseded` gesetzt statt dupliziert;
- `new_candidate` erzeugt kein Review-Item;
- jeder Klassifizierungslauf wird als `external_import_runs.mode=classify` protokolliert.

Aktuelle Duplicate-Strategie ist absichtlich konservativ:
- bestehender Kandidatenservice sucht aktive Camperwolf-Plätze im Umkreis bis 1 km und liefert Distanz + Namensähnlichkeit;
- **jeder** Treffer im 1-km-Kandidatenfenster führt vorerst zu `possible_duplicate`;
- es findet noch keine automatische Verknüpfung oder Zusammenführung statt;
- die Schwellen werden erst anhand echter Plätze statt Performance-Testdaten kalibriert.

CLI:
- `php artisan imports:classify-external` klassifiziert standardmäßig die Quelle `mobilithek-itp-bab`;
- alternativ `php artisan imports:classify-external <source-slug>`;
- der Befehl verändert keine Camperwolf-Plätze und keine Communitywerte.

Nächste sinnvolle Schritte:
- lokale Migration + `ExternalRecordClassificationTest`;
- danach Klassifizierung des bereits gestagten 2.612er Snapshots;
- erwartbar: 56 Records ohne Koordinaten -> `needs_review`; Performance-Testdaten erzeugen einige künstliche `possible_duplicate`-Treffer;
- später Admin-UI für Review-Queue und echte Kalibrierung der Duplicate-Regeln.


### Admin-Importzentrale / manueller CSV-Import 2026-09-24

Die externe Import-Infrastruktur ist jetzt über eine Admin-Oberfläche erreichbar.

Admin-Importzentrale:
- Route `/admin/imports`;
- Zugriff nur mit `admin.access` + `imports.view_history`;
- Upload zusätzlich nur mit `imports.run`;
- auf der Admin-Startseite erscheint eine Import-Kachel inklusive Anzahl offener Review-Fälle;
- drei Bereiche: Import starten, Import-Historie, Review-Queue;
- bekannte externe Quellen werden auf der Seite angezeigt.

Manueller Upload:
- auswählbare Adapter aktuell: DATEX-II/Mobilithek und CSV;
- Upload-Limit 50 MB;
- DATEX-II akzeptiert XML;
- CSV akzeptiert ausschließlich `.csv`;
- Option „Vollständiger Snapshot“ steuert, ob nicht mehr enthaltene externe IDs der jeweiligen Quelle im Quellenlayer als `missing` markiert werden;
- Camperwolf-Plätze werden durch den Upload weiterhin nicht direkt angelegt, überschrieben oder gelöscht.

CSV:
- feste Vorlage per `/admin/imports/csv-template`;
- UTF-8 CSV, Komma oder Semikolon wird erkannt;
- Pflichtspalten: `external_id`, `name`;
- weitere Standardspalten: `latitude`, `longitude`, `street`, `house_number`, `postal_code`, `city`, `country_code`, `place_type`, `operator`, `website`, `phone`, `parking_spaces`;
- Merkmale werden über Spalten `feature:<feature-slug>` importiert;
- unbekannte Spalten oder unbekannte Feature-Slugs brechen den Import fail-closed ab;
- Featurewerte: leer/unknown/unbekannt/? -> unknown; yes/ja/available/vorhanden/1/true -> available; no/nein/unavailable/nicht vorhanden/0/false -> unavailable;
- Teilkoordinaten sind verboten: Latitude/Longitude müssen entweder beide vorhanden oder beide leer sein;
- Koordinaten werden auf gültige Bereiche geprüft;
- `parking_spaces=0` wird wie bei DATEX konservativ als unbekannt behandelt;
- doppelte `external_id` innerhalb einer CSV brechen den Import ab;
- jede CSV-Quelle benötigt eine Quellenbezeichnung; gleiche Bezeichnung erzeugt/reused dieselbe technische Quelle `manual-csv-<slug>`.

Provenienz / Historie:
- hochgeladene Rohdatei wird auf dem lokalen privaten Storage unter `imports/manual` gespeichert;
- SHA-256, Dateigröße, MIME-Type und Storage-Pfad werden in `external_raw_snapshots` protokolliert;
- erfolgreicher Upload erzeugt einen `external_import_runs`-Eintrag mit `mode=manual_upload`;
- danach läuft automatisch die bestehende externe Record-Klassifizierung, die einen eigenen `mode=classify`-Lauf protokolliert;
- bestehende Source-Metadaten werden beim Wiederverwenden einer Quelle nicht mit nicht gelieferten Nullwerten überschrieben; ursprüngliches `created_at` bleibt erhalten.

Review-Queue:
- zeigt `pending`, `superseded`, `resolved` oder alle Einträge;
- mögliche Dubletten zeigen Kandidaten, Entfernung und Namensähnlichkeit;
- fehlende Koordinaten werden explizit gekennzeichnet;
- aktuell reine Sicht-/Prüfoberfläche: noch keine automatische Aktion „verknüpfen“, „als neuen Platz anlegen“ oder „ignorieren“; diese Aktionen werden erst zusammen mit dem sicheren Place-Apply-Workflow ergänzt.

Neue Kern-Dateien:
- `app/Http/Controllers/Admin/ImportCenterController.php`
- `resources/views/admin/imports/index.blade.php`
- `app/Services/Imports/ManualCsvPlaceParser.php`
- `tests/Feature/ManualCsvPlaceParserTest.php`
- `tests/Feature/AdminImportCenterTest.php`


### Import-Review-Aktionen / Apply-Workflow 2026-09-24

Die Admin-Importzentrale kann offene Review-Fälle jetzt aktiv bearbeiten.

Aktionen für offene `external_import_review_items`:
- „Mit vorhandenem Platz verknüpfen“: setzt ausschließlich `external_records.place_id`, Klassifizierung `linked`, löst alle offenen Review-Fälle dieses Records auf und legt Platzverlauf + Audit an;
- „Als neuen Platz übernehmen“: legt einen veröffentlichten Camperwolf-Platz aus belastbaren Basisdaten an und verknüpft den externen Record, Klassifizierung `created`;
- „Zurückstellen“: Review bleibt `pending`, ein interner Deferred-Hinweis wird in den Review-Details gespeichert;
- „Ignorieren“: Klassifizierung `ignored`, Review wird `resolved`, kein Platz wird angelegt oder verändert.

Sicherheitsregeln beim Neu-Anlegen:
- erforderlich: Name + gültige Latitude/Longitude;
- fehlende Koordinaten verhindern die automatische Platzanlage;
- bevorzugter Platztyp kommt aus `suggested_place_type`, Fallback `rest-area`;
- neuer Platz wird nur durch explizite Admin-Aktion veröffentlicht;
- `legal_status` und `opening_status` bleiben `unclear`;
- angelegt werden initial: Name, Typ, Koordinaten, Adresse, Betreiber, positive Stellplatzzahl, Beschreibung und Kontakte;
- DATEX/CSV-Telefon wird auf Camperwolf-`contact_type=telephone` gemappt, Website auf `website`, E-Mail auf `email`;
- eindeutige externe Featurewerte (`available`/`unavailable` ohne Quellkonflikt) werden beim erstmaligen Anlegen zusätzlich als initiale `place_features` materialisiert; der originale Quellwert bleibt parallel dauerhaft im External Layer erhalten;
- Verknüpfen überschreibt keine vorhandenen Camperwolf-Felder;
- alle Entscheidungen werden über `audit_logs` protokolliert; Verknüpfung/Neuanlage erzeugt zusätzlich `place_history` mit konkreter externer Quelle.

Neue Kern-Datei:
- `app/Services/Imports/ExternalRecordReviewService.php`
- Tests: `tests/Feature/ExternalRecordReviewServiceTest.php`

Noch offen beziehungsweise spätere Härtung:
- weitere Kalibrierung der Duplicate-Regeln anhand zusätzlicher echter Quellen;
- robuste dauerhafte Ignore-/Reopen-Regel, Reverse/Unlink und weitere Import-Härtung;
- zusätzliche Quellenadapter nach externer Freigabe (DZT, SID/Mobilithek).


### Neue externe Kandidaten in der Import-Zentrale 2026-09-24

Die `new_candidate`-Records sind jetzt direkt in der Admin-Importzentrale sichtbar und einzeln bearbeitbar.

UI:
- eigener Bereich „Neue Kandidaten“ zwischen Import-Historie und Review-Queue;
- paginiert mit 20 Records pro Seite;
- Suchfeld über Name/normalisierte Daten/externe ID;
- Filter nach externer Quelle;
- Anzeige von Name, Quelle, externer ID, Koordinaten, Adresse, Betreiber, vorgeschlagenem Platztyp, positiver Stellplatzzahl und als `available` gemappten Quell-Features;
- Auswahl-Checkboxen zählen markierte Kandidaten;
- kontrollierte Bulk-Übernahme für Auswahl und zusätzlich vollständige Übernahme aller offenen eindeutigen Kandidaten sind vorhanden.

Aktionen:
- „Platz übernehmen“ legt den Kandidaten über denselben sicheren Apply-Pfad wie ein Review als veröffentlichten Camperwolf-Platz an;
- nur `active + new_candidate + place_id IS NULL` darf direkt übernommen werden;
- nach Übernahme: `external_records.classification=created`, `place_id` gesetzt, Place-History und Audit werden geschrieben;
- „Ignorieren“ setzt ausschließlich `classification=ignored` und schreibt Audit; kein Camperwolf-Platz wird verändert oder angelegt.

Service:
- `ExternalRecordReviewService::createPlaceFromCandidate()`
- `ExternalRecordReviewService::ignoreCandidate()`
- gemeinsame interne Create-Logik für Review- und Candidate-Pfad verhindert unterschiedliche Apply-Regeln.

Routes:
- `admin.imports.candidates.create-place`
- `admin.imports.candidates.ignore`

Tests:
- `ExternalRecordReviewServiceTest` prüft direkte Candidate-Neuanlage und Ignorieren;
- `AdminImportCenterTest` prüft die Kandidatendarstellung inklusive Quell-Basisdaten.

Weiterhin bewusst:
- der externe Originalwert bleibt dauerhaft im External Layer erhalten;
- eindeutige Quellwerte dürfen nur initial in `place_features` materialisiert werden und überschreiben niemals bestehende aktive Camperwolf-/Communitywerte;
- unklare beziehungsweise mögliche Dubletten bleiben in der Review-Queue und werden nicht durch die Bulk-Freigabe erzwungen.


### Praxistest-Kandidaten für externe Imports 2026-09-24

In der Admin-Importzentrale wird oberhalb der normalen `new_candidate`-Liste jetzt ein Bereich „Praxistest-Kandidaten“ angezeigt.

Ziel:
- vor einer Bulk-Freigabe einige reale externe Datensätze bewusst einzeln übernehmen und das resultierende Camperwolf-Profil prüfen;
- keine automatische Qualitätsbewertung und keine fachliche Freigabeentscheidung.

Automatische Auswahl aus allen offenen `active + new_candidate + place_id IS NULL` Records:
- „Einfacher Datensatz“: bevorzugt geringe Zusatzkomplexität mit ungefähr zwei verfügbaren Quell-Features und ohne Kontaktlast;
- „Betreiber & Kontakte“: bevorzugt Betreibername + möglichst viele Kontakte; Namen mit „Autohof“ erhalten zusätzliche Priorität;
- „Viele Ausstattungsmerkmale“: Record mit möglichst vielen als `available` gemappten Features;
- „Wenig Metadaten“: geringste Metadatendichte bei weiterhin vorhandenem Namen und gültigen Koordinaten;
- derselbe externe Record wird nicht mehrfach als Praxistest-Kandidat angezeigt, sofern genügend unterschiedliche Kandidaten vorhanden sind.

UI:
- zeigt Name, Quelle, externe ID, Platztyp, Stellplatzzahl, Adresse, Betreiber, Anzahl Kontakte und verfügbare Quell-Features;
- Button „Testweise übernehmen“ nutzt exakt denselben sicheren Candidate-Apply-Pfad wie die normale Kandidatenliste;
- nach erfolgreicher Übernahme Weiterleitung direkt zum erzeugten Platzprofil;
- Link „In Kandidatenliste anzeigen“ setzt Suche + Quellenfilter;
- Hinweis fordert zur Prüfung von Name, Adresse, Betreiber, Kontakten, Stellplatzzahl und bewusst noch nicht übernommenen Quell-Features auf.

Implementierung:
- Auswahl erfolgt speicherschonend über DB-Cursor in `ImportCenterController::practiceCandidates()`;
- Helper `practiceCandidateData()` normalisiert nur die für die Auswahl/Anzeige benötigten Werte;
- `AdminImportCenterTest` deckt die Sichtbarkeit des Praxistest-Bereichs ab.


### Externe Feature-Fallbacks und Konflikthinweise 2026-09-24

Externe Featurewerte aus verknüpften `external_records` werden auf dem öffentlichen Platzprofil berücksichtigt. Zusätzlich werden bei neu aus externer Quelle angelegten Plätzen eindeutige, konfliktfreie Statuswerte einmalig als initiale `place_features` materialisiert; der External Layer bleibt parallel als Herkunftsebene bestehen.

Priorität:
- gespeicherte Camperwolf-/Community-/Owner-/Admin-Angaben bleiben die führende interne Information;
- ist der interne Feature-Status `unknown`, darf der neueste belastbare externe Wert `available` oder `unavailable` als reiner **Anzeige-Fallback** verwendet werden;
- der intern gespeicherte Status bleibt dabei unverändert. Dadurch wird ein externer Fallback beim späteren Bearbeiten nicht versehentlich zu einer Community-Angabe;
- `unknown` aus einer externen Quelle erzeugt keine Aussage;
- externe Featurewerte mit mapper-internem `conflict=true` werden weder als Fallback noch als Konflikthinweis verwendet.

Konflikthinweis:
- hat Camperwolf intern bereits einen bekannten Wert und der **neueste belastbare externe Wert** weicht davon ab, erscheint am bestehenden Info-Symbol des Merkmals ein zusätzlicher Hinweis;
- bestehender Freitext und Quellenkonflikt werden im selben Tooltip angezeigt, z. B. Freitext plus „Laut Mobilithek … ist dieses Merkmal nicht vorhanden.“;
- stimmt die externe Quelle mit dem internen Wert überein, erscheint kein zusätzlicher Quellenhinweis;
- ältere widersprüchliche Quellen erzeugen keinen Warnhinweis, wenn eine neuere belastbare Quelle den internen Camperwolf-Wert bestätigt;
- bei intern `unknown` und externem Fallback wird kein zusätzlicher Quellenhinweis am Info-Symbol gezeigt; die externe Quelle dient dann nur der Darstellung des Status.

Implementierung:
- `app/Services/Imports/ExternalFeatureOverlayService.php` liest aktive, mit dem Platz verknüpfte externe Records und ordnet Featurewerte per Feature-Slug zu;
- Sortierung für den wirksamen externen Wert: `source_updated_at` absteigend, danach `last_seen_at`, danach Record-ID;
- `FeatureWorkflowService::present()` unterstützt `display_status`, sodass Anzeige und persistierter Status getrennt bleiben;
- `PlaceProfileController` wendet den Overlay-Service vor der Feature-Präsentation an;
- bei nutzerspezifischen offenen Änderungsvorschlägen bleibt der vorgeschlagene Wert oberhalb des externen Fallbacks;
- `resources/views/places/_feature-category.blade.php` nutzt weiterhin das vorhandene Info-Symbol/Tooltip für Freitext und nur echte Quellenabweichungen.

Tests:
- `tests/Feature/ExternalFeatureOverlayTest.php` prüft:
  - externen Fallback ohne Mutation des internen Status;
  - Konflikt bei abweichendem neuesten Quellwert;
  - keinen Konflikt bei neuerer bestätigender Quelle trotz älterem Widerspruch;
  - Ignorieren von quellenintern als konfliktbehaftet markierten Werten;
  - gemeinsamen Tooltip aus Freitext + Quellenkonflikt.


### Kontrollierte Bulk-Übernahme externer Kandidaten 2026-09-24

Nach erfolgreichem Praxistest der Einzelübernahme unterstützt die Admin-Importzentrale jetzt eine kontrollierte Bulk-Übernahme für offene `new_candidate`-Records.

UI:
- Checkbox pro Kandidat;
- „Alle auf dieser Seite“ für die aktuell sichtbare Kandidatenseite;
- Live-Zähler der ausgewählten Records;
- Button „Ausgewählte übernehmen“ bleibt deaktiviert, solange nichts ausgewählt ist;
- Bestätigungsdialog vor der Übernahme;
- bestehende Einzelübernahme und Einzel-Ignorieren bleiben unverändert.

Sicherheitsregeln:
- nur explizit übermittelte Record-IDs werden verarbeitet;
- serverseitiges Limit: maximal 50 Kandidaten pro Bulk-Aufruf;
- IDs müssen eindeutig sein;
- vor jeder Mutation werden alle ausgewählten Records unter DB-Lock erneut validiert;
- jeder Record muss weiterhin `status=active`, `classification=new_candidate` und `place_id IS NULL` haben;
- `possible_duplicate`, `needs_review`, bereits verknüpfte/erstellte oder inaktive Records sind nicht bulk-fähig;
- der Batch läuft atomar in einer Datenbanktransaktion: ist ein ausgewählter Record nicht mehr gültig, wird kein Platz des Batches übernommen;
- die eigentliche Platzanlage nutzt denselben geprüften `createPlaceFromRecordObject()`-Pfad wie die Einzelübernahme;
- der External Layer bleibt dauerhaft bestehen; eindeutige konfliktfreie Statuswerte werden bei der Platzanlage zusätzlich initial in `place_features` materialisiert, ohne bestehende aktive Camperwolfwerte zu überschreiben.

Implementierung:
- `ExternalRecordReviewService::createPlacesFromCandidates()`;
- `ImportCenterController::createPlacesFromCandidates()`;
- Route `admin.imports.candidates.bulk-create`;
- Bulk-Formular in `resources/views/admin/imports/index.blade.php`.

Tests:
- Service-Test: nur ausgewählte Records werden angelegt;
- Service-Test: vollständiger Rollback, wenn ein Record nicht mehr `new_candidate` ist;
- Admin-Test: UI und echte Bulk-Route mit selektiver Übernahme.


### Fahrzeugtypen und Stellplatzanzahl je Fahrzeugtyp 2026-09-24

Das bestehende System `place_vehicle_types` wurde um eine optionale Stellplatzanzahl je Fahrzeugtyp erweitert.

Datenmodell:
- `place_vehicle_types.capacity`: optionale positive Ganzzahl; leer = Eignung bekannt, Anzahl unbekannt.
- `place_details.pitch_count` bleibt als unabhängige allgemeine Gesamtzahl bestehen.
- `place_details.pitch_count_source` dokumentiert bei extern angelegten Plätzen, ob die Gesamtzahl direkt aus der Quelle (`direct`) oder aus positiven Fahrzeugkapazitäten (`summed_vehicle_capacities`) abgeleitet wurde.
- Abweichungen zwischen allgemeiner Gesamtzahl und Summe der Fahrzeugtypen sind zulässig und werden nicht als Fehler behandelt.

UI:
- Bereich heißt „Geeignet für (Anz. Stellplätze)“.
- Jede Fahrzeugzeile besitzt Checkbox plus optionales rechtsbündiges Zahlenfeld.
- Ohne Zahl wird im Profil nur der Fahrzeugtyp gezeigt, z. B. `PKW`.
- Mit Zahl wird `PKW (25)` angezeigt.

DATEX-II Mapping:
- `car` → `car` / PKW
- `carWithTrailer` → `car-with-trailer` / PKW mit Anhänger
- `lorry` → `truck` / LKW
- `bus` → `coach` / Reisebus
- `heavyHaulageVehicle` wird im DATEX-Parking-Konverter bewusst ignoriert
- Nur positive Kapazitäten gelten als bekannte Anzahl; `0` bleibt unbekannt.

Import:
- Positive DATEX-Fahrzeugkapazitäten werden beim Erstellen eines Camperwolf-Platzes als initiale `place_vehicle_types`-Zuordnung inklusive `capacity` angelegt.
- Eine explizite DATEX-Gesamtzahl hat Vorrang für `place_details.pitch_count`.
- Fehlt die explizite Gesamtzahl, werden die positiven gemappten Fahrzeugkapazitäten addiert und als abgeleitete Gesamtzahl gespeichert.
- Die Migration `2026_09_24_150000_add_vehicle_capacity_support.php` führt einen einmaligen Backfill für bereits aus externen Records erstellte Plätze durch; bestehende aktive Fahrzeugzuordnungen werden dabei nicht überschrieben.

Neue/erweiterte Fahrzeugtypen:
- `car-with-trailer` / PKW mit Anhänger
- `truck` / LKW
- `heavy-haulage` / Schwerlasttransport
- vorhandenes `coach` / Reisebus wird weiterverwendet.


### DATEX Schwerlasttransport 2026-09-24

`heavyHaulageVehicle` wird im DATEX-Parking-Konverter bewusst **nicht** mehr auf Camperwolf `heavy-haulage` gemappt. In realen Datensätzen traten offensichtlich unplausible Werte auf (z. B. Schwerlastkapazität deutlich größer als die Gesamtstellplatzanzahl), daher wird dieses DATEX-Feld für die Fahrzeugkapazitätsübernahme ignoriert.

Der Camperwolf-Fahrzeugtyp `heavy-haulage` / „Schwerlasttransport“ bleibt im allgemeinen Fahrzeugkatalog bestehen und kann weiterhin manuell oder durch andere, eindeutigere Quellen verwendet werden.

Migration `2026_09_24_152000_remove_datex_heavy_haulage_capacities.php` entfernt ausschließlich bereits durch den externen Import/Backfill erzeugte Schwerlast-Zuordnungen anhand ihrer Import-Kommentare; manuell gepflegte Zuordnungen bleiben erhalten.


### Import-End-to-End-Testreset 2026-09-24

Für einen vollständigen lokalen Neuimport existiert der Artisan-Befehl:

`php artisan imports:test-reset`

Mit `--force` kann die Sicherheitsabfrage übersprungen werden.

Der Reset ist ausschließlich in `local`/`testing` erlaubt und:
- entfernt den markierten Performance-Datensatz über `PerformanceDataService`;
- löscht ausschließlich Camperwolf-Plätze, deren External Record als `classification=created` aus dem Import erzeugt wurde;
- löscht **keine** normalen Camperwolf-Plätze, die nur mit einem External Record verknüpft waren (`classification=linked`);
- entfernt External Records, Record Fields, Review Items, Import Runs und Raw Snapshot-Metadaten;
- löscht die zu den Raw Snapshots gespeicherten lokalen Importdateien;
- behält `external_sources` als Quellenkonfiguration bei;
- setzt bei den Quellen `last_checked_at` und `last_success_at` zurück.

Zweck: reproduzierbarer kompletter DATEX-/CSV-End-to-End-Test ab leerem Importzustand, ohne normale Camperwolf-Daten oder Quellenkonfigurationen zu verlieren.


### Import-Zentrale: Alle offenen Kandidaten übernehmen 2026-09-24

In der Kandidatenliste gibt es zusätzlich zur Seitenauswahl einen Button **„Alle offenen Kandidaten übernehmen“**.

Verhalten:
- übernimmt alle aktuell offenen `new_candidate`-Datensätze unabhängig von der Pagination;
- wenn ein Quellenfilter aktiv ist, gilt die Aktion nur für diese Quelle;
- die Textsuche wird bewusst nicht als Bulk-Grenze verwendet;
- Verarbeitung erfolgt browserseitig automatisch in 50er-Blöcken über wiederholte Requests;
- jeder Block nutzt die bestehende atomare Bulk-Übernahme;
- Fortschritt wird als `x / gesamt` angezeigt;
- nach Abschluss wird die Import-Zentrale neu geladen.

Ziel: große DATEX-Importe mit tausenden Kandidaten vollständig testen, ohne einen einzelnen langen HTTP-Request oder unnötig große Transaktionen.


### Externe Merkmale: Initiale Materialisierung 2026-09-24

Für externe Merkmale gilt nun:
- Der External Layer bleibt dauerhaft als Provenienz und Quellwahrheit erhalten.
- Eindeutige Statuswerte `available` / `unavailable` ohne Quellenkonflikt werden beim erstmaligen Erzeugen eines Camperwolf-Platzes zusätzlich als initialer aktiver `place_features`-Wert angelegt.
- Diese Initialwerte tragen den internen Hinweis `Initial aus externer Quelle übernommen.`.
- Existiert bereits ein aktiver Camperwolf-/Communitywert für das Merkmal, wird nichts materialisiert oder überschrieben.
- Konfliktbehaftete oder unbekannte Quellwerte werden nicht materialisiert.
- Spätere Community-/Adminbearbeitung versioniert den Camperwolf-Layer wie bisher; der External Layer bleibt unverändert daneben bestehen und kann weiterhin Konflikte sichtbar machen.
- Migration `2026_09_24_153000_materialize_created_external_features.php` zieht diese Initialwerte für bereits als `created` übernommene externe Plätze nach.

Zweck: Profil, Editor, Filter und Facettenzählung verwenden nach der initialen Übernahme denselben Camperwolf-Wert, während die externe Herkunft und spätere Quellenabweichungen weiterhin nachvollziehbar bleiben.


### DATEX-II Realimport / Web-Upload / aktueller Abschlussstand 2026-09-24

Der vorgezogene externe Importblock wurde erstmals vollständig mit echten Daten durchgespielt und für den aktuellen V1-Stand praktisch abgenommen.

Realer DATEX-II-Datensatz:
- lokale Testdatei: `D:\sampledata\ITP_StatischeDaten_bundesweit_20260225_1205.xml`;
- Dateigröße ca. 32,78 MB;
- Quelle/Feed: bundesweite statische ParkingTable-Daten;
- vollständiger Import: 2.612 gemappte externe Records;
- beim CLI-E2E-Lauf: 1 Fallback-Koordinate, 56 Datensätze ohne Koordinaten; unbekannte/irrelevante Equipment-Typen werden nur statistisch gemeldet;
- nach Klassifizierung wurden die eindeutigen `new_candidate`-Fälle vollständig übernommen; mögliche Dubletten beziehungsweise Review-Fälle bleiben bewusst offen;
- dadurch befinden sich erstmals mehr als 2.000 reale Plätze aus einer amtlichen Quelle im Camperwolf-Datenbestand.

Speicher-/Streaming-Härtung:
- `Datex2ParkingParser::parseFile()` verarbeitet XML über `XMLReader` recordweise statt die komplette Datei in den Speicher zu laden;
- Staging arbeitet zweipassig und in kleinen Chunks;
- Klassifizierung arbeitet per `chunkById()`;
- Uploadpfad vermeidet unnötige Candidate-Diagnostik und Flashing großer Requestdaten;
- vollständige Rohdatei kann damit speicherschonend verarbeitet werden.

Web-Upload-Fehler und Ursache:
- CLI-Verarbeitung der vollständigen XML war bereits erfolgreich, während der Browser zunächst mit HTTP 500 scheiterte;
- Ursache war nicht der Parser, sondern die separate PHP-Laufzeit von Laravel Herd/FastCGI;
- Web-SAPI: `cgi-fcgi`;
- Web-`php.ini`: `C:\Users\wulfi\.config\herd\bin\php84\php.ini`;
- FastCGI lief trotz bereits geänderter Datei zunächst noch mit `upload_max_filesize=2M`, `post_max_size=8M`, `memory_limit=128M`;
- nach echtem PHP-Neustart direkt über Herd wurden die vorgesehenen Werte `100M / 110M / 256M` aktiv;
- danach funktionierte der vollständige Web-Upload;
- die temporäre lokale Diagnose-Route `/__php-limits` wurde anschließend wieder entfernt.

Bulk-Verarbeitung:
- neben „Alle auf dieser Seite“ gibt es „Alle offenen Kandidaten übernehmen“;
- die Aktion verarbeitet alle aktuell offenen eindeutigen Kandidaten unabhängig von der Pagination;
- ein aktiver Quellenfilter wird berücksichtigt;
- der Browser ruft automatisch 50er-Batches auf und zeigt Fortschritt `x / gesamt`;
- jeder einzelne Batch nutzt weiterhin die atomare, serverseitig validierte Bulk-Logik;
- der reale DATEX-Bestand wurde damit erfolgreich vollständig für alle eindeutigen Fälle übernommen.

Externe Featurewerte / Materialisierung:
- beim ersten Realbestand wurde sichtbar, dass das Profil externe Feature-Fallbacks korrekt grün anzeigte, Browse-Filter und Editor jedoch nur den internen `place_features`-Layer auswerteten;
- endgültige Regel: External Layer bleibt dauerhaft als Herkunfts-/Quelllayer bestehen;
- eindeutige konfliktfreie externe Statuswerte `available` / `unavailable` werden beim erstmaligen Erzeugen des Camperwolf-Platzes zusätzlich als initiale aktive `place_features` materialisiert;
- bestehende aktive Camperwolf-/Communitywerte werden niemals überschrieben;
- `unknown` und quellenintern konfliktbehaftete Werte werden nicht materialisiert;
- interne Kennzeichnung: `Initial aus externer Quelle übernommen.`;
- Migration `2026_09_24_153000_materialize_created_external_features.php` backfillt bereits erstellte externe Plätze;
- Service: `ExternalFeatureMaterializationService`;
- Schutztests prüfen konfliktfreie Materialisierung und Nicht-Überschreiben vorhandener Camperwolf-Werte;
- dadurch verwenden Profil, Editor, Browse-Filter und Facettenzählung denselben initialen Camperwolf-Wert, während der ursprüngliche Quellwert separat erhalten bleibt.

Aktueller Quellenstatus:
- DZT/Open Data Germany API-/Knowledge-Graph-Zugang ist beantragt, aber noch nicht freigeschaltet;
- SID/Mobilithek-Zugang ist ebenfalls noch nicht freigegeben;
- weitere Adapter werden erst anhand echter freigeschalteter Schnittstellen umgesetzt, nicht gegen hypothetische Payloads.

Roadmap-Entscheidung:
- der externe Importblock gilt für den aktuellen V1-Stand als **vorläufig abgeschlossen/abgehakt**;
- der vorhandene DATEX-II-Bestand ist als reale Basis zunächst ausreichend;
- offene Review-Fälle bleiben bewusst zur manuellen Prüfung bestehen;
- DZT/SID werden nach externer Freigabe als Erweiterung wieder aufgenommen;
- bekannte spätere Hardening-Punkte (u. a. durable Ignore/Reopen, XSD-Validierung, Complete-Snapshot-Guard, Duplicate-Kalibrierung, Reverse/Unlink) sind keine Blocker für das Abhaken dieses Roadmap-Blocks;
- als nächster regulärer Roadmap-Block folgt wieder Deployment/Operations vor Go-live.


## 28. Fortschreibung – Deployment / Operations und Servervorbereitung vom 2026-09-25

Der externe Importblock ist für V1 vorläufig abgeschlossen. Der aktive letzte reguläre Roadmap-Block vor Go-live ist **Deployment / Operations**. Die Servervorbereitung und der erste produktive Laravel-Stand sind inzwischen weit fortgeschritten.

### Verbindliche Arbeitsweise für Serverarbeiten

Sascha möchte Serverarbeiten bevorzugt per SSH durchführen, ohne unnötig tiefe Linux-Erklärungen. Pro Schritt genügt kurz: was geprüft/geändert wird, warum und welche wesentliche Auswirkung das hat; danach ein konkreter kopierbarer SSH-Befehl. Änderungen werden einzeln durchgeführt und anschließend geprüft.

Vor potenziell gefährlichen, schwer rückgängig zu machenden oder live sichtbaren Befehlen muss ausdrücklich gewarnt werden. Plesk bleibt installiert und funktionsfähig; wenn ein Schritt in Plesk sicherer oder sinnvoller ist, soll dies ausdrücklich gesagt werden. Die Plesk-Oberfläche des Servers ist Englisch, daher bei UI-Anweisungen die englischen Menübezeichnungen verwenden.

Grundprinzip: **erst inventarisieren und sichern, dann verändern; eine Änderung nach der anderen und jeweils verifizieren.**

### Verbindliche Ziele und Leitplanken

1. Alle vorhandenen Websites müssen unverändert weiterlaufen.
2. Das bisherige WordPress von `camperwolf.de` wird vollständig nach `blog.camperwolf.de` verschoben, ohne Daten- oder Funktionsverlust.
3. Plesk bleibt vorerst bestehen.
4. SSH-Arbeiten dürfen Plesk und dessen verwaltete Websites nicht beschädigen oder unbeabsichtigt umgehen.
5. Laravel/Camperwolf läuft produktiv unter `camperwolf.de`.
6. Bestehendes WordPress bleibt nach dem Cutover zunächst als Rollback-Kopie erhalten.
7. Produktive Datenbanken dürfen nicht mit `migrate:fresh` oder ähnlichen Reset-Verfahren zurückgesetzt werden; produktiv ausschließlich Forward-Migrationen.
8. Entwicklung erfolgt lokal/GitHub, nicht direkt auf dem Produktionsserver.

### Bestätigter Produktionsserver

- Host: `elated-bose.85-215-77-212.plesk.page`
- Ubuntu 22.04.5 LTS
- Virtualisierung: Microsoft/Hyper-V
- 8 vCPU, AMD EPYC-Milan, 31 GiB RAM
- `/dev/vda1`: ca. 969 GiB, bei Inventur nur ca. 19 GiB belegt
- kein Swap; späterer Operations-Prüfpunkt, aktuell kein akuter Handlungsbedarf
- sehr geringe Systemlast
- Plesk Obsidian 18.0.80.8
- MariaDB 10.6.23
- Apache 2.4.52
- nginx 1.30.4
- Git 2.34.1
- Plesk PHP 8.1/8.2/8.3; zusätzlich für Camperwolf PHP 8.4.25 installiert
- Plesk Node.js Toolkit; Node 22.23.2 unter `/opt/plesk/node/22/bin/node`, npm 10.9.8
- Plesk Composer Extension vorhanden
- kein systemweites PHP/Node/npm erforderlich; für Deployment werden die Plesk-Pfade verwendet

### SSH-Zugang / Absicherung

Der root-Zugang per PuTTY-Key ist wiederhergestellt. Für die Einrichtung wurde Passwort-Login nur temporär aktiviert und danach wieder entfernt. Effektiver Zielzustand:
- `PermitRootLogin without-password`
- `PasswordAuthentication no`
- root-Zugang per Public Key funktioniert
- neuer PuTTY-Key wurde erfolgreich getestet
- VNC bleibt als Notfallzugang verfügbar

Die temporäre SSH-Konfigurationsdatei wurde entfernt und SSH anschließend neu geladen. Das temporär gesetzte root-Passwort sollte/ist als separater Kontrollpunkt gesperrt; bei späterer Security-Abnahme den Status nochmals explizit verifizieren.

### Bestehende Plesk-Websites

`camperwolf.de`, `ferienhaus-dolp.de` und `slg-bbs-essen.de` sind getrennte Plesk-Subscriptions auf `85.215.77.212`.

`ferienhaus-dolp.de`:
- WordPress unter `/var/www/vhosts/ferienhaus-dolp.de/httpdocs`
- PHP 8.2
- DB `wp_fd`
- muss unverändert bleiben.

`slg-bbs-essen.de`:
- Root `/var/www/vhosts/slg-bbs-essen.de/httpdocs`
- PHP 8.2
- DB `slgbbs_`
- Mail aktiv, ein Postfach und eine Weiterleitung
- Website und insbesondere Mail dürfen nicht beeinträchtigt werden.

`camperwolf.de`:
- ursprüngliches WordPress lag unter `/var/www/vhosts/camperwolf.de/httpdocs`
- alte WP-DB `camperwolf`
- WordPress bleibt dort derzeit als Rollback-Kopie erhalten
- produktiver Plesk Document Root zeigt inzwischen auf `laravel/current/public`.

### Backup vor Änderungen

Block 0.5 ist abgeschlossen:
- Plesk hatte bereits erfolgreiche automatische tägliche Backups.
- Zusätzlich wurde vor den Deploymentarbeiten ein manuelles vollständiges Server-/Plesk-Backup erstellt.
- Websites, Datenbanken, Mail/User-Dateien und Plesk-Konfiguration sind damit über Plesk gesichert.
- Aktuelle Plesk-Backups liegen jedoch auf demselben Server; **Offsite-Backup bleibt offen** und gehört zu Block 7.
- Der Restore-Ablauf wurde am 2026-09-29 praktisch verifiziert: Eine eigens angelegte Testdatei wurde in einem manuellen Full Backup gesichert, anschließend gelöscht und über Plesk gezielt wiederhergestellt; Inhalt wurde per SSH erfolgreich geprüft. Offsite-Backup bleibt separat offen.

### WordPress-Umzug zu blog.camperwolf.de

Blöcke 0.6 und 0.7 sind abgeschlossen.

- Subdomain `blog.camperwolf.de` angelegt.
- Root: `/var/www/vhosts/camperwolf.de/blog.camperwolf.de`
- separate DB `camperwolf_blog`, User `cw_blog`
- Dateien vollständig kopiert.
- DB vollständig kopiert.
- geklonte `wp-config.php` auf neue DB umgestellt.
- WordPress `home` und `siteurl` auf `https://blog.camperwolf.de` geändert.
- serialisierungssicheres WP-CLI Search/Replace durchgeführt; GUIDs bewusst ausgelassen.
- eigenes Let's-Encrypt-Zertifikat installiert.
- störende Plesk-`index.html` der neuen Subdomain entfernt.
- Browserprüfung erfolgreich: Startseite, Inhalte, Login und Funktionen laufen.
- altes WordPress unter `httpdocs` wurde nicht gelöscht und dient weiterhin als schneller Rollback-Bestand.

### Laravel-Produktionsbasis

Block 0.8 sowie wesentliche Teile der Blöcke 1–3 und 6 sind umgesetzt.

Produktionspfad:
`/var/www/vhosts/camperwolf.de/laravel/current`

Eigentümer:
`camperwolf.de_qpfpse9uwk:psacln`

Git:
- privates Repository `WulfieWolf/camperwolf`
- eigener read-only GitHub Deploy Key für den Plesk-Webuser
- Key-Datei `/var/www/vhosts/camperwolf.de/.ssh/github_deploy`
- Deploy-Key hat **keinen** Write-Zugriff
- Git-Operationen auf Produktion immer als Plesk-Webuser, nicht als root.

PHP/Composer:
- Camperwolf läuft mit Plesk PHP 8.4.25.
- Composer-App ID 2 ist der korrekte Plesk-Eintrag für `laravel/current/composer.json`.
- ein alter fehlerhafter Composer-Registry-Eintrag ID 1 existiert noch und kann später gefahrlos über Plesk bereinigt werden.
- Production-`composer install` lief erfolgreich.
- Telescope-Service-Provider wurde so angepasst, dass ein `--no-dev`-Produktionsinstall ohne Telescope funktioniert.

Node/Assets:
- `npm ci` und `npm run build` funktionieren mit Plesk Node 22 unter `/opt/plesk/node/22/bin`.
- Für den Plesk-Webuser ist `npm` nicht automatisch im PATH; deshalb bei manuellen Deployments explizit `env PATH=/opt/plesk/node/22/bin:$PATH /opt/plesk/node/22/bin/npm ...` verwenden.
- bekannte `fontaine`-Warnung beim Build ist optional/nicht fatal.
- `public/build` ist bewusst per `.gitignore` ausgeschlossen und wird durch `git pull` **nicht** aktualisiert. Frontend-/Tailwind-/Vite-Änderungen benötigen daher auf Produktion einen separaten Asset-Build.
- Am 2026-09-29 wurde ein veralteter Build vom 2026-09-25 als Ursache für den fehlenden Beta-Button gefunden; nach `npm ci` + `npm run build` war die Produktionsoberfläche wieder korrekt.

Storage:
- `storage` und `bootstrap/cache` gehören dem Plesk-Webuser.
- `artisan storage:link` ist eingerichtet.
- Profilbild-Upload wurde produktiv erfolgreich getestet.

### Produktionsdatenbank / .env

Produktive Laravel-DB:
- DB `camperwolf_app`
- DB-User `cw_app`
- nur lokale DB-Verbindung und nur Zugriff auf diese DB
- Passwörter/Secrets bleiben ausschließlich in der produktiven `.env` und werden nicht dokumentiert oder in Git gespeichert.

Wichtige Produktionswerte:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://camperwolf.de`
- Locale/Fallback Deutsch
- `SESSION_DRIVER=database`
- `QUEUE_CONNECTION=database`
- `CACHE_STORE=database`
- `TELESCOPE_ENABLED=false`
- `MAIL_MAILER=smtp`; produktiver Transaktionsmailversand läuft über Brevo. Die Camperwolf-Mail-Sicherheitsgrenzen und der zentrale Circuit Breaker sind aktiv.

Die Produktionsdatenbank wurde am 2026-09-29 bewusst ein letztes Mal als saubere Beta-Basis neu aufgebaut. Vorher wurde ein vollständiges Backup erstellt und der Restore-Ablauf praktisch verifiziert. Ab diesem Beta-Basisstand werden Nutzer-/Communitydaten als reale Produktionsdaten behandelt und grundsätzlich erhalten. Künftige Resets sind nur noch vorgesehen, wenn tiefgreifende Änderungen am Datenmodell sie ausdrücklich erforderlich machen; normale Weiterentwicklung erfolgt vorwärts über Migrationen und gezielte Datenmigrationen.

### Live-Cutover von camperwolf.de

Der Laravel-Cutover ist abgeschlossen:
- Plesk Document Root von `httpdocs` auf `laravel/current/public` geändert.
- SSL und HTTP→HTTPS bleiben aktiv.
- `https://camperwolf.de` liefert Laravel erfolgreich per PHP 8.4.25 aus.
- `https://camperwolf.de/up` liefert HTTP 200.
- Security-Header einschließlich HSTS sind aktiv.
- Browsertests erfolgreich: Registrierung/Login, Navigation, Einstellungen und Profilbild-Upload.
- altes WordPress bleibt als Rollback unter `httpdocs`.
- Notfall-Rollback für den Webroot: in Plesk bei `camperwolf.de` unter **Hosting Settings** den Document Root wieder auf `httpdocs` setzen.

### Regulärer Production-Deploymentablauf

Der Produktionsdeploy wurde mehrfach praktisch durchgeführt. Für künftige Updates gilt als Standard:

1. Im Projektverzeichnis `/var/www/vhosts/camperwolf.de/laravel/current` arbeiten.
2. Git-/Composer-/Artisan-Befehle grundsätzlich als Plesk-Webuser `camperwolf.de_qpfpse9uwk` ausführen, nicht als root.
3. Vor dem Pull `sudo -u camperwolf.de_qpfpse9uwk git status --short` prüfen. Das Repo soll sauber sein.
4. `sudo -u camperwolf.de_qpfpse9uwk git pull --ff-only` ausführen; `--ff-only` verhindert unbeabsichtigte Server-Merges.
5. Falls Composer-Dateien geändert wurden: `sudo -u camperwolf.de_qpfpse9uwk /opt/plesk/php/8.4/bin/php /opt/psa/var/modules/composer/composer.phar install --no-dev --optimize-autoloader`.
6. Bei DB-Änderungen: `sudo -u camperwolf.de_qpfpse9uwk /opt/plesk/php/8.4/bin/php artisan migrate --force`.
7. Bei Frontend-/Tailwind-/Vite-Änderungen: `sudo -u camperwolf.de_qpfpse9uwk env PATH=/opt/plesk/node/22/bin:$PATH /opt/plesk/node/22/bin/npm ci` und anschließend analog `npm run build`. `public/build` kommt nicht aus Git.
8. Caches gezielt neu aufbauen: mindestens `artisan optimize:clear`, danach je nach Release `config:cache`, `route:cache`, `view:cache`; Befehle immer als Webuser.
9. Wenn Queue-/Jobcode geändert wurde: `artisan queue:restart`; systemd startet den Worker automatisch neu. Service: `camperwolf-queue.service`.
10. `artisan schedule:list`, `systemctl is-active camperwolf-queue.service`, `/up` und mindestens einen Browser-Smoke-Test prüfen.
11. Bei größeren Releases vorher Full Backup und optional Maintenance Mode/Lockdown verwenden; danach erst wieder freigeben.

Wichtige Produktionspfade:
- PHP: `/opt/plesk/php/8.4/bin/php`
- Composer-PHAR: `/opt/psa/var/modules/composer/composer.phar`
- Node/npm: `/opt/plesk/node/22/bin/node` und `/opt/plesk/node/22/bin/npm`
- App: `/var/www/vhosts/camperwolf.de/laravel/current`

Der frühere Hinweis, dass ein Asset-Build über einen normalen `git pull` mitkommt, ist falsch: `public/build` ist ignoriert. Der Build ist ein eigener Deploymentschritt, sobald sich Frontend/CSS/JS geändert hat.

### Website-Zugriffsmodi / Notfall-Lockdown

Produktiv implementiert und getestet:
- **Normalbetrieb**: alles verfügbar.
- **Registrierung geschlossen**: öffentliche Website und bestehende Logins funktionieren; neue Registrierung wird serverseitig blockiert und eine frei definierbare Meldung angezeigt.
- **Lockdown**: Gäste und normale Nutzer sehen eine Wartungs-/Lockdown-Seite; Login bleibt erreichbar; Admin/System Owner können sich anmelden und das System normal verwenden.
- Ein normaler eingeloggter Nutzer sieht im Lockdown einen Hinweis, dass Konto/Daten nicht betroffen sind, sowie eine Abmeldefunktion.
- Lockdown-Nachrichten erhalten eingegebene Leerzeilen/Zeilenumbrüche; vollständige HTTP/HTTPS-URLs werden sicher automatisch klickbar gemacht.
- Einstellung liegt unter **Admin → System & Debug → Website Access**.
- Speicherung erfolgt DB-basiert in `site_settings`, damit der Modus sofort wirkt und mit Config-Caching kompatibel bleibt.
- CLI-Notfallweg:
  `sudo -u camperwolf.de_qpfpse9uwk /opt/plesk/php/8.4/bin/php artisan camperwolf:mode normal`
- Dadurch kann ein versehentlich aktivierter Lockdown auch per SSH aufgehoben werden.
- `artisan down` wird hierfür bewusst nicht verwendet.
- Featuretest `SiteAccessModeTest` lief lokal erfolgreich.
- Produktiv wurde „Registrierung geschlossen“ erfolgreich aktiviert und im Inkognito-Browser geprüft.
- Lockdown selbst wurde lokal vollständig getestet; ein unnötiger öffentlicher Live-Lockdown wurde vermieden.

### Aktueller Deployment-/Operations-Fahrplan

#### Block 0 – bestehenden Server sicher vorbereiten
- **0.1 Hardware/Ressourcen:** abgeschlossen.
- **0.2 Auslastung:** abgeschlossen.
- **0.3 Softwareinventur:** abgeschlossen.
- **0.4 Websites/Plesk-Abhängigkeiten:** abgeschlossen.
- **0.5 vollständiges Pre-Change-Backup:** abgeschlossen.
- **0.6 WordPress nach blog.camperwolf.de:** abgeschlossen.
- **0.7 WordPress-Umzug prüfen:** abgeschlossen.
- **0.8 Laravel-Produktionsbasis:** abgeschlossen.

#### Block 1 – Produktionsserver / Deploymentverfahren
**Weitgehend umgesetzt.**
- Produktionspfad, Webuser, GitHub Deploy Key, Repository, Composer und Asset-Build stehen.
- erstes reales Release erfolgreich durchgeführt.
- offen: finalen Standard-Deploy-/Rollback-Ablauf dokumentieren bzw. optional als Script automatisieren.

#### Block 2 – Produktions-.env
**Im Kern abgeschlossen.**
- Production/Debug/HTTPS-URL/DB/Session/Cache/Queue/Logging/Owner/Telescope und realer SMTP-Mailweg sind gesetzt und produktiv geprüft.
- `APP_URL=https://camperwolf.de` ist verbindlich; die frühere HTTP-Fehlkonfiguration wurde beim Live-Test der signierten Verifizierungslinks gefunden und korrigiert.
- optional später fachlich entscheiden, ob die Laravel-Zeitzone von UTC auf `Europe/Berlin` geändert werden soll.

#### Block 3 – Domain, HTTPS und Webserver
**Im Kern abgeschlossen.**
- `camperwolf.de` zeigt auf Laravel `public`.
- HTTPS, Redirect und Zertifikat funktionieren.
- Apache/nginx/PHP-FPM funktionieren in Plesk.
- Security-Header/HSTS aktiv.
- später nur noch Security-Endkontrolle bzw. Trusted-Proxy/Cloudflare, falls tatsächlich eingesetzt.

#### Block 4 – Mail
**Abgeschlossen und produktiv Ende-zu-Ende geprüft.**
- Brevo ist als Transaktionsmail-Provider eingerichtet; Absender `noreply@camperwolf.de`.
- SPF/DKIM/DMARC sowie Branding-Subdomain `em.camperwolf.de` sind eingerichtet und bestanden im Header-Test.
- Brevo-SMTP ist auf die Produktions-IP beschränkt; Secrets liegen nur in der Server-`.env`.
- zentrale Camperwolf-Mail-Sicherheit mit Circuit Breaker, globalen/Empfänger-/Typ-Limits, Tageslimit, Deduplizierung, Unique Queue Jobs, `tries=1` und Audit über `mail_deliveries` ist produktiv aktiv.
- Registrierung/E-Mail-Verifizierung, Passwort-Reset und E-Mail-Änderung wurden mit echten Mails und Links produktiv erfolgreich Ende-zu-Ende getestet.
- die einmaligen UX-Modals vor/nach Verifizierung und E-Mail-Änderung wurden ebenfalls produktiv bestätigt.
- `slg-bbs-essen.de`-Mail wurde nicht verändert.
- spätere Mailtypen müssen vor Aktivierung weiterhin auf Eskalations-/Loop-Risiko geprüft werden.

#### Block 5 – Queue und Scheduler
**Abgeschlossen und produktiv Ende-zu-Ende geprüft.**
- systemd-Service `camperwolf-queue.service` läuft dauerhaft als Plesk-Webuser.
- Queue-Reihenfolge: `mail,photos,default`; Worker mit automatischem Restart, `--tries=1` und Timeout 120 s.
- Laravel Scheduler läuft minütlich per Crontab des Plesk-Webusers.
- geplante Tasks wurden mit `schedule:list` geprüft.
- Queue-E2E-Test erfolgreich; keine fehlgeschlagenen Jobs.
- nach Deployments mit geändertem Job-/Servicecode Worker neu starten beziehungsweise `queue:restart` berücksichtigen.

#### Block 6 – Storage und Uploads
**Abgeschlossen für Beta-Basis.**
- `storage`, `bootstrap/cache` und Unterverzeichnisse gehören dem Plesk-Webuser und sind schreibbar.
- `public/storage` zeigt korrekt auf `storage/app/public`.
- Schreibtest als Webuser in `storage/framework/testing` und `bootstrap/cache` war erfolgreich.
- realer Profilbild-Upload funktioniert produktiv.
- weitere Pfade werden bei konkretem Import-/Exportbetrieb weiter beobachtet; kein aktueller Blocker.

#### Block 7 – Backup und Restore
**Lokales Plesk-Backup/Restore abgeschlossen; Offsite bleibt offen.**
- automatische Plesk-Backups sind vorhanden und laufen erfolgreich.
- am 2026-09-29 wurde zusätzlich ein manuelles Full Backup vor dem finalen Beta-Reset erstellt.
- ein Backupwarnhinweis wegen `laravel/current/.env.before-brevo` wurde behoben, indem die alte Secret-Kopie aus dem Web-/Backup-Baum nach `/root/.env.before-brevo-camperwolf` verschoben wurde; Repo danach sauber.
- echter Restore-Test erfolgreich: Testdatei gesichert, gelöscht, über `Files of domains` wiederhergestellt und Inhalt verifiziert.
- offen bleibt ein separates Offsite-Backup-/Katastrophenfall-Konzept.

#### Block 8 – Logging und Monitoring
**Grundkontrolle für Beta abgeschlossen; Ausbau später möglich.**
- Laravel-Log und systemd-Journal des Queue-Workers wurden produktiv geprüft.
- gefundener Fehler: Scheduler versuchte `telescope:prune`, obwohl Telescope in Production wegen `composer install --no-dev` nicht installiert ist.
- Fix auf `main`: Telescope-Pruning wird nur geplant, wenn `Laravel\\Telescope\\TelescopeServiceProvider` vorhanden ist (`b80290164d655fdc540b377c2af434c3dee6cf19`). Danach verschwand der Eintrag aus `schedule:list`.
- Queue-Restart wurde im Journal sauber bestätigt; keine neuen Queue-Fehler festgestellt.
- Pulse ist aktiv. Weitergehende Alerting-/Offsite-Monitoring-Lösung ist sinnvoll, aber kein aktueller Beta-Blocker.

#### Block 9 – Produktionssicherheit
**Camperwolf-spezifische Beta-Prüfung abgeschlossen; serverweite Härtung bewusst getrennt.**
- `APP_DEBUG=false`, HTTPS/HSTS, SSH-Key-Login, Dateirechte, Secret-Handhabung und die anwendungsbezogenen Schutzmechanismen wurden produktiv geprüft.
- der Camperwolf-spezifische Security-/Abuse-Pass ist für die Beta abgeschlossen.
- Firewall/Ports/Dienste, root-Login-Policy, Swap und gegebenenfalls Trusted Proxy/Cloudflare bleiben als **serverweite** Operations-Themen getrennt, weil auf demselben Plesk-Server weitere Projekte laufen. Keine pauschalen Serveränderungen nur für Camperwolf.
- Offsite-Backup bleibt ebenfalls ein späterer Operationspunkt und ist kein aktueller Beta-Blocker.

#### Block 10 – Deployment-Rehearsal / Beta-Abnahme
**Technische Produktionsbasis abgeschlossen; neuer Entwicklungsstack muss noch kontrolliert gemergt und deployt werden.**
- Git-Pull, Composer, Migrationen, Cache, Queue, Scheduler, Storage, Asset-Build, Mail, Backup/Restore, Logs und Browser-Smoke-Tests wurden auf Production praktisch durchgeführt.
- am 2026-09-29 wurde die Produktionsdatenbank als frische Beta-Basis neu aufgebaut und der Owner anschließend über den echten Registrierungs-/Verifizierungsweg neu angelegt.
- Niedersachsen wurde danach produktiv importiert und per Wiederholungslauf auf Idempotenz geprüft.
- Lockdown bleibt bewusst aktiv, bis der aktuell lokal abgenommene Branch-Stack vollständig nach `main` gemergt, auf Production deployt und dort mit NRW/Bayern sowie einem kurzen Smoke-Test geprüft wurde.
- ein erneutes `migrate:fresh` ist ab diesem Stand **kein** normaler Deploymentweg; Produktionsdaten gelten als erhaltenswert.

### Aktueller Einstiegspunkt

Production läuft weiterhin auf dem zuletzt freigegebenen `main` nach PR #13. Die Produktionsbasis inklusive Queue, Scheduler, Mail, Storage, Backup/Restore, Logging, Security-Basis und Niedersachsen-Import ist geprüft.

Der **nächste aktive Schritt** ist jetzt:
1. lokale Regressionstests auf `external-duplicate-groups`;
2. geordneter Merge des gestapelten Branch-Stacks nach `main`;
3. Production-Deployment mit Migrationen und frischem Frontend-Build;
4. produktiver NRW- und Bayern-Sync samt Dublettenprüfung und Idempotenz;
5. kurzer finaler Smoke-Test;
6. erst danach Lockdown deaktivieren und Beta-Kommunikation veröffentlichen.

Keine weitere Feature-Entwicklung und keine Suche nach weiteren Datenquellen vor diesem Merge-/Deployment-Block, außer ein Regressionstest zeigt einen echten Fehler.



## 29. Fortschreibung – Produktionsmail, Queue/Scheduler und Verifizierungs-UX vom 2026-09-27

Dieser Abschnitt ersetzt ältere Aussagen, nach denen echter Mailversand sowie Queue/Scheduler noch offen seien.

### Produktionsmail / Brevo

- Brevo Free wird ausschließlich für transaktionale Camperwolf-Mails verwendet; aktuell erwartetes Volumen ist niedrig.
- Sender: `Camperwolf.de <noreply@camperwolf.de>`.
- SMTP: `smtp-relay.brevo.com:587`; Zugangsdaten/Key bleiben ausschließlich in der produktiven `.env` und werden nicht dokumentiert.
- Brevo erlaubt den SMTP/API-Zugriff nur von der Produktions-IPv4 `85.215.77.212`.
- DNS/Auth eingerichtet und von Brevo bestätigt: DKIM, SPF/Provider-Authentifizierung, DMARC `p=none`, Branding über `em.camperwolf.de` inklusive Return-Path/Tracking-Subdomains.
- Ein realer Header-Test an GMX bestätigte DKIM/SPF/DMARC und Reverse-IP-Prüfung als bestanden. Eine erste künstliche Testmail landete dennoch im Spam; daraus wurden bewusst keine unnötigen DNS-Änderungen abgeleitet.
- `slg-bbs-essen.de` und dessen bestehende Mailfunktion wurden nicht verändert.

### Verbindliche Mail-Sicherheitsarchitektur

Camperwolf soll Mailfehler oder Programmschleifen so weit wie praktisch möglich daran hindern, unkontrolliert große Mailmengen zu erzeugen. Eine absolute 100-%-Garantie ist technisch nicht möglich; deshalb gelten mehrere voneinander unabhängige Schutzschichten:

- globaler Application-Circuit-Breaker `CAMPERWOLF_MAIL_ENABLED`;
- konservative Produktionslimits: 20/min global, 100/h global, 50/Tag global, 10/h je Empfänger; Verifizierung und Passwort-Reset jeweils 5/h je Typ;
- zentrale Versandstelle `SafeMailService` statt verteilter direkter Mailaufrufe;
- Duplicate-Suppression und atomare Cache-Locks;
- `ShouldBeUnique` für Account-Mailjobs;
- Queue-Jobs mit `tries=1`;
- Audit-Tabelle `mail_deliveries` mit Status sent/failed/blocked;
- keine unkontrollierten Mailaufrufe in Schleifen oder Events;
- neue Mailtypen dürfen erst produktiv aktiviert werden, nachdem explizit geprüft wurde, ob sie durch Schleifen, Retries oder Events eskalieren könnten;
- Providerlimit ist nur die letzte äußere Schutzschicht, nicht der primäre Schutzmechanismus.

Produktiv ist `CAMPERWOLF_MAIL_ENABLED=true`; die Sicherheitslimits sind aktiv. Der produktive Atomic-Lock wurde praktisch getestet.

### Account-Mails und UX

Produktiv Ende-zu-Ende erfolgreich getestet:
- Registrierung erzeugt die Verifizierungsmail;
- signierter HTTPS-Verifizierungslink funktioniert;
- Passwort-vergessen/Reset-Mail, Resetformular und Passwortänderung funktionieren;
- Änderung der E-Mail-Adresse löst erneute Verifizierung aus;
- Registrierung und E-Mail-Änderung zeigen nach Auslösen jeweils ein einmaliges Hinweis-Modal;
- nach erfolgreicher Bestätigung erscheint jeweils ein einmaliges Erfolgsmodal;
- nach Schließen/Reload erscheinen diese Modals nicht erneut.

Beim ersten Live-Verifizierungstest wurde eine alte Produktionsfehlkonfiguration gefunden: `APP_URL=http://camperwolf.de` erzeugte HTTP-signierte Links und dadurch `403 Invalid signature` nach HTTPS-Aufruf. `APP_URL` wurde auf `https://camperwolf.de` korrigiert, Config-Cache neu aufgebaut und ein frischer Link erfolgreich getestet. Alte bereits signierte HTTP-Links bleiben erwartungsgemäß ungültig.

Mail-Grundlage wurde über PR #8 (`6fdd50f4794875f000f221187918b7d067c8f588`) integriert. Die Verifizierungs-UX folgte über PR #9 (`b3efc32623782381d846839bac43be03c5ce724e`).

### Queue und Scheduler

- systemd-Service: `/etc/systemd/system/camperwolf-queue.service`;
- User/Group: `camperwolf.de_qpfpse9uwk:psacln`;
- Working Directory: `/var/www/vhosts/camperwolf.de/laravel/current`;
- Worker: Plesk PHP 8.4, `queue:work database --queue=mail,photos,default --sleep=3 --tries=1 --timeout=120`;
- Service ist enabled und aktiv; automatischer Restart nach Fehlern ist eingerichtet;
- Scheduler läuft minütlich im Crontab des Plesk-Webusers über `artisan schedule:run`;
- `schedule:list` und ein echter temporärer Queue-E2E-Job wurden erfolgreich geprüft;
- `queue:failed` zeigte keine fehlgeschlagenen Jobs.

### Produktionsrechte / Cache

Bei früheren root-ausgeführten Artisan-Cachebefehlen entstanden root-eigene Dateien in `bootstrap/cache`. Dies wurde korrigiert. `config.php`, `packages.php` und `services.php` gehören dem Plesk-Webuser und besitzen Modus 644; das Verzeichnis besitzt Modus 755. Produktions-Artisan-Befehle grundsätzlich als Plesk-Webuser ausführen, z. B. mit `sudo -u camperwolf.de_qpfpse9uwk /opt/plesk/php/8.4/bin/php artisan ...`.

### Kleine UI-Korrektur nach Mail-UX

Im Desktop-User-Menü erschien rechts neben dem Namen ein `?`. Ursache war kein vorgesehenes Hilfeelement, sondern der generische Fallback der zentralen `x-tabler-icon`-Komponente: `chevrons-up-down` wurde verwendet, fehlte aber in `config/tabler-icons.php`. Das Icon wurde über PR #10 ergänzt und produktiv ausgerollt. Merge-Commit: `0e26121a99b33a4d6911044d7634408556f2413b`. Die Anzeige ist produktiv korrigiert.

### Bereinigter aktueller Operations-Plan

Bereits abgeschlossen: Serverinventur/-vorbereitung, WordPress-Umzug, Laravel-Cutover, Domain/HTTPS/Webserver-Basis, Produktions-.env im Kern, echter Mailversand, Account-Mail-UX, Queue und Scheduler.

**Nächster aktiver Block: Block 6 – Storage und Uploads Endkontrolle.** Zunächst rein lesend prüfen: Storage-Struktur, `public/storage`-Symlink, Eigentümer/Rechte, öffentliche vs. private Fotoablage, Schreibbarkeit für Webuser/Worker sowie relevante Import-/Exportpfade. Danach reale Fotoverarbeitung/Löschen und Uploadlimits prüfen. Es sollen nur Befunde geändert werden, die tatsächlich falsch oder unsicher sind.

Danach in dieser Reihenfolge:
1. Block 7 – Backup/Restore einschließlich Offsite-Ziel und praktischem Restore-Test;
2. Block 8 – Logging/Monitoring einschließlich Queue-/Scheduler-/Fehlerüberwachung;
3. Block 9 – finale Produktionssicherheitsprüfung;
4. Optik / Produktidentität & Framework-Neutralisierung vor finaler Go-live-Abnahme: sichtbare Laravel-Logos/Branding, Favicon, Auth-/Reset-Optik, Standardfehlerseiten/-texte und unnötig offensichtliche Framework-Hinweise durch Camperwolf-spezifische Darstellung ersetzen; dies ist Produktidentität, kein Sicherheitsersatz;
5. Block 10 – finales Deployment-Rehearsal und Go-live-Abnahme einschließlich erneuter Prüfung aller bestehenden Websites.

Spätere, nicht blockierende Mailthemen: genaue Reply-To-/Support-Absenderstrategie, Security-Mail bei Passwortänderung fachlich entscheiden, V1-Admin-Einzelmailer mit denselben Sicherheitsprinzipien, Newsletter nur als striktes Opt-in, Support-/Inbound-Mailintegration später V2/V3.


## 30. Fortschreibung – Frische Beta-Produktionsbasis und Deployment-Runbook vom 2026-09-29

Dieser Abschnitt ersetzt ältere Aussagen, nach denen Storage, Restore-Test, Monitoring-Baseline oder der finale Beta-Neuaufbau noch ausstünden.

### Beta-Polish und aktueller Main-Stand

- Der isolierte Branch `beta-polish-2026-09-28` wurde nach lokaler Abnahme vollständig in `main` fast-forwarded.
- Enthalten sind unter anderem gruppierte Moderations- und Foto-Benachrichtigungen, Level-up-Benachrichtigungen, mobile Filterverbesserungen, hervorgehobene aktive Merkmalskategorien sowie die FKK/Naturist-Merkmale.
- Die neuen FKK-Merkmale:
  - `FKK` / `Naturist`: Unbekannt / Ja / Zu bestimmten Zeiten / Nein.
  - `FKK-Bereich` / `Naturist area`: Unbekannt / Ja / Nein.
  - standardmäßig sichtbar für Campingplatz, Wohnmobil-Stellplatz, Zeltplatz und freier Stellplatz; erweitert für Parkplatz und Camping/Outdoor; verborgen für Rastplatz und Service-Station.
- Relevante nachfolgende Produktionsfixes:
  - `470b6f0f0cbb6ca79bc534c4d8967c4b3f009ff8`: Composer-Lock-Metadaten synchronisiert, ohne Paketänderungen.
  - `b80290164d655fdc540b377c2af434c3dee6cf19`: Telescope-Pruning nur planen, wenn Telescope tatsächlich installiert ist.

### Frischer Beta-Neuaufbau der Produktionsdatenbank

Am 2026-09-29 wurde bewusst entschieden, die bisher durch Entwicklungstests gefüllte Produktionsdatenbank vor der öffentlichen Beta **einmal vollständig neu aufzubauen**. Ziel ist eine saubere, endgültige Beta-Ausgangsbasis statt selektivem Entfernen von Testdaten.

Vorbereitung:
1. vollständiges Plesk-Full-Backup erstellt;
2. Laravel in Maintenance Mode gesetzt;
3. `camperwolf-queue.service` gestoppt;
4. Scheduler-Cron des Webusers temporär auskommentiert;
5. `CAMPERWOLF_OWNER_EMAIL` in Production geprüft;
6. Produktions-`.env` und später ergänzte Niedersachsen-API-Werte kontrolliert.

Laravel blockiert destruktive DB-Befehle in Production bewusst über:
`DB::prohibitDestructiveCommands(app()->isProduction())`.

Für diesen ausdrücklich geplanten Einmal-Reset wurde deshalb:
1. der Config-Cache gelöscht;
2. nur für den einzelnen Artisan-Prozess `APP_ENV=local` gesetzt;
3. `migrate:fresh --seed --force` ausgeführt;
4. danach die Produktions-`.env` vervollständigt;
5. `config:cache` wieder unter echter Production-Konfiguration aufgebaut.

Wichtig: Dieser Weg ist **kein normaler Deploymentprozess**. Ab jetzt nur noch verwenden, wenn ein bewusst beschlossener struktureller Datenreset wirklich erforderlich ist.

### Owner nach dem Reset

- Der System Owner ist nicht durch eine spezielle Datenbankrolle definiert, sondern durch `CAMPERWOLF_OWNER_EMAIL`.
- `PermissionService::isOwner()` vergleicht die Benutzer-E-Mail mit dieser Konfiguration.
- Nach dem Reset wurde ein versehentlich per Tinker angelegter Owner-Datensatz wieder gelöscht.
- Der endgültige Owner wurde anschließend bewusst über den echten öffentlichen Registrierungsweg neu angelegt, E-Mail verifiziert, Anzeigename eingerichtet und Profilbild hochgeladen.
- Dadurch wurde gleichzeitig der reale Registrierungs-/Verifizierungs-/Profilpfad auf der frischen Beta-Datenbank geprüft.
- Danach wurde Lockdown aktiviert, damit vor der öffentlichen Freigabe kontrolliert echte Daten importiert und geprüft werden können.

### Frontend-Build: wichtiger Deployment-Befund

Nach dem Neuaufbau fehlte auf Production der orange Beta-Button links unten, obwohl:
- die Beta-Willkommensbenachrichtigung erzeugt wurde;
- der aktuelle Blade-Code inklusive `data-beta-notice-open` auf dem Server lag;
- der View-Cache neu aufgebaut worden war.

Ursache war ein veralteter Vite/Tailwind-Build: `public/build/manifest.json` stammte noch vom 2026-09-25. `public/build` ist per Git ignoriert und wird durch `git pull` nicht aktualisiert.

Produktionsbuild mit Plesk Node 22:
```bash
sudo -u camperwolf.de_qpfpse9uwk env PATH=/opt/plesk/node/22/bin:$PATH /opt/plesk/node/22/bin/npm ci
sudo -u camperwolf.de_qpfpse9uwk env PATH=/opt/plesk/node/22/bin:$PATH /opt/plesk/node/22/bin/npm run build
```

Der Build lief erfolgreich; die optionale `fontaine`-Warnung ist nicht fatal. Nach hartem Browser-Reload war der Beta-Button wieder korrekt sichtbar.

**Folgerung für alle künftigen Deployments:** Bei Änderungen an Blade-Klassen, Tailwind, CSS, JS, Vite oder sonstigen Frontend-Assets immer prüfen, ob ein neuer Production-Build nötig ist. Nicht davon ausgehen, dass `git pull` `public/build` aktualisiert.

### Backup-/Restore-Test

- Automatische Plesk-Backups wurden als erfolgreich vorhanden bestätigt.
- Ein manueller Backup-Lauf meldete zunächst:
  `laravel/current/.env.before-brevo: Cannot open: Permission denied`.
- Die alte Secret-Kopie wurde aus dem Repository-/Webbaum nach `/root/.env.before-brevo-camperwolf` verschoben; danach war `git status --short` sauber.
- Anschließend lief ein Full Backup ohne Warnung durch.
- Echte Restore-Prüfung:
  1. `storage/app/private/restore-test.txt` mit bekanntem Inhalt angelegt;
  2. Full Backup erstellt;
  3. Datei gelöscht;
  4. über Plesk `Files of domains` gezielt wiederhergestellt;
  5. Inhalt per `cat` erfolgreich verifiziert.
- Damit ist der lokale Plesk-Backup-/Restore-Weg praktisch bestätigt. Offsite-Backup bleibt ein eigener späterer Operationspunkt.

### Queue, Scheduler, Storage und Logs

- `camperwolf-queue.service` läuft als systemd-Service unter dem Plesk-Webuser, Queue-Reihenfolge `mail,photos,default`.
- `queue:restart` wurde produktiv ausgelöst und der saubere Stop/Restart im systemd-Journal bestätigt.
- Scheduler-Cron:
  ```
  * * * * * cd /var/www/vhosts/camperwolf.de/laravel/current && /opt/plesk/php/8.4/bin/php artisan schedule:run >> /dev/null 2>&1
  ```
- Storage-/Cache-Verzeichnisse sind für den Webuser schreibbar; `public/storage` ist korrekt verlinkt.
- Laravel-Log zeigte als einzigen aktuellen Schedulerfehler `telescope:prune` bei nicht installiertem Telescope. Der Code wurde so geändert, dass dieser Task nur bei vorhandenem Telescope-Provider registriert wird.
- Danach enthält `schedule:list` keinen Telescope-Task mehr; übrige Tasks bleiben aktiv.
- App-Zeitzone ist derzeit UTC; geplante Uhrzeiten wie Niedersachsen-Sync 03:10 beziehen sich daher aktuell auf UTC, solange dies nicht bewusst geändert wird.

### Produktive Niedersachsen-Konfiguration

- Produktions-`.env` enthält jetzt die benötigten Niedersachsen-Hub-Werte.
- `config/services.php` erwartet:
  - `NIEDERSACHSEN_HUB_API_KEY`
  - `NIEDERSACHSEN_HUB_EXPERIENCE` mit Default `open-data-niedersachsen-tourismus`
- Nach Ergänzung wurde `config:cache` neu erzeugt.
- Der Artisan-Befehl ist produktiv vorhanden:
  `imports:niedersachsen-sync [--no-classify] [--page-size=100]`.
- Standardlauf synchronisiert den Quellenlayer, klassifiziert neue Kandidaten/Dubletten und verarbeitet lizenzierte externe Fotos.
- Auf der frischen Beta-Datenbank produktiv ausgeführt und anschließend mit einem Wiederholungslauf auf Idempotenz geprüft. Details und aktuelle Kennzahlen stehen in Abschnitt 31/32.

### Unmittelbarer Plan ab diesem Stand

Dieser damalige Plan ist inzwischen weitgehend abgeschlossen und wird durch Abschnitt 32 ersetzt. Niedersachsen-Sync und Idempotenzcheck, Production-Smoke-Test, Queue/Scheduler/Logs/Security-Pass, frisches Full Backup sowie der Merge des final getesteten Admin-/Statistik-Blocks nach `main` sind erledigt. Lockdown bleibt bis zum bewussten öffentlichen Betastart aktiv. Ab Beta-Start weiterhin keine pauschalen Datenbank-Resets mehr; normale Releases über Vorwärtsmigrationen.



## 31. Fortschreibung - Beta-Datenbasis, Admin-UX und anonyme Nutzungsstatistik vom 2026-09-30

Dieser Abschnitt ersetzt insbesondere die Aussage aus Abschnitt 30, der erste produktive Niedersachsen-Sync sei noch nicht ausgeführt.

### Produktive Datenbasis vor der öffentlichen Beta

- Der erste produktive Niedersachsen-Hub-Sync wurde auf der frischen Beta-Datenbank ausgeführt.
- Der erste Versuch scheiterte an einem externen ISO-8601-Zeitstempel mit Offset, der unverändert in eine MySQL-DATETIME-Spalte geschrieben wurde. Der zentrale `ExternalRecordStagingService` normalisiert externe `source_updated_at`-Werte deshalb jetzt vor DB-Schreibvorgängen nach UTC im Format `Y-m-d H:i:s`. Regressionstest vorhanden; Fix über PR #11, Squash-Commit `89839f8384e7276e46bd2f939f4ef41c363e4245`.
- Danach lief der Niedersachsen-Sync erfolgreich: 396 eindeutige externe Datensätze, 396 neu, 0 Dubletten, 0 Review-Fälle.
- Alle 396 Niedersachsen-Kandidaten wurden kontrolliert als Camperwolf-Plätze übernommen.
- Nach der Verknüpfung wurden 949 lizenzierte externe Bilder erkannt. Der vorhandene Batch-/Fortschrittsworkflow wurde produktiv getestet; die Bilder wurden übernommen und die Foto-Queue anschließend vollständig abgearbeitet.
- DATEX-II-Autobahn-/Rastplatzdaten wurden ebenfalls erneut auf Production importiert. Der Browser-Upload benötigte domainspezifisch `upload_max_filesize=50M` und `post_max_size=60M`; diese Werte sind in Plesk für `camperwolf.de` gesetzt.
- Verbleibende DATEX-Reviewfälle, z. B. fehlende Koordinaten, werden manuell geprüft. Ein unvollständiger externer Datensatz kann derzeit durch manuelles Anlegen eines Camperwolf-Platzes und anschließendes Verknüpfen gelöst werden.
- V2-Idee: Im Import-Review fehlende Pflichtdaten direkt als Camperwolf-Override/Ergänzung erfassen und anschließend übernehmen, ohne den unveränderten externen Rohdatensatz zu überschreiben.
- Der Niedersachsen-Sync ist bereits täglich über den Laravel Scheduler geplant. Die automatische Synchronisation aktualisiert den externen Source-/Staging-Layer; die Übernahme neuer/unklarer Inhalte in den öffentlichen Camperwolf-Bestand bleibt vorerst bewusst administrativ kontrolliert.
- Vor Beta-Go-live bleibt ein Wiederholungslauf als Idempotenzcheck vorgesehen.

### Pre-Beta-Plan bis Freitag

Öffentliche Beta wurde für Freitag angekündigt. Status am 2026-09-30:
1. Niedersachsen-Wiederholungssync/Idempotenz - **erledigt**.
2. Adminbereich - **erledigt**; die frische Produktionsbasis enthält aktuell praktisch nur API-/Open-Data-Bestand, daher ist keine zusätzliche Moderations-Aufräumrunde nötig.
3. Gast-/User-/Admin-Smoke-Test - **erledigt und grün**.
4. Queue, Scheduler, Logs und Camperwolf-spezifischer Server-Security-Pass - **erledigt und grün**.
5. Frisches vollständiges Backup - **erledigt**; Backupgröße nach Bildimport ca. 928 MB, davon grob 410 MB Fotos.
6. Verbleibende Zeit für zusätzliche Datenquellen - **aktueller Arbeitsblock**; Entwicklung und Prüfung zunächst lokal, erst nach vollständiger Abnahme nach GitHub/Production.
7. Lockdown erst zur bewussten Beta-Freigabe aufheben - **weiterhin offen und absichtlich aktiv**.

### Admin-Landingpage - aktueller Arbeitsblock

Der Umbau wurde auf Branch `admin-statistics-usage-analytics` entwickelt, praktisch lokal und auf Production unter Lockdown geprüft und anschließend über PR #13 nach `main` gemergt. Production läuft wieder auf `main`; Merge-Commit `2435ae75f571a6c4bf2ed33a9ae6b6e566c9e908`.

Zielstruktur:
- **Administration:** Benutzer & Rechte, Duplikate zusammenführen, Bildverwaltung, Merkmalsverwaltung, Import-Zentrale, Systemnachricht, System & Debug.
- **Moderation:** Änderungsvorschläge, Fotomoderation, Review-Meldungen, Support & Tickets.
- **Information:** Aktionslog, Statistik.

Die bisherigen großen Kennzahlenkarten oben werden entfernt. Die Funktionskarten erhalten einheitliche, kontextbezogene Status-Bubbles:
- Änderungsvorschläge: offene Vorschläge und ggf. Quarantäne.
- Fotomoderation: offene Uploads/Meldungen.
- Bildverwaltung: Gesamtbestand.
- Import-Zentrale: getrennt neue Kandidaten und offene Prüffälle; damit werden auch neue Datensätze aus automatischen API-Syncs sichtbar.
- Support & Tickets: vorhandene Ticketstatus `new`, `open`, `in_progress`, `waiting_for_user`, `on_hold`, `resolved`, `closed`, jeweils nur bei Count > 0.
- Benutzer & Rechte: Beschreibung auf den tatsächlichen Funktionsumfang mit Profil-, Account-, Rollen- und Permissionverwaltung angepasst.
- Statistik ist über neue Permission `statistics.view` standardmäßig nur für Administratoren/Owner sichtbar, nicht für Moderatoren.

Bestehende Permissions bleiben für die übrigen Admin-/Moderationsbereiche zunächst erhalten; eine feinere Prüfung der einzelnen Adminbereiche folgt nach der gemeinsamen UX-Sichtprüfung.

### Statistikseite

Neue Admin-Statistikseite unter `/admin/statistics`, zunächst nur für Admin/Owner.

Sie nutzt für bestehende historische Daten ausschließlich bereits vorhandene Tabellen/Timestamps und zeigt u. a.:
- Plätze gesamt/veröffentlicht/inaktiv und nach Platztyp;
- Benutzer gesamt/aktiv/verifiziert sowie Rollen- und Accountstatus-Verteilung;
- aktive Bewertungen, Rezensionstexte und die fünf festen Einzelbewertungen je aktiver Bewertung;
- Fotos gesamt sowie Community-/externe Fotos;
- Merkmale, Merkmalskategorien und gesetzte Platzmerkmale;
- Favoriten, Änderungsvorschläge, Supporttickets;
- externe Quellen/Records/Verknüpfungen und Importläufe;
- Audit-/Merge-Daten;
- Statusverteilungen für Support, Änderungen und Fotos;
- Zeitreihen für neue Benutzer, Plätze, Bewertungen, Fotos, Änderungen, Support und Usage-Events;
- Zeitraumfilter 30 Tage / 12 Monate / Gesamt;
- Nutzungsstatistik nach Eventtyp, Funktionsbereich, Nutzerklasse und meistaufgerufenen einzelnen Platzprofilen.

Die Zeitdarstellung verwendet bewusst leichte interne Balkenvisualisierungen und keine zusätzliche externe Analytics-/Chart-Infrastruktur.

### Anonyme interne Nutzungsstatistik

Neue Tabelle `usage_events` und zentraler `UsageAnalyticsService`.

Gespeichert werden ausschließlich:
- `event_type`;
- `area` / Funktionsbereich bzw. Route;
- optional `content_type` und `content_id` für öffentlichen Inhalt, z. B. einen Platz;
- grobe `audience`: `guest`, `user`, `moderator`, `admin`;
- optionale unpersönliche Metadaten wie Anzahl oder Direct/Suggestion-Flag;
- `created_at` als Zeitstempel.

Nicht gespeichert werden:
- User-ID;
- IP-Adresse;
- Session-ID;
- dauerhafte Besucherkennung;
- Fingerprint.

Der Owner wird als `admin` gezählt. Analytics ist Best-Effort: ein Fehler beim Statistik-Insert darf die eigentliche Camperwolf-Funktion nicht blockieren.

Pageviews werden zentral nur für erfolgreiche 2xx-HTML-GETs gezählt, nicht für Assets, JSON-Feeds oder Redirects. Slug-basierte Platzseiten erhalten zusätzlich den konkreten Platzbezug.

Zum Beta-Start instrumentierte Funktionsereignisse umfassen insbesondere:
- Suche/Filterung;
- Start/Abschluss von Platzvorschlägen;
- Start/Abschluss von allgemeinen Änderungsvorschlägen;
- Favorit hinzufügen/entfernen;
- Bewertung absenden/entfernen/melden;
- Foto hochladen/entfernen;
- Öffnungszeiten-Bearbeitung öffnen und speichern/vorschlagen;
- Preisbearbeitung öffnen und speichern/vorschlagen;
- Merkmale speichern/vorschlagen;
- Supportformular öffnen und Ticket absenden.

Entwicklungsregel ab jetzt: Neue relevante Nutzerfunktionen sollen direkt passende anonyme Usage-Events erhalten, sofern dadurch die tatsächliche Nutzung oder UX sinnvoll messbar wird. Keine User-Journey-/personenbezogene Verfolgung einführen, solange dies nicht ausdrücklich neu entschieden wird.

Die Datenschutzerklärung wurde transparent ergänzt: keine Werbe-, Marketing- oder externen Analytics-Tracker; die interne anonyme Eventzählung und die ausdrücklich nicht gespeicherten Identifikatoren werden beschrieben. Privacy-Version auf 2026-09-30 gesetzt.

### Tests des Admin-/Analytics-Blocks

Für den neuen Block existiert ein fokussierter GitHub-Actions-Workflow `admin-statistics-tests` mit:
- PHP-Syntaxprüfung der geänderten Kernfiles;
- Blade-Compile über `php artisan view:cache`;
- Tests für anonyme Eventpersistenz ohne Identifikatoren;
- Audience-Klassifizierung;
- Admin-only-Statistikpermission;
- gruppierte Admin-Landingpage/Statistik-Sichtbarkeit;
- automatische Pageview-Erfassung;
- Locale-UI-Tests.

Die fokussierten Workflows `admin-statistics-tests`, `locale-tests` und `place-merge-tests` waren auf dem finalen PR-Head grün. Der generische Repository-`tests`-Workflow bleibt wegen des bekannten repositoryweiten Pint-Altbestands rot und stoppt vor PHPUnit; beim finalen Lauf wurden 428 Dateien mit 91 Style-Issues gemeldet. Dies wurde bewusst nicht als Funktionsfehler des Blocks gewertet.


### Adminbereich-Review - Detailbereiche 2026-09-30

Nach der neuen Admin-Landingpage wurden die einzelnen Adminbereiche geprüft. Folgende UX-Änderungen sind auf demselben Arbeitsbranch umgesetzt:

- **Bildverwaltung:** Vorschaubilder öffnen per Klick eine große Detailansicht im Modal/Lightbox-Stil des Platzprofils.
- **Fotomoderation:** Pending- und gemeldete Fotos sind ebenfalls in der großen Detailansicht prüfbar.
- **Plätze zusammenführen:** Die alten gemeinsamen Dropdowns wurden durch zwei getrennte Such-/Auswahlspalten ersetzt. Links wird der Hauptplatz gewählt, der bestehen bleibt; rechts das Duplikat, das nach Merge deaktiviert wird. Suche unterstützt Name, Slug und Platz-ID. Nach Auswahl werden Kerndaten, Adresse, Koordinaten und Karte angezeigt. Erst danach wird der bestehende Feld-/Datensatzvergleich explizit geladen. Die bestehende Merge-Logik bleibt unverändert; der alte Duplikat-Link wird bereits per 301 auf den Hauptplatz weitergeleitet.
- **Benutzer & Rechte:** Manuelle Auszeichnungen stehen direkt nach den Accountdetails und sind einklappbar; im geschlossenen Zustand wird "X von Y vergeben" gezeigt. Rollen folgen weiter unten. Individuelle Permission-Overrides sind ebenfalls einklappbar und zeigen die Anzahl vorhandener Overrides.
- **Manuelle E-Mail-Bestätigung:** neue Permission `users.verify_email`, standardmäßig nur Administratoren/Owner. Bei unbestätigten Accounts kann die E-Mail nach Sicherheitsabfrage administrativ bestätigt werden; die Aktion wird im Auditlog protokolliert.
- **Import-Zentrale:** Die vorhandenen Kennzahlen oben bleiben unverändert. Direkt darunter gibt es ein einklappbares Quellenprüfungs-Log mit Quelle, Typ, `last_checked_at` und `last_success_at`. Dafür werden die bereits zentral gespeicherten Informationen aus `external_sources` verwendet; es entsteht kein zweites Prüfprotokoll.
- **Änderungsvorschläge Übersicht:** Darstellung erfolgt in allen Statusansichten nach Platz und darunter nach tatsächlicher Moderationsgruppe. Eine Gruppe erscheint nur einmal mit Feldanzahl, Einreicher und Zeit. Pagination hält alle Gruppen eines Platzes auf derselben Seite.
- **Änderungsvorschläge Detail:** einzelne Feldänderungen sind deutlich kompakter. Kopfzeile enthält Anfrage-ID, Aktion, ggf. Datensatz-ID, Feldname und Status. "Bisher" und "Vorgeschlagen" bleiben nebeneinander. Komplexe Preis-/Öffnungszeiten-Vorschauen und der Entscheidungsblock bleiben erhalten.
- **Aktionslog:** geschlossene Einträge sind auf eine kompakte Zeile verdichtet; vollständige Vorher-/Nachher-Details bleiben aufklappbar.
- **Unverändert nach Review:** Merkmalsverwaltung, System & Debug, Review-Moderation und Support & Tickets.
- **Später:** Systemnachrichten erneut prüfen, sobald ein News-System existiert. Ziel ist dann eine gemeinsame Verwaltung für News, Systembenachrichtigungen und ähnliche Kommunikation.

## 32. Fortschreibung - Finaler Pre-Beta-Check und nächster Datenblock vom 2026-09-30

Dieser Abschnitt ist für den unmittelbaren Beta-Stand maßgeblich und ersetzt ältere offene Pre-Beta-Punkte, soweit sie hier als erledigt dokumentiert sind.

### Niedersachsen - erster echter Update-Zyklus

Der Wiederholungslauf `imports:niedersachsen-sync` wurde am 2026-09-30 auf Production unter Lockdown ausgeführt.

Ergebnis:
- 397 eindeutige externe Datensätze;
- 1 neu;
- 49 geändert;
- 347 unverändert;
- 0 als missing markiert;
- 950 externe Bilder gefunden;
- 949 importiert;
- 1 dauerhaft übersprungen;
- 0 offene Medienfälle;
- Klassifikation: 1 `new_candidate`, 0 `possible_duplicate`, 0 `needs_review`, 0 Review-Items;
- Sync-Run #8.

Der neue Datensatz wurde in der Import-Zentrale manuell geprüft, als plausibler neuer Platz bestätigt und übernommen. Das mitgelieferte Bild wurde ebenfalls korrekt übernommen. Damit ist der erste reale Update-Zyklus der Niedersachsen-Quelle erfolgreich bestätigt: bestehende Datensätze wurden wiedererkannt, Änderungen erkannt und nur der tatsächlich neue Datensatz als neu klassifiziert.

### Production-Smoke-Test

Nach Deployment des Admin-/Statistik-Blocks wurden die öffentlichen und internen Kernpfade auf Production praktisch geprüft:
- Gast: öffentliche Seiten, Suche/Filter/Karte/Platzprofile funktionieren.
- Normaler Nutzer: relevante Nutzerseiten und Kernfunktionen funktionieren.
- Admin/Owner: Adminbereiche laden und die überarbeiteten Werkzeuge funktionieren.
- Es traten bei diesem Durchgang keine auffälligen 500er oder sichtbaren regressiven Fehler auf.

Während des Gasttests fiel auf, dass die manuelle Sprachwahl ohne Login nicht gut auffindbar war. Deshalb wurde im Desktop-Header für Gäste zwischen "Platz vorschlagen" und "Anmelden" derselbe DE/EN-Selector ergänzt, der bereits im Usermenü verwendet wird. Die Änderung wurde auf Production geprüft und funktioniert.

### Queue und Scheduler

Final geprüft:
- `camperwolf-queue.service` ist enabled und active.
- Worker: `queue:work database --queue=mail,photos,default --sleep=3 --tries=1 --timeout=120`.
- Ein realer `ProcessPhotoUpload`-Job wurde nach dem Restart erfolgreich verarbeitet.
- `queue:failed`: keine fehlgeschlagenen Jobs.
- Webuser-Cron läuft jede Minute mit `artisan schedule:run`.
- Root-Crontab ist leer.
- Aktive Scheduler-Einträge:
  - `notifications:cluster` alle 10 Minuten;
  - `notifications:cleanup` täglich 03:20 UTC;
  - `security:cleanup` täglich 03:50 UTC;
  - `accounts:finalize-deletions` stündlich;
  - `data-exports:cleanup` stündlich;
  - `imports:niedersachsen-sync` täglich 03:10 UTC.
- Der frühere fehlerhafte Telescope-Prune-Eintrag ist nicht mehr aktiv.

### Logs

- Die sichtbaren Laravel-Fehler im aktuellen Logauszug waren historische Altlasten:
  - frühere Migration mit ungültigem Default für `valid_until`;
  - früherer `telescope:prune`-Scheduler bei nicht installiertem Telescope.
- Für den finalen Check wurden keine neuen relevanten Laravel-Fehler festgestellt.
- Das Queue-Journal zeigte nur den bewusst ausgelösten Restart sowie anschließend einen erfolgreich abgearbeiteten Fotojob.

### Camperwolf-spezifischer Security-Pass

Bewusst nur Camperwolf-spezifisch geändert/geprüft; auf dem Plesk-Server laufen weitere Projekte. Serverweite Dienste, Ports oder Firewallregeln werden deshalb nicht pauschal verändert.

Bestätigt:
- MariaDB lauscht nur lokal auf `127.0.0.1:3306`.
- Produktions-`.env` gehört dem Plesk-Webuser und hat Modus `600`.
- `https://camperwolf.de/.env` liefert HTTP 403.
- Effektive SSH-Konfiguration:
  - `PermitRootLogin without-password`;
  - `PubkeyAuthentication yes`;
  - `PasswordAuthentication no`;
  - `KbdInteractiveAuthentication no`.
- Root besitzt zwar noch ein gesetztes lokales Passwort, dieses ist wegen deaktiviertem SSH-Passwortlogin nicht für SSH-Anmeldung nutzbar.
- UFW ist serverweit inaktiv; zahlreiche Plesk-/Mail-/DNS-/FTP-Dienste lauschen öffentlich. Das ist als serverweiter Prüfpunkt notiert, wird vor der Beta aber bewusst nicht blind verändert, weil andere Projekte/Domains davon abhängen können.

### Backup und Lockdown

- Nach den finalen Änderungen wurde ein frisches vollständiges Plesk-Backup erstellt.
- Aktuelle Backupgröße ca. 928 MB; Wachstum gegenüber früher ca. 512 MB wird im Wesentlichen durch den inzwischen vorhandenen Bildbestand erklärt, derzeit grob 410 MB Fotos.
- Restore-Fähigkeit wurde bereits vorher praktisch erfolgreich getestet.
- Lockdown ist weiterhin aktiv und bleibt es bis zur bewussten öffentlichen Beta-Freigabe.

### GitHub / Production wieder auf sauberem Main

- PR #13 wurde aus Draft genommen und auf Mergeability geprüft.
- Finaler PR-Head vor Merge: `130948b320a66996d0091adda2156a18ea3b1c7a`.
- PR #13 wurde erfolgreich per Merge-Commit nach `main` übernommen:
  `2435ae75f571a6c4bf2ed33a9ae6b6e566c9e908`.
- Production wurde danach von `admin-statistics-usage-analytics` zurück auf `main` geschaltet.
- `main` wurde per Fast-Forward auf `origin/main` gebracht.
- Abschließender Zustand: Branch `main`, up to date mit `origin/main`, Working Tree sauber.
- Migrationen und Frontend-Build waren bereits auf demselben getesteten Codebestand ausgeführt worden; nach dem Branch-Wechsel war deshalb kein erneuter Build und keine neue Migration notwendig.

### Was vor der Beta noch offen ist

Die technische Beta-Basis ist fertig. Offen ist jetzt nur noch der kontrollierte Abschluss des aktuell lokal abgenommenen Entwicklungsstacks:

1. gezielte Regressionstests und anschließend möglichst vollständige Test-Suite auf `external-duplicate-groups`;
2. Branch-Stack in korrekter Reihenfolge nach `main` mergen;
3. Production mit vorwärtsgerichteten Migrationen und frischem Asset-Build aktualisieren;
4. NRW TFIS und Bayern ATKIS produktiv erstmals synchronisieren;
5. Dubletten-Gruppen und normale Review-Fälle prüfen, bevor neue Kandidaten massenhaft übernommen werden;
6. Wiederholungssync für NRW/Bayern zur Idempotenzkontrolle;
7. kurzer Production-Smoke-Test als Gast/User/Admin;
8. Lockdown bewusst deaktivieren;
9. Beta-Kommunikation/Telegram-Gruppe informieren.

Eine zusätzliche Admin-/Moderations-Aufräumrunde ist nicht erforderlich. Keine neue Datenquelle mehr vor diesem Block aufnehmen, sofern nicht ein konkreter technischer Grund entsteht.

### Nächster Arbeitsblock: Merge und Production-Deployment

Der vorherige Arbeitsblock "weitere Datenquellen" ist abgeschlossen:
- NRW TFIS ist lokal integriert und abgenommen;
- Bayern ATKIS ist lokal integriert, wiederholt neu importiert und abgenommen;
- Recherche-Import ist fertig;
- die Import-Dublettenprüfung wurde von einer Bayern-Sonderlösung zu einem generischen, quellenübergreifenden Gruppenworkflow weiterentwickelt;
- Kartenstichprobe und "Letzte Änderung" sind lokal abgenommen.

Der unmittelbare nächste Schritt ist **kein weiterer Import-Connector**, sondern der in Abschnitt 34 dokumentierte Merge-/Deployment-Ablauf. DZT bleibt wegen fehlendem verlässlichen Zugang zurückgestellt. Mobilithek, GovData und weitere Länderportale erst nach der Beta beziehungsweise nach dem erfolgreichen Deployment dieses Stacks.

## 33. Recherche-CSV - umgesetzt und lokal abgenommen (Stand 2026-10-01)

Admin-only Workflow für gezielte Recherche und Bereinigung bestehender Camperwolf-Plätze. Der alte generische CSV-Import bleibt davon getrennt.

Grundregeln:
- Recherche-Export bezieht sich über `place_id` eindeutig auf bestehende Camperwolf-Plätze.
- Pflichtdaten eines Platzes bleiben Name, Koordinaten und Platztyp; der Recherche-Workflow führt keinen zusätzlichen Vollständigkeitsscore ein.
- Ein Platz ist für Recherche interessant, sobald mindestens eines der definierten optionalen Basisfelder fehlt beziehungsweise unbekannt ist.
- Der Export liefert trotzdem den aktuellen Basisdatenstand als Kontext plus `missing_fields`.
- Alle Basisdaten dürfen durch Recherche vorgeschlagen werden, ausdrücklich auch Name, Platztyp und Koordinaten, damit sich der Workflow auch für Bereinigungen fehlerhafter Importdaten eignet.
- Leere CSV-Felder bedeuten "keine Aussage" und dürfen vorhandene Camperwolf-Werte nicht löschen.
- Identische Importwerte erzeugen kein Update. Neue Werte für bisher leere Felder werden als Ergänzungskandidaten behandelt; abweichende Werte zu bestehenden Angaben werden reviewpflichtig.
- Der Recherche-Import überschreibt Community-/Owner-/Admin-Daten nie blind. Recherchewerte bleiben zusätzlich im Quellenlayer nachvollziehbar.
- Mehrere Zeilen für dieselbe `place_id` sind ausdrücklich erlaubt.
- **Eine CSV-Zeile entspricht genau einer Recherchequelle für genau einen Platz. Alle in dieser Zeile eingetragenen Werte müssen durch diese Quelle belegt sein.**
- Werden unterschiedliche Quellen verwendet, entstehen getrennte Zeilen. Beispiel: Adresse aus Google Maps und Kontaktdaten von der Betreiberwebsite werden in zwei Zeilen dokumentiert.
- Liefern mehrere Quellen für dasselbe Feld denselben neuen Wert, ist das unkritisch und kann als zusätzliche Bestätigung dienen. Liefern sie unterschiedliche Werte, entsteht ein Konflikt zur manuellen Prüfung.
- `source_url` ist optional. Quellen ohne URL dürfen z. B. "Vor Ort erhoben" oder "Telefonisch erfragt" sein.
- Bei URL-Quellen wird die konkrete URL gespeichert und in der Platz-Historie klickbar dargestellt.
- Der Autor ist adminseitig frei benennbar, z. B. "KI-gestützte Ergänzung", "Sascha manuell recherchiert" oder eine Bezeichnung für ein Bulk-Datenupdate. KI ist dabei der Bearbeiter/Autor, nicht die fachliche Quelle.
- Vorgesehene Provenienzfelder: `author`, `source_label`, `source_url`, optional `researched_at` und `notes`.
- Zunächst nur Basisdaten, keine Ausstattungs-/Merkmalswerte, Preise oder Öffnungszeiten.
- Vorgesehener Basisumfang: Name, Platztyp, Koordinaten, vollständige Adresse, Betriebsstatus, Betreiber, allgemeine Stellplatzzahl, Website, Telefon, E-Mail sowie "Geeignet für" inklusive optionaler Kapazität.
- "Geeignet für" wird dynamisch über die aktiven `vehicle_types` exportiert/importiert. Spaltenformat `suitable_<slug>`: leer = keine Aussage, `yes` = geeignet ohne bekannte Kapazität, positive Ganzzahl = geeignet mit Kapazität, `no` = ausdrücklich ungeeignet. Dadurch erfordern später neu angelegte Fahrzeugtypen keine hartcodierte CSV-Anpassung.

Geplanter Ablauf:
1. Admin exportiert recherchebedürftige oder gezielt gefilterte Plätze.
2. Recherche wird extern/manuell durchgeführt; pro Quelle und Platz eine CSV-Zeile.
3. Eigener "Recherche-Import" validiert die Datei und schreibt zunächst in den External-/Source-Layer.
4. Bestehende Camperwolf-Werte werden feldweise verglichen; nur neue oder abweichende Informationen werden offen.
5. Review in der Import-Zentrale mit Einzel-, Auswahl- und später Bulk-Übernahme.
6. Erst die Freigabe verändert die eigentlichen Camperwolf-Basisdaten; die recherchierte Quelle bleibt nachvollziehbar gespeichert.

Historie/Provenienz:
- Öffentlicher Platzverlauf trennt künftig "Autor" und "Quelle".
- `place_history.metadata` trägt für Rechercheeinträge die Provenienzfelder; dadurch ist zunächst keine neue History-Tabelle nötig.
- Für bestehende externe History-Einträge ohne getrennten Autor wird als Autor generisch "Externe Datenquelle" und der vorhandene externe Quellenname als Quelle dargestellt.
- Nur sichere HTTP-/HTTPS-URLs werden als klickbare Quellenlinks ausgegeben.

Umsetzungsreihenfolge:
1. Platz-Historie um getrennten Autor/Quelle und klickbare Quellen-URL erweitern. **Auf Branch `research-import` umgesetzt und lokal abgenommen.**
2. Recherche-Export entwickeln. **Auf Branch `research-import` umgesetzt und lokal grün getestet.**
3. Recherche-Import, Feldvergleich und Review/Übernahme für bestehende Plätze entwickeln. **Auf `research-import` vollständig umgesetzt und lokal Ende-zu-Ende abgenommen, einschließlich Einzel- und Bulk-Apply, Schutz vor zwischenzeitlichen Änderungen und History/Provenienz.**
4. Lokal vollständig testen, danach PR; Production erst nach Abnahme. **Lokale Abnahme ist erfolgt; PR/Merge/Production stehen noch aus und sind Teil des nächsten Deployment-Blocks.**

Wichtige CSV-Struktur ab Import-Implementierung:
- aktuelle Camperwolf-Daten stehen ausschließlich in read-only Kontextspalten `current_*`;
- die gleichnamigen Vorschlagsspalten ohne Präfix sind beim Export leer und ausschließlich für recherchierte Angaben vorgesehen;
- entsprechend existieren bei Fahrzeugtypen Paare wie `current_suitable_motorhome` und `suitable_motorhome`;
- dadurch werden vorhandene Camperwolf-Werte beim späteren Upload nicht fälschlich der neu eingetragenen Recherchequelle zugeschrieben;
- ältere Recherche-Exports von vor dieser Trennung dürfen nicht als Importdatei weiterverwendet werden; vor echter Recherche immer frisch exportieren.

Recherche-Import - erster Teilblock:
- eigener admin-only Upload in der Import-Zentrale;
- CSV-Zeilen ohne ausgefüllte Vorschlagsfelder werden übersprungen;
- sobald mindestens ein Vorschlagsfeld gesetzt ist, sind `author` und `source_label` Pflicht; `source_url` bleibt optional, wird bei Angabe aber auf HTTP/HTTPS validiert;
- `researched_at` ist optional; `notes` optional;
- Werte werden normalisiert und validiert, unter anderem Koordinaten, Platztyp, Status, E-Mail, URLs, Kapazitäten und dynamische Fahrzeugspalten;
- Upload schreibt ausschließlich in `external_sources`, `external_records`, `external_record_fields`, `external_import_runs` und `external_import_review_items`; bestehende Camperwolf-Platzdaten bleiben unverändert;
- externe Recherche-Quelle ist zentral `research-import`; konkrete Autor-/Quellen-Provenienz bleibt zeilenbezogen im normalisierten Record/Review erhalten und wird nicht als eigene `external_source` pro Website angelegt;
- gleiche aktuelle und recherchierte Werte erzeugen keinen Review-Fall;
- neue oder abweichende Werte erzeugen `research_update`;
- widersprüchliche Werte mehrerer Recherchezeilen desselben Uploads für dasselbe Platz/Feld erzeugen `research_conflict`;
- vorhandene offene Review-Fälle desselben Recherche-Records werden bei erneutem Upload auf `superseded` gesetzt;
- Review-Queue zeigt Autor, Quelle/URL, Recherchezeit, Notiz sowie aktuellen gegen recherchierten Feldwert;
- Bewertung-Badges in der Review-Queue sind kontrastreicher und größer dargestellt;
- Recherche-Reviews können jetzt einzeln mit "Recherche übernehmen" in den Camperwolf-Bestand geschrieben werden;
- vor der Übernahme werden alle betroffenen aktuellen Werte erneut gegen den beim Upload gespeicherten Stand geprüft; bei zwischenzeitlicher Änderung wird die Übernahme abgebrochen statt einen neueren Wert zu überschreiben;
- Basisfelder auf `places` werden direkt aktualisiert; Adressen, Details, Kontakte und Fahrzeugzuordnungen werden über neue Versionen aktualisiert bzw. angelegt;
- Recherche-Telefonnummern werden kanonisch als `contact_type=telephone` gespeichert; Export und Vergleich erkennen sowohl ältere `phone`- als auch `telephone`-Kontakte;
- erfolgreiche Übernahme löst den Review, klassifiziert den External Record als `research_applied`, schreibt einen Admin-Audit-Log und einen öffentlichen Platzverlauf mit getrenntem Recherche-Autor und Quelle;
- zusätzlich gibt es eine Sammelaktion "Alle Recherchewerte übernehmen", die alle offenen eindeutigen `research_update`-Reviews nacheinander mit denselben Schutzprüfungen übernimmt; Quellkonflikte bleiben bewusst außen vor und müssen einzeln geprüft werden; fehlschlagende/stale Reviews bleiben offen, während die übrigen weiter verarbeitet werden;
- für die übrige Import-Review-Queue gibt es ebenfalls Sammelaktionen "Alle zurückstellen" und "Alle ignorieren"; Recherche-Reviews sind davon ausdrücklich ausgeschlossen. Zurückstellen lässt die Fälle offen und markiert sie gesammelt als zurückgestellt. Ignorieren schließt nur technisch ignorierbare Datensätze; bereits verknüpfte oder anderweitig geschützte Fälle werden ausgelassen und bleiben offen;
- lokaler End-to-End-Praxistest am 2026-10-01 erfolgreich: neue und geänderte Basiswerte, Zahlenwerte sowie Fahrzeug-Eignungen wurden korrekt übernommen; Platzverlauf zeigte Autor, Quellenlabel und klickbare Quellen-URL wie vorgesehen;
- explizites `no` bei einer Fahrzeug-Eignung kann derzeit nur dann materialisiert werden, wenn bereits eine positive Zuordnung existiert und deaktiviert werden kann. Ist bislang keine Zuordnung vorhanden, bleibt der Review offen, weil das aktuelle Platzmodell "unbekannt" und "explizit ungeeignet" noch nicht getrennt speichern kann. Der Quellenwert bleibt im External Layer erhalten.

Recherche-Export - aktueller Stand:
- eigener admin-only Download in der Import-Zentrale;
- zwei Scopes: standardmäßig nur Plätze mit mindestens einem fehlenden definierten Recherchefeld, optional alle veröffentlichten aktiven Plätze;
- Export ist UTF-8 mit BOM und Semikolon-Trennung für gute Tabellenkalkulations-Kompatibilität;
- enthält `place_id`, `missing_fields`, die vorgesehenen Provenienzspalten, aktuelle Basisdaten und dynamisch alle aktiven `suitable_<vehicle-slug>`-Spalten;
- Fahrzeugwert im Export: leer = keine bekannte Zuordnung, `yes` = geeignet ohne Kapazität, Zahl = geeignet mit bekannter Kapazität;
- Export ist strikt read-only und verändert keinerlei Platz-/Quellen-/History-Daten;
- aktuell als Recherchebedarf gezählte Felder: Land, PLZ, Ort, Straße, Betreiber, allgemeine Stellplatzzahl, Website, Telefon, E-Mail sowie mindestens eine bekannte Fahrzeug-Eignung. Hausnummer und Adresszusatz bleiben als optionale Detailfelder im Export enthalten, erzeugen aber keinen Recherchebedarf. Insbesondere `address_addition` ist bewusst kein Vollständigkeitskriterium, da dieses Feld bei sehr vielen vollständig brauchbaren Plätzen legitimerweise leer bleibt.



## 34. Fortschreibung - Datenquellen, Kartenstichprobe und generische Import-Dubletten vom 2026-10-01

Dieser Abschnitt ist für den unmittelbar folgenden Merge-/Deployment-Block maßgeblich. Er ersetzt ältere Aussagen, nach denen Bayern ATKIS noch "in Arbeit" sei, die Recherche-Übernahme noch fehle oder als nächste Aufgabe erst weitere Datenquellen gesucht werden sollten.

### 34.1 NRW TFIS - lokal abgeschlossen, noch nicht produktiv

Offizielle Quelle:
- OGC API: `https://ogc-api.nrw.de/tfis/v1`;
- öffentlich ohne beobachtete Authentifizierung;
- Lizenz: Datenlizenz Deutschland - Zero - Version 2.0;
- External Source: `nrw-tfis`.

Relevante Zuordnung:
- `Campingplatz` -> `campground`;
- `Parkplatz` -> `parking`;
- `Wanderparkplatz` -> eigener Typ `hiking-parking`;
- ein als `Parkplatz` geliefertes Objekt mit `info_ext = Wanderparkplatz` wird ebenfalls als `hiking-parking` behandelt.

Lokaler analysierter Bestand:
- 457 Campingplätze;
- 5.040 Parkplätze;
- 355 Wanderparkplätze;
- zusammen 5.852 Records;
- 1.208 benannte Kandidaten;
- 4.644 reine Source-Layer-Records;
- beim damaligen Stand vier mögliche Dubletten zu bestehenden Camperwolf-Plätzen.

Wichtig:
- `hiking-parking` ist inzwischen als Platztyp vorhanden.
- Keine automatische semantische Umdeutung eines Wanderparkplatzes zu Wohnmobilstellplatz nur wegen Name, Bild oder mutmaßlicher Nutzung.
- NRW wurde auf dem damaligen Branch bereits technisch fertiggestellt und in den späteren Branch-Stack übernommen; Production-Sync steht noch aus.

### 34.2 Recherche-Import - abgeschlossen

Branch `research-import` ist lokal abgenommen. Umgesetzt:
- öffentlicher Platzverlauf mit getrenntem Autor und Quelle;
- klickbare sichere Quellen-URLs;
- Recherche-CSV-Export mit `current_*`-Kontextspalten und leeren Vorschlagsspalten;
- dynamische Fahrzeugspalten;
- Import-Staging im External Layer;
- `research_update` und `research_conflict`;
- Einzelübernahme;
- Bulk "Alle Recherchewerte übernehmen";
- Schutz gegen stale Daten: vor Apply wird geprüft, ob sich der Camperwolf-Wert seit Upload verändert hat;
- Versionierung von Adresse, Details, Kontakten und Fahrzeugzuordnungen;
- Audit und öffentlicher Verlauf;
- Bulk "Alle zurückstellen"/"Alle ignorieren" für normale Import-Reviews, Recherchefälle davon ausgenommen.

Bekannte Modellgrenze bleibt:
- ein explizites Fahrzeug-`no` kann nur materialisiert werden, wenn bereits eine positive Zuordnung existiert, die deaktiviert werden kann;
- fehlt bisher jede Zuordnung, kann das aktuelle Platzmodell "unbekannt" nicht von "explizit ungeeignet" unterscheiden;
- der Quellenwert bleibt trotzdem im External Layer erhalten.

### 34.3 Bayern ATKIS Basis-DLM - lokal vollständig abgenommen

Offizielle Quelle:
- Bayerische Vermessungsverwaltung;
- WFS 2.0: `https://geoservices.bayern.de/wfs/v1/ogc_atkis_basisdlm.cgi`;
- öffentlich ohne beobachtete Authentifizierung;
- CC BY 4.0;
- tägliche Aktualität.

Relevante FeatureTypes und Funktionen:
- `AX_Platz/5310` Parkplatz -> `parking`: 5.368;
- `AX_Platz/5320` Rastplatz -> `rest-area`: 897;
- `AX_Platz/5330` Raststätte/Autohof -> `rest-area`: 671;
- `AX_Platz/5370` Caravan-/Wohnmobilstellplatz -> `motorhome-pitch`: 94;
- `AX_SportFreizeitUndErholungsflaeche/4330` Campingplatz -> `campground`: 832;
- Gesamt: 7.862 relevante Records;
- davon 3.493 mit öffentlichem Kandidatennamen und 4.369 reine Source-Layer-Records.

Fachliche Regeln:
- keine Namens-/Bild-Heuristik zur automatischen Umklassifizierung;
- `4330` kann auch reale Zeltplätze enthalten, bleibt aber zunächst amtlich als `campground`;
- `zustand` wird als Quellenmetadatum bewahrt und nicht blind als "dauerhaft geschlossen" materialisiert;
- `datumDerLetztenUeberpruefung` bleibt Source-Metadatum;
- ATKIS liefert für diese FeatureTypes keine brauchbare strukturierte Camper-Ausstattung, daher keine erfundene Übernahme von Toilette, Strom, Wasser usw.

#### Wichtige technische Entdeckung 1: case-sensitive externe IDs

ATKIS-IDs sind case-sensitive. Die bisherige MySQL-Collation von `external_records.external_id` war case-insensitive und erzeugte dadurch echte ID-Kollisionen:
- 92 Kollisionspaare;
- 184 betroffene Änderungen.

Fix:
- Migration `2026_10_01_101500_make_external_record_ids_case_sensitive.php`;
- auf MySQL wird `external_id` mit `utf8mb4_bin` verglichen;
- danach sauberer Wiederholungslauf mit 7.862 unveränderten Records und 0 neuen/0 geänderten Records.

Diese Migration muss vor dem produktiven Bayern-Sync gelaufen sein.

#### Wichtige technische Entdeckung 2: Repräsentativpunkt von Flächen

ATKIS liefert Flächen. Ein früher Ansatz konnte durch unterschiedliche Reihenfolge/Redundanz der Geometriepunkte instabile Repräsentativkoordinaten erzeugen. Stabilisiert wurde deshalb:
- Koordinaten-Vertices deduplizieren;
- deterministisch sortieren;
- arithmetischen Mittelwert bilden;
- auf sieben Nachkommastellen runden.

Dadurch bleibt derselbe Quellrecord über Wiederholungsläufe geometrisch stabil.

### 34.4 Warum ATKIS so viele scheinbare Dubletten erzeugt

Bei manueller Prüfung fiel "Waldcamping Brombach" auf. ATKIS enthielt dort zehn verschiedene External Records mit identischem Namen und eng benachbarten Koordinaten. Ursache war nicht die Punktberechnung, sondern das Datenmodell: eine reale Anlage kann aus mehreren amtlichen Teilflächen bestehen.

Beispiel "Waldcamping Brombach":
- zehn ATKIS-Records;
- alle `campground`;
- alle gehören fachlich sehr wahrscheinlich zu einem realen Campingplatz.

Das gleiche Muster tritt besonders bei großen Rastanlagen und Campingflächen auf. Deshalb darf ein Import nicht einfach "ein Source Record = ein Camperwolf-Platz" annehmen.

Gleichzeitig darf **nicht** automatisch nur aufgrund gleichen Namens und räumlicher Nähe gemergt werden:
- gegenüberliegende Autobahnseiten können reale getrennte Plätze sein;
- gleichnamige Anlagen können in Ausnahmefällen tatsächlich eigenständig sein;
- manuelle Entscheidung bleibt erforderlich.

### 34.5 Generische Import-Dubletten-Gruppen

Die zunächst Bayern-spezifische Lösung wurde auf Branch `external-duplicate-groups` zu einem allgemeinen Mechanismus umgebaut.

Aktuelle Erkennungsregel:
- aktiver, noch nicht verknüpfter External Record;
- nicht `ignored`;
- identischer bereinigter Name;
- identischer vorgeschlagener Platztyp;
- räumliche Verbindung innerhalb 750 m;
- Gruppen können aus mehreren verschiedenen External Sources bestehen.

Die Erkennung läuft nach der normalen `ExternalRecordClassificationService`-Klassifizierung und kann source-interne wie source-übergreifende Überschneidungen in `external_duplicate_group`-Reviews überführen.

Wichtig:
- nichts wird automatisch gemergt;
- vorhandene normale Dublettenprüfung gegen Camperwolf-Plätze bleibt bestehen;
- Dublettengruppen sind bewusst aus den Sammelaktionen "Alle zurückstellen" und "Alle ignorieren" ausgeschlossen;
- ignorierte Records werden nicht später wieder in eine Gruppe hineingezogen;
- jede Gruppenaktion bleibt auditierbar über die vorhandenen External-Record-/Place-History-Mechanismen.

#### Lokal bestätigte Bayern-Zahlen nach komplettem Reset und frischem Sync

Sauberer Neuimport am 2026-10-01, Sync-Run #51:
- 7.862 relevante Records;
- 3.493 benannte Kandidaten;
- 4.369 source-only;
- 794 `new_candidate`;
- 2.699 `possible_duplicate`;
- 0 `needs_review`;
- 2.699 offene Review-Items.

Gruppenstatistik:
- 743 Dubletten-Gruppen;
- 2.569 Records in Gruppen;
- durchschnittliche Gruppengröße 3,46;
- Median 3;
- kleinste Gruppe 2;
- größte Gruppe 18.

Damit verbleiben bei diesem lokalen Datenstand rechnerisch 130 `possible_duplicate`-Records außerhalb der gruppierten 2.569 Records, also normale Einzel-Dublettenfälle gegen bestehende Camperwolf-Kandidaten.

Größere plausible Gruppen waren unter anderem:
- Steigerwald Süd: 18;
- Vaterstetten Ost: 17;
- Nürnberg-Feucht Ost: 16;
- Köschinger Forst Ost: 15;
- Vaterstetten West: 15;
- DCC Campingpark Romantische Straße: 11;
- Kratzmühle: 11;
- Waldcamping Brombach: 10.

"Waldcamping Brombach" wurde nach komplettem Source-Reset und Neuimport erneut identisch als 10er-Gruppe erkannt. Das bestätigt Reproduzierbarkeit der Gruppierung.

### 34.6 Import-Zentrale - Gruppenworkflow praktisch abgenommen

Die Gruppenansicht ist jetzt quellenneutral:
- Gruppenname;
- Platztyp;
- beteiligte Quellen;
- je Mitglied Quelle, externe ID, interne Record-ID und Koordinaten;
- vorhandene Camperwolf-Kandidaten mit ID, Distanz und Namensähnlichkeit.

Aktionen:
1. **Als Hauptplatz verwenden**
   - aus dem gewählten External Record wird ein neuer Camperwolf-Platz angelegt;
   - alle aktuell offenen Mitglieder der Gruppe werden mit diesem Platz verknüpft.
2. **Als eigenen Platz anlegen**
   - nur dieses Gruppenmitglied wird bewusst als eigener Camperwolf-Platz angelegt;
   - die übrige Gruppe bleibt offen und schrumpft entsprechend.
3. **Mit vorhandenem Camperwolf-Platz verknüpfen**
   - alle offenen Gruppenmitglieder werden mit einem bereits existierenden Camperwolf-Platz verknüpft.
4. **Auf Karte prüfen**
   - öffnet ein gemeinsames Modal;
   - externe Records erscheinen als Marker mit Record-ID, Quelle und externer ID;
   - bestehende Camperwolf-Kandidaten erscheinen zusätzlich als eigene Kreis-Marker.

Der komplette Workflow wurde am 2026-10-01 im Browser praktisch getestet und vom Nutzer als fehlerfrei bestätigt:
- Gruppenanzeige;
- Karte;
- einzelne Records abspalten;
- Restgruppe weiterbearbeiten;
- Hauptplatz aus Gruppenrecord erzeugen;
- mit bestehendem Platz verknüpfen;
- Reload/Neuberechnung der Gruppe.

Zusätzlich wurde bewusst bestätigt, dass dieser Gruppenworkflow als **allgemeine Import-Infrastruktur** weiterverwendet werden soll. Neue External Sources sollen nach Möglichkeit keine eigene parallele Dubletten-UI erhalten, sondern dieselbe generische Gruppenprüfung und dieselben Review-Aktionen benutzen. Je mehr Datenquellen hinzukommen, desto wichtiger wird dieser quellenübergreifende Review-Weg.

#### Wichtige technische Entdeckung 3: Karte im Modal zunächst unsichtbar

Die Lazy-Map öffnete korrekt und die Daten wurden geladen, aber der Kartenbereich blieb praktisch höhenlos. Ursache war die Containerhöhe über eine Tailwind-Klasse, die im aktuellen Frontend-Build nicht zuverlässig vorhanden war.

Fix:
- Kartenhöhe direkt am Container gesetzt: ungefähr 60 vh, Mindesthöhe 320 px, Maximalhöhe 720 px;
- dadurch unabhängig von einem noch nicht neu generierten Tailwind-Build;
- Karte wird weiterhin erst beim Klick geladen.

Dies ergänzt die bereits bekannte Production-Regel: Bei Blade-/Tailwind-/JS-/Vite-Änderungen beim Deployment **immer** einen frischen `npm ci`/`npm run build` durchführen, weil `public/build` nicht per Git aktualisiert wird.

### 34.7 Kartenmarker - 1000er-Limit ohne geografischen ID-Bias

Die öffentliche Browse-Karte bleibt aus Performancegründen auf 1.000 Marker begrenzt. Die bisherige Auswahl nach `p.id` verzerrte den Kartenausschnitt aber stark, sobald große Importblöcke aus einzelnen Regionen hinzukamen.

Auf `map-marker-shuffle` wurde deshalb eine stabile pseudozufällige Teilmenge eingeführt:
- Multiplikator `2654435761`;
- Modulus `4294967296`;
- SQL-Ausdruck `(p.id * 2654435761) % 4294967296`;
- kein echtes `RAND()`, daher bleibt die Auswahl zwischen Reloads stabil;
- SQLite-Kompatibilität wurde berücksichtigt; die erste `MOD()`-Variante funktionierte dort nicht.

Wichtig für explizite Sortierung:
- ohne Sortierparameter bestimmt der stabile Hash die 1.000er-Auswahl;
- bei `name`, `city`, `score`, `newest` oder `changed` wird zuerst nach dem gewünschten fachlichen Sortierkriterium auf 1.000 begrenzt und erst danach deterministisch stabilisiert;
- Marker bleiben auf dieselben gecappten Result-IDs begrenzt, damit Listen-Hover und Karte zusammenpassen.

Zusätzlich wurde die Sortierung **"Letzte Änderung"** ergänzt.
- `places.updated_at` reicht dafür nicht aus, weil viele echte Platzänderungen in versionierten Nebentabellen beziehungsweise History landen.
- Deshalb wird `MAX(place_history.created_at)` pro Platz verwendet.
- Fallback für Plätze ohne History: `places.created_at`.
- vorhandener Index `place_history(place_id, created_at)` wird genutzt.

Beide Funktionen wurden lokal praktisch als korrekt bestätigt.

### 34.8 Manuelles "Plätze zusammenführen" und External Records

Bei der Prüfung fiel eine wichtige Inkompatibilität zwischen Import-Dublettenworkflow und dem bestehenden Admin-Merge auf:

Der `PlaceMergeService` verschob zwar unter anderem Reviews, Fotos, Favoriten und `place_data_sources`, aber **nicht** die direkte Zuordnung `external_records.place_id`.

Das hätte zu folgendem Fehlerbild führen können:
1. External Record erzeugt Platz #15.
2. Admin merged #15 später manuell in #1.
3. Platz #15 wird deaktiviert.
4. External Record zeigt aber weiterhin auf #15.
5. Ein späterer Quellsync arbeitet damit auf einer veralteten Platzzuordnung.

Fix auf dem aktuellen Branch:
- beim Merge werden alle `external_records.place_id = duplicateId` auf den Hauptplatz umgehängt;
- der Snapshot nimmt die External-Record-Zuordnungen mit auf;
- beim Reverse werden die ursprünglichen `place_id`-/Classification-Zuordnungen wiederhergestellt;
- Regressionstest ergänzt.

Zielregel:
**Ein einmal aufgelöster externer Datensatz bleibt dauerhaft mit dem gewählten Camperwolf-Hauptplatz verbunden - egal ob die Zuordnung direkt im Import-Review oder später über "Plätze zusammenführen" entstanden ist.**

Damit sind beide Dublettenmechanismen kompatibel.

Besonderheit für spätere Wartung:
- Wird ein externer Datensatz zunächst als eigener Platz angelegt und dieser Platz später über den Adminbereich "Plätze zusammenführen" in einen anderen Hauptplatz gemergt, darf der nächste Quellsync **keine neue Dublette desselben Records erzeugen**. Voraussetzung dafür ist das jetzt implementierte Umhängen von `external_records.place_id`.
- Diese Kopplung zwischen Import-System und normalem Place-Merge ist künftig bei Änderungen an einem der beiden Systeme als Regression explizit mitzutesten.

### 34.9 Source-spezifischer lokaler Reset

Für wiederholbare Importtests existiert:
`php artisan imports:reset-source {slug} --force`

Regeln:
- nur lokal/Testumgebung verwenden;
- löscht die konkrete External Source samt zugehörigen Runs/Reviews/Records;
- löscht nur von dieser Source erzeugte Camperwolf-Plätze, sofern sie nicht inzwischen von einer anderen Source mitbenutzt werden;
- gemeinsame Plätze bleiben erhalten;
- andere Quellen wie NRW oder Niedersachsen bleiben unberührt.

Bayern wurde vor dem finalen Neuimport damit vollständig zurückgesetzt:
- 7.862 Records;
- 18 Runs;
- 12.391 Review-Items;
- 2.103 zuvor erzeugte Plätze;
- 2.103 Plätze gelöscht;
- 0 shared places erhalten;
- 0 Snapshots.

Der anschließende frische Sync reproduzierte die erwarteten Zahlen und Gruppen.

### 34.10 Noch ausstehende automatische Regressionstests

Die neuen Funktionen wurden intensiv praktisch im Browser getestet. Vor dem Merge sollen in der nächsten Session zusätzlich mindestens diese gezielten Testdateien lokal laufen und grün sein:

- `tests/Feature/ExternalDuplicateGroupTest.php`;
- `tests/Feature/AdminImportCenterTest.php`;
- `tests/Feature/ExternalRecordClassificationTest.php`;
- `tests/Feature/PlaceMergeServiceTest.php`;
- die bereits vorhandenen Bayern-/NRW-/Recherchetests;
- danach sinnvollerweise die komplette Test-Suite, bevor der Branch-Stack nach `main` geht.

Keine Production-Änderung beginnen, solange diese Regressionstests nicht sauber sind.

### 34.11 Merge-Plan für den gestapelten Branch-Stack

Unmittelbar nächster Arbeitsschritt in einer neuen Session ist **nicht weitere Entwicklung**, sondern Merge + Deployment des abgenommenen Stacks.

Abhängigkeiten:
`main`
-> `research-import`
-> `bayern-atkis-import`
-> `map-marker-shuffle`
-> `bayern-duplicate-groups`
-> `external-duplicate-groups`

Stand 2026-10-01 gegenüber `main`:
- research: +55 / -0;
- Bayern: +79 / -0;
- Marker: +89 / -0;
- Bayern-Dubletten: +104 / -0;
- generische Dubletten: +129 / -0.

Empfohlener Ablauf:
1. Lokal auf `external-duplicate-groups` die gezielten Tests und danach vollständige Suite laufen lassen.
2. `git status` prüfen; keine ungeklärten lokalen/untracked Dateien übernehmen.
3. Branch-Vergleiche erneut gegen `main` prüfen.
4. PR `research-import -> main`; CI grün; bevorzugt Merge-Commit.
5. Danach `bayern-atkis-import -> main`; erneut Compare/CI; Merge-Commit.
6. Danach `map-marker-shuffle -> main`.
7. Danach `bayern-duplicate-groups -> main`.
8. Danach `external-duplicate-groups -> main`.
9. Nach jedem Merge sicherstellen, dass der nächste Branch weiterhin sauber auf dem neuen `main` aufsetzt. Bei unerwartetem Ahead/Behind **nicht blind mergen**.
10. Keine Squash-Merges mitten in dieser Kette, außer die nachfolgenden Branches werden danach bewusst rebased/neu aufgebaut.
11. Erst wenn der komplette Stack auf `main` ist, Production aktualisieren.

Alternativ wäre technisch ein einziger großer PR `external-duplicate-groups -> main` möglich, weil er den kompletten Stack enthält. Das ist aber schwerer zu reviewen und verliert die klare Teilblock-Trennung. Für den aktuellen Stand wird der sequentielle Merge bevorzugt.

### 34.12 Production-Deployment nach vollständigem Merge

Production bleibt bis dahin im Lockdown.

Vor Deployment:
- frisches Plesk-Full-Backup;
- sicherstellen, dass Production auf sauberem `main` steht;
- keine Datenbank-Resets auf Production;
- aktuellen `main` pullen.

Deployment-Schritte, die wegen der Änderungen relevant sind:
1. `composer install` gemäß bisherigem Production-Verfahren, falls Lockfile/Dependencies es verlangen.
2. `php artisan migrate --force` - besonders wichtig wegen case-sensitiver `external_records.external_id`.
3. Frontend zwingend frisch bauen:
   - `npm ci`;
   - `npm run build`;
   - bekannte optionale `fontaine`-Warnung ist nicht fatal.
4. Laravel-Caches nach dem etablierten Production-Ablauf neu aufbauen.
5. Queue mit `queue:restart` neu laden und systemd-Status prüfen.
6. `schedule:list` gegenprüfen.
7. Logs während/kurz nach Deployment beobachten.

Danach Daten:
1. NRW TFIS erstmalig produktiv synchronisieren und Statistiken prüfen.
2. Bayern ATKIS erstmalig produktiv synchronisieren.
3. **Dubletten-Gruppen zuerst prüfen**, bevor neue Kandidaten massenhaft als Plätze angelegt werden.
4. Quellenübergreifende Gruppen bewusst gegen bereits vorhandene Niedersachsen-/DATEX-/Community-Plätze prüfen.
5. Stichproben der Karte und der größten Gruppen durchführen.
6. Danach erst verbleibende `new_candidate`-Records übernehmen.
7. Wiederholungslauf/Idempotenz für NRW und Bayern durchführen.
8. Erst nach sauberem ersten Produktivlauf entscheiden, ob/mit welcher Frequenz Bayern/NRW in den Scheduler aufgenommen werden.

Wichtig:
- auf Production keine `imports:reset-source`-Aufräumläufe verwenden;
- bestehende Community-/Owner-/Admin-Daten nicht überschreiben;
- External Records sollen nach manueller Zuordnung dauerhaft auf denselben Camperwolf-Platz zeigen;
- ein späterer Admin-Merge zieht diese External-Record-Zuordnungen jetzt korrekt mit um.

### 34.13 Beta-Freigabe danach

Wenn Merge, Deployment, produktive Imports und kurzer Smoke-Test sauber sind:
1. Lockdown bewusst deaktivieren.
2. öffentlicher Gast-Gegencheck: Startseite, Suche, Karte, Platzprofil, Sprache, Login/Registrierung;
3. User/Admin-Kernpfade kurz prüfen;
4. Queue/Logs/Fehlerseite kontrollieren;
5. Beta-Kommunikation/Telegram-Gruppe veröffentlichen.

Bis dahin bleibt der Lockdown aktiv.

## 35. Fortschreibung - DatenScore, Filtervereinfachung und finaler Pre-Beta-Stand vom 2026-10-02

Dieser Abschnitt ist für den aktuellen technischen Stand maßgeblich und ersetzt ältere Aussagen, wonach DataScore noch nicht existiere, NRW/Bayern nur lokal seien, der öffentliche Preisblock Bestandteil der ersten Beta sei oder der frühere gestapelte Branch-Stack noch ausgerollt werden müsse.

### 35.1 DataScore - Produktentscheidung

Der **DatenScore** bewertet ausschließlich, wie vollständig die bekannten Platzdaten sind. Er bewertet ausdrücklich nicht:
- Richtigkeit;
- Aktualität;
- Vertrauenswürdigkeit;
- Qualität des Platzes.

Basisdaten und Merkmale werden getrennt gezeigt:
- Basisdaten: Betreiber, vollständige nutzbare Postadresse (Straße + PLZ + Ort; Hausnummer bewusst optional), Telefon, E-Mail, Website und bekannter Betriebsstatus;
- `BASIS_TOTAL = 6`;
- Merkmale: bekannte relevante aktive sichtbare `standard`-/`extended`-Merkmale des jeweiligen Platztyps; positive und negative explizite Zustände zählen als bekannt, `unknown` nicht;
- wenn ein Platztyp keine relevanten Merkmale besitzt, wird der Merkmalsteil technisch mit 10,0 angesetzt.

Gewichtung:
- Basisdaten 75 %;
- Merkmale 25 %;
- interne Formel auf 0-10;
- Anzeige mit einer Nachkommastelle;
- Rundung darf z. B. einen internen Wert 9,95 als 10,0 anzeigen.

Farben:
- grün ab 7,0;
- gelb ab 5,0 bis unter 7,0;
- rot unter 5,0.

"Letzte Änderung" wird separat angezeigt und beeinflusst den DatenScore nicht. Eine Änderung älter als ein Jahr erzeugt einen Hinweis, ist aber kein "zuletzt geprüft"-Signal.

### 35.2 DataScore - technische Umsetzung

Neue persistierte Felder auf `places`:
- `data_score_basis_known`;
- `data_score_basis_total`;
- `data_score_feature_known`;
- `data_score_feature_total`;
- `data_score_basis`;
- `data_score_features`;
- `data_score`;
- `data_score_dirty`;
- `data_score_calculated_at`.

Indizes liegen auf `data_score` sowie `(data_score_dirty, id)`.

Zentrale Logik: `App\Services\PlaceDataScoreService`.
- keine manuellen Increment-/Decrement-Counter;
- jede Neuberechnung leitet den Zustand aus den kanonischen Tabellen neu ab;
- `PlaceHistoryService` markiert betroffene Plätze dirty;
- Änderungen am Feature-Katalog markieren alle Scores dirty;
- `scoreForPlace()` heilt null/dirty beim Zugriff selbst;
- Scheduler berechnet dirty Scores alle fünf Minuten neu;
- Full-Rebuild: `php artisan places:recalculate-data-scores`;
- Admin System & Debug kann einen vollständigen Rebuild anstoßen;
- Full-Rebuild ist bewusst kein regulärer täglicher Scheduler.

Die erste produktive Komplettberechnung nach Migration ergab:
- **5.567 DatenScores neu berechnet**.

Migrationen:
- `2026_10_02_063000_add_data_scores_to_places.php`;
- `2026_10_02_064000_add_data_score_help_article.php`.

Die Profilkarte zeigt Gesamtwert, Details zu Basisdaten/Merkmalen, Hilfe-Link und ggf. Aktualitätshinweis. Das fehlende Chevron-Icon wurde über `config/tabler-icons.php` ergänzt.

### 35.3 Browse-Sortierung

DataScore kann auf- und absteigend sortiert werden. Die Sortier-UI wurde zu einem einzigen Dropdown zusammengeführt.

Der Standard bleibt bewusst die bereits vorhandene deterministische stabile Verteilung:
`(p.id * 2654435761) % 4294967296`, danach `p.id` als Tie-Breaker.

Begründung: "neueste zuerst" hatte bei der 1000er-Kartenbegrenzung neu importierte Regionen wie Bayern überproportional sichtbar gemacht. "Standard" soll die Ergebnisse stabil, aber nicht nach Importzeitpunkt verzerrt verteilen.

### 35.4 Platztyp- und Fahrzeugfilter - finaler Beta-Stand

**Platztyp**
- Dropdown bleibt bestehen;
- im neutralen Zustand sind optisch alle Platztypen ausgewählt;
- alle ausgewählt = keine Einschränkung;
- nichts ausgewählt = ebenfalls keine Einschränkung;
- nur eine Teilmenge = echte Filterung;
- keine separaten Platztyp-Filterchips unter "Aktiv";
- kleiner unauffälliger Button "Übernehmen".

**Geeignet für**
- Dropdown bleibt bestehen;
- neutral ist nichts ausgewählt und damit keine Einschränkung aktiv;
- einzelne ausgewählte Fahrzeugtypen filtern;
- ebenfalls keine zusätzlichen Fahrzeugtyp-Filterchips;
- gleicher kleiner Button "Übernehmen".

Die übrigen aktiven Filterchips für andere Filterarten bleiben bestehen.

### 35.5 Preise - für erste Beta bewusst aus öffentlicher UI entfernt

Der eigenständige Preisbereich wird für die erste Beta nicht angezeigt:
- Preisblock im öffentlichen Platzprofil entfernt;
- Preisfilter in der Suche entfernt;
- alte/manuell übergebene `price_values` werden im öffentlichen Browse ignoriert;
- öffentliche Preis-Facets werden nicht mehr unnötig berechnet;
- das öffentliche Platzprofil lädt die ausgeblendeten Preisdaten nicht mehr.

**Nicht gelöscht** wurden:
- strukturierte Preis-Tabellen;
- `PricePeriodService`;
- `PlacePriceController`;
- Preis-Editor;
- Preis-Routen;
- Change-Request-/Audit-/XP-/Badge-Logik.

Ziel: später mit verifizierten Platz-Ownern/Betreibern wieder aufnehmen, wenn komplexe saisonale Preisstrukturen von einer geeigneteren Nutzergruppe gepflegt werden können.

Die Hilfe "Das Platzprofil verstehen" wurde entsprechend auf Öffnungszeiten ohne separaten Preisblock angepasst.

### 35.6 Platzprofil-Layout

Nach Entfernen des Preisblocks wurde der Öffnungszeitenblock über die volle Seitenbreite gezogen.

Dabei wurde beim ersten Umbau versehentlich auch der Grid-Wrapper für:
- "Platzdetails";
- "Geeignet für"

aufgelöst. Das fiel direkt im Production-Sichttest auf und wurde mit PR #22 korrigiert.

Final:
- Desktop: "Platzdetails" links und "Geeignet für" rechts;
- darunter "Anfahrt & Zugang";
- Öffnungszeiten anschließend über volle Breite;
- Preisblock bleibt ausgeblendet.

### 35.7 GitHub / Deployments dieser Session

DataScore:
- PR #20 "Add place DataScore completeness indicator";
- Merge-Commit: `966abbf2025878c736374be113aed6c331150d57`;
- Production-Migrationen erfolgreich;
- initialer Full-Rebuild: 5.567 Plätze;
- `artisan optimize` erfolgreich.

Filter-/Preisvereinfachung:
- PR #21 "Simplify beta filters and hide public prices";
- Merge-Commit: `65be0c5272d23bc5fc7ea6eaa8f94a963a4a8b7a`;
- fokussierter Testworkflow: Browse/Profil grün, Blade-Compilation grün;
- temporärer Validierungsworkflow danach wieder aus dem Branch entfernt.

Layout-Hotfix:
- PR #22 "Restore place profile details grid";
- Merge-Commit und aktueller produktiver Anwendungscode: `5e405b155c7a79414bdaa144fff38888d57fbc1c`;
- Production per `git pull --ff-only` und `artisan optimize` aktualisiert;
- Nutzer hat den korrigierten Stand anschließend im Browser geprüft und als passend bestätigt.

### 35.8 Wichtiger Deployment-Befund: SupportContentSeeder

Auf Production fehlten zunächst zahlreiche Hilfeseiten, obwohl lokal deutlich mehr vorhanden waren.

Ursache:
- `SupportContentSeeder` ist **nicht** Teil des normalen `DatabaseSeeder`;
- reine Migrationen spielen diese redaktionellen Hilfeseiten deshalb nicht automatisch ein;
- der DataScore-Hilfeartikel war trotzdem vorhanden, weil er zusätzlich über eine eigene Migration eingeführt wurde.

Korrektur:
`php artisan db:seed --class=Database\\Seeders\\SupportContentSeeder --force`

Danach waren die Hilfeseiten vollständig.

**Verbindlicher Deployment-Merksatz:** Wenn `SupportContentSeeder.php` geändert wurde, muss dieser Seeder beim Deployment gezielt ausgeführt werden. Nicht darauf verlassen, dass `artisan migrate` oder der normale `DatabaseSeeder` ihn implizit ausführt.

### 35.8a Hilfetexte und manuelle Redaktion

Hilfetexte können im Adminbereich direkt auf Production bearbeitet werden. Der aktuelle `SupportContentSeeder` schreibt bestehende Artikel bei einem erneuten Lauf wieder mit den im Seeder hinterlegten Inhalten. Dadurch können manuelle Änderungen aus dem Adminbereich verloren gehen. Für die Beta bleibt dieses Verhalten unverändert; vor einem späteren erneuten Seeder-Lauf muss dieser Punkt berücksichtigt werden. Langfristig soll der Seeder bestehende redaktionelle Inhalte nicht mehr überschreiben.

### 35.9 Test-/CI-Hinweis

Für die Änderungen dieser Session existieren gezielte Regressionstests insbesondere in:
- `tests/Feature/PlaceDataScoreTest.php`;
- `tests/Feature/PlaceBrowseVariableFilterTest.php`;
- `tests/Feature/PlaceProfileLocaleTest.php`;
- zusätzlich betroffene Admin-/Katalogtests.

Der gezielte DataScore-Lauf war mit 10 Tests / 69 Assertions grün. Der gezielte Filter-/Preis-Lauf sowie die Blade-Compilation waren ebenfalls grün.

Der globale Repository-Workflow kann weiterhin an historischer Pint-Baseline scheitern. Das ist getrennt von den fokussiert getesteten Funktionsänderungen zu bewerten.

### 35.10 Marketing / Community

Marketing und Gewinnung aktiver Betatester wurden besprochen und bleiben nach dem technischen Beta-Start auf der Agenda. Details werden bewusst nicht in dieser Kontextdatei gepflegt; relevant ist nur, dass Akquise und Community-Aufbau als nächster nichttechnischer Themenblock vorgemerkt sind.

### 35.11 Unmittelbarer nächster Schritt

Der aktuelle Production-Stand ist funktional geprüft. Vor Öffnung für die Beta:
1. manuelles vollständiges Plesk-Backup erstellen;
2. Lockdown bewusst deaktivieren;
3. finaler Gast-/User-/Admin-Gegencheck;
4. Beta-Kommunikation freigeben.

Danach keine vorsorglichen Großumbauten mehr. Änderungen zunächst aus echten Fehlern, Feedback und beobachteter Nutzung ableiten.


## 36. Fortschreibung - Beta-Nachpflege, Import-Reviews, Statistik und Lokalisierung vom 2026-10-03

### 36.1 Importstatus und Review-Signale

Für Niedersachsen, NRW und Bayern gilt bei vollständigen Quell-Snapshots jetzt eine bewusst konservative Statuslogik:

- Ein in der Quelle gelisteter Datensatz kann einen Camperwolf-Platz mit `opening_status=unclear` auf `open` ergänzen.
- Verschwindet ein bereits verknüpfter Datensatz aus einem vollständigen Snapshot, wird `source_missing` erzeugt. Der Camperwolf-Platz wird **nicht** automatisch geschlossen.
- Meldet die Quelle einen Platz als offen, während Camperwolf ihn als `temporarily_closed`, `seasonally_closed` oder `permanently_closed` führt, wird `possible_reopen` erzeugt. Eine Wiedereröffnung erfolgt nur manuell.
- Nach manueller Prüfung kann `possible_reopen` entweder den Platz auf `open` setzen oder als geprüft aufgelöst werden.
- Wiederkehrende identische Signale werden nicht bei jedem Sync neu erzeugt; relevante spätere Zustandsänderungen können einen neuen Review rechtfertigen.

Niedersachsen verwendet zusätzlich die V1-Annahme "wenn im aktuellen Quellbestand gelistet, dann in Betrieb". Der Nutzer will beim nächsten regulären Sync ausdrücklich kontrollieren, ob bestehende unklare Stati wie erwartet aufgefüllt werden.

### 36.2 Kontextbezogene Import-Review-Werkzeuge

Die Review-Queue zeigt nicht mehr nur generische Aktionen:

- `missing_coordinates`: eigene Kartenansicht, Photon-Adresssuche, Kartenklick und verschiebbarer Marker; Koordinaten können gespeichert und anschließend erneut gegen Dubletten geprüft werden.
- `possible_duplicate`: Einzelreview-Karte mit externem Punkt und vorhandenen Kandidaten; bestehender Platz kann verknüpft oder ein neuer Camperwolf-Platz erzeugt werden.
- `possible_reopen`: Link zum verknüpften Platz sowie explizite Aktionen "auf In Betrieb setzen" oder geschlossen lassen/als geprüft auflösen.
- `source_missing`: bleibt ein rein manueller Prüfhinweis ohne automatische Statusänderung.
- Kontexttypen `source_missing` und `possible_reopen` sind von generischen Bulk-Aktionen ausgeschlossen.

Migration `2026_10_03_131500_add_external_record_manual_overrides_and_reopen_coordinate_reviews.php` ergänzt `external_records.manual_overrides` und öffnet alte gelöste `missing_coordinates`-Reviews einmalig erneut, wenn der aktive unverkettete Datensatz damals nur ignoriert wurde.

Manuelle Koordinaten werden als Override mit `coordinate_source=manual-review` gespeichert. Sie füllen nur fehlende Quellkoordinaten. Liefert die Quelle später selbst gültige Koordinaten, haben diese Vorrang.

Der Nutzer hat diesen Workflow auf Production praktisch geprüft: alte Fälle erschienen wieder, Koordinatenergänzung funktionierte, und Fälle wurden danach korrekt als Dubletten-/Kandidatenfälle neu einsortiert. Zuletzt waren noch 55 Koordinatenfälle für spätere manuelle Recherche offen.

### 36.3 Statistik und Bot-Traffic

Die interne Nutzungsstatistik klassifiziert Gastzugriffe als `human`, `bot` oder unklassifiziert, ohne vollständigen User-Agent, IP, Session-ID oder Fingerprint als Analysedatum zu speichern.

Ein Performanceproblem in `StatisticsController::activitySeries()` wurde am 2026-10-03 behoben: Zuvor wurden für jede Aktivitätstabelle sämtliche passenden Zeitstempel nach PHP geladen und dort gruppiert. Das war insbesondere bei stark wachsendem `usage_events` unnötig teuer und passte zum sporadischen 500/503-Verhalten der Statistikseite. Die Zeitreihe wird jetzt direkt in SQL nach Tag bzw. Monat aggregiert. `UsageAnalyticsTest` lief lokal grün; danach war der Fehler für den Nutzer nicht mehr reproduzierbar.

Auffällig hoher Bot-Traffic auf `support.report` ließ sich durch die starke globale Verlinkung des Report-Einstiegs und viele Query-Varianten erklären. Es wurden daher zusätzlich umgesetzt:
- `X-Robots-Tag: noindex, nofollow, noarchive` für `/support/report` und `/support/datenschutz-recht`;
- `Disallow` für beide Pfade in `public/robots.txt`;
- Regressionstest in `SecurityHardeningTest`.

Der fokussierte Security-Test lief beim Nutzer grün. Historische Statistikwerte bleiben unverändert; der neue Crawl-Schutz wirkt nur für zukünftige kooperative Crawler.

### 36.4 GPS-/Nearby-Suche

Die Standortfunktion ist produktiv:

- Browser-Geolocation nur nach expliziter Nutzeraktion.
- Eigene Koordinaten bleiben im Browser und werden nicht als Standort an Camperwolf gesendet oder dem Konto zugeordnet.
- Der Server liefert bei `nearby_candidates=1` alle Kandidaten der aktuell gesetzten fachlichen Filter ohne Kartenausschnittbegrenzung.
- Der Browser berechnet per Haversine die drei nächsten Treffer und passt die Karte auf eigenen Standort plus diese Treffer an.
- Datenschutzerklärung und Beta-Devlog wurden entsprechend aktualisiert.
- Desktop und Mobil wurden praktisch als funktionierend bestätigt.

### 36.5 Kontaktfelder und E-Mail-Schutz

Platz-Basisdaten unterstützen Telefon, E-Mail und Adresszusatz im Änderungs-/Direktbearbeitungsworkflow. Geschäftliche Kontaktdaten dürfen aus offiziellen Betreiber-/Platzseiten übernommen werden, wenn sie dort als Kontakt für Gäste/Geschäftszwecke veröffentlicht sind.

Öffentliche E-Mail-Adressen werden nicht direkt in das initiale HTML geschrieben, sondern über `places/{slug}/contact/email` lazy nachgeladen. Der Endpunkt ist mit `throttle:30,1` gedrosselt und sendet `no-store`.

### 36.6 Lokalisierungsinventur und großer Übersetzungs-Pass

Ein erneuter Repo-Scan zeigte trotz früherer DE/EN-Arbeit noch reale statisch deutsche Reststellen. Daraufhin wurde auf `main` ein zusammenhängender Lokalisierungs-Pass umgesetzt. Die Commitserie reicht von den öffentlichen UI-Übersetzungen ab `0d9e564a...` bis zum aktuellen Stand `b608f2fe...`.

Bearbeitet wurden insbesondere:
- öffentliche harte Texte und Inline-DE/EN-Ternaries in Navigation, Platzanlage und Profilbereichen;
- Datenexport-`README.txt` abhängig von der Nutzersprache;
- XP-Feldlabels und persistierte/angezeigte XP-Beschreibungen;
- Admin-Import-Center einschließlich struktureller Labels, Review-Aktionen, Karten-/JS-Texte und Validierungsmeldungen;
- `ImportCenterController`-Feedback, Bulk-Limits und generierte Review-Hinweise;
- DATEX-/CSV-/Recherche-Importfehler, soweit sie in interaktive Workflows gelangen;
- öffentliche Place-History-/Import-/Recherche-Zusammenfassungen mit sprachabhängiger Darstellung.

Bewusst nicht jeder deutsche String im Repository ist automatisch ein UI-Fehler. Weiterhin vorhandene Kandidaten müssen nach ihrer Sichtbarkeit bewertet werden:
- local/testing-only Demo-/Performance-/Reset-Exceptions;
- CLI-/Artisan-Texte;
- interne `internal_comment`-/Audit-Rohwerte;
- historische Migrationen/Testdaten;
- deutsche Referenznamen in Seedern, wenn die englische UI bewusst technische Slugs/Fallbacks zeigt.

Noch nicht als abgeschlossen markieren: Der große Lokalisierungs-Pass wurde nach der Commitserie noch nicht vom Nutzer als Gesamtblock getestet. Vor Deployment zuerst Tests und erneuten Restscan durchführen.

### 36.7 Veraltete Aussagen aus älteren Abschnitten

Folgende ältere Konzepte gelten nicht mehr als aktueller Arbeitsstand:

- "Lockdown bleibt bis zur Beta aktiv" ist überholt; die öffentliche Beta läuft.
- "NRW/Bayern/Niedersachsen nur lokal bzw. noch nicht produktiv" ist überholt; alle drei Quellen sind produktiv.
- "Vollständige DE/EN-Oberfläche" war als absolute Aussage zu stark; der Scan vom 2026-10-03 hat reale Reststellen gezeigt. Nach dem neuen Lokalisierungs-Pass ist erneut zu testen statt Vollständigkeit anzunehmen.
- Hinweise, dass Roadmap-9-Securitytests noch ausstehen, sind historisch; die späteren Abschnitte dokumentieren den abgeschlossenen/grünen Stand.
- Alte Pre-Beta-Hinweise wie "erst Backup, dann Lockdown aufheben" sind als Ablaufhistorie zu verstehen, nicht als nächste Aufgabe.
- `SupportContentSeeder` darf nicht als normaler Deployment-Schritt verstanden werden. Er ist nur gezielt auszuführen, weil seine `updateOrInsert`-Logik redaktionelle Änderungen überschreiben kann.

### 36.8 Nächster konkreter Arbeitsblock - historischer Stand

In der nächsten Session soll **keine neue Funktion** begonnen werden. Zuerst wird die Lokalisierungsarbeit abgeschlossen:

1. aktuellen `main` ziehen;
2. relevante Locale-, Import-, XP-, Export- und History-Tests ausführen und bei Bedarf die vollständige Testsuite ergänzen;
3. Repo erneut nach hart codierten deutschen UI-Strings durchsuchen;
4. verbleibende Treffer in "öffentlich sichtbar", "Admin sichtbar" und "nur intern/CLI/Testdaten" einordnen;
5. echte sichtbare Rest-Leaks korrigieren;
6. anschließend englische Oberfläche stichprobenartig in den normalen Hauptwegen prüfen und bei grünem Stand deployen.

Parallel nur beobachten:
- nächster Niedersachsen-Sync: `unclear -> open` und `possible_reopen` kontrollieren;
- Statistik: prüfen, ob 500/503 wegbleibt;
- Botzugriffe auf `support.report`: prüfen, ob neue Zugriffe nach robots/noindex deutlich abflachen.




## 37. Fortschreibung - RVR, Crawler-Schutz, XP, Onboarding und Userverwaltung vom 2026-10-03

Dieser Abschnitt ist für den aktuellen Stand nach der Beta-Nachpflege am Abend des 2026-10-03 maßgeblich. Er ersetzt ältere Aussagen, wonach RVR noch nicht produktiv sei, direkte Admin-Bearbeitungen keine XP erhalten, facettierte Dashboard-URLs frei gecrawlt werden oder die Userverwaltung noch keine Aktivitäts-/Profilübersicht besitzt.

### 37.1 RVR POI Camping - produktiv

- Neue produktive Quelle: rvr-poi-camping / "RVR POI - Camping", Provider Regionalverband Ruhr.
- WFS poi_einfach mit den relevanten Kategorien Campingplätze, Dauercampingplätze, Wohnmobilstellplätze und Jugendzeltplätze.
- Sync täglich 03:45 Uhr, ohne Überlappung.
- Finale Typ-Priorität: Campingplatz/Dauercampingplatz -> campground; sonst Wohnmobilstellplatz -> motorhome-pitch; sonst Jugendzeltplatz -> tent-site.
- Quellidentität über poi_id, ersatzweise gid:<gid>; Kategorien derselben POI-Identität werden kombiniert.
- Aktuell gelistete Records werden als opening_status=open behandelt. Vollständige Snapshots nutzen dieselbe source_missing-/possible_reopen-Logik wie die anderen produktiven Quellen.
- Produktiver Lauf: 263 relevante eindeutige/importierbare Records, 196 new_candidate, 67 possible_duplicate, 0 needs_review.
- ExternalRecordStagingService::completeSnapshot() normalisiert externe IDs vor whereNotIn konsequent zu Strings, damit gemischte numerische IDs und gid:-IDs nicht zu MySQL-Typkonvertierungsfehlern führen.
- ExternalDuplicateGroupService bildet external_duplicate_group nur noch, wenn mindestens zwei unterschiedliche externe Quellen beteiligt sind. Gleichnamige nahe Records derselben Quelle allein werden nicht mehr fälschlich gruppiert.

### 37.2 XP bei direkten strukturierten Bearbeitungen

PR #25 schloss die XP-Lücke im direkten Admin-/System-Owner-Weg für Merkmale, Öffnungszeiten und Preise.

- Merkmale: XP einmal pro Nutzer/Platz/Merkmal.
- Öffnungszeiten: XP nur bei inhaltlich bekanntem Zeitplan, nicht bei rein unknown.
- Preise: XP pro konkretem Offer; wiederholtes Speichern desselben Offers vergibt nicht erneut XP.
- Reale Ziel-Record-IDs werden als Source-IDs verwendet; Badge-Fortschritt läuft parallel.
- Allgemeine Platz-Basisdaten waren bereits korrekt, weil privilegierte Bearbeiter dort intern ebenfalls einen Change Request erzeugen und direkt anwenden.
- Import-Administration bleibt bewusst ohne XP. Das Freigeben/Erzeugen von Plätzen aus External Records ruft weder XpService noch BadgeService auf. Externe Quelldaten sollen nicht zum XP-Farming für Admins führen.

### 37.3 Crawler-Schutz für facettierte Suche

Am 2026-10-03 zeigte das aktuelle Production-access_ssl_log sehr starken GPTBot-Traffic. In der untersuchten Datei waren etwa 49.125 von 59.833 Requests GPTBot; das Muster bestand vor allem aus immer neuen /dashboard?...-Filterkombinationen.

Produktentscheidung:
- Öffentliche Platzdaten dürfen und sollen crawlbar bleiben.
- Platzprofile /places/... und sitemap.xml bleiben crawlbar.
- Das ungefilterte /dashboard bleibt crawlbar.
- Facettierte/parametrisierte Dashboard-Zustände /dashboard?... sollen nicht als praktisch unendlicher Crawl-Baum abgearbeitet werden.

PR #26:
- robots.txt enthält Disallow: /dashboard?.
- Eigener Middleware-Schutz LimitFilteredBrowse.
- Nur Requests auf /dashboard mit nichtleerem Query-String werden begrenzt.
- Default CAMPERWOLF_FILTERED_BROWSE_PER_MINUTE=60 pro IP.
- Ungefiltertes Dashboard ist von diesem speziellen Limiter nicht betroffen.
- Bei 429 wird direkt ein rate_limited-Security-Event mit Limiter filtered-browse geschrieben.
- Keine User-Agent-Erkennung - der Schutz ist verhaltensbasiert und greift auch bei Scrapern mit gefälschtem Browser-UA.
- Fokussierte Security-/Abuse-Tests waren lokal grün; die produktive robots.txt-Ausgabe mit Disallow: /dashboard? wurde geprüft.
- Die tatsächliche Abflachung des Bot-Traffics soll weiter beobachtet werden.

### 37.4 Welcome-Benachrichtigung und Profil-Onboarding

Beobachtung aus der Beta: die ersten neuen Nutzer nutzten den optionalen Anzeigenamen praktisch nicht. Statt einen neuen Pflichtmechanismus einzuführen wurde die bestehende Welcome-Benachrichtigung verbessert.

PR #27:
- alter Planungstext zum "späteren Tutorial" entfernt;
- neuer DE-Text: "Schön, dass du dabei bist. Prüfe deine Profileinstellungen, um deine Angaben bei Bedarf zu vervollständigen. Dort kannst du auch ein Profilbild hochladen und deinen Anzeigenamen ändern.";
- englische Entsprechung ergänzt;
- Welcome-Benachrichtigung verlinkt auf community-profile.edit.

Bestehende Welcome-Benachrichtigungen werden nicht rückwirkend verändert, weil Titel/Text/URL bei Anlage in user_notifications gespeichert werden. Für bereits registrierte Beta-Nutzer kann der vorhandene Admin-Bereich "Systemnachricht erstellen" genutzt werden. Eine normale Systemnachricht mit user_id = null erreicht alle zum Veröffentlichungszeitpunkt registrierten Nutzer und respektiert bei normaler Priorität die allgemeinen Notification-Einstellungen.

### 37.5 Userverwaltung im Adminbereich

PR #28 erweitert die Benutzerverwaltung:
- kleine Profilbilddarstellung ganz links in der Userliste;
- keine zusätzliche Thumbnail-Datei - dieselbe gespeicherte Profilbilddatei wird nur per CSS klein dargestellt;
- Initialen-Fallback ohne Profilbild;
- last_seen_at als Spalte "Zuletzt aktiv";
- größeres Profilbild in den Userdetails;
- eigener geschützter Admin-Endpunkt für Profilbilder, damit berechtigte Userverwaltung nicht an der öffentlichen Profilbild-Sichtbarkeit hängt;
- Sortierung nach Name, E-Mail, Status, Rolle, Zuletzt aktiv und Erstellt;
- Sortierzustand bleibt bei Filtern erhalten.

PR #29 ergänzt:
- eigene sortierbare Spalte "Anzeigename";
- zeigt public_alias, wenn gesetzt;
- sonst permanente CW-ID aus public_handle;
- nur bei unvollständigen Legacy-Daten Fallback auf Kontoname.

Die zugehörigen Tests in AdminUserPresentationTest wurden lokal grün bestätigt; Profilbildgröße und Darstellung in der Liste wurden im Browser als passend abgenommen.

### 37.6 GitHub-/Merge-Stand dieser Nachpflege

- PR #25 - direkte strukturierte Bearbeitungen erhalten XP.
- PR #26 - facettierte Dashboard-Crawler begrenzen.
- PR #27 - Welcome-Benachrichtigung auf Profilvervollständigung ausrichten.
- PR #28 - Aktivität, Profilbilder und Sortierung in der Admin-Userverwaltung.
- PR #29 - Anzeigename/CW-ID als zusätzliche sortierbare Spalte.
- PR #29 war der erste PR, den der Nutzer selbst über die GitHub-Oberfläche gemerged hat. Das ist ausdrücklich ein zulässiger Fallback, wenn der GitHub-Connector den Merge nicht ausführen kann.
- GitHub-main enthält damit den aktuellen Stand bis einschließlich PR #29. Für die zuletzt gemergten Admin-/Onboarding-Änderungen wurde in dieser Session kein abschließender Production-Deploy ausdrücklich bestätigt; bei Bedarf Serverstand vor weiteren Deployments prüfen.

### 37.7 Überholte Aussagen und aktueller nächster Fokus

Überholt bzw. nur noch historisch:
- "RVR noch nicht produktiv" - RVR POI Camping läuft produktiv.
- "direkte Admin-Bearbeitungen von Merkmalen/Öffnungszeiten/Preisen vergeben keine XP" - durch PR #25 behoben.
- "Dashboard-Filterzustände können frei von Crawlern durchlaufen werden" - durch robots.txt plus LimitFilteredBrowse begrenzt.
- "Userverwaltung zeigt nur Basisdaten" - inzwischen mit Profilbild, Zuletzt aktiv, Sortierung und Anzeigename erweitert.
- Abschnitt 36.8 beschreibt den damaligen nächsten Lokalisierungsblock und ist nicht mehr der aktuelle Arbeitsplan.

Aktueller Fokus:
1. Keine vorsorglichen Großumbauten beginnen; die öffentliche Beta soll echte Nutzung und konkrete Schwachstellen liefern.
2. Neuesten main-Stand bei Bedarf auf Production deployen und danach Userverwaltung/Welcome-Link kurz produktiv gegenprüfen.
3. Bot-/Crawler-Volumen nach robots.txt plus LimitFilteredBrowse beobachten.
4. RVR-/andere Import-Reviews nach Bedarf weiter abarbeiten.
5. Beim normalen Beta-Betrieb restliche harte Übersetzungen, UX-Kanten und fehlende Hilfetexte gezielt korrigieren.
6. Marketing/Betatester-Akquise bleibt als eigener nichttechnischer Block auf der Agenda.
