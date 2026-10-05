<x-layouts::app :title="__('support.my.title')">
    @php
        $typeLabels = collect(['bug', 'feature_request', 'improvement', 'data_issue', 'other'])->mapWithKeys(fn ($key) => [$key => __("support.types.{$key}.short")]);
        $statusLabels = collect(['new', 'open', 'in_progress', 'waiting_for_user', 'on_hold', 'resolved', 'closed'])->mapWithKeys(fn ($key) => [$key => __("support.statuses.{$key}")]);
    @endphp
    <div class="mx-auto w-full max-w-5xl space-y-6 px-5 py-8">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">{{ __('support.my.title') }}</h1>
                <p class="mt-2 text-zinc-500">{{ __('support.my.intro') }}</p>
            </div>
            <a href="{{ route('support.report') }}" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('support.my.new') }}</a>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            @forelse ($tickets as $ticket)
                <a href="{{ route('support.my.show', $ticket->id) }}" class="flex items-center justify-between gap-4 border-b border-zinc-200 p-4 last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900">
                    <div class="min-w-0">
                        <div class="font-semibold">#{{ $ticket->id }} · {{ $ticket->subject ?: \Illuminate\Support\Str::limit($ticket->description, 70) }}</div>
                        <div class="mt-1 text-sm text-zinc-500">{{ $typeLabels[$ticket->type] ?? $ticket->type }} · {{ __('support.my.updated', ['date' => \App\Support\LocalTime::parse($ticket->updated_at)->diffForHumans()]) }}</div>
                    </div>
                    <span class="shrink-0 rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium dark:bg-zinc-800">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span>
                </a>
            @empty
                <div class="p-8 text-center text-zinc-500">{{ __('support.my.empty') }}</div>
            @endforelse
        </div>
        {{ $tickets->links() }}
    </div>
</x-layouts::app>
