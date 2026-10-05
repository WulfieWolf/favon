<x-layouts::app :title="$article->title">
    <div class="mx-auto w-full max-w-3xl px-5 py-8">
        <a href="{{ route('help.index') }}" class="text-sm text-zinc-500 hover:text-zinc-950 dark:hover:text-white">{{ __('support.roadmap.back') }}</a>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight">{{ $article->title }}</h1>
        @if ($article->summary)
            <p class="mt-3 text-lg text-zinc-500">{{ $article->summary }}</p>
        @endif
        <div class="help-markdown mt-8 text-zinc-700 dark:text-zinc-300">
            {!! Str::markdown($article->body, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]) !!}
        </div>
        <div class="mt-10 border-t border-zinc-200 pt-5 dark:border-zinc-800">
            <a href="{{ route('support.report', ['type' => 'bug', 'context' => $article->context_key]) }}" class="text-sm font-medium hover:underline">{{ __('support.help.report_article') }}</a>
        </div>
    </div>
</x-layouts::app>
