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
            ['slug' => 'datenscore'],
            [
                'title' => 'DatenScore - Wie vollständig sind die Platzdaten?',
                'summary' => 'Was der DatenScore aussagt, wie er berechnet wird und warum er keine Garantie für die Richtigkeit der Angaben ist.',
                'body' => $this->germanBody(),
                'context_key' => 'data-score',
                'sort_order' => 35,
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        $id = DB::table('support_articles')->where('slug', 'datenscore')->value('id');

        if ($id && Schema::hasTable('support_article_translations')) {
            foreach ([
                'de' => [
                    'title' => 'DatenScore - Wie vollständig sind die Platzdaten?',
                    'summary' => 'Was der DatenScore aussagt, wie er berechnet wird und warum er keine Garantie für die Richtigkeit der Angaben ist.',
                    'body' => $this->germanBody(),
                ],
                'en' => [
                    'title' => 'DataScore - How complete is the place information?',
                    'summary' => 'What the DataScore means, how it is calculated and why it is not a guarantee that every detail is correct.',
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
        $id = DB::table('support_articles')->where('slug', 'datenscore')->value('id');

        if ($id) {
            DB::table('support_article_translations')->where('support_article_id', $id)->delete();
            DB::table('support_articles')->where('id', $id)->delete();
        }
    }

    private function germanBody(): string
    {
        return <<<'TEXT'
Der DatenScore zeigt, wie vollständig die bei Camperwolf bekannten Informationen zu einem Platz sind. Er bewertet nicht, wie gut ein Platz ist, und ist keine Garantie dafür, dass jede Angabe noch aktuell oder richtig ist.

## Der Score

Der DatenScore reicht von 0,0 bis 10,0. Er besteht aus zwei Teilen:

- Basisdaten: 75 %
- Merkmale: 25 %

Die Basisdaten werden stärker gewichtet, weil sie für die Planung einer Anreise besonders wichtig sind.

## Was zählt zu den Basisdaten?

Für den Basis-Score werden sechs freiwillige Angaben berücksichtigt:

- Betreiber
- Adresse mit Straße, Postleitzahl und Ort
- Telefon
- E-Mail
- Website
- Betriebsstatus

Eine Hausnummer ist für den Score nicht zwingend erforderlich, weil viele ländliche Camping- und Stellplätze eine nutzbare Adresse ohne Hausnummer haben.

Name, Koordinaten und Platztyp zählen nicht mit, weil diese Angaben für jeden Camperwolf-Platz ohnehin erforderlich sind. Beschreibung, Anfahrt, Preise und Öffnungszeiten zählen ebenfalls nicht zum DatenScore.

## Was zählt bei den Merkmalen?

Gezählt werden alle aktiven Merkmale, die laut aktuellem Merkmalskatalog für den jeweiligen Platztyp relevant sind.

Ein Merkmal gilt als bekannt, wenn ausdrücklich ein Wert dazu hinterlegt wurde. Dabei zählt auch eine negative Angabe wie "Toilette nicht vorhanden" als bekannt. "Unbekannt" oder ein noch nicht ausgefülltes Merkmal zählt nicht als bekannt.

Beispiel: Sind für einen Platztyp 100 Merkmale relevant und zu 20 davon ist ein Wert bekannt, beträgt der Merkmals-Score 2,0 von 10,0.

## Formel

Basis-Score = bekannte Basisdaten / mögliche Basisdaten × 10

Merkmals-Score = bekannte relevante Merkmale / alle relevanten Merkmale × 10

DatenScore = (Basis-Score × 0,75) + (Merkmals-Score × 0,25)

Intern wird mit der genauen Zahl gerechnet. Angezeigt wird eine Nachkommastelle. Dadurch kann zum Beispiel ein rechnerischer Wert von 9,95 als 10,0 angezeigt werden.

## Farben

- Grün: DatenScore ab 7,0
- Gelb: DatenScore ab 5,0 und unter 7,0
- Rot: DatenScore unter 5,0

Die Farben zeigen nur den Informationsstand. Ein grüner DatenScore bedeutet nicht, dass Camperwolf die Angaben garantiert oder vollständig überprüft hat.

## Letzte Änderung

Zusätzlich zeigt Camperwolf an, wann zuletzt eine Änderung an den erfassten Platzinformationen gespeichert wurde.

Dieses Datum ist kein Prüfdatum. Wenn heute zum Beispiel nur das Merkmal "Toilette" ergänzt wurde, bedeutet "Letzte Änderung: heute" nicht, dass heute auch Adresse, Betriebsstatus, Preise oder alle anderen Angaben überprüft wurden.

Angaben können sich jederzeit ändern. Prüfe deshalb besonders wichtige Informationen vor deiner Anreise gegebenenfalls direkt beim Betreiber.

## Mithelfen

Fehlende Angaben kannst du direkt über Camperwolf ergänzen oder als Änderung vorschlagen. Sobald eine freigegebene Information hinzukommt oder sich der Merkmalskatalog ändert, wird der DatenScore anhand des aktuellen Datenbestands neu berechnet.
TEXT;
    }

    private function englishBody(): string
    {
        return <<<'TEXT'
The DataScore shows how complete the information known to Camperwolf is for a place. It does not rate how good a place is and it is not a guarantee that every detail is still current or correct.

## The score

The DataScore ranges from 0.0 to 10.0 and consists of two parts:

- Basis data: 75%
- Features: 25%

Basis data is weighted more heavily because it is particularly important when planning a trip.

## What counts as basis data?

Six optional pieces of information are included:

- Operator
- Address with street, postal code and city
- Phone
- Email
- Website
- Operating status

A house number is not required for the score because many rural camping and motorhome places have a usable address without one.

Name, coordinates and place type do not count because they are already required for every Camperwolf place. Description, directions, prices and opening hours also do not count toward the DataScore.

## What counts as a known feature?

All active features that are relevant to the place type according to the current feature catalog are counted.

A feature is known when an explicit value has been stored. A negative value such as "toilet not available" also counts as known. "Unknown" or a feature that has not been filled in does not count as known.

Example: if 100 features are relevant to a place type and 20 have a known value, the feature score is 2.0 out of 10.0.

## Formula

Basis score = known basis data / possible basis data × 10

Feature score = known relevant features / all relevant features × 10

DataScore = (basis score × 0.75) + (feature score × 0.25)

The exact value is used internally. The displayed score is rounded to one decimal place. For example, a calculated value of 9.95 can therefore be displayed as 10.0.

## Colours

- Green: DataScore 7.0 or higher
- Yellow: DataScore 5.0 or higher and below 7.0
- Red: DataScore below 5.0

The colours only describe information completeness. A green DataScore does not mean that Camperwolf guarantees or has fully verified the information.

## Last change

Camperwolf also shows when a change to the recorded place information was last stored.

This is not a verification date. If only the "toilet" feature was added today, "Last change: today" does not mean that the address, operating status, prices or all other information were checked today.

Information can change at any time. Check particularly important details directly with the operator before travelling when appropriate.

## Help complete the data

You can add missing information or suggest a change directly through Camperwolf. When approved information is added or the feature catalog changes, the DataScore is recalculated from the current data.
TEXT;
    }
};
