<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PlaceTombstoneService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaceTombstoneServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_type_is_blocked_only_inside_hard_radius_and_other_type_is_warning(): void
    {
        config([
            'place_tombstones.block_radius_m' => 20,
            'place_tombstones.warning_radius_m' => 100,
        ]);

        $campground = $this->placeType('tombstone-campground');
        $tent = $this->placeType('tombstone-tent');

        $this->tombstone($campground, 51.4500000, 7.0100000, 'operator_request');

        $service = app(PlaceTombstoneService::class);

        $sameClose = $service->check(51.4500500, 7.0100000, $campground);
        $this->assertSame(PlaceTombstoneService::BLOCKED_SAME_TYPE, $sameClose['status']);
        $this->assertLessThanOrEqual(20, $sameClose['match']['distance_m']);

        $sameFurther = $service->check(51.4504000, 7.0100000, $campground);
        $this->assertSame(PlaceTombstoneService::WARNING_SAME_TYPE, $sameFurther['status']);

        $otherType = $service->check(51.4500500, 7.0100000, $tent);
        $this->assertSame(PlaceTombstoneService::WARNING_OTHER_TYPE, $otherType['status']);

        $far = $service->check(51.4520000, 7.0100000, $campground);
        $this->assertSame(PlaceTombstoneService::NONE, $far['status']);
    }

    public function test_regular_user_cannot_submit_same_type_on_tombstone_position(): void
    {
        config([
            'place_tombstones.block_radius_m' => 20,
            'place_tombstones.warning_radius_m' => 100,
        ]);

        $typeId = $this->placeType('blocked-user-test');
        $this->tombstone($typeId, 51.4500000, 7.0100000, 'manual_block');

        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $response = $this->actingAs($user)->post(route('places.suggest.store'), [
            'name' => 'Attempted recreation',
            'place_type_id' => $typeId,
            'latitude' => 51.4500100,
            'longitude' => 7.0100000,
            'country_code' => 'DE',
            'intent' => 'submit',
        ]);

        $response->assertSessionHasErrors('suggestion');
        $this->assertDatabaseMissing('places', [
            'name' => 'Attempted recreation',
        ]);
    }

    private function placeType(string $slug): int
    {
        return (int) DB::table('place_types')->insertGetId([
            'slug' => $slug,
            'icon_id' => null,
            'sort_order' => 10,
            'is_active' => true,
            'is_searchable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function tombstone(int $typeId, float $lat, float $lon, string $reason): int
    {
        return (int) DB::table('places')->insertGetId([
            'place_type_id' => $typeId,
            'name' => null,
            'slug' => null,
            'latitude' => $lat,
            'longitude' => $lon,
            'publication_status' => 'deleted',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => false,
            'deleted_at' => now(),
            'deletion_reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
