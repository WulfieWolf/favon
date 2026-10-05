<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;

/**
 * Provides the small, explicit set of user data that account emails may use.
 *
 * Mail types should add only their own required values (for example a signed
 * verification URL). Do not pass complete User models into mail templates.
 */
class MailContextService
{
    /**
     * @return array{user_id:int, locale:string, greeting_name:string, account_name:string, email:string, cw_id:?string}
     */
    public function forUser(User $user): array
    {
        $user->loadMissing('profile');

        /** @var UserProfile|null $profile */
        $profile = $user->profile;

        return [
            'user_id' => (int) $user->id,
            'locale' => $user->preferredLocale(),
            'greeting_name' => $user->mailGreetingName(),
            'account_name' => $user->name,
            'email' => $user->email,
            'cw_id' => $profile?->public_handle,
        ];
    }
}
