<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>{{ __('admin.system.lockdown_title') }} – Camperwolf</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    <main class="mx-auto flex min-h-screen max-w-xl items-center px-6 py-12">
        <div class="w-full rounded-2xl border border-zinc-200 bg-white p-8 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-2xl font-semibold">Camperwolf</div>
            <h1 class="mt-6 text-xl font-semibold">{{ __('admin.system.lockdown_title') }}</h1>
            <div class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                @php
                    $accessMessage = $message ?: __('admin.system.lockdown_default');
                    $escapedMessage = e($accessMessage);
                    $linkedMessage = preg_replace(
                        '~(https?://[^\s<]+)~i',
                        '<a href="$1" target="_blank" rel="noopener noreferrer" class="underline hover:no-underline">$1</a>',
                        $escapedMessage
                    );
                @endphp
                {!! $linkedMessage !!}
            </div>

            @auth
                <div class="mt-5 rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <div class="font-medium">{{ __('admin.system.lockdown_signed_in') }}</div>
                    <div class="mt-1">{{ __('admin.system.lockdown_signed_in_help') }}</div>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="mt-6">
                    @csrf
                    <button type="submit" class="inline-flex rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">
                        {{ __('admin.system.logout') }}
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="mt-6 inline-flex rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">
                    {{ __('admin.system.admin_login') }}
                </a>
            @endauth
        </div>
    </main>
</body>
</html>
