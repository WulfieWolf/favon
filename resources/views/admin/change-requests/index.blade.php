<x-layouts::app :title="__('admin.change_requests.title')">
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('admin.change_requests.title') }}</flux:heading>
                <flux:text class="mt-1">{{ __('admin.change_requests.intro') }}</flux:text>
            </div>
            <a href="{{ route('admin.index') }}" class="text-sm underline">{{ __('admin.change_requests.back_admin') }}</a>
        </div>

        @if ($status === 'pending')
            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('admin.change-requests.index', ['status' => 'pending', 'queue' => 'normal']) }}"
                    class="rounded-lg border px-3 py-2 text-sm font-medium {{ $queue === 'normal' ? 'border-neutral-900 bg-neutral-900 text-white dark:border-neutral-100 dark:bg-neutral-100 dark:text-neutral-900' : 'border-neutral-300 hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800' }}"
                >{{ __('admin.change_requests.queue_normal') }}</a>
                <a
                    href="{{ route('admin.change-requests.index', ['status' => 'pending', 'queue' => 'quarantine']) }}"
                    class="rounded-lg border px-3 py-2 text-sm font-medium {{ $queue === 'quarantine' ? 'border-amber-600 bg-amber-600 text-white dark:border-amber-400 dark:bg-amber-400 dark:text-neutral-950' : 'border-neutral-300 hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800' }}"
                >{{ __('admin.change_requests.queue_quarantine') }}</a>
            </div>
        @endif

        <div class="flex flex-wrap gap-2">
            @foreach (['pending', 'approved', 'rejected'] as $value)
                <a
                    href="{{ route('admin.change-requests.index', ['status' => $value, 'queue' => $value === 'pending' ? $queue : null]) }}"
                    class="rounded-lg border px-3 py-2 text-sm font-medium {{ $status === $value ? 'border-neutral-900 bg-neutral-900 text-white dark:border-neutral-100 dark:bg-neutral-100 dark:text-neutral-900' : 'border-neutral-300 hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800' }}"
                >
                    {{ __('admin.change_requests.statuses.'.$value) }}
                </a>
            @endforeach
        </div>

        <div class="space-y-5">
            @forelse ($requests->getCollection()->groupBy('place_id') as $placeId => $placeGroups)
                <section class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <div class="flex items-center justify-between gap-3 bg-neutral-50 px-4 py-3 dark:bg-neutral-900/50">
                        <div>
                            <div class="font-semibold">{{ $placeGroups->first()->place_name }}</div>
                            <div class="text-xs text-neutral-500">#{{ $placeId }}</div>
                        </div>
                        <span class="rounded-full bg-neutral-100 px-2 py-1 text-xs text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                            {{ trans_choice('admin.change_requests.groups_count', $placeGroups->count(), ['count' => $placeGroups->count()]) }}
                        </span>
                    </div>

                    <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @foreach ($placeGroups as $group)
                            @php
                                $fieldLabels = $group->fields->map(function ($field) {
                                    $key = 'admin.change_requests.fields.'.$field->target_table.'.'.$field->target_field;
                                    $translated = __($key);

                                    return $translated === $key
                                        ? $field->target_table.'.'.$field->target_field
                                        : $translated;
                                })->unique()->values();

                                $operationLabels = $group->operations
                                    ->map(fn ($operation) => __('admin.change_requests.operations.'.$operation))
                                    ->unique()
                                    ->implode(', ');
                            @endphp

                            <div class="grid gap-3 px-4 py-3 lg:grid-cols-[minmax(0,1fr)_220px_190px_auto] lg:items-center">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold">#{{ $group->anchor_id }}</span>
                                        <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs dark:bg-neutral-800">{{ $operationLabels }}</span>
                                        <span class="text-sm text-neutral-500">{{ trans_choice('admin.change_requests.fields_count', $group->field_count, ['count' => $group->field_count]) }}</span>
                                    </div>
                                    <div class="mt-1 truncate text-sm text-neutral-600 dark:text-neutral-300" title="{{ $fieldLabels->implode(', ') }}">
                                        {{ $fieldLabels->take(3)->implode(', ') }}
                                        @if ($fieldLabels->count() > 3)
                                            · {{ __('admin.change_requests.more_fields', ['count' => $fieldLabels->count() - 3]) }}
                                        @endif
                                    </div>
                                    @if ($group->tombstone_warning)
                                        <div class="mt-2 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-950 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100">
                                            {{ __('admin.change_requests.tombstone_warning', [
                                                'distance' => $group->tombstone_warning['match']['distance_m'],
                                                'type' => $group->tombstone_warning['match']['place_type_label'],
                                                'reason' => __('admin.place_deletion.reasons.'.($group->tombstone_warning['match']['deletion_reason'] ?: 'other')),
                                            ]) }}
                                        </div>
                                    @endif
                                </div>

                                <div class="text-sm">
                                    <div>{{ $group->submitter_name ?: __('admin.change_requests.unknown') }}</div>
                                    @if ($group->submitter_email)
                                        <div class="truncate text-xs text-neutral-500">{{ $group->submitter_email }}</div>
                                    @endif
                                </div>

                                <div class="text-sm text-neutral-500">
                                    {{ \App\Support\LocalTime::parse($group->submitted_at)->format(__('admin.change_requests.date_format')) }}
                                    @if ($status !== 'pending' && $group->reviewer_name)
                                        <div class="text-xs">{{ __('admin.change_requests.reviewed_by') }}: {{ $group->reviewer_name }}</div>
                                    @endif
                                </div>

                                <a href="{{ route('admin.change-requests.show', $group->anchor_id) }}" class="w-fit rounded-lg border border-neutral-300 px-3 py-2 text-sm font-medium hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800">
                                    {{ __('admin.change_requests.review') }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="rounded-xl border border-dashed border-neutral-300 p-8 text-center text-sm text-neutral-500 dark:border-neutral-700">
                    {{ __('admin.change_requests.empty') }}
                </div>
            @endforelse
        </div>

        {{ $requests->links() }}
    </div>
</x-layouts::app>
