<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DemoDataResetService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_reset_preserves_owner_and_builds_broad_dataset(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::factory()->create([
            'email' => 'owner@camperwolf.test',
        ]);
        $oldUser = User::factory()->create([
            'email' => 'old-user@camperwolf.test',
        ]);

        config()->set('camperwolf.owner_email', $owner->email);

        $placeTypeId = DB::table('place_types')->where('slug', 'campground')->value('id');
        $sourcePlaceId = (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Old duplicate',
            'slug' => 'old-duplicate',
            'latitude' => 51.0,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'allowed',
            'opening_status' => 'open',
            'is_active' => false,
            'created_by' => $oldUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $targetPlaceId = (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Old target',
            'slug' => 'old-target',
            'latitude' => 51.1,
            'longitude' => 7.1,
            'publication_status' => 'published',
            'legal_status' => 'allowed',
            'opening_status' => 'open',
            'is_active' => true,
            'created_by' => $oldUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('place_merges')->insert([
            'source_place_id' => $sourcePlaceId,
            'target_place_id' => $targetPlaceId,
            'merged_by' => $oldUser->id,
            'status' => 'completed',
            'decisions' => json_encode([]),
            'snapshot' => json_encode([]),
            'merged_at' => now(),
            'reversed_by' => null,
            'reversed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(DemoDataResetService::class)->reset();
        app(DemoDataSeeder::class)->run();

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'email' => 'owner@camperwolf.test',
        ]);

        $this->assertDatabaseMissing('users', [
            'id' => $oldUser->id,
        ]);
        $this->assertSame(0, DB::table('place_merges')->count());

        $this->assertSame(
            10,
            DB::table('users')->where('email', 'like', 'demo%@camperwolf.test')->count(),
        );

        $this->assertSame(50, DB::table('places')->count());
        $this->assertSame(350, DB::table('place_reviews')->count());

        $this->assertGreaterThan(
            DB::table('place_reviews')->count(),
            DB::table('place_review_versions')->count(),
            'Historical review versions should exist in addition to current versions.',
        );

        $this->assertGreaterThan(0, DB::table('xp_ledger')->count());
        $this->assertGreaterThan(0, DB::table('badge_progress_events')->count());
        $this->assertGreaterThan(0, DB::table('user_badge_unlocks')->count());
        $this->assertGreaterThan(0, DB::table('place_features')->count());
        $this->assertGreaterThan(0, DB::table('audit_logs')
            ->where('entity_type', 'place_feature')
            ->where('source', 'user')
            ->count());

        $placeTypeSlugs = [
            'campground',
            'motorhome-pitch',
            'tent-site',
            'parking',
            'hiking-parking',
            'rest-area',
            'free-pitch',
            'service-station',
            'camping-outdoor',
        ];

        $usedPlaceTypes = DB::table('places as p')
            ->join('place_types as pt', 'pt.id', '=', 'p.place_type_id')
            ->whereIn('pt.slug', $placeTypeSlugs)
            ->distinct()
            ->count('pt.slug');

        $this->assertSame(count($placeTypeSlugs), $usedPlaceTypes);

        $completePlaces = 0;
        $incompletePlaces = 0;

        foreach (DB::table('places')->get(['id', 'place_type_id']) as $place) {
            $visibleWorkflowCount = DB::table('feature_workflows as fw')
                ->join('features as f', 'f.id', '=', 'fw.feature_id')
                ->join('feature_place_types as fpt', function ($join) use ($place): void {
                    $join->on('fpt.feature_id', '=', 'f.id')
                        ->where('fpt.place_type_id', '=', $place->place_type_id)
                        ->whereIn('fpt.visibility', ['standard', 'extended']);
                })
                ->where('fw.is_active', true)
                ->where('f.is_active', true)
                ->count();

            $featureCount = DB::table('place_features')
                ->where('place_id', $place->id)
                ->where('is_active', true)
                ->whereNull('valid_until')
                ->count();

            if ($featureCount === $visibleWorkflowCount) {
                $completePlaces++;
            } elseif ($featureCount < $visibleWorkflowCount) {
                $incompletePlaces++;
            }
        }

        $this->assertGreaterThanOrEqual(4, $completePlaces);
        $this->assertGreaterThan($completePlaces, $incompletePlaces);

        $this->assertSame(50, DB::table('opening_hour_periods')->count());
        $this->assertSame(350, DB::table('opening_hours')->count());
        $this->assertGreaterThan(0, DB::table('opening_hour_periods')->where('is_year_round', false)->count());

        $this->assertGreaterThan(0, DB::table('place_price_offers')->count());
        $this->assertSame(
            50,
            DB::table('place_price_offers')->distinct()->count('place_id'),
            'Every demo place should have at least one structured price offer.',
        );
        $this->assertGreaterThan(0, DB::table('place_price_offers')->where('source_type', 'product')->count());
        $this->assertGreaterThan(0, DB::table('place_price_offers')->where('source_type', 'feature')->count());
        $this->assertGreaterThan(0, DB::table('place_price_periods')->count());
        $this->assertGreaterThan(0, DB::table('place_price_lines')->count());
        $this->assertGreaterThan(0, DB::table('place_price_lines')->where('price_status', 'free')->count());
        $this->assertGreaterThan(0, DB::table('place_price_lines')->where('price_status', 'fixed')->count());
        $this->assertGreaterThan(0, DB::table('place_price_lines')->where('price_status', 'from')->count());

        $countries = DB::table('place_addresses')
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->distinct()
            ->count('country_code');

        $this->assertGreaterThanOrEqual(8, $countries);
    }
}
