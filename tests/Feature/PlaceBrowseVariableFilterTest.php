<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PlaceReviewService;
use App\Services\PricePeriodService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaceBrowseVariableFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_numeric_feature_filter_works_and_public_price_filter_is_ignored(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $placeTypeId = (int) DB::table('place_types')->where('is_active', true)->value('id');
        $smallId = $this->place($placeTypeId, 'Kleiner Anschluss', 'kleiner-anschluss');
        $largeId = $this->place($placeTypeId, 'Großer Anschluss', 'grosser-anschluss');
        $featureId = (int) DB::table('features')->where('slug', 'electricity')->value('id');

        $this->feature($smallId, $featureId, 6);
        $this->feature($largeId, $featureId, 16);
        $this->price($smallId, $user->id, 12);
        $this->price($largeId, $user->id, 30);

        $this->get(route('dashboard', [
            'feature_values' => ['electricity' => ['amperage' => ['min' => 10, 'max' => 16]]],
        ]))
            ->assertOk()
            ->assertSee('Großer Anschluss')
            ->assertDontSee('Kleiner Anschluss');

        $this->withSession(['locale' => 'de'])
            ->get(route('dashboard', [
                'reset_filters' => 1,
                'price_values' => ['comparison' => ['min' => 20, 'max' => 30]],
            ]))
            ->assertOk()
            ->assertSee('Großer Anschluss')
            ->assertSee('Kleiner Anschluss')
            ->assertDontSee('Übernachtung ab');
    }

    public function test_place_type_rating_and_priority_filters_work_together(): void
    {
        $this->seed(DatabaseSeeder::class);

        $highReviewer = User::factory()->create();
        $lowReviewer = User::factory()->create();
        $campgroundTypeId = (int) DB::table('place_types')->where('slug', 'campground')->value('id');
        $tentTypeId = (int) DB::table('place_types')->where('slug', 'tent-site')->value('id');

        $campgroundId = $this->place($campgroundTypeId, 'Top Camping', 'top-camping');
        $tentId = $this->place($tentTypeId, 'Einfacher Zeltplatz', 'einfacher-zeltplatz');

        $dogsAllowedId = (int) DB::table('features')->where('slug', 'dogs-allowed')->value('id');
        $this->booleanFeature($campgroundId, $dogsAllowedId, 'allowed');

        $this->price($campgroundId, $highReviewer->id, 18);
        $this->price($tentId, $lowReviewer->id, 9);

        app(PlaceReviewService::class)->submit(
            $highReviewer,
            $campgroundId,
            array_fill_keys(['cleanliness', 'functionality', 'condition', 'safety', 'usability'], 5),
            'Sehr gut.',
        );
        app(PlaceReviewService::class)->submit(
            $lowReviewer,
            $tentId,
            array_fill_keys(['cleanliness', 'functionality', 'condition', 'safety', 'usability'], 2),
            'Einfach.',
        );

        $this->withSession(['locale' => 'de'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Das Wichtigste', 'Score', 'Hunde erlaubt']);

        $this->get(route('dashboard', ['place_types' => ['campground']]))
            ->assertOk()
            ->assertSee('Top Camping')
            ->assertDontSee('Einfacher Zeltplatz')
            ->assertSee('5,0')
            ->assertSee('1 Bewertung')
            ->assertSee('Hunde erlaubt');

        $this->get(route('dashboard', ['place_types' => ['tent-site']]))
            ->assertOk()
            ->assertSee('Einfacher Zeltplatz')
            ->assertDontSee('Top Camping')
            ->assertDontSee('Hunde erlaubt');

        $this->get(route('dashboard', [
            'reset_filters' => 1,
            'rating' => ['min' => 4, 'max' => 5],
        ]))
            ->assertOk()
            ->assertSee('Top Camping')
            ->assertDontSee('Einfacher Zeltplatz');

        $this->get(route('dashboard', [
            'place_types' => ['campground', 'tent-site'],
        ]))
            ->assertOk()
            ->assertSee('Top Camping')
            ->assertSee('Einfacher Zeltplatz')
            ->assertSee('Hunde erlaubt');
    }

    public function test_place_type_dropdown_defaults_to_all_and_all_selected_normalizes_to_unfiltered(): void
    {
        $this->seed(DatabaseSeeder::class);

        $campgroundTypeId = (int) DB::table('place_types')->where('slug', 'campground')->value('id');
        $tentTypeId = (int) DB::table('place_types')->where('slug', 'tent-site')->value('id');

        $this->place($campgroundTypeId, 'Camping Auswahl', 'camping-auswahl');
        $this->place($tentTypeId, 'Zelt Auswahl', 'zelt-auswahl');

        $allPlaceTypeSlugs = DB::table('place_types')
            ->where('is_active', true)
            ->where('is_searchable', true)
            ->orderBy('sort_order')
            ->pluck('slug')
            ->all();

        $this->withSession(['locale' => 'de'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Alle Platztypen')
            ->assertSee('Übernehmen')
            ->assertSee('value="campground"', false)
            ->assertSee('value="tent-site"', false)
            ->assertSee('checked', false);

        $this->get(route('dashboard', [
            'place_types_filter' => 1,
            'place_types' => $allPlaceTypeSlugs,
        ]))
            ->assertOk()
            ->assertSee('Camping Auswahl')
            ->assertSee('Zelt Auswahl')
            ->assertDontSee('Platztyp:');
    }

    public function test_standard_browse_sort_uses_stable_shuffle(): void
    {
        $this->seed(DatabaseSeeder::class);

        $placeTypeId = (int) DB::table('place_types')->where('is_active', true)->value('id');
        $firstId = $this->place($placeTypeId, 'Standard Alpha', 'standard-alpha');
        $secondId = $this->place($placeTypeId, 'Standard Beta', 'standard-beta');

        $expected = collect([
            ['id' => $firstId, 'name' => 'Standard Alpha'],
            ['id' => $secondId, 'name' => 'Standard Beta'],
        ])->sortBy(fn (array $place) => (($place['id'] * 2654435761) % 4294967296))
            ->pluck('name')
            ->values()
            ->all();

        $this->withSession(['locale' => 'de'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<option value="standard:" selected>Standard</option>', false)
            ->assertSeeInOrder($expected);
    }

    public function test_browse_can_sort_by_data_score_in_both_directions(): void
    {
        $this->seed(DatabaseSeeder::class);

        $placeTypeId = (int) DB::table('place_types')->where('is_active', true)->value('id');
        $lowId = $this->place($placeTypeId, 'Niedriger DatenScore', 'niedriger-datenscore');
        $highId = $this->place($placeTypeId, 'Hoher DatenScore', 'hoher-datenscore');

        DB::table('places')->where('id', $lowId)->update([
            'data_score' => 2.5,
            'data_score_dirty' => false,
            'data_score_calculated_at' => now(),
        ]);
        DB::table('places')->where('id', $highId)->update([
            'data_score' => 8.5,
            'data_score_dirty' => false,
            'data_score_calculated_at' => now(),
        ]);

        $this->withSession(['locale' => 'de'])
            ->get(route('dashboard', [
                'sort' => 'data_score',
                'sort_direction' => 'desc',
            ]))
            ->assertOk()
            ->assertSee('DatenScore')
            ->assertSee('Aufsteigend')
            ->assertSee('Absteigend')
            ->assertSeeInOrder(['Hoher DatenScore', 'Niedriger DatenScore']);

        $this->get(route('dashboard', [
            'sort' => 'data_score',
            'sort_direction' => 'asc',
        ]))
            ->assertOk()
            ->assertSeeInOrder(['Niedriger DatenScore', 'Hoher DatenScore']);
    }

    private function booleanFeature(int $placeId, int $featureId, string $status): void
    {
        DB::table('place_features')->insert([
            'place_id' => $placeId,
            'feature_id' => $featureId,
            'feature_option_id' => null,
            'status' => $status,
            'metadata' => null,
            'value_number' => null,
            'value_text' => null,
            'unit_key' => null,
            'unit_id' => null,
            'rate_quantity' => null,
            'rate_unit_id' => null,
            'valid_from' => now(),
            'valid_until' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function place(int $placeTypeId, string $name, string $slug): int
    {
        return (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => $name,
            'slug' => $slug,
            'latitude' => 51.0,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function feature(int $placeId, int $featureId, int $amperage): void
    {
        DB::table('place_features')->insert([
            'place_id' => $placeId,
            'feature_id' => $featureId,
            'feature_option_id' => null,
            'status' => 'available',
            'metadata' => json_encode(['amperage' => $amperage, 'connector' => 'cee-blue']),
            'value_number' => null,
            'value_text' => null,
            'unit_key' => null,
            'unit_id' => null,
            'rate_quantity' => null,
            'rate_unit_id' => null,
            'valid_from' => now(),
            'valid_until' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function price(int $placeId, int $userId, float $amount): void
    {
        $overnightAmount = min(5.0, $amount);
        $vehicleAmount = max(0.0, $amount - $overnightAmount);

        $this->priceComponent($placeId, $userId, 'motorhome-pitch', 'per-pitch-night', $vehicleAmount);
        $this->priceComponent($placeId, $userId, 'adult', 'per-person-night', $overnightAmount);
    }

    private function priceComponent(
        int $placeId,
        int $userId,
        string $productSlug,
        string $billingSlug,
        float $amount,
    ): void {
        $productId = (int) DB::table('price_products')->where('slug', $productSlug)->value('id');
        $eurId = (int) DB::table('units')->where('unit_key', 'EUR')->value('id');
        $billingId = (int) DB::table('price_billing_units')->where('slug', $billingSlug)->value('id');

        app(PricePeriodService::class)->applyDirect(
            $placeId,
            $userId,
            [
                'source_type' => 'product',
                'price_product_id' => $productId,
                'feature_id' => null,
                'price_product_variant_id' => null,
                'custom_product_name' => null,
                'custom_variant_name' => null,
                'display_name' => null,
                'min_vehicle_length_m' => null,
                'max_vehicle_length_m' => null,
                'min_age' => null,
                'max_age' => null,
                'linked_offer_id' => null,
                'is_refundable' => false,
                'condition_text' => null,
            ],
            app(PricePeriodService::class)->normalizeRange(true, null, null),
            [[
                'price_status' => 'from',
                'amount' => $amount,
                'currency_unit_id' => $eurId,
                'rate_quantity' => 1,
                'price_billing_unit_id' => $billingId,
            ]],
        );
    }
}
