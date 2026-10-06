<x-layouts::app :title="__('admin.title')">
    <div class="mx-auto w-full max-w-6xl space-y-6 px-4 py-6">
        <div>
            <flux:heading size="xl">{{ __('admin.title') }}</flux:heading>
            <flux:text class="mt-1">{{ __('admin.overview.intro') }}</flux:text>
        </div>

        @php($card = 'block rounded-xl border border-neutral-200 p-5 transition hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800')
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @if ($canViewUsers)
                <a href="{{ route('admin.users.index') }}" class="{{ $card }}"><div class="font-semibold">{{ __('admin.overview.users_rights') }}</div><div class="mt-1 text-sm text-neutral-500">{{ $userCount }} {{ __('admin.overview.users') }}</div></a>
            @endif
            @if ($canMergePlaces)
                <a href="{{ route('admin.place-merges.index') }}" class="{{ $card }}"><div class="font-semibold">{{ __('admin.overview.merge_places') }}</div><div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.merge_places_help') }}</div></a>
            @endif
            @if ($canManageSupport)
                <a href="{{ route('admin.support.index') }}" class="{{ $card }}"><div class="font-semibold">{{ __('admin.overview.support') }}</div><div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.support_help') }}</div></a>
            @endif
            @if ($canSendSystemNotifications)
                <a href="{{ route('admin.notifications.create') }}" class="{{ $card }}"><div class="font-semibold">{{ __('admin.overview.system_notification') }}</div><div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.system_notification_help') }}</div></a>
            @endif
            @if ($canViewStatistics)
                <a href="{{ route('admin.statistics.index') }}" class="{{ $card }}"><div class="font-semibold">{{ __('admin.overview.statistics') }}</div><div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.statistics_help') }}</div></a>
            @endif
            @if ($canViewAuditLogs)
                <a href="{{ route('admin.audit-logs.index') }}" class="{{ $card }}"><div class="font-semibold">{{ __('admin.overview.audit_log') }}</div><div class="mt-1 text-sm text-neutral-500">{{ $auditLogCount }}</div></a>
            @endif
            @if ($canUseSystemTools)
                <a href="{{ route('admin.system.index') }}" class="{{ $card }}"><div class="font-semibold">{{ __('admin.overview.system_tools') }}</div><div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.system_tools_help') }}</div></a>
            @endif
        </div>
    </div>
</x-layouts::app>
