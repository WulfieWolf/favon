<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_grace_period_hides_profile_and_can_be_cancelled(): void
    {
        $user = User::factory()->create();
        DB::table('user_profiles')->insert([
            'user_id' => $user->id,
            'public_handle' => 'CW-DELETE-TEST',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(AccountDeletionService::class);
        $service->request($user, false);

        $user->refresh();
        $this->assertSame('pending_deletion', $user->account_status);
        $this->assertNotNull($user->deletion_scheduled_for);
        $this->get(route('users.profile', 'CW-DELETE-TEST'))->assertNotFound();

        $this->assertTrue($service->cancel($user));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_status' => 'active',
            'deletion_scheduled_for' => null,
        ]);
    }

    public function test_finalization_scrubs_identity_and_keeps_created_place(): void
    {
        $user = User::factory()->create([
            'name' => 'Example Person',
            'email' => 'example-person@example.test',
        ]);

        DB::table('user_profiles')->insert([
            'user_id' => $user->id,
            'public_handle' => 'CW-OLD-ID',
            'bio' => 'Private profile text',
            'birth_date' => '1980-01-01',
            'gender' => 'custom',
            'vehicle_type' => 'campervan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeTypeId = DB::table('place_types')->insertGetId([
            'slug' => 'deletion-test',
            'icon_id' => null,
            'sort_order' => 10,
            'is_active' => true,
            'is_searchable' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeId = DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Persistent Place',
            'slug' => 'persistent-place',
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

        app(AccountDeletionService::class)->finalize($user->id);

        $row = DB::table('users')->where('id', $user->id)->first();
        $this->assertSame('deleted', $row->account_status);
        $this->assertSame('Deleted User', $row->name);
        $this->assertNotSame('example-person@example.test', $row->email);

        $profile = DB::table('user_profiles')->where('user_id', $user->id)->first();
        $this->assertNotSame('CW-OLD-ID', $profile->public_handle);
        $this->assertNull($profile->bio);
        $this->assertNull($profile->birth_date);
        $this->assertNull($profile->gender);
        $this->assertNull($profile->vehicle_type);

        $this->assertDatabaseHas('places', [
            'id' => $placeId,
            'created_by' => $user->id,
        ]);
    }
}
