<x-layouts::app :title="__('photos.library.page_title')">
    <div class="mx-auto max-w-6xl space-y-6">
        <div>
            <flux:heading size="xl">{{ __('photos.library.page_title') }}</flux:heading>
            <flux:text class="mt-1">{{ __('photos.library.intro') }}</flux:text>
        </div>
        @forelse ($conflicts as $conflict)
            <section class="rounded-xl border p-5 {{ $conflict->remaining_to_remove ? 'border-amber-400' : 'border-emerald-400' }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $conflict->place_name }}</h2>
                        <p class="text-sm text-neutral-500">{{ __('photos.library.conflict_summary', ['count' => $conflict->photos->count(), 'limit' => $conflict->photo_limit, 'remaining' => $conflict->remaining_to_remove]) }}</p>
                    </div>
                    <a href="{{ route('places.show', $conflict->place_slug) }}" class="text-sm underline">{{ __('photos.library.to_place') }}</a>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($conflict->photos as $photo)
                        <article class="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700">
                            <img src="{{ route('photos.owner', ['uuid' => $photo->uuid, 'variant' => 'preview']) }}" alt="{{ __('photos.library.own_photo') }}" class="aspect-[4/3] w-full object-cover">
                            <div class="space-y-2 p-3 text-sm">
                                <div>{{ $photo->width }}×{{ $photo->height }} · {{ \Illuminate\Support\Number::format($photo->file_size / 1024 / 1024, 1, 1, app()->getLocale()) }} MB</div>
                                <div class="text-neutral-500">{{ __('photos.library.status_line', ['status' => __("photos.status.{$photo->status}"), 'count' => $photo->helpful_count]) }}</div>
                                <form method="POST" action="{{ route('photos.destroy', $photo->id) }}" onsubmit='return confirm(@json(__('photos.library.delete_confirm')));'>
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-lg border border-red-300 px-3 py-2 text-red-700 dark:border-red-800 dark:text-red-300">{{ __('photos.library.delete_photo') }}</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-xl border border-neutral-200 p-8 text-center text-neutral-500 dark:border-neutral-700">{{ __('photos.library.no_selection_required') }}</div>
        @endforelse

        <section class="space-y-4">
            <div>
                <h2 class="text-lg font-semibold">{{ __('photos.library.all') }}</h2>
                <p class="text-sm text-neutral-500">{{ __('photos.library.all_intro') }}</p>
            </div>
            @forelse ($photos->getCollection()->groupBy(fn ($photo) => $photo->place_id ?: 0) as $placeId => $group)
                <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h3 class="font-semibold">{{ $group->first()->place_name ?: __('photos.library.without_place') }}</h3>
                        @if ($group->first()->place_slug)
                            <a class="text-sm underline" href="{{ route('places.show', $group->first()->place_slug) }}">{{ __('photos.library.to_place') }}</a>
                        @endif
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($group as $photo)
                            <article class="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700">
                                @if ($photo->is_active && $photo->uuid && in_array($photo->status, ['pending', 'approved'], true))
                                    <img src="{{ route('photos.owner', ['uuid' => $photo->uuid, 'variant' => 'preview']) }}" alt="{{ __('photos.library.own_photo') }}" class="aspect-[4/3] w-full object-cover">
                                @else
                                    <div class="grid aspect-[4/3] place-items-center bg-neutral-100 text-xs text-neutral-500 dark:bg-neutral-900">{{ $photo->status === 'processing' ? __('photos.processing') : __('photos.library.no_preview') }}</div>
                                @endif
                                <div class="space-y-2 p-3 text-xs">
                                    <div class="font-semibold">{{ __("photos.status.{$photo->status}") }}</div>
                                    <div>{{ $photo->width }}×{{ $photo->height }} · {{ __('photos.helpful_count', ['count' => $photo->helpful_count]) }}</div>
                                    @if ($photo->moderation_reason)
                                        <div class="text-red-600">{{ $photo->moderation_reason }}</div>
                                    @endif
                                    @if ($photo->is_active)
                                        <form method="POST" action="{{ route('photos.destroy', $photo->id) }}" onsubmit='return confirm(@json(__('photos.library.delete_confirm')));'>
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded border border-red-300 px-2 py-1 text-red-700 dark:border-red-800 dark:text-red-300">{{ __('photos.remove') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-neutral-200 p-8 text-center text-neutral-500 dark:border-neutral-700">{{ __('photos.library.none') }}</div>
            @endforelse
            {{ $photos->links() }}
        </section>
    </div>
</x-layouts::app>
