<x-layouts::auth.simple title="Favon">
    <div class="flex flex-col gap-6 text-center">
        <h1 class="text-2xl font-semibold">Favon</h1>

        @error('telegram')
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                {{ $message }}
            </div>
        @enderror

        <div class="flex min-h-12 items-center justify-center">
            @if (config('telegram.client_id') && config('telegram.client_secret'))
                <a
                    href="{{ route('telegram.redirect') }}"
                    class="inline-flex min-h-12 items-center justify-center rounded-full bg-[#54a9eb] px-7 text-base font-medium text-white transition hover:brightness-95"
                >
                    {{ __('auth.telegram_login') }}
                </a>
            @else
                <div class="text-sm text-red-600 dark:text-red-400">{{ __('auth.telegram_not_configured') }}</div>
            @endif
        </div>

        <div class="border-t border-zinc-200 pt-5 dark:border-zinc-800">
            <a href="{{ route('login') }}" class="text-sm underline underline-offset-4">
                {{ __('auth.admin_login') }}
            </a>
        </div>
    </div>
</x-layouts::auth.simple>
