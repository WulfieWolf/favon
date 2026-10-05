<?php

namespace Tests\Feature;

use App\Services\Imports\OverturePlacesCsvStageService;
use App\Services\Imports\OverturePlacesMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OverturePlacesImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_mapper_preserves_overture_metadata_and_maps_place_types(): void
    {
        $mapped = app(OverturePlacesMapper::class)->map([
            'id' => '08f123',
            'name' => 'Wohnmobilstellplatz Test',
            'category' => 'rv_park',
            'basic_category' => 'rv_park',
            'confidence' => '0.91',
            'operating_status' => 'open',
            'latitude' => '51.45',
            'longitude' => '7.01',
            'address' => 'Testweg 7, 45127 Essen',
            'locality' => 'Essen',
            'postcode' => '45127',
            'region' => 'Nordrhein-Westfalen',
            'country' => 'DE',
            'website' => 'https://example.test',
            'phone' => '+4912345',
            'email' => 'info@example.test',
            'taxonomy_hierarchy' => '["rv_park","lodging"]',
            'taxonomy_alternates' => '[]',
            'sources' => '[{"dataset":"meta","license":"CDLA-Permissive-2.0"}]',
        ]);

        $this->assertSame('08f123', $mapped['external_id']);
        $this->assertSame('motorhome-pitch', $mapped['place']['suggested_place_type']);
        $this->assertSame('open', $mapped['place']['opening_status']);
        $this->assertSame('Essen', $mapped['place']['address']['city']);
        $this->assertSame('https://example.test', $mapped['place']['operator']['url']);
        $this->assertSame(0.91, $mapped['source_properties']['confidence']);
        $this->assertSame('meta', $mapped['source_properties']['sources'][0]['dataset']);
    }

    public function test_mapper_treats_current_listing_as_open_unless_explicitly_closed(): void
    {
        $mapper = app(OverturePlacesMapper::class);

        foreach ([null, '', 'open', 'unknown'] as $status) {
            $mapped = $mapper->map([
                'id' => 'test-'.md5((string) $status),
                'name' => 'Camping Test',
                'category' => 'campground',
                'latitude' => 51.0,
                'longitude' => 7.0,
                'country' => 'DE',
                'operating_status' => $status,
            ]);

            $this->assertSame('open', $mapped['place']['opening_status']);
        }

        $temporarilyClosed = $mapper->map([
            'id' => 'temporary',
            'name' => 'Camping Test',
            'category' => 'campground',
            'latitude' => 51.0,
            'longitude' => 7.0,
            'country' => 'DE',
            'operating_status' => 'temporarily_closed',
        ]);

        $permanentlyClosed = $mapper->map([
            'id' => 'permanent',
            'name' => 'Camping Test',
            'category' => 'campground',
            'latitude' => 51.0,
            'longitude' => 7.0,
            'country' => 'DE',
            'operating_status' => 'permanently_closed',
        ]);

        $this->assertSame('temporarily_closed', $temporarilyClosed['place']['opening_status']);
        $this->assertSame('permanently_closed', $permanentlyClosed['place']['opening_status']);
    }

    public function test_mapper_maps_campground_and_holiday_park_to_campground(): void
    {
        $mapper = app(OverturePlacesMapper::class);

        $this->assertSame('campground', $mapper->placeType('campground'));
        $this->assertSame('campground', $mapper->placeType('holiday_park'));
        $this->assertSame('motorhome-pitch', $mapper->placeType('rv_park'));
        $this->assertNull($mapper->placeType('car_dealer'));
    }

    public function test_csv_stage_creates_source_records_without_classifying_them(): void
    {
        $path = storage_path('framework/testing/overture-test.csv');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $headers = [
            'id', 'name', 'category', 'basic_category', 'confidence', 'operating_status',
            'latitude', 'longitude', 'address', 'locality', 'postcode', 'region', 'country',
            'website', 'phone', 'email', 'taxonomy_hierarchy', 'taxonomy_alternates', 'sources',
        ];

        $handle = fopen($path, 'wb');
        fputcsv($handle, $headers, ',', '"', '');

        for ($i = 1; $i <= 1000; $i++) {
            fputcsv($handle, [
                'overture-'.$i,
                'Camping Test '.$i,
                $i % 10 === 0 ? 'rv_park' : 'campground',
                $i % 10 === 0 ? 'rv_park' : 'campground',
                '0.9',
                'open',
                '51.'.$i,
                '7.'.$i,
                'Testweg '.$i,
                'Essen',
                '45127',
                'Nordrhein-Westfalen',
                'DE',
                'https://example.test/'.$i,
                '',
                '',
                '[]',
                '[]',
                '[]',
            ], ',', '"', '');
        }

        fclose($handle);

        try {
            $result = app(OverturePlacesCsvStageService::class)->stage($path);

            $this->assertSame(1000, $result['mapped_records']);
            $this->assertSame(1000, DB::table('external_records')
                ->where('external_source_id', $result['source_id'])
                ->count());
            $this->assertSame(0, DB::table('external_records')
                ->where('external_source_id', $result['source_id'])
                ->whereNotNull('classification')
                ->count());
        } finally {
            @unlink($path);
        }
    }
}
