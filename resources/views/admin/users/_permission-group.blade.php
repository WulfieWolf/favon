<div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
    <div class="border-b border-neutral-200 bg-neutral-50 px-4 py-3 font-semibold dark:border-neutral-700 dark:bg-neutral-900/50">
        {{ __('admin.users.categories.'.$permissionGroup['category']) }}
    </div>
    <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
        @each('admin.users._permission-row', $permissionGroup['permissions'], 'permissionRow')
    </div>
</div>
