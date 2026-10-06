<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
@php($errorKey = $errorKey ?? (string) $status)
<head>
    @include('partials.head', ['title' => __('errors.'.$errorKey.'.technical')])
</head>
<body class="min-h-screen bg-zinc-100 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    <main class="mx-auto flex min-h-screen w-full max-w-5xl items-center px-5 py-12 pb-20 xl:px-7">
        <div class="grid w-full items-center gap-10 md:grid-cols-[minmax(0,1fr)_minmax(280px,420px)]">
            <section>
                <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    {{ __('errors.eyebrow', ['status' => $status]) }}
                </div>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">
                    {{ __('errors.'.$errorKey.'.title') }}
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-zinc-600 dark:text-zinc-300">
                    {{ __('errors.'.$errorKey.'.message') }}
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('home') }}" class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                        {{ __('errors.home') }}
                    </a>
                    @if ((int) $status === 419)
                        <button type="button" onclick="window.location.reload()" class="rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-white dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900">
                            {{ __('errors.reload') }}
                        </button>
                    @elseif (in_array((int) $status, [500, 503], true))
                        <a href="{{ route('support.report', ['type' => 'bug', 'context' => 'error-'.$status, 'route' => request()->route()?->getName(), 'source' => request()->fullUrl()]) }}" class="rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-white dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900">
                            {{ __('errors.report') }}
                        </a>
                    @endif
                </div>

                <div class="mt-8 border-t border-zinc-200 pt-4 text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    {{ __('errors.'.$errorKey.'.technical') }}
                    @if (filled($exceptionMessage ?? null))
                        <div class="mt-1">{{ $exceptionMessage }}</div>
                    @endif
                </div>
            </section>

            <figure class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="aspect-[4/3] bg-zinc-200 dark:bg-zinc-800">
                    <img
                        src="{{ asset('images/merlin.png') }}"
                        alt=""
                        class="h-full w-full object-cover"
                        onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');"
                    >
                    <div class="hidden h-full w-full place-items-center p-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('errors.photo_placeholder') }}
                    </div>
                </div>
            </figure>
        </div>
    </main>

    <footer class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/95 px-3 py-1.5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95">
        <div class="mx-auto flex w-full items-center justify-start gap-3 text-[11px] text-zinc-500 dark:text-zinc-400">
            <div class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1">
                <a href="{{ route('legal.imprint') }}" class="hover:text-zinc-950 dark:hover:text-white">{{ __('legal.labels.imprint') }}</a>
                <a href="{{ route('legal.privacy') }}" class="hover:text-zinc-950 dark:hover:text-white">{{ __('legal.labels.privacy') }}</a>
                <a href="{{ route('legal.terms') }}" class="hover:text-zinc-950 dark:hover:text-white">{{ __('legal.labels.terms') }}</a>
            </div>
        </div>
    </footer>
</body>
</html>
