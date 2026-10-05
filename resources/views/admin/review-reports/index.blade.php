<x-layouts::app :title="__('admin.review_reports.title')">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('admin.review_reports.title') }}</flux:heading>
                <flux:text class="mt-1">{{ __('admin.review_reports.intro') }}</flux:text>
            </div>
            <a href="{{ route('admin.index') }}" class="text-sm underline">{{ __('admin.review_reports.back_admin') }}</a>
        </div>
<div class="flex flex-wrap gap-2 text-sm">
            @foreach (['pending', 'removed', 'dismissed'] as $key)
                <a
                    href="{{ route('admin.review-reports.index', ['status' => $key]) }}"
                    class="rounded-lg border px-3 py-2 {{ $status === $key ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-950' : 'border-zinc-300 dark:border-zinc-700' }}"
                >
                    {{ __('admin.review_reports.statuses.'.$key) }}
                </a>
            @endforeach
        </div>

        <div class="space-y-4">
            @forelse ($reports as $report)
                <article class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="text-xs text-zinc-400">{{ \App\Support\LocalTime::parse($report->created_at)->format(__('admin.review_reports.date_format')) }}</div>
                            <h2 class="mt-1 font-semibold">{{ $report->place_name }}</h2>
                            <div class="mt-1 text-xs text-zinc-500">
                                {{ __('admin.review_reports.reported_by', ['reporter' => $report->reporter_name, 'author' => $report->author_name]) }}
                            </div>
                        </div>
                        <a href="{{ route('places.show', $report->place_slug) }}#reviews" class="text-sm font-medium underline">{{ __('admin.review_reports.open_place') }}</a>
                    </div>

                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                        <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-950/50">
                            <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('admin.review_reports.report') }}</div>
                            <div class="mt-2 text-sm font-medium">
                                {{ __('admin.review_reports.reasons.'.$report->reason) === 'admin.review_reports.reasons.'.$report->reason ? $report->reason : __('admin.review_reports.reasons.'.$report->reason) }}
                            </div>
                            @if ($report->comment)
                                <p class="mt-2 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-300">{{ $report->comment }}</p>
                            @endif
                        </div>

                        <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-950/50">
                            <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('admin.review_reports.current_review') }}</div>
                            @if ($report->overall_score !== null)
                                <div class="mt-2 text-lg font-semibold">{{ number_format((float) $report->overall_score, 1, __('admin.review_reports.decimal_separator'), __('admin.review_reports.thousands_separator')) }} / 5</div>
                            @endif
                            @if ($report->review_text)
                                <p class="mt-2 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-300">{{ $report->review_text }}</p>
                            @else
                                <p class="mt-2 text-sm italic text-zinc-400">{{ __('admin.review_reports.no_review_text') }}</p>
                            @endif
                        </div>
                    </div>

                    @if ($status === 'pending')
                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <form method="POST" action="{{ route('admin.review-reports.dismiss', $report->id) }}" class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                                @csrf
                                <label class="block text-xs font-medium">
                                    {{ __('admin.review_reports.internal_comment') }} <span class="font-normal text-zinc-400">({{ __('admin.review_reports.optional') }})</span>
                                    <textarea name="moderator_comment" rows="2" maxlength="2000" class="mt-1 w-full rounded-md border border-zinc-300 bg-white px-2 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                                </label>
                                <button type="submit" class="mt-3 rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                                    {{ __('admin.review_reports.dismiss') }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.review-reports.remove', $report->id) }}" class="rounded-lg border border-red-200 p-3 dark:border-red-900">
                                @csrf
                                <label class="block text-xs font-medium">
                                    {{ __('admin.review_reports.removal_reason') }}
                                    <select name="reason_code" required class="mt-1 w-full rounded-md border border-zinc-300 bg-white px-2 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-950">
                                        <option value="">{{ __('admin.review_reports.choose_reason') }}</option>
                                        @foreach (__('admin.review_reports.moderation_reasons') as $code => $label)
                                            <option value="{{ $code }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="mt-2 block text-xs font-medium">
                                    {{ __('admin.review_reports.reason_details') }} <span class="font-normal text-zinc-400">({{ __('admin.review_reports.optional') }})</span>
                                    <textarea name="reason_details" rows="2" maxlength="1000" class="mt-1 w-full rounded-md border border-zinc-300 bg-white px-2 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                                </label>
                                <button type="submit" class="mt-3 rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-500" onclick="return confirm(@js(__('admin.review_reports.remove_confirmation')));">
                                    {{ __('admin.review_reports.remove') }}
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="mt-4 text-xs text-zinc-500">
                            {{ __('admin.review_reports.moderated_at', ['date' => $report->moderated_at ? \App\Support\LocalTime::parse($report->moderated_at)->format(__('admin.review_reports.date_format')) : '–']) }}
                            @if ($report->moderator_name) · {{ __('admin.review_reports.moderated_by', ['name' => $report->moderator_name]) }} @endif
                            @if ($report->moderator_comment) · {{ $report->moderator_comment }} @endif
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700">
                    {{ __('admin.review_reports.empty') }}
                </div>
            @endforelse
        </div>

        {{ $reports->links() }}
    </div>
</x-layouts::app>
