<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TelegramLoginService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function verify(array $payload): string
    {
        $token = (string) config('telegram.bot_token');
        if ($token === '') {
            throw new RuntimeException('Telegram login is not configured.');
        }

        $receivedHash = strtolower((string) ($payload['hash'] ?? ''));
        $authDate = filter_var($payload['auth_date'] ?? null, FILTER_VALIDATE_INT);
        $telegramId = trim((string) ($payload['id'] ?? ''));

        if ($receivedHash === '' || $authDate === false || $telegramId === '' || ! ctype_digit($telegramId)) {
            throw new RuntimeException('Invalid Telegram login payload.');
        }

        $maxAge = max(30, (int) config('telegram.auth_max_age_seconds', 300));
        $now = now()->timestamp;
        if ($authDate > $now + 60 || ($now - $authDate) > $maxAge) {
            throw new RuntimeException('Telegram login payload has expired.');
        }

        $signedFields = [];
        foreach (['auth_date', 'first_name', 'id', 'last_name', 'photo_url', 'username'] as $key) {
            if (array_key_exists($key, $payload) && $payload[$key] !== null) {
                $signedFields[$key] = (string) $payload[$key];
            }
        }

        ksort($signedFields);
        $dataCheckString = collect($signedFields)
            ->map(fn (string $value, string $key): string => $key.'='.$value)
            ->implode("\n");

        $secretKey = hash('sha256', $token, true);
        $expectedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (! hash_equals($expectedHash, $receivedHash)) {
            throw new RuntimeException('Telegram login signature is invalid.');
        }

        return $telegramId;
    }

    public function resolveUser(string $telegramUserId, string $locale): User
    {
        $existingUserId = DB::table('community_accounts')
            ->where('telegram_user_id', $telegramUserId)
            ->value('user_id');

        if ($existingUserId) {
            return User::query()->findOrFail((int) $existingUserId);
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
