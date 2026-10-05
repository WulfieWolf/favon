<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminUserPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_list_shows_last_seen_and_profile_photo(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');

        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $target = User::factory()->create([
            'name' => 'Avatar User',
            'last_seen_at' => '2026-10-03 18:00:00',
        ]);

        Storage::disk('local')->put('profile/admin-user.png', 'image-bytes');

        $photoId = (int) DB::table('photos')->insertGetId([
            'user_id' => $target->id,
            'storage_path' => 'profile/admin-user.png',
            'mime_type' => 'image/png',
            'status' => 'approved',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $target->id)->update([
            'profile_photo_id' => $photoId,
        ]);

        $this->actingAs($owner)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Avatar User')
            ->assertSee('2026-10-03')
            ->assertSee(route('admin.users.photo', $target), false);
    }

    public function test_admin_user_details_show_full_profile_photo_and_photo_route_serves_existing_file(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');

        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $target = User::factory()->create(['name' => 'Detail Avatar User']);

        Storage::disk('local')->put('profile/admin-detail-user.png', 'image-bytes');

        $photoId = (int) DB::table('photos')->insertGetId([
            'user_id' => $target->id,
            'storage_path' => 'profile/admin-detail-user.png',
            'mime_type' => 'image/png',
            'status' => 'approved',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $target->id)->update([
            'profile_photo_id' => $photoId,
        ]);

        $this->actingAs($owner)
            ->get(route('admin.users.show', $target))
            ->assertOk()
            ->assertSee(route('admin.users.photo', $target), false);

        $this->actingAs($owner)
            ->get(route('admin.users.photo', $target))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }
    public function test_admin_user_list_supports_sorting_for_all_data_columns(): void
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

        $this->actingAs($owner)
            ->get(route('admin.users.index', ['sort' => 'last_seen', 'dir' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder(['Zulu User', 'Alpha User']);
    }

    public function test_admin_user_list_shows_alias_or_cw_id_as_display_name(): void
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

        $this->actingAs($owner)
            ->get(route('admin.users.index', ['sort' => 'display_name', 'dir' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder(['CW-10002', 'Wolfie']);
    }

}
