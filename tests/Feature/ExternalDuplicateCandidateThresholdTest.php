<?php

namespace Tests\Feature;

use App\Services\Imports\Datex2ParkingCandidateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExternalDuplicateCandidateThresholdTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_flags_meaningful_duplicate_candidates(): void
    {
        $placeTypeId = (int) DB::table('place_types')->where('is_active', true)->value('id');

        $this->place($placeTypeId, 'Very close unrelated stop', 'very-close', 52.0010, 9.0000);
        $this->place($placeTypeId, 'Campingplatz Northeim', 'far-medium-name', 52.0069, 9.0000);
        $this->place($placeTypeId, 'Aral Autohof Bockel', 'medium-strong-name', 52.0040, 9.0000);
        $this->place($placeTypeId, 'Completely unrelated', 'medium-unrelated', 52.0030, 9.0000);

        $mapped = [
            'place' => [
                'name' => 'Wohnmobilstation Aral Autohof Bockel',
                'latitude' => 52.0000,
                'longitude' => 9.0000,
            ],
        ];

        $candidates = app(Datex2ParkingCandidateService::class)->candidates($mapped, 10);
        $slugs = collect($candidates)->pluck('slug')->all();

        self::assertContains('very-close', $slugs);
        self::assertContains('medium-strong-name', $slugs);
        self::assertNotContains('far-medium-name', $slugs);
        self::assertNotContains('medium-unrelated', $slugs);
    }

    private function place(int $placeTypeId, string $name, string $slug, float $latitude, float $longitude): void
    {
        DB::table('places')->insert([
            'place_type_id' => $placeTypeId,
            'name' => $name,
            'slug' => $slug,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
