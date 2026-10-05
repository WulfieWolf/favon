<x-layouts::app :title="$page['title']">
    <div class="mx-auto w-full max-w-4xl px-5 py-8 md:py-10">
        <div class="mb-8">
            <h1 class="text-3xl font-semibold tracking-tight">{{ $page['title'] }}</h1>
            <p class="mt-2 text-zinc-500">{{ $page['subtitle'] }}</p>
            <p class="mt-2 text-xs text-zinc-400">{{ __('legal.labels.last_updated', ['date' => $page['version']]) }}</p>
        </div>

        <div class="space-y-7">
            @foreach ($page['sections'] as $section)
                <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900/50">
                    <h2 class="text-lg font-semibold">{{ $section['title'] }}</h2>

                    <div class="mt-3 space-y-3 text-sm leading-6 text-zinc-700 dark:text-zinc-300">
                        @foreach ($section['paragraphs'] ?? [] as $paragraph)
                            <p class="whitespace-pre-line">{{ $paragraph }}</p>
                        @endforeach
                    </div>

                    @if (! empty($section['links']))
                        <div class="mt-4 flex flex-wrap gap-3">
                            @foreach ($section['links'] as $link)
                                <a
                                    href="{{ route($link['route'], $link['parameter'] ?? null) }}"
                                    class="text-sm font-medium underline underline-offset-4"
                                >
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if (! empty($section['external_links']))
                        <div class="mt-4 flex flex-wrap gap-3">
                            @foreach ($section['external_links'] as $link)
                                <a
                                    href="{{ $link['url'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-sm font-medium underline underline-offset-4"
                                >
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
</x-layouts::app>
