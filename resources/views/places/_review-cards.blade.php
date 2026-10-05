@foreach ($placeReviews as $review)
                @php
                    $presenter = $reviewPresenters[(int) $review->user_id] ?? null;
                    $isOwnReview = auth()->check() && (int) auth()->id() === (int) $review->user_id;
                    $photos = $reviewPhotos->get((int) $review->review_id, collect());
                @endphp

                <article id="review-{{ $review->review_id }}" data-review-id="{{ $review->review_id }}" class="scroll-mt-24 grid gap-5 rounded-xl border border-zinc-200 p-4 md:grid-cols-[220px_minmax(0,1fr)] dark:border-zinc-800">
                    <div class="min-w-0">
                        @if ($presenter)
                            <div class="flex items-center gap-3">
                                <x-user-status-avatar
                                    :user="$presenter['user']"
                                    :photo-url="$presenter['photo_url']"
                                    :gamification="$presenter['gamification']"
                                    :title="$presenter['title']"
                                    :joined-at="$presenter['joined_at']"
                                    shape="rounded-full"
                                />
                                <div class="min-w-0">
                                    @if ($review->public_handle)
                                        <a href="{{ route('users.profile', $review->public_handle) }}" class="block truncate text-sm font-semibold hover:underline">
                                            {{ $review->public_handle }}
                                        </a>
                                    @else
                                        <div class="truncate text-sm font-semibold">{{ __('reviews.card.anonymous_user') }}</div>
                                    @endif
                                    @if ($presenter['title'])
                                        <div class="truncate text-xs text-zinc-500">{{ $presenter['title']['label'] }}</div>
                                    @endif
                                    @if ($presenter['gamification'])
                                        <div class="mt-0.5 text-xs text-zinc-400">{{ __('reviews.card.level', ['level' => $presenter['gamification']['level']]) }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if ($review->verified_visit)
                            <div class="mt-3 inline-flex rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-medium text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                                {{ __('reviews.card.verified_visit') }}
                            </div>
                        @endif

                        <div class="mt-5 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                            <div class="flex items-center gap-3">
                                <div class="flex items-baseline gap-1.5">
                                    <div class="text-4xl font-bold tracking-tight">{{ \Illuminate\Support\Number::format((float) $review->overall_score, 1, 1, app()->getLocale()) }}</div>
                                    <div class="text-xs text-zinc-500">{{ __('reviews.out_of_five') }}</div>
                                </div>

                                <details class="group">
                                    <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-[11px] font-medium text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300">
                                        <span>{{ __('reviews.card.details') }}</span>
                                        <span class="transition group-open:rotate-180">⌄</span>
                                    </summary>

                                    <dl class="mt-3 space-y-2 text-xs">
                                        @foreach ($dimensionMeta as $key => $meta)
                                            <div class="flex items-baseline gap-2">
                                                <dt class="text-zinc-500">{{ $meta['label'] }}</dt>
                                                <dd class="font-semibold">
                                                    {{ \Illuminate\Support\Number::format((float) $review->{$meta['field']}, 1, 1, app()->getLocale()) }} <span class="text-amber-500">★</span>
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </details>
                            </div>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div class="text-xs text-zinc-400">
                            {{ \App\Support\LocalTime::parse($review->current_published_at)->translatedFormat(__('reviews.date_format')) }}
                        </div>

                        @if ($review->review_text)
                            <p class="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-zinc-700 dark:text-zinc-300">{{ $review->review_text }}</p>
                        @else
                            <div class="mt-2 inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                {{ __('reviews.card.stars_only') }}
                            </div>
                        @endif

                        @if ($photos->isNotEmpty())
                            <div class="mt-4 flex flex-wrap gap-3">
                                @foreach ($photos as $photo)
                                    <div id="photo-{{ $photo->id }}" class="scroll-mt-24 w-36 overflow-hidden rounded-lg border border-zinc-200 sm:w-44 dark:border-zinc-700">
                                        <button type="button" data-photo-lightbox data-photo-src="{{ route('photos.show', ['uuid' => $photo->uuid, 'variant' => 'detail']) }}" data-photo-alt="{{ __('photos.review_photo') }}" data-photo-caption="{{ __('photos.helpful_count', ['count' => $photo->helpful_count]) }}" class="block w-full">
                                            <img src="{{ route('photos.show', ['uuid' => $photo->uuid, 'variant' => 'preview']) }}" alt="{{ __('photos.review_photo') }}" loading="lazy" class="aspect-[4/3] w-full object-cover">
                                        </button>
                                        <div class="flex flex-wrap items-center gap-1 p-2 text-[11px]">
                                            <span class="mr-auto text-zinc-500">{{ __('photos.helpful_count', ['count' => $photo->helpful_count]) }}</span>
                                            @auth
                                                @if (! $isOwnReview)
                                                    <form method="POST" action="{{ $photo->viewer_voted ? route('photos.helpful.destroy', $photo->id) : route('photos.helpful.store', $photo->id) }}">
                                                        @csrf
                                                        @if ($photo->viewer_voted) @method('DELETE') @endif
                                                        <button type="submit" class="rounded px-1.5 py-1 font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                                            {{ $photo->viewer_voted ? __('photos.helpful_selected') : __('photos.helpful') }}
                                                        </button>
                                                    </form>
                                                    <details class="relative">
                                                        <summary class="cursor-pointer list-none rounded px-1.5 py-1 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('photos.report') }}</summary>
                                                        <form method="POST" action="{{ route('photos.report', $photo->id) }}" class="absolute right-0 top-7 z-20 w-64 rounded-lg border border-zinc-200 bg-white p-3 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                                                            @csrf
                                                            <select name="reason" required class="w-full rounded border border-zinc-300 bg-white p-2 text-xs dark:border-zinc-700 dark:bg-zinc-950">
                                                                @foreach (['wrong_place', 'privacy', 'inappropriate', 'spam', 'copyright', 'other'] as $reason)
                                                                    <option value="{{ $reason }}">{{ __("photos.report_reasons.{$reason}") }}</option>
                                                                @endforeach
                                                            </select>
                                                            <textarea name="comment" rows="2" maxlength="1000" placeholder="{{ __('photos.report_comment_placeholder') }}" class="mt-2 w-full rounded border border-zinc-300 bg-white p-2 text-xs dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                                                            <button type="submit" class="mt-2 rounded bg-zinc-900 px-2 py-1.5 font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('photos.report_submit') }}</button>
                                                        </form>
                                                    </details>
                                                @endif
                                                @if ($canManagePhotoCovers)
                                                    @if ((int) ($placePhotoSetting?->admin_photo_id ?? 0) === (int) $photo->id)
                                                        <form method="POST" action="{{ route('admin.photos.cover.clear', $place->id) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="rounded px-1.5 py-1 font-medium text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-950/30">{{ __('photos.cover.admin_selected') }}</button>
                                                        </form>
                                                    @else
                                                        <form method="POST" action="{{ route('admin.photos.cover.set', $photo->id) }}">
                                                            @csrf
                                                            <button type="submit" class="rounded px-1.5 py-1 font-medium text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('photos.cover.select') }}</button>
                                                        </form>
                                                    @endif
                                                    @if ($photo->is_thumbnail_eligible)
                                                        <form method="POST" action="{{ route('admin.photos.thumbnail.exclude', $photo->id) }}" onsubmit='return confirm(@json(__('photos.cover.exclude_confirm')));'>
                                                            @csrf
                                                            <input type="hidden" name="reason" value="{{ __('photos.cover.exclude_reason') }}">
                                                            <button type="submit" class="rounded px-1.5 py-1 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30">{{ __('photos.cover.exclude') }}</button>
                                                        </form>
                                                    @else
                                                        <form method="POST" action="{{ route('admin.photos.thumbnail.include', $photo->id) }}">
                                                            @csrf
                                                            <button type="submit" class="rounded px-1.5 py-1 text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-950/30">{{ __('photos.cover.include') }}</button>
                                                        </form>
                                                    @endif
                                                @endif
                                            @endauth
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-3 text-xs dark:border-zinc-800">
                            @if ((int) $review->history_count > 0)
                                <a href="{{ route('reviews.history', $review->review_id) }}" class="rounded-md px-2 py-1 font-medium text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-white">
                                    {{ __('reviews.card.history', ['count' => $review->history_count]) }}
                                </a>
                            @endif

                            @auth
                                @if (! $isOwnReview)
                                    <details class="relative">
                                        <summary class="cursor-pointer list-none rounded-md px-2 py-1 font-medium text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                                            {{ __('reviews.card.report') }}
                                        </summary>
                                        <form method="POST" action="{{ route('reviews.report', $review->review_id) }}" class="absolute right-0 top-7 z-20 w-72 max-w-[calc(100vw-3rem)] rounded-xl border border-zinc-200 bg-white p-3 shadow-xl md:left-0 md:right-auto dark:border-zinc-700 dark:bg-zinc-900">
                                            @csrf
                                            <label class="block text-xs font-medium">
                                                {{ __('reviews.card.report_reason') }}
                                                <select name="reason" required class="mt-1 w-full rounded-md border border-zinc-300 bg-white px-2 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-950">
                                                    @foreach (['illegal_inappropriate', 'abuse_threat_discrimination', 'personal_data', 'false_invented', 'serious_accusation', 'personal_dispute', 'unrelated', 'advertising_spam', 'manipulated_conflict', 'copyright', 'other'] as $reason)
                                                        <option value="{{ $reason }}">{{ __("reviews.report_reasons.{$reason}") }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label class="mt-3 block text-xs font-medium">
                                                {{ __('reviews.card.report_comment') }} <span class="font-normal text-zinc-400">({{ __('reviews.form.optional') }})</span>
                                                <textarea name="comment" rows="3" maxlength="1000" class="mt-1 w-full rounded-md border border-zinc-300 bg-white px-2 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-950" placeholder="{{ __('reviews.card.report_comment_placeholder') }}"></textarea>
                                            </label>
                                            <button type="submit" class="mt-3 rounded-md bg-zinc-900 px-3 py-2 text-xs font-semibold text-white dark:bg-white dark:text-zinc-950">
                                                {{ __('reviews.card.report_submit') }}
                                            </button>
                                        </form>
                                    </details>
                                @endif
                            @endauth
                        </div>
                    </div>

                </article>
@endforeach
