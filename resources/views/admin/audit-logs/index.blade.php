<x-layouts::app :title="__('admin.audit_logs.title')">
    @php
        $identifierLabel = static function (string $group, string $value): string {
            $key = 'admin.audit_logs.'.$group.'.'.$value;
            $translation = __($key);

            return $translation === $key ? $value : $translation;
        };
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('admin.audit_logs.title') }}</flux:heading>
                <flux:text class="mt-1">{{ __('admin.audit_logs.intro') }}</flux:text>
            </div>
            <a href="{{ route('admin.index') }}" class="text-sm underline">{{ __('admin.audit_logs.back_admin') }}</a>
        </div>

        <form method="GET" class="grid gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700 md:grid-cols-4">
            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('admin.audit_logs.entity_type') }}</span>
                <select name="entity_type" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-neutral-900">
                    <option value="">{{ __('admin.audit_logs.all') }}</option>
                    @foreach ($entityTypes as $value)
                        <option value="{{ $value }}" @selected($entityType === $value)>{{ $identifierLabel('entity_types', $value) }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('admin.audit_logs.action') }}</span>
                <select name="action" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-neutral-900">
                    <option value="">{{ __('admin.audit_logs.all') }}</option>
                    @foreach ($actions as $value)
                        <option value="{{ $value }}" @selected($action === $value)>{{ $identifierLabel('actions', $value) }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block text-sm">
                <span class="mb-1 block font-medium">{{ __('admin.audit_logs.source') }}</span>
                <select name="source" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-neutral-900">
                    <option value="">{{ __('admin.audit_logs.all') }}</option>
                    @foreach ($sources as $value)
                        <option value="{{ $value }}" @selected($source === $value)>{{ $identifierLabel('sources', $value) }}</option>
                    @endforeach
                </select>
            </label>

            <div class="flex items-end gap-2">
                <button type="submit" class="rounded-lg bg-neutral-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-neutral-900">{{ __('admin.audit_logs.filter') }}</button>
                <a href="{{ route('admin.audit-logs.index') }}" class="rounded-lg border border-neutral-300 px-4 py-2 text-sm dark:border-neutral-700">{{ __('admin.audit_logs.reset') }}</a>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                @forelse ($logs as $log)
                    @php
                        $oldValues = $log->old_values
                            ? json_encode(json_decode($log->old_values, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                            : null;
                        $newValues = $log->new_values
                            ? json_encode(json_decode($log->new_values, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                            : null;
                    @endphp

                    <details class="group bg-white dark:bg-neutral-900">
                        <summary class="cursor-pointer list-none px-4 py-2.5 hover:bg-neutral-50 dark:hover:bg-neutral-800/60">
                            <div class="grid gap-2 text-sm lg:grid-cols-[150px_180px_150px_minmax(220px,1fr)_220px] lg:items-center">
                                <div class="whitespace-nowrap text-neutral-500">{{ \App\Support\LocalTime::parse($log->created_at)->format(__('admin.audit_logs.date_format')) }}</div>
                                <div class="truncate" title="{{ $log->actor_name ?: __('admin.audit_logs.system_unknown') }}{{ $log->actor_email ? ' · '.$log->actor_email : '' }}">
                                    {{ $log->actor_name ?: __('admin.audit_logs.system_unknown') }}
                                </div>
                                <div class="truncate text-neutral-500">{{ $identifierLabel('sources', $log->source) }}</div>
                                <div class="truncate font-semibold" title="{{ $identifierLabel('actions', $log->action) }}">{{ $identifierLabel('actions', $log->action) }}</div>
                                <div class="truncate text-neutral-500">{{ $identifierLabel('entity_types', $log->entity_type) }} #{{ $log->entity_id }}</div>
                            </div>
                        </summary>

                        <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
                            @if ($log->internal_comment)
                                <div class="mb-4 rounded-lg p-3 text-sm" style="background-color:#262626;color:#f5f5f5;">
                                    {{ $log->internal_comment }}
                                </div>
                            @endif

                            <div class="grid gap-4 lg:grid-cols-2">
                                <div>
                                    <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('admin.audit_logs.before') }}</div>
                                    <pre class="min-h-20 overflow-x-auto rounded-lg p-3 text-xs whitespace-pre-wrap" style="background-color:#171717;color:#f5f5f5;">{{ $oldValues ?? 'null' }}</pre>
                                </div>
                                <div>
                                    <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('admin.audit_logs.after') }}</div>
                                    <pre class="min-h-20 overflow-x-auto rounded-lg p-3 text-xs whitespace-pre-wrap" style="background-color:#171717;color:#f5f5f5;">{{ $newValues ?? 'null' }}</pre>
                                </div>
                            </div>
                        </div>
                    </details>
                @empty
                    <div class="p-8 text-center text-sm text-neutral-500">{{ __('admin.audit_logs.empty') }}</div>
                @endforelse
            </div>
        </div>

        @if ($logs->hasPages())
            <div>{{ $logs->links() }}</div>
        @endif
    </div>
</x-layouts::app>
