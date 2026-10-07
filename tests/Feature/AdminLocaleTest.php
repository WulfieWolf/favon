<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_overview_and_user_management_are_available_in_german_and_english(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $target = User::factory()->create(['name' => 'Locale Target']);
        config(['favon.owner_email' => $owner->email]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'de'])
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Zentrale Verwaltung für Favon.')
            ->assertSee('Benutzer & Rechte');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Central administration for Favon.')
            ->assertSee('Users & permissions');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Registered accounts and their roles.')
            ->assertSee('Locale Target');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.users.show', $target))
            ->assertOk()
            ->assertSee('Individual permission overrides')
            ->assertSee('Assign role');
    }
}
