<x-layouts::app :title="__('admin.system.title')">
    <div class="mx-auto w-full max-w-5xl space-y-6 px-5 py-8 xl:px-7">
        <div>
            <a href="{{ route('admin.index') }}" class="text-sm text-zinc-500 hover:text-zinc-950 dark:hover:text-white">
                {{ __('admin.system.back_admin') }}
            </a>
            <h1 class="mt-4 text-2xl font-semibold tracking-tight">{{ __('admin.system.title') }}</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin.system.intro') }}</p>
        </div>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-semibold">{{ __('admin.system.access_title') }}</h2>
                <span @class([
                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' => $accessMode === 'normal',
                    'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300' => $accessMode === 'registration_closed',
                    'bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300' => $accessMode === 'lockdown',
                ])>{{ __('admin.system.access_modes.'.$accessMode) }}</span>
            </div>
            <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('admin.system.access_help') }}</p>

            <form method="POST" action="{{ route('admin.system.access') }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="access-mode" class="text-sm font-medium">{{ __('admin.system.access_mode_label') }}</label>
                    <select id="access-mode" name="mode" class="mt-1 block w-full rounded-lg border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        @foreach (['normal', 'registration_closed', 'lockdown'] as $mode)
                            <option value="{{ $mode }}" @selected($accessMode === $mode)>{{ __('admin.system.access_modes.'.$mode) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="access-message" class="text-sm font-medium">{{ __('admin.system.access_message_label') }}</label>
                    <textarea id="access-message" name="message" rows="4" maxlength="1000" class="mt-1 block w-full rounded-lg border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-950" placeholder="{{ __('admin.system.access_message_placeholder') }}">{{ old('message', $accessMessage) }}</textarea>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('admin.system.access_message_help') }}</p>
                </div>

                <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                    {{ __('admin.system.lockdown_warning') }}
                </div>

                <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                    {{ __('admin.system.save_access') }}
                </button>
            </form>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="max-w-2xl">
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-semibold">{{ __('admin.system.debug_title') }}</h2>
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-semibold',
                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' => $debugEnabled,
                            'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' => ! $debugEnabled,
                        ])>
                            {{ $debugEnabled ? __('admin.system.active') : __('admin.system.inactive') }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        {{ __('admin.system.debug_help') }}
                    </p>
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('admin.system.debug_session_help') }}
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.system.debug') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="enabled" value="{{ $debugEnabled ? 0 : 1 }}">
                    <button
                        type="submit"
                        @class([
                            'rounded-lg px-4 py-2 text-sm font-semibold transition',
                            'border border-red-300 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950/30' => $debugEnabled,
                            'bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200' => ! $debugEnabled,
                        ])
                    >
                        {{ $debugEnabled ? __('admin.system.disable_debug') : __('admin.system.enable_debug') }}
                    </button>
                </form>
            </div>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="max-w-2xl">
                    <h2 class="text-lg font-semibold">{{ __('admin.system.data_scores_title') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('admin.system.data_scores_help') }}</p>
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('admin.system.data_scores_status', [
                            'calculated' => $dataScoreMetrics['calculated'],
                            'total' => $dataScoreMetrics['total'],
                            'dirty' => $dataScoreMetrics['dirty'],
                        ]) }}
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.system.data-scores.rebuild') }}" onsubmit="return confirm(@js(__('admin.system.data_scores_confirm')))">
                    @csrf
                    <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                        {{ __('admin.system.data_scores_rebuild') }}
                    </button>
                </form>
            </div>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <div>
                <h2 class="text-lg font-semibold">{{ __('admin.system.security_title') }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin.system.security_help') }}</p>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    'quarantined' => $securityMetrics['quarantined'],
                    'abuse_flags_24h' => $securityMetrics['abuse_flags_24h'],
                    'rate_limited_24h' => $securityMetrics['rate_limited_24h'],
                    'browse_rejected_24h' => $securityMetrics['browse_rejected_24h'],
                    'registration_limited_24h' => $securityMetrics['registration_limited_24h'],
                ] as $metric => $value)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('admin.system.security_metrics.'.$metric) }}</div>
                        <div class="mt-1 text-2xl font-semibold">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800">
                <div class="border-b border-zinc-200 px-4 py-3 text-sm font-semibold dark:border-zinc-800">
                    {{ __('admin.system.security_recent') }}
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-zinc-50 text-left text-xs text-zinc-500 dark:bg-zinc-950/40 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-2">{{ __('admin.system.security_time') }}</th>
                                <th class="px-4 py-2">{{ __('admin.system.security_event') }}</th>
                                <th class="px-4 py-2">{{ __('admin.system.security_route') }}</th>
                                <th class="px-4 py-2">{{ __('admin.system.security_actor') }}</th>
                                <th class="px-4 py-2">{{ __('admin.system.security_source') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse ($securityEvents as $event)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2 text-zinc-500">{{ \App\Support\LocalTime::parse($event->created_at)->format(__('admin.system.security_date_format')) }}</td>
                                    <td class="px-4 py-2">{{ __('admin.system.security_events.'.$event->event_type) }}</td>
                                    <td class="px-4 py-2 text-zinc-500">{{ $event->route_name ?: '—' }}</td>
                                    <td class="px-4 py-2 text-zinc-500">{{ $event->user_name ?: __('admin.system.security_guest') }}</td>
                                    <td class="px-4 py-2 font-mono text-xs text-zinc-500">{{ $event->source_ip_hash ? substr($event->source_ip_hash, 0, 12).'…' : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-zinc-500">{{ __('admin.system.security_empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-dashed border-zinc-300 p-5 text-zinc-500 dark:border-zinc-700">
            <div class="font-semibold">{{ __('admin.system.future_title') }}</div>
            <p class="mt-1 text-sm">{{ __('admin.system.future_help') }}</p>
        </section>
    </div>
</x-layouts::app>
