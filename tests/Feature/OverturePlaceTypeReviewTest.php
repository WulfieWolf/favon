<?php

namespace Tests\Feature;

use App\Services\Imports\OverturePlaceTypeReviewService;
use Database\Seeders\PublicSourcesSupportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OverturePlaceTypeReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_contains_only_created_linked_overture_places_and_excludes_open_duplicates(): void
    {
        [$sourceId, $campgroundTypeId] = $this->baseFixture();

        $createdPlaceId = $this->place('Created Overture Place', $campgroundTypeId);
        $duplicatePlaceId = $this->place('Existing Candidate', $campgroundTypeId);

        $this->externalRecord($sourceId, 'created-1', 'created', $createdPlaceId, 'Created Overture Place');
        $this->externalRecord($sourceId, 'duplicate-1', 'possible_duplicate', null, 'Open Duplicate');

        $path = storage_path('framework/testing/overture-type-export.csv');

        try {
            $result = app(OverturePlaceTypeReviewService::class)->exportCreated($path);

            $this->assertSame(1, $result['records']);
            $contents = file_get_contents($path);

            $this->assertStringContainsString('created-1', $contents);
            $this->assertStringContainsString('Created Overture Place', $contents);
            $this->assertStringNotContainsString('duplicate-1', $contents);
            $this->assertStringNotContainsString('Open Duplicate', $contents);
        } finally {
            @unlink($path);
        }
    }

    public function test_review_preview_and_apply_change_only_created_overture_place(): void
    {
        [$sourceId, $campgroundTypeId] = $this->baseFixture();
        $serviceTypeId = $this->placeType('service-station');

        $placeId = $this->place('Camper Werkstatt', $campgroundTypeId);
        $this->externalRecord($sourceId, 'created-service', 'created', $placeId, 'Camper Werkstatt');

        $path = storage_path('framework/testing/overture-type-review.csv');
        $this->writeReviewCsv($path, [[
            'external_id' => 'created-service',
            'place_id' => $placeId,
            'name' => 'Camper Werkstatt',
            'current_place_type' => 'campground',
            'target_place_type' => 'service-station',
            'reason' => 'Campingbezogene Werkstatt.',
        ]]);

        try {
            $preview = app(OverturePlaceTypeReviewService::class)->review($path, false);

            $this->assertSame(1, $preview['would_change']);
            $this->assertSame(0, $preview['changed']);
            $this->assertSame($campgroundTypeId, DB::table('places')->where('id', $placeId)->value('place_type_id'));

            $applied = app(OverturePlaceTypeReviewService::class)->review($path, true);

            $this->assertSame(1, $applied['would_change']);
            $this->assertSame(1, $applied['changed']);
            $this->assertSame($serviceTypeId, DB::table('places')->where('id', $placeId)->value('place_type_id'));
            $this->assertDatabaseHas('place_history', [
                'place_id' => $placeId,
                'external_source_id' => $sourceId,
                'action' => 'overture_place_type_reclassified',
            ]);
            $this->assertDatabaseHas('audit_logs', [
                'entity_type' => 'place',
                'entity_id' => $placeId,
                'action' => 'overture_place_type_reclassified',
            ]);
        } finally {
            @unlink($path);
        }
    }

    public function test_review_blocks_possible_duplicate_even_if_csv_mentions_it(): void
    {
        [$sourceId, $campgroundTypeId] = $this->baseFixture();
        $placeId = $this->place('Existing Place', $campgroundTypeId);

        $this->externalRecord($sourceId, 'duplicate-unsafe', 'possible_duplicate', null, 'External Duplicate');

        $path = storage_path('framework/testing/overture-type-blocked.csv');
        $this->writeReviewCsv($path, [[
            'external_id' => 'duplicate-unsafe',
            'place_id' => $placeId,
            'name' => 'External Duplicate',
            'current_place_type' => 'campground',
            'target_place_type' => 'service-station',
            'reason' => 'Should never apply.',
        ]]);

        try {
            $result = app(OverturePlaceTypeReviewService::class)->review($path, true);

            $this->assertSame(1, $result['blocked']);
            $this->assertSame(0, $result['changed']);
            $this->assertSame($campgroundTypeId, DB::table('places')->where('id', $placeId)->value('place_type_id'));
        } finally {
            @unlink($path);
        }
    }

    public function test_public_sources_seeder_only_creates_targeted_help_article(): void
    {
        $before = DB::table('support_articles')->count();

        $this->seed(PublicSourcesSupportSeeder::class);

        $this->assertSame($before + 1, DB::table('support_articles')->count());
        $article = DB::table('support_articles')->where('slug', 'verwendete-oeffentliche-quellen')->first();

        $this->assertNotNull($article);
        $this->assertSame('Verwendete öffentliche Quellen', $article->title);
        $this->assertStringContainsString('Overture Maps', (string) $article->body);
        $this->assertStringContainsString('Regionalverband Ruhr', (string) $article->body);

        $this->assertDatabaseHas('support_article_translations', [
            'support_article_id' => $article->id,
            'locale' => 'de',
            'title' => 'Verwendete öffentliche Quellen',
        ]);
        $this->assertDatabaseHas('support_article_translations', [
            'support_article_id' => $article->id,
            'locale' => 'en',
            'title' => 'Public data sources used',
        ]);
    }

    private function baseFixture(): array
    {
        $campgroundTypeId = $this->placeType('campground');
        $this->placeType('motorhome-pitch');
        $this->placeType('camping-outdoor');
        $this->placeType('service-station');

        $sourceId = (int) DB::table('external_sources')->insertGetId([
            'slug' => 'overture-places-camping',
            'name' => 'Overture Maps Places - Camping',
            'source_type' => 'file',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$sourceId, $campgroundTypeId];
    }

    private function placeType(string $slug): int
    {
        $existing = DB::table('place_types')->where('slug', $slug)->value('id');
        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('place_types')->insertGetId([
            'slug' => $slug,
            'is_active' => true,
            'is_searchable' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function place(string $name, int $placeTypeId): int
    {
        return (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => $name,
            'slug' => 'type-review-'.uniqid(),
            'latitude' => 51.0,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'open',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function externalRecord(int $sourceId, string $externalId, string $classification, ?int $placeId, string $name): int
    {
        $normalized = [
            'external_id' => $externalId,
            'place' => [
                'name' => $name,
                'address' => ['city' => 'Essen'],
                'operator' => ['url' => 'https://example.test'],
            ],
            'source_properties' => [
                'overture_category' => 'campground',
                'basic_category' => 'campground',
                'confidence' => 0.9,
                'taxonomy_hierarchy' => ['lodging', 'campground'],
                'taxonomy_alternates' => [],
                'address_freeform' => 'Testweg 1, Essen',
            ],
        ];

        return (int) DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => $externalId,
            'place_id' => $placeId,
            'status' => 'active',
            'classification' => $classification,
            'normalized_data' => json_encode($normalized),
            'normalized_hash' => hash('sha256', json_encode($normalized)),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function writeReviewCsv(string $path, array $rows): void
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $handle = fopen($path, 'wb');
        fputcsv($handle, [
            'external_id',
            'place_id',
            'name',
            'current_place_type',
            'target_place_type',
            'reason',
        ], ';', '"', '');

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['external_id'],
                $row['place_id'],
                $row['name'],
                $row['current_place_type'],
                $row['target_place_type'],
                $row['reason'],
            ], ';', '"', '');
        }

        fclose($handle);
    }
}
