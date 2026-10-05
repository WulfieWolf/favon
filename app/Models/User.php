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
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * Transitional Favon account model.
 *
 * Public community profiles, profile photos and gamification are intentionally
 * not part of Favon. Authentication itself will be rebuilt around Telegram.
 */
#[Fillable(['name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_until' => 'datetime',
        ];
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

    public function mailGreetingName(): string
    {
        return trim((string) $this->name);
    }

    public function publicName(): string
    {
        return (string) $this->name;
    }

    public function publicProfileUrl(): ?string
    {
        return null;
    }

    public function publicProfilePhotoUrl(): ?string
    {
        return null;
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

    public function initials(): string
    {
        $initials = Str::initials($this->publicName(), true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
