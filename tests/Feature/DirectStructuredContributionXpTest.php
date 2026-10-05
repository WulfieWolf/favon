<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BadgeService;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DirectStructuredContributionXpTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_opening_hours_reward_uses_standard_dedupe_key(): void
    {
        [$userId, $placeId] = $this->createUserAndPlace();
        $placeName = 'Testplatz';

        $request = (object) [
            'id' => 0,
            'submitted_by' => $userId,
            'place_id' => $placeId,
            'target_table' => 'opening_hours',
            'target_field' => 'period_schedule',
            'proposed_value' => json_encode([
                'schedule' => [
                    '1' => ['mode' => '24h'],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        $xp = app(XpService::class);
        $xp->awardApprovedPlaceChange($request, 99, $placeName);
        $xp->awardApprovedPlaceChange($request, 100, $placeName);

        $this->assertDatabaseCount('xp_ledger', 1);
        $this->assertDatabaseHas('xp_ledger', [
            'user_id' => $userId,
            'place_id' => $placeId,
            'action_key' => 'opening_hours.period_schedule',
            'xp' => 1,
        ]);
    }

    public function test_direct_opening_hours_with_only_unknown_values_awards_no_xp(): void
    {
        [$userId, $placeId] = $this->createUserAndPlace();

        $request = (object) [
            'id' => 0,
            'submitted_by' => $userId,
            'place_id' => $placeId,
            'target_table' => 'opening_hours',
            'target_field' => 'period_schedule',
            'proposed_value' => json_encode([
                'schedule' => [
                    '1' => ['mode' => 'unknown'],
                    '2' => ['mode' => 'unknown'],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        app(XpService::class)->awardApprovedPlaceChange($request, 99, 'Testplatz');

        $this->assertDatabaseCount('xp_ledger', 0);
    }

    public function test_direct_price_reward_uses_offer_specific_dedupe_key(): void
    {
        [$userId, $placeId] = $this->createUserAndPlace();

        $request = (object) [
            'id' => 0,
            'submitted_by' => $userId,
            'place_id' => $placeId,
            'target_table' => 'place_price_offers',
            'target_field' => 'period_pricing',
            'proposed_value' => json_encode(['offer' => []]),
        ];

        $xp = app(XpService::class);
        $xp->awardApprovedPlaceChange($request, 501, 'Testplatz');
        $xp->awardApprovedPlaceChange($request, 501, 'Testplatz');

        $this->assertDatabaseCount('xp_ledger', 1);
        $this->assertDatabaseHas('xp_ledger', [
            'user_id' => $userId,
            'place_id' => $placeId,
            'action_key' => 'price-offer:501',
            'xp' => 1,
        ]);
    }

    private function createUserAndPlace(): array
    {
        $user = User::factory()->create();

        $placeTypeId = DB::table('place_types')->insertGetId([
            'slug' => 'test-'.uniqid(),
            'icon_id' => null,
            'sort_order' => 10,
            'is_active' => true,
            'is_searchable' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeId = (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Testplatz',
            'slug' => 'testplatz-'.uniqid(),
            'latitude' => 51.0,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [(int) $user->id, $placeId];
    }
}
