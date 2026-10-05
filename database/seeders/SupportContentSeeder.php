<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SupportContentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $obsoleteArticleSlugs = [
            'platz-details-bearbeiten',
            'merkmale-eines-platzes-eintragen',
            'platzvorschlag-pruefen-und-einreichen',
        ];

        $obsoleteArticleIds = DB::table('support_articles')
            ->whereIn('slug', $obsoleteArticleSlugs)
            ->pluck('id');

        if ($obsoleteArticleIds->isNotEmpty()) {
            if (Schema::hasTable('support_article_translations')) {
                DB::table('support_article_translations')
                    ->whereIn('support_article_id', $obsoleteArticleIds)
                    ->delete();
            }

            DB::table('support_articles')
                ->whereIn('id', $obsoleteArticleIds)
                ->delete();
        }

        $articles = [
            [
                'slug' => 'plaetze-suchen-und-filtern',
                'title' => 'Plätze finden',
                'summary' => 'So findest du dich auf der Platzsuche zurecht.',
                'context_key' => 'place-browse',
                'sort_order' => 10,
                'body' => "Auf dieser Seite kannst du nach Plätzen suchen und die Ergebnisse mit verschiedenen Filtern eingrenzen.\n\n## Einen Platz suchen\n\nDie **Suche oben** hilft dir, einen bestimmten Platz oder Plätze an einem bestimmten Ort zu finden. Gib dafür einfach einen Namen oder einen Ort ein.\n\n## Ergebnisliste und Karte\n\nDie **Ergebnisliste** zeigt dir alle Plätze, die zu deiner aktuellen Suche passen.\n\n- Auf einem Computer findest du die Liste in der Mitte der Seite.\n- Auf einem Handy steht sie unterhalb der Karte.\n\nDie **Karte** zeigt dir die gleichen Ergebnisse als Markierungen. Klickst du eine Markierung an, bekommst du eine kurze Vorschau. Mit **\"Profil öffnen\"** kommst du zur vollständigen Platzseite.\n\n## Ergebnisse filtern\n\nMit den **Filtern** kannst du die Ergebnisse weiter eingrenzen - zum Beispiel nach Platztyp, Ausstattung oder anderen Eigenschaften.\n\n- Auf einem Computer findest du die Filter links.\n- Auf einem Handy öffnest du sie über das **Trichtersymbol oben links**.\n- Mit **+** fügst du einen Filter zu deiner Suche hinzu.\n- Mit **-** entfernst du einen bereits ausgewählten Filter wieder.\n\n## Einen Platz öffnen\n\nEinen interessanten Platz gefunden? Klick einfach auf den Eintrag in der Ergebnisliste.\n\nAlternativ kannst du ihn auf der Karte auswählen und dort auf **\"Profil öffnen\"** klicken.",
            ],
            [
                'slug' => 'platzprofil-verstehen',
                'title' => 'Das Platzprofil verstehen',
                'summary' => 'Erklärung zu Status, Merkmalen, Standort und Änderungsvorschlägen.',
                'context_key' => 'place-profile',
                'sort_order' => 20,
                'body' => "Auf dem Platzprofil findest du alle Informationen, die Camperwolf aktuell über diesen Platz kennt.\n\n## Basisinformationen\n\nGanz oben findest du die wichtigsten **Basisinformationen zum Platz** - zum Beispiel Angaben zum Betreiber und zum aktuellen Status.\n\nMit **\"Verlauf\"** kannst du die Änderungshistorie des Platzes einblenden. Dort kannst du nachvollziehen, welche Informationen wann geändert wurden.\n\n## Geeignet für\n\nHier siehst du, **für welche Fahrzeuge der Platz vorgesehen ist**. So kannst du schnell erkennen, ob der Platz grundsätzlich zu deinem Fahrzeug passt.\n\n## Anfahrt & Zugang\n\nHier findest du Informationen, die dir bei der Planung deiner Anreise helfen.\n\nDazu gehören zum Beispiel:\n\n- Kontaktdaten\n- Informationen zur Zufahrt und zum Zugang\n- Adresse und Standort\n- Links zu verschiedenen Kartendiensten\n\n## Öffnungszeiten\n\nHier findest du die **aktuell bei Camperwolf bekannten Öffnungszeiten**.\n\n> **Wichtig:** Die Angaben bei Camperwolf werden von der Community gepflegt und können sich geändert haben. Prüfe deshalb vor einer längeren Anreise wichtige Informationen wie Öffnungszeiten am besten noch einmal direkt beim Betreiber oder auf dessen Webseite.\n\n## Ausstattung & Merkmale\n\nHier findest du alle **aktuell bekannten Eigenschaften und Ausstattungsmerkmale** des Platzes.\n\nIn der Regel gilt:\n\n- **Grün** - vorhanden\n- **Rot** - nicht vorhanden\n- **Grau** - noch nicht bekannt\n\nBei einigen Merkmalen findest du zusätzliche Angaben, zum Beispiel einen Preis, eine Entfernung oder technische Details.\n\n## Bewertungen & Rezensionen\n\nHier findest du die **Bewertungen und Rezensionen anderer Nutzer**.\n\nJe nach vorhandenen Beiträgen kannst du dort auch Fotos sehen, die Nutzer zu diesem Platz hochgeladen haben.",
            ],
            [
                'slug' => 'platz-vorschlagen-schritt-fuer-schritt',
                'title' => 'Einen neuen Platz vorschlagen – Schritt für Schritt',
                'summary' => 'Vom Standort über die Merkmale bis zur Einreichung.',
                'context_key' => 'place-create-step-1',
                'sort_order' => 30,
                'body' => "Du kennst einen Platz, der noch nicht bei Camperwolf eingetragen ist? Dann kannst du ihn hier vorschlagen.\n\nFür einen neuen Platz brauchen wir zunächst nur wenige Informationen. Alles Weitere kannst du freiwillig ergänzen - oder später der Community überlassen.\n\n## 1. Name des Platzes\n\nGib den **Namen des Platzes** ein.\n\nVerwende am besten den offiziellen Namen des Betreibers oder den Namen, unter dem der Platz allgemein bekannt ist.\n\n## 2. Platztyp auswählen\n\nWähle den Platztyp aus, der den Platz am besten beschreibt:\n\n- **Campingplatz** - Klassischer Campingplatz für Wohnmobile, Wohnwagen, Zelte und ähnliche Campingformen.\n- **Wohnmobilstellplatz** - Ein Stellplatz speziell für Wohnmobile oder Camper, häufig mit Parkplatzcharakter.\n- **Zeltplatz** - Ein Platz speziell für Zelte, zum Beispiel Trekking-, Jugend- oder Pfadfinderplätze.\n- **Parkplatz** - Ein normaler Parkplatz. Ob dort Übernachten erlaubt oder geduldet ist, wird unabhängig davon erfasst.\n- **Rastplatz / Autohof** - Rastplatz, Raststätte oder Autohof für Reisende, einschließlich LKW-Verkehr.\n- **Freier Stellplatz** - Ein einfacher Stellplatz außerhalb klassischer Camping- oder Parkplatzstrukturen, zum Beispiel eine Wiese, ein Hof oder ein Platz am See.\n- **Servicestation** - Ein Ort für campingbezogene Dienstleistungen wie Frischwasser, Entsorgung, Waschanlage oder Werkstatt.\n- **Camping-/Outdoor-Shop** - Camping- oder Outdoor-Fachhandel und andere dauerhaft campingbezogene Geschäfte.\n\n## 3. Position festlegen\n\nAls Nächstes brauchen wir die **genaue Position des Platzes**.\n\nDafür hast du **drei alternative Möglichkeiten** - du musst also nur eine davon verwenden. Sobald du eine Position auf einem anderen Weg festlegst, wird die bisherige Auswahl entsprechend ersetzt:\n\n- Suche nach einer Adresse.\n- Oder klicke die Position direkt auf der Karte an. Camperwolf versucht dann, die passende Adresse automatisch zu ergänzen.\n- Oder gib die Koordinaten direkt ein, wenn du sie kennst.\n\nKoordinaten kannst du zum Beispiel in dieser Form eingeben:\n\n\`51.1234567, 7.1234567\`\n\nAchte darauf, möglichst die tatsächliche Position des Platzes und nicht nur einen ungefähren Punkt in der Umgebung auszuwählen.\n\n## Das reicht bereits\n\nName, Platztyp und Position reichen aus, um einen neuen Platz vorzuschlagen.\n\nWenn du keine weiteren Informationen ergänzen möchtest, kannst du jetzt auf **\"Zur Prüfung einreichen\"** klicken.\n\nMöchtest du direkt noch etwas über die Ausstattung des Platzes eintragen, wähle stattdessen **\"Merkmale ergänzen\"**.\n\n## Optional: Merkmale ergänzen\n\nIm nächsten Schritt findest du die Merkmale, die für den gewählten Platztyp vorgesehen sind.\n\nKlicke auf ein Merkmal, um zu sehen, welche Angaben du dazu machen kannst.\n\nBei manchen Merkmalen geht es nur darum, ob etwas **vorhanden** oder **nicht vorhanden** ist. Bei anderen kannst du zusätzliche Informationen eintragen - zum Beispiel Entfernungen, Maße, Preise oder technische Details.\n\nEs gibt **keine Mindestanzahl an Merkmalen**, die du ausfüllen musst.\n\nTrage einfach ein, was du nach bestem Wissen sicher sagen kannst. Wenn du etwas nicht weißt, lass das Merkmal offen. Eine fehlende Angabe bedeutet bei Camperwolf **\"unbekannt\"** und nicht **\"nicht vorhanden\"**.\n\nFehlende Informationen können später jederzeit von dir oder anderen Mitgliedern der Community ergänzt werden.\n\n## Vorschlag einreichen\n\nWenn du fertig bist, klicke auf **\"Zur Prüfung einreichen\"**.\n\nDein Vorschlag wird anschließend von einem Moderator oder Administrator geprüft. Sobald eine Entscheidung getroffen wurde, erhältst du eine Benachrichtigung über das **Glockensymbol**.",
            ],
            [
                'slug' => 'benachrichtigungen',
                'title' => 'Benachrichtigungen',
                'summary' => 'Persönliche Ereignisse und wichtige Hinweise von Camperwolf.',
                'context_key' => 'notifications',
                'sort_order' => 40,
                'body' => <<<'TEXT'
Über die **Glocke im Kopfbereich** informiert dich Camperwolf über Dinge, die für dich wichtig sein könnten.

Dazu gehören zum Beispiel:

- einer deiner Vorschläge wurde geprüft oder freigeschaltet
- eines deiner Fotos wurde als hilfreich bewertet - bei häufig bewerteten Fotos erhältst du nicht für jeden einzelnen Klick eine Meldung, sondern nur bei bestimmten Meilensteinen
- du hast ein Achievement, eine neue Badge-Stufe oder eine besondere Auszeichnung erhalten
- eine deiner Änderungen oder anderen Beiträge wurde moderiert
- wichtige Informationen von Camperwolf, zum Beispiel über geplante Wartungsarbeiten, neue Funktionen oder Community-Aktionen

Neue Benachrichtigungen erkennst du direkt an der Glocke. Über die Benachrichtigungsseite kannst du auch ältere Meldungen noch einmal ansehen.

## Wie lange bleiben Benachrichtigungen gespeichert?

Benachrichtigungen werden nicht unbegrenzt gespeichert.

**Gelesene Benachrichtigungen** werden normalerweise nach 30 Tagen entfernt.

**Ungelesene Benachrichtigungen** können länger erhalten bleiben. Bei längerer Inaktivität können sehr alte ungelesene Meldungen nach 180 Tagen entfernt werden.

Wichtige Systemmeldungen können länger verfügbar bleiben.
TEXT,
            ],
            [
                'slug' => 'benachrichtigungseinstellungen',
                'title' => 'Benachrichtigungseinstellungen',
                'summary' => 'Lege fest, über welche Ereignisse du informiert werden möchtest.',
                'context_key' => 'notification-settings',
                'sort_order' => 41,
                'body' => "Hier kannst du festlegen, über welche Ereignisse du per [[benachrichtigungen|Benachrichtigung]] informiert wirst.\n\n- **Entscheidungen zu meinen Vorschlägen** - Informiert dich, wenn einer deiner Änderungsvorschläge bestätigt oder abgelehnt wurde.\n- **Änderungen an meinen Favoriten** - Informiert dich über bestätigte Änderungen an Plätzen, die du als Favorit gespeichert hast.\n- **Allgemeine Camperwolf-Hinweise** - Informiert dich über allgemeine Neuigkeiten und Hinweise von Camperwolf, zum Beispiel über geplante Wartungsarbeiten oder Änderungen an der Plattform.\n\n> **Wichtig:** Meldungen, die für den sicheren oder zuverlässigen Betrieb von Camperwolf notwendig sind, können unabhängig von diesen Einstellungen angezeigt werden.",
            ],
            [
                'slug' => 'profil-und-kontodaten',
                'title' => 'Profil und Kontodaten',
                'summary' => 'Benutzername und E-Mail-Adresse im Benutzerkonto verwalten.',
                'context_key' => 'account-profile',
                'sort_order' => 45,
                'body' => "Im Profilbereich kannst du deinen **Benutzernamen** und die **E-Mail-Adresse** deines Camperwolf-Kontos ändern.\n\nDer Benutzername ist nicht Teil deines öffentlichen Camperwolf-Profils.\n\nWenn du deine E-Mail-Adresse änderst, musst du die neue Adresse anschließend erneut bestätigen.",
            ],
            [
                'slug' => 'sicherheit-im-benutzerkonto',
                'title' => 'Sicherheit im Benutzerkonto',
                'summary' => 'Passwort, Zwei-Faktor-Authentifizierung und Passkeys verwalten.',
                'context_key' => 'account-security',
                'sort_order' => 46,
                'body' => "Hier kannst du die Sicherheit deines Camperwolf-Kontos verwalten.\n\n- **Passwort ändern** - Ändere dein aktuelles Passwort. Verwende am besten ein eigenes, starkes Passwort, das du nicht auch für andere Dienste benutzt.\n- **Zwei-Faktor-Authentifizierung (2FA)** - Schützt dein Konto zusätzlich mit einem Code aus einer Authenticator-App. Nach der Aktivierung brauchst du beim Anmelden neben deinem Passwort auch diesen Code. Du erhältst außerdem Wiederherstellungscodes für den Fall, dass du keinen Zugriff mehr auf deine Authenticator-App hast.\n- **Passkeys** - Ermöglichen eine Anmeldung ohne Passwort, zum Beispiel mit Windows Hello, Fingerabdruck, Gesichtserkennung oder der Gerätesperre deines Smartphones. Welche Möglichkeiten verfügbar sind, hängt von deinem Gerät und Browser ab.\n\n> **Wichtig:** Richte 2FA oder einen Passkey nur ein, wenn du weißt, wie du später darauf zugreifen kannst. Bewahre Wiederherstellungscodes sicher auf und entferne einen alten Passkey erst, wenn du eine andere Möglichkeit zur Anmeldung hast.\n\nTeile dein Passwort, deine 2FA-Codes und deine Wiederherstellungscodes niemals mit anderen Personen.",
            ],
            [
                'slug' => 'darstellung-anpassen',
                'title' => 'Darstellung anpassen',
                'summary' => 'Helles oder dunkles Erscheinungsbild auswählen.',
                'context_key' => 'account-appearance',
                'sort_order' => 47,
                'body' => "In den Darstellungseinstellungen kannst du auswählen, wie Camperwolf für dich aussehen soll.\n\nDu kannst zwischen einem **hellen** und einem **dunklen** Erscheinungsbild wählen.\n\nDie Einstellung betrifft nur die Darstellung von Camperwolf und kann jederzeit wieder geändert werden.",
            ],
            [
                'slug' => 'regeln-und-empfehlungen-fuer-fotouploads',
                'title' => 'Regeln und Empfehlungen für Fotouploads',
                'summary' => 'Welche Fotos bei Camperwolf erlaubt sind und welche Bilder anderen Campern besonders helfen.',
                'context_key' => 'photo-rules',
                'sort_order' => 48,
                'body' => <<<'TEXT'
Fotos sollen anderen Campern zeigen, **wie ein Platz wirklich aussieht**. Damit die Platzgalerien hilfreich und fair bleiben, gelten ein paar Regeln.

## Verbindliche Regeln

- **Keine Nacktheit oder sexualisierten Inhalte.** Sichtbare Geschlechtsteile, entblößte weibliche Brust und pornografische Inhalte sind nicht erlaubt - auch nicht bei FKK-Plätzen.
- **Keine rechtswidrigen oder jugendgefährdenden Inhalte.** Dazu gehören zum Beispiel verbotene Symbole, Gewalt- oder Gore-Inhalte, illegale Drogen, rechtswidrige Waffeninhalte und andere unzulässige Darstellungen. Es gelten insbesondere das anwendbare deutsche Recht und unmittelbar geltendes EU-Recht.
- **Camperwolf kann auch andere unpassende Inhalte ablehnen**, wenn sie gegen diese Plattformregeln verstoßen, selbst wenn sie nicht eindeutig rechtswidrig sind.
- **Achte auf die Rechte anderer Personen.** Lade keine Fotos mit erkennbaren Personen hoch, wenn du nicht sicher bist, dass sie mit der Veröffentlichung einverstanden sind. Beachte außerdem Fotografierverbote und die Regeln des jeweiligen Platzes.
- **Fotos in der Platzgalerie müssen zum Platz gehören.** Dazu zählen zum Beispiel Stellflächen, Zufahrt, Sanitäranlagen, Rezeption, Ver- und Entsorgung oder die direkt relevante Umgebung. Reine Selfies und private Urlaubsbilder gehören nicht in die Platzgalerie.
- **Lade nur Fotos hoch, die du verwenden darfst.** Übernimm keine Bilder von Webseiten, Suchmaschinen, Social Media, Buchungsportalen oder Betreiberseiten, wenn du dafür keine ausdrücklichen Nutzungsrechte hast.
- **Keine privaten oder vertraulichen Informationen.** Dazu gehören zum Beispiel Buchungsdaten, Zugangscodes, QR-Codes, E-Mail-Adressen oder Telefonnummern.
- **Keine irreführenden Bilder.** KI-generierte Platzfotos, Fotomontagen oder Bearbeitungen, die den tatsächlichen Zustand wesentlich verändern, sind nicht erlaubt.
- **Keine Werbung oder Spam.** Werbegrafiken, Rabattcodes, Flyer oder Bilder, die hauptsächlich der Eigenwerbung dienen, sind nicht erlaubt.

## Empfehlungen für Platzfotos

Am hilfreichsten sind Fotos, mit denen andere Camper den Platz schon vor der Anreise besser einschätzen können.

Gute Motive sind zum Beispiel:

- Stellflächen, Größe und Bodenbeschaffenheit
- Ein- und Ausfahrt sowie schwierige Zufahrten
- Ver- und Entsorgung
- Stromanschlüsse und andere Infrastruktur
- Sanitäranlagen
- Rezeption, Automaten und Beschilderung
- Barrieren, Höhenbegrenzungen oder andere erkennbare Einschränkungen
- Aussicht und unmittelbare Umgebung
- nahe Infrastruktur, wenn sie für den Platz praktisch wichtig ist
- sachlich dokumentierte Mängel wie Schlaglöcher oder defekte Einrichtungen

Vermeide nach Möglichkeit erkennbare Personen - auch dich selbst. **Der Platz sollte im Mittelpunkt stehen.**

Mehrere unterschiedliche Motive helfen mehr als viele fast identische Fotos. Verwende möglichst aktuelle und realistische Aufnahmen ohne starke Filter.

## Empfehlungen für Profilbilder

Ein Profilbild muss natürlich keinen Bezug zu einem Platz haben. Wähle einfach ein Bild, mit dem du dich in der Community zeigen möchtest.

Die verbindlichen Fotoregeln gelten trotzdem. Sind andere Personen erkennbar, stelle sicher, dass sie mit der Veröffentlichung einverstanden sind.
TEXT,
            ],
            [
                'slug' => 'regeln-und-empfehlungen-fuer-rezensionen',
                'title' => 'Regeln und Empfehlungen für Rezensionen',
                'summary' => 'Wie Rezensionen anderen Campern helfen und welche Inhalte bei Camperwolf nicht erlaubt sind.',
                'context_key' => 'review-rules',
                'sort_order' => 49,
                'body' => <<<'TEXT'
Rezensionen sollen anderen Campern helfen, einen Platz **realistisch einzuschätzen**. Gute Erfahrungen sind dabei genauso willkommen wie schlechte.

## Verbindliche Regeln

- **Keine rechtswidrigen, pornografischen, gewaltverherrlichenden, diskriminierenden, beleidigenden oder bedrohenden Inhalte.** Es gelten insbesondere das anwendbare deutsche Recht und unmittelbar geltendes EU-Recht. Camperwolf kann außerdem Inhalte ablehnen, die gegen diese Plattformregeln verstoßen.
- **Keine persönlichen oder vertraulichen Daten.** Veröffentliche keine privaten Namen, Telefonnummern, E-Mail-Adressen, Wohnadressen, Kennzeichen, Buchungsnummern oder ähnliche Angaben. Öffentlich angegebene geschäftliche Informationen eines Platzes oder Betreibers darfst du sachlich nennen.
- **Keine erfundenen Erfahrungen oder wissentlich falschen Behauptungen.** Beschreibe nur Dinge, die du nach bestem Wissen für richtig hältst.
- **Keine unbelegten schweren Anschuldigungen.** Behauptungen über Straftaten, Betrug, Diebstahl oder ähnlich schweres Fehlverhalten dürfen nicht ohne eine verlässliche Grundlage als Tatsache dargestellt werden.
- **Keine Rachebewertungen oder persönlichen Auseinandersetzungen.** Was aus einem Konflikt für andere Camper wichtig ist, darfst du sachlich beschreiben. Aus "Der Betreiber hat mir verboten, meinen Wagen zu waschen" kann zum Beispiel die hilfreiche Information "Fahrzeugwäsche ist auf dem Platz nicht erlaubt" werden.
- **Bleib beim Platz und deinem Aufenthalt.** Die Rezension muss sich auf den Platz, seinen Betrieb oder etwas beziehen, das für den Aufenthalt relevant ist.
- **Keine Werbung, kein Spam, keine Affiliate-Inhalte und keine Eigenwerbung.**
- **Keine kopierten Rezensionen oder fremden Texte**, wenn du nicht die nötigen Rechte daran hast.
- **Keine manipulierten Bewertungen.** Bewerte nicht deinen eigenen Platz über ein normales Nutzerkonto. Gekaufte, beauftragte oder gezielt im Interesse eines Betreibers oder Konkurrenten erstellte Rezensionen sind ebenfalls nicht erlaubt.
- **Stelle bekannte alte Zustände nicht absichtlich als aktuell dar.** Eine ältere Rezension wird dadurch nicht automatisch falsch. Entscheidend ist, dass sie deine tatsächliche Erfahrung zum damaligen Zeitpunkt beschreibt.

## Empfehlungen

Schreibe so, dass andere Camper verstehen können, **was du erlebt hast und warum du den Platz so bewertest**.

- Beschreibe möglichst deine eigene Erfahrung.
- **Sei konkret.** "Nachts war der Straßenverkehr deutlich hörbar" hilft mehr als "schlechter Platz".
- **Unterscheide Beobachtung und persönliche Meinung.** "Für meinen 7-m-Camper waren die Stellplätze eng" ist hilfreicher als "Die Stellplätze sind unbrauchbar".
- Nenne wichtigen Kontext, zum Beispiel Reisezeit, Fahrzeuggröße, volle oder ruhige Saison oder besondere Wetterbedingungen.
- **Sachliche Kritik ist ausdrücklich willkommen.** Eine schlechte Erfahrung darf auch zu einer klar negativen Rezension führen.
- Besonders hilfreich sind Erfahrungen zu Sauberkeit, Ruhe, Zufahrt, Stellplatzgröße, Umgebung, Versorgung, Sanitäranlagen, Sicherheit, Nutzbarkeit und dem Umgang mit konkreten Problemen.
- Beschreibe Regeln und Einschränkungen sachlich, wenn sie für andere Camper wichtig sind.
- Vermeide lange Nebengeschichten, die nichts über den Platz aussagen.

> **Eine negative Erfahrung musst du nicht künstlich ausgleichen.** Entscheidend ist, dass deine Rezension deine tatsächliche Erfahrung sachlich und nach bestem Wissen beschreibt.
TEXT,
            ],
            [
                'slug' => 'ueber-camperwolf',
                'title' => 'Über Camperwolf',
                'summary' => 'Warum es Camperwolf gibt, was wir anders machen wollen und wohin sich das Projekt entwickeln soll.',
                'context_key' => 'about-camperwolf',
                'sort_order' => 4,
                'body' => <<<'TEXT'
## Warum noch eine Camping-Plattform?

Camping-Apps, Stellplatzverzeichnisse und Webseiten gibt es bereits einige. Warum also noch eine?

Die Frage habe ich mir natürlich auch gestellt.

Als Camper kenne ich viele der bestehenden Angebote selbst. Und genauso kenne ich einige Dinge, die mich dabei immer wieder stören: Funktionen verschwinden hinter einer Paywall, eigentlich ganz normale Plätze werden mit "Premium", "VIP" oder anderen besonderen Empfehlungen hervorgehoben und bei manchen Einträgen weiß man irgendwann nicht mehr, ob eine Information wirklich noch aktuell ist - oder warum ein bestimmter Platz eigentlich besonders empfohlen wird.

Dabei sind gerade beim Camping aktuelle und verlässliche Informationen wichtiger als irgendein Premium-Siegel.

Also entstand die Idee zu Camperwolf.

## Was soll Camperwolf anders machen?

Camperwolf soll eine **kostenlose und von der Community gepflegte Platzdatenbank** sein.

Alle Funktionen der Plattform sollen grundsätzlich allen Nutzern zur Verfügung stehen. Es gibt keine Premium-Mitgliedschaft, die bessere Suchfunktionen, zusätzliche Platzinformationen oder andere Vorteile freischaltet.

Die Grundlage der Datenbank bilden Informationen aus verschiedenen Datenquellen - soweit möglich aus öffentlichen und meist staatlichen Quellen - sowie die Beiträge der Camperwolf-Community.

Diese Daten sollen aber nicht einfach nur gesammelt werden.

Fehlende Informationen können ergänzt, falsche Angaben korrigiert und bestehende Einträge aktualisiert werden. Wer vor Ort feststellt, dass sich etwas geändert hat, kann dieses Wissen mit anderen teilen.

Je mehr Menschen Camperwolf nutzen und dabei mithelfen, desto aktueller und vollständiger kann die Datenbank werden.

## Die Community bestimmt, was Camperwolf braucht

Camperwolf stellt die technische Plattform bereit. Was daraus entsteht, soll sich aber an den Menschen orientieren, die sie tatsächlich benutzen.

Das betrifft nicht nur die Daten zu einzelnen Plätzen.

Fehlt ein Platz? Dann kann er vorgeschlagen werden.

Fehlt bei den Platzinformationen ein Merkmal, das für Camper wichtig ist? Dann kann daraus ein neues Merkmal werden.

Fehlt eine sinnvolle Möglichkeit bei der Suche oder gibt es eine bessere Idee für eine bestehende Funktion? Auch das kann die Plattform weiterentwickeln.

Camperwolf soll nicht vorgeben, welche Informationen Camper brauchen. Die Plattform soll die Werkzeuge bereitstellen, mit denen die Community diese Informationen sammeln, pflegen und nutzen kann.

## Was Camperwolf heute schon ausmacht

### Suche nach dem, was dir wichtig ist

Statt nur nach einem bestimmten Platz zu suchen, kannst du die Ergebnisse anhand verschiedener Merkmale eingrenzen.

Du suchst beispielsweise einen bestimmten Platztyp, brauchst Strom, reist mit Hund oder möchtest andere bestimmte Voraussetzungen erfüllt haben? Die Suche soll sich möglichst gut danach richten können, was **für dich** wichtig ist.

### Informationen gemeinsam aktuell halten

Jeder registrierte Nutzer kann dazu beitragen, die Daten besser zu machen.

Fehlende Plätze können vorgeschlagen, Informationen ergänzt und falsche oder veraltete Angaben korrigiert werden.

Das ist ein wesentlicher Teil des Konzepts: Die Menschen, die tatsächlich vor Ort sind, haben häufig die aktuellsten Informationen.

### Bewertungen mit nachvollziehbaren Kriterien

Ob jemand einen Platz schön findet, ist subjektiv.

Deshalb kombiniert Camperwolf persönliche Rezensionen mit festen Bewertungskriterien, die für Camper relevant sind. So bleibt Platz für die persönliche Erfahrung, während Bewertungen trotzdem besser miteinander vergleichbar werden.

## Und später?

Camperwolf soll mit der Community weiter wachsen. Einige Ideen gehen deshalb bereits deutlich über eine reine Platzdatenbank hinaus.

Dazu gehören zum Beispiel:

- **Gruppen** - Bereiche, in denen du dich mit anderen Campern vernetzen kannst. Vielleicht hast du neben dem Campen bestimmte Interessen, die du mit anderen teilst, möchtest dich mit Gleichgesinnten austauschen oder einfach gemeinsam mit Freunden einen Campingtrip planen. Gruppen könnten dafür gemeinsame Favoriten, Austausch und die Planung von Treffen oder Reisen bieten.
- **Wissen teilen** - eine gemeinschaftlich gepflegte Wissensdatenbank rund ums Camping. Von "Was brauche ich für meine erste Tour?" bis zu praktischen Tipps, Erfahrungen und Dingen, die man besser nicht macht.
- **Weitere Platz- und Veranstaltungstypen** - beispielsweise temporäre Campingmöglichkeiten bei Festivals, Märkten oder anderen Veranstaltungen sowie weitere Orte, die unterwegs für Camper interessant sein können.
- **Mehr Community-Funktionen** - Möglichkeiten zum Austausch untereinander, beispielsweise über Diskussionsbereiche oder ähnliche Funktionen.

Welche dieser Ideen tatsächlich umgesetzt werden und wie sie am Ende aussehen, ist noch nicht festgelegt. Auch hier soll gelten: **Wir bauen nicht einfach Funktionen, weil wir sie bauen können, sondern weil die Community sie sinnvoll nutzen kann.**

## Camperwolf soll mit seinen Nutzern wachsen

Am Ende ist die Idee ziemlich einfach:

**Camperwolf liefert die Plattform. Die Community macht daraus eine gute Platzdatenbank.**

Du kannst Camperwolf einfach zur Platzsuche benutzen. Wenn du möchtest, kannst du aber genauso dabei helfen, einen fehlenden Platz einzutragen, eine veraltete Information zu korrigieren oder deine Erfahrungen weiterzugeben.

**Dabei ist es völlig egal, ob du Camperwolf nur nutzt oder selbst Inhalte beiträgst. Wenn dir die Plattform dabei hilft, einen passenden Platz zu finden oder unterwegs eine nützliche Information liefert, dann funktioniert die Idee bereits.**

Und wenn Camperwolf irgendwann etwas fehlt, an das heute noch niemand gedacht hat, dann ist das vielleicht einfach das nächste Feature.
TEXT,
            ],
            [
                'slug' => 'verwendete-software-dienste-und-lizenzen',
                'title' => 'Verwendete Software, Dienste & Lizenzen',
                'summary' => 'Die wichtigsten technischen Bausteine und externen Dienste hinter Camperwolf.',
                'context_key' => 'technology-transparency',
                'sort_order' => 6,
                'body' => <<<'TEXT'
Camperwolf basiert auf verschiedenen Open-Source-Projekten und einigen externen Diensten. Auf dieser Seite nennen wir die wichtigsten Bausteine, die für Nutzer, Datenschutz oder Lizenzhinweise relevant sind.

Die Liste ist bewusst keine vollständige Aufzählung jeder einzelnen technischen Abhängigkeit. Kleine interne Bibliotheken und reine Entwicklungswerkzeuge werden hier nicht einzeln aufgeführt. Lizenz- und Copyright-Hinweise, die mit verwendeten Softwarepaketen ausgeliefert werden müssen, bleiben unabhängig davon in den jeweiligen Paketen und Projektdateien erhalten.

## Zentrale Software

### Laravel

Camperwolf verwendet **Laravel** als serverseitiges Web-Framework.

- Lizenz: **MIT License**
- Projekt: https://laravel.com
- Quellcode: https://github.com/laravel/framework

### Livewire

Für interaktive Teile der Benutzeroberfläche verwendet Camperwolf **Livewire**.

- Lizenz: **MIT License**
- Projekt: https://livewire.laravel.com
- Quellcode: https://github.com/livewire/livewire

### Livewire Flux

Teile der Benutzeroberfläche verwenden **Livewire Flux**.

- Lizenz: **proprietäre Lizenz**
- Projekt: https://fluxui.dev

Flux wird im Rahmen der dafür vorgesehenen Lizenz verwendet. Es handelt sich nicht um ein Open-Source-Paket unter MIT-, BSD- oder einer vergleichbaren freien Lizenz.

### Tailwind CSS

Für das Styling der Oberfläche verwendet Camperwolf **Tailwind CSS**.

- Lizenz: **MIT License**
- Projekt: https://tailwindcss.com
- Quellcode: https://github.com/tailwindlabs/tailwindcss

### Leaflet

Die interaktive Karte wird mit **Leaflet** dargestellt.

- Lizenz: **BSD 2-Clause License**
- Projekt: https://leafletjs.com
- Quellcode: https://github.com/Leaflet/Leaflet

## Karten- und Geodaten

### OpenStreetMap

Camperwolf verwendet Kartendaten von **OpenStreetMap**.

Die OpenStreetMap-Daten stehen unter der **Open Data Commons Open Database License (ODbL)**. Die vorgeschriebene Quellenangabe für OpenStreetMap und seine Mitwirkenden wird direkt an der Karte angezeigt.

- Informationen zu Copyright und Lizenz: https://www.openstreetmap.org/copyright

### Photon

Für Adresssuche, Rückwärtssuche und bestimmte Ortsauswahlen verwendet Camperwolf derzeit den öffentlichen **Photon**-Dienst von komoot.

Photon ist ein Open-Source-Geocoder auf Basis von OpenStreetMap-Daten.

- Software-Lizenz: **Apache License 2.0**
- Projekt: https://github.com/komoot/photon
- Öffentlicher Dienst: https://photon.komoot.io

Wenn du eine entsprechende Suchfunktion benutzt, wird eine Anfrage an den Photon-Dienst gesendet. Weitere Informationen dazu findest du in der [[faq|FAQ]] und insbesondere in der Datenschutzerklärung.

## E-Mail-Versand

### Brevo

Für transaktionale E-Mails wie E-Mail-Bestätigungen und Passwort-Zurücksetzungen verwendet Camperwolf **Brevo**.

Brevo ist ein externer Dienst und keine in Camperwolf eingebundene Open-Source-Bibliothek. Deshalb gibt es hier keine Open-Source-Lizenz anzugeben.

Weitere Informationen zur dabei stattfindenden Datenverarbeitung findest du in der Datenschutzerklärung.

## Was bedeutet das für Camperwolf?

Die genannten Projekte und Dienste bleiben Eigentum bzw. Werke ihrer jeweiligen Rechteinhaber. Die Nutzung innerhalb von Camperwolf bedeutet keine Partnerschaft, Empfehlung oder offizielle Verbindung mit diesen Anbietern.

Wo eine Lizenz eine sichtbare Quellenangabe verlangt, wird diese an der dafür vorgesehenen Stelle angezeigt. Das gilt insbesondere für die OpenStreetMap-Daten auf der Karte.

Diese Seite wird ergänzt, wenn neue wesentliche Dienste oder technische Komponenten hinzukommen, die für Transparenz, Datenschutz oder Lizenzhinweise relevant sind.
TEXT,
            ],
            [
                'slug' => 'faq',
                'title' => 'FAQ - Häufige Fragen zu Camperwolf',
                'summary' => 'Antworten auf häufige Fragen zu Camperwolf, dem Projekt und seinen Grundsätzen.',
                'context_key' => 'faq',
                'sort_order' => 5,
                'body' => <<<'TEXT'
## Wird Camperwolf Geld kosten?

Nein. Camperwolf soll grundsätzlich kostenlos nutzbar bleiben.

Es ist nicht vorgesehen, Funktionen hinter einer Paywall zu verstecken oder zahlenden Nutzern Vorteile gegenüber anderen Nutzern zu geben. Die grundlegende Idee ist eine frei nutzbare Plattform, die von den Beiträgen ihrer Community lebt.

## Wie finanziert sich Camperwolf?

Aktuell wird Camperwolf vollständig aus eigenen Mitteln finanziert.

Für die Zukunft möchten wir nicht ausschließen, freiwillige Spenden oder Werbung anzubieten, um einen Teil der laufenden Kosten für Server, E-Mail-Versand und andere Dienste zu decken.

Eine Finanzierung soll aber nicht dazu führen, dass wichtige Funktionen nur gegen Bezahlung verfügbar sind.

## Wer steckt hinter Camperwolf?

Camperwolf ist ein privat entwickeltes und unabhängiges Projekt. Hinter der Plattform steht derzeit kein Campingkonzern, Stellplatzbetreiber, Reiseveranstalter oder anderes großes Unternehmen.

Camperwolf ist aus der Idee entstanden, Informationen über Camping-, Stell-, Park- und Serviceplätze an einer Stelle zu sammeln und gemeinsam mit der Community aktuell zu halten.

## Warum fehlen noch Plätze oder Informationen?

Camperwolf befindet sich noch im Aufbau. Unsere Daten stammen aus verschiedenen öffentlichen Datenquellen und aus Beiträgen der Community.

Deshalb kann es vorkommen, dass ein Platz noch fehlt oder zu einem vorhandenen Platz noch nicht alle Informationen bekannt sind.

Genau dabei kannst du helfen: Fehlende Plätze können vorgeschlagen und vorhandene Informationen ergänzt oder korrigiert werden.

## Woher kommen die Informationen über die Plätze?

Die Daten bei Camperwolf können aus verschiedenen Quellen stammen. Dazu gehören öffentliche Datenquellen, Beiträge unserer Nutzer und später auch Angaben von verifizierten Platzbetreibern.

Dabei soll möglichst nachvollziehbar bleiben, woher eine Information stammt und wann sie zuletzt geändert wurde.

## Kann ich selbst einen Platz hinzufügen oder Informationen korrigieren?

Ja. Das ist ein wichtiger Bestandteil von Camperwolf.

Fehlt ein Platz, kannst du ihn vorschlagen. Sind vorhandene Angaben falsch oder unvollständig, kannst du Änderungen vorschlagen.

Je mehr Menschen ihre Erfahrungen und ihr Wissen beitragen, desto vollständiger und aktueller kann Camperwolf werden.

## Warum werden Änderungen nicht immer sofort übernommen?

Nicht jede Änderung wird automatisch veröffentlicht. Je nach Art der Änderung kann sie zunächst geprüft werden.

Damit möchten wir verhindern, dass versehentlich falsche Informationen, Spam oder absichtliche Manipulationen auf der Plattform landen.

Gleichzeitig versuchen wir, die Moderation so unkompliziert wie möglich zu halten.

## Kann ich mich auf die Angaben bei Camperwolf verlassen?

Wir versuchen, die Informationen bei Camperwolf möglichst korrekt und aktuell zu halten. Eine Garantie dafür können wir jedoch nicht geben.

Informationen können sich ändern oder aus öffentlichen Quellen und Community-Beiträgen stammen, die möglicherweise nicht mehr aktuell sind.

Das gilt besonders für Angaben wie Preise, Öffnungszeiten, Zufahrtsmöglichkeiten oder vorübergehende Einschränkungen. Wenn eine Information für deine Reise besonders wichtig ist, solltest du sie im Zweifel zusätzlich beim Betreiber oder vor Ort prüfen.

Wenn dir bei Camperwolf eine falsche Angabe auffällt, kannst du uns helfen, sie zu korrigieren.

## Warum brauche ich für manche Funktionen einen Account?

Plätze suchen und Informationen ansehen kannst du auch ohne Benutzerkonto.

Für eigene Beiträge - zum Beispiel Platz- und Änderungsvorschläge, Rezensionen oder Fotos - benötigst du einen Account.

Dadurch können Beiträge einem Benutzer zugeordnet, Änderungen nachvollzogen und Missbrauch besser verhindert werden. Außerdem können wir dich so über Entscheidungen zu deinen Beiträgen informieren.

## Werden meine persönlichen Daten verkauft?

Nein. Camperwolf verkauft keine persönlichen Nutzerdaten.

Wir möchten mit Camperwolf eine Community-Plattform betreiben und kein Geschäftsmodell auf dem Verkauf persönlicher Daten aufbauen.

Welche Daten verarbeitet werden und wofür sie benötigt werden, erklären wir ausführlicher in unserer Datenschutzerklärung.

## Warum gibt es XP, Level, Badges und Achievements?

Sie sollen die Mitarbeit an Camperwolf etwas interessanter machen und Beiträge zur Community sichtbar anerkennen.

Für verschiedene Aktivitäten kannst du XP sammeln sowie Badges und Achievements freischalten. Daraus entstehen aber keine kostenpflichtigen Vorteile oder exklusiven Funktionen.

Mehr dazu findest du in unseren Hilfeseiten zu [[level-und-xp|Level und XP]] und [[badges-achievements|Badges & Achievements]].

## Können Platzbetreiber ihren Eintrag selbst verwalten?

Eine eigene Verifizierung für Platzbetreiber ist geplant, aber noch nicht verfügbar.

Damit sollen Betreiber später bestimmte Informationen zu ihrem Platz selbst pflegen können. Änderungen sollen dabei weiterhin transparent und nachvollziehbar bleiben.

Bis diese Funktion verfügbar ist, können Betreiber falsche oder fehlende Informationen wie andere Nutzer melden beziehungsweise eine Änderung vorschlagen oder sich an den Support wenden.

## Gibt es eine Camperwolf-App?

Noch nicht.

Camperwolf wird zunächst als Website entwickelt und dabei besonders für die Nutzung auf Smartphones optimiert.

Eine eigene App ist für später vorgesehen. Zunächst konzentrieren wir uns aber darauf, dass die eigentliche Plattform zuverlässig funktioniert und genügend nützliche Inhalte bietet.

## Wie kann ich Camperwolf unterstützen?

Am meisten hilfst du Camperwolf durch deine Mitarbeit.

Du kannst zum Beispiel fehlende Plätze hinzufügen, falsche Angaben korrigieren, Informationen ergänzen, Fotos hochladen, Rezensionen schreiben oder Fehler melden.

Natürlich hilft es auch, Camperwolf anderen Campern zu zeigen. Je mehr Menschen ihr Wissen beitragen, desto nützlicher wird die Plattform für alle.

Finanzielle Unterstützung ist aktuell weder notwendig noch vorgesehen. Sollte es später eine Möglichkeit für freiwillige Spenden geben, werden wir darüber transparent informieren.

## Warum ist Camperwolf noch eine Beta?

Camperwolf befindet sich noch in der Entwicklung. Während der Beta möchten wir herausfinden, wie sich die Plattform im echten Einsatz verhält und wo noch Fehler oder Verbesserungsmöglichkeiten bestehen.

Funktionen können sich deshalb noch ändern, erweitert oder wieder entfernt werden. Auch Fehler können trotz unserer Tests auftreten.

Während der Beta kann es außerdem notwendig sein, Testdaten oder Benutzerkonten zurückzusetzen. Darauf weisen wir während der Beta deutlich hin.

Feedback ist in dieser Phase ausdrücklich erwünscht. Wenn dir etwas nicht funktioniert, unverständlich ist oder einfach besser gelöst werden könnte, kannst du es uns gerne melden.
TEXT,
            ],
            [
                'slug' => 'account-loeschen',
                'title' => 'Account löschen',
                'summary' => 'So funktioniert die dauerhafte Löschung deines Camperwolf-Accounts.',
                'context_key' => 'account-deletion',
                'sort_order' => 52,
                'body' => <<<'TEXT'
Wenn du Camperwolf nicht mehr nutzen möchtest, kannst du dein Benutzerkonto dauerhaft löschen.

Vor dem Löschen zeigt dir Camperwolf an, wie viele Fotos, Rezensionen, angelegte Plätze und Änderungsvorschläge mit deinem Konto verbunden sind.

## Du musst deinen Account nicht löschen

Wenn du Camperwolf eine Zeit lang oder auch dauerhaft nicht mehr nutzen möchtest, musst du deinen Account nicht unbedingt löschen.

**Inaktive Accounts werden nicht automatisch gelöscht.** Es ist also kein Problem, deinen Account einfach bestehen zu lassen, wenn du Camperwolf momentan nicht mehr nutzen möchtest. Du kannst später jederzeit wiederkommen.

Die Account-Löschung ist vor allem dafür gedacht, wenn du ausdrücklich möchtest, dass dein Benutzerkonto und deine persönlichen Daten dauerhaft entfernt werden.

Du hast zwei Möglichkeiten:

## Löschung in 30 Tagen

Wenn du **"Löschung in 30 Tagen"** auswählst, wird dein Account zunächst zur Löschung vorgemerkt und du wirst abgemeldet.

Deine Daten werden zu diesem Zeitpunkt **noch nicht endgültig gelöscht**. Innerhalb dieser 30 Tage kannst du die Löschung noch abbrechen. Danach wird die endgültige Löschung automatisch durchgeführt.

Diese Variante ist sinnvoll, wenn du dir noch nicht zu 100 % sicher bist.

## Sofortige Löschung

Bei der **sofortigen Löschung** beginnt die endgültige Löschung direkt nach deiner Bestätigung.

Es gibt **keine 30-tägige Wartezeit und keine Möglichkeit, die Löschung anschließend abzubrechen**.

> **Wichtig:** Endgültig gelöscht bedeutet endgültig gelöscht. Sobald die Löschung durchgeführt wurde, können wir deinen Account und die entfernten Daten nicht wiederherstellen.

## Was wird gelöscht?

Bei der endgültigen Löschung werden deine persönlichen Kontodaten entfernt oder anonymisiert. Dazu gehören unter anderem:

- Name und E-Mail-Adresse
- Profildaten wie Beschreibung, Heimatort, Geburtsdatum, Fahrzeugangaben und Social-Media-Links
- Kontoeinstellungen und Einwilligungen
- Favoriten
- Benachrichtigungen
- XP, Badge-Fortschritte und freigeschaltete Badges
- Passkeys und Daten der Zwei-Faktor-Authentifizierung
- vorhandene Datenexporte
- deine hochgeladenen Fotos und die dazu gespeicherten Dateien
- die Texte deiner Rezensionen

Dein bisheriges öffentliches Profil ist anschließend nicht mehr deinem früheren Benutzerkonto zuzuordnen.

## Was bleibt erhalten?

Einige Informationen können nicht einfach aus der Datenbank entfernt werden, weil sie für die Funktionsfähigkeit und Nachvollziehbarkeit von Camperwolf benötigt werden.

Dazu können zum Beispiel gehören:

- von dir angelegte Plätze
- eingereichte Änderungsvorschläge und deren Verlauf
- die Bewertungswerte früherer Rezensionen - der geschriebene Rezensionstext wird entfernt und die Rezension selbst nicht mehr veröffentlicht
- technische oder administrative Verlaufsinformationen
- Supportvorgänge, soweit sie weiterhin benötigt werden

Wo solche Informationen erhalten bleiben, wird die Verbindung zu deinen persönlichen Kontodaten entfernt oder auf einen anonymisierten gelöschten Account zurückgeführt. Dein Name, deine E-Mail-Adresse oder dein bisheriges öffentliches Profil werden darüber nicht weiter angezeigt.

## Vor dem Löschen: Daten herunterladen

Wenn du eine Kopie deiner Daten behalten möchtest, fordere **vor der endgültigen Löschung** unter [[meine-daten|Meine Daten]] einen Datenexport an und lade ihn herunter.

Bei der endgültigen Account-Löschung werden auch bereits erstellte Exportdateien gelöscht.

## Datenschutz

Weitere Informationen zur Verarbeitung und Löschung personenbezogener Daten findest du in unserer **Datenschutzerklärung**.
TEXT,
            ],
            [
                'slug' => 'meine-daten',
                'title' => 'Meine Daten',
                'summary' => 'Eine Kopie deiner bei Camperwolf gespeicherten Daten anfordern und herunterladen.',
                'context_key' => 'data-export',
                'sort_order' => 51,
                'body' => <<<'TEXT'
Unter **"Meine Daten"** kannst du eine Kopie der Daten anfordern, die Camperwolf zu deinem Benutzerkonto gespeichert hat.

## Was enthält der Datenexport?

Der Export enthält deine bei Camperwolf gespeicherten Daten und Aktivitäten. Dazu gehören zum Beispiel:

- deine Konto- und Profildaten
- deine Einstellungen und Einwilligungen
- Rezensionen und andere Beiträge
- Favoriten und weitere Interaktionen
- Fotos einschließlich der dazu gespeicherten Informationen
- XP, Badges und andere Gamification-Daten
- Benachrichtigungen
- deine Supportmeldungen und Nachrichten

Die Daten werden in einer **ZIP-Datei** zusammengestellt. Darin befinden sich deine Daten in strukturierten Dateien sowie deine vorhandenen Fotos.

## Wie fordere ich einen Export an?

Starte den Export unter **"Meine Daten"**. Camperwolf stellt die Datei anschließend für dich zusammen.

Sobald der Export fertig ist, kannst du ihn dort herunterladen.

> **Wichtig:** Ein fertiger Export steht dir **72 Stunden** zum Herunterladen zur Verfügung. Danach wird die Exportdatei automatisch gelöscht.

## Wie oft kann ich einen Export erstellen?

Nach einer erfolgreichen Exportanforderung kannst du nach **7 Tagen** einen neuen Export anfordern.

Schlägt die Erstellung eines Exports fehl, beginnt dadurch keine neue Wartezeit.

## Wer kann meinen Export herunterladen?

Der Download gehört zu deinem Benutzerkonto. Andere Camperwolf-Nutzer können deinen Datenexport nicht herunterladen.

## Datenschutz und deine Rechte

Der Datenexport hilft dir dabei, die zu deinem Camperwolf-Konto gespeicherten Daten einzusehen und in einem maschinenlesbaren Format zu erhalten.

Weitere Informationen zu deinen Datenschutzrechten findest du in unserer **Datenschutzerklärung**. Dort findest du auch Informationen zum Auskunftsrecht nach **Art. 15 DSGVO** und zum Recht auf Datenübertragbarkeit nach **Art. 20 DSGVO**.
TEXT,
            ],
            [
                'slug' => 'level-und-xp',
                'title' => 'Level und XP',
                'summary' => 'So funktioniert das freiwillige Level- und XP-System von Camperwolf.',
                'context_key' => 'gamification',
                'sort_order' => 60,
                'body' => <<<'TEXT'
Wenn du Camperwolf mit Informationen, Bewertungen, Fotos oder anderen Beiträgen hilfst, kannst du dafür **Erfahrungspunkte (XP)** bekommen.

Mit den XP steigt nach und nach dein **Level**. Das zeigt, wie viel du bereits zur Community beigetragen hast.

Wichtig dabei: Ein höheres Level bringt **keine besonderen Rechte oder Vorteile**. Es beeinflusst auch keine Bewertungen oder die Reihenfolge von Plätzen.

## Wie bekomme ich XP?

XP bekommst du für hilfreiche Beiträge zu Camperwolf.

Wenn ein Beitrag erst von einem Moderator geprüft werden muss, werden die XP vergeben, sobald der Beitrag freigegeben wurde.

Aktuell bekommst du zum Beispiel:

- **+1 XP** für eine zusätzliche Information oder ein Merkmal zu einem Platz
- **+2 XP** für größere Texte, zum Beispiel eine Beschreibung oder Hinweise zur Anfahrt
- **+1 XP** für jede beantwortete Frage bei einer Bewertung
- **+1 XP** für ein Foto - maximal 5 Foto-XP pro Platz
- **+2 XP** für eine Rezension
- **+4 XP insgesamt** für eine ausführliche Rezension mit mindestens 300 Zeichen
- **+1 XP**, wenn jemand dein Foto als hilfreich bewertet - maximal 10 XP pro Foto

Die Angaben, die unbedingt benötigt werden, um einen neuen Platz anzulegen - zum Beispiel Name, Platztyp und Position - bringen keine zusätzlichen XP.

Wenn du freiwillig weitere Informationen zum neuen Platz einträgst, kannst du dafür aber XP bekommen.

## Kann ich für dieselbe Information mehrfach XP bekommen?

Nein. Für dieselbe Information zu einem Platz bekommst du normalerweise nur einmal XP.

Wenn du zum Beispiel ein Merkmal ergänzt und dafür XP erhalten hast, bekommst du nicht erneut XP, wenn du dieses Merkmal später selbst aktualisierst.

Ergänzt oder aktualisiert ein anderer Nutzer diese Information zum ersten Mal, kann dieser dafür ebenfalls XP bekommen.

## Was passiert, wenn ich etwas lösche und neu eintrage?

Bereits erhaltene XP lassen sich dadurch nicht erneut verdienen.

Ein Beispiel: Du hast für fünf Fotos eines Platzes bereits die maximalen 5 Foto-XP bekommen. Wenn du diese Fotos löschst und neue hochlädst, bekommst du dafür nicht noch einmal Foto-XP.

## Wie funktionieren die Level?

Du startest mit **Level 1 bei 0 XP**.

Die ersten Level kannst du relativ schnell erreichen. Mit jedem weiteren Level brauchst du mehr XP für den nächsten Aufstieg.

Deine bereits verdienten XP bleiben dabei erhalten.

## Wo sehe ich meine XP?

In deinem Profil kannst du deinen **XP-Verlauf** ansehen.

Dort siehst du, wann du XP bekommen hast und wofür. Auch Korrekturen werden dort angezeigt.

Zum Beispiel:

`01.01.2026, 20:32 - Ausführliche Rezension geschrieben: +4 XP`

`02.01.2026, 11:32 - Merkmal eines Platzes ergänzt: +1 XP`

## Was sind Sonder-XP und Korrekturen?

Manchmal kann es zusätzliche XP geben - zum Beispiel für besondere Community-Aktionen, Veranstaltungen oder Auszeichnungen.

Es kann auch vorkommen, dass bereits vergebene XP korrigiert werden müssen. Wird zum Beispiel ein Beitrag von einem Moderator gelöscht, für den du zuvor XP bekommen hast, können die dafür vergebenen XP wieder abgezogen werden.

Solche Änderungen erscheinen ebenfalls in deinem XP-Verlauf.

## Ich möchte das nicht. Kann ich Level und XP ausblenden?

Ja. In deinen Profileinstellungen kannst du festlegen, dass deine **Gamification nicht öffentlich angezeigt** wird.

Dann sehen andere Nutzer dein Level, deine Fortschrittsanzeige, deine Badges und deinen XP-Verlauf nicht mehr.

Deine XP gehen dadurch **nicht verloren**. Camperwolf zählt sie im Hintergrund weiter und dein Level kann weiterhin steigen.

Die Einstellung betrifft nur dein eigenes Profil. Level und Badges anderer Nutzer werden dir weiterhin angezeigt, sofern diese ihre Gamification öffentlich anzeigen.\n\nMehr über die anderen Community-Auszeichnungen findest du unter [[badges-und-achievements|Badges & Achievements]].
TEXT,
            ],
            [
                'slug' => 'badges-und-achievements',
                'title' => 'Badges & Achievements',
                'summary' => 'Auszeichnungen und besondere Erfolge für deine Beiträge zur Camperwolf-Community.',
                'context_key' => 'gamification',
                'sort_order' => 61,
                'body' => <<<'TEXT'
Bei Camperwolf kannst du durch deine Mitarbeit **Badges und Achievements** freischalten.

Sie sind kleine Auszeichnungen für unterschiedliche Beiträge zur Community - zum Beispiel für neue Plätze, hilfreiche Informationen, Rezensionen, Fotos oder andere Aktivitäten.

## Badges

Badges begleiten dich über längere Zeit. Bei vielen von ihnen kannst du verschiedene Stufen erreichen, während du Camperwolf weiter mit deinen Beiträgen unterstützt.

Was für einen bestimmten Badge zählt und was du für die nächste Stufe brauchst, kannst du direkt beim jeweiligen Badge sehen.

## Achievements

Achievements sind besondere Erfolge, die du durch bestimmte Aktivitäten freischalten kannst.

Was du dafür tun musst, steht direkt beim jeweiligen Achievement. Einige Achievements bringen dir zusätzlich **XP**.

Es gibt außerdem ein paar **versteckte Achievements**. Was du dafür tun musst, verraten wir natürlich nicht. :)

## Besondere Auszeichnungen

Manche Auszeichnungen kannst du nicht selbst freischalten. Sie werden zum Beispiel für besondere Aktionen, Veranstaltungen oder Unterstützung der Community vergeben.

## Badge als Profil-Titel

Freigeschaltete Badges und Auszeichnungen kannst du in deinem Nutzerprofil ganz unten als **Profil-Titel** auswählen. Der ausgewählte Titel wird anschließend in deinem Profil angezeigt.

Wenn du ein Achievement, eine neue Badge-Stufe oder eine besondere Auszeichnung erhältst, informiert dich Camperwolf außerdem über die [[benachrichtigungen|Benachrichtigungen]]. Reine XP-Änderungen erzeugen keine eigene Benachrichtigung.

Mehr über Level und Erfahrungspunkte findest du unter [[level-und-xp|Level und XP]].
TEXT,
            ],
            [
                'slug' => 'support-und-meldungen',
                'title' => 'Supportmeldungen und Tickets',
                'summary' => 'Fehler melden, Antworten verfolgen und eigene Supportmeldungen verwalten.',
                'context_key' => 'support-tickets',
                'sort_order' => 50,
                'body' => <<<'TEXT'
Wenn du bei Camperwolf einen Fehler findest, eine Verbesserung vorschlagen möchtest oder ein anderes Problem hast, kannst du uns über **"Fehler melden"** eine Nachricht schicken.

Dabei werden einige Informationen zur aktuellen Seite automatisch mitgeschickt. Das hilft uns dabei, das Problem schneller zu finden.

Bei bekannten Fehlern oder bereits geplanten Funktionen zeigt Camperwolf dir möglicherweise passende Einträge an, bevor du eine neue Meldung abschickst.

## Meine Meldungen

Wenn du angemeldet bist, findest du unter **"Meine Meldungen"** alle Supportmeldungen, die du selbst erstellt hast.

Dort kannst du:

- den aktuellen Status deiner Meldung sehen
- Antworten von Camperwolf lesen
- auf Rückfragen antworten
- den bisherigen Verlauf der Meldung verfolgen

Wenn sich der Status deiner Meldung ändert oder du eine Antwort erhältst, wirst du über Camperwolf benachrichtigt.
TEXT,
            ],
        ];

        foreach ($articles as $article) {
            DB::table('support_articles')->updateOrInsert(
                ['slug' => $article['slug']],
                $article + [
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );

            $articleId = DB::table('support_articles')->where('slug', $article['slug'])->value('id');
            if (Schema::hasTable('support_article_translations')) {
                DB::table('support_article_translations')->updateOrInsert(
                    ['support_article_id' => $articleId, 'locale' => 'de'],
                    [
                        'title' => $article['title'],
                        'summary' => $article['summary'] ?? null,
                        'body' => $article['body'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );

                $english = (require lang_path('en/help_articles.php'))[$article['slug']] ?? null;
                if ($english) {
                    DB::table('support_article_translations')->updateOrInsert(
                        ['support_article_id' => $articleId, 'locale' => 'en'],
                        [
                            'title' => $english['title'],
                            'summary' => $english['summary'] ?? null,
                            'body' => $english['body'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                    );
                }
            }
        }
    }
}
