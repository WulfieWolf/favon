@php
    $user = auth()->user();
    $profileUrl = $user->publicProfileUrl();
    $publicName = $user->publicName();
    $profilePhotoUrl = $user->publicProfilePhotoUrl();
    $gamification = $user->gamificationSummary();
    $badgeTitle = $gamification ? app('App\\Services\\BadgeService')->titleForUser((int) $user->id) : null;
    $joinedAt = $user->created_at?->translatedFormat('F Y');
    $permissionService = app(\App\Services\PermissionService::class);
    $rolePreview = $permissionService->activeRolePreview($user);
    $previewRoles = $permissionService->isOwner($user)
        ? \Illuminate\Support\Facades\DB::table('roles')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['slug', 'name'])
        : collect();
@endphp

<flux:dropdown position="bottom" align="start">
    <button
        type="button"
        data-test="sidebar-menu-button"
        class="flex w-full items-center gap-3 rounded-lg px-2 py-1.5 text-left hover:bg-zinc-100 dark:hover:bg-zinc-800"
    >
        <x-user-status-avatar
            :user="$user"
            :photo-url="$profilePhotoUrl"
            :gamification="$gamification"
            :title="$badgeTitle"
            :joined-at="$joinedAt"
        />

        <span class="min-w-0 flex-1 truncate font-medium">{{ $publicName }}</span>
        <x-tabler-icon name="chevrons-up-down" class="size-4 shrink-0 text-zinc-500" />
    </button>

    <flux:menu>
        @if ($profileUrl)
            <a href="{{ $profileUrl }}" class="flex items-center gap-2 rounded-lg px-1 py-1.5 text-start text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                <span class="relative inline-flex size-9 shrink-0">
                    @if ($profilePhotoUrl)
                        <img src="{{ $profilePhotoUrl }}" alt="{{ $publicName }}" class="size-9 rounded-full object-cover">
                    @else
                        <span class="flex size-9 items-center justify-center rounded-full bg-zinc-200 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                            {{ $user->initials() }}
                        </span>
                    @endif
                    @if ($gamification)
                        <span class="absolute bottom-0 right-0 inline-flex min-w-4 translate-x-1/4 translate-y-1/4 items-center justify-center rounded-full border border-white bg-zinc-900 px-1 text-[9px] font-bold leading-4 text-white dark:border-zinc-950 dark:bg-white dark:text-zinc-950">
                            {{ $gamification['level'] }}
                        </span>
                    @endif
                </span>
                <div class="grid min-w-0 flex-1 text-start text-sm leading-tight">
                    <flux:heading class="truncate">{{ $publicName }}</flux:heading>
                    @if ($badgeTitle)
                        <flux:text class="truncate text-xs">{{ $badgeTitle['label'] }}</flux:text>
                    @endif
                    <flux:text class="truncate">{{ $user->email }}</flux:text>
                </div>
            </a>
        @else
            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                <span class="relative inline-flex size-9 shrink-0">
                    @if ($profilePhotoUrl)
                        <img src="{{ $profilePhotoUrl }}" alt="{{ $publicName }}" class="size-9 rounded-full object-cover">
                    @else
                        <span class="flex size-9 items-center justify-center rounded-full bg-zinc-200 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                            {{ $user->initials() }}
                        </span>
                    @endif
                    @if ($gamification)
                        <span class="absolute bottom-0 right-0 inline-flex min-w-4 translate-x-1/4 translate-y-1/4 items-center justify-center rounded-full border border-white bg-zinc-900 px-1 text-[9px] font-bold leading-4 text-white dark:border-zinc-950 dark:bg-white dark:text-zinc-950">
                            {{ $gamification['level'] }}
                        </span>
                    @endif
                </span>
                <div class="grid min-w-0 flex-1 text-start text-sm leading-tight">
                    <flux:heading class="truncate">{{ $publicName }}</flux:heading>
                    <flux:text class="truncate">{{ $user->email }}</flux:text>
                </div>
            </div>
        @endif

        <flux:menu.separator />


        <flux:menu.radio.group>
            @if ($profileUrl)
                <flux:menu.item :href="$profileUrl" icon="user">
                    {{ __('community_profile.public_title') }}
                </flux:menu.item>
            @endif
            <flux:menu.item :href="route('community-profile.edit')" icon="pencil-square" wire:navigate>
                {{ __('community_profile.settings.title') }}
            </flux:menu.item>
            @if ($permissionService->can($user, 'admin.access'))
                <flux:menu.item :href="route('admin.index')" icon="wrench-screwdriver">
                    {{ __('ui.admin') }}
                </flux:menu.item>
            @endif

            @if ($permissionService->isOwner($user))
                <div class="px-2 py-1.5">
                    <form method="POST" action="{{ route('role-preview.update') }}">
                        @csrf
                        <label class="mb-1 block text-xs font-medium {{ $rolePreview ? 'text-amber-500' : 'text-zinc-500 dark:text-zinc-400' }}">
                            {{ __('ui.role') }}
                        </label>
                        <select
                            name="role"
                            onchange="this.form.submit()"
                            class="h-9 w-full rounded-lg border px-2 text-xs outline-none {{ $rolePreview ? 'border-amber-500 bg-amber-950/40 text-amber-100' : 'border-zinc-300 bg-white text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100' }}"
                            title="{{ __('global.role_preview_title') }}"
                        >
                            <option value="default" @selected($rolePreview === null)>{{ __('global.role_preview_default') }}</option>
                            @foreach ($previewRoles as $previewRole)
                                <option value="{{ $previewRole->slug }}" @selected($rolePreview === $previewRole->slug)>
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

            <flux:menu.item :href="route('help.index')" icon="question-mark-circle">
                {{ __('global.help_support') }}
            </flux:menu.item>
            <flux:menu.item :href="route('support.my.index')" icon="chat-bubble-left-right">
                {{ __('community_profile.settings.my_reports') }}
            </flux:menu.item>
            @if ($permissionService->can($user, 'support.view_all'))
                <flux:menu.item :href="route('admin.support.index')" icon="inbox-stack">
                    {{ __('global.manage_support') }}
                </flux:menu.item>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item
                    as="button"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                    class="w-full cursor-pointer"
                    data-test="logout-button"
                >
                    {{ __('Log out') }}
                </flux:menu.item>
            </form>
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
