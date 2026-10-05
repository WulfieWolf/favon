<x-layouts::app :title="__('places.draft.title')">
    <div class="mx-auto w-full max-w-5xl px-5 py-8 xl:px-7">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="mb-2 inline-flex rounded-full border border-amber-400/50 bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:text-amber-300">
                    {{ __('places.draft.badge') }} · {{ __('places.draft.step') }}
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">{{ __('places.draft.title') }}</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('places.draft.intro', ['name' => $place->name]) }}
                </p>
            </div>
            <a href="{{ route('dashboard') }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                {{ __('places.common.overview') }}
            </a>
        </div>
<form method="POST" action="{{ route('places.drafts.update', $place->id) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">{{ __('places.draft.description_section') }}</h2>
                <div class="mt-4 grid gap-4">
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('places.draft.description') }} <span class="text-xs font-normal text-zinc-500">(+{{ config('xp.place_info.long_text_xp', 2) }} XP)</span></span>
                        <textarea name="description" rows="5" maxlength="10000" placeholder="{{ __('places.draft.description_placeholder') }}" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">{{ old('description', $translation->description ?? '') }}</textarea>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('places.draft.directions') }} <span class="text-xs font-normal text-zinc-500">(+{{ config('xp.place_info.long_text_xp', 2) }} XP)</span></span>
                        <textarea name="directions" rows="3" maxlength="10000" placeholder="{{ __('places.draft.directions_placeholder') }}" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">{{ old('directions', $translation->directions ?? '') }}</textarea>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('places.draft.access') }} <span class="text-xs font-normal text-zinc-500">(+{{ config('xp.place_info.long_text_xp', 2) }} XP)</span></span>
                        <textarea name="access_information" rows="3" maxlength="10000" placeholder="{{ __('places.draft.access_placeholder') }}" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">{{ old('access_information', $translation->access_information ?? '') }}</textarea>
                    </label>
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">{{ __('places.draft.details_section') }}</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block md:col-span-2">
                        <span class="mb-1 block text-sm font-medium">{{ __('places.draft.operator') }} <span class="text-xs font-normal text-zinc-500">(+{{ config('xp.place_info.default_xp', 1) }} XP)</span></span>
                        <input type="text" name="operator_name" value="{{ old('operator_name', $details->operator_name ?? '') }}" maxlength="255" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium">{{ __('places.draft.pitch_count') }} <span class="text-xs font-normal text-zinc-500">(+{{ config('xp.place_info.default_xp', 1) }} XP)</span></span>
                        <input type="number" name="pitch_count" value="{{ old('pitch_count', $details->pitch_count ?? '') }}" min="0" max="1000000" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>
                </div>
            </section>

            <div class="flex flex-wrap justify-end gap-3">
                <button type="submit" name="intent" value="save" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                    {{ __('places.draft.save') }}
                </button>
                <button type="submit" name="intent" value="next" class="rounded-lg bg-zinc-900 px-5 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                    {{ __('places.draft.continue_features') }}
                </button>
            </div>
        </form>
    </div>
</x-layouts::app>
