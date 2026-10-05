<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_visitors_use_supported_browser_language(): void
    {
        $this->withHeader('Accept-Language', 'de-DE,de;q=0.9,en;q=0.8')
            ->get('/')
            ->assertOk();

        $this->assertSame('de', app()->getLocale());

        $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9,en;q=0.8')
            ->get('/')
            ->assertOk();

        $this->assertSame('en', app()->getLocale());
    }

    public function test_missing_browser_language_falls_back_to_english(): void
    {
        $this->get('/')->assertOk();

        $this->assertSame('en', app()->getLocale());
    }

    public function test_manual_selection_is_stored_in_session_and_cookie(): void
    {
        $response = $this->from('/')->post(route('locale.update'), ['locale' => 'de']);

        $response->assertRedirect('/');
        $response->assertSessionHas('locale', 'de');
        $response->assertCookie('locale', 'de');
    }

    public function test_authenticated_manual_selection_is_also_stored_on_the_user(): void
    {
        $user = User::factory()->create(['locale' => 'de']);

        $this->actingAs($user)
            ->from('/')
            ->post(route('locale.update'), ['locale' => 'en'])
            ->assertRedirect('/');

        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_explicit_session_selection_wins_over_browser_language(): void
    {
        $this->withSession(['locale' => 'de'])
            ->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->get('/')
            ->assertOk();

        $this->assertSame('de', app()->getLocale());
    }

    public function test_an_additional_enabled_locale_is_accepted_without_controller_changes(): void
    {
        config()->set('locales.available.fr', [
            'enabled' => true,
            'native_name' => 'Français',
            'flag' => '🇫🇷',
        ]);

        $response = $this->from('/')->post(route('locale.update'), ['locale' => 'fr']);

        $response->assertRedirect('/');
        $response->assertSessionHas('locale', 'fr');
        $response->assertCookie('locale', 'fr');
    }
}
