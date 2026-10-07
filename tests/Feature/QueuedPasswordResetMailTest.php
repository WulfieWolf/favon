<?php

namespace Tests\Feature;

use App\Jobs\SendPasswordResetMail;
use App\Mail\PasswordResetMail;
use App\Models\User;
use App\Services\PasswordResetMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueuedPasswordResetMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('favon.mail.enabled', true);
    }

    public function test_identical_password_reset_jobs_are_unique_at_queue_level(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        SendPasswordResetMail::dispatch($user->id, 'same-token');
        SendPasswordResetMail::dispatch($user->id, 'same-token');

        Queue::assertPushed(SendPasswordResetMail::class, 1);
    }

    public function test_job_sends_password_reset_through_safe_mail_service(): void
    {
        Mail::fake();

        $user = User::factory()->create(['locale' => 'de']);
        $job = new SendPasswordResetMail($user->id, 'test-reset-token');

        $job->handle(app(PasswordResetMailService::class));

        Mail::assertSent(PasswordResetMail::class, function (PasswordResetMail $mail) use ($user): bool {
            return $mail->context['user_id'] === $user->id
                && str_contains($mail->context['reset_url'], 'test-reset-token');
        });

        $this->assertDatabaseHas('mail_deliveries', [
            'user_id' => $user->id,
            'mail_type' => 'password_reset',
            'status' => 'sent',
        ]);
    }

    public function test_same_reset_token_cannot_be_sent_twice(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $service = app(PasswordResetMailService::class);

        (new SendPasswordResetMail($user->id, 'same-token'))->handle($service);

        (new SendPasswordResetMail($user->id, 'same-token'))->handle($service);

        Mail::assertSent(PasswordResetMail::class, 1);
        $this->assertDatabaseCount('mail_deliveries', 1);
    }

    public function test_job_does_nothing_when_user_no_longer_exists(): void
    {
        Mail::fake();

        (new SendPasswordResetMail(999999, 'unused-token'))
            ->handle(app(PasswordResetMailService::class));

        Mail::assertNothingSent();
        $this->assertDatabaseCount('mail_deliveries', 0);
    }
}
