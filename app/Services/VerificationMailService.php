<?php

namespace App\Services;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Support\Facades\URL;

class VerificationMailService
{
    public function __construct(
        private readonly MailContextService $context,
        private readonly SafeMailService $mail,
    ) {}

    public function send(User $user, string $reason = 'registration'): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $locale = $user->preferredLocale();
        $expires = (int) config('auth.verification.expire', 60);
        $reason = in_array($reason, ['registration', 'email_change'], true) ? $reason : 'registration';

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes($expires),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
                'reason' => $reason,
            ],
        );

        $context = [
            ...$this->context->forUser($user),
            'verification_url' => $url,
            'expires_in' => $expires,
            'reason' => $reason,
        ];

        $this->mail->send(
            'email_verification',
            $user->getEmailForVerification(),
            fn () => new VerifyEmailMail($context, $locale),
            $user,
            dedupeKey: $reason.'|'.sha1($user->getEmailForVerification()),
            locale: $locale,
        );
    }
}
