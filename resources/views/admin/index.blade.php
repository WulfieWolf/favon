<x-layouts::app :title="__('admin.title')">
    <div class="mx-auto w-full max-w-6xl space-y-6 px-4 py-6">
        <div>
            <flux:heading size="xl">{{ __('admin.title') }}</flux:heading>
            <flux:text class="mt-1">Favon-Verwaltung im Übergangsstand nach dem Camperwolf-Cleanup.</flux:text>
        </div>

        @php($card = 'block rounded-xl border border-neutral-200 p-5 transition hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800')
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @if ($canViewUsers)
                <a href="{{ route('admin.users.index') }}" class="{{ $card }}"><div class="font-semibold">Benutzer & Rechte</div><div class="mt-1 text-sm text-neutral-500">{{ $userCount }} Konten</div></a>
            @endif
            @if ($canMergePlaces)
                <a href="{{ route('admin.place-merges.index') }}" class="{{ $card }}"><div class="font-semibold">Plätze zusammenführen</div><div class="mt-1 text-sm text-neutral-500">Dubletten bearbeiten</div></a>
            @endif
            @if ($canManageSupport)
                <a href="{{ route('admin.support.index') }}" class="{{ $card }}"><div class="font-semibold">Support</div><div class="mt-1 text-sm text-neutral-500">Tickets und Takedown-Anfragen</div></a>
            @endif
            @if ($canSendSystemNotifications)
                <a href="{{ route('admin.notifications.create') }}" class="{{ $card }}"><div class="font-semibold">Systemnachricht</div><div class="mt-1 text-sm text-neutral-500">Interne Benachrichtigungen</div></a>
            @endif
            @if ($canViewStatistics)
                <a href="{{ route('admin.statistics.index') }}" class="{{ $card }}"><div class="font-semibold">Statistik</div><div class="mt-1 text-sm text-neutral-500">Nutzungs-Insights</div></a>
            @endif
            @if ($canViewAuditLogs)
                <a href="{{ route('admin.audit-logs.index') }}" class="{{ $card }}"><div class="font-semibold">Audit-Log</div><div class="mt-1 text-sm text-neutral-500">{{ $auditLogCount }} Einträge</div></a>
            @endif
            @if ($canUseSystemTools)
                <a href="{{ route('admin.system.index') }}" class="{{ $card }}"><div class="font-semibold">System</div><div class="mt-1 text-sm text-neutral-500">Zugriff und Diagnose</div></a>
            @endif
        </div>
    </div>
</x-layouts::app>
