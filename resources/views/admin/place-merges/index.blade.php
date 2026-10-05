@php
    $publicationStatuses = __('admin.place_merges.publication_statuses');
    $mergeStatuses = __('admin.place_merges.statuses');
    $fieldValue = static function (string $field, mixed $value): mixed {
        if ($value === null || $value === '') {
            return '—';
        }

        $translationKey = match ($field) {
            'legal_status' => "place_editing.info.legal_statuses.{$value}",
            'opening_status' => "place_editing.info.opening_statuses.{$value}",
            default => null,
        };

        if ($translationKey && \Illuminate\Support\Facades\Lang::has($translationKey)) {
            return __($translationKey);
        }

        $legacyLegalStatuses = __('admin.place_merges.legacy_legal_statuses');

        return $field === 'legal_status'
            ? ($legacyLegalStatuses[$value] ?? $value)
            : $value;
    };
@endphp

<x-layouts::app :title="__('admin.place_merges.title')">
    <div class="mx-auto max-w-7xl space-y-6">
        <div>
            <flux:heading size="xl">{{ __('admin.place_merges.title') }}</flux:heading>
            <flux:text class="mt-1">{{ __('admin.place_merges.intro') }}</flux:text>
        </div>

        @if ($error)<div class="rounded-lg border border-red-300 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200">{{ $error }}</div>@endif
<div class="grid gap-5 lg:grid-cols-2">
            @foreach ([
                'main' => [
                    'title' => __('admin.place_merges.main_place_title'),
                    'help' => __('admin.place_merges.main_place_help'),
                    'search' => $mainSearch,
                    'results' => $mainResults,
                    'selected' => $mainPlace,
                    'other_id' => $duplicateId,
                    'other_search' => $duplicateSearch,
                ],
                'duplicate' => [
                    'title' => __('admin.place_merges.duplicate_title'),
                    'help' => __('admin.place_merges.duplicate_help'),
                    'search' => $duplicateSearch,
                    'results' => $duplicateResults,
                    'selected' => $duplicatePlace,
                    'other_id' => $mainId,
                    'other_search' => $mainSearch,
                ],
            ] as $side => $config)
                @php
                    $isMain = $side === 'main';
                    $searchName = $isMain ? 'main_search' : 'duplicate_search';
                    $otherSearchName = $isMain ? 'duplicate_search' : 'main_search';
                    $otherIdName = $isMain ? 'duplicate' : 'main';
                @endphp
                <section class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                    <div>
                        <h2 class="font-semibold">{{ $config['title'] }}</h2>
                        <p class="mt-1 text-sm text-neutral-500">{{ $config['help'] }}</p>
                    </div>

                    <form method="GET" class="mt-4 flex gap-2">
                        <input
                            name="{{ $searchName }}"
                            value="{{ $config['search'] }}"
                            placeholder="{{ __('admin.place_merges.search_name_or_id') }}"
                            class="min-w-0 flex-1 rounded-lg border-neutral-300 bg-white dark:border-neutral-700 dark:bg-neutral-900"
                        >
                        @if ($config['other_id'])
                            <input type="hidden" name="{{ $otherIdName }}" value="{{ $config['other_id'] }}">
                        @endif
                        @if ($config['other_search'] !== '')
                            <input type="hidden" name="{{ $otherSearchName }}" value="{{ $config['other_search'] }}">
                        @endif
                        <button class="rounded-lg border border-neutral-300 px-4 py-2 dark:border-neutral-700">{{ __('admin.place_merges.search') }}</button>
                    </form>

                    @if ($config['search'] !== '' && $config['results']->isEmpty())
                        <div class="mt-3 text-sm text-neutral-500">{{ __('admin.place_merges.no_search_results') }}</div>
                    @elseif ($config['results']->isNotEmpty())
                        <div class="mt-3 max-h-56 divide-y divide-neutral-200 overflow-y-auto rounded-lg border border-neutral-200 dark:divide-neutral-700 dark:border-neutral-700">
                            @foreach ($config['results'] as $result)
                                <a
                                    href="{{ route('admin.place-merges.index', array_filter([
                                        $side => $result->id,
                                        $searchName => $config['search'],
                                        $otherIdName => $config['other_id'] ?: null,
                                        $otherSearchName => $config['other_search'] !== '' ? $config['other_search'] : null,
                                    ], fn ($value) => $value !== null && $value !== '')) }}"
                                    class="block px-3 py-2 text-sm hover:bg-neutral-50 dark:hover:bg-neutral-800"
                                >
                                    <div class="font-medium">#{{ $result->id }} · {{ $result->name }}</div>
                                    <div class="text-xs text-neutral-500">{{ $result->city ?: '—' }} · {{ $publicationStatuses[$result->publication_status] ?? $result->publication_status }}</div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if ($config['selected'])
                        @php
                            $address = collect([
                                trim(implode(' ', array_filter([$config['selected']->street, $config['selected']->house_number]))),
                                trim(implode(' ', array_filter([$config['selected']->postal_code, $config['selected']->city]))),
                                $config['selected']->country_code,
                            ])->filter()->implode(', ');
                        @endphp
                        <div class="mt-4 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                            <div class="p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="text-xs text-neutral-500">#{{ $config['selected']->id }}</div>
                                        <div class="font-semibold">{{ $config['selected']->name }}</div>
                                        <div class="mt-1 text-sm text-neutral-500">{{ $config['selected']->place_type_label }}</div>
                                    </div>
                                    <span class="rounded-full bg-neutral-100 px-2 py-1 text-xs dark:bg-neutral-800">{{ $publicationStatuses[$config['selected']->publication_status] ?? $config['selected']->publication_status }}</span>
                                </div>
                                <div class="mt-3 text-sm">{{ $address !== '' ? $address : __('admin.place_merges.address_missing') }}</div>
                                <div class="mt-1 text-xs text-neutral-500">{{ number_format((float) $config['selected']->latitude, 6, '.', '') }}, {{ number_format((float) $config['selected']->longitude, 6, '.', '') }}</div>
                            </div>
                            <div
                                class="h-48 w-full bg-neutral-100 dark:bg-neutral-800"
                                data-merge-map
                                data-lat="{{ $config['selected']->latitude }}"
                                data-lng="{{ $config['selected']->longitude }}"
                                data-label="{{ $config['selected']->name }}"
                            ></div>
                        </div>
                    @endif
                </section>
            @endforeach
        </div>

        @if ($mainPlace && $duplicatePlace)
            <form method="GET" class="flex justify-end">
                <input type="hidden" name="compare" value="1">
                <input type="hidden" name="main" value="{{ $mainPlace->id }}">
                <input type="hidden" name="duplicate" value="{{ $duplicatePlace->id }}">
                <input type="hidden" name="main_search" value="{{ $mainSearch }}">
                <input type="hidden" name="duplicate_search" value="{{ $duplicateSearch }}">
                <button class="rounded-lg bg-neutral-900 px-5 py-2.5 font-semibold text-white dark:bg-white dark:text-neutral-900">{{ __('admin.place_merges.compare') }}</button>
            </form>
        @endif

        @if ($comparison)
            @if($comparison['pendingChangeRequests'] > 0)<div class="rounded-lg border border-red-300 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200">{{ trans_choice('admin.place_merges.pending_changes_warning', $comparison['pendingChangeRequests'], ['count' => $comparison['pendingChangeRequests']]) }}</div>@endif
            <form method="POST" action="{{ route('admin.place-merges.store') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="main_id" value="{{ $comparison['main']->id }}">
                <input type="hidden" name="duplicate_id" value="{{ $comparison['duplicate']->id }}">

                <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <table class="w-full min-w-[850px] text-sm">
                        <thead class="bg-neutral-50 text-left dark:bg-neutral-900"><tr><th class="p-3">{{ __('admin.place_merges.field') }}</th><th class="p-3">{{ __('admin.place_merges.main_place') }}: {{ $comparison['main']->name }}</th><th class="p-3">{{ __('admin.place_merges.duplicate') }}: {{ $comparison['duplicate']->name }}</th><th class="p-3">{{ __('admin.place_merges.decision') }}</th></tr></thead>
                        <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                            @foreach ($comparison['fields'] as $key => $field)
                                <tr><td class="p-3 font-semibold">{{ $field['label'] }} @if($field['required'])<span title="{{ __('admin.place_merges.required') }}">*</span>@endif</td><td class="p-3">{{ $fieldValue($key, $field['main']) }}</td><td class="p-3">{{ $fieldValue($key, $field['duplicate']) }}</td><td class="p-3"><select name="fields[{{ $key }}]" class="rounded-lg border-neutral-300 bg-white dark:border-neutral-700 dark:bg-neutral-900"><option value="main" @selected($field['suggested']==='main')>{{ __('admin.place_merges.from_main') }}</option><option value="duplicate" @selected($field['suggested']==='duplicate')>{{ __('admin.place_merges.from_duplicate') }}</option>@unless($field['required'])<option value="delete" @selected($field['suggested']==='delete')>{{ __('admin.place_merges.delete_unknown') }}</option>@endunless</select></td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @foreach (collect($comparison['records'])->groupBy('group') as $group => $records)
                    <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
                        <div class="border-b border-neutral-200 bg-neutral-50 p-3 font-semibold dark:border-neutral-700 dark:bg-neutral-900">{{ $group }}</div>
                        <table class="w-full min-w-[900px] text-sm"><thead><tr class="text-left"><th class="p-3">{{ __('admin.place_merges.record') }}</th><th class="p-3">{{ __('admin.place_merges.main_place') }}</th><th class="p-3">{{ __('admin.place_merges.duplicate') }}</th><th class="p-3">{{ __('admin.place_merges.decision') }}</th></tr></thead><tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @foreach ($records as $record)
                            <tr><td class="p-3 font-medium">{{ $record['identity'] }}</td><td class="p-3"><pre class="max-w-md whitespace-pre-wrap text-xs">{{ $record['main'] ? json_encode($record['main'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : '—' }}</pre></td><td class="p-3"><pre class="max-w-md whitespace-pre-wrap text-xs">{{ $record['duplicate'] ? json_encode($record['duplicate'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : '—' }}</pre></td><td class="p-3"><select name="records[{{ $record['token'] }}]" class="rounded-lg border-neutral-300 bg-white dark:border-neutral-700 dark:bg-neutral-900"><option value="main" @selected($record['suggested']==='main')>{{ __('admin.place_merges.from_main') }}</option><option value="duplicate" @selected($record['suggested']==='duplicate')>{{ __('admin.place_merges.from_duplicate') }}</option><option value="delete" @selected($record['suggested']==='delete')>{{ __('admin.place_merges.delete') }}</option></select></td></tr>
                        @endforeach
                        </tbody></table>
                    </div>
                @endforeach

                <div class="rounded-xl border border-amber-300 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-950/30">
                    <label class="flex gap-3"><input type="checkbox" name="confirmation" value="1" required class="mt-1"><span><strong>{{ __('admin.place_merges.confirm_heading') }}</strong> {{ __('admin.place_merges.confirm_help') }}</span></label>
                    <button @disabled($comparison['pendingChangeRequests'] > 0) class="mt-4 rounded-lg bg-amber-700 px-5 py-2.5 font-semibold text-white hover:bg-amber-800 disabled:cursor-not-allowed disabled:opacity-50">{{ __('admin.place_merges.merge_now') }}</button>
                </div>
            </form>
        @endif

        <section class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
            <h2 class="font-semibold">{{ __('admin.place_merges.recent') }}</h2>
            <div class="mt-3 divide-y divide-neutral-200 dark:divide-neutral-700">
                @forelse($recentMerges as $merge)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3"><div><strong>{{ $merge->source_name }}</strong> → <strong>{{ $merge->target_name }}</strong><div class="text-xs text-neutral-500">{{ \App\Support\LocalTime::parse($merge->merged_at)->format(__('admin.place_merges.date_format')) }} · {{ $merge->actor_name ?: __('admin.place_merges.unknown') }} · {{ $mergeStatuses[$merge->status] ?? $merge->status }}</div></div>@if($merge->status === 'completed')<form method="POST" action="{{ route('admin.place-merges.reverse', $merge->id) }}" onsubmit="return confirm(@js(__('admin.place_merges.reverse_confirmation')))">@csrf<button class="rounded-lg border border-red-300 px-3 py-2 text-sm text-red-700 dark:border-red-800 dark:text-red-300">{{ __('admin.place_merges.reverse') }}</button></form>@endif</div>
                @empty<div class="py-3 text-sm text-neutral-500">{{ __('admin.place_merges.no_merges') }}</div>@endforelse
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            (() => {
                const initMergeMaps = () => {
                    if (typeof L === 'undefined') return;
                    document.querySelectorAll('[data-merge-map]:not([data-initialized])').forEach((element) => {
                        const lat = Number(element.dataset.lat);
                        const lng = Number(element.dataset.lng);
                        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
                        element.dataset.initialized = '1';
                        const map = L.map(element, { zoomControl: true, attributionControl: true }).setView([lat, lng], 15);
                        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(map);
                        L.marker([lat, lng]).addTo(map).bindPopup(element.dataset.label || '').openPopup();
                    });
                };
                document.addEventListener('DOMContentLoaded', initMergeMaps, { once: true });
                document.addEventListener('livewire:navigated', initMergeMaps);
            })();
        </script>
    @endpush
</x-layouts::app>
