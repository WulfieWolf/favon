<x-layouts::app :title="$notification->title">
    <div class="mx-auto w-full max-w-4xl px-5 py-8 xl:px-7">
        <a href="{{ route('notifications.index') }}" class="text-sm font-medium text-zinc-500 hover:text-zinc-950 dark:hover:text-white">
            ← {{ __('notifications.back') }}
        </a>

        <div class="mt-5 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-start gap-3">
                <div class="grid size-10 shrink-0 place-items-center rounded-full border border-zinc-200 bg-zinc-50 text-zinc-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300">
                    <x-tabler-icon name="bell" class="size-5" />
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-semibold">{{ $notification->title }}</h1>
                        @if ($notification->priority === 'important')
                            <span class="rounded-full border border-amber-400/50 bg-amber-500/10 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:text-amber-300">{{ __('notifications.important') }}</span>
                        @endif
                    </div>
                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $notification->message }}</p>
                    <div class="mt-3 text-xs text-zinc-400">{{ \App\Support\LocalTime::parse($notification->available_at)->format('d.m.Y H:i') }}</div>
                </div>
            </div>
        </div>

        @if ($events->isNotEmpty())
            <section class="mt-6">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-zinc-400">{{ __('notifications.details') }}</h2>

                <div class="space-y-2">
                    @foreach ($events as $event)
                        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="font-medium">{{ $event->title }}</div>
                                <div class="text-xs text-zinc-400">{{ \App\Support\LocalTime::parse($event->occurred_at)->format('d.m.Y H:i') }}</div>
                            </div>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $event->message }}</p>

                            @if ($event->url)
                                <a href="{{ $event->url }}" class="mt-2 inline-flex text-sm font-medium text-zinc-700 underline-offset-4 hover:underline dark:text-zinc-200">
                                    {{ __('notifications.open_place') }}
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @elseif ($notification->url && !request()->fullUrlIs($notification->url))
            <div class="mt-5">
                <a href="{{ $notification->url }}" class="inline-flex rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                    {{ __('notifications.open_link') }}
                </a>
            </div>
        @endif
    </div>
</x-layouts::app>
