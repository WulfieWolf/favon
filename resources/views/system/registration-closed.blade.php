<x-layouts::auth :title="__('admin.system.registration_closed_title')">
    <div class="flex flex-col gap-6 text-center">
        <x-auth-header
            :title="__('admin.system.registration_closed_title')"
            :description="$message ?: __('admin.system.registration_closed_default')"
        />

        <flux:button :href="route('login')" variant="primary" class="w-full" wire:navigate>
            {{ __('global.login') }}
        </flux:button>

        <flux:link :href="route('home')" wire:navigate>
            {{ __('admin.system.back_to_site') }}
        </flux:link>
    </div>
</x-layouts::auth>
