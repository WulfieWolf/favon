<x-layouts::app :title="__('places.suggest.title')">
    <div class="mx-auto w-full max-w-5xl space-y-6 px-4 py-6">
        <div>
            <h1 class="text-2xl font-semibold">{{ __('places.suggest.title') }}</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('places.suggest.intro') }}</p>
        </div>

        <div class="rounded-xl border border-zinc-300 bg-zinc-100 p-4 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
            <div class="font-semibold">{{ __('places.suggest.rules_title') }}</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>{{ __('places.suggest.rules_legal') }}</li>
                <li>{{ __('places.suggest.rules_private') }}</li>
                <li>{{ __('places.suggest.rules_personal') }}</li>
                <li>{{ __('places.suggest.rules_contact') }}</li>
            </ul>
            <p class="mt-2 font-medium">{{ __('places.suggest.rules_reject') }}</p>
        </div>

        <form method="POST" action="{{ route('places.store') }}" class="space-y-6">
            @csrf

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">{{ __('places.suggest.place_type') }}</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($placeTypes as $type)
                        @php($checked = (string) old('place_type_id') === (string) $type->id)
                        <label class="cursor-pointer">
                            <div class="flex h-full items-start gap-3 rounded-xl border border-zinc-300 p-4 transition has-[:checked]:border-zinc-950 has-[:checked]:bg-zinc-100 has-[:checked]:ring-2 has-[:checked]:ring-zinc-950/20 dark:border-zinc-700 dark:has-[:checked]:border-white dark:has-[:checked]:bg-zinc-800 dark:has-[:checked]:ring-white/20">
                                <input type="radio" name="place_type_id" value="{{ $type->id }}" class="mt-1 size-4 shrink-0 accent-zinc-950 dark:accent-white" @checked($checked) required>
                                <div>
                                    <div class="font-semibold">{{ __("place_types.{$type->slug}.name") }}</div>
                                    <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __("place_types.{$type->slug}.description") }}</div>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('place_type_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="grid gap-5">
                    <div>
                        <label for="name" class="text-sm font-medium">{{ __('places.suggest.name') }}</label>
                        <input id="name" name="name" value="{{ old('name') }}" maxlength="255" required class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="description" class="text-sm font-medium">{{ __('places.suggest.description') }}</label>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('places.suggest.description_help') }}</p>
                        <textarea id="description" name="description" rows="4" maxlength="2000" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">{{ old('description') }}</textarea>
                        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <fieldset>
                            <legend class="text-sm font-medium">{{ __('places.suggest.environment') }}</legend>
                            <div class="mt-2 grid grid-cols-3 gap-2">
                                @foreach (['outdoor', 'indoor', 'both'] as $value)
                                    <label class="cursor-pointer">
                                        <div class="flex items-center justify-center gap-2 rounded-lg border border-zinc-300 px-3 py-2 text-sm has-[:checked]:border-zinc-950 has-[:checked]:bg-zinc-100 has-[:checked]:font-semibold dark:border-zinc-700 dark:has-[:checked]:border-white dark:has-[:checked]:bg-zinc-800">
                                            <input type="radio" name="environment" value="{{ $value }}" class="size-4 accent-zinc-950 dark:accent-white" @checked(old('environment') === $value)>
                                            <span>{{ __("places.suggest.environment_{$value}") }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="text-sm font-medium">{{ __('places.suggest.target_audience') }}</legend>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                @foreach (['mm', 'mf'] as $value)
                                    <label class="cursor-pointer">
                                        <div class="flex items-center justify-center gap-2 rounded-lg border border-zinc-300 px-3 py-2 text-sm has-[:checked]:border-zinc-950 has-[:checked]:bg-zinc-100 has-[:checked]:font-semibold dark:border-zinc-700 dark:has-[:checked]:border-white dark:has-[:checked]:bg-zinc-800">
                                            <input type="checkbox" name="target_audience[]" value="{{ $value }}" class="size-4 accent-zinc-950 dark:accent-white" @checked(in_array($value, old('target_audience', []), true))>
                                            <span>{{ __("places.suggest.target_audience_{$value}") }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('places.suggest.target_audience_help') }}</p>
                            @error('target_audience') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </fieldset>

                        <fieldset>
                            <legend class="text-sm font-medium">{{ __('places.suggest.gloryhole') }}</legend>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                @foreach (['1' => __('places.suggest.yes'), '0' => __('places.suggest.no')] as $value => $label)
                                    <label class="cursor-pointer">
                                        <div class="flex items-center justify-center gap-2 rounded-lg border border-zinc-300 px-3 py-2 text-sm has-[:checked]:border-zinc-950 has-[:checked]:bg-zinc-100 has-[:checked]:font-semibold dark:border-zinc-700 dark:has-[:checked]:border-white dark:has-[:checked]:bg-zinc-800">
                                            <input type="radio" name="has_gloryhole" value="{{ $value }}" class="size-4 accent-zinc-950 dark:accent-white" @checked((string) old('has_gloryhole') === (string) $value)>
                                            <span>{{ $label }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">{{ __('places.suggest.position') }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('places.suggest.position_help') }}</p>

                <div class="relative mt-4">
                    <label for="place-search" class="text-sm font-medium">{{ __('places.suggest.search') }}</label>
                    <input
                        id="place-search"
                        type="search"
                        autocomplete="off"
                        placeholder="{{ __('places.suggest.search_placeholder') }}"
                        data-favon-address-search
                        data-results="place-search-results"
                        data-map="favon-place-create-map"
                        class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"
                    >
                    <div
                        id="place-search-results"
                        data-favon-address-results
                        class="absolute left-0 right-0 z-[1100] mt-1 hidden max-h-72 overflow-y-auto rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
                    ></div>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('places.suggest.search_note') }}</p>
                </div>

                <div id="favon-place-create-map"
                    data-favon-place-picker
                    data-lat-input="latitude"
                    data-lng-input="longitude"
                    data-search-input="place-search"
                    data-street-input="street_address"
                    data-postal-input="postal_code"
                    data-city-input="city"
                    data-country-input="country_code"
                    data-locale="{{ app()->getLocale() }}"
                    data-geocoder-searching="{{ __('places.geocoder.searching') }}"
                    data-geocoder-none="{{ __('places.geocoder.none') }}"
                    data-geocoder-unavailable="{{ __('places.geocoder.unavailable') }}"
                    data-geocoder-reverse-searching="{{ __('places.geocoder.reverse_searching') }}"
                    data-geocoder-reverse-none="{{ __('places.geocoder.reverse_none') }}"
                    data-geocoder-reverse-failed="{{ __('places.geocoder.reverse_failed') }}"
                    data-reverse-on-init="{{ request()->filled('lat') && request()->filled('lng') && ! old('street_address') ? '1' : '0' }}"
                    class="mt-4 h-80 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-200 dark:border-zinc-800 dark:bg-zinc-800"></div>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <div>
                        <label for="latitude" class="text-sm font-medium">{{ __('places.suggest.latitude') }}</label>
                        <input id="latitude" name="latitude" value="{{ old('latitude', request()->query('lat')) }}" required inputmode="decimal" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    </div>
                    <div>
                        <label for="longitude" class="text-sm font-medium">{{ __('places.suggest.longitude') }}</label>
                        <input id="longitude" name="longitude" value="{{ old('longitude', request()->query('lng')) }}" required inputmode="decimal" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    </div>
                </div>

                <div class="mt-5">
                    <div class="text-sm font-medium">{{ __('places.suggest.address') }}</div>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('places.suggest.address_help') }}</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-6">
                        <input id="street_address" name="street_address" value="{{ old('street_address') }}" placeholder="{{ __('places.suggest.street_address') }}" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 md:col-span-6 dark:border-zinc-700 dark:bg-zinc-950">
                        <input id="postal_code" name="postal_code" value="{{ old('postal_code') }}" placeholder="{{ __('places.suggest.postal_code') }}" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 md:col-span-2 dark:border-zinc-700 dark:bg-zinc-950">
                        <input id="city" name="city" value="{{ old('city') }}" placeholder="{{ __('places.suggest.city') }}" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 md:col-span-2 dark:border-zinc-700 dark:bg-zinc-950">
                        <select id="country_code" name="country_code" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 md:col-span-2 dark:border-zinc-700 dark:bg-zinc-950">
                            @foreach ($countries as $country)
                                <option value="{{ $country->code }}" @selected(old('country_code', 'DE') === $country->code)>{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <div class="flex justify-end gap-3">
                <a href="{{ route('dashboard') }}" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-700">{{ __('places.common.cancel') }}</a>
                <button type="submit" class="rounded-lg bg-zinc-950 px-5 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('places.suggest.submit') }}</button>
            </div>
        </form>
    </div>

</x-layouts::app>
