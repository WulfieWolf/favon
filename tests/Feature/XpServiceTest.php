<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class XpServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_place_information_is_rewarded_only_once_per_user_place_and_field(): void
    {
        $user = User::factory()->create();
        $placeId = $this->createPlace($user->id);
        $service = app(XpService::class);

        $this->assertTrue($service->awardPlaceField(
            $user->id,
            $placeId,
            'place_details.pitch_count',
            1,
            'Anzahl der Stellplätze angegeben',
        ));

        $this->assertFalse($service->awardPlaceField(
            $user->id,
            $placeId,
            'place_details.pitch_count',
            1,
            'Anzahl der Stellplätze später aktualisiert',
        ));

        $this->assertSame(1, $service->totalForUser($user->id));
        $this->assertDatabaseCount('xp_ledger', 1);
    }

    public function test_another_user_can_earn_xp_for_the_same_place_information(): void
    {
        $creator = User::factory()->create();
        $other = User::factory()->create();
        $placeId = $this->createPlace($creator->id);
        $service = app(XpService::class);

        $service->awardPlaceField($creator->id, $placeId, 'feature:10', 1, 'Merkmal ergänzt');
        $service->awardPlaceField($other->id, $placeId, 'feature:10', 1, 'Merkmal aktualisiert');

        $this->assertSame(1, $service->totalForUser($creator->id));
        $this->assertSame(1, $service->totalForUser($other->id));
        $this->assertDatabaseCount('xp_ledger', 2);
    }

    public function test_photo_xp_is_capped_at_five_per_user_and_place(): void
    {
        $user = User::factory()->create();
        $placeId = $this->createPlace($user->id);
        $service = app(XpService::class);

        for ($photoId = 1; $photoId <= 7; $photoId++) {
            $service->awardPhoto($user->id, $placeId, $photoId, 'Testplatz');
        }

        $this->assertSame(5, $service->totalForUser($user->id));
        $this->assertSame(5, DB::table('xp_ledger')->where('event_type', 'photo_upload')->count());
    }

    public function test_detailed_review_can_receive_the_one_time_upgrade_bonus(): void
    {
        $user = User::factory()->create();
        $placeId = $this->createPlace($user->id);
        $service = app(XpService::class);

        $service->awardReview($user->id, $placeId, 1, 'Testplatz', false);
        $service->awardReview($user->id, $placeId, 1, 'Testplatz', true);
        $service->awardReview($user->id, $placeId, 1, 'Testplatz', true);

        $this->assertSame(4, $service->totalForUser($user->id));
        $this->assertDatabaseCount('xp_ledger', 2);
    }

    public function test_manual_awards_and_corrections_remain_separate_ledger_entries(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $service = app(XpService::class);

        $service->awardManual($user, 50, 'Besondere Community-Hilfe', $admin);
        $service->awardManual($user, -10, 'Korrektur einer Sondervergabe', $admin);

        $this->assertSame(40, $service->totalForUser($user->id));
        $this->assertDatabaseCount('xp_ledger', 2);
    }

    private function createPlace(int $userId): int
    {
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

        return DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Testplatz',
            'slug' => 'testplatz-'.uniqid(),
            'latitude' => 51.0,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
