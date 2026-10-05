<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Services\PermissionService;
use App\Services\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class AdminController extends Controller
{
    public function index(PermissionService $permissions): View
    {
        $user = auth()->user();

        $supportStatusCounts = DB::table('support_tickets')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();

        return view('admin.index', [
            'isOwner' => $permissions->isOwner($user),
            'canUseSystemTools' => $permissions->can($user, 'admin.access'),
            'canApproveChanges' => $permissions->can($user, 'places.approve_changes'),
            'canMergePlaces' => $permissions->can($user, 'places.merge'),
            'canViewUsers' => $permissions->can($user, 'users.view'),
            'canViewReviewReports' => $permissions->can($user, 'reports.view_all'),
            'canViewAuditLogs' => $permissions->can($user, 'audit.view_all'),
            'canViewStatistics' => $permissions->can($user, 'statistics.view'),
            'canSendSystemNotifications' => $permissions->can($user, 'notifications.send_system'),
            'canManageSupport' => $permissions->can($user, 'support.view_all'),
            'canModeratePhotos' => false,
            'canManageFeatures' => $permissions->can($user, 'features.manage_catalog'),
            'userCount' => User::count(),
            'pendingChangeRequestCount' => DB::table('change_requests')->where('status', 'pending')->count(),
            'quarantinedPlaceSubmissionCount' => DB::table('abuse_flags')
                ->where('entity_type', 'place')
                ->where('status', 'open')
                ->distinct('entity_id')
                ->count('entity_id'),
            'pendingReviewReportCount' => DB::table('place_review_reports')->where('status', 'pending')->count(),
            'pendingPhotoCount' => 0,
            'pendingPhotoReportCount' => 0,
            'photoCount' => 0,
            'featureCount' => DB::table('features')->where('is_active', true)->count(),
            'supportStatusCounts' => $supportStatusCounts,
            'auditLogCount' => DB::table('audit_logs')->count(),
        ]);
    }

    public function users(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $role = (string) $request->query('role', '');
        $verification = (string) $request->query('verification', '');
        $sort = (string) $request->query('sort', 'name');
        $direction = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['name', 'email', 'status', 'role', 'last_seen', 'created'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        $usersQuery = User::query()
            ->select(['users.id','users.name','users.email','users.email_verified_at','users.account_status','users.last_seen_at','users.created_at'])
            ->when($search !== '', function ($query) use ($search): void { $query->where(function ($query) use ($search): void { $query->where('users.name','like','%'.$search.'%')->orWhere('users.email','like','%'.$search.'%')->orWhere('users.id', ctype_digit($search) ? (int) $search : -1); }); })
            ->when(in_array($status, ['active','suspended','pending_deletion','deleted'], true), fn ($query) => $query->where('users.account_status',$status))
            ->when($verification === 'verified', fn ($query) => $query->whereNotNull('users.email_verified_at'))
            ->when($verification === 'unverified', fn ($query) => $query->whereNull('users.email_verified_at'))
            ->when($role !== '', fn ($query) => $query->whereHas('roles', fn ($roles) => $roles->where('roles.slug',$role)->where('roles.is_active',true)));

        match ($sort) {
            'email' => $usersQuery->orderBy('users.email', $direction),
            'status' => $usersQuery->orderBy('users.account_status', $direction),
            'role' => $usersQuery->orderByRaw(
                "COALESCE((SELECT MIN(r.sort_order) FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = users.id AND r.is_active = 1), 2147483647) {$direction}"
            ),
            'last_seen' => $usersQuery->orderByRaw('users.last_seen_at IS NULL')->orderBy('users.last_seen_at', $direction),
            'created' => $usersQuery->orderBy('users.created_at', $direction),
            default => $usersQuery->orderBy('users.name', $direction),
        };

        $users = $usersQuery->orderBy('users.name')->orderBy('users.email')->paginate(30)->withQueryString();
        $roleRows = DB::table('user_roles')->join('roles','roles.id','=','user_roles.role_id')->whereIn('user_roles.user_id',$users->getCollection()->pluck('id'))->where('roles.is_active',true)->orderBy('roles.sort_order')->get(['user_roles.user_id','roles.slug','roles.name'])->groupBy('user_id');
        $filterRoles = DB::table('roles')->where('is_active',true)->where('slug','!=','guest')->orderBy('sort_order')->get(['slug','name']);
        return view('admin.users.index', compact('users','roleRows','filterRoles','search','status','role','verification','sort','direction'));
    }

    public function user(User $user, PermissionService $permissions): View
    {
        $actor = auth()->user();

        $roles = DB::table('roles')
            ->where('is_active', true)
            ->where('slug', '!=', 'guest')
            ->orderBy('sort_order')
            ->get();

        $assignedRoleIds = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->pluck('role_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $actorIsOwner = $permissions->isOwner($actor);
        $targetIsOwner = $permissions->isOwner($user);
        $actorCanAssignRoles = $permissions->can($actor, 'users.assign_roles');
        $actorCanOverridePermissions = $permissions->can($actor, 'users.override_permissions');

        $roleCards = $roles->map(function ($role) use ($user, $assignedRoleIds, $actorIsOwner, $targetIsOwner, $actorCanAssignRoles): array {
            $slug = (string) $role->slug;
            $assigned = in_array((int) $role->id, $assignedRoleIds, true);
            $adminProtected = $slug === 'admin' && ! $actorIsOwner;

            return [
                'slug' => $slug,
                'target_user_id' => (int) $user->id,
                'assigned' => $assigned,
                'can_change' => ! $targetIsOwner
                    && ($actorCanAssignRoles || ($slug === 'admin' && $actorIsOwner))
                    && ! $adminProtected,
                'admin_owner_only' => $slug === 'admin' && ! $actorIsOwner,
            ];
        })->values();

        $permissionRows = DB::table('permissions')
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('slug')
            ->get();

        $overrides = DB::table('user_permission_overrides')
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('permission_id');

        $permissionGroups = $permissionRows
            ->groupBy('category')
            ->map(function ($permissions, $category) use ($overrides, $user, $targetIsOwner, $actorCanOverridePermissions): array {
                return [
                    'category' => (string) $category,
                    'permissions' => $permissions->map(function ($permission) use ($overrides, $user, $targetIsOwner, $actorCanOverridePermissions): array {
                        $override = $overrides->get($permission->id);

                        return [
                            'slug' => (string) $permission->slug,
                            'target_user_id' => (int) $user->id,
                            'target_is_owner' => $targetIsOwner,
                            'actor_can_override' => $actorCanOverridePermissions,
                            'name' => (string) $permission->name,
                            'description' => $permission->description,
                            'state' => $override === null ? 'inherit' : ((bool) $override->allowed ? 'allow' : 'deny'),
                            'reason' => $override->reason ?? '',
                        ];
                    })->values(),
                ];
            })
            ->values();

        $manualBadges = collect();
        $manualBadgeUnlocks = collect();

        return view('admin.users.show', [
            'targetUser' => $user,
            'roleCards' => $roleCards,
            'permissionGroups' => $permissionGroups,
            'overrides' => $overrides,
            'isOwner' => $targetIsOwner,
            'actorIsOwner' => $actorIsOwner,
            'actorCanAssignRoles' => $actorCanAssignRoles,
            'actorCanOverridePermissions' => $actorCanOverridePermissions,
            'actorCanManageBadges' => false,
            'manualBadges' => $manualBadges,
            'manualBadgeUnlocks' => $manualBadgeUnlocks,
            'profile' => null,
            'statistics' => app(AccountDeletionService::class)->statistics((int) $user->id),
            'actorCanEditProfile' => $permissions->can($actor, 'users.edit_profile'),
            'actorCanVerifyEmail' => $permissions->can($actor, 'users.verify_email'),
            'actorCanSuspend' => $permissions->can($actor, 'users.suspend'),
            'actorCanUnsuspend' => $permissions->can($actor, 'users.unsuspend'),
            'actorCanDelete' => $permissions->can($actor, 'users.delete_account'),
        ]);
    }

    public function updateAccount(Request $request, User $user, PermissionService $permissions): RedirectResponse
    {
        abort_unless($permissions->can($request->user(), 'users.edit_profile'), 403);
        abort_if($permissions->isOwner($user) && ! $permissions->isOwner($request->user()), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        if ($permissions->isOwner($user) && strcasecmp($user->email, $data['email']) !== 0) {
            return back()->withErrors(['email' => __('global.owner_email_locked')])->withInput();
        }

        $changedFields = [];
        if ($user->name !== $data['name']) {
            $changedFields[] = 'name';
        }
        if (strcasecmp($user->email, $data['email']) !== 0) {
            $changedFields[] = 'email';
        }

        $emailChanged = in_array('email', $changedFields, true);

        DB::table('users')->where('id', $user->id)->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
            'updated_at' => now(),
        ]);

        $this->auditAccount($request->user(), (int) $user->id, 'account_updated', null, [
            'changed_fields' => $changedFields,
            'email_verification_reset' => $emailChanged,
        ]);

        return back()->with('ui_toast', __('admin.users.status_account_updated'));
    }

    public function verifyEmail(Request $request, User $user, PermissionService $permissions): RedirectResponse
    {
        abort_unless($permissions->can($request->user(), 'users.verify_email'), 403);
        abort_if($user->account_status === 'deleted', 404);

        if ($user->email_verified_at) {
            return back()->with('ui_toast', __('admin.users.email_already_verified'));
        }

        DB::table('users')->where('id', $user->id)->update([
            'email_verified_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAccount(
            $request->user(),
            (int) $user->id,
            'email_manually_verified',
            ['email_verified_at' => null],
            ['email_verified_at' => now()->toIso8601String()],
        );

        return back()->with('ui_toast', __('admin.users.email_manually_verified'));
    }

    public function suspend(Request $request, User $user, PermissionService $permissions): RedirectResponse
    {
        abort_unless($permissions->can($request->user(),'users.suspend'),403); abort_if($permissions->isOwner($user) || $request->user()->is($user),403);
        $data=$request->validate(['reason'=>['required','string','max:1000'],'suspended_until'=>['nullable','date','after:now']]);
        $old=['account_status'=>$user->account_status,'suspended_until'=>$user->suspended_until?->toIso8601String()];
        DB::table('users')->where('id',$user->id)->update(['account_status'=>'suspended','suspension_reason'=>$data['reason'],'suspended_until'=>$data['suspended_until'] ?? null,'updated_at'=>now()]); DB::table('sessions')->where('user_id',$user->id)->delete();
        $this->auditAccount($request->user(), (int) $user->id, 'account_suspended', $old, [
            'account_status' => 'suspended',
            'suspended_until' => $data['suspended_until'] ?? null,
            'reason_recorded_on_account' => true,
        ]);
        return back()->with('ui_toast',__('admin.users.status_suspended'));
    }

    public function unsuspend(Request $request, User $user, PermissionService $permissions): RedirectResponse
    {
        abort_unless($permissions->can($request->user(),'users.unsuspend'),403); abort_if($permissions->isOwner($user),403);
        DB::table('users')->where('id',$user->id)->where('account_status','suspended')->update(['account_status'=>'active','suspension_reason'=>null,'suspended_until'=>null,'updated_at'=>now()]);
        $this->auditAccount($request->user(),(int)$user->id,'account_unsuspended',['account_status'=>'suspended'],['account_status'=>'active']);
        return back()->with('ui_toast',__('admin.users.status_unsuspended'));
    }

    public function deleteAccount(Request $request, User $user, PermissionService $permissions, AccountDeletionService $deletion): RedirectResponse
    {
        abort_unless($permissions->can($request->user(),'users.delete_account'),403); abort_if($permissions->isOwner($user) || $request->user()->is($user),403);
        $data=$request->validate(['confirmation'=>['required','string']]); if ($data['confirmation'] !== $user->name) return back()->withErrors(['confirmation'=>__('admin.users.delete_confirmation_mismatch')]);
        $actor=$request->user(); $targetId=(int)$user->id; $old=['account_status'=>$user->account_status]; $deletion->finalize($targetId);
        $this->auditAccount($actor,$targetId,'account_deleted',$old,['account_status'=>'deleted']);
        return redirect()->route('admin.users.index')->with('ui_toast',__('admin.users.status_deleted'));
    }

    private function auditAccount(User $actor, int $targetId, string $action, ?array $oldValues, ?array $newValues): void
    {
        DB::table('audit_logs')->insert(['user_id'=>$actor->id,'entity_type'=>'user_account','entity_id'=>$targetId,'action'=>$action,'source'=>'admin','old_values'=>$oldValues ? json_encode($oldValues,JSON_UNESCAPED_UNICODE) : null,'new_values'=>$newValues ? json_encode($newValues,JSON_UNESCAPED_UNICODE) : null,'internal_comment'=>null,'created_at'=>now()]);
    }

    public function assignRole(Request $request, User $user, RolePermissionService $access): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'string', 'max:100'],
        ]);

        try {
            $access->assignRole($request->user(), $user, $data['role']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['access' => $e->getMessage()]);
        }

        return back()->with('ui_toast', __('admin.users.status_role_assigned'));
    }

    public function removeRole(Request $request, User $user, RolePermissionService $access): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'string', 'max:100'],
        ]);

        try {
            $access->removeRole($request->user(), $user, $data['role']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['access' => $e->getMessage()]);
        }

        return back()->with('ui_toast', __('admin.users.status_role_removed'));
    }

    public function setOverride(Request $request, User $user, RolePermissionService $access): RedirectResponse
    {
        $data = $request->validate([
            'permission' => ['required', 'string', 'max:150'],
            'state' => ['required', 'in:inherit,allow,deny'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            if ($data['state'] === 'inherit') {
                $access->clearUserOverride($request->user(), $user, $data['permission']);
            } else {
                $access->setUserOverride(
                    $request->user(),
                    $user,
                    $data['permission'],
                    $data['state'] === 'allow',
                    $data['reason'] ?? null,
                );
            }
        } catch (RuntimeException $e) {
            return back()->withErrors(['access' => $e->getMessage()]);
        }

        return back()->with('ui_toast', __('admin.users.status_override_saved'));
    }
}
