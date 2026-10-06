<?php

namespace Tests\Feature;

use App\Exceptions\MailSafetyException;
use App\Models\User;
use App\Services\SafeMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SafeMailServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('camperwolf.mail.enabled', true);
    }

    public function test_mail_is_blocked_when_the_circuit_breaker_is_disabled(): void
    {
        Mail::fake();
        config()->set('camperwolf.mail.enabled', false);

        try {
            app(SafeMailService::class)->send(
                'test',
                'blocked@example.test',
                fn () => new TestAccountMail('Blocked'),
                dedupeKey: 'circuit-breaker',
            );

            $this->fail('Expected the mail circuit breaker to block delivery.');
        } catch (MailSafetyException $exception) {
            $this->assertSame('Application mail circuit breaker is disabled.', $exception->getMessage());
        }

        Mail::assertNothingSent();
        $this->assertDatabaseCount('mail_deliveries', 0);
    }

    public function test_it_sends_one_mail_and_records_only_a_hashed_recipient(): void
    {
        Mail::fake();
        RateLimiter::clear('mail:global:minute');
        RateLimiter::clear('mail:global:hour');
        RateLimiter::clear('mail:global:day');

        $user = User::factory()->create(['email' => 'person@example.test']);

        app(SafeMailService::class)->send(
            'test',
            $user->email,
            fn () => new TestAccountMail('Hello'),
            $user,
            'event-1',
            'de',
        );

        Mail::assertSent(TestAccountMail::class, 1);

        $delivery = DB::table('mail_deliveries')->first();

        $this->assertSame('sent', $delivery->status);
        $this->assertSame('test', $delivery->mail_type);
        $this->assertStringNotContainsString('person@example.test', (string) json_encode($delivery));
    }

    public function test_it_suppresses_an_immediate_duplicate(): void
    {
        Mail::fake();
        RateLimiter::clear('mail:global:minute');
        RateLimiter::clear('mail:global:hour');

        $service = app(SafeMailService::class);
        $user = User::factory()->create(['email' => 'person@example.test']);

        $send = fn () => $service->send(
            'test',
            $user->email,
            fn () => new TestAccountMail('Hello'),
            $user,
            'same-event',
            'de',
        );

        $send();

        try {
            $send();
            $this->fail('Expected duplicate suppression.');
        } catch (MailSafetyException $exception) {
            $this->assertSame('Duplicate mail suppressed.', $exception->getMessage());
        }

        Mail::assertSent(TestAccountMail::class, 1);
    }

    public function test_it_stops_after_the_global_safety_limit(): void
    {
        Mail::fake();
        RateLimiter::clear('mail:global:minute');
        RateLimiter::clear('mail:global:hour');
        config()->set('camperwolf.mail.global_per_minute', 2);

        $service = app(SafeMailService::class);

        $service->send('test', 'one@example.test', fn () => new TestAccountMail('One'), dedupeKey: '1');
        $service->send('test', 'two@example.test', fn () => new TestAccountMail('Two'), dedupeKey: '2');

        $this->expectException(MailSafetyException::class);
        $service->send('test', 'three@example.test', fn () => new TestAccountMail('Three'), dedupeKey: '3');
    }

    public function test_it_stops_after_the_global_daily_safety_limit(): void
    {
        Mail::fake();
        RateLimiter::clear('mail:global:minute');
        RateLimiter::clear('mail:global:hour');
        RateLimiter::clear('mail:global:day');
        config()->set('camperwolf.mail.global_per_minute', 20);
        config()->set('camperwolf.mail.global_per_hour', 20);
        config()->set('camperwolf.mail.global_per_day', 2);

        $service = app(SafeMailService::class);

        $service->send('test', 'day-one@example.test', fn () => new TestAccountMail('One'), dedupeKey: 'day-1');
        $service->send('test', 'day-two@example.test', fn () => new TestAccountMail('Two'), dedupeKey: 'day-2');

        $this->expectException(MailSafetyException::class);
        $service->send('test', 'day-three@example.test', fn () => new TestAccountMail('Three'), dedupeKey: 'day-3');
    }

    public function test_it_applies_a_central_limit_per_mail_type(): void
    {
        Mail::fake();
        config()->set('camperwolf.mail.global_per_minute', 20);
        config()->set('camperwolf.mail.global_per_hour', 20);
        config()->set('camperwolf.mail.global_per_day', 20);
        config()->set('camperwolf.mail.type_per_hour.email_verification', 1);

        $service = app(SafeMailService::class);
        $service->send('email_verification', 'type-one@example.test', fn () => new TestAccountMail('One'), dedupeKey: 'type-1');

        $this->expectException(MailSafetyException::class);
        $service->send('email_verification', 'type-two@example.test', fn () => new TestAccountMail('Two'), dedupeKey: 'type-2');
    }

    public function test_failed_delivery_stores_only_exception_class_not_exception_message(): void
    {
        Mail::shouldReceive('to->send')->once()->andThrow(new \RuntimeException('secret smtp detail person@example.test'));

        $service = app(SafeMailService::class);

        try {
            $service->send('test', 'failure@example.test', fn () => new TestAccountMail('Failure'), dedupeKey: 'failure');
        } catch (\RuntimeException) {
            // Expected.
        }

        $delivery = DB::table('mail_deliveries')->first();

        $this->assertSame('RuntimeException', $delivery->failure_reason);
        $this->assertStringNotContainsString('secret smtp detail', (string) $delivery->failure_reason);
    }

    public function test_debug_preview_flag_never_enables_preview_outside_local_environment(): void
    {
        config()->set('camperwolf.mail.debug_preview', true);
        app()->detectEnvironment(fn () => 'production');

        $method = new \ReflectionMethod(SafeMailService::class, 'shouldDebugPreview');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke(app(SafeMailService::class)));
    }
}

class TestAccountMail extends Mailable
{
    public function __construct(public string $messageText) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Camperwolf test');
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>'.e($this->messageText).'</p>',
        );
    }
}
