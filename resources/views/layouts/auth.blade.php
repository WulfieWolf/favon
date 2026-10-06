<x-layouts::auth.simple :title="$title ?? null">
    <div class="pb-10">
        {{ $slot }}
    </div>

    <footer class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/95 px-3 py-1.5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95">
        <div class="mx-auto flex w-full items-center justify-start gap-3 text-[11px] text-zinc-500 dark:text-zinc-400">
            <div class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1">
                <a href="{{ route('legal.imprint') }}" class="hover:text-zinc-950 dark:hover:text-white">{{ __('legal.labels.imprint') }}</a>
                <a href="{{ route('legal.privacy') }}" class="hover:text-zinc-950 dark:hover:text-white">{{ __('legal.labels.privacy') }}</a>
                <a href="{{ route('legal.terms') }}" class="hover:text-zinc-950 dark:hover:text-white">{{ __('legal.labels.terms') }}</a>
            </div>
        </div>
    </footer>
</x-layouts::auth.simple>
