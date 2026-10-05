<?php

namespace App\Services;

use App\Exceptions\MailSafetyException;
use App\Models\User;
use Closure;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class SafeMailService
{
    /**
     * All application mail should pass through this method.
     *
     * @param  Closure(): Mailable  $mailFactory
     */
    public function send(
        string $mailType,
        string $recipient,
        Closure $mailFactory,
        ?User $user = null,
        ?string $dedupeKey = null,
        ?string $locale = null,
    ): void {
        $recipient = mb_strtolower(trim($recipient));

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new MailSafetyException('Invalid mail recipient.');
        }

        if (! (bool) config('camperwolf.mail.enabled', false)) {
            throw new MailSafetyException('Application mail circuit breaker is disabled.');
        }

        $recipientHash = $this->hash($recipient);
        $fingerprint = $this->hash($mailType.'|'.$recipient.'|'.($dedupeKey ?? 'default'));
        $deliveryId = $this->reserveDelivery($mailType, $recipientHash, $fingerprint, $user, $locale);

        try {
            $this->reserveRateLimits($mailType, $recipientHash);

            $mail = $mailFactory();

            if ($this->shouldDebugPreview()) {
                $subject = method_exists($mail, 'envelope')
                    ? $mail->envelope()->subject
                    : null;

                Log::debug('Camperwolf mail preview', [
                    'mail_type' => $mailType,
                    'recipient_hash' => $recipientHash,
                    'subject' => $subject,
                ]);
            }

            Mail::to($recipient)->send($mail);

            DB::table('mail_deliveries')->where('id', $deliveryId)->update([
                'status' => 'sent',
                'sent_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (MailSafetyException $exception) {
            DB::table('mail_deliveries')->where('id', $deliveryId)->update([
                'status' => 'blocked',
                'failure_reason' => $this->safeFailureReason($exception),
                'updated_at' => now(),
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            DB::table('mail_deliveries')->where('id', $deliveryId)->update([
                'status' => 'failed',
                'failure_reason' => $this->safeFailureReason($exception),
                'updated_at' => now(),
            ]);

            throw $exception;
        }
    }

    private function reserveRateLimits(string $mailType, string $recipientHash): void
    {
        foreach ($this->rateLimits($mailType, $recipientHash) as [$key, $maxAttempts, $decaySeconds]) {
            if ($maxAttempts < 1) {
                $this->recordSafetyEvent('mail_safety_rate_limited', $mailType, $recipientHash, $key);

                throw new MailSafetyException('Mail safety rate limit reached.');
            }

            if (! RateLimiter::attempt($key, $maxAttempts, static fn (): bool => true, $decaySeconds)) {
                $this->recordSafetyEvent('mail_safety_rate_limited', $mailType, $recipientHash, $key);

                throw new MailSafetyException('Mail safety rate limit reached.');
            }
        }
    }

    /**
     * @return array<int, array{string, int, int}>
     */
    private function rateLimits(string $mailType, string $recipientHash): array
    {
        $typeLimits = (array) config('camperwolf.mail.type_per_hour', []);
        $typeLimit = (int) ($typeLimits[$mailType] ?? $typeLimits['default'] ?? 20);

        return [
            ['mail:global:minute', (int) config('camperwolf.mail.global_per_minute', 20), 60],
            ['mail:global:hour', (int) config('camperwolf.mail.global_per_hour', 100), 3600],
            ['mail:global:day', (int) config('camperwolf.mail.global_per_day', 50), 86400],
            ['mail:recipient:hour:'.$recipientHash, (int) config('camperwolf.mail.recipient_per_hour', 10), 3600],
            ['mail:type:hour:'.$this->hash($mailType), $typeLimit, 3600],
        ];
    }

    private function reserveDelivery(
        string $mailType,
        string $recipientHash,
        string $fingerprint,
        ?User $user,
        ?string $locale,
    ): int {
        $seconds = max(1, (int) config('camperwolf.mail.duplicate_window_seconds', 60));
        $lock = Cache::lock('mail:dedupe:'.$fingerprint, 10);

        return $lock->block(3, function () use ($mailType, $recipientHash, $fingerprint, $user, $locale, $seconds): int {
            $duplicate = DB::table('mail_deliveries')
                ->where('message_fingerprint', $fingerprint)
                ->where('attempted_at', '>=', now()->subSeconds($seconds))
                ->exists();

            if ($duplicate) {
                $this->recordSafetyEvent('mail_duplicate_suppressed', $mailType, $recipientHash, 'duplicate');

                throw new MailSafetyException('Duplicate mail suppressed.');
            }

            return DB::table('mail_deliveries')->insertGetId([
                'user_id' => $user?->id,
                'mail_type' => $mailType,
                'recipient_hash' => $recipientHash,
                'message_fingerprint' => $fingerprint,
                'status' => 'attempting',
                'mailer' => (string) config('mail.default'),
                'locale' => $locale,
                'attempted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    private function recordSafetyEvent(string $eventType, string $mailType, string $recipientHash, string $limit): void
    {
        if (! app()->bound('request')) {
            return;
        }

        app(SecurityEventService::class)->record(request(), $eventType, [
            'mail_type' => $mailType,
            'recipient_hash' => $recipientHash,
            'limit' => $limit,
        ]);
    }

    private function safeFailureReason(Throwable $exception): string
    {
        return mb_substr(class_basename($exception), 0, 255);
    }

    private function shouldDebugPreview(): bool
    {
        return app()->environment('local')
            && (bool) config('camperwolf.mail.debug_preview', false);
    }

    private function hash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
