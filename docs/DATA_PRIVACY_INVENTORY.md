# Camperwolf – Datenschutz-Dateninventar

Stand: 2026-09-23

Dieses Dokument beschreibt den **technisch tatsächlich vorhandenen Datenbestand** von Camperwolf. Es ist die Grundlage für Roadmap-Punkt 10 (Datenschutz, Rechtstexte, Auskunft/Löschung und Betriebsprozesse).

Es trifft noch keine endgültige rechtliche Bewertung und legt noch keine Löschfristen fest. Wo Produktentscheidungen nötig sind, sind sie ausdrücklich als offen markiert.

## 1. Grundsätze

Für die weitere Datenschutzarbeit werden Daten in drei Gruppen betrachtet:

1. **Direkt personenbezogen** – identifiziert einen Nutzer direkt oder ist unmittelbar seinem Konto zugeordnet.
2. **Indirekt personenbeziehbar** – ist über User-ID, öffentliche CW-ID, IP-Hash, Beitragshistorie oder ähnliche Verknüpfungen einer Person zuordenbar.
3. **Fach-/Systemdaten** – grundsätzlich nicht personenbezogen, können aber durch Autoren-/Bearbeiterbezug personenbeziehbar werden.

## 2. Konto und Authentifizierung

### Tabellen / Daten

`users`
- ID
- Name
- E-Mail-Adresse
- E-Mail-Verifizierungszeitpunkt
- Passwort-Hash
- Remember-Token
- bevorzugte Sprache
- Profilfoto-ID
- `last_seen_at`
- Erstell-/Änderungszeitpunkte

`password_reset_tokens`
- E-Mail-Adresse
- Reset-Token
- Erstellzeitpunkt

`sessions`
- Session-ID
- optionale User-ID
- **rohe IP-Adresse**
- **User-Agent**
- Session-Payload
- letzte Aktivität

### Zweck
- Registrierung, Login, Sessionbetrieb
- E-Mail-Verifizierung / Passwort-Reset
- Spracheinstellungen
- technische Sessionverwaltung
- letzte Aktivität für Benachrichtigungs-/Nutzungslogik

### Datenschutz-Relevanz
Direkt personenbezogen.

### Offene Entscheidungen
- Aufbewahrungsdauer alter Sessions festlegen.
- Prüfen, ob `last_seen_at` wirklich dauerhaft benötigt wird oder nur begrenzt.
- Rohe Session-IP ist technisch Laravel-Standard, sollte aber eine klare Aufbewahrung erhalten.

## 3. Community-Profil

### `user_profiles`
- User-ID
- dauerhafte öffentliche CW-ID (`public_handle`)
- optionaler öffentlicher Alias
- Zeitpunkt der Alias-Finalisierung
- Bio
- Heimatort Stadt/Land
- Heimatort Koordinaten
- Geocoder-Quell-ID
- Geburtsdatum
- Geschlecht
- frei eingegebenes Geschlecht
- Fahrzeugtyp
- Fahrzeugdetails
- ausgewählter Badge
- Zeitstempel

### `user_profile_social_links`
- User-ID
- Plattform
- URL
- Sortierung

### `user_settings`
- Sichtbarkeit echter Name
- Reviews/Fotos/Beitrittsdatum/Aktivitätszahlen im Profil
- E-Mail-Benachrichtigungen
- Sichtbarkeit von Profilfoto, Bio, Heimatort, Alter, Geschlecht, Fahrzeug und Social Links

### Zweck
- öffentliches bzw. abgestuft sichtbares Community-Profil
- Wiedererkennung über permanente CW-ID
- freiwillige Selbstdarstellung

### Datenschutz-Relevanz
Direkt personenbezogen; Geburtsdatum, Geschlecht, Heimatort und Social Links sind besonders privatsphärenrelevant.

### Bestehende Schutzmaßnahmen
- Sichtbarkeiten pro Profilbereich
- Alter standardmäßig privat
- Geschlecht standardmäßig privat
- Heimatort standardmäßig nur für registrierte Nutzer
- echter Name standardmäßig nicht öffentlich

### Offene Entscheidungen
- Soll ein vollständiges **Geburtsdatum** überhaupt gespeichert werden oder genügt Geburtsjahr / Altersgruppe?
- Brauchen wir Geschlecht für Camperwolf fachlich überhaupt?
- Brauchen wir präzise Heimatort-Koordinaten oder reichen Stadt + Land?
- Soll die automatische CW-ID nach Accountlöschung erhalten/anonymisiert reserviert bleiben oder vollständig verschwinden?
- Was passiert mit einem finalisierten Alias nach Accountlöschung?

## 4. Einwilligungen / Zustimmungen

### `user_consents`
- User-ID
- Consent-Typ
- Version
- akzeptiert am
- widerrufen am

### Zweck
Technische Grundlage, um versionierte Zustimmungen nachweisbar zu speichern.

### Aktueller Befund
Die Tabelle existiert, aber der Audit hat **keinen bereits etablierten vollständigen Consent-Workflow** ergeben.

### Offene Entscheidungen
- Welche Dinge benötigen überhaupt explizite Zustimmung?
- Nutzungsbedingungen/Datenschutzbestätigung bei Registrierung versioniert protokollieren?
- E-Mail-Benachrichtigungen als Einstellung statt „Consent“ behandeln, soweit keine Werbung versendet wird.
- Separate Zustimmung für optionale Dienste nur dann einführen, wenn tatsächlich erforderlich.

## 5. Community-Beiträge und Platzdaten

### Personenbezug über Autoren-/Bearbeiterfelder
Unter anderem:
- `places.created_by`
- `places.approved_by`
- `change_requests.submitted_by`
- `change_requests.reviewed_by`
- diverse administrative / Moderationsfelder
- Audit-Log-User-ID

### Inhaltsdaten
Nutzer können unter anderem beitragen:
- neue Plätze
- Platzbeschreibungen
- Adressen/Kontakte
- Merkmale
- Öffnungszeiten
- Preise
- Freitextkommentare / Moderationshinweise

### Zweck
Community-Pflege des öffentlichen Platzverzeichnisses, Moderation und Nachvollziehbarkeit.

### Datenschutz-Relevanz
Die eigentlichen Platzdaten sind meist Sach-/Unternehmensdaten. Durch Autorenbezug, Kommentare und Audit-Historie werden sie jedoch teilweise personenbeziehbar.

### Offene Entscheidungen
Zentral für Account-Löschung:
- **Platzdaten selbst sollten nach Accountlöschung grundsätzlich bestehen bleiben**, weil sie Bestandteil des öffentlichen Datenbestands sind.
- Autoren-/Bearbeiterbezug könnte anonymisiert werden, statt den Platz zu löschen.
- Interne Kommentare müssen auf mögliche personenbezogene Freitextangaben geprüft werden.
- Audit-Historie braucht eine eigene Aufbewahrungs-/Anonymisierungsregel.

## 6. Reviews

### Aktuelles Review-System
`place_reviews`
- User-ID
- Place-ID
- Status
- aktuelle Version
- Verifizierungsstatus
- Zeitpunkte

`place_review_versions`
- fünf Bewertungsdimensionen
- Gesamtscore
- Review-Freitext
- Sichtbarkeit
- Gültigkeitszeitraum
- Versionshistorie

`place_review_reports`
- Melder
- gemeldete Review/Version
- Grund
- optionaler Kommentar
- Moderationsdaten

### Zweck
Bewertungen, Review-Historie und Moderation.

### Datenschutz-Relevanz
Direkt an Nutzerkonto/CW-ID gebundener User-generated Content; Freitext kann zusätzliche personenbezogene Angaben enthalten.

### Offene Entscheidung
Bei Accountlöschung gibt es drei denkbare Produktmodelle:

1. **Reviews löschen** – höchste Datenminimierung, aber Score/Historie verändert sich.
2. **Reviews anonymisiert erhalten** – Inhalt/Score bleibt, Autor wird z. B. „Gelöschter Nutzer“.
3. **Reviews historisch vollständig erhalten** – stärkste Nachvollziehbarkeit, aber größter Personenbezug.

Für Camperwolf erscheint technisch Modell 2 besonders passend, muss aber gemeinsam entschieden werden.

## 7. Fotos

### `photos`
- User-ID
- UUID
- Dateipfade
- MIME, Größe, Bildmaße
- Status
- Moderationsdaten
- Verarbeitungsfehler
- Zeitstempel

### weitere personenbezogene Relationen
- Helpful-Stimmen: User-ID + Foto
- Meldungen: Melder-ID, Grund, Kommentar
- Moderierende Nutzer
- Titelbild-/Thumbnail-Entscheidungen durch Admins

### Wichtige bestehende Datenschutzmaßnahme
Die Bildverarbeitung:
- richtet das Bild aus,
- konvertiert nach WebP,
- führt `stripImage()` aus,
- entfernt den ursprünglichen Dateinamen,
- löscht die temporäre Originaldatei.

Damit werden EXIF-/GPS- und sonstige Bildmetadaten aus den ausgelieferten Varianten entfernt.

### Offene Entscheidungen
- Was passiert mit Community-Fotos bei Accountlöschung?
  - löschen,
  - anonymisiert weiterverwenden,
  - nur dann erhalten, wenn sie an einen weiterbestehenden Review/Platzbeitrag gebunden sind?
- Meldungs-/Moderationshistorie unabhängig vom Bild ggf. anonymisiert erhalten.

## 8. Favoriten, Helpful-Stimmen und sonstige Interaktionen

Beispiele:
- Place-Favoriten
- Foto-Helpful-Stimmen
- Review-/sonstige Community-Interaktionen

### Zweck
Personalisierung und Community-Funktionen.

### Datenschutz-Relevanz
Nutzerbezogenes Nutzungsverhalten.

### Vorläufige Empfehlung für spätere Löschlogik
Bei Accountlöschung vollständig löschen, sofern keine zwingende fachliche Historie benötigt wird.

## 9. Benachrichtigungen

### `user_notifications`
- User-ID
- Typ/Priorität
- Titel/Nachricht
- Ziel-URL
- Metadaten
- Ersteller
- Verfügbarkeit/Ablauf

### `user_notification_reads`
- User-ID
- Notification-ID
- gelesen am

### `notification_events`
- User-ID
- Eventtyp
- Cluster-Key
- Nachricht
- optionale Place-ID
- Payload
- Zeitpunkte

### Zweck
In-App-Benachrichtigungen und Ereignis-Clustering.

### Datenschutz-Relevanz
Direkt nutzerbezogen; Inhalte können Rückschlüsse auf Aktivitäten und Moderationsvorgänge zulassen.

### Bestehender Prozess
Es existiert bereits `notifications:cleanup` für alte/gelesene bzw. veraltete unwichtige Benachrichtigungen.

### Offene Entscheidung
Konkrete verbindliche Retention in Datenschutz-/Betriebsdoku festhalten.

## 10. Support

### `support_tickets`
Für registrierte Nutzer:
- User-ID

Für Gäste:
- Name
- E-Mail
- optional Telefon

Zusätzlich:
- Betreff
- Beschreibung
- Modulinformation
- Route
- interne Quell-URL
- **User-Agent**
- Bearbeiter
- Status/Priorität
- Zeitpunkte

### `support_ticket_messages`
- Ticket
- optional User-ID
- Nachrichtentyp
- Freitext

### `support_ticket_events`
- Ticket
- optional User-ID
- Status-/Aktionshistorie
- Metadaten

### Zweck
Support, Bugmeldungen, Verbesserungsvorschläge und Datenprobleme.

### Datenschutz-Relevanz
Direkt personenbezogen; Freitext kann beliebige zusätzliche personenbezogene Angaben enthalten.

### Auffälligkeit
Der User-Agent wird bei jedem neu angelegten Support-Ticket dauerhaft im Ticket gespeichert.

### Offene Entscheidungen
- Brauchen wir den User-Agent dauerhaft oder z. B. nur für Bugtickets / begrenzte Zeit?
- Aufbewahrungsdauer geschlossener Supportfälle festlegen.
- Gastkontaktdaten nach Abschluss ggf. früher anonymisieren/löschen.

## 11. Audit- und Moderationshistorie

### `audit_logs`
- optionale User-ID
- Entity-Typ/-ID
- Aktion
- Quelle
- alte Werte
- neue Werte
- interner Kommentar
- Zeitpunkt

Zusätzlich gibt es Nutzerbezüge in:
- Change Requests
- Reports
- Moderationsentscheidungen
- Rollen-/Permission-Verwaltung
- manuellen Badge-Vergaben

### Zweck
Nachvollziehbarkeit von Änderungen, Schutz vor Manipulation, Moderation und Betrieb.

### Datenschutz-Relevanz
Indirekt bis direkt personenbezogen. `old_values` / `new_values` können außerdem fachliche oder personenbezogene Inhalte enthalten.

### Offene Entscheidung
Bei Accountlöschung sollte vermutlich **nicht die fachliche Historie zerstört**, sondern der direkte Nutzerbezug anonymisiert werden. Details müssen in 10.2 festgelegt werden.

## 12. Gamification

Nutzerbezogene Tabellen umfassen unter anderem:
- Badge-Fortschrittsereignisse
- Badge-Unlocks
- Aktivitätstage
- XP-/Level-bezogene Daten
- manuelle Vergabekommentare
- vergebender Admin

### Zweck
XP, Level, Badges, Achievements und sichtbarer Community-Fortschritt.

### Datenschutz-Relevanz
Profiling des Community-Verhaltens im rein spielerischen/produktinternen Sinn; direkt über User-ID zugeordnet.

### Offene Entscheidung
Bei Accountlöschung grundsätzlich löschbar. Historische manuelle Moderations-/Vergabeaktionen könnten ggf. anonymisiert verbleiben.

## 13. Security / Missbrauchsschutz

### `abuse_flags`
- optionale User-ID
- Entity-Bezug
- Regel/Schweregrad
- **nur gehashte Quell-IP**
- technischer Kontext
- Bearbeiter/Auflösung

### `security_events`
- optionale User-ID
- Route
- **nur gehashte Quell-IP**
- kleiner technischer Kontext
- Zeitpunkt

### Bestehende Schutzmaßnahmen
- keine rohe IP in diesen Tabellen
- Hash = SHA-256 aus IP + App-Key
- Security Events werden nach **90 Tagen** automatisch gelöscht

### Datenschutz-Relevanz
Pseudonymisierte technische Nutzungs-/Missbrauchsdaten.

### Offene Entscheidung
Auch für `abuse_flags` eine Retention festlegen, besonders für erledigte Flags.

## 14. Session-/Server-/Frameworkdaten

### Laravel Sessions
Wie oben beschrieben:
- rohe IP
- User-Agent
- Session-Payload

### Logs / Pulse / Telescope
- Pulse wird als Monitoring genutzt.
- Telescope ist standardmäßig deaktiviert und nur bei Diagnosebedarf vorgesehen.
- Anwendungs-/Webserverlogs können zusätzlich IP, Requestpfad, Fehlerdaten und User-Agent enthalten – deren konkrete Produktionskonfiguration steht erst bei Deployment fest.

### Offene Entscheidung
Retention und Datenschutzvorgaben müssen bei Roadmap-Punkt 11 passend zur tatsächlichen Serverkonfiguration festgelegt werden.

## 15. Externe Dienste / Datenübermittlung

Im aktuellen Produkt werden externe Karten-/Geocoding-Dienste verwendet. Der genaue technische Anbieter-/Requestpfad muss vor dem finalen Datenschutzerklärungstext noch gegen die tatsächliche Produktionskonfiguration geprüft werden.

Wichtig für 10.5:
- Kartenkacheln können bei direkter Browser-Einbindung die Besucher-IP an den Kartenanbieter übertragen.
- Browserseitiges Geocoding kann ebenfalls externe Requests des Nutzers erzeugen.
- Serverseitiges Geocoding würde dagegen primär die Server-IP übertragen.

Diese Punkte werden **nicht aus dem Datenbankschema allein abgeleitet** und müssen vor Go-live technisch verifiziert werden.

## 16. Vorläufige Datenklassen für Löschung/Auskunft

### A – bei Accountlöschung voraussichtlich vollständig löschen
- Passwort/Authentifizierungsdaten
- Sessions
- Profilfelder und Social Links
- Profilfoto
- Einstellungen
- Favoriten
- Helpful-Stimmen
- Benachrichtigungen
- Gamificationdaten
- aktive Reset-Tokens

### B – fachliche Inhalte vermutlich anonymisiert erhalten
- angelegte Plätze
- angenommene Platzänderungen
- Reviews – **Entscheidung noch offen**
- Fotos – **Entscheidung noch offen**
- Change Requests
- Audit-/Moderationshistorie

Direkter Autorbezug würde entfernt bzw. auf einen technischen „gelöschten Nutzer“-/Null-Bezug reduziert.

### C – zeitlich begrenzt erhalten
- Security Events: bereits 90 Tage
- Sessions: Frist offen
- erledigte Abuse Flags: Frist offen
- Supporttickets: Frist offen
- Logs/Pulse/Serverlogs: abhängig von Produktionskonfiguration

## 17. Entscheidungen für 10.2

Die wichtigsten Produktentscheidungen vor Implementierung der Account-Löschung sind:

1. **Reviews nach Accountlöschung:** löschen oder anonymisiert erhalten?
2. **Fotos nach Accountlöschung:** löschen oder anonymisiert erhalten?
3. **CW-ID / Alias:** vollständig freigeben, dauerhaft sperren oder anonymisierte Historie behalten?
4. **Geburtsdatum:** weiterhin vollständig speichern, reduzieren oder Profilfeld entfernen?
5. **Geschlecht:** fachlicher Nutzen vorhanden oder Feld entfernen?
6. **Heimatort:** präzise Koordinaten wirklich nötig?
7. **Support:** gewünschte Aufbewahrungsdauer nach Schließung?
8. **Audit/Change Requests:** direkter Nutzerbezug nach Löschung anonymisieren – fachliche Historie behalten?
9. **Abuse Flags:** Aufbewahrungsdauer nach Erledigung?
10. **Sessions/Logs:** konkrete Retention im späteren Produktionsbetrieb.

Diese Entscheidungen bilden den Eingang für Roadmap-Punkt 10.2.


## 18. Verbindliches V1-Löschmodell

Für Roadmap-Punkt 10.2 gilt:

- Ein Account wird nicht physisch aus `users` entfernt. Zur relationalen Integrität bleibt ein irreversibel anonymisierter Tombstone-Datensatz bestehen.
- Öffentliche CW-ID und Alias werden nicht weiterverwendet; öffentliche Profile verschwinden bereits während der Karenz.
- Persönliche Konto-/Profildaten werden beim endgültigen Abschluss entfernt.
- Alle vom Nutzer hochgeladenen Fotos werden physisch aus dem Storage entfernt; der Foto-Datensatz bleibt nur als inaktiver `deleted`-Tombstone bestehen.
- Rezensionstexte werden entfernt. Bewertungswerte bleiben ausschließlich für die historische Bewertungsentwicklung bestehen; der aktuelle Review wird beendet und zählt ab diesem Zeitpunkt nicht mehr zum aktuellen Score.
- Angelegte Plätze und beigetragene Platzdaten bleiben erhalten.
- Favoriten, Benachrichtigungen, Profil-/Social-Daten, Rollen/Overrides und Gamificationdaten werden entfernt.
- Helpful-Votes und fachlich notwendige historische Relationen dürfen auf den anonymisierten Tombstone zeigen.
- Supportdaten werden nicht pauschal zusammen mit dem Account gelöscht, aber vom Account entkoppelt. Die eigene Support-Retention wird separat festgelegt.
- Security-/Abuse-Daten werden vom direkten User-Bezug entkoppelt; ihre Retention richtet sich nach den jeweiligen Security-Regeln.

### Löschzeitpunkt

Nutzer können wählen zwischen:

1. **30 Tage Karenz**: Account sofort öffentlich ausgeblendet und funktional eingeschränkt. Während der Frist ist eine Reaktivierung möglich.
2. **Sofort endgültig**: Anonymisierung startet unmittelbar und ist nicht rückgängig zu machen.

Nach endgültiger Anonymisierung ist keine Reaktivierung mehr möglich.

Die Löschoberfläche muss vor Bestätigung transparent anzeigen:
- Anzahl aktiver Fotos;
- Anzahl aktiver Rezensionen;
- Anzahl angelegter Plätze;
- Anzahl eingereichter Änderungen;
- welche Daten entfernt werden;
- welche Community-Sachdaten erhalten bleiben;
- welche Folgen irreversibel sind.

### Nachfolgender Inhaltsregel-Block

Direkt nach 10.2 werden verbindliche Community-Inhaltsregeln für Fotos und Rezensionen definiert:
- zulässige/unzulässige Bildinhalte;
- Persönlichkeits- und Datenschutzbezug auf Fotos;
- zulässige/unzulässige Inhalte in Rezensionstexten;
- konkrete Moderations-/Ablehnungsgründe;
- verständliche Hinweise bereits beim Upload bzw. Schreiben.


## 19. Datenexport / Auskunft

Für V1 existiert ein sicherer Self-Service-Datenexport.

### Self-Service
- Erstellung asynchron über die Queue;
- höchstens ein laufender Export pro Nutzer;
- neuer Export frühestens alle 7 Tage;
- Download 72 Stunden verfügbar;
- private Storage-Ablage und eigentümergebundene Downloadroute;
- automatische stündliche Bereinigung abgelaufener Exporte;
- sofortige Löschung aller Exportdateien bei endgültiger Accountlöschung.

### Exportinhalt
Der Export verwendet eine bewusst gepflegte Whitelist und keine automatische Serialisierung des Datenbankschemas. Enthalten werden dem Nutzer direkt zuordenbare Konto-/Profildaten, eigene Community-Inhalte und Aktivitäten, soweit sie für den Self-Service sinnvoll und sicher bereitgestellt werden können.

Nicht automatisch exportiert werden insbesondere:
- Passwort-Hashes und Authentifizierungsgeheimnisse;
- Session-Payloads;
- interne Tabellen-/Spaltenstrukturen und Storagepfade;
- API-Schlüssel/Serverkonfiguration;
- interne Sicherheitsmechanismen;
- interne Administrator-IDs;
- nicht für den Nutzer bestimmte interne Moderationsnotizen oder Daten Dritter.

### Formelle Datenschutzanfragen
Das Self-Service-Limit ist kein Limit des gesetzlichen Auskunftsrechts. Formelle oder weitergehende Datenschutzanfragen können zusätzlich über Hilfe & Support gestellt und fallbezogen geprüft werden.
