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
        <header class="sticky top-0 border-b border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95" style="z-index:2147482000">
            <div class="mx-auto flex h-14 w-full items-center justify-between px-3 md:hidden">
                @if (request()->routeIs('dashboard') || request()->routeIs('home'))
                    <button
                        type="button"
                        data-mobile-filter-toggle
                        class="grid size-10 place-items-center rounded-lg text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white"
                        aria-label="{{ __('ui.browse.open_filters') }}"
                        aria-expanded="false"
                    >
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M4 5h16l-6.5 7.5V19l-3 1.5v-8z"/>
                        </svg>
                    </button>
                @else
                    <a href="{{ route('dashboard') }}" class="grid size-10 place-items-center rounded-lg bg-zinc-900 text-xs font-bold text-white dark:bg-white dark:text-zinc-950">CW</a>
                @endif

                <details class="relative">
                    <summary class="flex cursor-pointer list-none items-center gap-1 rounded-lg px-3 py-2 text-sm font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        <span>{{ request()->routeIs('dashboard') || request()->routeIs('home') ? __('ui.places') : ($title ?? 'Camperwolf') }}</span>
                        <svg class="size-4 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </summary>
                    <div class="absolute left-1/2 top-11 z-50 w-48 -translate-x-1/2 rounded-xl border border-zinc-200 bg-white p-1.5 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                        <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('ui.places') }}</a>
                        <a href="{{ route('help.index') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('global.help_support') }}</a>
                        <a href="{{ route('help.show', 'faq') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">FAQ</a>
                        <a href="{{ route('help.show', 'ueber-camperwolf') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('global.about_camperwolf') }}</a>
                    </div>
                </details>

                @auth
                    @php
                        $mobileUser = auth()->user();
                        $mobilePermissionService = app(\App\Services\PermissionService::class);
                        $mobileRolePreview = $mobilePermissionService->activeRolePreview($mobileUser);
                        $mobilePreviewRoles = $mobilePermissionService->isOwner($mobileUser)
                            ? \Illuminate\Support\Facades\DB::table('roles')
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->orderBy('name')
                                ->get(['slug', 'name'])
                            : collect();
                        $mobileNotificationService = app(\App\Services\UserNotificationService::class);
                        $mobileUnreadNotificationCount = $mobileNotificationService->unreadCount((int) $mobileUser->id);
                    @endphp
                    <div class="flex items-center gap-1">
                        <a
                            href="{{ route('notifications.index') }}"
                            class="relative grid size-10 place-items-center rounded-full text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white"
                            aria-label="{{ __('notifications.dropdown_title') }}"
                            title="{{ __('notifications.dropdown_title') }}"
                        >
                            <x-tabler-icon name="bell" class="size-5" />
                            @if ($mobileUnreadNotificationCount > 0)
                                <span
                                    class="absolute right-0 top-0 inline-flex min-w-4 items-center justify-center rounded-full px-1 text-[9px] font-extrabold leading-4"
                                    style="background-color:#dc2626 !important;color:#ffffff !important;"
                                >
                                    {{ $mobileUnreadNotificationCount > 99 ? '99+' : $mobileUnreadNotificationCount }}
                                </span>
                            @endif
                        </a>

                        <flux:dropdown position="bottom" align="end">
                        <button type="button" class="relative inline-flex size-10 items-center justify-center rounded-full">
                            <span class="flex size-9 items-center justify-center rounded-full bg-zinc-200 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $mobileUser->initials() }}</span>
                        </button>
                        <flux:menu>

                            @if ($mobilePermissionService->can($mobileUser, 'admin.access'))
                                <flux:menu.item :href="route('admin.index')" icon="wrench-screwdriver">{{ __('ui.admin') }}</flux:menu.item>
                            @endif

                            @if ($mobilePermissionService->isOwner($mobileUser))
                                <div class="px-2 py-1.5">
                                    <form method="POST" action="{{ route('role-preview.update') }}">
                                        @csrf
                                        <label class="mb-1 block text-xs font-medium {{ $mobileRolePreview ? 'text-amber-500' : 'text-zinc-500 dark:text-zinc-400' }}">{{ __('ui.role') }}</label>
                                        <select
                                            name="role"
                                            onchange="this.form.submit()"
                                            class="h-9 w-full rounded-lg border px-2 text-xs outline-none {{ $mobileRolePreview ? 'border-amber-500 bg-amber-950/40 text-amber-100' : 'border-zinc-300 bg-white text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100' }}"
                                            title="{{ __('global.role_preview_title') }}"
                                        >
                                            <option value="default" @selected($mobileRolePreview === null)>{{ __('global.role_preview_default') }}</option>
                                            @foreach ($mobilePreviewRoles as $previewRole)
                                                <option value="{{ $previewRole->slug }}" @selected($mobileRolePreview === $previewRole->slug)>
                                                    {{ \Illuminate\Support\Facades\Lang::has('admin.users.role_names.'.$previewRole->slug) ? __('admin.users.role_names.'.$previewRole->slug) : $previewRole->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                            @endif

                            <div class="px-2 py-1.5">
                                <form method="POST" action="{{ route('locale.update') }}">
                                    @csrf
                                    <label class="mb-1 block text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('ui.language') }}</label>
                                    <select
                                        name="locale"
                                        onchange="this.form.submit()"
                                        class="h-9 w-full rounded-lg border border-zinc-300 bg-white px-2 text-xs text-zinc-900 outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                                    >
                                        @foreach (\App\Support\LocaleConfiguration::enabled() as $localeCode => $localeConfig)
                                            <option value="{{ $localeCode }}" @selected(app()->getLocale() === $localeCode)>
                                                {{ trim(($localeConfig['flag'] ?? '').' '.($localeConfig['native_name'] ?? strtoupper($localeCode))) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>

                            <flux:menu.item :href="route('help.index')" icon="question-mark-circle">{{ __('global.help_support') }}</flux:menu.item>
                            <flux:menu.separator />
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">{{ __('Log out') }}</flux:menu.item>
                            </form>
                        </flux:menu>
                        </flux:dropdown>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('global.login') }}</a>
                @endauth
            </div>

            <div class="mx-auto hidden h-16 w-full items-center gap-5 px-5 md:flex xl:px-7">
                <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2 font-semibold tracking-tight">
                    <span class="grid size-9 place-items-center rounded-lg bg-zinc-900 text-sm font-bold text-white dark:bg-white dark:text-zinc-950">CW</span>
                    <span class="text-base">Camperwolf</span>
                </a>

                <nav class="hidden h-full items-center gap-1 text-sm md:flex">
                    <a href="{{ route('dashboard') }}" class="flex h-full items-center border-b-2 px-3 font-medium {{ request()->routeIs('dashboard') || request()->routeIs('home') ? 'border-zinc-900 text-zinc-950 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white' }}">
                        {{ __('ui.places') }}
                    </a>
                    <a href="{{ route('help.index') }}" class="flex h-full items-center border-b-2 px-2 text-xs font-medium {{ request()->routeIs('help.*') || request()->routeIs('roadmap') || request()->routeIs('support.*') ? 'border-zinc-900 text-zinc-950 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white' }}">
                        {{ __('global.help_support') }}
                    </a>
                    <a href="{{ route('help.show', 'faq') }}" class="flex h-full items-center border-b-2 px-2 text-xs font-medium {{ request()->routeIs('help.show') && request()->route('slug') === 'faq' ? 'border-zinc-900 text-zinc-950 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white' }}">FAQ</a>
                    <a href="{{ route('help.show', 'ueber-camperwolf') }}" class="flex h-full items-center border-b-2 px-2 text-xs font-medium {{ request()->routeIs('help.show') && request()->route('slug') === 'ueber-camperwolf' ? 'border-zinc-900 text-zinc-950 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white' }}">{{ __('global.about_camperwolf') }}</a>
                </nav>

                <form method="GET" action="{{ route('dashboard') }}" class="mx-auto hidden w-full max-w-xl xl:block">
                    @auth
                        @if (request()->boolean('favorites'))
                            <input type="hidden" name="favorites" value="1">
                        @endif
                    @endauth
                    <label class="relative block">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-zinc-400">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </span>
                        <input
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="{{ __('ui.search_placeholder') }}"
                            class="h-10 w-full rounded-lg border border-zinc-300 bg-zinc-50 pl-10 pr-3 text-sm outline-none transition focus:border-zinc-500 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:focus:border-zinc-500 dark:focus:bg-zinc-900"
                        >
                    </label>
                </form>

                <div class="ml-auto flex items-center gap-2">


                    @auth
                        @php
                            $permissionService = app(\App\Services\PermissionService::class);
                        @endphp

                        @if ($permissionService->can(auth()->user(), 'places.suggest'))
                            <a href="{{ route('places.suggest.create') }}" class="rounded-lg bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                                {{ $permissionService->can(auth()->user(), 'places.create_direct') ? __('ui.add_place') : __('ui.suggest_place') }}
                            </a>
                        @endif

                        @php
                            $notificationService = app(\App\Services\UserNotificationService::class);
                            $unreadNotifications = $notificationService->unreadForUser((int) auth()->id(), 8);
                            $unreadNotificationCount = $notificationService->unreadCount((int) auth()->id());
                        @endphp

                        <div class="relative" data-notification-menu>
                            <button
                                type="button"
                                data-notification-toggle
                                class="flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 bg-zinc-100/80 px-2 text-zinc-700 transition hover:bg-zinc-200 hover:text-zinc-950 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 dark:hover:text-white"
                                aria-label="{{ __('notifications.dropdown_title') }}"
                                aria-expanded="false"
                            >
                                <x-tabler-icon name="bell" class="size-5 shrink-0" />
                                @if ($unreadNotificationCount > 0)
                                    <span
                                        class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] font-extrabold leading-5"
                                        style="background-color:#dc2626 !important;color:#ffffff !important;"
                                    >
                                        {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
                                    </span>
                                @endif
                            </button>

                            <div
                                data-notification-dropdown
                                class="absolute right-0 top-11 z-[60] hidden w-[360px] max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
                            >
                                <div class="flex items-baseline gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                                    <div class="font-semibold">{{ __('notifications.dropdown_title') }}</div>
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">(<a href="{{ route('help.show', 'benachrichtigungen') }}" class="hover:text-zinc-700 hover:underline dark:hover:text-zinc-300">{{ __('notifications.help_link') }}</a>)</span>
                                </div>

                                <div class="max-h-[420px] overflow-y-auto">
                                    @forelse ($unreadNotifications as $notification)
                                        <a href="{{ route('notifications.show', $notification->type === 'beta_welcome' ? ['notification' => $notification->id, 'beta' => 1] : ['notification' => $notification->id]) }}" class="block border-b border-zinc-100 px-4 py-3 transition hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/70">
                                            <div class="flex gap-3">
                                                <div class="mt-0.5 size-2 shrink-0 rounded-full bg-red-600"></div>
                                                <div class="min-w-0">
                                                    <div class="truncate text-sm font-semibold">{{ $notification->title }}</div>
                                                    <div class="mt-1 line-clamp-2 text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ $notification->message }}</div>
                                                    <div class="mt-1 text-[11px] text-zinc-400">{{ \App\Support\LocalTime::parse($notification->available_at)->diffForHumans() }}</div>
                                                </div>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="px-4 py-8 text-center text-sm text-zinc-500">
                                            {{ __('notifications.dropdown_empty') }}
                                        </div>
                                    @endforelse
                                </div>

                                <div class="grid grid-cols-2 border-t border-zinc-200 dark:border-zinc-800">
                                    <form method="POST" action="{{ route('notifications.read-all') }}" class="border-r border-zinc-200 dark:border-zinc-800">
                                        @csrf
                                        <button type="submit" class="block w-full px-3 py-3 text-center text-xs font-medium text-zinc-500 hover:bg-zinc-50 hover:text-zinc-950 disabled:cursor-default disabled:opacity-40 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white" @disabled($unreadNotificationCount === 0)>
                                            {{ __('notifications.mark_all_read') }}
                                        </button>
                                    </form>
                                    <a href="{{ route('notifications.index') }}" class="block px-3 py-3 text-center text-xs font-medium text-zinc-600 hover:bg-zinc-50 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white">
                                        {{ __('notifications.show_all_short') }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <x-desktop-user-menu />
                    @else
                        <button
                            type="button"
                            data-auth-feature
                            data-auth-title="{{ __('global.auth_prompt_suggest_title') }}"
                            data-auth-message="{{ __('global.auth_prompt_suggest_message') }}"
                            class="inline-flex rounded-lg bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200"
                        >
                            {{ __('ui.suggest_place') }}
                        </button>

                        <form method="POST" action="{{ route('locale.update') }}">
                            @csrf
                            <label class="sr-only" for="guest-header-locale">{{ __('ui.language') }}</label>
                            <select
                                id="guest-header-locale"
                                name="locale"
                                onchange="this.form.submit()"
                                class="h-9 rounded-lg border border-zinc-300 bg-white px-2 text-xs font-medium text-zinc-700 outline-none hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                title="{{ __('ui.language') }}"
                            >
                                @foreach (\App\Support\LocaleConfiguration::enabled() as $localeCode => $localeConfig)
                                    <option value="{{ $localeCode }}" @selected(app()->getLocale() === $localeCode)>
                                        {{ trim(($localeConfig['flag'] ?? '').' '.($localeConfig['native_name'] ?? strtoupper($localeCode))) }}
                                    </option>
                                @endforeach
                            </select>
                        </form>

                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white">{{ __('global.login') }}</a>
                        @endif
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="hidden rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 sm:inline-flex dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">{{ __('global.register') }}</a>
                        @endif
                    @endauth
                </div>
            </div>
        </header>

        {{ $slot }}

        @php
            $supportContext = app(\App\Services\SupportContextService::class)->fromRequest(request());
        @endphp
        <div class="fixed bottom-12 right-4 z-40 flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-white/95 p-1.5 shadow-md backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
            <a
                href="{{ route('help.context', ['context' => $supportContext['context_key']]) }}"
                class="grid size-9 place-items-center rounded-lg text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white"
                title="{{ __('global.context_help') }}"
                aria-label="{{ __('global.context_help') }}"
            >
                <x-tabler-icon name="help-circle" class="size-5" />
            </a>
            <a
                href="{{ route('support.report', [
                    'type' => 'bug',
                    'context' => $supportContext['context_key'],
                    'module' => $supportContext['module'],
                    'route' => $supportContext['route_name'],
                    'source' => $supportContext['source_url'],
                ]) }}"
                class="grid size-9 place-items-center rounded-lg bg-zinc-900 text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200"
                title="{{ __('global.report_bug') }}"
                aria-label="{{ __('global.report_bug') }}"
            >
                <x-tabler-icon name="bug" class="size-5" />
            </a>
        </div>

        <button
            type="button"
            data-beta-notice-open
            class="fixed bottom-12 left-4 z-40 rounded-lg border border-orange-400 bg-orange-50/95 px-3 py-2 text-xs font-semibold text-orange-800 shadow-md backdrop-blur transition hover:bg-orange-100 dark:border-orange-500 dark:bg-orange-950/90 dark:text-orange-200 dark:hover:bg-orange-900"
            aria-haspopup="dialog"
            aria-controls="beta-notice-dialog"
        >
            {{ __('global.beta_badge') }}
        </button>

        <div id="beta-notice-dialog" data-beta-notice class="fixed inset-0 hidden items-center justify-center bg-black/70 p-4" style="z-index:2147483002" role="dialog" aria-modal="true" aria-labelledby="beta-notice-title">
            <div class="w-full max-w-lg rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-orange-600 dark:text-orange-400">{{ __('global.beta_badge') }}</div>
                        <h2 id="beta-notice-title" class="mt-1 text-xl font-semibold">{{ __('global.beta_title') }}</h2>
                    </div>
                    <button type="button" data-beta-notice-close class="grid size-9 shrink-0 place-items-center rounded-lg text-zinc-500 hover:bg-zinc-100 hover:text-zinc-950 dark:hover:bg-zinc-800 dark:hover:text-white" aria-label="{{ __('global.beta_close') }}">×</button>
                </div>

                <div class="mt-5 space-y-4 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                    <p>{{ __('global.beta_intro') }}</p>
                    <p>{{ __('global.beta_reset') }}</p>
                    <p>{{ __('global.beta_accounts') }}</p>
                    <p>{{ __('global.beta_feedback') }}</p>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <a href="https://t.me/CamperWolf" target="_blank" rel="noopener noreferrer" class="rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-orange-700">
                        {{ __('global.beta_telegram') }}
                    </a>
                    <button type="button" data-beta-notice-close class="rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                        {{ __('global.beta_close') }}
                    </button>
                </div>
            </div>
        </div>

        @guest
            <div data-auth-prompt class="fixed inset-0 hidden items-center justify-center bg-black/70 p-4" style="z-index:2147483001" role="dialog" aria-modal="true" aria-labelledby="auth-prompt-title">
                <div class="w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 id="auth-prompt-title" data-auth-prompt-title class="text-xl font-semibold">{{ __('global.auth_prompt_title') }}</h2>
                            <p data-auth-prompt-message class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                                {{ __('global.auth_prompt_default') }}
                            </p>
                        </div>
                        <button type="button" data-auth-prompt-close class="grid size-9 shrink-0 place-items-center rounded-lg text-zinc-500 hover:bg-zinc-100 hover:text-zinc-950 dark:hover:bg-zinc-800 dark:hover:text-white" aria-label="{{ __('global.auth_prompt_close') }}">×</button>
                    </div>

                    <div class="mt-5 rounded-lg bg-zinc-50 p-3 text-sm text-zinc-500 dark:bg-zinc-800/70">
                        {{ __('global.auth_prompt_reason') }}
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2">
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">{{ __('global.register_free') }}</a>
                        @endif
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">{{ __('global.login') }}</a>
                        @endif
                        <button type="button" data-auth-prompt-close class="px-3 py-2.5 text-sm text-zinc-500 hover:text-zinc-950 dark:hover:text-white">{{ __('global.maybe_later') }}</button>
                    </div>
                </div>
            </div>

            @if (request()->routeIs('home') || request()->routeIs('dashboard'))
                <div data-welcome-splash class="fixed inset-0 hidden items-center justify-center bg-black/70 p-4" style="z-index:2147483000" role="dialog" aria-modal="true" aria-labelledby="welcome-splash-title">
                    <div class="w-full max-w-xl rounded-2xl border border-zinc-700 bg-zinc-950 p-8 text-zinc-100 shadow-2xl sm:p-9">
                        <div class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('global.welcome_kicker') }}</div>
                        <h2 id="welcome-splash-title" class="mt-1 text-2xl font-semibold tracking-tight text-white">{{ __('global.welcome_title') }}</h2>

                        <p class="mt-5 leading-7 text-zinc-300">{{ __('global.welcome_intro') }}</p>
                        <p class="mt-3 leading-7 text-zinc-300">{{ __('global.welcome_use') }}</p>

                        <p class="mt-5 text-sm text-zinc-400">
                            {{ __('global.welcome_about_prompt') }}
                            <a href="{{ route('help.show', 'ueber-camperwolf') }}" class="font-medium text-zinc-200 hover:text-white hover:underline">{{ __('global.welcome_about_link') }}</a>
                        </p>

                        <div class="mt-6 border-t border-zinc-800 pt-4 text-sm text-zinc-400">
                            <strong class="font-medium text-zinc-200">{{ __('global.welcome_privacy_title') }}:</strong>
                            {{ __('global.welcome_privacy_text') }}
                            <a href="{{ route('legal.privacy') }}" class="font-medium text-zinc-200 hover:text-white hover:underline">{{ __('global.welcome_privacy_link') }}</a>
                        </div>

                        <div class="mt-7 flex flex-wrap items-center gap-3">
                            <button type="button" data-welcome-dismiss class="rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-zinc-200">{{ __('global.welcome_explore') }}</button>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-lg border border-zinc-700 px-4 py-2.5 text-sm font-semibold text-zinc-200 transition hover:bg-zinc-900 hover:text-white">{{ __('global.welcome_join') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @endguest

        <x-ui-feedback-dialog />

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
        @stack('scripts')
        <script>
            (() => {
                const initNotificationMenu = () => {
                    document.querySelectorAll('[data-notification-menu]').forEach((menu) => {
                        if (menu.dataset.bound) return;
                        menu.dataset.bound = '1';

                        const toggle = menu.querySelector('[data-notification-toggle]');
                        const dropdown = menu.querySelector('[data-notification-dropdown]');

                        toggle?.addEventListener('click', (event) => {
                            event.stopPropagation();
                            const open = dropdown?.classList.contains('hidden');
                            dropdown?.classList.toggle('hidden', !open);
                            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                        });

                        document.addEventListener('click', (event) => {
                            if (!menu.contains(event.target)) {
                                dropdown?.classList.add('hidden');
                                toggle?.setAttribute('aria-expanded', 'false');
                            }
                        });
                    });
                };

                const initGuestPrompts = () => {
                    const prompt = document.querySelector('[data-auth-prompt]');
                    if (prompt && !prompt.dataset.bound) {
                        prompt.dataset.bound = '1';
                        const title = prompt.querySelector('[data-auth-prompt-title]');
                        const message = prompt.querySelector('[data-auth-prompt-message]');

                        const closePrompt = () => {
                            prompt.classList.add('hidden');
                            prompt.classList.remove('flex');
                        };

                        document.querySelectorAll('[data-auth-feature]').forEach((trigger) => {
                            if (trigger.dataset.authBound) return;
                            trigger.dataset.authBound = '1';
                            trigger.addEventListener('click', (event) => {
                                event.preventDefault();
                                title.textContent = trigger.dataset.authTitle || @js(__('global.auth_prompt_title'));
                                message.textContent = trigger.dataset.authMessage || @js(__('global.auth_prompt_default'));
                                prompt.classList.remove('hidden');
                                prompt.classList.add('flex');
                            });
                        });

                        prompt.querySelectorAll('[data-auth-prompt-close]').forEach((button) => button.addEventListener('click', closePrompt));
                        prompt.addEventListener('click', (event) => {
                            if (event.target === prompt) closePrompt();
                        });
                    }

                    const splash = document.querySelector('[data-welcome-splash]');
                    if (splash && !splash.dataset.bound) {
                        splash.dataset.bound = '1';
                        const storageKey = 'camperwolf.welcome-seen.v1';
                        let seen = false;
                        try { seen = localStorage.getItem(storageKey) === '1'; } catch (_) {}

                        const closeSplash = () => {
                            splash.classList.add('hidden');
                            splash.classList.remove('flex');
                        };

                        const dismissSplashPermanently = () => {
                            try { localStorage.setItem(storageKey, '1'); } catch (_) {}
                            closeSplash();
                        };

                        splash.querySelectorAll('[data-welcome-close]').forEach((button) => button.addEventListener('click', closeSplash));
                        splash.querySelectorAll('[data-welcome-dismiss]').forEach((button) => button.addEventListener('click', dismissSplashPermanently));
                        splash.addEventListener('click', (event) => {
                            if (event.target === splash) closeSplash();
                        });

                        if (!seen) {
                            splash.classList.remove('hidden');
                            splash.classList.add('flex');
                        }
                    }
                };

                const initBetaNotice = () => {
                    const dialog = document.querySelector('[data-beta-notice]');
                    const trigger = document.querySelector('[data-beta-notice-open]');
                    if (!dialog || !trigger || dialog.dataset.bound) return;

                    dialog.dataset.bound = '1';

                    const open = () => {
                        dialog.classList.remove('hidden');
                        dialog.classList.add('flex');
                    };

                    const close = () => {
                        dialog.classList.add('hidden');
                        dialog.classList.remove('flex');
                    };

                    trigger.addEventListener('click', open);

                    if (new URLSearchParams(window.location.search).get('beta') === '1') {
                        open();
                    }
                    dialog.querySelectorAll('[data-beta-notice-close]').forEach((button) => button.addEventListener('click', close));
                    dialog.addEventListener('click', (event) => {
                        if (event.target === dialog) close();
                    });
                };

                const initGlobalUi = () => {
                    initNotificationMenu();
                    initGuestPrompts();
                    initBetaNotice();
                };

                document.addEventListener('DOMContentLoaded', initGlobalUi, { once: true });
                document.addEventListener('livewire:navigated', initGlobalUi);
                document.addEventListener('keydown', (event) => {
                    if (event.key !== 'Escape') return;
                    document.querySelectorAll('[data-auth-prompt], [data-welcome-splash], [data-beta-notice]').forEach((overlay) => {
                        overlay.classList.add('hidden');
                        overlay.classList.remove('flex');
                    });
                });
            })();
        </script>
    </body>
</html>
