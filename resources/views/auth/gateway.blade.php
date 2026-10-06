<x-layouts::auth title="Favon">
    <div class="flex flex-col gap-6 text-center">
        <div class="space-y-2">
            <h1 class="text-2xl font-semibold">Favon</h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">
                {{ __('auth.gateway_intro') }}
            </p>
        </div>

        @error('telegram')
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                {{ $message }}
            </div>
        @enderror

        <div class="flex min-h-12 items-center justify-center">
            @if (config('telegram.bot_username'))
                <script
                    async
                    src="https://telegram.org/js/telegram-widget.js?22"
                    data-telegram-login="{{ ltrim((string) config('telegram.bot_username'), '@') }}"
                    data-size="large"
                    data-userpic="false"
                    data-auth-url="{{ route('telegram.callback') }}"
                ></script>
            @else
                <div class="text-sm text-red-600 dark:text-red-400">{{ __('auth.telegram_not_configured') }}</div>
            @endif
        </div>

        <p class="text-xs leading-5 text-zinc-500 dark:text-zinc-400">
            {!! __('auth.telegram_legal', [
                'terms' => '<a class="underline underline-offset-2" href="'.e(route('legal.terms')).'">'.e(__('legal.labels.terms')).'</a>',
                'privacy' => '<a class="underline underline-offset-2" href="'.e(route('legal.privacy')).'">'.e(__('legal.labels.privacy')).'</a>',
            ]) !!}
        </p>

        <div class="border-t border-zinc-200 pt-5 dark:border-zinc-800">
            <a href="{{ route('login') }}" class="text-sm underline underline-offset-4">
                {{ __('auth.admin_login') }}
            </a>
        </div>
    </div>
</x-layouts::auth>
