<?php

namespace Tests\Feature\Settings;

use App\Jobs\SendVerificationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.profile')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('email_change_pending', session('verification_modal'));

        $user->refresh();

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);

        Queue::assertPushed(SendVerificationMail::class, function (SendVerificationMail $job) use ($user): bool {
            return $job->userId === $user->id && $job->reason === 'email_change';
        });
    }

    public function test_system_owner_cannot_change_owner_email_from_profile_settings(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        config(['favon.owner_email' => $user->email]);

        $this->actingAs($user);

        Livewire::test('pages::settings.profile')
            ->set('name', $user->name)
            ->set('email', 'changed-owner@example.test')
            ->call('updateProfileInformation')
            ->assertHasErrors(['email']);

        $this->assertSame($user->email, $user->fresh()->email);
        Queue::assertNothingPushed();
    }

    public function test_email_verification_status_is_unchanged_when_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.profile')
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'password')
            ->call('deleteUser');

        $response
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($user->fresh());
        $this->assertFalse(auth()->check());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $response->assertHasErrors(['password']);

        $this->assertNotNull($user->fresh());
    }
}
