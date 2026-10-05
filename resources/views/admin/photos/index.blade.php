<x-layouts::app :title="__('admin.photos.moderation_title')">
    <div class="space-y-8">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <flux:heading size="xl">{{ __('admin.photos.moderation_title') }}</flux:heading>
                <flux:text class="mt-1">{{ __('admin.photos.moderation_intro') }}</flux:text>
            </div>
            <a href="{{ route('admin.photos.library') }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                {{ __('admin.photos.manage_all') }}
            </a>
        </div>
<section>
            <div class="mb-4 flex items-center gap-2">
                <h2 class="text-lg font-semibold">{{ __('admin.photos.awaiting_approval') }}</h2>
                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-500 dark:bg-zinc-800">{{ $pendingPhotos->total() }}</span>
            </div>

            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($pendingPhotos as $photo)
                    <article class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                        @if ($photo->uuid)
                            <button type="button" data-admin-photo-lightbox data-photo-src="{{ route('admin.photos.asset', ['uuid' => $photo->uuid, 'variant' => 'detail']) }}" data-photo-alt="{{ __('admin.photos.pending_photo_alt', ['place' => $photo->place_name]) }}" data-photo-caption="{{ $photo->place_name }}" class="block w-full cursor-zoom-in">
                                <img
                                    src="{{ route('admin.photos.asset', ['uuid' => $photo->uuid, 'variant' => 'preview']) }}"
                                    alt="{{ __('admin.photos.pending_photo_alt', ['place' => $photo->place_name]) }}"
                                    class="h-56 w-full bg-zinc-100 object-contain dark:bg-zinc-950"
                                >
                            </button>
                        @else
                            <div class="grid h-56 place-items-center bg-zinc-100 text-sm text-zinc-500 dark:bg-zinc-950">{{ __('admin.photos.legacy_without_file') }}</div>
                        @endif
                        <div class="space-y-4 p-4">
                            <div>
                                <a href="{{ route('places.show', $photo->place_slug) }}" class="font-semibold hover:underline">{{ $photo->place_name }}</a>
                                <div class="mt-1 text-xs text-zinc-500">{{ __('admin.photos.uploaded_by', ['name' => $photo->user_name, 'date' => \App\Support\LocalTime::parse($photo->created_at)->format(__('admin.photos.date_format'))]) }}</div>
                                <div class="text-xs text-zinc-400">{{ $photo->width }} × {{ $photo->height }} {{ __('admin.photos.pixels') }}</div>
                            </div>

                            @if ($photo->uuid)
                                <form method="POST" action="{{ route('admin.photos.approve', $photo->id) }}">
                                    @csrf
                                    <button class="w-full rounded-lg bg-green-700 px-3 py-2 text-sm font-semibold text-white hover:bg-green-600">{{ __('admin.photos.approve') }}</button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('admin.photos.reject', $photo->id) }}" class="space-y-2">
                                @csrf
<select name="reason_code" required class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                    <option value="">{{ __('admin.photos.choose_moderation_reason') }}</option>
                                    @foreach (__('admin.photos.moderation_reasons') as $code => $label)
                                        <option value="{{ $code }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input name="reason_details" maxlength="500" placeholder="{{ __('admin.photos.reason_details_optional') }}" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                <button class="w-full rounded-lg border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950/30">{{ __('admin.photos.reject') }}</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-xl border border-dashed border-zinc-300 p-6 text-sm text-zinc-500 dark:border-zinc-700">{{ __('admin.photos.no_pending') }}</div>
                @endforelse
            </div>

            @if ($pendingPhotos->hasPages())
                <div class="mt-5">{{ $pendingPhotos->links() }}</div>
            @endif
        </section>

        <section>
            <div class="mb-4 flex items-center gap-2">
                <h2 class="text-lg font-semibold">{{ __('admin.photos.open_reports') }}</h2>
                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-500 dark:bg-zinc-800">{{ $photoReports->count() }}</span>
            </div>

            <div class="space-y-4">
                @forelse ($photoReports as $report)
                    <article class="grid gap-4 rounded-xl border border-zinc-200 p-4 md:grid-cols-[180px_minmax(0,1fr)] dark:border-zinc-800">
                        @if ($report->uuid)
                            <button type="button" data-admin-photo-lightbox data-photo-src="{{ route('admin.photos.asset', ['uuid' => $report->uuid, 'variant' => 'detail']) }}" data-photo-alt="{{ __('admin.photos.reported_photo_alt') }}" data-photo-caption="{{ $report->place_name }}" class="block w-full cursor-zoom-in">
                                <img src="{{ route('admin.photos.asset', ['uuid' => $report->uuid, 'variant' => 'preview']) }}" alt="{{ __('admin.photos.reported_photo_alt') }}" class="h-40 w-full rounded-lg bg-zinc-100 object-contain dark:bg-zinc-950">
                            </button>
                        @else
                            <div class="grid h-40 place-items-center rounded-lg bg-zinc-100 text-sm text-zinc-500 dark:bg-zinc-950">{{ __('admin.photos.legacy_without_file') }}</div>
                        @endif
                        <div class="space-y-3">
                            <div>
                                <a href="{{ route('places.show', $report->place_slug) }}" class="font-semibold hover:underline">{{ $report->place_name }}</a>
                                @php($reasonKey = 'photos.report_reasons.'.$report->reason)
                                <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('admin.photos.reason') }}: {{ __($reasonKey) === $reasonKey ? $report->reason : __($reasonKey) }}</div>
                                @if ($report->comment)<div class="mt-1 text-sm text-zinc-500">{{ $report->comment }}</div>@endif
                                <div class="mt-1 text-xs text-zinc-400">{{ __('admin.photos.reported_by', ['name' => $report->reporter_name, 'date' => \App\Support\LocalTime::parse($report->created_at)->format(__('admin.photos.date_format'))]) }}</div>
                            </div>

                            <div class="grid gap-3 lg:grid-cols-2">
                                <form method="POST" action="{{ route('admin.photos.reports.dismiss', $report->report_id) }}" class="space-y-2">
                                    @csrf
                                    <input name="comment" maxlength="2000" placeholder="{{ __('admin.photos.internal_comment_optional') }}" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                    <button class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">{{ __('admin.photos.no_violation') }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.photos.remove', $report->id) }}" class="space-y-2">
                                    @csrf
                                    <input type="hidden" name="report_id" value="{{ $report->report_id }}">
<select name="reason_code" required class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                    <option value="">{{ __('admin.photos.choose_moderation_reason') }}</option>
                                    @foreach (__('admin.photos.moderation_reasons') as $code => $label)
                                        <option value="{{ $code }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input name="reason_details" maxlength="500" placeholder="{{ __('admin.photos.reason_details_optional') }}" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                    <button class="w-full rounded-lg bg-red-700 px-3 py-2 text-sm font-semibold text-white hover:bg-red-600">{{ __('admin.photos.remove_photo') }}</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-zinc-300 p-6 text-sm text-zinc-500 dark:border-zinc-700">{{ __('admin.photos.no_open_reports') }}</div>
                @endforelse
            </div>
        </section>
    </div>
    @include('admin.photos._lightbox')
</x-layouts::app>
