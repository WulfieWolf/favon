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
    <main class="min-h-[calc(100vh-4rem)] bg-zinc-100 dark:bg-zinc-950">
        {{ $slot }}
    </main>

    @if ($verificationModal = session('verification_modal'))
        @include('partials.email-verification-modal', ['verificationModal' => $verificationModal])
    @endif
</x-layouts::app.header>
