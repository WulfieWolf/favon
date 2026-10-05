<x-layouts::app :title="__('admin_imports.title')">
    <div class="space-y-8">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <flux:heading size="xl">{{ __('admin_imports.title') }}</flux:heading>
                <flux:text class="mt-1">{{ __('admin_imports.intro') }}</flux:text>
            </div>
            <a href="{{ route('admin.index') }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">{{ __('admin_imports.back_admin') }}</a>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200">
                <div class="font-semibold">{{ __('admin_imports.import_not_run') }}</div>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            @foreach ([
                [__('admin_imports.sources'), $summary['sources']],
                [__('admin_imports.active_external_records'), $summary['active_records']],
                [__('admin_imports.new_candidates'), $summary['new_candidates']],
                [__('admin_imports.possible_duplicates'), $summary['possible_duplicates']],
                [__('admin_imports.needs_review'), $summary['needs_review']],
                [__('admin_imports.open_reviews'), $summary['pending_reviews']],
            ] as [$label, $value])
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <div class="text-xs text-zinc-500">{{ $label }}</div>
                    <div class="mt-1 text-2xl font-semibold">{{ number_format($value, 0, ',', '.') }}</div>
                </div>
            @endforeach
        </div>

        <details class="group rounded-xl border border-zinc-200 dark:border-zinc-800">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-4">
                <div>
                    <div class="font-semibold">{{ __('admin_imports.source_check') }}</div>
                    <div class="mt-1 text-sm text-zinc-500">{{ __('admin_imports.source_freshness_help') }}</div>
                </div>
                <span class="text-sm text-zinc-400 group-open:rotate-180">⌄</span>
            </summary>
            <div class="overflow-x-auto border-t border-zinc-200 dark:border-zinc-800">
                <table class="min-w-[720px] w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs text-zinc-500 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-3">{{ __('admin_imports.source') }}</th>
                            <th class="px-4 py-3">{{ __('admin_imports.type') }}</th>
                            <th class="px-4 py-3">{{ __('admin_imports.last_checked') }}</th>
                            <th class="px-4 py-3">{{ __('admin_imports.last_success') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($sources as $source)
                            <tr>
                                <td class="px-4 py-3"><div class="font-medium">{{ $source->name }}</div><div class="text-xs text-zinc-400">{{ $source->slug }}</div></td>
                                <td class="px-4 py-3">{{ $source->source_type }}</td>
                                <td class="px-4 py-3 text-zinc-500">{{ $source->last_checked_at ? \App\Support\LocalTime::parse($source->last_checked_at)->format('d.m.Y H:i') : '—' }}</td>
                                <td class="px-4 py-3 text-zinc-500">{{ $source->last_success_at ? \App\Support\LocalTime::parse($source->last_success_at)->format('d.m.Y H:i') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-6 text-center text-zinc-500">{{ __('admin_imports.no_sources') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </details>

        <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">{{ __('admin_imports.research_export') }}</h2>
                    <p class="mt-1 max-w-3xl text-sm text-zinc-500">
                        {{ __('admin_imports.research_export_help') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.imports.research-export') }}" class="rounded-lg bg-zinc-900 px-3 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('admin_imports.research_needed_export') }}</a>
                    <a href="{{ route('admin.imports.research-export', ['scope' => 'all']) }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold dark:border-zinc-700">{{ __('admin_imports.export_all_places') }}</a>
                </div>
            </div>
            <div class="mt-4 rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-xs text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                {{ __('admin_imports.research_csv_help') }}
            </div>

            <form method="POST" action="{{ route('admin.imports.research-upload') }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
                @csrf
                <label class="min-w-[280px] flex-1">
                    <span class="mb-1 block text-sm font-medium">{{ __('admin_imports.research_file') }}</span>
                    <input type="file" name="research_file" required accept=".csv,text/csv" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                </label>
                <button class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('admin_imports.research_stage') }}</button>
            </form>
            <p class="mt-2 text-xs text-zinc-500">{{ __('admin_imports.research_stage_help') }}</p>
        </section>

        <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">{{ __('admin_imports.start_import') }}</h2>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('admin_imports.start_import_help') }}</p>
                </div>
                <a href="{{ route('admin.imports.template') }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold dark:border-zinc-700">{{ __('admin_imports.csv_template') }}</a>
            </div>

            <form method="POST" action="{{ route('admin.imports.upload') }}" enctype="multipart/form-data" class="mt-5 grid gap-4 lg:grid-cols-2" data-import-form>
                @csrf

                <label class="block">
                    <span class="mb-1 block text-sm font-medium">{{ __('admin_imports.data_source_format') }}</span>
                    <select name="adapter" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950" data-import-adapter>
                        <option value="datex2" @selected(old('adapter', 'datex2') === 'datex2')>DATEX-II / Mobilithek</option>
                        <option value="csv" @selected(old('adapter') === 'csv')>{{ __('admin_imports.csv_manual_source') }}</option>
                    </select>
                </label>

                <label class="block" data-csv-source>
                    <span class="mb-1 block text-sm font-medium">{{ __('admin_imports.source_label_csv') }}</span>
                    <input name="source_name" value="{{ old('source_name') }}" placeholder="{{ __('admin_imports.source_placeholder') }}" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    <span class="mt-1 block text-xs text-zinc-500">{{ __('admin_imports.source_name_help') }}</span>
                </label>

                <label class="block lg:col-span-2">
                    <span class="mb-1 block text-sm font-medium">{{ __('admin_imports.file') }}</span>
                    <input type="file" name="file" required accept=".xml,.csv,text/csv,application/xml,text/xml" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    <span class="mt-1 block text-xs text-zinc-500">{{ __('admin_imports.file_help') }}</span>
                </label>

                <label class="flex items-start gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-800 lg:col-span-2">
                    <input type="checkbox" name="complete_snapshot" value="1" @checked(old('complete_snapshot')) class="mt-1">
                    <span>
                        <span class="block text-sm font-medium">{{ __('admin_imports.full_snapshot') }}</span>
                        <span class="block text-xs text-zinc-500">{{ __('admin_imports.full_snapshot_help') }}</span>
                    </span>
                </label>

                <div class="lg:col-span-2 flex justify-end">
                    <button class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('admin_imports.upload_stage') }}</button>
                </div>
            </form>
        </section>

        <section class="space-y-3">
            <div>
                <h2 class="text-lg font-semibold">{{ __('admin_imports.import_history') }}</h2>
                <p class="mt-1 text-sm text-zinc-500">{{ __('admin_imports.import_runs_help') }}</p>
            </div>

            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
                <table class="min-w-[1000px] w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs text-zinc-500 dark:bg-zinc-900">
                        <tr>
                            <th class="px-3 py-3">{{ __('admin_imports.run') }}</th>
                            <th class="px-3 py-3">{{ __('admin_imports.source') }}</th>
                            <th class="px-3 py-3">{{ __('admin_imports.mode') }}</th>
                            <th class="px-3 py-3">{{ __('admin_imports.status') }}</th>
                            <th class="px-3 py-3">{{ __('admin_imports.records') }}</th>
                            <th class="px-3 py-3">{{ __('admin_imports.new') }}</th>
                            <th class="px-3 py-3">{{ __('admin_imports.changed') }}</th>
                            <th class="px-3 py-3">{{ __('admin_imports.unchanged') }}</th>
                            <th class="px-3 py-3">{{ __('admin_imports.review') }}</th>
                            <th class="px-3 py-3">{{ __('admin_imports.time') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($runs as $run)
                            <tr>
                                <td class="px-3 py-3 font-mono text-xs">#{{ $run->id }}</td>
                                <td class="px-3 py-3"><div class="font-medium">{{ $run->source_name }}</div><div class="text-xs text-zinc-400">{{ $run->source_slug }}</div></td>
                                <td class="px-3 py-3">{{ $run->mode }}</td>
                                <td class="px-3 py-3">{{ $run->status }}</td>
                                <td class="px-3 py-3">{{ $run->source_record_count ?? $run->mapped_record_count }}</td>
                                <td class="px-3 py-3">{{ $run->created_count }}</td>
                                <td class="px-3 py-3">{{ $run->updated_count }}</td>
                                <td class="px-3 py-3">{{ $run->skipped_count }}</td>
                                <td class="px-3 py-3">{{ $run->review_count }}</td>
                                <td class="px-3 py-3 text-xs text-zinc-500">{{ \App\Support\LocalTime::parse($run->created_at)->format('d.m.Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="p-8 text-center text-zinc-500">{{ __('admin_imports.no_import_runs') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $runs->links() }}
        </section>

        @if (!empty($duplicateGroups))
            <section id="duplicate-groups" class="scroll-mt-4 space-y-4 rounded-xl border border-amber-300 bg-amber-50/40 p-5 dark:border-amber-800 dark:bg-amber-950/10">
                <div>
                    <h2 class="text-lg font-semibold">{{ __('admin_imports.duplicate_groups') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('admin_imports.duplicate_groups_help') }}
                    </p>
                </div>

                <div class="rounded-lg border border-amber-300 bg-amber-100/60 p-4 dark:border-amber-800 dark:bg-amber-950/30">
                    <div class="font-semibold">{{ __('admin_imports.manual_atkis_bulk') }}</div>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('admin_imports.atkis_bulk_help') }}
                    </p>
                    <div class="mt-3 text-sm">
                        {{ __('admin_imports.current_preview') }}
                        <strong>{{ __('admin_imports.groups_count', ['count' => number_format($manualAtkisDuplicatePreview['groups'], 0, ',', '.')]) }}</strong>
                        {{ __('admin_imports.with') }}
                        <strong>{{ __('admin_imports.atkis_records_count', ['count' => number_format($manualAtkisDuplicatePreview['records'], 0, ',', '.')]) }}</strong>.
                    </div>
                    <form
                        method="POST"
                        action="{{ route('admin.imports.duplicate-groups.link-eligible-atkis') }}"
                        class="mt-3 flex flex-wrap items-center gap-3"
                        data-confirm="{{ __('admin_imports.confirm_atkis', ['groups' => $manualAtkisDuplicatePreview['groups'], 'records' => $manualAtkisDuplicatePreview['records']]) }}" onsubmit="return confirm(this.dataset.confirm)"
                    >
                        @csrf
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="confirmed" value="1" required class="rounded">
                            <span>{{ __('admin_imports.atkis_confirmed') }}</span>
                        </label>
                        <button
                            class="rounded-lg bg-amber-700 px-3 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50 dark:bg-amber-600"
                            @disabled(($manualAtkisDuplicatePreview['groups'] ?? 0) === 0)
                        >
                            {{ __('admin_imports.atkis_link') }}
                        </button>
                    </form>
                    @error('duplicate_group_bulk')
                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                <div class="space-y-4">
                    @foreach ($duplicateGroups as $group)
                        <article class="rounded-xl border border-amber-200 bg-white p-4 dark:border-amber-900 dark:bg-zinc-950">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold">{{ $group['name'] }}</div>
                                    <div class="mt-1 text-xs text-zinc-500">
                                        {{ $group['place_type'] }} · {{ count($group['members']) }} {{ __('admin_imports.external_records') }} · {{ implode(', ', $group['source_names']) }}
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    data-duplicate-map-open
                                    data-endpoint="{{ route('admin.imports.duplicate-groups.map', $group['review_ids'][0]) }}"
                                    class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900"
                                >
                                    {{ __('admin_imports.map_check') }}
                                </button>
                            </div>

                            <div class="mt-3 overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-800">
                                <table class="min-w-[980px] w-full text-left text-xs">
                                    <thead class="bg-zinc-50 text-zinc-500 dark:bg-zinc-900">
                                        <tr>
                                            <th class="px-3 py-2">{{ __('admin_imports.source') }}</th>
                                            <th class="px-3 py-2">{{ __('admin_imports.external_id') }}</th>
                                            <th class="px-3 py-2">{{ __('admin_imports.coordinates') }}</th>
                                            <th class="px-3 py-2">{{ __('admin_imports.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                        @foreach ($group['members'] as $member)
                                            <tr>
                                                <td class="px-3 py-2">{{ $member['source_name'] }}</td>
                                                <td class="px-3 py-2">
                                                    <div class="font-mono">{{ $member['external_id'] }}</div>
                                                    <div class="mt-0.5 text-zinc-400">{{ __('admin_imports.record', ['id' => $member['record_id']]) }}</div>
                                                </td>
                                                <td class="px-3 py-2 font-mono">{{ $member['latitude'] }}, {{ $member['longitude'] }}</td>
                                                <td class="px-3 py-2">
                                                    <div class="flex flex-wrap gap-2">
                                                        <form method="POST" action="{{ route('admin.imports.duplicate-groups.create-place') }}" data-confirm="{{ __('admin_imports.confirm_primary') }}" onsubmit="return confirm(this.dataset.confirm)">
                                                            @csrf
                                                            @foreach ($group['review_ids'] as $reviewId)
                                                                <input type="hidden" name="review_ids[]" value="{{ $reviewId }}">
                                                            @endforeach
                                                            <input type="hidden" name="primary_review_id" value="{{ $member['review_id'] }}">
                                                            <button class="rounded-lg bg-zinc-900 px-2.5 py-1.5 font-semibold text-white dark:bg-white dark:text-zinc-950">
                                                                {{ __('admin_imports.use_as_primary') }}
                                                            </button>
                                                        </form>

                                                        <form method="POST" action="{{ route('admin.imports.duplicate-groups.create-separate', $member['review_id']) }}" data-confirm="{{ __('admin_imports.confirm_separate') }}" onsubmit="return confirm(this.dataset.confirm)">
                                                            @csrf
                                                            <button class="rounded-lg border border-zinc-300 px-2.5 py-1.5 font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900">
                                                                {{ __('admin_imports.create_separate') }}
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if (!empty($group['candidates']))
                                <div class="mt-4">
                                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('admin_imports.link_existing') }}</div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($group['candidates'] as $candidate)
                                            <form method="POST" action="{{ route('admin.imports.duplicate-groups.link') }}" data-confirm="{{ __('admin_imports.confirm_group_link') }}" onsubmit="return confirm(this.dataset.confirm)">
                                                @csrf
                                                @foreach ($group['review_ids'] as $reviewId)
                                                    <input type="hidden" name="review_ids[]" value="{{ $reviewId }}">
                                                @endforeach
                                                <input type="hidden" name="place_id" value="{{ $candidate['place_id'] }}">
                                                <button class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900">
                                                    #{{ $candidate['place_id'] }} {{ $candidate['name'] }}
                                                    · {{ $candidate['distance_m'] }} m
                                                    · {{ number_format((float) $candidate['name_similarity'], 1, ',', '.') }} %
                                                </button>
                                            </form>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="mt-4 text-xs text-zinc-500">
                                    {{ __('admin_imports.no_existing_candidate') }}
                                </div>
                            @endif


                        </article>
                    @endforeach
                </div>

                <dialog id="duplicate-group-map-dialog" class="w-[min(1000px,94vw)] rounded-xl border border-zinc-300 bg-white p-0 shadow-2xl backdrop:bg-black/50 dark:border-zinc-700 dark:bg-zinc-950">
                    <div class="flex items-center justify-between border-b border-zinc-200 p-4 dark:border-zinc-800">
                        <div>
                            <div class="font-semibold" data-duplicate-map-title>{{ __('admin_imports.duplicate_group') }}</div>
                            <div class="mt-1 text-xs text-zinc-500" data-duplicate-map-subtitle>{{ __('admin_imports.external_and_candidates') }}</div>
                        </div>
                        <button type="button" data-duplicate-map-close class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700">{{ __('admin_imports.close') }}</button>
                    </div>
                    <div class="p-4">
                        <div class="mb-3 hidden rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200" data-duplicate-map-error></div>
                        <div class="w-full rounded-lg bg-zinc-100 dark:bg-zinc-900" style="height:60vh;min-height:320px;max-height:720px" data-duplicate-map></div>
                        <div class="mt-3 text-xs text-zinc-500">
                            {{ __('admin_imports.duplicate_map_legend') }}
                        </div>
                    </div>
                </dialog>
            </section>
        @endif

        @if (!empty($practiceCandidates))
            <section class="rounded-xl border border-amber-300 bg-amber-50/40 p-5 dark:border-amber-800 dark:bg-amber-950/10">
                <div>
                    <h2 class="text-lg font-semibold">{{ __('admin_imports.practice_candidates') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('admin_imports.practice_candidates_help') }}</p>
                </div>

                <div class="mt-4 grid gap-4 xl:grid-cols-2">
                    @foreach ($practiceCandidates as $sample)
                        @php($candidate = $sample['candidate'])
                        <article class="rounded-xl border border-amber-200 bg-white p-4 dark:border-amber-900 dark:bg-zinc-950">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">{{ $sample['title'] }}</div>
                                    <div class="mt-1 font-semibold">{{ $candidate['name'] }}</div>
                                    <div class="mt-1 text-xs text-zinc-500">{{ $candidate['source_name'] }} · <span class="font-mono">{{ $candidate['external_id'] }}</span></div>
                                </div>
                                <div class="text-right text-xs text-zinc-500">
                                    @if ($candidate['place_type'])<div>{{ $candidate['place_type'] }}</div>@endif
                                    @if ($candidate['parking_spaces'])<div>{{ __('admin_imports.parking_spaces_count', ['count' => $candidate['parking_spaces']]) }}</div>@endif
                                </div>
                            </div>

                            <p class="mt-3 text-xs text-zinc-600 dark:text-zinc-400">{{ $sample['reason'] }}</p>

                            <dl class="mt-3 grid gap-2 text-xs sm:grid-cols-2">
                                <div class="rounded-lg bg-zinc-50 p-2 dark:bg-zinc-900">
                                    <dt class="font-semibold text-zinc-400">{{ __('admin_imports.address') }}</dt>
                                    <dd class="mt-1">{{ $candidate['address_text'] ?: __('admin_imports.no_address') }}</dd>
                                </div>
                                <div class="rounded-lg bg-zinc-50 p-2 dark:bg-zinc-900">
                                    <dt class="font-semibold text-zinc-400">{{ __('admin_imports.operator') }}</dt>
                                    <dd class="mt-1">{{ $candidate['operator_name'] ?: __('admin_imports.no_value') }}</dd>
                                </div>
                                <div class="rounded-lg bg-zinc-50 p-2 dark:bg-zinc-900">
                                    <dt class="font-semibold text-zinc-400">{{ __('admin_imports.contacts') }}</dt>
                                    <dd class="mt-1">{{ $candidate['contact_count'] }}</dd>
                                </div>
                                <div class="rounded-lg bg-zinc-50 p-2 dark:bg-zinc-900">
                                    <dt class="font-semibold text-zinc-400">{{ __('admin_imports.source_features') }}</dt>
                                    <dd class="mt-1">{{ $candidate['available_feature_count'] }} {{ __('admin_imports.available') }}</dd>
                                    @if (($candidate['conflicting_feature_count'] ?? 0) > 0)
                                        <dd class="mt-1 text-amber-700 dark:text-amber-300">{{ $candidate['conflicting_feature_count'] }} {{ __('admin_imports.source_conflicts') }}</dd>
                                    @endif
                                </div>
                            </dl>

                            @if (!empty($candidate['available_features']))
                                <div class="mt-3 text-xs text-zinc-500">{{ collect($candidate['available_features'])->take(10)->pluck('label')->implode(', ') }}@if(count($candidate['available_features']) > 10) +{{ count($candidate['available_features']) - 10 }}@endif</div>
                            @endif
                            @if (!empty($candidate['conflicting_features']))
                                <div class="mt-2 text-xs text-amber-700 dark:text-amber-300">
                                    Quellkonflikt: {{ collect($candidate['conflicting_features'])->pluck('label')->implode(', ') }}
                                </div>
                            @endif

                            <div class="mt-4 flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('admin.imports.candidates.create-place', $candidate['id']) }}" data-confirm="{{ __('admin_imports.confirm_practice') }}" onsubmit="return confirm(this.dataset.confirm)">
                                    @csrf
                                    <button class="rounded-lg bg-zinc-900 px-3 py-2 text-xs font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('admin_imports.practice_import') }}</button>
                                </form>

                                <a href="{{ route('admin.imports.index', ['candidate_search' => $candidate['external_id'], 'candidate_source' => $candidate['source_slug']]) }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold dark:border-zinc-700">{{ __('admin_imports.show_candidate_list') }}</a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-100/50 p-3 text-xs text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                    {{ __('admin_imports.practice_after_help') }}
                </div>
            </section>
        @endif


        <section class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">{{ __('admin_imports.external_images') }}</h2>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('admin_imports.external_images_help') }}</p>
                    <p class="mt-1 text-xs text-zinc-400">
                        {{ $candidateSource ? __('admin_imports.open_for_source', ['count' => number_format($externalMediaPending, 0, ',', '.')]) : __('admin_imports.open_total', ['count' => number_format($externalMediaPending, 0, ',', '.')]) }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <form
                        id="external-media-selected"
                        method="POST"
                        action="{{ route('admin.imports.media.import-selected') }}"
                        class="flex flex-wrap items-center gap-2"
                        data-confirm="{{ __('admin_imports.confirm_images_selected') }}" onsubmit="return confirm(this.dataset.confirm)"
                    >
                        @csrf
                        <label class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-xs text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                            <input type="checkbox" data-external-media-select-all class="rounded">
                            {{ __('admin_imports.all_displayed') }}
                        </label>
                        <span class="rounded-lg border border-zinc-200 px-3 py-2 text-xs text-zinc-500 dark:border-zinc-800">
                            {{ __('admin_imports.selected_label') }}: <strong data-external-media-selected-count>0</strong>
                        </span>
                        <button
                            type="submit"
                            data-external-media-submit
                            disabled
                            class="rounded-lg bg-zinc-900 px-3 py-2 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40 dark:bg-white dark:text-zinc-950"
                        >
                            {{ __('admin_imports.take_selected') }}
                        </button>
                    </form>

                    <button
                        type="button"
                        data-external-media-import-all
                        data-endpoint="{{ route('admin.imports.media.import-all-batch') }}"
                        data-source="{{ $candidateSource }}"
                        class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700"
                        @disabled($externalMediaPending === 0)
                    >
                        {{ __('admin_imports.all_open_images') }}
                    </button>
                    <span class="hidden text-xs text-zinc-500" data-external-media-progress></span>
                </div>
            </div>

            @if ($externalMediaPending > 60)
                <div class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:border-amber-900 dark:bg-amber-950/20 dark:text-amber-200">
                    {{ __('admin_imports.external_images_preview', ['count' => number_format($externalMediaPending, 0, ',', '.')]) }}
                </div>
            @endif

            @if (!empty($externalMedia))
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    @foreach ($externalMedia as $media)
                        <article class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
                            <div class="aspect-[4/3] bg-zinc-100 dark:bg-zinc-900">
                                <img
                                    src="{{ $media['url'] }}"
                                    alt="{{ __('admin_imports.external_image_alt', ['place' => $media['place_name']]) }}"
                                    loading="lazy"
                                    referrerpolicy="no-referrer"
                                    class="h-full w-full object-cover"
                                >
                            </div>
                            <div class="space-y-2 p-3">
                                <div class="flex items-start gap-2">
                                    <input
                                        type="checkbox"
                                        name="media_keys[]"
                                        value="{{ $media['key'] }}"
                                        form="external-media-selected"
                                        data-external-media-select
                                        class="mt-1 rounded"
                                    >
                                    <div class="min-w-0">
                                        <a href="{{ route('places.show', $media['place_slug']) }}" class="font-medium hover:underline">{{ $media['place_name'] }}</a>
                                        <div class="mt-1 text-xs text-zinc-500">{{ $media['source_name'] }}</div>
                                    </div>
                                </div>
                                <div class="text-xs text-zinc-500">
                                    {{ $media['license'] }}
                                    @if($media['author']) · {{ $media['author'] }} @endif
                                    @if($media['width'] && $media['height']) · {{ $media['width'] }}×{{ $media['height'] }} @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700">
                    {{ __('admin_imports.no_external_images') }}
                </div>
            @endif
        </section>


        <section class="space-y-3">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">{{ __('admin_imports.new_candidates') }}</h2>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('admin_imports.candidate_help') }}</p>
                </div>

                <form
                    id="candidate-bulk-create"
                    method="POST"
                    action="{{ route('admin.imports.candidates.bulk-create') }}"
                    class="flex flex-wrap items-center gap-2"
                    data-candidate-bulk-form
                    data-confirm="{{ __('admin_imports.confirm_candidates_selected') }}" onsubmit="return confirm(this.dataset.confirm)"
                >
                    @csrf
                    <label class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-xs text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                        <input type="checkbox" data-candidate-select-all class="rounded">
                        {{ __('admin_imports.all_page') }}
                    </label>
                    <div class="rounded-lg border border-zinc-200 px-3 py-2 text-xs text-zinc-500 dark:border-zinc-800">
                        {{ __('admin_imports.selected_label') }}: <strong data-candidate-selected-count>0</strong>
                    </div>
                    <button
                        type="submit"
                        data-candidate-bulk-submit
                        disabled
                        class="rounded-lg bg-zinc-900 px-3 py-2 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40 dark:bg-white dark:text-zinc-950"
                    >
                        {{ __('admin_imports.take_selected') }}
                    </button>
                </form>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        data-candidate-create-all
                        data-endpoint="{{ route('admin.imports.candidates.create-all-batch') }}"
                        data-source="{{ $candidateSource }}"
                        class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700"
                        @disabled($summary['new_candidates'] === 0)
                    >
                        {{ __('admin_imports.all_open_candidates') }}
                    </button>
                    <span class="hidden text-xs text-zinc-500" data-candidate-create-all-progress></span>
                </div>
            </div>

            @error('candidate_bulk')
                <div class="rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">{{ $message }}</div>
            @enderror

            <form method="GET" class="grid gap-3 rounded-xl border border-zinc-200 p-4 md:grid-cols-[1fr_260px_auto] dark:border-zinc-800">
                <input
                    name="candidate_search"
                    value="{{ $candidateSearch }}"
                    placeholder="{{ __('admin_imports.search_name_or_id') }}"
                    class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                >
                <select name="candidate_source" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    <option value="">{{ __('admin_imports.all_sources') }}</option>
                    @foreach ($sources as $source)
                        <option value="{{ $source->slug }}" @selected($candidateSource === $source->slug)>{{ $source->name }}</option>
                    @endforeach
                </select>
                <button class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">Filtern</button>
                <input type="hidden" name="review_status" value="{{ $reviewStatus }}">
            </form>

            <div class="space-y-3">
                @forelse ($candidates as $candidate)
                    <article class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                        <div class="flex items-start gap-3">
                            <label class="mt-1" title="{{ __('admin_imports.bulk_select_title') }}">
                                <input
                                    type="checkbox"
                                    name="candidate_ids[]"
                                    value="{{ $candidate->id }}"
                                    form="candidate-bulk-create"
                                    data-candidate-select
                                    class="rounded"
                                >
                            </label>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="font-semibold">{{ $candidate->name }}</div>
                                        <div class="mt-1 text-xs text-zinc-500">
                                            {{ $candidate->source_name }} · <span class="font-mono">{{ $candidate->external_id }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right text-xs text-zinc-500">
                                        @if ($candidate->place_type)<div>{{ $candidate->place_type }}</div>@endif
                                        @if ($candidate->parking_spaces)<div>{{ __('admin_imports.parking_spaces_count', ['count' => $candidate->parking_spaces]) }}</div>@endif
                                    </div>
                                </div>

                                <div class="mt-3 grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-4">
                                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('admin_imports.coordinates') }}</div>
                                        <div class="mt-1 font-mono text-xs">{{ $candidate->latitude }}, {{ $candidate->longitude }}</div>
                                    </div>
                                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('admin_imports.address') }}</div>
                                        <div class="mt-1 text-xs">{{ $candidate->address_text ?: __('admin_imports.no_address') }}</div>
                                    </div>
                                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('admin_imports.operator') }}</div>
                                        <div class="mt-1 text-xs">{{ $candidate->operator_name ?: __('admin_imports.no_value') }}</div>
                                    </div>
                                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('admin_imports.source_equipment') }}</div>
                                        <div class="mt-1 text-xs">
                                            @if (!empty($candidate->available_features))
                                                {{ collect($candidate->available_features)->take(8)->pluck('label')->implode(', ') }}
                                                @if (count($candidate->available_features) > 8)
                                                    +{{ count($candidate->available_features) - 8 }}
                                                @endif
                                            @else
                                                {{ __('admin_imports.no_conflict_free_features') }}
                                            @endif
                                            @if (!empty($candidate->conflicting_features))
                                                <div class="mt-1 text-amber-700 dark:text-amber-300">
                                                    Quellkonflikt: {{ collect($candidate->conflicting_features)->pluck('label')->implode(', ') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <form method="POST" action="{{ route('admin.imports.candidates.create-place', $candidate->id) }}" data-confirm="{{ __('admin_imports.confirm_candidate') }}" onsubmit="return confirm(this.dataset.confirm)">
                                        @csrf
                                        <button class="rounded-lg bg-zinc-900 px-3 py-2 text-xs font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('admin_imports.take_place') }}</button>
                                    </form>

                                    <details class="relative">
                                        <summary class="cursor-pointer rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold dark:border-zinc-700">{{ __('admin_imports.ignore') }}</summary>
                                        <form method="POST" action="{{ route('admin.imports.candidates.ignore', $candidate->id) }}" class="absolute left-0 z-20 mt-2 w-80 rounded-xl border border-zinc-200 bg-white p-3 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                                            @csrf
                                            <label class="block text-xs font-medium">{{ __('admin_imports.internal_note_optional') }}</label>
                                            <textarea name="note" rows="2" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-950" placeholder="{{ __('admin_imports.candidate_ignore_reason') }}"></textarea>
                                            <button class="mt-2 w-full rounded-lg bg-zinc-900 px-3 py-2 text-xs font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('admin_imports.ignore_candidate') }}</button>
                                        </form>
                                    </details>
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700">
                        {{ __('admin_imports.no_candidates') }}
                    </div>
                @endforelse
            </div>

            {{ $candidates->links() }}
        </section>

        <section class="space-y-3">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 id="review-queue" class="scroll-mt-4 text-lg font-semibold">Review-Queue</h2>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('admin_imports.reviews_help') }}</p>
                    @if (($summary['pending_research_conflicts'] ?? 0) > 0)
                        <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">{{ __('admin_imports.research_conflicts_notice', ['count' => $summary['pending_research_conflicts']]) }}</p>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if (($summary['pending_other_reviews'] ?? 0) > 0)
                        <form id="selected-reviews-form" method="POST" class="flex flex-wrap items-center gap-2">
                            @csrf
                            <button
                                type="submit"
                                formaction="{{ route('admin.imports.reviews.defer-selected') }}"
                                class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold dark:border-zinc-700"
                                data-confirm="{{ __('admin_imports.confirm_defer_selected') }}" onclick="return confirm(this.dataset.confirm)"
                            >
                                {{ __('admin_imports.defer_selected') }}
                            </button>
                            <button
                                type="submit"
                                formaction="{{ route('admin.imports.reviews.ignore-selected') }}"
                                class="rounded-lg border border-red-400 px-3 py-2 text-sm font-semibold text-red-700 dark:border-red-800 dark:text-red-300"
                                data-confirm="{{ __('admin_imports.confirm_ignore_selected') }}" onclick="return confirm(this.dataset.confirm)"
                            >
                                {{ __('admin_imports.ignore_selected') }}
                            </button>
                        </form>
                    @endif
                    @if (($summary['pending_research_updates'] ?? 0) > 0)
                        <form method="POST" action="{{ route('admin.imports.research-reviews.approve-all') }}" data-confirm="{{ __('admin_imports.confirm_research_all', ['count' => $summary['pending_research_updates']]) }}" onsubmit="return confirm(this.dataset.confirm)">
                            @csrf
                            <button class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500">
                                {{ __('admin_imports.approve_research_all', ['count' => $summary['pending_research_updates']]) }}
                            </button>
                        </form>
                    @endif
                    @if (($summary['pending_other_reviews'] ?? 0) > 0)
                        <details class="relative">
                            <summary class="cursor-pointer list-none rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold dark:border-zinc-700">
                                {{ __('admin_imports.defer_all', ['count' => $summary['pending_other_reviews']]) }}
                            </summary>
                            <div class="absolute right-0 z-30 mt-2 w-96 max-w-[90vw] rounded-xl border border-zinc-200 bg-white p-4 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                                <div class="text-sm font-semibold">{{ __('admin_imports.defer_all_title') }}</div>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ __('admin_imports.defer_all_help') }}
                                </p>
                                <p class="mt-2 text-xs text-zinc-500">
                                    {{ __('admin_imports.research_bulk_excluded') }}
                                </p>
                                <form method="POST" action="{{ route('admin.imports.reviews.defer-all') }}" class="mt-3">
                                    @csrf
                                    <button class="w-full rounded-lg bg-zinc-900 px-3 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">
                                        {{ __('admin_imports.defer_all_confirm') }}
                                    </button>
                                </form>
                            </div>
                        </details>

                        <details class="relative">
                            <summary class="cursor-pointer list-none rounded-lg border border-red-400 px-3 py-2 text-sm font-semibold text-red-700 dark:border-red-800 dark:text-red-300">
                                {{ __('admin_imports.ignore_all', ['count' => $summary['pending_other_reviews']]) }}
                            </summary>
                            <div class="absolute right-0 z-30 mt-2 w-96 max-w-[90vw] rounded-xl border border-red-200 bg-white p-4 shadow-xl dark:border-red-900 dark:bg-zinc-900">
                                <div class="text-sm font-semibold text-red-700 dark:text-red-300">{{ __('admin_imports.ignore_all_title') }}</div>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ __('admin_imports.ignore_all_help') }}
                                </p>
                                <p class="mt-2 text-xs text-zinc-500">
                                    {{ __('admin_imports.ignore_all_protected') }}
                                </p>
                                <form method="POST" action="{{ route('admin.imports.reviews.ignore-all') }}" class="mt-3">
                                    @csrf
                                    <button class="w-full rounded-lg bg-red-700 px-3 py-2 text-sm font-semibold text-white hover:bg-red-800">
                                        {{ __('admin_imports.ignore_all_confirm') }}
                                    </button>
                                </form>
                            </div>
                        </details>
                    @endif
                    <form method="GET">
                        <select name="review_status" onchange="this.form.submit()" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            @foreach (['pending' => __('admin_imports.status_pending'), 'superseded' => __('admin_imports.status_superseded'), 'resolved' => __('admin_imports.status_resolved'), 'all' => __('admin_imports.status_all')] as $value => $label)
                                <option value="{{ $value }}" @selected($reviewStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            <div class="space-y-3">
                @forelse ($reviews as $review)
                    @php($details = $review->details_array)
                    @php($reviewTypeLabel = match ($review->type) {
                        'missing_coordinates' => __('admin_imports.type_missing_coordinates'),
                        'possible_duplicate' => __('admin_imports.type_possible_duplicate'),
                        'source_missing' => __('admin_imports.type_source_missing'),
                        'possible_reopen' => __('admin_imports.type_possible_reopen'),
                        'nearby_deleted_place' => __('admin_imports.type_nearby_deleted_place'),
                        'deleted_place_changed' => __('admin_imports.type_deleted_place_changed'),
                        'research_update' => __('admin_imports.type_research_update'),
                        'research_conflict' => __('admin_imports.type_research_conflict'),
                        default => $review->type,
                    })
                    <article class="rounded-xl border-2 border-zinc-300 p-4 dark:border-zinc-700">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                @if ($review->status === 'pending' && !in_array($review->type, ['research_update', 'research_conflict', 'external_duplicate_group', 'source_missing', 'possible_reopen', 'nearby_deleted_place', 'deleted_place_changed'], true))
                                    <input
                                        type="checkbox"
                                        name="review_ids[]"
                                        value="{{ $review->id }}"
                                        form="selected-reviews-form"
                                        class="mt-1 h-4 w-4 rounded border-zinc-300"
                                        aria-label="Review #{{ $review->id }} markieren"
                                    >
                                @endif
                                <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold">{{ $details['external_name'] ?? $details['name'] ?? $review->external_id ?? __('admin_imports.external_record') }}</span>
                                    <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium dark:bg-zinc-800">{{ $reviewTypeLabel }}</span>
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-xs font-semibold',
                                        'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-200' => $review->severity === 'error',
                                        'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-200' => $review->severity !== 'error',
                                    ])>{{ $review->severity }}</span>
                                </div>
                                <div class="mt-1 text-xs text-zinc-500">{{ $review->source_name }} · {{ $review->external_id }}</div>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-2">
                                <div class="text-xs text-zinc-500">{{ \App\Support\LocalTime::parse($review->created_at)->format('d.m.Y H:i') }}</div>
                                @if ($review->type === 'possible_duplicate' && $review->status === 'pending')
                                    <button
                                        type="button"
                                        data-review-duplicate-map-open
                                        data-endpoint="{{ route('admin.imports.reviews.map', $review->id) }}"
                                        class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900"
                                    >
                                        {{ __('admin_imports.map_check') }}
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if ($review->type === 'missing_coordinates')
                            <div class="mt-3 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-900">{{ __('admin_imports.missing_coordinates_help') }}</div>
                        @endif

                        @if ($review->type === 'source_missing')
                            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-900 dark:bg-amber-950/20">
                                {{ __('admin_imports.source_missing_help') }}
                                @if ($review->place_slug)
                                    <div class="mt-2">
                                        <a href="{{ route('places.show', $review->place_slug) }}" target="_blank" rel="noopener noreferrer" class="font-semibold underline">
                                            {{ __('admin_imports.check_camperwolf_place', ['place' => $review->place_name ?? ('#'.$review->place_id)]) }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if (in_array($review->type, ['nearby_deleted_place', 'deleted_place_changed'], true))
                            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-900 dark:bg-amber-950/20">
                                <div class="font-semibold">{{ __('admin_imports.deleted_place_review_help') }}</div>
                                <div class="mt-1 text-xs text-zinc-600 dark:text-zinc-300">
                                    @if ($review->type === 'nearby_deleted_place')
                                        {{ __('admin_imports.deleted_place_review_meta', [
                                            'distance' => $details['distance_m'] ?? '?',
                                            'type' => $details['deleted_place_type'] ?? '?',
                                            'reason' => __('admin.place_deletion.reasons.'.($details['deletion_reason'] ?? 'other')),
                                        ]) }}
                                    @else
                                        {{ __('admin_imports.deleted_place_changed_meta', [
                                            'type' => $details['suggested_place_type'] ?? '?',
                                            'reason' => __('admin.place_deletion.reasons.'.($details['deletion_reason'] ?? 'other')),
                                        ]) }}
                                    @endif
                                </div>
                                @if (!empty($details['deletion_note']))
                                    <div class="mt-2 text-xs text-zinc-500">{{ $details['deletion_note'] }}</div>
                                @endif
                            </div>
                        @endif

                        @if ($review->type === 'possible_reopen')
                            <div class="mt-3 rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm dark:border-sky-900 dark:bg-sky-950/20">
                                {{ __('admin_imports.possible_reopen_help') }}
                                <div class="mt-1 text-xs text-zinc-500">
                                    {{ __('admin_imports.camperwolf') }}: <strong>{{ $details['current_opening_status'] ?? __('admin_imports.status_closed') }}</strong>
                                    · {{ __('admin_imports.source') }}: <strong>{{ $details['source_opening_status'] ?? 'open' }}</strong>
                                </div>
                                @if ($review->place_slug)
                                    <div class="mt-2">
                                        <a href="{{ route('places.show', $review->place_slug) }}" target="_blank" rel="noopener noreferrer" class="font-semibold underline">
                                            {{ __('admin_imports.check_camperwolf_place', ['place' => $review->place_name ?? ('#'.$review->place_id)]) }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if (in_array($review->type, ['research_update', 'research_conflict'], true))
                            <div class="mt-3 space-y-3">
                                <div class="rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-900">
                                    <div class="flex flex-wrap gap-x-4 gap-y-1">
                                        <span><strong>Autor:</strong> {{ $details['author'] ?? '—' }}</span>
                                        <span><strong>{{ __('admin_imports.source') }}:</strong>
                                            @if (!empty($details['source_url']))
                                                <a href="{{ $details['source_url'] }}" target="_blank" rel="noopener noreferrer" class="underline">{{ $details['source_label'] ?? $details['source_url'] }}</a>
                                            @else
                                                {{ $details['source_label'] ?? '—' }}
                                            @endif
                                        </span>
                                        @if (!empty($details['researched_at']))
                                            <span><strong>Recherchiert:</strong> {{ \App\Support\LocalTime::parse($details['researched_at'])->format('d.m.Y H:i') }}</span>
                                        @endif
                                    </div>
                                    @if (!empty($details['notes']))
                                        <div class="mt-2 text-xs text-zinc-500">{{ $details['notes'] }}</div>
                                    @endif
                                </div>

                                @if (!empty($details['changes']))
                                    <div class="overflow-x-auto">
                                        <table class="min-w-[700px] w-full text-left text-sm">
                                            <thead class="text-xs text-zinc-500">
                                                <tr><th class="pb-2">{{ __('admin_imports.field') }}</th><th class="pb-2">{{ __('admin_imports.current') }}</th><th class="pb-2">{{ __('admin_imports.research') }}</th><th class="pb-2">{{ __('admin_imports.rating') }}</th></tr>
                                            </thead>
                                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                                @foreach ($details['changes'] as $field => $change)
                                                    <tr>
                                                        <td class="py-2 font-mono text-xs">{{ $field }}</td>
                                                        <td class="py-2">{{ is_scalar($change['current'] ?? null) && ($change['current'] ?? '') !== '' ? $change['current'] : '—' }}</td>
                                                        <td class="py-2 font-medium">{{ is_scalar($change['proposed'] ?? null) ? $change['proposed'] : json_encode($change['proposed'] ?? null) }}</td>
                                                        <td class="py-2">
                                                            @if (!empty($change['conflict']))
                                                                <span class="inline-flex items-center rounded-full border border-amber-500 bg-amber-200 px-2 py-0.5 text-xs font-semibold text-amber-950 dark:border-amber-400 dark:bg-amber-300 dark:text-amber-950">Quellkonflikt</span>
                                                            @elseif (($change['status'] ?? '') === 'new')
                                                                <span class="inline-flex items-center rounded-full border border-emerald-500 bg-emerald-200 px-2 py-0.5 text-xs font-semibold dark:border-emerald-400 dark:bg-emerald-300" style="color:#000000 !important;">Neue Angabe</span>
                                                            @else
                                                                <span class="inline-flex items-center rounded-full border border-sky-500 bg-sky-200 px-2 py-0.5 text-xs font-semibold text-sky-950 dark:border-sky-400 dark:bg-sky-300 dark:text-sky-950">Abweichung</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if (!empty($details['candidates']) && $review->type !== 'possible_duplicate')
                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-[650px] w-full text-left text-sm">
                                    <thead class="text-xs text-zinc-500"><tr><th class="pb-2">{{ __('admin_imports.candidate_column') }}</th><th class="pb-2">{{ __('admin_imports.distance_column') }}</th><th class="pb-2">{{ __('admin_imports.name_similarity_column') }}</th></tr></thead>
                                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                        @foreach ($details['candidates'] as $candidate)
                                            <tr>
                                                <td class="py-2">
                                                    @if (!empty($candidate['slug']))
                                                        <a class="font-medium hover:underline" href="{{ route('places.show', $candidate['slug']) }}">{{ $candidate['name'] }}</a>
                                                    @else
                                                        {{ $candidate['name'] }}
                                                    @endif
                                                    <span class="text-xs text-zinc-400">#{{ $candidate['place_id'] }}</span>
                                                </td>
                                                <td class="py-2">{{ $candidate['distance_m'] }} m</td>
                                                <td class="py-2">{{ number_format((float) $candidate['name_similarity'], 1, ',', '.') }} %</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if ($review->status === 'pending')
                            @if (in_array($review->type, ['research_update', 'research_conflict'], true))
                                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-900">
                                    <div class="text-xs text-zinc-500">
                                        {{ __('admin_imports.version_safe') }}
                                    </div>
                                    <form method="POST" action="{{ route('admin.imports.research-reviews.approve', $review->id) }}" data-confirm="{{ __('admin_imports.confirm_research_one') }}" onsubmit="return confirm(this.dataset.confirm)">
                                        @csrf
                                        <button class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500">{{ __('admin_imports.apply_research') }}</button>
                                    </form>
                                </div>
                            @elseif ($review->type === 'source_missing')
                                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                                    <form method="POST" action="{{ route('admin.imports.reviews.resolve-source-missing', $review->id) }}" data-confirm="{{ __('admin_imports.confirm_missing_resolve') }}" onsubmit="return confirm(this.dataset.confirm)">
                                        @csrf
                                        <button class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500">{{ __('admin_imports.resolve_checked') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.imports.reviews.defer', $review->id) }}">
                                        @csrf
                                        <button class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold dark:border-zinc-700">{{ __('admin_imports.defer') }}</button>
                                    </form>
                                </div>
                            @elseif (in_array($review->type, ['nearby_deleted_place', 'deleted_place_changed'], true))
                                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                                    <form method="POST" action="{{ route('admin.imports.reviews.deleted-place.create', $review->id) }}" data-confirm="{{ __('admin_imports.confirm_deleted_place_create') }}" onsubmit="return confirm(this.dataset.confirm)">
                                        @csrf
                                        <button class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500">{{ __('admin_imports.deleted_place_create') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.imports.reviews.deleted-place.ignore', $review->id) }}">
                                        @csrf
                                        <button class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold dark:border-zinc-700">{{ __('admin_imports.deleted_place_keep') }}</button>
                                    </form>
                                </div>
                            @elseif ($review->type === 'possible_reopen')
                                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                                    <form method="POST" action="{{ route('admin.imports.reviews.reopen', $review->id) }}" data-confirm="{{ __('admin_imports.confirm_reopen') }}" onsubmit="return confirm(this.dataset.confirm)">
                                        @csrf
                                        <button class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500">{{ __('admin_imports.set_open') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.imports.reviews.resolve-reopen', $review->id) }}" data-confirm="{{ __('admin_imports.confirm_keep_closed') }}" onsubmit="return confirm(this.dataset.confirm)">
                                        @csrf
                                        <button class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold dark:border-zinc-700">{{ __('admin_imports.keep_closed_checked') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.imports.reviews.defer', $review->id) }}">
                                        @csrf
                                        <button class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold dark:border-zinc-700">{{ __('admin_imports.defer') }}</button>
                                    </form>
                                </div>
                            @elseif ($review->type === 'possible_duplicate' && !empty($details['candidates']))
                                <div class="mt-5 grid gap-5 border-t border-zinc-200 pt-5 dark:border-zinc-800 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.5fr)]">
                                    <section class="min-w-0 rounded-xl border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-900/50">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('admin_imports.new_external_record') }}</div>
                                        <div class="mt-2 break-words text-lg font-bold leading-snug text-zinc-950 dark:text-white">
                                            {{ $details['external_name'] ?? $details['name'] ?? $review->external_id ?? __('admin_imports.external_record') }}
                                        </div>

                                        <div class="mt-5 flex max-w-sm flex-col items-stretch gap-2">
                                            <form method="POST" action="{{ route('admin.imports.reviews.create-place', $review->id) }}" data-confirm="{{ __('admin_imports.confirm_review_create') }}" onsubmit="return confirm(this.dataset.confirm)">
                                                @csrf
                                                <button class="w-full rounded-lg border border-amber-400 bg-amber-50 px-3 py-2.5 text-left text-xs font-semibold text-amber-900 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200 dark:hover:bg-amber-950/50">{{ __('admin_imports.create_new_place') }}</button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.imports.reviews.defer', $review->id) }}">
                                                @csrf
                                                <button class="w-full rounded-lg border border-zinc-300 px-3 py-2.5 text-left text-xs font-semibold hover:bg-white dark:border-zinc-700 dark:hover:bg-zinc-950">{{ __('admin_imports.defer') }}</button>
                                            </form>

                                            <details class="relative">
                                                <summary class="cursor-pointer list-none rounded-lg border border-red-300 px-3 py-2.5 text-xs font-semibold text-red-700 dark:border-red-900 dark:text-red-300">{{ __('admin_imports.ignore') }}</summary>
                                                <form method="POST" action="{{ route('admin.imports.reviews.ignore', $review->id) }}" class="mt-2 rounded-xl border border-zinc-200 bg-white p-3 shadow-lg dark:border-zinc-700 dark:bg-zinc-950">
                                                    @csrf
                                                    <label class="block text-xs font-medium">{{ __('admin_imports.internal_note_optional') }}</label>
                                                    <textarea name="note" rows="2" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-950" placeholder="{{ __('admin_imports.why_ignore') }}"></textarea>
                                                    <button class="mt-2 w-full rounded-lg bg-red-700 px-3 py-2 text-xs font-semibold text-white">{{ __('admin_imports.finish_ignore') }}</button>
                                                </form>
                                            </details>
                                        </div>
                                    </section>

                                    <section class="min-w-0">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('admin_imports.existing_candidate_places') }}</div>
                                        <div class="mt-2 space-y-3">
                                            @foreach ($details['candidates'] as $candidate)
                                                <div class="rounded-xl border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-900/50">
                                                    <div class="break-words text-base font-bold leading-snug text-zinc-950 dark:text-white">
                                                        @if (!empty($candidate['slug']))
                                                            <a class="hover:underline" href="{{ route('places.show', $candidate['slug']) }}">{{ $candidate['name'] }}</a>
                                                        @else
                                                            {{ $candidate['name'] }}
                                                        @endif
                                                        <span class="ml-1 text-xs font-normal text-zinc-400">#{{ $candidate['place_id'] }}</span>
                                                    </div>
                                                    <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-zinc-500">
                                                        <span>{{ __('admin_imports.distance_column') }}: <strong class="text-zinc-700 dark:text-zinc-300">{{ $candidate['distance_m'] }} m</strong></span>
                                                        <span>{{ __('admin_imports.name_similarity_column') }}: <strong class="text-zinc-700 dark:text-zinc-300">{{ number_format((float) $candidate['name_similarity'], 1, ',', '.') }} %</strong></span>
                                                    </div>

                                                    <div class="mt-4 flex flex-wrap gap-2">
                                                        <form method="POST" action="{{ route('admin.imports.reviews.link', $review->id) }}">
                                                            @csrf
                                                            <input type="hidden" name="place_id" value="{{ $candidate['place_id'] }}">
                                                            <button class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900">
                                                                {{ __('admin_imports.link_this_place') }}
                                                            </button>
                                                        </form>

                                                        <form
                                                            method="POST"
                                                            action="{{ route('admin.imports.reviews.promote-candidate', $review->id) }}"
                                                            data-confirm="{{ __('admin_imports.confirm_promote_candidate') }}"
                                                            onsubmit="return confirm(this.dataset.confirm)"
                                                        >
                                                            @csrf
                                                            <input type="hidden" name="place_id" value="{{ $candidate['place_id'] }}">
                                                            <button class="rounded-lg border border-amber-400 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200 dark:hover:bg-amber-950/50">
                                                                {{ __('admin_imports.promote_candidate_explicit') }}
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </section>
                                </div>

                                @if (!empty($details['deferred']))
                                    <div class="mt-3 text-xs text-amber-700 dark:text-amber-300">
                                        {{ __('admin_imports.deferred') }}{{ !empty($details['deferred']['note']) ? ': '.$details['deferred']['note'] : '' }}
                                    </div>
                                @endif
                            @else
                            <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                                @if (!empty($details['candidates']))
                                    <div class="mb-3">
                                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('admin_imports.link_existing') }}</div>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($details['candidates'] as $candidate)
                                                <div class="flex flex-wrap gap-2 rounded-lg border border-zinc-200 p-2 dark:border-zinc-800">
                                                    <form method="POST" action="{{ route('admin.imports.reviews.link', $review->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="place_id" value="{{ $candidate['place_id'] }}">
                                                        <button class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900">
                                                            {{ $candidate['name'] }} · {{ $candidate['distance_m'] }} m
                                                        </button>
                                                    </form>

                                                    <form
                                                        method="POST"
                                                        action="{{ route('admin.imports.reviews.promote-candidate', $review->id) }}"
                                                        data-confirm="{{ __('admin_imports.confirm_promote_candidate') }}"
                                                        onsubmit="return confirm(this.dataset.confirm)"
                                                    >
                                                        @csrf
                                                        <input type="hidden" name="place_id" value="{{ $candidate['place_id'] }}">
                                                        <button class="rounded-lg border border-amber-400 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200 dark:hover:bg-amber-950/50">
                                                            {{ __('admin_imports.promote_candidate') }}
                                                        </button>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($review->type === 'missing_coordinates')
                                        <button
                                            type="button"
                                            data-coordinate-review-open
                                            data-name="{{ e($details['external_name'] ?? $details['name'] ?? $review->external_id ?? __('admin_imports.external_record')) }}"
                                            data-endpoint="{{ route('admin.imports.reviews.coordinates', $review->id) }}"
                                            class="rounded-lg bg-zinc-900 px-3 py-2 text-xs font-semibold text-white dark:bg-white dark:text-zinc-950"
                                        >
                                            {{ __('admin_imports.position_add') }}
                                        </button>
                                    @else
                                        <form method="POST" action="{{ route('admin.imports.reviews.create-place', $review->id) }}" data-confirm="{{ __('admin_imports.confirm_review_create') }}" onsubmit="return confirm(this.dataset.confirm)">
                                            @csrf
                                            <button class="rounded-lg bg-zinc-900 px-3 py-2 text-xs font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('admin_imports.create_new_place') }}</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('admin.imports.reviews.defer', $review->id) }}">
                                        @csrf
                                        <button class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold dark:border-zinc-700">{{ __('admin_imports.defer') }}</button>
                                    </form>

                                    <details class="relative">
                                        <summary class="cursor-pointer rounded-lg border border-red-300 px-3 py-2 text-xs font-semibold text-red-700 dark:border-red-900 dark:text-red-300">{{ __('admin_imports.ignore') }}</summary>
                                        <form method="POST" action="{{ route('admin.imports.reviews.ignore', $review->id) }}" class="absolute right-0 z-20 mt-2 w-80 rounded-xl border border-zinc-200 bg-white p-3 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                                            @csrf
                                            <label class="block text-xs font-medium">{{ __('admin_imports.internal_note_optional') }}</label>
                                            <textarea name="note" rows="2" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-950" placeholder="{{ __('admin_imports.why_ignore') }}"></textarea>
                                            <button class="mt-2 w-full rounded-lg bg-red-700 px-3 py-2 text-xs font-semibold text-white">{{ __('admin_imports.finish_ignore') }}</button>
                                        </form>
                                    </details>
                                </div>

                                @if (!empty($details['deferred']))
                                    <div class="mt-3 text-xs text-amber-700 dark:text-amber-300">
                                        {{ __('admin_imports.deferred') }}{{ !empty($details['deferred']['note']) ? ': '.$details['deferred']['note'] : '' }}
                                    </div>
                                @endif
                            </div>
                            @endif
                        @else
                            <div class="mt-3 text-xs text-zinc-500">Status: {{ $review->status }}@if($review->resolution_note) · {{ $review->resolution_note }}@endif</div>
                        @endif
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700">{{ __('admin_imports.no_reviews') }}</div>
                @endforelse
            </div>

            {{ $reviews->links() }}
        </section>

        <dialog id="review-duplicate-map-dialog" class="w-[min(1000px,94vw)] rounded-xl border border-zinc-300 bg-white p-0 shadow-2xl backdrop:bg-black/50 dark:border-zinc-700 dark:bg-zinc-950">
            <div class="flex items-center justify-between border-b border-zinc-200 p-4 dark:border-zinc-800">
                <div>
                    <div class="font-semibold" data-review-map-title>{{ __('admin_imports.duplicate_review_title') }}</div>
                    <div class="mt-1 text-xs text-zinc-500">{{ __('admin_imports.duplicate_review_subtitle') }}</div>
                </div>
                <button type="button" data-review-map-close class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700">{{ __('admin_imports.close') }}</button>
            </div>
            <div class="p-4">
                <div class="mb-3 hidden rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200" data-review-map-error></div>
                <div class="w-full rounded-lg bg-zinc-100 dark:bg-zinc-900" style="height:60vh;min-height:320px;max-height:720px" data-review-map></div>
                <div class="mt-3 text-xs text-zinc-500">{{ __('admin_imports.duplicate_review_legend') }}</div>
            </div>
        </dialog>

        <dialog id="coordinate-review-dialog" class="w-[min(1000px,94vw)] rounded-xl border border-zinc-300 bg-white p-0 shadow-2xl backdrop:bg-black/50 dark:border-zinc-700 dark:bg-zinc-950">
            <form method="POST" data-coordinate-review-form>
                @csrf
                <div class="flex items-center justify-between border-b border-zinc-200 p-4 dark:border-zinc-800">
                    <div>
                        <div class="font-semibold" data-coordinate-review-title>{{ __('admin_imports.position_add') }}</div>
                        <div class="mt-1 text-xs text-zinc-500">{{ __('admin_imports.coordinate_help') }}</div>
                    </div>
                    <button type="button" data-coordinate-review-close class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700">{{ __('admin_imports.close') }}</button>
                </div>
                <div class="p-4">
                    <div class="relative">
                        <div class="flex gap-2">
                            <input type="search" autocomplete="off" data-coordinate-search placeholder="{{ __('admin_imports.search_address') }}" class="min-w-0 flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            <button type="button" data-coordinate-search-button class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold dark:border-zinc-700">{{ __('admin_imports.search') }}</button>
                        </div>
                        <div data-coordinate-search-results class="absolute z-[2000] mt-1 hidden max-h-64 w-full overflow-y-auto rounded-lg border border-zinc-300 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"></div>
                        <div data-coordinate-search-status class="mt-1 min-h-5 text-xs text-zinc-500">{{ __('admin_imports.coordinate_search_help') }}</div>
                    </div>
                    <div class="mt-3 w-full rounded-lg bg-zinc-100 dark:bg-zinc-900" style="height:55vh;min-height:320px;max-height:650px" data-coordinate-map></div>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="text-xs font-medium">{{ __('admin_imports.latitude') }}
                            <input type="number" step="0.0000001" min="-90" max="90" name="latitude" required data-coordinate-lat class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        </label>
                        <label class="text-xs font-medium">{{ __('admin_imports.longitude') }}
                            <input type="number" step="0.0000001" min="-180" max="180" name="longitude" required data-coordinate-lng class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        </label>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('admin_imports.save_coordinates_recheck') }}</button>
                    </div>
                </div>
            </form>
        </dialog>

        <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
            <h2 class="text-lg font-semibold">{{ __('admin_imports.known_sources') }}</h2>
            <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($sources as $source)
                    <div class="rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-900">
                        <div class="font-medium">{{ $source->name }}</div>
                        <div class="mt-1 font-mono text-xs text-zinc-400">{{ $source->slug }}</div>
                        <div class="mt-2 text-xs text-zinc-500">{{ $source->source_type }}@if($source->last_success_at) · {{ __('admin_imports.last_success_inline', ['date' => \App\Support\LocalTime::parse($source->last_success_at)->format(app()->getLocale() === 'de' ? 'd.m.Y H:i' : 'Y-m-d H:i')]) }}@endif</div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            (() => {
                const initImportForm = () => {
                    document.querySelectorAll('[data-import-form]').forEach((form) => {
                        if (form.dataset.bound) return;
                        form.dataset.bound = '1';
                        const adapter = form.querySelector('[data-import-adapter]');
                        const csvSource = form.querySelector('[data-csv-source]');
                        const sync = () => csvSource?.classList.toggle('hidden', adapter?.value !== 'csv');
                        adapter?.addEventListener('change', sync);
                        sync();
                    });
                };
                const initCandidateSelection = () => {
                    const counter = document.querySelector('[data-candidate-selected-count]');
                    const submit = document.querySelector('[data-candidate-bulk-submit]');
                    const selectAll = document.querySelector('[data-candidate-select-all]');
                    const boxes = [...document.querySelectorAll('[data-candidate-select]')];

                    const sync = () => {
                        const selected = boxes.filter((box) => box.checked).length;
                        if (counter) counter.textContent = selected.toString();
                        if (submit) submit.disabled = selected === 0;

                        if (selectAll) {
                            selectAll.checked = boxes.length > 0 && selected === boxes.length;
                            selectAll.indeterminate = selected > 0 && selected < boxes.length;
                        }
                    };

                    boxes.forEach((box) => {
                        if (box.dataset.bound) return;
                        box.dataset.bound = '1';
                        box.addEventListener('change', sync);
                    });

                    if (selectAll && !selectAll.dataset.bound) {
                        selectAll.dataset.bound = '1';
                        selectAll.addEventListener('change', () => {
                            boxes.forEach((box) => {
                                box.checked = selectAll.checked;
                            });
                            sync();
                        });
                    }

                    sync();
                };

                const initCreateAllCandidates = () => {
                    const button = document.querySelector('[data-candidate-create-all]');
                    const progress = document.querySelector('[data-candidate-create-all-progress]');
                    const token = document.querySelector('#candidate-bulk-create input[name="_token"]')?.value;

                    if (!button || button.dataset.bound || !token) return;
                    button.dataset.bound = '1';

                    button.addEventListener('click', async () => {
                        const source = button.dataset.source || '';
                        const scopeLabel = source
                            ? @js(__('admin_imports.js_scope_source'))
                            : @js(__('admin_imports.js_scope_all'));

                        if (!confirm(@js(__('admin_imports.js_confirm_all_candidates', ['scope' => '__SCOPE__'])).replace('__SCOPE__', scopeLabel))) {
                            return;
                        }

                        const originalText = button.textContent.trim();
                        let createdTotal = 0;
                        let initialTotal = null;

                        button.disabled = true;
                        if (progress) {
                            progress.classList.remove('hidden');
                            progress.textContent = @js(__('admin_imports.js_starting'));
                        }

                        try {
                            while (true) {
                                const response = await fetch(button.dataset.endpoint, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': token,
                                    },
                                    body: JSON.stringify({
                                        source_slug: source || null,
                                    }),
                                });

                                const payload = await response.json().catch(() => ({}));

                                if (!response.ok) {
                                    throw new Error(payload.message || @js(__('admin_imports.js_bulk_failed')));
                                }

                                if (initialTotal === null) {
                                    initialTotal = Number(payload.total_before || 0);
                                }

                                createdTotal += Number(payload.created || 0);
                                const remaining = Number(payload.remaining || 0);
                                const total = initialTotal || createdTotal + remaining;

                                if (progress) {
                                    progress.textContent = @js(__('admin_imports.js_bulk_progress', ['created' => '__CREATED__', 'total' => '__TOTAL__']))
                                        .replace('__CREATED__', String(createdTotal))
                                        .replace('__TOTAL__', String(total));
                                }

                                if (payload.done || remaining === 0) {
                                    break;
                                }
                            }

                            if (progress) {
                                progress.textContent = @js(__('admin_imports.js_candidates_done', ['count' => '__COUNT__'])).replace('__COUNT__', String(createdTotal));
                            }

                            window.location.reload();
                        } catch (error) {
                            button.disabled = false;
                            button.textContent = originalText;
                            if (progress) {
                                progress.classList.remove('hidden');
                                progress.textContent = error instanceof Error ? error.message : @js(__('admin_imports.js_bulk_failed'));
                            }
                        }
                    });
                };

                const initExternalMedia = () => {
                    const boxes = [...document.querySelectorAll('[data-external-media-select]')];
                    const selectAll = document.querySelector('[data-external-media-select-all]');
                    const counter = document.querySelector('[data-external-media-selected-count]');
                    const submit = document.querySelector('[data-external-media-submit]');
                    const importAll = document.querySelector('[data-external-media-import-all]');
                    const progress = document.querySelector('[data-external-media-progress]');
                    const token = document.querySelector('#external-media-selected input[name="_token"]')?.value;

                    const syncSelection = () => {
                        const selected = boxes.filter((box) => box.checked).length;
                        if (counter) counter.textContent = selected.toString();
                        if (submit) submit.disabled = selected === 0;
                        if (selectAll) {
                            selectAll.checked = boxes.length > 0 && selected === boxes.length;
                            selectAll.indeterminate = selected > 0 && selected < boxes.length;
                        }
                    };

                    boxes.forEach((box) => {
                        if (box.dataset.bound) return;
                        box.dataset.bound = '1';
                        box.addEventListener('change', syncSelection);
                    });

                    if (selectAll && !selectAll.dataset.bound) {
                        selectAll.dataset.bound = '1';
                        selectAll.addEventListener('change', () => {
                            boxes.forEach((box) => {
                                box.checked = selectAll.checked;
                            });
                            syncSelection();
                        });
                    }

                    if (importAll && !importAll.dataset.bound && token) {
                        importAll.dataset.bound = '1';
                        importAll.addEventListener('click', async () => {
                            const source = importAll.dataset.source || '';
                            if (!confirm(@js(__('admin_imports.js_confirm_all_images')))) {
                                return;
                            }

                            importAll.disabled = true;
                            let queuedTotal = 0;

                            if (progress) {
                                progress.classList.remove('hidden');
                                progress.textContent = @js(__('admin_imports.js_starting'));
                            }

                            try {
                                while (true) {
                                    const response = await fetch(importAll.dataset.endpoint, {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': token,
                                        },
                                        body: JSON.stringify({ source_slug: source || null }),
                                    });

                                    const payload = await response.json().catch(() => ({}));
                                    if (!response.ok) {
                                        throw new Error(payload.message || @js(__('admin_imports.js_media_failed')));
                                    }

                                    queuedTotal += Number(payload.queued || 0);
                                    const remaining = Number(payload.remaining || 0);

                                    if (progress) {
                                        progress.textContent = @js(__('admin_imports.js_media_progress', ['queued' => '__QUEUED__', 'remaining' => '__REMAINING__']))
                                            .replace('__QUEUED__', String(queuedTotal))
                                            .replace('__REMAINING__', String(remaining));
                                    }

                                    if (payload.done || remaining === 0) {
                                        break;
                                    }
                                }

                                window.location.reload();
                            } catch (error) {
                                importAll.disabled = false;
                                if (progress) {
                                    progress.classList.remove('hidden');
                                    progress.textContent = error instanceof Error ? error.message : @js(__('admin_imports.js_media_failed'));
                                }
                            }
                        });
                    }

                    syncSelection();
                };

                const initDuplicateGroupMaps = () => {
                    const dialog = document.getElementById('duplicate-group-map-dialog');
                    const mapElement = dialog?.querySelector('[data-duplicate-map]');
                    const title = dialog?.querySelector('[data-duplicate-map-title]');
                    const subtitle = dialog?.querySelector('[data-duplicate-map-subtitle]');
                    const errorBox = dialog?.querySelector('[data-duplicate-map-error]');
                    let map = null;
                    let layerGroup = null;

                    const clearMap = () => {
                        if (layerGroup) {
                            layerGroup.clearLayers();
                        }
                    };

                    const render = (payload) => {
                        if (!dialog || !mapElement || typeof L === 'undefined') {
                            throw new Error(@js(__('admin_imports.js.map_failed')));
                        }

                        if (!map) {
                            map = L.map(mapElement, { zoomControl: true, attributionControl: true });
                            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                maxZoom: 19,
                                attribution: '&copy; OpenStreetMap contributors'
                            }).addTo(map);
                            layerGroup = L.layerGroup().addTo(map);
                        } else {
                            clearMap();
                        }

                        if (title) title.textContent = payload.name || @js(__('admin_imports.duplicate_group'));
                        if (subtitle) {
                            subtitle.textContent = (payload.place_type ? payload.place_type + ' · ' : '')
                                + @js(__('admin_imports.external_and_candidates'));
                        }

                        const points = [];

                        (payload.members || []).forEach((member) => {
                            const lat = Number(member.latitude);
                            const lng = Number(member.longitude);
                            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                            points.push([lat, lng]);
                            const reviewIds = Array.isArray(payload.review_ids) ? payload.review_ids : [];
                            const actionUrl = String(payload.create_place_url || '');
                            const token = document.querySelector('input[name="_token"]')?.value || '';

                            const hiddenReviewIds = reviewIds
                                .map((reviewId) => '<input type="hidden" name="review_ids[]" value="' + Number(reviewId) + '">')
                                .join('');

                            const actionForm = member.review_id && actionUrl
                                ? '<form method="POST" action="' + actionUrl + '" class="mt-2" data-confirm="' + escapeHtml(@js(__('admin_imports.js.primary_confirm'))) + '" onsubmit="return confirm(this.dataset.confirm)">'
                                    + '<input type="hidden" name="_token" value="' + token + '">'
                                    + hiddenReviewIds
                                    + '<input type="hidden" name="primary_review_id" value="' + Number(member.review_id) + '">'
                                    + '<button type="submit" style="margin-top:8px;padding:6px 10px;border-radius:8px;border:1px solid #a1a1aa;font-weight:600;cursor:pointer;">{{ __('admin_imports.use_as_primary') }}</button>'
                                    + '</form>'
                                : '';

                            L.marker([lat, lng])
                                .addTo(layerGroup)
                                .bindPopup(
                                    '<strong>Record #' + member.record_id + '</strong><br>'
                                    + String(member.source_name || '') + '<br>'
                                    + String(member.external_id || '')
                                    + actionForm
                                );
                        });

                        (payload.candidates || []).forEach((candidate) => {
                            const lat = Number(candidate.latitude);
                            const lng = Number(candidate.longitude);
                            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                            points.push([lat, lng]);
                            L.circleMarker([lat, lng], { radius: 9, weight: 3, fillOpacity: 0.35 })
                                .addTo(layerGroup)
                                .bindPopup(
                                    '<strong>Camperwolf #' + candidate.place_id + '</strong><br>'
                                    + String(candidate.name || '')
                                );
                        });

                        if (points.length === 1) {
                            map.setView(points[0], 17);
                        } else if (points.length > 1) {
                            map.fitBounds(points, { padding: [30, 30], maxZoom: 17 });
                        } else {
                            map.setView([51.0, 10.0], 6);
                        }

                        setTimeout(() => map.invalidateSize(), 0);
                    };

                    document.querySelectorAll('[data-duplicate-map-open]').forEach((button) => {
                        if (button.dataset.bound) return;
                        button.dataset.bound = '1';

                        button.addEventListener('click', async () => {
                            if (!dialog || !button.dataset.endpoint) return;

                            if (errorBox) {
                                errorBox.classList.add('hidden');
                                errorBox.textContent = '';
                            }

                            if (title) title.textContent = @js(__('admin_imports.js.map_loading'));
                            if (subtitle) subtitle.textContent = '';
                            dialog.showModal();

                            try {
                                const response = await fetch(button.dataset.endpoint, {
                                    headers: { 'Accept': 'application/json' }
                                });
                                const payload = await response.json().catch(() => ({}));

                                if (!response.ok) {
                                    throw new Error(payload.message || @js(__('admin_imports.js_map_data_failed')));
                                }

                                render(payload);
                            } catch (error) {
                                if (errorBox) {
                                    errorBox.classList.remove('hidden');
                                    errorBox.textContent = error instanceof Error ? error.message : @js(__('admin_imports.js_map_data_failed'));
                                }
                            }
                        });
                    });

                    if (dialog && !dialog.dataset.bound) {
                        dialog.dataset.bound = '1';
                        dialog.querySelector('[data-duplicate-map-close]')?.addEventListener('click', () => dialog.close());
                        dialog.addEventListener('click', (event) => {
                            if (event.target === dialog) dialog.close();
                        });
                    }
                };

                const initReviewDuplicateMaps = () => {
                    const dialog = document.getElementById('review-duplicate-map-dialog');
                    const mapElement = dialog?.querySelector('[data-review-map]');
                    const title = dialog?.querySelector('[data-review-map-title]');
                    const errorBox = dialog?.querySelector('[data-review-map-error]');
                    if (!dialog || !mapElement) return;

                    let map = dialog._camperwolfMap || null;
                    let layerGroup = dialog._camperwolfLayer || null;
                    const token = document.querySelector('input[name="_token"]')?.value || '';

                    const escapeHtml = (value) => String(value ?? '')
                        .replaceAll('&', '&amp;')
                        .replaceAll('<', '&lt;')
                        .replaceAll('>', '&gt;')
                        .replaceAll('"', '&quot;')
                        .replaceAll("'", '&#039;');

                    const ensureMap = () => {
                        if (typeof L === 'undefined') throw new Error(@js(__('admin_imports.js.map_failed')));

                        if (!map) {
                            map = L.map(mapElement, { zoomControl: true, attributionControl: true });
                            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                maxZoom: 19,
                                attribution: '&copy; OpenStreetMap contributors'
                            }).addTo(map);
                            layerGroup = L.layerGroup().addTo(map);
                            dialog._camperwolfMap = map;
                            dialog._camperwolfLayer = layerGroup;
                        } else {
                            layerGroup.clearLayers();
                        }
                    };

                    const render = (payload) => {
                        ensureMap();
                        if (title) title.textContent = payload.name || @js(__('admin_imports.duplicate_review_title'));

                        const points = [];
                        const createUrl = String(payload.create_place_url || '');
                        const linkUrl = String(payload.link_url || '');
                        const promoteUrl = String(payload.promote_url || '');

                        (payload.members || []).forEach((member) => {
                            const lat = Number(member.latitude);
                            const lng = Number(member.longitude);
                            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                            points.push([lat, lng]);
                            const createForm = createUrl && token
                                ? '<form method="POST" action="' + escapeHtml(createUrl) + '" style="margin-top:8px" data-confirm="' + escapeHtml(@js(__('admin_imports.js.create_place_confirm'))) + '" onsubmit="return confirm(this.dataset.confirm)">'
                                    + '<input type="hidden" name="_token" value="' + escapeHtml(token) + '">'
                                    + '<button type="submit" style="padding:6px 10px;border-radius:8px;border:1px solid #a1a1aa;font-weight:600;cursor:pointer;">{{ __('admin_imports.create_new_place') }}</button>'
                                    + '</form>'
                                : '';

                            L.marker([lat, lng])
                                .addTo(layerGroup)
                                .bindPopup(
                                    '<strong>' + escapeHtml(@js(__('admin_imports.map_external_record'))) + '</strong><br>'
                                    + escapeHtml(member.source_name) + '<br>'
                                    + escapeHtml(member.external_id)
                                    + createForm
                                );
                        });

                        (payload.candidates || []).forEach((candidate) => {
                            const lat = Number(candidate.latitude);
                            const lng = Number(candidate.longitude);
                            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                            points.push([lat, lng]);

                            const linkForm = linkUrl && token
                                ? '<form method="POST" action="' + escapeHtml(linkUrl) + '" style="margin-top:8px" data-confirm="' + escapeHtml(@js(__('admin_imports.js.link_place_confirm'))) + '" onsubmit="return confirm(this.dataset.confirm)">'
                                    + '<input type="hidden" name="_token" value="' + escapeHtml(token) + '">'
                                    + '<input type="hidden" name="place_id" value="' + Number(candidate.place_id) + '">'
                                    + '<button type="submit" style="padding:6px 10px;border-radius:8px;border:1px solid #a1a1aa;font-weight:600;cursor:pointer;">' + escapeHtml(@js(__('admin_imports.js.link_place'))) + '</button>'
                                    + '</form>'
                                : '';

                            const promoteForm = promoteUrl && token
                                ? '<form method="POST" action="' + escapeHtml(promoteUrl) + '" style="margin-top:8px" data-confirm="' + escapeHtml(@js(__('admin_imports.js.promote_candidate_confirm'))) + '" onsubmit="return confirm(this.dataset.confirm)">'
                                    + '<input type="hidden" name="_token" value="' + escapeHtml(token) + '">'
                                    + '<input type="hidden" name="place_id" value="' + Number(candidate.place_id) + '">'
                                    + '<button type="submit" style="padding:6px 10px;border-radius:8px;border:1px solid #d97706;background:#fffbeb;font-weight:600;cursor:pointer;">' + escapeHtml(@js(__('admin_imports.js.promote_candidate'))) + '</button>'
                                    + '</form>'
                                : '';

                            const profileLink = candidate.slug
                                ? '<br><a href="/places/' + encodeURIComponent(String(candidate.slug)) + '" target="_blank" rel="noopener noreferrer" style="text-decoration:underline">{{ __('admin_imports.profile_open') }}</a>'
                                : '';

                            L.circleMarker([lat, lng], { radius: 9, weight: 3, fillOpacity: 0.35 })
                                .addTo(layerGroup)
                                .bindPopup(
                                    '<strong>Camperwolf #' + Number(candidate.place_id) + '</strong><br>'
                                    + escapeHtml(candidate.name)
                                    + '<br>' + Number(candidate.distance_m || 0) + ' m · '
                                    + Number(candidate.name_similarity || 0).toLocaleString('de-DE', { maximumFractionDigits: 1 }) + ' %'
                                    + profileLink
                                    + linkForm
                                    + promoteForm
                                );
                        });

                        if (points.length === 1) {
                            map.setView(points[0], 17);
                        } else if (points.length > 1) {
                            map.fitBounds(points, { padding: [30, 30], maxZoom: 17 });
                        } else {
                            map.setView([51.0, 10.0], 6);
                        }

                        setTimeout(() => map.invalidateSize(), 0);
                    };

                    document.querySelectorAll('[data-review-duplicate-map-open]').forEach((button) => {
                        if (button.dataset.bound) return;
                        button.dataset.bound = '1';

                        button.addEventListener('click', async () => {
                            if (!button.dataset.endpoint) return;
                            if (errorBox) {
                                errorBox.classList.add('hidden');
                                errorBox.textContent = '';
                            }
                            if (title) title.textContent = @js(__('admin_imports.js.map_loading'));
                            dialog.showModal();

                            try {
                                const response = await fetch(button.dataset.endpoint, {
                                    headers: { Accept: 'application/json' }
                                });
                                const payload = await response.json().catch(() => ({}));
                                if (!response.ok) throw new Error(payload.message || @js(__('admin_imports.js_map_data_failed')));
                                render(payload);
                            } catch (error) {
                                if (errorBox) {
                                    errorBox.classList.remove('hidden');
                                    errorBox.textContent = error instanceof Error ? error.message : @js(__('admin_imports.js_map_data_failed'));
                                }
                            }
                        });
                    });

                    if (!dialog.dataset.bound) {
                        dialog.dataset.bound = '1';
                        dialog.querySelector('[data-review-map-close]')?.addEventListener('click', () => dialog.close());
                        dialog.addEventListener('click', (event) => {
                            if (event.target === dialog) dialog.close();
                        });
                    }
                };

                const initCoordinateReviewMaps = () => {
                    const dialog = document.getElementById('coordinate-review-dialog');
                    const form = dialog?.querySelector('[data-coordinate-review-form]');
                    const mapElement = dialog?.querySelector('[data-coordinate-map]');
                    const title = dialog?.querySelector('[data-coordinate-review-title]');
                    const latInput = dialog?.querySelector('[data-coordinate-lat]');
                    const lngInput = dialog?.querySelector('[data-coordinate-lng]');
                    const searchInput = dialog?.querySelector('[data-coordinate-search]');
                    const searchButton = dialog?.querySelector('[data-coordinate-search-button]');
                    const searchResults = dialog?.querySelector('[data-coordinate-search-results]');
                    const searchStatus = dialog?.querySelector('[data-coordinate-search-status]');

                    if (!dialog || !form || !mapElement || !latInput || !lngInput) return;

                    let map = dialog._camperwolfMap || null;
                    let marker = dialog._camperwolfMarker || null;

                    const ensureMap = () => {
                        if (typeof L === 'undefined') throw new Error(@js(__('admin_imports.js.map_failed')));

                        if (!map) {
                            map = L.map(mapElement, { zoomControl: true, attributionControl: true }).setView([51.0, 10.0], 6);
                            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                maxZoom: 19,
                                attribution: '&copy; OpenStreetMap contributors'
                            }).addTo(map);
                            map.on('click', (event) => setPosition(event.latlng.lat, event.latlng.lng, false));
                            dialog._camperwolfMap = map;
                        }
                    };

                    const setPosition = (lat, lng, center = true) => {
                        lat = Number(lat);
                        lng = Number(lng);
                        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                        ensureMap();
                        latInput.value = lat.toFixed(7);
                        lngInput.value = lng.toFixed(7);

                        if (!marker) {
                            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                            marker.on('dragend', () => {
                                const position = marker.getLatLng();
                                latInput.value = Number(position.lat).toFixed(7);
                                lngInput.value = Number(position.lng).toFixed(7);
                            });
                            dialog._camperwolfMarker = marker;
                        } else {
                            marker.setLatLng([lat, lng]);
                        }

                        if (center) map.setView([lat, lng], 16);
                    };

                    const clearResults = () => {
                        if (!searchResults) return;
                        searchResults.innerHTML = '';
                        searchResults.classList.add('hidden');
                    };

                    const formatAddress = (properties = {}) => {
                        const street = [properties.street, properties.housenumber].filter(Boolean).join(' ');
                        const city = [properties.postcode, properties.city ?? properties.locality ?? properties.district].filter(Boolean).join(' ');
                        return [street, city, properties.state, properties.country]
                            .filter(Boolean)
                            .filter((value, index, array) => array.indexOf(value) === index)
                            .join(', ') || properties.name || @js(__('admin_imports.unknown_place'));
                    };

                    const search = async () => {
                        const query = searchInput?.value?.trim() || '';
                        if (query.length < 3) {
                            clearResults();
                            if (searchStatus) searchStatus.textContent = @js(__('admin_imports.js.search_min'));
                            return;
                        }

                        if (searchButton) searchButton.disabled = true;
                        if (searchStatus) searchStatus.textContent = @js(__('admin_imports.js.searching'));

                        try {
                            const center = map ? map.getCenter() : { lat: 51.0, lng: 10.0 };
                            const params = new URLSearchParams({
                                q: query,
                                limit: '7',
                                lang: @json(app()->getLocale()),
                                lat: String(center.lat),
                                lon: String(center.lng),
                            });

                            const response = await fetch('https://photon.komoot.io/api/?' + params.toString(), {
                                headers: { Accept: 'application/json' },
                            });

                            if (!response.ok) throw new Error('HTTP ' + response.status);
                            const data = await response.json();
                            const features = Array.isArray(data.features) ? data.features : [];

                            clearResults();

                            features.forEach((feature) => {
                                const coordinates = feature?.geometry?.coordinates ?? [];
                                const lng = Number(coordinates[0]);
                                const lat = Number(coordinates[1]);
                                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                                const button = document.createElement('button');
                                button.type = 'button';
                                button.className = 'block w-full border-b border-zinc-200 px-3 py-2 text-left text-sm last:border-b-0 hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800';
                                button.textContent = formatAddress(feature.properties || {});
                                button.addEventListener('click', () => {
                                    setPosition(lat, lng, true);
                                    if (searchInput) searchInput.value = button.textContent;
                                    clearResults();
                                    if (searchStatus) searchStatus.textContent = @js(__('admin_imports.js.position_added'));
                                });
                                searchResults?.appendChild(button);
                            });

                            if (searchResults && searchResults.children.length > 0) {
                                searchResults.classList.remove('hidden');
                                if (searchStatus) searchStatus.textContent = @js(__('admin_imports.js.results', ['count' => '__COUNT__'])).replace('__COUNT__', String(searchResults.children.length));
                            } else if (searchStatus) {
                                searchStatus.textContent = @js(__('admin_imports.js.no_results'));
                            }
                        } catch (error) {
                            clearResults();
                            if (searchStatus) searchStatus.textContent = @js(__('admin_imports.js.search_unavailable'));
                        } finally {
                            if (searchButton) searchButton.disabled = false;
                        }
                    };

                    document.querySelectorAll('[data-coordinate-review-open]').forEach((button) => {
                        if (button.dataset.bound) return;
                        button.dataset.bound = '1';
                        button.addEventListener('click', () => {
                            ensureMap();
                            form.action = button.dataset.endpoint || '';
                            if (title) {
                                title.textContent = @js(__('admin_imports.js.position_title', ['name' => '__NAME__']))
                                    .replace('__NAME__', button.dataset.name || @js(__('admin_imports.external_record')));
                            }
                            latInput.value = '';
                            lngInput.value = '';
                            if (searchInput) searchInput.value = '';
                            clearResults();
                            if (marker) {
                                map.removeLayer(marker);
                                marker = null;
                                dialog._camperwolfMarker = null;
                            }
                            map.setView([51.0, 10.0], 6);
                            dialog.showModal();
                            setTimeout(() => map.invalidateSize(), 0);
                        });
                    });

                    if (!dialog.dataset.bound) {
                        dialog.dataset.bound = '1';
                        dialog.querySelector('[data-coordinate-review-close]')?.addEventListener('click', () => dialog.close());
                        dialog.addEventListener('click', (event) => {
                            if (event.target === dialog) dialog.close();
                        });
                        searchButton?.addEventListener('click', search);
                        searchInput?.addEventListener('keydown', (event) => {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                search();
                            }
                        });
                        latInput.addEventListener('change', () => setPosition(latInput.value, lngInput.value, true));
                        lngInput.addEventListener('change', () => setPosition(latInput.value, lngInput.value, true));
                    }
                };

                const initImportCenter = () => {
                    initImportForm();
                    initCandidateSelection();
                    initCreateAllCandidates();
                    initExternalMedia();
                    initDuplicateGroupMaps();
                    initReviewDuplicateMaps();
                    initCoordinateReviewMaps();
                };

                document.addEventListener('DOMContentLoaded', initImportCenter, { once: true });
                document.addEventListener('livewire:navigated', initImportCenter);
            })();
        </script>
    @endpush
</x-layouts::app>
