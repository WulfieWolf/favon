<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Services\DevReleaseService;
use App\Services\SecurityEventService;
use App\Services\PublicHandleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $ip = (string) request()->ip();
        $hourKey = 'registration:hour:'.$ip;
        $dayKey = 'registration:day:'.$ip;

        if (RateLimiter::tooManyAttempts($hourKey, 3) || RateLimiter::tooManyAttempts($dayKey, 10)) {
            app(SecurityEventService::class)->record(request(), 'registration_rate_limited');

            throw ValidationException::withMessages([
                'email' => __('security.registration_rate_limited'),
            ]);
        }

        RateLimiter::hit($hourKey, 3600);
        RateLimiter::hit($dayKey, 86400);

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'legal_acceptance' => ['accepted'],
        ], [
            'legal_acceptance.accepted' => __('legal.registration.acceptance_required'),
        ])->validate();

        $user = DB::transaction(function () use ($input): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'locale' => app()->getLocale(),
            ]);

            $now = now();

            DB::table('user_profiles')->insert([
                'user_id' => $user->id,
                'public_handle' => app(PublicHandleService::class)->automaticForUserId((int) $user->id),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('user_settings')->insertOrIgnore([
                'user_id' => $user->id,
                'show_real_name' => false,
                'show_reviews_in_profile' => true,
                'show_photos_in_profile' => true,
                'show_join_date' => true,
                'show_activity_counts' => true,
                'allow_email_notifications' => true,
                'profile_photo_visibility' => 'public',
                'bio_visibility' => 'registered',
                'hometown_visibility' => 'registered',
                'age_visibility' => 'private',
                'gender_visibility' => 'private',
                'vehicle_visibility' => 'registered',
                'social_links_visibility' => 'registered',
                'show_gamification' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('user_consents')->insert([
                [
                    'user_id' => $user->id,
                    'consent_type' => 'terms_acceptance',
                    'version' => (string) config('legal.versions.terms'),
                    'accepted_at' => $now,
                    'revoked_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'user_id' => $user->id,
                    'consent_type' => 'privacy_notice_acknowledgement',
                    'version' => (string) config('legal.versions.privacy'),
                    'accepted_at' => $now,
                    'revoked_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            return $user;
        });

        $notifications = app(\App\Services\UserNotificationService::class);
        $locale = $notifications->userLocale((int) $user->id);
        $notifications->createImmediate(
            (int) $user->id,
            'welcome',
            __('notifications.welcome_title', [], $locale),
            __('notifications.welcome_message', [], $locale),
            route('community-profile.edit'),
            'normal',
            'bell',
        );

        if (app(DevReleaseService::class)->current()?->stage === 'beta') {
            $notifications->createImmediate(
                (int) $user->id,
                'beta_welcome',
                __('notifications.beta_welcome_title', [], $locale),
                __('notifications.beta_welcome_message', [], $locale),
                null,
                'important',
                'info-circle',
            );
        }

        Session::flash('verification_modal', 'registration_pending');

        return $user->load('profile');
    }
}
