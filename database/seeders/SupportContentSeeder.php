<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SupportContentSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('support_articles')) {
            return;
        }

        $legacyCamperwolfArticleSlugs = [
            'platz-details-bearbeiten',
            'merkmale-eines-platzes-eintragen',
            'platzvorschlag-pruefen-und-einreichen',
            'plaetze-suchen-und-filtern',
            'platzprofil-verstehen',
            'platz-vorschlagen-schritt-fuer-schritt',
            'benachrichtigungen',
            'benachrichtigungseinstellungen',
            'profil-und-kontodaten',
            'sicherheit-im-benutzerkonto',
            'darstellung-anpassen',
            'regeln-und-empfehlungen-fuer-fotouploads',
            'regeln-und-empfehlungen-fuer-rezensionen',
            'ueber-camperwolf',
            'verwendete-software-dienste-und-lizenzen',
            'faq',
            'account-loeschen',
            'meine-daten',
            'level-und-xp',
            'badges-und-achievements',
            'support-und-meldungen',
        ];

        $articleIds = DB::table('support_articles')
            ->whereIn('slug', $legacyCamperwolfArticleSlugs)
            ->pluck('id');

        if ($articleIds->isEmpty()) {
            return;
        }

        if (Schema::hasTable('support_article_translations')) {
            DB::table('support_article_translations')
                ->whereIn('support_article_id', $articleIds)
                ->delete();
        }

        DB::table('support_articles')
            ->whereIn('id', $articleIds)
            ->delete();
    }
}
