<x-layouts::app :title="__('support.help.title')">
    <div class="mx-auto w-full max-w-5xl space-y-8 px-5 py-8">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">{{ __('support.help.title') }}</h1>
            <p class="mt-2 text-zinc-500">{{ __('support.help.intro') }}</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('support.report') }}" class="rounded-xl border border-zinc-200 p-5 transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900">
                <div class="font-semibold">{{ __('support.help.create_report') }}</div>
                <div class="mt-1 text-sm text-zinc-500">{{ __('support.help.create_report_intro') }}</div>
            </a>
            <a href="{{ route('roadmap') }}" class="rounded-xl border border-zinc-200 p-5 transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900">
                <div class="font-semibold">{{ __('support.roadmap.title') }}</div>
                <div class="mt-1 text-sm text-zinc-500">{{ __('support.help.roadmap_intro') }}</div>
            </a>
            <a href="{{ route('support.privacy-legal') }}" class="rounded-xl border border-zinc-200 p-5 transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900">
                <div class="font-semibold">{{ __('support.help.privacy_legal') }}</div>
                <div class="mt-1 text-sm text-zinc-500">{{ __('support.help.privacy_legal_intro') }}</div>
            </a>
            @auth
                <a href="{{ route('support.my.index') }}" class="rounded-xl border border-zinc-200 p-5 transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900">
                    <div class="font-semibold">{{ __('support.help.my_reports') }}</div>
                    <div class="mt-1 text-sm text-zinc-500">{{ __('support.help.my_reports_intro') }}</div>
                </a>
            @else
                <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                    <div class="font-semibold">{{ __('support.help.guest_report') }}</div>
                    <div class="mt-1 text-sm text-zinc-500">{{ __('support.help.guest_report_intro') }}</div>
                </div>
            @endauth
        </div>

        <form method="GET" action="{{ route('help.index') }}" class="max-w-xl">
            <input name="q" value="{{ $q }}" type="search" placeholder="{{ __('support.help.search') }}" class="h-11 w-full rounded-lg border border-zinc-300 bg-white px-4 text-sm dark:border-zinc-700 dark:bg-zinc-900">
        </form>

        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($articles as $article)
                <a href="{{ route('help.show', $article->slug) }}" class="rounded-xl border border-zinc-200 p-5 transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900">
                    <div class="font-semibold">{{ $article->title }}</div>
                    @if ($article->summary)
                        <div class="mt-2 text-sm leading-6 text-zinc-500">{{ $article->summary }}</div>
                    @endif
                </a>
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500 dark:border-zinc-700">
                    {{ __('support.help.empty') }}
                </div>
            @endforelse
        </div>
    </div>
</x-layouts::app>
