<x-layouts::app :title="__('admin.place_submissions.title')">
    <div class="mx-auto w-full max-w-6xl space-y-6 px-4 py-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">{{ __('admin.place_submissions.title') }}</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin.place_submissions.intro') }}</p>
            </div>
            <a href="{{ route('admin.index') }}" class="text-sm text-zinc-500 hover:text-zinc-950 dark:hover:text-white">{{ __('places.common.back_overview') }}</a>
        </div>

        <div class="space-y-3">
            @forelse ($places as $place)
                <a href="{{ route('admin.place-submissions.show', $place->id) }}" class="block rounded-xl border border-zinc-200 bg-white p-4 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold">{{ $place->name }}</div>
                            <div class="mt-1 text-sm text-zinc-500">{{ __("place_types.{$place->place_type_slug}.name") }}</div>
                            @if ($place->postal_code || $place->city)
                                <div class="mt-1 text-sm text-zinc-500">{{ trim(($place->postal_code ? $place->postal_code.' ' : '').($place->city ?? '')) }}</div>
                            @endif
                        </div>
                        <div class="text-right text-xs text-zinc-500">
                            <div>{{ $place->creator_name ?? '—' }}</div>
                            <div class="mt-1">{{ AppSupportLocalTime::parse($place->created_at)->diffForHumans() }}</div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700">{{ __('admin.place_submissions.empty') }}</div>
            @endforelse
        </div>

        {{ $places->links() }}
    </div>
</x-layouts::app>
