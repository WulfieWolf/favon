<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ChangeRequestModerationService;
use App\Services\PricePeriodService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlacePriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_editor_system_texts_are_available_in_german_and_english(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $this->createPlace($user);

        $this->actingAs($user)
            ->withSession(['locale' => 'de'])
            ->get(route('places.prices.edit', 'price-testplatz'))
            ->assertOk()
            ->assertSee('Preise bearbeiten')
            ->assertSee('Neues Preisangebot')
            ->assertSee('Preispositionen');

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('places.prices.edit', 'price-testplatz'))
            ->assertOk()
            ->assertSee('Edit prices')
            ->assertSee('New price offer')
            ->assertSee('Price items')
            ->assertSee('Price Testplatz');
    }

    public function test_registered_user_can_submit_structured_price_proposal_without_changing_live_prices(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $placeId = $this->createPlace($user);

        $productId = DB::table('price_products')->where('slug', 'motorhome-pitch')->value('id');
        $billingId = DB::table('price_billing_units')->where('slug', 'per-pitch-night')->value('id');
        $eurId = DB::table('units')->where('unit_key', 'EUR')->value('id');

        $payload = [
            'source_type' => 'product',
            'price_product_id' => $productId,
            'display_name' => 'Wohnmobil XXL',
            'max_vehicle_length_m' => 15,
            'period_mode' => 'year_round',
            'lines' => [
                [
                    'price_status' => 'fixed',
                    'amount' => 25,
                    'currency_unit_id' => $eurId,
                    'rate_quantity' => 1,
                    'price_billing_unit_id' => $billingId,
                ],
            ],
        ];

        $this->actingAs($user)
            ->put(route('places.prices.update', 'price-testplatz'), $payload)
            ->assertRedirect(route('places.show', 'price-testplatz'));

        $this->assertDatabaseMissing('place_price_offers', [
            'place_id' => $placeId,
            'is_active' => true,
        ]);

        $request = DB::table('change_requests as cr')
            ->join('suggestable_fields as sf', 'sf.id', '=', 'cr.suggestable_field_id')
            ->where('cr.place_id', $placeId)
            ->where('sf.target_table', 'place_price_offers')
            ->where('sf.target_field', 'period_pricing')
            ->first(['cr.id', 'cr.status', 'cr.submitted_by']);

        $this->assertNotNull($request);
        $this->assertSame('pending', $request->status);
        $this->assertSame($user->id, (int) $request->submitted_by);
    }

    public function test_approval_creates_offer_period_price_and_xp(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $moderator = User::factory()->create();
        $this->assignRole($moderator, 'mod');
        $placeId = $this->createPlace($user);

        $prices = app(PricePeriodService::class);
        $offer = $this->offerData('child');
        $offer['max_age'] = 11;

        $requestId = $prices->createProposal(
            $placeId,
            $user->id,
            $offer,
            $prices->normalizeRange(true, null, null),
            [[
                'price_status' => 'unknown',
                'amount' => null,
                'currency_unit_id' => null,
                'rate_quantity' => 1,
                'price_billing_unit_id' => null,
            ]],
            'Preis ist bekanntlich nicht veröffentlicht.',
        );

        app(ChangeRequestModerationService::class)->approve($moderator, $requestId, 'Bestätigt.');

        $request = DB::table('change_requests')->where('id', $requestId)->first();

        $this->assertSame('approved', $request->status);
        $this->assertNotNull($request->result_record_id);

        $this->assertDatabaseHas('place_price_offers', [
            'id' => $request->result_record_id,
            'place_id' => $placeId,
            'price_product_id' => $offer['price_product_id'],
            'max_age' => 11,
            'is_active' => true,
        ]);

        $periodId = DB::table('place_price_periods')
            ->where('place_price_offer_id', $request->result_record_id)
            ->where('is_active', true)
            ->value('id');

        $this->assertNotNull($periodId);

        $this->assertDatabaseHas('place_price_lines', [
            'place_price_period_id' => $periodId,
            'price_status' => 'unknown',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('xp_ledger', [
            'user_id' => $user->id,
            'place_id' => $placeId,
            'event_type' => 'place_info',
            'action_key' => 'price-offer:'.$request->result_record_id,
        ]);
    }

    public function test_seasonal_price_overrides_only_overlapping_part_of_same_offer(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::factory()->create();
        $moderator = User::factory()->create();
        $user = User::factory()->create();
        $this->assignRole($moderator, 'mod');
        $placeId = $this->createPlace($owner);

        $prices = app(PricePeriodService::class);
        $offerData = $this->offerData('motorhome-pitch');
        $eurId = DB::table('units')->where('unit_key', 'EUR')->value('id');
        $billingId = DB::table('price_billing_units')->where('slug', 'per-pitch-night')->value('id');

        $baseline = $prices->applyDirect(
            $placeId,
            $moderator->id,
            $offerData,
            $prices->normalizeRange(true, null, null),
            [[
                'price_status' => 'fixed',
                'amount' => 20,
                'currency_unit_id' => $eurId,
                'rate_quantity' => 1,
                'price_billing_unit_id' => $billingId,
            ]],
        );

        $offerId = $baseline['offer_id'];
        $requestId = $prices->createProposal(
            $placeId,
            $user->id,
            $offerData,
            $prices->normalizeRange(false, '01.07.', '31.08.'),
            [[
                'price_status' => 'fixed',
                'amount' => 30,
                'currency_unit_id' => $eurId,
                'rate_quantity' => 1,
                'price_billing_unit_id' => $billingId,
            ]],
            'Hauptsaison',
            $offerId,
        );

        app(ChangeRequestModerationService::class)->approve($moderator, $requestId, 'Bestätigt.');

        $active = DB::table('place_price_periods')
            ->where('place_price_offer_id', $offerId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->get();

        $this->assertCount(3, $active);

        $labels = $active->map(fn ($period) => $prices->rangeLabel([
            'is_year_round' => (bool) $period->is_year_round,
            'start_month' => $period->start_month,
            'start_day' => $period->start_day,
            'end_month' => $period->end_month,
            'end_day' => $period->end_day,
        ]))->sort()->values()->all();

        $this->assertContains('01.01.–30.06.', $labels);
        $this->assertContains('01.07.–31.08.', $labels);
        $this->assertContains('01.09.–31.12.', $labels);

        $summer = $active->first(fn ($period) =>
            (int) $period->start_month === 7
            && (int) $period->start_day === 1
            && (int) $period->end_month === 8
            && (int) $period->end_day === 31
        );

        $this->assertSame(
            30.0,
            (float) DB::table('place_price_lines')
                ->where('place_price_period_id', $summer->id)
                ->where('is_active', true)
                ->value('amount'),
        );
    }

    private function offerData(string $productSlug): array
    {
        return [
            'source_type' => 'product',
            'price_product_id' => (int) DB::table('price_products')->where('slug', $productSlug)->value('id'),
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
        ];
    }

    private function createPlace(User $user): int
    {
        $placeTypeId = DB::table('place_types')->where('is_active', true)->value('id');

        return DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Price Testplatz',
            'slug' => 'price-testplatz',
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assignRole(User $user, string $slug): void
    {
        $roleId = DB::table('roles')->where('slug', $slug)->value('id');

        DB::table('user_roles')->updateOrInsert(
            ['user_id' => $user->id, 'role_id' => $roleId],
            [
                'assigned_by' => null,
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
