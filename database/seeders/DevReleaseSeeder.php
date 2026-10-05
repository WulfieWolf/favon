<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevReleaseSeeder extends Seeder
{
    public function run(): void
    {
        $releases = [
            [1, 1, '2026-09-16 21:00:00', 'Project foundation', 'Projektgrundlage', [
                ['feature', 'Laravel project, authentication and local development environment established.', 'Laravel-Projekt, Anmeldung und lokale Entwicklungsumgebung eingerichtet.'],
                ['maintenance', 'Initial database and application conventions defined.', 'Erste Datenbank- und Anwendungskonventionen festgelegt.'],
            ]],
            [2, 1, '2026-09-16 22:00:00', 'Core place data model', 'Grundlegendes Platz-Datenmodell', [
                ['feature', 'Reference tables, place types, features and vehicle types added.', 'Referenztabellen, Platztypen, Merkmale und Fahrzeugtypen ergänzt.'],
                ['feature', 'Place data prepared for localized and versioned content.', 'Platzdaten für lokalisierte und versionierte Inhalte vorbereitet.'],
            ]],
            [3, 1, '2026-09-17 08:30:00', 'Flexible feature system', 'Flexibles Merkmalsystem', [
                ['feature', 'Hybrid feature values and configurable options introduced.', 'Hybride Merkmalswerte und konfigurierbare Optionen eingeführt.'],
                ['improvement', 'Reference data can be deactivated without destructive deletion.', 'Referenzdaten können ohne destruktives Löschen deaktiviert werden.'],
            ]],
            [4, 1, '2026-09-17 11:00:00', 'Versioned place information', 'Versionierte Platzinformationen', [
                ['feature', 'Addresses, contacts, details and other mutable place data support historical versions.', 'Adressen, Kontakte, Details und weitere veränderliche Platzdaten unterstützen historische Versionen.'],
                ['feature', 'Provenance and suggestable fields prepared for transparent changes.', 'Herkunft und vorschlagbare Felder für transparente Änderungen vorbereitet.'],
            ]],
            [5, 1, '2026-09-17 13:00:00', 'Roles and permissions', 'Rollen und Berechtigungen', [
                ['feature', 'Guest, user, moderator and administrator permission model added.', 'Berechtigungsmodell für Gast, Nutzer, Moderator und Administrator ergänzt.'],
                ['security', 'System owner privileges separated from ordinary administrator roles.', 'System-Owner-Rechte von normalen Administratorrollen getrennt.'],
            ]],
            [6, 1, '2026-09-17 14:00:00', 'Map browsing', 'Kartensuche', [
                ['feature', 'Map-centric desktop browse view with place list and filters added.', 'Kartenbasierte Desktop-Ansicht mit Platzliste und Filtern ergänzt.'],
                ['improvement', 'Faceted feature counts and interactive map/list behavior added.', 'Facettierte Merkmalszähler und interaktives Verhalten zwischen Karte und Liste ergänzt.'],
            ]],
            [7, 1, '2026-09-17 15:00:00', 'Place profiles', 'Platzprofile', [
                ['feature', 'Dedicated place profile page introduced.', 'Eigene Profilseite für Plätze eingeführt.'],
                ['improvement', 'Current address, details, contacts, vehicle types and features are assembled centrally.', 'Aktuelle Adresse, Details, Kontakte, Fahrzeugtypen und Merkmale werden zentral zusammengeführt.'],
            ]],
            [8, 1, '2026-09-17 16:00:00', 'Moderation and audit trail', 'Moderation und Änderungsprotokoll', [
                ['feature', 'Change requests can be reviewed, approved and applied through the moderation workflow.', 'Änderungsvorschläge können über den Moderationsablauf geprüft, freigegeben und angewendet werden.'],
                ['feature', 'Administrative audit log added for traceable changes.', 'Administratives Änderungsprotokoll für nachvollziehbare Änderungen ergänzt.'],
            ]],
            [9, 1, '2026-09-17 17:00:00', 'Role preview', 'Rollenvorschau', [
                ['feature', 'System owner can simulate active roles without changing database assignments.', 'Der System Owner kann Rollen simulieren, ohne Datenbankzuweisungen zu verändern.'],
                ['security', 'Direct route access follows the simulated permissions as well.', 'Auch direkter Routenzugriff berücksichtigt die simulierten Berechtigungen.'],
            ]],
            [10, 1, '2026-09-17 18:30:00', 'Draft workflow and internationalization', 'Entwurfsworkflow und Internationalisierung', [
                ['feature', 'New places can be saved as drafts, enriched and submitted later.', 'Neue Plätze können als Entwurf gespeichert, ergänzt und später eingereicht werden.'],
                ['improvement', 'Address lookup and reverse geocoding support map-based place creation.', 'Adresssuche und Rückwärtssuche unterstützen die kartengestützte Platzerstellung.'],
                ['improvement', 'Country selection uses ISO country codes while showing localized country names.', 'Die Länderauswahl verwendet ISO-Ländercodes und zeigt lokalisierte Ländernamen.'],
                ['feature', 'German and English language switching added.', 'Sprachumschaltung zwischen Deutsch und Englisch ergänzt.'],
                ['feature', 'Bilingual developer log and automatic visible version label introduced.', 'Zweisprachiger Devlog und automatisch abgeleitete sichtbare Versionsnummer eingeführt.'],
                ['fix', 'Dark-mode contrast improved in place creation actions and status messages.', 'Dark-Mode-Kontraste bei Aktionen und Statusmeldungen der Platzerstellung verbessert.'],
            ]],
            [11, 1, '2026-09-17 21:30:00', 'Structured feature workflow', 'Strukturierter Merkmalsworkflow', [
                ['feature', 'Draft creation now includes a configurable third step for facilities, rules, measurements and costs.', 'Die Entwurfserstellung enthält jetzt einen konfigurierbaren dritten Schritt für Ausstattung, Regeln, Messwerte und Kosten.'],
                ['feature', 'Feature details can appear conditionally, including amperage, connector types, prices, durations and distances.', 'Merkmalsdetails können abhängig von vorherigen Antworten eingeblendet werden, darunter Stromstärke, Anschlussarten, Preise, Dauern und Entfernungen.'],
                ['feature', 'A fourth review step summarizes the draft before it is submitted to moderation.', 'Ein vierter Prüfschritt fasst den Entwurf vor dem Einreichen zur Moderation zusammen.'],
                ['improvement', 'Optional comments can be stored for every configured feature.', 'Zu jedem konfigurierten Merkmal kann ein optionaler Hinweis gespeichert werden.'],
                ['improvement', 'The development version label was moved to a smaller unobtrusive position in the upper-right corner.', 'Der Entwicklungsversion-Hinweis wurde kleiner und unauffälliger oben rechts platziert.'],
            ]],
            [12, 1, '2026-09-18 08:15:00', 'Interactive feature profiles', 'Interaktive Merkmalsprofile', [
                ['feature', 'Place profiles now show the complete configured feature catalogue, including unknown values.', 'Platzprofile zeigen jetzt den vollständigen konfigurierten Merkmalskatalog einschließlich unbekannter Werte.'],
                ['improvement', 'Feature states use clear positive, negative and unknown visual states and structured detail values are shown directly.', 'Merkmalszustände werden klar als positiv, negativ oder unbekannt dargestellt und strukturierte Detailwerte direkt angezeigt.'],
                ['improvement', 'Optional feature comments are exposed through a compact information tooltip.', 'Optionale Merkmalskommentare werden über einen kompakten Info-Tooltip angezeigt.'],
                ['feature', 'Users can propose changes to individual features directly from the place profile.', 'Nutzer können Änderungen einzelner Merkmale direkt aus dem Platzprofil vorschlagen.'],
                ['feature', 'Administrators and the system owner can edit individual feature values directly while retaining version history and audit logging.', 'Administratoren und der System Owner können einzelne Merkmalswerte direkt bearbeiten; Versionshistorie und Audit-Log bleiben erhalten.'],
            ]],
            [13, 1, '2026-09-18 22:00:00', 'Reviews and photo foundation', 'Bewertungen und Foto-Grundlage', [
                ['feature', 'Five-dimensional place reviews with version history, validity periods and moderation reports were introduced.', 'Fünfdimensionale Platzbewertungen mit Versionshistorie, Gültigkeitszeitraum und Moderationsmeldungen wurden eingeführt.'],
                ['feature', 'Photo uploads, moderation, private processing and place galleries were added.', 'Foto-Uploads, Moderation, private Verarbeitung und Platzgalerien wurden ergänzt.'],
                ['improvement', 'Review pagination and review/photo presentation were hardened for practical use.', 'Review-Paginierung sowie die Darstellung von Bewertungen und Fotos wurden für die praktische Nutzung verbessert.'],
            ]],
            [14, 1, '2026-09-19 22:00:00', 'Community profiles and gamification', 'Community-Profile und Gamification', [
                ['feature', 'Public Camperwolf profiles, permanent CW IDs, optional aliases and privacy controls were completed.', 'Öffentliche Camperwolf-Profile, permanente CW-IDs, optionale Aliase und Privatsphäre-Einstellungen wurden fertiggestellt.'],
                ['feature', 'XP, levels, badges and achievements were connected to community contributions.', 'XP, Level, Badges und Achievements wurden mit Community-Beiträgen verknüpft.'],
                ['feature', 'Favorites, notifications and the support/help foundation were integrated into the user workflow.', 'Favoriten, Benachrichtigungen sowie die Support-/Hilfe-Grundlage wurden in den Nutzerworkflow integriert.'],
            ]],
            [15, 1, '2026-09-20 23:00:00', 'Place maintenance and structured operations', 'Platzpflege und strukturierte Betriebsdaten', [
                ['feature', 'Published places became fully maintainable through moderated suggestions and audited privileged direct edits.', 'Veröffentlichte Plätze wurden über moderierte Vorschläge und auditierte privilegierte Direktbearbeitung vollständig pflegbar.'],
                ['feature', 'Recurring opening-hour periods and structured seasonal pricing were implemented.', 'Wiederkehrende Öffnungszeiten-Zeiträume und strukturierte saisonale Preise wurden umgesetzt.'],
                ['feature', 'Safe duplicate-place merging with conflict handling and reversal support was completed.', 'Sicheres Zusammenführen doppelter Plätze mit Konfliktbehandlung und Rückgängig-Funktion wurde fertiggestellt.'],
                ['improvement', 'The V1 place-type and feature catalogue was consolidated for the current product scope.', 'Der V1-Platztypen- und Merkmalskatalog wurde für den aktuellen Produktumfang konsolidiert.'],
            ]],
            [16, 1, '2026-09-21 23:00:00', 'Localization, browsing and responsive UX', 'Lokalisierung, Suche und responsive UX', [
                ['feature', 'The user interface was completed in German and English with locale-aware administration and workflows.', 'Die Benutzeroberfläche wurde in Deutsch und Englisch einschließlich lokalisierter Administration und Workflows vervollständigt.'],
                ['feature', 'Variable feature and price filters with numeric ranges, option counts and active filter tags were added.', 'Variable Merkmals- und Preisfilter mit Zahlenbereichen, Optionszählern und aktiven Filter-Tags wurden ergänzt.'],
                ['improvement', 'Desktop and mobile browse/profile layouts, map behavior and navigation controls were substantially refined.', 'Desktop- und Mobile-Layouts für Suche und Platzprofil sowie Kartenverhalten und Navigation wurden deutlich überarbeitet.'],
            ]],
            [17, 1, '2026-09-22 15:00:00', 'Fast category-based feature editing', 'Schnelle kategorieweise Merkmalsbearbeitung', [
                ['feature', 'Place features can now be edited category by category while changing only the values that were actually modified.', 'Platzmerkmale können nun kategorieweise bearbeitet werden, wobei nur tatsächlich geänderte Werte übertragen werden.'],
                ['feature', 'Local drafts survive reloads and warn before leaving with unsaved feature changes.', 'Lokale Entwürfe überstehen Neuladen und warnen beim Verlassen mit ungespeicherten Merkmalsänderungen.'],
                ['feature', 'Pending community suggestions remain visible to their author and newer suggestions supersede older pending ones.', 'Offene Community-Vorschläge bleiben für ihren Autor sichtbar und neuere Vorschläge ersetzen ältere offene Vorschläge.'],
                ['improvement', 'Known and unknown features are clearly separated while remaining directly maintainable on mobile and desktop.', 'Bekannte und unbekannte Merkmale sind klar getrennt und bleiben auf Mobile und Desktop direkt pflegbar.'],
            ]],
            [18, 1, '2026-09-22 21:30:00', 'Unified feedback and validation UX', 'Einheitliches Feedback und Validierungs-UX', [
                ['feature', 'Form validation now uses a central feedback dialog with field highlighting, focus and automatic navigation to the first affected input.', 'Formularvalidierung verwendet nun einen zentralen Feedback-Dialog mit Feldmarkierung, Fokus und automatischer Navigation zum ersten betroffenen Eingabefeld.'],
                ['feature', 'Important completed actions use central confirmation dialogs while small instant actions use unobtrusive toasts.', 'Wichtige abgeschlossene Aktionen verwenden zentrale Bestätigungsdialoge, kleine Sofortaktionen unaufdringliche Toasts.'],
                ['improvement', 'Legacy inline status and error boxes were removed from the main application and administration workflows.', 'Alte Inline-Status- und Fehlerboxen wurden aus den wesentlichen Anwendungs- und Administrationsabläufen entfernt.'],
                ['improvement', 'German and English validation fallbacks were centralized to avoid raw framework error messages.', 'Deutsche und englische Validierungs-Fallbacks wurden zentralisiert, damit keine rohen Framework-Fehlermeldungen mehr erscheinen.'],
            ]],
            [19, 1, '2026-09-23 10:45:00', 'Performance and diagnostics', 'Performance und Diagnose', [
                ['improvement', 'Browse queries and facet calculations were optimized against a realistic 10,000-place performance dataset.', 'Browse-Abfragen und Facettenberechnungen wurden anhand eines realistischen Performance-Datensatzes mit 10.000 Plätzen optimiert.'],
                ['improvement', 'Browse results and map markers are capped at 1,000 displayed matches while filters continue to evaluate the complete matching dataset.', 'Ergebnisliste und Kartenmarker sind auf 1.000 angezeigte Treffer begrenzt, während Suche und Filter weiterhin den vollständigen passenden Datenbestand auswerten.'],
                ['feature', 'Pulse reporting, EXPLAIN tooling and an administrator-only session debug mode provide on-demand performance diagnostics.', 'Pulse-Reports, EXPLAIN-Werkzeuge und ein nur für Administratoren verfügbarer sitzungsbasierter Debug-Modus ermöglichen Performance-Diagnosen bei Bedarf.'],
                ['maintenance', 'Telescope is disabled by default and can be enabled temporarily for detailed request inspection.', 'Telescope ist standardmäßig deaktiviert und kann für detaillierte Request-Analysen temporär aktiviert werden.'],
            ]],
        ];

        DB::transaction(function () use ($releases): void {
            foreach ($releases as [$milestone, $build, $releasedAt, $titleEn, $titleDe, $items]) {
                DB::table('dev_releases')->updateOrInsert(
                    ['milestone' => $milestone, 'build' => $build],
                    [
                        'stage' => 'pre-alpha',
                        'released_at' => $releasedAt,
                        'is_public' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );

                $releaseId = DB::table('dev_releases')
                    ->where('milestone', $milestone)
                    ->where('build', $build)
                    ->value('id');

                foreach ([
                    'en' => [$titleEn, null],
                    'de' => [$titleDe, null],
                ] as $locale => [$title, $summary]) {
                    DB::table('dev_release_translations')->updateOrInsert(
                        ['dev_release_id' => $releaseId, 'locale' => $locale],
                        ['title' => $title, 'summary' => $summary, 'created_at' => now(), 'updated_at' => now()],
                    );
                }

                DB::table('dev_release_items')->where('dev_release_id', $releaseId)->delete();

                foreach ($items as $index => [$type, $textEn, $textDe]) {
                    $itemId = DB::table('dev_release_items')->insertGetId([
                        'dev_release_id' => $releaseId,
                        'type' => $type,
                        'sort_order' => ($index + 1) * 10,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('dev_release_item_translations')->insert([
                        [
                            'dev_release_item_id' => $itemId,
                            'locale' => 'en',
                            'text' => $textEn,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'dev_release_item_id' => $itemId,
                            'locale' => 'de',
                            'text' => $textDe,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    ]);
                }
            }
        });
    }
}
