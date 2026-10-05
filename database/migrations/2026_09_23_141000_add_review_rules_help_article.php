<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('support_articles')->updateOrInsert(
            ['slug' => 'regeln-und-empfehlungen-fuer-rezensionen'],
            [
                'title' => 'Regeln und Empfehlungen für Rezensionen',
                'summary' => 'Wie Rezensionen anderen Campern helfen und welche Inhalte bei Camperwolf nicht erlaubt sind.',
                'body' => $this->germanBody(),
                'context_key' => 'review-rules',
                'sort_order' => 49,
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        $id = DB::table('support_articles')
            ->where('slug', 'regeln-und-empfehlungen-fuer-rezensionen')
            ->value('id');

        if ($id && Schema::hasTable('support_article_translations')) {
            foreach ([
                'de' => [
                    'title' => 'Regeln und Empfehlungen für Rezensionen',
                    'summary' => 'Wie Rezensionen anderen Campern helfen und welche Inhalte bei Camperwolf nicht erlaubt sind.',
                    'body' => $this->germanBody(),
                ],
                'en' => [
                    'title' => 'Rules and recommendations for reviews',
                    'summary' => 'How reviews can help other campers and which content is not allowed on Camperwolf.',
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
            ->where('slug', 'regeln-und-empfehlungen-fuer-rezensionen')
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

• Keine rechtswidrigen, pornografischen, gewaltverherrlichenden, diskriminierenden, beleidigenden oder bedrohenden Inhalte. Maßgeblich sind insbesondere das anwendbare deutsche Recht und unmittelbar geltendes EU-Recht. Camperwolf kann darüber hinaus Inhalte ablehnen, die gegen diese Plattformregeln verstoßen.
• Keine persönlichen oder vertraulichen Daten. Veröffentliche keine privaten Namen, Telefonnummern, E-Mail-Adressen, Wohnadressen, Kennzeichen, Buchungsnummern oder vergleichbare Angaben. Öffentlich angegebene geschäftliche Informationen eines Platzes oder Betreibers dürfen sachlich genannt werden.
• Keine erfundenen Erfahrungen oder wissentlich falschen Tatsachenbehauptungen. Beschreibe nur Dinge, die du nach bestem Wissen für richtig hältst.
• Keine unbelegten schweren Anschuldigungen. Behauptungen über Straftaten, Betrug, Diebstahl oder vergleichbar schwerwiegendes Fehlverhalten dürfen nicht ohne belastbare Grundlage als Tatsache dargestellt werden.
• Keine persönlichen Auseinandersetzungen oder Rachebewertungen. Sachlich relevante Folgen eines Konflikts dürfen beschrieben werden, wenn sie anderen Campern helfen. Aus „Der Betreiber hat mir verboten, meinen Wagen zu waschen“ kann beispielsweise die hilfreiche Information „Fahrzeugwäsche ist auf dem Platz nicht erlaubt“ werden.
• Die Rezension muss sich auf den Platz, seinen Betrieb oder einen für den Aufenthalt relevanten Aspekt beziehen.
• Keine Werbung, Spam, Affiliate-Inhalte oder Eigenwerbung.
• Keine kopierten Rezensionen oder fremden Texte, an denen du nicht die erforderlichen Rechte besitzt.
• Keine manipulierten Bewertungen. Bewerte nicht den eigenen Platz unter einem normalen Nutzerkonto und veröffentliche keine gekauften, beauftragten oder gezielt im Interesse eines Betreibers oder Konkurrenten erstellten Rezensionen.
• Stelle bekannte alte Zustände nicht bewusst als aktuell dar. Eine ältere Rezension wird dadurch nicht automatisch falsch: Entscheidend ist, dass sie deine tatsächliche Erfahrung zum damaligen Zeitpunkt beschreibt.

Empfehlungen

Schreibe eine Rezension, die anderen Campern hilft, den Platz besser einzuschätzen und eine Entscheidung zu treffen.

• Beschreibe möglichst deine eigene Erfahrung.
• Sei konkret. „Nachts war der Straßenverkehr deutlich hörbar“ hilft mehr als „schlechter Platz“.
• Unterscheide Beobachtung und persönliche Meinung. „Für meinen 7-m-Camper waren die Stellplätze eng“ ist hilfreicher als „Die Stellplätze sind unbrauchbar“.
• Nenne relevanten Kontext, zum Beispiel Reisezeit, Fahrzeuggröße, volle oder ruhige Saison oder besondere Wetterbedingungen.
• Sachliche Kritik ist ausdrücklich willkommen. Eine schlechte Erfahrung darf auch zu einer klar negativen Rezension führen.
• Besonders hilfreich sind Erfahrungen zu Sauberkeit, Ruhe, Zufahrt, Stellplatzgröße, Umgebung, Versorgung, Sanitäranlagen, Sicherheit, Nutzbarkeit und dem Umgang mit konkreten Problemen.
• Beschreibe Regeln und Einschränkungen sachlich, wenn sie für andere Camper relevant sind.
• Vermeide lange Nebengeschichten, die nichts über den Platz aussagen.

Du musst eine negative Erfahrung nicht künstlich „ausgleichen“. Entscheidend ist, dass deine Rezension deine tatsächliche Erfahrung sachlich und nach bestem Wissen beschreibt.
TEXT;
    }

    private function englishBody(): string
    {
        return <<<'TEXT'
Binding rules

• No illegal, pornographic, glorifying violence, discriminatory, abusive or threatening content. In particular, applicable German law and directly applicable EU law apply. Camperwolf may also reject content that violates these platform rules.
• No personal or confidential data. Do not publish private names, phone numbers, email addresses, home addresses, licence plates, booking numbers or similar information. Publicly listed business information about a place or operator may be mentioned factually.
• No invented experiences or knowingly false statements of fact. Only describe things you believe to be true to the best of your knowledge.
• No unsupported serious accusations. Claims about crimes, fraud, theft or similarly serious misconduct must not be presented as fact without a reliable basis.
• No personal disputes or revenge reviews. Relevant consequences of a conflict may be described factually when they help other campers. For example, “The operator would not let me wash my vehicle” can become the useful information “Vehicle washing is not permitted at this place.”
• The review must relate to the place, its operation or an aspect relevant to a stay.
• No advertising, spam, affiliate content or self-promotion.
• Do not copy reviews or other text for which you do not hold the necessary rights.
• No manipulated reviews. Do not review your own place through a normal user account and do not publish purchased, commissioned or deliberately biased reviews on behalf of an operator or competitor.
• Do not knowingly present an outdated condition as current. An older review does not automatically become false: what matters is that it accurately describes your experience at the time.

Recommendations

Write a review that helps other campers understand the place and decide whether it suits them.

• Describe your own experience whenever possible.
• Be specific. “Road traffic was clearly audible at night” is more useful than “bad place”.
• Separate observation from personal opinion. “The pitches felt tight for my 7-metre camper” is more useful than “The pitches are unusable”.
• Add relevant context such as season, vehicle size, busy or quiet periods, or unusual weather.
• Factual criticism is explicitly welcome. A bad experience may result in a clearly negative review.
• Particularly useful topics include cleanliness, noise, access, pitch size, surroundings, services, sanitary facilities, safety, usability and how concrete problems were handled.
• Describe rules and restrictions factually when they are relevant to other campers.
• Avoid long side stories that do not help people understand the place.

You do not need to artificially “balance” a negative experience. What matters is that your review describes your actual experience factually and to the best of your knowledge.
TEXT;
    }
};
