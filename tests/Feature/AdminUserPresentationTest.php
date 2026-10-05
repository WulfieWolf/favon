<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminUserPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_list_supports_sorting_for_all_current_data_columns(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::factory()->create([
            'name' => 'Middle Owner',
            'email_verified_at' => now(),
        ]);
        config(['camperwolf.owner_email' => $owner->email]);

        User::factory()->create([
            'name' => 'Alpha User',
            'email' => 'alpha@example.test',
            'account_status' => 'active',
            'last_seen_at' => '2026-10-01 10:00:00',
            'created_at' => '2026-09-01 10:00:00',
        ]);
        User::factory()->create([
            'name' => 'Zulu User',
            'email' => 'zulu@example.test',
            'account_status' => 'suspended',
            'last_seen_at' => '2026-10-03 10:00:00',
            'created_at' => '2026-10-01 10:00:00',
        ]);

        foreach (['name', 'display_name', 'email', 'status', 'role', 'last_seen', 'created'] as $sort) {
            $this->actingAs($owner)
                ->get(route('admin.users.index', ['sort' => $sort, 'dir' => 'desc']))
                ->assertOk();
        }

        $this->actingAs($owner)
            ->get(route('admin.users.index', ['sort' => 'name', 'dir' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder(['Zulu User', 'Alpha User']);
    }

    public function test_admin_user_list_shows_transitional_alias_or_internal_handle(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $withAlias = User::factory()->create(['name' => 'Alias Account']);
        $withoutAlias = User::factory()->create(['name' => 'Handle Account']);

        DB::table('user_profiles')->insert([
            [
                'user_id' => $withAlias->id,
                'public_handle' => 'CW-10001',
                'public_alias' => 'Wolfie',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $withoutAlias->id,
                'public_handle' => 'CW-10002',
                'public_alias' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($owner)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Wolfie')
            ->assertSee('CW-10002');
    }
}
