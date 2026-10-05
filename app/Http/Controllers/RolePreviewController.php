<?php

namespace App\Http\Controllers;

use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class RolePreviewController extends Controller
{
    public function update(Request $request, PermissionService $permissions): RedirectResponse
    {
        $user = $request->user();
        abort_unless($permissions->isOwner($user), 403);

        $data = $request->validate([
            'role' => ['required', 'string', 'max:100'],
        ]);

        if ($data['role'] === 'default') {
            $request->session()->forget(PermissionService::ROLE_PREVIEW_SESSION_KEY);

            return back()->with('ui_toast', __('admin.access_control.preview_disabled'));
        }

        $role = DB::table('roles')
            ->where('slug', $data['role'])
            ->where('is_active', true)
            ->first(['slug', 'name']);

        abort_unless($role, 422, __('admin.access_control.preview_invalid'));

        $request->session()->put(PermissionService::ROLE_PREVIEW_SESSION_KEY, $data['role']);

        $translationKey = 'admin.users.role_names.'.$role->slug;
        $roleName = Lang::has($translationKey) ? __($translationKey) : $role->name;

        return back()->with('ui_toast', __('admin.access_control.preview_enabled', ['role' => $roleName]));
    }
}
