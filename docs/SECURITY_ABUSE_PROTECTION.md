# Camperwolf Security & Abuse Protection

Stand: 2026-09-23

## Ziel

Camperwolf soll normale Besucher und Suchmaschinen nicht unnötig einschränken, gleichzeitig aber automatisierte Massenaktionen, Spam, Registrierungsfarmen und einfache Scraping-/DoS-Muster früh begrenzen.

Die Schutzstrategie ist mehrschichtig:

1. Laravel-Permissions und fachliche Regeln
2. zentrale Rate-Limits für schreibende Aktionen
3. zusätzliche IP-Grenzen bei besonders missbrauchsanfälligen Aktionen
4. E-Mail-Verifizierung für Community-Funktionen
5. später Cloudflare als äußere Schutzschicht vor dem Produktionsserver
6. Moderation/Audit als letzte, nicht erste Schutzebene

## Aktive Laravel-Limiter

### support-submit
- 2 pro Minute
- 5 pro Stunde
- 15 pro Tag
- Schlüssel: User-ID, bei Gästen IP
- Admin/System Owner: kein Community-Limit

### place-create
- 2 pro Minute
- neue Accounts (< 24h): 3 pro Stunde / 8 pro Tag
- ältere Accounts: 5 pro Stunde / 15 pro Tag
- zusätzlich maximal 30 Platzanlagen pro IP und Tag, um Account-Farmen zu bremsen
- Admin/System Owner: kein Community-Limit

### community-write
Für Platzinformationen, Öffnungszeiten, Preise, Merkmale, Draft-Featurepflege und ähnliche Schreibaktionen:
- 20 pro Minute
- 100 pro Stunde
- Admin/System Owner: kein Community-Limit

### review-write
- 5 pro Stunde
- 15 pro Tag
- fachliche Review-Cooldowns gelten zusätzlich
- Admin/System Owner: kein Community-Limit

### photo-upload
- 5 Upload-Requests pro Minute
- 15 pro Stunde
- 50 pro Tag
- bestehende Dateigrößen-, Pixel-, MIME- und Bildverarbeitungsprüfungen gelten zusätzlich
- Admin/System Owner: kein Community-Limit
- Admin-Fotos umgehen nur die Moderationswarteschlange, niemals die technische Bildprüfung

### report-create
Für Review-/Foto-Meldungen:
- 3 pro Minute
- 10 pro Stunde
- 30 pro Tag
- Admin/System Owner: kein Community-Limit

### support-reply
- 20 pro Stunde
- Admin/System Owner: kein Community-Limit

### engagement-write
Für kleine Community-Aktionen wie Helpful, Favoriten und ähnliche Interaktionen:
- 60 pro Minute
- 300 pro Stunde
- Admin/System Owner: kein Community-Limit

## Registrierung und Login

- Login: Fortify-Limit 5 Versuche pro Minute je Kombination aus Loginname/E-Mail und IP.
- Zwei-Faktor-Challenge: 5 Versuche pro Minute.
- Registrierung: 3 Konten pro Stunde und 10 Konten pro Tag je IP.
- Community-Schreibfunktionen liegen zusätzlich hinter `auth` + `verified`.

## Öffentliche Lesezugriffe / Suchmaschinen

Öffentliche Platzprofile, Review-Feeds, Review-Historien und Userprofile sind bereits an einen benannten `public-read`-Limiter angebunden.

Dieser ist absichtlich standardmäßig deaktiviert:

```env
CAMPERWOLF_PUBLIC_READ_LIMIT_ENABLED=false
CAMPERWOLF_PUBLIC_READ_PER_MINUTE=180
```

Damit werden während Entwicklung und beim initialen Launch keine normalen Besucher oder Suchmaschinen eingeschränkt.

Der serverseitige `public-read`-Limiter ist nur ein Notfall-Fallback. Der reguläre Scraping-/Bot-Schutz soll später am Edge über Cloudflare erfolgen, weil dort verifizierte Suchmaschinen von verdächtigen Bots unterschieden werden können.

## Cloudflare – erst beim Server-Go-live

Cloudflare wird nicht während der lokalen Entwicklung benötigt.

Vor Aktivierung auf dem Produktionsserver prüfen:

- DNS-Einträge vollständig übernehmen
- Website, Mail und Subdomains prüfen
- Free-Plan verwenden; keine kostenpflichtigen Add-ons ohne bewusste Entscheidung
- normale Besucher ohne globale Challenge passieren lassen
- Verified Bots / Suchmaschinen nicht blockieren
- Bot-/WAF-Regeln zunächst konservativ einstellen
- aggressive Challenge-/Under-Attack-Modi nur bei konkretem Anlass
- Origin nach Möglichkeit gegen direkten externen Zugriff absichern

### Kritisch: echte Client-IP

Sobald Camperwolf hinter Cloudflare läuft, muss Laravel die echte Besucher-IP zuverlässig erhalten und nur vertrauenswürdigen Proxy-Headern glauben.

Das ist für folgende Schutzmechanismen wichtig:

- Registrierungs-Limits
- Gast-Support
- IP-Zusatzlimit bei Platzanlage
- optionaler Public-Read-Fallback
- spätere Abuse-Auswertung

Die endgültige Trusted-Proxy-Konfiguration wird erst beim Server-/Cloudflare-Go-live gesetzt, weil sie zur realen Deployment-Topologie passen muss. Nicht pauschal alle Forwarded-Header aus beliebigen direkten Requests vertrauen.

## Grundsatz

Rate-Limits sollen normale Menschen praktisch nie treffen. Sie sind eine technische Bremse gegen automatisierte oder fehlerhafte Massenaktionen.

Cloudflare ergänzt diesen Schutz später vor Laravel; es ersetzt die Laravel-Regeln nicht.


## Regelbasierte Abuse-Quarantäne

Für neue Platzvorschläge existiert zusätzlich zu Rate-Limits eine transparente regelbasierte Quarantäne.

Aktive V1-Regeln:

- `strong_nearby_duplicate`: gleicher normalisierter Platzname innerhalb von 75 Metern zu einem bereits veröffentlichten oder offenen Platz;
- `new_account_place_burst`: Account jünger als 24 Stunden mit mindestens drei offenen Platzvorschlägen innerhalb einer Stunde;
- `high_pending_place_volume`: mindestens acht offene Platzvorschläge desselben Accounts innerhalb von 24 Stunden.

Eine Regel löscht oder verwirft niemals automatisch Daten. Sie erzeugt einen Eintrag in `abuse_flags` und trennt den Vorschlag aus der normalen Moderationswarteschlange in die Quarantäne.

Moderatoren sehen den konkreten Auslöser und können den Vorschlag weiterhin normal genehmigen oder ablehnen. Beim Abschluss der Publikationsanfrage werden offene Abuse-Flags des Platzes als erledigt markiert.

Admin/System Owner umgehen die automatische Quarantäne im normalen privilegierten Modus. Die Rollen-Vorschau respektiert dagegen die simulierte Rolle und hebt den Abuse-/Rate-Limit-Schutz nicht auf.

Die Quarantäne speichert für Korrelationszwecke nur einen mit dem App-Key gesalzenen SHA-256-Hash der Quell-IP, nicht die rohe IP-Adresse.

## Replay-Schutz bei Platzanlage

Identische Platz-POSTs desselben Nutzers werden innerhalb von zehn Minuten dedupliziert, wenn Name, Platztyp und Position identisch sind und bereits ein eigener Entwurf oder offener Vorschlag existiert.

Damit erzeugen Doppelklicks, Browser-Retries oder einfache Replay-Skripte keinen zweiten Moderationsdatensatz. Admin/System Owner sind von diesem fachlichen Replay-Schutz ausgenommen, weil ihre Direktpflege bewusst nicht durch Community-Schutzregeln eingeschränkt wird.

## Bereits vorhandene Deduplizierung

- Review-Meldungen: Unique-Constraint je Review-Version und Melder plus `insertOrIgnore`.
- Foto-Meldungen: Unique-Constraint je Foto und Melder plus `insertOrIgnore`.
- Review selbst: Unique-Constraint je Platz und Nutzer.
- Feature-Vorschläge desselben Users können bestehende offene eigene Vorschläge ersetzen/`superseded` markieren.

Diese vorhandenen Mechanismen werden durch die Abuse-Quarantäne ergänzt, nicht ersetzt.


## Öffentliche Browse-Härtung

Die öffentliche Suche/Filterung besitzt einen eigenen Middleware-Schutz gegen absichtlich übergroße oder tief verschachtelte Query-Strings.

Aktuelle Grenzen:

- maximal 8 KiB Query-String;
- maximal 120 Leaf-Werte in der Query-Struktur;
- maximal 5 Ebenen Query-Verschachtelung;
- Freitextsuche `q` maximal 120 Zeichen.

Normale Such- und Filteranfragen bleiben unverändert. Übermäßig große oder strukturell auffällige Requests werden vor den teureren Datenbankabfragen abgewiesen.

Gefilterte Dashboard-Aufrufe besitzen zusätzlich einen eigenen IP-basierten `filtered-browse`-Limiter. Er greift nur, wenn `/dashboard` Query-Parameter enthält, standardmäßig mit 60 Requests pro Minute. Das ungefilterte `/dashboard`, Platzprofile und die Sitemap werden davon nicht begrenzt. Überschreitungen liefern 429 und werden über das bestehende Security-Monitoring als `rate_limited` erfasst. Der Grenzwert kann über `CAMPERWOLF_FILTERED_BROWSE_PER_MINUTE` angepasst werden.

`robots.txt` ergänzt diese technische Bremse für kooperative Crawler: `/dashboard` bleibt crawlbar, `/dashboard?...` wird ausgeschlossen. Öffentliche Platzprofile bleiben crawlbar und über die Sitemap vollständig auffindbar.

Der vorhandene benannte `public-read`-Limiter hängt zusätzlich an Startseite und Dashboard. Er bleibt standardmäßig deaktiviert und kann bei einem akuten allgemeinen Scraping-/Lastproblem serverseitig als Notfallbremse aktiviert werden. Regulärer Bot-/Scraping-Schutz kann später zusätzlich am Edge erfolgen.

## Auth-Härtung

Zusätzlich zu Fortifys bestehendem Login-/2FA-Schutz und Laravels Passwort-Reset-Broker-Throttle gilt:

- POST `/forgot-password`: maximal 20 Reset-Link-Anfragen pro Stunde je IP.

Damit kann ein einzelner Client nicht über viele verschiedene Zieladressen massenhaft Reset-Mails auslösen.

## Security Header

Web-Antworten erhalten standardmäßig:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=(self), payment=()`
- `X-Powered-By` wird aus der Application-Response entfernt, sofern vorhanden.
- In Produktion über HTTPS zusätzlich HSTS für ein Jahr inklusive Subdomains.

Private Oberflächen wie Admin, Login, Registrierung, Passwort-Reset, Settings, Notifications und eigene Support-/Foto-Bereiche erhalten zusätzlich `X-Robots-Tag: noindex, nofollow, noarchive`.

`public/robots.txt` weist kooperative Crawler ebenfalls an, diese privaten Bereiche nicht zu crawlen. Öffentliche Platzsuche und Platzprofile bleiben absichtlich crawlbar.

Ein strikter Content-Security-Policy-Header ist aktuell bewusst nicht global aktiviert, weil die bestehende Livewire/Flux/Map-JavaScript-Struktur vorher separat CSP-kompatibel geprüft werden müsste. Ein ungetesteter CSP-Rollout könnte reguläre Funktionen brechen.


## Security Monitoring

Der Bereich **Administration → System & Debug** zeigt eine kompakte Security-/Abuse-Übersicht:

- offene Quarantänefälle;
- Abuse-Flags der letzten 24 Stunden;
- 429-Antworten der letzten 24 Stunden;
- abgewiesene Browse-Queries der letzten 24 Stunden;
- blockierte Registrierungsversuche der letzten 24 Stunden;
- die letzten 20 technischen Security-Ereignisse.

Aufgezeichnete Security-Ereignisse enthalten nur:

- Ereignistyp;
- optional User-ID;
- Route;
- gesalzenen SHA-256-Hash der Quell-IP;
- kleine technische Metadaten wie HTTP-Methode/Grund;
- Zeitstempel.

Request-Inhalte, Passwörter, E-Mail-Adressen aus Requests und rohe IP-Adressen werden nicht in `security_events` gespeichert.

Ereignisse älter als 90 Tage werden täglich über `security:cleanup` entfernt. Der Scheduler führt dies standardmäßig um 03:50 Uhr aus.

System Owner und Administratoren können System & Debug über `admin.access` verwenden. Die Rollen-Vorschau bleibt wirksam.

## V1-Abgrenzung

Die Anwendung selbst protokolliert und begrenzt fachlichen Missbrauch. Sie versucht bewusst nicht, einen vollständigen Netzwerk-/DDoS-/Bot-Schutz nachzubauen.

Beim Produktions-Go-live bleiben deshalb insbesondere diese Infrastrukturaufgaben offen:

- Cloudflare Free vor den Origin schalten;
- echte Client-IP/Trusted Proxies korrekt konfigurieren;
- Verified Search Bots zulassen;
- konservative WAF/Bot-Regeln aktivieren;
- direkten Origin-Zugriff soweit sinnvoll einschränken;
- Scheduler und Pulse dauerhaft betreiben/überwachen.

Damit ist der Laravel-seitige V1-Sicherheitsblock abgeschlossen.
