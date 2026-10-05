<?php

namespace Tests\Feature;

use App\Services\Imports\RvrCampingSyncService;
use ReflectionMethod;
use Tests\TestCase;

class RvrCampingSyncServiceTest extends TestCase
{
    public function test_parses_filtered_rvr_wfs_feature_with_epsg4326_axis_order(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<wfs:FeatureCollection xmlns:wfs="http://www.opengis.net/wfs" xmlns:gml="http://www.opengis.net/gml" xmlns:ms="http://mapserver.gis.umn.edu/mapserver">
  <gml:featureMember>
    <ms:poi_einfach>
      <ms:geom><gml:Point srsName="EPSG:4326"><gml:pos>51.582676 6.518394</gml:pos></gml:Point></ms:geom>
      <ms:gid>77</ms:gid>
      <ms:poi_id>1234</ms:poi_id>
      <ms:name>Camping am Testsee</ms:name>
      <ms:beschreibung>Testbeschreibung</ms:beschreibung>
      <ms:link>https://example.test</ms:link>
      <ms:ort>Teststadt</ms:ort>
      <ms:plz>45127</ms:plz>
      <ms:strasse>Testweg</ms:strasse>
      <ms:hausnummer>7</ms:hausnummer>
      <ms:kategorie_id>42</ms:kategorie_id>
      <ms:kategorie>Campingplätze</ms:kategorie>
      <ms:kategorie_pfad>Freizeit/Campingplätze</ms:kategorie_pfad>
      <ms:institution>Regionalverband Ruhr</ms:institution>
      <ms:geaendert_am>2026-09-29</ms:geaendert_am>
    </ms:poi_einfach>
  </gml:featureMember>
</wfs:FeatureCollection>
XML;

        $service = app(RvrCampingSyncService::class);
        $method = new ReflectionMethod($service, 'parseGml');
        $method->setAccessible(true);

        $items = $method->invoke($service, $xml, 'Campingplätze');

        $this->assertCount(1, $items);
        $this->assertSame('1234', $items[0]['poi_id']);
        $this->assertSame('Camping am Testsee', $items[0]['name']);
        $this->assertSame('Campingplätze', $items[0]['kategorie']);
        $this->assertSame(51.582676, $items[0]['latitude']);
        $this->assertSame(6.518394, $items[0]['longitude']);
        $this->assertSame('Teststadt', $items[0]['ort']);
        $this->assertSame('https://example.test', $items[0]['link']);
    }

    public function test_ignores_features_from_unexpected_category(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<wfs:FeatureCollection xmlns:wfs="http://www.opengis.net/wfs" xmlns:gml="http://www.opengis.net/gml" xmlns:ms="http://mapserver.gis.umn.edu/mapserver">
  <gml:featureMember>
    <ms:poi_einfach>
      <ms:geom><gml:Point srsName="EPSG:4326"><gml:pos>51.5 7.0</gml:pos></gml:Point></ms:geom>
      <ms:poi_id>999</ms:poi_id>
      <ms:name>Bahnhof Test</ms:name>
      <ms:kategorie>Bahnhöfe</ms:kategorie>
    </ms:poi_einfach>
  </gml:featureMember>
</wfs:FeatureCollection>
XML;

        $service = app(RvrCampingSyncService::class);
        $method = new ReflectionMethod($service, 'parseGml');
        $method->setAccessible(true);

        $this->assertSame([], $method->invoke($service, $xml, 'Campingplätze'));
    }
}
