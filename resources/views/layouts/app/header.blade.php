@props([
    'title' => null,
    'socialTitle' => null,
    'metaDescription' => null,
    'socialImage' => null,
    'canonicalUrl' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', [
            'title' => $title,
            'socialTitle' => $socialTitle,
            'metaDescription' => $metaDescription,
            'socialImage' => $socialImage,
            'canonicalUrl' => $canonicalUrl,
        ])
    </head>
    <body class="min-h-screen bg-zinc-100 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <header class="sticky top-0 z-50 border-b border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95">
            <div class="mx-auto flex h-14 w-full items-center gap-4 px-4 md:h-16 md:px-6">
                <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2 font-semibold">
                    <span class="grid size-9 place-items-center rounded-lg bg-zinc-900 text-sm font-bold text-white dark:bg-white dark:text-zinc-950">F</span>
                    <span>Favon</span>
                </a>

                <nav class="hidden items-center gap-1 text-sm md:flex">
                    <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('dashboard') || request()->routeIs('home') || request()->routeIs('places.show') ? 'bg-zinc-100 font-medium dark:bg-zinc-800' : 'text-zinc-500 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white' }}">
                        Plätze
                    </a>
                    @if (auth()->check() && app(\App\Services\PermissionService::class)->can(auth()->user(), 'places.submit'))
                        <a href="{{ route('places.create') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('places.create') ? 'bg-zinc-100 font-medium dark:bg-zinc-800' : 'text-zinc-500 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white' }}">
                            {{ __('ui.add_place') }}
                        </a>
                    @endif
                    <a href="{{ route('help.index') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('help.*') || request()->routeIs('support.*') ? 'bg-zinc-100 font-medium dark:bg-zinc-800' : 'text-zinc-500 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white' }}">
                        Hilfe
                    </a>
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    <form method="POST" action="{{ route('locale.update') }}">
                        @csrf
                        <select name="locale" onchange="this.form.submit()" class="h-9 rounded-lg border border-zinc-300 bg-white px-2 text-xs dark:border-zinc-700 dark:bg-zinc-900">
                            @foreach (\App\Support\LocaleConfiguration::enabled() as $localeCode => $localeConfig)
                                <option value="{{ $localeCode }}" @selected(app()->getLocale() === $localeCode)>
                                    {{ trim(($localeConfig['flag'] ?? '').' '.($localeConfig['native_name'] ?? strtoupper($localeCode))) }}
                                </option>
                            @endforeach
                        </select>
                    </form>

                    @auth
                        @php($unreadNotifications = app(\App\Services\UserNotificationService::class)->unreadCount((int) auth()->id()))
                        <a
                            href="{{ route('notifications.index') }}"
                            class="relative grid size-9 place-items-center rounded-lg text-zinc-500 hover:bg-zinc-100 hover:text-zinc-950 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                            title="{{ __('notifications.title') }}"
                            aria-label="{{ __('notifications.title') }}"
                        >
                            <x-tabler-icon name="bell" class="size-5" />
                            @if ($unreadNotifications > 0)
                                <span class="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-600 px-1.5 py-0.5 text-center text-[10px] font-semibold leading-none text-white">
                                    {{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}
                                </span>
                            @endif
                        </a>
                        <x-desktop-user-menu />
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-medium">Anmelden</a>
                        @endif
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="hidden rounded-lg bg-zinc-900 px-3 py-2 text-sm font-semibold text-white sm:inline-flex dark:bg-white dark:text-zinc-950">Registrieren</a>
                        @endif
                    @endauth
                </div>
            </div>
        </header>

        {{ $slot }}

        <x-ui-feedback-dialog />

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
        @stack('scripts')
    </body>
</html>
