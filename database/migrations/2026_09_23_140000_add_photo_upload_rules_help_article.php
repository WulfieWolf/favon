<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('support_articles')->updateOrInsert(
            ['slug' => 'regeln-und-empfehlungen-fuer-fotouploads'],
            [
                'title' => 'Regeln und Empfehlungen für Fotouploads',
                'summary' => 'Welche Fotos bei Camperwolf erlaubt sind und welche Bilder anderen Campern besonders helfen.',
                'body' => $this->germanBody(),
                'context_key' => 'photo-rules',
                'sort_order' => 48,
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        $id = DB::table('support_articles')
            ->where('slug', 'regeln-und-empfehlungen-fuer-fotouploads')
            ->value('id');

        if ($id && \Illuminate\Support\Facades\Schema::hasTable('support_article_translations')) {
            foreach ([
                'de' => [
                    'title' => 'Regeln und Empfehlungen für Fotouploads',
                    'summary' => 'Welche Fotos bei Camperwolf erlaubt sind und welche Bilder anderen Campern besonders helfen.',
                    'body' => $this->germanBody(),
                ],
                'en' => [
                    'title' => 'Rules and recommendations for photo uploads',
                    'summary' => 'Which photos are allowed on Camperwolf and which images are particularly useful to other campers.',
                    'body' => $this->englishBody(),
                ],
            ] as $locale => $translation) {
                DB::table('support_article_translations')->updateOrInsert(
                    ['support_article_id' => $id, 'locale' => $locale],
                    $translation + ['created_at' => $now, 'updated_at' => $now],
                );
            }
        }
    }

    public function down(): void
    {
        $id = DB::table('support_articles')
            ->where('slug', 'regeln-und-empfehlungen-fuer-fotouploads')
            ->value('id');

        if ($id) {
            DB::table('support_article_translations')->where('support_article_id', $id)->delete();
            DB::table('support_articles')->where('id', $id)->delete();
        }
    }

    private function germanBody(): string
    {
        return <<<'TEXT'
Verbindliche Regeln

• Keine Nacktheit oder sexualisierten Inhalte. Sichtbare Geschlechtsteile, entblößte weibliche Brust und pornografische Inhalte sind nicht erlaubt – auch nicht bei FKK-Plätzen.
• Keine rechtswidrigen oder jugendgefährdenden Inhalte. Maßgeblich sind insbesondere das anwendbare deutsche Recht und unmittelbar geltendes EU-Recht. Dazu gehören beispielsweise verbotene Symbolik, Gewalt- oder Gore-Inhalte, illegale Drogen, rechtswidrige Waffeninhalte und andere unzulässige Darstellungen.
• Camperwolf kann auch Inhalte ablehnen, die nicht zwingend rechtswidrig sind, aber gegen diese Plattformregeln verstoßen.
• Keine Verletzung von Persönlichkeitsrechten. Lade keine Fotos hoch, auf denen Personen erkennbar sind, wenn du nicht sicher bist, dass sie mit der Veröffentlichung einverstanden sind. Beachte außerdem Fotografierverbote und Hausregeln des jeweiligen Platzes.
• Nur platzbezogene Inhalte. Erlaubt sind beispielsweise Stellflächen, Zufahrt, Sanitäranlagen, Rezeption, Ver- und Entsorgung, relevante Umgebung, Strand oder nahe Einkaufsmöglichkeiten. Reine Selfies, private Urlaubsbilder oder andere nicht platzbezogene Motive gehören nicht in die Platzgalerie.
• Lade nur Fotos hoch, an denen du selbst die erforderlichen Rechte besitzt. Keine Bilder von Webseiten, Suchmaschinen, Social Media, Buchungsportalen oder Betreiberseiten übernehmen, wenn du dafür keine ausdrücklichen Nutzungsrechte hast.
• Keine privaten oder vertraulichen Informationen wie Buchungsdaten, Zugangscodes, QR-Codes, E-Mail-Adressen, Telefonnummern oder vergleichbare Angaben.
• Keine irreführenden Bilder. KI-generierte Platzfotos, Fotomontagen oder Bearbeitungen, die den tatsächlichen Zustand wesentlich verändern, sind nicht erlaubt.
• Keine Werbung oder Spam. Werbegrafiken, Rabattcodes, Flyer oder Bilder, deren Hauptzweck Eigenwerbung ist, sind nicht erlaubt.

Empfehlungen für Platzfotos

Lade Fotos hoch, die anderen Campern helfen, den Platz besser kennenzulernen und einzuschätzen.

Besonders hilfreich sind zum Beispiel:
• Stellflächen, Größe und Bodenbeschaffenheit
• Ein- und Ausfahrt sowie schwierige Zufahrten
• Ver- und Entsorgung
• Stromanschlüsse und andere Infrastruktur
• Sanitäranlagen
• Rezeption, Automaten und Beschilderung
• Barrieren, Höhenbegrenzungen oder erkennbare Einschränkungen
• Aussicht und unmittelbare Umgebung
• nahe Infrastruktur, wenn sie für den Platz praktisch relevant ist
• sachlich dokumentierte Mängel wie Schlaglöcher oder defekte Einrichtungen

Vermeide nach Möglichkeit erkennbare Personen – auch dich selbst. Der Platz sollte im Mittelpunkt stehen. Mehrere unterschiedliche Motive sind hilfreicher als viele nahezu identische Fotos. Verwende möglichst aktuelle und realistische Aufnahmen ohne starke Filter.

Empfehlungen für Profilbilder

Für Profilbilder muss kein Platzbezug bestehen. Wähle ein Bild, mit dem du dich in der Community darstellen möchtest. Auch hier gelten alle verbindlichen Fotoregeln. Wenn weitere Personen erkennbar sind, stelle sicher, dass sie mit der Veröffentlichung einverstanden sind.
TEXT;
    }

    private function englishBody(): string
    {
        return <<<'TEXT'
Binding rules

• No nudity or sexualised content. Visible genitals, exposed female breasts and pornographic content are not permitted, including at naturist locations.
• No illegal or age-inappropriate content. In particular, applicable German law and directly applicable EU law apply. This includes prohibited symbols, graphic violence or gore, illegal drugs, unlawful weapons content and other prohibited material.
• Camperwolf may also reject content that is not necessarily illegal but violates these platform rules.
• Do not violate personality or privacy rights. Do not upload photos showing identifiable people unless you are sure they agree to publication. Also respect photography restrictions and site rules.
• Place-gallery photos must be related to the place. Suitable subjects include pitches, access, sanitary facilities, reception, service points, relevant surroundings, nearby beaches or practically relevant shopping options. Pure selfies and unrelated private photos do not belong in place galleries.
• Only upload photos for which you hold the necessary rights. Do not copy images from websites, search engines, social media, booking portals or operator sites without explicit usage rights.
• Do not show private or confidential information such as booking details, access codes, QR codes, email addresses or phone numbers.
• No misleading images. AI-generated place photos, composites or edits that materially alter the real condition are not permitted.
• No advertising or spam. Advertising graphics, discount codes, flyers or images primarily intended for self-promotion are not permitted.

Recommendations for place photos

Upload photos that help other campers understand and assess the place.

Particularly useful subjects include:
• pitches, dimensions and ground conditions
• entrances, exits and difficult access
• fresh-water and waste facilities
• electrical hookups and other infrastructure
• sanitary facilities
• reception, machines and signs
• barriers, height restrictions or visible limitations
• views and the immediate surroundings
• nearby infrastructure when practically relevant to the place
• factual documentation of defects such as potholes or broken facilities

Avoid identifiable people where possible, including yourself. Keep the place as the main subject. Several different views are more useful than many nearly identical photos. Prefer recent, realistic images without heavy filters.

Recommendations for profile photos

Profile photos do not need to be related to a place. Choose an image that represents you in the community. All binding photo rules still apply. If other people are identifiable, make sure they agree to publication.
TEXT;
    }
};
