@props([
    'title' => null,
    'socialTitle' => null,
    'metaDescription' => null,
    'socialImage' => null,
    'canonicalUrl' => null,
])

<x-layouts::app.header
    :title="$title"
    :social-title="$socialTitle"
    :meta-description="$metaDescription"
    :social-image="$socialImage"
    :canonical-url="$canonicalUrl"
>
    <main class="min-h-[calc(100vh-4rem)] bg-zinc-100 pb-10 dark:bg-zinc-950">
        {{ $slot }}
    </main>

    <footer class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/95 px-3 py-1.5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95">
        <div class="mx-auto flex w-full items-center justify-between gap-3 text-[11px] text-zinc-500 dark:text-zinc-400">
            <div class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1">
                <a href="{{ route('legal.imprint') }}" class="hover:text-zinc-950 dark:hover:text-white">{{ __('legal.labels.imprint') }}</a>
                <a href="{{ route('legal.privacy') }}" class="hover:text-zinc-950 dark:hover:text-white">{{ __('legal.labels.privacy') }}</a>
                <a href="{{ route('legal.terms') }}" class="hover:text-zinc-950 dark:hover:text-white">{{ __('legal.labels.terms') }}</a>
            </div>
        </div>
    </footer>

    @if ($verificationModal = session('verification_modal'))
        @include('partials.email-verification-modal', ['verificationModal' => $verificationModal])
    @endif
</x-layouts::app.header>
