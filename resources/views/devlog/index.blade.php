<x-layouts::app :title="__('devlog.title')">
    <div class="mx-auto w-full max-w-5xl px-5 py-8 xl:px-7">
        <div class="mb-8">
            <h1 class="text-2xl font-semibold tracking-tight">{{ __('devlog.title') }}</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('devlog.intro') }}</p>
        </div>

        @forelse ($releases as $release)
            <article class="mb-6 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            {{ $release->version_label }}
                        </div>
                        <h2 class="mt-1 text-lg font-semibold">{{ $release->title }}</h2>
                        @if ($release->summary)
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $release->summary }}</p>
                        @endif
                    </div>
                    <time class="text-xs text-zinc-500 dark:text-zinc-400" datetime="{{ \App\Support\LocalTime::parse($release->released_at)->toDateString() }}">
                        {{ \App\Support\LocalTime::parse($release->released_at)->format('d.m.Y') }}
                    </time>
                </div>

                <div class="mt-5 space-y-6">
                    @foreach ($release->items->groupBy(fn ($item) => $item->section ?: 'general') as $section => $items)
                        <section>
                            <h3 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ __("devlog.sections.$section") }}
                            </h3>
                            <div class="space-y-3">
                                @foreach ($items->groupBy('type') as $type => $typedItems)
                                    <div class="grid gap-2 sm:grid-cols-[110px_1fr]">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                            {{ __("devlog.$type") }}
                                        </div>
                                        <ul class="space-y-1.5 text-sm text-zinc-700 dark:text-zinc-200">
                                            @foreach ($typedItems as $item)
                                                <li class="flex gap-2">
                                                    <span class="mt-[0.55rem] size-1.5 shrink-0 rounded-full bg-zinc-400"></span>
                                                    <span>{{ $item->text }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-zinc-200 bg-white p-6 text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                {{ __('devlog.empty') }}
            </div>
        @endforelse
    </div>
</x-layouts::app>
