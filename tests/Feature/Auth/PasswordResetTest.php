<?php

namespace Tests\Feature\Auth;

use App\Jobs\SendPasswordResetMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Laravel\Fortify\Features;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::resetPasswords());
    }

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->post(route('password.request'), ['email' => $user->email]);

        Queue::assertPushed(SendPasswordResetMail::class, function (SendPasswordResetMail $job) use ($user): bool {
            return $job->userId === $user->id && $job->token !== '';
        });
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', $token))->assertOk();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $token = Password::broker()->createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login', absolute: false));
    }
}
