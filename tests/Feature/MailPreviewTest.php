<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->detectEnvironment(fn () => 'local');
        require base_path('routes/web.php');
    }

    public function test_local_preview_requires_authentication(): void
    {
        config()->set('favon.mail.debug_preview', true);

        $response = $this->get('/dev/mail/verify-email');

        $response->assertRedirect();
    }

    public function test_local_preview_renders_without_sending_mail(): void
    {
        config()->set('favon.mail.debug_preview', true);

        $user = User::factory()->unverified()->create([
            'name' => 'Sascha Schwarz',
            'email' => 'sascha@example.test',
            'locale' => 'de',
        ]);

        $response = $this->actingAs($user)->get('/dev/mail/verify-email?locale=en&reason=registration');

        $response->assertOk()
            ->assertSee('Mail preview')
            ->assertSee('Recipient')
            ->assertSee('Reason')
            ->assertSee('Registration')
            ->assertSee('Verify your email address - Camperwolf.de')
            ->assertSee('sascha@example.test');
    }

    public function test_preview_controller_returns_not_found_when_preview_flag_is_disabled(): void
    {
        config()->set('favon.mail.debug_preview', false);

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/dev/mail/verify-email')
            ->assertNotFound();
    }
}
