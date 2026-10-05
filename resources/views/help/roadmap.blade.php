<x-layouts::app :title="__('support.roadmap.title')">
    @php
        $typeLabels = collect(['known_bug', 'suggested_feature', 'planned_feature'])->mapWithKeys(fn ($key) => [$key => __("support.roadmap_types.{$key}")]);
        $statusLabels = collect(['reported', 'confirmed', 'suggested', 'planned', 'in_progress', 'resolved', 'closed', 'not_planned'])->mapWithKeys(fn ($key) => [$key => __("support.statuses.{$key}")]);
    @endphp
    <div class="mx-auto w-full max-w-5xl space-y-6 px-5 py-8">
        <div>
            <a href="{{ route('help.index') }}" class="text-sm text-zinc-500 hover:text-zinc-950 dark:hover:text-white">{{ __('support.roadmap.back') }}</a>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">{{ __('support.roadmap.title') }}</h1>
            <p class="mt-2 text-zinc-500">{{ __('support.roadmap.intro') }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('roadmap') }}" class="rounded-lg px-3 py-2 text-sm {{ $type === '' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-950' : 'bg-zinc-100 dark:bg-zinc-800' }}">{{ __('support.roadmap.all') }}</a>
            @foreach ($typeLabels as $key => $label)
                <a href="{{ route('roadmap', ['type' => $key]) }}" class="rounded-lg px-3 py-2 text-sm {{ $type === $key ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-950' : 'bg-zinc-100 dark:bg-zinc-800' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="space-y-3">
            @forelse ($entries as $entry)
                <article class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium dark:bg-zinc-800">{{ $typeLabels[$entry->type] ?? $entry->type }}</span>
                        <span class="rounded-md px-2 py-1 text-xs font-medium {{ $entry->status === 'resolved' ? 'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-300' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}">{{ $statusLabels[$entry->status] ?? $entry->status }}</span>
                    </div>
                    <h2 class="mt-3 text-lg font-semibold">{{ $entry->title }}</h2>
                    @if ($entry->description)
                        <div class="mt-2 whitespace-pre-line text-sm leading-6 text-zinc-600 dark:text-zinc-400">{{ $entry->description }}</div>
                    @endif
                    <div class="mt-3 text-xs text-zinc-400">{{ __('support.roadmap.updated', ['date' => \App\Support\LocalTime::parse($entry->updated_at)->translatedFormat(__('support.date_format'))]) }}</div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500 dark:border-zinc-700">{{ __('support.roadmap.empty') }}</div>
            @endforelse
        </div>
    </div>
</x-layouts::app>
