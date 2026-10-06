@props([
    'heading' => null,
    'subheading' => null,
    'wide' => false,
])

<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist aria-label="{{ __('Settings') }}">
            <flux:navlist.item :href="route('profile.edit')" wire:navigate>{{ __('community_profile.settings.account') }}</flux:navlist.item>
            <flux:navlist.item :href="route('security.edit')" wire:navigate>{{ __('community_profile.settings.security') }}</flux:navlist.item>
            <flux:navlist.item :href="route('notifications.settings')" wire:navigate>{{ __('community_profile.settings.notifications') }}</flux:navlist.item>
            <flux:navlist.item :href="route('support.my.index')">{{ __('community_profile.settings.my_reports') }}</flux:navlist.item>
            <flux:navlist.item :href="route('help.index')">{{ __('community_profile.settings.help_support') }}</flux:navlist.item>
            <flux:navlist.item :href="route('appearance.edit')" wire:navigate>{{ __('community_profile.settings.appearance') }}</flux:navlist.item>
            <flux:navlist.item :href="route('data-export.show')">{{ __('data_export.nav') }}</flux:navlist.item>
            <flux:navlist.item :href="route('account-deletion.show')" class="text-red-600 dark:text-red-400">{{ __('account_deletion.nav') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div @class(['mt-5 w-full', 'max-w-4xl' => $wide, 'max-w-lg' => ! $wide])>
            {{ $slot }}
        </div>
    </div>
</div>
