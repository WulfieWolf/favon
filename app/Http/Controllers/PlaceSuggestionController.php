<?php

namespace App\Http\Controllers;

use App\Services\AbuseProtectionService;
use App\Services\BadgeService;
use App\Services\CountryService;
use App\Services\FeatureWorkflowService;
use App\Services\PermissionService;
use App\Services\PlaceTypeFeatureService;
use App\Services\PlaceTombstoneService;
use App\Services\UsageAnalyticsService;
use App\Services\XpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class PlaceSuggestionController extends Controller
{
    public function create(Request $request): View
    {
        return view('places.suggest', [
            'placeTypes' => $this->placeTypes(),
            'canPublishDirectly' => $this->canPublishDirectly($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $countryCodes = array_keys(app(CountryService::class)->all('en'));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'place_type_id' => ['required', 'integer', 'exists:place_types,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'country_code' => ['nullable', 'string', 'size:2', Rule::in($countryCodes)],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'house_number' => ['nullable', 'string', 'max:32'],
            'address_addition' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'intent' => ['required', 'in:draft,submit'],
        ], [
            'name.required' => __('places.suggest.validation.name_required'),
            'name.max' => __('places.suggest.validation.name_max'),
            'place_type_id.required' => __('places.suggest.validation.place_type_required'),
            'place_type_id.integer' => __('places.suggest.validation.place_type_invalid'),
            'place_type_id.exists' => __('places.suggest.validation.place_type_invalid'),
            'latitude.required' => __('places.suggest.validation.latitude_required'),
            'latitude.numeric' => __('places.suggest.validation.latitude_invalid'),
            'latitude.between' => __('places.suggest.validation.latitude_invalid'),
            'longitude.required' => __('places.suggest.validation.longitude_required'),
            'longitude.numeric' => __('places.suggest.validation.longitude_invalid'),
            'longitude.between' => __('places.suggest.validation.longitude_invalid'),
            'intent.required' => __('places.suggest.validation.intent_invalid'),
            'intent.in' => __('places.suggest.validation.intent_invalid'),
        ]);

        $placeTypeIsActive = DB::table('place_types')
            ->where('id', $data['place_type_id'])
            ->where('is_active', true)
            ->where('is_searchable', true)
            ->exists();

        if (! $placeTypeIsActive) {
            return back()->withInput()->withErrors(['place_type_id' => __('places.suggest.inactive_type')]);
        }

        $tombstoneCheck = app(PlaceTombstoneService::class)->check(
            (float) $data['latitude'],
            (float) $data['longitude'],
            (int) $data['place_type_id'],
        );
        $canOverrideTombstone = app(PermissionService::class)->can($request->user(), 'places.create_direct');

        if ($tombstoneCheck['status'] === PlaceTombstoneService::BLOCKED_SAME_TYPE && ! $canOverrideTombstone) {
            return back()
                ->withInput()
                ->withErrors(['suggestion' => __('places.suggest.tombstone_blocked')]);
        }

        $publishDirectly = $data['intent'] === 'submit' && $this->canPublishDirectly($request);

        if (! app(PermissionService::class)->can($request->user(), 'admin.access')) {
            $replay = $this->recentPlaceReplay($request, $data);

            if ($replay) {
                if ($replay->publication_status === 'draft') {
                    return redirect()
                        ->route('places.drafts.features.edit', $replay->id)
                        ->with('ui_toast', __('places.suggest.duplicate_replay'));
                }

                return redirect()
                    ->route('places.suggest.create')
                    ->with('ui_toast', __('places.suggest.duplicate_replay'));
            }
        }

        try {
            $result = DB::transaction(function () use ($request, $data, $publishDirectly): array {
                $now = now();
                $isDraft = $data['intent'] === 'draft';
                $publicationStatus = $isDraft ? 'draft' : ($publishDirectly ? 'published' : 'pending');
                $slug = $this->uniqueSlug($data['name']);

                $placeId = DB::table('places')->insertGetId([
                    'place_type_id' => $data['place_type_id'],
                    'name' => trim($data['name']),
                    'slug' => $slug,
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'publication_status' => $publicationStatus,
                    'legal_status' => 'unclear',
                    'opening_status' => 'unclear',
                    'is_active' => true,
                    'internal_comment' => $isDraft
                        ? 'Vom Nutzer als Entwurf angelegt.'
                        : ($publishDirectly
                            ? 'Von einem Administrator direkt veröffentlicht.'
                            : 'Vom Nutzer als neuer Platz vorgeschlagen; wartet auf Moderation.'),
                    'created_by' => $request->user()->id,
                    'approved_by' => $publishDirectly ? $request->user()->id : null,
                    'approved_at' => $publishDirectly ? $now : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $this->storeInitialAddress($placeId, $data, $now);

                $changeRequestId = null;
                if (! $isDraft && ! $publishDirectly) {
                    $changeRequestId = $this->createPublicationRequest(
                        $placeId,
                        $request->user()->id,
                        $data['comment'] ?? null,
                        $now,
                    );
                }

                DB::table('audit_logs')->insert([
                    'user_id' => $request->user()->id,
                    'entity_type' => 'place',
                    'entity_id' => $placeId,
                    'action' => $isDraft
                        ? 'place_draft_created'
                        : ($publishDirectly ? 'place_created_by_admin' : 'place_suggested'),
                    'source' => $publishDirectly ? 'admin' : 'user',
                    'old_values' => null,
                    'new_values' => json_encode([
                        'name' => trim($data['name']),
                        'place_type_id' => (int) $data['place_type_id'],
                        'latitude' => (float) $data['latitude'],
                        'longitude' => (float) $data['longitude'],
                        'publication_status' => $publicationStatus,
                        'change_request_id' => $changeRequestId,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'internal_comment' => null,
                    'created_at' => $now,
                ]);

                return [
                    'place_id' => $placeId,
                    'change_request_id' => $changeRequestId,
                    'name' => trim($data['name']),
                    'is_draft' => $isDraft,
                    'is_published' => $publishDirectly,
                    'slug' => $slug,
                ];
            });
        } catch (RuntimeException $e) {
            report($e);

            return back()->withInput()->withErrors(['suggestion' => __('places.suggest.workflow_unavailable')]);
        }

        if (! $result['is_draft'] && ! $result['is_published']) {
            app(AbuseProtectionService::class)->inspectNewPlace($request, (int) $result['place_id']);
        }

        if (
            $canOverrideTombstone
            && $tombstoneCheck['status'] === PlaceTombstoneService::BLOCKED_SAME_TYPE
        ) {
            DB::table('audit_logs')->insert([
                'user_id' => $request->user()->id,
                'entity_type' => 'place',
                'entity_id' => (int) $result['place_id'],
                'action' => 'place_tombstone_override',
                'source' => 'admin',
                'old_values' => json_encode([
                    'tombstone_place_id' => $tombstoneCheck['match']['place_id'],
                    'distance_m' => $tombstoneCheck['match']['distance_m'],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'new_values' => json_encode([
                    'created_place_id' => (int) $result['place_id'],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'internal_comment' => null,
                'created_at' => now(),
            ]);
        }

        app(UsageAnalyticsService::class)->track(
            $request,
            $result['is_draft']
                ? 'place_draft_saved'
                : ($result['is_published'] ? 'place_created_directly' : 'place_suggestion_submitted'),
            'place_suggestions',
            'place',
            (int) $result['place_id'],
        );

        if ($result['is_published']) {
            $this->recordDirectPublication(
                (int) $request->user()->id,
                (int) $result['place_id'],
                (string) $result['name'],
            );

            return redirect()
                ->route('places.show', $result['slug'])
                ->with('ui_dialog', [
                    'variant' => 'success',
                    'message' => __('places.suggest.status_published', ['name' => $result['name']]),
                ]);
        }

        if ($result['is_draft']) {
            return redirect()
                ->route('places.drafts.features.edit', $result['place_id'])
                ->with('ui_dialog', [
                    'variant' => 'success',
                    'message' => __('places.draft.status_created'),
                ]);
        }

        return redirect()
            ->route('places.suggest.create')
            ->with('ui_dialog', [
                'variant' => 'success',
                'message' => __('places.suggest.status_submitted', [
                    'name' => $result['name'],
                    'id' => $result['change_request_id'],
                ]),
            ]);
    }

    public function nearbyDuplicates(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'name' => ['nullable', 'string', 'max:255'],
            'exclude_place_id' => ['nullable', 'integer', 'exists:places,id'],
        ]);

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];
        $latitudeDelta = 0.01;
        $longitudeDelta = 0.01 / max(0.2, cos(deg2rad($latitude)));

        $candidates = DB::table('places as p')
            ->join('place_types as pt', 'pt.id', '=', 'p.place_type_id')
            ->where('p.is_active', true)
            ->whereIn('p.publication_status', ['published', 'pending'])
            ->when(isset($data['exclude_place_id']), fn ($query) => $query->where('p.id', '!=', (int) $data['exclude_place_id']))
            ->whereBetween('p.latitude', [$latitude - $latitudeDelta, $latitude + $latitudeDelta])
            ->whereBetween('p.longitude', [$longitude - $longitudeDelta, $longitude + $longitudeDelta])
            ->limit(50)
            ->get([
                'p.id',
                'p.name',
                'p.slug',
                'p.latitude',
                'p.longitude',
                'p.publication_status',
                'pt.slug as place_type_slug',
            ]);

        $needle = mb_strtolower(trim((string) ($data['name'] ?? '')));

        $matches = $candidates
            ->map(function ($candidate) use ($latitude, $longitude, $needle) {
                $distance = $this->distanceMeters(
                    $latitude,
                    $longitude,
                    (float) $candidate->latitude,
                    (float) $candidate->longitude,
                );

                $similarity = 0.0;
                if ($needle !== '') {
                    similar_text($needle, mb_strtolower((string) $candidate->name), $similarity);
                }

                return [
                    'id' => (int) $candidate->id,
                    'name' => (string) $candidate->name,
                    'slug' => (string) $candidate->slug,
                    'publication_status' => (string) $candidate->publication_status,
                    'place_type_slug' => (string) $candidate->place_type_slug,
                    'distance_m' => (int) round($distance),
                    'name_similarity' => round($similarity, 1),
                ];
            })
            ->filter(fn (array $candidate) => $candidate['distance_m'] <= 750)
            ->sort(function (array $a, array $b): int {
                $similarityOrder = $b['name_similarity'] <=> $a['name_similarity'];

                return $similarityOrder !== 0
                    ? $similarityOrder
                    : ($a['distance_m'] <=> $b['distance_m']);
            })
            ->take(8)
            ->values();

        return response()->json(['matches' => $matches]);
    }

    public function editDraft(Request $request, int $place): RedirectResponse
    {
        $draft = $this->draftForUser($request, $place);

        return redirect()->route('places.drafts.features.edit', $draft->id);
    }

    public function updateDraft(Request $request, int $place): RedirectResponse
    {
        $draft = $this->draftForUser($request, $place);

        return redirect()->route('places.drafts.features.edit', $draft->id);
    }

    public function editDraftFeatures(
        Request $request,
        int $place,
        FeatureWorkflowService $workflowService,
        PlaceTypeFeatureService $placeTypeFeatures,
    ): View {
        $draft = $this->draftForUser($request, $place);
        $placeTypeSlug = (string) DB::table('place_types')->where('id', $draft->place_type_id)->value('slug');
        $groups = $placeTypeFeatures->quickGroups(
            $workflowService->groupedForPlace($draft->id),
            $placeTypeSlug,
        );

        return view('places.draft-features', [
            'place' => $draft,
            'groups' => $groups,
            'workflowService' => $workflowService,
            'canPublishDirectly' => $this->canPublishDirectly($request),
        ]);
    }

    public function updateDraftFeatures(
        Request $request,
        int $place,
        FeatureWorkflowService $workflowService,
        PlaceTypeFeatureService $placeTypeFeatures,
    ): RedirectResponse {
        $draft = $this->draftForUser($request, $place);
        $workflows = $workflowService->workflowsForPlace($draft->id)
            ->filter(fn ($workflow) => ($workflow->visibility ?? 'extended') === 'standard')
            ->values();
        $validated = $workflowService->validate($request, $workflows);
        $intent = $request->validate(['intent' => ['required', 'in:save,submit']])['intent'];

        DB::transaction(function () use ($request, $draft, $workflowService, $workflows, $validated): void {
            $workflowService->saveDraft($draft->id, $request->user()->id, $workflows, $validated);
        });

        if ($intent === 'submit') {
            return $this->submitDraft($request, $draft->id);
        }

        app(UsageAnalyticsService::class)->track(
            $request,
            'place_draft_saved',
            'place_suggestions',
            'place',
            (int) $draft->id,
        );

        return redirect()
            ->route('places.drafts.features.edit', $draft->id)
            ->with('ui_toast', __('feature_workflow.status_saved'));
    }

    public function reviewDraft(Request $request, int $place): RedirectResponse
    {
        $draft = $this->draftForUser($request, $place);

        return redirect()->route('places.drafts.features.edit', $draft->id);
    }

    public function submitDraft(Request $request, int $place): RedirectResponse
    {
        $draft = $this->draftForUser($request, $place);
        $data = $request->validate([
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $publishDirectly = $this->canPublishDirectly($request);

        try {
            $changeRequestId = DB::transaction(function () use ($request, $draft, $data, $publishDirectly): ?int {
                $now = now();
                $publicationStatus = $publishDirectly ? 'published' : 'pending';

                DB::table('places')
                    ->where('id', $draft->id)
                    ->where('publication_status', 'draft')
                    ->update([
                        'publication_status' => $publicationStatus,
                        'approved_by' => $publishDirectly ? $request->user()->id : null,
                        'approved_at' => $publishDirectly ? $now : null,
                        'internal_comment' => $publishDirectly
                            ? 'Entwurf wurde von einem Administrator direkt veröffentlicht.'
                            : 'Entwurf wurde vom Ersteller zur Moderation eingereicht.',
                        'updated_at' => $now,
                    ]);

                $changeRequestId = $publishDirectly
                    ? null
                    : $this->createPublicationRequest(
                        $draft->id,
                        $request->user()->id,
                        $data['comment'] ?? null,
                        $now,
                    );

                DB::table('audit_logs')->insert([
                    'user_id' => $request->user()->id,
                    'entity_type' => 'place',
                    'entity_id' => $draft->id,
                    'action' => $publishDirectly ? 'place_draft_published_by_admin' : 'place_draft_submitted',
                    'source' => $publishDirectly ? 'admin' : 'user',
                    'old_values' => json_encode(['publication_status' => 'draft'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'new_values' => json_encode([
                        'publication_status' => $publicationStatus,
                        'change_request_id' => $changeRequestId,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'internal_comment' => null,
                    'created_at' => $now,
                ]);

                return $changeRequestId;
            });
        } catch (RuntimeException $e) {
            report($e);

            return back()->withInput()->withErrors(['draft' => __('places.suggest.workflow_unavailable')]);
        }

        if (! $publishDirectly) {
            app(AbuseProtectionService::class)->inspectNewPlace($request, (int) $draft->id);
        }

        app(UsageAnalyticsService::class)->track(
            $request,
            $publishDirectly ? 'place_created_directly' : 'place_suggestion_submitted',
            'place_suggestions',
            'place',
            (int) $draft->id,
        );

        if ($publishDirectly) {
            $this->recordDirectPublication(
                (int) $request->user()->id,
                (int) $draft->id,
                (string) $draft->name,
            );

            return redirect()
                ->route('places.show', $draft->slug)
                ->with('ui_dialog', [
                    'variant' => 'success',
                    'message' => __('places.suggest.status_published', ['name' => $draft->name]),
                ]);
        }

        return redirect()
            ->route('places.suggest.create')
            ->with('ui_dialog', [
                'variant' => 'success',
                'message' => __('places.draft.status_submitted', [
                    'name' => $draft->name,
                    'id' => $changeRequestId,
                ]),
            ]);
    }


    private function recentPlaceReplay(Request $request, array $data): ?object
    {
        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];
        $epsilon = 0.000001;

        return DB::table('places')
            ->where('created_by', $request->user()->id)
            ->where('place_type_id', (int) $data['place_type_id'])
            ->where('name', trim((string) $data['name']))
            ->whereIn('publication_status', ['draft', 'pending'])
            ->whereBetween('latitude', [$latitude - $epsilon, $latitude + $epsilon])
            ->whereBetween('longitude', [$longitude - $epsilon, $longitude + $epsilon])
            ->where('created_at', '>=', now()->subMinutes(10))
            ->orderByDesc('id')
            ->first(['id', 'publication_status']);
    }

    private function canPublishDirectly(Request $request): bool
    {
        return app(PermissionService::class)->can($request->user(), 'places.create_direct');
    }

    private function recordDirectPublication(int $userId, int $placeId, string $placeName): void
    {
        $user = \App\Models\User::find($userId);
        if ($user && ! DB::table('place_history')->where('place_id', $placeId)->where('action', 'place_created')->exists()) {
            app(\App\Services\PlaceHistoryService::class)->addUser(
                $placeId,
                $user,
                'place_created',
                __('place_profile.history.actions.place_created'),
                ['submitted_at' => now()->toIso8601String(), 'approved_at' => now()->toIso8601String()],
            );
        }

        $publication = (object) [
            'submitted_by' => $userId,
            'place_id' => $placeId,
            'target_table' => 'places',
            'target_field' => 'publication_status',
            'proposed_value' => json_encode('published', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        app(XpService::class)->awardApprovedPlaceChange($publication, $placeId, $placeName);
        app(BadgeService::class)->recordApprovedPlaceChange($publication, $placeId, $placeName);
    }

    private function draftForUser(Request $request, int $place): object
    {
        $draft = DB::table('places')
            ->where('id', $place)
            ->where('created_by', $request->user()->id)
            ->where('publication_status', 'draft')
            ->where('is_active', true)
            ->first(['id', 'name', 'slug', 'place_type_id', 'latitude', 'longitude', 'publication_status']);

        abort_unless($draft, 404);

        return $draft;
    }

    private function saveDraftDetails(int $placeId, int $userId, array $data, $now): void
    {
        $translationValues = [
            'description' => $data['description'] ?? null,
            'directions' => $data['directions'] ?? null,
            'access_information' => $data['access_information'] ?? null,
        ];

        $translation = DB::table('place_translations')
            ->where('place_id', $placeId)
            ->where('locale', app()->getLocale())
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->first();

        if ($translation) {
            DB::table('place_translations')->where('id', $translation->id)->update($translationValues + [
                'updated_at' => $now,
            ]);
        } else {
            DB::table('place_translations')->insert($translationValues + [
                'place_id' => $placeId,
                'locale' => app()->getLocale(),
                'is_active' => true,
                'version_valid_from' => $now,
                'version_valid_until' => null,
                'internal_comment' => 'Vom Ersteller im Entwurf gepflegt.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $detailValues = [
            'operator_name' => $data['operator_name'] ?? null,
            'pitch_count' => $data['pitch_count'] ?? null,
        ];

        $details = DB::table('place_details')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->first();

        if ($details) {
            DB::table('place_details')->where('id', $details->id)->update($detailValues + [
                'updated_at' => $now,
            ]);
        } else {
            DB::table('place_details')->insert($detailValues + [
                'place_id' => $placeId,
                'is_active' => true,
                'version_valid_from' => $now,
                'version_valid_until' => null,
                'internal_comment' => 'Vom Ersteller im Entwurf gepflegt.',
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function createPublicationRequest(int $placeId, int $userId, ?string $comment, $now): int
    {
        $publicationFieldId = DB::table('suggestable_fields')
            ->where('target_table', 'places')
            ->where('target_field', 'publication_status')
            ->where('is_suggestable', true)
            ->where('allow_update', true)
            ->where('is_active', true)
            ->value('id');

        if (! $publicationFieldId) {
            throw new RuntimeException('Missing workflow field places.publication_status. Run SuggestableFieldSeeder.');
        }

        return DB::table('change_requests')->insertGetId([
            'group_uuid' => null,
            'place_id' => $placeId,
            'suggestable_field_id' => $publicationFieldId,
            'target_record_id' => $placeId,
            'operation' => 'update',
            'original_value' => json_encode('pending', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'proposed_value' => json_encode('published', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'pending',
            'submitted_by' => $userId,
            'submitted_at' => $now,
            'user_comment' => $comment ?: 'Neuer Platz zur Prüfung eingereicht.',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'moderator_comment' => null,
            'result_record_id' => null,
            'applied_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function storeInitialAddress(int $placeId, array $data, $now): void
    {
        $hasAddress = collect([
            $data['country_code'] ?? null,
            $data['postal_code'] ?? null,
            $data['city'] ?? null,
            $data['street'] ?? null,
            $data['house_number'] ?? null,
            $data['address_addition'] ?? null,
        ])->contains(fn ($value) => filled($value));

        if (! $hasAddress) {
            return;
        }

        DB::table('place_addresses')->insert([
            'place_id' => $placeId,
            'country_code' => isset($data['country_code']) ? Str::upper($data['country_code']) : null,
            'region_id' => null,
            'postal_code' => $data['postal_code'] ?? null,
            'city' => $data['city'] ?? null,
            'street' => $data['street'] ?? null,
            'house_number' => $data['house_number'] ?? null,
            'address_addition' => $data['address_addition'] ?? null,
            'is_active' => true,
            'version_valid_from' => $now,
            'version_valid_until' => null,
            'internal_comment' => 'Mit neuem Platzvorschlag angelegt.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function placeTypes()
    {
        $locale = app()->getLocale();

        return DB::table('place_types as pt')
            ->leftJoin('translations as tr', function ($join) use ($locale) {
                $join->on('tr.entity_id', '=', 'pt.id')
                    ->where('tr.entity_type', 'place_type')
                    ->where('tr.field', 'name')
                    ->where('tr.locale', $locale)
                    ->where('tr.is_active', true);
            })
            ->leftJoin('translations as td', function ($join) use ($locale) {
                $join->on('td.entity_id', '=', 'pt.id')
                    ->where('td.entity_type', 'place_type')
                    ->where('td.field', 'description')
                    ->where('td.locale', $locale)
                    ->where('td.is_active', true);
            })
            ->leftJoin('translations as tde', function ($join) {
                $join->on('tde.entity_id', '=', 'pt.id')
                    ->where('tde.entity_type', 'place_type')
                    ->where('tde.field', 'description')
                    ->where('tde.locale', 'en')
                    ->where('tde.is_active', true);
            })
            ->where('pt.is_active', true)
            ->where('pt.is_searchable', true)
            ->orderBy('pt.sort_order')
            ->orderBy('pt.slug')
            ->get([
                'pt.id',
                'pt.slug',
                DB::raw('COALESCE(tr.value, pt.slug) as label'),
                DB::raw('COALESCE(td.value, tde.value) as description'),
            ]);
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'place';
        }

        $slug = $base;
        $suffix = 2;

        while (DB::table('places')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
