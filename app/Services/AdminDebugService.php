<?php

namespace App\Services;

use App\Models\User;

class AdminDebugService
{
    public const SESSION_KEY = 'camperwolf.admin_debug';

    public function __construct(private readonly PermissionService $permissions)
    {
    }

    public function canManage(?User $user): bool
    {
        return $user !== null && $this->permissions->can($user, 'admin.access');
    }

    public function enabled(?User $user): bool
    {
        if (! (bool) session(self::SESSION_KEY, false)) {
            return false;
        }

        if (! $this->canManage($user)) {
            session()->forget(self::SESSION_KEY);

            return false;
        }

        return true;
    }

    public function setEnabled(?User $user, bool $enabled): void
    {
        abort_unless($this->canManage($user), 403);

        if ($enabled) {
            session()->put(self::SESSION_KEY, true);

            return;
        }

        session()->forget(self::SESSION_KEY);
    }
}
