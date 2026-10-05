<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_visit_the_dashboard(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_contains_browser_only_location_control(): void
    {
        $this->withSession(['locale' => 'de'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('map-location-button', false)
            ->assertSee('map-nearby-button', false)
            ->assertSee('Kartenoptionen')
            ->assertSee('Mein Standort')
            ->assertSee('Plätze in der Umgebung')
            ->assertSee('navigator.geolocation.getCurrentPosition', false)
            ->assertSee("url.searchParams.set('nearby_candidates', '1');", false);
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }
}
