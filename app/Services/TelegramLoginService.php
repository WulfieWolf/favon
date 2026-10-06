<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramLoginService
{
    public function authorizationUrl(Request $request, string $redirectUri): string
    {
        $clientId = trim((string) config('telegram.client_id'));
        if ($clientId === '') {
            throw new RuntimeException('Telegram login is not configured.');
        }

        $state = $this->base64UrlEncode(random_bytes(32));
        $nonce = $this->base64UrlEncode(random_bytes(32));
        $verifier = $this->base64UrlEncode(random_bytes(64));
        $challenge = $this->base64UrlEncode(hash('sha256', $verifier, true));

        $request->session()->put([
            'telegram_oidc_state' => $state,
            'telegram_oidc_nonce' => $nonce,
            'telegram_oidc_verifier' => $verifier,
            'telegram_oidc_redirect_uri' => $redirectUri,
        ]);

        return rtrim((string) config('telegram.authorization_url'), '?').'?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function verifyAuthorizationResponse(Request $request): string
    {
        if ($request->filled('error')) {
            throw new RuntimeException('Telegram rejected the authorization request.');
        }

        $code = trim((string) $request->query('code', ''));
        $state = trim((string) $request->query('state', ''));

        $expectedState = (string) $request->session()->pull('telegram_oidc_state', '');
        $nonce = (string) $request->session()->pull('telegram_oidc_nonce', '');
        $verifier = (string) $request->session()->pull('telegram_oidc_verifier', '');
        $redirectUri = (string) $request->session()->pull('telegram_oidc_redirect_uri', '');

        if (
            $code === ''
            || $state === ''
            || $expectedState === ''
            || ! hash_equals($expectedState, $state)
            || $nonce === ''
            || $verifier === ''
            || $redirectUri === ''
        ) {
            throw new RuntimeException('Invalid Telegram authorization response.');
        }

        $idToken = $this->exchangeCode($code, $verifier, $redirectUri);

        return $this->verifyIdToken($idToken, $nonce);
    }

    public function resolveUser(string $telegramUserId, string $locale): User
    {
        $existingUserId = DB::table('community_accounts')
            ->where('telegram_user_id', $telegramUserId)
            ->value('user_id');

        if ($existingUserId) {
            return User::query()->findOrFail((int) $existingUserId);
        }

        if (app(SiteAccessService::class)->registrationClosed()) {
            throw new RuntimeException('Favon registration is currently closed.');
        }

        return DB::transaction(function () use ($telegramUserId, $locale): User {
            $existing = DB::table('community_accounts')
                ->where('telegram_user_id', $telegramUserId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return User::query()->findOrFail((int) $existing->user_id);
            }

            $user = User::query()->create([
                'name' => 'User',
                'email' => null,
                'password' => null,
                'locale' => $locale,
            ]);

            $communityNumber = (int) DB::table('community_accounts')->insertGetId([
                'user_id' => $user->id,
                'telegram_user_id' => $telegramUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $user->forceFill([
                'name' => sprintf('User-%07d', $communityNumber),
            ])->save();

            $roleId = DB::table('roles')
                ->where('slug', 'user')
                ->where('is_active', true)
                ->value('id');

            if ($roleId) {
                DB::table('user_roles')->insertOrIgnore([
                    'user_id' => $user->id,
                    'role_id' => $roleId,
                    'assigned_by' => null,
                    'assigned_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->recordInitialConsent((int) $user->id);

            return $user->fresh();
        });
    }

    private function exchangeCode(string $code, string $verifier, string $redirectUri): string
    {
        $clientId = trim((string) config('telegram.client_id'));
        $clientSecret = (string) config('telegram.client_secret');

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('Telegram login is not configured.');
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->withBasicAuth($clientId, $clientSecret)
                ->timeout(8)
                ->post((string) config('telegram.token_url'), [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                    'client_id' => $clientId,
                    'code_verifier' => $verifier,
                ])
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Telegram token exchange failed.', 0, $exception);
        }

        $idToken = trim((string) $response->json('id_token', ''));
        if ($idToken === '') {
            throw new RuntimeException('Telegram did not return an ID token.');
        }

        return $idToken;
    }

    private function verifyIdToken(string $idToken, string $expectedNonce): string
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid Telegram ID token.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $header = json_decode($this->base64UrlDecode($encodedHeader), true);
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);
        $signature = $this->base64UrlDecode($encodedSignature);

        if (! is_array($header) || ! is_array($payload)) {
            throw new RuntimeException('Invalid Telegram ID token.');
        }

        if (($header['alg'] ?? null) !== 'RS256' || ! is_string($header['kid'] ?? null)) {
            throw new RuntimeException('Unsupported Telegram ID token.');
        }

        $jwk = collect($this->jwks())
            ->first(fn (array $key): bool => ($key['kid'] ?? null) === $header['kid'] && ($key['kty'] ?? null) === 'RSA');

        if (! is_array($jwk)) {
            Cache::forget('telegram_oidc_jwks');
            $jwk = collect($this->jwks())
                ->first(fn (array $key): bool => ($key['kid'] ?? null) === $header['kid'] && ($key['kty'] ?? null) === 'RSA');
        }

        if (! is_array($jwk)) {
            throw new RuntimeException('Telegram signing key was not found.');
        }

        $verified = openssl_verify(
            $encodedHeader.'.'.$encodedPayload,
            $signature,
            $this->rsaJwkToPem($jwk),
            OPENSSL_ALGO_SHA256,
        );

        if ($verified !== 1) {
            throw new RuntimeException('Telegram ID token signature is invalid.');
        }

        $now = now()->timestamp;
        $issuer = (string) config('telegram.issuer');
        $clientId = (string) config('telegram.client_id');

        if (($payload['iss'] ?? null) !== $issuer) {
            throw new RuntimeException('Telegram ID token issuer is invalid.');
        }

        $audience = $payload['aud'] ?? null;
        $audiences = is_array($audience) ? array_map('strval', $audience) : [(string) $audience];
        if (! in_array($clientId, $audiences, true)) {
            throw new RuntimeException('Telegram ID token audience is invalid.');
        }

        $expiresAt = filter_var($payload['exp'] ?? null, FILTER_VALIDATE_INT);
        $issuedAt = filter_var($payload['iat'] ?? null, FILTER_VALIDATE_INT);
        if ($expiresAt === false || $issuedAt === false || $expiresAt <= $now || $issuedAt > $now + 60) {
            throw new RuntimeException('Telegram ID token has expired or is not yet valid.');
        }

        $nonce = (string) ($payload['nonce'] ?? '');
        if ($nonce === '' || ! hash_equals($expectedNonce, $nonce)) {
            throw new RuntimeException('Telegram ID token nonce is invalid.');
        }

        $telegramId = trim((string) ($payload['sub'] ?? ''));
        if ($telegramId === '' || ! ctype_digit($telegramId)) {
            throw new RuntimeException('Telegram ID token subject is invalid.');
        }

        return $telegramId;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function jwks(): array
    {
        return Cache::remember('telegram_oidc_jwks', now()->addHour(), function (): array {
            try {
                $response = Http::acceptJson()
                    ->timeout(8)
                    ->get((string) config('telegram.jwks_url'))
                    ->throw();
            } catch (RequestException $exception) {
                throw new RuntimeException('Telegram signing keys could not be loaded.', 0, $exception);
            }

            $keys = $response->json('keys');

            if (! is_array($keys)) {
                throw new RuntimeException('Telegram signing keys are invalid.');
            }

            return array_values(array_filter($keys, 'is_array'));
        });
    }

    /**
     * @param array<string, mixed> $jwk
     */
    private function rsaJwkToPem(array $jwk): string
    {
        $modulus = $this->base64UrlDecode((string) ($jwk['n'] ?? ''));
        $exponent = $this->base64UrlDecode((string) ($jwk['e'] ?? ''));

        if ($modulus === '' || $exponent === '') {
            throw new RuntimeException('Telegram RSA signing key is invalid.');
        }

        $rsaPublicKey = $this->asn1Sequence(
            $this->asn1Integer($modulus).
            $this->asn1Integer($exponent),
        );

        $algorithmIdentifier = $this->asn1Sequence(
            "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01".
            "\x05\x00",
        );

        $subjectPublicKeyInfo = $this->asn1Sequence(
            $algorithmIdentifier.
            "\x03".$this->asn1Length(strlen($rsaPublicKey) + 1)."\x00".$rsaPublicKey,
        );

        return "-----BEGIN PUBLIC KEY-----\n".
            chunk_split(base64_encode($subjectPublicKeyInfo), 64, "\n").
            "-----END PUBLIC KEY-----\n";
    }

    private function asn1Sequence(string $value): string
    {
        return "\x30".$this->asn1Length(strlen($value)).$value;
    }

    private function asn1Integer(string $value): string
    {
        $value = ltrim($value, "\x00");
        if ($value === '') {
            $value = "\x00";
        }

        if ((ord($value[0]) & 0x80) !== 0) {
            $value = "\x00".$value;
        }

        return "\x02".$this->asn1Length(strlen($value)).$value;
    }

    private function asn1Length(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $encoded = '';
        while ($length > 0) {
            $encoded = chr($length & 0xff).$encoded;
            $length >>= 8;
        }

        return chr(0x80 | strlen($encoded)).$encoded;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder !== 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw new RuntimeException('Invalid base64url value.');
        }

        return $decoded;
    }

    private function recordInitialConsent(int $userId): void
    {
        $now = now();

        foreach ([
            'terms_acceptance' => (string) config('legal.versions.terms'),
            'privacy_notice_acknowledgement' => (string) config('legal.versions.privacy'),
        ] as $type => $version) {
            DB::table('user_consents')->insertOrIgnore([
                'user_id' => $userId,
                'consent_type' => $type,
                'version' => $version,
                'accepted_at' => $now,
                'revoked_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
