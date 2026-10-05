<x-layouts::app :title="__('reviews.history.page_title', ['place' => $review->place_name])">
    <div class="mx-auto w-full max-w-5xl px-6 py-8">
<div class="mb-6">
            <a href="{{ route('places.show', $review->place_slug) }}#reviews" class="text-sm font-medium text-zinc-500 hover:text-zinc-950 dark:hover:text-white">{{ __('reviews.history.back', ['place' => $review->place_name]) }}</a>
            <h1 class="mt-3 text-2xl font-semibold">{{ __('reviews.history.title') }}</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ __('reviews.history.intro') }}</p>
        </div>

        <div class="space-y-4">
            @foreach ($versions as $version)
                <article class="grid gap-5 rounded-xl border border-zinc-200 bg-white p-5 md:grid-cols-[minmax(0,1fr)_190px] dark:border-zinc-800 dark:bg-zinc-900">
                    <div>
                        <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-400">
                            <span>{{ __('reviews.history.version', ['number' => $version->version_number]) }}</span>
                            <span>·</span>
                            <span>{{ \App\Support\LocalTime::parse($version->valid_from)->translatedFormat(__('reviews.date_time_format')) }}</span>
                            @if ((int) $review->current_version_id === (int) $version->id)
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">{{ __('reviews.history.current') }}</span>
                            @endif
                        </div>

                        @if ($version->review_text)
                            <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-6 text-zinc-700 dark:text-zinc-300">{{ $version->review_text }}</p>
                        @else
                            <div class="mt-3 inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">{{ __('reviews.card.stars_only') }}</div>
                        @endif

                        @auth
                            @if ((int) auth()->id() === (int) $review->user_id && (int) $review->current_version_id !== (int) $version->id)
                                <form method="POST" action="{{ route('reviews.history.hide', ['review' => $review->id, 'version' => $version->id]) }}" class="mt-4" onsubmit='return confirm(@json(__('reviews.history.hide_confirm')));'>
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-red-600 hover:underline dark:text-red-400">{{ __('reviews.history.hide') }}</button>
                                </form>
                            @endif
                        @endauth
                    </div>

                    <div class="md:border-l md:border-zinc-200 md:pl-5 dark:md:border-zinc-800">
                        <div class="flex items-baseline gap-1.5">
                            <div class="text-4xl font-bold">{{ \Illuminate\Support\Number::format((float) $version->overall_score, 1, 1, app()->getLocale()) }}</div>
                            <div class="text-xs text-zinc-500">{{ __('reviews.out_of_five') }}</div>
                        </div>
                        <dl class="mt-3 space-y-1.5 text-xs">
                            @foreach ($dimensions as $dimension)
                                <div class="flex justify-between gap-3">
                                    <dt class="text-zinc-500">{{ $dimension['label'] }}</dt>
                                    <dd class="font-semibold">{{ \Illuminate\Support\Number::format((float) $version->{$dimension['field']}, 1, 1, app()->getLocale()) }} <span class="text-amber-500">★</span></dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</x-layouts::app>
