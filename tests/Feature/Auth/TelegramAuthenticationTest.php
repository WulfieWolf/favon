<?php

namespace Tests\Feature\Auth;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TelegramAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'telegram.bot_token' => '123456:TEST_TOKEN',
            'telegram.bot_username' => 'favonde_bot',
            'telegram.auth_max_age_seconds' => 300,
        ]);

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_sees_gateway_and_private_directory_redirects_to_it(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Favon')
            ->assertSee('favonde_bot', false)
            ->assertSee(route('login'), false);

        $this->get(route('dashboard'))
            ->assertRedirect(route('home'));
    }

    public function test_valid_telegram_login_creates_minimal_user_and_reuses_it(): void
    {
        $payload = $this->signedPayload('987654321');

        $this->post(route('telegram.callback'), $payload)
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $user = auth()->user();
        $this->assertNotNull($user);
        $this->assertSame('User-0000001', $user->name);
        $this->assertNull($user->email);
        $this->assertNull($user->password);

        $this->assertDatabaseHas('community_accounts', [
            'id' => 1,
            'user_id' => $user->id,
            'telegram_user_id' => '987654321',
        ]);

        $this->assertDatabaseHas('user_roles', [
            'user_id' => $user->id,
            'role_id' => DB::table('roles')->where('slug', 'user')->value('id'),
        ]);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();

        $this->post(route('telegram.callback'), $this->signedPayload('987654321'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('community_accounts', 1);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_tampered_or_stale_telegram_payload_is_rejected(): void
    {
        $tampered = $this->signedPayload('111111111');
        $tampered['id'] = '222222222';

        $this->post(route('telegram.callback'), $tampered)
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('telegram');

        $this->assertGuest();
        $this->assertDatabaseCount('community_accounts', 0);

        $stale = $this->signedPayload('333333333', now()->subMinutes(10)->timestamp);

        $this->post(route('telegram.callback'), $stale)
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('telegram');

        $this->assertGuest();
        $this->assertDatabaseCount('community_accounts', 0);
    }

    /**
     * @return array<string, string|int>
     */
    private function signedPayload(string $telegramId, ?int $authDate = null): array
    {
        $payload = [
            'id' => $telegramId,
            'first_name' => 'Ignored',
            'username' => 'ignored_username',
            'auth_date' => $authDate ?? now()->timestamp,
        ];

        ksort($payload);
        $check = collect($payload)
            ->map(fn ($value, $key) => $key.'='.$value)
            ->implode("\n");

        $secret = hash('sha256', (string) config('telegram.bot_token'), true);
        $payload['hash'] = hash_hmac('sha256', $check, $secret);

        return $payload;
    }
}
