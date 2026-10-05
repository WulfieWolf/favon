<x-layouts::app :title="__('admin.change_requests.review_title')">
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('admin.change_requests.review_title') }}</flux:heading>
                <flux:text class="mt-1">{{ $anchor->place_name }} · {{ __('admin.change_requests.request') }} #{{ $anchor->id }}</flux:text>
            </div>
            <a href="{{ route('admin.change-requests.index', ['status' => $anchor->status]) }}" class="text-sm underline">{{ __('admin.change_requests.back_queue') }}</a>
        </div>
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="text-xs uppercase tracking-wide text-neutral-500">{{ __('admin.change_requests.status') }}</div>
                <div class="mt-1 font-semibold">{{ __('admin.change_requests.statuses.'.$anchor->status) }}</div>
            </div>
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="text-xs uppercase tracking-wide text-neutral-500">{{ __('admin.change_requests.submitted_by') }}</div>
                <div class="mt-1 font-semibold">{{ $anchor->submitter_name ?: __('admin.change_requests.unknown') }}</div>
                @if ($anchor->submitter_email)
                    <div class="text-xs text-neutral-500">{{ $anchor->submitter_email }}</div>
                @endif
            </div>
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="text-xs uppercase tracking-wide text-neutral-500">{{ __('admin.change_requests.submitted') }}</div>
                <div class="mt-1 font-semibold">{{ \App\Support\LocalTime::parse($anchor->submitted_at)->format(__('admin.change_requests.date_format')) }}</div>
            </div>
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="text-xs uppercase tracking-wide text-neutral-500">{{ __('admin.change_requests.group') }}</div>
                <div class="mt-1 font-semibold">{{ $anchor->group_uuid ? trans_choice('admin.change_requests.fields_count', $group->count(), ['count' => $group->count()]) : __('admin.change_requests.single_request') }}</div>
                @if ($anchor->group_uuid)
                    <div class="mt-1 break-all text-xs text-neutral-500">{{ $anchor->group_uuid }}</div>
                @endif
            </div>
        </div>

        @if ($tombstoneWarning)
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-700 dark:bg-amber-950/30 dark:text-amber-100">
                <div class="font-semibold">{{ __('admin.change_requests.tombstone_title') }}</div>
                <div class="mt-1">
                    {{ __('admin.change_requests.tombstone_warning', [
                        'distance' => $tombstoneWarning['match']['distance_m'],
                        'type' => $tombstoneWarning['match']['place_type_label'],
                        'reason' => __('admin.place_deletion.reasons.'.($tombstoneWarning['match']['deletion_reason'] ?: 'other')),
                    ]) }}
                </div>
                @if (!empty($tombstoneWarning['match']['deletion_note']))
                    <div class="mt-2 text-xs opacity-80">{{ $tombstoneWarning['match']['deletion_note'] }}</div>
                @endif
            </div>
        @endif

        @if ($abuseFlags->isNotEmpty())
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-700 dark:bg-amber-950/30 dark:text-amber-100">
                <div class="font-semibold">{{ __('admin.change_requests.quarantine_title') }}</div>
                <div class="mt-1">{{ __('admin.change_requests.quarantine_help') }}</div>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($abuseFlags as $flag)
                        <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-900 dark:bg-amber-900/50 dark:text-amber-100">
                            {{ __('admin.change_requests.abuse_rules.'.$flag->rule_code) }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        @if (! $anchor->suggestable_field_active || ! $anchor->is_suggestable)
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/30 dark:text-amber-100">
                {{ __('admin.change_requests.field_disabled_warning') }}
            </div>
        @endif

        <section class="space-y-3">
            <div>
                <flux:heading size="lg">{{ __('admin.change_requests.proposed_changes') }}</flux:heading>
                <flux:text class="mt-1">{{ __('admin.change_requests.group_help') }}</flux:text>
            </div>

            <div class="divide-y divide-neutral-200 rounded-xl border border-neutral-200 px-4 dark:divide-neutral-700 dark:border-neutral-700">
                @foreach ($group as $item)
                    @php
                        $originalDecoded = $item->original_value === null ? null : json_decode($item->original_value, true);
                        $proposedDecoded = $item->proposed_value === null ? null : json_decode($item->proposed_value, true);
                        $original = $originalDecoded === null
                            ? null
                            : json_encode($originalDecoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $proposed = $proposedDecoded === null
                            ? null
                            : json_encode($proposedDecoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $isOpeningPeriodProposal = $item->target_table === 'opening_hours'
                            && $item->target_field === 'period_schedule'
                            && is_array($proposedDecoded);
                        $isPricePeriodProposal = $item->target_table === 'place_price_offers'
                            && $item->target_field === 'period_pricing'
                            && is_array($proposedDecoded);
                        $fieldKey = 'admin.change_requests.fields.'.$item->target_table.'.'.$item->target_field;
                        $fieldLabel = __($fieldKey);
                    @endphp

                    <div class="py-4">
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="font-semibold">#{{ $item->id }}</span>
                            <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-medium dark:bg-neutral-800">{{ __('admin.change_requests.operations.'.$item->operation) }}</span>
                            @if ($item->target_record_id)
                                <span class="text-neutral-500">{{ __('admin.change_requests.record') }} #{{ $item->target_record_id }}</span>
                            @endif
                            <span class="font-medium">{{ $fieldLabel === $fieldKey ? $item->target_table.'.'.$item->target_field : $fieldLabel }}</span>
                            <span class="ml-auto rounded-full bg-neutral-100 px-2 py-0.5 text-xs dark:bg-neutral-800">{{ __('admin.change_requests.statuses.'.$item->status) }}</span>
                        </div>

                        @if ($isOpeningPeriodProposal)
                            @php
                                $range = $proposedDecoded['range'] ?? [];
                                $rangeLabel = ! empty($range['is_year_round'])
                                    ? __('admin.change_requests.year_round')
                                    : __('admin.change_requests.date_range', [
                                        'start_day' => sprintf('%02d', $range['start_day'] ?? 0),
                                        'start_month' => sprintf('%02d', $range['start_month'] ?? 0),
                                        'end_day' => sprintf('%02d', $range['end_day'] ?? 0),
                                        'end_month' => sprintf('%02d', $range['end_month'] ?? 0),
                                    ]
                                    );
                                $affectedPeriods = $originalDecoded['affected_periods'] ?? [];
                                $schedule = $proposedDecoded['schedule'] ?? [];
                                $dayLabels = __('admin.change_requests.day_labels');
                            @endphp

                            <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950 dark:border-blue-900 dark:bg-blue-950/20 dark:text-blue-100">
                                <div class="font-semibold">{{ __('admin.change_requests.new_period', ['period' => $rangeLabel]) }}</div>
                                @if ($affectedPeriods === [])
                                    <div class="mt-1 text-xs opacity-80">{{ __('admin.change_requests.no_periods_overwritten') }}</div>
                                @else
                                    <div class="mt-2 text-xs font-semibold uppercase tracking-wide opacity-70">{{ __('admin.change_requests.impact_on_approval') }}</div>
                                    <div class="mt-1 space-y-1">
                                        @foreach ($affectedPeriods as $affected)
                                            <div>
                                                {{ $affected['label'] ?? __('admin.change_requests.existing_period') }}
                                                @if (empty($affected['remainders']))
                                                    → {{ __('admin.change_requests.fully_replaced') }}
                                                @else
                                                    → {{ __('admin.change_requests.remains_as', ['periods' => implode(__('admin.change_requests.range_joiner'), $affected['remainders'])]) }}
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                                @foreach ($dayLabels as $dayKey => $dayLabel)
                                    @php
                                        $day = $schedule[$dayKey] ?? ['mode' => 'unknown'];
                                        $mode = $day['mode'] ?? 'unknown';
                                        $display = match ($mode) {
                                            'closed' => __('admin.change_requests.opening_modes.closed'),
                                            '24h' => __('admin.change_requests.opening_modes.24h'),
                                            'appointment' => __('admin.change_requests.opening_modes.appointment'),
                                            'not_provided' => __('admin.change_requests.opening_modes.not_provided'),
                                            'hours' => trim(
                                                ($day['opens_at'] ?? '').'–'.($day['closes_at'] ?? '')
                                                .(
                                                    ! empty($day['opens_at_2']) && ! empty($day['closes_at_2'])
                                                        ? ' / '.$day['opens_at_2'].'–'.$day['closes_at_2']
                                                        : ''
                                                )
                                            ),
                                            default => __('admin.change_requests.opening_modes.unknown'),
                                        };
                                    @endphp
                                    <div class="rounded-lg border border-neutral-200 px-3 py-2 dark:border-neutral-700">
                                        <div class="text-xs text-neutral-500">{{ $dayLabel }}</div>
                                        <div class="mt-0.5 font-medium">{{ $display }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif ($isPricePeriodProposal)
                            @php
                                $range = $proposedDecoded['range'] ?? [];
                                $rangeLabel = ! empty($range['is_year_round'])
                                    ? __('admin.change_requests.year_round')
                                    : __('admin.change_requests.date_range', [
                                        'start_day' => sprintf('%02d', $range['start_day'] ?? 0),
                                        'start_month' => sprintf('%02d', $range['start_month'] ?? 0),
                                        'end_day' => sprintf('%02d', $range['end_day'] ?? 0),
                                        'end_month' => sprintf('%02d', $range['end_month'] ?? 0),
                                    ]
                                    );
                                $affectedPeriods = $originalDecoded['affected_periods'] ?? [];
                                $preview = $proposedDecoded['offer_preview'] ?? [];
                                $lines = $proposedDecoded['lines'] ?? [];
                            @endphp

                            <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950 dark:border-blue-900 dark:bg-blue-950/20 dark:text-blue-100">
                                <div class="font-semibold">{{ $preview['label'] ?? __('admin.change_requests.price_offer') }}</div>
                                @if (! empty($preview['descriptor']) && ($preview['descriptor'] ?? null) !== ($preview['label'] ?? null))
                                    <div class="mt-1 text-xs opacity-80">{{ $preview['descriptor'] }}</div>
                                @endif
                                <div class="mt-2 text-xs font-semibold uppercase tracking-wide opacity-70">{{ __('admin.change_requests.period') }}</div>
                                <div>{{ $rangeLabel }}</div>

                                @if ($affectedPeriods === [])
                                    <div class="mt-2 text-xs opacity-80">{{ __('admin.change_requests.no_price_periods_overwritten') }}</div>
                                @else
                                    <div class="mt-3 text-xs font-semibold uppercase tracking-wide opacity-70">{{ __('admin.change_requests.impact_on_approval') }}</div>
                                    <div class="mt-1 space-y-1">
                                        @foreach ($affectedPeriods as $affected)
                                            <div>
                                                {{ $affected['label'] ?? __('admin.change_requests.existing_period') }}
                                                @if (empty($affected['remainders']))
                                                    → {{ __('admin.change_requests.fully_replaced') }}
                                                @else
                                                    → {{ __('admin.change_requests.remains_as', ['periods' => implode(__('admin.change_requests.range_joiner'), $affected['remainders'])]) }}
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($lines as $line)
                                    @php
                                        $status = $line['price_status'] ?? 'unknown';
                                        $display = match ($status) {
                                            'included' => __('admin.change_requests.price_statuses.included'),
                                            'free' => __('admin.change_requests.price_statuses.free'),
                                            'on_request' => __('admin.change_requests.price_statuses.on_request'),
                                            'unknown' => __('admin.change_requests.price_statuses.unknown'),
                                            'from' => __('admin.change_requests.from_price', ['amount' => $line['amount'] ?? '?']),
                                            default => __('admin.change_requests.price', ['amount' => $line['amount'] ?? '?']),
                                        };
                                    @endphp
                                    <div class="rounded-lg border border-neutral-200 px-3 py-2 dark:border-neutral-700">
                                        <div class="font-medium">{{ $display }}</div>
                                        @if (! empty($line['price_billing_unit_id']))
                                            <div class="text-xs text-neutral-500">{{ __('admin.change_requests.billing_unit') }} #{{ $line['price_billing_unit_id'] }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-3 grid gap-3 lg:grid-cols-2">
                                <div>
                                    <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('admin.change_requests.previous') }}</div>
                                    <pre class="overflow-x-auto rounded-lg bg-neutral-100 p-3 text-xs whitespace-pre-wrap dark:bg-neutral-900">{{ $original ?? 'null' }}</pre>
                                </div>
                                <div>
                                    <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('admin.change_requests.proposed') }}</div>
                                    <pre class="overflow-x-auto rounded-lg bg-neutral-100 p-3 text-xs whitespace-pre-wrap dark:bg-neutral-900">{{ $proposed ?? 'null' }}</pre>
                                </div>
                            </div>
                        @endif

                        @if ($item->user_comment)
                            <div class="mt-3">
                                <div class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('admin.change_requests.user_comment') }}</div>
                                <div class="mt-1 whitespace-pre-wrap text-sm">{{ $item->user_comment }}</div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        @if ($anchor->reviewed_at)
            <section class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                <flux:heading size="lg">{{ __('admin.change_requests.moderation_decision') }}</flux:heading>
                <div class="mt-3 text-sm">
                    <div><span class="text-neutral-500">{{ __('admin.change_requests.reviewed_by') }}:</span> {{ $anchor->reviewer_name ?: __('admin.change_requests.unknown') }}</div>
                    <div><span class="text-neutral-500">{{ __('admin.change_requests.time') }}:</span> {{ \App\Support\LocalTime::parse($anchor->reviewed_at)->format(__('admin.change_requests.date_format')) }}</div>
                    @if ($anchor->moderator_comment)
                        <div class="mt-3 whitespace-pre-wrap">{{ $anchor->moderator_comment }}</div>
                    @endif
                </div>
            </section>
        @endif

        @if ($allPending)
            <section class="space-y-4 rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                <div>
                    <flux:heading size="lg">{{ __('admin.change_requests.decision') }}</flux:heading>
                    <flux:text class="mt-1">
                        {{ __('admin.change_requests.decision_help') }}
                    </flux:text>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <form method="POST" action="{{ route('admin.change-requests.approve', $anchor->id) }}" class="space-y-3 rounded-xl border border-emerald-300 p-4 dark:border-emerald-800">
                        @csrf
                        <label class="block text-sm">
                            <span class="mb-1 block font-medium">{{ __('admin.change_requests.approval_comment') }}</span>
                            <textarea name="moderator_comment" rows="4" maxlength="2000" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-neutral-900" placeholder="{{ __('admin.change_requests.optional') }}"></textarea>
                        </label>
                        <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">{{ __('admin.change_requests.approve_group') }}</button>
                    </form>

                    <form method="POST" action="{{ route('admin.change-requests.reject', $anchor->id) }}" class="space-y-3 rounded-xl border border-red-300 p-4 dark:border-red-800">
                        @csrf
                        <label class="block text-sm">
                            <span class="mb-1 block font-medium">{{ __('admin.change_requests.rejection_reason') }}</span>
                            <textarea name="moderator_comment" rows="4" maxlength="2000" required class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-neutral-900" placeholder="{{ __('admin.change_requests.required') }}"></textarea>
                        </label>
                        <button type="submit" class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">{{ __('admin.change_requests.reject_group') }}</button>
                    </form>
                </div>
            </section>
        @elseif ($anchor->status === 'approved' && ! $anchor->applied_at)
            <div class="rounded-xl border border-blue-300 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950/30 dark:text-blue-100">
                {{ __('admin.change_requests.approved_not_applied') }}
            </div>
        @endif
    </div>
</x-layouts::app>
