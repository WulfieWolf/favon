<?php

namespace App\Models;

use App\Jobs\SendPasswordResetMail;
use App\Jobs\SendVerificationMail;
use App\Services\PermissionService;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $locale
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $account_status
 * @property string|null $suspension_reason
 * @property Carbon|null $suspended_until
 */
#[Fillable(['name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_until' => 'datetime',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }


    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function sendEmailVerificationNotification(): void
    {
        SendVerificationMail::dispatch((int) $this->id, 'registration');
    }

    public function sendPasswordResetNotification($token): void
    {
        SendPasswordResetMail::dispatch((int) $this->id, (string) $token);
    }

    public function preferredLocale(): string
    {
        return $this->locale ?: (string) config('app.fallback_locale', 'de');
    }

    /**
     * Name used to address the user in account and security emails.
     * The automatic CW-ID is deliberately not used as a greeting.
     */
    public function mailGreetingName(): string
    {
        return trim((string) ($this->profile?->public_alias ?: $this->name));
    }

    public function publicName(): string
    {
        if (($this->account_status ?? 'active') !== 'active') {
            return $this->name;
        }

        return $this->profile?->public_alias ?: $this->profile?->public_handle ?: $this->name;
    }



    public function hasPermission(string $permissionSlug): bool
    {
        return app(PermissionService::class)->can($this, $permissionSlug);
    }

    public function hasRole(string $roleSlug): bool
    {
        return app(PermissionService::class)->hasRole($this, $roleSlug);
    }

    public function isSystemOwner(): bool
    {
        return app(PermissionService::class)->isOwner($this);
    }

    /**
     * Get the user's initials.
     */
    public function initials(): string
    {
        $initials = Str::initials($this->publicName(), true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
