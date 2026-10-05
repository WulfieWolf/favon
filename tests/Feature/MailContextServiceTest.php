<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Services\MailContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailContextServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_the_saved_user_locale_and_real_name_before_an_alias_exists(): void
    {
        $user = User::factory()->create([
            'name' => 'Sascha Schwarz',
            'email' => 'sascha@example.test',
            'locale' => 'de',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'public_handle' => 'CW-12378',
        ]);

        $context = app(MailContextService::class)->forUser($user->fresh());

        $this->assertSame('de', $context['locale']);
        $this->assertSame('Sascha Schwarz', $context['greeting_name']);
        $this->assertSame('CW-12378', $context['cw_id']);
        $this->assertSame('sascha@example.test', $context['email']);
    }

    public function test_it_uses_an_empty_greeting_name_when_no_usable_name_exists(): void
    {
        $user = User::factory()->create([
            'name' => '   ',
            'locale' => 'de',
        ]);

        $context = app(MailContextService::class)->forUser($user->fresh());

        $this->assertSame('', $context['greeting_name']);
    }

    public function test_it_prefers_a_user_selected_public_alias_for_the_greeting(): void
    {
        $user = User::factory()->create([
            'name' => 'Sascha Schwarz',
            'locale' => 'en',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'public_handle' => 'CW-12378',
            'public_alias' => 'Wulfie',
        ]);

        $context = app(MailContextService::class)->forUser($user->fresh());

        $this->assertSame('en', $context['locale']);
        $this->assertSame('Wulfie', $context['greeting_name']);
    }
}
