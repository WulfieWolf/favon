<?php

namespace Tests\Feature;

use Database\Seeders\DevReleaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DevLogTest extends TestCase
{
    use RefreshDatabase;


    public function test_dev_release_seeder_keeps_pre_alpha_history_private(): void
    {
        $this->seed(DevReleaseSeeder::class);

        $this->assertSame(
            0,
            DB::table('dev_releases')
                ->where('stage', 'pre-alpha')
                ->where('is_public', true)
                ->count(),
        );
    }

    public function test_devlog_shows_public_beta_release_and_hides_internal_pre_alpha_history(): void
    {
        $this->withSession(['locale' => 'de'])
            ->get(route('devlog'))
            ->assertOk()
            ->assertSee('Was ist neu?')
            ->assertSee('v1 Beta')
            ->assertSee('Plätze suchen')
            ->assertSee('Suche nach Orten und Platznamen.')
            ->assertSee('Nutze deinen Standort, um deine gefilterten Plätze in deiner Nähe zu finden.')
            ->assertDontSee('Pre-Alpha');

        $this->withSession(['locale' => 'en'])
            ->get(route('devlog'))
            ->assertOk()
            ->assertSee('What&#039;s new?', false)
            ->assertSee('v1 Beta')
            ->assertSee('Finding places')
            ->assertSee('Search for places by location or name.')
            ->assertSee('Use your location to find your filtered places nearby.')
            ->assertDontSee('Pre-Alpha');
    }
}
