<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AbuseProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_likely_duplicate_place_is_quarantined_out_of_normal_moderation_queue(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $this->assignRole($admin, 'admin');
        $user = User::factory()->create(['email_verified_at' => now()]);
        $placeTypeId = (int) DB::table('place_types')->where('is_active', true)->value('id');

        $this->actingAs($admin)->post(route('places.suggest.store'), [
            'name' => 'Camping am Testsee',
            'place_type_id' => $placeTypeId,
            'latitude' => 51.455600,
            'longitude' => 7.011600,
            'intent' => 'submit',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('places.suggest.store'), [
            'name' => 'Camping am Testsee',
            'place_type_id' => $placeTypeId,
            'latitude' => 51.455650,
            'longitude' => 7.011650,
            'intent' => 'submit',
        ])->assertRedirect();

        $pendingPlace = DB::table('places')
            ->where('created_by', $user->id)
            ->where('publication_status', 'pending')
            ->first();

        $this->assertNotNull($pendingPlace);
        $this->assertDatabaseHas('abuse_flags', [
            'entity_type' => 'place',
            'entity_id' => $pendingPlace->id,
            'user_id' => $user->id,
            'rule_code' => 'strong_nearby_duplicate',
            'severity' => 'high',
            'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.change-requests.index', ['status' => 'pending', 'queue' => 'normal']))
            ->assertOk()
            ->assertViewHas('requests', function ($requests) use ($pendingPlace): bool {
                return ! $requests->getCollection()->contains(
                    fn ($item) => (int) $item->place_id === (int) $pendingPlace->id,
                );
            });

        $this->actingAs($admin)
            ->get(route('admin.change-requests.index', ['status' => 'pending', 'queue' => 'quarantine']))
            ->assertOk()
            ->assertViewHas('requests', function ($requests) use ($pendingPlace): bool {
                return $requests->getCollection()->contains(
                    fn ($item) => (int) $item->place_id === (int) $pendingPlace->id,
                );
            })
            ->assertSee('Quarantine');
    }

    public function test_normal_distinct_place_stays_in_normal_queue(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $this->assignRole($admin, 'admin');
        $user = User::factory()->create(['email_verified_at' => now()]);
        $placeTypeId = (int) DB::table('place_types')->where('is_active', true)->value('id');

        $this->actingAs($user)->post(route('places.suggest.store'), [
            'name' => 'Einzigartiger Testplatz',
            'place_type_id' => $placeTypeId,
            'latitude' => 50.100000,
            'longitude' => 8.600000,
            'intent' => 'submit',
        ])->assertRedirect();

        $placeId = (int) DB::table('places')
            ->where('name', 'Einzigartiger Testplatz')
            ->value('id');

        $this->assertDatabaseMissing('abuse_flags', [
            'entity_type' => 'place',
            'entity_id' => $placeId,
            'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.change-requests.index', ['status' => 'pending', 'queue' => 'normal']))
            ->assertOk()
            ->assertSee('Einzigartiger Testplatz');
    }

    public function test_admin_direct_publication_is_never_quarantined(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $this->assignRole($admin, 'admin');
        $placeTypeId = (int) DB::table('place_types')->where('is_active', true)->value('id');

        foreach ([1, 2] as $index) {
            $this->actingAs($admin)->post(route('places.suggest.store'), [
                'name' => 'Admin Duplicate Test',
                'place_type_id' => $placeTypeId,
                'latitude' => 51.500000 + ($index / 100000),
                'longitude' => 7.100000 + ($index / 100000),
                'intent' => 'submit',
            ])->assertRedirect();
        }

        $this->assertSame(0, DB::table('abuse_flags')->count());
        $this->assertSame(
            2,
            DB::table('places')->where('name', 'Admin Duplicate Test')->where('publication_status', 'published')->count(),
        );
    }


    public function test_identical_place_replay_does_not_create_second_pending_place(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $placeTypeId = (int) DB::table('place_types')->where('is_active', true)->value('id');

        $payload = [
            'name' => 'Replay Testplatz',
            'place_type_id' => $placeTypeId,
            'latitude' => 51.400000,
            'longitude' => 7.200000,
            'intent' => 'submit',
        ];

        $this->actingAs($user)
            ->post(route('places.suggest.store'), $payload)
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('places.suggest.store'), $payload)
            ->assertRedirect(route('places.suggest.create'))
            ->assertSessionHas('ui_toast', __('places.suggest.duplicate_replay'));

        $this->assertSame(
            1,
            DB::table('places')
                ->where('created_by', $user->id)
                ->where('name', 'Replay Testplatz')
                ->count(),
        );
    }

    private function assignRole(User $user, string $roleSlug): void
    {
        $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');

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
