# Camperwolf Performance-Testdaten

Stand: 2026-09-22

Dieser Datensatz ist ausschließlich für lokale Performance- und Querytests gedacht. Er ist bewusst von den normalen Demo-Daten getrennt und darf nur in den Umgebungen `local` und `testing` erzeugt oder entfernt werden.

## Datensatz erzeugen

Unterstützte Größen:

```bash
php artisan camperwolf:performance-seed 10000
php artisan camperwolf:performance-seed 25000
php artisan camperwolf:performance-seed 50000
```

Wenn bereits Performance-Daten existieren, bricht der Befehl ab. Dadurch werden versehentliche Doppelbestände vermieden.

Der Generator erzeugt in reproduzierbarer Verteilung unter anderem:

- Performance-Benutzer inklusive Profilen und Profileinstellungen
- veröffentlichte, inaktive und wenige noch nicht veröffentlichte Plätze
- aktuelle versionierte Adressen
- Platztexte, Details und Kontakte
- Öffnungszeiten
- einfache und variable Merkmale
- variable Merkmals-Metadaten passend zu den aktuellen `feature_workflows`
- Fahrzeugtypen
- Reviews und aktuelle Review-Versionen
- strukturierte Preisangebote, Perioden und Preiszeilen
- Favoriten
- Foto-Datensätze und Verknüpfungen

Die Daten werden bewusst nicht überall gleich verteilt. Einige Plätze haben beispielsweise keine Reviews oder Fotos, während andere mehrere davon besitzen.

## Dummybilder

Für Fotoabfragen werden nicht tausende echte Dateien erzeugt. Stattdessen erstellt der Generator zehn Detail-/Preview-Paare unter:

```text
storage/app/private/performance/
```

Alle Performance-Foto-Datensätze referenzieren verteilt diesen kleinen Pool.

Wenn Imagick verfügbar ist, werden echte WebP-Dateien erzeugt. Falls Imagick lokal fehlt, legt der Generator nur kleine Platzhalterdateien an. Das reicht für reine Datenbank- und Querytests, ist aber nicht für einen realistischen Bildauslieferungs-Benchmark gedacht.

Upload-, Konvertierungs- und Storage-Durchsatz werden später bei Bedarf separat getestet.

## Datensatz entfernen

```bash
php artisan camperwolf:performance-clear
```

Ohne Rückfrage:

```bash
php artisan camperwolf:performance-clear --force
```

Der Cleanup entfernt nur klar markierte Performance-Daten:

- Plätze mit dem Slug-Präfix `perf-place-`
- Benutzer unter `@performance.camperwolf.test`
- als Performance-Daten markierte Fotozeilen
- den lokalen Ordner `storage/app/private/performance`

Normale Demo-Daten, bestehende Entwicklungsdaten und der konfigurierte Owner-Account werden nicht gezielt angefasst.

## Zweck für Roadmap-Punkt 8

Der Datensatz ist die Messbasis für den nächsten Schritt:

1. Baseline der öffentlichen Platzsuche aufnehmen.
2. Browse-Facetten, Marker, Ratings und Preisfilter messen.
3. Platzprofil und seine Query-Anzahl messen.
4. Auffällige SQL-Abfragen mit `EXPLAIN` / `EXPLAIN ANALYZE` prüfen.
5. Erst danach Indizes oder Query-Strukturen verändern.
6. Mit demselben Datensatz erneut messen und Vorher/Nachher vergleichen.

Der Performance-Datensatz selbst ist kein Benchmark-Ergebnis. Er stellt nur sicher, dass alle Messungen unter vergleichbaren Bedingungen stattfinden.
