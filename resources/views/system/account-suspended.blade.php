<x-layouts::app :title="__('admin.users.suspended_title')">
    <div class="mx-auto max-w-2xl space-y-6 py-10">
        <flux:heading size="xl">{{ __('admin.users.suspended_title') }}</flux:heading>
        <flux:text>{{ __('admin.users.suspended_text') }}</flux:text>
        @if ($until)
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950/30">
                {{ __('admin.users.suspended_until_notice', ['date' => \App\Support\LocalTime::format($until, app()->getLocale() === 'de' ? 'd.m.Y H:i' : 'Y-m-d H:i')]) }}
            </div>
        @endif
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="underline" type="submit">{{ __('admin.system.logout') }}</button></form>
    </div>
</x-layouts::app>
