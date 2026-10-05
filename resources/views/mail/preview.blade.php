<x-layouts::app :title="__('mail.preview.title')">
    <div class="mx-auto w-full max-w-4xl space-y-5 px-5 py-8">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">{{ __('mail.preview.title') }}</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ __('mail.preview.local_only') }}</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800"><strong>{{ __('mail.preview.recipient') }}:</strong> {{ $recipient }}</div>
            <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800"><strong>{{ __('mail.preview.subject') }}:</strong> {{ $subject }}</div>
            <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800"><strong>{{ __('mail.preview.language') }}:</strong> {{ strtoupper($locale) }}</div>
            <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800"><strong>{{ __('mail.preview.reason') }}:</strong> {{ $reason === 'email_change' ? __('mail.preview.reason_email_change') : __('mail.preview.reason_registration') }}</div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700" href="{{ request()->fullUrlWithQuery(['locale' => 'de']) }}">{{ __('mail.preview.language_de') }}</a>
            <a class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700" href="{{ request()->fullUrlWithQuery(['locale' => 'en']) }}">{{ __('mail.preview.language_en') }}</a>
            <a class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700" href="{{ request()->fullUrlWithQuery(['reason' => 'registration']) }}">{{ __('mail.preview.reason_registration') }}</a>
            <a class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700" href="{{ request()->fullUrlWithQuery(['reason' => 'email_change']) }}">{{ __('mail.preview.reason_email_change') }}</a>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800">
            <iframe title="{{ __('mail.preview.content_title') }}" class="h-[650px] w-full bg-white" srcdoc="{{ $html }}"></iframe>
        </div>
    </div>
</x-layouts::app>
