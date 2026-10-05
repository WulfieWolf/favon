<x-layouts::app :title="__('notifications.title')">
    <div class="mx-auto w-full max-w-4xl px-5 py-8 xl:px-7">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">{{ __('notifications.title') }}</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('notifications.history_intro') }}</p>
            </div>

            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                    {{ __('notifications.mark_all_read') }}
                </button>
            </form>
        </div>
<div class="space-y-3">
            @forelse ($notifications as $notification)
                <a href="{{ route('notifications.show', $notification->type === 'beta_welcome' ? ['notification' => $notification->id, 'beta' => 1] : ['notification' => $notification->id]) }}"
                    class="block rounded-xl border p-4 transition hover:bg-zinc-50 dark:hover:bg-zinc-900 {{ $notification->read_at ? 'border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950' : 'border-zinc-300 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900' }}">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-full border border-zinc-200 bg-white text-zinc-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300">
                            <x-tabler-icon name="bell" class="size-4" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="font-semibold">{{ $notification->title }}</div>
                                @if (!$notification->read_at)
                                    <span class="rounded-full bg-red-600 px-2 py-0.5 text-[10px] font-semibold text-white">{{ __('notifications.new') }}</span>
                                @endif
                                @if ($notification->priority === 'important')
                                    <span class="rounded-full border border-amber-400/50 bg-amber-500/10 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:text-amber-300">{{ __('notifications.important') }}</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $notification->message }}</p>
                            <div class="mt-2 text-xs text-zinc-400">{{ \App\Support\LocalTime::parse($notification->available_at)->diffForHumans() }}</div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700">
                    {{ __('notifications.empty') }}
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    </div>
</x-layouts::app>
