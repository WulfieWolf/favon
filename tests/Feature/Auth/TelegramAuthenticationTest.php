<?php

namespace Tests\Feature\Auth;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'telegram.client_id' => '8889001977',
            'telegram.client_secret' => 'test-secret',
            'telegram.authorization_url' => 'https://oauth.telegram.org/auth',
            'telegram.token_url' => 'https://oauth.telegram.org/token',
            'telegram.jwks_url' => 'https://oauth.telegram.org/.well-known/jwks.json',
            'telegram.issuer' => 'https://oauth.telegram.org',
        ]);

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_sees_gateway_and_private_directory_redirects_to_it(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Favon')
            ->assertSee(route('telegram.redirect'), false)
            ->assertSee(route('login'), false);

        $this->get(route('dashboard'))
            ->assertRedirect(route('home'));
    }

    public function test_telegram_redirect_uses_authorization_code_flow_with_pkce(): void
    {
        $response = $this->get(route('telegram.redirect'));

        $response->assertRedirect();

        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith('https://oauth.telegram.org/auth?', $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertSame('8889001977', $query['client_id'] ?? null);
        $this->assertSame(route('telegram.callback'), $query['redirect_uri'] ?? null);
        $this->assertSame('code', $query['response_type'] ?? null);
        $this->assertSame('openid', $query['scope'] ?? null);
        $this->assertSame('S256', $query['code_challenge_method'] ?? null);
        $this->assertNotEmpty($query['state'] ?? null);
        $this->assertNotEmpty($query['nonce'] ?? null);
        $this->assertNotEmpty($query['code_challenge'] ?? null);

        $this->assertSame($query['state'], session('telegram_oidc_state'));
        $this->assertSame($query['nonce'], session('telegram_oidc_nonce'));
        $this->assertNotEmpty(session('telegram_oidc_verifier'));
    }

    public function test_valid_telegram_oidc_login_creates_minimal_user_and_reuses_it(): void
    {
        [$privateKey, $jwk] = $this->rsaSigningKey();

        $this->get(route('telegram.redirect'))->assertRedirect();
        $state = (string) session('telegram_oidc_state');
        $nonce = (string) session('telegram_oidc_nonce');

        Http::fake([
            'https://oauth.telegram.org/token' => Http::response([
                'id_token' => $this->idToken('987654321', $nonce, $privateKey),
            ]),
            'https://oauth.telegram.org/.well-known/jwks.json' => Http::response([
                'keys' => [$jwk],
            ]),
        ]);

        $this->get(route('telegram.callback', ['code' => 'first-code', 'state' => $state]))
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

        $this->get(route('telegram.redirect'))->assertRedirect();
        $state = (string) session('telegram_oidc_state');
        $nonce = (string) session('telegram_oidc_nonce');

        Http::fake([
            'https://oauth.telegram.org/token' => Http::response([
                'id_token' => $this->idToken('987654321', $nonce, $privateKey),
            ]),
            'https://oauth.telegram.org/.well-known/jwks.json' => Http::response([
                'keys' => [$jwk],
            ]),
        ]);

        $this->get(route('telegram.callback', ['code' => 'second-code', 'state' => $state]))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('community_accounts', 1);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_invalid_state_or_expired_id_token_is_rejected(): void
    {
        $this->get(route('telegram.redirect'))->assertRedirect();

        $this->get(route('telegram.callback', ['code' => 'code', 'state' => 'wrong-state']))
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('telegram');

        $this->assertGuest();
        $this->assertDatabaseCount('community_accounts', 0);

        [$privateKey, $jwk] = $this->rsaSigningKey();

        $this->get(route('telegram.redirect'))->assertRedirect();
        $state = (string) session('telegram_oidc_state');
        $nonce = (string) session('telegram_oidc_nonce');

        Http::fake([
            'https://oauth.telegram.org/token' => Http::response([
                'id_token' => $this->idToken('333333333', $nonce, $privateKey, now()->subHours(2)->timestamp),
            ]),
            'https://oauth.telegram.org/.well-known/jwks.json' => Http::response([
                'keys' => [$jwk],
            ]),
        ]);

        $this->get(route('telegram.callback', ['code' => 'expired-code', 'state' => $state]))
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('telegram');

        $this->assertGuest();
        $this->assertDatabaseCount('community_accounts', 0);
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function rsaSigningKey(): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $this->assertNotFalse($resource);

        $privateKey = '';
        $this->assertTrue(openssl_pkey_export($resource, $privateKey));

        $details = openssl_pkey_get_details($resource);
        $this->assertIsArray($details);

        return [
            $privateKey,
            [
                'kid' => 'test-key',
                'kty' => 'RSA',
                'alg' => 'RS256',
                'use' => 'sig',
                'n' => $this->base64UrlEncode($details['rsa']['n']),
                'e' => $this->base64UrlEncode($details['rsa']['e']),
            ],
        ];
    }

    private function idToken(
        string $telegramId,
        string $nonce,
        string $privateKey,
        ?int $issuedAt = null,
    ): string {
        $issuedAt ??= now()->timestamp;

        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'kid' => 'test-key',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));

        $payload = $this->base64UrlEncode(json_encode([
            'iss' => 'https://oauth.telegram.org',
            'aud' => '8889001977',
            'sub' => $telegramId,
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
            'nonce' => $nonce,
        ], JSON_THROW_ON_ERROR));

        $signingInput = $header.'.'.$payload;
        $signature = '';

        $this->assertTrue(openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256));

        return $signingInput.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
