<?php

namespace App\Http\Controllers;

use App\Services\FeatureWorkflowService;
use App\Services\PermissionService;
use App\Services\PlaceHistoryService;
use App\Services\UsageAnalyticsService;
use App\Services\XpService;
use App\Services\BadgeService;
use App\Services\UserNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PlaceFeatureController extends Controller
{
    public function update(
        Request $request,
        string $slug,
        int $feature,
        FeatureWorkflowService $workflows,
        PermissionService $permissions,
        UserNotificationService $notifications,
    ): RedirectResponse {
        $place = DB::table('places')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where('publication_status', 'published')
            ->first(['id', 'name', 'slug']);

        abort_unless($place, 404);

        $workflow = $workflows->workflowsForPlace((int) $place->id)
            ->firstWhere('feature_id', $feature);

        abort_unless($workflow, 404);

        $validated = $workflows->validate($request, collect([$workflow]));
        $data = $workflows->normalizeFeatureData($workflow, $validated);

        if ($permissions->can($request->user(), 'places.edit')) {
            $changed = $this->saveDirect((int) $place->id, $request->user()->id, $workflow, $data);

            if ($changed) {
                $this->recordDirectFeatureHistory((int) $place->id, $request->user(), [$changed]);
                $this->recordDirectFeatureRewards((int) $place->id, (string) $place->name, (int) $request->user()->id, $changed);
                $notifications->queueFavoritePlaceChange(
                    (int) $place->id,
                    [(int) $request->user()->id],
                    'direct_feature_edit',
                );

                app(UsageAnalyticsService::class)->track(
                    $request,
                    'feature_edit_submitted',
                    'features',
                    'place',
                    (int) $place->id,
                    ['direct' => true, 'count' => 1],
                );
            }

            return back()->with('ui_toast', __('feature_workflow.status_direct_saved'));
        }

        abort_unless($permissions->can($request->user(), 'places.suggest'), 403);

        try {
            $requestId = $this->createSuggestion((int) $place->id, $request->user()->id, $workflow, $data);
        } catch (RuntimeException $exception) {
            report($exception);

            return back()->withErrors(['feature' => __('feature_workflow.errors.unavailable')]);
        }

        if ($requestId) {
            app(UsageAnalyticsService::class)->track(
                $request,
                'feature_edit_submitted',
                'features',
                'place',
                (int) $place->id,
                ['direct' => false, 'count' => 1],
            );
        }

        return back()->with('ui_toast', $requestId
            ? __('feature_workflow.status_suggestion_submitted', ['id' => $requestId])
            : __('feature_workflow.status_unchanged'));
    }

    public function updateCategory(
        Request $request,
        string $slug,
        int $category,
        FeatureWorkflowService $workflows,
        PermissionService $permissions,
        UserNotificationService $notifications,
    ): RedirectResponse {
        $place = DB::table('places')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where('publication_status', 'published')
            ->first(['id', 'name', 'slug']);

        abort_unless($place, 404);

        $categoryWorkflows = $workflows->workflowsForPlace((int) $place->id)
            ->where('category_id', $category)
            ->values();

        abort_if($categoryWorkflows->isEmpty(), 404);

        $submittedIds = collect(array_keys((array) $request->input('features', [])))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($submittedIds->isEmpty()) {
            return back()->with('ui_dialog', [
                'variant' => 'success',
                'message' => trans_choice(
                    $permissions->can($request->user(), 'places.edit')
                        ? 'feature_workflow.category_direct_saved'
                        : 'feature_workflow.category_suggestions_submitted',
                    0,
                    ['count' => 0],
                ),
            ]);
        }

        abort_if(
            $submittedIds->contains(fn (int $id) => ! $categoryWorkflows->contains('feature_id', $id)),
            422,
        );

        $selectedWorkflows = $categoryWorkflows
            ->filter(fn ($workflow) => $submittedIds->contains((int) $workflow->feature_id))
            ->values();

        $validated = $workflows->validate($request, $selectedWorkflows);
        $changedCount = 0;

        if ($permissions->can($request->user(), 'places.edit')) {
            $historyChanges = [];
            foreach ($selectedWorkflows as $workflow) {
                $data = $workflows->normalizeFeatureData($workflow, $validated);
                if ($change = $this->saveDirect((int) $place->id, (int) $request->user()->id, $workflow, $data)) {
                    $changedCount++;
                    $historyChanges[] = $change;
                $this->recordDirectFeatureRewards((int) $place->id, (string) $place->name, (int) $request->user()->id, $change);
                }
            }

            if ($changedCount > 0) {
                $this->recordDirectFeatureHistory((int) $place->id, $request->user(), $historyChanges);
                $notifications->queueFavoritePlaceChange(
                    (int) $place->id,
                    [(int) $request->user()->id],
                    'direct_feature_edit',
                );

                app(UsageAnalyticsService::class)->track(
                    $request,
                    'feature_edit_submitted',
                    'features',
                    'place',
                    (int) $place->id,
                    ['direct' => true, 'count' => $changedCount],
                );
            }

            return back()->with('ui_dialog', [
                'variant' => 'success',
                'message' => trans_choice(
                    'feature_workflow.category_direct_saved',
                    $changedCount,
                    ['count' => $changedCount],
                ),
            ]);
        }

        abort_unless($permissions->can($request->user(), 'places.suggest'), 403);

        try {
            foreach ($selectedWorkflows as $workflow) {
                $data = $workflows->normalizeFeatureData($workflow, $validated);
                if ($this->createSuggestion((int) $place->id, (int) $request->user()->id, $workflow, $data)) {
                    $changedCount++;
                }
            }
        } catch (RuntimeException $exception) {
            report($exception);

            return back()->withErrors(['feature' => __('feature_workflow.errors.unavailable')]);
        }

        if ($changedCount > 0) {
            app(UsageAnalyticsService::class)->track(
                $request,
                'feature_edit_submitted',
                'features',
                'place',
                (int) $place->id,
                ['direct' => false, 'count' => $changedCount],
            );
        }

        return back()->with('ui_dialog', [
            'variant' => 'success',
            'message' => trans_choice(
                'feature_workflow.category_suggestions_submitted',
                $changedCount,
                ['count' => $changedCount],
            ),
        ]);
    }

    private function saveDirect(int $placeId, int $userId, object $workflow, array $data): array|false
    {
        return DB::transaction(function () use ($placeId, $userId, $workflow, $data): array|false {
            $now = now();
            $locale = app()->getLocale();

            $existing = DB::table('place_features')
                ->where('place_id', $placeId)
                ->where('feature_id', $workflow->feature_id)
                ->where('is_active', true)
                ->whereNull('valid_until')
                ->lockForUpdate()
                ->first();

            $oldComment = $existing
                ? DB::table('place_feature_notes')
                    ->where('place_feature_id', $existing->id)
                    ->where('locale', $locale)
                    ->where('is_active', true)
                    ->value('note')
                : null;

            $oldMetadata = $existing && $existing->metadata
                ? (json_decode($existing->metadata, true) ?: [])
                : [];

            $oldValues = $existing ? [
                'record_id' => $existing->id,
                'status' => $existing->status ?? 'unknown',
                'metadata' => $oldMetadata,
                'comment' => $oldComment,
            ] : null;

            if (
                $existing
                && ($existing->status ?? 'unknown') === $data['status']
                && $oldMetadata == $data['metadata']
                && trim((string) $oldComment) === $data['comment']
            ) {
                return false;
            }

            if (! $existing && $data['status'] === 'unknown' && $data['metadata'] === [] && $data['comment'] === '') {
                return false;
            }

            if ($existing) {
                DB::table('place_features')->where('id', $existing->id)->update([
                    'is_active' => false,
                    'valid_until' => $now,
                    'updated_at' => $now,
                ]);

                $values = (array) $existing;
                unset($values['id']);
                $values['status'] = $data['status'];
                $values['metadata'] = $data['metadata'] === []
                    ? null
                    : json_encode($data['metadata'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $values['is_active'] = true;
                $values['valid_from'] = $now;
                $values['valid_until'] = null;
                $values['created_at'] = $now;
                $values['updated_at'] = $now;

                $newId = DB::table('place_features')->insertGetId($values);

                $notes = DB::table('place_feature_notes')
                    ->where('place_feature_id', $existing->id)
                    ->where('is_active', true)
                    ->get();

                foreach ($notes as $note) {
                    if ($note->locale === $locale) {
                        continue;
                    }

                    DB::table('place_feature_notes')->insert([
                        'place_feature_id' => $newId,
                        'locale' => $note->locale,
                        'note' => $note->note,
                        'is_active' => true,
                        'internal_comment' => $note->internal_comment,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            } else {
                $newId = DB::table('place_features')->insertGetId([
                    'place_id' => $placeId,
                    'feature_id' => $workflow->feature_id,
                    'feature_option_id' => null,
                    'value_number' => null,
                    'value_text' => null,
                    'unit_key' => null,
                    'unit_id' => null,
                    'rate_quantity' => null,
                    'rate_unit_id' => null,
                    'status' => $data['status'],
                    'metadata' => $data['metadata'] === []
                        ? null
                        : json_encode($data['metadata'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'is_active' => true,
                    'internal_comment' => 'Direkt administrativ gepflegt.',
                    'valid_from' => $now,
                    'valid_until' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if ($data['comment'] !== '') {
                DB::table('place_feature_notes')->insert([
                    'place_feature_id' => $newId,
                    'locale' => $locale,
                    'note' => $data['comment'],
                    'is_active' => true,
                    'internal_comment' => 'Direkt administrativ gepflegt.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('audit_logs')->insert([
                'user_id' => $userId,
                'entity_type' => 'place_feature',
                'entity_id' => $newId,
                'action' => 'place_feature_direct_edit',
                'source' => 'admin',
                'old_values' => $oldValues === null ? null : json_encode($oldValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'new_values' => json_encode([
                    'record_id' => $newId,
                    'feature_id' => $workflow->feature_id,
                    'status' => $data['status'],
                    'metadata' => $data['metadata'],
                    'comment' => $data['comment'] !== '' ? $data['comment'] : null,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'internal_comment' => null,
                'created_at' => $now,
            ]);

            return [
                'record_id' => (int) $newId,
                'feature_id' => (int) $workflow->feature_id,
                'label' => (string) $workflow->feature_label,
                'old' => [
                    'status' => $oldValues['status'] ?? 'unknown',
                    'metadata' => $oldValues['metadata'] ?? [],
                ],
                'new' => [
                    'status' => $data['status'],
                    'metadata' => $data['metadata'],
                ],
            ];
        });
    }

    private function recordDirectFeatureRewards(int $placeId, string $placeName, int $userId, array $change): void
    {
        $recordId = (int) ($change['record_id'] ?? 0);
        $featureId = (int) ($change['feature_id'] ?? 0);

        if ($recordId < 1 || $featureId < 1) {
            return;
        }

        app(XpService::class)->awardPlaceField(
            $userId,
            $placeId,
            'feature:'.$featureId,
            (int) config('xp.place_info.default_xp', 1),
            'Merkmal ergänzt oder aktualisiert',
            'place_feature',
            $recordId,
        );

        $request = (object) [
            'submitted_by' => $userId,
            'place_id' => $placeId,
            'target_table' => 'place_features',
            'target_field' => 'status',
            'proposed_value' => null,
        ];

        app(BadgeService::class)->recordApprovedPlaceChange($request, $recordId, $placeName);
    }

    private function recordDirectFeatureHistory(int $placeId, \App\Models\User $user, array $changes): void
    {
        $presenter = app(\App\Services\PlaceHistoryPresenter::class);
        $lines = collect($changes)->map(fn (array $change) =>
            $change['label'].': '.$presenter->formatFeatureState($change['old']).' → '.$presenter->formatFeatureState($change['new'])
        )->all();

        app(PlaceHistoryService::class)->addUser(
            $placeId,
            $user,
            'place_data_changed',
            __('place_profile.history.actions.place_data_changed'),
            [
                'submitted_at' => now()->toIso8601String(),
                'approved_at' => now()->toIso8601String(),
                'changes' => $lines,
            ],
        );
    }

    private function createSuggestion(int $placeId, int $userId, object $workflow, array $data): ?int
    {
        return DB::transaction(function () use ($placeId, $userId, $workflow, $data): ?int {
            $now = now();
            $locale = app()->getLocale();

            $existing = DB::table('place_features')
                ->where('place_id', $placeId)
                ->where('feature_id', $workflow->feature_id)
                ->where('is_active', true)
                ->whereNull('valid_until')
                ->lockForUpdate()
                ->first();

            $currentMetadata = $existing && $existing->metadata
                ? (json_decode($existing->metadata, true) ?: [])
                : [];
            $currentComment = $existing
                ? DB::table('place_feature_notes')
                    ->where('place_feature_id', $existing->id)
                    ->where('locale', $locale)
                    ->where('is_active', true)
                    ->value('note')
                : null;

            $pendingGroups = $this->pendingSuggestionGroupsForFeature(
                $placeId,
                $userId,
                (int) $workflow->feature_id,
                $existing?->id ? (int) $existing->id : null,
            );

            if (! $existing && $data['status'] === 'unknown' && $data['metadata'] === [] && $data['comment'] === '') {
                if ($pendingGroups->isNotEmpty()) {
                    $this->supersedePendingSuggestionGroups($pendingGroups, $userId, $placeId, (int) $workflow->feature_id, $now);
                }

                return null;
            }

            $operation = $existing ? 'update' : 'create';
            $fields = [];

            if (! $existing) {
                $fields['feature_id'] = [null, (int) $workflow->feature_id];
            }

            if (! $existing || ($existing->status ?? 'unknown') !== $data['status']) {
                $fields['status'] = [$existing->status ?? null, $data['status']];
            }

            if (! $existing || $currentMetadata != $data['metadata']) {
                $fields['metadata'] = [
                    $existing?->metadata,
                    $data['metadata'] === []
                        ? null
                        : json_encode($data['metadata'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
            }

            if (! $existing || trim((string) $currentComment) !== $data['comment']) {
                $fields['comment'] = [
                    ['locale' => $locale, 'note' => $currentComment],
                    ['locale' => $locale, 'note' => $data['comment'] !== '' ? $data['comment'] : null],
                ];
            }

            if ($pendingGroups->isNotEmpty()) {
                $this->supersedePendingSuggestionGroups($pendingGroups, $userId, $placeId, (int) $workflow->feature_id, $now);
            }

            if ($fields === []) {
                return null;
            }

            $definitions = DB::table('suggestable_fields')
                ->where('target_table', 'place_features')
                ->whereIn('target_field', array_keys($fields))
                ->where('is_suggestable', true)
                ->where('is_active', true)
                ->get()
                ->keyBy('target_field');

            foreach (array_keys($fields) as $field) {
                $definition = $definitions->get($field);
                if (! $definition) {
                    throw new RuntimeException("Missing suggestable field place_features.{$field}. Run SuggestableFieldSeeder.");
                }

                $allowed = $operation === 'create' ? $definition->allow_create : $definition->allow_update;
                if (! $allowed) {
                    throw new RuntimeException("Operation {$operation} is not enabled for place_features.{$field}.");
                }
            }

            $groupUuid = (string) Str::uuid();
            $firstId = null;

            foreach ($fields as $field => [$original, $proposed]) {
                $id = DB::table('change_requests')->insertGetId([
                    'group_uuid' => $groupUuid,
                    'place_id' => $placeId,
                    'suggestable_field_id' => $definitions[$field]->id,
                    'target_record_id' => $existing?->id,
                    'operation' => $operation,
                    'original_value' => json_encode($original, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'proposed_value' => json_encode($proposed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'status' => 'pending',
                    'submitted_by' => $userId,
                    'submitted_at' => $now,
                    'user_comment' => 'Merkmalsänderung direkt aus dem Platzprofil vorgeschlagen.',
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'moderator_comment' => null,
                    'result_record_id' => null,
                    'applied_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $firstId ??= $id;
            }

            DB::table('audit_logs')->insert([
                'user_id' => $userId,
                'entity_type' => 'place',
                'entity_id' => $placeId,
                'action' => 'place_feature_change_suggested',
                'source' => 'user',
                'old_values' => null,
                'new_values' => json_encode([
                    'feature_id' => $workflow->feature_id,
                    'group_uuid' => $groupUuid,
                    'change_request_id' => $firstId,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'internal_comment' => null,
                'created_at' => $now,
            ]);

            return $firstId;
        });
    }

    private function pendingSuggestionGroupsForFeature(
        int $placeId,
        int $userId,
        int $featureId,
        ?int $targetRecordId,
    ): \Illuminate\Support\Collection {
        $rows = DB::table('change_requests as cr')
            ->join('suggestable_fields as sf', 'sf.id', '=', 'cr.suggestable_field_id')
            ->where('cr.place_id', $placeId)
            ->where('cr.submitted_by', $userId)
            ->where('cr.status', 'pending')
            ->where('sf.target_table', 'place_features')
            ->get([
                'cr.id',
                'cr.group_uuid',
                'cr.target_record_id',
                'cr.proposed_value',
                'sf.target_field',
            ]);

        $recordFeatureIds = DB::table('place_features')
            ->whereIn('id', $rows->pluck('target_record_id')->filter()->unique())
            ->pluck('feature_id', 'id');

        return $rows
            ->groupBy(fn ($row) => $row->group_uuid ?: 'request-'.$row->id)
            ->filter(function ($group) use ($featureId, $targetRecordId, $recordFeatureIds) {
                if ($targetRecordId !== null && $group->contains(
                    fn ($row) => (int) ($row->target_record_id ?? 0) === $targetRecordId
                )) {
                    return true;
                }

                if ($group->contains(function ($row) use ($featureId, $recordFeatureIds) {
                    $recordId = (int) ($row->target_record_id ?? 0);

                    return $recordId > 0 && (int) ($recordFeatureIds[$recordId] ?? 0) === $featureId;
                })) {
                    return true;
                }

                $featureRequest = $group->firstWhere('target_field', 'feature_id');
                if (! $featureRequest) {
                    return false;
                }

                return (int) json_decode($featureRequest->proposed_value, true) === $featureId;
            })
            ->values();
    }

    private function supersedePendingSuggestionGroups(
        \Illuminate\Support\Collection $groups,
        int $userId,
        int $placeId,
        int $featureId,
        $now,
    ): void {
        $requestIds = $groups
            ->flatten(1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($requestIds->isEmpty()) {
            return;
        }

        DB::table('change_requests')
            ->whereIn('id', $requestIds)
            ->where('status', 'pending')
            ->update([
                'status' => 'superseded',
                'reviewed_at' => $now,
                'moderator_comment' => 'Durch einen neueren Vorschlag desselben Nutzers ersetzt.',
                'updated_at' => $now,
            ]);

        DB::table('audit_logs')->insert([
            'user_id' => $userId,
            'entity_type' => 'place',
            'entity_id' => $placeId,
            'action' => 'place_feature_pending_suggestion_superseded',
            'source' => 'user',
            'old_values' => json_encode([
                'feature_id' => $featureId,
                'change_request_ids' => $requestIds->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'new_values' => null,
            'internal_comment' => null,
            'created_at' => $now,
        ]);
    }
}
