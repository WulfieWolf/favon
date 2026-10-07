<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_gateway_sends_security_headers_and_is_not_indexable(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=()')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function test_private_browse_rejects_excessively_long_search_terms(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard', ['q' => str_repeat('x', 121)]))
            ->assertStatus(422);

        $this->assertDatabaseHas('security_events', [
            'event_type' => 'browse_query_rejected',
        ]);
    }

    public function test_private_browse_rejects_excessive_query_complexity(): void
    {
        $query = [];

        for ($i = 0; $i < 121; $i++) {
            $query['features']['feature-'.$i] = 'yes';
        }

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard', $query))
            ->assertStatus(414);
    }

    public function test_admin_login_surface_is_marked_noindex(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function test_public_support_entry_forms_are_marked_noindex(): void
    {
        $this->get(route('support.report'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

        $this->get(route('support.privacy-legal'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function test_robots_disallows_authenticated_directory_content(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Disallow: /dashboard', $robots);
        $this->assertStringContainsString('Disallow: /places/', $robots);
        $this->assertStringNotContainsString('Sitemap:', $robots);
    }

    public function test_filtered_dashboard_requests_are_rate_limited_but_plain_dashboard_is_not(): void
    {
        config(['favon.security.filtered_browse_per_minute' => 2]);

        RateLimiter::clear('filtered-browse:127.0.0.1');
        $this->actingAs(User::factory()->create());

        $this->get(route('dashboard', ['q' => 'Essen']))->assertOk();
        $this->get(route('dashboard', ['q' => 'Bochum']))->assertOk();
        $this->get(route('dashboard', ['q' => 'Dortmund']))->assertStatus(429);

        $this->assertDatabaseHas('security_events', [
            'event_type' => 'rate_limited',
        ]);

        RateLimiter::clear('filtered-browse:127.0.0.1');

        for ($i = 0; $i < 3; $i++) {
            $this->get(route('dashboard'))->assertOk();
        }
    }

    public function test_password_reset_mail_requests_are_limited_per_ip(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->post('/forgot-password', [
                'email' => 'reset-'.$i.'@example.test',
            ])->assertStatus(302);
        }

        $this->post('/forgot-password', [
            'email' => 'reset-21@example.test',
        ])->assertStatus(429);

        $this->assertDatabaseHas('security_events', [
            'event_type' => 'rate_limited',
        ]);
    }
}
