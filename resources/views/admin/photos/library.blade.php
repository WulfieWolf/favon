<x-layouts::app :title="__('admin.photos.library_title')">
    @php
        $sortable = __('admin.photos.sortable');
        $sortUrl = fn (string $column) => route('admin.photos.library', array_merge(request()->except(['sort', 'direction', 'library_page']), [
            'sort' => $column,
            'direction' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc',
        ]));
        $sortMarker = fn (string $column) => $sort === $column ? ($direction === 'asc' ? ' ↑' : ' ↓') : '';
        $statusLabels = __('photos.status');
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <flux:heading size="xl">{{ __('admin.photos.library_title') }}</flux:heading>
                <flux:text class="mt-1">{{ __('admin.photos.library_intro') }}</flux:text>
            </div>
            <a href="{{ route('admin.photos.index') }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                {{ __('admin.photos.to_moderation') }}
            </a>
        </div>
<form method="GET" action="{{ route('admin.photos.library') }}" class="grid gap-3 rounded-xl border border-zinc-200 p-4 md:grid-cols-[minmax(220px,1fr)_180px_180px_auto] dark:border-zinc-800">
            <input name="search" value="{{ $filters['search'] }}" placeholder="{{ __('admin.photos.search_placeholder') }}" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
            <select name="status" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="">{{ __('admin.photos.all_statuses') }}</option>
                @foreach ($statusLabels as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="activity" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="all" @selected($filters['activity'] === 'all')>{{ __('admin.photos.activity_all') }}</option>
                <option value="active" @selected($filters['activity'] === 'active')>{{ __('admin.photos.activity_active') }}</option>
                <option value="inactive" @selected($filters['activity'] === 'inactive')>{{ __('admin.photos.activity_inactive') }}</option>
            </select>
            <button class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('admin.photos.filter') }}</button>
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
        </form>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
            <table class="min-w-[1350px] w-full divide-y divide-zinc-200 text-left text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-xs text-zinc-500 dark:bg-zinc-900">
                    <tr>
                        @foreach ($sortable as $column => $label)
                            <th class="whitespace-nowrap px-3 py-3 font-semibold">
                                <a href="{{ $sortUrl($column) }}" class="hover:text-zinc-950 dark:hover:text-white">{{ $label }}{{ $sortMarker($column) }}</a>
                            </th>
                        @endforeach
                        <th class="px-3 py-3 font-semibold">{{ __('admin.photos.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($photos as $photo)
                        <tr class="align-top {{ $photo->is_active ? '' : 'opacity-65' }}">
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2">
                                    @if ($photo->uuid && $photo->is_active && in_array($photo->status, ['pending', 'approved'], true))
                                        <button type="button" data-admin-photo-lightbox data-photo-src="{{ route('admin.photos.asset', ['uuid' => $photo->uuid, 'variant' => 'detail']) }}" data-photo-alt="{{ __('admin.photos.photo_alt', ['id' => $photo->id]) }}" data-photo-caption="{{ $photo->place_name ?? ('#'.$photo->id) }}" class="cursor-zoom-in"><img src="{{ route('admin.photos.asset', ['uuid' => $photo->uuid, 'variant' => 'preview']) }}" alt="{{ __('admin.photos.photo_alt', ['id' => $photo->id]) }}" class="h-14 w-20 rounded bg-zinc-100 object-cover dark:bg-zinc-900"></button>
                                    @else
                                        <div class="grid h-14 w-20 place-items-center rounded bg-zinc-100 text-[10px] text-zinc-400 dark:bg-zinc-900">{{ __('admin.photos.no_file') }}</div>
                                    @endif
                                    <div><div class="font-semibold">#{{ $photo->id }}</div><div class="max-w-28 truncate text-[10px] text-zinc-400" title="{{ $photo->uuid }}">{{ $photo->uuid ?: __('admin.photos.legacy_without_uuid') }}</div></div>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                @if ($photo->place_slug)
                                    <a href="{{ route('places.show', $photo->place_slug) }}" class="font-medium hover:underline">{{ $photo->place_name }}</a>
                                @else
                                    <span class="text-zinc-400">{{ __('admin.photos.no_assignment') }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @if ($photo->place_review_id && $photo->place_slug)
                                    <a href="{{ route('places.show', $photo->place_slug).'#review-'.$photo->place_review_id }}" class="font-medium hover:underline">#{{ $photo->place_review_id }}</a>
                                    <div class="text-xs text-zinc-400">{{ __('admin.photos.review_statuses.'.$photo->review_status) }}</div>
                                @else
                                    <span class="text-zinc-400">–</span>
                                @endif
                            </td>
                            <td class="px-3 py-3"><div class="font-medium">{{ $photo->author_name ?? __('admin.photos.unknown') }}</div><div class="text-xs text-zinc-400">{{ $photo->author_email }}</div></td>
                            <td class="px-3 py-3"><span class="rounded-full bg-zinc-100 px-2 py-1 text-xs dark:bg-zinc-800">{{ $statusLabels[$photo->status] ?? $photo->status }}</span>@if(!$photo->is_active)<div class="mt-1 text-xs text-red-500">{{ __('admin.photos.inactive') }}</div>@endif</td>
                            <td class="whitespace-nowrap px-3 py-3">{{ $photo->width }} × {{ $photo->height }} px</td>
                            <td class="whitespace-nowrap px-3 py-3">{{ $photo->mime_type }}</td>
                            <td class="whitespace-nowrap px-3 py-3">{{ number_format(((int) $photo->file_size) / 1024, 0, __('admin.photos.decimal_separator'), __('admin.photos.thousands_separator')) }} KB</td>
                            <td class="px-3 py-3 text-center font-semibold">{{ $photo->helpful_count }}</td>
                            <td class="whitespace-nowrap px-3 py-3">{{ \App\Support\LocalTime::parse($photo->created_at)->format(__('admin.photos.date_format')) }}</td>
                            <td class="px-3 py-3">
                                <details class="relative">
                                    <summary class="cursor-pointer list-none rounded border border-zinc-300 px-2 py-1 text-xs font-medium dark:border-zinc-700">{{ __('admin.photos.edit') }}</summary>
                                    <div class="mt-2 w-64 space-y-2 rounded-lg border border-zinc-200 bg-white p-3 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                                        @if ($canModerate && $photo->uuid && $photo->is_active && $photo->status === 'pending')
                                            <form method="POST" action="{{ route('admin.photos.approve', $photo->id) }}">@csrf<button class="w-full rounded bg-emerald-700 px-2 py-1.5 text-xs font-semibold text-white">{{ __('admin.photos.approve') }}</button></form>
                                        @endif
                                        @if ($canSetCover && $photo->uuid && $photo->is_active && $photo->status === 'approved')
                                            <form method="POST" action="{{ route('admin.photos.cover.set', $photo->id) }}">@csrf<button class="w-full rounded border border-amber-400 px-2 py-1.5 text-xs font-semibold text-amber-700 dark:text-amber-300">{{ __('photos.cover.select') }}</button></form>
                                            @if ($photo->is_thumbnail_eligible)
                                                <form method="POST" action="{{ route('admin.photos.thumbnail.exclude', $photo->id) }}">@csrf<input type="hidden" name="reason" value="{{ __('photos.cover.exclude_reason') }}"><button class="w-full rounded border border-zinc-300 px-2 py-1.5 text-xs dark:border-zinc-700">{{ __('photos.cover.exclude') }}</button></form>
                                            @else
                                                <form method="POST" action="{{ route('admin.photos.thumbnail.include', $photo->id) }}">@csrf<button class="w-full rounded border border-zinc-300 px-2 py-1.5 text-xs dark:border-zinc-700">{{ __('photos.cover.include') }}</button></form>
                                            @endif
                                        @endif
                                        @if ($canDelete && $photo->is_active)
                                            <form method="POST" action="{{ route('admin.photos.remove', $photo->id) }}" class="space-y-2" onsubmit="return confirm(@js(__('admin.photos.remove_confirmation')));">
                                                @csrf
                                                <select name="reason_code" required class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-950">
                                                    <option value="">{{ __('admin.photos.choose_moderation_reason') }}</option>
                                                    @foreach (__('admin.photos.moderation_reasons') as $code => $label)
                                                        <option value="{{ $code }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <input name="reason_details" maxlength="500" placeholder="{{ __('admin.photos.reason_details_optional') }}" class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-950">
                                                <button class="w-full rounded bg-red-700 px-2 py-1.5 text-xs font-semibold text-white">{{ __('admin.photos.remove') }}</button>
                                            </form>
                                        @endif
                                        @if (!$photo->uuid && $photo->is_active)<div class="text-xs text-amber-600 dark:text-amber-300">{{ __('admin.photos.legacy_unavailable') }}</div>@endif
                                        @if (!$photo->is_active)<div class="text-xs text-zinc-400">{{ __('admin.photos.no_actions_removed') }}</div>@endif
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="px-4 py-10 text-center text-zinc-500">{{ __('admin.photos.no_filter_results') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $photos->links() }}
    </div>
    @include('admin.photos._lightbox')
</x-layouts::app>
