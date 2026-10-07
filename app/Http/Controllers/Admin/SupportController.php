<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use App\Services\UserNotificationService;
use App\Support\LocalTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportController extends Controller
{
    private const STATUSES = ['new', 'open', 'in_progress', 'waiting_for_user', 'on_hold', 'resolved', 'closed'];
    private const PRIORITIES = ['low', 'normal', 'high', 'critical'];

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $type = (string) $request->query('type', '');
        $q = trim((string) $request->query('q', ''));
        $privacy = $request->boolean('privacy');

        $tickets = DB::table('support_tickets as st')
            ->leftJoin('users as u', 'u.id', '=', 'st.user_id')
            ->leftJoin('users as a', 'a.id', '=', 'st.assigned_to')
            ->leftJoin('support_ticket_privacy_cases as pc', 'pc.ticket_id', '=', 'st.id')
            ->when($status !== '', fn ($query) => $query->where('st.status', $status))
            ->when($type !== '', fn ($query) => $query->where('st.type', $type))
            ->when($privacy, fn ($query) => $query->whereNotNull('pc.id'))
            ->when($q !== '', function ($query) use ($q): void {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->where('st.id', $q)
                        ->orWhere('st.subject', 'like', $like)
                        ->orWhere('st.description', 'like', $like)
                        ->orWhere('u.name', 'like', $like)
                        ->orWhere('st.guest_name', 'like', $like)
                        ->orWhere('st.guest_email', 'like', $like);
                });
            })
            ->orderByRaw("CASE WHEN pc.completed_at IS NULL AND pc.due_at < ? THEN 0 ELSE 1 END", [now()])
            ->orderByRaw("CASE st.priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
            ->orderByRaw("CASE st.status WHEN 'new' THEN 1 WHEN 'open' THEN 2 WHEN 'in_progress' THEN 3 WHEN 'waiting_for_user' THEN 4 WHEN 'on_hold' THEN 5 WHEN 'resolved' THEN 6 WHEN 'closed' THEN 7 ELSE 8 END")
            ->orderByDesc('st.updated_at')
            ->select([
                'st.*',
                'u.name as user_name',
                'u.email as user_email',
                'a.name as assigned_name',
                'pc.identity_status as privacy_identity_status',
                'pc.deadline_rule as privacy_deadline_rule',
                'pc.received_at as privacy_received_at',
                'pc.original_due_at as privacy_original_due_at',
                'pc.due_at as privacy_due_at',
                'pc.completed_at as privacy_completed_at',
            ])
            ->paginate(40)
            ->withQueryString();

        return view('admin.support.index', compact('tickets', 'status', 'type', 'q', 'privacy'));
    }

    public function show(int $ticket): View
    {
        $ticketRow = DB::table('support_tickets as st')
            ->leftJoin('users as u', 'u.id', '=', 'st.user_id')
            ->leftJoin('users as a', 'a.id', '=', 'st.assigned_to')
            ->leftJoin('support_ticket_privacy_cases as pc', 'pc.ticket_id', '=', 'st.id')
            ->where('st.id', $ticket)
            ->first([
                'st.*',
                'u.name as user_name',
                'u.email as user_email',
                'a.name as assigned_name',
                'pc.id as privacy_case_id',
                'pc.identity_status as privacy_identity_status',
                'pc.deadline_rule as privacy_deadline_rule',
                'pc.received_at as privacy_received_at',
                'pc.original_due_at as privacy_original_due_at',
                'pc.due_at as privacy_due_at',
                'pc.extension_reason as privacy_extension_reason',
                'pc.completed_at as privacy_completed_at',
            ]);

        abort_unless($ticketRow, 404);

        $messages = DB::table('support_ticket_messages as stm')
            ->leftJoin('users as u', 'u.id', '=', 'stm.user_id')
            ->where('stm.ticket_id', $ticket)
            ->orderBy('stm.created_at')
            ->get(['stm.*', 'u.name as user_name']);

        $events = DB::table('support_ticket_events as ste')
            ->leftJoin('users as u', 'u.id', '=', 'ste.user_id')
            ->where('ste.ticket_id', $ticket)
            ->orderByDesc('ste.created_at')
            ->get(['ste.*', 'u.name as user_name']);

        $supportPermissionId = DB::table('permissions')->where('slug', 'support.view_all')->value('id');
        $assigneeIds = $supportPermissionId
            ? DB::table('user_roles as ur')
                ->join('role_permissions as rp', 'rp.role_id', '=', 'ur.role_id')
                ->where('rp.permission_id', $supportPermissionId)
                ->pluck('ur.user_id')
                ->unique()
            : collect();

        $ownerEmail = config('favon.owner_email');

        $assignees = DB::table('users')
            ->where(function ($query) use ($assigneeIds, $ownerEmail): void {
                if ($assigneeIds->isNotEmpty()) {
                    $query->whereIn('id', $assigneeIds);
                } else {
                    $query->whereRaw('1 = 0');
                }

                if (is_string($ownerEmail) && $ownerEmail !== '') {
                    $query->orWhere('email', $ownerEmail);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $publicEntries = DB::table('public_support_entries')
            ->orderByRaw("CASE type WHEN 'known_bug' THEN 1 WHEN 'suggested_feature' THEN 2 WHEN 'planned_feature' THEN 3 ELSE 4 END")
            ->orderBy('title')
            ->get(['id', 'type', 'status', 'title', 'is_public']);

        return view('admin.support.show', compact('ticketRow', 'messages', 'events', 'assignees', 'publicEntries'));
    }

    public function reply(Request $request, int $ticket, UserNotificationService $notifications): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:20000'],
        ]);

        $ticketRow = DB::table('support_tickets')->where('id', $ticket)->first();
        abort_unless($ticketRow, 404);
        abort_if($ticketRow->status === 'closed', 422, __('admin.support.errors.closed_reply'));

        $now = now();

        DB::table('support_ticket_messages')->insert([
            'ticket_id' => $ticket,
            'user_id' => $request->user()->id,
            'message_type' => 'staff_reply',
            'message' => $validated['message'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $updates = ['updated_at' => $now];
        if (! $ticketRow->first_response_at) {
            $updates['first_response_at'] = $now;
        }
        if ($ticketRow->status === 'new') {
            $updates['status'] = 'open';
            $this->event($ticket, $request->user()->id, 'status_changed', 'new', 'open');
        }

        DB::table('support_tickets')->where('id', $ticket)->update($updates);

        if ($ticketRow->user_id) {
            $locale = $notifications->userLocale((int) $ticketRow->user_id);
            $notifications->createImmediate(
                (int) $ticketRow->user_id,
                'support_reply',
                __('notifications.support_reply_title', ['id' => $ticket], $locale),
                __('notifications.support_reply_message', [], $locale),
                route('support.my.show', $ticket),
                'normal',
                'message-circle',
            );
        }

        return back()->with('ui_toast', __('admin.support.status_reply_sent'));
    }

    public function internalNote(Request $request, int $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:20000'],
        ]);

        abort_unless(DB::table('support_tickets')->where('id', $ticket)->exists(), 404);

        DB::table('support_ticket_messages')->insert([
            'ticket_id' => $ticket,
            'user_id' => $request->user()->id,
            'message_type' => 'internal_note',
            'message' => $validated['message'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('support_tickets')->where('id', $ticket)->update(['updated_at' => now()]);

        return back()->with('ui_toast', __('admin.support.status_note_saved'));
    }

    public function update(Request $request, int $ticket, UserNotificationService $notifications, PermissionService $permissions): RedirectResponse
    {
        $ticketRow = DB::table('support_tickets')->where('id', $ticket)->first();
        abort_unless($ticketRow, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'priority' => ['required', Rule::in(self::PRIORITIES)],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'public_entry_id' => ['nullable', 'integer', 'exists:public_support_entries,id'],
            'privacy_identity_status' => ['nullable', Rule::in(['not_required', 'pending', 'confirmed'])],
            'privacy_due_at' => ['nullable', 'date'],
            'privacy_extension_reason' => ['nullable', 'string', 'max:5000'],
        ]);

        if ((string) ($ticketRow->assigned_to ?? '') !== (string) ($validated['assigned_to'] ?? '')
            && ! $permissions->can($request->user(), 'support.assign')) {
            abort(403);
        }

        $privacyCase = DB::table('support_ticket_privacy_cases')->where('ticket_id', $ticket)->first();

        if ($privacyCase && $privacyCase->deadline_rule === 'gdpr_data_subject_right') {
            $newDueAt = filled($validated['privacy_due_at'] ?? null)
                ? $this->localDateEndAsUtc((string) $validated['privacy_due_at'])
                : Carbon::parse((string) $privacyCase->due_at, 'UTC');
            $originalDueAt = Carbon::parse((string) $privacyCase->original_due_at, 'UTC');
            $maximumDueAt = $originalDueAt->copy()
                ->setTimezone(LocalTime::timezone())
                ->addMonthsNoOverflow(2)
                ->endOfDay()
                ->utc();

            if ($newDueAt->gt($maximumDueAt)) {
                return back()->withErrors(['privacy_due_at' => __('admin.support.privacy.maximum_extension')])->withInput();
            }

            if ($newDueAt->gt($originalDueAt) && blank($validated['privacy_extension_reason'] ?? null)) {
                return back()->withErrors(['privacy_extension_reason' => __('admin.support.privacy.extension_reason_required')])->withInput();
            }
        }

        $now = now();
        $updates = [
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'public_entry_id' => $validated['public_entry_id'] ?? null,
            'closed_at' => $validated['status'] === 'closed' ? ($ticketRow->closed_at ?: $now) : null,
            'updated_at' => $now,
        ];

        DB::transaction(function () use ($ticketRow, $validated, $updates, $request, $ticket): void {
            DB::table('support_tickets')->where('id', $ticket)->update($updates);

            $privacyCase = DB::table('support_ticket_privacy_cases')->where('ticket_id', $ticket)->first();
            if ($privacyCase) {
                $privacyUpdates = [
                    'identity_status' => $validated['privacy_identity_status'] ?? $privacyCase->identity_status,
                    'due_at' => $privacyCase->deadline_rule === 'gdpr_data_subject_right'
                        ? (filled($validated['privacy_due_at'] ?? null)
                            ? $this->localDateEndAsUtc((string) $validated['privacy_due_at'])
                            : Carbon::parse((string) $privacyCase->due_at, 'UTC'))
                        : (filled($validated['privacy_due_at'] ?? null)
                            ? $this->localDateEndAsUtc((string) $validated['privacy_due_at'])
                            : null),
                    'extension_reason' => filled($validated['privacy_extension_reason'] ?? null) ? $validated['privacy_extension_reason'] : null,
                    'completed_at' => in_array($validated['status'], ['resolved', 'closed'], true)
                        ? ($privacyCase->completed_at ?: now())
                        : null,
                    'updated_at' => now(),
                ];

                foreach (['identity_status', 'due_at', 'extension_reason'] as $field) {
                    $old = $privacyCase->{$field};
                    $new = $privacyUpdates[$field];
                    $oldComparable = $old instanceof \DateTimeInterface ? $old->format('Y-m-d H:i:s') : (string) ($old ?? '');
                    $newComparable = $new instanceof \DateTimeInterface ? $new->format('Y-m-d H:i:s') : (string) ($new ?? '');
                    if ($oldComparable !== $newComparable) {
                        $eventOld = $field === 'extension_reason' ? (filled($oldComparable) ? 'set' : null) : ($old === null ? null : $oldComparable);
                        $eventNew = $field === 'extension_reason' ? (filled($newComparable) ? 'set' : null) : ($new === null ? null : $newComparable);

                        $this->event(
                            $ticket,
                            $request->user()->id,
                            'privacy_'.$field.'_changed',
                            $eventOld,
                            $eventNew,
                        );
                    }
                }

                if ((string) ($privacyCase->completed_at ?? '') !== (string) ($privacyUpdates['completed_at'] ?? '')) {
                    $this->event(
                        $ticket,
                        $request->user()->id,
                        'privacy_completed_at_changed',
                        $privacyCase->completed_at ? (string) $privacyCase->completed_at : null,
                        $privacyUpdates['completed_at'] ? (string) $privacyUpdates['completed_at'] : null,
                    );
                }

                DB::table('support_ticket_privacy_cases')->where('ticket_id', $ticket)->update($privacyUpdates);
            }

            foreach (['status', 'priority', 'assigned_to', 'public_entry_id'] as $field) {
                $old = $ticketRow->{$field};
                $new = $validated[$field] ?? null;
                if ((string) $old === (string) $new) {
                    continue;
                }

                $this->event(
                    $ticket,
                    $request->user()->id,
                    $field.'_changed',
                    $old === null ? null : (string) $old,
                    $new === null ? null : (string) $new,
                );
            }
        });

        if ($ticketRow->user_id && $ticketRow->status !== $validated['status']) {
            $locale = $notifications->userLocale((int) $ticketRow->user_id);
            $notifications->createImmediate(
                (int) $ticketRow->user_id,
                'support_status',
                __('notifications.support_status_title', ['id' => $ticket], $locale),
                __('notifications.support_status_message', [
                    'status' => __('notifications.support_statuses.'.$validated['status'], [], $locale),
                ], $locale),
                route('support.my.show', $ticket),
                'normal',
                'message-circle',
            );
        }

        return back()->with('ui_toast', __('admin.support.status_ticket_updated'));
    }

    private function localDateEndAsUtc(string $date): Carbon
    {
        return Carbon::parse($date, LocalTime::timezone())
            ->endOfDay()
            ->utc();
    }

    private function event(int $ticketId, ?int $userId, string $type, ?string $old, ?string $new): void
    {
        DB::table('support_ticket_events')->insert([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'event_type' => $type,
            'old_value' => $old,
            'new_value' => $new,
            'metadata' => null,
            'created_at' => now(),
        ]);
    }

}
