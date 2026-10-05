<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PermissionService
{
    public const ROLE_PREVIEW_SESSION_KEY = 'camperwolf.role_preview';

    public function isOwner(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $ownerEmail = config('camperwolf.owner_email');

        return is_string($ownerEmail)
            && $ownerEmail !== ''
            && Str::lower(trim($user->email)) === Str::lower(trim($ownerEmail));
    }

    public function activeRolePreview(?User $user): ?string
    {
        if (! $this->isOwner($user)) {
            return null;
        }

        $roleSlug = session(self::ROLE_PREVIEW_SESSION_KEY);
        if (! is_string($roleSlug) || $roleSlug === '') {
            return null;
        }

        $exists = DB::table('roles')
            ->where('slug', $roleSlug)
            ->where('is_active', true)
            ->exists();

        if (! $exists) {
            session()->forget(self::ROLE_PREVIEW_SESSION_KEY);

            return null;
        }

        return $roleSlug;
    }

    public function can(?User $user, string $permissionSlug): bool
    {
        $rolePreview = $this->activeRolePreview($user);

        if ($user && $this->isOwner($user) && $rolePreview === null) {
            return true;
        }

        $permission = DB::table('permissions')
            ->select(['id', 'is_active'])
            ->where('slug', $permissionSlug)
            ->first();

        if (! $permission || ! $permission->is_active) {
            return false;
        }

        if ($rolePreview !== null) {
            return DB::table('role_permissions')
                ->join('roles', 'roles.id', '=', 'role_permissions.role_id')
                ->where('roles.slug', $rolePreview)
                ->where('roles.is_active', true)
                ->where('role_permissions.permission_id', $permission->id)
                ->exists();
        }

        if (! $user) {
            return DB::table('role_permissions')
                ->join('roles', 'roles.id', '=', 'role_permissions.role_id')
                ->where('roles.slug', 'guest')
                ->where('roles.is_active', true)
                ->where('role_permissions.permission_id', $permission->id)
                ->exists();
        }

        $override = DB::table('user_permission_overrides')
            ->where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->value('allowed');

        if ($override !== null) {
            return (bool) $override;
        }

        return DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->join('role_permissions', 'role_permissions.role_id', '=', 'roles.id')
            ->where('user_roles.user_id', $user->id)
            ->where('roles.is_active', true)
            ->where('role_permissions.permission_id', $permission->id)
            ->exists();
    }

    public function hasRole(User $user, string $roleSlug): bool
    {
        $rolePreview = $this->activeRolePreview($user);

        if ($rolePreview !== null) {
            return $rolePreview === $roleSlug;
        }

        if ($this->isOwner($user)) {
            return $roleSlug === 'admin' || $roleSlug === 'owner';
        }

        return DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $user->id)
            ->where('roles.slug', $roleSlug)
            ->where('roles.is_active', true)
            ->exists();
    }
}
