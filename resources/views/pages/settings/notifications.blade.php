<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('notifications.title')] class extends Component {
    public bool $moderationDecisions = true;
    public bool $favoriteChanges = true;
    public bool $generalSystem = true;

    public function mount(): void
    {
        $preferences = DB::table('user_notification_preferences')
            ->where('user_id', Auth::id())
            ->first();

        if (! $preferences) {
            return;
        }

        $this->moderationDecisions = (bool) $preferences->moderation_decisions;
        $this->favoriteChanges = (bool) $preferences->favorite_changes;
        $this->generalSystem = (bool) $preferences->general_system;
    }

    public function save(): void
    {
        DB::table('user_notification_preferences')->updateOrInsert(
            ['user_id' => Auth::id()],
            [
                'moderation_decisions' => $this->moderationDecisions,
                'favorite_changes' => $this->favoriteChanges,
                'general_system' => $this->generalSystem,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        Flux::toast(variant: 'success', text: __('notifications.settings_saved'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('notifications.title') }}</flux:heading>

    <x-pages::settings.layout
        :heading="__('notifications.title')"
        :subheading="__('notifications.settings_subtitle')"
    >
        <form wire:submit="save" class="my-6 w-full space-y-4">
            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <div class="font-medium">{{ __('notifications.moderation_decisions') }}</div>
                    <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('notifications.moderation_decisions_help') }}
                    </div>
                </div>
                <input wire:model="moderationDecisions" type="checkbox" class="mt-1 size-5 rounded border-zinc-300">
            </label>

            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <div class="font-medium">{{ __('notifications.favorite_changes') }}</div>
                    <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('notifications.favorite_changes_help') }}
                    </div>
                </div>
                <input wire:model="favoriteChanges" type="checkbox" class="mt-1 size-5 rounded border-zinc-300">
            </label>

            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <div class="font-medium">{{ __('notifications.general_system') }}</div>
                    <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('notifications.general_system_help') }}
                    </div>
                </div>
                <input wire:model="generalSystem" type="checkbox" class="mt-1 size-5 rounded border-zinc-300">
            </label>

            <div class="flex items-start justify-between gap-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 opacity-75 dark:border-zinc-700 dark:bg-zinc-800/50">
                <div>
                    <div class="font-medium">{{ __('notifications.important_system') }}</div>
                    <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('notifications.important_system_help') }}
                    </div>
                </div>
                <input type="checkbox" checked disabled class="mt-1 size-5 rounded border-zinc-300">
            </div>

            <div class="pt-2">
                <flux:button variant="primary" type="submit">{{ __('notifications.save_settings') }}</flux:button>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
