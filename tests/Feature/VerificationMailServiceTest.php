<?php

namespace Tests\Feature;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\VerificationMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class VerificationMailServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config()->set('favon.mail.enabled', true);
        RateLimiter::clear('mail:global:minute');
        RateLimiter::clear('mail:global:hour');
    }

    public function test_unverified_user_gets_localized_verification_mail(): void
    {
        $user = User::factory()->unverified()->create([
            'name' => 'Sascha Schwarz',
            'email' => 'sascha@example.test',
            'locale' => 'de',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'public_handle' => 'CW-12378',
            'public_alias' => 'Wulfie',
        ]);

        app(VerificationMailService::class)->send($user->fresh());

        Mail::assertSent(VerifyEmailMail::class, function (VerifyEmailMail $mail) {
            return $mail->mailLocale === 'de'
                && $mail->context['greeting_name'] === 'Wulfie'
                && $mail->context['reason'] === 'registration'
                && str_contains($mail->context['verification_url'], '/email/verify/');
        });
    }

    public function test_email_change_uses_the_specific_copy_reason(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'changed@example.test',
            'locale' => 'en',
        ]);

        app(VerificationMailService::class)->send($user, 'email_change');

        Mail::assertSent(VerifyEmailMail::class, function (VerifyEmailMail $mail) {
            return $mail->mailLocale === 'en'
                && $mail->context['reason'] === 'email_change';
        });
    }

    public function test_verified_user_does_not_receive_verification_mail(): void
    {
        $user = User::factory()->create();

        app(VerificationMailService::class)->send($user);

        Mail::assertNothingSent();
    }
}
