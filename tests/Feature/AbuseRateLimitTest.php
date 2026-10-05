<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AbuseRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_support_submission_is_rate_limited(): void
    {
        $this->seed(DatabaseSeeder::class);

        for ($i = 1; $i <= 2; $i++) {
            $this->post(route('support.store'), [
                'type' => 'other',
                'description' => 'Legitime Testmeldung Nummer '.$i,
                'guest_name' => 'Rate Limit Test',
                'guest_email' => 'ratelimit@example.test',
                'website' => '',
            ])->assertRedirect(route('support.thanks'));
        }

        $this->post(route('support.store'), [
            'type' => 'other',
            'description' => 'Diese Meldung muss gedrosselt werden.',
            'guest_name' => 'Rate Limit Test',
            'guest_email' => 'ratelimit@example.test',
            'website' => '',
        ])->assertStatus(429);
    }

    public function test_new_account_is_stopped_after_two_place_creations_per_minute(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $placeTypeId = (int) DB::table('place_types')->where('is_active', true)->value('id');

        for ($i = 1; $i <= 2; $i++) {
            $this->actingAs($user)
                ->post(route('places.suggest.store'), [
                    'name' => 'Rate Limit Platz '.$i,
                    'place_type_id' => $placeTypeId,
                    'latitude' => 51.45 + ($i / 10000),
                    'longitude' => 7.01 + ($i / 10000),
                    'intent' => 'draft',
                ])
                ->assertRedirect();
        }

        $this->actingAs($user)
            ->post(route('places.suggest.store'), [
                'name' => 'Rate Limit Platz 3',
                'place_type_id' => $placeTypeId,
                'latitude' => 51.46,
                'longitude' => 7.02,
                'intent' => 'draft',
            ])
            ->assertStatus(429);
    }

    public function test_admin_is_not_limited_by_community_place_creation_limits(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $this->assignRole($admin, 'admin');
        $placeTypeId = (int) DB::table('place_types')->where('is_active', true)->value('id');

        for ($i = 1; $i <= 6; $i++) {
            $this->actingAs($admin)
                ->post(route('places.suggest.store'), [
                    'name' => 'Admin Rate Limit Platz '.$i,
                    'place_type_id' => $placeTypeId,
                    'latitude' => 51.45 + ($i / 10000),
                    'longitude' => 7.01 + ($i / 10000),
                    'intent' => 'submit',
                ])
                ->assertRedirect();
        }

        $this->assertSame(
            6,
            DB::table('places')->where('name', 'like', 'Admin Rate Limit Platz %')->count(),
        );
    }

    public function test_public_read_rate_limit_is_disabled_by_default(): void
    {
        $this->assertFalse((bool) config('camperwolf.security.public_read_limit_enabled'));
    }

    private function assignRole(User $user, string $roleSlug): void
    {
        $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');

        DB::table('user_roles')->updateOrInsert(
            ['user_id' => $user->id, 'role_id' => $roleId],
            [
                'assigned_by' => null,
                'assigned_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
}
