<x-layouts::app :title="__('admin.statistics.title')">
    <div class="space-y-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('admin.statistics.title') }}</flux:heading>
                <flux:text class="mt-1">{{ __('admin.statistics.intro') }}</flux:text>
            </div>

            <a href="{{ route('admin.index') }}" class="text-sm text-neutral-500 hover:text-neutral-900 dark:hover:text-white">
                {{ __('admin.statistics.back_admin') }}
            </a>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach (['30d', '12m', 'all'] as $periodOption)
                <a
                    href="{{ route('admin.statistics.index', ['period' => $periodOption]) }}"
                    @class([
                        'rounded-full border px-3 py-1.5 text-sm font-medium transition',
                        'border-neutral-900 bg-neutral-900 text-white dark:border-white dark:bg-white dark:text-neutral-900' => $period === $periodOption,
                        'border-neutral-200 text-neutral-600 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800' => $period !== $periodOption,
                    ])
                >
                    {{ __('admin.statistics.periods.'.$periodOption) }}
                </a>
            @endforeach
        </div>

        <section class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('admin.statistics.inventory_title') }}</flux:heading>
                <flux:text class="mt-1">{{ __('admin.statistics.inventory_help') }}</flux:text>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['places_total', 'places'],
                    ['users_total', 'users'],
                    ['reviews_total', 'reviews'],
                    ['rating_values', 'rating_values'],
                    ['photos_total', 'photos'],
                    ['features_active', 'features'],
                    ['favorites', 'favorites'],
                    ['support_tickets', 'support_tickets'],
                    ['external_records', 'external_records'],
                    ['import_runs', 'import_runs'],
                    ['audit_events', 'audit_events'],
                    ['merges', 'merges'],
                ] as [$key, $label])
                    <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                        <div class="text-sm text-neutral-500">{{ __('admin.statistics.metrics.'.$label) }}</div>
                        <div class="mt-2 text-3xl font-semibold">{{ number_format($inventory[$key], 0, ',', '.') }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                    <div class="font-semibold">{{ __('admin.statistics.places_detail') }}</div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div>
                            <div class="text-xs text-neutral-500">{{ __('admin.statistics.metrics.published_places') }}</div>
                            <div class="mt-1 text-xl font-semibold">{{ number_format($inventory['places_published'], 0, ',', '.') }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-neutral-500">{{ __('admin.statistics.metrics.inactive_places') }}</div>
                            <div class="mt-1 text-xl font-semibold">{{ number_format($inventory['places_inactive'], 0, ',', '.') }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-neutral-500">{{ __('admin.statistics.metrics.place_features') }}</div>
                            <div class="mt-1 text-xl font-semibold">{{ number_format($inventory['place_features_active'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="mt-5 space-y-2">
                        @foreach ($placeTypes as $row)
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <span>{{ $row['label'] }}</span>
                                <span class="font-semibold tabular-nums">{{ number_format($row['count'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                    <div class="font-semibold">{{ __('admin.statistics.users_detail') }}</div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div>
                            <div class="text-xs text-neutral-500">{{ __('admin.statistics.metrics.active_users') }}</div>
                            <div class="mt-1 text-xl font-semibold">{{ number_format($inventory['users_active'], 0, ',', '.') }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-neutral-500">{{ __('admin.statistics.metrics.verified_users') }}</div>
                            <div class="mt-1 text-xl font-semibold">{{ number_format($inventory['users_verified'], 0, ',', '.') }}</div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('admin.statistics.roles') }}</div>
                        <div class="mt-2 space-y-2">
                            @foreach ($roles as $row)
                                <div class="flex items-center justify-between gap-4 text-sm">
                                    <span>{{ $row['label'] }}</span>
                                    <span class="font-semibold tabular-nums">{{ number_format($row['count'], 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('admin.statistics.account_statuses') }}</div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($userStatuses as $status => $count)
                                <span class="rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-medium text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">
                                    {{ __('admin.users.account_statuses.'.$status) }}: {{ $count }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                    <div class="font-semibold">{{ __('admin.statistics.content_detail') }}</div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="flex items-center justify-between gap-4 text-sm">
                            <span>{{ __('admin.statistics.metrics.review_texts') }}</span>
                            <strong>{{ number_format($inventory['review_texts'], 0, ',', '.') }}</strong>
                        </div>
                        <div class="flex items-center justify-between gap-4 text-sm">
                            <span>{{ __('admin.statistics.metrics.community_photos') }}</span>
                            <strong>{{ number_format($inventory['photos_community'], 0, ',', '.') }}</strong>
                        </div>
                        <div class="flex items-center justify-between gap-4 text-sm">
                            <span>{{ __('admin.statistics.metrics.external_photos') }}</span>
                            <strong>{{ number_format($inventory['photos_external'], 0, ',', '.') }}</strong>
                        </div>
                        <div class="flex items-center justify-between gap-4 text-sm">
                            <span>{{ __('admin.statistics.metrics.feature_categories') }}</span>
                            <strong>{{ number_format($inventory['feature_categories_active'], 0, ',', '.') }}</strong>
                        </div>
                        <div class="flex items-center justify-between gap-4 text-sm">
                            <span>{{ __('admin.statistics.metrics.change_requests') }}</span>
                            <strong>{{ number_format($inventory['change_requests'], 0, ',', '.') }}</strong>
                        </div>
                        <div class="flex items-center justify-between gap-4 text-sm">
                            <span>{{ __('admin.statistics.metrics.external_linked') }}</span>
                            <strong>{{ number_format($inventory['external_linked'], 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                    <div class="font-semibold">{{ __('admin.statistics.status_detail') }}</div>
                    <div class="mt-4 space-y-4">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('admin.statistics.support_statuses') }}</div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($supportStatuses as $status => $count)
                                    <span class="rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-medium text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">
                                        {{ __('admin.support.statuses.'.$status) }}: {{ $count }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('admin.statistics.change_statuses') }}</div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($changeStatuses as $status => $count)
                                    <span class="rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-medium text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">
                                        {{ __('admin.change_requests.statuses.'.$status) }}: {{ $count }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('admin.statistics.photo_statuses') }}</div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($photoStatuses as $status => $count)
                                    <span class="rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-medium text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">
                                        {{ $status }}: {{ $count }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('admin.statistics.activity_title') }}</flux:heading>
                <flux:text class="mt-1">{{ __('admin.statistics.activity_help') }}</flux:text>
            </div>

            @php
                $activityMetrics = [
                    'users' => __('admin.statistics.activity.users'),
                    'places' => __('admin.statistics.activity.places'),
                    'reviews' => __('admin.statistics.activity.reviews'),
                    'photos' => __('admin.statistics.activity.photos'),
                    'changes' => __('admin.statistics.activity.changes'),
                    'support' => __('admin.statistics.activity.support'),
                    'usage' => __('admin.statistics.activity.usage'),
                ];
            @endphp

            <div class="space-y-4 rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                @foreach ($activityMetrics as $metric => $label)
                    @php
                        $maxValue = max(1, collect($activitySeries)->max($metric) ?? 1);
                    @endphp
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <div class="text-sm font-medium">{{ $label }}</div>
                            <div class="text-xs text-neutral-500">{{ number_format(collect($activitySeries)->sum($metric), 0, ',', '.') }}</div>
                        </div>
                        <div class="flex h-20 items-end gap-px overflow-hidden" title="{{ $label }}">
                            @foreach ($activitySeries as $point)
                                @php
                                    $height = $point[$metric] > 0 ? max(4, (int) round(($point[$metric] / $maxValue) * 100)) : 1;
                                @endphp
                                <div
                                    class="min-w-[3px] flex-1 rounded-t bg-neutral-700/70 dark:bg-neutral-300/70"
                                    style="height: {{ $height }}%"
                                    title="{{ $point['label'] }}: {{ $point[$metric] }}"
                                ></div>
                            @endforeach
                        </div>
                        @if (count($activitySeries) > 0)
                            <div class="mt-1 flex justify-between text-[10px] text-neutral-400">
                                <span>{{ $activitySeries[0]['label'] }}</span>
                                <span>{{ $activitySeries[count($activitySeries) - 1]['label'] }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('admin.statistics.usage_title') }}</flux:heading>
                <flux:text class="mt-1">{{ __('admin.statistics.usage_help') }}</flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                    <div class="text-sm text-neutral-500">{{ __('admin.statistics.metrics.usage_events') }}</div>
                    <div class="mt-2 text-3xl font-semibold">{{ number_format($usageTotal, 0, ',', '.') }}</div>
                </div>
                @foreach ($usageByAudience as $row)
                    <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                        <div class="text-sm text-neutral-500">{{ __('admin.statistics.audiences.'.$row['label']) }}</div>
                        <div class="mt-2 text-3xl font-semibold">{{ number_format($row['count'], 0, ',', '.') }}</div>
                    </div>
                @endforeach
            </div>

            <div>
                <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-500">
                    {{ __('admin.statistics.guest_traffic_title') }}
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach (['human', 'bot', 'unclassified'] as $trafficType)
                        <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                            <div class="text-sm text-neutral-500">{{ __('admin.statistics.guest_traffic.'.$trafficType) }}</div>
                            <div class="mt-1 text-2xl font-semibold">{{ number_format((int) ($guestTraffic[$trafficType] ?? 0), 0, ',', '.') }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-3">
                <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                    <div class="font-semibold">{{ __('admin.statistics.events_title') }}</div>
                    <div class="mt-4 space-y-2">
                        @forelse ($usageByEvent as $row)
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <span class="truncate">{{ __('admin.statistics.events.'.$row['label'], [], app()->getLocale()) !== 'admin.statistics.events.'.$row['label'] ? __('admin.statistics.events.'.$row['label']) : $row['label'] }}</span>
                                <strong class="tabular-nums">{{ number_format($row['count'], 0, ',', '.') }}</strong>
                            </div>
                        @empty
                            <div class="text-sm text-neutral-500">{{ __('admin.statistics.no_usage_data') }}</div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                    <div class="font-semibold">{{ __('admin.statistics.pages_title') }}</div>
                    <div class="mt-4 space-y-2">
                        @forelse ($pageViewsByArea as $row)
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <span class="truncate" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                                <strong class="tabular-nums">{{ number_format($row['count'], 0, ',', '.') }}</strong>
                            </div>
                        @empty
                            <div class="text-sm text-neutral-500">{{ __('admin.statistics.no_usage_data') }}</div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                    <div class="font-semibold">{{ __('admin.statistics.top_places_title') }}</div>
                    <div class="mt-4 space-y-2">
                        @forelse ($topPlaces as $row)
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <a class="truncate hover:underline" href="{{ route('places.show', $row['slug']) }}">{{ $row['name'] }}</a>
                                <strong class="tabular-nums">{{ number_format($row['views'], 0, ',', '.') }}</strong>
                            </div>
                        @empty
                            <div class="text-sm text-neutral-500">{{ __('admin.statistics.no_usage_data') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-dashed border-neutral-300 p-4 text-sm text-neutral-500 dark:border-neutral-700">
                {{ __('admin.statistics.privacy_note') }}
            </div>
        </section>
    </div>
</x-layouts::app>
