<x-layouts::app :title="__('support.thanks.title')">
    <div class="mx-auto max-w-xl px-5 py-16 text-center">
        <h1 class="text-3xl font-semibold">{{ __('support.thanks.heading') }}</h1>
        <p class="mt-3 text-zinc-500">{{ __('support.thanks.saved', ['id' => session('ticket_id') ? ' (#'.session('ticket_id').')' : '']) }}</p>
        <a href="{{ route('help.index') }}" class="mt-6 inline-block rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('support.thanks.back') }}</a>
    </div>
</x-layouts::app>
