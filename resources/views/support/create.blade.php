<x-layouts::app :title="$legalEntry ? __('support.privacy_legal.title') : __('support.create.title')">
    @php
        $types = collect($availableTypes)->mapWithKeys(fn ($key) => [$key => [__("support.types.{$key}.label"), __("support.types.{$key}.description")]]);
        $publicStatusLabels = collect(['reported', 'confirmed', 'suggested', 'planned', 'in_progress', 'resolved', 'closed', 'not_planned'])->mapWithKeys(fn ($key) => [$key => __("support.statuses.{$key}")]);
    @endphp
    <div class="mx-auto w-full max-w-4xl space-y-7 px-5 py-8">
        <div>
            <a href="{{ route('help.index') }}" class="text-sm text-zinc-500 hover:text-zinc-950 dark:hover:text-white">{{ __('support.roadmap.back') }}</a>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">{{ $legalEntry ? __('support.privacy_legal.title') : __('support.create.title') }}</h1>
            <p class="mt-2 text-zinc-500">{{ $legalEntry ? __('support.privacy_legal.intro') : __('support.create.intro') }}</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($types as $key => [$label, $description])
                <a href="{{ route($legalEntry ? 'support.privacy-legal' : 'support.report', array_filter(['type'=>$key,'context'=>$contextKey,'module'=>$module,'route'=>$routeName,'source'=>$sourceUrl])) }}"
                   class="rounded-xl border p-4 transition {{ $type === $key ? 'border-zinc-900 bg-zinc-100 dark:border-white dark:bg-zinc-800' : 'border-zinc-200 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900' }}">
                    <div class="font-semibold">{{ $label }}</div>
                    <div class="mt-1 text-sm text-zinc-500">{{ $description }}</div>
                </a>
            @endforeach
        </div>

        @if ($legalEntry)
            <section class="rounded-xl border border-blue-200 bg-blue-50/60 p-5 text-sm dark:border-blue-900 dark:bg-blue-950/20">
                <h2 class="font-semibold">{{ __('support.privacy_legal.self_service_title') }}</h2>
                <p class="mt-1 text-zinc-600 dark:text-zinc-400">{{ __('support.privacy_legal.self_service_intro') }}</p>
                @auth
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if (Route::has('data-export.show'))
                            <a href="{{ route('data-export.show') }}" class="rounded-lg border border-blue-300 px-3 py-2 text-xs font-medium hover:bg-white dark:border-blue-800 dark:hover:bg-zinc-900">{{ __('support.privacy_legal.export_link') }}</a>
                        @endif
                        @if (Route::has('account-deletion.show'))
                            <a href="{{ route('account-deletion.show') }}" class="rounded-lg border border-blue-300 px-3 py-2 text-xs font-medium hover:bg-white dark:border-blue-800 dark:hover:bg-zinc-900">{{ __('support.privacy_legal.deletion_link') }}</a>
                        @endif
                    </div>
                @endauth
                <p class="mt-3 text-xs text-zinc-500">{{ __('support.privacy_legal.identity_note') }}</p>
            </section>
        @endif

        @if ($type)
            @if ($relatedEntries->isNotEmpty())
                <section class="rounded-xl border border-amber-200 bg-amber-50/60 p-5 dark:border-amber-900 dark:bg-amber-950/20">
                    <h2 class="font-semibold">{{ __('support.create.possibly_reported') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('support.create.possibly_reported_intro') }}</p>
                    <div class="mt-4 space-y-2">
                        @foreach ($relatedEntries as $entry)
                            <div class="rounded-lg border border-amber-200 bg-white p-3 dark:border-amber-900 dark:bg-zinc-900">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="font-medium">{{ $entry->title }}</div>
                                    <span class="shrink-0 text-xs text-zinc-500">{{ $publicStatusLabels[$entry->status] ?? $entry->status }}</span>
                                </div>
                                @if ($entry->description)
                                    <div class="mt-1 line-clamp-2 text-sm text-zinc-500">{{ $entry->description }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <form method="POST" action="{{ route('support.store') }}" class="space-y-5 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                @csrf
                <div class="hidden" aria-hidden="true">
                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="context_key" value="{{ $contextKey }}">
                <input type="hidden" name="module" value="{{ $module }}">
                <input type="hidden" name="route_name" value="{{ $routeName }}">
                <input type="hidden" name="source_url" value="{{ $sourceUrl }}">

                @guest
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="font-medium">{{ __('support.create.as_guest') }}</div>
                        <p class="mt-1 text-zinc-500">
                            {{ __('support.create.guest_explanation') }}
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-medium hover:bg-white dark:border-zinc-700 dark:hover:bg-zinc-800">{{ __('support.create.sign_in') }}</a>
                            @endif
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-lg bg-zinc-900 px-3 py-2 text-xs font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('support.create.register') }}</a>
                            @endif
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="text-sm">{{ __('support.create.name') }}
                            <input name="guest_name" value="{{ old('guest_name') }}" required autocomplete="name" class="mt-1 h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                        </label>
                        <label class="text-sm">{{ __('support.create.email') }}
                            <input name="guest_email" value="{{ old('guest_email') }}" type="email" required autocomplete="email" class="mt-1 h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                        </label>
                        <label class="text-sm">{{ __('support.create.phone') }} <span class="text-zinc-400">({{ __('support.create.optional') }})</span>
                            <input name="guest_phone" value="{{ old('guest_phone') }}" type="tel" autocomplete="tel" class="mt-1 h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                        </label>
                    </div>
                    <p class="text-xs text-zinc-500">{{ __('support.create.privacy') }}</p>
                @endguest

                <label class="block text-sm">{{ __('support.create.subject') }} <span class="text-zinc-400">({{ __('support.create.optional') }})</span>
                    <input name="subject" value="{{ old('subject') }}" class="mt-1 h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                </label>

                <label class="block text-sm">{{ $legalEntry ? __('support.privacy_legal.description') : __('support.create.description') }}
                    <textarea name="description" rows="7" required class="mt-1 w-full rounded-lg border border-zinc-300 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">{{ old('description') }}</textarea>
                </label>

                @if ($contextKey || $sourceUrl)
                    <div class="rounded-lg bg-zinc-50 p-3 text-xs text-zinc-500 dark:bg-zinc-900">
                        {{ __('support.create.context_sent') }}
                        @if ($contextKey) · {{ __('support.create.area', ['area' => $contextKey]) }} @endif
                        @if ($sourceUrl) · {{ __('support.create.page', ['page' => $sourceUrl]) }} @endif
                    </div>
                @endif
<button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950">{{ __('support.create.submit') }}</button>
            </form>
        @endif
    </div>
</x-layouts::app>
