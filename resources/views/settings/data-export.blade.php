<x-layouts::app :title="__('data_export.title')">
<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('data_export.title')" :subheading="__('data_export.subtitle')" :wide="true">
        <div class="space-y-6">
            <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
                <h2 class="text-lg font-semibold">{{ __('data_export.intro_title') }}</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('data_export.intro_text') }}</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
                    <h2 class="font-semibold">{{ __('data_export.format_title') }}</h2>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('data_export.format_text') }}</p>
                </div>

                <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
                    <h2 class="font-semibold">{{ __('data_export.limit_title') }}</h2>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                        {{ __('data_export.limit_text', ['days' => $cooldownDays, 'hours' => $downloadHours]) }}
                    </p>
                </div>
            </div>

            <div class="rounded-xl border border-sky-200 bg-sky-50/70 p-5 dark:border-sky-900 dark:bg-sky-950/25">
                <h2 class="font-semibold">{{ __('data_export.rights_title') }}</h2>
                <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">{{ __('data_export.rights_text') }}</p>
                <a href="{{ route('help.index') }}" class="mt-3 inline-block text-sm font-medium underline">
                    {{ __('community_profile.settings.help_support') }}
                </a>
            </div>

            @if ($latest)
                @php
                    $status = (string) $latest->status;
                    $isReady = $status === 'ready'
                        && $latest->expires_at
                        && \App\Support\LocalTime::parse($latest->expires_at)->isFuture();
                @endphp
                <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold">{{ __('data_export.latest_title') }}</h2>
                            <div class="mt-1 text-sm font-medium">{{ __('data_export.status.'.$status) }}</div>
                        </div>
                        <a href="{{ route('data-export.show') }}" class="text-sm underline">{{ __('data_export.refresh') }}</a>
                    </div>

                    <div class="mt-4 space-y-1 text-sm text-zinc-600 dark:text-zinc-300">
                        <p>{{ __('data_export.requested_at', ['date' => \App\Support\LocalTime::parse($latest->requested_at)->translatedFormat(__('data_export.date_time_format'))]) }}</p>
                        @if ($latest->completed_at)
                            <p>{{ __('data_export.completed_at', ['date' => \App\Support\LocalTime::parse($latest->completed_at)->translatedFormat(__('data_export.date_time_format'))]) }}</p>
                        @endif
                        @if ($latest->expires_at)
                            <p>{{ __('data_export.expires_at', ['date' => \App\Support\LocalTime::parse($latest->expires_at)->translatedFormat(__('data_export.date_time_format'))]) }}</p>
                        @endif
                        @if ($latest->file_size)
                            <p>{{ __('data_export.file_size', ['size' => \Illuminate\Support\Number::fileSize((int) $latest->file_size)]) }}</p>
                        @endif
                    </div>

                    @if ($isReady)
                        <flux:button class="mt-4" variant="primary" :href="route('data-export.download', $latest->token)">
                            {{ __('data_export.download') }}
                        </flux:button>
                    @elseif ($status === 'failed')
                        <p class="mt-4 text-sm text-red-600 dark:text-red-400">{{ __('data_export.failed_text') }}</p>
                    @endif
                </div>
            @endif

            @error('export')
                <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200">{{ $message }}</div>
            @enderror

            @php
                $active = $latest && in_array($latest->status, ['queued', 'processing'], true);
                $cooldownActive = $nextAvailableAt && now()->lt($nextAvailableAt);
                $canRequest = ! $active && ! $cooldownActive;
            @endphp

            @if ($cooldownActive)
                <p class="text-sm text-zinc-500">
                    {{ __('data_export.next_available', ['date' => $nextAvailableAt->translatedFormat(__('data_export.date_time_format'))]) }}
                </p>
            @endif

            <form method="POST" action="{{ route('data-export.request') }}">
                @csrf
                <flux:button type="submit" variant="primary" :disabled="! $canRequest">
                    {{ __('data_export.request_button') }}
                </flux:button>
            </form>
        </div>
    </x-pages::settings.layout>
</section>
</x-layouts::app>
