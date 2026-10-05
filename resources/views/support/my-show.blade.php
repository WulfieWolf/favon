<x-layouts::app :title="__('support.my.ticket_title', ['id' => $ticketRow->id])">
    @php
        $statusLabels = collect(['new', 'open', 'in_progress', 'waiting_for_user', 'on_hold', 'resolved', 'closed'])->mapWithKeys(fn ($key) => [$key => __("support.statuses.{$key}")]);
    @endphp
    <div class="mx-auto w-full max-w-3xl space-y-6 px-5 py-8">
        <div>
            <a href="{{ route('support.my.index') }}" class="text-sm text-zinc-500 hover:text-zinc-950 dark:hover:text-white">{{ __('support.my.back') }}</a>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold">{{ __('support.my.ticket_title', ['id' => $ticketRow->id]) }}</h1>
                <span class="rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium dark:bg-zinc-800">{{ $statusLabels[$ticketRow->status] ?? $ticketRow->status }}</span>
            </div>
            <p class="mt-3 whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ $ticketRow->description }}</p>
        </div>

        @if ($publicEntry)
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/20">
                <div class="text-sm font-semibold">{{ __('support.my.public_entry') }}</div>
                <div class="mt-1">{{ $publicEntry->title }}</div>
                <a href="{{ route('roadmap') }}" class="mt-2 inline-block text-sm font-medium hover:underline">{{ __('support.my.view_roadmap') }}</a>
            </div>
        @endif

        <div class="space-y-3">
            @foreach ($messages as $message)
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-center justify-between gap-3 text-xs text-zinc-500">
                        <span>{{ $message->message_type === 'staff_reply' ? __('support.my.support_name') : ($message->user_name ?: __('support.my.you')) }}</span>
                        <span>{{ \App\Support\LocalTime::parse($message->created_at)->translatedFormat(__('support.date_time_format')) }}</span>
                    </div>
                    <div class="mt-2 whitespace-pre-line text-sm leading-6">{{ $message->message }}</div>
                </div>
            @endforeach
        </div>

        @if ($ticketRow->status !== 'closed')
            <form method="POST" action="{{ route('support.my.reply', $ticketRow->id) }}" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                @csrf
                <label class="block text-sm font-medium">{{ __('support.my.reply') }}
                    <textarea name="message" rows="5" required class="mt-2 w-full rounded-lg border border-zinc-300 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900"></textarea>
                </label>
                <button class="mt-3 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('support.my.send_reply') }}</button>
            </form>
        @else
            <div class="rounded-xl bg-zinc-100 p-4 text-sm text-zinc-500 dark:bg-zinc-900">{{ __('support.my.closed') }}</div>
        @endif
    </div>
</x-layouts::app>
