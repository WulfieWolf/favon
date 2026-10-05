<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChangeRequestModerationService
{
    public function __construct(
        private PermissionService $permissions,
        private ChangeRequestApplyService $applyService,
        private UserNotificationService $notifications,
    ) {
    }

    public function approve(User $reviewer, int $changeRequestId, ?string $comment = null): void
    {
        $context = $this->notificationContext($changeRequestId);
        $this->applyService->approveAndApply($reviewer, $changeRequestId, $comment);
        $this->queueDecisionNotification($context, 'approved', $comment);

        if ($context?->place_id) {
            $this->notifications->queueFavoritePlaceChange(
                (int) $context->place_id,
                array_values(array_filter([
                    (int) ($context->submitted_by ?? 0),
                    (int) $reviewer->id,
                ])),
                'approved_suggestion',
            );
        }
    }

    public function reject(User $reviewer, int $changeRequestId, string $comment): void
    {
        $context = $this->notificationContext($changeRequestId);
        $this->review($reviewer, $changeRequestId, 'rejected', $comment);
        $this->queueDecisionNotification($context, 'rejected', $comment);
    }

    private function notificationContext(int $changeRequestId): ?object
    {
        $context = DB::table('change_requests as cr')
            ->join('places as p', 'p.id', '=', 'cr.place_id')
            ->where('cr.id', $changeRequestId)
            ->first([
                'cr.id',
                'cr.submitted_by',
                'cr.place_id',
                'cr.group_uuid',
                'p.name as place_name',
                'p.slug as place_slug',
                'p.publication_status',
            ]);

        if (! $context) {
            return null;
        }

        $query = DB::table('change_requests')->where('place_id', $context->place_id);
        if ($context->group_uuid) {
            $query->where('group_uuid', $context->group_uuid);
        } else {
            $query->where('id', $context->id);
        }

        $context->suggestion_count = max(1, (int) $query->count());

        return $context;
    }

    private function queueDecisionNotification(?object $context, string $decision, ?string $comment): void
    {
        if (! $context?->submitted_by) {
            return;
        }

        if (! $this->notifications->preferenceEnabled((int) $context->submitted_by, 'moderation_decisions')) {
            return;
        }

        $url = $decision === 'rejected' && $context->publication_status !== 'published'
            ? route('places.drafts.edit', $context->place_id)
            : route('places.show', $context->place_slug);

        $this->notifications->upsertModerationDecision(
            (int) $context->submitted_by,
            (int) $context->place_id,
            (string) $context->place_name,
            $url,
            $decision,
            (int) ($context->suggestion_count ?? 1),
            $comment,
        );
    }

    private function review(User $reviewer, int $changeRequestId, string $status, ?string $comment): void
    {
        if (! $this->permissions->can($reviewer, 'places.approve_changes')) {
            throw new RuntimeException('Missing permission places.approve_changes.');
        }

        DB::transaction(function () use ($reviewer, $changeRequestId, $status, $comment): void {
            $anchor = DB::table('change_requests')
                ->where('id', $changeRequestId)
                ->lockForUpdate()
                ->first();

            if (! $anchor) {
                throw new RuntimeException('Change request not found.');
            }

            $query = DB::table('change_requests')->where('place_id', $anchor->place_id);

            if ($anchor->group_uuid) {
                $query->where('group_uuid', $anchor->group_uuid);
            } else {
                $query->where('id', $anchor->id);
            }

            $requests = $query
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($requests->isEmpty()) {
                throw new RuntimeException('No change requests found for this review group.');
            }

            if ($requests->contains(fn ($request) => $request->status !== 'pending')) {
                throw new RuntimeException('At least one request in this group has already been reviewed.');
            }

            $reviewedAt = now();

            foreach ($requests as $request) {
                DB::table('change_requests')
                    ->where('id', $request->id)
                    ->update([
                        'status' => $status,
                        'reviewed_by' => $reviewer->id,
                        'reviewed_at' => $reviewedAt,
                        'moderator_comment' => $comment,
                        'updated_at' => $reviewedAt,
                    ]);

                DB::table('audit_logs')->insert([
                    'user_id' => $reviewer->id,
                    'entity_type' => 'change_request',
                    'entity_id' => $request->id,
                    'action' => 'change_request_rejected',
                    'source' => 'admin',
                    'old_values' => json_encode([
                        'status' => $request->status,
                        'reviewed_by' => $request->reviewed_by,
                        'reviewed_at' => $request->reviewed_at,
                        'moderator_comment' => $request->moderator_comment,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'new_values' => json_encode([
                        'status' => $status,
                        'reviewed_by' => $reviewer->id,
                        'reviewed_at' => $reviewedAt->toIso8601String(),
                        'moderator_comment' => $comment,
                        'group_uuid' => $request->group_uuid,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'internal_comment' => null,
                    'created_at' => $reviewedAt,
                ]);
            }
        });
    }
}
