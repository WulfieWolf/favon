<?php

namespace Tests\Feature;

use App\Exceptions\MailSafetyException;
use App\Jobs\SendVerificationMail;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use App\Services\VerificationMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueuedVerificationMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_identical_verification_jobs_are_unique_at_queue_level(): void
    {
        Queue::fake();

        $user = User::factory()->unverified()->create();

        SendVerificationMail::dispatch($user->id, 'registration');
        SendVerificationMail::dispatch($user->id, 'registration');

        Queue::assertPushed(SendVerificationMail::class, 1);
    }

    public function test_queued_job_sends_through_the_guarded_mail_service(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'queued@example.test',
            'locale' => 'de',
        ]);

        (new SendVerificationMail($user->id))->handle(app(VerificationMailService::class));

        Mail::assertSent(VerifyEmailMail::class, 1);
        $this->assertDatabaseHas('mail_deliveries', [
            'user_id' => $user->id,
            'mail_type' => 'email_verification',
            'status' => 'sent',
        ]);
    }

    public function test_duplicate_queued_jobs_cannot_send_twice(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'duplicate-queue@example.test',
        ]);

        $first = new SendVerificationMail($user->id);
        $second = new SendVerificationMail($user->id);

        $first->handle(app(VerificationMailService::class));

        try {
            $second->handle(app(VerificationMailService::class));
        } catch (MailSafetyException $exception) {
            $this->assertSame('Duplicate mail suppressed.', $exception->getMessage());
        }

        Mail::assertSent(VerifyEmailMail::class, 1);
        $this->assertDatabaseCount('mail_deliveries', 1);
    }

    public function test_job_does_nothing_when_user_is_already_verified(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        (new SendVerificationMail($user->id))->handle(app(VerificationMailService::class));

        Mail::assertNothingSent();
        $this->assertDatabaseCount('mail_deliveries', 0);
    }

    public function test_job_does_nothing_when_user_no_longer_exists(): void
    {
        Mail::fake();

        (new SendVerificationMail(999999))->handle(app(VerificationMailService::class));

        Mail::assertNothingSent();
        $this->assertDatabaseCount('mail_deliveries', 0);
    }
}
