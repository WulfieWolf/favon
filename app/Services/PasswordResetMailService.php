<?php

namespace App\Services;

use App\Mail\PasswordResetMail;
use App\Models\User;

class PasswordResetMailService
{
    public function __construct(
        private readonly MailContextService $context,
        private readonly SafeMailService $mail,
    ) {}

    public function send(User $user, string $token): void
    {
        $locale = $user->preferredLocale();
        $expires = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
        $url = route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]);

        $context = [
            ...$this->context->forUser($user),
            'reset_url' => $url,
            'expires_in' => $expires,
        ];

        $this->mail->send(
            'password_reset',
            $user->getEmailForPasswordReset(),
            fn () => new PasswordResetMail($context, $locale),
            $user,
            dedupeKey: hash('sha256', $token),
            locale: $locale,
        );
    }
}
