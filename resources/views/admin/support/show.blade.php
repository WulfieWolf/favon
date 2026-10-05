<x-layouts::app :title="__('admin.support.ticket_title', ['id' => $ticketRow->id])">
    @php
        $statusLabels = __('admin.support.statuses');
        $priorityLabels = __('admin.support.priorities');
        $typeLabels = collect(__('support.types'))->mapWithKeys(fn ($value, $key) => [$key => $value['short']])->all();
        $eventLabels = __('admin.support.events');
    @endphp
    @php $supportPermissions = app(\App\Services\PermissionService::class); @endphp
    <div class="mx-auto w-full max-w-7xl space-y-6 px-5 py-8">
        <div>
            <a href="{{ route('admin.support.index') }}" class="text-sm text-zinc-500 hover:text-zinc-950 dark:hover:text-white">← {{ __('admin.support.title') }}</a>
            <h1 class="mt-3 text-3xl font-semibold">{{ __('admin.support.ticket_title', ['id' => $ticketRow->id]) }}</h1>
        </div>
<div class="grid gap-6 lg:grid-cols-[1fr_360px]">
            <div class="space-y-5">
                <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                        <span class="rounded bg-zinc-100 px-2 py-1 dark:bg-zinc-800">{{ $typeLabels[$ticketRow->type] ?? $ticketRow->type }}</span>
                        <span>{{ $ticketRow->user_name ?: ($ticketRow->guest_name ?: __('admin.support.guest')) }}</span>
                        @if ($ticketRow->user_email || $ticketRow->guest_email)<span>· {{ $ticketRow->user_email ?: $ticketRow->guest_email }}</span>@endif
                        @if ($ticketRow->guest_phone)<span>· {{ $ticketRow->guest_phone }}</span>@endif
                    </div>
                    @if ($ticketRow->privacy_case_id && !$ticketRow->user_id && $ticketRow->guest_email)
                        <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/20 dark:text-amber-200">
                            {{ __('admin.support.privacy.guest_delivery_warning') }}
                        </div>
                    @endif
                    @if ($ticketRow->subject)<h2 class="mt-3 text-xl font-semibold">{{ $ticketRow->subject }}</h2>@endif
                    <div class="mt-3 whitespace-pre-line leading-7">{{ $ticketRow->description }}</div>
                    <div class="mt-4 space-y-1 text-xs text-zinc-500">
                        @if ($ticketRow->context_key)<div>{{ __('admin.support.context') }}: {{ $ticketRow->context_key }} / {{ $ticketRow->module }}</div>@endif
                        @if ($ticketRow->route_name)<div>{{ __('admin.support.route') }}: {{ $ticketRow->route_name }}</div>@endif
                        @if ($ticketRow->source_url)<div class="break-all">{{ __('admin.support.source') }}: <a href="{{ $ticketRow->source_url }}" class="hover:underline">{{ $ticketRow->source_url }}</a></div>@endif
                        @if ($ticketRow->user_agent)<div class="break-all">{{ __('admin.support.browser') }}: {{ $ticketRow->user_agent }}</div>@endif
                    </div>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-semibold">{{ __('admin.support.communication') }}</h2>
                    @foreach ($messages as $message)
                        <div class="rounded-xl border p-4 {{ $message->message_type === 'internal_note' ? 'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/20' : 'border-zinc-200 dark:border-zinc-700' }}">
                            <div class="flex justify-between gap-3 text-xs text-zinc-500">
                                <span>{{ __('admin.support.message_types.'.$message->message_type) }} · {{ $message->user_name ?: __('admin.support.guest') }}</span>
                                <span>{{ \App\Support\LocalTime::parse($message->created_at)->format(__('support.date_time_format')) }}</span>
                            </div>
                            <div class="mt-2 whitespace-pre-line text-sm leading-6">{{ $message->message }}</div>
                        </div>
                    @endforeach
                </section>

                @if ($ticketRow->status !== 'closed' && ($supportPermissions->can(auth()->user(), 'support.reply') || $supportPermissions->can(auth()->user(), 'support.internal_note')))
                    <div class="grid gap-4 md:grid-cols-2">
                        @if ($supportPermissions->can(auth()->user(), 'support.reply'))
                        <form method="POST" action="{{ route('admin.support.reply', $ticketRow->id) }}" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            @csrf
                            <div class="font-semibold">{{ __('admin.support.reply_to_user') }}</div>
                            <textarea name="message" rows="5" required class="mt-3 w-full rounded-lg border border-zinc-300 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900"></textarea>
                            <button class="mt-3 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('admin.support.send_reply') }}</button>
                        </form>
                        @endif
                        @if ($supportPermissions->can(auth()->user(), 'support.internal_note'))
                        <form method="POST" action="{{ route('admin.support.note', $ticketRow->id) }}" class="rounded-xl border border-amber-300 bg-amber-50/50 p-4 dark:border-amber-800 dark:bg-amber-950/10">
                            @csrf
                            <div class="font-semibold">{{ __('admin.support.internal_note') }}</div>
                            <textarea name="message" rows="5" required class="mt-3 w-full rounded-lg border border-amber-300 bg-white p-3 dark:border-amber-800 dark:bg-zinc-900"></textarea>
                            <button class="mt-3 rounded-lg border border-amber-500 px-4 py-2 text-sm font-semibold">{{ __('admin.support.save_note') }}</button>
                        </form>
                        @endif
                    </div>
                @endif
            </div>

            <aside class="space-y-5">
                @if ($supportPermissions->can(auth()->user(), 'support.change_status'))
                <form method="POST" action="{{ route('admin.support.update', $ticketRow->id) }}" class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                    @csrf
                    @method('PUT')
                    <h2 class="font-semibold">{{ __('admin.support.case_management') }}</h2>
                    @if ($ticketRow->privacy_case_id)
                        <div class="rounded-lg border border-blue-200 bg-blue-50/60 p-4 text-sm dark:border-blue-900 dark:bg-blue-950/20">
                            <div class="font-semibold">{{ __('admin.support.privacy.title') }}</div>
                            <div class="mt-2 space-y-1 text-xs text-zinc-500">
                                <div>{{ __('admin.support.privacy.received') }}: {{ \App\Support\LocalTime::parse($ticketRow->privacy_received_at)->format(__('support.date_time_format')) }}</div>
                                @if ($ticketRow->privacy_original_due_at)
                                    <div>{{ __('admin.support.privacy.original_deadline') }}: {{ \App\Support\LocalTime::parse($ticketRow->privacy_original_due_at)->format(__('support.date_format')) }}</div>
                                @else
                                    <div>{{ __('admin.support.privacy.deadline_rule') }}: {{ __('admin.support.privacy.deadline_rules.'.$ticketRow->privacy_deadline_rule) }}</div>
                                @endif
                                @if ($ticketRow->privacy_completed_at)
                                    <div>{{ __('admin.support.privacy.completed') }}: {{ \App\Support\LocalTime::parse($ticketRow->privacy_completed_at)->format(__('support.date_time_format')) }}</div>
                                @endif
                            </div>
                        </div>
                        <label class="block text-sm">{{ __('admin.support.privacy.identity_status') }}
                            <select name="privacy_identity_status" class="mt-1 h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                                @foreach (__('admin.support.privacy.identity_statuses') as $key => $label)
                                    <option value="{{ $key }}" @selected($ticketRow->privacy_identity_status === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm">{{ __('admin.support.privacy.deadline') }}
                            <input type="date" name="privacy_due_at" value="{{ $ticketRow->privacy_due_at ? \App\Support\LocalTime::parse($ticketRow->privacy_due_at)->format('Y-m-d') : '' }}" class="mt-1 h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                            @error('privacy_due_at')<div class="mt-1 text-xs text-red-600">{{ $message }}</div>@enderror
                        </label>
                        <label class="block text-sm">{{ __('admin.support.privacy.extension_reason') }}
                            <textarea name="privacy_extension_reason" rows="3" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">{{ old('privacy_extension_reason', $ticketRow->privacy_extension_reason) }}</textarea>
                            <div class="mt-1 text-xs text-zinc-500">{{ $ticketRow->privacy_deadline_rule === 'gdpr_data_subject_right' ? __('admin.support.privacy.extension_help') : __('admin.support.privacy.manual_deadline_help') }}</div>
                            @error('privacy_extension_reason')<div class="mt-1 text-xs text-red-600">{{ $message }}</div>@enderror
                        </label>
                    @endif
                    <label class="block text-sm">{{ __('admin.support.status') }}
                        <select name="status" class="mt-1 h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                            @foreach ($statusLabels as $key => $label)<option value="{{ $key }}" @selected($ticketRow->status === $key)>{{ $label }}</option>@endforeach
                        </select>
                    </label>
                    <label class="block text-sm">{{ __('admin.support.priority') }}
                        <select name="priority" class="mt-1 h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                            @foreach ($priorityLabels as $key => $label)<option value="{{ $key }}" @selected($ticketRow->priority === $key)>{{ $label }}</option>@endforeach
                        </select>
                    </label>
                    @if ($supportPermissions->can(auth()->user(), 'support.assign'))
                    <label class="block text-sm">{{ __('admin.support.assignment') }}
                        <select name="assigned_to" class="mt-1 h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="">{{ __('admin.support.unassigned') }}</option>
                            @foreach ($assignees as $assignee)<option value="{{ $assignee->id }}" @selected((int)$ticketRow->assigned_to === (int)$assignee->id)>{{ $assignee->name }} ({{ $assignee->email }})</option>@endforeach
                        </select>
                    </label>
                    @else
                        <input type="hidden" name="assigned_to" value="{{ $ticketRow->assigned_to }}">
                    @endif
                    <label class="block text-sm">{{ __('admin.support.public_entry') }}
                        <select name="public_entry_id" class="mt-1 h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="">{{ __('admin.support.no_link') }}</option>
                            @foreach ($publicEntries as $entry)<option value="{{ $entry->id }}" @selected((int)$ticketRow->public_entry_id === (int)$entry->id)>{{ $entry->is_public ? '●' : '○' }} {{ $entry->title }}</option>@endforeach
                        </select>
                    </label>
                    <button class="w-full rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('admin.support.save') }}</button>
                </form>
                @endif

                <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                    <h2 class="font-semibold">{{ __('admin.support.history') }}</h2>
                    <div class="mt-3 space-y-3">
                        @foreach ($events as $event)
                            @php
                                $oldEventValue = $event->old_value;
                                $newEventValue = $event->new_value;
                                if ($event->event_type === 'status_changed') {
                                    $oldEventValue = $statusLabels[$event->old_value] ?? $event->old_value;
                                    $newEventValue = $statusLabels[$event->new_value] ?? $event->new_value;
                                } elseif ($event->event_type === 'priority_changed') {
                                    $oldEventValue = $priorityLabels[$event->old_value] ?? $event->old_value;
                                    $newEventValue = $priorityLabels[$event->new_value] ?? $event->new_value;
                                }
                            @endphp
                            <div class="text-xs">
                                <div class="font-medium">{{ $eventLabels[$event->event_type] ?? $event->event_type }}</div>
                                @if ($event->old_value !== null || $event->new_value !== null)<div class="mt-0.5 text-zinc-500">{{ $oldEventValue ?? '—' }} → {{ $newEventValue ?? '—' }}</div>@endif
                                <div class="mt-0.5 text-zinc-400">{{ $event->user_name ?: __('admin.support.system_guest') }} · {{ \App\Support\LocalTime::parse($event->created_at)->format(__('support.date_time_format')) }}</div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-layouts::app>
