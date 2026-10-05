<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RolePermissionService
{
    public function __construct(private PermissionService $permissions)
    {
    }

    public function assignRole(User $actor, User $target, string $roleSlug): void
    {
        $role = DB::table('roles')->where('slug', $roleSlug)->where('is_active', true)->first();
        if (! $role) {
            throw new RuntimeException(__('admin.access_control.errors.role_missing'));
        }

        if ($roleSlug === 'admin' && ! $this->permissions->isOwner($actor)) {
            throw new RuntimeException(__('admin.access_control.errors.admin_assign_owner'));
        }

        if ($roleSlug !== 'admin' && ! $this->permissions->can($actor, 'users.assign_roles')) {
            throw new RuntimeException(__('admin.access_control.errors.missing_assign_roles'));
        }

        DB::transaction(function () use ($actor, $target, $role, $roleSlug): void {
            $exists = DB::table('user_roles')
                ->where('user_id', $target->id)
                ->where('role_id', $role->id)
                ->exists();

            if (! $exists) {
                DB::table('user_roles')->insert([
                    'user_id' => $target->id,
                    'role_id' => $role->id,
                    'assigned_by' => $actor->id,
                    'assigned_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->audit($actor, 'user_role', $target->id, 'role_assigned', null, ['role' => $roleSlug]);
            }
        });
    }

    public function removeRole(User $actor, User $target, string $roleSlug): void
    {
        if ($this->permissions->isOwner($target)) {
            throw new RuntimeException(__('admin.access_control.errors.owner_roles_locked'));
        }

        if ($roleSlug === 'admin' && ! $this->permissions->isOwner($actor)) {
            throw new RuntimeException(__('admin.access_control.errors.admin_remove_owner'));
        }

        if ($actor->id === $target->id && $roleSlug === 'admin') {
            throw new RuntimeException(__('admin.access_control.errors.self_admin_remove'));
        }

        if ($roleSlug !== 'admin' && ! $this->permissions->can($actor, 'users.assign_roles')) {
            throw new RuntimeException(__('admin.access_control.errors.missing_assign_roles'));
        }

        $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');
        if (! $roleId) {
            return;
        }

        DB::transaction(function () use ($actor, $target, $roleId, $roleSlug): void {
            $deleted = DB::table('user_roles')
                ->where('user_id', $target->id)
                ->where('role_id', $roleId)
                ->delete();

            if ($deleted) {
                $this->audit($actor, 'user_role', $target->id, 'role_removed', ['role' => $roleSlug], null);
            }
        });
    }

    public function setUserOverride(User $actor, User $target, string $permissionSlug, bool $allowed, ?string $reason = null): void
    {
        if (! $this->permissions->can($actor, 'users.override_permissions')) {
            throw new RuntimeException(__('admin.access_control.errors.missing_override_permissions'));
        }

        if ($this->permissions->isOwner($target)) {
            throw new RuntimeException(__('admin.access_control.errors.owner_override_locked'));
        }

        $permission = DB::table('permissions')->where('slug', $permissionSlug)->where('is_active', true)->first();
        if (! $permission) {
            throw new RuntimeException(__('admin.access_control.errors.permission_missing'));
        }

        $old = DB::table('user_permission_overrides')
            ->where('user_id', $target->id)
            ->where('permission_id', $permission->id)
            ->first();

        DB::transaction(function () use ($actor, $target, $permission, $permissionSlug, $allowed, $reason, $old): void {
            DB::table('user_permission_overrides')->updateOrInsert(
                ['user_id' => $target->id, 'permission_id' => $permission->id],
                [
                    'allowed' => $allowed,
                    'set_by' => $actor->id,
                    'reason' => $reason,
                    'updated_at' => now(),
                    'created_at' => $old?->created_at ?? now(),
                ],
            );

            $this->audit(
                $actor,
                'user_permission_override',
                $target->id,
                'override_set',
                $old ? ['permission' => $permissionSlug, 'allowed' => (bool) $old->allowed] : null,
                ['permission' => $permissionSlug, 'allowed' => $allowed, 'reason' => $reason],
            );
        });
    }

    public function clearUserOverride(User $actor, User $target, string $permissionSlug): void
    {
        if (! $this->permissions->can($actor, 'users.override_permissions')) {
            throw new RuntimeException(__('admin.access_control.errors.missing_override_permissions'));
        }

        $permissionId = DB::table('permissions')->where('slug', $permissionSlug)->value('id');
        if (! $permissionId) {
            return;
        }

        DB::transaction(function () use ($actor, $target, $permissionId, $permissionSlug): void {
            $old = DB::table('user_permission_overrides')
                ->where('user_id', $target->id)
                ->where('permission_id', $permissionId)
                ->first();

            if ($old) {
                DB::table('user_permission_overrides')->where('id', $old->id)->delete();
                $this->audit($actor, 'user_permission_override', $target->id, 'override_removed', [
                    'permission' => $permissionSlug,
                    'allowed' => (bool) $old->allowed,
                ], null);
            }
        });
    }

    private function audit(User $actor, string $entityType, int $entityId, string $action, ?array $oldValues, ?array $newValues): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $actor->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'source' => 'admin',
            'old_values' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
            'new_values' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
            'internal_comment' => null,
            'created_at' => now(),
        ]);
    }
}
