<?php

namespace Tests\Feature\Auth;

use App\Jobs\SendVerificationMail;
use App\Models\User;
use App\Services\PublicHandleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk()
            ->assertSee('name="legal_acceptance"', false)
            ->assertSee(route('legal.terms'), false)
            ->assertSee(route('legal.privacy'), false);
    }

    public function test_new_users_can_register(): void
    {
        Queue::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'legal_acceptance' => '1',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertSessionHas('verification_modal', 'registration_pending')
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'public_handle' => app(PublicHandleService::class)->automaticForUserId((int) $user->id),
        ]);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'profile_photo_visibility' => 'public',
            'bio_visibility' => 'registered',
            'age_visibility' => 'private',
            'gender_visibility' => 'private',
            'show_gamification' => true,
        ]);

        $this->assertDatabaseHas('user_consents', [
            'user_id' => $user->id,
            'consent_type' => 'terms_acceptance',
            'version' => config('legal.versions.terms'),
        ]);

        $this->assertDatabaseHas('user_consents', [
            'user_id' => $user->id,
            'consent_type' => 'privacy_notice_acknowledgement',
            'version' => config('legal.versions.privacy'),
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'type' => 'welcome',
            'message' => __('notifications.welcome_message', [], $user->locale),
            'url' => route('dashboard'),
        ]);


        Queue::assertPushed(SendVerificationMail::class, function (SendVerificationMail $job) use ($user): bool {
            return $job->userId === $user->id
                && $job->reason === 'registration';
        });

        Queue::assertPushed(SendVerificationMail::class, 1);
        $this->assertDatabaseCount('mail_deliveries', 0);
    }

    public function test_repeated_framework_notification_is_deduplicated_before_queueing(): void
    {
        Queue::fake();

        $user = User::factory()->unverified()->create();

        $user->sendEmailVerificationNotification();
        $user->sendEmailVerificationNotification();

        Queue::assertPushed(SendVerificationMail::class, 1);
        $this->assertDatabaseCount('mail_deliveries', 0);
    }

    public function test_registration_requires_legal_acceptance(): void
    {
        $this->post(route('register.store'), [
            'name' => 'No Consent',
            'email' => 'no-consent@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasErrors('legal_acceptance');

        $this->assertDatabaseMissing('users', [
            'email' => 'no-consent@example.test',
        ]);
    }

    public function test_registration_is_rate_limited_per_ip(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->post(route('register.store'), [
                'name' => 'Registration '.$i,
                'email' => 'registration-'.$i.'@example.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'legal_acceptance' => '1',
            ])->assertSessionHasNoErrors();

            $this->post(route('logout'));
        }

        $this->post(route('register.store'), [
            'name' => 'Registration 4',
            'email' => 'registration-4@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'legal_acceptance' => '1',
        ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', [
            'email' => 'registration-4@example.test',
        ]);

        $this->assertDatabaseHas('security_events', [
            'event_type' => 'registration_rate_limited',
        ]);
    }
}
