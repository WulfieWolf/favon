<?php

namespace Tests\Feature;

use App\Services\Imports\RvrCampingMapper;
use Tests\TestCase;

class RvrCampingMapperTest extends TestCase
{
    public function test_maps_rvr_fields_and_marks_listed_places_open(): void
    {
        $mapped = app(RvrCampingMapper::class)->map([
            'external_id' => '123',
            'poi_id' => '123',
            'name' => 'Camping am Testsee',
            'beschreibung' => 'Beschreibung',
            'link' => 'https://example.test',
            'ort' => 'Essen',
            'plz' => '45127',
            'strasse' => 'Testweg',
            'hausnummer' => '7',
            'adresse_zusatz' => 'Tor 2',
            'latitude' => 51.45,
            'longitude' => 7.01,
            'geaendert_am' => '2026-09-29',
            'categories' => ['Campingplätze'],
            'institution' => 'Stadt Essen',
        ]);

        $this->assertSame('123', $mapped['external_id']);
        $this->assertSame('Camping am Testsee', $mapped['place']['name']);
        $this->assertSame(51.45, $mapped['place']['latitude']);
        $this->assertSame(7.01, $mapped['place']['longitude']);
        $this->assertSame('campground', $mapped['place']['suggested_place_type']);
        $this->assertSame('open', $mapped['place']['opening_status']);
        $this->assertSame('45127', $mapped['place']['address']['postal_code']);
        $this->assertSame('Essen', $mapped['place']['address']['city']);
        $this->assertSame('Testweg', $mapped['place']['address']['street']);
        $this->assertSame('7', $mapped['place']['address']['house_number']);
        $this->assertSame('Tor 2', $mapped['place']['address']['address_addition']);
        $this->assertSame('https://example.test', $mapped['place']['operator']['url']);
        $this->assertSame('Beschreibung', $mapped['place']['description']);
        $this->assertSame('2026-09-29', $mapped['source_updated_at']);
        $this->assertSame('Stadt Essen', $mapped['source_properties']['institution']);
    }

    public function test_uses_gid_fallback_external_id_when_prepared_by_sync(): void
    {
        $mapped = app(RvrCampingMapper::class)->map([
            'external_id' => 'gid:64956',
            'gid' => '64956',
            'poi_id' => null,
            'name' => 'Autohaus Pauli',
            'latitude' => 51.0,
            'longitude' => 7.0,
            'categories' => ['Wohnmobilstellplätze'],
        ]);

        $this->assertSame('gid:64956', $mapped['external_id']);
        $this->assertNull($mapped['source_properties']['poi_id']);
        $this->assertSame('64956', $mapped['source_properties']['gid']);
        $this->assertSame('motorhome-pitch', $mapped['place']['suggested_place_type']);
    }

    public function test_campground_has_priority_over_motorhome_and_tent_categories(): void
    {
        $mapper = app(RvrCampingMapper::class);

        $this->assertSame('campground', $mapper->placeType([
            'Wohnmobilstellplätze',
            'Jugendzeltplätze',
            'Campingplätze',
        ]));

        $this->assertSame('campground', $mapper->placeType([
            'Wohnmobilstellplätze',
            'Dauercampingplätze',
        ]));
    }

    public function test_maps_motorhome_and_tent_only_categories(): void
    {
        $mapper = app(RvrCampingMapper::class);

        $this->assertSame('motorhome-pitch', $mapper->placeType(['Wohnmobilstellplätze']));
        $this->assertSame('tent-site', $mapper->placeType(['Jugendzeltplätze']));
        $this->assertNull($mapper->placeType(['Andere Kategorie']));
    }
}
