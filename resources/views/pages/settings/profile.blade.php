<?php

use App\Concerns\ProfileValidationRules;
use App\Jobs\SendVerificationMail;
/* @chisel-email-verification */
use App\Services\PermissionService;
use App\Services\SecurityEventService;
/* @end-chisel-email-verification */
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component
{
    use ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public bool $emailWasChanged = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->emailWasChanged = Session::get('verification_email_change_for') === hash('sha256', mb_strtolower($this->email));
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        if (app(PermissionService::class)->isOwner($user) && strcasecmp($user->email, $validated['email']) !== 0) {
            $this->addError('email', __('global.owner_email_locked'));

            return;
        }

        $user->fill($validated);

        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $this->emailWasChanged = true;
            Session::put('verification_email_change_for', hash('sha256', mb_strtolower($user->email)));
            SendVerificationMail::dispatch((int) $user->id, 'email_change');

            $resendKey = 'verification-resend:user:'.$user->id;
            RateLimiter::hit($resendKey, 3600);
            RateLimiter::hit($resendKey.':cooldown', 60);
            Session::flash('verification_modal', 'email_change_pending');

            $this->redirectRoute('profile.edit', navigate: true);

            return;
        }

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /* @chisel-email-verification */
    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $key = 'verification-resend:user:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            app(SecurityEventService::class)->record(request(), 'verification_mail_rate_limited');
            Session::flash('status', 'verification-link-rate-limited');

            return;
        }

        if (RateLimiter::tooManyAttempts($key.':cooldown', 1)) {
            Session::flash('status', 'verification-link-cooldown');

            return;
        }

        RateLimiter::hit($key, 3600);
        RateLimiter::hit($key.':cooldown', 60);

        SendVerificationMail::dispatch((int) $user->id, $this->emailWasChanged ? 'email_change' : 'registration');

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
    /* @end-chisel-email-verification */
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                {{-- @chisel-email-verification --}}
                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @elseif (session('status') === 'verification-link-cooldown')
                            <flux:text class="mt-2 font-medium !dark:text-amber-400 !text-amber-600">
                                {{ __('Please wait one minute before requesting another verification email.') }}
                            </flux:text>
                        @elseif (session('status') === 'verification-link-rate-limited')
                            <flux:text class="mt-2 font-medium !dark:text-red-400 !text-red-600">
                                {{ __('Too many verification emails were requested. Please try again later.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
                {{-- @end-chisel-email-verification --}}
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

            </div>
        </form>

    </x-pages::settings.layout>
</section>
