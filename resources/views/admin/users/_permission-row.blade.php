<form method="POST" action="{{ route('admin.users.permissions.override', $permissionRow['target_user_id']) }}" class="grid gap-3 p-4 lg:grid-cols-[minmax(260px,1fr)_180px_minmax(220px,1fr)_auto] lg:items-end">
    @csrf
    @method('PUT')
    <input type="hidden" name="permission" value="{{ $permissionRow['slug'] }}">

    <div>
        <div class="font-medium">{{ app()->getLocale() === 'de' ? $permissionRow['name'] : $permissionRow['slug'] }}</div>
        <div class="text-xs text-neutral-500">{{ $permissionRow['slug'] }}</div>
        @if ($permissionRow['description'] && app()->getLocale() === 'de')
            <div class="mt-1 text-sm text-neutral-500">{{ $permissionRow['description'] }}</div>
        @endif
    </div>

    <label class="block text-sm">
        <span class="mb-1 block text-neutral-500">{{ __('admin.users.override') }}</span>
        <select name="state" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-neutral-900" @disabled($permissionRow['target_is_owner'] || ! $permissionRow['actor_can_override'])>
            <option value="inherit" @selected($permissionRow['state'] === 'inherit')>{{ __('admin.users.inherit') }}</option>
            <option value="allow" @selected($permissionRow['state'] === 'allow')>{{ __('admin.users.allow') }}</option>
            <option value="deny" @selected($permissionRow['state'] === 'deny')>{{ __('admin.users.deny') }}</option>
        </select>
    </label>

    <label class="block text-sm">
        <span class="mb-1 block text-neutral-500">{{ __('admin.users.reason') }}</span>
        <input type="text" name="reason" value="{{ $permissionRow['reason'] }}" maxlength="500" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-neutral-900" placeholder="{{ __('admin.users.optional') }}" @disabled($permissionRow['target_is_owner'] || ! $permissionRow['actor_can_override'])>
    </label>

    @if (! $permissionRow['target_is_owner'] && $permissionRow['actor_can_override'])
        <button type="submit" class="rounded-lg border border-neutral-300 px-3 py-2 text-sm font-medium hover:bg-neutral-50 dark:border-neutral-600 dark:hover:bg-neutral-800">{{ __('admin.users.save') }}</button>
    @endif
</form>
