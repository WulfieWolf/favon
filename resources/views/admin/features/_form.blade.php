@php
    $workflow = $feature?->workflow ?? [
        'status_mode' => 'availability',
        'status_options' => [
            ['value' => 'unknown', 'label' => ['de' => 'Unbekannt', 'en' => 'Unknown']],
            ['value' => 'available', 'label' => ['de' => 'Vorhanden', 'en' => 'Available']],
            ['value' => 'unavailable', 'label' => ['de' => 'Nicht vorhanden', 'en' => 'Not available']],
        ],
        'details' => [],
    ];
    $visibility = $feature?->visibility ?? [];
    $valueTypes = __('admin.feature_catalog.value_types');
    $detailTypes = __('admin.feature_catalog.detail_types');
    $visibilities = __('admin.feature_catalog.visibilities');
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5" data-feature-form>
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <label class="text-sm">{{ __('admin.feature_catalog.name_de') }}<input name="name_de" required value="{{ old('name_de', $feature?->name_de) }}" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"></label>
        <label class="text-sm">{{ __('admin.feature_catalog.name_en') }}<input name="name_en" required value="{{ old('name_en', $feature?->name_en) }}" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"></label>
        <label class="text-sm">{{ __('admin.feature_catalog.technical_slug') }}<input name="slug" required value="{{ old('slug', $feature?->slug) }}" placeholder="dog-water-station" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 font-mono text-xs dark:border-zinc-700 dark:bg-zinc-950"></label>
        <label class="text-sm">{{ __('admin.feature_catalog.category') }}<select name="category_id" required class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">@foreach($categories as $category)<option value="{{ $category->id }}" @selected((int) old('category_id', $feature?->category_id) === (int) $category->id)>{{ $category->name_localized ?: $category->name_fallback ?: $category->slug }}</option>@endforeach</select></label>
        <label class="text-sm">{{ __('admin.feature_catalog.base_type') }}<select name="value_type" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">@foreach($valueTypes as $value => $label)<option value="{{ $value }}" @selected(old('value_type', $feature?->value_type ?? 'boolean') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label class="text-sm">{{ __('admin.feature_catalog.unit_type') }}<input name="unit_type" value="{{ old('unit_type', $feature?->unit_type) }}" placeholder="{{ __('admin.feature_catalog.unit_type_placeholder') }}" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"></label>
        <label class="text-sm">{{ __('admin.feature_catalog.workflow_type') }}<input name="status_mode" required value="{{ old('status_mode', $workflow['status_mode'] ?? 'custom') }}" placeholder="availability" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"></label>
        <label class="text-sm">{{ __('admin.feature_catalog.sort_order') }}<input name="sort_order" type="number" min="0" required value="{{ old('sort_order', $feature?->sort_order ?? 10) }}" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"></label>
        <label class="text-sm">{{ __('admin.feature_catalog.filter_priority') }}<input name="filter_priority" type="number" min="0" value="{{ old('filter_priority', $feature?->filter_priority) }}" placeholder="{{ __('admin.feature_catalog.filter_priority_placeholder') }}" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"></label>
    </div>

    <div class="flex flex-wrap gap-5 text-sm">
        <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $feature?->is_active ?? true))> {{ __('admin.feature_catalog.active') }}</label>
        <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_searchable" value="1" @checked(old('is_searchable', $feature?->is_searchable ?? true))> {{ __('admin.feature_catalog.filter_searchable') }}</label>
    </div>

    <div>
        <div class="mb-2 flex items-center justify-between gap-3"><h3 class="font-semibold">{{ __('admin.feature_catalog.status_options') }}</h3><button type="button" data-add-status class="rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-700">{{ __('admin.feature_catalog.add_status') }}</button></div>
        <div class="space-y-2" data-status-list>
            @foreach(($workflow['status_options'] ?? []) as $index => $status)
                <div class="grid gap-2 md:grid-cols-[160px_1fr_1fr_auto]" data-repeat-row>
                    <input name="statuses[{{ $index }}][value]" required value="{{ $status['value'] ?? '' }}" placeholder="{{ __('admin.feature_catalog.value') }}" class="rounded border border-zinc-300 bg-white px-2 py-1.5 font-mono text-xs dark:border-zinc-700 dark:bg-zinc-950">
                    <input name="statuses[{{ $index }}][de]" required value="{{ $status['label']['de'] ?? '' }}" placeholder="{{ __('admin.feature_catalog.german') }}" class="rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    <input name="statuses[{{ $index }}][en]" required value="{{ $status['label']['en'] ?? '' }}" placeholder="{{ __('admin.feature_catalog.english') }}" class="rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    <button type="button" data-remove-row class="rounded border border-red-300 px-2 text-xs text-red-600 dark:border-red-800">{{ __('admin.feature_catalog.remove') }}</button>
                </div>
            @endforeach
        </div>
    </div>

    <div>
        <div class="mb-2 flex items-center justify-between gap-3"><div><h3 class="font-semibold">{{ __('admin.feature_catalog.detail_fields') }}</h3><p class="text-xs text-zinc-500">{{ __('admin.feature_catalog.options_format_help') }}</p></div><button type="button" data-add-detail class="rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-700">{{ __('admin.feature_catalog.add_detail') }}</button></div>
        <div class="space-y-3" data-detail-list>
            @foreach(($workflow['details'] ?? []) as $index => $detail)
                @php
                    $optionLines = collect($detail['options'] ?? [])->map(fn ($option) => ($option['value'] ?? '').'|'.($option['label']['de'] ?? '').'|'.($option['label']['en'] ?? ''))->implode("\n");
                @endphp
                <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800" data-repeat-row>
                    <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                        <input name="details[{{ $index }}][key]" value="{{ $detail['key'] ?? '' }}" placeholder="{{ __('admin.feature_catalog.technical_key') }}" class="rounded border border-zinc-300 bg-white px-2 py-1.5 font-mono text-xs dark:border-zinc-700 dark:bg-zinc-950">
                        <select name="details[{{ $index }}][type]" class="rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">@foreach($detailTypes as $value => $label)<option value="{{ $value }}" @selected(($detail['type'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
                        <input name="details[{{ $index }}][de]" value="{{ $detail['label']['de'] ?? '' }}" placeholder="{{ __('admin.feature_catalog.label_de') }}" class="rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        <input name="details[{{ $index }}][en]" value="{{ $detail['label']['en'] ?? '' }}" placeholder="{{ __('admin.feature_catalog.label_en') }}" class="rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        <input name="details[{{ $index }}][unit]" value="{{ $detail['unit'] ?? '' }}" placeholder="{{ __('admin.feature_catalog.unit_placeholder') }}" class="rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        <input name="details[{{ $index }}][show_for]" value="{{ implode(',', $detail['show_for'] ?? []) }}" placeholder="{{ __('admin.feature_catalog.visible_for_placeholder') }}" class="rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        <textarea name="details[{{ $index }}][options_text]" rows="2" placeholder="{{ __('admin.feature_catalog.options_placeholder') }}" class="rounded border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-950 xl:col-span-2">{{ $optionLines }}</textarea>
                    </div>
                    <button type="button" data-remove-row class="mt-2 rounded border border-red-300 px-2 py-1 text-xs text-red-600 dark:border-red-800">{{ __('admin.feature_catalog.remove_detail') }}</button>
                </div>
            @endforeach
        </div>
    </div>

    <div>
        <h3 class="mb-2 font-semibold">{{ __('admin.feature_catalog.place_types') }}</h3>
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($placeTypes as $placeType)
                <div class="rounded-lg border border-zinc-200 p-2 text-sm dark:border-zinc-800">
                    <div class="font-medium">{{ $placeType->name_localized ?: $placeType->name_fallback ?: $placeType->slug }}</div>
                    <select name="visibility[{{ $placeType->id }}]" class="mt-1 w-full rounded border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-950">@foreach($visibilities as $value => $label)<option value="{{ $value }}" @selected(($visibility[$placeType->id] ?? 'hidden') === $value)>{{ $label }}</option>@endforeach</select>
                    <input name="place_type_filter_priority[{{ $placeType->id }}]" type="number" min="0" value="{{ old('place_type_filter_priority.'.$placeType->id, $feature?->place_type_filter_priority[$placeType->id] ?? null) }}" placeholder="{{ __('admin.feature_catalog.place_type_filter_priority_placeholder') }}" class="mt-2 w-full rounded border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-950">
                </div>
            @endforeach
        </div>
    </div>

    <label class="block text-sm">{{ __('admin.feature_catalog.internal_note') }}<textarea name="internal_comment" rows="2" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">{{ old('internal_comment', $feature?->internal_comment) }}</textarea></label>

    <div class="flex justify-end"><button class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ $feature ? __('admin.feature_catalog.save_feature') : __('admin.feature_catalog.add_feature') }}</button></div>
</form>
