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

        $currentNonce = $nonce;

        Http::fake([
            'https://oauth.telegram.org/token' => function () use (&$currentNonce, $privateKey) {
                return Http::response([
                    'id_token' => $this->idToken('987654321', $currentNonce, $privateKey),
                ]);
            },
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

        $currentNonce = $nonce;

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
        $privateKey = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCr0zZYfdxqb0n3
tk7/+FjP0svEGHHyGDtD4/gsIRvGynHfiPwaE0mlG0I/rhcOggeDClXIZGeCFEQu
fLCyOacKDBTNstlkjdjhiGRWsPPkKFPgfiqMzJq8+KANXUDAAJfjpdFf5KPzzZKP
VcNpZjMaw8XY3tOfDoEYpqkE0SjkH4i9ppSexMTUOYHw5mzZEc+pwyp5ppDhQvWU
zO5O6djAmHrQF/Xx14rZpxkkXQav+07klQlIHjZpeFrtCnoADCl8Q8/Ar7W+z9P9
EyIIpbmVpe5XKDejZnEfDFX84uwP/HifgQ2hgJFYz/Naslx/HwLA2s034AXnCI8+
xlNIAYVdAgMBAAECggEAJv8mxGq8TcO4S+oqf9nDfldfO8A4jDOHr97bglh5T2K+
+XbDkL9z5W8MWBuQzBAi2FDOK07uVw12c/6Es8515MfdKNpAkJvI71bfPvWmRNAK
SVcZHR+KtvzOhnn1qh34WwhVPqhLtZegfbt/QDqbuqVYD+JysRS/o/KfRaKa3zsQ
3L5NIQH5f8yx6QVQJXQX+wjFbh9x9b1PoTl0Fos2aFM8om8XTLmCXkjgF0Qt/Ye+
1Oimb+c5tuilOfXiiNAAgBa5I15RzgmHeQAVPdfbdceRgX34ezz35Hwin5ruH2J2
8l3D/1tbSUFta14RTieo/hE0HfBFQxP6g1LwasI3QQKBgQDwa8gP2JdH+cqN8d/G
GjF4IUd31ILjTIejp6mDc0sP7MoD7O1gINea2DhghRgd9XXIX90w5OTPCv0C6/HI
5rEe3NKJakfpWW+UxLYXFKXFrDOgnaAxECbuKPwS+FMgLWVQdbnzoWJYKFEGonFD
TeGZp1PEh673jzL8lMNjH8YF1QKBgQC29Ycw5f/eg4Nnse7YZ3Bo5abj2pUZRL8V
OvLtj2LxHBC+3tV2h5ALr1BANbmmBdeviezDjW6X1WfBSs9uptKjFBRHTL343Rvb
oHb1cLpcza8sw6BLQvv9V69pioHcKVpcb8fjE8jPZgt+lqqx68sKEF4jCUU0+fFb
4CIPfKAdaQKBgQCHxiampEfTEwNMJEOeqd40HH8y8iW03dxgFOiLXsoORUhU7TGl
Lwbz4JX+FEvpZ1zL+y98VFfPgUIfq0XRkk6Gwmh3yDiyVJrKJkk7QaLYvoYtd7cm
3htONoEc6XZwXpKv3LxWFVbnuGUB3S0fuFTmpHOPMp0iG5HMyOqLCT+YvQKBgFhE
iF6c+B7gEAt9GqAo92CEO0n+cKRqOE4DzKOz46YzRhjv5Mh0ipg4kl0IDnL8qpwz
zJhjqZFzEcV9VCosLb8jtszXR2fDNOd2uS2cnyyaxwKvtqvYuz30ido/SntvL/sc
qrDxIJZ+wtjl06BXA/PtBZ2doVf3pewPbB9QnubRAoGBAICoiAC1LaKHdzS8X3/s
TvayU5xxU+zcEgB+Ky94FW/C2H0CNtcxamoc5ju7Ffm1U6Mb+mUfvoVjJe9XEYFc
GGqo0uVI111NQXr3NtXMJl9TZ/bYPxG1fMfSLTMCnVBeI51VioAyIaOFp73kR+uU
6QRJuD0D6eM3fJQCMt1azdqb
-----END PRIVATE KEY-----
PEM;

        $resource = openssl_pkey_get_private($privateKey);
        $this->assertNotFalse($resource);

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
