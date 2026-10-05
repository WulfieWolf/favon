<div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
    <div class="flex items-center justify-between gap-3">
        <div>
            <div class="font-semibold">{{ __('admin.users.role_names.'.$roleCard['slug']) }}</div>
            <div class="text-xs text-neutral-500">{{ $roleCard['slug'] }}</div>
        </div>
        <span class="rounded-full px-2 py-1 text-xs {{ $roleCard['assigned'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300' }}">
            {{ $roleCard['assigned'] ? __('admin.users.active') : __('admin.users.inactive') }}
        </span>
    </div>

    @if ($roleCard['can_change'])
        @if ($roleCard['assigned'])
            <form method="POST" action="{{ route('admin.users.roles.remove', $roleCard['target_user_id']) }}" class="mt-4">
                @csrf
                @method('DELETE')
                <input type="hidden" name="role" value="{{ $roleCard['slug'] }}">
                <button type="submit" class="rounded-lg border border-neutral-300 px-3 py-2 text-sm font-medium hover:bg-neutral-50 dark:border-neutral-600 dark:hover:bg-neutral-800">{{ __('admin.users.remove_role') }}</button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.users.roles.assign', $roleCard['target_user_id']) }}" class="mt-4">
                @csrf
                <input type="hidden" name="role" value="{{ $roleCard['slug'] }}">
                <button type="submit" class="rounded-lg border border-neutral-300 px-3 py-2 text-sm font-medium hover:bg-neutral-50 dark:border-neutral-600 dark:hover:bg-neutral-800">{{ __('admin.users.assign_role') }}</button>
            </form>
        @endif
    @elseif ($roleCard['admin_owner_only'])
        <div class="mt-4 text-xs text-neutral-500">{{ __('admin.users.admin_owner_only') }}</div>
    @endif
</div>
