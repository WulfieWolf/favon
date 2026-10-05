@push('styles')
<style>
    .cw-rating-choice {
        position: relative;
        display: inline-flex;
        cursor: pointer;
    }

    .cw-rating-choice input {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }

    .cw-rating-choice span {
        display: inline-flex;
        min-width: 2.75rem;
        height: 2.5rem;
        align-items: center;
        justify-content: center;
        border: 1px solid rgb(212 212 216);
        border-radius: .5rem;
        background: white;
        padding: 0 .7rem;
        font-size: .875rem;
        font-weight: 600;
        color: rgb(82 82 91);
        transition: border-color .15s ease, background-color .15s ease, color .15s ease, transform .15s ease;
    }

    .cw-rating-choice:hover span {
        border-color: rgb(245 158 11);
        color: rgb(217 119 6);
    }

    .cw-rating-choice input:checked + span {
        border-color: rgb(245 158 11);
        background: rgb(254 243 199);
        color: rgb(180 83 9);
        box-shadow: 0 0 0 2px rgba(245, 158, 11, .18);
        transform: translateY(-1px);
    }

    .cw-rating-choice input:focus-visible + span {
        outline: 2px solid rgb(245 158 11);
        outline-offset: 2px;
    }

    .dark .cw-rating-choice span {
        border-color: rgb(82 82 91);
        background: rgb(24 24 27);
        color: rgb(161 161 170);
    }

    .dark .cw-rating-choice:hover span {
        border-color: rgb(245 158 11);
        color: rgb(251 191 36);
    }

    .dark .cw-rating-choice input:checked + span {
        border-color: rgb(245 158 11);
        background: rgba(120, 53, 15, .45);
        color: rgb(251 191 36);
        box-shadow: 0 0 0 2px rgba(245, 158, 11, .2);
    }

    .cw-score-summary {
        display: grid;
        grid-template-columns: minmax(240px, .72fr) minmax(0, 1.28fr);
        gap: 1.25rem;
        margin-top: 1.25rem;
        padding: 1.25rem;
        border: 1px solid rgb(228 228 231);
        border-radius: .75rem;
        background: rgb(250 250 250);
    }

    .dark .cw-score-summary {
        border-color: rgb(63 63 70);
        background: rgb(39 39 42);
    }

    .cw-score-overall {
        display: flex;
        min-height: 11rem;
        flex-direction: column;
        justify-content: center;
    }

    .cw-score-number-row {
        display: flex;
        align-items: flex-end;
        gap: .5rem;
        margin-top: .25rem;
    }

    .cw-score-number {
        font-size: clamp(4.5rem, 8vw, 7rem);
        font-weight: 700;
        line-height: .88;
        letter-spacing: -.05em;
        color: rgb(24 24 27);
    }

    .dark .cw-score-number {
        color: rgb(250 250 250);
    }

    .cw-score-dimensions {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: .75rem;
    }

    .cw-score-dimension {
        display: grid;
        grid-template-columns: 110px minmax(0, 1fr) 52px;
        align-items: center;
        gap: .75rem;
    }

    @media (max-width: 767px) {
        .cw-score-summary {
            grid-template-columns: 1fr;
            padding: .75rem;
        }

        .cw-score-overall {
            min-height: 0;
        }

        .cw-score-dimension {
            grid-template-columns: 95px minmax(0, 1fr) 48px;
        }
    }
</style>
@endpush

@php
    $dimensionMeta = $reviewDimensions;

    $currentPublishedAt = $currentUserReview?->current_published_at
        ? \App\Support\LocalTime::parse($currentUserReview->current_published_at)
        : null;
    $correctionUntil = $currentPublishedAt?->copy()->addMinutes(\App\Services\PlaceReviewService::CORRECTION_MINUTES);
    $nextUpdateAt = $currentPublishedAt?->copy()->addDays(\App\Services\PlaceReviewService::UPDATE_COOLDOWN_DAYS);
    $insideCorrectionWindow = $correctionUntil && now()->lte($correctionUntil);
    $canUpdateReview = ! $currentUserReview || $insideCorrectionWindow || ($nextUpdateAt && now()->gte($nextUpdateAt));
    $hasReviewHistory = collect($reviewHistory)->contains(fn ($month) => ($month['count'] ?? 0) > 0);
    $activePhotoCount = $currentUserPhotos->whereIn('status', ['processing', 'pending', 'approved'])->count();
    $remainingPhotoSlots = max(0, 5 - $activePhotoCount);
@endphp

<section id="reviews" class="scroll-mt-24 rounded-xl border border-zinc-200 bg-white p-3 sm:p-5 dark:border-zinc-800 dark:bg-zinc-900">
    <div>
        <h2 class="text-lg font-semibold">{{ __('reviews.title') }}</h2>
        <p class="mt-1 text-sm text-zinc-500">
            {{ __('reviews.intro') }}
        </p>
    </div>

    @if ($reviewSummary['count'] > 0)
        <div class="cw-score-summary">
            <div class="cw-score-overall">
                <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('reviews.overall_score') }}</div>
                <div class="cw-score-number-row">
                    <div class="cw-score-number">
                        {{ \Illuminate\Support\Number::format($reviewSummary['overall'], 1, 1, app()->getLocale()) }}
                    </div>
                    <div class="pb-2 text-base font-medium text-zinc-500">{{ __('reviews.out_of_five') }}</div>
                </div>
                <div class="mt-3 text-sm text-zinc-500">
                    {{ trans_choice('reviews.review_count', $reviewSummary['count'], ['count' => $reviewSummary['count']]) }}
                </div>
                <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-zinc-300 dark:bg-zinc-700">
                    <div
                        class="h-full rounded-full bg-amber-400 dark:bg-amber-500"
                        style="width: {{ max(0, min(100, ($reviewSummary['overall'] / 5) * 100)) }}%"
                    ></div>
                </div>
            </div>

            <div class="cw-score-dimensions">
                @foreach ($dimensionMeta as $key => $meta)
                    @php
                        $dimensionScore = $reviewSummary['dimensions'][$key];
                    @endphp
                    <div class="cw-score-dimension">
                        <div class="truncate text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $meta['label'] }}</div>
                        <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div
                                class="h-full rounded-full bg-zinc-500 dark:bg-zinc-400"
                                style="width: {{ max(0, min(100, ($dimensionScore / 5) * 100)) }}%"
                            ></div>
                        </div>
                        <div class="text-right text-sm font-semibold">
                            {{ \Illuminate\Support\Number::format($dimensionScore, 1, 1, app()->getLocale()) }}<span class="font-normal text-zinc-400">/5</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($hasReviewHistory)
            <details class="group mt-3 rounded-lg border border-zinc-200 dark:border-zinc-800" data-review-history>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-zinc-600 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800/60">
                    <span>{{ __('reviews.timeline') }}</span>
                    <span class="text-xs text-zinc-400 transition group-open:rotate-180">⌄</span>
                </summary>
                <div class="border-t border-zinc-200 p-4 dark:border-zinc-800" data-review-chart data-series='@json($reviewHistory)'>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="text-xs text-zinc-500">{{ __('reviews.monthly_score_explanation') }}</div>
                        <div class="flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-zinc-500">
                            @foreach (['overall' => __('reviews.chart.overall')] + collect($dimensionMeta)->mapWithKeys(fn ($meta, $key) => [$key => $meta['label']])->all() as $key => $label)
                                <label class="inline-flex cursor-pointer items-center gap-1">
                                    <input type="checkbox" checked data-chart-series="{{ $key }}" class="size-3 rounded border-zinc-300">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <canvas class="mt-3 h-52 w-full" height="208" data-chart-canvas></canvas>
                </div>
            </details>
        @else
            <div class="mt-3 text-xs text-zinc-400">
                {{ __('reviews.timeline_empty') }}
            </div>
        @endif
    @else
        <div class="mt-5 rounded-xl border border-dashed border-zinc-300 px-4 py-4 text-sm text-zinc-500 dark:border-zinc-700">
            {{ __('reviews.empty_score') }}
        </div>
    @endif

    <div class="mt-6 border-t border-zinc-200 pt-6 dark:border-zinc-800">
        @auth
            <details class="group rounded-xl border border-amber-300 bg-amber-50/60 dark:border-amber-800 dark:bg-amber-950/15" @if($errors->any()) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3">
                    <div>
                        <div class="font-semibold text-amber-900 dark:text-amber-200">
                            {{ $currentUserReview ? __('reviews.form.update') : __('reviews.form.create') }}
                        </div>
                        <div class="mt-0.5 text-xs text-amber-800/70 dark:text-amber-300/70">
                            {{ __('reviews.form.subtitle') }}
                        </div>
                    </div>
                    <span class="text-sm text-amber-700 transition group-open:rotate-180 dark:text-amber-300">⌄</span>
                </summary>

                <div class="border-t border-amber-200 p-4 dark:border-amber-900">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <p class="max-w-3xl text-xs leading-5 text-zinc-500">
                            {{ __('reviews.form.rules') }}
                        </p>

                        @if ($currentUserReview)
                            <form method="POST" action="{{ route('reviews.destroy', $place->slug) }}" onsubmit='return confirm(@json(__('reviews.form.delete_confirm')));'>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-red-600 hover:underline dark:text-red-400">
                                    {{ __('reviews.form.delete') }}
                                </button>
                            </form>
                        @endif
                    </div>

                    @if (! $canUpdateReview)
                        <div class="mt-4 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-600 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                            {!! __('reviews.form.next_update', ['date' => '<strong>'.e($nextUpdateAt->translatedFormat(__('reviews.date_time_format'))).'</strong>']) !!}
                        </div>
                    @else
                        <form method="POST" action="{{ route('reviews.store', $place->slug) }}" enctype="multipart/form-data" class="mt-5 space-y-5" novalidate>
                            @csrf

                            @foreach ($dimensionMeta as $key => $meta)
                                @php
                                    $currentValue = old($key, $currentUserReview ? $currentUserReview->{$meta['field']} : null);
                                @endphp
                                <fieldset>
                                    <legend class="text-sm font-semibold">{{ $meta['label'] }}</legend>
                                    <div class="mt-2 flex flex-wrap gap-1.5" aria-label="{{ __('reviews.form.rate_dimension', ['dimension' => $meta['label']]) }}">
                                        @for ($star = 1; $star <= 5; $star++)
                                            @php
                                                $ratingId = 'rating-'.$key.'-'.$star;
                                            @endphp
                                            <label class="cw-rating-choice" for="{{ $ratingId }}" title="{{ __('reviews.form.stars_out_of_five', ['stars' => $star]) }}">
                                                <input
                                                    id="{{ $ratingId }}"
                                                    type="radio"
                                                    name="{{ $key }}"
                                                    value="{{ $star }}"
                                                    @checked((int) $currentValue === $star)
                                                    required
                                                >
                                                <span>{{ $star }} ★</span>
                                            </label>
                                        @endfor
                                    </div>
                                    <div class="mt-2 text-xs text-zinc-600 dark:text-zinc-300">{{ $meta['help'] }}</div>
                                    <div class="mt-0.5 text-xs text-zinc-400">{{ $meta['examples'] }}</div>
                                </fieldset>
                            @endforeach

                            <label class="block">
                                <span class="text-sm font-semibold">{{ __('reviews.form.review') }} <span class="font-normal text-zinc-400">({{ __('reviews.form.optional') }})</span></span>
                                <textarea
                                    name="review_text"
                                    rows="4"
                                    minlength="20"
                                    maxlength="2000"
                                    class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder:text-zinc-400 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                                    placeholder="{{ __('reviews.form.placeholder') }}"
                                >{{ old('review_text', $insideCorrectionWindow ? $currentUserReview?->review_text : '') }}</textarea>
                                <span class="mt-1 block text-xs text-zinc-400">{{ __('reviews.form.text_help') }}</span>
                            </label>

                            <x-review-content-rules />

                            @if (! $currentUserReview && $remainingPhotoSlots > 0)
                                @include('places._photo-picker', [
                                    'photoPickerId' => 'review-photo-picker',
                                    'photoPickerMax' => $remainingPhotoSlots,
                                    'photoPickerRequired' => false,
                                ])
                            @endif

                            @php
                                $reviewSubmitLabel = $insideCorrectionWindow
                                    ? __('reviews.form.save_correction')
                                    : ($currentUserReview ? __('reviews.form.publish_new') : __('reviews.form.publish'));
                            @endphp
                            <button type="submit" data-photo-submit data-photo-submit-base="{{ $reviewSubmitLabel }}" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                                {{ $reviewSubmitLabel }}
                            </button>
                        </form>
                    @endif

                    @if ($currentUserReview)
                        <div class="mt-5 border-t border-amber-200 pt-4 dark:border-amber-900">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <div class="text-sm font-semibold">{{ __('photos.review_photos') }}</div>
                                    <div class="text-xs text-zinc-500">{{ __('photos.active_count', ['count' => $activePhotoCount]) }}</div>
                                </div>
                            </div>

                            @if ($currentUserPhotos->isNotEmpty())
                                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5">
                                    @foreach ($currentUserPhotos as $photo)
                                        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                                            @if (in_array($photo->status, ['pending', 'approved'], true))
                                                <img
                                                    src="{{ $photo->status === 'approved' ? route('photos.show', ['uuid' => $photo->uuid, 'variant' => 'preview']) : route('photos.owner', ['uuid' => $photo->uuid, 'variant' => 'preview']) }}"
                                                    alt="{{ __('photos.own_review_photo') }}"
                                                    class="aspect-square w-full object-cover {{ $photo->status === 'approved' ? '' : 'opacity-70' }}"
                                                >
                                            @else
                                                <div class="grid aspect-square place-items-center bg-zinc-100 px-2 text-center text-xs text-zinc-500 dark:bg-zinc-800">
                                                    {{ $photo->status === 'processing' ? __('photos.processing') : __('photos.processing_failed') }}
                                                </div>
                                            @endif
                                            <div class="p-2 text-[11px]">
                                                <div class="text-zinc-500">
                                                    {{ __("photos.status.{$photo->status}") }}
                                                </div>
                                                <form method="POST" action="{{ route('photos.destroy', $photo->id) }}" class="mt-1">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="font-medium text-red-600 hover:underline dark:text-red-400">{{ __('photos.remove') }}</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if ($remainingPhotoSlots > 0)
                                <form method="POST" action="{{ route('photos.store', $place->slug) }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                                    @csrf
                                    @include('places._photo-picker', [
                                        'photoPickerId' => 'additional-photo-picker',
                                        'photoPickerMax' => $remainingPhotoSlots,
                                        'photoPickerRequired' => true,
                                    ])
                                    <button type="submit" data-photo-submit data-photo-submit-base="{{ __('photos.upload') }}" class="rounded-md bg-zinc-900 px-3 py-2 text-xs font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('photos.upload') }}</button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            </details>
        @else
            <button
                type="button"
                data-auth-feature
                data-auth-title="{{ __('reviews.form.login_title') }}"
                data-auth-message="{{ __('reviews.form.login_message') }}"
                class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/20 dark:text-amber-200 dark:hover:bg-amber-950/35"
            >
                {{ __('reviews.form.login_button') }}
            </button>
        @endauth
    </div>

    <div class="mt-7 border-t border-zinc-200 pt-6 dark:border-zinc-800">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-baseline gap-2">
                <h3 class="font-semibold">{{ __('reviews.list.title') }}</h3>
                @if ($reviewSummary['count'] > 0)
                    <span class="text-xs text-zinc-400">{{ $reviewSummary['count'] }}</span>
                @endif
            </div>

            @if ($reviewSummary['count'] > 1)
                <form method="GET" action="{{ route('places.show', $place->slug) }}#reviews">
                    <label class="flex items-center gap-2 text-xs text-zinc-500">
                        {{ __('reviews.list.sort') }}
                        <select name="sort" onchange="this.form.submit()" class="rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="newest" @selected($reviewSort === 'newest')>{{ __('reviews.list.newest') }}</option>
                            <option value="oldest" @selected($reviewSort === 'oldest')>{{ __('reviews.list.oldest') }}</option>
                            <option value="best" @selected($reviewSort === 'best')>{{ __('reviews.list.best') }}</option>
                            <option value="worst" @selected($reviewSort === 'worst')>{{ __('reviews.list.worst') }}</option>
                        </select>
                    </label>
                </form>
            @endif
        </div>

        @php
            $remainingReviews = max(0, $reviewSummary['count'] - $placeReviews->count());
        @endphp
        <div
            data-review-feed
            data-review-url="{{ route('places.reviews.feed', $place->slug) }}"
            data-review-sort="{{ $reviewSort }}"
            data-review-cursor="{{ $placeReviews->nextCursor()?->encode() }}"
            data-review-total="{{ $reviewSummary['count'] }}"
            data-review-loaded="{{ $placeReviews->count() }}"
        >
            <div data-review-list class="mt-4 space-y-4">
                @if ($placeReviews->count() === 0)
                    <div class="rounded-xl border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700">
                        {{ __('reviews.list.empty') }}
                    </div>
                @else
                    @include('places._review-cards', ['placeReviews' => $placeReviews->getCollection()])
                @endif
            </div>

            <div data-review-controls class="mt-5 text-center {{ $placeReviews->hasMorePages() ? '' : 'hidden' }}">
                <button
                    type="button"
                    data-review-load
                    class="inline-flex min-w-56 items-center justify-center rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium hover:bg-zinc-50 disabled:cursor-wait disabled:opacity-60 dark:border-zinc-700 dark:hover:bg-zinc-800"
                >
                    {{ trans_choice('reviews.list.show_more', min(5, $remainingReviews), ['count' => min(5, $remainingReviews)]) }}
                </button>
                <div data-review-remaining class="mt-1 text-xs text-zinc-400">{{ trans_choice('reviews.list.remaining', $remainingReviews, ['count' => $remainingReviews]) }}</div>
                <div data-review-status role="status" aria-live="polite" class="mt-2 hidden text-sm text-red-600 dark:text-red-400"></div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
(() => {
    const reviewTranslations = {
        removeFile: @js(__('photos.picker.remove_file', ['name' => ':name'])),
        submitWithPhotos: @js(__('photos.picker.submit_with_photos', ['action' => ':action', 'count' => ':count', 'photos' => ':photos'])),
        photo: @js(trans_choice('photos.photo', 1)),
        photos: @js(trans_choice('photos.photo', 2)),
        selectionExceeded: @js(__('photos.picker.selection_exceeded', ['maximum' => ':maximum', 'photos' => ':photos'])),
        remainingOne: @js(trans_choice('reviews.list.remaining', 1, ['count' => ':count'])),
        remainingMany: @js(trans_choice('reviews.list.remaining', 2, ['count' => ':count'])),
        showOne: @js(trans_choice('reviews.list.show_more', 1, ['count' => ':count'])),
        showMany: @js(trans_choice('reviews.list.show_more', 2, ['count' => ':count'])),
        loading: @js(__('reviews.list.loading')),
        loadFailed: @js(__('reviews.list.load_failed')),
    };

    const interpolate = (template, replacements) => Object.entries(replacements)
        .reduce((value, [key, replacement]) => value.replaceAll(`:${key}`, String(replacement)), template);

    const palette = {
        overall: '#71717a',
        cleanliness: '#16a34a',
        functionality: '#2563eb',
        condition: '#9333ea',
        safety: '#dc2626',
        usability: '#d97706',
    };

    const bindReviewCharts = () => {
        document.querySelectorAll('[data-review-chart]').forEach((root) => {
            if (root.dataset.bound) return;
            root.dataset.bound = '1';

            const canvas = root.querySelector('[data-chart-canvas]');
            if (!canvas) return;

            const series = JSON.parse(root.dataset.series || '[]');
            const checkboxes = [...root.querySelectorAll('[data-chart-series]')];

            const draw = () => {
                palette.overall = getComputedStyle(root).color || '#71717a';
                const rect = canvas.getBoundingClientRect();
                const ratio = window.devicePixelRatio || 1;
                canvas.width = Math.max(1, Math.round(rect.width * ratio));
                canvas.height = Math.max(1, Math.round(208 * ratio));

                const ctx = canvas.getContext('2d');
                ctx.scale(ratio, ratio);

                const width = rect.width;
                const height = 208;
                const pad = { left: 32, right: 10, top: 10, bottom: 28 };
                const plotWidth = Math.max(1, width - pad.left - pad.right);
                const plotHeight = height - pad.top - pad.bottom;

                ctx.clearRect(0, 0, width, height);
                ctx.font = '10px sans-serif';
                ctx.lineWidth = 1;
                ctx.textAlign = 'right';
                ctx.textBaseline = 'middle';

                for (let value = 1; value <= 5; value++) {
                    const y = pad.top + ((5 - value) / 4) * plotHeight;
                    ctx.strokeStyle = 'rgba(113,113,122,.18)';
                    ctx.beginPath();
                    ctx.moveTo(pad.left, y);
                    ctx.lineTo(width - pad.right, y);
                    ctx.stroke();
                    ctx.fillStyle = 'rgb(113,113,122)';
                    ctx.fillText(String(value), pad.left - 7, y);
                }

                if (series.length === 0) return;

                const xFor = (index) => series.length === 1
                    ? pad.left + plotWidth / 2
                    : pad.left + (index / (series.length - 1)) * plotWidth;
                const yFor = (value) => pad.top + ((5 - value) / 4) * plotHeight;

                const activeKeys = checkboxes.filter((box) => box.checked).map((box) => box.dataset.chartSeries);

                activeKeys.forEach((key) => {
                    ctx.strokeStyle = palette[key] || '#71717a';
                    ctx.lineWidth = key === 'overall' ? 3 : 1.5;
                    ctx.beginPath();

                    let drawing = false;
                    series.forEach((point, index) => {
                        const value = point[key];
                        if (value === null || value === undefined) {
                            drawing = false;
                            return;
                        }

                        const x = xFor(index);
                        const y = yFor(Number(value));

                        if (!drawing) {
                            ctx.moveTo(x, y);
                            drawing = true;
                        } else {
                            ctx.lineTo(x, y);
                        }
                    });

                    ctx.stroke();
                });

                ctx.fillStyle = 'rgb(113,113,122)';
                ctx.textBaseline = 'top';
                ctx.textAlign = 'center';

                const labelEvery = Math.max(1, Math.ceil(series.length / 6));
                series.forEach((point, index) => {
                    if (index % labelEvery !== 0 && index !== series.length - 1) return;
                    const [year, month] = point.month.split('-');
                    ctx.fillText(month + '/' + year.slice(2), xFor(index), height - 18);
                });
            };

            checkboxes.forEach((box) => box.addEventListener('change', draw));
            window.addEventListener('resize', draw, { passive: true });

            const historyDetails = root.closest('[data-review-history]');
            if (historyDetails) {
                historyDetails.addEventListener('toggle', () => {
                    if (historyDetails.open) {
                        window.requestAnimationFrame(draw);
                    }
                });
            }

            if (!historyDetails || historyDetails.open) {
                draw();
            }
        });
    };

    const bindPhotoPickers = () => {
        document.querySelectorAll('[data-photo-picker]').forEach((picker) => {
            if (picker.dataset.bound) return;
            picker.dataset.bound = '1';

            const input = picker.querySelector('[data-photo-input]');
            const openButton = picker.querySelector('[data-photo-open]');
            const previews = picker.querySelector('[data-photo-previews]');
            const count = picker.querySelector('[data-photo-count]');
            const error = picker.querySelector('[data-photo-error]');
            const form = picker.closest('form');
            const submit = form?.querySelector('[data-photo-submit]');
            const maximum = Number(picker.dataset.photoMax || 0);
            const selected = new Map();

            if (!input || maximum < 1) return;

            const keyFor = (file) => `${file.name}:${file.size}:${file.lastModified}`;

            const syncInput = () => {
                const transfer = new DataTransfer();
                selected.forEach(({ file }) => transfer.items.add(file));
                input.files = transfer.files;
            };

            const refresh = () => {
                previews.replaceChildren();
                previews.classList.toggle('hidden', selected.size === 0);
                previews.classList.toggle('flex', selected.size > 0);
                count.textContent = String(selected.size);

                selected.forEach(({ file, url }, key) => {
                    const card = document.createElement('div');
                    card.className = 'relative w-28 overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900';

                    const image = document.createElement('img');
                    image.src = url;
                    image.alt = file.name;
                    image.className = 'aspect-square w-full bg-zinc-100 object-cover dark:bg-zinc-800';

                    const name = document.createElement('div');
                    name.className = 'truncate px-2 py-1.5 text-[10px] text-zinc-500';
                    name.textContent = file.name;
                    name.title = file.name;

                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'absolute right-1 top-1 grid size-6 place-items-center rounded-full bg-black/70 text-sm font-bold text-white hover:bg-black';
                    remove.setAttribute('aria-label', interpolate(reviewTranslations.removeFile, { name: file.name }));
                    remove.textContent = '×';
                    remove.addEventListener('click', () => {
                        URL.revokeObjectURL(url);
                        selected.delete(key);
                        syncInput();
                        refresh();
                    });

                    card.append(image, name, remove);
                    previews.append(card);
                });

                if (submit) {
                    const base = submit.dataset.photoSubmitBase || submit.textContent.trim();
                    submit.textContent = selected.size > 0
                        ? interpolate(reviewTranslations.submitWithPhotos, {
                            action: base,
                            count: selected.size,
                            photos: selected.size === 1 ? reviewTranslations.photo : reviewTranslations.photos,
                        })
                        : base;
                }
            };

            openButton?.addEventListener('click', () => input.click());
            input.addEventListener('change', () => {
                const incoming = [...input.files];
                let rejected = 0;

                incoming.forEach((file) => {
                    const key = keyFor(file);
                    if (selected.has(key)) return;
                    if (selected.size >= maximum) {
                        rejected++;
                        return;
                    }
                    selected.set(key, { file, url: URL.createObjectURL(file) });
                });

                syncInput();
                refresh();

                if (rejected > 0) {
                    error.textContent = interpolate(reviewTranslations.selectionExceeded, {
                        maximum,
                        photos: maximum === 1 ? reviewTranslations.photo : reviewTranslations.photos,
                    });
                    error.classList.remove('hidden');
                } else {
                    error.textContent = '';
                    error.classList.add('hidden');
                }
            });

            form?.addEventListener('reset', () => {
                selected.forEach(({ url }) => URL.revokeObjectURL(url));
                selected.clear();
                window.requestAnimationFrame(() => {
                    syncInput();
                    refresh();
                });
            });

            refresh();
        });
    };

    const bindReviewFeeds = () => {
        document.querySelectorAll('[data-review-feed]').forEach((root) => {
            if (root.dataset.feedBound) return;
            root.dataset.feedBound = '1';

            const list = root.querySelector('[data-review-list]');
            const controls = root.querySelector('[data-review-controls]');
            const button = root.querySelector('[data-review-load]');
            const remainingNode = root.querySelector('[data-review-remaining]');
            const status = root.querySelector('[data-review-status]');
            if (!list || !controls || !button || !remainingNode || !status) return;

            let cursor = root.dataset.reviewCursor || '';
            let loaded = Number(root.dataset.reviewLoaded || 0);
            const total = Number(root.dataset.reviewTotal || 0);
            let loading = false;

            const refresh = () => {
                const remaining = Math.max(0, total - loaded);
                const nextBatch = Math.min(5, remaining);
                remainingNode.textContent = interpolate(
                    remaining === 1 ? reviewTranslations.remainingOne : reviewTranslations.remainingMany,
                    { count: remaining },
                );
                button.textContent = interpolate(
                    nextBatch === 1 ? reviewTranslations.showOne : reviewTranslations.showMany,
                    { count: nextBatch },
                );
                controls.classList.toggle('hidden', !cursor || remaining === 0);
            };

            button.addEventListener('click', async () => {
                if (loading || !cursor) return;
                loading = true;
                button.disabled = true;
                button.textContent = reviewTranslations.loading;
                status.textContent = '';
                status.classList.add('hidden');
                root.setAttribute('aria-busy', 'true');

                try {
                    const url = new URL(root.dataset.reviewUrl, window.location.origin);
                    url.searchParams.set('sort', root.dataset.reviewSort || 'newest');
                    url.searchParams.set('review_cursor', cursor);

                    const response = await fetch(url, {
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);

                    const payload = await response.json();
                    const template = document.createElement('template');
                    template.innerHTML = payload.html || '';
                    let appended = 0;

                    template.content.querySelectorAll('[data-review-id]').forEach((card) => {
                        const id = card.dataset.reviewId;
                        if (!id || list.querySelector(`[data-review-id="${id}"]`)) return;
                        list.append(card);
                        appended++;
                    });

                    loaded += appended;
                    cursor = payload.next_cursor || '';
                    root.dataset.reviewLoaded = String(loaded);
                    root.dataset.reviewCursor = cursor;
                    refresh();
                } catch (error) {
                    status.textContent = reviewTranslations.loadFailed;
                    status.classList.remove('hidden');
                } finally {
                    loading = false;
                    button.disabled = false;
                    root.removeAttribute('aria-busy');
                    if (!controls.classList.contains('hidden')) refresh();
                }
            });

            refresh();
        });
    };

    const init = () => {
        bindReviewCharts();
        bindPhotoPickers();
        bindReviewFeeds();
    };

    document.addEventListener('DOMContentLoaded', init, { once: true });
    document.addEventListener('livewire:navigated', init);
})();
</script>
@endpush
