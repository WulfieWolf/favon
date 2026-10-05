<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceItems($this->updatedItems());
    }

    public function down(): void
    {
        $this->replaceItems($this->originalItems());
    }

    private function replaceItems(array $items): void
    {
        $releaseId = DB::table('dev_releases')
            ->where('stage', 'beta')
            ->where('milestone', 1)
            ->where('build', 0)
            ->value('id');

        if (! $releaseId) {
            return;
        }

        DB::transaction(function () use ($releaseId, $items): void {
            DB::table('dev_release_items')->where('dev_release_id', $releaseId)->delete();

            $now = now();

            foreach ($items as $index => [$section, $type, $de, $en]) {
                $itemId = DB::table('dev_release_items')->insertGetId([
                    'dev_release_id' => $releaseId,
                    'type' => $type,
                    'section' => $section,
                    'sort_order' => ($index + 1) * 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('dev_release_item_translations')->insert([
                    [
                        'dev_release_item_id' => $itemId,
                        'locale' => 'de',
                        'text' => $de,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'dev_release_item_id' => $itemId,
                        'locale' => 'en',
                        'text' => $en,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ]);
            }
        });
    }

    private function updatedItems(): array
    {
        return [
            ['search', 'feature', 'Suche nach Orten und Platznamen.', 'Search for places by location or name.'],
            ['search', 'feature', 'Filtere nach Platztyp, geeignetem Fahrzeug, Bewertung und verfügbaren Merkmalen.', 'Filter by place type, suitable vehicle, rating and available features.'],
            ['search', 'feature', 'Entdecke Plätze auf der Karte und beschränke die Suche bei Bedarf auf den sichtbaren Kartenausschnitt.', 'Explore places on the map and optionally limit results to the visible map area.'],
            ['search', 'feature', 'Sortiere Ergebnisse und speichere interessante Plätze als Favoriten.', 'Sort results and save interesting places as favourites.'],
            ['search', 'feature', 'Der Datenbestand wurde mit öffentlichen Daten aus SID/DATEX-II sowie aus Niedersachsen, Nordrhein-Westfalen und Bayern erweitert.', 'The database has been expanded with public data from SID/DATEX-II as well as from Lower Saxony, North Rhine-Westphalia and Bavaria.'],

            ['places', 'feature', 'Schlage neue Plätze mit Basisdaten, Position, Platztyp und passenden Merkmalen vor.', 'Suggest new places with basic details, location, place type and relevant features.'],
            ['places', 'feature', 'Schlage Änderungen an Adresse, Position, Betreiber- und Kontaktdaten vor.', 'Suggest changes to address, location, operator and contact details.'],
            ['places', 'feature', 'Ergänze und ändere Ausstattung, Regeln und weitere Merkmale eines Platzes.', 'Add and update facilities, rules and other place features.'],
            ['places', 'feature', 'Trage Öffnungszeiten ein oder aktualisiere vorhandene Angaben.', 'Add opening hours or update existing information.'],
            ['places', 'feature', 'Verfolge freigegebene Änderungen in der Platz-Historie.', 'Follow approved changes in the place history.'],
            ['places', 'feature', 'Der DatenScore zeigt, wie vollständig die Informationen eines Platzes bereits gepflegt sind.', 'The DataScore shows how complete the information for a place currently is.'],

            ['reviews', 'feature', 'Bewerte Plätze anhand mehrerer Kriterien und ergänze einen optionalen Text.', 'Review places using several criteria and add optional written feedback.'],
            ['reviews', 'feature', 'Aktualisiere deine Bewertung später und sieh ihre Versionshistorie.', 'Update your review later and view its version history.'],

            ['photos', 'feature', 'Lade Fotos zu deinen Bewertungen hoch und verwalte deine eigenen Bilder.', 'Upload photos with your reviews and manage your own images.'],
            ['photos', 'feature', 'Markiere hilfreiche Fotos und melde problematische Inhalte.', 'Mark useful photos as helpful and report problematic content.'],

            ['account', 'feature', 'Verwalte Profil, Anzeigename, E-Mail-Adresse und Sicherheitseinstellungen deines Kontos.', 'Manage your profile, display name, email address and account security settings.'],
            ['account', 'feature', 'Passe Sprache sowie helle, dunkle oder systemabhängige Darstellung an.', 'Choose your language and light, dark or system-based appearance.'],
            ['account', 'improvement', 'Camperwolf ist für Desktop und Smartphone optimiert.', 'Camperwolf is optimized for desktop and smartphone use.'],

            ['community', 'feature', 'Sammle XP, steige im Level auf und schalte Badges und Achievements frei.', 'Earn XP, level up and unlock badges and achievements.'],

            ['notifications', 'feature', 'Erhalte Benachrichtigungen zu relevanten Änderungen, Moderation, Badges und wichtigen Camperwolf-Mitteilungen.', 'Receive notifications about relevant changes, moderation, badges and important Camperwolf announcements.'],
            ['notifications', 'feature', 'Passe deine Benachrichtigungspräferenzen an.', 'Adjust your notification preferences.'],

            ['privacy', 'feature', 'Fordere einen Export deiner gespeicherten Daten an.', 'Request an export of your stored data.'],
            ['privacy', 'feature', 'Lösche dein Konto mit der vorgesehenen Widerrufsfrist.', 'Delete your account using the available cancellation grace period.'],

            ['support', 'feature', 'Nutze die Hilfeartikel und melde Probleme oder Fragen direkt an den Support.', 'Use the help articles and send problems or questions directly to support.'],
        ];
    }

    private function originalItems(): array
    {
        return [
            ['search', 'feature', 'Suche nach Orten und Platznamen.', 'Search for places by location or name.'],
            ['search', 'feature', 'Grenze Suchergebnisse mit Filtern nach Platztyp, Fahrzeug, Preis, Bewertung und verfügbaren Merkmalen ein.', 'Narrow results with filters for place type, vehicle, price, rating and available features.'],
            ['search', 'feature', 'Entdecke Plätze auf der Karte und beschränke die Suche bei Bedarf auf den sichtbaren Kartenausschnitt.', 'Explore places on the map and optionally limit results to the visible map area.'],
            ['search', 'feature', 'Sortiere Ergebnisse und speichere interessante Plätze als Favoriten.', 'Sort results and save interesting places as favourites.'],
            ['places', 'feature', 'Schlage neue Plätze mit Basisdaten, Position, Platztyp und passenden Merkmalen vor.', 'Suggest new places with basic details, location, place type and relevant features.'],
            ['places', 'feature', 'Schlage Änderungen an Basisdaten wie Adresse, Position und Kontaktdaten vor.', 'Suggest changes to basic details such as address, location and contact information.'],
            ['places', 'feature', 'Ergänze und ändere Ausstattung, Regeln und weitere Merkmale eines Platzes.', 'Add and update facilities, rules and other place features.'],
            ['places', 'feature', 'Trage Öffnungszeiten und Preise ein oder aktualisiere vorhandene Angaben.', 'Add or update opening hours and prices.'],
            ['places', 'feature', 'Verfolge freigegebene Änderungen in der Platz-Historie.', 'Follow approved changes in the place history.'],
            ['reviews', 'feature', 'Bewerte Plätze anhand mehrerer Kriterien und ergänze einen optionalen Text.', 'Review places using several criteria and add optional written feedback.'],
            ['reviews', 'feature', 'Aktualisiere deine Bewertung später und sieh ihre Versionshistorie.', 'Update your review later and view its version history.'],
            ['photos', 'feature', 'Lade Fotos zu deinen Bewertungen hoch und verwalte deine eigenen Bilder.', 'Upload photos with your reviews and manage your own images.'],
            ['photos', 'feature', 'Markiere hilfreiche Fotos und melde problematische Inhalte.', 'Mark useful photos as helpful and report problematic content.'],
            ['account', 'feature', 'Verwalte Profil, Anzeigename, E-Mail-Adresse und Sicherheitseinstellungen deines Kontos.', 'Manage your profile, display name, email address and account security settings.'],
            ['account', 'feature', 'Passe Sprache sowie helle, dunkle oder systemabhängige Darstellung an.', 'Choose your language and light, dark or system-based appearance.'],
            ['community', 'feature', 'Sammle XP, steige im Level auf und schalte Badges und Achievements frei.', 'Earn XP, level up and unlock badges and achievements.'],
            ['notifications', 'feature', 'Erhalte Benachrichtigungen zu relevanten Änderungen, Moderation, Badges und wichtigen Camperwolf-Mitteilungen.', 'Receive notifications about relevant changes, moderation, badges and important Camperwolf announcements.'],
            ['notifications', 'feature', 'Passe deine Benachrichtigungspräferenzen an.', 'Adjust your notification preferences.'],
            ['privacy', 'feature', 'Fordere einen Export deiner gespeicherten Daten an.', 'Request an export of your stored data.'],
            ['privacy', 'feature', 'Lösche dein Konto mit der vorgesehenen Widerrufsfrist.', 'Delete your account using the available cancellation grace period.'],
            ['support', 'feature', 'Nutze die Hilfeartikel und melde Probleme oder Fragen direkt an den Support.', 'Use the help articles and send problems or questions directly to support.'],
        ];
    }
};
