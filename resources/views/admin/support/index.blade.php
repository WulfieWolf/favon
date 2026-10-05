<x-layouts::app :title="__('admin.support.title')">
    @php
        $typeLabels = collect(__('support.types'))->mapWithKeys(fn ($value, $key) => [$key => $value['short']])->all();
        $statusLabels = __('admin.support.statuses');
        $priorityLabels = __('admin.support.priorities');
    @endphp
    @php $supportPermissions = app(\App\Services\PermissionService::class); @endphp
    <div class="mx-auto w-full max-w-7xl space-y-6 px-5 py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">{{ __('admin.support.title') }}</h1>
                <p class="mt-2 text-zinc-500">{{ __('admin.support.intro') }}</p>
            </div>
            @if ($supportPermissions->can(auth()->user(), 'support.manage_content'))
                <a href="{{ route('admin.support.content') }}" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-700">{{ __('admin.support.manage_content') }}</a>
            @endif
        </div>

        <form method="GET" class="grid gap-3 rounded-xl border border-zinc-200 p-4 md:grid-cols-5 dark:border-zinc-700">
            <input name="q" value="{{ $q }}" placeholder="{{ __('admin.support.search_placeholder') }}" class="h-10 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
            <select name="status" class="h-10 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">{{ __('admin.support.all_statuses') }}</option>
                @foreach ($statusLabels as $key => $label)<option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>@endforeach
            </select>
            <select name="type" class="h-10 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">{{ __('admin.support.all_types') }}</option>
                @foreach ($typeLabels as $key => $label)<option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>@endforeach
            </select>
            <label class="flex h-10 items-center gap-2 rounded-lg border border-zinc-300 px-3 text-sm dark:border-zinc-700">
                <input type="checkbox" name="privacy" value="1" @checked($privacy)>
                {{ __('admin.support.privacy.only') }}
            </label>
            <button class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('admin.support.filter') }}</button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900">
                    <tr><th class="p-3">#</th><th class="p-3">{{ __('admin.support.report') }}</th><th class="p-3">{{ __('admin.support.sender') }}</th><th class="p-3">{{ __('admin.support.type') }}</th><th class="p-3">{{ __('admin.support.status') }}</th><th class="p-3">{{ __('admin.support.priority') }}</th><th class="p-3">{{ __('admin.support.privacy.deadline') }}</th><th class="p-3">{{ __('admin.support.assigned') }}</th><th class="p-3">{{ __('admin.support.updated') }}</th></tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr class="border-t border-zinc-200 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900">
                            <td class="p-3 font-medium"><a class="hover:underline" href="{{ route('admin.support.show', $ticket->id) }}">{{ $ticket->id }}</a></td>
                            <td class="max-w-sm p-3"><a class="font-medium hover:underline" href="{{ route('admin.support.show', $ticket->id) }}">{{ $ticket->subject ?: \Illuminate\Support\Str::limit($ticket->description, 70) }}</a><div class="mt-1 text-xs text-zinc-500">{{ $ticket->context_key ?: __('admin.support.no_context') }}</div></td>
                            <td class="p-3">{{ $ticket->user_name ?: ($ticket->guest_name ?: __('admin.support.guest')) }}</td>
                            <td class="p-3">{{ $typeLabels[$ticket->type] ?? $ticket->type }}</td>
                            <td class="p-3">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</td>
                            <td class="p-3">{{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}</td>
                            <td class="p-3 whitespace-nowrap">
                                @if ($ticket->privacy_due_at)
                                    @php($privacyOverdue = !$ticket->privacy_completed_at && \App\Support\LocalTime::parse($ticket->privacy_due_at)->isPast())
                                    <span class="{{ $privacyOverdue ? 'font-semibold text-red-600 dark:text-red-400' : 'text-zinc-500' }}">
                                        {{ \App\Support\LocalTime::parse($ticket->privacy_due_at)->format(__('support.date_format')) }}
                                    </span>
                                    @if ($privacyOverdue)<div class="text-xs text-red-600 dark:text-red-400">{{ __('admin.support.privacy.overdue') }}</div>@endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="p-3">{{ $ticket->assigned_name ?: '—' }}</td>
                            <td class="p-3 whitespace-nowrap text-zinc-500">{{ \App\Support\LocalTime::parse($ticket->updated_at)->format(__('support.date_time_format')) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="p-8 text-center text-zinc-500">{{ __('admin.support.no_tickets') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $tickets->links() }}
    </div>
</x-layouts::app>
