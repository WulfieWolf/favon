<x-layouts::auth.simple title="Favon">
    <div class="flex flex-col gap-6 text-center">
        <h1 class="text-2xl font-semibold">Favon</h1>

        @error('telegram')
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                {{ $message }}
            </div>
        @enderror

        <div class="flex min-h-12 items-center justify-center">
            @if (config('telegram.bot_username'))
                <script>
                    window.onTelegramAuth = function (telegramUser) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = @json(route('telegram.callback'));

                        const fields = {
                            _token: @json(csrf_token()),
                            ...telegramUser,
                        };

                        Object.entries(fields).forEach(([name, value]) => {
                            if (value === undefined || value === null) return;
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = name;
                            input.value = String(value);
                            form.appendChild(input);
                        });

                        document.body.appendChild(form);
                        form.submit();
                    };
                </script>
                <script
                    async
                    src="https://telegram.org/js/telegram-widget.js?22"
                    data-telegram-login="{{ ltrim((string) config('telegram.bot_username'), '@') }}"
                    data-size="large"
                    data-userpic="false"
                    data-onauth="onTelegramAuth(user)"
                ></script>
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
