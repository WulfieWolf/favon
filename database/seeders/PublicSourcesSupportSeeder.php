<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PublicSourcesSupportSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $slug = 'verwendete-oeffentliche-quellen';

        $de = [
            'title' => 'Verwendete öffentliche Quellen',
            'summary' => 'Öffentliche Datenquellen, die Camperwolf für Platzdaten verwendet, einschließlich Lizenzhinweisen.',
            'body' => <<<'TEXT'
Camperwolf ergänzt Community-Daten durch öffentlich verfügbare Datensätze. Die Daten werden automatisiert eingelesen, vereinheitlicht und teilweise mit bestehenden Camperwolf-Einträgen abgeglichen.

Die ursprüngliche Quelle bleibt bei importierten Datensätzen nachvollziehbar.

## Niedersachsen Hub / destination.one

**Anbieter:** Niedersachsen Hub / destination.one  
**Verwendung:** Campingplätze und weitere touristische Platzdaten in Niedersachsen  
**Quelle:** https://open.destination.one  
**Lizenz:** Je nach Datensatz CC0, CC BY oder CC BY-SA

Auf open.destination.one werden Open-Data-Inhalte mit CC0, CC BY oder CC BY-SA veröffentlicht. Die konkrete Lizenz kann sich je Datensatz unterscheiden. Stammdaten und Mediendaten können unterschiedliche Lizenzangaben besitzen.

Camperwolf verarbeitet nur Datensätze, deren Lizenz eine Weiterverwendung erlaubt. Daten können beim Import technisch vereinheitlicht und mit Community-Daten ergänzt werden.

## Touristik- und Freizeitinformationen NRW - TFIS NRW

**Anbieter:** Geobasis NRW / Bezirksregierung Köln  
**Verwendung:** Campingplätze, Parkplätze und Wanderparkplätze in Nordrhein-Westfalen  
**Quelle:** https://www.opengeodata.nrw.de  
**Datensatz:** Touristik- und Freizeitinformationen NRW  
**Lizenz:** Datenlizenz Deutschland - Zero - Version 2.0

Der Datensatz wird von GovData und Geobasis NRW als frei nutzbarer Open-Data-Datensatz unter der Datenlizenz Deutschland - Zero - Version 2.0 geführt.

## Bayerische Vermessungsverwaltung - ATKIS

**Anbieter:** Bayerische Vermessungsverwaltung  
**Verwendung:** Campingplätze, Parkplätze und Wanderparkplätze in Bayern  
**Quelle:** https://www.geodaten.bayern.de/opengeodata/  
**Lizenz:** Creative Commons Namensnennung 4.0 International - CC BY 4.0

Die Daten dürfen bearbeitet, weitergegeben und kommerziell verwendet werden. Die Lizenz verlangt eine angemessene Quellenangabe, einen Link zur Lizenz und einen Hinweis darauf, wenn die Daten verändert wurden.

**Quellenhinweis:** Bayerische Vermessungsverwaltung - www.geodaten.bayern.de - Daten verändert - Lizenz: CC BY 4.0

## Regionalverband Ruhr - POI Camping

**Anbieter:** Regionalverband Ruhr  
**Verwendung:** Campingplätze, Dauercampingplätze, Wohnmobilstellplätze und Jugendzeltplätze in der Metropole Ruhr  
**Quelle:** https://www.govdata.de/suche/daten/poi-camping17bde  
**Datendienst:** Geoportal Ruhr / öffentlicher WFS-Dienst  
**Lizenzhinweis:** GovData führt den Datensatz als frei nutzbaren Open-Data-Datensatz. Maßgeblich sind die jeweils aktuellen Lizenz- und Metadatenangaben des Datensatzes.

Camperwolf verwendet ausgewählte POI-Kategorien des Regionalverbandes Ruhr. Die Daten werden über den öffentlichen WFS-Dienst abgerufen und technisch vereinheitlicht.

## Overture Maps

**Anbieter:** Overture Maps Foundation  
**Verwendung:** Campingplätze, Wohnmobilstellplätze und weitere campingbezogene Orte in Deutschland  
**Quelle:** https://overturemaps.org  
**Lizenzinformationen:** https://docs.overturemaps.org/attribution/

Camperwolf verwendet Daten aus dem Overture-Places-Datensatz. Overture führt Daten verschiedener Anbieter zusammen. Die jeweilige Herkunft und Lizenz wird in den Quelldaten mitgeführt.

Zu den im verwendeten Datenbestand vorkommenden Lizenzen gehören insbesondere:

- CDLA Permissive 2.0
- Apache License 2.0
- CC0 1.0

Bei Daten einzelner Anbieter können zusätzliche Attribution- oder NOTICE-Anforderungen gelten. Maßgeblich sind die Lizenz- und Quelleninformationen des jeweiligen Overture-Datensatzes.

Camperwolf verändert und vereinheitlicht importierte Daten, ordnet sie eigenen Platztypen zu und kann sie mit Community-Daten oder anderen öffentlichen Quellen zusammenführen.

## Hinweise

Öffentliche Quelldaten werden von Camperwolf nicht als verbindlich angesehen. Angaben können veraltet, unvollständig oder fehlerhaft sein und durch die Camperwolf-Community korrigiert oder ergänzt werden.

Die Nennung einer Datenquelle bedeutet nicht, dass der jeweilige Anbieter Camperwolf unterstützt, betreibt oder die von Camperwolf vorgenommenen Änderungen geprüft hat.
TEXT,
        ];

        $en = [
            'title' => 'Public data sources used',
            'summary' => 'Public data sources used by Camperwolf for place data, including licensing information.',
            'body' => <<<'TEXT'
Camperwolf supplements community data with publicly available datasets. Data is imported automatically, normalised and, where appropriate, matched with existing Camperwolf places.

The original source remains traceable for imported records.

## Niedersachsen Hub / destination.one

**Provider:** Niedersachsen Hub / destination.one  
**Use:** Campgrounds and other tourism-related place data in Lower Saxony  
**Source:** https://open.destination.one  
**Licence:** Depending on the individual record: CC0, CC BY or CC BY-SA

Open-data content on open.destination.one is published under CC0, CC BY or CC BY-SA. The exact licence can differ by record, and master data and media may carry different licence information.

## Tourism and leisure information NRW - TFIS NRW

**Provider:** Geobasis NRW / Bezirksregierung Köln  
**Use:** Campgrounds, parking areas and hiking parking areas in North Rhine-Westphalia  
**Source:** https://www.opengeodata.nrw.de  
**Dataset:** Touristik- und Freizeitinformationen NRW  
**Licence:** Data licence Germany - Zero - Version 2.0

GovData and Geobasis NRW list the dataset as freely reusable open data under Data licence Germany - Zero - Version 2.0.

## Bavarian Surveying Administration - ATKIS

**Provider:** Bayerische Vermessungsverwaltung  
**Use:** Campgrounds, parking areas and hiking parking areas in Bavaria  
**Source:** https://www.geodaten.bayern.de/opengeodata/  
**Licence:** Creative Commons Attribution 4.0 International - CC BY 4.0

The data may be modified, redistributed and used commercially. The licence requires appropriate attribution, a link to the licence and an indication when changes have been made.

**Attribution:** Bayerische Vermessungsverwaltung - www.geodaten.bayern.de - data modified - licence: CC BY 4.0

## Regionalverband Ruhr - POI Camping

**Provider:** Regionalverband Ruhr  
**Use:** Campgrounds, permanent campgrounds, motorhome pitches and youth campsites in the Ruhr metropolitan area  
**Source:** https://www.govdata.de/suche/daten/poi-camping17bde  
**Data service:** Geoportal Ruhr / public WFS service  
**Licence note:** GovData lists the dataset as freely reusable open data. The current licence and metadata published for the dataset are authoritative.

## Overture Maps

**Provider:** Overture Maps Foundation  
**Use:** Campgrounds, RV parks and other camping-related places in Germany  
**Source:** https://overturemaps.org  
**Licence information:** https://docs.overturemaps.org/attribution/

Camperwolf uses data from the Overture Places dataset. Overture combines data from several providers and retains source and licence information in the dataset.

Licences occurring in the data used by Camperwolf include in particular:

- CDLA Permissive 2.0
- Apache License 2.0
- CC0 1.0

Some providers may require additional attribution or NOTICE information. The source and licence information attached to the relevant Overture data is authoritative.

Camperwolf normalises and modifies imported data, maps it to its own place types and may combine it with community data or other public sources.

## Notes

Public source data is not treated as authoritative by Camperwolf. Information can be outdated, incomplete or incorrect and may be corrected or supplemented by the Camperwolf community.

Listing a source does not imply endorsement, operation of Camperwolf or approval of Camperwolf's modifications by that provider.
TEXT,
        ];

        DB::table('support_articles')->updateOrInsert(
            ['slug' => $slug],
            [
                'title' => $de['title'],
                'summary' => $de['summary'],
                'context_key' => 'public-data-sources',
                'sort_order' => 6,
                'body' => $de['body'],
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        if (! Schema::hasTable('support_article_translations')) {
            return;
        }

        $articleId = (int) DB::table('support_articles')->where('slug', $slug)->value('id');

        foreach (['de' => $de, 'en' => $en] as $locale => $translation) {
            DB::table('support_article_translations')->updateOrInsert(
                ['support_article_id' => $articleId, 'locale' => $locale],
                [
                    'title' => $translation['title'],
                    'summary' => $translation['summary'],
                    'body' => $translation['body'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }
}
