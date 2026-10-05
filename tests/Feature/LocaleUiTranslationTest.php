<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PageTitle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class LocaleUiTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_titles_do_not_convert_translation_groups_to_strings(): void
    {
        $this->assertSame('ui', PageTitle::resolve('ui'));
    }

    public function test_authentication_screen_is_available_in_german_and_english(): void
    {
        $this->withSession(['locale' => 'de'])
            ->get(route('login'))
            ->assertOk()
            ->assertSee('Bei deinem Konto anmelden')
            ->assertSee('Angemeldet bleiben');

        $this->withSession(['locale' => 'en'])
            ->get(route('login'))
            ->assertOk()
            ->assertSee('Log in to your account')
            ->assertSee('Remember me');
    }

    public function test_notification_settings_are_available_in_german_and_english(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['locale' => 'de'])
            ->get(route('notifications.settings'))
            ->assertOk()
            ->assertSee('Benachrichtigungen');

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('notifications.settings'))
            ->assertOk()
            ->assertSee('Notifications');
    }

    public function test_place_browse_is_available_in_german_and_english(): void
    {
        $this->withSession(['locale' => 'de'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Keine Plätze gefunden');

        $this->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No places found');
    }

    public function test_favorites_page_is_available_in_german_and_english(): void
    {
        $favorites = new LengthAwarePaginator([], 0, 24);

        app()->setLocale('de');
        $this->view('favorites.index', compact('favorites'))
            ->assertSee('Noch keine Favoriten')
            ->assertSee('Plätze durchsuchen');

        app()->setLocale('en');
        $this->view('favorites.index', compact('favorites'))
            ->assertSee('No favorites yet')
            ->assertSee('Browse places');
    }
}
