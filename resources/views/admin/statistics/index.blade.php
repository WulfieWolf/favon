<x-layouts::app title="Statistik">
    <div class="mx-auto w-full max-w-7xl space-y-8 px-4 py-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading size="xl">Statistik</flux:heading>
                <flux:text class="mt-1">Aggregierte Favon-Nutzungs- und Bestandsdaten ohne personenbezogene Nutzungsverläufe.</flux:text>
            </div>
            <a href="{{ route('admin.index') }}" class="text-sm text-neutral-500 hover:text-neutral-900 dark:hover:text-white">← Admin</a>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach (['30d' => '30 Tage', '12m' => '12 Monate', 'all' => 'Gesamt'] as $periodOption => $label)
                <a href="{{ route('admin.statistics.index', ['period' => $periodOption]) }}"
                   @class([
                       'rounded-full border px-3 py-1.5 text-sm font-medium',
                       'border-neutral-900 bg-neutral-900 text-white dark:border-white dark:bg-white dark:text-neutral-900' => $period === $periodOption,
                       'border-neutral-200 text-neutral-600 dark:border-neutral-700 dark:text-neutral-300' => $period !== $periodOption,
                   ])>
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <section class="space-y-4">
            <flux:heading size="lg">Bestand</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    ['places_total', 'Plätze'],
                    ['users_total', 'Benutzer'],
                    ['favorites', 'Favoriten'],
                    ['support_tickets', 'Support-Tickets'],
                    ['audit_events', 'Audit-Ereignisse'],
                    ['merges', 'Zusammenführungen'],
                ] as [$key, $label])
                    <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                        <div class="text-sm text-neutral-500">{{ $label }}</div>
                        <div class="mt-2 text-3xl font-semibold">{{ number_format($inventory[$key], 0, ',', '.') }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-4 xl:grid-cols-3">
                <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                    <div class="font-semibold">Plätze</div>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div><div class="text-neutral-500">Veröffentlicht</div><strong>{{ number_format($inventory['places_published'], 0, ',', '.') }}</strong></div>
                        <div><div class="text-neutral-500">Inaktiv</div><strong>{{ number_format($inventory['places_inactive'], 0, ',', '.') }}</strong></div>
                    </div>
                    <div class="mt-5 space-y-2">
                        @foreach ($placeTypes as $row)
                            <div class="flex justify-between gap-4 text-sm"><span>{{ $row['label'] }}</span><strong>{{ number_format($row['count'], 0, ',', '.') }}</strong></div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                    <div class="font-semibold">Benutzer</div>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div><div class="text-neutral-500">Aktiv</div><strong>{{ number_format($inventory['users_active'], 0, ',', '.') }}</strong></div>
                        <div><div class="text-neutral-500">Verifiziert</div><strong>{{ number_format($inventory['users_verified'], 0, ',', '.') }}</strong></div>
                    </div>
                    <div class="mt-5 space-y-2">
                        @foreach ($roles as $row)
                            <div class="flex justify-between gap-4 text-sm"><span>{{ $row['label'] }}</span><strong>{{ number_format($row['count'], 0, ',', '.') }}</strong></div>
                        @endforeach
                    </div>
                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach ($userStatuses as $status => $count)
                            <span class="rounded-full bg-neutral-100 px-2.5 py-1 text-xs dark:bg-neutral-800">{{ $status }}: {{ $count }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                    <div class="font-semibold">Supportstatus</div>
                    <div class="mt-4 space-y-2">
                        @forelse ($supportStatuses as $status => $count)
                            <div class="flex justify-between gap-4 text-sm"><span>{{ $status }}</span><strong>{{ number_format($count, 0, ',', '.') }}</strong></div>
                        @empty
                            <div class="text-sm text-neutral-500">Keine Tickets.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <flux:heading size="lg">Aktivität</flux:heading>
                <flux:text class="mt-1">Zeitlicher Verlauf ausgewählter aggregierter Ereignisse.</flux:text>
            </div>
            @php
                $activityMetrics = [
                    'users' => 'Neue Benutzer',
                    'places' => 'Neue Plätze',
                    'favorites' => 'Favoriten',
                    'support' => 'Support-Tickets',
                    'usage' => 'Nutzungsereignisse',
                ];
            @endphp
            <div class="space-y-4 rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                @foreach ($activityMetrics as $metric => $label)
                    @php($maxValue = max(1, collect($activitySeries)->max($metric) ?? 1))
                    <div>
                        <div class="mb-2 flex justify-between gap-3">
                            <div class="text-sm font-medium">{{ $label }}</div>
                            <div class="text-xs text-neutral-500">{{ number_format(collect($activitySeries)->sum($metric), 0, ',', '.') }}</div>
                        </div>
                        <div class="flex h-20 items-end gap-px overflow-hidden">
                            @foreach ($activitySeries as $point)
                                @php($height = $point[$metric] > 0 ? max(4, (int) round(($point[$metric] / $maxValue) * 100)) : 1)
                                <div class="min-w-[3px] flex-1 rounded-t bg-neutral-700/70 dark:bg-neutral-300/70"
                                     style="height: {{ $height }}%"
                                     title="{{ $point['label'] }}: {{ $point[$metric] }}"></div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <flux:heading size="lg">Nutzung</flux:heading>
                <flux:text class="mt-1">Die Ereignisse enthalten keine User-ID, IP-Adresse, Session-ID, Fingerprints oder gespeicherten User-Agent.</flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                    <div class="text-sm text-neutral-500">Ereignisse</div>
                    <div class="mt-2 text-3xl font-semibold">{{ number_format($usageTotal, 0, ',', '.') }}</div>
                </div>
                @foreach ($usageByAudience as $row)
                    <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                        <div class="text-sm text-neutral-500">{{ ucfirst($row['label']) }}</div>
                        <div class="mt-2 text-3xl font-semibold">{{ number_format($row['count'], 0, ',', '.') }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                @foreach (['human' => 'Gast · Mensch', 'bot' => 'Gast · Bot', 'unclassified' => 'Gast · Unklassifiziert'] as $trafficType => $label)
                    <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                        <div class="text-sm text-neutral-500">{{ $label }}</div>
                        <div class="mt-1 text-2xl font-semibold">{{ number_format((int) ($guestTraffic[$trafficType] ?? 0), 0, ',', '.') }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-4 xl:grid-cols-3">
                <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                    <div class="font-semibold">Ereignisse</div>
                    <div class="mt-4 space-y-2">
                        @forelse ($usageByEvent as $row)
                            <div class="flex justify-between gap-4 text-sm"><span>{{ $row['label'] }}</span><strong>{{ number_format($row['count'], 0, ',', '.') }}</strong></div>
                        @empty
                            <div class="text-sm text-neutral-500">Noch keine Daten.</div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                    <div class="font-semibold">Seitenbereiche</div>
                    <div class="mt-4 space-y-2">
                        @forelse ($pageViewsByArea as $row)
                            <div class="flex justify-between gap-4 text-sm"><span class="truncate">{{ $row['label'] }}</span><strong>{{ number_format($row['count'], 0, ',', '.') }}</strong></div>
                        @empty
                            <div class="text-sm text-neutral-500">Noch keine Daten.</div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                    <div class="font-semibold">Meistgesehene Plätze</div>
                    <div class="mt-4 space-y-2">
                        @forelse ($topPlaces as $row)
                            <div class="flex justify-between gap-4 text-sm">
                                <a class="truncate hover:underline" href="{{ route('places.show', $row['slug']) }}">{{ $row['name'] }}</a>
                                <strong>{{ number_format($row['views'], 0, ',', '.') }}</strong>
                            </div>
                        @empty
                            <div class="text-sm text-neutral-500">Noch keine Daten.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-dashed border-neutral-300 p-4 text-sm text-neutral-500 dark:border-neutral-700">
                Platzbezogene Aufrufe werden nur aggregiert gezählt. Es wird nicht gespeichert, welcher Benutzer einen bestimmten Platz angesehen hat.
            </div>
        </section>
    </div>
</x-layouts::app>
