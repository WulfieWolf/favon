<?php

namespace App\Http\Controllers;

use App\Services\SupportContextService;
use App\Services\UsageAnalyticsService;
use App\Support\LocalTime;
use App\Services\UserNotificationService;
use App\Services\LocalizedSupportContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    private const REGULAR_TYPES = ['bug', 'feature_request', 'improvement', 'data_issue', 'other'];
    private const PRIVACY_TYPES = ['privacy_access', 'privacy_deletion', 'privacy_rectification', 'privacy_objection', 'privacy_restriction', 'privacy_portability', 'privacy_other', 'legal_other'];
    private const DATA_SUBJECT_RIGHT_TYPES = ['privacy_access', 'privacy_deletion', 'privacy_rectification', 'privacy_objection', 'privacy_restriction', 'privacy_portability'];
    private const TYPES = ['bug', 'feature_request', 'improvement', 'data_issue', 'other', 'privacy_access', 'privacy_deletion', 'privacy_rectification', 'privacy_objection', 'privacy_restriction', 'privacy_portability', 'privacy_other', 'legal_other'];

    public function create(Request $request, SupportContextService $contexts, LocalizedSupportContentService $content): View
    {
        return $this->createView($request, $contexts, $content, false);
    }

    public function privacyLegal(Request $request, SupportContextService $contexts, LocalizedSupportContentService $content): View
    {
        return $this->createView($request, $contexts, $content, true);
    }

    private function createView(Request $request, SupportContextService $contexts, LocalizedSupportContentService $content, bool $legalEntry): View
    {
        $type = (string) $request->query('type', '');
        $availableTypes = $legalEntry ? self::PRIVACY_TYPES : self::REGULAR_TYPES;
        if (! in_array($type, $availableTypes, true)) {
            $type = '';
        }

        $contextKey = $contexts->sanitizeContext($request->query('context'));
        $module = $contexts->sanitizeModule($request->query('module'));
        $routeName = is_string($request->query('route')) ? mb_substr($request->query('route'), 0, 160) : null;
        $sourceUrl = is_string($request->query('source')) ? mb_substr($request->query('source'), 0, 2000) : null;

        $publicTypes = match ($type) {
            'bug' => ['known_bug'],
            'feature_request' => ['suggested_feature', 'planned_feature'],
            default => [],
        };

        $relatedEntries = $publicTypes !== []
            ? $content->entries()
                ->where('pse.is_public', true)
                ->whereIn('pse.type', $publicTypes)
                ->when($contextKey, function ($query) use ($contextKey): void {
                    $query->where(function ($context) use ($contextKey): void {
                        $context->whereNull('pse.context_key')->orWhere('pse.context_key', $contextKey);
                    });
                })
                ->whereNotIn('pse.status', ['closed'])
                ->orderByRaw("CASE pse.status WHEN 'in_progress' THEN 1 WHEN 'confirmed' THEN 2 WHEN 'planned' THEN 3 WHEN 'suggested' THEN 4 WHEN 'resolved' THEN 5 WHEN 'not_planned' THEN 6 ELSE 7 END")
                ->orderByDesc('pse.updated_at')
                ->limit(12)
                ->get()
            : collect();

        return view('support.create', compact(
            'type',
            'contextKey',
            'module',
            'routeName',
            'sourceUrl',
            'relatedEntries',
            'availableTypes',
            'legalEntry',
        ));
    }

    public function store(
        Request $request,
        SupportContextService $contexts,
        UsageAnalyticsService $analytics,
    ): RedirectResponse
    {
        $isGuest = ! $request->user();

        $validated = $request->validate([
            'type' => ['required', Rule::in(self::TYPES)],
            'description' => ['required', 'string', 'min:5', 'max:20000'],
            'subject' => ['nullable', 'string', 'max:255'],
            'guest_name' => [$isGuest ? 'required' : 'nullable', 'string', 'max:255'],
            'guest_email' => [$isGuest ? 'required' : 'nullable', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:80'],
            'context_key' => ['nullable', 'string', 'max:100'],
            'module' => ['nullable', 'string', 'max:100'],
            'route_name' => ['nullable', 'string', 'max:160'],
            'source_url' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'max:0'],
        ]);

        $sourceUrl = $validated['source_url'] ?? null;
        if ($sourceUrl) {
            $sourceHost = parse_url($sourceUrl, PHP_URL_HOST);
            if ($sourceHost && ! hash_equals((string) $request->getHost(), (string) $sourceHost)) {
                $sourceUrl = null;
            }
        }

        $user = $request->user();
        $now = now();

        $ticketId = DB::table('support_tickets')->insertGetId([
            'user_id' => $user?->id,
            'guest_name' => $user ? null : ($validated['guest_name'] ?? null),
            'guest_email' => $user ? null : ($validated['guest_email'] ?? null),
            'guest_phone' => $user ? null : ($validated['guest_phone'] ?? null),
            'type' => $validated['type'],
            'status' => 'new',
            'priority' => 'normal',
            'subject' => $validated['subject'] ?? null,
            'description' => $validated['description'],
            'context_key' => $contexts->sanitizeContext($validated['context_key'] ?? null),
            'module' => $contexts->sanitizeModule($validated['module'] ?? null),
            'route_name' => $validated['route_name'] ?? null,
            'source_url' => $sourceUrl,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
            'public_entry_id' => null,
            'assigned_to' => null,
            'first_response_at' => null,
            'closed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('support_ticket_events')->insert([
            'ticket_id' => $ticketId,
            'user_id' => $user?->id,
            'event_type' => 'created',
            'old_value' => null,
            'new_value' => 'new',
            'metadata' => null,
            'created_at' => $now,
        ]);

        if (in_array($validated['type'], self::PRIVACY_TYPES, true)) {
            $usesGdprDeadline = in_array($validated['type'], self::DATA_SUBJECT_RIGHT_TYPES, true);
            $dueAt = $usesGdprDeadline
                ? $now->copy()->setTimezone(LocalTime::timezone())->addMonthNoOverflow()->endOfDay()->utc()
                : null;

            DB::table('support_ticket_privacy_cases')->insert([
                'ticket_id' => $ticketId,
                'identity_status' => $user ? 'not_required' : 'pending',
                'deadline_rule' => $usesGdprDeadline ? 'gdpr_data_subject_right' : 'manual',
                'received_at' => $now,
                'original_due_at' => $dueAt,
                'due_at' => $dueAt,
                'extension_reason' => null,
                'completed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('support_ticket_events')->insert([
                'ticket_id' => $ticketId,
                'user_id' => $user?->id,
                'event_type' => 'privacy_case_created',
                'old_value' => null,
                'new_value' => $dueAt?->toDateString(),
                'metadata' => json_encode([
                    'identity_status' => $user ? 'not_required' : 'pending',
                    'deadline_rule' => $usesGdprDeadline ? 'gdpr_data_subject_right' : 'manual',
                ]),
                'created_at' => $now,
            ]);
        }

        $analytics->track(
            $request,
            'support_submitted',
            'support',
            'support_ticket',
            (int) $ticketId,
            ['type' => $validated['type']],
        );

        if ($user) {
            return redirect()->route('support.my.show', $ticketId)
                ->with('ui_dialog', [
                    'variant' => 'success',
                    'message' => __('support.flash.submitted'),
                ]);
        }

        return redirect()->route('support.thanks')->with('ticket_id', $ticketId);
    }

    public function thanks(): View
    {
        return view('support.thanks');
    }

    public function myIndex(Request $request): View
    {
        $tickets = DB::table('support_tickets')
            ->where('user_id', $request->user()->id)
            ->orderByRaw("status = 'closed'")
            ->orderByDesc('updated_at')
            ->paginate(30);

        return view('support.my-index', compact('tickets'));
    }

    public function myShow(Request $request, int $ticket, LocalizedSupportContentService $content): View
    {
        $ticketRow = DB::table('support_tickets')
            ->where('id', $ticket)
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless($ticketRow, 404);

        $messages = DB::table('support_ticket_messages as stm')
            ->leftJoin('users as u', 'u.id', '=', 'stm.user_id')
            ->where('stm.ticket_id', $ticket)
            ->where('stm.message_type', '!=', 'internal_note')
            ->orderBy('stm.created_at')
            ->get(['stm.*', 'u.name as user_name']);

        $publicEntry = $ticketRow->public_entry_id
            ? $content->entries()->where('pse.id', $ticketRow->public_entry_id)->where('pse.is_public', true)->first()
            : null;

        return view('support.my-show', compact('ticketRow', 'messages', 'publicEntry'));
    }

    public function reply(Request $request, int $ticket, UserNotificationService $notifications): RedirectResponse
    {
        $ticketRow = DB::table('support_tickets')
            ->where('id', $ticket)
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless($ticketRow, 404);
        abort_if($ticketRow->status === 'closed', 422, __('support.errors.closed_reply'));

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:20000'],
        ]);

        $now = now();

        DB::transaction(function () use ($ticketRow, $request, $validated, $now): void {
            DB::table('support_ticket_messages')->insert([
                'ticket_id' => $ticketRow->id,
                'user_id' => $request->user()->id,
                'message_type' => 'user_message',
                'message' => $validated['message'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $updates = ['updated_at' => $now];

            if (in_array($ticketRow->status, ['waiting_for_user', 'resolved'], true)) {
                $updates['status'] = 'open';
                $updates['closed_at'] = null;

                DB::table('support_ticket_events')->insert([
                    'ticket_id' => $ticketRow->id,
                    'user_id' => $request->user()->id,
                    'event_type' => 'status_changed',
                    'old_value' => $ticketRow->status,
                    'new_value' => 'open',
                    'metadata' => null,
                    'created_at' => $now,
                ]);
            }

            DB::table('support_tickets')->where('id', $ticketRow->id)->update($updates);
        });

        return back()->with('ui_toast', __('support.flash.reply_sent'));
    }
}
