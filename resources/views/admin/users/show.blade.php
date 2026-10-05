<x-layouts::app :title="__('admin.users.rights_title')">
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <div class="grid h-32 w-32 place-items-center rounded-xl bg-neutral-100 text-3xl font-semibold text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">{{ mb_strtoupper(mb_substr($targetUser->name, 0, 1)) }}</div>
                <div>
                    <flux:heading size="xl">{{ $targetUser->name }}</flux:heading>
                    <flux:text class="mt-1">{{ $targetUser->email }}</flux:text>
                </div>
            </div>
            <a href="{{ route('admin.users.index') }}" class="text-sm underline">{{ __('admin.users.back_users') }}</a>
        </div>
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700"><div class="text-xs text-neutral-500">{{ __('admin.users.user_id') }}</div><div class="mt-1 font-semibold">{{ $targetUser->id }}</div></div>
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700"><div class="text-xs text-neutral-500">{{ __('admin.users.status') }}</div><div class="mt-1 font-semibold">{{ __('admin.users.account_statuses.'.$targetUser->account_status) }}</div></div>
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="text-xs text-neutral-500">{{ __('admin.users.verified_label') }}</div>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <div class="font-semibold">{{ $targetUser->email_verified_at ? __('admin.users.yes') : __('admin.users.no') }}</div>
                    @if (! $targetUser->email_verified_at && $actorCanVerifyEmail && $targetUser->account_status !== 'deleted')
                        <form method="POST" action="{{ route('admin.users.email.verify', $targetUser) }}" onsubmit="return confirm(@js(__('admin.users.verify_email_confirmation', ['email' => $targetUser->email])))">
                            @csrf
                            <button type="submit" class="rounded-lg border border-amber-300 px-2 py-1 text-xs font-medium text-amber-800 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-200 dark:hover:bg-amber-950/30">
                                {{ __('admin.users.verify_email_manually') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700"><div class="text-xs text-neutral-500">{{ __('admin.users.last_seen') }}</div><div class="mt-1 font-semibold">{{ $targetUser->last_seen_at ? \App\Support\LocalTime::format($targetUser->last_seen_at, app()->getLocale()==='de'?'d.m.Y H:i':'Y-m-d H:i') : __('admin.users.none') }}</div></div>
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700"><div class="text-xs text-neutral-500">{{ __('admin.users.locale') }}</div><div class="mt-1 font-semibold">{{ strtoupper($targetUser->locale ?? 'de') }}</div></div>
        </section>

        @if ($actorCanEditProfile && $targetUser->account_status !== 'deleted')
        <section class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700"><flux:heading size="lg">{{ __('admin.users.account_data') }}</flux:heading><form method="POST" action="{{ route('admin.users.account.update',$targetUser) }}" class="mt-4 grid gap-3 md:grid-cols-3">@csrf @method('PUT')<label class="text-sm">{{ __('admin.users.name') }}<input name="name" value="{{ old('name',$targetUser->name) }}" class="mt-1 w-full rounded-lg border px-3 py-2 dark:bg-neutral-900"></label><label class="text-sm">{{ __('admin.users.email') }}<input name="email" value="{{ old('email',$targetUser->email) }}" class="mt-1 w-full rounded-lg border px-3 py-2 dark:bg-neutral-900"></label><label class="text-sm">{{ __('admin.users.public_alias') }}<input name="public_alias" value="{{ old('public_alias',$profile?->public_alias) }}" class="mt-1 w-full rounded-lg border px-3 py-2 dark:bg-neutral-900"></label><div class="md:col-span-3 text-xs text-neutral-500">{{ __('admin.users.email_change_help') }}</div><button class="w-fit rounded-lg border px-3 py-2">{{ __('admin.users.save_account') }}</button></form></section>
        @endif

        @if (! $isOwner && auth()->id() !== $targetUser->id && $targetUser->account_status !== 'deleted')
        <section class="space-y-4 rounded-xl border border-amber-300 p-4 dark:border-amber-700"><flux:heading size="lg">{{ __('admin.users.account_actions') }}</flux:heading>
            @if ($targetUser->account_status === 'suspended' && $actorCanUnsuspend)<div><div class="text-sm">{{ $targetUser->suspension_reason }}</div><form method="POST" action="{{ route('admin.users.unsuspend',$targetUser) }}" class="mt-2">@csrf @method('DELETE')<button class="rounded-lg border px-3 py-2">{{ __('admin.users.unsuspend') }}</button></form></div>
            @elseif ($actorCanSuspend)<form method="POST" action="{{ route('admin.users.suspend',$targetUser) }}" class="grid gap-3 md:grid-cols-2">@csrf<label class="text-sm md:col-span-2">{{ __('admin.users.suspension_reason') }}<textarea required name="reason" maxlength="1000" class="mt-1 w-full rounded-lg border px-3 py-2 dark:bg-neutral-900"></textarea></label><label class="text-sm">{{ __('admin.users.suspended_until') }}<input type="datetime-local" name="suspended_until" class="mt-1 w-full rounded-lg border px-3 py-2 dark:bg-neutral-900"></label><div class="md:self-end"><button class="rounded-lg border border-amber-400 px-3 py-2">{{ __('admin.users.suspend') }}</button></div></form>@endif
            @if ($actorCanDelete)<form method="POST" action="{{ route('admin.users.account.delete',$targetUser) }}" class="border-t border-red-200 pt-4 dark:border-red-900">@csrf @method('DELETE')<div class="font-semibold text-red-700 dark:text-red-300">{{ __('admin.users.delete_account') }}</div><div class="mt-1 text-sm">{{ __('admin.users.delete_help',['name'=>$targetUser->name]) }}</div><input required name="confirmation" class="mt-3 rounded-lg border px-3 py-2 dark:bg-neutral-900" autocomplete="off"><button class="ml-2 rounded-lg border border-red-400 px-3 py-2 text-red-700 dark:text-red-300">{{ __('admin.users.delete_now') }}</button></form>@endif
        </section>
        @endif

@if ($isOwner)
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950/30">
                <div class="font-semibold text-amber-900 dark:text-amber-100">{{ __('admin.overview.owner_title') }}</div>
                <div class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                    {{ __('admin.users.owner_help') }}
                </div>
            </div>
        @endif

        <section class="space-y-3">
            <div>
                <flux:heading size="lg">{{ __('admin.users.roles') }}</flux:heading>
                <flux:text class="mt-1">{{ __('admin.users.roles_help') }}</flux:text>
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                @each('admin.users._role-card', $roleCards, 'roleCard')
            </div>
        </section>

        <details class="group rounded-xl border border-neutral-200 dark:border-neutral-700">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-4">
                <div>
                    <div class="font-semibold">{{ __('admin.users.overrides') }}</div>
                    <div class="mt-1 text-sm text-neutral-500">{{ trans_choice('admin.users.overrides_summary', $overrides->count(), ['count' => $overrides->count()]) }}</div>
                </div>
                <span class="text-sm text-neutral-400 group-open:rotate-180">⌄</span>
            </summary>
            <div class="space-y-3 border-t border-neutral-200 p-4 dark:border-neutral-700">
                <flux:text>{{ __('admin.users.overrides_help') }}</flux:text>

            @if ($isOwner)
                <div class="rounded-xl border border-dashed border-neutral-300 p-4 text-sm text-neutral-500 dark:border-neutral-700">
                    {{ __('admin.users.owner_overrides') }}
                </div>
            @elseif (! $actorCanOverridePermissions)
                <div class="rounded-xl border border-dashed border-neutral-300 p-4 text-sm text-neutral-500 dark:border-neutral-700">
                    {{ __('admin.users.view_only_overrides') }}
                </div>
            @endif

            <div class="space-y-5">
                @each('admin.users._permission-group', $permissionGroups, 'permissionGroup')
            </div>
            </div>
        </details>
    </div>
</x-layouts::app>
