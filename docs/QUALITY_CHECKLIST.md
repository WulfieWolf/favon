# Camperwolf.de – Funktions- und Qualitätsprüfung

Diese Checkliste ist für eine vollständige manuelle Prüfung der aktuell vorhandenen Camperwolf-Funktionen gedacht. Sie soll auch von Personen genutzt werden können, die das Projekt technisch nicht kennen.

**Tester:** ______________________________  
**Datum:** ______________________________  
**Version / Stand:** ______________________________  
**Browser / Gerät:** ______________________________

## Ergebnis markieren

- [ ] OK
- [ ] Fehler
- [ ] Nicht geprüft / nicht anwendbar

Bei einem Fehler möglichst kurz notieren:
- Was wurde gemacht?
- Was wurde erwartet?
- Was ist stattdessen passiert?
- Screenshot, wenn sinnvoll.

---

# 1. Grundfunktionen

- [ ] Startseite / Anwendung lässt sich ohne Fehler öffnen.
- [ ] Navigation funktioniert.
- [ ] Seitenwechsel funktionieren ohne sichtbare Darstellungsfehler.
- [ ] Deutsche Sprache funktioniert.
- [ ] Englische Sprache funktioniert.
- [ ] Wechsel der Sprache bleibt beim weiteren Navigieren erhalten.
- [ ] Hell-/Dunkelmodus funktioniert.
- [ ] Darstellung bleibt auch nach einem Reload korrekt.
- [ ] Anwendung funktioniert nach Ab- und erneutem Anmelden weiterhin korrekt.

# 2. Registrierung, Login und Benutzerkonto

- [ ] Neuer Benutzer kann sich registrieren.
- [ ] Pflichtfelder bei Registrierung werden geprüft.
- [ ] Ungültige E-Mail-Adresse wird abgewiesen.
- [ ] Bereits verwendete E-Mail-Adresse wird erkannt.
- [ ] E-Mail-Verifizierung funktioniert.
- [ ] Nicht verifizierter Benutzer wird korrekt eingeschränkt.
- [ ] Login mit gültigen Daten funktioniert.
- [ ] Login mit falschen Daten wird abgewiesen.
- [ ] Passwort-vergessen / Passwort-Reset funktioniert.
- [ ] Logout funktioniert.
- [ ] Profilseite lässt sich öffnen.
- [ ] Name kann geändert werden.
- [ ] E-Mail-Adresse kann geändert werden.
- [ ] Erforderliche neue E-Mail-Verifizierung funktioniert.
- [ ] Sicherheitseinstellungen lassen sich öffnen.
- [ ] Erscheinungsbild-Einstellungen lassen sich öffnen.
- [ ] Benutzerkonto kann entsprechend der vorgesehenen Funktion gelöscht werden.
- [ ] Community-Profil lässt sich öffnen.
- [ ] Für bestehende und neu registrierte Nutzer existiert ein eindeutiger automatischer Anzeigename im Format „CWNutzer…“.
- [ ] Öffentliche Profil-URL über den Anzeigenamen funktioniert.
- [ ] Ein eigener Anzeigename kann genau einmal vergeben werden.
- [ ] Anzeigename akzeptiert nur URL-taugliche Zeichen und lehnt bereits belegte Namen unabhängig von Groß-/Kleinschreibung ab.
- [ ] Nach Vergabe des eigenen Anzeigenamens wird dieser im Profil und Benutzermenü verwendet.
- [ ] Profilbild kann hochgeladen, ersetzt und entfernt werden.
- [ ] Profilbild ist standardmäßig öffentlich sichtbar.
- [ ] Bio kann gespeichert werden.
- [ ] Herkunft kann nur über die Ortsuche als Stadt/Land übernommen werden.
- [ ] Geburtsdatum kann freiwillig gespeichert werden.
- [ ] Geburtsdatum wird außerhalb der eigenen Profileinstellungen niemals angezeigt.
- [ ] Sichtbares Alter wird korrekt aus dem Geburtsdatum berechnet.
- [ ] Geschlecht bietet männlich, weiblich, nicht-binär/andere Geschlechtsidentität und keine Angabe.
- [ ] Bei nicht-binär/anderer Geschlechtsidentität kann optional eine Selbstbezeichnung eingetragen werden.
- [ ] Fahrzeug-/Reiseart kann aus der vorgegebenen Liste gewählt werden.
- [ ] Freie Fahrzeugdetails können gespeichert werden.
- [ ] Bis zu fünf Social Links können gepflegt werden.
- [ ] Sichtbarkeit „Öffentlich“, „Nur registrierte Nutzer“ und „Privat“ funktioniert je Profilbereich.
- [ ] Standard-Sichtbarkeit entspricht der Vorgabe: Profilbild öffentlich; Alter/Geschlecht privat; übrige freiwillige Angaben nur für registrierte Nutzer.
- [ ] Gast sieht keine Angaben, die auf „Nur registrierte Nutzer“ oder „Privat“ stehen.
- [ ] Angemeldeter fremder Nutzer sieht Angaben für „Nur registrierte Nutzer“, aber keine privaten Angaben.
- [ ] Profilinhaber sieht seine eigenen Angaben unabhängig von der öffentlichen Sichtbarkeit.

# 3. Benachrichtigungseinstellungen

- [ ] Bereich „Benachrichtigungen“ im Benutzerkonto lässt sich öffnen.
- [ ] „Entscheidungen zu meinen Vorschlägen“ kann ein-/ausgeschaltet werden.
- [ ] „Änderungen an meinen Favoriten“ kann ein-/ausgeschaltet werden.
- [ ] „Allgemeine Camperwolf-Hinweise“ kann ein-/ausgeschaltet werden.
- [ ] Einstellungen bleiben nach Speichern und Reload erhalten.
- [ ] „Wichtige Systeminfos“ wird angezeigt.
- [ ] „Wichtige Systeminfos“ kann nicht deaktiviert werden.
- [ ] Hinweis zur verpflichtenden Zustellung wichtiger Systeminfos ist verständlich.

# 4. Platzübersicht

- [ ] Platzübersicht wird ohne Fehler geladen.
- [ ] Liste der Plätze wird angezeigt.
- [ ] Karte wird angezeigt.
- [ ] Kartenmarker werden angezeigt.
- [ ] Anzahl und Inhalt der sichtbaren Marker passen zur gefilterten Liste.
- [ ] Klick auf einen Platznamen öffnet das richtige Platzprofil.
- [ ] Klick auf einen Kartenmarker zeigt den richtigen Platz.
- [ ] Link im Karten-Popup öffnet das richtige Platzprofil.
- [ ] Hover über einen Platz hebt den passenden Kartenmarker hervor.
- [ ] Listen-/Kartenteil lässt sich in der Breite verändern.
- [ ] Listenansicht kann wie vorgesehen ein-/ausgeblendet werden.
- [ ] Kartenansicht kann wie vorgesehen ein-/ausgeblendet werden.
- [ ] Sortierung nach „Neueste“ funktioniert.
- [ ] Sortierung nach „Name“ funktioniert.
- [ ] Sortierung nach „Ort“ funktioniert.
- [ ] Trefferzahl ist plausibel.

# 5. Suche und Filter

- [ ] Suche nach Platzname funktioniert.
- [ ] Suche nach Ort funktioniert.
- [ ] Suche nach Postleitzahl funktioniert.
- [ ] Suche nach passenden Merkmalen funktioniert.
- [ ] Einzelner Tag-/Merkmalfilter funktioniert.
- [ ] Mehrere Tagfilter funktionieren gemeinsam.
- [ ] Mehrere Filter verwenden die erwartete UND-Logik.
- [ ] Aktive Filter werden sichtbar dargestellt.
- [ ] Einzelner aktiver Filter kann entfernt werden.
- [ ] „Zurücksetzen“ entfernt alle Filter.
- [ ] Tag-Suche in der Filterleiste funktioniert.
- [ ] „Mehr …“ bei längeren Taggruppen funktioniert.
- [ ] Trefferzahlen bei Tags wirken plausibel.
- [ ] Nicht passende / leere Filteroptionen werden korrekt behandelt.
- [ ] Filter „Nur Favoriten“ funktioniert.
- [ ] Filter „Nur Favoriten“ zeigt nur favorisierte Plätze.
- [ ] „Nur Favoriten“ lässt sich mit weiteren Filtern kombinieren.
- [ ] „Nur Favoriten“ wird als aktiver Filter angezeigt.
- [ ] Suche bleibt innerhalb des Favoritenfilters erhalten.
- [ ] Sortierung bleibt innerhalb des Favoritenfilters erhalten.

# 6. Kartenfilter

- [ ] Kartenausschnitt-Filter lässt sich aktivieren.
- [ ] Nach Aktivierung zeigt die Liste nur Plätze im sichtbaren Kartenausschnitt.
- [ ] Verschieben der Karte aktualisiert die Ergebnisse korrekt.
- [ ] Zoomen der Karte aktualisiert die Ergebnisse korrekt.
- [ ] Kartenausschnitt wird als aktiver Filter angezeigt.
- [ ] Kartenausschnitt-Filter kann wieder deaktiviert werden.
- [ ] Kombination aus Kartenausschnitt + Tags funktioniert.
- [ ] Kombination aus Kartenausschnitt + Favoriten funktioniert.

# 7. Favoriten

- [ ] Nicht favorisierten Platz erkennt man am leeren Herz.
- [ ] Herz in der Platzübersicht fügt den Platz zu Favoriten hinzu.
- [ ] Nach Favorisieren wird das Herz rot / gefüllt dargestellt.
- [ ] Favoritenstatus bleibt nach Reload erhalten.
- [ ] Favoritenstatus ist auch auf der Platzdetailseite korrekt.
- [ ] Klick auf rotes Herz entfernt den Platz aus Favoriten.
- [ ] Nach Entfernen wird das Herz wieder leer dargestellt.
- [ ] Entfernen funktioniert auch aus einer gefilterten Favoritenansicht.
- [ ] Favorit hinzufügen/entfernen erzeugt keine Fehlermeldung.
- [ ] Favoritenanzahl im Filter ist plausibel.

# 8. Platzprofil

- [ ] Platzprofil öffnet ohne Fehler.
- [ ] Platzname ist korrekt.
- [ ] Platztyp ist korrekt.
- [ ] Adresse / Ort werden korrekt dargestellt.
- [ ] Beschreibung wird korrekt dargestellt.
- [ ] Rechtlicher/Nutzungsstatus wird korrekt dargestellt.
- [ ] Öffnungsstatus wird korrekt dargestellt.
- [ ] Karte zeigt den richtigen Standort.
- [ ] Kartenmarker liegt an der erwarteten Position.
- [ ] Alle vorgesehenen Merkmalsgruppen werden angezeigt.
- [ ] Bekannte positive Merkmale werden korrekt dargestellt.
- [ ] Bekannte negative Merkmale werden korrekt dargestellt.
- [ ] Unbekannte Merkmale werden korrekt dargestellt.
- [ ] Dynamische Werte wie Ampere, Entfernung, Dauer oder Preis werden korrekt dargestellt.
- [ ] Kommentare/Hinweise zu Merkmalen lassen sich über das Info-Symbol anzeigen.
- [ ] Info-Hinweise funktionieren auch per Klick/Tap.
- [ ] Favoritenstatus ist korrekt.
- [ ] Zurück-Link führt sinnvoll zurück.

# 9. Neuen Platz vorschlagen – Schritt 1

- [ ] „Platz vorschlagen“ lässt sich öffnen.
- [ ] Name kann eingegeben werden.
- [ ] Platztyp kann gewählt werden.
- [ ] Pflichtfelder werden geprüft.
- [ ] Karte funktioniert.
- [ ] Position kann korrekt gewählt werden.
- [ ] Adresssuche funktioniert.
- [ ] Rückwärtssuche aus Kartenposition funktioniert.
- [ ] Land kann gewählt werden.
- [ ] Optionale Adressfelder funktionieren.
- [ ] Ein Entwurf kann gespeichert werden.
- [ ] Entwurf lässt sich später wieder öffnen.
- [ ] Direkte Einreichung aus dem vorgesehenen Ablauf funktioniert.

# 10. Platzentwurf – Schritt 2

- [ ] Beschreibung kann eingetragen werden.
- [ ] Anfahrts-/Zugangshinweise können eingetragen werden.
- [ ] Betreibername kann eingetragen werden.
- [ ] Anzahl Stellplätze kann eingetragen werden.
- [ ] Betriebsart kann gewählt werden.
- [ ] Daten werden korrekt gespeichert.
- [ ] Zurück-/Weiter-Navigation funktioniert.
- [ ] Bereits gespeicherte Werte bleiben erhalten.

# 11. Platzentwurf – Schritt 3 Merkmale

- [ ] Alle vorgesehenen Merkmalsgruppen werden angezeigt.
- [ ] Einzelne Merkmale lassen sich öffnen/bearbeiten.
- [ ] Status eines Merkmals kann geändert werden.
- [ ] Abhängige Felder erscheinen nur bei passenden Statuswerten.
- [ ] Zahlenfelder akzeptieren gültige Werte.
- [ ] Ungültige Zahlenwerte werden abgewiesen.
- [ ] Einheiten werden korrekt dargestellt.
- [ ] Auswahlfelder funktionieren.
- [ ] Preisfelder funktionieren.
- [ ] Preisbasis / Einheit funktioniert.
- [ ] Freitext-Kommentar zu einem Merkmal kann eingetragen werden.
- [ ] Mehrere Merkmale können geändert und gemeinsam gespeichert werden.
- [ ] Nicht geänderte Merkmale bleiben unverändert.
- [ ] „Unbekannt“ bleibt wirklich unbekannt und wird nicht als „Nein“ gespeichert.
- [ ] Gespeicherte Werte bleiben nach Reload erhalten.

# 12. Platzentwurf – Schritt 4 Prüfung und Einreichung

- [ ] Zusammenfassung lässt sich öffnen.
- [ ] Grunddaten werden korrekt zusammengefasst.
- [ ] Beschreibung/Details werden korrekt zusammengefasst.
- [ ] Merkmale werden korrekt zusammengefasst.
- [ ] Zurück zum Bearbeiten funktioniert.
- [ ] Einreichung funktioniert.
- [ ] Eingereichter Entwurf erhält den vorgesehenen Pending-Status.
- [ ] Eingereichter Entwurf ist für den normalen Nutzer nicht mehr direkt bearbeitbar.
- [ ] Einreichung erscheint in der Moderation.

# 13. Änderungsvorschläge an bestehenden Plätzen

- [ ] Normaler Nutzer sieht „Änderung vorschlagen“.
- [ ] Bestehendes Merkmal kann als Änderung vorgeschlagen werden.
- [ ] Unbekanntes Merkmal kann ergänzt werden.
- [ ] Statusänderung kann vorgeschlagen werden.
- [ ] Detailwert kann vorgeschlagen werden.
- [ ] Preis-/Rate-Änderung kann vorgeschlagen werden.
- [ ] Kommentar kann vorgeschlagen werden.
- [ ] Vorschlag ohne tatsächliche Änderung wird sinnvoll behandelt.
- [ ] Erfolgreicher Vorschlag zeigt eine Bestätigung.
- [ ] Vorschlag erscheint anschließend in der Moderation.
- [ ] Mehrere Felder eines Vorgangs werden als zusammengehörige Änderung behandelt.

# 14. Moderation

- [ ] Moderationsbereich lässt sich mit berechtigtem Konto öffnen.
- [ ] Offene Änderungsvorschläge werden angezeigt.
- [ ] Detailansicht eines Vorschlags funktioniert.
- [ ] Ursprünglicher und vorgeschlagener Wert sind nachvollziehbar.
- [ ] Vorschlag kann genehmigt werden.
- [ ] Genehmigte Änderung wird am Platz korrekt angewendet.
- [ ] Vorschlag kann abgelehnt werden.
- [ ] Ablehnungsgrund kann gespeichert werden.
- [ ] Abgelehnte Änderung wird nicht am Platz angewendet.
- [ ] Bereits bearbeiteter Vorschlag kann nicht versehentlich erneut bearbeitet werden.
- [ ] Zusammengehörige Änderungspakete werden gemeinsam verarbeitet.

# 15. Benachrichtigungen – Glocke und Verlauf

- [ ] Glockenbutton wird sichtbar dargestellt.
- [ ] Anzahl ungelesener Nachrichten wird korrekt angezeigt.
- [ ] Zähler ist als rote Zahl gut erkennbar.
- [ ] Klick auf Glocke öffnet das Dropdown.
- [ ] Dropdown zeigt nur ungelesene Nachrichten.
- [ ] Klick außerhalb schließt das Dropdown.
- [ ] Einzelne Benachrichtigung lässt sich öffnen.
- [ ] Öffnen markiert sie als gelesen.
- [ ] „Alle als gelesen markieren“ funktioniert.
- [ ] Vollständiger Benachrichtigungsverlauf lässt sich öffnen.
- [ ] Gelesene und ungelesene Einträge sind unterscheidbar.
- [ ] Detailseite einer Benachrichtigung funktioniert.
- [ ] Bei geclusterten Meldungen werden Einzelereignisse angezeigt.
- [ ] Links aus Benachrichtigungen führen zum richtigen Ziel.

# 16. Benachrichtigungen – Vorschläge

- [ ] Genehmigter eigener Vorschlag erzeugt eine Benachrichtigung.
- [ ] Abgelehnter eigener Vorschlag erzeugt eine Benachrichtigung.
- [ ] Ablehnungsgrund erscheint in der Nachricht.
- [ ] Mehrere Entscheidungen werden nach dem vorgesehenen Zeitraum zusammengefasst.
- [ ] Abschalten der Kategorie verhindert neue Vorschlagsbenachrichtigungen.
- [ ] Wieder-Einschalten aktiviert zukünftige Vorschlagsbenachrichtigungen wieder.

# 17. Benachrichtigungen – Favoriten

Für diesen Test möglichst zwei Benutzer verwenden.

- [ ] Benutzer A favorisiert einen Platz.
- [ ] Benutzer B ändert den favorisierten Platz bzw. reicht eine Änderung ein.
- [ ] Nach Genehmigung erhält Benutzer A eine Favoriten-Benachrichtigung.
- [ ] Mehrere Favoritenänderungen werden nach dem vorgesehenen Zeitraum zusammengefasst.
- [ ] Der Nutzer, der die Änderung selbst vorgeschlagen hat, erhält nicht zusätzlich die Meldung „Favorit geändert“.
- [ ] Der Nutzer erhält trotzdem seine normale Meldung über Genehmigung/Ablehnung des eigenen Vorschlags.
- [ ] Der Moderator/Admin, der selbst geändert hat, erhält keine unnötige Favoritenmeldung für seine eigene Änderung.
- [ ] Abschalten der Favoritenbenachrichtigungen verhindert neue entsprechende Nachrichten.
- [ ] Wieder-Einschalten aktiviert zukünftige Favoritenbenachrichtigungen wieder.

# 18. Systemmeldungen

- [ ] Admin kann eine normale Systemmeldung erstellen.
- [ ] Titel kann eingegeben werden.
- [ ] Nachrichtentext kann eingegeben werden.
- [ ] Optionaler Link kann gesetzt werden.
- [ ] Optionales Ablaufdatum kann gesetzt werden.
- [ ] Normale Meldung wird den vorgesehenen Nutzern angezeigt.
- [ ] Nutzer mit abgeschalteten allgemeinen Hinweisen erhält normale Systemmeldung nicht.
- [ ] Admin kann eine wichtige Systemmeldung erstellen.
- [ ] Wichtige Systemmeldung wird auch bei abgeschalteten allgemeinen Hinweisen angezeigt.
- [ ] Wichtige Systemmeldung ist als wichtig erkennbar.
- [ ] Abgelaufene Systemmeldung wird nicht mehr angezeigt.
- [ ] Neue Nutzer erhalten keine alten Systemmeldungen, die vor ihrer Registrierung veröffentlicht wurden.

# 19. Welcome-Benachrichtigung

- [ ] Neu registrierter Benutzer erhält eine Welcome-Nachricht.
- [ ] Welcome-Nachricht erscheint nur einmal.
- [ ] Bereits bestehender Benutzer erhält nicht nachträglich eine neue Welcome-Nachricht.
- [ ] Welcome-Nachricht lässt sich öffnen und als gelesen markieren.

# 20. Rollen und Rechte

Mindestens mit User, Moderator/Admin und System Owner prüfen.

- [ ] Normaler Benutzer sieht keine Admin-Funktionen.
- [ ] Normaler Benutzer kann Plätze/Änderungen vorschlagen.
- [ ] Normaler Benutzer kann keine fremden Moderationsfunktionen aufrufen.
- [ ] Moderator/Admin sieht die vorgesehenen Verwaltungsfunktionen.
- [ ] Nicht erlaubte Admin-URLs liefern keinen unberechtigten Zugriff.
- [ ] System Owner hat die vorgesehenen erweiterten Rechte.
- [ ] Rollen-Vorschau des System Owners lässt sich aktivieren.
- [ ] Bei Rollen-Vorschau ändert sich die sichtbare Oberfläche passend.
- [ ] Bei Rollen-Vorschau werden auch die Routenrechte korrekt eingeschränkt.
- [ ] Verlassen der Rollen-Vorschau stellt die normalen Owner-Rechte wieder her.

# 21. Admin – Benutzerverwaltung

- [ ] Benutzerliste lässt sich öffnen.
- [ ] Benutzer-Detailansicht lässt sich öffnen.
- [ ] Rolle kann zugewiesen werden.
- [ ] Rolle kann entfernt werden.
- [ ] Berechtigungs-Override kann gesetzt werden.
- [ ] Änderung wirkt sich auf die tatsächlichen Rechte aus.
- [ ] Unberechtigte Nutzer können diese Funktionen nicht aufrufen.

# 22. Audit-Log

- [ ] Audit-Log lässt sich mit passender Berechtigung öffnen.
- [ ] Direkte Admin-Änderung erscheint im Audit-Log.
- [ ] Genehmigte Änderung erscheint nachvollziehbar im Audit-Log.
- [ ] Abgelehnte Änderung wird als Moderationsvorgang nachvollziehbar protokolliert.
- [ ] Favorit hinzufügen wird protokolliert.
- [ ] Favorit entfernen wird protokolliert.
- [ ] Systemmeldung wird protokolliert.
- [ ] Benutzer, Zeitpunkt und Aktion sind plausibel.
- [ ] Alte/neue Werte sind bei Änderungen nachvollziehbar.

# 23. Direkte Admin-/Owner-Bearbeitung von Merkmalen

- [ ] Berechtigter Admin/Owner sieht „Bearbeiten“ statt „Änderung vorschlagen“.
- [ ] Merkmal kann direkt geändert werden.
- [ ] Änderung wird ohne Moderationsrunde gespeichert.
- [ ] Statusänderung funktioniert.
- [ ] Detailwerte funktionieren.
- [ ] Preise/Raten funktionieren.
- [ ] Kommentare funktionieren.
- [ ] Keine Änderung erzeugt keine unnötige neue Version.
- [ ] Direkte Änderung erscheint im Audit-Log.
- [ ] Andere Nutzer mit diesem Platz als Favorit erhalten später die vorgesehene Änderungsbenachrichtigung.
- [ ] Der bearbeitende Admin selbst erhält keine Favoritenmeldung über seine eigene Änderung.

# 24. Datenqualität und Versionsverhalten

- [ ] Änderung überschreibt historische Datensätze nicht unkontrolliert.
- [ ] Aktuell gültiger Wert wird im Profil angezeigt.
- [ ] Alte Version bleibt technisch nachvollziehbar.
- [ ] Deaktivierte Referenzdaten verschwinden aus der normalen Auswahl.
- [ ] Unbekannte Werte werden nicht als „Nein“ dargestellt.
- [ ] Negative Merkmale erscheinen nicht bei positiven Feature-Filtern.
- [ ] Preis und Einheiten werden korrekt kombiniert dargestellt.
- [ ] Mehrsprachige Bezeichnungen verwenden die richtige Sprache bzw. sinnvollen Fallback.

# 25. Mobile / kleinere Bildschirmgrößen

Auf mindestens einem Smartphone oder mit Browser-Responsive-Modus prüfen.

- [ ] Header bleibt benutzbar.
- [ ] Benutzer-Menü bleibt erreichbar.
- [ ] Glocke/Benachrichtigungen bleiben erreichbar.
- [ ] Platzübersicht bleibt benutzbar.
- [ ] Filter bleiben erreichbar.
- [ ] Karte bleibt bedienbar.
- [ ] Platzprofil bleibt gut lesbar.
- [ ] Favoritenherz bleibt gut anklickbar.
- [ ] Merkmal-Editor bleibt benutzbar.
- [ ] Info-Tooltips funktionieren per Tap.
- [ ] Formulare laufen nicht aus dem Bildschirm.
- [ ] Buttons überdecken keine wichtigen Inhalte.

# 26. Browserprüfung

Mindestens die aktuell unterstützten Browser prüfen.

- [ ] Chrome / Chromium
- [ ] Firefox
- [ ] Edge
- [ ] Safari, falls verfügbar
- [ ] Android-Browser / Chrome Mobile
- [ ] iPhone / Safari Mobile, falls verfügbar

Bei jedem Browser mindestens prüfen:
- [ ] Login
- [ ] Platzübersicht
- [ ] Karte
- [ ] Filter
- [ ] Platzprofil
- [ ] Favoriten
- [ ] Benachrichtigungen
- [ ] Formulare

# 27. Fehlerfälle und Robustheit

- [ ] Ungültige URL führt zu sinnvoller Fehlerseite.
- [ ] Nicht vorhandener Platz führt zu 404 statt Serverfehler.
- [ ] Nicht vorhandene Benachrichtigung führt nicht zu fremden Daten.
- [ ] Nutzer kann keine Benachrichtigung eines anderen Nutzers öffnen.
- [ ] Nutzer kann keinen Entwurf eines anderen Nutzers bearbeiten.
- [ ] Nutzer kann keine Admin-Aktion durch direkte URL erzwingen.
- [ ] Doppelklick / mehrfaches Absenden erzeugt keine offensichtlichen Doppelvorgänge.
- [ ] Leere Ergebnisliste wird verständlich dargestellt.
- [ ] Keine Favoriten wird verständlich dargestellt.
- [ ] Keine Benachrichtigungen wird verständlich dargestellt.
- [ ] Fehlende optionale Platzdaten verursachen keine Darstellungsfehler.
- [ ] Sonderzeichen, Umlaute und längere Texte werden korrekt dargestellt.

# 28. Benachrichtigungs-Zeitsteuerung / Scheduler

Nur erforderlich, wenn die Server-/Scheduler-Funktion mitgetestet wird.

- [ ] Moderationsereignisse werden nach dem vorgesehenen Zeitfenster geclustert.
- [ ] Favoritenereignisse werden nach dem vorgesehenen Zeitfenster geclustert.
- [ ] Direkte Systemmeldungen erscheinen sofort.
- [ ] Welcome-Nachricht erscheint sofort.
- [ ] Gelesene normale Nachrichten werden nach vorgesehener Aufbewahrungszeit bereinigt.
- [ ] Alte ungelesene normale Nachrichten inaktiver Nutzer werden wie vorgesehen bereinigt.
- [ ] Wichtige persönliche Nachrichten werden nicht durch normale Bereinigung entfernt.
- [ ] Abgelaufene Broadcast-/Systemmeldungen werden bereinigt.

# 29. Hilfe & kontextbezogene Unterstützung

- [ ] Hilfe & Support lässt sich öffnen.
- [ ] Hilfeartikel werden angezeigt.
- [ ] Hilfe-Suche findet passende Artikel.
- [ ] Hilfeartikel lässt sich vollständig öffnen.
- [ ] Auf der Platzübersicht führt „Hilfe“ zum passenden Artikel.
- [ ] Auf einem Platzprofil führt „Hilfe“ zum passenden Artikel.
- [ ] In jedem Schritt der Platzanlage führt „Hilfe“ zum passenden Artikel.
- [ ] In den Benachrichtigungseinstellungen führt „Hilfe“ zum passenden Artikel.
- [ ] Im Benutzerkonto führt „Hilfe“ zum passenden Artikel oder sinnvoll zur Hilfeübersicht.
- [ ] Fehlt ein passender Artikel, entsteht kein Fehler; die Hilfeübersicht wird geöffnet.
- [ ] Admin kann neuen Hilfeartikel anlegen.
- [ ] Admin kann Titel, Text, Kontext und Reihenfolge bearbeiten.
- [ ] Hilfeartikel kann deaktiviert und wieder aktiviert werden.
- [ ] Deaktivierter Hilfeartikel ist öffentlich nicht mehr sichtbar.

# 30. Supportmeldung erstellen

- [ ] „Fehler melden“ ist auf den vorgesehenen Seiten direkt erreichbar.
- [ ] Beim Öffnen werden Seite, Modul, Route und URL als Kontext übernommen.
- [ ] Fehler kann als Kategorie ausgewählt werden.
- [ ] Feature-Wunsch kann als Kategorie ausgewählt werden.
- [ ] Verbesserung kann als Kategorie ausgewählt werden.
- [ ] Daten-/Inhaltsproblem kann als Kategorie ausgewählt werden.
- [ ] Sonstiges kann als Kategorie ausgewählt werden.
- [ ] Bei Fehlern werden passende öffentliche Known Bugs angezeigt.
- [ ] Bei Feature-Wünschen werden passende vorgeschlagene/geplante Funktionen angezeigt.
- [ ] Meldung kann trotz ähnlicher öffentlicher Einträge weiterhin abgesendet werden.
- [ ] Meldetext ist Pflicht.
- [ ] Betreff ist optional.
- [ ] Angemeldeter Nutzer muss keine Kontaktdaten erneut eingeben.
- [ ] Gast kann eine Meldung ohne Registrierung absenden.
- [ ] Gast kann Name freiwillig angeben.
- [ ] Gast kann E-Mail freiwillig angeben.
- [ ] Gast kann Telefonnummer freiwillig angeben.
- [ ] Gast kann auch komplett ohne Kontaktdaten melden.
- [ ] Datenschutzhinweis zu Gast-Kontaktdaten ist sichtbar.
- [ ] Spam-/Rate-Limit verhindert offensichtliches massenhaftes Absenden.
- [ ] Nach Gastmeldung erscheint eine verständliche Bestätigung.
- [ ] Nach Meldung eines angemeldeten Nutzers öffnet sich die eigene Ticketansicht.

# 31. Meine Meldungen

- [ ] „Meine Meldungen“ ist im Benutzerbereich erreichbar.
- [ ] Nur eigene Tickets werden angezeigt.
- [ ] Tickettyp wird korrekt angezeigt.
- [ ] Status wird korrekt angezeigt.
- [ ] Letzte Aktivität wird korrekt angezeigt.
- [ ] Ticketdetail zeigt die ursprüngliche Meldung.
- [ ] Supportantworten sind sichtbar.
- [ ] Interne Notizen sind für den Nutzer niemals sichtbar.
- [ ] Nutzer kann auf offenes Ticket antworten.
- [ ] Nutzerantwort erscheint anschließend im Verlauf.
- [ ] Antwort auf „Wartet auf Nutzer“ öffnet das Ticket wieder.
- [ ] Antwort auf „Gelöst“ öffnet das Ticket wieder.
- [ ] Geschlossenes Ticket kann vom Nutzer nicht mehr beantwortet werden.
- [ ] Verknüpfter öffentlicher Known-Bug-/Roadmap-Eintrag wird angezeigt.

# 32. Support-Backoffice

- [ ] Berechtigte Rolle kann Support-Warteschlange öffnen.
- [ ] Unberechtigte Rolle kann Support-Warteschlange nicht öffnen.
- [ ] Gast- und Benutzer-Tickets erscheinen in derselben Warteschlange.
- [ ] Tickets können nach Status gefiltert werden.
- [ ] Tickets können nach Typ gefiltert werden.
- [ ] Ticketsuche funktioniert.
- [ ] Ticketdetail zeigt Absender und vorhandene Kontaktdaten.
- [ ] Ticketdetail zeigt Kontext, Route, Quellseite und Browserinformation.
- [ ] Support kann öffentliche Antwort schreiben.
- [ ] Antwort wird dem registrierten Nutzer angezeigt.
- [ ] Antwort erzeugt eine Benachrichtigung für den registrierten Nutzer.
- [ ] Berechtigte Rolle kann interne Notiz schreiben.
- [ ] Interne Notiz bleibt intern.
- [ ] Status kann geändert werden.
- [ ] Priorität kann geändert werden.
- [ ] Ticket kann einem berechtigten Mitarbeiter zugewiesen werden.
- [ ] Nutzer ohne Zuweisungsrecht kann keine Zuweisung ändern.
- [ ] Ticket kann mit öffentlichem Eintrag verknüpft werden.
- [ ] Statusänderung erzeugt eine Benachrichtigung für registrierten Nutzer.
- [ ] Status „On Hold“ funktioniert.
- [ ] Status „Wartet auf Nutzer“ funktioniert.
- [ ] Status „Gelöst“ funktioniert.
- [ ] Status „Geschlossen“ funktioniert.
- [ ] Geschlossenes Ticket zeigt keine normalen Antwortfelder mehr.
- [ ] Status-, Prioritäts-, Zuweisungs- und Verknüpfungsänderungen erscheinen in der Historie.

# 33. Known Bugs & Roadmap

- [ ] Öffentliche Known-Bugs-&-Roadmap-Seite lässt sich ohne Login öffnen.
- [ ] Filter „Known Bugs“ funktioniert.
- [ ] Filter „Suggested Features“ funktioniert.
- [ ] Filter „Planned Features“ funktioniert.
- [ ] Status eines öffentlichen Eintrags wird korrekt angezeigt.
- [ ] Gelöster Eintrag bleibt sichtbar und ist als gelöst markiert.
- [ ] Nicht öffentliche Einträge sind öffentlich nicht sichtbar.
- [ ] Admin kann Known Bug anlegen.
- [ ] Admin kann Suggested Feature anlegen.
- [ ] Admin kann Planned Feature anlegen.
- [ ] Admin kann Status ändern.
- [ ] Admin kann Eintrag veröffentlichen und wieder aus der Öffentlichkeit nehmen.
- [ ] Admin kann Kontext für einen öffentlichen Eintrag hinterlegen.
- [ ] Öffentliche Liste wird nur redaktionell durch berechtigte Rollen gepflegt.
- [ ] Eine Nutzermeldung wird nicht automatisch öffentlich.

# 34. Gastzugriff & Registrierungsanreize

- [ ] Startseite zeigt direkt Platzsuche/Karte statt Login-Seite.
- [ ] Ausgeloggter Gast kann Platzliste sehen.
- [ ] Ausgeloggter Gast kann Suche und Tag-Filter verwenden.
- [ ] Ausgeloggter Gast kann Karte verwenden.
- [ ] Ausgeloggter Gast kann veröffentlichte Platzprofile öffnen.
- [ ] Favoritenfilter ist für Gäste sichtbar, öffnet aber den Registrierungsdialog.
- [ ] Favoriten-Herz ist für Gäste sichtbar, öffnet aber den Registrierungsdialog.
- [ ] „Platz vorschlagen“ ist für Gäste sichtbar und erklärt den Community-Nutzen einer Registrierung.
- [ ] „Änderung vorschlagen“ ist für Gäste sichtbar und erklärt den Community-Nutzen einer Registrierung.
- [ ] Registrierungsdialog bietet Registrieren, Anmelden und Abbrechen.
- [ ] Registrierungsdialog verwendet je nach gewünschter Funktion passenden Text.
- [ ] Gastnavigation enthält Plätze, Karte und Hilfe & Support.
- [ ] Gastnavigation bietet Anmelden und Registrieren.
- [ ] Beim ersten Besuch als Gast erscheint einmalig der Camperwolf-Willkommensdialog.
- [ ] Willkommensdialog hebt Platzsuche, Filter und Community-Mitwirkung hervor.
- [ ] Willkommensdialog kann per X, Button und Klick außerhalb geschlossen werden.
- [ ] Nach dem Schließen erscheint der Willkommensdialog im selben Browser nicht bei jedem Seitenaufruf erneut.
- [ ] Direkter Aufruf eines Adminbereichs als Gast liefert eine neutrale Nicht-gefunden-Reaktion.
- [ ] Fehlende Berechtigung verrät bei Permission-geschützten Bereichen nicht unnötig deren Existenz.
- [ ] Gast-Support verlangt Name und E-Mail.
- [ ] Telefonnummer im Gast-Support bleibt optional.
- [ ] Gast-Support weist ausdrücklich darauf hin, dass Ticketverwaltung nur möglich ist, wenn man sich vor dem Absenden anmeldet.

# 35. Level & XP

- [ ] Neue Nutzer haben Gamification standardmäßig sichtbar.
- [ ] Gamification kann in den Profileinstellungen ausgeschaltet und wieder eingeschaltet werden.
- [ ] Ausschalten blendet nur die eigene Level-/XP-Darstellung aus; XP werden weiter gesammelt.
- [ ] Andere Nutzer mit sichtbarer Gamification bleiben mit Levelanzeige sichtbar.
- [ ] Öffentliches Profil zeigt Level, Gesamt-XP, Fortschritt zum nächsten Level und Fortschrittsbalken.
- [ ] Level-Badge erscheint am Profilbild in den vorgesehenen Profil-/Menüflächen.
- [ ] XP-Verlauf zeigt Datum/Uhrzeit, verständlichen Grund und +/- XP.
- [ ] Deaktivierte Gamification blendet den öffentlichen XP-Verlauf vollständig aus.
- [ ] „Was ist das?“ führt zum Hilfeartikel „Level und XP“.
- [ ] Hilfeartikel erklärt aktuelle XP-Regeln, Limits und historische Regelbehandlung.
- [ ] Genehmigter neuer Platz vergibt XP nur für tatsächlich vorhandene freiwillige Informationen.
- [ ] Pflicht-Grunddaten der Erstanlage erhalten keine Initial-XP.
- [ ] Beschreibung/Anfahrt/Zugangshinweis ergeben jeweils 2 XP.
- [ ] Normales Informationsfeld/Merkmal ergibt 1 XP.
- [ ] Derselbe Nutzer erhält für dieselbe Platzinformation nur einmal XP.
- [ ] Ein anderer Nutzer kann für dieselbe Information ebenfalls einmal XP erhalten.
- [ ] Wiederholte Änderung derselben Information erzeugt beim selben Nutzer keine zusätzlichen XP.
- [ ] Negative/manual Korrekturen bleiben als eigener Ledger-Eintrag sichtbar.
- [ ] XP-Gesamtstand entspricht der Summe des Ledgers.
- [ ] Levelkurve liefert frühe Level schnell und steigt ab etwa Level 50 deutlich stärker an.

# 36. Abschlussprüfung

- [ ] Keine sichtbaren 500-Fehler während des Testdurchlaufs.
- [ ] Keine offensichtlichen kaputten Links.
- [ ] Keine Funktionen enden auf Platzhalter-Seiten oder „Funktion folgt“, sofern sie als fertig gelten.
- [ ] Keine unverständlichen technischen Fehlermeldungen für normale Nutzer.
- [ ] Wichtige Aktionen geben sichtbares Feedback.
- [ ] Bedienung ist über verschiedene Seiten hinweg konsistent.
- [ ] Rote Favoritenherzen bedeuten überall dasselbe.
- [ ] Benachrichtigungszustände sind überall konsistent.
- [ ] Sprache und Begriffe sind innerhalb der Anwendung konsistent.
- [ ] Kritische Fehler wurden dokumentiert.
- [ ] Auffälligkeiten / Verbesserungsvorschläge wurden dokumentiert.

---

# Fehlerprotokoll

| Nr. | Bereich | Sprache | Beschreibung | Schweregrad | Screenshot / Hinweis | Status |
|---|---|---|---|---|---|---|
| 1 |  | DE / EN / beide |  |  |  |  |
| 2 |  | DE / EN / beide |  |  |  |  |
| 3 |  | DE / EN / beide |  |  |  |  |
| 4 |  | DE / EN / beide |  |  |  |  |
| 5 |  | DE / EN / beide |  |  |  |  |

## Empfohlene Schweregrade

- **Kritisch:** Anwendung/Funktion nicht nutzbar, Datenverlust, Sicherheits-/Rechteproblem.
- **Hoch:** zentrale Funktion fehlerhaft, aber Anwendung grundsätzlich nutzbar.
- **Mittel:** Funktion eingeschränkt oder deutlich falsches Verhalten.
- **Niedrig:** Darstellungs-, Text- oder Komfortproblem.

---

## Hinweis

Diese Liste soll mit der Entwicklung von Camperwolf mitwachsen. Bei jeder neuen Benutzerfunktion sollte mindestens ein neuer Prüfschritt ergänzt werden. Noch nicht implementierte oder bewusst zurückgestellte Funktionen gehören erst dann in den regulären Prüflauf, wenn sie tatsächlich verfügbar sind.


# Badge- und Achievement-System

- [ ] Fortschritts-Badges Entdecker, Pfadfinder, Kenner, Fotograf, Spürnase und Communityhelfer werden im Profil vor dem XP-Verlauf angezeigt.
- [ ] Bronze/Silber/Gold/Platin werden bei den festgelegten Schwellen einmalig freigeschaltet und behalten ihr Freischaltdatum.
- [ ] Nach Platin läuft der sichtbare Fortschrittszähler weiter.
- [ ] Dieselbe Information desselben Platzes kann vom selben Nutzer nicht mehrfach für Pfadfinder/Spürnase gezählt werden.
- [ ] Beim Anlegen beigetragene Informationen zählen für Pfadfinder; spätere neue Ergänzungen/Korrekturen zählen für Spürnase.
- [ ] Ein veröffentlichter neuer Platz zählt für Entdecker und erzeugt die vorgesehene Basis-XP-Vergabe.
- [ ] Fotograf zählt höchstens fünf aktive akzeptierte Fotos pro Nutzer und Platz.
- [ ] Wird ein zählendes Foto gelöscht, sinkt der aktuelle Fotograf-Zähler; bereits erreichte Stufen bleiben erhalten.
- [ ] Sichtbare Achievements zeigen Bedingung, Fortschritt sofern sinnvoll, Freischaltdatum und Seltenheit.
- [ ] Versteckte Achievements werden Besuchern niemals konkret gezeigt; Besucher sehen nur gefunden/gesamt.
- [ ] Versteckte Achievements können nicht als öffentlicher Profil-Titel ausgewählt werden.
- [ ] Manuelle Badges können im Adminbereich mit optionalem öffentlichen Kommentar vergeben und entzogen werden.
- [ ] Fester XP-Wert einer manuellen Auszeichnung wird im XP-Verlauf dokumentiert.
- [ ] Nutzer kann genau einen freigeschalteten sichtbaren Badge/Achievement-Titel auswählen oder „Kein Titel“.
- [ ] Gewählter Titel wird im Profil und in der Avatar-Statusanzeige dargestellt.
- [ ] Level bleibt direkt am Avatar sichtbar; Statuskarte zeigt Titel, Level/XP und Mitglied-seit.
- [ ] Deaktivierte Gamification blendet Level, Titel, Badges/Achievements und XP-Verlauf öffentlich aus, sammelt intern aber weiter.


---

# V1-Regressionstest – Stand 2026-09-20

Diese Ergänzung deckt die seit dem letzten größeren Doku-Stand neu hinzugekommenen bzw. wesentlich geänderten Funktionen ab.

## Community-Profil / öffentliche Identität

- [ ] Jeder Nutzer behält dauerhaft eine feste Camperwolf-ID im Format `CW-XXXXX`.
- [ ] Ein optionaler öffentlicher Alias wird getrennt von der festen Camperwolf-ID gespeichert.
- [ ] Wird ein Alias vergeben, bleibt die feste Camperwolf-ID weiterhin sichtbar/verfügbar.
- [ ] Öffentliche Profil-URL über Alias funktioniert.
- [ ] Öffentliche Profil-URL über feste Camperwolf-ID funktioniert weiterhin.
- [ ] Review-/Profil-Links bevorzugen den Alias, falls vorhanden.
- [ ] Alias kann nur einmal final vergeben werden.
- [ ] Profil-Einstellungen zeigen aktuellen Anzeigenamen und feste Camperwolf-ID getrennt.
- [ ] Fahrzeug-/Reiseart nutzt den zentralen Fahrzeugtypen-Katalog.
- [ ] Beitrittsdatum kann über die Sichtbarkeitseinstellung ausgeblendet werden.
- [ ] Profiltexte und XP-Gruppierungen erscheinen auf Deutsch und Englisch passend zur Sprache.
- [ ] Öffentliche Profilseite bleibt auf schmaler Mobilbreite nutzbar.
- [ ] Profilbild ist mobil nicht überdimensioniert.
- [ ] „Profil bearbeiten“-Button ist auf Desktop und Mobile kompakt und konsistent.
- [ ] Nach Speichern im Community-Profil springt die Layoutbreite nicht mehr um.

## Reviews / Qualitätsbewertung

- [ ] Bewertung ist nur für angemeldete Nutzer möglich.
- [ ] Alle fünf Pflichtdimensionen sind vorhanden: Sauberkeit, Funktionalität, Zustand, Sicherheit, Nutzbarkeit.
- [ ] Jede Dimension akzeptiert 1–5.
- [ ] Optionaler Bewertungstext wird nur innerhalb der vorgesehenen Länge akzeptiert.
- [ ] Pro Nutzer/Platz existiert genau eine aktuelle Bewertung.
- [ ] Frühere Versionen bleiben historisch erhalten.
- [ ] Direkte Korrektur derselben Version funktioniert im 30-Minuten-Fenster.
- [ ] Nach dem Korrekturfenster greift der vorgesehene 28-Tage-Abstand für eine neue Version.
- [ ] Bewertungen verlieren nach 12 Monaten ihre aktuelle Gültigkeit entsprechend der vorgesehenen Logik.
- [ ] Standard-Sortierung ist „neueste zuerst“.
- [ ] Weitere Sortierungen funktionieren.
- [ ] Monatsverlauf/Score-Historie wird korrekt erzeugt.
- [ ] Reviewer-Link führt zum korrekten öffentlichen Profil/Alias.
- [ ] Profilbild-/Gamification-Sichtbarkeit des Reviewers wird respektiert.
- [ ] XP/Badge-Fortschritt wird nur entsprechend der vorgesehenen Review-Regeln vergeben.
- [ ] Verifizierter Besuch wird noch nicht verlangt; die V1-Bewertung funktioniert ohne Check-in.

## Öffnungszeiten – Zeitraumlogik

- [ ] Ganzjähriger Zeitraum kann angelegt werden.
- [ ] Saisonaler Zeitraum kann angelegt werden.
- [ ] Neuer Zeitraum überschreibt nur die tatsächlich überlappenden Tage.
- [ ] Nicht überlappende Reststücke des alten Zeitraums bleiben erhalten.
- [ ] Beispiel 01.01–01.07 + neu 01.02–31.03 ergibt 01.01–31.01 / neu / 01.04–01.07.
- [ ] Ganzjährig + neuer Teilzeitraum wird korrekt in vorher / neu / nachher aufgeteilt.
- [ ] Neuer ganzjähriger Zeitraum ersetzt bestehende saisonale Bereiche korrekt.
- [ ] Jahreswechsel-/Wrap-Around-Zeiträume funktionieren.
- [ ] Vorschlag eines normalen Nutzers verändert Live-Daten vor Freigabe nicht.
- [ ] Moderation zeigt die Auswirkungen eines Zeitraumvorschlags verständlich.
- [ ] Ablehnung lässt bestehende Live-Daten unverändert.
- [ ] „Unbekannt“ erzeugt keine affirmative gespeicherte Angabe.
- [ ] „Keine Angabe des Betreibers“ wird als eigene affirmative Information gespeichert.
- [ ] Reine Unbekannt-Angaben erzeugen keine XP/Badge-Belohnung.

## Strukturierte Preise

- [ ] Preisangebot kann mit Produkt angelegt werden.
- [ ] Optionale Produktvariante funktioniert.
- [ ] Optionaler Anzeigename funktioniert.
- [ ] Ganzjähriger Preis funktioniert.
- [ ] Saisonaler Preis überschreibt nur den betroffenen Zeitraum desselben Angebots.
- [ ] Mehrere Preispositionen/Zuschläge sind möglich.
- [ ] Verknüpfung zu einem Hauptangebot funktioniert, sofern genutzt.
- [ ] Erstattbare Kaution/Deposit wird korrekt dargestellt.
- [ ] Status „inklusive“, „kostenlos“, „auf Anfrage“, „ab“ und feste Preise werden korrekt dargestellt.
- [ ] Abrechnungseinheiten und Mengen werden korrekt dargestellt.
- [ ] Normale Nutzer reichen Preisänderungen zur Moderation ein.
- [ ] Moderation kann strukturierte Preisvorschläge verständlich prüfen.
- [ ] Nicht vorhandene Preisarten werden im öffentlichen Profil nicht einzeln als „Noch keine Angabe“ aufgelistet.
- [ ] Sind keinerlei Preisangaben vorhanden, erscheint nur ein allgemeiner Hinweis wie „Noch keine Preisinformationen vorhanden“.
- [ ] Legacy-Preisangaben werden nur angezeigt, wenn tatsächlich ein verwertbarer Wert vorhanden ist.

## V1-Platztypen

- [ ] Beim neuen Platz werden nur die aktuellen V1-Typen angeboten.
- [ ] Campingplatz ist auswählbar.
- [ ] Wohnmobilstellplatz ist auswählbar.
- [ ] Zeltplatz ist auswählbar.
- [ ] Parkplatz ist auswählbar.
- [ ] Rastplatz / Autohof ist auswählbar.
- [ ] Freier Stellplatz ist auswählbar.
- [ ] Servicestation ist auswählbar.
- [ ] Camping & Outdoor ist auswählbar.
- [ ] Alte Legacy-Typen bleiben für bestehende Daten lesbar, werden aber nicht für neue Plätze angeboten.
- [ ] Rechts-/Übernachtungsstatus bleibt eine separate Eigenschaft und wird nicht aus dem Platztyp abgeleitet.

## Platz anlegen – neuer Zwei-Schritt-Workflow

- [ ] Schritt 1 fragt nur die nötigen Kerndaten ab: Name, Platztyp, Position.
- [ ] Ein Platz kann direkt nach Schritt 1 zur Prüfung eingereicht werden.
- [ ] Alternativ kann mit Schritt 2 fortgefahren werden.
- [ ] Schritt 2 ist ausdrücklich optional.
- [ ] Schritt 2 filtert nur nach den zum Platztyp gehörenden Kategorien.
- [ ] Innerhalb einer angezeigten Kategorie werden alle Merkmale dieser Kategorie angeboten.
- [ ] Nutzer kann Schritt 2 unverändert lassen und trotzdem einreichen.
- [ ] Nach Einreichen wird nicht behauptet, der Platz sei schon veröffentlicht.
- [ ] Rückmeldung sagt sinngemäß, dass der Platz zur Überprüfung weitergegeben wurde und nach Freigabe informiert wird.
- [ ] Nach Einreichen ist der Platz `pending`.
- [ ] Detailpflege erfolgt regulär nach Freigabe.

## Duplikatprüfung

- [ ] Duplikatprüfung reagiert auf Kartenposition.
- [ ] Veröffentlichte Plätze in der Nähe werden berücksichtigt.
- [ ] Bereits eingereichte, noch nicht freigegebene `pending`-Plätze werden berücksichtigt.
- [ ] Entwürfe werden nicht als öffentliche/pending Duplikate behandelt.
- [ ] Entfernte Plätze außerhalb des Suchradius werden nicht als Treffer angezeigt.
- [ ] Namensähnlichkeit beeinflusst die Reihenfolge sinnvoll.
- [ ] Warnung blockiert das Anlegen nicht hart.
- [ ] Beim Bearbeiten eines bestehenden Platzes kann dieser selbst aus der Duplikatprüfung ausgeschlossen werden.

## Platztypabhängige Merkmal-Kategorien

- [ ] Platzprofil zeigt die Standardkategorien des jeweiligen Platztyps.
- [ ] Nicht passende, komplett leere Kategorien sind standardmäßig verborgen.
- [ ] Eine eigentlich nicht standardmäßige Kategorie erscheint automatisch, sobald dort mindestens ein Merkmal gepflegt wurde.
- [ ] „Weitere Merkmale“ blendet zusätzliche Kategorien für Bearbeiter ein.
- [ ] „x von y Merkmalen bekannt“ zählt nur Standardkategorien plus tatsächlich gepflegte Sonderkategorien.
- [ ] Versteckte, leere Fremdkategorien erhöhen den Nenner nicht.
- [ ] Die aktuelle Typ-/Kategorie-Zuordnung wird nicht als endgültig betrachtet; vor Launch erfolgt ein eigener Katalog-Review.

## Rastplatz-/Autohof-Katalog

- [ ] Kategorie „Tanken & Rast“ ist vorhanden.
- [ ] Rastplatz-Schritt 2 zeigt die vollständigen Merkmale der zugeordneten Kategorien, nicht nur sechs handverlesene Quick-Features.
- [ ] Kraftstoff-/Tankstellenmerkmale sind verfügbar.
- [ ] E-Ladestation ist verfügbar.
- [ ] AdBlue/LPG/CNG/Wasserstoff können gepflegt werden.
- [ ] Shop/Hotel/Werkstatt/Reifenservice können gepflegt werden.
- [ ] PKW, Motorrad, Kleintransporter, LKW-Klassen, Sattel-/Lastzug und Reisebus sind als Fahrzeugtypen vorhanden.
- [ ] Gesicherter LKW-Parkplatz und weitere Rastplatzservices können gepflegt werden.

## Pflege bestehender Plätze

- [ ] Jeder veröffentlichte Platz besitzt sinnvolle Editier-/Änderungsvorschlagswege für alle relevanten Bereiche.
- [ ] Name kann geändert/vorgeschlagen werden.
- [ ] Platztyp kann geändert/vorgeschlagen werden.
- [ ] Kartenposition kann geändert/vorgeschlagen werden.
- [ ] Nutzungs-/Rechtsstatus kann geändert/vorgeschlagen werden.
- [ ] Betreiber kann geändert/vorgeschlagen werden.
- [ ] Stellplatzzahl kann geändert/vorgeschlagen werden.
- [ ] Betriebsart kann geändert/vorgeschlagen werden.
- [ ] Öffnungsstatus kann geändert/vorgeschlagen werden.
- [ ] Website kann geändert/vorgeschlagen bzw. entfernt werden.
- [ ] Adresse kann geändert/vorgeschlagen werden.
- [ ] Beschreibung, Anfahrt und Zugang können geändert/vorgeschlagen werden.
- [ ] Geeignete Fahrzeugtypen können geändert/vorgeschlagen werden.
- [ ] Merkmale besitzen direkte Stift-/Vorschlagsaktionen.
- [ ] Öffnungszeiten besitzen eigenen Editor.
- [ ] Preise besitzen eigenen Editor.
- [ ] Position kann im Änderungsworkflow per Karte korrigiert werden.
- [ ] Positionsänderung nutzt die Duplikatwarnung und schließt den aktuellen Platz selbst aus.
- [ ] Normaler Nutzer erzeugt Change Requests statt Live-Änderungen.
- [ ] Freigegebene Änderung wird historisch/auditierbar angewendet.
- [ ] Überflüssige Links, die nur auf denselben Abschnitt springen, sind entfernt.

## V1-Vorbereitung / bewusst noch offen

- [ ] Vor Launch vollständigen Merkmal-/Kategoriekatalog gemeinsam prüfen.
- [ ] Kategorien ggf. stärker spezialisieren und anschließend Platztypen neu zuordnen.
- [x] Technische DE/EN-Abdeckung der aktuell vorhandenen V1-Oberflächen und sichtbaren Workflow-Fehler umgesetzt.
- [ ] Manuellen DE/EN-Rundgang mit Betatestern vollständig durchführen und sprachabhängige Auffälligkeiten protokollieren.
- [ ] Desktop- und Mobile-Pass über alle V1-Kernworkflows durchführen.
- [ ] Offizielle/open-data Quellen als initiale reale Basisdaten importieren.
- [ ] Import ist wiederholbar und überschreibt Community-/Owner-/Admin-Daten nicht blind.
- [ ] API-/Importänderungen erscheinen transparent mit konkreter Quelle im Platzlog.
- [ ] Verified Visit/Check-in bleibt nach V1.
- [ ] Owner-Verifizierung bleibt nach V1.
- [ ] Temporäre Events/Festivals bleiben V2.

---

# V1-Sprachprüfung – Stand 2026-09-21

Die technische Umstellung der aktuell vorhandenen Oberfläche auf Deutsch und Englisch ist umgesetzt. Dieser Abschnitt dient der manuellen Abnahme durch Betatester. Die Punkte gelten erst als geprüft, wenn die jeweilige Seite beziehungsweise Aktion tatsächlich in beiden Sprachen geöffnet oder ausgeführt wurde.

## Hinweise für Betatester

- Systemtexte, Schaltflächen, Hilfetexte, Statusmeldungen und sichtbare Fehlermeldungen müssen der gewählten Sprache folgen.
- Von Nutzern verfasste Inhalte wie Rezensionen, Kommentare, Beschreibungen und Meldungen bleiben absichtlich in der Sprache erhalten, in der sie geschrieben wurden.
- Interne Audit-, Seeder- und Konsolentexte gehören nicht zur übersetzten Benutzeroberfläche.
- Bei einem Sprachfehler bitte die gewählte Sprache, Seite/Aktion, den falschen Text und möglichst einen Screenshot im Fehlerprotokoll festhalten.
- Besonders auf einzelne deutsche Wörter in der englischen Oberfläche, einzelne englische Wörter in der deutschen Oberfläche und sichtbare Übersetzungsschlüssel wie `admin.example.key` achten.

## Sprachwechsel und Speicherung

- [ ] Sprache kann auf Deutsch gestellt werden.
- [ ] Sprache kann auf Englisch gestellt werden.
- [ ] Die gewählte Sprache bleibt beim Navigieren erhalten.
- [ ] Die gewählte Sprache bleibt nach einem Reload erhalten.
- [ ] Die gewählte Sprache bleibt nach Abmelden und erneutem Anmelden entsprechend der vorgesehenen Speicherung erhalten.
- [ ] Direkte Links öffnen die Seite in der aktuell gewählten Sprache.
- [ ] Nach Formularfehlern oder Weiterleitungen bleibt die gewählte Sprache erhalten.
- [ ] Es erscheinen keine rohen Übersetzungsschlüssel.
- [ ] Es erscheinen keine Platzhalter wie `:name`, `:count` oder `:id` ohne eingesetzten Wert.

## Öffentliche Seiten und Gastzugriff

- [ ] Platzsuche, Filter, Sortierung und leere Ergebniszustände sind vollständig auf Deutsch.
- [ ] Platzsuche, Filter, Sortierung und leere Ergebniszustände sind vollständig auf Englisch.
- [ ] Kartenhinweise, Karten-Popups und Gastdialoge folgen der gewählten Sprache.
- [ ] Öffentliche Platzprofile einschließlich Merkmalen, Öffnungszeiten, Preisen und Fototexten sind in beiden Sprachen stimmig.
- [ ] Platztypen, Merkmale, Optionen, Einheiten und Preisprodukte verwenden die passende Übersetzung aus den Katalogdaten.
- [ ] Hilfe, Known Bugs & Roadmap sowie öffentliche Supportseiten sind in beiden Sprachen bedienbar.
- [ ] Nutzerverfasste Rezensionen und Platztexte werden beim Sprachwechsel nicht verändert.

## Registrierung und Benutzerkonto

- [ ] Registrierung, Login, E-Mail-Verifizierung und Passwort-Reset sind vollständig auf Deutsch.
- [ ] Registrierung, Login, E-Mail-Verifizierung und Passwort-Reset sind vollständig auf Englisch.
- [ ] Profil-, Sicherheits-, Passkey- und Zwei-Faktor-Seiten enthalten keine fremdsprachigen Resttexte.
- [ ] Community-Profil, Sichtbarkeitseinstellungen und öffentliche Profilseite folgen der gewählten Sprache.
- [ ] Level-, XP-, Badge- und Achievement-Texte erscheinen passend auf Deutsch und Englisch.
- [ ] Systemgenerierte XP-Beschreibungen wechseln die Sprache; frei eingegebene beziehungsweise manuelle Texte bleiben unverändert.
- [ ] Benachrichtigungseinstellungen, Glocke, Verlauf und Detailseiten sind in beiden Sprachen vollständig.

## Plätze, Änderungen und Moderation

- [ ] Neuen Platz vorschlagen und Entwurf einreichen funktioniert vollständig auf Deutsch.
- [ ] Neuen Platz vorschlagen und Entwurf einreichen funktioniert vollständig auf Englisch.
- [ ] Duplikatwarnungen und Bestätigungen folgen der gewählten Sprache.
- [ ] Merkmale bearbeiten beziehungsweise vorschlagen zeigt in beiden Sprachen passende Beschriftungen und Rückmeldungen.
- [ ] Öffnungszeiten anlegen, ändern und vorschlagen ist in beiden Sprachen vollständig.
- [ ] Preise anlegen, ändern und vorschlagen ist in beiden Sprachen vollständig.
- [ ] Überschneidungswarnungen bei Öffnungszeiten und Preisen sind in beiden Sprachen verständlich.
- [ ] Reviews, Review-Paginierung, Fotos und Fotoauswahl nach einem Merge sind in beiden Sprachen vollständig.
- [ ] Admin-Moderation, Änderungsvorschläge, Platz-Merge, Merkmalsverwaltung, Benutzerverwaltung und Support-Administration sind in beiden Sprachen vollständig.
- [ ] Rollen-Vorschau und Zugriffsfehler folgen der gewählten Sprache.

## Fehler- und Randzustände

- [ ] Pflichtfeldfehler erscheinen auf Deutsch, wenn Deutsch gewählt ist.
- [ ] Pflichtfeldfehler erscheinen auf Englisch, wenn Englisch gewählt ist.
- [ ] Nicht mehr vorhandene oder zwischenzeitlich geänderte Preisangebote liefern eine verständliche Meldung in der gewählten Sprache.
- [ ] Veraltete Öffnungszeiten- oder Preisvorschläge liefern eine verständliche Meldung in der gewählten Sprache.
- [ ] Fehlende beziehungsweise nicht verfügbare Workflows zeigen keine technische Exception oder Seeder-Anweisung.
- [ ] Nicht erlaubte Aktionen liefern den vorgesehenen neutralen Fehlerzustand ohne Sprachmix.
- [ ] Leere Listen und „noch keine Daten“-Zustände sind in beiden Sprachen verständlich.
- [ ] Singular und Plural sind bei 0, 1 und mehreren Einträgen plausibel.
- [ ] Datum, Uhrzeit und Zahlenformat wirken in Deutsch und Englisch passend.
- [ ] Lange englische Texte verursachen keine abgeschnittenen Buttons, Überlagerungen oder unlesbaren Layouts.

## Abschluss der Sprachprüfung

- [ ] Mindestens ein vollständiger Rundgang wurde auf Deutsch durchgeführt.
- [ ] Mindestens ein vollständiger Rundgang wurde auf Englisch durchgeführt.
- [ ] Mindestens ein Rundgang erfolgte auf Desktop.
- [ ] Mindestens ein Rundgang erfolgte auf einem kleinen beziehungsweise mobilen Bildschirm.
- [ ] Alle gefundenen Sprachfehler wurden im Fehlerprotokoll mit Sprache dokumentiert.
- [ ] Kritische und hohe Sprach-/Workflowfehler sind behoben oder ausdrücklich als Blocker dokumentiert.

